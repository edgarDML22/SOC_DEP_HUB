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
    <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-20 font-sans">
        <div class="max-w-5xl mx-auto space-y-6">

            <!-- Navegación Superior -->
            <header class="flex items-center gap-4 mb-8">
                <button @click="goBack" class="w-11 h-11 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 hover:text-primary-600 transition-all shadow-sm group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </button>
                <h1 class="text-2xl font-black text-surface-900 tracking-tight">Detalles del Espacio</h1>
            </header>

            <!-- Loading -->
            <section v-if="isLoading" class="flex flex-col items-center justify-center p-20">
                <div class="w-12 h-12 border-4 border-surface-200 border-t-primary-600 rounded-full animate-spin mb-4"></div>
                <p class="text-sm font-extrabold uppercase tracking-widest text-surface-400">Cargando detalles...</p>
            </section>

            <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 translate-y-4 scale-[0.98]" enter-to-class="opacity-100 translate-y-0 scale-100">
                <div v-if="space && !isLoading" class="space-y-6">
                    
                    <!-- Main Info Card -->
                    <article class="bg-white rounded-[2.5rem] shadow-xl shadow-surface-200/40 border border-surface-200 overflow-hidden relative">
                        <!-- Accent Bar -->
                        <div class="absolute top-0 left-0 right-0 h-2 bg-linear-to-r from-emerald-500 to-emerald-400"></div>

                        <div class="p-8 lg:p-10">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
                                <div class="flex gap-6 items-center">
                                    <div class="p-5 bg-emerald-50 text-emerald-600 rounded-2xl shadow-sm border border-emerald-100 shrink-0">
                                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                            <polyline points="9 22 9 12 15 12 15 22" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-3 mb-1">
                                            <h2 class="text-3xl font-black text-surface-900 leading-tight">{{ space.nombre_espacio }}</h2>
                                            <span :class="{
                                                'bg-green-50 text-green-700 border-green-200': space.estatus === 'ACTIVO',
                                                'bg-amber-50 text-amber-700 border-amber-200': space.estatus === 'MANTENIMIENTO',
                                                'bg-red-50 text-red-700 border-red-200': space.estatus === 'DESHABILITADO',
                                                'bg-surface-50 text-surface-600 border-surface-200': space.estatus === 'INACTIVO'
                                            }" class="px-3 py-1 rounded-lg border text-[10px] font-black uppercase tracking-widest shadow-sm">
                                                {{ space.estatus }}
                                            </span>
                                        </div>
                                        <div class="flex gap-2 mt-2">
                                            <span v-if="space.es_reserva_on_demand" class="text-[9px] font-bold bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-lg border border-indigo-100 uppercase tracking-widest">ON DEMAND</span>
                                            <span v-if="space.es_clase_programada" class="text-[9px] font-bold bg-primary-50 text-primary-600 px-2 py-0.5 rounded-lg border border-primary-100 uppercase tracking-widest">CLASE</span>
                                            <span v-if="space.es_uso_libre" class="text-[9px] font-bold bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-lg border border-emerald-100 uppercase tracking-widest">USO LIBRE</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 shrink-0">
                                    <button v-if="!isEditing" @click="goToDisciplines" class="px-5 py-2.5 bg-emerald-50 text-emerald-700 rounded-xl font-bold hover:bg-emerald-600 hover:text-white transition-colors text-sm flex items-center gap-2 border border-emerald-200 hover:border-emerald-600 shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                        Disciplinas
                                    </button>
                                    <button @click="toggleEdit" class="px-5 py-2.5 bg-primary-50 text-primary-700 rounded-xl font-bold hover:bg-primary-600 hover:text-white transition-colors text-sm flex items-center gap-2 border border-primary-200 hover:border-primary-600 shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        {{ isEditing ? 'Cancelar' : 'Editar Info' }}
                                    </button>
                                    <button v-if="!isEditing" @click="showDeleteModal = true" class="px-5 py-2.5 bg-white text-red-600 rounded-xl font-bold hover:bg-red-50 transition-colors text-sm flex items-center gap-2 border border-surface-200 hover:border-red-200 shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        Deshabilitar
                                    </button>
                                </div>
                            </div>

                            <div v-if="!isEditing" class="grid grid-cols-1 md:grid-cols-2 gap-8 border-t border-surface-100 pt-8 mt-8">
                                <div class="space-y-6">
                                    <h3 class="text-sm font-black text-surface-900 uppercase tracking-widest flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-primary-500"></div>
                                        Datos Generales
                                    </h3>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100">
                                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500 block mb-1">Capacidad Máxima</span>
                                            <p class="text-2xl font-black text-primary-600 leading-none">{{ space.capacidad_maxima }} <span class="text-xs font-bold text-surface-400">personas</span></p>
                                        </div>
                                        <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100">
                                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500 block mb-2">Configuración de Uso</span>
                                            <div class="flex flex-col gap-1.5">
                                                <span v-if="space.es_reserva_on_demand" class="text-[9px] font-black bg-white text-indigo-600 px-2.5 py-1 rounded-lg border border-indigo-100 shadow-sm w-fit">RESERVA ON DEMAND</span>
                                                <span v-if="space.es_clase_programada" class="text-[9px] font-black bg-white text-primary-600 px-2.5 py-1 rounded-lg border border-primary-100 shadow-sm w-fit">CLASE PROGRAMADA</span>
                                                <span v-if="space.es_uso_libre" class="text-[9px] font-black bg-white text-emerald-600 px-2.5 py-1 rounded-lg border border-emerald-100 shadow-sm w-fit">USO LIBRE</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-surface-50/50 rounded-2xl p-5 border border-surface-100 flex flex-col gap-2">
                                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Descripción</span>
                                        <p class="text-sm font-medium text-surface-700 leading-relaxed">{{ space.descripcion || 'Sin descripción disponible.' }}</p>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <h3 class="text-sm font-black text-surface-900 uppercase tracking-widest flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                        Disciplinas Autorizadas
                                    </h3>
                                    <div class="flex flex-wrap gap-2">
                                        <div v-for="d in space.disciplinas" :key="d.id_disciplina"
                                            class="bg-white text-surface-700 px-4 py-2.5 rounded-xl text-xs font-bold border border-surface-200 shadow-sm flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
                                            {{ d.nombre_disciplina }}
                                        </div>
                                        <div v-if="!space.disciplinas?.length" class="text-surface-400 italic text-sm font-bold p-4 bg-surface-50 rounded-xl border border-surface-200 border-dashed w-full text-center">
                                            No hay disciplinas asignadas a este espacio.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Form -->
                            <div v-else class="space-y-6 animate-in slide-in-from-top duration-300 border-t border-surface-100 pt-8 mt-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-6">
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Nombre</label>
                                            <input v-model="editForm.nombre_espacio" type="text" class="w-full px-4 py-3.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none">
                                        </div>
                                        <div class="grid grid-cols-2 gap-6">
                                            <div class="space-y-1.5">
                                                <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Capacidad</label>
                                                <input v-model="editForm.capacidad_maxima" type="number" class="w-full px-4 py-3.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none">
                                            </div>
                                            <div class="space-y-1.5">
                                                <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Estatus</label>
                                                <select v-model="editForm.estatus" class="w-full px-4 py-3.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none cursor-pointer uppercase">
                                                    <option value="ACTIVO">ACTIVO</option>
                                                    <option value="MANTENIMIENTO">MANTENIMIENTO</option>
                                                    <option value="DESHABILITADO">DESHABILITADO</option>
                                                    <option value="INACTIVO">INACTIVO</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Configuración de Uso</label>
                                            <div class="flex flex-col gap-3">
                                                <button @click="() => { editForm.es_reserva_on_demand = !editForm.es_reserva_on_demand; if (editForm.es_reserva_on_demand) editForm.es_uso_libre = false; }" type="button"
                                                    :class="editForm.es_reserva_on_demand ? 'bg-indigo-50 border-indigo-500 text-indigo-700 ring-4 ring-indigo-50' : 'bg-white border-surface-200 text-surface-500 hover:border-surface-300 hover:bg-surface-50'"
                                                    class="flex items-center justify-between px-5 py-4 rounded-[1.25rem] border-2 text-sm font-bold transition-all shadow-sm group">
                                                    <span>Reserva On Demand</span>
                                                    <div :class="editForm.es_reserva_on_demand ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-surface-200 bg-surface-50 text-transparent group-hover:border-surface-300'" class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all shrink-0">
                                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                    </div>
                                                </button>
                                                <button @click="() => { editForm.es_clase_programada = !editForm.es_clase_programada; if (editForm.es_clase_programada) editForm.es_uso_libre = false; }" type="button"
                                                    :class="editForm.es_clase_programada ? 'bg-primary-50 border-primary-500 text-primary-700 ring-4 ring-primary-50' : 'bg-white border-surface-200 text-surface-500 hover:border-surface-300 hover:bg-surface-50'"
                                                    class="flex items-center justify-between px-5 py-4 rounded-[1.25rem] border-2 text-sm font-bold transition-all shadow-sm group">
                                                    <span>Clase Programada</span>
                                                    <div :class="editForm.es_clase_programada ? 'bg-primary-600 border-primary-600 text-white' : 'border-surface-200 bg-surface-50 text-transparent group-hover:border-surface-300'" class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all shrink-0">
                                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                    </div>
                                                </button>
                                                <button @click="() => { editForm.es_uso_libre = !editForm.es_uso_libre; if (editForm.es_uso_libre) { editForm.es_reserva_on_demand = false; editForm.es_clase_programada = false; } }" type="button"
                                                    :class="editForm.es_uso_libre ? 'bg-emerald-50 border-emerald-500 text-emerald-700 ring-4 ring-emerald-50' : 'bg-white border-surface-200 text-surface-500 hover:border-surface-300 hover:bg-surface-50'"
                                                    class="flex items-center justify-between px-5 py-4 rounded-[1.25rem] border-2 text-sm font-bold transition-all shadow-sm group">
                                                    <span>Uso Libre</span>
                                                    <div :class="editForm.es_uso_libre ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-surface-200 bg-surface-50 text-transparent group-hover:border-surface-300'" class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all shrink-0">
                                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Descripción</label>
                                            <textarea v-model="editForm.descripcion" rows="4" class="w-full px-4 py-3.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-medium text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none resize-none"></textarea>
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                        <div class="bg-primary-50/50 p-8 rounded-[2rem] border border-primary-100 h-full flex flex-col justify-center">
                                            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-sm border border-primary-100 mb-6 text-primary-600">
                                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                            </div>
                                            <h4 class="text-xl font-black text-surface-900 mb-2">Gestión de Disciplinas</h4>
                                            <p class="text-sm font-medium text-surface-600 mb-8 leading-relaxed">Las disciplinas permitidas para este espacio se gestionan en una vista dedicada para mayor control y precisión.</p>
                                            <button type="button" @click="goToDisciplines" class="w-full bg-white text-primary-700 py-3.5 rounded-xl font-bold hover:bg-primary-60 transition-colors flex justify-center items-center gap-2 border border-primary-200 shadow-sm hover:shadow-md">
                                                Editar Disciplinas
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-3 pt-6">
                                    <button @click="handleUpdate" :disabled="isSaving" class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-3.5 rounded-xl font-bold transition-all shadow-sm hover:shadow-md disabled:opacity-50 flex items-center gap-2">
                                        <svg v-if="isSaving" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                        {{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </Transition>
        </div>

        <!-- Deactivation Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm" @click.self="showDeleteModal = false">
                    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0">
                        <div v-if="showDeleteModal" class="bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden text-center">
                            <div class="p-8">
                                <div class="w-20 h-20 bg-red-50 text-red-600 rounded-[1.5rem] flex items-center justify-center mx-auto mb-6 border border-red-100">
                                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </div>
                                <h3 class="text-2xl font-black text-surface-900 mb-3">¿Deshabilitar Espacio?</h3>
                                <p class="text-sm font-medium text-surface-500 mb-8 leading-relaxed">Esta acción cambiará el estado a <b class="text-surface-900">DESHABILITADO</b>. El espacio no podrá ser reservado ni utilizado hasta que se active nuevamente.</p>
                                
                                <div class="bg-surface-50 rounded-[1.5rem] p-5 mb-8 text-left space-y-4 border border-surface-200">
                                    <div class="flex items-center gap-3 text-sm font-bold text-surface-700">
                                        <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        </div>
                                        Sin Reservaciones Activas
                                    </div>
                                    <div class="flex items-center gap-3 text-sm font-bold text-surface-700">
                                        <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        </div>
                                        Sin Sesiones Programadas
                                    </div>
                                    <div class="flex items-center gap-3 text-sm font-bold text-surface-700">
                                        <div class="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                        </div>
                                        Sin Encuentros de Torneo
                                    </div>
                                </div>

                                <div class="flex gap-3">
                                    <button @click="showDeleteModal = false" class="flex-1 py-3.5 bg-white border border-surface-200 hover:bg-surface-50 text-surface-700 rounded-xl font-bold transition-all text-sm">
                                        Cancelar
                                    </button>
                                    <button @click="confirmDelete" :disabled="isSaving" class="flex-1 py-3.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition-all shadow-sm hover:shadow-md disabled:opacity-50 text-sm flex justify-center items-center gap-2">
                                        <svg v-if="isSaving" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                        {{ isSaving ? 'Procesando...' : 'Deshabilitar' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </main>
</template>
