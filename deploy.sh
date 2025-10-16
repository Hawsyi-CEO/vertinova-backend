#!/bin/bash

echo "========================================="
echo "VERTINOVA BACKEND DEPLOYMENT SCRIPT"
echo "========================================="
echo ""

# Set strict mode
set -e

echo "📦 Step 1: Update dependencies..."
composer install --no-dev --optimize-autoloader

echo ""
echo "🔑 Step 2: Generate application key (if needed)..."
if ! grep -q "APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

echo ""
echo "🗄️  Step 3: Run database migrations..."
php artisan migrate --force

echo ""
echo "🧹 Step 4: Clear and cache configurations..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "⚡ Step 5: Optimize for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "🔐 Step 6: Set proper permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo ""
echo "📁 Step 7: Link storage..."
php artisan storage:link

echo ""
echo "✅ Deployment completed successfully!"
echo ""
echo "⚠️  IMPORTANT: Don't forget to:"
echo "   1. Update .env with your production database credentials"
echo "   2. Set APP_ENV=production"
echo "   3. Set APP_DEBUG=false"
echo "   4. Configure your web server (Nginx/Apache)"
echo ""
