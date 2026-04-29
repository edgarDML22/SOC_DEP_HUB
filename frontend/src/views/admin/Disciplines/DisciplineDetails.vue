<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useDisciplinesStore } from '@/stores/admin/disciplines';

const route = useRoute();
const router = useRouter();
const disciplinesStore = useDisciplinesStore();

const { categories, isLoading: storeLoading } = storeToRefs(disciplinesStore);

const discipline = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const isEditing = ref(false);
const showDeleteModal = ref(false);

const editForm = ref({
    nombre_disciplina: '',
    id_categoria: null,
    descripcion: '',
    estatus: ''
});

onMounted(async () => {
    try {
        await disciplinesStore.fetchCategories();
        const data = await disciplinesStore.fetchDisciplineDetails(route.params.id);
        discipline.value = data;
        if (route.query.edit === 'true') {
            isEditing.value = true;
        }
        resetForm();
    } catch (error) {
        console.error(error);
        alert("Error al cargar los detalles.");
    } finally {
        isLoading.value = false;
    }
});

const resetForm = () => {
    editForm.value = {
        nombre_disciplina: discipline.value.nombre_disciplina,
        id_categoria: discipline.value.id_categoria,
        descripcion: discipline.value.descripcion || '',
        estatus: discipline.value.estatus
    };
};

const toggleEdit = () => {
    if (isEditing.value) resetForm();
    isEditing.value = !isEditing.value;
};

const handleUpdate = async () => {
    isSaving.value = true;
    const res = await disciplinesStore.updateDiscipline(discipline.value.id_disciplina, editForm.value);
    isSaving.value = false;
    if (res.success) {
        discipline.value = res.data;
        isEditing.value = false;
    } else {
        alert(res.error);
    }
};

const openDeleteModal = () => {
    showDeleteModal.value = true;
};

const confirmDelete = async () => {
    isSaving.value = true;
    const res = await disciplinesStore.deleteDiscipline(discipline.value.id_disciplina);
    isSaving.value = false;
    if (res.success) {
        showDeleteModal.value = false;
        router.push({ name: 'disciplines-list' });
    } else {
        alert(res.error);
    }
};

const goBack = () => router.push({ name: 'disciplines-list' });
</script>

<template>
    <div class="admin-container p-6 bg-gray-50 min-h-screen">
        <header class="flex items-center gap-4 mb-8">
            <button @click="goBack" class="p-2 bg-white rounded-xl border border-gray-100 text-gray-500 hover:text-indigo-600 transition-all shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </button>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900">Detalles de Disciplina</h1>
                <p class="text-gray-500">Información detallada y configuración</p>
            </div>
        </header>

        <div v-if="isLoading" class="flex justify-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>

        <div v-else-if="discipline" class="max-w-4xl space-y-6">
            <!-- Main Info Card -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-8">
                        <div class="flex gap-6 items-center">
                            <div class="p-5 bg-indigo-50 text-indigo-600 rounded-2xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" x2="6" y1="1" y2="4"/><line x1="10" x2="10" y1="1" y2="4"/><line x1="14" x2="14" y1="1" y2="4"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <h2 class="text-2xl font-bold text-gray-900">{{ discipline.nombre_disciplina }}</h2>
                                    <span :class="{
                                        'bg-emerald-50 text-emerald-600': discipline.estatus === 'ACTIVO',
                                        'bg-amber-50 text-amber-600': discipline.estatus === 'MANTENIMIENTO',
                                        'bg-red-50 text-red-600': discipline.estatus === 'DESHABILITADO',
                                        'bg-gray-50 text-gray-500': discipline.estatus === 'INACTIVO'
                                    }" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                                        {{ discipline.estatus }}
                                    </span>
                                </div>
                                <p class="text-indigo-600 font-bold uppercase tracking-widest text-sm">
                                    {{ discipline.categoria?.nombre_categoria || discipline.categoria_disciplina }}
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="toggleEdit" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl font-bold hover:bg-indigo-600 hover:text-white transition-all text-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                {{ isEditing ? 'Cancelar Edición' : 'Editar Información' }}
                            </button>
                            <button v-if="!isEditing" @click="openDeleteModal" class="px-4 py-2 bg-red-50 text-red-600 rounded-xl font-bold hover:bg-red-600 hover:text-white transition-all text-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Deshabilitar
                            </button>
                        </div>
                    </div>

                    <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">ID Disciplina</label>
                                <p class="text-gray-900 font-medium">#{{ discipline.id_disciplina }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">Categoría</label>
                                <p class="text-gray-900 font-medium">
                                    {{ discipline.categoria?.nombre_categoria || discipline.categoria_disciplina }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">Descripción</label>
                                <p class="text-gray-600">{{ discipline.descripcion || 'Sin descripción disponible.' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <div v-else class="space-y-6 animate-in slide-in-from-top duration-300">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nombre</label>
                                <input v-model="editForm.nombre_disciplina" type="text" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Categoría</label>
                                <select v-model="editForm.id_categoria" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-medium">
                                    <option :value="null" disabled>Selecciona una categoría</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nombre_categoria }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Estatus</label>
                                <select v-model="editForm.estatus" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold">
                                    <option value="ACTIVO">ACTIVO</option>
                                    <option value="MANTENIMIENTO">MANTENIMIENTO</option>
                                    <option value="DESHABILITADO">DESHABILITADO</option>
                                    <option value="INACTIVO">INACTIVO</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                            <textarea v-model="editForm.descripcion" rows="4" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <button @click="handleUpdate" :disabled="isSaving" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 disabled:opacity-50">
                                {{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructors Section -->
            <div id="instructors" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                        Instructores que imparten esta disciplina
                    </h3>
                    
                    <div v-if="discipline.instructores?.length" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="ins in discipline.instructores" :key="ins.id_instructor" 
                             class="flex items-center gap-4 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            <div class="w-12 h-12 bg-indigo-600 text-white rounded-xl flex items-center justify-center font-bold text-lg">
                                {{ ins.nombre_completo.charAt(0) }}
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">{{ ins.nombre_completo }}</p>
                                <p class="text-xs text-gray-500">ID: #{{ ins.id_instructor }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                        <p class="text-gray-400 italic">No hay instructores asignados a esta disciplina.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deactivation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-[32px] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-300">
                <div class="p-8 text-center">
                    <div class="w-20 h-20 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">¿Deshabilitar Disciplina?</h3>
                    <p class="text-gray-500 mb-8">¿Estás seguro de deshabilitar <b>{{ discipline.nombre_disciplina }}</b>? El sistema validará que no haya actividades pendientes.</p>
                    
                    <div class="flex gap-3">
                        <button @click="showDeleteModal = false" class="flex-1 py-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl font-bold transition-all">
                            Cancelar
                        </button>
                        <button @click="confirmDelete" :disabled="isSaving" class="flex-1 py-4 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-bold transition-all shadow-lg shadow-red-100 disabled:opacity-50">
                            {{ isSaving ? 'Procesando...' : 'Deshabilitar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
