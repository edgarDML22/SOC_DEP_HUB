<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useInstructorStore } from '@/stores/instructorStore';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import IconClock from '@/components/icons/IconClock.vue';
import IconHourglass from '@/components/icons/IconHourglass.vue';
import IconLock from '@/components/icons/IconLock.vue';
import IconUser from '@/components/icons/IconUser.vue';
import IconArrowLeft from '@/components/icons/IconArrowLeft.vue';

const route = useRoute();
const router = useRouter();
const instructorStore = useInstructorStore();
const toast = useToast();

const instructorId = route.params.id;
const instructor = ref(null);
const activities = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);

// Step Management
const currentStep = ref(1); // 1: Status Selection, 2: Impact Analysis, 3: Confirmation

// Form State
const selectedStatus = ref('');
const reassignments = ref([]); // Array of { id_actividad, action: 'reasignar' | 'deshabilitar', substituteId: null }
const candidateSubstitutes = ref({}); // activityId -> [candidates]
const recoverOriginals = ref(false);

onMounted(async () => {
    try {
        const data = await instructorStore.fetchStatusImpact(instructorId);
        if (data.success) {
            instructor.value = data.instructor;
            activities.value = data.actividades;
            selectedStatus.value = instructor.value.estatus;

            // Initialize reassignments
            reassignments.value = activities.value.map(a => ({
                id_actividad: a.id_actividad_plantilla,
                nombre: a.disciplina?.nombre_disciplina || 'Actividad',
                horario: `${a.dia_semana} ${a.hora_inicio} - ${a.hora_fin}`,
                action: 'reasignar',
                substituteId: null
            }));
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar la información del instructor', life: 5000 });
    } finally {
        isLoading.value = false;
    }
});

const nextStep = async () => {
    if (currentStep.value === 1) {
        if (!selectedStatus.value) return;
        if (selectedStatus.value === instructor.value.estatus) {
            toast.add({ severity: 'info', summary: 'Sin cambios', detail: 'El estatus seleccionado es el mismo actual', life: 3000 });
            return;
        }

        if (selectedStatus.value === 'ACTIVO' && instructor.value.estatus === 'BAJA_TEMPORAL') {
            currentStep.value = 3; // Go straight to confirmation if returning from leave
            return;
        }

        if (activities.value.length === 0) {
            currentStep.value = 3; // No activities to reassign
        } else {
            currentStep.value = 2;
            await loadAllSubstitutes();
        }
    } else if (currentStep.value === 2) {
        // Validate that all reassignments have a choice
        const missing = reassignments.value.find(r => r.action === 'reasignar' && !r.substituteId);
        if (missing) {
            toast.add({ severity: 'warn', summary: 'Atención', detail: 'Debes seleccionar un sustituto para todas las actividades reasignadas o elegir deshabilitar', life: 4000 });
            return;
        }
        currentStep.value = 3;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const loadAllSubstitutes = async () => {
    isLoading.value = true;
    try {
        for (const act of activities.value) {
            const res = await instructorStore.fetchCandidateSubstitutes(act.id_actividad_plantilla);
            if (res.success) {
                candidateSubstitutes.value[act.id_actividad_plantilla] = res.candidatos;
            }
        }
    } catch (error) {
        console.error("Error loading substitutes:", error);
    } finally {
        isLoading.value = false;
    }
};

const saveChanges = async () => {
    isSaving.value = true;
    try {
        const payload = {
            nuevo_estatus: selectedStatus.value,
            reasignaciones: reassignments.value.map(r => ({
                id_actividad: r.id_actividad,
                accion: r.action,
                id_sustituto: r.substituteId
            })),
            recuperar_originales: recoverOriginals.value
        };

        const res = await instructorStore.applyMeticulousStatus(instructorId, payload);
        if (res.success) {
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Estatus actualizado correctamente', life: 3000 });
            setTimeout(() => router.push('/admin/instructors'), 1500);
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: res.message, life: 5000 });
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error crítico al procesar el cambio', life: 5000 });
    } finally {
        isSaving.value = false;
    }
};

const goBack = () => router.push('/admin/instructors');

const getStatusColor = (status) => {
    switch (status) {
        case 'ACTIVO': return 'text-emerald-600 bg-emerald-50 border-emerald-200';
        case 'INACTIVO': return 'text-red-600 bg-red-50 border-red-200';
        case 'BAJA_TEMPORAL': return 'text-amber-600 bg-amber-50 border-amber-200';
        default: return 'text-gray-600 bg-gray-50 border-gray-200';
    }
};
</script>

<template>
    <main class="status-management">
        <Toast />
        <header class="app-header">
            <button @click="goBack" class="btn-back">
                <IconArrowLeft class="w-5 h-5" />
            </button>
            <div>
                <p class="greeting-label">Cambiar situación del instructor</p>
                <h1 class="header-title" v-if="instructor">{{ instructor.nombre_completo }}</h1>
            </div>
        </header>

        <section v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <p>Cargando información...</p>
        </section>

        <section v-else class="wizard-container">
            <!-- STEPS INDICATOR -->
            <div class="steps-nav">
                <div class="step-item" :class="{ 'active': currentStep >= 1, 'completed': currentStep > 1 }">
                    <span class="step-num">1</span>
                    <span class="step-label">Situación</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item" :class="{ 'active': currentStep >= 2, 'completed': currentStep > 2 }">
                    <span class="step-num">2</span>
                    <span class="step-label">Clases</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item" :class="{ 'active': currentStep >= 3 }">
                    <span class="step-num">3</span>
                    <span class="step-label">Revisar</span>
                </div>
            </div>

            <!-- STEP 1: SELECT NEW STATUS -->
            <div v-if="currentStep === 1" class="step-content animate-fade-in">
                <div class="card">
                    <h2 class="section-title">¿Qué está pasando con este instructor?</h2>
                    <p class="section-desc">Selecciona la situación actual. El sistema te ayudará a saber qué pasará con sus clases y su acceso al club.</p>

                    <div class="status-options">
                        <label class="status-option" :class="{ 'selected': selectedStatus === 'ACTIVO' }">
                            <input type="radio" v-model="selectedStatus" value="ACTIVO" class="hidden">
                            <div class="option-icon bg-emerald-100 text-emerald-600">
                                <IconUser class="w-6 h-6" />
                            </div>
                            <div class="option-info">
                                <h3>Trabajando normalmente</h3>
                                <p>Está activo, puede entrar al sistema e impartir sus clases sin problemas.</p>
                            </div>
                        </label>

                        <label class="status-option" :class="{ 'selected': selectedStatus === 'BAJA_TEMPORAL' }">
                            <input type="radio" v-model="selectedStatus" value="BAJA_TEMPORAL" class="hidden">
                            <div class="option-icon bg-amber-100 text-amber-600">
                                <IconHourglass class="w-6 h-6" />
                            </div>
                            <div class="option-info">
                                <h3>Ausente por un tiempo (Incapacidad / Permiso)</h3>
                                <p>Aún podrá entrar al sistema para ver avisos, pero alguien más debe cubrir sus clases mientras no está.</p>
                            </div>
                        </label>

                        <label class="status-option" :class="{ 'selected': selectedStatus === 'INACTIVO' }">
                            <input type="radio" v-model="selectedStatus" value="INACTIVO" class="hidden">
                            <div class="option-icon bg-red-100 text-red-600">
                                <IconLock class="w-6 h-6" />
                            </div>
                            <div class="option-info">
                                <h3>Baja definitiva / Fuera del club</h3>
                                <p>Ya no tendrá acceso al sistema. Sus clases deben pasar a otro instructor de forma permanente o cancelarse.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- STEP 2: IMPACT ANALYSIS & REASSIGNMENT -->
            <div v-if="currentStep === 2" class="step-content animate-fade-in">
                <div class="card">
                    <h2 class="section-title">Repartir sus clases</h2>
                    <p class="section-desc">Este instructor tiene <strong>{{ activities.length }}</strong> clases asignadas. Elige quién las cubrirá o si prefieres suspenderlas por ahora.</p>

                    <div class="activities-reassign-list">
                        <div v-for="(r, index) in reassignments" :key="r.id_actividad" class="reassign-item">
                            <div class="reassign-header">
                                <div class="act-info">
                                    <span class="act-name">{{ r.nombre }}</span>
                                    <span class="act-schedule">{{ r.horario }}</span>
                                </div>
                                <div class="act-actions">
                                    <select v-model="r.action" class="action-select">
                                        <option value="reasignar">Pasar a otro instructor</option>
                                        <option value="deshabilitar">Suspender clase</option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="r.action === 'reasignar'" class="substitute-selector">
                                <div v-if="candidateSubstitutes[r.id_actividad]?.length > 0">
                                    <p class="selector-label">Instructores disponibles para esta hora:</p>
                                    <select v-model="r.substituteId" class="sub-select">
                                        <option :value="null" disabled>Selecciona un compañero...</option>
                                        <option v-for="c in candidateSubstitutes[r.id_actividad]" :key="c.id_instructor"
                                            :value="c.id_instructor">
                                            {{ c.nombre_completo }}
                                        </option>
                                    </select>
                                </div>
                                <div v-else class="no-subs-alert">
                                    ⚠️ No hay instructores libres en este horario. Tendrás que suspender la clase o buscar otra solución.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: FINAL CONFIRMATION -->
            <div v-if="currentStep === 3" class="step-content animate-fade-in">
                <div class="card">
                    <h2 class="section-title">Revisar y Guardar</h2>
                    <p class="section-desc">Casi terminamos. Revisa que los cambios sean correctos antes de aplicarlos.</p>

                    <div class="summary-box">
                        <div class="summary-item">
                            <span>Nueva situación:</span>
                            <span class="badge" :class="getStatusColor(selectedStatus)">
                                {{ selectedStatus === 'ACTIVO' ? 'Trabajando' : selectedStatus === 'BAJA_TEMPORAL' ? 'Ausente temporal' : 'Baja definitiva' }}
                            </span>
                        </div>

                        <div v-if="selectedStatus === 'ACTIVO' && instructor.estatus === 'BAJA_TEMPORAL'"
                            class="recover-option">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" v-model="recoverOriginals" class="w-5 h-5 accent-emerald-600">
                                <span class="text-emerald-800 font-bold">Devolverle sus clases originales (Ya regresó de su ausencia)</span>
                            </label>
                        </div>

                        <div v-if="currentStep >= 2 && activities.length > 0" class="summary-activities">
                            <h3>Resumen de sus clases:</h3>
                            <ul>
                                <li v-for="r in reassignments" :key="r.id_actividad">
                                    {{ r.nombre }}:
                                    <strong>{{ r.action === 'reasignar' ? 'Se pasa a otro compañero' : 'Se suspende' }}</strong>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="warning-box" v-if="selectedStatus === 'INACTIVO'">
                        <p>⚠️ <strong>Aviso Importante:</strong> Al darlo de baja definitiva, ya no podrá entrar a su cuenta del club.</p>
                    </div>
                </div>
            </div>

            <!-- WIZARD ACTIONS -->
            <div class="wizard-actions">
                <button v-if="currentStep > 1" @click="prevStep" class="btn-secondary">Anterior</button>
                <div class="flex-grow"></div>
                <button v-if="currentStep < 3" @click="nextStep" class="btn-primary">Siguiente paso</button>
                <button v-else @click="saveChanges" class="btn-save" :disabled="isSaving">
                    {{ isSaving ? 'Guardando...' : 'Confirmar cambios' }}
                </button>
            </div>
        </section>
    </main>
</template>

<style scoped>
.status-management {
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
}

.wizard-container {
    max-width: 800px;
    margin: 0 auto;
}

.steps-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2.5rem;
    padding: 0 1rem;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    position: relative;
    z-index: 1;
}

.step-num {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: white;
    border: 2px solid var(--p-surface-200);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    color: var(--p-surface-400);
    transition: all 0.3s;
}

.step-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--p-surface-400);
}

