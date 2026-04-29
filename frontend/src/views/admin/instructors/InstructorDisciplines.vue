<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { useInstructorStore } from '@/stores/instructorStore';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';

// Import Icons
import IconFutbol from '@/components/icons/sports/IconFutbol.vue';
import IconBasquetbol from '@/components/icons/sports/IconBasquetbol.vue';
import IconTenis from '@/components/icons/sports/IconTenis.vue';
import IconVoleibol from '@/components/icons/sports/IconVoleibol.vue';
import IconSquash from '@/components/icons/sports/IconSquash.vue';
import IconFrontenis from '@/components/icons/sports/IconFrontenis.vue';
import IconPadel from '@/components/icons/sports/IconPadel.vue';
import IconDefault from '@/components/icons/sports/IconDefault.vue';

const route = useRoute();
const router = useRouter();
const instructorStore = useInstructorStore();
const toast = useToast();

const instructorId = route.params.id;
const instructor = ref(null);
const allDisciplines = ref([]);
const selectedDisciplines = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);

const statusOverlay = ref({
    show: false,
    type: 'success', // 'success' or 'error'
    message: ''
});

onMounted(async () => {
    await Promise.all([
        fetchInstructor(),
        fetchAllDisciplines()
    ]);
    isLoading.value = false;
});

const fetchInstructor = async () => {
    try {
        const data = await instructorStore.fetchInstructorDetails(instructorId);
        instructor.value = data;
        selectedDisciplines.value = data.disciplinas ? data.disciplinas.map(d => d.id_disciplina) : [];
    } catch (error) {
        console.error("Error fetching instructor:", error);
    }
};

const fetchAllDisciplines = async () => {
    try {
        const res = await api.get('/disciplinas/all');
        if (res.data.success) {
            allDisciplines.value = res.data.data;
        }
    } catch (error) {
        console.error("Error fetching all disciplines:", error);
    }
};

const toggleSelection = (id) => {
    const index = selectedDisciplines.value.indexOf(id);
    if (index > -1) {
        selectedDisciplines.value.splice(index, 1);
    } else {
        selectedDisciplines.value.push(id);
    }
};

const assignedDisciplinesList = computed(() => {
    return allDisciplines.value.filter(d => selectedDisciplines.value.includes(d.id_disciplina));
});

const availableDisciplinesList = computed(() => {
    return allDisciplines.value.filter(d => !selectedDisciplines.value.includes(d.id_disciplina));
});

const getIcon = (name) => {
    if (!name) return IconDefault;
    const n = name.toLowerCase();
    if (n.includes('futbol')) return IconFutbol;
    if (n.includes('basquetbol')) return IconBasquetbol;
    if (n.includes('tenis') && !n.includes('padel') && !n.includes('squash')) return IconTenis;
    if (n.includes('voleibol')) return IconVoleibol;
    if (n.includes('squash')) return IconSquash;
    if (n.includes('frontenis')) return IconFrontenis;
    if (n.includes('padel')) return IconPadel;
    return IconDefault;
};

const saveChanges = async () => {
    isSaving.value = true;
    try {
        const res = await instructorStore.updateInstructor(instructorId, {
            disciplinas: selectedDisciplines.value
        });
        if (res.success) {
            statusOverlay.value = {
                show: true,
                type: 'success',
                message: 'Disciplinas actualizadas exitosamente'
            };
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cambios guardados', life: 3000 });
            
            setTimeout(() => {
                router.push('/admin/instructors');
            }, 1500);
        } else {
            statusOverlay.value = {
                show: true,
                type: 'error',
                message: res.error || 'Error al guardar cambios'
            };
            toast.add({ severity: 'error', summary: 'Error', detail: res.error || 'No se pudo guardar', life: 5000 });
            
            setTimeout(() => {
                statusOverlay.value.show = false;
            }, 2500);
        }
    } catch (error) {
        console.error("Error saving disciplines:", error);
        toast.add({ severity: 'error', summary: 'Error Crítico', detail: 'Error de conexión', life: 5000 });
    } finally {
        isSaving.value = false;
    }
};

const goBack = () => {
    router.push('/admin/instructors');
};
</script>

