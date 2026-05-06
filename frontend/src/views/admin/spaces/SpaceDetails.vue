<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useSpacesStore } from '@/stores/admin/spaces';
import { useDisciplinesStore } from '@/stores/admin/disciplines';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'

// Iconos de Deportes
import IconFutbol from '@/components/icons/disciplines/IconFutbol.vue';
import IconBasquetbol from '@/components/icons/disciplines/IconBasquetbol.vue';
import IconTenis from '@/components/icons/disciplines/IconTenis.vue';
import IconVoleibol from '@/components/icons/disciplines/IconVoleibol.vue';
import IconSquash from '@/components/icons/disciplines/IconSquash.vue';
import IconFrontenis from '@/components/icons/disciplines/IconFrontenis.vue';
import IconPadel from '@/components/icons/disciplines/IconPadel.vue';
import IconDefault from '@/components/icons/disciplines/IconDefault.vue';

const route = useRoute();
const router = useRouter();
const spacesStore = useSpacesStore();
const disciplinesStore = useDisciplinesStore();
const { disciplines } = storeToRefs(disciplinesStore);

const space = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const isEditing = ref(false);

const isEditable = computed(() => {
    return space.value?.estatus === 'MANTENIMIENTO' || space.value?.estatus === 'DESHABILITADO';
});

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

const goBack = () => router.push({ name: 'spaces-list' });

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
                <LoadingSpinner />
                <p class="text-sm font-extrabold uppercase tracking-widest text-surface-400 mt-4">Cargando detalles...</p>
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
                                    <div class="p-5 bg-emerald-50 text-emerald-600 rounded-2xl shadow-sm border border-emerald-100 shrink-0 flex items-center justify-center">
                                        <component :is="getIcon(space.nombre_espacio)" class="w-8 h-8" />
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
                                    <button @click="toggleEdit" class="px-5 py-2.5 rounded-xl font-bold transition-colors text-sm flex items-center gap-2 border shadow-sm" :class="isEditing ? 'bg-surface-100 text-surface-700 border-surface-300 hover:bg-surface-200' : 'bg-primary-50 text-primary-700 hover:bg-primary-600 hover:text-white border-primary-200 hover:border-primary-600'">
                                        <svg v-if="!isEditing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        {{ isEditing ? 'Cancelar Edición' : 'Editar Información' }}
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
                            </div>

                            <!-- Edit Form -->
                            <div v-else class="space-y-6 animate-in slide-in-from-top duration-300 border-t border-surface-100 pt-8 mt-8">
                                <div v-if="!isEditable" class="p-4 bg-amber-50 border border-amber-200 rounded-xl flex gap-3 text-amber-800 text-sm font-semibold mb-6">
                                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    La información principal solo puede editarse cuando el espacio está en MANTENIMIENTO o DESHABILITADO para evitar conflictos.
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8" :class="{'opacity-60 pointer-events-none': !isEditable}">
                                    <div class="space-y-6 col-span-1 md:col-span-2">
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Nombre</label>
                                            <input v-model="editForm.nombre_espacio" type="text" class="w-full px-4 py-3.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none">
                                        </div>
                                        <div class="grid grid-cols-2 gap-6">
                                            <div class="space-y-1.5">
                                                <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Capacidad</label>
                                                <input v-model="editForm.capacidad_maxima" type="number" class="w-full px-4 py-3.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-bold text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all outline-none">
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
                                </div>
                                <div class="flex justify-end gap-3 pt-6" v-if="isEditable">
                                    <ConfirmButton
                                        label="Guardar Cambios"
                                        :loading="isSaving"
                                        @click="handleUpdate"
                                    />
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </Transition>
        </div>
    </main>
</template>
