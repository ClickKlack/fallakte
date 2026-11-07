# syntax=docker/dockerfile:1

# Docker-Image für Amis Fallakte – derselbe Image-Stand dient als Webserver und als Scheduler.
# PHP-Abhängigkeiten und Frontend werden in eigenen Stufen gebaut; das fertige Image enthält
# weder Node noch Composer. Zugangsdaten gelangen nie ins Image (siehe .dockerignore),
# die Konfiguration kommt zur Laufzeit aus der .env neben der docker-compose.yml.

# --- 1. PHP-Abhängigkeiten (ohne Dev-Pakete) ---------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
        --no-scripts --no-autoloader --ignore-platform-reqs

# --- 2. Frontend (Vite-Build) ----------------------------------------------------------------
FROM node:24-alpine AS frontend
WORKDIR /app
ARG APP_NAME="Amis Fallakte"
ENV VITE_APP_NAME=${APP_NAME}
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js jsconfig.json ./
COPY resources ./resources
# app.js bindet Ziggy direkt aus vendor/ ein
COPY --from=vendor /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
RUN npm run build

# --- 3. Laufzeit: PHP 8.4 + Apache ------------------------------------------------------------
# PHP 8.4 statt 8.3, weil die composer.lock Symfony 8 (PHP >= 8.4.1) enthält.
FROM php:8.4-apache AS runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev \
    && docker-php-ext-install -j"$(nproc)" opcache pcntl zip \
    && apt-get purge -y --auto-remove \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-fallakte.ini"
COPY docker/entrypoint.sh /usr/local/bin/fallakte-entrypoint
COPY docker/artisan.sh /usr/local/bin/artisan

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build

# Autoloader und Paket-Erkennung erst hier, weil dafür der vollständige Quellcode nötig ist
COPY --from=vendor /usr/bin/composer /usr/local/bin/composer
RUN mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions \
        storage/framework/views bootstrap/cache \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && php artisan package:discover --ansi \
    && rm /usr/local/bin/composer \
    && chmod +x /usr/local/bin/fallakte-entrypoint /usr/local/bin/artisan \
    && chown -R www-data:www-data storage bootstrap/cache

# Datenbank, Logs, Sessions usw. liegen komplett unter storage/ (als Volume eingebunden)
ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/app/storage/database/fallakte.sqlite \
    LOG_CHANNEL=stack \
    LOG_STACK=single,stderr

VOLUME ["/app/storage"]
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://localhost/up >/dev/null || exit 1

ENTRYPOINT ["fallakte-entrypoint"]
CMD ["apache2-foreground"]
