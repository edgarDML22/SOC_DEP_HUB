<script setup>
import { ref, watch, computed } from 'vue';
import { useInstructorStore } from '@/stores/admin/instructorStore';
import { useToast } from 'primevue/usetoast';
import IconHourglass from '@/components/icons/IconHourglass.vue';
import IconLock from '@/components/icons/IconLock.vue';
import IconUser from '@/components/icons/IconUser.vue';
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'

const props = defineProps({
    show: Boolean,
    instructorId: [String, Number]
});

const emit = defineEmits(['close', 'updated']);

const instructorStore = useInstructorStore();
const toast = useToast();

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
                toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el instructor', life: 4000 });
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
            toast.add({ severity: 'info', summary: 'Sin cambios', detail: 'El estatus seleccionado es el mismo actual', life: 3000 });
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
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el impacto', life: 4000 });
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
            toast.add({ severity: 'warn', summary: 'Atención', detail: 'Selecciona un sustituto para todas las actividades', life: 4000 });
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
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Estatus actualizado correctamente', life: 3000 });
            emit('updated');
            emit('close');
        } else {
            toast.add({ severity: 'error', summary: 'Error', detail: res.message, life: 5000 });
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Error crítico al procesar el cambio', life: 5000 });
    } finally {
        isSaving.value = false;
    }
};

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
    <Teleport to="body">
        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
                @click.self="emit('close')">
                <Transition enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0">
                    <div v-if="show" class="bg-white w-full max-w-2xl rounded-4xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
                        
                        <!-- Cabecera -->
                        <div class="flex items-center justify-between px-8 py-6 border-b border-surface-100">
                            <div>
                                <h2 class="text-xl font-black text-surface-900 leading-tight">Cambiar Situación</h2>
                                <p class="text-xs font-bold text-surface-500 mt-1 uppercase tracking-wider" v-if="instructor">
                                    {{ instructor.nombre_completo }}
                                </p>
                            </div>
                            <button @click="emit('close')" class="w-10 h-10 rounded-xl bg-surface-100 hover:bg-surface-200
                                     flex items-center justify-center text-surface-500 transition-colors">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M18 6L6 18M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Cuerpo con pasos -->
                        <div class="flex-1 overflow-y-auto p-8 space-y-6 bg-surface-50/30">
                            
                            <div v-if="isLoading" class="flex flex-col items-center justify-center py-12 gap-4">
                                <div class="w-10 h-10 border-4 border-surface-200 border-t-primary-600 rounded-full animate-spin"></div>
                                <p class="text-sm font-bold text-surface-400 uppercase tracking-widest">Cargando impacto...</p>
                            </div>

                            <div v-else>
                                <!-- INDICADOR DE PASOS -->
                                <div class="flex items-center justify-between mb-10 px-4">
                                    <div class="flex flex-col items-center gap-2 relative z-10">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-black text-sm border-2 transition-all duration-300"
                                            :class="currentStep >= 1 ? 'bg-primary-600 border-primary-600 text-white shadow-lg shadow-primary-200' : 'bg-white border-surface-200 text-surface-400'">
                                            <span v-if="currentStep > 1">✓</span>
                                            <span v-else>1</span>
                                        </div>
                                        <span class="text-[10px] font-black uppercase tracking-widest" :class="currentStep >= 1 ? 'text-primary-700' : 'text-surface-400'">Situación</span>
                                    </div>
                                    <div class="flex-1 h-0.5 mx-4 -mt-6" :class="currentStep > 1 ? 'bg-primary-600' : 'bg-surface-200'"></div>
                                    <div class="flex flex-col items-center gap-2 relative z-10">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-black text-sm border-2 transition-all duration-300"
                                            :class="currentStep >= 2 ? 'bg-primary-600 border-primary-600 text-white shadow-lg shadow-primary-200' : 'bg-white border-surface-200 text-surface-400'">
                                            <span v-if="currentStep > 2">✓</span>
                                            <span v-else>2</span>
                                        </div>
                                        <span class="text-[10px] font-black uppercase tracking-widest" :class="currentStep >= 2 ? 'text-primary-700' : 'text-surface-400'">Clases</span>
                                    </div>
                                    <div class="flex-1 h-0.5 mx-4 -mt-6" :class="currentStep > 2 ? 'bg-primary-600' : 'bg-surface-200'"></div>
                                    <div class="flex flex-col items-center gap-2 relative z-10">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-black text-sm border-2 transition-all duration-300"
                                            :class="currentStep === 3 ? 'bg-primary-600 border-primary-600 text-white shadow-lg shadow-primary-200' : 'bg-white border-surface-200 text-surface-400'">
                                            3
                                        </div>
                                        <span class="text-[10px] font-black uppercase tracking-widest" :class="currentStep === 3 ? 'text-primary-700' : 'text-surface-400'">Revisar</span>
                                    </div>
                                </div>

                                <!-- STEP 1 -->
                                <div v-if="currentStep === 1" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                                    <h3 class="text-lg font-black text-surface-900 mb-2">¿Cuál es la situación actual?</h3>
                                    <p class="text-sm font-medium text-surface-500 mb-6">Selecciona una opción para gestionar sus clases y accesos.</p>
                                    
                                    <div class="space-y-4">
                                        <label class="flex items-center gap-5 p-5 rounded-3xl border-2 cursor-pointer transition-all hover:bg-surface-50 group"
                                            :class="selectedStatus === 'ACTIVO' ? 'border-emerald-500 bg-emerald-50 ring-4 ring-emerald-50' : 'border-surface-200 bg-white'">
                                            <input type="radio" v-model="selectedStatus" value="ACTIVO" class="hidden">
                                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-colors"
                                                :class="selectedStatus === 'ACTIVO' ? 'bg-emerald-600 text-white shadow-lg' : 'bg-emerald-100 text-emerald-600 group-hover:bg-emerald-200'">
                                                <IconUser class="w-7 h-7" />
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="font-black text-surface-900 leading-tight">Trabajando normalmente</h4>
                                                <p class="text-xs font-bold text-surface-500 mt-1">Activo, con acceso total a sus clases y sistema.</p>
                                            </div>
                                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                                                :class="selectedStatus === 'ACTIVO' ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-surface-200 bg-white'">
                                                <div v-if="selectedStatus === 'ACTIVO'" class="w-2 h-2 rounded-full bg-white"></div>
                                            </div>
                                        </label>

                                        <label class="flex items-center gap-5 p-5 rounded-3xl border-2 cursor-pointer transition-all hover:bg-surface-50 group"
                                            :class="selectedStatus === 'BAJA_TEMPORAL' ? 'border-amber-500 bg-amber-50 ring-4 ring-amber-50' : 'border-surface-200 bg-white'">
                                            <input type="radio" v-model="selectedStatus" value="BAJA_TEMPORAL" class="hidden">
                                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-colors"
                                                :class="selectedStatus === 'BAJA_TEMPORAL' ? 'bg-amber-600 text-white shadow-lg' : 'bg-amber-100 text-amber-600 group-hover:bg-amber-200'">
                                                <IconHourglass class="w-7 h-7" />
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="font-black text-surface-900 leading-tight">Baja Temporal</h4>
                                                <p class="text-xs font-bold text-surface-500 mt-1">Incapacidad o permiso. Alguien cubrirá sus clases.</p>
                                            </div>
                                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                                                :class="selectedStatus === 'BAJA_TEMPORAL' ? 'bg-amber-600 border-amber-600 text-white' : 'border-surface-200 bg-white'">
                                                <div v-if="selectedStatus === 'BAJA_TEMPORAL'" class="w-2 h-2 rounded-full bg-white"></div>
                                            </div>
                                        </label>

                                        <label class="flex items-center gap-5 p-5 rounded-3xl border-2 cursor-pointer transition-all hover:bg-surface-50 group"
                                            :class="selectedStatus === 'INACTIVO' ? 'border-red-500 bg-red-50 ring-4 ring-red-50' : 'border-surface-200 bg-white'">
                                            <input type="radio" v-model="selectedStatus" value="INACTIVO" class="hidden">
                                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-colors"
                                                :class="selectedStatus === 'INACTIVO' ? 'bg-red-600 text-white shadow-lg' : 'bg-red-100 text-red-600 group-hover:bg-red-200'">
                                                <IconLock class="w-7 h-7" />
                                            </div>
                                            <div class="flex-1">
                                                <h4 class="font-black text-surface-900 leading-tight">Baja Definitiva</h4>
                                                <p class="text-xs font-bold text-surface-500 mt-1">Fuera del club. Sus clases se reasignarán permanentemente.</p>
                                            </div>
                                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                                                :class="selectedStatus === 'INACTIVO' ? 'bg-red-600 border-red-600 text-white' : 'border-surface-200 bg-white'">
                                                <div v-if="selectedStatus === 'INACTIVO'" class="w-2 h-2 rounded-full bg-white"></div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- STEP 2 -->
                                <div v-if="currentStep === 2" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                                    <h3 class="text-lg font-black text-surface-900 mb-2">Repartir sus clases</h3>
                                    <p class="text-sm font-medium text-surface-500 mb-6">Tiene <strong>{{ activities.length }}</strong> clases. Selecciona un sustituto o suspende la clase.</p>
                                    
                                    <div class="space-y-4">
                                        <div v-for="r in reassignments" :key="r.id_actividad" class="bg-white border border-surface-200 rounded-3xl overflow-hidden shadow-sm">
                                            <div class="bg-surface-50/80 px-6 py-4 flex justify-between items-center border-b border-surface-100">
                                                <div>
                                                    <p class="text-sm font-black text-surface-900">{{ r.nombre }}</p>
                                                    <p class="text-[10px] font-bold text-surface-400 uppercase tracking-wider">{{ r.horario }}</p>
                                                </div>
                                                <select v-model="r.action" class="text-xs font-bold px-3 py-2 bg-white border border-surface-200 rounded-xl focus:ring-2 focus:ring-primary-500/30 outline-none transition-all">
                                                    <option value="reasignar">Reasignar</option>
                                                    <option value="deshabilitar">Suspender</option>
                                                </select>
                                            </div>
                                            <div v-if="r.action === 'reasignar'" class="p-5">
                                                <div v-if="candidateSubstitutes[r.id_actividad]?.length > 0">
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2 ml-1">Sustituto disponible</label>
                                                    <select v-model="r.substituteId" class="w-full text-sm font-bold p-3 bg-surface-50 border border-surface-100 rounded-2xl focus:ring-2 focus:ring-primary-500/30 outline-none transition-all">
                                                        <option :value="null" disabled>Seleccionar compañero...</option>
                                                        <option v-for="c in candidateSubstitutes[r.id_actividad]" :key="c.id_instructor" :value="c.id_instructor">
                                                            {{ c.nombre_completo }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div v-else class="flex items-center gap-3 p-4 bg-amber-50 border border-amber-100 rounded-2xl text-amber-700 text-xs font-bold leading-tight">
                                                    <span class="text-lg">⚠️</span>
                                                    No hay instructores libres en este horario. Deberás suspender la clase.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- STEP 3 -->
                                <div v-if="currentStep === 3" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                                    <h3 class="text-lg font-black text-surface-900 mb-2">Revisar y Confirmar</h3>
                                    <p class="text-sm font-medium text-surface-500 mb-6">Confirma que todo sea correcto antes de guardar los cambios.</p>
                                    
                                    <div class="bg-surface-50 border border-surface-200 rounded-3xl p-6 space-y-6">
                                        <div class="flex justify-between items-center pb-4 border-b border-surface-200">
                                            <span class="text-sm font-bold text-surface-500">Nueva situación:</span>
                                            <span class="px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest shadow-sm" :class="getStatusColor(selectedStatus)">
                                                {{ selectedStatus === 'ACTIVO' ? 'Trabajando' : selectedStatus === 'BAJA_TEMPORAL' ? 'Ausente temporal' : 'Baja definitiva' }}
                                            </span>
                                        </div>

                                        <div v-if="selectedStatus === 'ACTIVO' && instructor.estatus === 'BAJA_TEMPORAL'" class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl">
                                            <label class="flex items-center gap-4 cursor-pointer">
                                                <input type="checkbox" v-model="recoverOriginals" class="w-6 h-6 accent-emerald-600 rounded-lg">
                                                <span class="text-sm font-black text-emerald-900 leading-tight">Regresar a sus clases originales (Ya volvió)</span>
                                            </label>
                                        </div>

                                        <div v-if="activities.length > 0" class="space-y-3">
                                            <p class="text-[10px] font-black uppercase tracking-widest text-surface-400 ml-1">Resumen de clases</p>
                                            <div class="space-y-2 max-h-40 overflow-y-auto pr-2 custom-scrollbar">
                                                <div v-for="r in reassignments" :key="r.id_actividad" class="flex justify-between items-center text-xs p-3 bg-white border border-surface-100 rounded-xl">
                                                    <span class="font-bold text-surface-700 truncate mr-4">{{ r.nombre }}</span>
                                                    <span class="font-black uppercase tracking-widest px-2 py-0.5 rounded-md" :class="r.action === 'reasignar' ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600'">
                                                        {{ r.action === 'reasignar' ? 'Reasignado' : 'Suspendido' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="selectedStatus === 'INACTIVO'" class="mt-6 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-start gap-3">
                                        <span class="text-lg">🚨</span>
                                        <p class="text-xs font-bold text-red-700">Aviso: Al dar de baja definitiva, el acceso al sistema se revoca de inmediato.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pie del modal -->
                        <div class="px-8 py-5 border-t border-surface-100 flex items-center justify-between gap-4 bg-white">
                            <CancelButton v-if="currentStep > 1" label="Anterior" @click="prevStep" />
                            <div v-else></div>
                            
                            <div class="flex gap-3">
                                <CancelButton label="Cancelar" @click="emit('close')" v-if="currentStep === 1" />
                                <ConfirmButton v-if="currentStep < 3" label="Siguiente" @click="nextStep" :loading="isLoading" />
                                <ConfirmButton v-else label="Guardar Cambios" :loading="isSaving" @click="saveChanges" />
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.animate-in {
    animation-duration: 0.3s;
    animation-fill-mode: both;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: var(--p-surface-200);
    border-radius: 10px;
}
</style>
