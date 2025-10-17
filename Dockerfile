# ====================================
# Stage 1: Frontend Builder
# ====================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Install pnpm
RUN corepack enable && corepack prepare pnpm@latest --activate

# Copy package files
COPY package.json pnpm-lock.yaml ./

# Install dependencies
RUN pnpm install --frozen-lockfile

# Copy necessary Laravel files for wayfinder plugin
COPY artisan composer.json composer.lock ./
COPY bootstrap ./bootstrap
COPY config ./config
COPY routes ./routes
COPY app ./app

# Install Composer dependencies (needed for wayfinder)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache php83 php83-dom php83-xml php83-xmlwriter php83-tokenizer php83-fileinfo \
    && composer install --no-dev --no-scripts --no-interaction

# Copy source files needed for build
COPY resources ./resources
COPY public ./public
COPY vite.config.ts tsconfig.json ./

# Build frontend assets
RUN pnpm run build

# ====================================
# Stage 2: PHP Dependencies Builder
# ====================================
FROM php:8.4-fpm-alpine AS php-builder

WORKDIR /app

# Install system dependencies for PHP extensions
RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev libxml2-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip xml intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# ====================================
# Stage 3: Final Production Image
# ====================================
FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

# Install runtime dependencies + nginx + supervisor  
RUN apk add --no-cache \
    nginx \
    supervisor \
    wget \
    netcat-openbsd \
    libpng libjpeg-turbo freetype \
    libzip libxml2 icu-libs \
    # Build dependencies for PHP extensions
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev libxml2-dev icu-dev zlib-dev \
    # Install PHP extensions
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip xml intl opcache pcntl \
    # Install Redis extension via PECL
    && apk add --no-cache --virtual .redis-build-deps autoconf g++ make \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .redis-build-deps \
    # Remove other build dependencies to reduce image size
    && apk del libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev libxml2-dev icu-dev zlib-dev

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy PHP dependencies from builder
COPY --from=php-builder /app/vendor ./vendor

# Copy application code
COPY . .

# Copy built frontend assets from frontend-builder
COPY --from=frontend-builder /app/public/build ./public/build

# Create necessary directories
RUN mkdir -p /var/www/html/storage/logs \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/log/supervisor \
    /var/log/nginx \
    /run/nginx

# Copy supervisor configurations
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/supervisor/supervisord-worker.conf /etc/supervisor/conf.d/supervisord-worker.conf

# Copy and setup scripts
COPY docker/set-permissions.sh /usr/local/bin/set-permissions.sh
COPY docker/healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/set-permissions.sh \
    && chmod +x /usr/local/bin/healthcheck.sh

# Copy nginx configuration
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
RUN rm -f /etc/nginx/http.d/default.conf.default

# Run Laravel optimization commands
RUN php artisan storage:link --force \
    # && php artisan filament:optimize \
    # && php artisan optimize
# Configure PHP for production (opcache)
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Configure PHP-FPM
RUN echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 35" >> /usr/local/etc/php-fpm.d/www.conf

# Create startup script for running supervisor (manages nginx, php-fpm, reverb, scheduler, workers)
RUN echo '#!/bin/sh' > /usr/local/bin/start.sh \
    && echo 'set -e' >> /usr/local/bin/start.sh \
    && echo '/usr/local/bin/set-permissions.sh' >> /usr/local/bin/start.sh \
    && echo 'php-fpm -D' >> /usr/local/bin/start.sh \
    && echo 'nginx &' >> /usr/local/bin/start.sh \
    && echo 'exec supervisord -c /etc/supervisor/conf.d/supervisord.conf' >> /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

# Expose HTTP and WebSocket ports
EXPOSE 80 8080

# Start supervisor (which starts nginx, php-fpm, reverb, scheduler, workers)
CMD ["/usr/local/bin/start.sh"]