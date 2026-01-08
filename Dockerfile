FROM php:8.2-cli

# ---------- System dependencies ----------
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
    && rm -rf /var/lib/apt/lists/*

# ---------- Composer ----------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---------- App directory ----------
WORKDIR /var/www/html
COPY . .

# ---------- Install ALL dependencies (includes Collision) ----------
RUN composer install \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# ---------- Permissions ----------
RUN chmod -R 775 storage bootstrap/cache

# ---------- Expose port ----------
EXPOSE 8080

# ---------- Start Laravel ----------
CMD sh -c "\
php artisan migrate --force --no-interaction && \
php artisan config:clear && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
php -S 0.0.0.0:8080 -t public"
