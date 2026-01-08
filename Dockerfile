FROM php:8.2-apache

# **CRITICAL: Install ALL dependencies including oniguruma**
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \        # <-- This fixes the mbstring error
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

# **CRITICAL: Configure Apache properly - disable all MPMs first**
RUN a2dismod mpm_event mpm_worker mpm_prefork

# Enable only prefork MPM
RUN a2enmod mpm_prefork

# Enable required Apache modules
RUN a2enmod rewrite headers

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for better caching
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

# **CRITICAL: Configure Apache for port 8080**
# 1. Change port to 8080 in ports.conf
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf

# 2. Update all VirtualHost configurations
RUN sed -i 's/:80>/:8080>/g' /etc/apache2/sites-available/*.conf

# 3. Set DocumentRoot to Laravel's public directory
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/*.conf

# 4. Configure directory permissions for Laravel
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
    Options Indexes FollowSymLinks\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

# **Simple startup command that works**
CMD sh -c " \
    echo 'Starting Laravel application...' && \
    # Create .env if it doesn't exist \
    if [ ! -f .env ]; then \
        echo 'Creating .env file from example...' && \
        cp .env.example .env; \
    fi && \
    # Generate APP_KEY if not set \
    if ! grep -q '^APP_KEY=base64:' .env || [ -z \"\$(grep '^APP_KEY=base64:' .env | cut -d= -f2)\" ]; then \
        echo 'Generating application key...' && \
        php artisan key:generate --force; \
    fi && \
    # Wait a moment for services \
    sleep 2 && \
    # Try to run migrations (might fail if DB not ready) \
    echo 'Running database migrations...' && \
    php artisan migrate --force --no-interaction || echo 'Migrations may have failed, continuing...' && \
    # Cache configurations \
    echo 'Caching configurations...' && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    # Start Apache \
    echo 'Starting Apache on port 8080...' && \
    exec apache2-foreground \
"