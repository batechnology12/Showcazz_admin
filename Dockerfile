FROM php:8.2-apache

# **CRITICAL: Configure Apache FIRST - disable all MPMs, enable only prefork**
RUN a2dismod mpm_event mpm_worker
RUN a2enmod mpm_prefork

# Install PHP extensions for PostgreSQL and Laravel
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        mbstring \
        exif \
        pcntl

# Enable Apache modules
RUN a2enmod rewrite headers

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies (skip scripts to avoid database errors)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# **CRITICAL: Configure Apache for Railway**
# 1. Change port to 8080
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf

# 2. Update all VirtualHost configurations
RUN sed -i 's/:80>/:8080>/g' /etc/apache2/sites-available/*.conf

# 3. Set DocumentRoot to Laravel's public directory
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/*.conf

# 4. Configure directory permissions
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

# Startup command
CMD sh -c " \
    echo 'Starting Laravel application...' && \
    # Create .env if missing \
    if [ ! -f .env ]; then \
        echo 'Creating .env file...' && \
        cp .env.example .env; \
    fi && \
    # Generate APP_KEY if missing \
    if ! grep -q '^APP_KEY=base64:' .env || [ -z \"\$(grep '^APP_KEY=base64:' .env | cut -d= -f2)\" ]; then \
        echo 'Generating application key...' && \
        php artisan key:generate --force; \
    fi && \
    # Run database migrations \
    echo 'Running migrations...' && \
    php artisan migrate --force --no-interaction || echo 'Migrations may have failed, continuing...' && \
    # Cache configurations \
    echo 'Caching configurations...' && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    # Start Apache \
    echo 'Starting Apache on port 8080...' && \
    apache2-foreground \
"