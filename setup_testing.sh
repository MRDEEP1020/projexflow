#!/bin/bash
# ═══════════════════════════════════════════════════════════════════
# PROJEXFLOW — TEST SETUP SCRIPT
# Run this once from your project root before any testing
# ═══════════════════════════════════════════════════════════════════

echo "▶ Step 1: Install Pest + plugins"
composer remove phpunit/phpunit --dev -n 2>/dev/null || true
composer require pestphp/pest --dev --with-all-dependencies
composer require pestphp/pest-plugin-laravel --dev
composer require pestphp/pest-plugin-livewire --dev

echo "▶ Step 2: Initialize Pest"
php artisan pest:install

echo "▶ Step 3: Create test database"
touch database/testing.sqlite

echo "▶ Step 4: Copy .env for testing"
cp .env .env.testing
sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env.testing
sed -i 's/DB_DATABASE=.*/DB_DATABASE=database\/testing.sqlite/' .env.testing
echo "QUEUE_CONNECTION=sync" >> .env.testing
echo "MAIL_MAILER=array" >> .env.testing
echo "SESSION_DRIVER=array" >> .env.testing
echo "CACHE_STORE=array" >> .env.testing

echo "▶ Step 5: Run migrations on test DB"
php artisan migrate --env=testing --seed

echo "✓ Done. Run: php artisan test --parallel"
