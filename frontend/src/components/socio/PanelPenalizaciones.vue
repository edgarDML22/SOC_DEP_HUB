<script setup>
import { computed } from 'vue';
import { useProfileStore } from '@/stores/profiles/socioStore';

const socioStore = useProfileStore();

const isFuture = (fecha) => {
  if (!fecha) return false;
  const str = String(fecha);
  const datePart = str.split(/[ T]/)[0];
  const d = new Date(`${datePart}T00:00:00`);
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  return d >= hoy;
};

const estadoLudoteca = computed(() => {
  const fecha = socioStore.fechaFinPenalizacionLudoteca;
  const rawStatus = socioStore.profileData?.estatus_penalizacion;
  const isPenalized = rawStatus === 'PENALIZADO_LUDOTECA' || rawStatus === 'PENALIZADO_AMBOS';

  if (isPenalized || isFuture(fecha)) {
    const formatted = socioStore.formatFechaLiberacion(fecha);
    const dateText = formatted ? ` hasta ${formatted}` : '';
    return { activa: true, texto: `Penalizado${dateText}` };
  }
  return { activa: false, texto: 'SIN PENALIZACION' };
});

const estadoReserva = computed(() => {
  const fecha = socioStore.fechaFinPenalizacionReserva;
  const rawStatus = socioStore.profileData?.estatus_penalizacion;
  const isPenalized = rawStatus === 'PENALIZADO_RESERVA' || rawStatus === 'PENALIZADO_AMBOS';

  if (isPenalized || isFuture(fecha)) {
    const formatted = socioStore.formatFechaLiberacion(fecha);
    const dateText = formatted ? ` hasta ${formatted}` : '';
    return { activa: true, texto: `Penalizado${dateText}` };
  }
  return { activa: false, texto: 'SIN PENALIZACION' };
});

const showRetrasosLudoteca = computed(() => {
  const plan = socioStore.profileData?.modalidad_plan;
  const retrasos = socioStore.profileData?.retrasos_acumulados_ludoteca || 0;
  return (plan === 'FAMILIAR' || plan === 'FAMILIAR_PLUS') && retrasos > 0;
});
</script>

<template>
  <div class="bg-white rounded-3xl border border-surface-200 p-5 shadow-sm group hover:border-primary-200 transition-colors">
    <div class="flex items-center gap-3 border-b border-surface-100 pb-4 mb-4">
      <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-primary-600 shrink-0 shadow-sm border border-surface-100 group-hover:bg-primary-50 group-hover:border-primary-200 transition-all">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="17" r="1" fill="currentColor"></circle>
          <path d="M12 10L12 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
          <path d="M3.44722 18.1056L10.2111 4.57771C10.9482 3.10361 13.0518 3.10362 13.7889 4.57771L20.5528 18.1056C21.2177 19.4354 20.2507 21 18.7639 21H5.23607C3.7493 21 2.78231 19.4354 3.44722 18.1056Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
      </div>
      <div>
        <h3 class="text-base font-bold text-surface-900 m-0 leading-tight">Penalizaciones</h3>
        <p class="text-[11px] font-medium text-surface-500 m-0 mt-0.5 uppercase tracking-wider">Estado de cuenta</p>
      </div>
    </div>

    <div class="flex flex-col gap-4">
      <!-- Ludoteca -->
      <div class="flex flex-col gap-1.5">
        <div class="flex justify-between items-center">
          <span class="text-sm font-semibold text-surface-700">Ludoteca</span>
          <span :class="estadoLudoteca.activa ? 'text-red-600' : 'text-green-600'" class="text-xs font-bold uppercase tracking-wide">
            {{ estadoLudoteca.texto }}
          </span>
        </div>
        <div v-if="showRetrasosLudoteca" class="bg-orange-50 border border-orange-200 rounded-lg p-2 flex justify-between items-center mt-1">
          <span class="text-xs font-medium text-orange-800">Retrasos acumulados</span>
          <span class="bg-orange-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
            {{ socioStore.profileData?.retrasos_acumulados_ludoteca }}
          </span>
        </div>
      </div>

      <!-- Reservas -->
      <div class="flex flex-col gap-1.5">
        <div class="flex justify-between items-center">
          <span class="text-sm font-semibold text-surface-700">Reservaciones</span>
          <span :class="estadoReserva.activa ? 'text-red-600' : 'text-green-600'" class="text-xs font-bold uppercase tracking-wide">
            {{ estadoReserva.texto }}
          </span>
        </div>
        <div v-if="(socioStore.profileData?.contador_no_shows || 0) > 0" class="bg-amber-50 border border-amber-200 rounded-lg p-2 flex justify-between items-center mt-1">
          <span class="text-xs font-medium text-amber-800">Faltas acumuladas (No Show)</span>
          <span class="bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
            {{ socioStore.profileData?.contador_no_shows }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
