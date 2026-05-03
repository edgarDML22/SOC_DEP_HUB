<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useCategoryStore } from '@/stores/admin/categoryStore';
import { useformat } from '@/utils/formatters';

const { formatText } = useformat();
const router = useRouter();
const categoryStore = useCategoryStore();
const { categories, isLoading } = storeToRefs(categoryStore);

const showModal = ref(false);
const isSaving = ref(false);
const editingCategory = ref(null);

const form = ref({
    nombre: '',
    descripcion: '',
    estatus: 'ACTIVO'
});

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
    if (!form.value.nombre) {
        alert("El nombre es obligatorio.");
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
    } else {
        alert(res.error);
    }
};

const remove = async (id) => {
    if (!confirm("¿Estás seguro de eliminar esta categoría?")) return;
    const res = await categoryStore.deleteCategory(id);
    if (!res.success) {
        alert(res.error);
    }
};

const goBack = () => router.push({ name: 'disciplines-list' });
</script>

<template>
    <div class="admin-container p-6 bg-gray-50 min-h-screen">
        <header class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-4">
                <button @click="goBack"
                    class="p-2 bg-white rounded-xl border border-gray-100 text-gray-500 hover:text-indigo-600 transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900">Categorías de Disciplinas</h1>
                    <p class="text-gray-500">Administra las categorías disponibles para clasificar deportes</p>
                </div>
            </div>
            <button @click="openCreate"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-indigo-200 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Nueva Categoría
            </button>
        </header>

        <div v-if="isLoading && categories.length === 0" class="flex justify-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="cat in categories" :key="cat.id_categoria"
                class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01" />
                        </svg>
                    </div>
                    <div class="flex gap-2">
                        <button @click="openEdit(cat)"
                            class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                        <button @click="remove(cat.id_categoria)"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Eliminar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ formatText(cat.nombre) }}</h3>
                <p class="text-gray-500 text-sm flex-grow">{{ cat.descripcion || 'Sin descripción.' }}</p>
                <div class="mt-4">
                    <span :class="cat.estatus === 'ACTIVO' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'"
                        class="px-2 py-1 rounded-md text-xs font-bold uppercase">
                        {{ formatText(cat.estatus) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden">
                <div class="p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">
                            {{ editingCategory ? 'Editar Categoría' : 'Nueva Categoría' }}
                        </h2>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nombre de la Categoría</label>
                            <input v-model="form.nombre" type="text" placeholder="Ej. ACUATICO"
                                class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                            <textarea v-model="form.descripcion" rows="3" placeholder="Breve descripción..."
                                class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Estatus</label>
                            <select v-model="form.estatus"
                                class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500">
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="INACTIVO">INACTIVO</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-8 flex gap-3">
                        <button @click="showModal = false"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-bold transition-all">Cancelar</button>
                        <button @click="save" :disabled="isSaving"
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 disabled:opacity-50">
                            {{ isSaving ? 'Guardando...' : (editingCategory ? 'Actualizar' : 'Crear') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
