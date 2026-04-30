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
const showDeleteModal = ref(false);

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

onMounted(async () => {
    try {
        await disciplinesStore.fetchDisciplines();
        const data = await spacesStore.fetchSpaceDetails(route.params.id);
        space.value = data;
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
        nombre_espacio: space.value.nombre_espacio,
        capacidad_maxima: space.value.capacidad_maxima,
        es_reserva_on_demand: !!space.value.es_reserva_on_demand,
        es_clase_programada: !!space.value.es_clase_programada,
        es_uso_libre: !!space.value.es_uso_libre,
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

const confirmDelete = async () => {
    isSaving.value = true;
    const res = await spacesStore.deleteSpace(space.value.id_espacio);
    isSaving.value = false;
    if (res.success) {
        showDeleteModal.value = false;
        router.push({ name: 'spaces-list' });
    } else {
        alert(res.error);
    }
};

const goToDisciplines = () => {
    router.push({ name: 'spaces-disciplines', params: { id: space.value.id_espacio } });
};

const goBack = () => router.push({ name: 'spaces-list' });
</script>

<template>
    <div class="admin-container p-6 bg-gray-50 min-h-screen">
        <header class="flex items-center gap-4 mb-8">
            <button @click="goBack"
                class="p-2 bg-white rounded-xl border border-gray-100 text-gray-500 hover:text-indigo-600 transition-all shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
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
                            </div>
                            <div class="flex gap-2">
                                <span v-if="space.es_reserva_on_demand"
                                    class="text-[9px] font-bold bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-lg border border-indigo-100 uppercase tracking-widest">ON
                                    DEMAND</span>
                                <span v-if="space.es_clase_programada"
                                    class="text-[9px] font-bold bg-blue-50 text-blue-600 px-2 py-0.5 rounded-lg border border-blue-100 uppercase tracking-widest">CLASE</span>
                                <span v-if="space.es_uso_libre"
                                    class="text-[9px] font-bold bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-lg border border-emerald-100 uppercase tracking-widest">USO
                                    LIBRE</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button v-if="!isEditing" @click="goToDisciplines"
                            class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-xl font-bold hover:bg-emerald-600 hover:text-white transition-all text-sm flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Disciplinas
                        </button>
                        <button @click="toggleEdit"
                            class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl font-bold hover:bg-indigo-600 hover:text-white transition-all text-sm flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            {{ isEditing ? 'Cancelar' : 'Editar Info' }}
                        </button>
                        <button v-if="!isEditing" @click="showDeleteModal = true"
                            class="px-4 py-2 bg-red-50 text-red-600 rounded-xl font-bold hover:bg-red-600 hover:text-white transition-all text-sm flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Deshabilitar
                        </button>
                    </div>
                </div>

                <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Datos Generales
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 rounded-2xl">
                                <label
                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Capacidad
                                    Máxima</label>
                                <p class="text-xl font-black text-indigo-600">{{ space.capacidad_maxima }} <span
                                        class="text-xs font-normal text-gray-500">personas</span></p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-2xl">
                                <label
                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Configuración
                                    de Uso</label>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span v-if="space.es_reserva_on_demand"
                                        class="text-[8px] font-black bg-white text-indigo-600 px-2 py-1 rounded-md border border-indigo-100">RESERVA</span>
                                    <span v-if="space.es_clase_programada"
                                        class="text-[8px] font-black bg-white text-blue-600 px-2 py-1 rounded-md border border-blue-100">CLASE</span>
                                    <span v-if="space.es_uso_libre"
                                        class="text-[8px] font-black bg-white text-emerald-600 px-2 py-1 rounded-md border border-emerald-100">LIBRE</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label
                                class="text-xs font-bold text-gray-400 uppercase tracking-widest block mb-2">Descripción</label>
                            <p class="text-gray-600 leading-relaxed bg-gray-50 p-4 rounded-2xl">{{ space.descripcion ||
                                'Sin descripción disponible.' }}</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Disciplinas Autorizadas
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <div v-for="d in space.disciplinas" :key="d.id_disciplina"
                                class="bg-indigo-50 text-indigo-600 px-4 py-2 rounded-xl text-sm font-bold border border-indigo-100">
                                {{ d.nombre_disciplina }}
                            </div>
                            <div v-if="!space.disciplinas?.length" class="text-gray-400 italic py-4">No hay disciplinas
                                asignadas a este espacio.</div>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div v-else class="space-y-6 animate-in slide-in-from-top duration-300">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nombre</label>
                                <input v-model="editForm.nombre_espacio" type="text"
                                    class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Capacidad</label>
                                    <input v-model="editForm.capacidad_maxima" type="number"
                                        class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Estatus</label>
                                    <select v-model="editForm.estatus"
                                        class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold uppercase">
                                        <option value="ACTIVO">ACTIVO</option>
                                        <option value="MANTENIMIENTO">MANTENIMIENTO</option>
                                        <option value="DESHABILITADO">DESHABILITADO</option>
                                        <option value="INACTIVO">INACTIVO</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Configuración de Uso</label>
                                <div class="grid grid-cols-1 gap-2">
                                    <button
                                        @click="() => { editForm.es_reserva_on_demand = !editForm.es_reserva_on_demand; if (editForm.es_reserva_on_demand) editForm.es_uso_libre = false; }"
                                        :class="editForm.es_reserva_on_demand ? 'bg-indigo-600 text-white' : 'bg-gray-50 text-gray-500'"
                                        class="flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold transition-all">
                                        <span>RESERVA ON DEMAND</span>
                                        <div :class="editForm.es_reserva_on_demand ? 'bg-white text-indigo-600' : 'bg-gray-200 text-gray-400'"
                                            class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]">
                                            {{ editForm.es_reserva_on_demand ? '✓' : '' }}
                                        </div>
                                    </button>
                                    <button
                                        @click="() => { editForm.es_clase_programada = !editForm.es_clase_programada; if (editForm.es_clase_programada) editForm.es_uso_libre = false; }"
                                        :class="editForm.es_clase_programada ? 'bg-blue-600 text-white' : 'bg-gray-50 text-gray-500'"
                                        class="flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold transition-all">
                                        <span>CLASE PROGRAMADA</span>
                                        <div :class="editForm.es_clase_programada ? 'bg-white text-blue-600' : 'bg-gray-200 text-gray-400'"
                                            class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]">
                                            {{ editForm.es_clase_programada ? '✓' : '' }}
                                        </div>
                                    </button>
                                    <button
                                        @click="() => { editForm.es_uso_libre = !editForm.es_uso_libre; if (editForm.es_uso_libre) { editForm.es_reserva_on_demand = false; editForm.es_clase_programada = false; } }"
                                        :class="editForm.es_uso_libre ? 'bg-emerald-600 text-white' : 'bg-gray-50 text-gray-500'"
                                        class="flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold transition-all">
                                        <span>USO LIBRE</span>
                                        <div :class="editForm.es_uso_libre ? 'bg-white text-emerald-600' : 'bg-gray-200 text-gray-400'"
                                            class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]">
                                            {{ editForm.es_uso_libre ? '✓' : '' }}
                                        </div>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                                <textarea v-model="editForm.descripcion" rows="4"
                                    class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                            </div>
                        </div>

                        <div class="space-y-6 bg-indigo-50/50 p-6 rounded-3xl border border-indigo-100 h-fit">
                            <h4 class="font-bold text-indigo-900 mb-2">Gestión de Disciplinas</h4>
                            <p class="text-sm text-indigo-700 mb-4">Las disciplinas permitidas para este espacio ahora
                                se gestionan en una vista dedicada para mayor precisión.</p>
                            <button @click="goToDisciplines"
                                class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all flex justify-center items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Editar Disciplinas
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-6">
                        <button @click="handleUpdate" :disabled="isSaving"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-12 py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 disabled:opacity-50">
                            {{ isSaving ? 'Guardando...' : 'Actualizar Todo' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deactivation Modal -->
    <Transition name="fade">
        <div v-if="showDeleteModal"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div
                class="bg-white rounded-[32px] w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in duration-300">
                <div class="p-8 text-center">
                    <div
                        class="w-20 h-20 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">¿Deshabilitar Espacio?</h3>
                    <p class="text-gray-500 mb-8">Esta acción cambiará el estado a <b>DESHABILITADO</b>. El espacio no
                        podrá ser reservado ni utilizado hasta que se active nuevamente.</p>

                    <div class="bg-gray-50 rounded-2xl p-4 mb-8 text-left space-y-3">
                        <div class="flex items-center gap-3 text-sm font-bold text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Sin Reservaciones Activas
                        </div>
                        <div class="flex items-center gap-3 text-sm font-bold text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Sin Sesiones Programadas
                        </div>
                        <div class="flex items-center gap-3 text-sm font-bold text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Sin Encuentros de Torneo
                        </div>
                    </div>

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
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
