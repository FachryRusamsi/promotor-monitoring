# Stage 1: Build Node Assets (Inertia/Vue)
FROM node:20-alpine AS node-builder

WORKDIR /app
COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build


# Stage 2: Build PHP Application
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
    libpq-dev \
    nginx

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . .

# Copy built node assets from Stage 1
COPY --from=node-builder /app/public/build ./public/build

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Setup Nginx configuration
COPY docker/nginx/default.conf /etc/nginx/sites-enabled/default

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80 and 9000
EXPOSE 80 9000

# Start script
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
