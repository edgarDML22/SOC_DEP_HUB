<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import api from '@/services/api';
import { useInstructorStore } from '@/stores/instructorStore';

const router = useRouter();
const instructorStore = useInstructorStore();

const { instructors, isLoading, error: errorMsg } = storeToRefs(instructorStore);

const disciplinasList = ref([]);

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
  await instructorStore.fetchInstructors();
});

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
  router.push(`/admin/instructors/${instructor.id_instructor}`);
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
            // Refrescar lista forzando fetch
            await instructorStore.fetchInstructors(true);
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
  background-color: var(--p-surface-50);
  padding: 1.5rem;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  padding-bottom: 90px;
}

/* Header */
.home-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.greeting-label {
  font-size: 0.75rem;
  color: var(--p-surface-500);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.greeting-name {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--p-surface-900);
  margin: 0;
  letter-spacing: -0.02em;
}

/* Botones */
.btn-primary {
    background-color: var(--p-primary-600);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
}

.btn-primary:hover {
    background-color: var(--p-primary-700);
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
}

.btn-secondary {
    background-color: white;
    color: var(--p-surface-700);
    border: 1px solid var(--p-surface-200);
    padding: 0.75rem 1.5rem;
    border-radius: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background-color: var(--p-surface-50);
    border-color: var(--p-surface-300);
}

/* Filtros */
.filters-container {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    margin-bottom: 2rem;
    background: white;
    padding: 1.5rem;
    border-radius: 20px;
    border: 1px solid var(--p-surface-200);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.search-input {
    width: 100%;
    padding: 0.875rem 1rem;
    border-radius: 12px;
    border: 1px solid var(--p-surface-200);
    font-size: 1rem;
    outline: none;
    transition: all 0.2s;
    background: var(--p-surface-50);
}

.search-input:focus {
    border-color: var(--p-primary-500);
    background: white;
    box-shadow: 0 0 0 4px var(--p-primary-50);
}

.filter-group {
    display: flex;
    gap: 1rem;
}

.filter-select {
    flex: 1;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    border: 1px solid var(--p-surface-200);
    background: white;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--p-surface-700);
    outline: none;
    cursor: pointer;
}

/* Loading & Empty States */
.loading-state, .error-state {
    text-align: center;
    padding: 4rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.spinner {
    width: 48px;
    height: 48px;
    border: 4px solid var(--p-surface-200);
    border-top-color: var(--p-primary-600);
    border-radius: 50%;
    animation: spin 1s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

@keyframes spin { 100% { transform: rotate(360deg); } }

/* Listado de Instructores */
.sessions-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.session-card {
  background-color: #ffffff;
  border: 1px solid var(--p-surface-200);
  border-radius: 18px;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.session-card::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: transparent;
  transition: background 0.3s;
}

.session-card.clickable { cursor: pointer; }

.session-card.clickable:hover {
  border-color: var(--p-primary-200);
  transform: translateX(4px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
}

.session-card.clickable:hover::before {
  background: var(--p-primary-500);
}

.time-block {
  background-color: var(--p-surface-50);
  border-radius: 12px;
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 70px;
  border: 1px solid var(--p-surface-100);
}

.time-start {
  font-size: 0.9rem;
  font-weight: 800;
  color: var(--p-primary-600);
  text-transform: uppercase;
}

.session-details {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.client-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--p-surface-900);
  margin: 0 0 0.25rem 0;
}

.location-name {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--p-surface-500);
  margin: 0 0 0.75rem 0;
}

.disciplinas-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.chip {
    background-color: var(--p-primary-50);
    color: var(--p-primary-700);
    border: 1px solid var(--p-primary-100);
    font-size: 0.7rem;
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    font-weight: 700;
}

.chip-empty {
    font-size: 0.75rem;
    color: var(--p-surface-400);
    font-style: italic;
}

.session-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.status-badge {
  padding: 0.4rem 0.8rem;
  border-radius: 10px;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.badge-success { background-color: #ecfdf5; color: #059669; border: 1px solid #10b98133; }
.badge-danger { background-color: #fef2f2; color: #dc2626; border: 1px solid #ef444433; }
.badge-warning { background-color: #fffbeb; color: #d97706; border: 1px solid #f59e0b33; }

.arrow-right {
  color: var(--p-surface-300);
  font-size: 1.5rem;
  transition: transform 0.2s;
}

.session-card:hover .arrow-right {
  color: var(--p-primary-500);
  transform: translateX(2px);
}

/* Empty state */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 20px;
  border: 2px dashed var(--p-surface-200);
}

.empty-icon-circle {
  width: 64px;
  height: 64px;
  background-color: var(--p-surface-50);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
  color: var(--p-surface-400);
}

.empty-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--p-surface-900);
  margin-bottom: 0.5rem;
}

.empty-text {
  font-size: 0.9rem;
  color: var(--p-surface-500);
}

/* Modal Styling */
.modal-backdrop {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-content {
    background: white;
    width: 100%;
    max-width: 550px;
    border-radius: 24px;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid var(--p-surface-200);
}

.modal-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--p-surface-100);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 { 
  margin: 0; 
  font-size: 1.5rem; 
  font-weight: 800;
  color: var(--p-surface-900);
  letter-spacing: -0.02em;
}

.btn-close {
    background: var(--p-surface-50);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    font-size: 1.25rem;
    cursor: pointer;
    color: var(--p-surface-500);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-close:hover {
    background: var(--p-surface-100);
    color: var(--p-surface-900);
}

.modal-body {
    padding: 2rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--p-surface-500);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.form-group input, .form-group select {
    padding: 0.75rem 1rem;
    border-radius: 12px;
    border: 1px solid var(--p-surface-200);
    outline: none;
    font-weight: 600;
    background: var(--p-surface-50);
    transition: all 0.2s;
}

.form-group input:focus, .form-group select:focus {
    border-color: var(--p-primary-500);
    background: white;
    box-shadow: 0 0 0 4px var(--p-primary-50);
}

.form-row {
    display: flex;
    gap: 1rem;
}

.half { flex: 1; }

.disciplinas-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.checkbox-label {
    padding: 0.5rem 1.25rem;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
    background: white;
}

.checkbox-label.selected {
    background-color: var(--p-primary-600);
    color: white;
    border-color: var(--p-primary-600);
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
}

.modal-footer {
    padding: 1.5rem 2rem;
    border-top: 1px solid var(--p-surface-100);
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}
</style>