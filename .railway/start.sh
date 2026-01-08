#!/bin/bash
set -e

echo "=== Starting Laravel on Railway ==="

# Wait for Railway to inject environment variables
echo "Waiting for environment variables..."
sleep 5

# Display database info for debugging
echo "Database connection info:"
echo "PGHOST: ${PGHOST:-Not set}"
echo "PGPORT: ${PGPORT:-Not set}"
echo "PGDATABASE: ${PGDATABASE:-Not set}"
echo "DATABASE_URL: ${DATABASE_URL:0:30}..."  # Show first 30 chars

# Create .env file if missing
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env 2>/dev/null || echo "Using default .env"
    
    # Generate APP_KEY
    php artisan key:generate --force
fi

# Update .env with Railway's database variables
if [ -n "$DATABASE_URL" ]; then
    echo "Using DATABASE_URL from Railway"
    echo "DATABASE_URL=$DATABASE_URL" >> .env
elif [ -n "$PGHOST" ]; then
    echo "Using PostgreSQL variables from Railway"
    echo "DB_CONNECTION=pgsql" >> .env
    echo "DB_HOST=$PGHOST" >> .env
    echo "DB_PORT=$PGPORT" >> .env
    echo "DB_DATABASE=$PGDATABASE" >> .env
    echo "DB_USERNAME=$PGUSER" >> .env
    echo "DB_PASSWORD=$PGPASSWORD" >> .env
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force --no-interaction

# Cache configurations
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP server
echo "Starting PHP server on port 8080..."
php -S 0.0.0.0:8080 -t public