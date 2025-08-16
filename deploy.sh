#!/bin/bash
# Deploy script for Laravel on aaPanel (AWS)
# Usage: Run via SSH after code upload

cd /www/wwwroot/backend.connectinc.app || exit 1

# Exit on any error
set -e

echo "Starting deployment..."

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Optimize Laravel cache
echo "Optimizing..."
php artisan optimize

# Clear and cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Restart queue workers
php artisan queue:restart

# Reload web server
sudo systemctl reload nginx

echo "Deployment completed successfully!"
