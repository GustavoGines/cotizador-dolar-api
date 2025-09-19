# ============ Etapa 1: Build de assets con Node (Vite) ============
FROM node:20 AS build-assets
WORKDIR /app

# Copio solo lo necesario para cachear npm ci
COPY package*.json vite.config.js ./

# Instalo dependencias
RUN npm ci

# Copio el código de frontend
COPY resources ./resources
# Copio la carpeta public también (necesaria para que exista public/build)
COPY public ./public


# Build de assets (Vite)
ENV NODE_ENV=production
RUN npm run build

# 👀 Debug temporal (puedes quitar después)
RUN ls -la /app/public/build || true


# ============ Etapa 2: Vendor PHP (Composer) ============
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./

# Instalo sin dev, sin scripts (artisan aún no existe)
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts


# ============ Etapa 3: PHP + Apache (runtime) ============
FROM php:8.2-apache

# Paquetes del sistema y extensiones PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip zip libzip-dev libonig-dev libpq-dev \
  && docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql zip \
  && docker-php-ext-enable opcache \
  && rm -rf /var/lib/apt/lists/*

# Apache: DocumentRoot en /public y rewrite
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}/../!g' /etc/apache2/apache2.conf \
 && printf "\n<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n" >> /etc/apache2/apache2.conf

# Opcache recomendado en producción
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.enable_cli=1'; \
      echo 'opcache.jit=1255'; \
      echo 'opcache.jit_buffer_size=128M'; \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.interned_strings_buffer=16'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Copio el código de la app
WORKDIR /var/www/html
COPY . .

# Copio vendor de la etapa composer
COPY --from=vendor /app/vendor /var/www/html/vendor

# Copio los assets compilados (Vite) desde la etapa Node
COPY --from=build-assets /app/public/build /var/www/html/public/build

# Permisos mínimos para cache y logs
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Script de arranque
COPY start-server.sh /usr/local/bin/start-server.sh
RUN chmod +x /usr/local/bin/start-server.sh

EXPOSE 80
CMD ["start-server.sh"]
