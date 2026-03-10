FROM php:8.1-apache

# Install MySQL PHP extension
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Set splash.php as the first page to open
RUN echo "DirectoryIndex splash.php index.php index.html home.php" > /etc/apache2/conf-enabled/directoryindex.conf

# Copy all project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]