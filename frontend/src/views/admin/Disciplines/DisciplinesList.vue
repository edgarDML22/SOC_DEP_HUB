<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useDisciplinesStore } from '@/stores/admin/disciplines';

const router = useRouter();
const disciplinesStore = useDisciplinesStore();
const { disciplines, isLoading } = storeToRefs(disciplinesStore);

const search = ref('');
const filterStatus = ref('TODOS');
const filterCategory = ref('TODOS');
const showNewModal = ref(false);
const showDeleteModal = ref(false);
const selectedDiscipline = ref(null);
const isSaving = ref(false);

// Instructors Modal State
const showInstructorsModal = ref(false);
const isFetchingInstructors = ref(false);
const selectedInstructors = ref([]);

const newDiscipline = ref({
    nombre_disciplina: '',
    id_categoria: null,
    descripcion: '',
    estatus: 'ACTIVO'
});

onMounted(() => {
    disciplinesStore.fetchDisciplines();
    disciplinesStore.fetchCategories();
});

const categories = computed(() => {
    // Filtrar 'INFANTIL' según requerimiento
    return (disciplinesStore.categories || []).filter(c => c.nombre_categoria !== 'INFANTIL');
});

const filteredDisciplines = computed(() => {
    let result = [...disciplines.value];

    // Búsqueda
    if (search.value) {
        const q = search.value.toLowerCase();
        result = result.filter(d => {
            const catName = d.categoria?.nombre_categoria || d.categoria_disciplina || '';
            return d.nombre_disciplina.toLowerCase().includes(q) ||
                   catName.toLowerCase().includes(q);
        });
    }

    // Filtro Estatus
    if (filterStatus.value !== 'TODOS') {
        result = result.filter(d => d.estatus === filterStatus.value);
    }

    // Filtro Categoría
    if (filterCategory.value !== 'TODOS') {
        result = result.filter(d => d.id_categoria === filterCategory.value);
    }

    // Ordenar: primero por categoría, luego por nombre
    result.sort((a, b) => {
        const catA = a.categoria?.nombre_categoria || a.categoria_disciplina || '';
        const catB = b.categoria?.nombre_categoria || b.categoria_disciplina || '';
        const catCompare = catA.localeCompare(catB);
        if (catCompare !== 0) return catCompare;
        return a.nombre_disciplina.localeCompare(b.nombre_disciplina);
    });

    return result;
});

const openDetails = (id) => {
    router.push({ name: 'disciplines-details', params: { id } });
};

const openEdit = (id) => {
    router.push({ name: 'disciplines-details', params: { id }, query: { edit: 'true' } });
};

const openInstructors = async (id) => {
    isFetchingInstructors.value = true;
    showInstructorsModal.value = true;
    try {
        const fullData = await disciplinesStore.fetchDisciplineDetails(id);
        selectedInstructors.value = fullData.instructores || [];
    } catch (error) {
        console.error("Error al cargar instructores:", error);
    } finally {
        isFetchingInstructors.value = false;
    }
};

const openCategories = () => {
    router.push({ name: 'disciplines-categories' });
};

const openDeleteModal = (discipline) => {
    selectedDiscipline.value = discipline;
    showDeleteModal.value = true;
};

const confirmDelete = async () => {
    if (!selectedDiscipline.value) return;
    isSaving.value = true;
    const res = await disciplinesStore.deleteDiscipline(selectedDiscipline.value.id_disciplina);
    isSaving.value = false;
    if (res.success) {
        showDeleteModal.value = false;
    } else {
        alert(res.error);
    }
};

const saveNewDiscipline = async () => {
    if (!newDiscipline.value.nombre_disciplina || !newDiscipline.value.id_categoria) {
        alert("Nombre y categoría son obligatorios.");
        return;
    }
    isSaving.value = true;
    const res = await disciplinesStore.createDiscipline(newDiscipline.value);
    isSaving.value = false;
    if (res.success) {
        showNewModal.value = false;
        newDiscipline.value = { nombre_disciplina: '', id_categoria: null, descripcion: '', estatus: 'ACTIVO' };
    } else {
        alert(res.error);
    }
};
</script>

