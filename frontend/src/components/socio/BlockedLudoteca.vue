<script setup>
import { computed } from 'vue';
import { useProfileStore } from '@/stores/profiles/socioStore';

const profileStore = useProfileStore();

// Format date nicely
const formattedDate = computed(() => {
  const dateStr = profileStore.fechaFinPenalizacion;
  if (!dateStr) return 'Fecha indefinida';
  
  const date = new Date(dateStr);
  return new Intl.DateTimeFormat('es-MX', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
});

// Calculate reason based on status
const motivoPenalizacion = computed(() => {
  const cuentaStatus = profileStore.profileData?.estatus_cuenta?.toUpperCase();
  const penaltyStatus = profileStore.profileData?.estatus_penalizacion?.toUpperCase();
  
  if (cuentaStatus === 'INACTIVO' || cuentaStatus === 'SUSPENDIDO') {
    return 'La cuenta del socio se encuentra inactiva o suspendida administrativamente.';
  }
  
  if (penaltyStatus === 'PENALIZADO_LUDOTECA' || penaltyStatus === 'PENALIZADO_AMBOS') {
    return 'Acumulación de retrasos u otras incidencias en el reglamento de la Ludoteca.';
  }
  
  return 'Acceso bloqueado temporalmente.';
});
</script>

<template>
  <div class="flex flex-col items-center justify-center min-h-[50vh] px-4 py-12 text-center animate-fade-in">
    <div class="bg-white p-8 md:p-10 rounded-3xl shadow-sm border border-red-200 max-w-md w-full">
      
      <div class="w-20 h-20 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-red-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
      </div>

      <h2 class="text-2xl font-extrabold text-surface-900 mb-3 tracking-tight">Acceso Bloqueado</h2>
      
      <div class="bg-surface-50 rounded-2xl p-4 mb-6 border border-surface-100 text-left">
        <div class="mb-3">
          <p class="text-xs font-bold text-surface-400 uppercase tracking-wider mb-1">Motivo de la penalización</p>
          <p class="text-sm font-medium text-surface-700 m-0">{{ motivoPenalizacion }}</p>
        </div>
        
        <div>
          <p class="text-xs font-bold text-surface-400 uppercase tracking-wider mb-1">Fecha de liberación</p>
          <p class="text-sm font-bold text-red-600 m-0 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            {{ formattedDate }}
          </p>
        </div>
      </div>

      <p class="text-surface-500 font-medium text-sm leading-relaxed mb-8">
        Si tienes alguna duda o consideras que esto es un error, por favor acércate a recepción para más información.
      </p>
      
      <router-link to="/socio/home" class="block w-full bg-surface-900 hover:bg-black text-white font-bold py-3.5 px-6 rounded-xl transition-all shadow-md active:scale-95 text-center">
        Volver al inicio
      </router-link>
      
    </div>
  </div>
</template>
