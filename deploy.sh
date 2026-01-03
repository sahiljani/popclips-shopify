#!/bin/bash

# Deployment script for Popclips Laravel Application
# This script can be run manually on the server or used by CI/CD

set -e

echo "🚀 Starting deployment..."

# Navigate to project directory
cd /home/janisahil-popclips/htdocs/popclips.janisahil.com

# Enable maintenance mode
echo "🔧 Enabling maintenance mode..."
php artisan down --refresh=15 --retry=60 || true

# Pull latest changes from git
echo "📥 Pulling latest changes..."
git pull origin main

# Install PHP dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Install Node dependencies and build assets
echo "🏗️ Building frontend assets..."
npm ci
npm run build

# Run database migrations
echo "🗃️ Running database migrations..."
php artisan migrate --force

# Clear and optimize caches
echo "🧹 Clearing and caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue workers
echo "🔄 Restarting queue workers..."
php artisan queue:restart || true

# Set correct permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache

# Disable maintenance mode
echo "✅ Disabling maintenance mode..."
php artisan up

echo "🎉 Deployment completed successfully!"
