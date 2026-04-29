<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useSpacesStore } from '@/stores/admin/spaces';
import { useDisciplinesStore } from '@/stores/admin/disciplines';

// Import Icons
import IconFutbol from '@/components/icons/sports/IconFutbol.vue';
import IconBasquetbol from '@/components/icons/sports/IconBasquetbol.vue';
import IconTenis from '@/components/icons/sports/IconTenis.vue';
import IconVoleibol from '@/components/icons/sports/IconVoleibol.vue';
import IconSquash from '@/components/icons/sports/IconSquash.vue';
import IconFrontenis from '@/components/icons/sports/IconFrontenis.vue';
import IconPadel from '@/components/icons/sports/IconPadel.vue';
import IconDefault from '@/components/icons/sports/IconDefault.vue';

const router = useRouter();
const spacesStore = useSpacesStore();
const disciplinesStore = useDisciplinesStore();
const { spaces, isLoading } = storeToRefs(spacesStore);
const { disciplines } = storeToRefs(disciplinesStore);

const search = ref('');
const filterTipo = ref('TODOS');
const filterStatus = ref('TODOS');
const isSaving = ref(false);
const showDeleteModal = ref(false);
const selectedSpace = ref(null);

const newSpace = ref({
    nombre_espacio: '',
    capacidad_maxima: 10,
    es_reserva_on_demand: true,
    es_clase_programada: false,
    es_uso_libre: false,
    estatus: 'ACTIVO',
    descripcion: '',
    disciplinas: []
});

onMounted(() => {
    spacesStore.fetchSpaces();
    disciplinesStore.fetchDisciplines();
});

const filteredSpaces = computed(() => {
    let result = [...spaces.value];
    
    // Aplicar filtros
    if (search.value) {
        const q = search.value.toLowerCase();
        result = result.filter(s => s.nombre_espacio.toLowerCase().includes(q));
    }
    if (filterTipo.value !== 'TODOS') {
        if (filterTipo.value === 'RESERVA_ON_DEMAND') result = result.filter(s => s.es_reserva_on_demand);
        else if (filterTipo.value === 'CLASE_PROGRAMADA') result = result.filter(s => s.es_clase_programada);
        else if (filterTipo.value === 'USO_LIBRE') result = result.filter(s => s.es_uso_libre);
    }
    if (filterStatus.value !== 'TODOS') {
        result = result.filter(s => s.estatus === filterStatus.value);
    }

    // Ordenar: primero por nombre
    result.sort((a, b) => a.nombre_espacio.localeCompare(b.nombre_espacio));

    return result;
});

const openDetails = (id) => {
    router.push({ name: 'spaces-details', params: { id } });
};

const openEdit = (id) => {
    router.push({ name: 'spaces-details', params: { id }, query: { edit: 'true' } });
};

const goToDisciplines = (id) => {
    router.push({ name: 'spaces-disciplines', params: { id } });
};

const openDeleteModal = (space) => {
    selectedSpace.value = space;
    showDeleteModal.value = true;
};

const confirmDelete = async () => {
    if (!selectedSpace.value) return;
    isSaving.value = true;
    const res = await spacesStore.deleteSpace(selectedSpace.value.id_espacio);
    isSaving.value = false;
    if (res.success) {
        showDeleteModal.value = false;
        await spacesStore.fetchSpaces();
    } else {
        alert(res.error);
    }
};

const toggleDisciplina = (id) => {
    const index = newSpace.value.disciplinas.indexOf(id);
    if (index > -1) newSpace.value.disciplinas.splice(index, 1);
    else newSpace.value.disciplinas.push(id);
};

const saveNewSpace = async () => {
    if (!newSpace.value.nombre_espacio) {
        alert("El nombre es obligatorio.");
        return;
    }
    isSaving.value = true;
    const res = await spacesStore.createSpace(newSpace.value);
    isSaving.value = false;
    if (res.success) {
        showNewModal.value = false;
        newSpace.value = { 
            nombre_espacio: '', 
            capacidad_maxima: 10, 
            es_reserva_on_demand: true, 
            es_clase_programada: false, 
            es_uso_libre: false, 
            estatus: 'ACTIVO', 
            descripcion: '', 
            disciplinas: [] 
        };
    } else {
        alert(res.error);
    }
};
const getIcon = (name) => {
    if (!name) return IconDefault;
    const n = name.toLowerCase();
    if (n.includes('futbol')) return IconFutbol;
    if (n.includes('basquetbol')) return IconBasquetbol;
    if (n.includes('tenis') && !n.includes('padel') && !n.includes('squash')) return IconTenis;
    if (n.includes('voleibol')) return IconVoleibol;
    if (n.includes('squash')) return IconSquash;
    if (n.includes('frontenis')) return IconFrontenis;
    if (n.includes('padel')) return IconPadel;
    return IconDefault;
};
</script>

