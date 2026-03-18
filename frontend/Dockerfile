# Usamos una versión estable de Node
FROM node:20-alpine

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