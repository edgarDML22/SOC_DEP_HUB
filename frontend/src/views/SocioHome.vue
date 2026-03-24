<template>
  <div class="socio-container">
    <h1>Inicio - Área de Socios</h1>
    <p>Bienvenido a tu espacio deportivo.</p>
    
    <button @click="handleLogout" class="logout-btn">Cerrar Sesión</button>
    <button @click="getSupportLink" class="logout-btn">Obtener enlace de soporte</button>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const getSupportLink = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/v1/system/support-link');
    const result = await response.json();
    const url = result.data.support_url;
    if (url) {
      window.open(url, '_blank');
    }
  } catch (error) {
    console.error("Hubo un error al obtener el link:", error);
    alert("No se pudo cargar el formulario de soporte.");
  }
};
const handleLogout = async () => {
  try {
    const token = localStorage.getItem('auth_token');
    await axios.post('http://localhost:8000/api/v1/auth/logout', {}, {
      headers: { Authorization: `Bearer ${token}` }
    });
  } catch (error) {
    console.error("Error al cerrar sesión:", error);
  } finally {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    router.push('/login');
  }
};
</script>

<style scoped>
.socio-container {
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