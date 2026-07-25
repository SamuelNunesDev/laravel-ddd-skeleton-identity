FROM php:8.5-fpm-bookworm

ARG APP_UID=1000
ARG APP_GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" intl opcache pcntl pdo_pgsql zip \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

RUN usermod --uid "${APP_UID}" www-data \
    && groupmod --gid "${APP_GID}" www-data

COPY --from=composer:2.10.2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/identity.ini

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/html

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

COPY --chown=www-data:www-data . .
RUN composer dump-autoload --optimize --no-interaction \
    && mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && mkdir -p "${COMPOSER_HOME}" \
    && chown -R www-data:www-data bootstrap/cache storage vendor "${COMPOSER_HOME}"

USER www-data

CMD ["php-fpm"]
