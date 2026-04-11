<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();

// Importación de componentes SVG existentes en tu proyecto
import {
  IconCalendar,
  IconUser,
  IconInbox
} from '@/components/icons';

// Datos de sesiones
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

// Helper para ordenar los días cronológicamente en la semana
const ordenDias = {
  'LUNES': 1, 'MARTES': 2, 'MIÉRCOLES': 3, 'JUEVES': 4, 'VIERNES': 5, 'SÁBADO': 6, 'DOMINGO': 7
};

// Agrupamos las sesiones por día y las ordenamos
const sesionesAgrupadas = computed(() => {
  const grupos = {};
  sesionesData.value.forEach(sesion => {
    if (!grupos[sesion.diaSemana]) {
      grupos[sesion.diaSemana] = [];
    }
    grupos[sesion.diaSemana].push(sesion);
  });

  // Ordenar los días del 1 al 7
  const diasOrdenados = Object.keys(grupos).sort((a, b) => ordenDias[a] - ordenDias[b]);

  const resultado = {};
  diasOrdenados.forEach(dia => {
    // Ordenar las sesiones dentro de un día por hora de inicio
    resultado[dia] = grupos[dia].sort((a, b) => a.horaInicio.localeCompare(b.horaInicio));
  });

  return resultado;
});

const haySesiones = computed(() => sesionesData.value.length > 0);

const handleSessionClick = (sesion) => {
  router.push({
    path: `/instructor/sessions/${sesion.id}`,
    state: { sessionData: JSON.stringify(sesion) } // Pasamos los datos vía history state
  });
};
</script>

<template>
  <main class="home-instructor">

    <header class="home-header">
      <div>
        <p class="greeting-label">Agenda Semanal</p>
        <h1 class="greeting-name">Sesiones Disponibles</h1>
      </div>
    </header>

    <!-- Estado de Carga -->
    <section v-if="isLoading" class="loading-state" style="text-align: center; padding: 3rem 1rem;">
      <p class="text-gray-500 font-medium">Cargando agenda semanal...</p>
    </section>

    <!-- Estado de Error -->
    <section v-else-if="errorMsg" class="error-state" style="text-align: center; padding: 3rem 1rem;">
      <p class="text-red-500 font-bold">{{ errorMsg }}</p>
    </section>

    <!-- Mapeo por Día -->
    <div v-else-if="haySesiones">
      <!-- Mapeo por Día -->
      <section v-for="(sesiones, dia) in sesionesAgrupadas" :key="dia" class="today-sessions-section">

        <header class="section-header">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <IconCalendar class="icon-svg-small" style="color: var(--p-primary-600, #2563eb);" />
            <h2 class="section-title">{{ dia }}</h2>
          </div>
        </header>

        <div class="sessions-list">

          <div v-for="session in sesiones" :key="session.id" class="session-card clickable"
            @click="handleSessionClick(session)">
            <!-- Horario -->
            <div class="time-block">
              <span class="time-start">{{ session.horaInicio }}</span>
              <span class="time-end">{{ session.horaFin }}</span>
            </div>

            <!-- Detalles (Espacio y Capacidad) -->
            <div class="session-details">
              <h4 class="client-name">{{ session.espacio }}</h4>
              <p class="location-name">Capacidad: {{ session.capacidadMaxima }} px.</p>
            </div>

            <!-- Tipo de sesión y Flecha -->
            <div class="session-actions">
              <span class="status-badge" :class="`badge-${session.statusType}`">
                {{ session.tipo }}
              </span>
              <span class="arrow-right">›</span>
            </div>

          </div>

        </div>
      </section>
    </div>

    <!-- Estado de Vacío -->
    <section v-else class="next-session-section">
      <div class="empty-state">
        <div class="empty-icon-circle">
          <IconInbox class="icon-svg-large" />
        </div>
        <h3 class="empty-title">Sin agenda esta semana</h3>
        <p class="empty-text">No tienes sesiones programadas para los próximos días.</p>
      </div>
    </section>

  </main>
</template>

<style scoped>
/* Contenedor Principal (Replicado de InstructorHomeView) */
.home-instructor {
  background-color: var(--p-surface-50, #f8fafc);
  padding: 1rem;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  font-family: inherit;
  padding-bottom: 90px;
}

/* Header de bienvenida / Títulos */
.home-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.greeting-label {
  font-size: 0.8rem;
  color: var(--p-surface-500, #6b7280);
  margin: 0 0 0.15rem 0;
}

.greeting-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--p-surface-900, #111827);
  margin: 0;
}

/* Títulos de sección generales */
.today-sessions-section {
  margin-bottom: 1.5rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.section-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--p-surface-600, #475569);
  letter-spacing: 0.5px;
  margin-bottom: 0;
  text-transform: uppercase;
}

.icon-svg-small {
  width: 18px;
  height: 18px;
}

/* =========================================
   LISTADO DE SESIONES
   ========================================= */
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
  transition: border-color 0.2s, transform 0.2s;
}

.session-card.clickable {
  cursor: pointer;
}

.session-card.clickable:hover {
  border-color: var(--p-primary-400, #60a5fa);
  transform: translateY(-1px);
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
  white-space: nowrap;
}

/* Modificadores de Badge */
.badge-success-dark {
  background-color: var(--p-primary-600, #2563eb);
}

.badge-success {
  background-color: #10b981;
}

.badge-info {
  background-color: #3b82f6;
}

.arrow-right {
  color: var(--p-surface-400, #9ca3af);
  font-weight: bold;
  font-size: 1.2rem;
  line-height: 1;
}

/* =========================================
   EMPTY STATE (Caso Base)
   ========================================= */
.next-session-section {
  margin-top: 1rem;
}

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
</style>
