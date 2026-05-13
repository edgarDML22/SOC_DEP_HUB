<template>
  <div class="dashboard-container">
    <h1>Panel de Gerencia</h1>
    <p>Bienvenido. Has iniciado sesión correctamente.</p>
    
    <button @click="handleLogout" class="logout-btn">Cerrar Sesión</button>
  </div>
</template>

<script setup>
import { useAdminStore } from '@/stores/profiles/adminStore';

const profileStore = useAdminStore();

const handleLogout = () => profileStore.logout();
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