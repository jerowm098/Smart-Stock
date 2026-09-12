FROM php:8.2-apache

# Enable Apache rewrite module (required for Laravel .htaccess)
RUN a2enmod rewrite

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

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

# Copy .env from example if it doesn't exist (for production builds)
RUN cp .env.example .env 2>/dev/null || true

# Generate Laravel application key
RUN php artisan key:generate

# Set correct document root for Apache (Laravel serves from public/)
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides and directory index fallback
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s|AllowOverride None|AllowOverride All|' /etc/apache2/sites-available/000-default.conf
RUN echo '<Directory /var/www/html/public>\n    DirectoryIndex index.php index.html\n</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Fix permissions for Laravel storage and cache directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