.step-item.active .step-num {
    border-color: var(--p-primary-600);
    color: var(--p-primary-600);
    box-shadow: 0 0 0 4px var(--p-primary-50);
}

.step-item.active .step-label {
    color: var(--p-primary-700);
}

.step-item.completed .step-num {
    background: var(--p-primary-600);
    border-color: var(--p-primary-600);
    color: white;
}

.step-line {
    flex-grow: 1;
    height: 2px;
    background: var(--p-surface-200);
    margin: 0 1rem;
    margin-bottom: 1.5rem;
}

.card {
    background: white;
    border-radius: 24px;
    border: 1px solid var(--p-surface-200);
    padding: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.section-title {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--p-surface-900);
    margin-bottom: 0.5rem;
}

.section-desc {
    color: var(--p-surface-500);
    margin-bottom: 2rem;
}

/* Status Options Styles */
.status-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.status-option {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.5rem;
    border: 1px solid var(--p-surface-200);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s;
}

.status-option:hover {
    border-color: var(--p-primary-300);
    background: var(--p-primary-50/20);
}

.status-option.selected {
    border-color: var(--p-primary-600);
    background: var(--p-primary-50/50);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.option-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.option-info h3 {
    font-weight: 800;
    margin: 0;
    color: var(--p-surface-900);
}

.option-info p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--p-surface-500);
}

