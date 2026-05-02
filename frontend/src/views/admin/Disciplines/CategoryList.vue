<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useDisciplinesStore } from '@/stores/admin/disciplines';

const router = useRouter();
const disciplinesStore = useDisciplinesStore();
const { categories, isLoading } = storeToRefs(disciplinesStore);

const showModal = ref(false);
const isSaving = ref(false);
const editingCategory = ref(null);

const form = ref({
    nombre_categoria: '',
    descripcion_categoria: ''
});

onMounted(() => {
    disciplinesStore.fetchCategories();
});

const openCreate = () => {
    editingCategory.value = null;
    form.value = { nombre_categoria: '', descripcion_categoria: '' };
    showModal.value = true;
};

const openEdit = (cat) => {
    editingCategory.value = cat;
    form.value = { 
        nombre_categoria: cat.nombre_categoria, 
        descripcion_categoria: cat.descripcion_categoria 
    };
    showModal.value = true;
};

const save = async () => {
    if (!form.value.nombre_categoria) {
        alert("El nombre es obligatorio.");
        return;
    }
    isSaving.value = true;
    let res;
    if (editingCategory.value) {
        res = await disciplinesStore.updateCategory(editingCategory.value.id, form.value);
    } else {
        res = await disciplinesStore.createCategory(form.value);
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
    const res = await disciplinesStore.deleteCategory(id);
    if (!res.success) {
        alert(res.error);
    }
};

const goBack = () => router.push({ name: 'disciplines-list' });
</script>

<template>
    <div class="admin-container p-6 lg:p-8 bg-surface-50 min-h-screen">
        <header class="flex justify-between items-center mb-8 max-w-7xl mx-auto">
            <div class="flex items-center gap-4">
                <button @click="goBack" class="p-2.5 bg-white rounded-xl border border-surface-200 text-surface-500 hover:text-primary-600 hover:bg-surface-50 transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </button>
                <div>
                    <h1 class="text-3xl font-extrabold text-surface-900">Categorías de Disciplinas</h1>
                    <p class="text-sm font-medium text-surface-500 mt-1">Administra las categorías disponibles para clasificar deportes</p>
                </div>
            </div>
            <button @click="openCreate" class="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-sm flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Nueva Categoría
            </button>
        </header>

        <div v-if="isLoading && categories.length === 0" class="flex justify-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        </div>

        <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-5 max-w-7xl mx-auto">
            <div v-for="cat in categories" :key="cat.id" class="bg-white rounded-[1.5rem] p-6 shadow-sm border border-surface-200 hover:shadow-md transition-all flex flex-col sm:flex-row gap-6 relative group overflow-hidden">
                <div class="absolute inset-y-0 left-0 w-1.5 sm:w-2 bg-primary-400"></div>
                
                <div class="flex items-center justify-center sm:w-32 sm:border-r border-surface-100 bg-surface-50/50 -m-6 sm:m-0 sm:-ml-6 sm:p-6 p-6 border-b sm:border-b-0">
                    <div class="w-16 h-16 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01" /></svg>
                    </div>
                </div>

                <div class="flex-1 flex flex-col">
                    <div class="flex justify-between items-start gap-4">
                        <div>
                            <h3 class="text-base font-black text-surface-900 leading-tight">{{ cat.nombre_categoria }}</h3>
                        </div>
                        <div class="flex gap-1.5">
                            <button @click="openEdit(cat)" class="p-2 text-surface-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                            </button>
                            <button @click="remove(cat.id)" class="p-2 text-surface-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Eliminar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-4 py-2.5 px-3.5 rounded-xl bg-surface-50 border border-surface-100 flex-1">
                        <p class="text-[10px] font-black uppercase tracking-wider text-surface-400 mb-1">Descripción</p>
                        <p class="text-xs text-surface-500 font-medium line-clamp-2 leading-relaxed">
                            {{ cat.descripcion_categoria || 'Sin descripción disponible.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
          <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm" @click.self="showModal = false">
                <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0">
                    <div v-if="showModal" class="bg-white rounded-[2rem] w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
                        <div class="flex justify-between items-center px-6 py-5 border-b border-surface-100">
                            <div>
                                <h2 class="text-lg font-black text-surface-900">{{ editingCategory ? 'Editar Categoría' : 'Nueva Categoría' }}</h2>
                                <p class="text-xs text-surface-500 font-medium m-0 mt-0.5">Ingresa los datos de la categoría.</p>
                            </div>
                            <button @click="showModal = false" class="w-9 h-9 rounded-xl bg-surface-100 hover:bg-surface-200 flex items-center justify-center text-surface-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div class="p-6 overflow-y-auto space-y-5 bg-surface-50/30">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Nombre <span class="text-red-400">*</span></label>
                                <input v-model="form.nombre_categoria" type="text" placeholder="Ej. ACUATICO" class="w-full px-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Descripción</label>
                                <textarea v-model="form.descripcion_categoria" rows="3" placeholder="Breve descripción..." class="w-full px-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all resize-none outline-none"></textarea>
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-surface-100 flex items-center justify-end gap-3">
                            <button @click="showModal = false" class="px-5 py-2.5 rounded-xl border border-surface-200 bg-white text-sm font-semibold text-surface-700 hover:bg-surface-50 transition-colors">Cancelar</button>
                            <button @click="save" :disabled="isSaving" class="px-5 py-2.5 rounded-xl bg-primary-600 text-white text-sm font-bold hover:bg-primary-700 transition-colors disabled:opacity-50 flex items-center gap-2">
                                <svg v-if="isSaving" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                </svg>
                                {{ isSaving ? 'Guardando...' : (editingCategory ? 'Actualizar' : 'Crear Categoría') }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
          </Transition>
        </Teleport>
    </div>
</template>
