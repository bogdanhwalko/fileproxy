FROM php:8.2-fpm

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install \
        bcmath \
        mbstring \
        opcache \
        pdo_mysql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/entrypoint.sh /usr/local/bin/fileproxy-entrypoint

RUN chmod +x /usr/local/bin/fileproxy-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["fileproxy-entrypoint"]
CMD ["php-fpm"]
