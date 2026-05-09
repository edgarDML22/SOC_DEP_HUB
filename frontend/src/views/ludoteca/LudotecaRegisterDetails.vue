<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminLudotecaStore } from '@/stores/ludoteca/adminLudotecaStore';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import { useformat } from '@/utils/formatters';
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue';
import { IconStar } from '@/components/icons';

const route = useRoute();
const router = useRouter();
const recordId = parseInt(route.params.id);
const { formatText, dateFormat } = useformat();

const store = useAdminLudotecaStore();
const { fetchRecord } = store;

// Buscamos el item en el record del store
const recordItem = computed(() => store.record.find(r => r.id_historial === recordId));

const isLoading = ref(true);
const errorMsg = ref('');

onMounted(async () => {
    try {
        // Forzamos carga para asegurar campos nuevos (comentarios)
        if (!recordItem.value) {
            await fetchRecord({}, true);
        }
        if (!recordItem.value) {
            errorMsg.value = "No se encontró el registro histórico solicitado.";
        }
    } catch (error) {
        console.error("Error al cargar historial:", error);
        errorMsg.value = "Hubo un problema al cargar los detalles del registro.";
    } finally {
        isLoading.value = false;
    }
});

const goBack = () => {
    router.push('/admin/ludoteca');
};

const AVATAR_GRADIENTS = [
    'from-blue-400 to-blue-600',
    'from-indigo-400 to-indigo-600',
    'from-violet-400 to-violet-600',
    'from-fuchsia-400 to-fuchsia-600',
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
                <h1 class="text-2xl font-black text-surface-900 tracking-tight">Detalles del Registro</h1>
            </header>

            <section v-if="errorMsg"
                class="text-center p-12 bg-red-50 rounded-2xl border border-red-100 animate-scale-in">
                <p class="text-red-600 font-bold">{{ errorMsg }}</p>
                <button @click="goBack"
                    class="mt-4 px-6 py-2.5 bg-white border border-red-200 text-red-600 rounded-xl font-bold hover:bg-red-50 transition-colors shadow-sm">Volver
                    al historial</button>
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
                <section v-if="recordItem && !isLoading">
                    <article
                        class="bg-white rounded-2xl shadow-xl shadow-surface-200/40 border border-surface-200 overflow-hidden relative">

                        <!-- Accent Bar -->
                        <div class="absolute top-0 left-0 right-0 h-1 bg-primary-500"></div>

                        <!-- Cabecera -->
                        <div class="flex items-center justify-between px-7 py-6 border-b border-surface-100">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-linear-to-br flex items-center justify-center
                                         text-white font-black text-lg shadow-sm shrink-0"
                                    :class="avatarGradient(recordItem.nombre_menor)">
                                    {{ initials(recordItem.nombre_menor) }}
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-surface-900 leading-tight">
                                        {{ recordItem.nombre_menor }}
                                    </h2>
                                    <p class="text-xs font-bold text-surface-500 mt-0.5 uppercase tracking-widest">
                                        Ingreso registrado el {{ dateFormat(recordItem.hora_ingreso.split(' ')[0]) }}
                                    </p>
                                </div>
                            </div>
                            <div class="shrink-0">
                                <BadgeStatus :status="recordItem.estatus_final" />
                            </div>
                        </div>

                        <!-- Cuerpo -->
                        <div class="bg-surface-50/40 p-6 lg:p-8">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                                <!-- COLUMNA IZQUIERDA: Participantes -->
                                <div class="space-y-6">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-surface-400">
                                        Responsable</p>

                                    <div
                                        class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm flex items-center gap-4">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-surface-100 flex items-center justify-center text-surface-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-surface-900">{{
                                                recordItem.nombre_titular }}</span>
                                            <span class="block text-[10px] font-mono text-surface-400">Acción #{{
                                                recordItem.numero_accion }}</span>
                                        </div>
                                    </div>

                                    <!-- Comentarios del Responsable -->
                                    <div class="space-y-3">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-surface-400">
                                            Comentarios realizados</p>
                                        <div class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm text-sm leading-relaxed min-h-[100px] flex items-center justify-center">
                                            <span v-if="recordItem.comentarios_padre" class="italic text-surface-600">
                                                "{{ recordItem.comentarios_padre }}"
                                            </span>
                                            <span v-else class="text-surface-400 font-medium italic">
                                                Sin comentarios disponibles
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- COLUMNA DERECHA: Tiempos y Calificación -->
                                <div class="space-y-6">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-surface-400">
                                        Detalles de la Estancia</p>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm">
                                            <span
                                                class="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-1">Horario</span>
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2 text-xs font-bold text-emerald-600">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    {{ recordItem.hora_ingreso.split(' ')[1] }}
                                                </div>
                                                <div class="flex items-center gap-2 text-xs font-bold text-rose-600">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                    {{ recordItem.hora_egreso.split(' ')[1] }}
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm flex flex-col justify-center text-center">
                                            <span
                                                class="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-1">Duración</span>
                                            <span class="text-2xl font-bold text-surface-900">{{
                                                recordItem.tiempo_total_minutos }}</span>
                                            <span
                                                class="text-[10px] font-bold text-surface-400 uppercase">Minutos</span>
                                        </div>
                                    </div>

                                    <div v-if="recordItem.calificacion_servicio"
                                        class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm">
                                        <span
                                            class="block text-[10px] font-black uppercase tracking-widest text-surface-400 mb-2">Calificación
                                            del Servicio</span>
                                        <div class="flex items-center gap-2">
                                            <div class="flex gap-1">
                                                <IconStar v-for="i in 5" :key="i" class="w-5 h-5"
                                                    :class="i <= recordItem.calificacion_servicio ? 'text-amber-500 fill-amber-500' : 'text-surface-200'" />
                                            </div>
                                            <span class="text-lg font-bold text-amber-500 ml-2">{{
                                                recordItem.calificacion_servicio }}.0</span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Pie -->
                        <div class="flex items-center justify-end gap-3 px-7 py-5 border-t border-surface-100 bg-white">
                            <button @click="goBack"
                                class="px-6 py-2.5 rounded-xl border border-surface-200 bg-white
                                     text-sm font-bold text-surface-700 hover:bg-surface-50 transition-colors shadow-sm">
                                Volver al historial
                            </button>
                        </div>
                    </article>
                </section>
            </Transition>
        </div>
    </main>
</template>
