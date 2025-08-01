FROM php:8.4-apache

# Enable Apache mod_rewrite (for pretty URLs if needed)
RUN a2enmod rewrite

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy project files into the container
COPY . /var/www/html/

# Give www-data user permissions
RUN chown -R www-data:www-data /var/www/html

# Recommended: Set Apache document root if /public used
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

EXPOSE 80
