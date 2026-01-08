FROM php:8.2-apache

# Fix Apache MPM conflict
RUN a2dismod mpm_event mpm_worker
RUN a2enmod mpm_prefork rewrite headers

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    curl \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        zip \
        gd \
        pdo_mysql \
        mbstring \
        xml \
        exif \
        pcntl \
        bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
RUN composer install --optimize-autoloader --no-scripts --no-interaction

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Change Apache port to 8080 for Railway
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

EXPOSE 8080
CMD ["apache2-foreground"]