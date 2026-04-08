<template>
  <div class="forgot-password-container">
    <h2>Recuperar Contraseña</h2>
    <form @submit.prevent="handleSubmit">
      <div class="form-group">
        <label for="email">Correo Electrónico:</label>
        <input 
          type="email" 
          id="email" 
          v-model="email" 
          placeholder="Ej: usuario@ejemplo.com" 
          required
        />
      </div>

      <button type="submit" :disabled="isLoading">
        {{ isLoading ? 'Enviando...' : 'Enviar enlace' }}
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
import { ref } from 'vue';
import api from '@/services/api'; 

const email = ref('');
const isLoading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const handleSubmit = async () => {
  isLoading.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const response = await api.post('/auth/forgot-password', { 
      correo_electronico: email.value 
    });

    if (response.data.success === false) {
      errorMessage.value = response.data.message; 
    } else {
      successMessage.value = 'Se ha enviado un enlace de recuperación a tu correo.';
      console.log("¡Token secreto de prueba!:", response.data.token_prueba);
      email.value = '';
    }
    
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Hubo un error de conexión al intentar enviar el enlace.';
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>

.forgot-password-container {
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
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  font-weight: 500;
  margin-bottom: 6px;
  font-size: 13px;
  color: #6b7280;
}

input[type="email"] {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background-color: #f9fafb;
  color: #111827;
  font-size: 15px;
  font-weight: 500;
  box-sizing: border-box;
}

input[type="email"]:focus {
  outline: none;
  border-color: #1d4ed8;
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