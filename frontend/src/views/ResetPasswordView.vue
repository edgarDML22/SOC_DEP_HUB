<template>
  <div class="reset-password-container">
    <h2>Restablecer Contraseña</h2>
    <p class="user-email">Para: {{ userEmail }}</p>

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
import { useRoute } from 'vue-router'; 
import axios from 'axios'; 

const route = useRoute();

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
    
    const response = await axios.post('http://localhost:8000/api/v1/auth/reset-password', {
      token: token.value,
      correo_electronico: userEmail.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    });
   
    if (response.data.success === false) {
      errorMessage.value = response.data.message || 'El token es inválido o ha expirado.';
    } else {
      
      successMessage.value = 'Tu contraseña ha sido restablecida con éxito. Ya puedes iniciar sesión.';
      
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
  max-width: 400px;
  margin: 50px auto;
  padding: 20px;
  border: 1px solid #ccc;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.user-email {
  font-weight: bold;
  color: #555;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  margin-bottom: 5px;
}

input[type="password"] {
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