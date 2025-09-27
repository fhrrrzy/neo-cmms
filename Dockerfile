# Use official PHP 8.2 image with FPM for Nginx
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies including Nginx
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm \
    nginx \
    supervisor \
    cron \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy Nginx configuration
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

# Copy PHP-FPM configuration
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Remove default Nginx site and enable our config
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies (this layer will be cached if composer files don't change)
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

# Copy package.json files for Node.js
COPY package*.json ./

# Install Node.js dependencies
RUN npm ci --only=production

# Copy application files
COPY . /var/www/html

# Complete Composer autoloader
RUN composer dump-autoload --optimize

# Build assets
RUN npm run build

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create Laravel environment file if it doesn't exist
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Generate application key
RUN php artisan key:generate

# Create storage and cache directories with proper permissions
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage

# Expose port 80
EXPOSE 80

# Start supervisor to manage Nginx, PHP-FPM and other services
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
