<template>
  <div class="reset-password-container">
    <h2>Restablecer Contraseña</h2>
    <p class="user-email">Para: <strong>{{ userEmail }}</strong></p>

    <form @submit.prevent="handleSubmit">
      <div class="form-group">
        <label for="password">Nueva Contraseña:</label>
        <input 
          type="password" 
          id="password" 
          v-model="password" 
          required
        />
      </div>

      <div class="form-group">
        <label for="password_confirmation">Confirmar Contraseña:</label>
        <input 
          type="password" 
          id="password_confirmation" 
          v-model="passwordConfirmation" 
          required
        />
      </div>

      <button type="submit" :disabled="isLoading">
        {{ isLoading ? 'Guardando...' : 'Restablecer Contraseña' }}
      </button>
    </form>

    <div v-if="successMessage" class="feedback-message success">
      {{ successMessage }}
    </div>
    <div v-if="errorMessage" class="feedback-message error">
      {{ errorMessage }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router'; 
import api from '@/services/api'; 

const route = useRoute();
const router = useRouter(); 

// Referencias reactivas para el estado
const userEmail = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const isLoading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');
const token = ref('');

onMounted(() => {
  userEmail.value = route.query.email || '';
  token.value = route.query.token || ''; 
});

const handleSubmit = async () => {
  isLoading.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'Las contraseñas no coinciden.';
    isLoading.value = false; 
    return;
  }

  try {
    const response = await api.post('auth/reset-password', {
      token: token.value,
      correo_electronico: userEmail.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    });
   
    if (response.data.success === false) {
      errorMessage.value = response.data.message || 'El token es inválido o ha expirado.';
    } else {
      successMessage.value = 'Tu contraseña ha sido restablecida con éxito. Redirigiendo al login...';
      
      password.value = '';
      passwordConfirmation.value = '';

      setTimeout(() => {
          router.push('/login'); 
      }, 2500);
    }

  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Hubo un error al restablecer la contraseña. Inténtalo de nuevo.';
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>

.reset-password-container {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  color: #111827;
  max-width: 400px;
  margin: 50px auto;
  padding: 24px;
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

h2 {
  font-size: 24px;
  font-weight: 700;
  margin-top: 0;
  margin-bottom: 8px;
}

.user-email {
  font-size: 14px;
  color: #6b7280;
  margin-top: 0;
  margin-bottom: 24px;
}

.user-email strong {
  color: #111827;
  font-weight: 600;
}

.form-group {
  margin-bottom: 16px;
}

label {
  display: block;
  font-weight: 500;
  margin-bottom: 6px;
  font-size: 13px;
  color: #6b7280;
}

input[type="password"] {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background-color: #f9fafb;
  color: #111827;
  font-size: 15px;
  font-weight: 500;
  box-sizing: border-box;
  transition: border-color 0.2s;
}

input[type="password"]:focus {
  outline: none;
  border-color: #1d4ed8;
  background-color: #ffffff;
}


button {
  width: 100%;
  padding: 10px 16px;
  background-color: #1d4ed8; 
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  margin-top: 10px;
  transition: background-color 0.2s;
}

button:hover {
  background-color: #1e40af;
}

button:disabled {
  background-color: #93c5fd;
  cursor: not-allowed;
}

.feedback-message {
  margin-top: 15px;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 14px;
  text-align: center;
}

.feedback-message.success {
  color: #166534;
  background-color: #dcfce7;
  border: 1px solid #bbf7d0;
}

.feedback-message.error {
  color: #991b1b;
  background-color: #fee2e2;
  border: 1px solid #f87171;
}
</style>