# dolar-api-laravel
API en Laravel para convertir USD→ARS consumiendo DolarAPI. Desplegable en Render (Docker).

## Endpoints
- GET /api/health
- GET /api/convertir?valor=100&tipo=oficial

## Deploy
Render Web Service (Dockerfile + start-server.sh). Setear APP_KEY y APP_URL en envs.

## Dev
composer install
cp .env.example .env
php artisan key:generate
php artisan serve