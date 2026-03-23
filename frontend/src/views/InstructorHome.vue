<template>
  <div class="instructor-container">
    <h1>Panel de Instructor</h1>
    <p>Área de escáner y registro de asistencia.</p>
    
    <button @click="handleLogout" class="logout-btn">Cerrar Sesión</button>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

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
.instructor-container {
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