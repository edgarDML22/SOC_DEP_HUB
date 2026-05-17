<script setup>
import { computed } from 'vue';
import { useAdminStore } from '@/stores/profiles/adminStore';
import { IconGuests, IconTarget, IconCalendar, IconGrid } from '@/components/icons';

const profileStore = useAdminStore();

// Saludo dinámico según la hora
const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Buenos días';
  if (hour < 19) return 'Buenas tardes';
  return 'Buenas noches';
});
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans pb-12 pt-6">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-10">
      
      <!-- HEADER SALUDO -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-black text-surface-900 tracking-tight m-0">
            {{ greeting }}, <span class="text-primary-600">{{ profileStore.fullName.split(' ')[0] || 'Administrador' }}</span> 👋
          </h1>
          <p class="text-sm font-medium text-surface-500 mt-2 m-0 max-w-xl">
            Te damos la bienvenida al Panel de Control de Gerencia. Aquí puedes administrar las operaciones de la ludoteca, torneos, socios y más.
          </p>
        </div>
      </div>

      <!-- CARDS MÓDULOS (Placeholders Estéticos) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <div class="group bg-white rounded-3xl p-6 border border-surface-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
          <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out"></div>
          <div class="relative flex flex-col h-full justify-between">
            <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center mb-6 shadow-inner">
              <IconGuests class="w-6 h-6" />
            </div>
            <div>
              <p class="text-[10px] uppercase font-black tracking-widest text-surface-400 mb-1">Socios</p>
              <h3 class="text-2xl font-black text-surface-900 leading-none">Gestión Activa</h3>
            </div>
          </div>
        </div>

        <div class="group bg-white rounded-3xl p-6 border border-surface-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
          <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out"></div>
          <div class="relative flex flex-col h-full justify-between">
            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center mb-6 shadow-inner">
              <IconTarget class="w-6 h-6" />
            </div>
            <div>
              <p class="text-[10px] uppercase font-black tracking-widest text-surface-400 mb-1">Actividades</p>
              <h3 class="text-2xl font-black text-surface-900 leading-none">Clases y Más</h3>
            </div>
          </div>
        </div>

        <div class="group bg-white rounded-3xl p-6 border border-surface-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
          <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out"></div>
          <div class="relative flex flex-col h-full justify-between">
            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mb-6 shadow-inner">
              <IconCalendar class="w-6 h-6" />
            </div>
            <div>
              <p class="text-[10px] uppercase font-black tracking-widest text-surface-400 mb-1">Reservas</p>
              <h3 class="text-2xl font-black text-surface-900 leading-none">On-Demand</h3>
            </div>
          </div>
        </div>

        <div class="group bg-white rounded-3xl p-6 border border-surface-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
          <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out"></div>
          <div class="relative flex flex-col h-full justify-between">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-6 shadow-inner">
              <IconGrid class="w-6 h-6" />
            </div>
            <div>
              <p class="text-[10px] uppercase font-black tracking-widest text-surface-400 mb-1">Espacios</p>
              <h3 class="text-2xl font-black text-surface-900 leading-none">Control Total</h3>
            </div>
          </div>
        </div>

      </div>

      <!-- SECCIÓN EXTRA: MENSAJE O PLACEHOLDER -->
      <div class="bg-white rounded-[2.5rem] border border-surface-200 p-10 flex flex-col md:flex-row items-center gap-8 shadow-sm">
        <div class="w-full md:w-1/3 flex justify-center">
          <div class="w-48 h-48 bg-surface-50 rounded-full border-4 border-white shadow-lg flex items-center justify-center text-surface-300 relative overflow-hidden">
             <!-- Patrón abstracto de fondo -->
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 12px 12px;"></div>
            <svg class="w-20 h-20 relative z-10 text-primary-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
        </div>
        <div class="w-full md:w-2/3 text-center md:text-left">
          <h2 class="text-2xl font-black text-surface-900 mb-3 tracking-tight">Todo listo para operar</h2>
          <p class="text-surface-500 text-sm font-medium leading-relaxed max-w-2xl mb-6">
            Utiliza el menú lateral para acceder a los reportes detallados, la configuración del club y la administración de usuarios. Todas las herramientas que necesitas están a un clic de distancia.
          </p>
        </div>
      </div>

    </div>
  </main>
</template>