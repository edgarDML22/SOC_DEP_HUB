<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useActividadesStore } from '@/stores/actividadesStore';
import { useBootstrapStore } from '@/stores/profiles/bootstrapStore';
import ActividadesAbiertas from './ActividadesAbiertas.vue';
import ActividadesCerradas from './ActividadesCerradas.vue';
import MisInscripcionesClases from './MisInscripcionesClases.vue';

const router = useRouter();
const store = useActividadesStore();
const bootstrapStore = useBootstrapStore();

const activeView = ref('mis-inscripciones');

const tabs = [
  {
    key: 'mis-inscripciones',
    label: 'Mis Inscripciones',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/></svg>`,
  },
  {
    key: 'actividades-abiertas',
    label: 'Abiertas',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>`,
  },
  {
    key: 'actividades-cerradas',
    label: 'Cerradas',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
  },
];

// Cargar datos al montar
onMounted(async () => {
  await Promise.all([
    store.fetchSesiones(),
    store.fetchMisInscripciones(),
    store.fetchInstructores(),
    bootstrapStore.fetchSocioData(),
  ]);
});
</script>

<template>
  <div class="w-full font-sans bg-slate-50 text-slate-800 min-h-screen relative overflow-hidden pb-12">
    <!-- Luces de fondo (Glows adaptados a Light Mode) -->
    <div class="absolute top-[-20%] left-[-10%] w-[600px] h-[600px] rounded-full bg-blue-200/20 blur-[150px] pointer-events-none" />
    <div class="absolute top-[10%] right-[-15%] w-[500px] h-[500px] rounded-full bg-violet-200/20 blur-[130px] pointer-events-none" />

    <!-- Contenedor del Layout -->
    <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 pt-6 md:pt-8 relative z-10">

      <!-- Botón Volver -->
      <button
        @click="router.push('/socio/home')"
        class="flex items-center gap-2 text-slate-500 hover:text-blue-600 font-bold text-sm transition-all duration-300 mb-6 focus:outline-none w-fit group cursor-pointer"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 group-hover:-translate-x-1.5 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="m15 18-6-6 6-6"/>
        </svg>
        Volver al Panel
      </button>

      <!-- Encabezado Premium -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 p-6 rounded-3xl border border-slate-200 bg-white/80 backdrop-blur-xl shadow-lg relative">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-50/30 to-violet-50/30 rounded-3xl pointer-events-none" />
        <div class="relative z-10 space-y-1.5">
          <span class="text-[10px] font-black uppercase tracking-[0.25em] text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200/50">
            Wellness Club Hub
          </span>
          <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight leading-none m-0 pt-1">
            Actividades Programadas
          </h2>
          <p class="text-sm text-slate-500 font-semibold">
            Planifica tus disciplinas favoritas, inscribe familiares o invita amigos.
          </p>
        </div>
        
        <!-- Status Stats Mini -->
        <div class="flex items-center gap-3 md:gap-4 shrink-0 relative z-10">
          <div class="bg-white rounded-3xl border border-slate-200 p-4 min-w-[110px] sm:min-w-[120px] flex flex-col items-center justify-center text-center shadow-sm hover:shadow-md hover:border-blue-300 transition-all duration-300">
            <span class="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider mb-1">Inscripciones</span>
            <span class="text-2xl md:text-3xl font-black text-blue-600 leading-none">{{ store.misInscripciones.length }}</span>
          </div>
          <div class="bg-white rounded-3xl border border-slate-200 p-4 min-w-[110px] sm:min-w-[120px] flex flex-col items-center justify-center text-center shadow-sm hover:shadow-md hover:border-emerald-350 transition-all duration-300">
            <span class="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider mb-1">Clases Hoy</span>
            <span class="text-2xl md:text-3xl font-black text-emerald-650 leading-none">
              {{ store.sesiones.filter(s => s.fecha_sesion === new Date().toISOString().split('T')[0]).length }}
            </span>
          </div>
        </div>
      </div>

      <!-- Segmented Control (Pills) — Mismos estilos que el de Reservaciones -->
      <div class="flex p-1.5 bg-surface-100 rounded-2xl w-full max-w-2xl mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-6">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          :id="`tab-actividades-${tab.key}`"
          @click="activeView = tab.key"
          class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none cursor-pointer"
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
    <div class="w-full relative z-10">
      <MisInscripcionesClases v-show="activeView === 'mis-inscripciones'" />
      <ActividadesAbiertas    v-show="activeView === 'actividades-abiertas'" />
      <ActividadesCerradas    v-show="activeView === 'actividades-cerradas'" />
    </div>

  </div>
</template>

<style scoped>
/* Scrollbar ultra fino */
.scrollbar-thin::-webkit-scrollbar {
  height: 3px;
}
.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 9px;
}
</style>
