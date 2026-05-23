<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { useAgendaStore } from '@/stores/agendaStore';
import { storeToRefs } from 'pinia';

// Iconos estándar
import {
  IconCalendar,
  IconInbox
} from '@/components/icons';

// Componente dinámico de iconos de disciplina
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';

const router = useRouter();
const agendaStore = useAgendaStore();
const { encuentrosInstructorVisibles } = storeToRefs(agendaStore);

const sesionesData = ref([]);
const isLoading = ref(true);
const errorMsg = ref('');

onMounted(async () => {
  try {
    const [sessionsRes] = await Promise.all([
      api.get('/instructor/sessions'),
      agendaStore.fetchInstructorEncuentros(),
    ]);
    if (sessionsRes.data && sessionsRes.data.success) {
      sesionesData.value = sessionsRes.data.data;
    }
  } catch (error) {
    console.error("Error cargando la agenda:", error);
    errorMsg.value = "Hubo un problema al cargar tu agenda semanal.";
  } finally {
    isLoading.value = false;
  }
});

const formatHoraEncuentro = (iso) => {
  if (!iso) return '—';
  return new Date(iso).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
};

const ordenDias = {
  'LUNES': 1, 'MARTES': 2, 'MIERCOLES': 3, 'JUEVES': 4, 'VIERNES': 5, 'SABADO': 6, 'DOMINGO': 7
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

const haySesiones = computed(() => sesionesData.value.length > 0 || encuentrosInstructorVisibles.value.length > 0);

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
              <h1 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight" >Agenda Semanal</h1>
              <p class="text-sm md:text-base font-medium text-surface-500 m-0 mt-2">Próximos eventos y calendario de disponibilidad</p>
          </div>
      </div>

      <!-- Estado de Carga -->
      <section v-if="isLoading" class="flex flex-col items-center justify-center p-12 mt-10">
        <div class="w-10 h-10 rounded-full border-3 border-surface-200 border-t-primary-500 animate-spin"/>
        <p class="text-surface-500 font-semibold mt-4 text-sm tracking-wide">Cargando tu agenda semanal...</p>
      </section>

      <!-- Estado de Error -->
      <section v-else-if="errorMsg" class="flex flex-col items-center justify-center p-8 bg-red-50 rounded-3xl border border-red-200 max-w-lg mx-auto mt-10 text-center shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-red-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p class="text-red-700 font-bold text-lg mb-1">¡Ups! Algo salió mal</p>
        <p class="text-red-600 text-sm font-medium">{{ errorMsg }}</p>
      </section>

      <!-- Encuentros de torneo (filtrados en backend — ver agendaStore.js) -->
      <section v-if="!isLoading && !errorMsg && encuentrosInstructorVisibles.length" class="flex flex-col gap-4 mt-2">
        <div class="flex items-center gap-3">
          <div class="inline-flex items-center gap-2 bg-amber-50 text-amber-800 px-4 py-2 rounded-2xl text-xs font-bold tracking-widest uppercase border border-amber-100">
            Torneos — Hoy
          </div>
          <div class="h-px bg-linear-to-r from-surface-200 to-transparent grow"></div>
        </div>
        <div class="flex flex-col gap-3">
          <div
            v-for="enc in encuentrosInstructorVisibles"
            :key="'enc-' + enc.id_encuentro"
            class="bg-white border border-amber-200 rounded-3xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs"
          >
            <div>
              <h4 class="font-bold text-surface-900">{{ enc.nombre_torneo }}</h4>
              <p class="text-xs text-surface-500 mt-1">{{ enc.espacio || 'Espacio por confirmar' }}</p>
            </div>
            <span class="text-sm font-bold text-surface-900 bg-surface-100 px-4 py-2 rounded-xl border border-surface-200">
              {{ formatHoraEncuentro(enc.fecha_hora_inicio) }}
            </span>
          </div>
        </div>
      </section>

      <!-- Mapeo por Día -->
      <div v-if="!isLoading && !errorMsg && sesionesData.length" class="flex flex-col gap-8 mt-2">
        
        <section v-for="(sesiones, dia) in sesionesAgrupadas" :key="dia" class="flex flex-col gap-4">
          
          <!-- Day separator header with a premium badge -->
          <div class="flex items-center gap-3 mt-4 mb-2">
            <div class="inline-flex items-center gap-2 bg-linear-to-r from-primary-50 to-primary-100/50 text-primary-700 px-4 py-2 rounded-2xl text-xs font-bold tracking-widest uppercase border border-primary-100/70 shadow-3xs">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-primary-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                <line x1="16" x2="16" y1="2" y2="6"/>
                <line x1="8" x2="8" y1="2" y2="6"/>
                <line x1="3" x2="21" y1="10" y2="10"/>
              </svg>
              {{ dia }}
            </div>
            <div class="h-px bg-linear-to-r from-surface-200 to-transparent grow"></div>
          </div>

          <!-- Session list as individual cards -->
          <div class="flex flex-col gap-4">
            <div v-for="session in sesiones" :key="session.id" 
                 class="group bg-white border border-surface-200 rounded-3xl p-5 md:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 cursor-pointer hover:border-primary-300 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 active:scale-[0.99] shadow-xs"
                 @click="handleSessionClick(session)">
              
              <div class="flex items-center gap-5 w-full min-w-0">
                <!-- Round-square container for dynamic discipline icon -->
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center bg-primary-50 border border-primary-100 text-primary-600 shrink-0 group-hover:bg-primary-600 group-hover:text-white group-hover:border-primary-600 transition-colors duration-300 shadow-xs">
                  <DisciplineIcon :name="session.tipo" class="w-7 h-7" />
                </div>

                <!-- Details -->
                <div class="flex flex-col grow min-w-0">
                  <h4 class="font-bold text-surface-900 text-base md:text-lg m-0 group-hover:text-primary-700 transition-colors leading-snug">{{ session.tipo }}</h4>
                  
                  <!-- Location Row with SVG Pin icon -->
                  <div class="text-xs md:text-sm font-medium text-surface-500 mt-1.5 flex items-center gap-1.5 min-w-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-surface-400 shrink-0 group-hover:text-primary-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/>
                      <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span class="truncate">{{ session.espacio }}</span>
                  </div>
                </div>
              </div>

              <!-- Time badge and Capacity -->
              <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-2 shrink-0 border-t sm:border-t-0 border-surface-100 pt-3 sm:pt-0">
                <!-- Horario Badge -->
                <div class="bg-surface-100 rounded-xl px-4 py-2 flex items-center justify-center border border-surface-200 group-hover:bg-primary-50 group-hover:border-primary-100 transition-colors duration-300 shadow-2xs">
                  <span class="font-bold text-surface-900 group-hover:text-primary-900 text-sm leading-none tracking-tight">
                    {{ session.horaInicio }} - {{ session.horaFin }}
                  </span>
                </div>
                
                <!-- Capacidad -->
                <span class="text-xs font-semibold text-surface-500 group-hover:text-surface-600 transition-colors">
                  Cap. {{ session.capacidadMaxima }}
                </span>
              </div>

            </div>
          </div>

        </section>

      </div>

      <!-- Estado de Vacío -->
      <section v-else-if="!isLoading && !errorMsg" class="h-72 flex flex-col items-center justify-center rounded-3xl border border-dashed border-surface-300 bg-white shadow-xs transition-colors mt-6 p-6">
        <div class="w-16 h-16 bg-surface-50 text-surface-400 rounded-full flex items-center justify-center mb-4 border border-surface-100 shadow-2xs">
            <IconInbox class="w-8 h-8" />
        </div>
        <h3 class="text-lg font-bold text-surface-900 mb-1">Sin agenda esta semana</h3>
        <p class="text-surface-500 font-medium text-sm text-center px-4 max-w-sm">No tienes sesiones programadas para los próximos días.</p>
      </section>

    </div>
  </main>
</template>
