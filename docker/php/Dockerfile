FROM php:8.2-apache

# Default to Yandex mirror to work around ISPs that block deb.debian.org
# (Fastly CDN). Override with `--build-arg DEBIAN_MIRROR=http://...` if
# behind a corporate proxy or building from another region.
ARG DEBIAN_MIRROR=http://mirror.yandex.ru

RUN set -eux; \
    if [ -f /etc/apt/sources.list.d/debian.sources ]; then \
        sed -i "s|http://deb.debian.org|${DEBIAN_MIRROR}|g; \
                s|https://deb.debian.org|${DEBIAN_MIRROR}|g; \
                s|http://security.debian.org|${DEBIAN_MIRROR}|g; \
                s|https://security.debian.org|${DEBIAN_MIRROR}|g" \
                /etc/apt/sources.list.d/debian.sources; \
    fi; \
    if [ -f /etc/apt/sources.list ]; then \
        sed -i "s|http://deb.debian.org|${DEBIAN_MIRROR}|g; \
                s|https://deb.debian.org|${DEBIAN_MIRROR}|g; \
                s|http://security.debian.org|${DEBIAN_MIRROR}|g; \
                s|https://security.debian.org|${DEBIAN_MIRROR}|g" \
                /etc/apt/sources.list; \
    fi; \
    apt-get update && apt-get install -y --no-install-recommends \
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
