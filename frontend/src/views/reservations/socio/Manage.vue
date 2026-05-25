<script setup>
import { ref, computed, watch, defineEmits } from 'vue';
import { useAlerts } from '@/composables/useAlerts';
import { useReservationStore } from '@/stores/reservationStore';
import { storeToRefs } from 'pinia';
import { useRouter } from 'vue-router';

const { toastInfo, confirmWarning, confirmDelete, showLoading, closeLoading, successModal } = useAlerts();
const reservationStore = useReservationStore();
const router = useRouter(); 
const emit = defineEmits(['switch-tab']);
const { misReservacionesTotales, cargando, misReservacionesCargadas } = storeToRefs(reservationStore);
const { cancelarReservacion, descartarBorrador } = reservationStore;

// FILTROS LOCALES (Ahora con íconos representativos)
const filters = [
    { id: 'TODAS', label: 'Todas', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>' },
    { id: 'PENDIENTE', label: 'Pendientes', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' },
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
    let list = misReservacionesTotales.value;
    if (activeFilter.value !== 'TODAS') {
        if (activeFilter.value === 'NO SHOW') {
            list = list.filter(r => r.estatus_operativo === 'NO_SHOW' || r.estatus_operativo === 'NO SHOW');
        } else {
            list = list.filter(r => r.estatus_operativo === activeFilter.value);
        }
    }
    
    // Sort / Ordenamiento
    return [...list].sort((a, b) => {
        const orderA = orderMap[a.estatus_operativo] || 99;
        const orderB = orderMap[b.estatus_operativo] || 99;
        
        if (orderA !== orderB) {
            return orderA - orderB;
        }
        
        // Si tienen el mismo estatus, la más reciente primero
        const dateA = new Date(`${a.fecha_reserva}T${a.hora_inicio}`);
        const dateB = new Date(`${b.fecha_reserva}T${b.hora_inicio}`);
        return dateB - dateA;
    });
});

// Modal state
const showModal = ref(false);
const selectedReserva = ref(null);

// Recargar lista cuando el flag se invalide (cancelación, descarte, nueva confirmación)
// immediate:true => también carga en el primer render
watch(misReservacionesCargadas, (cargadas) => {
    if (!cargadas) {
        reservationStore.fetchMisReservaciones();
    }
}, { immediate: true });

// Controladores del Modal de Detalles
const openDetails = (reserva) => {
    selectedReserva.value = reserva;
    showModal.value = true;
};

// Lógica de Cancelación con SweetAlert2
const isCancelling = ref(false);

const openCancelModal = async (reserva, event) => {
    event.stopPropagation();

    const ahoraStr = new Date().toLocaleString("en-US", { timeZone: "America/Mexico_City" });
    const ahoraMexico = new Date(ahoraStr);
    const fechaHoraReserva = new Date(`${reserva.fecha_reserva}T${reserva.hora_inicio}`);
    const minutosRestantes = (fechaHoraReserva - ahoraMexico) / 60000;
    const esTardia = minutosRestantes < 120;

    const titulo = esTardia ? 'Cancelación con Penalización' : 'Cancelar Reservación';

    // Mensaje con NO SHOW en rojo usando html en lugar de text
    const mensajeHtml = esTardia
        ? `¡Atención! Faltan menos de 2 horas (o el horario ya inició) para tu reservación de <strong>${reserva.disciplina?.nombre_disciplina || 'este espacio'}</strong>. Si cancelas ahora, se registrará un <span style="color:#dc2626;font-weight:700;">NO SHOW</span> en tu cuenta. ¿Deseas continuar?`
        : `¿Estás seguro de que deseas cancelar tu reservación de <strong>${reserva.disciplina?.nombre_disciplina || 'este espacio'}</strong>? El horario quedará libre para otros socios.`;

    const Swal = (await import('sweetalert2')).default;

    const result = await Swal.fire({
        title: titulo,
        html: mensajeHtml,
        showCancelButton: true,
        confirmButtonText: 'Sí, Cancelar',
        cancelButtonText: 'Regresar',
        buttonsStyling: false,
        background: 'var(--p-surface-50)',
        color: 'var(--p-surface-900)',
        customClass: {
            popup: 'swal-border-radius',
            confirmButton: 'btn-delete-confirm',
            cancelButton: 'btn-cancel',
        }
    });

    if (result.isConfirmed) {
        showLoading('Cancelando reservación...');
        const res = await cancelarReservacion(reserva.id_reserva);
        closeLoading();

        if (res?.success) {
            if (res.nuevo_estatus === 'NO_SHOW') {
                await Swal.fire({
                    title: 'No Show registrado',
                    html: 'Tu reservación fue cancelada tardíamente.<br>Se registró un <span style="color:#dc2626;font-weight:700;">NO SHOW</span> en tu cuenta.',
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    buttonsStyling: false,
                    background: 'var(--p-surface-50)',
                    color: 'var(--p-surface-900)',
                    customClass: { popup: 'swal-border-radius', confirmButton: 'btn-primary' }
                });
            } else {
                await successModal('Reservación cancelada', 'Tu reservación ha sido cancelada correctamente.');
            }
        } else {
            toastInfo('Error', res?.error || 'No se pudo cancelar la reservación.', 'error');
        }
        isCancelling.value = false;
    }
};

// 1. Agrega esta variable para controlar el spinner del botón individual:
const actionLoadingId = ref(null);

// 2. Actualiza la función confirmarDescarte (Ahora usa spinner):
const confirmarDescarte = async (reserva, event) => {
    event.stopPropagation();
    
    const result = await confirmDelete(
        'Descartar Borrador',
        '¿Estás seguro de que deseas descartar esta reservación pendiente? Esta acción no se puede deshacer.',
        'Sí, Descartar'
    );
    
    if (result.isConfirmed) {
        actionLoadingId.value = reserva.id_reserva;
        await descartarBorrador(reserva.id_reserva);
        await reservationStore.fetchMisReservaciones(true);
        actionLoadingId.value = null;
        await successModal('Borrador descartado', 'La reservación pendiente fue eliminada correctamente.');
    }
};

// 3. Actualiza continuarBorrador (Apaga el modal azul antes de redirigir):
const continuarBorrador = async (reserva, event) => {
    event.stopPropagation();
    
    const result = await confirmWarning(
        'Continuar Reservación',
        '¿Deseas reanudar esta reservación donde la dejaste?',
        'Sí, Continuar'
    );
    
    if (result.isConfirmed) {
        reservationStore.mostrarModalDraft = false; // <-- ESTO MATA AL MODAL
        await reservationStore.buscarReservaActiva();
        reservationStore.pasoActual = "4"; 
        emit('switch-tab', 'hacer-reserva'); 
    }
};

const orderMap = {
    'PENDIENTE': 1,
    'ACTIVA': 2,
    'NO_SHOW': 3,
    'NO SHOW': 3,
    'COMPLETADA': 4,
    'CANCELADA': 5
};

const closeDetails = () => {
    showModal.value = false;
    selectedReserva.value = null;
};

const getStatusConfig = (status) => {
    const configs = {
        'ACTIVA':     { label: 'ACTIVA',     class: 'bg-green-50 text-green-700 border-green-200' },
        'COMPLETADA': { label: 'COMPLETADA', class: 'bg-blue-50 text-blue-700 border-blue-200' },
        'CANCELADA':  { label: 'CANCELADA',  class: 'bg-orange-50 text-orange-700 border-orange-200' },
        'NO_SHOW':    { label: 'NO SHOW',    class: 'bg-red-50 text-red-700 border-red-200' },
        'NO SHOW':    { label: 'NO SHOW',    class: 'bg-red-50 text-red-700 border-red-200' },
        'PENDIENTE':  { label: 'PENDIENTE',  class: 'bg-yellow-50 text-yellow-700 border-yellow-200' },
    };
    return configs[status] || { label: status, class: 'bg-surface-50 text-surface-700 border-surface-200' };
};

const formatearHora = (hora) => {
    if (!hora) return "";
    return hora.substring(0, 5);
};

const formatearFecha = (fecha) => {
    if (!fecha) return "";
    const [anio, mes, dia] = fecha.substring(0, 10).split("-");
    return `${dia}/${mes}/${anio}`;
};

const selectTab = (id) => {
    activeTab.value = id;
};
</script>

<template>
    <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8 pt-4 font-sans">
        <div class="max-w-5xl mx-auto flex flex-col gap-6">

            <!-- FILTROS PILL (scroll horizontal) -->
            <div class="flex gap-2 overflow-x-auto scrollbar-none pb-1 -mx-1 px-1">
                <button
                    v-for="filter in filters"
                    :key="filter.id"
                    @click="activeFilter = filter.id"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-all focus:outline-none shrink-0 border"
                    :class="activeFilter === filter.id
                        ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-200'
                        : 'bg-white text-surface-600 border-surface-200 hover:border-primary-300 hover:text-primary-700 hover:bg-primary-50'"
                >
                    <span v-html="filter.icon" class="[&>svg]:w-3.5 [&>svg]:h-3.5 shrink-0"></span>
                    {{ filter.label }}
                </button>
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
                    class="bg-white p-5 md:p-6 rounded-2xl border border-surface-200 shadow-sm hover:shadow-md hover:border-primary-200 transition-all flex flex-col gap-3 group border-l-4 border-l-blue-500">
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
                                <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10" />
                                        <polyline points="12 6 12 12 16 14" />
                                    </svg>
                                </div>
                                <span class="truncate">{{ formatearHora(reserva.hora_inicio) }} - {{ formatearHora(reserva.hora_fin) }}</span>
                            </div>

                            <div class="flex items-center gap-2.5 text-sm font-semibold text-surface-600">
                                <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                        <circle cx="12" cy="10" r="3" />
                                    </svg>
                                </div>
                                <span class="truncate">{{ reserva.espacio_fisico?.nombre_espacio || 'Espacio no asignado' }}</span>
                            </div>

                            <div class="flex items-center gap-2.5 text-sm font-semibold text-surface-600">
                                <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" />
                                        <line x1="8" y1="2" x2="8" y2="6" />
                                        <line x1="3" y1="10" x2="21" y2="10" />
                                    </svg>
                                </div>
                                <span class="truncate">{{ formatearFecha(reserva.fecha_reserva) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Botón X: Cancelar reservación ACTIVA -->
                            <button
                                v-if="reserva.estatus_operativo === 'ACTIVA'"
                                @click="openCancelModal(reserva, $event)"
                                class="w-9 h-9 bg-red-50 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm focus:outline-none"
                                title="Cancelar reservación">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <!-- Botón continuar: Solo para borradores PENDIENTE -->
                            <button
                                v-if="reserva.estatus_operativo === 'PENDIENTE'"
                                @click="continuarBorrador(reserva, $event)"
                                class="w-9 h-9 bg-green-50 text-green-600 rounded-xl flex items-center justify-center hover:bg-green-600 hover:text-white transition-all shadow-sm focus:outline-none"
                                title="Continuar reservación">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14"/>
                                    <path d="m12 5 7 7-7 7"/>
                                </svg>
                            </button>

                            <button
                                v-if="reserva.estatus_operativo === 'PENDIENTE'"
                                @click="confirmarDescarte(reserva, $event)"
                                :disabled="actionLoadingId === reserva.id_reserva"
                                class="w-9 h-9 bg-red-50 text-red-600 rounded-xl flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-sm focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Descartar borrador">
                                <svg v-if="actionLoadingId !== reserva.id_reserva" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span v-else class="w-4 h-4 border-2 border-red-300 border-t-red-600 rounded-full animate-spin"></span>
                            </button>

                            <!-- Botón Ver Detalles -->
                            <button @click="openDetails(reserva)"
                                class="w-9 h-9 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm focus:outline-none"
                                title="Ver detalles">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-4 h-4" viewBox="0 0 24 24"
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

        </div>

        <!-- MODAL DE DETALLES -->
        <Transition name="fade">
            <div v-if="showModal" class="fixed inset-0 z-100 flex items-center justify-center p-4">
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
                                    class="text-[11px] font-extrabold text-surface-900 uppercase tracking-widest">Deporte</span>
                                <div class="flex items-center gap-2 text-surface-900 font-medium">
                                    <div
                                        class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
                                    class="text-[11px] font-extrabold text-surface-900 uppercase tracking-widest">Horario</span>
                                <div class="flex items-center gap-2 text-surface-900 font-medium">
                                    <div
                                        class="w-8 h-8 bg-green-50 text-green-600 rounded-lg flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
                                    class="text-[11px] font-extrabold text-surface-900 uppercase tracking-widest">Espacio</span>
                                <div class="flex items-center gap-2 text-surface-900 font-medium">
                                    <div
                                        class="w-8 h-8 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
                                class="text-[11px] font-extrabold text-surface-900 uppercase tracking-widest mb-4 block">Acompañantes</span>

                            <div v-if="!selectedReserva?.acompanantes_draft || selectedReserva?.acompanantes_draft.length === 0"
                                class="p-4 bg-surface-50 border border-surface-200 rounded-2xl text-center text-surface-500 text-sm font-medium">
                                No se eligieron acompañantes
                            </div>

                            <div v-else class="flex flex-col gap-2 max-h-40 overflow-y-auto pr-2 scrollbar-thin">
                                <div v-for="(acomp, idx) in selectedReserva.acompanantes_draft" :key="idx"
                                    class="flex items-center justify-between p-3.5 bg-white rounded-xl border border-surface-200 shadow-sm">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div
                                            class="w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center text-xs font-extrabold uppercase shrink-0 shadow-sm">
                                            {{ acomp.nombre?.charAt(0) || '?' }}
                                        </div>
                                        <span class="text-sm font-semibold text-surface-900 truncate leading-snug">{{ acomp.nombre }}</span>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border tracking-wide uppercase"
                                        :class="{
                                            'bg-green-50 text-green-700 border-green-200': acomp.tipo?.toUpperCase() === 'AMIGO',
                                            'bg-purple-50 text-purple-700 border-purple-200': acomp.tipo?.toUpperCase() === 'FAMILIAR',
                                            'bg-orange-50 text-orange-700 border-orange-200': acomp.tipo?.toUpperCase() === 'INVITADO'
                                        }">
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