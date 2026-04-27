<script setup>
import { ref, markRaw, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { useInstructorStore } from '@/stores/profiles/instructorStore';

// Importación de componentes SVG
import {
  IconCalendar,
  IconClock,
  IconHourglass,
  IconUser,
  IconInbox,
  IconBaby
} from '@/components/icons';

const router = useRouter();
const profileStore = useInstructorStore();

// Datos reactivos para las estadísticas superiores
const stats = ref([
  { id: 1, value: '-', label: 'Sesiones hoy', icon: markRaw(IconCalendar), iconColor: 'text-green' },
  { id: 2, value: '-', label: 'Prox. 2 horas', icon: markRaw(IconClock), iconColor: 'text-blue' },
  { id: 3, value: '-', label: 'Pendientes', icon: markRaw(IconHourglass), iconColor: 'text-yellow' },
  { id: 4, value: '-', label: 'Total Inscritos', icon: markRaw(IconUser), iconColor: 'text-gray' }
]);

// Datos reactivos para la lista de sesiones
const todaySessions = ref([]);
const nextSession = ref(null);
const isLoading = ref(true);

onMounted(async () => {
  try {
    const response = await api.get('/instructor/sessions');
    if (response.data && response.data.success) {
      const allSessions = response.data.data;

      // Obtener qué día es hoy
      const dias = ['DOMINGO', 'LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO'];
      const todayName = dias[new Date().getDay()];

      // Filtrar sesiones del día de hoy
      const sesionesMismoDia = allSessions.filter(s => s.diaSemana === todayName);

      // Ordenar por hora
      sesionesMismoDia.sort((a, b) => a.horaInicio.localeCompare(b.horaInicio));

      const nowString = new Date().toTimeString().substring(0, 5); // 'HH:MM'
      const prox = sesionesMismoDia.find(s => s.horaInicio >= nowString);

      // Alimentar lista mapeada final
      todaySessions.value = sesionesMismoDia.map(s => ({
        id: s.id,
        startTime: s.horaInicio,
        endTime: s.horaFin,
        client: s.tipo,
        location: s.espacio,
        status: s.status,
        statusType: s.statusType,
        inscritos: s.inscritos || 0, // Gente que ya pasó o está asistiendo
        originalData: s // Guardamos el objeto original para vue-router
      }));

      // Set nextSession mapped if found
      nextSession.value = todaySessions.value.find(s => s.startTime >= nowString) || null;

      // Llenamos las estadisticas reales
      stats.value[0].value = sesionesMismoDia.length; // Sesiones Hoy

      // Lógica de próximas horas (simplificado)
      const currentHour = new Date().getHours();
      let proximas = 0;
      sesionesMismoDia.forEach(s => {
        const sHour = parseInt(s.horaInicio.substring(0, 2));
        if (sHour >= currentHour && sHour <= currentHour + 2) proximas++;
      });
      stats.value[1].value = proximas;

      stats.value[2].value = sesionesMismoDia.filter(s => s.status === 'Programada').length; // Pendientes

      // Sumador de alumnos para la estadística (en base a la gente que asiste o ya pasó la clase)
      stats.value[3].value = todaySessions.value.reduce((sum, current) => sum + current.inscritos, 0);
    }
  } catch (error) {
    console.error("Error cargando agenda de hoy:", error);
  } finally {
    isLoading.value = false;
  }
});

const handleGoToDetails = (sessionObj) => {
  router.push({
    path: `/instructor/sessions/${sessionObj.id}`,
    state: { sessionData: JSON.stringify(sessionObj.originalData) }
  });
};
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans pb-24 md:pb-8">
    <div class="max-w-5xl mx-auto p-4 md:p-8 space-y-6 md:space-y-8">
      
      <!-- ESTADO DE CARGA -->
      <div v-if="isLoading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>

      <template v-else>
        <!-- SECCIÓN 1: BIENVENIDA -->
        <div class="flex flex-col gap-1 md:gap-1.5 pt-2 animate-fade-in">
          <p class="text-surface-500 font-medium text-sm md:text-base m-0">Bienvenido de vuelta,</p>
          <div class="flex items-center gap-3">
              <h2 class="text-2xl md:text-3xl font-bold text-surface-900 tracking-tight m-0 drop-shadow-sm">
                {{ profileStore.fullName || 'Instructor' }}
              </h2>
              <span v-if="profileStore.isCuidador" class="bg-primary-100 text-primary-700 px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1.5 border border-primary-200">
                  <IconBaby class="w-3.5 h-3.5" /> Ludoteca
              </span>
          </div>
        </div>

        <div v-if="profileStore.isCuidador" class="animate-fade-in space-y-6">
            <div class="bg-linear-to-br from-primary-800 to-primary-600 text-white rounded-3xl md:rounded-[2.5rem] p-6 md:p-8 relative overflow-hidden shadow-xl shadow-primary-700/20 flex flex-col md:flex-row md:items-center justify-between gap-6 transition-all hover:shadow-2xl hover:shadow-primary-700/30">
              <!-- Elementos decorativos -->
              <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
              <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
              
              <div class="relative z-10 flex-1">
                <div class="flex items-center gap-3 mb-4">
                  <span class="bg-white/20 backdrop-blur-md text-white border border-white/30 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider shadow-sm flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span> Turno Activo
                  </span>
                </div>
                
                <h3 class="text-2xl md:text-3xl font-bold mb-2 tracking-tight">¡Eres el Cuidador de hoy!</h3>
                <p class="text-primary-100 font-medium text-sm md:text-base opacity-90 max-w-sm leading-relaxed">
                  Tu agenda de clases se ha pausado para priorizar el control de menores en la ludoteca.
                </p>
              </div>

              <div class="relative z-10 shrink-0 w-full md:w-auto">
                <router-link to="/instructor/ludoteca" class="w-full sm:w-auto bg-white text-primary-700 hover:bg-primary-50 rounded-xl px-8 py-4 font-bold transition-all active:scale-95 shadow-lg flex items-center justify-center gap-3 group">
                  Ir al Tablero Operativo 
                  <IconBaby class="w-5 h-5 group-hover:rotate-12 transition-transform" />
                </router-link>
              </div>
            </div>

            <div class="bg-white rounded-3xl border border-dashed border-surface-300 p-12 flex flex-col items-center justify-center text-center space-y-4 opacity-60">
                <div class="w-20 h-20 bg-surface-100 rounded-full flex items-center justify-center">
                    <IconBaby class="w-10 h-10 text-surface-400" />
                </div>
                <div class="max-w-xs">
                    <h4 class="text-surface-900 font-bold">Modo Ludoteca Activo</h4>
                    <p class="text-surface-500 text-sm">Puedes acceder a todas las funciones desde la sección de Ludoteca en tu barra de navegación.</p>
                </div>
            </div>
        </div>

        <template v-else>
            <!-- SECCIÓN 2: PRÓXIMA SESIÓN (Highlight) -->
            <div class="bg-linear-to-br from-primary-800 to-primary-600 text-white rounded-3xl md:rounded-[2.5rem] p-6 md:p-8 relative overflow-hidden shadow-xl shadow-primary-700/20 flex flex-col md:flex-row md:items-center justify-between gap-6 transition-all hover:shadow-2xl hover:shadow-primary-700/30 animate-fade-in">
              <!-- Elementos decorativos (Glassmorphism blobs) -->
              <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
              <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
              
              <div class="relative z-10 flex-1">
                <div class="flex items-center gap-3 mb-4">
                  <span class="bg-white/20 backdrop-blur-md text-white border border-white/30 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider shadow-sm">
                    Próxima Sesión
                  </span>
                </div>
                
                <div v-if="!nextSession">
                  <h3 class="text-2xl md:text-3xl font-bold mb-2 tracking-tight">Ninguna sesión inminente</h3>
                  <p class="text-primary-100 font-medium text-sm md:text-base opacity-90 max-w-sm leading-relaxed">
                    No tienes sesiones programadas para las próximas horas.
                  </p>
                </div>
                <div v-else>
                  <h3 class="text-2xl md:text-3xl font-bold mb-2 tracking-tight">{{ nextSession.client }}</h3>
                  <p class="text-primary-100 font-medium text-sm md:text-base opacity-90 max-w-sm leading-relaxed">
                    {{ nextSession.location }} • {{ nextSession.startTime }} a {{ nextSession.endTime }}
                  </p>
                </div>
              </div>

              <div class="relative z-10 shrink-0 flex flex-col sm:flex-row gap-3 w-full md:w-auto" v-if="nextSession">
                <button @click="handleGoToDetails(nextSession)" class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-8 py-3.5 font-bold transition-all active:scale-95 shadow-lg shadow-black/20 text-center border border-primary-500 flex items-center justify-center gap-2 hover:-translate-y-0.5">
                  Ver detalles <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
              </div>
            </div>

            <!-- SECCIÓN 3: ESTADÍSTICAS RÁPIDAS -->
            <div class="animate-fade-in">
              <h3 class="text-xl md:text-2xl font-bold text-surface-900 mb-5 tracking-tight">Resumen de hoy</h3>
              
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-5">
                <div v-for="stat in stats" :key="stat.id" class="group bg-white rounded-3xl border border-surface-200 p-5 md:p-6 aspect-square flex flex-col items-center justify-center text-center shadow-sm hover:shadow-xl hover:border-primary-200 hover:-translate-y-1.5 transition-all duration-300 ease-out">
                  <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-colors mb-4 mt-2">
                    <component :is="stat.icon" class="w-7 h-7 md:w-8 md:h-8" />
                  </div>
                  <span class="font-bold text-xl md:text-2xl text-surface-900 group-hover:text-primary-700 transition-colors leading-tight">{{ stat.value }}</span>
                  <span class="font-medium text-surface-500 text-xs md:text-sm mt-1 leading-tight">{{ stat.label }}</span>
                </div>
              </div>
            </div>

            <!-- SECCIÓN 4: SESIONES DE HOY -->
            <div class="bg-white rounded-3xl border border-surface-200 p-6 md:p-8 shadow-sm animate-fade-in">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl md:text-2xl font-bold text-surface-900 tracking-tight">Sesiones de hoy</h3>
                <router-link to="/instructor/sessions" class="text-primary-600 font-semibold text-sm hover:text-primary-700 hover:underline transition-all hidden md:block">
                  Ver Semana &rarr;
                </router-link>
              </div>
              
              <div v-if="todaySessions.length === 0" class="h-32 flex items-center justify-center rounded-2xl border border-dashed border-surface-300 bg-surface-50 transition-colors hover:bg-surface-100">
                <p class="text-surface-500 font-medium text-xs md:text-sm tracking-widest text-center px-4 uppercase">Sin sesiones registradas este día</p>
              </div>
              
              <div v-else class="flex flex-col gap-3">
                <article v-for="session in todaySessions" :key="session.id" @click="handleGoToDetails(session)" class="bg-surface-50 hover:bg-surface-100 border border-surface-200 rounded-2xl p-4 flex items-center gap-4 cursor-pointer transition-all hover:border-primary-400 hover:-translate-y-0.5 active:scale-95 group">
                  <div class="bg-white border border-surface-200 rounded-xl px-3 py-2 flex flex-col items-center justify-center min-w-[70px] shadow-sm">
                    <span class="font-bold text-surface-900 text-sm">{{ session.startTime }}</span>
                    <span class="text-xs text-surface-500">{{ session.endTime }}</span>
                  </div>

                  <div class="flex-1 flex flex-col">
                    <h4 class="font-bold text-surface-900 text-base m-0">{{ session.client }}</h4>
                    <p class="text-surface-500 text-sm m-0 line-clamp-1">{{ session.location }}</p>
                  </div>

                  <div class="flex items-center gap-3">
                    <span class="px-3 py-1.5 rounded-full text-xs font-bold text-white capitalize shadow-sm" :class="{
                      'bg-green-500': session.statusType === 'success',
                      'bg-blue-500': session.statusType === 'info',
                      'bg-primary-600': session.statusType === 'success-dark',
                      'bg-surface-400': !['success', 'info', 'success-dark'].includes(session.statusType)
                    }">
                      {{ session.status }}
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-surface-400 group-hover:text-primary-600 transition-colors hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </div>
                </article>
              </div>
              
              <!-- Botón móvil para ver semana -->
              <router-link to="/instructor/sessions" class="block text-center w-full mt-4 bg-surface-50 hover:bg-surface-100 text-surface-700 border border-surface-200 rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 md:hidden">
                  Ver Semana
              </router-link>
            </div>
        </template>
      </template>
    </div>
  </main>
</template>
