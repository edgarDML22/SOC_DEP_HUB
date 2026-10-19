<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useSpacesStore } from '@/stores/admin/spaces';
import { useDisciplinesStore } from '@/stores/admin/disciplines';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import AppModal from '@/components/ui/modal/AppModal.vue';
import AppButton from '@/components/ui/AppButton.vue';
import SpaceFormFields from '@/components/admin/spaces/SpaceFormFields.vue';
import { spaceToForm, validateSpaceForm } from '@/components/admin/spaces/spaceForm';
import { IconEdit, IconWarning } from '@/components/icons';
import { useAlerts } from '@/composables/useAlerts';

// Iconos de Deportes
import IconFutbol from '@/components/icons/disciplines/IconFutbol.vue';
import IconBasquetbol from '@/components/icons/disciplines/IconBasquetbol.vue';
import IconTenis from '@/components/icons/disciplines/IconTenis.vue';
import IconVoleibol from '@/components/icons/disciplines/IconVoleibol.vue';
import IconSquash from '@/components/icons/disciplines/IconSquash.vue';
import IconFrontenis from '@/components/icons/disciplines/IconFrontenis.vue';
import IconPadel from '@/components/icons/disciplines/IconPadel.vue';
import IconDefault from '@/components/icons/disciplines/IconDefault.vue';

const route = useRoute();
const router = useRouter();
const spacesStore = useSpacesStore();
const disciplinesStore = useDisciplinesStore();
const { disciplines } = storeToRefs(disciplinesStore);

const space = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const isEditing = ref(false);
const editErrors = ref({});
const { toastSuccess, toastError } = useAlerts();

const isEditable = computed(() => {
    return space.value?.estatus === 'MANTENIMIENTO' || space.value?.estatus === 'DESHABILITADO';
});

const editForm = ref({
    nombre_espacio: '',
    capacidad_maxima: 0,
    es_reserva_on_demand: false,
    es_clase_programada: false,
    es_uso_libre: false,
    estatus: '',
    descripcion: '',
    disciplinas: []
});

// Al corregir un campo marcado, su error desaparece
watch(editForm, () => {
    const prev = editErrors.value;
    if (!Object.keys(prev).length) return;
    editErrors.value = Object.fromEntries(Object.entries(validateSpaceForm(editForm.value)).filter(([key]) => key in prev));
}, { deep: true });

onMounted(async () => {
    const id = route.params.id;

    // 1. Intentar cargar desde caché inmediatamente
    const cached = spacesStore.spaces.find(s => String(s.id_espacio) === String(id));
    if (cached) {
        space.value = cached;
        isLoading.value = false;
        if (route.query.edit === 'true') isEditing.value = true;
        resetForm();
    } else {
        isLoading.value = true;
    }

    try {
        await disciplinesStore.fetchDisciplines();

        // 2. Traer data fresca en segundo plano (silent fetch)
        const data = await spacesStore.fetchSpaceDetails(id, true, true);
        if (data) {
            space.value = data;
            if (route.query.edit === 'true') {
                isEditing.value = true;
            }
            resetForm();
        }
    } catch (error) {
        console.error("Error background loading space details:", error);
    } finally {
        isLoading.value = false;
    }
});

const resetForm = () => {
    editForm.value = spaceToForm(space.value);
    editErrors.value = {};
};

// "Editar información" abre el modal (antes era un formulario dentro de la página)
const toggleEdit = () => {
    if (!isEditing.value) resetForm();
    isEditing.value = !isEditing.value;
};

const handleUpdate = async () => {
    editErrors.value = validateSpaceForm(editForm.value);
    if (Object.keys(editErrors.value).length) return;
    isSaving.value = true;
    const res = await spacesStore.updateSpace(space.value.id_espacio, editForm.value);
    isSaving.value = false;
    if (res.success) {
        space.value = res.data;
        isEditing.value = false;
        toastSuccess('Información del espacio actualizada');
    } else {
        toastError(res.error || 'No se pudo actualizar el espacio');
    }
};

const goBack = () => router.push({ name: 'spaces-list' });

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
</script>

