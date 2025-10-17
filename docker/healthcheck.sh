#!/bin/sh
# Container health check script
# Checks if both nginx and PHP-FPM are running properly

set -e

# Check if nginx is running
if ! pgrep nginx > /dev/null; then
    echo "ERROR: Nginx is not running"
    exit 1
fi

# Check if PHP-FPM is running
if ! pgrep php-fpm > /dev/null; then
    echo "ERROR: PHP-FPM is not running"
    exit 1
fi

# Check if nginx can respond to requests
if ! wget --quiet --tries=1 --spider http://127.0.0.1:80/health 2>/dev/null; then
    echo "ERROR: Nginx health endpoint not responding"
    exit 1
fi

# Check if PHP-FPM socket is accessible
if ! nc -z 127.0.0.1 9000 2>/dev/null; then
    echo "ERROR: PHP-FPM socket not accessible"
    exit 1
fi

echo "OK: All services healthy"
exit 0