<template>
    <main class="home-instructor">
        <Toast />
        
        <!-- Status Overlay -->
        <Transition name="fade">
            <div v-if="statusOverlay.show" class="status-overlay" :class="statusOverlay.type">
                <div class="overlay-content">
                    <div class="icon-wrapper">
                        <svg v-if="statusOverlay.type === 'success'" xmlns="http://www.w3.org/2000/svg" class="w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    </div>
                    <h2>{{ statusOverlay.message }}</h2>
                </div>
            </div>
        </Transition>
        <header class="app-header">
            <button @click="goBack" class="btn-back">
                <svg class="icon-back" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </button>
            <div>
                <p class="greeting-label">Gestión de Disciplinas</p>
                <h1 class="header-title" v-if="instructor">{{ instructor.nombre_completo }}</h1>
            </div>
        </header>

        <section v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <p>Cargando disciplinas...</p>
        </section>

        <section v-else class="disciplines-content">
            <!-- SECCIÓN 1: DISCIPLINAS QUE IMPARTE -->
            <div class="card mb-8 section-assigned">
                <div class="card-header flex justify-between items-end">
                    <div>
                        <h3 class="text-emerald-700">Disciplinas que Imparte</h3>
                        <p class="text-sm text-emerald-600/70">Estas son las disciplinas asignadas actualmente.</p>
                    </div>
                    <span class="badge-count bg-emerald-100 text-emerald-700">{{ assignedDisciplinesList.length }}</span>
                </div>

                <div class="disciplines-grid">
                    <div v-for="d in assignedDisciplinesList" :key="d.id_disciplina" 
                         @click="toggleSelection(d.id_disciplina)"
                         class="discipline-item active">
                        <div class="discipline-card-body">
                            <div class="icon-circle">
                                <component :is="getIcon(d.nombre_disciplina)" class="w-8 h-8" />
                            </div>
                            <span class="discipline-name">{{ d.nombre_disciplina }}</span>
                            <div class="check-mark">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                        </div>
                    </div>
                    <div v-if="assignedDisciplinesList.length === 0" class="empty-mini-state">
                        No hay disciplinas asignadas. Selecciona una de abajo para agregarla.
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: DISCIPLINAS DISPONIBLES -->
            <div class="card section-available">
                <div class="card-header flex justify-between items-end">
                    <div>
                        <h3 class="text-gray-700">Otras Disciplinas Disponibles</h3>
                        <p class="text-sm text-gray-500">Haz clic para agregar una nueva disciplina al instructor.</p>
                    </div>
                    <span class="badge-count bg-gray-100 text-gray-600">{{ availableDisciplinesList.length }}</span>
                </div>

                <div class="disciplines-grid">
                    <div v-for="d in availableDisciplinesList" :key="d.id_disciplina" 
                         @click="toggleSelection(d.id_disciplina)"
                         class="discipline-item">
                        <div class="discipline-card-body">
                            <div class="icon-circle">
                                <component :is="getIcon(d.nombre_disciplina)" class="w-8 h-8 opacity-40" />
                            </div>
                            <span class="discipline-name">{{ d.nombre_disciplina }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="actions-sticky">
                <button @click="goBack" class="btn-secondary">Cancelar</button>
                <button @click="saveChanges" class="btn-primary" :disabled="isSaving">
                    {{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}
                </button>
            </div>
        </section>
    </main>
</template>

<style scoped>
.home-instructor {
    background-color: var(--p-surface-50);
    padding: 1.5rem;
    min-height: 100vh;
    padding-bottom: 100px;
}

.app-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
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

.header-title {
    font-size: 1.75rem;
    font-weight: 800;
    margin: 0;
    color: var(--p-surface-900);
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
    transition: all 0.2s;
}

.icon-back { width: 20px; height: 20px; }

.card {
    background: white;
    border-radius: 24px;
    border: 1px solid var(--p-surface-200);
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.section-assigned {
    border-color: var(--p-emerald-200);
    background-color: var(--p-emerald-50/30);
}

.card-header { margin-bottom: 1.5rem; }
.card-header h3 { font-size: 1.15rem; font-weight: 800; }

.badge-count {
    padding: 0.25rem 0.75rem;
    border-radius: 99px;
    font-size: 0.75rem;
    font-weight: 800;
}

.disciplines-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1rem;
}

.discipline-item {
    cursor: pointer;
    position: relative;
    transition: transform 0.2s;
}

.discipline-item:hover {
    transform: translateY(-4px);
}

.discipline-card-body {
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 20px;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    height: 100%;
}

.discipline-item.active .discipline-card-body {
    background: white;
    border-color: var(--p-emerald-500);
    box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.1);
}

.icon-circle {
    width: 56px;
    height: 56px;
    background: var(--p-surface-50);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--p-surface-400);
    transition: all 0.3s;
}

.discipline-item.active .icon-circle {
    background: var(--p-emerald-600);
    color: white;
}

.discipline-name {
    font-weight: 700;
    color: var(--p-surface-600);
    text-align: center;
    font-size: 0.85rem;
}

.discipline-item.active .discipline-name {
    color: var(--p-emerald-900);
}

.check-mark {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--p-emerald-600);
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.empty-mini-state {
    grid-column: 1 / -1;
    padding: 2rem;
    text-align: center;
    color: var(--p-emerald-600/60);
    font-style: italic;
    font-size: 0.85rem;
    background: white;
    border-radius: 16px;
    border: 2px dashed var(--p-emerald-200);
}

.actions-sticky {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(12px);
    padding: 1.25rem 2rem;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    border-top: 1px solid var(--p-surface-200);
    z-index: 100;
}

.btn-primary {
    background-color: var(--p-primary-600);
    color: white;
    border: none;
    padding: 0.875rem 2.5rem;
    border-radius: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary:hover:not(:disabled) {
    background-color: var(--p-primary-700);
    transform: translateY(-2px);
}

.btn-secondary {
    background-color: white;
    color: var(--p-surface-700);
    border: 1px solid var(--p-surface-200);
    padding: 0.875rem 2rem;
    border-radius: 14px;
    font-weight: 700;
    cursor: pointer;
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 5rem;
    gap: 1rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--p-surface-200);
    border-top-color: var(--p-primary-600);
    border-radius: 50%;
    animation: spin 1s infinite linear;
}

@keyframes spin { 100% { transform: rotate(360deg); } }

/* Status Overlay Styles */
.status-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(8px);
    background: rgba(255, 255, 255, 0.8);
}

.status-overlay.success { color: var(--p-emerald-600); }
.status-overlay.error { color: var(--p-red-600); }

.overlay-content {
    text-align: center;
    animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.icon-wrapper {
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: center;
}

.overlay-content h2 {
    font-size: 1.5rem;
    font-weight: 800;
}

@keyframes popIn {
    0% { transform: scale(0.5); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
