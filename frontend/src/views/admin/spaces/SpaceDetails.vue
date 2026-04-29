<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useSpacesStore } from '@/stores/admin/spaces';
import { useDisciplinesStore } from '@/stores/admin/disciplines';

const route = useRoute();
const router = useRouter();
const spacesStore = useSpacesStore();
const disciplinesStore = useDisciplinesStore();
const { disciplines } = storeToRefs(disciplinesStore);

const space = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const isEditing = ref(false);

const editForm = ref({
    nombre_espacio: '',
    capacidad_maxima: 0,
    tipo_espacio: '',
    estatus: '',
    descripcion: '',
    disciplinas: []
});

onMounted(async () => {
    try {
        await disciplinesStore.fetchDisciplines();
        const data = await spacesStore.fetchSpaceDetails(route.params.id);
        space.value = data;
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
        nombre_espacio: space.value.nombre_espacio,
        capacidad_maxima: space.value.capacidad_maxima,
        tipo_espacio: space.value.tipo_espacio,
        estatus: space.value.estatus,
        descripcion: space.value.descripcion || '',
        disciplinas: space.value.disciplinas.map(d => d.id_disciplina)
    };
};

const toggleEdit = () => {
    if (isEditing.value) resetForm();
    isEditing.value = !isEditing.value;
};

const toggleDisciplina = (id) => {
    const index = editForm.value.disciplinas.indexOf(id);
    if (index > -1) editForm.value.disciplinas.splice(index, 1);
    else editForm.value.disciplinas.push(id);
};

const handleUpdate = async () => {
    isSaving.value = true;
    const res = await spacesStore.updateSpace(space.value.id_espacio, editForm.value);
    isSaving.value = false;
    if (res.success) {
        space.value = res.data;
        isEditing.value = false;
    } else {
        alert(res.error);
    }
};

const handleDelete = async () => {
    if (!confirm("¿Estás seguro de deshabilitar este espacio? Si tiene actividades programadas, el sistema no lo permitirá.")) return;
    const res = await spacesStore.deleteSpace(space.value.id_espacio);
    if (res.success) {
        router.push({ name: 'spaces-list' });
    } else {
        alert(res.error);
    }
};

const goBack = () => router.push({ name: 'spaces-list' });
</script>

<template>
    <div class="admin-container p-6 bg-gray-50 min-h-screen">
        <header class="flex items-center gap-4 mb-8">
            <button @click="goBack" class="p-2 bg-white rounded-xl border border-gray-100 text-gray-500 hover:text-indigo-600 transition-all shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </button>
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900">Detalles del Espacio</h1>
                <p class="text-gray-500">Configuración técnica y disciplinas permitidas</p>
            </div>
        </header>

        <div v-if="isLoading" class="flex justify-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>

        <div v-else-if="space" class="max-w-5xl space-y-6">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-8">
                        <div class="flex gap-6 items-center">
                            <div class="p-5 bg-emerald-50 text-emerald-600 rounded-2xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <h2 class="text-2xl font-bold text-gray-900">{{ space.nombre_espacio }}</h2>
                                    <span :class="{
                                        'bg-emerald-50 text-emerald-600': space.estatus === 'ACTIVO',
                                        'bg-amber-50 text-amber-600': space.estatus === 'MANTENIMIENTO',
                                        'bg-red-50 text-red-600': space.estatus === 'DESHABILITADO',
                                        'bg-gray-50 text-gray-500': space.estatus === 'INACTIVO'
                                    }" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                                        {{ space.estatus }}
                                    </span>
                                </div>
                                <p class="text-indigo-600 font-bold uppercase tracking-widest text-sm">{{ space.tipo_espacio.replace(/_/g, ' ') }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="toggleEdit" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl font-bold hover:bg-indigo-600 hover:text-white transition-all text-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                {{ isEditing ? 'Cancelar Edición' : 'Editar Información' }}
                            </button>
                            <button v-if="!isEditing" @click="handleDelete" class="px-4 py-2 bg-red-50 text-red-600 rounded-xl font-bold hover:bg-red-600 hover:text-white transition-all text-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                Deshabilitar
                            </button>
                        </div>
                    </div>

                    <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div class="space-y-6">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Datos Generales
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-gray-50 rounded-2xl">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Capacidad Máxima</label>
                                    <p class="text-xl font-black text-indigo-600">{{ space.capacidad_maxima }} <span class="text-xs font-normal text-gray-500">personas</span></p>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-2xl">
                                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Tipo de Uso</label>
                                    <p class="text-sm font-bold text-gray-700">{{ space.tipo_espacio.replace(/_/g, ' ') }}</p>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-400 uppercase tracking-widest block mb-2">Descripción</label>
                                <p class="text-gray-600 leading-relaxed bg-gray-50 p-4 rounded-2xl">{{ space.descripcion || 'Sin descripción disponible.' }}</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                Disciplinas Autorizadas
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <div v-for="d in space.disciplinas" :key="d.id_disciplina" class="bg-indigo-50 text-indigo-600 px-4 py-2 rounded-xl text-sm font-bold border border-indigo-100">
                                    {{ d.nombre_disciplina }}
                                </div>
                                <div v-if="!space.disciplinas?.length" class="text-gray-400 italic py-4">No hay disciplinas asignadas a este espacio.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Form -->
                    <div v-else class="space-y-6 animate-in slide-in-from-top duration-300">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Nombre</label>
                                    <input v-model="editForm.nombre_espacio" type="text" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Capacidad</label>
                                        <input v-model="editForm.capacidad_maxima" type="number" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Estatus</label>
                                        <select v-model="editForm.estatus" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold uppercase">
                                            <option value="ACTIVO">ACTIVO</option>
                                            <option value="MANTENIMIENTO">MANTENIMIENTO</option>
                                            <option value="DESHABILITADO">DESHABILITADO</option>
                                            <option value="INACTIVO">INACTIVO</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Uso</label>
                                    <select v-model="editForm.tipo_espacio" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold">
                                        <option value="RESERVA_ON_DEMAND">RESERVA ON DEMAND</option>
                                        <option value="CLASE_PROGRAMADA">CLASE PROGRAMADA</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                                    <textarea v-model="editForm.descripcion" rows="4" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <label class="block text-sm font-bold text-gray-700">Editar Disciplinas Permitidas</label>
                                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 max-h-[400px] overflow-y-auto">
                                    <div class="grid grid-cols-1 gap-2">
                                        <button v-for="d in disciplines" :key="d.id_disciplina"
                                            @click="toggleDisciplina(d.id_disciplina)"
                                            :class="editForm.disciplinas.includes(d.id_disciplina) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'bg-white text-gray-600 border border-gray-100'"
                                            class="w-full text-left px-4 py-3 rounded-xl text-sm font-bold transition-all">
                                            <div class="flex justify-between items-center">
                                                {{ d.nombre_disciplina }}
                                                <svg v-if="editForm.disciplinas.includes(d.id_disciplina)" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-6">
                            <button @click="handleUpdate" :disabled="isSaving" class="bg-indigo-600 hover:bg-indigo-700 text-white px-12 py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 disabled:opacity-50">
                                {{ isSaving ? 'Guardando...' : 'Actualizar Todo' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
