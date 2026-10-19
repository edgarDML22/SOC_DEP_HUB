<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useCategoryStore } from '@/stores/admin/categoryStore';
import { useformat } from '@/utils/formatters';
import { useAlerts } from '@/composables/useAlerts';

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue';
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue';
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue';
import SearchInput from '@/components/gerente/ui/SearchInput.vue';
import AppModal from '@/components/ui/modal/AppModal.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppField from '@/components/ui/form/AppField.vue';
import AppInput from '@/components/ui/form/AppInput.vue';
import AppSelect from '@/components/ui/form/AppSelect.vue';
import AppTextarea from '@/components/ui/form/AppTextarea.vue';
import IconPlus from '@/components/icons/IconPlus.vue';
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue';
import FilterContainer from '@/components/gerente/ui/FilterContainer.vue';
import FilterSelect from '@/components/gerente/ui/FilterSelect.vue';
import EliminarCategoriaModal from '@/components/admin/categories/EliminarCategoriaModal.vue';
import {
    IconAlertCircle,
    IconLayers,
    IconEdit
} from '@/components/icons';

const { formatText } = useformat();
const { toastSuccess, toastError } = useAlerts();
const router = useRouter();
const categoryStore = useCategoryStore();
const { categories, isLoading } = storeToRefs(categoryStore);

const search = ref('');
const filterEstatus = ref(null);
const showModal = ref(false);
const isSaving = ref(false);
const editingCategory = ref(null);
const showDeleteModal = ref(false);
const categoryToDelete = ref(null);

const form = ref({
    nombre: '',
    descripcion: '',
    estatus: 'ACTIVO'
});

const OPT_ESTATUS_FORM = [
    { label: 'Activo', value: 'ACTIVO' },
    { label: 'Inactivo', value: 'INACTIVO' },
];
const formErrors = ref({});

const validateForm = () => {
    const errors = {};
    if (!form.value.nombre?.trim()) errors.nombre = 'Escribe el nombre de la categoría.';
    else if (form.value.nombre.trim().length > 100) errors.nombre = 'Máximo 100 caracteres.';
    formErrors.value = errors;
    return Object.keys(errors).length === 0;
};

// Al corregir un campo marcado, su error desaparece
watch(form, () => {
    if (Object.keys(formErrors.value).length) validateForm();
}, { deep: true });

const OPT_ESTATUS = [
    { label: 'Todos', value: null },
    { label: 'Activo', value: 'ACTIVO' },
    { label: 'Inactivo', value: 'INACTIVO' },
];

const filteredCategories = computed(() => {
    let r = [...categories.value];
    if (search.value) {
        const q = search.value.toLowerCase();
        r = r.filter(c =>
            c.nombre.toLowerCase().includes(q) ||
            (c.descripcion && c.descripcion.toLowerCase().includes(q))
        );
    }
    if (filterEstatus.value) {
        r = r.filter(c => c.estatus === filterEstatus.value);
    }
    return r.sort((a, b) => a.nombre.localeCompare(b.nombre));
});

const hasActiveFilters = computed(() => search.value || filterEstatus.value);

const clearFilters = () => {
    search.value = '';
    filterEstatus.value = null;
};

onMounted(() => {
    categoryStore.fetchCategories();
});

const openCreate = () => {
    editingCategory.value = null;
    form.value = { nombre: '', descripcion: '', estatus: 'ACTIVO' };
    formErrors.value = {};
    showModal.value = true;
};

const openEdit = (cat) => {
    editingCategory.value = cat;
    form.value = {
        nombre: cat.nombre,
        descripcion: cat.descripcion || '',
        estatus: cat.estatus
    };
    formErrors.value = {};
    showModal.value = true;
};

const save = async () => {
    if (!validateForm()) return;
    isSaving.value = true;
    const isEdit = !!editingCategory.value;
    const res = isEdit
        ? await categoryStore.updateCategory(editingCategory.value.id_categoria, form.value)
        : await categoryStore.createCategory(form.value);
    isSaving.value = false;
    if (res?.success) {
        showModal.value = false;
        toastSuccess(isEdit ? 'Categoría actualizada' : `Categoría ${form.value.nombre.trim()} creada`);
    } else {
        toastError(res?.error || 'No se pudo guardar la categoría');
    }
};

const remove = (cat) => {
    categoryToDelete.value = cat;
    showDeleteModal.value = true;
};

const buildMenuItems = (cat) => [
    {
        label: 'Editar',
        icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                   <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                   <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
               </svg>`,
        action: () => openEdit(cat),
    },
    ...(cat.estatus === 'ACTIVO' ? [{ separator: true }, {
        label: 'Eliminar',
        icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                   <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/>
               </svg>`,
        action: () => remove(cat),
        destructive: true,
    }] : [])
];
</script>

