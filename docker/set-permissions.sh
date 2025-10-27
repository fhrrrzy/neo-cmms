#!/bin/sh
# Set permissions for named volumes
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure all storage subdirectories exist with correct permissions
mkdir -p /var/www/html/storage/framework/{cache,sessions,views} /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Execute the original command
exec "$@"
