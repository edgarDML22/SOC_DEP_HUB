<script setup>
import { ref } from "vue";
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();

const items = ref([
    { route: '/socio/reservations/manage', label: 'Gestión', icon: 'pi pi-calendar-plus' },
    { route: '/socio/reservations/on-demand', label: 'On Demand', icon: 'pi pi-bolt' },
    { route: '/socio/reservations/active-sessions', label: 'Programadas', icon: 'pi pi-list' }
]);
</script>

<template>
  <div class="min-h-screen bg-surface-50 font-sans p-4 md:p-8 pb-24 md:pb-8">
    <div class="max-w-5xl mx-auto flex flex-col gap-5">
        
        <!-- Encabezado con Botón Volver -->
        <div>
            <button @click="router.back()" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 transition-colors mb-4 font-medium text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Volver
            </button>
            <h2 class="text-2xl md:text-3xl font-extrabold text-surface-900 m-0 tracking-tight">Agenda</h2>
        </div>
        
        <!-- Pestañas Segmented Control iOS -->
        <div class="flex p-1 bg-surface-100 rounded-xl mb-2 w-full max-w-lg mx-auto md:mx-0 overflow-x-auto scrollbar-thin">
            <router-link v-for="tab in items" :key="tab.route" :to="tab.route"
                class="flex-1 py-1.5 md:py-2 px-3 text-xs md:text-sm rounded-lg text-center transition-all whitespace-nowrap flex items-center justify-center gap-2"
                :class="$route.path.includes(tab.route) ? 'font-bold text-primary-700 bg-white shadow-sm' : 'font-medium text-surface-500 hover:text-surface-700'"
            >
                <i :class="tab.icon" class="text-[14px]"></i>
                {{ tab.label }}
            </router-link>
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
  </div>
</template>