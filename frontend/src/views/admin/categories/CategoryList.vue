<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useCategoryStore } from '@/stores/admin/categoryStore';
import { useformat } from '@/utils/formatters';
import { useAlerts } from '@/composables/useAlerts';

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue';
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue';
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue';
import SearchInput from '@/components/gerente/ui/SearchInput.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue';
import CancelButton from '@/components/gerente/ui/CancelButton.vue';
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue';
import FilterContainer from '@/components/gerente/ui/FilterContainer.vue';
import FilterSelect from '@/components/gerente/ui/FilterSelect.vue';
import EliminarCategoriaModal from '@/components/admin/categories/EliminarCategoriaModal.vue';
import { 
    IconAlertCircle, 
    IconChevronDown, 
    IconLayers, 
    IconEdit, 
    IconTrash,
    IconSearch,
    IconFilter
} from '@/components/icons';

const { formatText } = useformat();
const { toastInfo } = useAlerts();
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

const OPT_ESTATUS = [
    { label: 'Todos los estatus', value: null },
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
    showModal.value = true;
};

const openEdit = (cat) => {
    editingCategory.value = cat;
    form.value = {
        nombre: cat.nombre,
        descripcion: cat.descripcion,
        estatus: cat.estatus
    };
    showModal.value = true;
};

