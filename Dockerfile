FROM php:8.2-apache

# Enable Apache rewrite module (required for Laravel .htaccess)
RUN a2enmod rewrite

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

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
