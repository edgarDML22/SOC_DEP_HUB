# Usamos PHP 8.4 CLI (optimizado para el artisan serve de Laravel 11)
FROM php:8.4-cli

# Definimos el directorio de trabajo dentro del contenedor
WORKDIR /var/www

# Instalamos las dependencias del sistema operativo (incluyendo libicu-dev para intl)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    git \
    curl

# Configuramos e instalamos las extensiones nativas de PHP
RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo_pgsql zip intl

# Instalamos y habilitamos el controlador de MongoDB (Para el RF-12 de Telemetría)
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Traemos Composer desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Exponemos el puerto 8000 para que tu compu pueda ver el proyecto
EXPOSE 8000

# El comando que mantendrá vivo el backend y escuchando peticiones
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]