<script setup>
import { ref, watch } from 'vue';
import { useDisciplinesStore } from '@/stores/admin/disciplines';
import { useCategoryStore } from '@/stores/admin/categoryStore';
import { useAlerts } from '@/composables/useAlerts';
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue';
import CancelButton from '@/components/gerente/ui/CancelButton.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import { IconWarning, IconTrash, IconAlertCircle } from '@/components/icons';
import { useformat } from '@/utils/formatters';

const props = defineProps({
    show: { type: Boolean, required: true },
    category: { type: Object, default: null }
});

const { formatText } = useformat();

const emit = defineEmits(['close', 'deleted']);

const disciplinesStore = useDisciplinesStore();
const categoryStore = useCategoryStore();
const { toastInfo } = useAlerts();

const isVerifying = ref(false);
const isDeleting = ref(false);
const verificationResult = ref(null);

watch(() => props.show, async (newVal) => {
    if (newVal && props.category) {
        verificationResult.value = null;
        isVerifying.value = true;
        try {
            verificationResult.value = await disciplinesStore.verifyCategoryDelete(props.category.id_categoria);
        } catch (error) {
            console.error("Error verifying deletion:", error);
            toastInfo('Error', 'No se pudo verificar el estado de la categoría.', 'error');
        } finally {
            isVerifying.value = false;
        }
    }
});

const confirmDelete = async () => {
    if (!props.category) return;
    isDeleting.value = true;
    try {
        const res = await categoryStore.deleteCategory(props.category.id_categoria);
        if (res.success) {
            toastInfo('Éxito', 'Categoría eliminada correctamente.', 'success');
            emit('deleted');
            emit('close');
        } else {
            toastInfo('Error', res.error, 'error');
        }
    } catch (error) {
        toastInfo('Error', 'Ocurrió un error al intentar eliminar la categoría.', 'error');
    } finally {
        isDeleting.value = false;
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                @click.self="emit('close')">
                <Transition enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0">
                    <div v-if="show" class="bg-white w-full max-w-md rounded-4xl shadow-2xl overflow-hidden border-t-4"
                        :class="verificationResult?.puede_eliminar ? 'border-t-blue-500' : 'border-t-red-500'">

                        <div class="p-8 text-center">
                            <!-- ICONO ESTADO -->
                            <div v-if="isVerifying"
                                class="w-16 h-16 rounded-3xl bg-slate-50 flex items-center justify-center mx-auto mb-5">
                                <LoadingSpinner size="lg" />
                            </div>
                            <div v-else-if="verificationResult?.puede_eliminar"
                                class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-500 flex items-center justify-center mx-auto mb-5">
                                <IconTrash class="w-8 h-8" />
                            </div>
                            <div v-else
                                class="w-16 h-16 rounded-3xl bg-red-50 text-red-500 flex items-center justify-center mx-auto mb-5">
                                <IconWarning class="w-8 h-8" />
                            </div>

                            <h3 class="text-xl font-black text-slate-900 mb-2">
                                {{ isVerifying ? 'Verificando...' : 'Eliminar Categoría' }}
                            </h3>

                            <div v-if="isVerifying" class="py-4">
                                <p class="text-sm text-slate-500">Consultando dependencias en el sistema...</p>
                            </div>

                            <div v-else-if="verificationResult" class="space-y-4">
                                <!-- CASO A: PUEDE ELIMINAR -->
                                <div v-if="verificationResult.puede_eliminar">
                                    <p class="text-sm text-slate-600 leading-relaxed">
                                        La categoría <span class="font-bold text-slate-900">"{{ category?.nombre
                                        }}"</span> puede eliminarse.
                                        No tiene disciplinas activas vinculadas.
                                    </p>
                                    <p class="text-xs text-slate-400 mt-4">Esta acción no se puede deshacer.</p>
                                </div>

                                <!-- CASO B: BLOQUEADO -->
                                <div v-else class="space-y-3">
                                    <p class="text-sm text-slate-600 leading-relaxed">
                                        No es posible eliminar la categoría <span class="font-bold text-slate-900">"{{
                                            formatText(category?.nombre) }}"</span>.
                                    </p>
                                    <div
                                        class="p-4 bg-red-50 rounded-2xl border border-red-100 flex items-start gap-3 text-left">
                                        <IconAlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
                                        <div>
                                            <p class="text-xs font-bold text-red-700 uppercase tracking-tight">
                                                Restricción de seguridad</p>
                                            <p class="text-[13px] text-red-600 font-medium mt-1">
                                                Tiene <span class="font-black">{{ verificationResult.disciplinas_activas
                                                }}</span> disciplinas activas vinculadas.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Lista de disciplinas (opcional, si el backend las manda) -->
                                    <div v-if="verificationResult.nombres_disciplinas?.length"
                                        class="max-h-32 overflow-y-auto px-2 mt-2">
                                        <ul class="text-left space-y-1">
                                            <li v-for="name in verificationResult.nombres_disciplinas" :key="name"
                                                class="text-[11px] font-bold text-slate-400 flex items-center gap-2">
                                                <div class="w-1 h-1 rounded-full bg-slate-300" />
                                                {{ name }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ACCIONES -->
                        <div class="p-7 pt-0 flex gap-3">
                            <CancelButton @click="emit('close')" class="flex-1"
                                :label="verificationResult?.puede_eliminar ? 'Cancelar' : 'Cerrar'" />

                            <ConfirmButton v-if="verificationResult?.puede_eliminar" label="Confirmar Eliminación"
                                :loading="isDeleting" @click="confirmDelete"
                                class="flex-1 bg-red-600! hover:bg-red-700! shadow-red-200" />

                            <!-- Tooltip/Mensaje si está deshabilitado -->
                            <div v-else-if="verificationResult && !verificationResult.puede_eliminar"
                                class="flex-1 group relative">
                                <ConfirmButton label="Eliminar" disabled class="w-full" />
                                <div
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-slate-900 text-white text-[10px] font-bold rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap shadow-xl">
                                    Deshabilitado por disciplinas activas
                                    <div
                                        class="absolute top-full left-1/2 -translate-x-1/2 border-8 border-transparent border-t-slate-900" />
                                </div>
                            </div>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
