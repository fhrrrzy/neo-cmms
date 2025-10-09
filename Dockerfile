# Use pre-built PHP image with extensions
FROM webdevops/php-fpm:8.4

# Set working directory
WORKDIR /var/www/html

# Install additional system dependencies
RUN apt-get update && apt-get install -y \
    supervisor \
    cron \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g pnpm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && rm -rf /tmp/* /var/tmp/* \
    && npm cache clean --force

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy package files first for better caching
COPY package.json ./
COPY composer.json composer.lock* ./

# Install Node dependencies
RUN pnpm install

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy application files (exclude unnecessary files)
COPY . /var/www/html

# Build frontend assets for production
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

# Create storage link (can be done at build time)
RUN php artisan storage:link

# Configure PHP for production (webdevops image already has optimized settings)
RUN echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini

# Expose port
EXPOSE 9000

CMD ["php-fpm"]