# ============ Etapa 1: Build de assets con Node (Vite) ============
FROM node:20 AS build-assets
WORKDIR /app

# Copio solo lo necesario para instalar dependencias
COPY package*.json vite.config.js ./

# Instalo dependencias
RUN npm ci

# Copio el código fuente necesario para el build
COPY resources ./resources
COPY public ./public

# Build de assets (Vite)
ENV NODE_ENV=production
RUN npm run build
# 👀 Mover el manifest al lugar esperado por Laravel
RUN mv /app/public/build/.vite/manifest.json /app/public/build/manifest.json

# 👀 Debug: mostrar que se generó manifest.json
RUN echo ">> Archivos en /app/public/build:" && ls -la /app/public/build


# ============ Etapa 2: Vendor PHP (Composer) ============
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts


# ============ Etapa 3: PHP + Apache (runtime) ============
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip zip libzip-dev libonig-dev libpq-dev \
  && docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql zip \
  && docker-php-ext-enable opcache \
  && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}/../!g' /etc/apache2/apache2.conf \
 && printf "\n<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n" >> /etc/apache2/apache2.conf

# Opcache recomendado
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

# Copio el código de Laravel
WORKDIR /var/www/html
COPY . .

# Copio vendor
COPY --from=vendor /app/vendor /var/www/html/vendor

# Copio los assets compilados
COPY --from=build-assets /app/public/build /var/www/html/public/build

# 👀 Debug: confirmar que manifest.json esté en el lugar correcto
RUN echo ">> Archivos en /var/www/html/public/build:" && ls -la /var/www/html/public/build

RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

COPY start-server.sh /usr/local/bin/start-server.sh
RUN chmod +x /usr/local/bin/start-server.sh

EXPOSE 80
CMD ["start-server.sh"]
