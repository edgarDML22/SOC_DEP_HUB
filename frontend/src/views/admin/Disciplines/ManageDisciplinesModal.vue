<script setup>
import { ref, computed, watch } from 'vue';
import api from '@/services/api';
import { useSpacesStore } from '@/stores/admin/spaces';
import { useInstructorStore } from '@/stores/admin/instructorStore';
import { useDisciplinesStore } from '@/stores/admin/disciplines';
import { useAlerts } from '@/composables/useAlerts';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import AppModal from '@/components/ui/modal/AppModal.vue';
import AppButton from '@/components/ui/AppButton.vue';
import SearchInput from '@/components/gerente/ui/SearchInput.vue';
import { IconGrid } from '@/components/icons';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';

const props = defineProps({
    show: { type: Boolean, required: true },
    type: { type: String, required: true }, // 'instructor' | 'space'
    item: { type: Object, default: () => ({}) }
});

const emit = defineEmits(['close', 'saved']);

const { toastInfo, toastSuccess, toastError } = useAlerts();
const search = ref('');
const spacesStore = useSpacesStore();
const instructorStore = useInstructorStore();
const disciplinesStore = useDisciplinesStore();

const isLoading = ref(true);
const isSaving = ref(false);
const selectedDisciplines = ref([]);
const allDisciplines = ref([]);
const sortedDisciplines = computed(() => {
    return [...allDisciplines.value].sort((a, b) => 
        (a.nombre_disciplina || '').localeCompare(b.nombre_disciplina || '', 'es', { sensitivity: 'base' })
    );
});
const initialDisciplines = ref([]);
const processingDisciplines = ref([]); // Almacena IDs de disciplinas en proceso

const headerSubtitle = computed(() =>
    props.type === 'instructor' ? props.item?.nombre_completo || '' : props.item?.nombre_espacio || ''
);

const visibleDisciplines = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q ? sortedDisciplines.value.filter(d => (d.nombre_disciplina || '').toLowerCase().includes(q)) : sortedDisciplines.value;
});

const isSpaceActive = computed(() => props.type === 'space' && props.item?.estatus === 'ACTIVO');

// Cargar catálogo de disciplinas
const loadDisciplines = async () => {
    isLoading.value = true;
    try {
        await disciplinesStore.fetchDisciplines();
        allDisciplines.value = disciplinesStore.disciplines;
    } catch (error) {
        console.error("Error loading disciplines:", error);
    } finally {
        isLoading.value = false;
    }
};

watch(() => props.show, async (newVal) => {
    if (newVal) {
        search.value = '';
        // Inicializar
        const ids = props.item?.disciplinas ? props.item.disciplinas.map(d => d.id_disciplina) : [];
        selectedDisciplines.value = [...ids];
        initialDisciplines.value = [...ids]; // Guardar estado inicial para permitir correcciones
        if (allDisciplines.value.length === 0) {
            await loadDisciplines();
        }
    }
});

const toggleSelection = async (id) => {
    const isSelected = selectedDisciplines.value.includes(id);
    const isInitial = initialDisciplines.value.includes(id);

    if (isSelected) {
        // Remover
        // Si el espacio está activo, solo bloqueamos si la disciplina ya estaba guardada anteriormente.
        // Esto permite corregir selecciones hechas por error en la sesión actual.
        if (props.type === 'space' && isSpaceActive.value && isInitial) {
            toastInfo('Acción bloqueada', 'No se pueden eliminar disciplinas ya asignadas mientras el espacio esté ACTIVO.', 'warning');
            return;
        }

        if (props.type === 'instructor' && isInitial) {
            // Solo llamamos a la API si ya estaba asignada originalmente
            processingDisciplines.value.push(id);
            try {
                const res = await api.delete(`/instructores/${props.item.id_instructor}/disciplinas/${id}`);
                if (res.data.success) {
                    selectedDisciplines.value = selectedDisciplines.value.filter(dId => dId !== id);
                    initialDisciplines.value = initialDisciplines.value.filter(dId => dId !== id);
                    toastSuccess('Disciplina quitada del instructor');
                } else {
                    toastInfo('Bloqueado', res.data.message || 'La disciplina está bloqueada y no se puede remover.', 'error');
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    toastInfo('Bloqueado', error.response.data.message || 'La disciplina está bloqueada por actividades asignadas.', 'error');
                } else {
                    toastInfo('Error', 'No se pudo remover la disciplina.', 'error');
                }
            } finally {
                processingDisciplines.value = processingDisciplines.value.filter(dId => dId !== id);
            }
        } else {
            // Space (o instructor si es una selección nueva de esta sesión)
            selectedDisciplines.value = selectedDisciplines.value.filter(dId => dId !== id);
        }
    } else {
        // Agregar
        selectedDisciplines.value.push(id);
    }
};

