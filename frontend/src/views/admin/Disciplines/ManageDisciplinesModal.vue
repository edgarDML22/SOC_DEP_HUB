<script setup>
import { ref, computed, watch } from 'vue';
import api from '@/services/api';
import { useSpacesStore } from '@/stores/admin/spaces';
import { useInstructorStore } from '@/stores/admin/instructorStore';
import { useDisciplinesStore } from '@/stores/admin/disciplines';
import { useAlerts } from '@/composables/useAlerts';
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue';
import CancelButton from '@/components/gerente/ui/CancelButton.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';

const props = defineProps({
    show: { type: Boolean, required: true },
    type: { type: String, required: true }, // 'instructor' | 'space'
    item: { type: Object, default: () => ({}) }
});

const emit = defineEmits(['close', 'saved']);

const { toastInfo } = useAlerts();
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

const headerTitle = computed(() => {
    if (props.type === 'instructor') {
        return `Gestionar Disciplinas de: ${props.item?.nombre_completo || ''}`;
    }
    return `Gestionar Disciplinas de: ${props.item?.nombre_espacio || ''}`;
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
                    toastInfo('Disciplina Eliminada', 'Se ha quitado la disciplina correctamente.', 'success');
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
            toastInfo('Disciplina Removida', 'Se quitó la disciplina de la selección.', 'info');
        }
    } else {
        // Agregar
        selectedDisciplines.value.push(id);
        toastInfo('Disciplina Agregada', 'Se añadió la disciplina a la selección.', 'success');
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
            toastInfo('Éxito', 'Disciplinas guardadas correctamente', 'success');
            emit('saved');
            emit('close');
        } else {
            toastInfo('Error', res.error || 'No se pudo guardar', 'error');
        }
    } catch (error) {
        console.error("Error saving disciplines:", error);
        toastInfo('Error Crítico', 'Error de conexión', 'error');
    } finally {
        isSaving.value = false;
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show" class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
                @click.self="emit('close')">
                <Transition enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0">
                    <div v-if="show" class="bg-white w-full max-w-4xl rounded-4xl shadow-2xl flex flex-col max-h-[92vh] overflow-hidden">
                        
                        <!-- Cabecera -->
                        <div class="flex items-center justify-between px-8 py-6 bg-white border-b border-surface-100">
                            <div>
                                <h2 class="text-xl font-black text-surface-900 leading-tight">{{ headerTitle }}</h2>
                                <p class="text-xs font-bold text-surface-500 mt-1 uppercase tracking-wider">
                                    Selecciona las disciplinas asociadas
                                </p>
                            </div>
                            <button @click="emit('close')"
                                class="w-10 h-10 rounded-xl bg-surface-100 hover:bg-surface-200 flex items-center justify-center text-surface-500 transition-colors">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M18 6L6 18M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Cuerpo -->
                        <div class="overflow-y-auto p-8 space-y-8 bg-surface-50/30">
                            <div v-if="isLoading" class="flex flex-col items-center justify-center py-10 gap-4">
                                <LoadingSpinner />
                                <p class="text-surface-500 font-medium">Cargando disciplinas...</p>
                            </div>
                            <div v-else>
                                <div v-if="isSpaceActive" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs font-bold flex items-center gap-2">
                                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    El espacio está ACTIVO. No se permite eliminar disciplinas ya asignadas, pero puedes agregar nuevas y corregir tu selección actual.
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                    <div v-for="d in sortedDisciplines" :key="d.id_disciplina"
                                        @click="!processingDisciplines.includes(d.id_disciplina) && toggleSelection(d.id_disciplina)"
                                        class="cursor-pointer group relative transform transition-all"
                                        :class="[processingDisciplines.includes(d.id_disciplina) ? 'opacity-50 pointer-events-none' : 'hover:-translate-y-1']">
                                        <div
                                            class="border rounded-3xl p-6 flex flex-col items-center justify-center gap-4 h-full transition-all duration-300 shadow-sm relative"
                                            :class="selectedDisciplines.includes(d.id_disciplina) 
                                                ? 'bg-primary-600 border-primary-600 shadow-md' 
                                                : 'bg-white border-surface-200 hover:bg-primary-600 hover:border-primary-600 hover:shadow-lg'">
                                            
                                            <!-- Ícono -->
                                            <div
                                                class="w-16 h-16 rounded-2xl flex items-center justify-center transition-all duration-300 shadow-inner"
                                                :class="selectedDisciplines.includes(d.id_disciplina) 
                                                    ? 'bg-white/20 text-white' 
                                                    : 'bg-surface-100 text-surface-400 group-hover:bg-white/20 group-hover:text-white'">
                                                <DisciplineIcon :name="d.nombre_disciplina" :icon="d.icono"
                                                    class="w-10 h-10 transition-all duration-300"
                                                    :class="selectedDisciplines.includes(d.id_disciplina) ? 'opacity-100' : 'opacity-70 group-hover:opacity-100'" />
                                            </div>

                                            <!-- Nombre -->
                                            <span
                                                class="text-sm font-bold text-center leading-tight transition-colors duration-300"
                                                :class="selectedDisciplines.includes(d.id_disciplina) 
                                                    ? 'text-white' 
                                                    : 'text-surface-600 group-hover:text-white'">
                                                {{ d.nombre_disciplina }}
                                            </span>

                                            <!-- Check flotante cuando está seleccionado -->
                                            <div v-if="selectedDisciplines.includes(d.id_disciplina) && !processingDisciplines.includes(d.id_disciplina)"
                                                class="absolute -top-3 -right-3 bg-white text-primary-600 w-8 h-8 rounded-full flex items-center justify-center border-[3px] border-primary-600 shadow-md animate-scale-in">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="4" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12" />
                                                </svg>
                                            </div>

                                            <!-- Spinner flotante cuando está cargando -->
                                            <div v-if="processingDisciplines.includes(d.id_disciplina)"
                                                class="absolute inset-0 bg-white/40 backdrop-blur-[1px] flex items-center justify-center rounded-3xl z-10">
                                                <div class="w-8 h-8 border-4 border-surface-200 border-t-primary-600 rounded-full animate-spin shadow-sm"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pie del modal -->
                        <div class="flex items-center justify-end gap-3 px-8 py-5 border-t border-surface-100 bg-white">
                            <CancelButton @click="emit('close')" />
                            <ConfirmButton label="Guardar Cambios" :loading="isSaving" @click="saveChanges" />
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.animate-scale-in {
    animation: scaleIn 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
