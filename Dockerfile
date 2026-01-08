FROM php:8.2-cli

# Install required dependencies
RUN apt-get update && apt-get install -y \
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
        exif

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# **FIX: Install WITH dev dependencies or install specific missing package**
RUN composer install --optimize-autoloader --no-scripts

# Alternative: Install only production + collision package
# RUN composer install --no-dev --optimize-autoloader --no-scripts && \
#     composer require nunomaduro/collision --dev --no-scripts

# Set permissions
RUN chmod -R 755 storage bootstrap/cache

EXPOSE 8080

# **FIX: Skip package discovery during startup**
CMD sh -c "\
    if [ ! -f .env ]; then \
        echo 'APP_NAME=Showcazz' > .env && \
        echo 'APP_ENV=production' >> .env && \
        echo 'APP_DEBUG=false' >> .env && \
        php artisan key:generate --force; \
    fi && \
    # Generate APP_KEY if not set \
    if ! grep -q '^APP_KEY=base64:' .env; then \
        php artisan key:generate --force; \
    fi && \
    # Run package discovery FIRST \
    php artisan package:discover --ansi || true && \
    # Run migrations \
    php artisan migrate --force --no-interaction && \
    # Cache configurations \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    # Start server \
    php -S 0.0.0.0:8080 -t public"