<template>
    <main class="min-h-screen bg-surface-50 px-4 py-5 pb-20 font-sans sm:p-6 lg:p-8">
        <div class="max-w-5xl mx-auto space-y-6">

            <!-- Navegación Superior -->
            <header class="flex flex-col sm:flex-row sm:items-center gap-3 mb-8">
                <button @click="goBack"
                    class="w-11 h-11 shrink-0 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 hover:text-primary-600 transition-all shadow-sm group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </button>
                <h1 class="text-xl sm:text-2xl font-black text-surface-900 tracking-tight">Detalles del Espacio</h1>
            </header>

            <!-- Loading -->
            <section v-if="isLoading" class="flex flex-col items-center justify-center p-20">
                <LoadingSpinner />
                <p class="text-sm font-extrabold uppercase tracking-widest text-surface-400 mt-4">Cargando detalles...
                </p>
            </section>

            <Transition enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 translate-y-4 scale-[0.98]"
                enter-to-class="opacity-100 translate-y-0 scale-100">
                <div v-if="space && !isLoading" class="space-y-6">

                    <!-- Main Info Card -->
                    <article
                        class="bg-white rounded-[2.5rem] shadow-xl shadow-surface-200/40 border border-surface-200 overflow-hidden relative">
                        <!-- Accent Bar -->
                        <div class="absolute top-0 left-0 right-0 h-2 bg-linear-to-r from-emerald-500 to-emerald-400">
                        </div>

                        <div class="p-5 sm:p-8 lg:p-10">
                            <!-- Cabecera: icono, nombre, estado y botón editar -->
                            <div
                                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 sm:gap-6 mb-8">
                                <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 sm:items-center min-w-0 w-full">
                                    <div
                                        class="p-4 sm:p-5 bg-emerald-50 text-emerald-600 rounded-2xl shadow-sm border border-emerald-100 shrink-0 flex items-center justify-center w-fit">
                                        <component :is="getIcon(space.nombre_espacio)" class="w-8 h-8" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 mb-1">
                                            <h2
                                                class="text-2xl sm:text-3xl font-black text-surface-900 leading-tight wrap-break-word">
                                                {{ space.nombre_espacio }}</h2>
                                            <span :class="{
                                                'bg-green-50 text-green-700 border-green-200': space.estatus === 'ACTIVO',
                                                'bg-amber-50 text-amber-700 border-amber-200': space.estatus === 'MANTENIMIENTO',
                                                'bg-red-50 text-red-700 border-red-200': space.estatus === 'DESHABILITADO',
                                                'bg-surface-50 text-surface-600 border-surface-200': space.estatus === 'INACTIVO'
                                            }"
                                                class="self-start sm:self-auto px-3 py-1 rounded-lg border text-[10px] font-black uppercase tracking-widest shadow-sm">
                                                {{ space.estatus }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <span v-if="space.es_reserva_on_demand"
                                                class="text-[9px] font-bold bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-lg border border-indigo-100 uppercase tracking-widest">ON
                                                DEMAND</span>
                                            <span v-if="space.es_clase_programada"
                                                class="text-[9px] font-bold bg-primary-50 text-primary-600 px-2 py-0.5 rounded-lg border border-primary-100 uppercase tracking-widest">CLASE</span>
                                            <span v-if="space.es_uso_libre"
                                                class="text-[9px] font-bold bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-lg border border-emerald-100 uppercase tracking-widest">USO
                                                LIBRE</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 shrink-0 w-full sm:w-auto">
                                    <AppButton variant="soft" class="w-full sm:w-auto" @click="toggleEdit">
                                        <template #icon><IconEdit class="h-4 w-4" /></template>
                                        Editar información
                                    </AppButton>
                                </div>
                            </div>

                            <!-- Vista de solo lectura -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 border-t border-surface-100 pt-8 mt-8">
                                <div class="space-y-6 lg:col-span-2">
                                    <h3
                                        class="text-sm font-black text-surface-900 uppercase tracking-widest flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-primary-500"></div>
                                        Datos Generales
                                    </h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100">
                                            <span
                                                class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500 block mb-1">Capacidad
                                                Máxima</span>
                                            <p class="text-2xl font-black text-primary-600 leading-none">{{
                                                space.capacidad_maxima }} <span
                                                    class="text-xs font-bold text-surface-400">personas</span></p>
                                        </div>
                                        <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100">
                                            <span
                                                class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500 block mb-2">Configuración
                                                de Uso</span>
                                            <div class="flex flex-col gap-1.5">
                                                <span v-if="space.es_reserva_on_demand"
                                                    class="text-[9px] font-black bg-white text-indigo-600 px-2.5 py-1 rounded-lg border border-indigo-100 shadow-sm w-fit">RESERVA
                                                    ON DEMAND</span>
                                                <span v-if="space.es_clase_programada"
                                                    class="text-[9px] font-black bg-white text-primary-600 px-2.5 py-1 rounded-lg border border-primary-100 shadow-sm w-fit">CLASE
                                                    PROGRAMADA</span>
                                                <span v-if="space.es_uso_libre"
                                                    class="text-[9px] font-black bg-white text-emerald-600 px-2.5 py-1 rounded-lg border border-emerald-100 shadow-sm w-fit">USO
                                                    LIBRE</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="bg-surface-50/50 rounded-2xl p-5 border border-surface-100 flex flex-col gap-2">
                                        <span
                                            class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Descripción</span>
                                        <p class="text-sm font-medium text-surface-700 leading-relaxed">{{
                                            space.descripcion || 'Sin descripción disponible.' }}</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </article>
                </div>
            </Transition>
        </div>

        <!-- MODAL: EDITAR INFORMACIÓN -->
        <AppModal v-model="isEditing" title="Editar información" :subtitle="space?.nombre_espacio" :icon="IconEdit"
            size="lg" :persistent="isSaving">
            <div v-if="!isEditable" role="note"
                class="mb-4 flex items-start gap-2.5 rounded-2xl bg-amber-50 p-3.5 text-sm font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30">
                <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
                Para evitar conflictos con reservas y clases, la información solo puede editarse cuando el espacio está
                en Mantenimiento o Deshabilitado. Cambia su estatus desde la lista de espacios.
            </div>
            <form id="space-edit-form" novalidate @submit.prevent="handleUpdate">
                <SpaceFormFields v-model="editForm" :errors="editErrors" :disabled="!isEditable" />
            </form>
            <template #footer>
                <AppButton variant="secondary" :disabled="isSaving" @click="isEditing = false">
                    {{ isEditable ? 'Cancelar' : 'Cerrar' }}
                </AppButton>
                <AppButton v-if="isEditable" type="submit" form="space-edit-form" :loading="isSaving">Guardar cambios</AppButton>
            </template>
        </AppModal>
    </main>
</template>