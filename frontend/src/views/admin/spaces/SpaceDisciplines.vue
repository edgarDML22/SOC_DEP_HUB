<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { useSpacesStore } from '@/stores/admin/spaces';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'

// Import Icons
import IconFutbol from '@/components/icons/sports/IconFutbol.vue';
import IconBasquetbol from '@/components/icons/sports/IconBasquetbol.vue';
import IconTenis from '@/components/icons/sports/IconTenis.vue';
import IconVoleibol from '@/components/icons/sports/IconVoleibol.vue';
import IconSquash from '@/components/icons/sports/IconSquash.vue';
import IconFrontenis from '@/components/icons/sports/IconFrontenis.vue';
import IconPadel from '@/components/icons/sports/IconPadel.vue';
import IconDefault from '@/components/icons/sports/IconDefault.vue';

const route = useRoute();
const router = useRouter();
const spacesStore = useSpacesStore();
const toast = useToast();

const spaceId = route.params.id;
const space = ref(null);
const allDisciplines = ref([]);
const selectedDisciplines = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);

const statusOverlay = ref({
    show: false,
    type: 'success', // 'success' or 'error'
    message: ''
});

onMounted(async () => {
    await Promise.all([
        fetchSpace(),
        fetchAllDisciplines()
    ]);
    isLoading.value = false;
});

const fetchSpace = async () => {
    try {
        const data = await spacesStore.fetchSpaceDetails(spaceId);
        space.value = data;
        selectedDisciplines.value = data.disciplinas ? data.disciplinas.map(d => d.id_disciplina) : [];
    } catch (error) {
        console.error("Error fetching space:", error);
    }
};

const fetchAllDisciplines = async () => {
    try {
        const res = await api.get('/disciplinas/all');
        if (res.data.success) {
            allDisciplines.value = res.data.data;
        }
    } catch (error) {
        console.error("Error fetching all disciplines:", error);
    }
};

const toggleSelection = (id) => {
    const index = selectedDisciplines.value.indexOf(id);
    if (index > -1) {
        selectedDisciplines.value.splice(index, 1);
    } else {
        selectedDisciplines.value.push(id);
    }
};

const assignedDisciplinesList = computed(() => {
    return allDisciplines.value.filter(d => selectedDisciplines.value.includes(d.id_disciplina));
});

const availableDisciplinesList = computed(() => {
    return allDisciplines.value.filter(d => !selectedDisciplines.value.includes(d.id_disciplina));
});

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

const saveChanges = async () => {
    isSaving.value = true;
    try {
        const res = await spacesStore.updateSpace(spaceId, {
            disciplinas: selectedDisciplines.value
        });
        if (res.success) {
            statusOverlay.value = {
                show: true,
                type: 'success',
                message: 'Disciplinas del espacio actualizadas exitosamente'
            };
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cambios guardados', life: 3000 });

            setTimeout(() => {
                router.push({ name: 'spaces-details', params: { id: spaceId } });
            }, 1500);
        } else {
            statusOverlay.value = {
                show: true,
                type: 'error',
                message: res.error || 'Error al guardar cambios'
            };
            toast.add({ severity: 'error', summary: 'Error', detail: res.error || 'No se pudo guardar', life: 5000 });

            setTimeout(() => {
                statusOverlay.value.show = false;
            }, 2500);
        }
    } catch (error) {
        console.error("Error saving disciplines:", error);
        toast.add({ severity: 'error', summary: 'Error Crítico', detail: 'Error de conexión', life: 5000 });
    } finally {
        isSaving.value = false;
    }
};

const goBack = () => {
    router.push({ name: 'spaces-details', params: { id: spaceId } });
};
</script>

