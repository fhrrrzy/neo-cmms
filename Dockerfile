# Use pre-built PHP image with all extensions
FROM webdevops/php-official:8.4-alpine

# Set working directory
WORKDIR /var/www/html

# Install Node.js and pnpm
RUN apk add --no-cache nodejs npm \
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

# Copy and setup init script
COPY docker/init-permissions.sh /usr/local/bin/init-permissions.sh
RUN chmod +x /usr/local/bin/init-permissions.sh

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