const saveChanges = async () => {
    isSaving.value = true;
    try {
        let res;
        if (props.type === 'instructor') {
            res = await instructorStore.updateInstructor(props.item.id_instructor, { disciplinas: selectedDisciplines.value });
        } else {
            res = await spacesStore.updateSpace(props.item.id_espacio, { disciplinas: selectedDisciplines.value });
        }

        if (res.success) {
            toastSuccess('Disciplinas guardadas correctamente');
            emit('saved');
            emit('close');
        } else {
            toastError(res.error || 'No se pudieron guardar las disciplinas');
        }
    } catch (error) {
        console.error("Error saving disciplines:", error);
        toastError('Error de conexión al guardar las disciplinas');
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <AppModal
        :model-value="show"
        title="Gestionar disciplinas"
        :subtitle="headerSubtitle"
        :icon="IconGrid"
        size="lg"
        :persistent="isSaving"
        @update:model-value="(v) => !v && emit('close')"
    >
        <div v-if="isLoading" class="flex flex-col items-center justify-center gap-3 py-10">
            <LoadingSpinner />
            <p class="m-0 text-sm font-semibold text-surface-600">Cargando disciplinas…</p>
        </div>

        <div v-else class="flex flex-col gap-4">
            <div v-if="isSpaceActive" role="note"
                class="flex items-start gap-2.5 rounded-2xl bg-amber-50 p-3.5 text-sm font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                El espacio está activo: puedes agregar disciplinas, pero no quitar las que ya tenía asignadas.
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0 flex-1">
                    <SearchInput v-model="search" placeholder="Buscar disciplina…" />
                </div>
                <span class="shrink-0 text-sm font-semibold text-surface-600">
                    <span class="font-bold tabular-nums text-primary-700 dark:text-primary-300">{{ selectedDisciplines.length }}</span> seleccionadas
                </span>
            </div>

            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                <button v-for="d in visibleDisciplines" :key="d.id_disciplina" type="button" role="checkbox"
                    :aria-checked="selectedDisciplines.includes(d.id_disciplina)"
                    :disabled="processingDisciplines.includes(d.id_disciplina)"
                    class="relative flex cursor-pointer items-center gap-2 rounded-2xl border-2 p-2.5 text-left sm:gap-2.5 sm:p-3 transition-all duration-150 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-100 disabled:cursor-wait"
                    :class="selectedDisciplines.includes(d.id_disciplina)
                        ? 'border-primary-500 bg-primary-50/70 ring-4 ring-primary-100 dark:bg-primary-500/10 dark:ring-primary-500/15'
                        : 'border-surface-200 bg-white hover:border-surface-300 hover:bg-surface-50'"
                    @click="toggleSelection(d.id_disciplina)">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-colors"
                        :class="selectedDisciplines.includes(d.id_disciplina)
                            ? 'bg-gradient-to-br from-primary-500 to-primary-700 text-white'
                            : 'bg-surface-100 text-surface-600'">
                        <LoadingSpinner v-if="processingDisciplines.includes(d.id_disciplina)" size="sm" class="!mx-0" />
                        <DisciplineIcon v-else :name="d.nombre_disciplina" :icon="d.icono" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0 flex-1 truncate text-sm font-bold text-surface-900">{{ d.nombre_disciplina }}</span>
                    <!-- En móvil se oculta para dejar espacio al nombre (la selección ya se nota por el borde) -->
                    <span class="hidden h-5 w-5 shrink-0 items-center justify-center rounded-md border-2 transition-colors sm:flex"
                        :class="selectedDisciplines.includes(d.id_disciplina) ? 'border-transparent bg-primary-600 text-white' : 'border-surface-300'"
                        aria-hidden="true">
                        <svg v-if="selectedDisciplines.includes(d.id_disciplina)" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                    </span>
                </button>
            </div>

            <p v-if="!visibleDisciplines.length" class="m-0 py-6 text-center text-sm font-semibold text-surface-500">
                No hay disciplinas que coincidan con “{{ search }}”.
            </p>
        </div>

        <template #footer>
            <AppButton variant="secondary" :disabled="isSaving" @click="emit('close')">Cancelar</AppButton>
            <AppButton :loading="isSaving" :disabled="isLoading" @click="saveChanges">Guardar cambios</AppButton>
        </template>
    </AppModal>
</template>
