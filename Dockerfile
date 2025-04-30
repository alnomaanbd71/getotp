FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && docker-php-ext-install pdo_mysql

RUN a2enmod rewrite headers
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