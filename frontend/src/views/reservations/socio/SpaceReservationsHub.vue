<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useProfileStore } from '@/stores/profiles/socioStore';
import OnDemand from './OnDemand.vue';
import Manage from './Manage.vue';

const router = useRouter();
const profileStore = useProfileStore();

const { isPenalized, fechaFinPenalizacion } = profileStore;

const activeView = ref('mis-reservas');

const tabs = [
  {
    key: 'mis-reservas',
    label: 'Mis Reservas',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/></svg>`,
  },
  {
    key: 'hacer-reserva',
    label: 'Hacer Reserva',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>`,
  },
];

const formatDate = (dateString) => {
  if (!dateString) return 'próximamente';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' });
};
</script>

<template>
  <div class="w-full font-sans bg-surface-50 min-h-screen">

    <!-- Contenedor del Layout -->
    <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 pt-4 md:pt-6 lg:pt-8 bg-surface-50">

      <!-- Botón Volver -->
      <button
        @click="router.push('/socio/home')"
        class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 focus:outline-none w-fit group"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m15 18-6-6 6-6"/>
        </svg>
        Volver
      </button>

      <!-- Título -->
      <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 mb-5 tracking-tight">Reservaciones</h2>

      <!-- Penalización: bloqueo total -->
      <div v-if="isPenalized" class="bg-red-50 border border-red-200 rounded-2xl p-6 mb-8 flex flex-col items-center text-center shadow-sm">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4 text-red-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
        </div>
        <h3 class="text-xl font-bold text-red-900 mb-2">Acceso Restringido</h3>
        <p class="text-red-700 max-w-md mx-auto leading-relaxed">
          Tu cuenta se encuentra bajo una sanción temporal debido a la acumulación de 3 No Shows.
        </p>
        <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg font-bold text-sm">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          La sanción expira el: {{ formatDate(fechaFinPenalizacion) }}
        </div>
      </div>

      <!-- Segmented Control (Pills) — Mismos estilos que el viejo Reservations.vue -->
      <div v-else class="flex p-1.5 bg-surface-100 rounded-2xl w-full max-w-2xl mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-6">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeView = tab.key"
          class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
          :class="activeView === tab.key
            ? 'bg-primary-600 text-white font-extrabold rounded-xl shadow-md transform scale-[1.02]'
            : 'text-surface-500 font-bold hover:bg-white/60 hover:text-surface-700 rounded-xl'"
        >
          <span v-html="tab.icon" class="flex-shrink-0"></span>
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Área de Contenido Condicional — v-show para preservar estado y evitar re-mounts -->
    <div class="w-full">
      <OnDemand v-show="activeView === 'hacer-reserva'" @switch-tab="activeView = $event" />
      <Manage v-show="activeView === 'mis-reservas'" @switch-tab="activeView = $event" />
    </div>

  </div>
</template>
