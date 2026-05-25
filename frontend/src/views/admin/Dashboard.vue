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
  <main class="w-full bg-surface-50 min-h-screen font-sans pb-16 pt-8 animate-fade-in">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-12">
      
      <!-- HEADER SALUDO -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-surface-200/60 pb-6">
        <div>
          <h1 class="text-3xl md:text-4xl font-black text-surface-900 tracking-tight m-0">
            {{ greeting }}, <span class="text-primary-700">{{ profileStore.fullName.split(' ')[0] || 'Administrador' }}</span>
          </h1>
          <p class="text-sm font-medium text-surface-500 mt-2 m-0 max-w-xl">
            Bienvenido al Panel de Control de Gerencia. Aquí puedes administrar las operaciones de la ludoteca, torneos, socios y más.
          </p>
        </div>
        <!-- Indicador de Estado Activo de Gerencia -->
        <div class="flex items-center gap-2 self-start md:self-center px-4 py-2 bg-white rounded-2xl border border-surface-200 shadow-xs">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
          </span>
          <span class="text-xs font-black text-surface-700 tracking-wider uppercase">Sesión Activa</span>
        </div>
      </div>

      <!-- CARDS MÓDULOS (Bento Grid Estético) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- CARD SOCIOS -->
        <div class="group bg-white rounded-[1.8rem] p-8 border border-surface-200/80 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.02)] hover:shadow-[0_20px_40px_-15px_rgba(0,0,0,0.06)] hover:-translate-y-1 active:scale-[0.98] active:translate-y-0 transition-all duration-300 ease-out cursor-pointer relative overflow-hidden">
          <div class="absolute -right-8 -top-8 w-28 h-28 bg-primary-50/50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out"></div>
          <div class="relative flex flex-col h-full justify-between min-h-32">
            <div class="w-12 h-12 rounded-2xl bg-primary-50 text-primary-700 flex items-center justify-center mb-6 shadow-inner border border-primary-100/50">
              <IconGuests class="w-6 h-6" />
            </div>
            <div>
              <p class="text-[10px] uppercase font-black tracking-widest text-surface-400 mb-1.5">Socios</p>
              <h3 class="text-2xl font-black text-surface-900 leading-none tracking-tight">Gestión Activa</h3>
            </div>
          </div>
        </div>

        <!-- CARD ACTIVIDADES -->
        <div class="group bg-white rounded-[1.8rem] p-8 border border-surface-200/80 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.02)] hover:shadow-[0_20px_40px_-15px_rgba(0,0,0,0.06)] hover:-translate-y-1 active:scale-[0.98] active:translate-y-0 transition-all duration-300 ease-out cursor-pointer relative overflow-hidden">
          <div class="absolute -right-8 -top-8 w-28 h-28 bg-purple-50/50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out"></div>
          <div class="relative flex flex-col h-full justify-between min-h-32">
            <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mb-6 shadow-inner border border-purple-100/50">
              <IconTarget class="w-6 h-6" />
            </div>
            <div>
              <p class="text-[10px] uppercase font-black tracking-widest text-surface-400 mb-1.5">Actividades</p>
              <h3 class="text-2xl font-black text-surface-900 leading-none tracking-tight">Clases y Más</h3>
            </div>
          </div>
        </div>

        <!-- CARD RESERVAS -->
        <div class="group bg-white rounded-[1.8rem] p-8 border border-surface-200/80 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.02)] hover:shadow-[0_20px_40px_-15px_rgba(0,0,0,0.06)] hover:-translate-y-1 active:scale-[0.98] active:translate-y-0 transition-all duration-300 ease-out cursor-pointer relative overflow-hidden">
          <div class="absolute -right-8 -top-8 w-28 h-28 bg-amber-50/50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out"></div>
          <div class="relative flex flex-col h-full justify-between min-h-32">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-6 shadow-inner border border-amber-100/50">
              <IconCalendar class="w-6 h-6" />
            </div>
            <div>
              <p class="text-[10px] uppercase font-black tracking-widest text-surface-400 mb-1.5">Reservas</p>
              <h3 class="text-2xl font-black text-surface-900 leading-none tracking-tight">On-Demand</h3>
            </div>
          </div>
        </div>

        <!-- CARD ESPACIOS -->
        <div class="group bg-white rounded-[1.8rem] p-8 border border-surface-200/80 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.02)] hover:shadow-[0_20px_40px_-15px_rgba(0,0,0,0.06)] hover:-translate-y-1 active:scale-[0.98] active:translate-y-0 transition-all duration-300 ease-out cursor-pointer relative overflow-hidden">
          <div class="absolute -right-8 -top-8 w-28 h-28 bg-emerald-50/50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out"></div>
          <div class="relative flex flex-col h-full justify-between min-h-32">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-6 shadow-inner border border-emerald-100/50">
              <IconGrid class="w-6 h-6" />
            </div>
            <div>
              <p class="text-[10px] uppercase font-black tracking-widest text-surface-400 mb-1.5">Espacios</p>
              <h3 class="text-2xl font-black text-surface-900 leading-none tracking-tight">Control Total</h3>
            </div>
          </div>
        </div>

      </div>

      <!-- SECCIÓN EXTRA: MENSAJE O PLACEHOLDER -->
      <div class="bg-white rounded-[2.2rem] border border-surface-200/80 p-10 flex flex-col md:flex-row items-center gap-10 shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)]">
        <div class="w-full md:w-1/3 flex justify-center">
          <div class="w-40 h-40 bg-surface-50 rounded-[2rem] border border-surface-200 flex items-center justify-center text-surface-300 relative overflow-hidden shadow-inner">
             <!-- Patrón abstracto de fondo -->
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#cbd5e1 1.5px, transparent 1.5px); background-size: 14px 14px;"></div>
            <svg class="w-16 h-16 relative z-10 text-primary-500/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
        </div>
        <div class="w-full md:w-2/3 text-center md:text-left space-y-4">
          <h2 class="text-2xl font-black text-surface-900 tracking-tight">Todo listo para operar</h2>
          <p class="text-surface-500 text-sm font-medium leading-relaxed max-w-2xl">
            Utiliza el menú lateral para acceder a los reportes detallados, la configuración del club y la administración de usuarios. Todas las herramientas que necesitas están a un clic de distancia.
          </p>
        </div>
      </div>

    </div>
  </main>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>