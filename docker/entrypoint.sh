#!/bin/sh
set -e

# ==================================================================
# Entrypoint - ang unang tumatakbo pag-start ng container.
# Ginagawa nito ang mga bagay na hindi pwedeng gawin habang
# bini-build ang image (kasi wala pa ang Render env vars noon).
# ==================================================================

echo ">>> [entrypoint] Starting Laravel + Supabase container setup..."

# ---------------------------------------------------------------
# 1) PORT - inject ng Render (e.g. 10000). I-default sa 80 kung wala.
# ---------------------------------------------------------------
PORT="${PORT:-80}"
echo ">>> Using PORT=${PORT}"

# ---------------------------------------------------------------
# 2) Writable runtime directories (ephemeral filesystem ng Render)
# ---------------------------------------------------------------
mkdir -p /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/framework/cache/data \
         /app/storage/framework/testing \
         /app/storage/logs \
         /app/bootstrap/cache
chmod -R 777 /app/storage /app/bootstrap/cache

# ---------------------------------------------------------------
# 3) APP_KEY - kailangan ng Laravel para sa encryption/sessions.
#    Magsa-generate kung wala pa (lalo na sa free plan / blueprint).
# ---------------------------------------------------------------
if [ -z "${APP_KEY}" ]; then
    echo ">>> APP_KEY not set - generating one..."
    APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    export APP_KEY
    # Minimum .env file para may APP_KEY sa susunod na restart
    touch /app/.env
    sed -i "/^APP_KEY=/d" /app/.env 2>/dev/null || true
    echo "APP_KEY=${APP_KEY}" >> /app/.env
fi

# ---------------------------------------------------------------
# 4) Laravel optimizations
#    package:discover   -> i-build ang bootstrap/cache/packages.php
#    config:cache       -> pinagsama-samang config files
#    route:cache        -> cached routes
#    view:cache         -> compiled Blade templates
#    (|| true = huwag i-abort ang boot kung may babala lang)
# ---------------------------------------------------------------
echo ">>> Running Laravel optimizations..."
php artisan package:discover --ansi >/dev/null 2>&1 || true
php artisan config:cache   >/dev/null 2>&1 || true
# WARNING: route:cache and view:cache are intentionally SKIPPED here.
# If you run them, the compiled route list locks in the current APP_URL
# (http://localhost from .env.example). Any later env var override for
# APP_URL would have zero effect until you manually clear the cache.
# Leaving them uncached = tiny cold-start penalty, correct HTTPS URLs.
# php artisan route:cache    >/dev/null 2>&1 || true
# php artisan view:cache     >/dev/null 2>&1 || true

# ---------------------------------------------------------------
# 5) SQLite database - i-create ang file at patakbuhin ang migrations
#    Kailangan ito para gumana ang auth (users table) at ang
#    Eloquent models (products table) sa Laravel.
# ---------------------------------------------------------------
echo ">>> Ensuring SQLite database exists..."
touch /app/database/database.sqlite
chmod 666 /app/database/database.sqlite

echo ">>> Running migrations..."
php artisan migrate --force --no-interaction 2>&1 || echo ">>> migrate warning: pakitingnan ang logs"

# ---------------------------------------------------------------
# 6) Nginx config - palitan ang ${PORT} sa template
#    envsubst = tool na nagpapalit ng environment placeholders
# ---------------------------------------------------------------
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf
echo ">>> Nginx config generated for PORT=${PORT}"

# ---------------------------------------------------------------
# 6) Simulan ang main process (supervisord ang nasa CMD)
# ---------------------------------------------------------------
echo ">>> Starting supervisord (nginx + php-fpm)..."
exec "$@"