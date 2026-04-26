# syntax=docker/dockerfile:1.6
ARG PHP_VERSION=8.4

# ---------- Stage: base (PHP-FPM with extensions) ----------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS base

ARG TARGETARCH
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_MEMORY_LIMIT=512M

RUN apk add --no-cache \
        bash git curl unzip icu-dev libpq-dev libzip-dev oniguruma-dev \
        libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
        autoconf g++ make linux-headers \
        nodejs npm \
        postgresql-client \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath intl opcache pcntl pdo_pgsql zip gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && pecl clear-cache \
    && apk del autoconf g++ make linux-headers $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/* /tmp/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-webfactory.ini

WORKDIR /var/www/html

# Composer (latest)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------- Stage: app (development) -----------------------------------------
FROM base AS app

# Add a non-root user that matches typical UID/GID on Linux hosts.
# On Windows + WSL2, file permissions are flexible — root inside container is fine for dev.
RUN addgroup -g 1000 -S app \
    && adduser  -u 1000 -S -G app -h /home/app app \
    && mkdir -p /home/app/.composer /home/app/.npm \
    && chown -R app:app /home/app /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# We bind-mount the project from the host, so we don't COPY app code here.
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F"]

# ---------- Stage: prod (multi-stage build for production image) -------------
FROM base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

FROM base AS prod
COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative \
    && php artisan optimize \
    && php artisan event:cache \
    && php artisan route:cache \
    && php artisan view:cache
USER www-data
EXPOSE 9000
CMD ["php-fpm", "-F"]
