# PHP 8.4 CLI — base para Laravel Octane con RoadRunner (Fase 2 de rendimiento)
FROM php:8.4-cli

# Definimos el directorio de trabajo dentro del contenedor
WORKDIR /var/www

# Instalamos las dependencias del sistema operativo
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configuramos e instalamos las extensiones nativas de PHP
# pcntl y sockets son requeridas por Laravel Octane / RoadRunner
RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo_pgsql zip intl pcntl sockets mbstring

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

# Instalamos dependencias de PHP en tiempo de build para no depender del host
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-scripts --no-interaction
COPY . .

# ── Descargamos la fuente FontAwesome 6 Free Solid para los PDFs ────────────
RUN curl -sSL \
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.ttf" \
    -o /var/www/resources/fonts/fa-solid-900.ttf

# ── Copiamos la portada al directorio public para que DOMPDF la encuentre ───
# (Si ya existe en el repo dentro de backend/public, este paso no hace daño)
RUN if [ -f /var/www/../frontend/src/assets/Programacion_disciplinas.png ]; then \
        cp /var/www/../frontend/src/assets/Programacion_disciplinas.png /var/www/public/; \
    fi

# ── Creamos y damos permisos al directorio de caché de fuentes de DOMPDF ────
# DOMPDF escribe archivos .ufm/.afm aquí la primera vez que procesa una fuente.
RUN mkdir -p /var/www/storage/fonts \
    && mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/storage/framework/cache \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && chmod -R 775 /var/www/storage \
    && chown -R www-data:www-data /var/www/storage

# ── Pre-calentamos el caché de fuentes de DOMPDF ────────────────────────────
# El script genera los archivos .ufm para Inter y MaterialIcons durante el
# build, evitando errores de escritura en tiempo de ejecucion.
RUN php /var/www/scripts/cache_dompdf_fonts.php || true

# ── Ejecutamos scripts post-install de Composer ──────────────────────────────
RUN composer run-script post-autoload-dump --no-interaction 2>/dev/null || true

# Descargamos el binario RoadRunner (rr) directamente en el PATH del contenedor.
RUN curl -sSL https://github.com/roadrunner-server/roadrunner/releases/download/v2024.3.5/roadrunner-2024.3.5-linux-amd64.tar.gz \
    | tar -xz --strip-components=1 -C /usr/local/bin roadrunner-2024.3.5-linux-amd64/rr \
    && chmod +x /usr/local/bin/rr

# Exponemos el puerto 8000 para que tu compu pueda ver el proyecto
EXPOSE 8000

# Servidor HTTP: Laravel Octane con RoadRunner.
# --max-requests=500 es obligatorio para reciclar workers y evitar conexiones PDO
# muertas con la base de datos remota Neon (PostgreSQL serverless).
# Los servicios scheduler y queue-worker sobreescriben este CMD con su propio command:.
CMD ["php", "artisan", "octane:start", "--server=roadrunner", "--host=0.0.0.0", "--port=8000", "--workers=4", "--max-requests=500"]