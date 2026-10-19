<script setup>
import { ref, watch, computed } from 'vue';
import { useDisciplinesStore } from '@/stores/admin/disciplines';
import { useCategoryStore } from '@/stores/admin/categoryStore';
import { useAlerts } from '@/composables/useAlerts';
import AppModal from '@/components/ui/modal/AppModal.vue';
import AppButton from '@/components/ui/AppButton.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import { IconWarning, IconTrash, IconAlertCircle } from '@/components/icons';
import { useformat } from '@/utils/formatters';

const props = defineProps({
    show: { type: Boolean, required: true },
    category: { type: Object, default: null }
});

const emit = defineEmits(['close', 'deleted']);

const { formatText } = useformat();
const disciplinesStore = useDisciplinesStore();
const categoryStore = useCategoryStore();
const { toastSuccess, toastError } = useAlerts();

const isVerifying = ref(false);
const isDeleting = ref(false);
const verificationResult = ref(null);
const deleteError = ref('');

// Estados: verificando · se puede eliminar · bloqueada por disciplinas · error al verificar
const state = computed(() => {
    if (isVerifying.value) return 'verifying';
    if (!verificationResult.value || typeof verificationResult.value.puede_eliminar !== 'boolean') return 'error';
    return verificationResult.value.puede_eliminar ? 'can-delete' : 'blocked';
});

const nombre = computed(() => formatText(props.category?.nombre));

const verify = async () => {
    verificationResult.value = null;
    deleteError.value = '';
    isVerifying.value = true;
    try {
        verificationResult.value = await disciplinesStore.verifyCategoryDelete(props.category.id_categoria);
    } catch (error) {
        console.error('Error verifying deletion:', error);
        verificationResult.value = { error: 'No se pudo verificar si la categoría puede eliminarse.' };
    } finally {
        isVerifying.value = false;
    }
};

watch(() => props.show, (newVal) => {
    if (newVal && props.category) verify();
});

const confirmDelete = async () => {
    if (!props.category) return;
    isDeleting.value = true;
    deleteError.value = '';
    try {
        const res = await categoryStore.deleteCategory(props.category.id_categoria);
        if (res?.success) {
            toastSuccess(`Categoría ${nombre.value} eliminada`);
            emit('deleted');
            emit('close');
        } else {
            deleteError.value = res?.error || 'No se pudo eliminar la categoría.';
            toastError('No se pudo eliminar la categoría');
        }
    } catch (error) {
        deleteError.value = 'Ocurrió un error al intentar eliminar la categoría.';
        toastError('No se pudo eliminar la categoría');
    } finally {
        isDeleting.value = false;
    }
};
</script>

<template>
    <AppModal
        :model-value="show"
        :title="{ blocked: 'No se puede eliminar', error: 'No pudimos verificar' }[state] || '¿Eliminar categoría?'"
        :subtitle="nombre"
        :icon="state === 'blocked' || state === 'error' ? IconWarning : IconTrash"
        :tone="state === 'blocked' || state === 'error' ? 'amber' : 'rose'"
        size="sm"
        :persistent="isDeleting"
        @update:model-value="(v) => !v && emit('close')"
    >
        <!-- Verificando dependencias -->
        <div v-if="state === 'verifying'" class="flex flex-col items-center gap-3 py-6">
            <LoadingSpinner size="lg" />
            <p class="m-0 text-sm font-semibold text-surface-600">Revisando disciplinas vinculadas…</p>
        </div>

        <!-- Se puede eliminar -->
        <div v-else-if="state === 'can-delete'" class="text-[15px] leading-relaxed text-surface-700">
            La categoría <strong class="text-surface-900">{{ nombre }}</strong> no tiene disciplinas activas vinculadas y
            puede eliminarse.
            <span class="mt-3 block text-xs font-medium text-surface-500">
                Dejará de estar disponible para nuevas disciplinas y quedará como Inactiva en el historial.
            </span>
        </div>

        <!-- Bloqueada por disciplinas activas -->
        <div v-else-if="state === 'blocked'" class="flex flex-col gap-3">
            <p class="m-0 text-[15px] leading-relaxed text-surface-700">
                <strong class="text-surface-900">{{ nombre }}</strong> tiene
                <strong class="text-surface-900">{{ verificationResult.disciplinas_activas }}</strong>
                {{ verificationResult.disciplinas_activas === 1 ? 'disciplina activa vinculada' : 'disciplinas activas vinculadas' }}.
                Desactívalas o cámbialas de categoría antes de eliminarla.
            </p>
            <ul v-if="verificationResult.nombres_disciplinas?.length"
                class="m-0 flex max-h-40 list-none flex-wrap gap-1.5 overflow-y-auto p-0">
                <li v-for="name in verificationResult.nombres_disciplinas" :key="name"
                    class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30">
                    {{ name }}
                </li>
            </ul>
        </div>

        <!-- Error al verificar (no es lo mismo que "bloqueada") -->
        <div v-else class="flex flex-col gap-3">
            <p class="m-0 text-[15px] leading-relaxed text-surface-700">
                No pudimos comprobar si <strong class="text-surface-900">{{ nombre }}</strong> tiene disciplinas vinculadas.
            </p>
            <p class="m-0 flex items-start gap-2 text-sm font-semibold text-amber-800 dark:text-amber-200">
                <IconAlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                {{ verificationResult?.error || 'Intenta de nuevo en unos segundos.' }}
            </p>
        </div>

        <div v-if="deleteError" role="alert"
            class="mt-4 flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
            <IconAlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
            {{ deleteError }}
        </div>

        <template #footer>
            <AppButton variant="secondary" :disabled="isDeleting" @click="emit('close')">
                {{ state === 'can-delete' ? 'Cancelar' : 'Cerrar' }}
            </AppButton>
            <AppButton v-if="state === 'can-delete'" tone="rose" :loading="isDeleting" @click="confirmDelete">Sí, eliminar</AppButton>
            <AppButton v-else-if="state === 'error'" variant="soft" @click="verify">Reintentar</AppButton>
        </template>
    </AppModal>
</template>
