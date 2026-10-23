# Usamos una versión estable de Node (LTS activo)
FROM node:22.14.0-alpine3.21

# Directorio de trabajo
WORKDIR /app

# Copiamos los archivos de configuración primero para aprovechar la caché de Docker
COPY package*.json ./

# Instalamos las dependencias
RUN npm install

# Copiamos el resto del código
COPY . .

# Exponemos el puerto de Vite
EXPOSE 5173

# Comando para arrancar en modo desarrollo con acceso externo
CMD ["npm", "run", "dev", "--", "--host"]