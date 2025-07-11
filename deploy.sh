#!/bin/bash

# Exit on any error
set -e

echo "Starting deployment..."

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Run database migrations
php artisan migrate --force

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
