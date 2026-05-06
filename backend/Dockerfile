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

# Instalamos las librerías del sistema necesarias para GD y otras extensiones
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Configuramos GD con soporte de fuentes y colores reales
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

# Instalamos las dependencias de Linux necesarias para las imágenes y luego la extensión GD y BCMath
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd bcmath

# Instalamos y habilitamos el controlador de MongoDB (Para el RF-12 de Telemetría)
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Traemos Composer desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Exponemos el puerto 8000 para que tu compu pueda ver el proyecto
EXPOSE 8000

# El comando que mantendrá vivo el backend y escuchando peticiones
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]