<template>
    <main class="min-h-screen bg-surface-50 px-4 py-5 pb-16 font-sans sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto space-y-5 sm:space-y-6">

            <AdminPageHeader title="Categorías de Disciplinas"
                subtitle="Administra las categorías disponibles para clasificar deportes" back-route="disciplines-list" />

            <!-- Acción principal a la izquierda y el conteo justo a su derecha -->
            <div class="flex items-center gap-3 sm:gap-4">
                <AppButton class="shrink-0" @click="openCreate">
                    <template #icon><IconPlus class="h-4 w-4" /></template>
                    Nueva Categoría
                </AppButton>
                <span class="min-w-0 whitespace-nowrap text-sm font-semibold text-surface-600">
                    <span class="font-bold tabular-nums text-surface-900">{{ filteredCategories.length }}</span>
                    de <span class="tabular-nums">{{ categories.length }}</span><span class="hidden sm:inline"> categorías</span>
                </span>
            </div>

            <!-- FILTROS -->
            <FilterContainer :has-active-filters="hasActiveFilters" :active-count="filterEstatus ? 1 : 0" @clear="clearFilters">
                <template #search>
                    <SearchInput v-model="search" placeholder="Buscar categoría por nombre o descripción…" />
                </template>

                <FilterSelect label="Estatus" v-model="filterEstatus" :options="OPT_ESTATUS">
                    <template #icon>
                        <IconAlertCircle />
                    </template>
                </FilterSelect>
            </FilterContainer>

            <!-- CARGANDO -->
            <div v-if="isLoading && categories.length === 0"
                class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <TableSkeleton :rows="3" :columns="3" :has-avatar="true" />
            </div>

            <!-- VACÍO -->
            <div v-else-if="filteredCategories.length === 0" class="bg-white rounded-2xl border-2 border-dashed border-slate-200 p-16
                       flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5">
                        <path
                            d="M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01" />
                    </svg>
                </div>
                <h3 class="text-base font-black text-slate-900">Sin resultados</h3>
                <p class="text-sm text-slate-500 mt-1">No se encontraron categorías con los criterios de búsqueda.</p>
            </div>

            <!-- LISTA -->
            <template v-else>
                <div class="hidden md:flex flex-col gap-4">
                    <div v-for="cat in filteredCategories" :key="cat.id_categoria" class="bg-white rounded-2xl border border-slate-200 shadow-sm
                            hover:shadow-md hover:border-slate-300
                            transition-all duration-200 group overflow-hidden flex">

                        <div class="w-1.5 shrink-0 bg-blue-500" />

                        <div class="flex items-center justify-center px-5 py-4 shrink-0">
                            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center
                                        shadow-sm group-hover:scale-105 transition-transform duration-200">
                                <IconLayers class="w-7 h-7" />
                            </div>
                        </div>

                        <div class="flex-1 min-w-0 py-4 pr-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-black text-slate-900 truncate leading-tight">
                                        {{ formatText(cat.nombre) }}
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                        <BadgeStatus :status="cat.estatus" size="sm" />
                                    </div>
                                    <p
                                        class="text-xs text-slate-500 font-medium line-clamp-2 leading-relaxed mt-1.5 max-w-2xl">
                                        {{ cat.descripcion || 'Sin descripción disponible.' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <ActionMenu :items="buildMenuItems(cat)" align="right" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:hidden flex flex-col gap-3">
                    <div v-for="cat in filteredCategories" :key="cat.id_categoria"
                        class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 space-y-2.5">

                        <!-- Fila 1: icono, nombre y menú de tres puntos -->
                        <div class="flex items-center gap-3">
                            <div class="w-1.5 h-10 shrink-0 rounded-full bg-blue-500" />
                            <div
                                class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-sm shrink-0">
                                <IconLayers class="w-5 h-5" />
                            </div>
                            <p class="flex-1 min-w-0 text-sm font-bold text-slate-900 truncate leading-tight">
                                {{ formatText(cat.nombre) }}
                            </p>
                            <ActionMenu :items="buildMenuItems(cat)" align="right" />
                        </div>

                        <!-- Fila 2: descripción (tal y como está) y disciplinas asociadas -->
                        <div>
                            <p class="text-xs text-slate-500 font-medium line-clamp-3 leading-relaxed">
                                {{ cat.descripcion || 'Sin descripción disponible.' }}
                            </p>
                            <span v-if="cat.disciplinas?.length"
                                class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-lg border uppercase tracking-wide bg-indigo-50 text-indigo-700 border-indigo-100 mt-2">
                                {{ cat.disciplinas.length }} disciplinas
                            </span>
                        </div>

                        <!-- Fila 3: estatus alineado a la derecha sin líneas separadoras -->
                        <div class="flex justify-end pt-1">
                            <BadgeStatus :status="cat.estatus" size="sm" />
                        </div>
                    </div>
                </div>
            </template>

            <!-- MODAL: NUEVA / EDITAR CATEGORÍA -->
            <AppModal v-model="showModal" :title="editingCategory ? 'Editar categoría' : 'Nueva categoría'"
                :subtitle="editingCategory ? formatText(editingCategory.nombre) : 'Crea una nueva clasificación para disciplinas'"
                :icon="editingCategory ? IconEdit : IconLayers" :persistent="isSaving">
                <form id="category-form" class="flex flex-col gap-4" novalidate @submit.prevent="save">
                    <AppField label="Nombre" for="cat-nombre" required :error="formErrors.nombre">
                        <AppInput id="cat-nombre" v-model="form.nombre" :icon="IconLayers" maxlength="100"
                            placeholder="Ej. Acuático, Combate…" autocomplete="off" :invalid="!!formErrors.nombre" />
                    </AppField>
                    <AppField label="Estatus" required>
                        <AppSelect v-model="form.estatus" :options="OPT_ESTATUS_FORM" />
                    </AppField>
                    <AppField label="Descripción" for="cat-descripcion" optional>
                        <AppTextarea id="cat-descripcion" v-model="form.descripcion" rows="4"
                            placeholder="Breve descripción de la categoría…" />
                    </AppField>
                </form>
                <template #footer>
                    <AppButton variant="secondary" :disabled="isSaving" @click="showModal = false">Cancelar</AppButton>
                    <AppButton type="submit" form="category-form" :loading="isSaving">
                        {{ editingCategory ? 'Guardar cambios' : 'Crear categoría' }}
                    </AppButton>
                </template>
            </AppModal>

            <!-- MODAL ELIMINAR -->
            <EliminarCategoriaModal :show="showDeleteModal" :category="categoryToDelete"
                @close="showDeleteModal = false" @deleted="categoryStore.fetchCategories(true)" />

        </div>
    </main>
</template>
