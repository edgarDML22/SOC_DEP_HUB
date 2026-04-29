<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const activeView = ref('inscripcion');

const tabs = [
  {
    key: 'inscripcion',
    label: 'Inscripción',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>`,
  },
  {
    key: 'mis-inscripciones',
    label: 'Mis Inscripciones',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/></svg>`,
  },
];
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

    <!-- Área de Contenido -->
    <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8">
      <div class="max-w-7xl mx-auto">
        <!-- Placeholder Inscripción -->
        <div v-if="activeView === 'inscripcion'"
          class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm">
          <div class="w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center text-primary-400 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>
            </svg>
          </div>
          <p class="text-surface-900 font-bold text-lg">Inscripción a Clases</p>
          <p class="text-surface-500 font-medium text-sm text-center max-w-xs mt-1">
            Próximamente podrás inscribirte a las clases y actividades programadas del club.
          </p>
        </div>

        <!-- Placeholder Mis Inscripciones -->
        <div v-else
          class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm">
          <div class="w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center text-primary-400 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
              <line x1="16" x2="16" y1="2" y2="6"/>
              <line x1="8" x2="8" y1="2" y2="6"/>
              <line x1="3" x2="21" y1="10" y2="10"/>
              <path d="m9 16 2 2 4-4"/>
            </svg>
          </div>
          <p class="text-surface-900 font-bold text-lg">Mis Inscripciones</p>
          <p class="text-surface-500 font-medium text-sm text-center max-w-xs mt-1">
            Aquí verás el historial de todas tus clases y actividades inscritas.
          </p>
        </div>
      </div>
    </div>

  </div>
</template>
