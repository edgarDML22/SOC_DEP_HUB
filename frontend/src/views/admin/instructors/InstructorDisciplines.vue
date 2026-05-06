<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import { useInstructorStore } from '@/stores/admin/instructorStore';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'

// Import Icons
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
const instructorStore = useInstructorStore();
const toast = useToast();

const instructorId = route.params.id;
const instructor = ref(null);
// Usamos las disciplinas directamente del store para evitar problemas de reactividad con storeToRefs
const disciplinesCatalog = computed(() => instructorStore.disciplinesCatalog || []);
const selectedDisciplines = ref([]);
const isLoading = ref(true);
const isSaving = ref(false);
const error = ref(null);

const statusOverlay = ref({
    show: false,
    type: 'success', // 'success' or 'error'
    message: ''
});


onMounted(async () => {
    isLoading.value = true;
    try {
        // Cargamos en paralelo para optimizar tiempo
        await Promise.all([
            fetchInstructor(),
            instructorStore.fetchDisciplinesCatalogAction()
        ]);
    } catch (err) {
        console.error("Error initializing disciplines view:", err);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los datos iniciales.', life: 3000 });
    } finally {
        isLoading.value = false;
    }
});

const fetchInstructor = async () => {
    try {
        const data = await instructorStore.fetchInstructorDetails(instructorId);
        instructor.value = data;
        selectedDisciplines.value = data.disciplinas ? data.disciplinas.map(d => d.id_disciplina) : [];
    } catch (error) {
        console.error("Error fetching instructor:", error);
    }
};

const toggleSelection = async (id) => {
    const index = selectedDisciplines.value.indexOf(id);
    if (index > -1) {
        // Attempt to remove via API first
        try {
            const res = await api.delete(`/instructores/${instructorId}/disciplinas/${id}`);
            if (res.data.success) {
                selectedDisciplines.value.splice(index, 1);
            } else {
                showBlockedOverlay(res.data.message || 'La disciplina está bloqueada y no se puede remover.');
            }
        } catch (error) {
            if (error.response && error.response.status === 422) {
                showBlockedOverlay(error.response.data.message || 'La disciplina está bloqueada por actividades asignadas.');
            } else {
                toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo remover la disciplina.', life: 3000 });
            }
        }
    } else {
        selectedDisciplines.value.push(id);
    }
};

const showBlockedOverlay = (message) => {
    statusOverlay.value = {
        show: true,
        type: 'error',
        message: message
    };
    setTimeout(() => {
        statusOverlay.value.show = false;
    }, 3000);
};

const assignedDisciplinesList = computed(() => {
    const catalog = disciplinesCatalog.value || [];
    const selected = selectedDisciplines.value || [];
    return catalog.filter(d => selected.includes(d.id_disciplina));
});

const availableDisciplinesList = computed(() => {
    const catalog = disciplinesCatalog.value || [];
    const selected = selectedDisciplines.value || [];
    return catalog.filter(d => !selected.includes(d.id_disciplina));
});

const getIcon = (name) => {
    if (!name) return IconDefault;
    const n = name.toLowerCase();
    if (n.includes('futbol')) return IconFutbol;
    if (n.includes('basquetbol') || n.includes('baloncesto')) return IconBasquetbol;
    if (n.includes('tenis') && !n.includes('padel') && !n.includes('squash')) return IconTenis;
    if (n.includes('voleibol')) return IconVoleibol;
    if (n.includes('squash')) return IconSquash;
    if (n.includes('frontenis')) return IconFrontenis;
    if (n.includes('padel')) return IconPadel;
    if (n.includes('natacion') || n.includes('acuatic') || n.includes('alberca')) return IconNatacion;
    if (n.includes('yoga') || n.includes('pilates') || n.includes('meditacion')) return IconYoga;
    if (n.includes('pesas') || n.includes('acondicionamiento') || n.includes('crossfit') || n.includes('funcional') || n.includes('gym')) return IconPesas;
    if (n.includes('marciales') || n.includes('karate') || n.includes('taekwondo') || n.includes('box')) return IconArtesMarciales;
    if (n.includes('gimnasia')) return IconGimnasia;
    if (n.includes('baile') || n.includes('zumba') || n.includes('aerobics') || n.includes('jazz') || n.includes('barre')) return IconBaile;
    if (n.includes('spinning')) return IconSpinning;
    if (n.includes('columna') || n.includes('higiene')) return IconColumna;
    return IconDefault;
};

