#!/usr/bin/env bash
set -e

if [ -z "$APP_KEY" ]; then php artisan key:generate --force; fi
php artisan config:cache
php artisan route:cache
php artisan view:cache || true
php artisan storage:link || true
# Si usás DB:
# php artisan migrate --force
apache2-foreground