<template>
    <div class="admin-container p-6 bg-gray-50 min-h-screen">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900">Espacios Físicos</h1>
                <p class="text-gray-500">Gestiona las canchas, salones e instalaciones</p>
            </div>
            <button @click="showNewModal = true" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-indigo-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Nuevo Espacio
            </button>
        </header>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-8 flex flex-col md:flex-row gap-4 items-center">
            <div class="relative flex-1 w-full">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
                <input v-model="search" type="text" placeholder="Buscar por nombre..." class="w-full pl-12 pr-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all text-gray-700">
            </div>
            <div class="w-full md:w-64">
                <select v-model="filterTipo" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold text-gray-700">
                    <option value="TODOS">Todos los tipos</option>
                    <option value="RESERVA_ON_DEMAND">Reserva On Demand</option>
                    <option value="CLASE_PROGRAMADA">Clase Programada</option>
                    <option value="USO_LIBRE">Uso Libre</option>
                </select>
            </div>
            <div class="w-full md:w-64">
                <select v-model="filterStatus" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold text-gray-700">
                    <option value="TODOS">Todos los estados</option>
                    <option value="ACTIVO">Activo</option>
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
            <div v-for="space in filteredSpaces" :key="space.id_espacio" 
                 class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all p-6 flex flex-col group">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <span :class="{
                        'bg-emerald-50 text-emerald-600': space.estatus === 'ACTIVO',
                        'bg-amber-50 text-amber-600': space.estatus === 'MANTENIMIENTO',
                        'bg-red-50 text-red-600': space.estatus === 'DESHABILITADO',
                        'bg-gray-50 text-gray-500': space.estatus === 'INACTIVO'
                    }" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                        {{ space.estatus }}
                    </span>
                </div>
                
                <h3 class="text-xl font-bold text-gray-900 mb-1">{{ space.nombre_espacio }}</h3>
                <div class="flex gap-2 mb-3">
                    <span v-if="space.es_reserva_on_demand" class="text-[9px] font-bold bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-lg border border-indigo-100">ON DEMAND</span>
                    <span v-if="space.es_clase_programada" class="text-[9px] font-bold bg-blue-50 text-blue-600 px-2 py-0.5 rounded-lg border border-blue-100">CLASE</span>
                    <span v-if="space.es_uso_libre" class="text-[9px] font-bold bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-lg border border-emerald-100">USO LIBRE</span>
                </div>
                
                <div class="flex flex-wrap gap-1 mb-6">
                    <span v-for="d in space.disciplinas" :key="d.id_disciplina" class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded-md">
                        {{ d.nombre_disciplina }}
                    </span>
                    <span v-if="!space.disciplinas?.length" class="text-gray-400 text-xs italic">Sin disciplinas asignadas</span>
                </div>

                <div class="pt-4 border-t border-gray-50 flex items-center gap-2">
                    <!-- 1. Ver Detalles -->
                    <button @click="openDetails(space.id_espacio)" 
                            class="w-9 h-9 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                            title="Ver Detalles">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <path d="M14 2v6h6" />
                            <path d="M16 13H8" />
                            <path d="M16 17H8" />
                            <path d="M10 9H8" />
                        </svg>
                    </button>

                    <!-- 2. Editar -->
                    <button @click="openEdit(space.id_espacio)" 
                            class="w-9 h-9 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm"
                            title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                            <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                        </svg>
                    </button>

                    <!-- 3. Disciplinas -->
                    <button @click="goToDisciplines(space.id_espacio)" 
                            class="w-9 h-9 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all shadow-sm"
                            title="Gestionar Disciplinas">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                        </svg>
                    </button>

                    <!-- 4. Deshabilitar -->
                    <button @click="openDeleteModal(space)" 
                            class="w-9 h-9 bg-red-50 text-red-600 rounded-xl flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-sm"
                            title="Deshabilitar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- New Space Modal -->
        <div v-if="showNewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Nuevo Espacio</h2>
                        <button @click="showNewModal = false" class="text-gray-400 hover:text-gray-600 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Espacio</label>
                                <input v-model="newSpace.nombre_espacio" type="text" placeholder="Ej. Cancha de Tenis 1" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Capacidad Máxima</label>
                                <input v-model="newSpace.capacidad_maxima" type="number" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Configuración de Uso</label>
                                <div class="grid grid-cols-1 gap-2">
                                    <button @click="() => { newSpace.es_reserva_on_demand = !newSpace.es_reserva_on_demand; if(newSpace.es_reserva_on_demand) newSpace.es_uso_libre = false; }"
                                            :class="newSpace.es_reserva_on_demand ? 'bg-indigo-600 text-white' : 'bg-gray-50 text-gray-500'"
                                            class="flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold transition-all">
                                        <span>RESERVA ON DEMAND</span>
                                        <div :class="newSpace.es_reserva_on_demand ? 'bg-white text-indigo-600' : 'bg-gray-200 text-gray-400'" class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]">
                                            {{ newSpace.es_reserva_on_demand ? '✓' : '' }}
                                        </div>
                                    </button>
                                    <button @click="() => { newSpace.es_clase_programada = !newSpace.es_clase_programada; if(newSpace.es_clase_programada) newSpace.es_uso_libre = false; }"
                                            :class="newSpace.es_clase_programada ? 'bg-blue-600 text-white' : 'bg-gray-50 text-gray-500'"
                                            class="flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold transition-all">
                                        <span>CLASE PROGRAMADA</span>
                                        <div :class="newSpace.es_clase_programada ? 'bg-white text-blue-600' : 'bg-gray-200 text-gray-400'" class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]">
                                            {{ newSpace.es_clase_programada ? '✓' : '' }}
                                        </div>
                                    </button>
                                    <button @click="() => { newSpace.es_uso_libre = !newSpace.es_uso_libre; if(newSpace.es_uso_libre) { newSpace.es_reserva_on_demand = false; newSpace.es_clase_programada = false; } }"
                                            :class="newSpace.es_uso_libre ? 'bg-emerald-600 text-white' : 'bg-gray-50 text-gray-500'"
                                            class="flex items-center justify-between px-4 py-3 rounded-xl text-xs font-bold transition-all">
                                        <span>USO LIBRE</span>
                                        <div :class="newSpace.es_uso_libre ? 'bg-white text-emerald-600' : 'bg-gray-200 text-gray-400'" class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]">
                                            {{ newSpace.es_uso_libre ? '✓' : '' }}
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <label class="block text-sm font-bold text-gray-700">Disciplinas Permitidas</label>
                            <div class="h-64 overflow-y-auto bg-gray-50 rounded-2xl p-4 border border-gray-100">
                                <div class="grid grid-cols-2 gap-2">
                                    <button v-for="d in disciplines" :key="d.id_disciplina"
                                        @click="toggleDisciplina(d.id_disciplina)"
                                        :class="newSpace.disciplinas.includes(d.id_disciplina) ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-100 hover:bg-gray-100'"
                                        class="flex items-center gap-2 p-2 rounded-xl text-[10px] font-bold transition-all text-left">
                                        <div class="p-1.5 rounded-lg" :class="newSpace.disciplinas.includes(d.id_disciplina) ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-400'">
                                            <component :is="getIcon(d.nombre_disciplina)" class="w-4 h-4" />
                                        </div>
                                        <span class="truncate">{{ d.nombre_disciplina }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3">
                        <button @click="showNewModal = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-bold transition-all">
                            Cancelar
                        </button>
                        <button @click="saveNewSpace" :disabled="isSaving" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 disabled:opacity-50">
                            {{ isSaving ? 'Guardando...' : 'Crear Espacio' }}
                        </button>
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
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">¿Deshabilitar Espacio?</h3>
                    <p class="text-gray-500 mb-8" v-if="selectedSpace">¿Estás seguro de deshabilitar <b>{{ selectedSpace.nombre_espacio }}</b>? El sistema validará que no haya actividades pendientes.</p>
                    
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