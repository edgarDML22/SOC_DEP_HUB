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
  IconInbox
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
  <main class="home-instructor">

    <!-- Saludo personalizado con datos del profileStore -->
    <header class="home-header">
      <div>
        <p class="greeting-label">Bienvenido de vuelta,</p>
        <h1 class="greeting-name">{{ profileStore.fullName || 'Instructor' }}</h1>
      </div>
    </header>

    <!-- Estado de carga centralizado -->
    <section v-if="isLoading" class="flex flex-col items-center py-12 gap-4">
      <div class="w-10 h-10 border-4 border-surface-200 border-t-primary-600 rounded-full animate-spin"></div>
      <span class="text-surface-500 font-medium">Cargando dashboard...</span>
    </section>

    <div v-else class="flex flex-col gap-8">
      <!-- Stats -->
      <section class="grid grid-cols-2 gap-4">
        <article v-for="stat in stats" :key="stat.id" class="bg-white border border-surface-200 rounded-2xl p-4 flex items-center gap-4 shadow-sm">
          <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :class="{
            'bg-green-100 text-green-600': stat.iconColor === 'text-green',
            'bg-blue-100 text-blue-600': stat.iconColor === 'text-blue',
            'bg-amber-100 text-amber-600': stat.iconColor === 'text-yellow',
            'bg-surface-100 text-surface-500': stat.iconColor === 'text-gray',
          }">
            <component :is="stat.icon" class="w-5 h-5" />
          </div>
          <div class="flex flex-col">
            <span class="text-xl font-black text-surface-900 leading-none">{{ stat.value }}</span>
            <span class="text-[10px] font-bold text-surface-500 uppercase tracking-tight">{{ stat.label }}</span>
          </div>
        </article>
      </section>

      <!-- PRÓXIMA SESIÓN -->
      <section>
        <h2 class="section-title">PRÓXIMA SESIÓN</h2>

        <div v-if="!nextSession" class="empty-state">
          <div class="empty-icon-circle">
            <IconInbox class="w-8 h-8" />
          </div>
          <h3 class="empty-title">Ninguna sesión inminente</h3>
          <p class="empty-text">No tienes sesiones programadas para las próximas horas.</p>
        </div>

        <article v-else class="session-card" @click="handleGoToDetails(nextSession)">
          <div class="time-block">
            <span class="time-start">{{ nextSession.startTime }}</span>
            <span class="time-end">{{ nextSession.endTime }}</span>
          </div>

          <div class="session-details">
            <h4 class="client-name">{{ nextSession.client }}</h4>
            <p class="location-name">{{ nextSession.location }}</p>
          </div>

          <div class="session-actions">
            <span class="status-badge" :class="{
              'badge-info': nextSession.statusType === 'info',
              'badge-success': nextSession.statusType === 'success' || nextSession.statusType === 'success-dark'
            }">
              {{ nextSession.status }}
            </span>
            <span class="arrow-right">›</span>
          </div>
        </article>
      </section>

      <!-- SESIONES DE HOY -->
      <section>
        <header class="flex items-center justify-between mb-4">
          <h2 class="section-title !mb-0">SESIONES DE HOY</h2>
          <router-link to="/instructor/sessions" class="text-xs font-bold text-primary-600 hover:underline">
            Ver Semana &rarr;
          </router-link>
        </header>

        <div class="sessions-list" v-if="todaySessions.length > 0">
          <article v-for="session in todaySessions" :key="session.id" class="session-card"
            @click="handleGoToDetails(session)">
            <div class="time-block">
              <span class="time-start">{{ session.startTime }}</span>
              <span class="time-end">{{ session.endTime }}</span>
            </div>

            <div class="session-details">
              <h4 class="client-name">{{ session.client }}</h4>
              <p class="location-name">{{ session.location }}</p>
            </div>

            <div class="session-actions">
              <span class="status-badge" :class="{
                'badge-info': session.statusType === 'info',
                'badge-success': session.statusType === 'success' || session.statusType === 'success-dark'
              }">
                {{ session.status }}
              </span>
              <span class="arrow-right">›</span>
            </div>
          </article>
        </div>

        <!-- Estado si no hay sesiones hoy -->
        <div v-else class="empty-state !p-8">
          <p class="empty-text">Sin sesiones registradas este día.</p>
        </div>
      </section>
    </div>

  </main>
</template>

<style scoped>
/* No styles needed, using global system */
</style>