const saveChanges = async () => {
    isSaving.value = true;
    try {
        const res = await instructorStore.updateInstructor(instructorId, {
            disciplinas: selectedDisciplines.value
        });
        if (res.success) {
            statusOverlay.value = {
                show: true,
                type: 'success',
                message: 'Disciplinas actualizadas exitosamente'
            };
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cambios guardados', life: 3000 });

            setTimeout(() => {
                router.push('/admin/instructors');
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
    router.push('/admin/instructors');
};
</script>

<template>
    <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-32 font-sans relative">
        <Toast />

        <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-all duration-200" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="statusOverlay.show"
                class="fixed inset-0 z-9999 flex items-center justify-center bg-white/80 backdrop-blur-md"
                :class="statusOverlay.type === 'success' ? 'text-primary-600' : 'text-red-600'">
                <div class="text-center animate-[bounce_0.5s]">
                    <div class="flex justify-center mb-6">
                        <svg v-if="statusOverlay.type === 'success'" xmlns="http://www.w3.org/2000/svg"
                            class="w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-16 h-16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
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
            <button @click="goBack"
                class="w-11 h-11 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-700 hover:bg-surface-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </button>
            <div>
                <p class="text-xs text-surface-500 font-bold uppercase tracking-wider mb-1">Gestión de Disciplinas</p>
                <h1 class="text-3xl font-extrabold text-surface-900 m-0 leading-none" v-if="instructor">{{
                    instructor.nombre_completo }}</h1>
            </div>
        </header>

        <section v-if="isLoading" class="flex flex-col items-center justify-center py-20 gap-4">
            <LoadingSpinner />
            <p class="text-surface-500 font-medium">Cargando disciplinas...</p>
        </section>

        <section v-else class="max-w-7xl mx-auto space-y-8">
            <div class="bg-primary-50/30 border border-primary-200 rounded-4xl p-6 lg:p-8 shadow-sm">
                <div class="flex justify-between items-end mb-6">
                    <div>
                        <h3 class="text-xl font-extrabold text-primary-900">Disciplinas que Imparte</h3>
                        <p class="text-sm text-primary-600/70 font-medium">Estas son las disciplinas asignadas
                            actualmente.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-primary-100 text-primary-700">{{
                        assignedDisciplinesList.length }}</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <div v-for="d in assignedDisciplinesList" :key="d.id_disciplina"
                        @click="toggleSelection(d.id_disciplina)"
                        class="cursor-pointer relative transform transition-transform hover:-translate-y-1">
                        <div
                            class="bg-white border-2 border-primary-500 rounded-3xl p-6 flex flex-col items-center justify-center gap-4 h-full shadow-lg">
                            <div
                                class="w-16 h-16 bg-primary-600 text-white rounded-2xl flex items-center justify-center shadow-md">
                                <component :is="getIcon(d.nombre_disciplina)" class="w-10 h-10" />
                            </div>
                            <span class="text-base font-extrabold text-primary-950 text-center leading-tight">{{
                                d.nombre_disciplina }}</span>
                            <div
                                class="absolute -top-3 -right-3 bg-primary-600 text-white w-8 h-8 rounded-full flex items-center justify-center border-4 border-white shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-if="assignedDisciplinesList.length === 0"
                        class="col-span-full py-12 text-center text-primary-600/60 font-medium text-sm bg-white rounded-3xl border-2 border-dashed border-primary-200">
                        No hay disciplinas asignadas. Selecciona una de abajo para agregarla.
                    </div>
                </div>
            </div>

            <div class="bg-white border border-surface-200 rounded-4xl p-6 lg:p-8 shadow-sm">
                <div class="flex justify-between items-end mb-6">
                    <div>
                        <h3 class="text-xl font-extrabold text-surface-700">Otras Disciplinas Disponibles</h3>
                        <p class="text-sm text-surface-500 font-medium">Haz clic para agregar una nueva disciplina.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-surface-100 text-surface-600">{{
                        availableDisciplinesList.length }}</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <div v-for="d in availableDisciplinesList" :key="d.id_disciplina"
                        @click="toggleSelection(d.id_disciplina)"
                        class="cursor-pointer group relative transform transition-transform hover:-translate-y-1">
                        <div
                            class="bg-surface-50 border border-surface-200 rounded-3xl p-6 flex flex-col items-center justify-center gap-4 h-full group-hover:bg-primary-50 group-hover:border-primary-300 transition-colors shadow-sm">
                            <div
                                class="w-16 h-16 bg-surface-100 text-surface-400 rounded-2xl flex items-center justify-center group-hover:bg-primary-100 group-hover:text-primary-600 transition-colors shadow-inner">
                                <component :is="getIcon(d.nombre_disciplina)"
                                    class="w-10 h-10 opacity-70 group-hover:opacity-100" />
                            </div>
                            <span
                                class="text-base font-bold text-surface-600 text-center leading-tight group-hover:text-primary-800 transition-colors">{{
                                    d.nombre_disciplina }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-xl px-8 py-5 flex justify-end gap-4 border-t border-surface-200 z-30 shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.1)]">
                <CancelButton @click="goBack">
                    <template #icon>
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </template>
                </CancelButton>
                <ConfirmButton label="Guardar Cambios" :loading="isSaving" @click="saveChanges">
                    <template #icon>
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                    </template>
                </ConfirmButton>
            </div>
        </section>
    </main>
</template>
