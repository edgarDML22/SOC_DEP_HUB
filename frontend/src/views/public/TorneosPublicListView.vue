<template>
  <div class="pre-registro-page">
    <div class="background-elements">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
    </div>

    <div class="container">
      <header class="page-header">
        <h1 class="logo">SOC_DEP_HUB</h1>
        <div class="tournament-badge">
          <i class="fas fa-trophy"></i>
          <span>Explorar Torneos</span>
        </div>
      </header>

      <main class="wizard-container">
        <div class="section-title-wrapper">
          <h2 class="section-title">Torneos Disponibles</h2>
          <p class="section-subtitle">Selecciona el torneo al que deseas inscribirte para iniciar tu pre-registro.</p>
        </div>

        <!-- Barra de Búsqueda y Filtros -->
        <div class="search-filter-bar">
          <div class="search-group">
            <i class="fas fa-search search-icon"></i>
            <input type="text" v-model="searchQuery" placeholder="Buscar por nombre de torneo..."
              class="search-input" />
          </div>

          <div class="filters-group">
            <div class="filter-select-wrapper">
              <label for="filter-discipline">Disciplina</label>
              <select id="filter-discipline" v-model="selectedDiscipline" class="filter-select">
                <option value="">Todas las disciplinas</option>
                <option v-for="d in uniqueDisciplines" :key="d" :value="d">{{ d }}</option>
              </select>
            </div>

            <div class="filter-select-wrapper">
              <label for="filter-status">Estado</label>
              <select id="filter-status" v-model="selectedStatus" class="filter-select">
                <option value="">Todos los estados</option>
                <option value="EN_PLANIFICACION">Planificación</option>
                <option value="INSCRIPCIONES_ABIERTAS">Inscripciones Abiertas</option>
                <option value="ACTIVO">En Curso</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Estado de carga -->
        <div v-if="loading" class="state-container">
          <i class="fas fa-circle-notch fa-spin spinner-icon"></i>
          <p>Cargando torneos disponibles...</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="state-container error-state">
          <i class="fas fa-exclamation-circle error-icon"></i>
          <p>{{ error }}</p>
          <button @click="fetchTournaments" class="btn-primary mt-2">
            Reintentar <i class="fas fa-redo"></i>
          </button>
        </div>

        <!-- No hay resultados -->
        <div v-else-if="filteredTournaments.length === 0" class="state-container empty-state">
          <i class="fas fa-folder-open empty-icon"></i>
          <p>No se encontraron torneos con los criterios seleccionados.</p>
        </div>

        <!-- Listado en Grid -->
        <div v-else class="tournaments-grid">
          <div v-for="torneo in filteredTournaments" :key="torneo.id_torneo" class="tournament-card"
            :class="{ disabled: !isRegistrationOpen(torneo.estado) }">
            <div class="card-glow"></div>
            <div class="card-content">
              <div class="card-header-info">
                <span class="discipline-badge">
                  <i :class="getDisciplineIcon(torneo.disciplina)"></i>
                  {{ torneo.disciplina || 'General' }}
                </span>
                <span class="status-badge" :class="torneo.estado.toLowerCase()">
                  {{ getStatusLabel(torneo.estado) }}
                </span>
              </div>

              <h3 class="tournament-name">{{ torneo.nombre_torneo }}</h3>

              <div class="tournament-details">
                <p>
                  <i class="fas fa-layer-group"></i>
                  <strong>Categoría:</strong> {{ torneo.categoria || 'Sin categoría' }}
                </p>
                <p>
                  <i class="fas fa-calendar-alt"></i>
                  <strong>Fecha de Inicio:</strong> {{ formatDate(torneo.fecha_inicio) }}
                </p>
              </div>

              <div class="card-actions">
                <router-link v-if="isRegistrationOpen(torneo.estado)"
                  :to="{ name: 'torneo-pre-registro', params: { id: torneo.id_torneo } }"
                  class="btn-primary btn-enroll">
                  Pre-registrarme
                  <i class="fas fa-arrow-right"></i>
                </router-link>
                <button v-else disabled class="btn-secondary btn-enroll-disabled">
                  Inscripciones Cerradas
                  <i class="fas fa-lock"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </main>

      <footer class="page-footer">
        <p>&copy; 2024 SOC_DEP_HUB. Todos los derechos reservados.</p>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "@/services/api";

