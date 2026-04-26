<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();

const instructors = ref([]);
const disciplinasList = ref([]);
const isLoading = ref(true);
const errorMsg = ref('');

// FILTERS
const search = ref('');
const filterEstatus = ref('TODAS');
const filterDisciplina = ref('TODAS');

// MODAL NUEVO INSTRUCTOR
const showNewModal = ref(false);
const isSaving = ref(false);
const newInstructor = ref({
    nombre_completo: '',
    telefono: '',
    fecha_nacimiento: '',
    fecha_contratacion: '',
    estatus: 'ACTIVO',
    disciplinas: []
});

onMounted(async () => {
  await fetchDisciplinas();
  await fetchInstructors();
});

const fetchInstructors = async () => {
    isLoading.value = true;
    errorMsg.value = '';
    try {
        const response = await api.get('/instructors/all');
        if (response.data && response.data.success) {
            instructors.value = response.data.data;
        }
    } catch (error) {
        console.error("Error cargando los instructores:", error);
        errorMsg.value = "Hubo un problema al cargar los instructores.";
    } finally {
        isLoading.value = false;
    }
};

const fetchDisciplinas = async () => {
    try {
        const response = await api.get('/disciplinas/all');
        if (response.data && response.data.success) {
            disciplinasList.value = response.data.data;
        }
    } catch (error) {
        console.error("Error cargando disciplinas:", error);
    }
};

const filteredInstructors = computed(() => {
  let result = instructors.value;

  // 1. Búsqueda por texto
  if (search.value) {
    const q = search.value.toLowerCase();
    result = result.filter(i => i.nombre_completo && i.nombre_completo.toLowerCase().includes(q));
  }

  // 2. Filtro por Estatus
  if (filterEstatus.value !== 'TODAS') {
      result = result.filter(i => i.estatus === filterEstatus.value);
  }

  // 3. Filtro por Disciplina
  if (filterDisciplina.value !== 'TODAS') {
      result = result.filter(i => {
          if (!i.disciplinas) return false;
          // Verificar si alguna de sus disciplinas tiene el ID seleccionado
          return i.disciplinas.some(d => d.id_disciplina == filterDisciplina.value);
      });
  }

  return result;
});

const handleSessionClick = (instructor) => {
  router.push({
    path: `/admin/instructors/${instructor.id_instructor}`,
    state: { instructorData: JSON.stringify(instructor) }
  });
};

const toggleDisciplinaSelection = (id) => {
    const index = newInstructor.value.disciplinas.indexOf(id);
    if (index > -1) {
        newInstructor.value.disciplinas.splice(index, 1);
    } else {
        newInstructor.value.disciplinas.push(id);
    }
};

const saveNewInstructor = async () => {
    if(!newInstructor.value.nombre_completo) {
        alert("El nombre es requerido.");
        return;
    }

    isSaving.value = true;
    try {
        const res = await api.post('/instructors/create', newInstructor.value);
        if(res.data.success) {
            showNewModal.value = false;
            // Refrescar lista
            await fetchInstructors();
            // Reset form
            newInstructor.value = {
                nombre_completo: '',
                telefono: '',
                fecha_nacimiento: '',
                fecha_contratacion: '',
                estatus: 'ACTIVO',
                disciplinas: []
            };
        }
    } catch(e) {
        console.error("Error creando instructor:", e);
        alert(e.response?.data?.message || "Ocurrió un error al crear");
    } finally {
        isSaving.value = false;
    }
};

</script>

