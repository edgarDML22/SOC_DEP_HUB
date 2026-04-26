<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';

const route = useRoute();
const router = useRouter();

const instructorId = route.params.id;

const instructor = ref(null);
const disciplinasList = ref([]);
const errorMsg = ref('');
const isLoading = ref(true);

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
    isLoading.value = true;
    errorMsg.value = '';
    try {
        const response = await api.get(`/instructors/${instructorId}`);
        if (response.data && response.data.success) {
            instructor.value = response.data.data;
            // Popular formulario de edición
            editForm.value = {
                nombre_completo: instructor.value.nombre_completo,
                telefono: instructor.value.telefono || '',
                fecha_nacimiento: instructor.value.fecha_nacimiento || '',
                fecha_contratacion: instructor.value.fecha_contratacion || '',
                estatus: instructor.value.estatus,
                disciplinas: instructor.value.disciplinas.map(d => d.id_disciplina)
            };
        }
    } catch (error) {
        console.error("Error cargando detalles del instructor:", error);
        errorMsg.value = "Hubo un problema al cargar los detalles del instructor.";
    } finally {
        isLoading.value = false;
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
        const res = await api.put(`/instructors/update/${instructorId}`, editForm.value);
        if (res.data.success) {
            showEditModal.value = false;
            await fetchInstructorDetails(); // Refrescar datos locales
        }
    } catch (error) {
        console.error("Error al actualizar instructor:", error);
        alert(error.response?.data?.message || "Ocurrió un error al actualizar.");
    } finally {
        isSaving.value = false;
    }
};

const darDeBaja = async () => {
    if (!confirm("¿Estás seguro de que deseas dar de baja o inactivar a este instructor?")) return;
    
    isDeleting.value = true;
    try {
        const res = await api.delete(`/instructors/delete/${instructorId}`);
        if (res.data.success) {
            alert("Instructor dado de baja exitosamente.");
            router.push('/admin/instructors');
        }
    } catch (error) {
        console.error("Error al eliminar instructor:", error);
        alert(error.response?.data?.message || "Error al dar de baja al instructor.");
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
        <section v-if="errorMsg" class="error-state">
            <p class="error-text">{{ errorMsg }}</p>
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
                            'badge-success': instructor.estatus === 'ACTIVO',
                            'badge-danger': instructor.estatus === 'INACTIVO',
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
  background-color: var(--p-surface-50, #f8fafc);
  padding: 1rem;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  font-family: inherit;
  padding-bottom: 90px;
}

.app-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.btn-back {
    background: white;
    border: 1px solid var(--p-surface-200, #e2e8f0);
    border-radius: 12px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--p-surface-700, #334155);
    cursor: pointer;
    transition: all 0.2s;
}
.btn-back:hover { background-color: var(--p-surface-100, #f1f5f9); }

.icon-back { width: 24px; height: 24px; }

.header-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    color: var(--p-surface-900, #111827);
}

.detail-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.featured-card {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--p-surface-200, #e2e8f0);
    overflow: hidden;
    position: relative;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.card-accent-bar {
    height: 6px;
    background: linear-gradient(90deg, var(--p-primary-500, #3b82f6) 0%, var(--p-primary-300, #93c5fd) 100%);
}

.featured-body { padding: 1.5rem; }

.featured-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
}

.badge-success { background-color: #10b981; }
.badge-danger { background-color: #ef4444; }
.badge-warning { background-color: #f59e0b; }

.session-type {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0 0 0.5rem 0;
    color: var(--p-surface-900, #111827);
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    background-color: var(--p-surface-50, #f8fafc);
    border-radius: 12px;
    padding: 1rem;
}

.info-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    color: var(--p-surface-500, #64748b);
    font-weight: 600;
    text-transform: uppercase;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--p-surface-900, #111827);
}

.actions-group {
    display: flex;
    gap: 1rem;
}

.btn-primary {
    background-color: var(--p-primary-600, #2563eb);
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    text-align: center;
}
.btn-primary:hover { background-color: var(--p-primary-700, #1d4ed8); }

.btn-danger {
    background-color: white;
    color: #ef4444;
    border: 1px solid #fca5a5;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.btn-danger:hover { background-color: #fef2f2; }

/* Chips */
.disciplinas-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.chip {
    background-color: var(--p-primary-50, #eff6ff);
    color: var(--p-primary-700, #1d4ed8);
    border: 1px solid var(--p-primary-200, #bfdbfe);
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-weight: 600;
}

.chip-empty {
    font-size: 0.75rem;
    color: var(--p-surface-400, #9ca3af);
    font-style: italic;
}

/* Modales */
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

</style>