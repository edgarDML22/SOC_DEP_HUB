<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
// IMPORTANTE: Importamos tu instancia personalizada, no la librería global
import api from '@/services/api'; 

const router = useRouter();
const form = reactive({ email: '', password: '' });
const errorMessage = ref('');
const isLoading = ref(false);

const handleLogin = async () => {
    errorMessage.value = '';
    if (!form.email || !form.password) {
        errorMessage.value = 'Por favor, complete todos los campos.';
        return;
    }
    isLoading.value = true;
    
    try {
        // 1. Opcional: Si usas Sanctum con cookies, primero pide el CSRF-TOKEN
        // await api.get('/sanctum/csrf-cookie');

        const response = await api.post('/auth/login', form);
        
        if (response.data.success) {
            const { token, user } = response.data.data;
            
            // Guardamos info para persistencia
            localStorage.setItem('auth_token', token);
            localStorage.setItem('user_data', JSON.stringify(user));
            
            // Redirección por roles
            const routes = {
                'gerente': '/admin/dashboard',
                'subgerente': '/admin/dashboard',
                'socio_titular': '/socio/home',
                'miembro_familiar': '/socio/home',
                'instructor': '/instructor/home'
            };
            router.push(routes[user.rol] || '/');
        }
    } catch (error) {
        console.error(error);
        errorMessage.value = error.response?.status === 401 
            ? 'Credenciales incorrectas' 
            : 'Error de conexión con el servidor';
    } finally { 
        isLoading.value = false; 
    }
};
</script>

<template>
  <div class="min-h-screen w-full flex bg-surface-50 font-sans relative overflow-hidden">
    
    <!-- Lado izquierdo (Oculto en móvil, visible en lg) -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-800 to-primary-600 flex-col items-center justify-center p-12 relative overflow-hidden shadow-[inset_-10px_0_30px_rgba(0,0,0,0.1)]">
      <!-- Patrón sutil (svg pattern en el fondo) -->
      <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#fff 1.5px, transparent 1.5px); background-size: 24px 24px;"></div>
      
      <div class="relative z-10 flex flex-col items-center text-center text-white max-w-lg">
        <div class="p-2 bg-white/10 backdrop-blur-sm rounded-[2rem] shadow-2xl mb-8 border border-white/20">
            <img src="@/assets/LogoSocDep.jpg" alt="SOCDEP HUB Logo" class="w-32 h-32 md:w-40 md:h-40 rounded-[1.5rem] object-cover" />
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold tracking-tight mb-5 drop-shadow-md">SOC-DEP HUB</h1>
        <div class="w-16 h-1.5 bg-white/30 rounded-full mb-6"></div>
        <p class="text-lg lg:text-xl font-medium text-white/90 leading-relaxed shadow-sm">
          Tu club deportivo en la palma de tu mano. Gestiona tus reservas, comunidad y actividades en un solo lugar.
        </p>
      </div>
    </div>

    <!-- Lado derecho (Cubre todo en móvil, mitad en lg) -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8 lg:p-12 relative z-10">
      <!-- Decoración de fondo en móvil (parte superior azul) -->
      <div class="lg:hidden absolute top-0 left-0 w-full h-64 bg-gradient-to-b from-primary-800 to-primary-600 rounded-b-[3rem] shadow-md z-0"></div>

      <!-- Tarjeta del formulario (Flotante) -->
      <div class="w-full max-w-md bg-white rounded-2xl md:rounded-3xl shadow-xl lg:shadow-none lg:bg-transparent lg:border-none border border-surface-100 p-6 sm:p-10 z-10 relative mt-16 sm:mt-24 lg:mt-0 transition-all duration-300">
        
        <!-- Logo solo para móvil -->
        <div class="lg:hidden flex justify-center mb-6 -mt-16 sm:-mt-20">
          <div class="p-1.5 bg-white rounded-[1.5rem] shadow-lg border border-surface-100">
            <img src="@/assets/LogoSocDep.jpg" alt="SOCDEP HUB" class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl object-cover" />
          </div>
        </div>

        <div class="text-center lg:text-left mb-8">
          <h2 class="text-2xl sm:text-3xl font-bold text-surface-900 tracking-tight">¡Hola de nuevo!</h2>
          <p class="text-surface-500 mt-2 font-medium">Ingresa tus credenciales para continuar</p>
        </div>

        <!-- Alerta de Alerta -->
        <div v-if="errorMessage" class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 flex items-center gap-3 text-red-700 transition-all duration-300 ease-out animate-pulse">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span class="text-sm font-semibold">{{ errorMessage }}</span>
        </div>

        <!-- Formulario -->
        <form @submit.prevent="handleLogin" class="space-y-5">
          <div class="space-y-2">
            <label class="block text-sm font-medium text-surface-700 ml-1">Correo Electrónico</label>
            <input 
              v-model="form.email" 
              type="email" 
              placeholder="carlos@socdep.com" 
              required 
              class="w-full py-3.5 px-4 rounded-xl bg-surface-50 border border-surface-200 text-surface-900 placeholder:text-surface-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent focus:bg-white transition-all duration-300 ease-out font-medium"
            />
          </div>

          <div class="space-y-2">
            <div class="flex items-center justify-between ml-1">
              <label class="block text-sm font-medium text-surface-700">Contraseña</label>
              <a href="../forgot-password" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline transition-all duration-300 ease-out tracking-tight">¿Olvidaste tu contraseña?</a>
            </div>
            <input 
              v-model="form.password" 
              type="password" 
              placeholder="••••••••" 
              required 
              class="w-full py-3.5 px-4 rounded-xl bg-surface-50 border border-surface-200 text-surface-900 placeholder:text-surface-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent focus:bg-white transition-all duration-300 ease-out font-medium"
            />
          </div>

          <button 
            type="submit" 
            :disabled="isLoading"
            class="w-full bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 shadow-sm mt-5 disabled:opacity-70 disabled:active:scale-100 disabled:cursor-not-allowed flex justify-center items-center gap-2"
          >
            <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isLoading ? 'Autenticando...' : 'Iniciar Sesión' }}
          </button>
        </form>

        <div class="mt-8 mb-6 relative flex items-center justify-center">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-surface-200"></div>
          </div>
          <span class="relative px-4 text-xs font-semibold text-surface-400 bg-white lg:bg-surface-50 uppercase tracking-widest">o continuar con</span>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <button type="button" class="w-full bg-surface-50 hover:bg-surface-100 text-surface-700 border border-surface-200 rounded-xl px-4 py-2.5 font-semibold transition-all flex justify-center items-center gap-2 active:scale-95">
            <!-- SVG Google Icon -->
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/><path fill="none" d="M1 1h22v22H1z"/></svg>
            Google
          </button>
          <button type="button" class="w-full bg-surface-50 hover:bg-surface-100 text-surface-700 border border-surface-200 rounded-xl px-4 py-2.5 font-semibold transition-all flex justify-center items-center gap-2 active:scale-95">
            <!-- SVG Apple Icon -->
            <svg class="w-5 h-5 shrink-0 text-surface-900" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.15 2.95.89 3.84 2.14-3.13 1.84-2.61 5.9.46 7.15-.76 1.83-1.63 3.36-2.95 3.72zm-2.02-14.3c-1.12-1.39-2.97-1.84-3.8-1.57.29 1.94 1.39 3.65 2.71 4.54.89-.96 2.07-2.5 1.09-2.97z"/></svg>
            Apple
          </button>
        </div>

      </div>
    </div>
    
  </div>
</template>
