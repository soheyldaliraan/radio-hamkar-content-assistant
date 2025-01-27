FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor \
    default-mysql-client

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy package files first
COPY package*.json vite.config.js tailwind.config.js ./

# Install npm dependencies
RUN npm ci

# Copy composer files
COPY composer.json composer.lock ./

# Install Composer dependencies
RUN composer install --no-scripts --no-autoloader --no-dev --prefer-dist

# Copy the rest of the application
COPY . .

# Generate optimized autoloader and run scripts
RUN composer dump-autoload --optimize && composer run-script post-autoload-dump

# Build frontend assets
RUN npm run build

# Create storage directory structure and set permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/public \
    && find /var/www/html/storage -type f -exec chmod 664 {} \; \
    && find /var/www/html/storage -type d -exec chmod 775 {} \;

# Copy and set permissions for the scripts
COPY docker/entrypoint.sh /usr/local/bin/
COPY docker/init-storage.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/init-storage.sh

# Create supervisor directories and set permissions
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/run \
    && chmod 777 /var/run \
    && chown -R www-data:www-data /var/log/supervisor

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set the entrypoint
ENTRYPOINT ["entrypoint.sh"] 