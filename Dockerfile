# ====================================================================
#  Multi-stage Dockerfile: Laravel 12 + Supabase + Nginx + PHP-FPM
#
#  STRUCTURE (3 builds sa loob ng 1 image):
#    stage 1 (composer)  -> installs PHP dependencies (vendor/)
#    stage 2 (node)      -> compiles Vite/Tailwind assets (public/build/)
#    stage 3 (production)-> nginx + php-fpm + supervisor (final image)
#
#  Bakit nginx + php-fpm?
#    Ang Render ay nagpapadala ng HTTP traffic. Kailangan ng web
#    server (nginx) na makakarinig sa $PORT at mag-proxy papunta sa
#    php-fpm, na siyang nagpapatakbo ng Laravel PHP code.
# ====================================================================

# ------------------------------------------------------------------
#  STAGE 1: PHP dependencies (Composer)
# ------------------------------------------------------------------
FROM composer:2 AS composer

WORKDIR /app

# Copy lang ang manifest files para ma-cache ang composer install
COPY composer.json composer.lock ./

# Install production dependencies (walang dev packages)
# allow-plugins ay naka-configure na sa composer.json, kaya
# puwede mong gamitin ang plugins (php-http/discovery etc.)
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader

# ------------------------------------------------------------------
#  STAGE 2: Frontend assets (Node + Vite + Tailwind)
# ------------------------------------------------------------------
FROM node:20-alpine AS node

WORKDIR /app

# Copy lang muna ang package files para ma-cache ang npm ci
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund

# Ngayon copy ang source at i-build
COPY . .
RUN npm run build

# ------------------------------------------------------------------
#  STAGE 3: Production image (nginx + php-fpm + supervisor)
# ------------------------------------------------------------------
FROM php:8.3-fpm

# ============ System dependencies ============
# NOTE: ctype, fileinfo, session, pdo ay naka-built-in na sa
# php:8.3-fpm image - kaya pdo_mysql at bcmath lang ang i-install
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        libssl-dev \
        ca-certificates \
        curl \
        gettext-base \
        && docker-php-ext-install pdo_mysql bcmath \
        && apt-get clean \
        && rm -rf /var/lib/apt/lists/*

# ============ Copy application files ============
WORKDIR /app

# 1) PHP vendor galing sa Composer stage
COPY --from=composer /app/vendor ./vendor

# 2) Buong source code
COPY . .

# 3) Compiled frontend assets galing sa Node stage
#    (overwrite ang mapapasamang assets, kung meron man)
COPY --from=node /app/public/build ./public/build

# ============ Nginx configuration ============
# Template na may ${PORT} placeholder -> gagawing real config
# ng entrypoint.sh gamit ang envsubst (part ng gettext-base)
COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template
RUN rm -f /etc/nginx/sites-enabled/default

# ============ PHP-FPM configuration ============
# Pool config na may clear_env=no (para makuha ng Laravel ang
# environment variables na nagmula sa Render)
#
# IMPORTANTE: i-delete ang docker.conf at zz-docker.conf ng built-in
# php image. Ang zz-docker.conf ay may "listen = 9000" na mag-o-override
# sa socket config natin (kasi zz- ang filename = huling sine-sort).
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
RUN rm -f /usr/local/etc/php-fpm.d/docker.conf \
          /usr/local/etc/php-fpm.d/zz-docker.conf

# ============ Supervisor configuration ============
# Humahawak sa dalawang process: nginx + php-fpm
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ============ Entrypoint ============
# Nagha-handle ng $PORT, APP_KEY, storage perms, at Laravel caches
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# ============ Runtime directories & permissions ============
# Writable para kay www-data (ginagamit ng php-fpm at Laravel)
RUN mkdir -p /var/log/nginx \
    && mkdir -p storage/framework/{cache/data,sessions,views,testing} \
    && mkdir -p storage/logs bootstrap/cache \
    && chown -R www-data:www-data /app \
    && chmod -R 775 storage bootstrap/cache

# Laravel logs -> stdout (para makita sa Render)
RUN ln -sf /dev/stdout storage/logs/laravel.log

# ============ Runtime ============
EXPOSE 80
ENV APP_ENV=production \
    APP_DEBUG=false \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=null \
    LOG_CHANNEL=stderr

# $@ = ["/usr/bin/supervisord","-c","/etc/supervisor/conf.d/supervisord.conf"]
# na dadaan pa sa entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
