<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();

const socios = ref([]);
const isLoading = ref(true);
const errorMsg = ref('');

// FILTERS
const search = ref('');
const filterTipo = ref('TODAS');
const filterModalidad = ref('TODAS');
const filterGenero = ref('TODAS');
const filterEstatus = ref('TODAS');

onMounted(async () => {
  await fetchSocios();
});

const fetchSocios = async () => {
    isLoading.value = true;
    errorMsg.value = '';
    try {
        const response = await api.get('/socios/all');
        if (response.data && response.data.success) {
            socios.value = response.data.data;
        }
    } catch (error) {
        console.error("Error cargando los socios:", error);
        errorMsg.value = "Hubo un problema al cargar la lista de socios titulares.";
    } finally {
        isLoading.value = false;
    }
};

const filteredSocios = computed(() => {
  let result = socios.value;

  // 1. Búsqueda por texto (Nombre o No. de Acción)
  if (search.value) {
    const q = search.value.toLowerCase();
    result = result.filter(s => 
      (s.nombre_completo && s.nombre_completo.toLowerCase().includes(q)) ||
      (s.numero_accion && String(s.numero_accion).toLowerCase().includes(q))
    );
  }

  // 2. Filtros Select
  if (filterTipo.value !== 'TODAS') {
      result = result.filter(s => s.tipo_socio === filterTipo.value);
  }
  if (filterModalidad.value !== 'TODAS') {
      result = result.filter(s => s.modalidad_plan === filterModalidad.value);
  }
  if (filterGenero.value !== 'TODAS') {
      result = result.filter(s => s.genero === filterGenero.value);
  }
  if (filterEstatus.value !== 'TODAS') {
      result = result.filter(s => s.estatus_cuenta === filterEstatus.value);
  }

  return result;
});

const handleSocioClick = (socio) => {
  router.push({ path: `/admin/socios/${socio.id_socio}` });
};
</script>

<template>
  <main class="home-socio">

    <header class="home-header">
      <div>
        <p class="greeting-label">Administración</p>
        <h1 class="greeting-name">Socios Titulares</h1>
      </div>
    </header>

    <!-- Filtros -->
    <div class="filters-container">
      <input v-model="search" placeholder="Buscar por nombre o número de acción..." class="search-input" />
      
      <div class="filter-group">
          <!-- Tipo Socio -->
          <div class="filter-item">
              <label>Tipo</label>
              <select v-model="filterTipo" class="filter-select">
                  <option value="TODAS">Todos</option>
                  <option value="ACCIONISTA">Accionista</option>
                  <option value="RENTISTA">Rentista</option>
              </select>
          </div>

          <!-- Modalidad -->
          <div class="filter-item">
              <label>Modalidad</label>
              <select v-model="filterModalidad" class="filter-select">
                  <option value="TODAS">Todas</option>
                  <option value="INDIVIDUAL">Individual</option>
                  <option value="FAMILIAR">Familiar</option>
              </select>
          </div>

          <!-- Género -->
          <div class="filter-item">
              <label>Género</label>
              <select v-model="filterGenero" class="filter-select">
                  <option value="TODAS">Todos</option>
                  <option value="M">Masculino (M)</option>
                  <option value="F">Femenino (F)</option>
              </select>
          </div>

          <!-- Estatus -->
          <div class="filter-item">
              <label>Estatus de Cuenta</label>
              <select v-model="filterEstatus" class="filter-select">
                  <option value="TODAS">Todos</option>
                  <option value="AL_CORRIENTE">Al Corriente</option>
                  <option value="MOROSO">Moroso</option>
                  <option value="SUSPENDIDO">Suspendido</option>
              </select>
          </div>
      </div>
    </div>

    <!-- Estado de Carga -->
    <section v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
      <p class="text-gray-500 font-medium">Cargando socios...</p>
    </section>

    <!-- Estado de Error -->
    <section v-else-if="errorMsg" class="error-state">
      <p class="text-red-500 font-bold">{{ errorMsg }}</p>
    </section>

    <div v-else class="sessions-list">
      <div v-for="socio in filteredSocios" :key="socio.id_socio" class="session-card clickable"
        @click="handleSocioClick(socio)">

        <!-- Iniciales -->
        <div class="time-block">
          <span class="time-start">{{ socio.nombre_completo.split(' ')[0] }}</span>
        </div>

        <!-- Detalles -->
        <div class="session-details">
          <h4 class="client-name">{{ socio.nombre_completo }}</h4>
          <p class="location-name">
              <span class="font-semibold text-gray-700">No. {{ socio.numero_accion }}</span> 
              | {{ socio.tipo_socio }} | Plan {{ socio.modalidad_plan }}
          </p>
          
          <!-- Chips Penalizaciones Rápidas -->
          <div class="disciplinas-chips">
              <span v-if="socio.contador_no_shows > 0" class="chip chip-danger">
                  {{ socio.contador_no_shows }} No Shows
              </span>
              <span v-if="socio.retrasos_ludoteca > 0" class="chip chip-warning">
                  {{ socio.retrasos_ludoteca }} Retrasos Ludoteca
              </span>
          </div>
        </div>

        <!-- Estatus y Flecha -->
        <div class="session-actions">
          <span class="status-badge" :class="{
              'badge-success': socio.estatus_cuenta === 'AL_CORRIENTE',
              'badge-warning': socio.estatus_cuenta === 'MOROSO',
              'badge-danger': socio.estatus_cuenta === 'SUSPENDIDO'
          }">
            {{ socio.estatus_cuenta }}
          </span>
          <span class="arrow-right">›</span>
        </div>

      </div>

      <!-- Estado de Vacío -->
      <section v-if="filteredSocios.length === 0" class="next-session-section">
        <div class="empty-state">
          <div class="empty-icon-circle">
            <span style="font-size: 1.5rem; color: #9ca3af;">!</span>
          </div>
          <h3 class="empty-title">Sin resultados</h3>
          <p class="empty-text">No se encontraron socios con esos filtros.</p>
        </div>
      </section>

    </div>
  </main>
