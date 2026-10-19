<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useDisciplinesStore } from '@/stores/admin/disciplines';
import { useCategoryStore } from '@/stores/admin/categoryStore';
import { useformat } from '@/utils/formatters';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import AppModal from '@/components/ui/modal/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppField from '@/components/ui/form/AppField.vue'
import AppInput from '@/components/ui/form/AppInput.vue'
import AppSelect from '@/components/ui/form/AppSelect.vue'
import { useAlerts } from '@/composables/useAlerts'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import * as Icons from '@/components/icons'

const getDisciplineIcon = (name) => {
    const n = (name || '').toLowerCase()
    if (n.includes('basquetbol') || n.includes('baloncesto') || n.includes('basketball')) return Icons.IconBasquetbol
    if (n.includes('frontenis')) return Icons.IconFrontenis
    if (n.includes('futbol') || n.includes('fútbol') || n.includes('soccer')) return Icons.IconFutbol
    if (n.includes('padel') || n.includes('pádel')) return Icons.IconPadel
    if (n.includes('squash')) return Icons.IconSquash
    if (n.includes('tenis') || n.includes('tennis')) return Icons.IconTenis
    if (n.includes('voleibol') || n.includes('volleyball')) return Icons.IconVoleibol

    if (n.includes('aerobics')) return Icons.IconAerobic
    if (n.includes('jazz')) return Icons.IconJazz
    if (n.includes('zumba')) return Icons.IconZumba
    if (n.includes('baile')) return Icons.IconDance

    if (n.includes('meditación') || n.includes('meditacion')) return Icons.IconMeditation
    if (n.includes('pilates')) return Icons.IconPilates
    if (n.includes('barre')) return Icons.IconBarre
    if (n.includes('yoga')) return Icons.IconYoga

    if (n.includes('columna')) return Icons.IconHigieneColumna
    if (n.includes('acondicionamiento') || n.includes('entrenamiento')) return Icons.IconAerobic
    if (n.includes('gym')) return Icons.IconGym
    if (n.includes('tae kwon do') || n.includes('artes marciales')) return Icons.IconMartialArts
    if (n.includes('spinning') || n.includes('bici')) return Icons.IconSpinning
    if (n.includes('gimnasia')) return Icons.IconGymnastics
    if (n.includes('natación') || n.includes('natacion') || n.includes('acuatico') || n.includes('acuático')) return Icons.IconSwimming
    if (n.includes('ludoteca')) return Icons.IconBaby

    return Icons.IconDefault
}

const route = useRoute();
const router = useRouter();
const disciplinesStore = useDisciplinesStore();
const categoryStore = useCategoryStore();
const { formatText, formatCategoryEnum } = useformat();
const { toastSuccess } = useAlerts();

const { categories, isLoading: categoriesLoading } = storeToRefs(categoryStore);
const { isLoading: disciplinesLoading } = storeToRefs(disciplinesStore);

const discipline = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const isEditing = ref(false);
const formError = ref('');
const submitted = ref(false);
const editForm = ref({
    nombre_disciplina: '',
    categorias_ids: []
});

const disciplineId = computed(() => route.params.id);

onMounted(async () => {
    const id = disciplineId.value;
    if (!id || id === 'undefined') {
        isLoading.value = false;
        return;
    }

    // 1. Intentar cargar desde caché inmediatamente
    const cached = disciplinesStore.getDisciplineById(id);
    if (cached) {
        discipline.value = cached;
        isLoading.value = false;
        resetForm();
        if (route.query.edit === 'true') isEditing.value = true;
    } else {
        isLoading.value = true;
    }
    
    try {
        await categoryStore.fetchCategories();
        
        // 2. Traer data fresca en segundo plano (silent fetch)
        const data = await disciplinesStore.fetchDisciplineDetails(id, true, true);
        if (data) {
            discipline.value = data;
            if (route.query.edit === 'true') {
                isEditing.value = true;
            }
            resetForm();
        }
    } catch (error) {
        console.error("Error background loading discipline details:", error);
    } finally {
        isLoading.value = false;
    }
});

// La API devuelve categorias como array (many-to-many)
const disciplineCategory = computed(() => discipline.value?.categorias?.[0] ?? null)

const resetForm = () => {
    editForm.value = {
        categorias_ids: discipline.value.categorias?.map(c => c.id_categoria) ?? [],
        nombre_disciplina: discipline.value.nombre_disciplina
    };
};

const categoryOpts = computed(() => categories.value.map(c => ({ label: formatCategoryEnum(c.nombre), value: c.id_categoria })));

const openEdit = () => {
    resetForm();
    formError.value = '';
    submitted.value = false;
    isEditing.value = true;
};

const closeEdit = () => {
    isEditing.value = false;
};

