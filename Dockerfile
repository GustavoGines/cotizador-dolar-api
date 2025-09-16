# --------------------
# Etapa 1: build de assets con Node
# --------------------
FROM node:20 as build-assets
WORKDIR /app

# Copiamos package.json y lock para instalar dependencias
COPY package*.json vite.config.js tailwind.config.js ./
# (si usás postcss.config.js, también copiá ese)
COPY resources ./resources

RUN npm ci
RUN npm run build

# --------------------
# Etapa 2: PHP + Apache
# --------------------
FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git zip unzip libzip-dev libonig-dev \
 && docker-php-ext-install pdo pdo_mysql mbstring zip

# Configurar Apache
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
 && printf "\n<Directory /var/www/html/public>\n    AllowOverride All\n</Directory>\n" >> /etc/apache2/apache2.conf

# Copiar composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiamos el proyecto Laravel completo
COPY . .

# Copiamos los assets compilados desde la etapa Node
COPY --from=build-assets /app/public/build /var/www/html/public/build

# Instalar dependencias PHP
RUN composer install --no-dev --prefer-dist --optimize-autoloader

# Permisos
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Script de arranque
COPY start-server.sh /usr/local/bin/start-server.sh
RUN chmod +x /usr/local/bin/start-server.sh

EXPOSE 80
CMD ["start-server.sh"]
