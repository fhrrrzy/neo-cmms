# Use PHP-FPM image
FROM php:8.4-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    nodejs npm \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev libxml2-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip xml intl \
    && npm install -g pnpm \
    && npm cache clean --force

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files first
COPY . /var/www/html

# Install Node dependencies
RUN pnpm install

# Install PHP dependencies (skip scripts first, then run them after)
RUN composer install --no-dev --optimize-autoloader --no-scripts
RUN composer run-script post-autoload-dump

# Build frontend assets
RUN pnpm run build

# Clean up Node.js files after build (reduces image size)
RUN rm -rf node_modules package*.json pnpm-lock.yaml \
    && pnpm store prune

# Note: Storage permissions handled by volume mounts in docker-compose.yml

# Create supervisor log directory and copy configuration
RUN mkdir -p /var/log/supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy and setup permissions script
COPY docker/set-permissions.sh /usr/local/bin/set-permissions.sh
RUN chmod +x /usr/local/bin/set-permissions.sh

# Create necessary directories (permissions handled by volumes)
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views

# Create storage link and optimize Laravel
RUN php artisan storage:link \
    && php artisan filament:optimize \
    && php artisan optimize

# Configure PHP for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Expose port
EXPOSE 9000

CMD ["php-fpm"]