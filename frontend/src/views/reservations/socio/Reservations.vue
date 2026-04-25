<script setup>
import { ref, computed } from "vue";
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();

const items = ref([
    { route: '/socio/reservations/manage', label: 'Gestión', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/></svg>' },
    { route: '/socio/reservations/on-demand', label: 'On Demand', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>' },
    { route: '/socio/reservations/active-sessions', label: 'Programadas', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>' }
]);

const activeTab = computed(() => {
    if (route.path.includes('manage')) return '/socio/reservations/manage'
    if (route.path.includes('on-demand')) return '/socio/reservations/on-demand'
    if (route.path.includes('active-sessions')) return '/socio/reservations/active-sessions'
    return route.path
})
</script>

<template>
  <div class="w-full font-sans bg-surface-50 min-h-screen">
    
    <!-- Contenedor del Layout -->
    <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 pt-4 md:pt-6 lg:pt-8 bg-surface-50">
      <!-- Botón Volver -->
      <button @click="router.push('/socio/home')" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 focus:outline-none w-fit group">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Volver
      </button>

      <!-- Título -->
      <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 mb-5 tracking-tight">Agenda</h2>

      <!-- Nivel 1 (Pills - mismo estilo que Community/Amigos) -->
      <div class="flex p-1.5 bg-surface-100 rounded-2xl w-full max-w-2xl mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-6">
          <router-link 
             v-for="tab in items"
             :key="tab.route"
             :to="tab.route"
             class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
             :class="activeTab === tab.route ? 'bg-primary-600 text-white font-extrabold rounded-xl shadow-md transform scale-[1.02]' : 'text-surface-500 font-bold hover:bg-white/60 hover:text-surface-700 rounded-xl'"
          >
             <span v-html="tab.icon" class="flex-shrink-0"></span>
             {{ tab.label }}
          </router-link>
      </div>
    </div>

    <!-- Área de Contenido de la Ruta -->
    <div class="w-full">
        <router-view v-slot="{ Component }">
            <keep-alive>
                <component :is="Component" />
            </keep-alive>
        </router-view>
    </div>

  </div>
</template>