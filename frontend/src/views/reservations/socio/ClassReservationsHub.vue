<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useActividadesStore } from '@/stores/actividadesStore';
import ActividadesAbiertas from './ActividadesAbiertas.vue';
import ActividadesCerradas from './ActividadesCerradas.vue';
import MisInscripcionesClases from './MisInscripcionesClases.vue';

const router = useRouter();
const store = useActividadesStore();

const activeView = ref('mis-inscripciones');

const tabs = [
  {
    key: 'mis-inscripciones',
    label: 'Mis Inscripciones',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/></svg>`,
  },
  {
    key: 'actividades-abiertas',
    label: 'Actividades Abiertas',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>`,
  },
  {
    key: 'actividades-cerradas',
    label: 'Actividades Cerradas',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
  },
];

// Cargar datos al montar
onMounted(async () => {
  await Promise.all([
    store.fetchSesiones(),
    store.fetchMisInscripciones(),
  ]);
});
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
      <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 mb-5 tracking-tight">Actividades Programadas</h2>

      <!-- Segmented Control (Pills) -->
      <div class="flex p-1.5 bg-surface-100 rounded-2xl w-full max-w-2xl mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-6">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          :id="`tab-actividades-${tab.key}`"
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

    <!-- Área de Contenido — v-show para preservar estado y evitar re-mounts -->
    <div class="w-full">
      <MisInscripcionesClases v-show="activeView === 'mis-inscripciones'" />
      <ActividadesAbiertas    v-show="activeView === 'actividades-abiertas'" />
      <ActividadesCerradas    v-show="activeView === 'actividades-cerradas'" />
    </div>

  </div>
</template>
