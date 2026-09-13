# ---------- Stage 1: Build dependencies ----------
FROM php:8.3-fpm AS builder

WORKDIR /build

# Install system deps
RUN apt-get update && apt-get install -y \
    libssl-dev curl gnupg ca-certificates git \
    && docker-php-ext-install pdo pdo_mysql bcmath ctype fileinfo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy and install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# ---------- Stage 2: Production ----------
FROM php:8.3-fpm

WORKDIR /app

# Install PHP extensions and system deps
RUN apt-get update && apt-get install -y \
    libssl-dev curl gnupg ca-certificates \
    && docker-php-ext-install pdo pdo_mysql bcmath ctype fileinfo session \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=builder /usr/bin/composer /usr/bin/composer

# Copy vendor from builder
COPY --from=builder /build/vendor ./vendor

# Copy application source
COPY . .

# Create required directories with proper permissions for Render
RUN mkdir -p storage/framework/{cache,sessions,views,testing} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && ln -sf /dev/stdout storage/logs/laravel.log \
    && composer dump-autoload --optimize --no-dev

EXPOSE 9000

ENV APP_ENV=production \
    APP_DEBUG=false \
    CACHE_STORE=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=null

CMD ["php-fpm"]
