<template>
  <div class="login-container">
    <h2>Iniciar Sesión - SocDep Hub</h2>

    <div v-if="errorMessage" class="error-alert">
      {{ errorMessage }}
    </div>

    <form @submit.prevent="handleLogin">
      <div>
        <label for="email">Correo Electrónico:</label>
        <input type="email" id="email" v-model="form.email" required />
      </div>

      <div>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" v-model="form.password" required />
      </div>

      <button type="submit" :disabled="isLoading">
        {{ isLoading ? 'Cargando...' : 'Ingresar' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

// Estado reactivo (SDH-1103)
const form = reactive({
  email: '',
  password: ''
});
const errorMessage = ref('');
const isLoading = ref(false);

// Consumo y lógica (SDH-1104)
const handleLogin = async () => {
  errorMessage.value = ''; // <-- Así se accede al valor de un ref()
  isLoading.value = true;

  try {
    // Asegúrate de que esta URL apunte a tu contenedor de PHP o al puerto expuesto (ej. http://localhost:8000)
    const response = await axios.post('http://localhost:8000/api/v1/auth/login', form);
    if (response.data.success) {
      const { token, user } = response.data.data;

      // Persistencia segura temporal (SDH-1104)
      localStorage.setItem('auth_token', token);
      localStorage.setItem('user_data', JSON.stringify(user));

      // Redirección Condicional evaluando el Enum de tu BD
      switch (user.rol) {
        case 'gerente':
        case 'subgerente':
          router.push('/admin/dashboard');
          break;
        case 'socio_titular':
        case 'miembro_familiar':
          router.push('/socio/home');
          break;
        case 'instructor':
          router.push('/instructor/home');
          break;
        default:
          errorMessage.value = 'Rol no reconocido.';
      }
    }
  } catch (error) {
    if (error.response && error.response.status === 401) {
      errorMessage.value = 'Credenciales incorrectas';
    } else {
      errorMessage.value = 'Error de conexión con el servidor';
    }
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
/* Agrega aquí tus estilos base o de Tailwind para maquetar según los prototipos */
.error-alert {
  color: red;
  margin-bottom: 10px;
}
</style>