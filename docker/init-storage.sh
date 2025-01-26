#!/bin/bash

# Create storage directory structure
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/public/storage

# Set ownership
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/public/storage

# Set directory permissions
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;
find /var/www/html/public/storage -type d -exec chmod 775 {} \;

# Set file permissions
find /var/www/html/storage -type f -exec chmod 664 {} \;
find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \;
find /var/www/html/public/storage -type f -exec chmod 664 {} \; 