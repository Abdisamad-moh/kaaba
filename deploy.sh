#!/bin/bash
set -e

echo "Deployment started ..."

# Enter maintenance mode or return true
# if already is in maintenance mode
# (php artisan down) || true

# Pull the latest version of the app
git pull origin master

# Install composer dependencies
# composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Clear the old cache
# php artisan clear-compiled

# Recreate cache
# php artisan optimize

# Run database migrations
php bin/console cache:clear
php bin/console asset-map:compile
composer2 install --no-dev
php bin/console doctrine:migrations:diff --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear

# Exit maintenance mode
# php artisan up

echo "Deployment finished!"