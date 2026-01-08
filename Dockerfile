FROM php:8.2-apache

# Install PostgreSQL extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
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
        pdo_pgsql \
        pgsql \
        mbstring \
        xml \
        exif \
        pcntl \
        bcmath

# Disable all MPM modules FIRST
RUN a2dismod mpm_event mpm_worker mpm_prefork || true

# Enable only prefork + required modules
RUN a2enmod mpm_prefork rewrite headers

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy only composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies WITHOUT running post-install scripts
RUN composer install --no-dev --no-autoloader --no-scripts --no-interaction

# Copy the rest of the application
COPY . .

# Now run autoloader WITHOUT scripts
RUN composer dump-autoload --no-scripts --optimize

# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Change Apache port to 8080
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf \
    && sed -i 's/:80/:8080/g' /etc/apache2/sites-available/*.conf

# Copy .env.production as base
COPY .env.production .env

EXPOSE 8080

# Create startup script
COPY .railway/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]