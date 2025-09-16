#!/usr/bin/env bash
set -e

# 0) Elimina cualquier config/ruta cacheada del repo anterior
rm -f bootstrap/cache/*.php

# 1) Limpiar sin tocar el store "database"
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2) Generar APP_KEY si falta
if [ -z "$APP_KEY" ]; then php artisan key:generate --force; fi

# 3) Cachear solo lo seguro
php artisan config:cache
# OJO: si tenés closures en rutas, dejá comentado:
# php artisan route:cache
php artisan view:cache || true

# 4) (Opcional) limpiar cache explícitamente usando el store file
php artisan cache:clear --store=file || true

# 5) Enlazar storage
php artisan storage:link || true

# 6) Levantar Apache
exec apache2-foreground
