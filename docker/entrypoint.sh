#!/bin/sh
# Entrypoint container Studio Musik ITERA.
#
# Peran container (CONTAINER_ROLE):
#   app        → migrasi (opsional), seed (opsional), lalu Apache   [bawaan]
#   queue      → worker antrean email/notifikasi
#   scheduler  → penjadwal (sinkron status, retensi jaminan, prune log)
set -eu

cd /var/www/html
ROLE="${CONTAINER_ROLE:-app}"

log() { echo "[studio-entrypoint:$ROLE] $*"; }

# --- Struktur folder (volume baru bisa kosong) ------------------------------
mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs public/storage bootstrap/cache

# --- APP_KEY persisten -------------------------------------------------------
# Kunci diambil dari STUDIO_APP_KEY / APP_KEY. Bila kosong, dibuat sekali dan
# disimpan di volume storage agar sesi/enkripsi tetap valid setelah container
# dibuat ulang. Kunci juga ditulis ke storage/app/.env.docker (di-symlink
# sebagai .env oleh Dockerfile) supaya `docker compose exec app php artisan ...`
# memakai kunci yang sama dengan Apache, worker, dan scheduler.
APP_KEY="${STUDIO_APP_KEY:-${APP_KEY:-}}"
if [ -z "$APP_KEY" ]; then
    KEYFILE=storage/app/.app_key
    if [ ! -s "$KEYFILE" ]; then
        if [ "$ROLE" = "app" ]; then
            php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$KEYFILE"
            chmod 600 "$KEYFILE"
            log "APP_KEY baru dibuat dan disimpan di $KEYFILE"
        else
            log "menunggu APP_KEY dibuat oleh container app..."
            i=0; while [ ! -s "$KEYFILE" ] && [ $i -lt 60 ]; do sleep 2; i=$((i+1)); done
        fi
    fi
    APP_KEY="$(cat "$KEYFILE")"
fi
export APP_KEY
( umask 077; printf 'APP_KEY=%s\n' "$APP_KEY" > "storage/app/.env.docker.$ROLE" )
mv -f "storage/app/.env.docker.$ROLE" storage/app/.env.docker

# --- Tunggu basis data -------------------------------------------------------
i=0
until php -r '
    try {
        new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
        exit(0);
    } catch (Throwable $e) { exit(1); }' ; do
    i=$((i+1))
    if [ $i -ge 60 ]; then log "basis data tidak dapat dihubungi"; exit 1; fi
    sleep 2
done

# --- Tugas khusus container app ---------------------------------------------
if [ "$ROLE" = "app" ]; then
    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        log "menjalankan migrasi..."
        php artisan migrate --force
    fi

    # Akun admin pertama (idempoten: tidak membuat apa pun bila admin sudah ada).
    if [ -n "${ADMIN_EMAIL:-}" ]; then
        php artisan db:seed --force
    fi

    # Data contoh hanya sekali (penanda disimpan di volume storage).
    if [ "${SEED_DEMO:-false}" = "true" ] && [ ! -f storage/app/.demo_seeded ]; then
        log "mengisi data demo..."
        php artisan db:seed --class=DemoSeeder --force && touch storage/app/.demo_seeded
    fi
fi

# --- Cache konfigurasi (produksi) -------------------------------------------
if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
else
    php artisan optimize:clear > /dev/null
fi

log "siap"
exec "$@"
