<template>
  <div class="dashboard-container">
    <h1>Panel de Gerencia</h1>
    <p>Bienvenido. Has iniciado sesión correctamente.</p>
    
    <button @click="handleLogout" class="logout-btn">Cerrar Sesión</button>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

const handleLogout = async () => {
  try {
    // 1. Recuperar el token del localStorage
    const token = localStorage.getItem('auth_token');

    // 2. Avisarle al backend que destruya el token (pasándolo en los Headers)
    await axios.post('http://localhost:8000/api/v1/auth/logout', {}, {
      headers: {
        Authorization: `Bearer ${token}`
      }
    });

  } catch (error) {
    console.error("Error al cerrar sesión en el servidor:", error);
  } finally {
    // 3. Pase lo que pase con el backend, limpiamos el rastro en el navegador
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    
    // 4. Patada de regreso al Login
    router.push('/login');
  }
};
</script>

<style scoped>
.dashboard-container {
  padding: 20px;
  text-align: center;
}
.logout-btn {
  margin-top: 20px;
  padding: 10px 20px;
  background-color: #dc3545;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
.logout-btn:hover {
  background-color: #c82333;
}
</style>