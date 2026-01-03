#!/bin/bash

# Deployment script for Popclips Laravel Application
# This script is for manual deployment on the server
# For automatic deployment, use GitHub Actions CI/CD

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

# Check if deploy.tar.gz exists (from CI/CD)
if [ -f "deploy.tar.gz" ]; then
    echo "📦 Extracting deployment artifact..."
    cp .env .env.backup 2>/dev/null || true
    tar -xzf deploy.tar.gz --overwrite
    mv .env.backup .env 2>/dev/null || true
    rm -f deploy.tar.gz
else
    # Manual deployment - need to install dependencies
    echo "📦 Installing Composer dependencies..."
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    
    # Only build if npm is available
    if command -v npm &> /dev/null; then
        echo "🏗️ Building frontend assets..."
        npm ci
        npm run build
    else
        echo "⚠️ npm not found, skipping frontend build"
    fi
fi

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
