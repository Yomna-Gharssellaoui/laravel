
# ==========================================
# STAGE 1 : Build frontend with Node.js
# ==========================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY tailwind.config.js ./
COPY postcss.config.js ./

RUN npm run build


# ==========================================
# STAGE 2 : Install PHP dependencies
# ==========================================
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


# ==========================================
# STAGE 3 : Laravel production image
# ==========================================
FROM php:8.2-fpm-bookworm AS production

# System dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    unzip \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        bcmath \
        gd \
        intl \
        zip \
        opcache \
        pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy Laravel application
COPY . .

# Copy Composer dependencies
COPY --from=composer /app/vendor ./vendor

# Copy compiled frontend assets
COPY --from=frontend /app/public/build ./public/build

# Laravel writable directories
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Production PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

EXPOSE 9000

CMD ["php-fpm"]
