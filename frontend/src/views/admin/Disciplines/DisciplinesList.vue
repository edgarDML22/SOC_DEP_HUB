<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useDisciplinesStore } from '@/stores/admin/disciplines';

const router = useRouter();
const disciplinesStore = useDisciplinesStore();
const { disciplines, isLoading } = storeToRefs(disciplinesStore);

const search = ref('');
const showNewModal = ref(false);
const isSaving = ref(false);

const newDiscipline = ref({
    nombre_disciplina: '',
    categoria_disciplina: '',
    descripcion: '',
    estatus: 'ACTIVO'
});

onMounted(() => {
    disciplinesStore.fetchDisciplines();
});

const filteredDisciplines = computed(() => {
    if (!search.value) return disciplines.value;
    const q = search.value.toLowerCase();
    return disciplines.value.filter(d => 
        d.nombre_disciplina.toLowerCase().includes(q) || 
        d.categoria_disciplina.toLowerCase().includes(q)
    );
});

const openDetails = (id) => {
    router.push({ name: 'disciplines-details', params: { id } });
};

const saveNewDiscipline = async () => {
    if (!newDiscipline.value.nombre_disciplina || !newDiscipline.value.categoria_disciplina) {
        alert("Nombre y categoría son obligatorios.");
        return;
    }
    isSaving.value = true;
    const res = await disciplinesStore.createDiscipline(newDiscipline.value);
    isSaving.value = false;
    if (res.success) {
        showNewModal.value = false;
        newDiscipline.value = { nombre_disciplina: '', categoria_disciplina: '', descripcion: '', estatus: 'ACTIVO' };
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
            <button @click="showNewModal = true" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-lg shadow-indigo-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Nueva Disciplina
            </button>
        </header>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-8 flex flex-col md:flex-row gap-4 items-center">
            <div class="relative flex-1 w-full">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
                <input v-model="search" type="text" placeholder="Buscar por nombre o categoría..." class="w-full pl-12 pr-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all text-gray-700">
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
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" x2="6" y1="1" y2="4"/><line x1="10" x2="10" y1="1" y2="4"/><line x1="14" x2="14" y1="1" y2="4"/></svg>
                    </div>
                    <span :class="{
                        'bg-emerald-50 text-emerald-600': discipline.estatus === 'ACTIVO',
                        'bg-gray-50 text-gray-500': discipline.estatus !== 'ACTIVO'
                    }" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                        {{ discipline.estatus }}
                    </span>
                </div>
                
                <h3 class="text-xl font-bold text-gray-900 mb-1">{{ discipline.nombre_disciplina }}</h3>
                <p class="text-indigo-600 font-semibold text-sm mb-3 uppercase tracking-wide">{{ discipline.categoria_disciplina }}</p>
                <p class="text-gray-500 text-sm line-clamp-2 mb-6 flex-grow">{{ discipline.descripcion || 'Sin descripción disponible.' }}</p>

                <div class="pt-4 border-t border-gray-50 flex gap-2">
                    <button @click="openDetails(discipline.id_disciplina)" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 py-2.5 rounded-xl font-bold transition-all text-sm">
                        Ver Detalles
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!isLoading && filteredDisciplines.length === 0" class="text-center py-20">
            <p class="text-gray-400 font-medium">No se encontraron disciplinas que coincidan con tu búsqueda.</p>
        </div>

        <!-- New Discipline Modal -->
        <div v-if="showNewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Nueva Disciplina</h2>
                        <button @click="showNewModal = false" class="text-gray-400 hover:text-gray-600 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nombre de la Disciplina</label>
                            <input v-model="newDiscipline.nombre_disciplina" type="text" placeholder="Ej. Tennis, Natación..." class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Categoría</label>
                            <input v-model="newDiscipline.categoria_disciplina" type="text" placeholder="Ej. Deportes de Raqueta" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                            <textarea v-model="newDiscipline.descripcion" rows="3" placeholder="Breve descripción de la actividad..." class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3">
                        <button @click="showNewModal = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-bold transition-all">
                            Cancelar
                        </button>
                        <button @click="saveNewDiscipline" :disabled="isSaving" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 disabled:opacity-50">
                            {{ isSaving ? 'Guardando...' : 'Crear Disciplina' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;  
  overflow: hidden;
}
</style>