const torneos = ref([]);
const loading = ref(true);
const error = ref(null);

const searchQuery = ref("");
const selectedDiscipline = ref("");
const selectedStatus = ref("");

// Cargar Torneos
const fetchTournaments = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get("/torneos");
    torneos.value = response.data.data || [];
  } catch (err) {
    console.error("Error fetching tournaments:", err);
    error.value = "No se pudieron cargar los torneos. Por favor intenta más tarde.";
  } finally {
    loading.value = false;
  }
};

// Disciplinas Únicas para el filtro
const uniqueDisciplines = computed(() => {
  const set = new Set();
  torneos.value.forEach(t => {
    if (t.disciplina) set.add(t.disciplina);
  });
  return Array.from(set);
});

// Filtrado de Torneos
const filteredTournaments = computed(() => {
  return torneos.value.filter(t => {
    const matchesSearch = t.nombre_torneo?.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesDiscipline = !selectedDiscipline.value || t.disciplina === selectedDiscipline.value;
    const matchesStatus = !selectedStatus.value || t.estado === selectedStatus.value;
    return matchesSearch && matchesDiscipline && matchesStatus;
  });
});

// Helpers
const isRegistrationOpen = (status) => {
  return status === "EN_PLANIFICACION" || status === "INSCRIPCIONES_ABIERTAS" || status === "ACTIVO";
};

const getStatusLabel = (status) => {
  switch (status) {
    case "EN_PLANIFICACION": return "Planificación";
    case "INSCRIPCIONES_ABIERTAS": return "Inscripciones";
    case "ACTIVO": return "En Curso";
    case "FINALIZADO": return "Finalizado";
    case "CANCELADO": return "Cancelado";
    default: return status;
  }
};

const getDisciplineIcon = (disc) => {
  if (!disc) return "fa-tennis-ball";
  const name = disc.toLowerCase();
  if (name.includes("padel") || name.includes("pádel")) return "fa-table-tennis-paddle-ball";
  if (name.includes("squash")) return "fa-racquet";
  if (name.includes("tenis") || name.includes("tennis")) return "fa-baseball";
  return "fa-trophy";
};

const formatDate = (dateStr) => {
  if (!dateStr) return "Por definir";
  try {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateStr).toLocaleDateString('es-MX', options);
  } catch (e) {
    return dateStr;
  }
};

onMounted(() => {
  fetchTournaments();
});
</script>

<style scoped>
.pre-registro-page {
  min-height: 100vh;
  background: #0f172a;
  color: white;
  position: relative;
  overflow-x: hidden;
  padding: 2rem 0;
  display: flex;
  align-items: center;
}

.container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 1.5rem;
  width: 100%;
  position: relative;
  z-index: 10;
}

