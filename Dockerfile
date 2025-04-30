FROM php:8.2-apache

# 1. Fix module activation
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && docker-php-ext-install pdo_mysql

# 2. Correct a2enmod command
RUN a2enmod rewrite headers

# 3. Fix directory paths
COPY .htaccess /var/www/html/.htaccess
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html \
    && chmod 664 users.json \
    && chmod 644 error.log

EXPOSE 80
CMD ["apache2-foreground"]
