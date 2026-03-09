FROM php:8.1-apache

# Enable mysqli extension
RUN docker-php-ext-install mysqli

# Copy all project files to web server
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]