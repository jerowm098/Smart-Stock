FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module (required for Laravel .htaccess)
RUN a2enmod rewrite

# Install PHP extensions
RUN docker-php-ext-configure pdo_sqlite --with-pdo-sqlite=/usr/local \
    && docker-php-ext-install pdo pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (for better layer caching)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy entire project
COPY . .

# Set correct document root for Apache (Laravel serves from public/)
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/html>/,/<\/Directory>/ s|AllowOverride None|AllowOverride All|' /etc/apache2/sites-available/000-default.conf

# Set DirectoryIndex
RUN sed -i '/<Directory \/var\/www\/html\/public>/a\    DirectoryIndex index.php index.html' /etc/apache2/sites-available/000-default.conf

# Fix permissions for Laravel storage and cache directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
