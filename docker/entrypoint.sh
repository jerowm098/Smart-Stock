#!/bin/sh
set -e

# ==================================================================
# Entrypoint - ang unang tumatakbo pag-start ng container.
# Ginagawa nito ang mga bagay na hindi pwedeng gawin habang
# bini-build ang image (kasi wala pa ang Render env vars noon).
# Database: Supabase PostgreSQL only (walang SQLite).
# ==================================================================

echo ">>> [entrypoint] Starting Laravel + Supabase PostgreSQL container setup..."

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
# 4) Supabase check - i-verify na may DB_HOST/DB_PASSWORD.
#    Hindi na kailangan gumawa ng database.sqlite file.
#    NOTE: GET /home at GET /login ay walang DB query kaya dapat
#    200 kahit blanko ang DB vars. POST /login at /register ay
#    mag-500 pag blanko/mali ang DB vars o hindi pa na-migrate.
# ---------------------------------------------------------------
if [ -z "${DB_HOST}" ]; then
    echo ">>> WARNING: DB_HOST not set. Siguraduhing naka-set ang Supabase vars:"
    echo ">>> DB_CONNECTION=pgsql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_SSLMODE=require"
else
    echo ">>> Using Supabase host: ${DB_HOST}"
fi
if [ -z "${DB_PASSWORD}" ]; then
    echo ">>> WARNING: DB_PASSWORD not set. POST /login at POST /register mag-500 (SQLSTATE connection failed)."
    echo ">>> Kunin sa Supabase Dashboard > Project Settings > Database > Database password (o Reset)."
else
    echo ">>> DB_PASSWORD is set (hidden)."
fi

# ---------------------------------------------------------------
# 5) Laravel optimizations + Supabase migrate
#    MAHALAGA: kung hindi pa na-migrate ang Supabase (walang users
#    table), lahat ng may DB query ay 500: "relation users does not
#    exist". Kaya nag-migrate dito na may retry, hindi nakakamatay
#    ng container pag down ang DB (para mag-boot pa rin ang /login).
# ---------------------------------------------------------------
echo ">>> Running Laravel optimizations..."
php artisan package:discover --ansi >/dev/null 2>&1 || true
php artisan config:clear >/dev/null 2>&1 || true

echo ">>> Testing Supabase connection (pg_isready via PHP)..."
php -r '
try {
  $h=getenv("DB_HOST"); $p=getenv("DB_PORT") ?: "6543";
  $d=getenv("DB_DATABASE") ?: "postgres"; $u=getenv("DB_USERNAME") ?: "postgres";
  $pw=getenv("DB_PASSWORD") ?: "";
  if (!$h || !$pw) { echo ">>> SKIP migrate: kulang ang DB_HOST/DB_PASSWORD\n"; exit(0); }
  $dsn="pgsql:host=$h;port=$p;dbname=$d;sslmode=require;connect_timeout=8";
  new PDO($dsn, $u, $pw, [PDO::ATTR_TIMEOUT=>8]);
  echo ">>> Supabase reachable: $h:$p\n";
} catch (Throwable $e) {
  echo ">>> WARNING: Supabase not reachable yet: ".$e->getMessage()."\n";
}
' || true

echo ">>> Running migrations (Supabase)..."
php artisan migrate --force 2>&1 | head -n 50 || echo ">>> WARNING: migrate failed - itutuloy pa rin ang boot para mag-200 ang GET /login. Tingnan ang Render Logs."

php artisan config:cache   >/dev/null 2>&1 || true
# WARNING: route:cache and view:cache are intentionally SKIPPED here.
# If you run them, the compiled route list locks in the current APP_URL
# (http://localhost from .env.example). Any later env var override for
# APP_URL would have zero effect until you manually clear the cache.
# Leaving them uncached = tiny cold-start penalty, correct HTTPS URLs.

# ---------------------------------------------------------------
# 6) Nginx config - palitan ang ${PORT} sa template
# ---------------------------------------------------------------
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf
echo ">>> Nginx config generated for PORT=${PORT}"

# ---------------------------------------------------------------
# 7) Simulan ang main process (supervisord ang nasa CMD)
# ---------------------------------------------------------------
echo ">>> Starting supervisord (nginx + php-fpm)..."
exec "$@"