.background-elements {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.blob {
  position: absolute;
  filter: blur(80px);
  opacity: 0.2;
  border-radius: 50%;
}

.blob-1 {
  width: 400px;
  height: 400px;
  background: var(--primary-color);
  top: -100px;
  right: -100px;
}

.blob-2 {
  width: 300px;
  height: 300px;
  background: #6366f1;
  bottom: -50px;
  left: -50px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2.5rem;
}

.logo {
  font-size: 1.5rem;
  font-weight: 900;
  letter-spacing: 2px;
  color: var(--primary-color);
}

.tournament-badge {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(5px);
  padding: 0.5rem 1.2rem;
  border-radius: 2rem;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.tournament-badge i {
  color: #fbbf24;
}

.wizard-container {
  background: rgba(255, 255, 255, 0.02);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 2rem;
  padding: 3rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

@media (max-width: 768px) {
  .wizard-container {
    padding: 1.5rem;
  }
}

.section-title-wrapper {
  text-align: center;
  margin-bottom: 2.5rem;
}

.section-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.section-subtitle {
  color: var(--text-secondary);
  font-size: 1rem;
}

/* Barra de Filtros y Búsqueda */
.search-filter-bar {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 1.5rem;
  margin-bottom: 2.5rem;
  background: rgba(255, 255, 255, 0.03);
  padding: 1.2rem;
  border-radius: 1.2rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

@media (max-width: 768px) {
  .search-filter-bar {
    grid-template-columns: 1fr;
  }
}

.search-group {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 1.2rem;
  color: var(--text-secondary);
  font-size: 1.1rem;
}

.search-input {
  width: 100%;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.8rem;
  padding: 0.8rem 1rem 0.8rem 2.8rem;
  color: var(--text-primary);
  transition: all 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  background: rgba(255, 255, 255, 0.08);
}

.filters-group {
  display: flex;
  gap: 1rem;
}

@media (max-width: 600px) {
  .filters-group {
    flex-direction: column;
  }
}

.filter-select-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.filter-select-wrapper label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-secondary);
  letter-spacing: 0.5px;
}

.filter-select {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.8rem;
  padding: 0.8rem 1.5rem 0.8rem 1rem;
  color: var(--text-primary);
  min-width: 180px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.filter-select:focus {
  outline: none;
  border-color: var(--primary-color);
}

.filter-select option {
  background: #1e293b;
  color: white;
}

/* Grid de Torneos */
.tournaments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.tournament-card {
  position: relative;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 1.5rem;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.tournament-card:hover {
  transform: translateY(-8px);
  border-color: rgba(var(--primary-rgb), 0.3);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
}

.tournament-card.disabled {
  opacity: 0.7;
}

.card-glow {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle at 50% -20%, rgba(var(--primary-rgb), 0.15), transparent 70%);
  z-index: 1;
  pointer-events: none;
}

.card-content {
  padding: 1.8rem;
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  height: 100%;
  justify-content: space-between;
  min-height: 250px;
}

.card-header-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.2rem;
}

.discipline-badge {
  background: rgba(var(--primary-rgb), 0.1);
  color: var(--primary-color);
  border: 1px solid rgba(var(--primary-color), 0.2);
  padding: 0.3rem 0.8rem;
  border-radius: 2rem;
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.status-badge {
  padding: 0.3rem 0.8rem;
  border-radius: 2rem;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
}

.status-badge.en_planificacion {
  background: rgba(33, 150, 243, 0.1);
  color: #2196f3;
  border: 1px solid rgba(33, 150, 243, 0.2);
}

.status-badge.inscripciones_abiertas {
  background: rgba(76, 175, 80, 0.1);
  color: #4caf50;
  border: 1px solid rgba(76, 175, 80, 0.2);
}

.status-badge.activo {
  background: rgba(255, 152, 0, 0.1);
  color: #ff9800;
  border: 1px solid rgba(255, 152, 0, 0.2);
}

.status-badge.finalizado,
.status-badge.cancelado {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-secondary);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.tournament-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 1rem;
  line-height: 1.4;
}

.tournament-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.tournament-details p {
  font-size: 0.85rem;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tournament-details i {
  color: var(--primary-color);
  width: 16px;
  text-align: center;
}

.card-actions {
  margin-top: auto;
}

.btn-primary,
.btn-secondary {
  padding: 0.8rem 1.5rem;
  border-radius: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.8rem;
  transition: all 0.3s ease;
  width: 100%;
}

.btn-enroll {
  background: var(--primary-color);
  color: white;
  border: none;
  box-shadow: 0 4px 15px rgba(var(--primary-rgb), 0.25);
  cursor: pointer;
}

.btn-enroll:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(var(--primary-rgb), 0.4);
}

.btn-enroll-disabled {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  color: var(--text-secondary);
  cursor: not-allowed;
}

/* Loading, Error and Empty states */
.state-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
  color: var(--text-secondary);
}

.spinner-icon {
  font-size: 2.5rem;
  color: var(--primary-color);
  margin-bottom: 1.5rem;
}

.error-icon,
.empty-icon {
  font-size: 3rem;
  color: #ff4d4d;
  margin-bottom: 1.5rem;
}

.empty-icon {
  color: var(--text-secondary);
}

.page-footer {
  margin-top: 3rem;
  text-align: center;
  color: var(--text-secondary);
  font-size: 0.85rem;
}

.mt-2 {
  margin-top: 1rem;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
