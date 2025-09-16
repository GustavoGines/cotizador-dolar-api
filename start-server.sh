#!/usr/bin/env bash
set -e
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

if [ -z "$APP_KEY" ]; then php artisan key:generate --force; fi

php artisan config:cache
# php artisan route:cache   # comenta si tenés closures en rutas
php artisan view:cache || true
php artisan storage:link || true

apache2-foreground
