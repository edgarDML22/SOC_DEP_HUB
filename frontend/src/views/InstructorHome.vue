<template>
  <div class="instructor-container">
    <h1>Panel de Instructor</h1>
    <p>Área de escáner y registro de asistencia.</p>

    <div v-if="loading">
      <p>Cargando perfil...</p>
    </div>

    <div v-else-if="errorMessage">
      <p>{{ errorMessage }}</p>
    </div>

    <div v-else-if="profile" class="profile-container">
      <h2>Perfil del Instructor</h2>
      <pre>{{ JSON.stringify(profile, null, 2) }}</pre>
    </div>

    <button @click="handleLogout" class="logout-btn">Cerrar Sesión</button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

const profile = ref(null);
const loading = ref(true);
const errorMessage = ref('');

const loadProfile = async () => {
  try {
    const token = localStorage.getItem('auth_token');

    if (!token) {
      errorMessage.value = 'No hay token. Inicia sesión primero.';
      return;
    }

    const response = await axios.get('http://localhost:8000/api/v1/profile', {
      headers: {
        Authorization: `Bearer ${token}`
      }
    });

    profile.value = response.data.data;
  } catch (error) {
    console.error('Error al cargar el perfil:', error);

    if (error.response) {
      errorMessage.value = `Error ${error.response.status}: no se pudo cargar el perfil`;
    } else {
      errorMessage.value = 'Error al cargar el perfil';
    }
  } finally {
    loading.value = false;
  }
};

const handleLogout = async () => {
  try {
    const token = localStorage.getItem('auth_token');

    await axios.post('http://localhost:8000/api/v1/auth/logout', {}, {
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  } catch (error) {
    console.error('Error al cerrar sesión:', error);
  } finally {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    router.push('/login');
  }
};

onMounted(() => {
  loadProfile();
});
</script>

<style scoped>
.instructor-container {
  padding: 20px;
  text-align: center;
}

.profile-container {
  margin-top: 20px;
  text-align: left;
  display: inline-block;
  max-width: 100%;
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