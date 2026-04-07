<script setup>
import { ref, markRaw, onMounted } from 'vue';
import api from '@/services/api';
import InstructorNavBar from '@/components/instructor/InstructorNavBar.vue';

// Importación de componentes SVG
import { 
  IconCalendar, 
  IconClock, 
  IconHourglass, 
  IconUser, 
  IconInbox 
} from '@/components/icons';

// Datos reactivos para las estadísticas superiores
const stats = ref([
  { id: 1, value: '-', label: 'Sesiones hoy', icon: markRaw(IconCalendar), iconColor: 'text-green' },
  { id: 2, value: '-', label: 'Prox. 2 horas', icon: markRaw(IconClock), iconColor: 'text-blue' },
  { id: 3, value: '-', label: 'Pendientes', icon: markRaw(IconHourglass), iconColor: 'text-yellow' },
  { id: 4, value: '-', label: 'Total Inscritos', icon: markRaw(IconUser), iconColor: 'text-gray' }
]);

// Datos reactivos para la lista de sesiones
const todaySessions = ref([]);
const isLoading = ref(true);

onMounted(async () => {
  try {
    const response = await api.get('/v1/instructor/dashboard');
    if (response.data && response.data.success) {
      const dataStats = response.data.data.stats;
      
      stats.value[0].value = dataStats.sesionesHoy;
      stats.value[1].value = dataStats.proximas2Horas;
      stats.value[2].value = dataStats.pendientes;
      stats.value[3].value = dataStats.totalInscritos;
      
      todaySessions.value = response.data.data.todaySessions;
    }
  } catch (error) {
    console.error("Error cargando dashboard:", error);
  } finally {
    isLoading.value = false;
  }
});
</script>

<template>
  <InstructorNavBar />
  <main class="home-instructor">
    
    <section class="stats-section">
      <article v-for="stat in stats" :key="stat.id" class="stat-card">
        <div class="stat-icon-wrapper" :class="stat.iconColor">
          <component :is="stat.icon" class="icon-svg" />
        </div>
        <div class="stat-info">
          <span class="stat-value">{{ stat.value }}</span>
          <span class="stat-label">{{ stat.label }}</span>
        </div>
      </article>
    </section>

    <section class="next-session-section">
      <h2 class="section-title">PRÓXIMA SESIÓN</h2>
      
      <div class="empty-state">
        <div class="empty-icon-circle">
          <IconInbox class="icon-svg-large" />
        </div>
        <h3 class="empty-title">No hay sesiones próximas</h3>
        <p class="empty-text">No tienes más sesiones programadas para hoy.</p>
      </div>
    </section>

    <section class="today-sessions-section">
      <header class="section-header">
        <h2 class="section-title">SESIONES DE HOY</h2>
        <router-link to="/instructor/sessions" class="link-view-all">
          Ver todo &rarr;
        </router-link>
      </header>

      <div class="sessions-list">
        <article v-for="session in todaySessions" :key="session.id" class="session-card">
          
          <div class="time-block">
            <span class="time-start">{{ session.startTime }}</span>
            <span class="time-end">{{ session.endTime }}</span>
          </div>

          <div class="session-details">
            <h4 class="client-name">{{ session.client }}</h4>
            <p class="location-name">{{ session.location }}</p>
          </div>

          <div class="session-actions">
            <span class="status-badge" :class="`badge-${session.statusType}`">
              {{ session.status }}
            </span>
            <span class="arrow-right">›</span>
          </div>

        </article>
      </div>
    </section>

  </main>
</template>

<style scoped>
/* Variables base integradas al sistema */
.home-instructor {
  background-color: var(--p-surface-50, #f8fafc);
  padding: 1rem;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  font-family: inherit;
  padding-bottom: 90px; /* Espacio para el bottom nav bar */
}

/* Títulos de sección generales */
.section-title {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--p-surface-500, #6b7280);
  letter-spacing: 0.5px;
  margin-bottom: 0.75rem;
  text-transform: uppercase;
}

/* =========================================
   SECCIÓN 1: ESTADÍSTICAS
   ========================================= */
.stats-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

@media (min-width: 640px) {
  .stats-section {
    flex-direction: row;
    flex-wrap: wrap;
  }
  .stat-card {
    flex: 1 1 calc(50% - 0.75rem);
  }
}

.stat-card {
  background-color: #ffffff;
  border: 1px solid var(--p-surface-200, #e2e8f0);
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.stat-icon-wrapper {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-svg {
  width: 22px;
  height: 22px;
}

/* Colores simulados para iconos */
.text-green { color: #10b981; background-color: #d1fae5; }
.text-blue { color: #3b82f6; background-color: #dbeafe; }
.text-yellow { color: #f59e0b; background-color: #fef3c7; }
.text-gray { color: var(--p-surface-500, #64748b); background-color: var(--p-surface-100, #f1f5f9); }

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--p-surface-900, #111827);
  line-height: 1.2;
}

.stat-label {
  font-size: 0.8rem;
  color: var(--p-surface-500, #6b7280);
}

/* =========================================
   SECCIÓN 2: PRÓXIMA SESIÓN (EMPTY STATE)
   ========================================= */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 2.5rem 1rem;
  background: white;
  border-radius: 12px;
  border: 1px dashed var(--p-surface-300, #cbd5e1);
}

.empty-icon-circle {
  width: 60px;
  height: 60px;
  background-color: var(--p-surface-100, #f1f5f9);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
  color: var(--p-surface-400, #9ca3af);
}

.icon-svg-large {
  width: 28px;
  height: 28px;
}

.empty-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--p-surface-900, #111827);
  margin: 0 0 0.4rem 0;
}

.empty-text {
  font-size: 0.85rem;
  color: var(--p-surface-500, #64748b);
  margin: 0;
}

/* =========================================
   SECCIÓN 3: SESIONES DE HOY
   ========================================= */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.section-header .section-title {
  margin-bottom: 0;
}

.link-view-all {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--p-primary-600, #2563eb);
  text-decoration: none;
}

.sessions-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.session-card {
  background-color: #ffffff;
  border: 1px solid var(--p-surface-200, #e2e8f0);
  border-radius: 12px;
  padding: 0.75rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.time-block {
  background-color: var(--p-surface-100, #f1f5f9);
  border-radius: 8px;
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 55px;
}

.time-start {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--p-surface-900, #111827);
}

.time-end {
  font-size: 0.65rem;
  color: var(--p-surface-500, #64748b);
  margin-top: 2px;
}

.session-details {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.client-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--p-surface-900, #111827);
  margin: 0 0 0.2rem 0;
}

.location-name {
  font-size: 0.75rem;
  color: var(--p-surface-500, #64748b);
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;  
  overflow: hidden;
}

.session-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-badge {
  padding: 0.35rem 0.6rem;
  border-radius: 999px;
  font-size: 0.65rem;
  font-weight: 700;
  color: white;
  text-transform: capitalize;
}

/* Modificadores de Badge */
.badge-success-dark { background-color: var(--p-primary-600, #2563eb); }
.badge-success { background-color: #10b981; }
.badge-info { background-color: #3b82f6; }

.arrow-right {
  color: var(--p-surface-400, #9ca3af);
  font-weight: bold;
  font-size: 1.2rem;
  line-height: 1;
}
</style>
