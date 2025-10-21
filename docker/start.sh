#!/bin/sh
set -e

# Startup script for the CMMS application
# This script initializes the environment and starts all required services

# Set permissions
/usr/local/bin/set-permissions.sh

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in background
nginx &

# Start Supervisor to manage all processes
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