/* Reassignment Styles */
.activities-reassign-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.reassign-item {
    border: 1px solid var(--p-surface-200);
    border-radius: 16px;
    overflow: hidden;
}

.reassign-header {
    background: var(--p-surface-50);
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--p-surface-200);
}

.act-info {
    display: flex;
    flex-direction: column;
}

.act-name {
    font-weight: 800;
    color: var(--p-surface-900);
}

.act-schedule {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.action-select {
    padding: 0.5rem;
    border-radius: 8px;
    border: 1px solid var(--p-surface-300);
    font-weight: 700;
    font-size: 0.85rem;
}

.substitute-selector {
    padding: 1rem 1.5rem;
}

.selector-label {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--p-surface-500);
    margin-bottom: 0.5rem;
}

.sub-select {
    width: 100%;
    padding: 0.75rem;
    border-radius: 10px;
    border: 1px solid var(--p-surface-200);
}

.no-subs-alert {
    padding: 0.75rem;
    background: var(--p-red-50);
    color: var(--p-red-600);
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
}

/* Confirmation Styles */
.summary-box {
    background: var(--p-surface-50);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    font-weight: 800;
}

.badge {
    padding: 0.4rem 1rem;
    border-radius: 99px;
    font-size: 0.85rem;
}

.summary-activities h3 {
    font-size: 0.9rem;
    font-weight: 800;
    margin-bottom: 0.75rem;
    color: var(--p-surface-700);
}

.summary-activities ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.summary-activities li {
    font-size: 0.875rem;
    color: var(--p-surface-600);
    margin-bottom: 0.5rem;
    padding-left: 1rem;
    border-left: 3px solid var(--p-surface-200);
}

.warning-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
    padding: 1rem;
    border-radius: 12px;
    font-size: 0.875rem;
}

.wizard-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn-primary,
.btn-save {
    background-color: var(--p-primary-600);
    color: white;
    border: none;
    padding: 0.875rem 2rem;
    border-radius: 14px;
    font-weight: 700;
    cursor: pointer;
}

.btn-secondary {
    background-color: white;
    color: var(--p-surface-700);
    border: 1px solid var(--p-surface-200);
    padding: 0.875rem 2rem;
    border-radius: 14px;
    font-weight: 700;
}

.btn-save {
    background-color: var(--p-primary-700);
    padding: 0.875rem 3rem;
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

@keyframes spin {
    100% {
        transform: rotate(360deg);
    }
}

.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
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

.hidden {
    display: none;
}
</style>
