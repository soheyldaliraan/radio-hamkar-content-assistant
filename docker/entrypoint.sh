#!/bin/bash

# Initialize storage permissions
/usr/local/bin/init-storage.sh

# Ensure build directory exists and has proper permissions
mkdir -p /var/www/html/public/build
chown -R www-data:www-data /var/www/html/public/build
chmod -R 775 /var/www/html/public/build

# Create storage structure and set permissions
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/public/storage

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/public/storage
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/public/storage

# Create storage link if it doesn't exist
php artisan storage:link

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
max_tries=30
counter=0
while ! mysql -h mysql -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -e "SELECT 1" >/dev/null 2>&1; do
    counter=$((counter + 1))
    if [ $counter -gt $max_tries ]; then
        echo "Error: MySQL did not become ready in time"
        exit 1
    fi
    echo "Waiting for MySQL... attempt $counter of $max_tries"
    sleep 2
done
echo "MySQL is ready!"

# Run migrations and create session table
echo "Running migrations..."
php artisan migrate --force
php artisan session:table
php artisan migrate --force
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Start supervisor to manage PHP-FPM and scheduler
echo "Starting supervisor..."
supervisord -c /etc/supervisor/conf.d/supervisord.conf 