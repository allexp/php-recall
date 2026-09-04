FROM composer:2 AS dependencies

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.3-cli-alpine

RUN apk add --no-cache ca-certificates libxml2-dev sqlite-dev \
    && docker-php-ext-install dom pdo_sqlite

WORKDIR /app
COPY . /app
COPY --from=dependencies /app/vendor /app/vendor

RUN mkdir -p /app/var/cache /app/var/log /app/data \
    && chown -R www-data:www-data /app/var /app/data

USER www-data
EXPOSE 8080

ENV APP_ENV=prod
ENV APP_DEBUG=0

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "public/router.php"]
