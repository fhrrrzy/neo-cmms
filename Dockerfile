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

# Set permissions and create necessary directories
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create storage link
RUN php artisan storage:link

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