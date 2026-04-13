FROM php:8.2-apache

COPY . /var/www/html/

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN echo "DirectoryIndex landing.php" > /etc/apache2/conf-enabled/directoryindex.conf


EXPOSE 80