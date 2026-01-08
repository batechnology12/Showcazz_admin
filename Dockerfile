# Use PHP CLI 8.2
FROM php:8.2-cli

# ---------- System dependencies ----------
RUN apt-get update && apt-get install -y \
    git unzip \
    libpq-dev libzip-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libxml2-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql pgsql gd zip mbstring xml exif \
    && rm -rf /var/lib/apt/lists/*

# ---------- Composer ----------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ---------- App directory ----------
WORKDIR /var/www/html
COPY . .

# ---------- Install all dependencies ----------
RUN composer install \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

# ---------- Set permissions ----------
RUN chmod -R 775 storage bootstrap/cache

# ---------- Expose port ----------
EXPOSE 8080

# ---------- Start Laravel App (NO migrations, NO route caching) ----------
CMD sh -c "\
php artisan config:clear && \
php artisan view:clear && \
php artisan route:clear && \
php -S 0.0.0.0:8080 -t public"
