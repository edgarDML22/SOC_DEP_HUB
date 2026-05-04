<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';

import {
  IconCalendar,
  IconUser,
  IconInbox
} from '@/components/icons';

const router = useRouter();

const sesionesData = ref([]);
const isLoading = ref(true);
const errorMsg = ref('');

onMounted(async () => {
  try {
    const response = await api.get('/instructor/sessions');
    if (response.data && response.data.success) {
      sesionesData.value = response.data.data;
    }
  } catch (error) {
    console.error("Error cargando la agenda:", error);
    errorMsg.value = "Hubo un problema al cargar tus sesiones.";
  } finally {
    isLoading.value = false;
  }
});

const ordenDias = {
  'LUNES': 1, 'MARTES': 2, 'MIÉRCOLES': 3, 'JUEVES': 4, 'VIERNES': 5, 'SÁBADO': 6, 'DOMINGO': 7
};

const sesionesAgrupadas = computed(() => {
  const grupos = {};
  sesionesData.value.forEach(sesion => {
    if (!grupos[sesion.diaSemana]) {
      grupos[sesion.diaSemana] = [];
    }
    grupos[sesion.diaSemana].push(sesion);
  });

  const diasOrdenados = Object.keys(grupos).sort((a, b) => ordenDias[a] - ordenDias[b]);

  const resultado = {};
  diasOrdenados.forEach(dia => {
    resultado[dia] = grupos[dia].sort((a, b) => a.horaInicio.localeCompare(b.horaInicio));
  });

  return resultado;
});

const haySesiones = computed(() => sesionesData.value.length > 0);

const handleSessionClick = (sesion) => {
  router.push({
    path: `/instructor/sessions/${sesion.id}`,
    state: { sessionData: JSON.stringify(sesion) }
  });
};
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans p-4 md:p-6 lg:p-8 pb-24 lg:pb-8 flex justify-center">
    
    <div class="w-full max-w-5xl flex flex-col gap-6 animate-fade-in">

      <div class="flex flex-col md:flex-row md:items-end justify-between gap-5 border-b border-surface-200 pb-5">
          <div>
              <p class="text-sm md:text-base font-bold text-surface-400 m-0 tracking-widest uppercase mb-1">Agenda Semanal</p>
              <h1 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight" >Sesiones Disponibles</h1>
          </div>
      </div>

      <!-- Estado de Carga -->
      <section v-if="isLoading" class="flex flex-col items-center justify-center p-12">
        <LoadingSpinner />
        <p class="text-surface-500 font-medium mt-4">Cargando agenda semanal...</p>
      </section>

      <!-- Estado de Error -->
      <section v-else-if="errorMsg" class="flex flex-col items-center justify-center p-12 bg-red-50 rounded-3xl border border-red-200">
        <p class="text-red-600 font-bold">{{ errorMsg }}</p>
      </section>

      <!-- Mapeo por Día -->
      <div v-else-if="haySesiones" class="flex flex-col gap-8">
        
        <section v-for="(sesiones, dia) in sesionesAgrupadas" :key="dia" class="flex flex-col gap-3">
          
          <header class="flex items-center gap-2 px-2">
            <IconCalendar class="w-5 h-5 text-primary-600" />
            <h2 class="text-sm font-bold text-surface-600 tracking-wider uppercase m-0">{{ dia }}</h2>
          </header>

          <div class="bg-white rounded-3xl border border-surface-200 p-3 sm:p-5 shadow-sm flex flex-col gap-3">
            
            <div v-for="session in sesiones" :key="session.id" 
                 class="group bg-white border border-surface-200 rounded-2xl p-3 md:p-4 flex flex-row items-center justify-between gap-4 cursor-pointer hover:border-primary-300 hover:shadow-md hover:-translate-y-0.5 transition-all active:scale-95"
                 @click="handleSessionClick(session)">
              
              <div class="flex items-center gap-4 w-full min-w-0">
                <!-- Horario -->
                <div class="bg-surface-100 rounded-xl px-3 py-2 flex flex-col items-center justify-center min-w-[70px] shrink-0 border border-surface-200 group-hover:bg-primary-50 group-hover:border-primary-100 transition-colors">
                  <span class="font-bold text-surface-900 group-hover:text-primary-900 text-sm md:text-base leading-none">{{ session.horaInicio }}</span>
                  <span class="text-[10px] font-medium text-surface-500 group-hover:text-primary-600 mt-1 uppercase">{{ session.horaFin }}</span>
                </div>

                <!-- Detalles -->
                <div class="flex flex-col grow min-w-0">
                  <h4 class="font-bold text-surface-900 text-sm md:text-base m-0 mb-0.5 truncate">{{ session.espacio }}</h4>
                  <p class="text-xs font-medium text-surface-500 m-0 truncate">Capacidad: {{ session.capacidadMaxima }} px.</p>
                </div>
              </div>

              <!-- Tipo y Flecha -->
              <div class="flex items-center gap-3 shrink-0">
                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider" 
                      :class="{
                        'bg-primary-100 text-primary-800': session.statusType === 'info',
                        'bg-green-100 text-green-800': session.statusType === 'success',
                        'bg-surface-100 text-surface-800': !session.statusType || (session.statusType !== 'info' && session.statusType !== 'success')
                      }">
                  {{ session.tipo }}
                </span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-surface-400 group-hover:text-primary-600 group-hover:translate-x-1 transition-all" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
              </div>

            </div>

          </div>
        </section>

      </div>

      <!-- Estado de Vacío -->
      <section v-else class="h-64 flex flex-col items-center justify-center rounded-3xl border border-dashed border-surface-300 bg-white shadow-sm transition-colors mt-4">
        <div class="w-16 h-16 bg-surface-100 text-surface-400 rounded-full flex items-center justify-center mb-4">
            <IconInbox class="w-8 h-8" />
        </div>
        <h3 class="text-lg font-bold text-surface-900 mb-1">Sin agenda esta semana</h3>
        <p class="text-surface-500 font-medium text-sm text-center px-4">No tienes sesiones programadas para los próximos días.</p>
      </section>

    </div>
  </main>
</template>
