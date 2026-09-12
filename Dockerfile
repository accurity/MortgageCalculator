FROM php:8.4-apache

# De applicatie serveert alleen public/; src/ en templates/ blijven buiten de docroot.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
      /etc/apache2/sites-available/*.conf \
      /etc/apache2/apache2.conf \
      /etc/apache2/conf-available/*.conf

COPY --chown=www-data:www-data . /var/www/html

EXPOSE 80
