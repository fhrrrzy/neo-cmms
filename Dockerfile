# ====================================
# Stage 1: Composer Dependencies
# ====================================
FROM webdevops/php:8.4-alpine AS composer-deps

WORKDIR /app

# Copy Composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# ====================================
# Stage 2: Frontend Builder
# ====================================
FROM webdevops/php:8.4-alpine AS frontend-builder

WORKDIR /app

# Install Node.js and pnpm
RUN apk add --no-cache nodejs npm \
    && npm install -g pnpm

# Copy Composer dependencies from previous stage
COPY --from=composer-deps /app/vendor ./vendor

# Copy package files
COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

# Copy necessary files for Wayfinder (needs Laravel context)
COPY artisan ./
COPY composer.json composer.lock ./
COPY env.docker.example .env
COPY database ./database
COPY bootstrap ./bootstrap
COPY config ./config
COPY routes ./routes
COPY app ./app
COPY resources ./resources
COPY public ./public
COPY vite.config.ts tsconfig.json ./

# Ensure Laravel has sane defaults for build-time artisan usage
ENV APP_ENV=production \
    APP_DEBUG=false \
    CACHE_DRIVER=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=sync \
    VIEW_COMPILED_PATH=/app/storage/framework/views

# Setup Laravel environment and build frontend assets
RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install -j$(nproc) pdo_sqlite \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && touch database/database.sqlite \
    && chmod -R 777 storage database bootstrap \
    && sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env \
    && sed -i 's|DB_DATABASE=.*|DB_DATABASE=/app/database/database.sqlite|' .env \
    && php artisan key:generate --force \
    && php artisan config:clear \
    && php artisan cache:clear \
    && php artisan view:clear \
    && php artisan route:clear \
    && php artisan wayfinder:generate --with-form \
    && php artisan migrate --force --no-interaction 2>&1 || true \
    && pnpm run build

# ====================================
# Stage 3: Final Production Image
# ====================================
FROM webdevops/php:8.4-alpine

WORKDIR /var/www/html

# Install additional runtime dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    wget \
    netcat-openbsd

# Copy Composer dependencies
COPY --from=composer-deps /app/vendor ./vendor

# Copy application code
COPY . .

# Copy built frontend assets
COPY --from=frontend-builder /app/public/build ./public/build

# Create directories with proper permissions
RUN mkdir -p /var/www/html/storage/logs \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/log/supervisor \
    /var/log/nginx \
    /run/nginx \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

# Copy configurations
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/supervisor/supervisord-worker.conf /etc/supervisor/conf.d/supervisord-worker.conf
COPY docker/set-permissions.sh /usr/local/bin/set-permissions.sh
COPY docker/healthcheck.sh /usr/local/bin/healthcheck.sh
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/start.sh /usr/local/bin/start.sh

# Setup and optimize
RUN chmod +x /usr/local/bin/set-permissions.sh \
    && chmod +x /usr/local/bin/healthcheck.sh \
    && chmod +x /usr/local/bin/start.sh \
    && rm -f /etc/nginx/http.d/default.conf.default \
    && php artisan storage:link --force \
    && php artisan optimize \
    && php artisan filament:optimize

# Expose ports
EXPOSE 80 8080

# Use the startup script
CMD ["/usr/local/bin/start.sh"]
