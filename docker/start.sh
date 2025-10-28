#!/bin/sh
set -e

# Startup script for the CMMS application
# This script initializes the environment and starts all required services

# Set permissions
/usr/local/bin/set-permissions.sh

# Clear all cache to ensure fresh routes are loaded
php artisan optimize:clear || true

# Start PHP-FPM in background as www-data user
php-fpm -D

# Start Nginx in background
nginx &

# Wait a moment for services to start
sleep 2

# Re-apply permissions after volume mounts (for Docker volumes)
/usr/local/bin/set-permissions.sh

# Optimize the application
php artisan optimize
php artisan filament:optimize

# Start Supervisor to manage all processes
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
