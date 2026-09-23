<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSocioStore } from '@/stores/admin/socioStore';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import { useformat } from '@/utils/formatters';

const route = useRoute();
const router = useRouter();
const socioId = route.params.id;
const { formatText, dateFormat } = useformat();

const socioStore = useSocioStore();
const { fetchSocioDetails } = socioStore;

// Usar propiedad computada vinculada al store para máxima reactividad
const socio = computed(() => socioStore.getSocioById(socioId));

const isLoading = ref(true);
const errorMsg = ref('');

onMounted(async () => {
    if (socioStore.currentSocio && String(socioStore.currentSocio.id_socio) === String(socioId)) {
        isLoading.value = false;
    }
    
    try {
        await fetchSocioDetails(socioId);
    } catch (error) {
        console.error("Error al cargar socio:", error);
        errorMsg.value = "Hubo un problema al cargar los detalles de este socio.";
    } finally {
        isLoading.value = false;
    }
});

const AVATAR_GRADIENTS = [
    'from-primary-400 to-primary-600',
    'from-emerald-400 to-emerald-600',
    'from-purple-400 to-purple-600',
    'from-orange-400 to-orange-600',
    'from-rose-400 to-rose-600',
    'from-cyan-400 to-cyan-600',
]
const avatarGradient = (name = '') => {
    const idx = (name.charCodeAt(0) ?? 0) % AVATAR_GRADIENTS.length
    return AVATAR_GRADIENTS[idx]
}
const initials = (name = '') => {
    const parts = name.trim().split(' ').filter(Boolean)
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
    return (parts[0]?.[0] ?? '?').toUpperCase()
}

const goBack = () => {
    router.push('/admin/socios');
};
</script>

