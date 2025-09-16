#!/usr/bin/env bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

set -e

if [ -z "$APP_KEY" ]; then php artisan key:generate --force; fi
php artisan config:cache
php artisan route:cache
php artisan view:cache || true
php artisan storage:link || true
# Si usás DB:
# php artisan migrate --force
apache2-foreground