</template>

<style scoped>
.home-socio {
  background-color: var(--p-surface-50, #f8fafc);
  padding: 1rem;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  font-family: inherit;
  padding-bottom: 90px;
}

.home-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
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

/* Filtros */
.filters-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
    background: white;
    padding: 1rem;
    border-radius: 12px;
    border: 1px solid var(--p-surface-200, #e2e8f0);
}

.search-input {
    width: 100%;
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px solid var(--p-surface-300, #cbd5e1);
    font-size: 1rem;
    outline: none;
}
.search-input:focus { border-color: var(--p-primary-500, #3b82f6); }

.filter-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    flex: 1;
    min-width: 120px;
}

.filter-item label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--p-surface-600, #475569);
    text-transform: uppercase;
}

.filter-select {
    padding: 0.6rem;
    border-radius: 8px;
    border: 1px solid var(--p-surface-300, #cbd5e1);
    background: white;
    font-size: 0.85rem;
    outline: none;
}
.filter-select:focus { border-color: var(--p-primary-500, #3b82f6); }

/* Loading */
.loading-state {
    text-align: center;
    padding: 3rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--p-surface-200, #e2e8f0);
    border-top-color: var(--p-primary-600, #2563eb);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin { 100% { transform: rotate(360deg); } }

/* Error */
.error-state { text-align: center; padding: 3rem 1rem; }

/* Listado */
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

.session-card.clickable { cursor: pointer; }
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
  min-width: 60px;
}

.time-start {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--p-surface-900, #111827);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 60px;
}

.session-details {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.client-name {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--p-surface-900, #111827);
  margin: 0 0 0.2rem 0;
}

.location-name {
  font-size: 0.75rem;
  color: var(--p-surface-500, #64748b);
  margin: 0 0 0.4rem 0;
}

.disciplinas-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.chip {
    font-size: 0.65rem;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    font-weight: 600;
}

.chip-danger { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.chip-warning { background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a; }

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
  text-transform: uppercase;
  white-space: nowrap;
}

.badge-success { background-color: #10b981; }
.badge-danger { background-color: #ef4444; }
.badge-warning { background-color: #f59e0b; }

.arrow-right {
  color: var(--p-surface-400, #9ca3af);
  font-weight: bold;
  font-size: 1.2rem;
  line-height: 1;
}

/* Empty state */
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
