<script setup>
import { ref, watch, computed } from 'vue';
import { useInstructorStore } from '@/stores/admin/instructorStore';
import { useAlerts } from '@/composables/useAlerts';
import IconHourglass from '@/components/icons/IconHourglass.vue';
import IconLock from '@/components/icons/IconLock.vue';
import IconUser from '@/components/icons/IconUser.vue';
import IconShield from '@/components/icons/IconShield.vue';
import AppModal from '@/components/ui/modal/AppModal.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppSelect from '@/components/ui/form/AppSelect.vue';
import AppStepper from '@/components/ui/status/AppStepper.vue';
import StatusOptionGroup from '@/components/ui/status/StatusOptionGroup.vue';
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';

const props = defineProps({
    show: Boolean,
    instructorId: [String, Number]
});

const emit = defineEmits(['close', 'updated']);

const instructorStore = useInstructorStore();
const { toastSuccess, toastError, toastInfo } = useAlerts();

const STATUS_OPTIONS = [
    { value: 'ACTIVO', label: 'Trabajando normalmente', description: 'Activo, con acceso total a sus clases y al sistema.', icon: IconUser, tone: 'emerald' },
    { value: 'BAJA_TEMPORAL', label: 'Baja temporal', description: 'Incapacidad o permiso. Alguien cubrirá sus clases.', icon: IconHourglass, tone: 'amber' },
    { value: 'INACTIVO', label: 'Baja definitiva', description: 'Fuera del club. Sus clases se reasignan de forma permanente.', icon: IconLock, tone: 'rose' }
];
const ACTION_OPTIONS = [
    { label: 'Reasignar a un compañero', value: 'reasignar' },
    { label: 'Suspender la clase', value: 'deshabilitar' }
];
const substituteOptions = (idActividad) => (candidateSubstitutes.value[idActividad] || [])
    .map(c => ({ label: c.nombre_completo, value: c.id_instructor }));

const instructor = ref(null);
const activities = ref([]);
const isLoading = ref(false);
const isSaving = ref(false);

// Step Management
const currentStep = ref(1); // 1: Status Selection, 2: Impact Analysis, 3: Confirmation

// Form State
const selectedStatus = ref('');
const reassignments = ref([]);
const candidateSubstitutes = ref({});
const recoverOriginals = ref(false);

// Watch for modal opening to load data
watch(() => props.show, async (newVal) => {
    if (newVal && props.instructorId) {
        resetState();
        if (instructorStore.currentInstructor && String(instructorStore.currentInstructor.id_instructor) === String(props.instructorId)) {
            instructor.value = instructorStore.currentInstructor;
            selectedStatus.value = instructor.value.estatus;
        } else {
            isLoading.value = true;
            try {
                const data = await instructorStore.fetchInstructorDetails(props.instructorId);
                instructor.value = data;
                selectedStatus.value = data.estatus;
            } catch (error) {
                toastError('No se pudo cargar el instructor');
                emit('close');
            } finally {
                isLoading.value = false;
            }
        }
    }
});

const resetState = () => {
    currentStep.value = 1;
    selectedStatus.value = '';
    reassignments.value = [];
    candidateSubstitutes.value = {};
    recoverOriginals.value = false;
    instructor.value = null;
    activities.value = [];
};

