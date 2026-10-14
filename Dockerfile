# Image aplikasi Studio Musik ITERA (Laravel 13 + Apache + PHP 8.5).
#   docker compose up -d --build        → lihat README bagian "Docker"
#
# Build arg:
#   COMPOSER_DEV=true   sertakan dependensi dev (Faker, PHPUnit) — untuk lokal,
#                       DemoSeeder, dan `php artisan test` di dalam container.

# ---------------------------------------------------------------------------
# 1) Aset frontend (Vite + Tailwind)
# ---------------------------------------------------------------------------
FROM node:24-alpine AS aset
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
# Tailwind memindai app/ (enum berisi nama warna badge).
COPY app ./app
RUN npm run build

# ---------------------------------------------------------------------------
# 2) Dependensi PHP (vendor/)
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor
ARG COMPOSER_DEV=false
WORKDIR /app

COPY composer.json composer.lock ./
RUN set -eux; \
    if [ "$COMPOSER_DEV" = "true" ]; then FLAG=""; else FLAG="--no-dev"; fi; \
    composer install $FLAG --no-interaction --no-scripts --no-autoloader --prefer-dist \
        --ignore-platform-req=ext-gd --ignore-platform-req=ext-intl --ignore-platform-req=ext-zip

COPY . .
RUN set -eux; \
    if [ "$COMPOSER_DEV" = "true" ]; then FLAG=""; else FLAG="--no-dev"; fi; \
    composer dump-autoload $FLAG --optimize --no-scripts

# ---------------------------------------------------------------------------
# 3) Runtime
# ---------------------------------------------------------------------------
FROM php:8.5-apache AS app

ARG UID=1000
ARG GID=1000

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev libicu-dev unzip; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    # OPcache sudah menjadi bagian inti PHP sejak 8.5, tidak perlu dipasang.
    docker-php-ext-install -j"$(nproc)" pdo_mysql gd intl zip bcmath exif; \
    a2enmod rewrite headers; \
    rm -rf /var/lib/apt/lists/*; \
    # Jalankan Apache sebagai www-data pada port 8080 (tanpa root).
    usermod -u "$UID" www-data; groupmod -g "$GID" www-data; \
    sed -ri 's/Listen 80$/Listen 8080/' /etc/apache2/ports.conf; \
    chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-studio.ini
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/keamanan.conf /etc/apache2/conf-enabled/zz-keamanan.conf
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/studio-entrypoint

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=aset /app/public/build ./public/build

RUN set -eux; \
    rm -rf public/hot bootstrap/cache/*.php storage/logs/*.log; \
    mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions \
             storage/framework/views storage/logs public/storage bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache public/storage; \
    # .env hanya berisi APP_KEY yang ditulis entrypoint ke volume storage;
    # konfigurasi lain datang dari variabel lingkungan container.
    ln -sfn storage/app/.env.docker .env

USER www-data

EXPOSE 8080

HEALTHCHECK --interval=15s --timeout=5s --start-period=60s --retries=5 \
    CMD curl -fsS http://127.0.0.1:8080/api/health || exit 1

ENTRYPOINT ["studio-entrypoint"]
CMD ["apache2-foreground"]
