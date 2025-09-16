# ─── Base image & PHP extensions ───────────────────────────────────────────
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim unzip git curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    supervisor \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd pdo_mysql mbstring zip exif pcntl bcmath \
 && rm -rf /var/lib/apt/lists/*


# ─── Composer CLI ───────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ─── Working directory ──────────────────────────────────────────────────────
WORKDIR /var/www/html

# NOTE: ❌ Don’t copy src/ or run composer install here.
# The Laravel code will be mounted from host (./src),
# and dependencies installed at runtime via reset-laravel.

# ─── Supervisor ─────────────────────────────────────────────────────────────
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ─── Entrypoint ─────────────────────────────────────────────────────────────
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["sh", "-c", "php-fpm & /usr/bin/supervisord"]