<template>
    <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-32 font-sans relative">
        <Toast />

        <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="statusOverlay.show" class="fixed inset-0 z-9999 flex items-center justify-center bg-white/80 backdrop-blur-md" :class="statusOverlay.type === 'success' ? 'text-primary-600' : 'text-red-600'">
                <div class="text-center animate-[bounce_0.5s]">
                    <div class="flex justify-center mb-6">
                        <svg v-if="statusOverlay.type === 'success'" xmlns="http://www.w3.org/2000/svg" class="w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="15" y1="9" x2="9" y2="15" />
                            <line x1="9" y1="9" x2="15" y2="15" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-extrabold">{{ statusOverlay.message }}</h2>
                </div>
            </div>
        </Transition>
        
        <header class="flex items-center gap-6 mb-8 max-w-7xl mx-auto">
            <button @click="goBack" class="w-11 h-11 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-700 hover:bg-surface-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </button>
            <div>
                <p class="text-xs text-surface-500 font-bold uppercase tracking-wider mb-1">Disciplinas del Espacio</p>
                <h1 class="text-3xl font-extrabold text-surface-900 m-0 leading-none" v-if="space">{{ space.nombre_espacio }}</h1>
            </div>
        </header>

        <section v-if="isLoading" class="flex flex-col items-center justify-center py-20 gap-4">
            <LoadingSpinner />
            <p class="text-surface-500 font-medium">Cargando disciplinas...</p>
        </section>

        <section v-else class="max-w-7xl mx-auto space-y-8">
            <div class="bg-primary-50/50 border border-primary-100 rounded-[2.5rem] p-8 lg:p-10 shadow-sm relative overflow-hidden">
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-primary-200/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>

                <div class="flex justify-between items-end mb-8 relative z-10">
                    <div>
                        <h3 class="text-2xl font-black text-primary-900 leading-tight">Disciplinas Permitidas</h3>
                        <p class="text-sm text-primary-600/80 font-bold mt-1">Estas son las disciplinas asignadas actualmente a este espacio.</p>
                    </div>
                    <div class="px-4 py-1.5 rounded-xl border border-primary-200 bg-white shadow-sm flex items-center gap-2">
                         <span class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></span>
                         <span class="text-sm font-black text-primary-700">{{ assignedDisciplinesList.length }} ASIGNADAS</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5 relative z-10">
                    <div v-for="d in assignedDisciplinesList" :key="d.id_disciplina"
                        @click="toggleSelection(d.id_disciplina)" 
                        class="cursor-pointer relative group">
                        <div class="bg-white border-2 border-primary-500 rounded-3xl p-6 flex flex-col items-center gap-4 h-full shadow-md group-hover:-translate-y-1 group-hover:shadow-lg transition-all duration-300">
                            <div class="w-16 h-16 bg-primary-600 text-white rounded-[1.25rem] flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                                <component :is="getIcon(d.nombre_disciplina)" class="w-8 h-8" />
                            </div>
                            <span class="text-sm font-black text-primary-900 text-center leading-tight">{{ d.nombre_disciplina }}</span>
                            
                            <div class="absolute -top-2 -right-2 bg-primary-600 text-white w-8 h-8 rounded-full flex items-center justify-center border-[3px] border-white shadow-sm group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-if="assignedDisciplinesList.length === 0" class="col-span-full py-12 text-center text-primary-600/60 bg-white rounded-3xl border-2 border-dashed border-primary-200 shadow-sm flex flex-col items-center justify-center gap-3">
                        <svg class="w-8 h-8 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span class="text-sm font-bold">No hay disciplinas asignadas. Selecciona una de abajo para agregarla.</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-surface-200 rounded-[2.5rem] p-8 lg:p-10 shadow-xl shadow-surface-200/30">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-surface-900 leading-tight">Otras Disciplinas Disponibles</h3>
                        <p class="text-sm text-surface-500 font-bold mt-1">Haz clic para permitir una nueva disciplina en este espacio.</p>
                    </div>
                    <div class="px-4 py-1.5 rounded-xl border border-surface-200 bg-surface-50 flex items-center gap-2">
                         <span class="text-sm font-black text-surface-600">{{ availableDisciplinesList.length }} DISPONIBLES</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
                    <div v-for="d in availableDisciplinesList" :key="d.id_disciplina"
                        @click="toggleSelection(d.id_disciplina)" 
                        class="cursor-pointer group relative">
                        <div class="bg-surface-50 border border-surface-200 rounded-3xl p-6 flex flex-col items-center gap-4 h-full group-hover:bg-primary-50 group-hover:border-primary-300 group-hover:-translate-y-1 group-hover:shadow-md transition-all duration-300">
                            <div class="w-16 h-16 bg-white text-surface-400 border border-surface-200 shadow-sm rounded-[1.25rem] flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white group-hover:border-primary-600 transition-all duration-300 group-hover:scale-110">
                                <component :is="getIcon(d.nombre_disciplina)" class="w-8 h-8 opacity-80 group-hover:opacity-100" />
                            </div>
                            <span class="text-sm font-bold text-surface-500 text-center leading-tight group-hover:text-primary-800 transition-colors">{{ d.nombre_disciplina }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-xl px-8 py-5 flex justify-end gap-4 border-t border-surface-200 z-50">
                <CancelButton @click="goBack" />
                <ConfirmButton
                    label="Guardar Cambios"
                    :loading="isSaving"
                    @click="saveChanges"
                />
            </div>
        </section>
    </main>
</template>


