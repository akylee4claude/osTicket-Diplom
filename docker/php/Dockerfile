FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev libzip-dev libonig-dev libxml2-dev libldap2-dev libgmp-dev \
        libpng-dev libjpeg-dev libfreetype6-dev \
        unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        mysqli pdo_mysql intl zip mbstring xml gd gmp ldap opcache \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

COPY php.ini /usr/local/etc/php/conf.d/zz-osticket.ini

WORKDIR /var/www/html
