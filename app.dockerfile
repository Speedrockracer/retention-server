FROM php:7.2-fpm

RUN apt-get update
# RUN apt-get update && apt-get install -y libmcrypt-dev \
#     mysql-client libmagickwand-dev --no-install-recommends \
#     && docker-php-ext-install mcrypt pdo_mysql
