FROM php:8.2-apache

# Instalar extensiones necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libwebp-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_mysql mysqli zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite headers

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de Composer primero (para cache de Docker)
COPY composer.json composer.lock* ./

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copiar archivos de configuración de Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copiar el resto de la aplicación
COPY . .

# Regenerar autoloader con todos los archivos
RUN composer dump-autoload --no-dev --optimize

# Crear directorio para uploads y configurar permisos
RUN mkdir -p uploads/profiles uploads/movements && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 uploads

# Establecer zona horaria de PHP
RUN echo "date.timezone = Europe/Madrid" > /usr/local/etc/php/conf.d/timezone.ini

EXPOSE 80

CMD ["apache2-foreground"]