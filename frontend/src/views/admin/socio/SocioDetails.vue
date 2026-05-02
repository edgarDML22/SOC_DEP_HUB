<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSocioStore } from '@/stores/admin/socioStore';

const route = useRoute();
const router = useRouter();
const socioId = route.params.id;

const socioStore = useSocioStore();
const { fetchSocioDetails } = socioStore;

// Usar propiedad computada vinculada al store para máxima reactividad
const socio = computed(() => socioStore.getSocioById(socioId));

const isLoading = ref(true);
const errorMsg = ref('');

onMounted(async () => {
    try {
        await fetchSocioDetails(socioId);
    } catch (error) {
        console.error("Error al cargar socio:", error);
        errorMsg.value = "Hubo un problema al cargar los detalles de este socio.";
    } finally {
        isLoading.value = false;
    }
});

const goBack = () => {
    router.push('/admin/socios');
};
</script>

<template>
    <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-20 font-sans">
        <div class="max-w-4xl mx-auto space-y-6">
            
            <!-- Navegación Superior -->
            <header class="flex items-center gap-4 mb-8">
                <button @click="goBack" class="w-11 h-11 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 hover:text-primary-600 transition-all shadow-sm group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </button>
                <h1 class="text-2xl font-black text-surface-900 tracking-tight">Detalles del Socio Titular</h1>
            </header>

            <section v-if="errorMsg" class="text-center p-12 bg-red-50 rounded-[2rem] border border-red-100 animate-scale-in">
                <p class="text-red-600 font-bold">{{ errorMsg }}</p>
                <button @click="goBack" class="mt-4 px-6 py-2.5 bg-white border border-red-200 text-red-600 rounded-xl font-bold hover:bg-red-50 transition-colors shadow-sm">Volver al listado</button>
            </section>

            <section v-if="isLoading" class="flex flex-col items-center justify-center p-20">
                <div class="w-12 h-12 border-4 border-surface-200 border-t-primary-600 rounded-full animate-spin mb-4"></div>
                <p class="text-sm font-extrabold uppercase tracking-widest text-surface-400">Cargando detalles...</p>
            </section>

            <!-- Contenido Detallado -->
            <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 translate-y-4 scale-[0.98]" enter-to-class="opacity-100 translate-y-0 scale-100">
                <section v-if="socio && !isLoading">
                    <article class="bg-white rounded-[2.5rem] shadow-xl shadow-surface-200/40 border border-surface-200 overflow-hidden relative">
                        <!-- Accent Bar -->
                        <div class="absolute top-0 left-0 right-0 h-2" :class="{
                            'bg-green-500': socio.estatus_cuenta === 'AL_CORRIENTE',
                            'bg-amber-500': socio.estatus_cuenta === 'MOROSO',
                            'bg-red-500': socio.estatus_cuenta === 'SUSPENDIDO',
                            'bg-primary-500': socio.estatus_cuenta && socio.estatus_cuenta.startsWith('PENALIZADO')
                        }"></div>

                        <div class="p-8 lg:p-10">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                                <div>
                                    <h2 class="text-3xl font-black text-surface-900 leading-tight mb-2">{{ socio.nombre_completo }}</h2>
                                    <p class="text-surface-500 font-medium flex items-center gap-2">
                                        <svg class="w-4 h-4 text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" /><polyline points="22,6 12,13 2,6" /></svg>
                                        {{ socio.correo_electronico || 'Sin correo registrado' }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span class="px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest" :class="{
                                        'bg-green-50 text-green-700 border border-green-200': socio.estatus_cuenta === 'AL_CORRIENTE',
                                        'bg-amber-50 text-amber-700 border border-amber-200': socio.estatus_cuenta === 'MOROSO',
                                        'bg-red-50 text-red-700 border border-red-200': socio.estatus_cuenta === 'SUSPENDIDO',
                                        'bg-primary-50 text-primary-700 border border-primary-200': socio.estatus_cuenta && socio.estatus_cuenta.startsWith('PENALIZADO')
                                    }">
                                        {{ socio.estatus_cuenta ? socio.estatus_cuenta.replace('_', ' ') : 'S/E' }}
                                    </span>
                                    <span class="text-xs font-bold text-surface-400 uppercase tracking-widest bg-surface-50 px-3 py-1 rounded-lg border border-surface-100">
                                        Acción: {{ socio.numero_accion }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100">
                                    <span class="block text-[10px] font-extrabold uppercase tracking-widest text-surface-500 mb-1">Tipo Socio</span>
                                    <span class="text-base font-bold text-surface-900">{{ socio.tipo_socio }}</span>
                                </div>
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100">
                                    <span class="block text-[10px] font-extrabold uppercase tracking-widest text-surface-500 mb-1">Modalidad</span>
                                    <span class="text-base font-bold text-surface-900">{{ socio.modalidad_plan }}</span>
                                </div>
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100">
                                    <span class="block text-[10px] font-extrabold uppercase tracking-widest text-surface-500 mb-1">Género</span>
                                    <span class="text-base font-bold text-surface-900">{{ socio.genero }}</span>
                                </div>
                                <div class="bg-surface-50/50 rounded-2xl p-4 border border-surface-100">
                                    <span class="block text-[10px] font-extrabold uppercase tracking-widest text-surface-500 mb-1">F. Nacimiento</span>
                                    <span class="text-base font-bold text-surface-900">{{ socio.fecha_nacimiento || 'N/A' }}</span>
                                </div>
                            </div>

                            <!-- Datos Adicionales -->
                            <div class="mt-10 pt-8 border-t border-surface-100">
                                <h4 class="text-sm font-extrabold uppercase tracking-widest text-surface-400 mb-6">Información de Cuenta y Penalizaciones</h4>
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="bg-white rounded-2xl p-6 border border-surface-200 shadow-sm flex items-center justify-between">
                                        <span class="text-sm font-bold text-surface-600">No Shows</span>
                                        <span class="text-3xl font-black" :class="socio.contador_no_shows > 0 ? 'text-red-600' : 'text-surface-900'">
                                            {{ socio.contador_no_shows }}
                                        </span>
                                    </div>
                                    <div class="bg-white rounded-2xl p-6 border border-surface-200 shadow-sm flex items-center justify-between">
                                        <span class="text-sm font-bold text-surface-600">Retrasos Ludoteca</span>
                                        <span class="text-3xl font-black" :class="socio.retrasos_ludoteca > 0 ? 'text-amber-600' : 'text-surface-900'">
                                            {{ socio.retrasos_ludoteca }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>
            </Transition>
        </div>
    </main>
</template>