<template>
  <main class="home-instructor">

    <header class="home-header">
      <div>
        <p class="greeting-label">Administración</p>
        <h1 class="greeting-name">Instructores</h1>
      </div>
      <button @click="showNewModal = true" class="btn-primary">
          + Nuevo Instructor
      </button>
    </header>

    <!-- Filtros -->
    <div class="filters-container">
      <input v-model="search" placeholder="Buscar instructor por nombre..." class="search-input" />
      
      <div class="filter-group">
          <select v-model="filterEstatus" class="filter-select">
              <option value="TODAS">Todos los estatus</option>
              <option value="ACTIVO">Activos</option>
              <option value="INACTIVO">Inactivos</option>
              <option value="BAJA_TEMPORAL">Baja Temporal</option>
          </select>
          
          <select v-model="filterDisciplina" class="filter-select">
              <option value="TODAS">Todas las disciplinas</option>
              <option v-for="d in disciplinasList" :key="d.id_disciplina" :value="d.id_disciplina">
                  {{ d.nombre_disciplina }}
              </option>
          </select>
      </div>
    </div>

    <!-- Estado de Carga -->
    <section v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
      <p class="text-gray-500 font-medium">Cargando instructores...</p>
    </section>

    <!-- Estado de Error -->
    <section v-else-if="errorMsg" class="error-state">
      <p class="text-red-500 font-bold">{{ errorMsg }}</p>
    </section>

    <div v-else class="sessions-list">
      <div v-for="instructor in filteredInstructors" :key="instructor.id_instructor" class="session-card clickable"
        @click="handleSessionClick(instructor)">

        <!-- Nombre Corto / Iniciales -->
        <div class="time-block">
          <span class="time-start">{{ instructor.nombre_completo.split(' ')[0] }}</span>
        </div>

        <!-- Detalles -->
        <div class="session-details">
          <h4 class="client-name">{{ instructor.nombre_completo }}</h4>
          <p class="location-name">Tel: {{ instructor.telefono || 'N/A' }} | Ingreso: {{ instructor.fecha_contratacion || 'N/A' }}</p>
          
          <!-- Chips Disciplinas -->
          <div class="disciplinas-chips">
              <span v-for="disc in instructor.disciplinas" :key="disc.id_disciplina" class="chip">
                  {{ disc.nombre_disciplina }}
              </span>
              <span v-if="!instructor.disciplinas || instructor.disciplinas.length === 0" class="chip-empty">
                  Sin disciplinas
              </span>
          </div>
        </div>

        <!-- Estatus y Flecha -->
        <div class="session-actions">
          <span class="status-badge" :class="{
              'badge-success': instructor.estatus === 'ACTIVO' || instructor.estatus === 'Activo',
              'badge-danger': instructor.estatus === 'INACTIVO' || instructor.estatus === 'Inactivo',
              'badge-warning': instructor.estatus === 'BAJA_TEMPORAL'
          }">
            {{ instructor.estatus }}
          </span>
          <span class="arrow-right">›</span>
        </div>

      </div>

      <!-- Estado de Vacío -->
      <section v-if="filteredInstructors.length === 0" class="next-session-section">
        <div class="empty-state">
          <div class="empty-icon-circle">
            <span style="font-size: 1.5rem; color: #9ca3af;">!</span>
          </div>
          <h3 class="empty-title">Sin resultados</h3>
          <p class="empty-text">No se encontraron instructores con esos filtros.</p>
        </div>
      </section>

    </div>

    <!-- MODAL NUEVO INSTRUCTOR -->
    <div v-if="showNewModal" class="modal-backdrop" @click.self="showNewModal = false">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Añadir Nuevo Instructor</h2>
                <button @click="showNewModal = false" class="btn-close">×</button>
            </div>
            
            <div class="modal-body">
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" v-model="newInstructor.nombre_completo" placeholder="Ej. Juan Pérez" />
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label>Teléfono</label>
                        <input type="text" v-model="newInstructor.telefono" placeholder="Opcional" />
                    </div>
                    <div class="form-group half">
                        <label>Estatus</label>
                        <select v-model="newInstructor.estatus">
                            <option value="ACTIVO">ACTIVO</option>
                            <option value="INACTIVO">INACTIVO</option>
                            <option value="BAJA_TEMPORAL">BAJA_TEMPORAL</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group half">
                        <label>Fecha de Nacimiento</label>
                        <input type="date" v-model="newInstructor.fecha_nacimiento" />
                    </div>
                    <div class="form-group half">
                        <label>Fecha de Contratación</label>
                        <input type="date" v-model="newInstructor.fecha_contratacion" />
                    </div>
                </div>

                <div class="form-group">
                    <label>Disciplinas que Imparte</label>
                    <div class="disciplinas-grid">
                        <label v-for="d in disciplinasList" :key="d.id_disciplina" class="checkbox-label" :class="{ 'selected': newInstructor.disciplinas.includes(d.id_disciplina) }">
                            <input type="checkbox" :value="d.id_disciplina" @change="toggleDisciplinaSelection(d.id_disciplina)" :checked="newInstructor.disciplinas.includes(d.id_disciplina)" class="hidden-checkbox">
                            {{ d.nombre_disciplina }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button @click="showNewModal = false" class="btn-secondary">Cancelar</button>
                <button @click="saveNewInstructor" class="btn-primary" :disabled="isSaving">
                    {{ isSaving ? 'Guardando...' : 'Guardar Instructor' }}
                </button>
            </div>
        </div>
    </div>

  </main>
</template>

<style scoped>
/* Contenedor Principal */
.home-instructor {
  background-color: var(--p-surface-50, #f8fafc);
  padding: 1rem;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  font-family: inherit;
  padding-bottom: 90px;
}

/* Header */
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

.btn-primary {
    background-color: var(--p-primary-600, #2563eb);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-primary:hover {
    background-color: var(--p-primary-700, #1d4ed8);
}

.btn-secondary {
    background-color: white;
    color: var(--p-surface-700, #334155);
    border: 1px solid var(--p-surface-300, #cbd5e1);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-secondary:hover {
    background-color: var(--p-surface-100, #f1f5f9);
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
.search-input:focus {
    border-color: var(--p-primary-500, #3b82f6);
}

.filter-group {
    display: flex;
    gap: 1rem;
}

.filter-select {
    flex: 1;
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px solid var(--p-surface-300, #cbd5e1);
    background: white;
    font-size: 0.9rem;
    outline: none;
}

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
.error-state {
    text-align: center;
    padding: 3rem 1rem;
}

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
    background-color: var(--p-primary-50, #eff6ff);
    color: var(--p-primary-700, #1d4ed8);
    border: 1px solid var(--p-primary-200, #bfdbfe);
    font-size: 0.65rem;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    font-weight: 600;
}

.chip-empty {
    font-size: 0.65rem;
    color: var(--p-surface-400, #9ca3af);
    font-style: italic;
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

/* Modal CSS */
.modal-backdrop {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-content {
    background: white;
    width: 100%;
    max-width: 500px;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}

.modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--p-surface-200, #e2e8f0);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h2 { margin: 0; font-size: 1.2rem; }
.btn-close {
    background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}
.form-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--p-surface-700, #334155);
}
.form-group input, .form-group select {
    padding: 0.6rem;
    border-radius: 8px;
    border: 1px solid var(--p-surface-300, #cbd5e1);
    outline: none;
}
.form-group input:focus, .form-group select:focus {
    border-color: var(--p-primary-500, #3b82f6);
}

.form-row {
    display: flex;
    gap: 1rem;
}
.half { flex: 1; }

.disciplinas-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.hidden-checkbox { display: none; }

.checkbox-label {
    padding: 0.4rem 0.8rem;
    border: 1px solid var(--p-surface-300, #cbd5e1);
    border-radius: 999px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}

.checkbox-label.selected {
    background-color: var(--p-primary-50, #eff6ff);
    color: var(--p-primary-700, #1d4ed8);
    border-color: var(--p-primary-400, #60a5fa);
    font-weight: 600;
}

.modal-footer {
    padding: 1.25rem 1.5rem;
    border-top: 1px solid var(--p-surface-200, #e2e8f0);
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}
</style>