<template>
    <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-20 font-sans">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Navegación Superior -->
            <header class="flex items-center gap-4 mb-8">
                <button @click="goBack"
                    class="w-11 h-11 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 hover:text-primary-600 transition-all shadow-sm group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </button>
                <h1 class="text-2xl font-black text-surface-900 tracking-tight">Detalles del Socio Titular</h1>
            </header>

            <section v-if="errorMsg"
                class="text-center p-12 bg-red-50 rounded-2xl border border-red-100 animate-scale-in">
                <p class="text-red-600 font-bold">{{ errorMsg }}</p>
                <button @click="goBack"
                    class="mt-4 px-6 py-2.5 bg-white border border-red-200 text-red-600 rounded-xl font-bold hover:bg-red-50 transition-colors shadow-sm">Volver
                    al listado</button>
            </section>

            <section v-if="isLoading" class="flex flex-col items-center justify-center p-20">
                <LoadingSpinner />
                <p class="text-sm font-extrabold uppercase tracking-widest text-surface-400 mt-4">Cargando detalles...
                </p>
            </section>

            <!-- Contenido Detallado -->
            <Transition enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 translate-y-4 scale-[0.98]"
                enter-to-class="opacity-100 translate-y-0 scale-100">
                <section v-if="socio && !isLoading">
                    <!-- Tarjeta Principal Visual (Estilo Premium Instructor) -->
                    <article class="bg-white rounded-[2.5rem] shadow-xl shadow-surface-200/40 border border-surface-200 overflow-hidden relative">
                        <!-- Accent Bar Dinámica -->
                        <div class="absolute top-0 left-0 right-0 h-2" :class="{
                            'bg-linear-to-r from-green-500 to-green-400': socio.estatus_cuenta === 'AL_CORRIENTE',
                            'bg-linear-to-r from-amber-500 to-amber-400': socio.estatus_cuenta === 'MOROSO',
                            'bg-linear-to-r from-red-500 to-red-400': socio.estatus_cuenta === 'SUSPENDIDO',
                            'bg-linear-to-r from-primary-600 to-primary-400': socio.estatus_cuenta && socio.estatus_cuenta.startsWith('PENALIZADO')
                        }"></div>

                        <div class="p-8 lg:p-10">
                            <!-- Cabecera -->
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
                                <div class="flex items-center gap-5">
                                    <!-- Avatar Grande -->
                                    <div class="w-16 h-16 rounded-2xl bg-linear-to-br flex items-center justify-center
                                             text-white font-black text-2xl shadow-md shrink-0"
                                        :class="avatarGradient(socio.nombre_completo)">
                                        {{ initials(socio.nombre_completo) }}
                                    </div>
                                    <div>
                                        <h2 class="text-3xl font-black text-surface-900 leading-tight mb-2">{{ socio.nombre_completo }}</h2>
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 text-surface-500 font-medium">
                                            <p class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-primary-500" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                                    <polyline points="22,6 12,13 2,6" />
                                                </svg>
                                                {{ socio.correo_electronico || 'Sin correo registrado' }}
                                            </p>
                                            <p class="flex items-center gap-2 text-surface-600 font-bold bg-surface-100 px-3 py-1 rounded-lg">
                                                <svg class="w-4 h-4 text-surface-400" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <rect x="2" y="4" width="20" height="16" rx="2" ry="2"/>
                                                    <line x1="6" y1="8" x2="6" y2="8"/>
                                                    <line x1="10" y1="8" x2="18" y2="8"/>
                                                    <line x1="6" y1="12" x2="6" y2="12"/>
                                                    <line x1="10" y1="12" x2="18" y2="12"/>
                                                    <line x1="6" y1="16" x2="6" y2="16"/>
                                                    <line x1="10" y1="16" x2="18" y2="16"/>
                                                </svg>
                                                #{{ socio.numero_accion }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2 shrink-0">
                                    <!-- Estatus Premium -->
                                    <span class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest shadow-sm border"
                                        :class="{
                                            'bg-green-50 text-green-700 border-green-200': socio.estatus_cuenta === 'AL_CORRIENTE',
                                            'bg-amber-50 text-amber-700 border-amber-200': socio.estatus_cuenta === 'MOROSO',
                                            'bg-red-50 text-red-700 border-red-200': socio.estatus_cuenta === 'SUSPENDIDO',
                                            'bg-primary-50 text-primary-700 border-primary-200': socio.estatus_cuenta && socio.estatus_cuenta.startsWith('PENALIZADO')
                                        }">
                                        {{ socio.estatus_cuenta ? socio.estatus_cuenta.replace('_', ' ') : 'S/E' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Información General -->
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Tipo Socio</span>
                                    <span class="text-base font-bold text-surface-900">{{ socio.tipo_socio }}</span>
                                </div>
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Modalidad</span>
                                    <span class="text-base font-bold text-surface-900">{{ socio.modalidad_plan }}</span>
                                </div>
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">Género</span>
                                    <div>
                                        <span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-bold uppercase tracking-wider"
                                            :class="{
                                                'bg-blue-50 text-blue-700 border border-blue-200': socio.genero === 'M',
                                                'bg-pink-50 text-pink-700 border border-pink-200': socio.genero === 'F',
                                                'bg-surface-100 text-surface-600 border border-surface-200': socio.genero !== 'M' && socio.genero !== 'F'
                                            }">
                                            {{ socio.genero === 'M' ? 'Masculino' : socio.genero === 'F' ? 'Femenino' : 'Otro' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100 flex flex-col gap-1.5">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-surface-500">F. Nacimiento</span>
                                    <span class="text-base font-bold text-surface-900">{{ dateFormat(socio.fecha_nacimiento) || 'N/A' }}</span>
                                </div>
                            </div>

                            <!-- KPIs Incidencias -->
                            <div class="mt-8 pt-8 border-t border-surface-100">
                                <h3 class="text-sm font-black uppercase tracking-widest text-surface-400 mb-6">Métricas y Penalizaciones</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- KPI: No Shows -->
                                    <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden flex flex-col">
                                        <div class="h-1.5 w-full transition-all duration-500" :class="socio.contador_no_shows > 0 ? 'bg-red-500' : 'bg-surface-200'" />
                                        <div class="p-6 flex items-center justify-between flex-1 bg-surface-50/30">
                                            <div>
                                                <p class="text-xs font-black uppercase tracking-widest text-surface-500 mb-1">No Shows</p>
                                                <p class="text-4xl font-black leading-none transition-colors" :class="socio.contador_no_shows > 0 ? 'text-red-600' : 'text-surface-900'">
                                                    {{ socio.contador_no_shows }}
                                                </p>
                                                <p class="text-xs font-semibold mt-2" :class="socio.contador_no_shows > 0 ? 'text-red-500 font-bold' : 'text-surface-400'">
                                                    {{ socio.contador_no_shows > 0 ? 'Reservas canceladas sin aviso' : 'Sin incidencias' }}
                                                </p>
                                            </div>
                                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 transition-colors shadow-inner"
                                                :class="socio.contador_no_shows > 0 ? 'bg-red-50 text-red-500' : 'bg-white text-surface-300 border border-surface-200'">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-7 h-7">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                                    <line x1="16" y1="2" x2="16" y2="6" />
                                                    <line x1="8" y1="2" x2="8" y2="6" />
                                                    <line x1="3" y1="10" x2="21" y2="10" />
                                                    <line x1="9" y1="15" x2="15" y2="15" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- KPI: Retrasos Ludoteca -->
                                    <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden flex flex-col">
                                        <div class="h-1.5 w-full transition-all duration-500" :class="socio.retrasos_ludoteca > 0 ? 'bg-amber-500' : 'bg-surface-200'" />
                                        <div class="p-6 flex items-center justify-between flex-1 bg-surface-50/30">
                                            <div>
                                                <p class="text-xs font-black uppercase tracking-widest text-surface-500 mb-1">Retrasos Ludoteca</p>
                                                <p class="text-4xl font-black leading-none transition-colors" :class="socio.retrasos_ludoteca > 0 ? 'text-amber-600' : 'text-surface-900'">
                                                    {{ socio.retrasos_ludoteca }}
                                                </p>
                                                <p class="text-xs font-semibold mt-2" :class="socio.retrasos_ludoteca > 0 ? 'text-amber-500 font-bold' : 'text-surface-400'">
                                                    {{ socio.retrasos_ludoteca > 0 ? 'Incidencias de puntualidad' : 'Sin incidencias' }}
                                                </p>
                                            </div>
                                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 transition-colors shadow-inner"
                                                :class="socio.retrasos_ludoteca > 0 ? 'bg-amber-50 text-amber-500' : 'bg-white text-surface-300 border border-surface-200'">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-7 h-7">
                                                    <circle cx="12" cy="12" r="10" />
                                                    <polyline points="12 6 12 12 16 14" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pie de tarjeta -->
                            <div class="mt-8 pt-6 border-t border-surface-100 flex justify-end">
                                <button @click="goBack" class="w-full sm:w-auto px-6 py-3 rounded-xl border-2 border-surface-200 bg-white
                                         text-sm font-bold text-surface-700 hover:bg-surface-50 hover:border-surface-300 transition-colors shadow-sm">
                                    Volver al listado
                                </button>
                            </div>
                        </div>
                    </article>
                </section>
            </Transition>
        </div>
    </main>
</template>
