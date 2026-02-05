#!/bin/bash
set -e

# Wait for database availability (optional but recommended in prod, usually handled by orchestration or retry logic)
# Here we assume the DB is ready or Laravel will retry connection

echo "Running migrations..."
php artisan migrate --force

echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting Apache..."
exec apache2-foreground
