<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useSpacesStore } from '@/stores/admin/spaces';
import { useDisciplinesStore } from '@/stores/admin/disciplines';

const router = useRouter();
const spacesStore = useSpacesStore();
const disciplinesStore = useDisciplinesStore();
const { spaces, isLoading } = storeToRefs(spacesStore);
const { disciplines } = storeToRefs(disciplinesStore);

const search = ref('');
const filterTipo = ref('TODOS');
const filterStatus = ref('TODOS');
const showNewModal = ref(false);
const isSaving = ref(false);

const newSpace = ref({
    nombre_espacio: '',
    capacidad_maxima: 10,
    tipo_espacio: 'RESERVA_ON_DEMAND',
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
        result = result.filter(s => s.tipo_espacio === filterTipo.value);
    }
    if (filterStatus.value !== 'TODOS') {
        result = result.filter(s => s.estatus === filterStatus.value);
    }

    // Ordenar: primero por tipo_espacio, luego por estatus
    result.sort((a, b) => {
        const typeCompare = a.tipo_espacio.localeCompare(b.tipo_espacio);
        if (typeCompare !== 0) return typeCompare;
        
        return a.estatus.localeCompare(b.estatus);
    });

    return result;
});

const openDetails = (id) => {
    router.push({ name: 'spaces-details', params: { id } });
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
        newSpace.value = { nombre_espacio: '', capacidad_maxima: 10, tipo_espacio: 'RESERVA_ON_DEMAND', estatus: 'ACTIVO', descripcion: '', disciplinas: [] };
    } else {
        alert(res.error);
    }
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
                <p class="text-indigo-600 font-semibold text-sm mb-3 uppercase tracking-wide">{{ space.tipo_espacio.replace(/_/g, ' ') }}</p>
                
                <div class="flex flex-wrap gap-1 mb-6">
                    <span v-for="d in space.disciplinas" :key="d.id_disciplina" class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded-md">
                        {{ d.nombre_disciplina }}
                    </span>
                    <span v-if="!space.disciplinas?.length" class="text-gray-400 text-xs italic">Sin disciplinas asignadas</span>
                </div>

                <div class="pt-4 border-t border-gray-50 flex gap-2">
                    <button @click="openDetails(space.id_espacio)" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 py-2.5 rounded-xl font-bold transition-all text-sm">
                        Ver Detalles
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
                                <label class="block text-sm font-bold text-gray-700 mb-1">Tipo de Espacio</label>
                                <select v-model="newSpace.tipo_espacio" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold">
                                    <option value="RESERVA_ON_DEMAND">RESERVA ON DEMAND</option>
                                    <option value="CLASE_PROGRAMADA">CLASE PROGRAMADA</option>
                                </select>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <label class="block text-sm font-bold text-gray-700">Disciplinas Permitidas</label>
                            <div class="h-48 overflow-y-auto bg-gray-50 rounded-xl p-4 border border-gray-100 flex flex-wrap gap-2">
                                <button v-for="d in disciplines" :key="d.id_disciplina"
                                    @click="toggleDisciplina(d.id_disciplina)"
                                    :class="newSpace.disciplinas.includes(d.id_disciplina) ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
                                    {{ d.nombre_disciplina }}
                                </button>
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
    </div>
</template>