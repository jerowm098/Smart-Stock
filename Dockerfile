FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    libsqlite3-dev \
    && docker-php-ext-configure pdo_sqlite --with-pdo-sqlite=/usr/local \
    && docker-php-ext-install pdo mbstring exif pcntl bcmath zip intl gd

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy all project files
COPY . /var/www/html/

# Change working directory to Laravel public folder
WORKDIR /var/www/html

# Create required Laravel directories
RUN mkdir -p storage/framework/{sessions,views,cache,data} storage/logs bootstrap/cache public/storage

# Set permissions
RUN chmod -R 775 storage bootstrap/cache

# Install Composer dependencies (production only, skip scripts to avoid errors)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --ignore-platform-reqs

# Generate a new APP_KEY if not already set
RUN php artisan key:generate --force || true

# Configure Apache to serve from public/ directory
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN echo '<Directory /var/www/html/public>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    AllowOverride All' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    Require all granted' >> /etc/apache2/sites-available/000-default.conf \
    && echo '</Directory>' >> /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
