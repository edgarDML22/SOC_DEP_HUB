<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useDisciplinesStore } from '@/stores/admin/disciplines';

const route = useRoute();
const router = useRouter();
const disciplinesStore = useDisciplinesStore();

const discipline = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const isEditing = ref(false);

const editForm = ref({
    nombre_disciplina: '',
    categoria_disciplina: '',
    descripcion: '',
    estatus: ''
});

onMounted(async () => {
    try {
        const data = await disciplinesStore.fetchDisciplineDetails(route.params.id);
        discipline.value = data;
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
        categoria_disciplina: discipline.value.categoria_disciplina,
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

const handleDelete = async () => {
    if (!confirm("¿Estás seguro de eliminar esta disciplina?")) return;
    const res = await disciplinesStore.deleteDiscipline(discipline.value.id_disciplina);
    if (res.success) {
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
                                        'bg-gray-50 text-gray-500': discipline.estatus !== 'ACTIVO'
                                    }" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                                        {{ discipline.estatus }}
                                    </span>
                                </div>
                                <p class="text-indigo-600 font-bold uppercase tracking-widest text-sm">{{ discipline.categoria_disciplina }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="toggleEdit" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl font-bold hover:bg-indigo-600 hover:text-white transition-all text-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                {{ isEditing ? 'Cancelar Edición' : 'Editar Información' }}
                            </button>
                            <button v-if="!isEditing" @click="handleDelete" class="px-4 py-2 bg-red-50 text-red-600 rounded-xl font-bold hover:bg-red-600 hover:text-white transition-all text-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Eliminar
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
                                <p class="text-gray-900 font-medium">{{ discipline.categoria_disciplina }}</p>
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
                                <input v-model="editForm.categoria_disciplina" type="text" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Estatus</label>
                                <select v-model="editForm.estatus" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold">
                                    <option value="ACTIVO">ACTIVO</option>
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
        </div>
    </div>
</template>
