# ---------- Base Image ----------
FROM php:8.2-cli

# ---------- System Dependencies ----------
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        mbstring \
        xml \
        exif \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ---------- Composer ----------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---------- Working Directory ----------
WORKDIR /var/www/html

# ---------- Copy Project ----------
COPY . .

# ---------- Install PHP Dependencies ----------
# (Railway injects ENV vars at runtime — DO NOT create .env here)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# ---------- Permissions ----------
RUN chmod -R 775 storage bootstrap/cache

# ---------- Expose Port ----------
EXPOSE 8080

# ---------- Start Laravel ----------
CMD sh -c "\
php artisan key:generate --force || true && \
php artisan migrate --force --no-interaction && \
php artisan config:clear && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
php -S 0.0.0.0:8080 -t public"