const handleUpdate = async () => {
    submitted.value = true;
    formError.value = '';
    if (!editForm.value.nombre_disciplina.trim()) {
        formError.value = 'El nombre de la disciplina es requerido.';
        return;
    }
    isSaving.value = true;
    const res = await disciplinesStore.updateDiscipline(discipline.value.id_disciplina, editForm.value);
    isSaving.value = false;
    if (res.success) {
        discipline.value = res.data;
        isEditing.value = false;
        toastSuccess(`Disciplina ${res.data?.nombre_disciplina ?? ''} actualizada`.trim());
    } else {
        formError.value = res.error || 'No se pudo actualizar la disciplina.';
    }
};

// Removed delete modal logic

const goBack = () => router.push({ name: 'disciplines-list' });
</script>

<template>
    <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-20 font-sans">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Navegación Superior -->
            <header class="flex items-center gap-4 mb-8">
                <button @click="goBack"
                    class="w-11 h-11 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 hover:text-primary-600 transition-all shadow-sm group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </button>
                <h1 class="text-2xl font-black text-surface-900 tracking-tight">Detalles de Disciplina</h1>
            </header>

            <!-- Loading -->
            <section v-if="isLoading" class="flex flex-col items-center justify-center p-20">
                <LoadingSpinner />
                <p class="text-sm font-extrabold uppercase tracking-widest text-surface-400 mt-4">Cargando detalles...</p>
            </section>

            <Transition enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 translate-y-4 scale-[0.98]"
                enter-to-class="opacity-100 translate-y-0 scale-100">
                <div v-if="discipline && !isLoading" class="space-y-6">

                    <!-- Main Info Card -->
                    <article
                        class="bg-white rounded-[2.5rem] shadow-xl shadow-surface-200/40 border border-surface-200 overflow-hidden relative">
                        <!-- Accent Bar -->
                        <div class="absolute top-0 left-0 right-0 h-2 bg-linear-to-r from-primary-600 to-primary-400">
                        </div>

                        <div class="p-8 lg:p-10">
                            <div
                                class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
                                <div class="flex gap-6 items-center">
                                    <div
                                        class="p-5 bg-primary-50 text-primary-600 rounded-2xl shadow-sm border border-primary-100 shrink-0">
                                        <component :is="getDisciplineIcon(discipline.nombre_disciplina)" class="w-8 h-8 fill-current" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-3 mb-1">
                                            <h2 class="text-3xl font-black text-surface-900 leading-tight">{{
                                                discipline.nombre_disciplina }}</h2>
                                            <BadgeStatus :status="discipline.estatus" size="md" />
                                        </div>

                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2 shrink-0">
                                    <AppButton variant="soft" class="w-full md:w-auto" @click="openEdit">
                                        <template #icon><Icons.IconEdit class="h-4 w-4" /></template>
                                        Editar información
                                    </AppButton>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-8 border-t border-surface-100 pt-8 mt-8">
                                <div class="space-y-6">

                                    <div
                                        class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-widest text-surface-400">Categoría</span>
                                        <span class="text-lg font-black text-primary-700">
                                            {{ formatCategoryEnum(disciplineCategory?.nombre || discipline.categoria_disciplina) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </article>

                </div>
            </Transition>
        </div>
        <!-- MODAL: EDITAR INFORMACIÓN -->
        <AppModal
            v-model="isEditing"
            title="Editar disciplina"
            :subtitle="discipline?.nombre_disciplina"
            :icon="Icons.IconEdit"
            :persistent="isSaving"
        >
            <form id="edit-discipline-form" class="flex flex-col gap-4" novalidate @submit.prevent="handleUpdate">
                <div v-if="formError" role="alert"
                    class="flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
                    <Icons.IconAlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                    {{ formError }}
                </div>

                <AppField label="Nombre" for="disc-edit-nombre" required>
                    <AppInput id="disc-edit-nombre" v-model="editForm.nombre_disciplina"
                        :invalid="submitted && !editForm.nombre_disciplina.trim()" />
                </AppField>

                <AppField label="Categoría" :hint="discipline?.estatus === 'ACTIVO' ? '' : undefined">
                    <AppSelect v-model="editForm.categorias_ids[0]" :options="categoryOpts" placeholder="Selecciona una categoría"
                        :disabled="discipline?.estatus === 'ACTIVO'" />
                    <p v-if="discipline?.estatus === 'ACTIVO'"
                        class="m-0 flex items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300">
                        <Icons.IconWarning class="h-3.5 w-3.5 shrink-0" />
                        No se puede cambiar la categoría mientras la disciplina esté activa.
                    </p>
                </AppField>
            </form>

            <template #footer>
                <AppButton variant="secondary" :disabled="isSaving" @click="closeEdit">Cancelar</AppButton>
                <AppButton type="submit" form="edit-discipline-form" :loading="isSaving">Guardar cambios</AppButton>
            </template>
        </AppModal>
    </main>
</template>