<template>
    <div class="admin-container p-6 bg-gray-50 min-h-screen">
        <!-- Header -->
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900">Disciplinas</h1>
                <p class="text-gray-500">Gestiona los deportes y actividades del club</p>
            </div>
            <div class="flex gap-3">
                <button @click="openCategories"
                    class="flex items-center gap-2 bg-white hover:bg-gray-50 text-indigo-600 px-5 py-2.5 rounded-xl font-bold transition-all border border-indigo-100 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01" /></svg>
                    Categorías
                </button>
                <button @click="showNewModal = true"
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-indigo-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nueva Disciplina
                </button>
            </div>
        </header>

        <!-- Filters -->
        <div
            class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-8 flex flex-col md:flex-row gap-4 items-center">
            <div class="relative flex-1 w-full">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input v-model="search" type="text" placeholder="Buscar por nombre o categoría..."
                    class="w-full pl-12 pr-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all text-gray-700">
            </div>
            <div class="w-full md:w-64">
                <select v-model="filterCategory"
                    class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold text-gray-700">
                    <option value="TODOS">Todas las categorías</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nombre_categoria }}</option>
                </select>
            </div>
            <div class="w-full md:w-48">
                <select v-model="filterStatus"
                    class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold text-gray-700">
                    <option value="TODOS">Todos los estados</option>
                    <option value="ACTIVO">Activo</option>
                    <option value="INACTIVO">Inactivo</option>
                    <option value="MANTENIMIENTO">Mantenimiento</option>
                    <option value="DESHABILITADO">Deshabilitado</option>
                </select>
            </div>
        </div>

        <!-- List -->
        <div v-if="isLoading" class="flex justify-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="discipline in filteredDisciplines" :key="discipline.id_disciplina"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all p-6 flex flex-col group">
                <div class="flex justify-between items-start mb-4">
                    <div
                        class="p-3 bg-indigo-50 text-indigo-600 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8h1a4 4 0 0 1 0 8h-1" />
                            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z" />
                            <line x1="6" x2="6" y1="1" y2="4" />
                            <line x1="10" x2="10" y1="1" y2="4" />
                            <line x1="14" x2="14" y1="1" y2="4" />
                        </svg>
                    </div>
                    <span :class="{
                        'bg-emerald-50 text-emerald-600': discipline.estatus === 'ACTIVO',
                        'bg-amber-50 text-amber-600': discipline.estatus === 'MANTENIMIENTO',
                        'bg-red-50 text-red-600': discipline.estatus === 'DESHABILITADO',
                        'bg-gray-50 text-gray-500': discipline.estatus === 'INACTIVO'
                    }" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                        {{ discipline.estatus }}
                    </span>
                </div>

                <h3 class="text-xl font-bold text-gray-900 mb-1">{{ discipline.nombre_disciplina }}</h3>
                <p class="text-indigo-600 font-semibold text-sm mb-3 uppercase tracking-wide">
                    {{ discipline.categoria?.nombre_categoria || discipline.categoria_disciplina }}
                </p>
                <p class="text-gray-500 text-sm line-clamp-2 mb-6 flex-grow">
                    {{ discipline.descripcion || 'Sin descripción disponible.' }}
                </p>

                <div class="pt-4 border-t border-gray-50 flex items-center gap-2">
                    <!-- 1. Ver Detalles -->
                    <button @click="openDetails(discipline.id_disciplina)"
                        class="w-9 h-9 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                        title="Ver Detalles">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <path d="M14 2v6h6" />
                            <path d="M16 13H8" />
                            <path d="M16 17H8" />
                            <path d="M10 9H8" />
                        </svg>
                    </button>

                    <!-- 2. Editar -->
                    <button @click="openEdit(discipline.id_disciplina)"
                        class="w-9 h-9 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm"
                        title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                            <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                        </svg>
                    </button>

                    <!-- 3. Instructores -->
                    <button @click="openInstructors(discipline.id_disciplina)"
                        class="w-9 h-9 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center hover:bg-amber-600 hover:text-white transition-all shadow-sm"
                        title="Instructores">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </button>

                    <!-- 4. Deshabilitar -->
                    <button @click="openDeleteModal(discipline)"
                        class="w-9 h-9 bg-red-50 text-red-600 rounded-xl flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-sm"
                        title="Deshabilitar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!isLoading && filteredDisciplines.length === 0" class="text-center py-20">
            <p class="text-gray-400 font-medium">No se encontraron disciplinas que coincidan con tu búsqueda.</p>
        </div>

        <!-- New Discipline Modal -->
        <div v-if="showNewModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div
                class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Nueva Disciplina</h2>
                        <button @click="showNewModal = false" class="text-gray-400 hover:text-gray-600 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nombre de la Disciplina</label>
                            <input v-model="newDiscipline.nombre_disciplina" type="text"
                                placeholder="Ej. Tennis, Natación..."
                                class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Categoría</label>
                            <select v-model="newDiscipline.id_categoria"
                                class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-medium">
                                <option :value="null" disabled>Selecciona una categoría</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nombre_categoria }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                            <textarea v-model="newDiscipline.descripcion" rows="3"
                                placeholder="Breve descripción de la actividad..."
                                class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3">
                        <button @click="showNewModal = false"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-bold transition-all">
                            Cancelar
                        </button>
                        <button @click="saveNewDiscipline" :disabled="isSaving"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 disabled:opacity-50">
                            {{ isSaving ? 'Guardando...' : 'Crear Disciplina' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deactivation Modal -->
    <div v-if="showDeleteModal"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white rounded-[32px] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-300">
            <div class="p-8 text-center">
                <div
                    class="w-20 h-20 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">¿Deshabilitar Disciplina?</h3>
                <p class="text-gray-500 mb-8" v-if="selectedDiscipline">¿Estás seguro de deshabilitar <b>{{
                    selectedDiscipline.nombre_disciplina }}</b>? El sistema validará que no haya sesiones o torneos
                    activos.</p>

                <div class="flex gap-3">
                    <button @click="showDeleteModal = false"
                        class="flex-1 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl font-bold transition-all">
                        Cancelar
                    </button>
                    <button @click="confirmDelete" :disabled="isSaving"
                        class="flex-1 py-4 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-bold transition-all shadow-lg shadow-red-100 disabled:opacity-50">
                        {{ isSaving ? 'Procesando...' : 'Deshabilitar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructors Modal -->
    <div v-if="showInstructorsModal"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white rounded-[32px] w-full max-w-lg shadow-2xl overflow-hidden animate-in zoom-in duration-300">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Instructores</h2>
                    <button @click="showInstructorsModal = false" class="text-gray-400 hover:text-gray-600 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="isFetchingInstructors" class="flex justify-center py-12">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
                </div>

                <div v-else-if="selectedInstructors.length > 0" class="space-y-4 max-h-[400px] overflow-y-auto pr-2">
                    <div v-for="ins in selectedInstructors" :key="ins.id_instructor"
                        class="flex items-center gap-4 p-4 bg-gray-50 rounded-2xl border border-gray-100 hover:bg-white hover:border-indigo-100 transition-all group">
                        <div class="w-12 h-12 bg-indigo-600 text-white rounded-xl flex items-center justify-center font-bold text-lg shadow-sm group-hover:scale-105 transition-all">
                            {{ ins.nombre_completo.charAt(0) }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">{{ ins.nombre_completo }}</p>
                            <p class="text-xs text-gray-500 font-medium">ID: #{{ ins.id_instructor }}</p>
                        </div>
                        <div class="ml-auto">
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-12 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                    <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                    <p class="text-gray-500 font-medium italic">No hay instructores asignados a esta disciplina.</p>
                </div>

                <button @click="showInstructorsModal = false"
                    class="w-full mt-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold transition-all shadow-lg shadow-indigo-100">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
