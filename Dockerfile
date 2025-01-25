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

# Copy the entire application
COPY . .

# Install dependencies and build assets
RUN composer install --no-interaction --no-dev --prefer-dist \
    && npm ci \
    && npm run build

# Create storage directory structure and set permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && chmod -R 775 storage \
    && chown -R www-data:www-data storage \
    && chown -R www-data:www-data bootstrap/cache \
    && chown -R www-data:www-data public

# Copy and set permissions for the entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set the entrypoint
ENTRYPOINT ["entrypoint.sh"] 