const save = async () => {
    if (!form.value.nombre.trim()) {
        toastInfo('Error', 'El nombre es obligatorio.', 'error');
        return;
    }
    isSaving.value = true;
    let res;
    if (editingCategory.value) {
        res = await categoryStore.updateCategory(editingCategory.value.id_categoria, form.value);
    } else {
        res = await categoryStore.createCategory(form.value);
    }
    isSaving.value = false;
    if (res.success) {
        showModal.value = false;
        toastInfo('Éxito', editingCategory.value ? 'Categoría actualizada.' : 'Categoría creada.', 'success');
    } else {
        toastInfo('Error', res.error, 'error');
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
    { separator: true },
    {
        label: 'Eliminar',
        icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                   <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/>
               </svg>`,
        action: () => remove(cat),
        destructive: true,
    }
];
</script>

<template>
    <main class="min-h-screen bg-slate-50 p-6 lg:p-8 pb-16 font-sans">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <AdminPageHeader 
                title="Categorías de Disciplinas" 
                subtitle="Administra las categorías disponibles para clasificar deportes"
                back-route="disciplines-list"
            >
                <span class="text-sm font-bold text-slate-500">
                    {{ filteredCategories.length }}
                    <span class="font-medium text-slate-400">de {{ categories.length }}</span>
                </span>
                <button @click="openCreate"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-surface-900 text-white
                           text-sm font-bold hover:bg-primary-600 transition-colors shadow-sm cursor-pointer border-none">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    Nueva Categoría
                </button>
            </AdminPageHeader>

            <!-- FILTROS -->
            <FilterContainer :hasActiveFilters="hasActiveFilters" @clear="clearFilters">
                <template #search>
                    <SearchInput v-model="search" placeholder="Buscar categoría por nombre o descripción…" />
                </template>

                <FilterSelect
                    label="Estatus"
                    v-model="filterEstatus"
                    :options="OPT_ESTATUS"
                >
                    <template #icon>
                        <IconAlertCircle />
                    </template>
                </FilterSelect>
            </FilterContainer>

            <!-- CARGANDO -->
            <div v-if="isLoading && categories.length === 0" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <TableSkeleton :rows="3" :columns="3" :has-avatar="true" />
            </div>

            <!-- VACÍO -->
            <div v-else-if="filteredCategories.length === 0"
                class="bg-white rounded-2xl border-2 border-dashed border-slate-200 p-16
                       flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01" />
                    </svg>
                </div>
                <h3 class="text-base font-black text-slate-900">Sin resultados</h3>
                <p class="text-sm text-slate-500 mt-1">No se encontraron categorías con los criterios de búsqueda.</p>
            </div>

            <!-- LISTA -->
            <div v-else class="flex flex-col gap-4">
                <div v-for="cat in filteredCategories" :key="cat.id_categoria"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm
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
                                <p class="text-xs text-slate-500 font-medium line-clamp-2 leading-relaxed mt-1.5 max-w-2xl">
                                    {{ cat.descripcion || 'Sin descripción disponible.' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <!-- Botón Editar (Visible en Desktop) -->
                                <button @click="openEdit(cat)"
                                    class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600
                                           text-xs font-bold text-white hover:bg-blue-700
                                           transition-colors shadow-sm">
                                    <IconEdit class="w-3.5 h-3.5" />
                                    Editar
                                </button>
                                <!-- Botón Eliminar (Visible en Desktop) -->
                                <button @click="remove(cat)"
                                    class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-red-50
                                           text-xs font-bold text-red-600 hover:bg-red-600 hover:text-white
                                           transition-all border border-red-100 hover:border-red-600">
                                    <IconTrash class="w-3.5 h-3.5" />
                                    Eliminar
                                </button>
                                <ActionMenu :items="buildMenuItems(cat)" align="right" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
                    enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100" leave-to-class="opacity-0"
                >
                    <div v-if="showModal"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                        @click.self="showModal = false"
                    >
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 scale-95 translate-y-4"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                        >
                            <div v-if="showModal"
                                class="bg-white w-full max-w-lg rounded-4xl shadow-2xl flex flex-col overflow-hidden">
                                
                                <div class="flex items-center justify-between px-7 py-5 border-b border-slate-100">
                                    <div>
                                        <h2 class="text-lg font-black text-slate-900 leading-tight">
                                            {{ editingCategory ? 'Editar Categoría' : 'Nueva Categoría' }}
                                        </h2>
                                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                                            {{ editingCategory ? 'Modifica los datos de la categoría.' : 'Crea una nueva clasificación para disciplinas.' }}
                                        </p>
                                    </div>
                                    <button @click="showModal = false"
                                        class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M18 6L6 18M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="p-7 space-y-6 bg-slate-50/30">
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">
                                            Nombre <span class="text-red-400">*</span>
                                        </label>
                                        <div class="relative">
                                            <IconLayers class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                            <input v-model="form.nombre" placeholder="Ej. Acuático, Combate..."
                                                class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-semibold
                                                       text-slate-900 placeholder:text-slate-400
                                                       focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all shadow-sm"/>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Estatus</label>
                                        <div class="relative">
                                            <IconAlertCircle class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                            <select v-model="form.estatus"
                                                class="w-full pl-11 pr-8 py-3 bg-white border border-slate-200 rounded-xl text-sm font-semibold
                                                       text-slate-900 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all shadow-sm">
                                                <option value="ACTIVO">Activo</option>
                                                <option value="INACTIVO">Inactivo</option>
                                            </select>
                                            <IconChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                                        </div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Descripción</label>
                                        <textarea v-model="form.descripcion" rows="4" placeholder="Breve descripción de la categoría..."
                                            class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-semibold
                                                   text-slate-900 placeholder:text-slate-400 resize-none shadow-sm
                                                   focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3 px-7 py-4 border-t border-slate-100">
                                    <CancelButton @click="showModal = false" />
                                    <ConfirmButton
                                        :label="editingCategory ? 'Actualizar' : 'Crear'"
                                        :loading="isSaving"
                                        @click="save"
                                    />
                                </div>
                            </div>
                        </Transition>
                    </div>
                </Transition>
            </Teleport>

            <!-- MODAL ELIMINAR -->
            <EliminarCategoriaModal 
                :show="showDeleteModal"
                :category="categoryToDelete"
                @close="showDeleteModal = false"
                @deleted="categoryStore.fetchCategories(true)"
            />

        </div>
    </main>
</template>
