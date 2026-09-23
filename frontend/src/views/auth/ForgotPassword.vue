<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api'; 

const router = useRouter();

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

<template>
  <div class="min-h-screen w-full bg-surface-50 p-4 md:p-8 flex flex-col font-sans">
    
    <div class="w-full max-w-md mx-auto mb-6">
        <button @click="router.back()" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 transition-colors font-medium text-sm w-fit group pt-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Volver al inicio
        </button>
    </div>

    <!-- Container Card -->
    <div class="w-full max-w-md mx-auto bg-white rounded-2xl md:rounded-3xl shadow-sm border border-surface-200 p-6 md:p-10 space-y-8 mt-4 md:mt-12 transition-all">
      
      <div>
        <h2 class="text-2xl md:text-3xl font-extrabold text-surface-900 m-0 tracking-tight text-center">Recuperar Contraseña</h2>
        <p class="text-surface-500 text-sm font-medium mt-3 text-center">Ingresa el correo electrónico asociado a tu cuenta y te enviaremos instrucciones.</p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="space-y-2">
          <label for="email" class="block font-medium text-[13px] text-surface-600 tracking-wide uppercase px-1">Correo Electrónico</label>
          <input 
            type="email" 
            id="email" 
            v-model="email" 
            placeholder="usuario@ejemplo.com" 
            required
            class="w-full px-4 py-3.5 border border-surface-200 rounded-xl bg-surface-50 text-base font-medium text-surface-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all shadow-sm"
          />
        </div>

        <button type="submit" :disabled="isLoading" class="w-full py-4 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl shadow-[0_8px_20px_-6px_rgba(37,99,235,0.4)] active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300">
          {{ isLoading ? 'Enviando enlace...' : 'Enviar enlace de recuperación' }}
        </button>
      </form>

      <div v-if="successMessage" class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex gap-3 text-center transition-all">
        {{ successMessage }}
      </div>
      <div v-if="errorMessage" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium flex gap-3 animate-pulse">
        {{ errorMessage }}
      </div>
    </div>
  </div>
</template>