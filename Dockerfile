FROM php:8.2-apache

COPY . /var/www/html/
RUN if [ -d "/var/www/html/web" ]; then \
        cp -r /var/www/html/web/. /var/www/html/ && \
        rm -rf /var/www/html/web; \
    fi

RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/ \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && a2enmod rewrite

EXPOSE 80