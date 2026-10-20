FROM composer:2 AS dependencies

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts --ignore-platform-req=ext-pdo_pgsql

FROM php:8.3-fpm-alpine AS php

RUN apk add --no-cache ca-certificates libpq-dev \
    && docker-php-ext-install pdo_pgsql

WORKDIR /app
COPY . /app
COPY --from=dependencies /app/vendor /app/vendor
COPY ops/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

RUN APP_ENV=prod APP_DEBUG=0 php bin/console assets:install public --no-interaction

RUN mkdir -p /app/var/cache /app/var/log /app/data \
    && chown -R www-data:www-data /app/var /app/data

ENV APP_ENV=prod
ENV APP_DEBUG=0

EXPOSE 9000

CMD ["php-fpm"]

FROM nginx:1.27-alpine AS nginx

COPY ops/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=php /app/public /app/public

EXPOSE 8080

CMD ["nginx", "-g", "daemon off;"]
