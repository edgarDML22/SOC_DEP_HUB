<script setup>
import { useProfileStore } from '@/stores/profiles/socioStore';

const profileStore = useProfileStore();

const logout = () => {
  profileStore.logout();
};
</script>

<template>
  <div class="min-h-screen bg-linear-to-br from-red-50/40 via-surface-50 to-surface-100 flex flex-col items-center justify-center p-6 text-center font-sans">
    <div class="bg-white p-8 md:p-10 rounded-3xl shadow-xl max-w-md w-full flex flex-col items-center gap-6 border border-red-100/60">
      
      <!-- Icono de Alerta Suave -->
      <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center text-red-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
          <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
          <line x1="12" y1="9" x2="12" y2="13"/>
          <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
      </div>
      
      <h1 class="text-2xl font-bold text-surface-900 tracking-tight">Acceso Restringido</h1>
      
      <!-- Badge de Estatus de Cuenta -->
      <div class="bg-red-50 px-4 py-1.5 rounded-full border border-red-100 flex items-center gap-2">
        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
        <span class="text-xs font-bold tracking-wider text-red-800 uppercase">ESTATUS CUENTA: SUSPENDIDO</span>
      </div>
      
      <p class="text-surface-600 text-sm md:text-base leading-relaxed">
        Tu cuenta ha sido suspendida temporalmente. Para conocer los detalles y regularizar tu situación, te invitamos a acudir a las oficinas de Administración del club.
      </p>

      <!-- Botón de Cerrar Sesión Estilo Premium -->
      <button @click="logout" class="mt-2 w-full py-3 bg-surface-900 hover:bg-surface-800 text-white font-bold rounded-xl shadow-md transition-all active:scale-95 flex items-center justify-center gap-2 text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Cerrar Sesión
      </button>

    </div>
  </div>
</template>
