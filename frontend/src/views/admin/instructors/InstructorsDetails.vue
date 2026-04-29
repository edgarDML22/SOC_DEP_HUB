<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import api from '@/services/api';
import { useInstructorStore } from '@/stores/instructorStore';

const route = useRoute();
const router = useRouter();
const instructorStore = useInstructorStore();

const { isLoading, error: storeError } = storeToRefs(instructorStore);

const instructorId = route.params.id;

const instructor = ref(null);
const disciplinasList = ref([]);
const errorMsg = ref('');

// Modales
const showEditModal = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);

const editForm = ref({
    nombre_completo: '',
    telefono: '',
    fecha_nacimiento: '',
    fecha_contratacion: '',
    estatus: 'ACTIVO',
    disciplinas: []
});

onMounted(async () => {
    await fetchDisciplinas();
    await fetchInstructorDetails();
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

const fetchInstructorDetails = async () => {
    errorMsg.value = '';
    try {
        const data = await instructorStore.fetchInstructorDetails(instructorId);
        instructor.value = data;
        // Popular formulario de edición
        editForm.value = {
            nombre_completo: data.nombre_completo,
            telefono: data.telefono || '',
            fecha_nacimiento: data.fecha_nacimiento || '',
            fecha_contratacion: data.fecha_contratacion || '',
            estatus: data.estatus,
            disciplinas: data.disciplinas ? data.disciplinas.map(d => d.id_disciplina) : []
        };
    } catch (error) {
        console.error("Error cargando detalles del instructor:", error);
        errorMsg.value = "Hubo un problema al cargar los detalles del instructor.";
    }
};

const toggleDisciplinaSelection = (id) => {
    const index = editForm.value.disciplinas.indexOf(id);
    if (index > -1) {
        editForm.value.disciplinas.splice(index, 1);
    } else {
        editForm.value.disciplinas.push(id);
    }
};

const saveEditInstructor = async () => {
    if (!editForm.value.nombre_completo) {
        alert("El nombre es requerido.");
        return;
    }

    isSaving.value = true;
    try {
        const res = await instructorStore.updateInstructor(instructorId, editForm.value);
        if (res.success) {
            showEditModal.value = false;
            // Al ser reactivo el store y nosotros usar instructor.value = data en fetch,
            // y el store actualizar la lista, deberíamos refrescar la referencia local.
            instructor.value = instructorStore.getInstructorById(instructorId);
        } else {
            alert(res.error || "Ocurrió un error al actualizar.");
        }
    } catch (error) {
        console.error("Error al actualizar instructor:", error);
    } finally {
        isSaving.value = false;
    }
};

const darDeBaja = async () => {
    if (!confirm("¿Estás seguro de que deseas dar de baja o inactivar a este instructor?")) return;
    
    isDeleting.value = true;
    try {
        const res = await instructorStore.deleteInstructor(instructorId);
        if (res.success) {
            alert("Instructor dado de baja exitosamente.");
            router.push('/admin/instructors');
        } else {
            alert(res.error || "Error al dar de baja al instructor.");
        }
    } catch (error) {
        console.error("Error al eliminar instructor:", error);
    } finally {
        isDeleting.value = false;
    }
};

const goBack = () => {
    router.push('/admin/instructors');
};
</script>

<template>
    <main class="home-instructor">

        <!-- Navegación Superior -->
        <header class="app-header">
            <button @click="goBack" class="btn-back">
                <svg class="icon-back" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </button>
            <h1 class="header-title">Detalles del Instructor</h1>
        </header>

        <!-- Estado de Error -->
        <section v-if="errorMsg || storeError" class="error-state">
            <p class="error-text">{{ errorMsg || storeError }}</p>
            <button @click="goBack" class="btn-outline">Volver a los Instructores</button>
        </section>

        <!-- Estado de Carga -->
        <section v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <p class="text-gray-500 font-medium">Cargando detalles...</p>
        </section>

        <!-- Contenido Detallado -->
        <section v-if="instructor && !isLoading" class="detail-content">

            <!-- Tarjeta Principal Visual -->
            <article class="session-card featured-card">
                <div class="card-accent-bar"></div>

                <div class="featured-body">
                    <div class="featured-header">
                        <span class="status-badge" :class="{
                            'badge-success': instructor.estatus === 'ACTIVO' || instructor.estatus === 'Activo',
                            'badge-danger': instructor.estatus === 'INACTIVO' || instructor.estatus === 'Inactivo',
                            'badge-warning': instructor.estatus === 'BAJA_TEMPORAL'
                        }">
                            {{ instructor.estatus }}
                        </span>
                    </div>

                    <h2 class="session-type">{{ instructor.nombre_completo }}</h2>
                    <p class="session-location text-gray-500 mt-2">
                        📞 {{ instructor.telefono || 'Sin teléfono registrado' }}
                    </p>

                    <div class="info-grid mt-6">
                        <div class="info-cell">
                            <span class="info-label">Fecha de Ingreso</span>
                            <div class="info-value">
                                <span>{{ instructor.fecha_contratacion || 'N/A' }}</span>
                            </div>
                        </div>

                        <div class="info-cell">
                            <span class="info-label">Fecha de Nacimiento</span>
                            <div class="info-value">
                                <span>{{ instructor.fecha_nacimiento || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-4">
                        <h4 class="font-bold text-gray-700 mb-2">Disciplinas que Imparte</h4>
                        <div class="disciplinas-chips">
                            <span v-for="disc in instructor.disciplinas" :key="disc.id_disciplina" class="chip">
                                {{ disc.nombre_disciplina }}
                            </span>
                            <span v-if="!instructor.disciplinas || instructor.disciplinas.length === 0" class="chip-empty">
                                Sin disciplinas registradas
                            </span>
                        </div>
                    </div>

                    <div class="actions-group mt-8">
                        <button @click="showEditModal = true" class="btn-primary flex-1">Editar Instructor</button>
                        <button @click="darDeBaja" class="btn-danger flex-1" :disabled="isDeleting">
                            {{ isDeleting ? 'Procesando...' : 'Dar de Baja' }}
                        </button>
                    </div>
                </div>
            </article>
        </section>

        <!-- MODAL EDITAR INSTRUCTOR -->
        <div v-if="showEditModal" class="modal-backdrop" @click.self="showEditModal = false">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Editar Instructor</h2>
                    <button @click="showEditModal = false" class="btn-close">×</button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" v-model="editForm.nombre_completo" />
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Teléfono</label>
                            <input type="text" v-model="editForm.telefono" />
                        </div>
                        <div class="form-group half">
                            <label>Estatus</label>
                            <select v-model="editForm.estatus">
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="INACTIVO">INACTIVO</option>
                                <option value="BAJA_TEMPORAL">BAJA_TEMPORAL</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label>Fecha de Nacimiento</label>
                            <input type="date" v-model="editForm.fecha_nacimiento" />
                        </div>
                        <div class="form-group half">
                            <label>Fecha de Contratación</label>
                            <input type="date" v-model="editForm.fecha_contratacion" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Disciplinas que Imparte</label>
                        <div class="disciplinas-grid">
                            <label v-for="d in disciplinasList" :key="d.id_disciplina" class="checkbox-label" :class="{ 'selected': editForm.disciplinas.includes(d.id_disciplina) }">
                                <input type="checkbox" :value="d.id_disciplina" @change="toggleDisciplinaSelection(d.id_disciplina)" :checked="editForm.disciplinas.includes(d.id_disciplina)" class="hidden-checkbox">
                                {{ d.nombre_disciplina }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="showEditModal = false" class="btn-secondary">Cancelar</button>
                    <button @click="saveEditInstructor" class="btn-primary" :disabled="isSaving">
                        {{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </div>
        </div>

    </main>
</template>

<style scoped>
.home-instructor {
  background-color: var(--p-surface-50);
  padding: 1.5rem;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  padding-bottom: 90px;
}

.app-header {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.btn-back {
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 14px;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--p-surface-700);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.btn-back:hover { 
  background-color: var(--p-surface-50); 
  border-color: var(--p-surface-300);
  transform: translateX(-2px);
}

.icon-back { width: 20px; height: 20px; }

.header-title {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0;
    color: var(--p-surface-900);
    letter-spacing: -0.02em;
}

.detail-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.featured-card {
    background: white;
    border-radius: 24px;
    border: 1px solid var(--p-surface-200);
    overflow: hidden;
    position: relative;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
}

.card-accent-bar {
    height: 6px;
    background: linear-gradient(90deg, var(--p-primary-600) 0%, var(--p-primary-400) 100%);
}

.featured-body { padding: 2rem; }

.featured-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.status-badge {
    padding: 0.4rem 1rem;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.badge-success { background-color: #ecfdf5; color: #059669; border: 1px solid #10b98133; }
.badge-danger { background-color: #fef2f2; color: #dc2626; border: 1px solid #ef444433; }
.badge-warning { background-color: #fffbeb; color: #d97706; border: 1px solid #f59e0b33; }

.session-type {
    font-size: 2.25rem;
    font-weight: 900;
    margin: 0 0 0.5rem 0;
    color: var(--p-surface-900);
    letter-spacing: -0.03em;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    background-color: var(--p-surface-50);
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid var(--p-surface-100);
}

.info-cell {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.info-label {
    font-size: 0.7rem;
    color: var(--p-surface-500);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--p-surface-900);
}

.actions-group {
    display: flex;
    gap: 1.25rem;
}

.btn-primary {
    background-color: var(--p-primary-600);
    color: white;
    border: none;
    padding: 1rem 1.5rem;
    border-radius: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-align: center;
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
}
.btn-primary:hover { 
  background-color: var(--p-primary-700); 
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
}

.btn-danger {
    background-color: #fff1f2;
    color: #e11d48;
    border: 1px solid #fecdd3;
    padding: 1rem 1.5rem;
    border-radius: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.btn-danger:hover { 
  background-color: #ffe4e6; 
  border-color: #fb7185;
}

/* Chips */
.disciplinas-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.chip {
    background-color: var(--p-primary-50);
    color: var(--p-primary-700);
    border: 1px solid var(--p-primary-100);
    font-size: 0.8rem;
    padding: 0.35rem 1rem;
    border-radius: 10px;
    font-weight: 700;
}

.chip-empty {
    font-size: 0.85rem;
    color: var(--p-surface-400);
    font-style: italic;
}

/* Modales */
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

.loading-state {
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

</style>