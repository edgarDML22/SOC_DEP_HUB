<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAlerts } from '@/composables/useAlerts';
import { useReservationStore } from '@/stores/reservationStore';
import { storeToRefs } from 'pinia';

const { showAlert } = useAlerts();
const reservationStore = useReservationStore();
const { misReservacionesTotales, cargando } = storeToRefs(reservationStore);

// TABS: Agenda Completa, Mis Reservas, Mis actividades
const activeTab = ref('mis-reservas');

const tabs = [
    {
        id: 'agenda-completa',
        label: 'Agenda Completa',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>'
    },
    {
        id: 'mis-reservas',
        label: 'Mis Reservas',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10h10V2z"/><path d="M22 12H12v10h10V12z"/><path d="M12 12H2v10h10V12z"/><path d="M22 2H12v10h10V2z"/></svg>'
    },
    {
        id: 'mis-actividades',
        label: 'Mis actividades',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>'
    }
];

// FILTROS LOCALES (Ahora con íconos representativos)
const filters = [
    { id: 'TODAS', label: 'Todas', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>' },
    { id: 'PENDIENTE', label: 'Borradores', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' },
    { id: 'ACTIVA', label: 'Activas', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>' },
    { id: 'COMPLETADA', label: 'Completadas', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' },
    { id: 'CANCELADA', label: 'Canceladas', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' },
    { id: 'NO SHOW', label: 'No Show', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>' }
];

const activeFilter = ref('TODAS');

const activeFilterLabel = computed(() => {
    const found = filters.find(f => f.id === activeFilter.value);
    return found ? found.label : '';
});

// Filtrado local instantáneo
const reservasFiltradas = computed(() => {
    if (activeFilter.value === 'TODAS') {
        return misReservacionesTotales.value;
    }
    return misReservacionesTotales.value.filter(r => r.estatus_operativo === activeFilter.value);
});


// Modal state
const showModal = ref(false);
const selectedReserva = ref(null);

onMounted(() => {
    reservationStore.fetchMisReservaciones();
});

// Controladores del Modal de Detalles
const openDetails = (reserva) => {
    selectedReserva.value = reserva;
    showModal.value = true;
};

const closeDetails = () => {
    showModal.value = false;
    selectedReserva.value = null;
};

const getStatusConfig = (status) => {
    const configs = {
        'ACTIVA': { label: 'ACTIVA', class: 'bg-green-50 text-green-700 border-green-200' },
        'COMPLETADA': { label: 'COMPLETADA', class: 'bg-blue-50 text-blue-700 border-blue-200' },
        'CANCELADA': { label: 'CANCELADA', class: 'bg-red-50 text-red-700 border-red-200' },
        'NO SHOW': { label: 'NO SHOW', class: 'bg-orange-50 text-orange-700 border-orange-200' },
        'PENDIENTE': { label: 'BORRADOR', class: 'bg-yellow-50 text-yellow-700 border-yellow-200' }
    };
    return configs[status] || { label: status, class: 'bg-surface-50 text-surface-700 border-surface-200' };
};

const formatearHora = (hora) => {
    if (!hora) return "";
    return hora.substring(0, 5);
};

const selectTab = (id) => {
    if (id === 'mis-reservas') {
        activeTab.value = id;
    } else {
        showAlert("Esta sección estará disponible próximamente", "info");
    }
};
</script>

<template>
    <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8 pt-4 font-sans">
        <div class="max-w-5xl mx-auto flex flex-col gap-6">

            <!-- TABS SUPERIORES -->
            <div
                class="flex p-1.5 bg-surface-100 rounded-2xl w-full max-w-2xl mx-auto border border-surface-200 shadow-inner">
                <button v-for="tab in tabs" :key="tab.id" @click="selectTab(tab.id)"
                    class="flex-1 py-3 px-3 rounded-xl text-xs md:text-sm transition-all duration-300 flex items-center justify-center gap-2 focus:outline-none"
                    :class="activeTab === tab.id ? 'bg-primary-600 text-white font-extrabold shadow-md' : 'text-surface-500 font-bold hover:bg-white/60 hover:text-surface-800'">
                    <span v-html="tab.icon"></span>
                    {{ tab.label }}
                </button>
            </div>

            <!-- CONTENIDO: MIS RESERVAS -->
            <div v-if="activeTab === 'mis-reservas'" class="flex flex-col gap-4">

                <!-- NUEVOS FILTROS POR ÍCONOS -->
                <div
                    class="flex gap-6 md:gap-10 border-b border-surface-200 w-full overflow-x-auto scrollbar-none px-2">
                    <button v-for="filter in filters" :key="filter.id" @click="activeFilter = filter.id"
                        :title="filter.label"
                        class="pb-3 transition-all flex items-center justify-center focus:outline-none relative"
                        :class="activeFilter === filter.id ? 'text-primary-600' : 'text-surface-400 hover:text-surface-600'">
                        <span v-html="filter.icon"></span>
                        <!-- Indicador Activo -->
                        <div v-if="activeFilter === filter.id"
                            class="absolute bottom-0 left-0 w-full h-0.5 bg-primary-600 rounded-t-full"></div>
                    </button>
                </div>

                <!-- TÍTULO DINÁMICO DE LA PESTAÑA -->
                <div class="mt-4 mb-2 px-2">
                    <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">
                        {{ activeFilterLabel }}
                    </h2>
                </div>

                <!-- LOADING STATE -->
                <div v-if="cargando && misReservacionesTotales.length === 0" class="flex flex-col items-center py-20">
                    <div class="w-10 h-10 border-4 border-primary-100 border-t-primary-600 rounded-full animate-spin">
                    </div>
                    <p class="mt-4 text-surface-500 font-medium">Cargando tus reservaciones...</p>
                </div>

                <!-- EMPTY STATE -->
                <div v-else-if="reservasFiltradas.length === 0"
                    class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm mt-2">
                    <div
                        class="w-16 h-16 bg-surface-50 rounded-full flex items-center justify-center text-surface-300 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-surface-900 font-bold text-lg">No hay reservaciones</p>
                    <p class="text-surface-500 text-sm">No se encontraron reservaciones {{ activeFilterLabel }}".
                    </p>
                </div>

                <!-- LISTA DE RESERVAS (Card Rediseñada y Compacta) -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="reserva in reservasFiltradas" :key="reserva.id_reserva"
                        class="bg-white p-5 md:p-6 rounded-2xl border border-surface-200 shadow-sm hover:shadow-md hover:border-primary-200 transition-all flex flex-col gap-3 group">
                        <div class="flex items-start justify-between gap-4 w-full">
                            <h3 class="text-base md:text-lg font-bold text-surface-900 m-0 truncate flex-1">
                                {{ reserva.disciplina?.nombre_disciplina || 'Deporte no especificado' }}
                            </h3>
                            <span 
                                class="inline-flex px-2.5 py-1 rounded-md text-[11px] font-medium border tracking-widest uppercase shrink-0"
                                :class="getStatusConfig(reserva.estatus_operativo).class"
                            >
                                {{ getStatusConfig(reserva.estatus_operativo).label }}
                            </span>
                        </div>

                        <div class="flex items-end justify-between gap-4 w-full mt-1">
                            <div class="flex flex-col gap-2 min-w-0">
                                <div class="flex items-center gap-2.5 text-sm font-semibold text-surface-600">
                                    <div
                                        class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10" />
                                            <polyline points="12 6 12 12 16 14" />
                                        </svg>
                                    </div>
                                    <span class="truncate">{{ formatearHora(reserva.hora_inicio) }} - {{
                                        formatearHora(reserva.hora_fin) }}</span>
                                </div>

                                <div class="flex items-center gap-2.5 text-sm font-semibold text-surface-600">
                                    <div
                                        class="w-7 h-7 rounded-lg bg-primary-50 flex items-center justify-center text-primary-600 shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                            <circle cx="12" cy="10" r="3" />
                                        </svg>
                                    </div>
                                    <span class="truncate">{{ reserva.espacio_fisico?.nombre_espacio || 'Espacio no asignada' }}</span>
                                </div>
                            </div>

                            <button @click="openDetails(reserva)"
                                class="shrink-0 w-11 h-11 bg-blue-50 text-blue-600 rounded-[0.85rem] flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm focus:outline-none group/btn"
                                title="Ver detalles">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5 group-hover/btn:scale-110 transition-transform" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <path d="M14 2v6h6" />
                                    <path d="M16 13H8" />
                                    <path d="M16 17H8" />
                                    <path d="M10 9H8" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>


            </div>

            <!-- PLACEHOLDER OTRAS PESTAÑAS -->
            <div v-else
                class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm">
                <div
                    class="w-16 h-16 bg-surface-50 rounded-full flex items-center justify-center text-surface-300 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-surface-900 font-bold text-lg">Próximamente</p>
                <p class="text-surface-500 font-medium text-sm">Esta sección estará disponible en el futuro.</p>
            </div>

        </div>

        <!-- MODAL DE DETALLES -->
        <Transition name="fade">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-surface-900/60 backdrop-blur-sm" @click="closeDetails"></div>

                <div
                    class="relative bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/20 animate-scale-in">
                    <!-- Header Modal -->
                    <div class="bg-primary-600 p-6 text-white flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold tracking-tight">Detalles de Reservación</h3>
                            <p class="text-primary-100 text-xs font-medium opacity-80">{{ selectedReserva?.fecha_reserva
                                }}</p>
                        </div>
                        <button @click="closeDetails"
                            class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body Modal -->
                    <div class="p-8 flex flex-col gap-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Deporte -->
                            <div class="flex flex-col gap-1">
                                <span
                                    class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Deporte</span>
                                <div class="flex items-center gap-2 text-surface-900 font-bold">
                                    <div
                                        class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    {{ selectedReserva?.disciplina?.nombre_disciplina }}
                                </div>
                            </div>
                            <!-- Horario -->
                            <div class="flex flex-col gap-1">
                                <span
                                    class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Horario</span>
                                <div class="flex items-center gap-2 text-surface-900 font-bold">
                                    <div
                                        class="w-8 h-8 bg-green-50 text-green-600 rounded-lg flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    {{ formatearHora(selectedReserva?.hora_inicio) }} - {{
                                    formatearHora(selectedReserva?.hora_fin) }}
                                </div>
                            </div>
                            <!-- Espacio -->
                            <div class="flex flex-col gap-1 md:col-span-2">
                                <span
                                    class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Espacio</span>
                                <div class="flex items-center gap-2 text-surface-900 font-bold">
                                    <div
                                        class="w-8 h-8 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    {{ selectedReserva?.espacio_fisico?.nombre_espacio }}
                                </div>
                            </div>
                        </div>

                        <!-- ACOMPAÑANTES -->
                        <div class="border-t border-surface-100 pt-6">
                            <span
                                class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-3 block">Acompañantes</span>

                            <div v-if="!selectedReserva?.acompanantes_draft || selectedReserva?.acompanantes_draft.length === 0"
                                class="p-4 bg-surface-50 border border-surface-200 rounded-2xl text-center text-surface-500 text-sm font-medium">
                                No se eligieron acompañantes
                            </div>

                            <div v-else class="flex flex-col gap-2 max-h-40 overflow-y-auto pr-2 scrollbar-thin">
                                <div v-for="(acomp, idx) in selectedReserva.acompanantes_draft" :key="idx"
                                    class="flex items-center justify-between p-3 bg-white rounded-xl border border-surface-200 shadow-sm">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 bg-surface-100 rounded-full flex items-center justify-center text-xs font-bold text-surface-600">
                                            {{ acomp.nombre?.charAt(0) || 'A' }}
                                        </div>
                                        <span class="text-sm font-bold text-surface-900">{{ acomp.nombre }}</span>
                                    </div>
                                    <span
                                        class="text-[10px] font-bold px-2 py-1 rounded-md bg-surface-100 text-surface-600 uppercase tracking-tight">
                                        {{ acomp.tipo }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.scrollbar-none::-webkit-scrollbar {
    display: none;
}

.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@keyframes scale-in {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }

    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-scale-in {
    animation: scale-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>