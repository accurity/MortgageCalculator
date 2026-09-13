FROM php:8.4-apache

# De applicatie serveert alleen public/; de rest van de Laravel-map blijft
# buiten de docroot.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
      /etc/apache2/sites-available/*.conf \
      /etc/apache2/apache2.conf \
      /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --chown=www-data:www-data . /var/www/html
WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && mkdir -p public/logos \
    && chown www-data:www-data public/logos \
    && chmod ug+rwx public/logos

EXPOSE 80