const nextStep = async () => {
    if (currentStep.value === 1) {
        if (!selectedStatus.value) return;
        if (selectedStatus.value === instructor.value?.estatus) {
            toastInfo('Sin cambios', 'El estatus seleccionado es el mismo que el actual.', 'info');
            return;
        }

        if (selectedStatus.value === 'ACTIVO' && instructor.value?.estatus === 'BAJA_TEMPORAL') {
            currentStep.value = 3;
            return;
        }

        // Cargar impacto sólo cuando se decide avanzar
        isLoading.value = true;
        try {
            const data = await instructorStore.fetchStatusImpact(props.instructorId);
            if (data.success) {
                activities.value = data.actividades;
                reassignments.value = activities.value.map(a => ({
                    id_actividad: a.id_actividad_plantilla,
                    nombre: a.disciplina?.nombre_disciplina || 'Actividad',
                    horario: `${a.dia_semana} ${a.hora_inicio} - ${a.hora_fin}`,
                    action: 'reasignar',
                    substituteId: null
                }));
            }
        } catch (error) {
            toastError('No se pudo cargar el impacto en sus clases');
            isLoading.value = false;
            return;
        }

        if (activities.value.length === 0) {
            currentStep.value = 3;
            isLoading.value = false;
        } else {
            currentStep.value = 2;
            await loadAllSubstitutes();
        }
    } else if (currentStep.value === 2) {
        const missing = reassignments.value.find(r => r.action === 'reasignar' && !r.substituteId);
        if (missing) {
            toastInfo('Falta un sustituto', 'Selecciona un sustituto para todas las clases que vas a reasignar.', 'warning');
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

        const res = await instructorStore.applyMeticulousStatus(props.instructorId, payload);
        if (res.success) {
            toastSuccess(`Estatus de ${instructor.value?.nombre_completo || 'instructor'} actualizado`);
            emit('updated');
            emit('close');
        } else {
            toastError(res.message || 'No se pudo actualizar el estatus');
        }
    } catch (error) {
        toastError('Error al procesar el cambio de estatus');
    } finally {
        isSaving.value = false;
    }
};

</script>

<template>
    <AppModal
        :model-value="show"
        title="Cambiar estatus"
        :subtitle="instructor?.nombre_completo || 'Instructor'"
        :icon="IconShield"
        size="lg"
        :persistent="isSaving"
        @update:model-value="(v) => !v && emit('close')"
    >
        <div class="flex flex-col gap-6">
            <AppStepper :steps="['Situación', 'Clases', 'Revisar']" :current="currentStep" />

            <div v-if="isLoading" class="flex flex-col items-center justify-center gap-3 py-10">
                <LoadingSpinner size="lg" />
                <p class="m-0 text-sm font-semibold text-surface-600">Analizando sus clases…</p>
            </div>

            <!-- PASO 1: situación -->
            <section v-else-if="currentStep === 1" class="flex flex-col gap-4">
                <div>
                    <h3 class="m-0 text-base font-bold text-surface-900">¿Cuál es su situación actual?</h3>
                    <p class="m-0 mt-1 text-sm text-surface-600">Elige una opción para gestionar sus clases y su acceso.</p>
                </div>
                <StatusOptionGroup v-model="selectedStatus" :options="STATUS_OPTIONS" :current="instructor?.estatus" label="Situación del instructor" />
            </section>

            <!-- PASO 2: repartir clases -->
            <section v-else-if="currentStep === 2" class="flex flex-col gap-4">
                <div>
                    <h3 class="m-0 text-base font-bold text-surface-900">Repartir sus clases</h3>
                    <p class="m-0 mt-1 text-sm text-surface-600">
                        Tiene <strong class="text-surface-900">{{ activities.length }}</strong>
                        {{ activities.length === 1 ? 'clase' : 'clases' }}. Elige un sustituto o suspende la clase.
                    </p>
                </div>

                <article v-for="r in reassignments" :key="r.id_actividad" class="rounded-2xl ring-1 ring-surface-200">
                    <header class="flex flex-col gap-3 border-b border-surface-200/70 bg-surface-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="m-0 truncate text-sm font-bold text-surface-900">{{ r.nombre }}</p>
                            <p class="m-0 text-xs font-semibold text-surface-600">{{ r.horario }}</p>
                        </div>
                        <AppSelect v-model="r.action" :options="ACTION_OPTIONS" class="sm:!w-56" />
                    </header>
                    <div v-if="r.action === 'reasignar'" class="p-4">
                        <AppSelect
                            v-if="candidateSubstitutes[r.id_actividad]?.length"
                            v-model="r.substituteId"
                            :options="substituteOptions(r.id_actividad)"
                            placeholder="Seleccionar compañero…"
                        />
                        <div v-else class="flex items-start gap-2.5 rounded-xl bg-amber-50 p-3 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30">
                            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01" /></svg>
                            No hay instructores libres en este horario. Tendrás que suspender la clase.
                        </div>
                    </div>
                </article>
            </section>

            <!-- PASO 3: revisar -->
            <section v-else class="flex flex-col gap-4">
                <div>
                    <h3 class="m-0 text-base font-bold text-surface-900">Revisar y confirmar</h3>
                    <p class="m-0 mt-1 text-sm text-surface-600">Confirma que todo sea correcto antes de guardar.</p>
                </div>

                <div class="flex flex-col gap-4 rounded-2xl bg-surface-50 p-4 ring-1 ring-surface-200">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm font-semibold text-surface-600">Nueva situación</span>
                        <BadgeStatus :status="selectedStatus" />
                    </div>

                    <label v-if="selectedStatus === 'ACTIVO' && instructor?.estatus === 'BAJA_TEMPORAL'"
                        class="flex cursor-pointer items-center gap-3 rounded-xl bg-emerald-50 p-3 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:ring-emerald-500/30">
                        <input v-model="recoverOriginals" type="checkbox" class="h-5 w-5 accent-emerald-600" />
                        <span class="text-sm font-bold text-emerald-900 dark:text-emerald-200">Regresar a sus clases originales (ya volvió)</span>
                    </label>

                    <div v-if="activities.length" class="flex flex-col gap-2">
                        <p class="m-0 text-xs font-bold uppercase tracking-wider text-surface-600">Resumen de clases</p>
                        <div v-for="r in reassignments" :key="r.id_actividad" class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2.5 text-xs ring-1 ring-surface-200">
                            <span class="truncate font-semibold text-surface-800">{{ r.nombre }}</span>
                            <span class="shrink-0 rounded-full px-2 py-0.5 font-bold uppercase tracking-wider"
                                :class="r.action === 'reasignar' ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'">
                                {{ r.action === 'reasignar' ? 'Reasignada' : 'Suspendida' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="selectedStatus === 'INACTIVO'" class="flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" /><path d="M12 8v4M12 16h.01" /></svg>
                    Al dar de baja definitiva, su acceso al sistema se revoca de inmediato.
                </div>
            </section>
        </div>

        <template #footer>
            <AppButton v-if="currentStep > 1" variant="secondary" :disabled="isSaving || isLoading" class="sm:mr-auto" @click="prevStep">Anterior</AppButton>
            <AppButton v-else variant="secondary" @click="emit('close')">Cancelar</AppButton>
            <AppButton v-if="currentStep < 3" :loading="isLoading" :disabled="!selectedStatus" @click="nextStep">Siguiente</AppButton>
            <AppButton v-else :tone="selectedStatus === 'INACTIVO' ? 'rose' : 'blue'" :loading="isSaving" @click="saveChanges">Guardar cambios</AppButton>
        </template>
    </AppModal>
</template>

