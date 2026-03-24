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
import axios from 'axios'; // <-- ¡AQUÍ ESTÁ LA SOLUCIÓN! Faltaba importar axios

// Referencias reactivas para el estado
const email = ref('');
const isLoading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const handleSubmit = async () => {
  isLoading.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const response = await axios.post('http://localhost:8000/api/v1/auth/forgot-password', { 
      correo_electronico: email.value 
    });

    if (response.data.success === false) {
      
      errorMessage.value = response.data.message; 
    } else {
      
      successMessage.value = 'Se ha enviado un enlace de recuperación a tu correo.';
      console.log("¡Token secreto de prueba!:", response.data.token_prueba);
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
  max-width: 400px;
  margin: 50px auto;
  padding: 20px;
  border: 1px solid #ccc;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  margin-bottom: 5px;
}

input[type="email"] {
  width: 100%;
  padding: 8px;
  box-sizing: border-box;
}

button {
  width: 100%;
  padding: 10px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

button:disabled {
  background-color: #a0cfff;
  cursor: not-allowed;
}

.feedback-message {
  margin-top: 15px;
  padding: 10px;
  border-radius: 4px;
}

.feedback-message.success {
  color: #155724;
  background-color: #d4edda;
  border: 1px solid #c3e6cb;
}

.feedback-message.error {
  color: #721c24;
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
}
</style>