<script setup>
import { onMounted, onUnmounted, defineAsyncComponent, ref, computed, watch, nextTick, defineEmits } from 'vue';
import { storeToRefs } from 'pinia';
import { useReservationStore } from '@/stores/reservationStore';
import { useProfileStore } from '@/stores/profiles/socioStore';
import { IconArrowLeft } from '@/components/icons';
import StepAcompanantes from './StepAcompanantes.vue';
import StepConfirmacion from './StepConfirmacion.vue';

const emit = defineEmits(['switch-tab']);

const reservationStore = useReservationStore();

const {
    pasoActual,
    cargando,
    disciplinasUnicas,
    horariosDisponibles,
    reservaPayload,
    opcionesHoras,
    horaInicioTemp,
    horaFinTemp,
    esHorarioValidoParaPreview,
    errorValidacion,
    errorNavegacion,
    mostrarModalDraft,
    draftVerificado,
} = storeToRefs(reservationStore);

const {
    fetchDisponibilidadEspacios,
    seleccionarDisciplina,
    obtenerIconoName,
    validarHorario,
    buscarReservaActiva
} = reservationStore;

onMounted(async () => {
    // Siempre aseguramos tener los espacios disponibles (guard interno lo evita si ya cargó)
    reservationStore.fetchDisponibilidadEspacios();

    const profileStore = useProfileStore();
    await profileStore.fetchProfile();

    // Solo verificamos borradores si no lo hemos hecho ya en esta sesión
    if (!draftVerificado.value) {
        const tieneDraft = await reservationStore.buscarReservaActiva();
        draftVerificado.value = true; // Marcar como verificado independientemente del resultado

        if (tieneDraft && reservationStore.pasoActual === "1") {
            mostrarModalDraft.value = true;
        } else {
            mostrarModalDraft.value = false;
        }
    }
});

const formDuration = ref(60);

watch(horaInicioTemp, (newVal) => {
    if (newVal && opcionesHoras.value && opcionesHoras.value.length) {
        const idx = opcionesHoras.value.indexOf(newVal);
        if (idx !== -1) {
            let [h, m] = newVal.split(':').map(Number);
            m += formDuration.value;
            h += Math.floor(m / 60);
            m = m % 60;
            const endStr = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
            if (opcionesHoras.value.includes(endStr)) {
                horaFinTemp.value = endStr;
            } else {
                horaFinTemp.value = opcionesHoras.value[opcionesHoras.value.length - 1];
            }
        }
    }
});

watch(formDuration, () => {
    if (horaInicioTemp.value && opcionesHoras.value?.length) {
        const parseMins = (hStr) => {
            const [h, m] = hStr.split(':').map(Number);
            return h * 60 + (m || 0);
        };
        const lastSlotMins = parseMins(opcionesHoras.value[opcionesHoras.value.length - 1]);
        const startMins = parseMins(horaInicioTemp.value);
        // Si el slot actual ya no es válido con la nueva duración, lo limpiamos
        if (startMins + formDuration.value > lastSlotMins) {
            horaInicioTemp.value = null;
            horaFinTemp.value = null;
        } else {
            // Recalcular hora fin con nueva duración
            const temp = horaInicioTemp.value;
            horaInicioTemp.value = null;
            horaInicioTemp.value = temp;
        }
    }
});


const reanudarReserva = () => {
    mostrarModalDraft.value = false;
    reservationStore.pasoActual = "4";
    emit('switch-tab', 'hacer-reserva');
};

const ignorarReserva = async () => {
    mostrarModalDraft.value = false;
    await reservationStore.descartarBorrador();
};

const formatearHora = (horaString) => {
    if (!horaString) return '';
    return horaString.substring(0, 5);
};

const IconoDeporte = (disciplina) => {
    const nombreArchivo = obtenerIconoName(disciplina);
    return defineAsyncComponent(() => import(`@/components/icons/sports/${nombreArchivo}.vue`));
};

const horariosGrupados = computed(() => {
    const grupos = {
        "Mañana": [],
        "Tarde": [],
        "Noche": []
    };
    if (!opcionesHoras.value || !opcionesHoras.value.length) return grupos;

    const parseMins = (hStr) => {
        const [h, m] = hStr.split(':').map(Number);
        return h * 60 + (m || 0);
    };

    // Límite = último slot disponible (ej. 23:00 → 1380 min)
    const lastSlotMins = parseMins(opcionesHoras.value[opcionesHoras.value.length - 1]);

    opcionesHoras.value.forEach(hora => {
        const startMins = parseMins(hora);
        // Sólo mostramos el slot si inicio + duración NO excede el último slot
        if (startMins + formDuration.value > lastSlotMins) return;

        const h = parseInt(hora.split(':')[0]);
        if (h < 12) grupos["Mañana"].push(hora);
        else if (h < 18) grupos["Tarde"].push(hora);
        else grupos["Noche"].push(hora);
    });
    return grupos;
});

const getSlotStatus = (horaInicio) => {
    // 1. Validar si la hora ya pasó (solo si es para el día de hoy)
    // Nota: El backend siempre asume "hoy" para OnDemand por ahora, pero aquí somos precavidos.
    const ahoraStr = new Date().toLocaleString("en-US", { timeZone: "America/Mexico_City" });
    const ahoraMexico = new Date(ahoraStr);
    
    const [h, m] = horaInicio.split(':').map(Number);
    const horaSlot = new Date(ahoraMexico);
    horaSlot.setHours(h, m, 0, 0);

    if (horaSlot < ahoraMexico) {
        return { blocked: true, reason: 'PAST', label: 'Pasado' };
    }

    if (!horariosDisponibles.value || !horariosDisponibles.value.length) return { blocked: false };

    const parseMins = (hStr) => {
        const [h, m] = hStr.split(':').map(Number);
        return (h * 60) + (m || 0);
    };
    const startMins = parseMins(horaInicio);
    const endMins = startMins + formDuration.value;

    const bloque = horariosDisponibles.value.find(bloque => {
        if (!bloque.inicio || !bloque.fin) return false;
        const bkStart = parseMins(bloque.inicio);
        const bkEnd = parseMins(bloque.fin);
        return (startMins < bkEnd && endMins > bkStart);
    });

    if (bloque) {
        if (bloque.tipo === 'conflicto_personal') {
            return { blocked: true, reason: 'PERSONAL', label: 'Tu Agenda' };
        }
        return { blocked: true, reason: 'OCCUPIED', label: 'Ocupado' };
    }

    return { blocked: false };
};

const isHoraBloqueada = (horaInicio) => {
    return getSlotStatus(horaInicio).blocked;
};

// --- Step 3: Sticky status bar visibility ---
const bottomStatusRef = ref(null);
const bottomStatusVisible = ref(false);
let observer = null;

watch(pasoActual, (val) => {
    if (val === '3') {
        nextTick(() => {
            if (bottomStatusRef.value && window.IntersectionObserver) {
                observer = new IntersectionObserver(([entry]) => {
                    bottomStatusVisible.value = entry.isIntersecting;
                }, { threshold: 0.3 });
                observer.observe(bottomStatusRef.value);
            }
        });
    } else {
        if (observer) { observer.disconnect(); observer = null; }
        bottomStatusVisible.value = false;
    }
});

onUnmounted(() => { if (observer) observer.disconnect(); });
</script>

<template>
    <div class="w-full min-h-screen bg-surface-50 p-4 md:p-6 lg:p-8 font-sans pb-24 md:pb-8 flex flex-col items-center">

        <!-- STICKY STATUS BAR (Step 3 móvil) -->
        <Transition name="slide-down">
            <div v-if="pasoActual === '3' && !bottomStatusVisible && (errorValidacion || esHorarioValidoParaPreview)"
                class="fixed top-0 left-0 right-0 z-[100] md:hidden px-4 pt-2 pb-3 shadow-lg border-b"
                :class="errorValidacion ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200'">
                <div v-if="errorValidacion" class="flex items-center gap-2 text-red-600 font-semibold text-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ errorValidacion }}
                </div>
                <div v-else-if="esHorarioValidoParaPreview"
                    class="flex items-center gap-2 text-green-700 font-semibold text-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Tu lugar: <strong>{{ formatearHora(horaInicioTemp) }} — {{ formatearHora(horaFinTemp)
                    }}</strong></span>
                </div>
            </div>
        </Transition>

        <!-- HERO BANNER DRAFT (NUEVO DISEÑO) -->
        <div v-if="mostrarModalDraft"
            class="w-full max-w-5xl mb-6 bg-gradient-to-br from-primary-800 to-primary-600 text-white rounded-[2.5rem] p-6 md:p-10 shadow-xl flex flex-col lg:flex-row lg:items-center justify-between gap-8 border border-primary-500/30 overflow-hidden transition-all animate-fade-in relative z-10">
            <div class="absolute -top-10 -right-10 w-48 h-48 bg-white/10 rounded-full blur-3xl pointer-events-none">
            </div>

            <div class="flex-1 relative z-10">
                <div class="flex items-center gap-3 mb-4">
                    <!-- NUEVO BADGE SOFISTICADO -->
                    <span
                        class="bg-surface-900/40 text-white border border-white/20 px-3.5 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-wider shadow-sm flex items-center gap-2 backdrop-blur-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-300" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                        Reserva Pendiente
                    </span>
                </div>
                <h3 class="text-2xl md:text-3xl font-extrabold mb-3 tracking-tight">
                    ¿Deseas reanudar tu reservación?
                </h3>
                <p class="text-primary-100 font-medium text-sm md:text-base mb-2 opacity-95 max-w-xl leading-relaxed">
                    Aún cuentas con una reservación de <strong class="text-white">{{
                        reservaPayload.disciplinaSeleccionada }}</strong> que no terminaste de
                    confirmar...
                </p>
            </div>

            <div class="shrink-0 flex flex-col sm:flex-row w-full lg:w-auto gap-4 relative z-10">
                <!-- NUEVO BOTÓN DE CANCELAR CORREGIDO (Glassmorphism) -->
                <button @click="ignorarReserva"
                    class="w-full sm:w-auto px-6 py-3.5 bg-white/10 border border-white/20 text-white hover:bg-white/20 font-bold rounded-xl transition-all focus:outline-none">
                    Descartar Reservación Pendiente
                </button>
                <!-- BOTÓN DE CONTINUAR -->
                <button @click="reanudarReserva"
                    class="w-full sm:w-auto px-8 py-3.5 bg-white text-primary-800 font-extrabold rounded-xl shadow-xl hover:shadow-2xl hover:scale-[1.02] transition-all focus:outline-none">
                    Continuar Reservación
                </button>
            </div>
        </div>

        <!-- MAIN CARD WRAPPER -->
        <div v-if="!mostrarModalDraft"
            class="w-full max-w-5xl bg-white p-6 md:p-10 rounded-[2.5rem] shadow-sm border border-surface-200 h-fit">

            <!-- MINIMALIST PROGRESS INDICATOR -->
            <div class="flex items-center gap-1.5 mb-8 md:mb-10 w-full mx-auto">
                <div v-for="paso in 5" :key="paso" class="flex-1 h-3 md:h-2.5 rounded-full transition-all duration-500"
                    :class="parseInt(pasoActual) >= paso ? 'bg-primary-600 shadow-sm' : 'bg-surface-100'">
                </div>
            </div>

            <!-- HEADER DINÁMICO -->
            <div class="flex flex-col mb-10 border-b border-surface-100 pb-8 relative">

                <div class="flex items-center gap-4">
                    <button v-if="pasoActual !== '1'"
                        @click="pasoActual === '2' ? reservationStore.volverADisciplinas() : (pasoActual === '3' ? reservationStore.volverAEspacios() : reservationStore.intentarCambioPaso(String(parseInt(pasoActual) - 1)))"
                        class="w-12 h-12 md:w-14 md:h-14 rounded-2xl border-2 border-surface-200 flex items-center justify-center text-surface-500 hover:text-primary-600 hover:border-primary-300 hover:bg-primary-50/50 transition-all shrink-0 group active:scale-90">
                        <IconArrowLeft class="w-5 h-5 md:w-6 md:h-6 group-hover:-translate-x-1 transition-transform" />
                    </button>

                    <div class="flex-1">
                        <span class="text-xs font-semibold tracking-wider text-primary-600 uppercase mb-1.5 block">
                            Paso {{ pasoActual }} de 5
                        </span>
                        <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight leading-none">
                            <template v-if="pasoActual === '1'">¿Qué jugarás hoy?</template>
                            <template v-if="pasoActual === '2'">Selecciona la Cancha</template>
                            <template v-if="pasoActual === '3'">Elige tu Horario</template>
                            <template v-if="pasoActual === '4'">Invita Acompañantes</template>
                            <template v-if="pasoActual === '5'">Confirma tu Reserva</template>
                        </h2>
                    </div>
                </div>

                <p v-if="pasoActual === '2'"
                    class="text-surface-500 font-medium text-sm md:text-base mt-4 ml-0 md:ml-[72px] flex items-center flex-wrap gap-2 md:gap-3">
                    <span>Disponibilidad para <strong class="text-primary-700 capitalize">{{
                        reservaPayload.disciplinaSeleccionada
                            }}</strong></span>
                    <span v-if="reservationStore.espaciosPorDisciplina.length"
                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-surface-100/80 rounded-lg text-surface-700 font-semibold border border-surface-200">
                        <i class="pi pi-users text-xs"></i> Capacidad: {{
                            reservationStore.espaciosPorDisciplina[0]?.capacidad_maxima }}
                        personas p/cancha
                    </span>
                </p>
                <p v-if="pasoActual === '3'"
                    class="text-surface-500 font-medium text-sm mt-4 ml-0 md:ml-[72px] flex flex-wrap gap-2 items-center">
                    Espacio elegido: <span
                        class="bg-primary-50 text-primary-800 border border-primary-200 px-3 py-1 rounded-xl font-bold ml-1 text-sm">{{
                            reservaPayload.espacioSeleccionado }}</span>
                </p>

            </div>

            <!-- ALERTS -->
            <div v-if="errorNavegacion"
                class="flex items-center gap-3 w-full bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl font-semibold text-sm shadow-sm mb-10 animate-pulse">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-red-500" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <span>{{ errorNavegacion }}</span>
            </div>

            <div v-if="cargando" class="text-center py-24 text-surface-500">
                <div
                    class="w-12 h-12 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4">
                </div>
                <span class="font-bold tracking-wider uppercase text-sm">Cargando...</span>
            </div>

            <div v-else>
                <!-- PASO 1: DEPORTE -->
                <div v-if="pasoActual === '1'"
                    class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5 w-full">
                    <button v-for="disciplina in disciplinasUnicas" :key="disciplina"
                        @click="seleccionarDisciplina(disciplina)"
                        class="bg-primary-600 hover:bg-primary-700 flex flex-col items-center justify-center p-6 md:p-8 rounded-[2rem] w-full aspect-square cursor-pointer transition-all hover:shadow-[0_12px_25px_-6px_rgba(37,99,235,0.4)] hover:-translate-y-1.5 group active:scale-95 focus:outline-none border-none">
                        <div
                            class="w-16 h-16 md:w-20 md:h-20 text-white flex justify-center items-center transition-transform group-hover:scale-110 mb-4">
                            <component :is="IconoDeporte(disciplina)" class="w-full h-full fill-current" />
                        </div>
                        <span class="text-white text-lg md:text-xl font-bold text-center leading-tight tracking-tight">
                            {{ disciplina }}
                        </span>
                    </button>
                </div>

                <!-- PASO 2: ESPACIO -->
                <div v-if="pasoActual === '2'" class="flex flex-col w-full">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
                        <button v-for="cancha in reservationStore.espaciosPorDisciplina" :key="cancha.id_espacio"
                            @click="reservationStore.seleccionarEspacio(cancha.id_espacio)"
                            :disabled="cancha.estatus === 'Bloqueado por Mantenimiento' || cancha.estatus === 'Lleno/No Disponible'"
                            class="flex items-center justify-between w-full p-5 lg:p-7 rounded-[2rem] border-2 transition-all text-left bg-white font-sans group active:scale-95 focus:outline-none"
                            :class="(cancha.estatus === 'Disponible' || cancha.estatus === 'DISPONIBLE') ? 'border-surface-200 hover:border-primary-500 hover:shadow-xl hover:-translate-y-1 cursor-pointer' : 'border-surface-200 opacity-60 bg-surface-50 cursor-not-allowed hidden-hover'">
                            <div class="flex items-center gap-5 min-w-0">
                                <div class="shrink-0 w-16 h-16 rounded-2xl flex items-center justify-center transition-all duration-300 shadow-sm border border-surface-100"
                                    :class="(cancha.estatus === 'Disponible' || cancha.estatus === 'DISPONIBLE') ? 'bg-primary-50 text-primary-600 group-hover:bg-primary-600 group-hover:text-white group-hover:shadow-md' : 'bg-surface-200 text-surface-500'">
                                    <component :is="IconoDeporte(reservaPayload.disciplinaSeleccionada)"
                                        class="w-8 h-8 fill-current" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-base text-surface-900 truncate leading-snug">{{
                                        cancha.nombre_espacio }}
                                    </div>
                                    <div class="text-xs text-surface-400 font-semibold uppercase tracking-widest mt-1">
                                        {{ reservaPayload.disciplinaSeleccionada }}
                                    </div>
                                </div>
                            </div>

                            <div class="shrink-0 ml-4 hidden sm:block">
                                <span class="px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                    :class="{
                                        'bg-green-100 text-green-800 border border-green-200': (cancha.estatus === 'Disponible' || cancha.estatus === 'DISPONIBLE'),
                                        'bg-red-100 text-red-800 border border-red-200': cancha.estatus === 'Bloqueado por Mantenimiento' || cancha.estatus === 'MANTENIMIENTO',
                                        'bg-yellow-100 text-yellow-800 border border-yellow-200': cancha.estatus === 'Lleno/No Disponible'
                                    }">
                                    {{ cancha.estatus === 'Lleno/No Disponible' ? 'Lleno' : ((cancha.estatus ===
                                        'Disponible' ||
                                        cancha.estatus === 'DISPONIBLE') ? 'Disponible' : 'Mantenimiento') }}
                                </span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- PASO 3: HORARIOS (NEW GRID) -->
                <div v-if="pasoActual === '3'" class="flex flex-col w-full">
                    <!-- Selector de duración -->
                    <div
                        class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-5 bg-surface-50/80 border border-surface-200 p-5 rounded-[2rem] shadow-sm">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-white rounded-xl shadow-sm border border-surface-100 flex items-center justify-center text-primary-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <polyline points="12 6 12 12 16 14" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-bold text-surface-900 text-sm leading-tight">Duración de reserva</div>
                                <div class="text-xs text-surface-500 font-medium mt-0.5">Define cuánto tiempo jugarás
                                </div>
                            </div>
                        </div>
                        <div class="flex p-1 bg-surface-200/50 rounded-xl w-full md:w-auto overflow-hidden">
                            <button @click="formDuration = 60"
                                :class="formDuration === 60 ? 'bg-white shadow border border-surface-100 font-bold text-primary-700' : 'text-surface-500 font-bold hover:bg-white/50'"
                                class="flex-1 md:flex-none px-5 py-2.5 rounded-lg text-sm transition-all focus:outline-none">1
                                hr</button>
                            <button @click="formDuration = 90"
                                :class="formDuration === 90 ? 'bg-white shadow border border-surface-100 font-bold text-primary-700' : 'text-surface-500 font-bold hover:bg-white/50'"
                                class="flex-1 md:flex-none px-5 py-2.5 rounded-lg text-sm transition-all focus:outline-none">1.5
                                hrs</button>
                            <button @click="formDuration = 120"
                                :class="formDuration === 120 ? 'bg-white shadow border border-surface-100 font-bold text-primary-700' : 'text-surface-500 font-bold hover:bg-white/50'"
                                class="flex-1 md:flex-none px-5 py-2.5 rounded-lg text-sm transition-all focus:outline-none">2
                                hrs</button>
                        </div>
                    </div>

                    <!-- Opciones por Turno -->
                    <div class="flex flex-col gap-10">
                        <div v-for="(horas, nomGrupo) in horariosGrupados" :key="nomGrupo">
                            <div class="flex items-center gap-3 mb-5 border-b border-surface-100 pb-3">
                                <svg v-if="nomGrupo === 'Mañana'" xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5 text-yellow-500" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="4" />
                                    <path d="M12 2v2" />
                                    <path d="M12 20v2" />
                                    <path d="m4.93 4.93 1.41 1.41" />
                                    <path d="m17.66 17.66 1.41 1.41" />
                                    <path d="M2 12h2" />
                                    <path d="M20 12h2" />
                                    <path d="m6.34 17.66-1.41 1.41" />
                                    <path d="m19.07 4.93-1.41 1.41" />
                                </svg>
                                <svg v-if="nomGrupo === 'Tarde'" xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5 text-orange-500" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="4" />
                                    <path d="m17.66 17.66 1.41 1.41" />
                                    <path d="M20 12h2" />
                                    <path d="m19.07 4.93-1.41 1.41" />
                                </svg>
                                <svg v-if="nomGrupo === 'Noche'" xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5 text-indigo-500" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                                </svg>

                                <h4 class="text-xs font-bold uppercase tracking-wider text-surface-900 m-0">{{ nomGrupo
                                }}</h4>
                            </div>

                            <div v-if="horas.length === 0"
                                class="text-surface-400 font-medium text-sm p-4 border-2 border-dashed border-surface-200 rounded-2xl text-center">
                                No hay horarios disponibles en este turno.
                            </div>

                            <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 md:gap-4">
                                <button v-for="hora in horas" :key="hora"
                                    @click="getSlotStatus(hora).blocked ? null : (horaInicioTemp = hora)"
                                    :disabled="getSlotStatus(hora).blocked"
                                    class="py-3.5 px-2 border-2 rounded-2xl text-center font-bold transition-all focus:outline-none relative overflow-hidden group"
                                    :class="{
                                        'bg-primary-600 border-primary-600 text-white shadow-lg -translate-y-1': horaInicioTemp === hora,
                                        'bg-white border-surface-200 text-surface-700 hover:border-primary-400 hover:text-primary-700 hover:-translate-y-0.5 hover:shadow-sm active:scale-95 cursor-pointer': horaInicioTemp !== hora && !getSlotStatus(hora).blocked,
                                        'bg-surface-100/50 border-surface-200 text-surface-300 opacity-40 cursor-not-allowed hidden-hover': getSlotStatus(hora).reason === 'PAST',
                                        'bg-surface-100/50 border-surface-200 text-surface-400 opacity-70 cursor-not-allowed hidden-hover line-through decoration-surface-400 decoration-2': getSlotStatus(hora).reason === 'OCCUPIED',
                                        'bg-orange-50 border-orange-200 text-orange-600 opacity-80 cursor-not-allowed hidden-hover': getSlotStatus(hora).reason === 'PERSONAL'
                                    }">
                                    {{ formatearHora(hora) }}
                                    <span v-if="getSlotStatus(hora).blocked"
                                        class="absolute text-[9px] uppercase tracking-wider bottom-0.5 left-0 w-full text-center font-bold leading-none no-underline"
                                        :class="{
                                            'text-surface-400': getSlotStatus(hora).reason === 'PAST' || getSlotStatus(hora).reason === 'OCCUPIED',
                                            'text-orange-600': getSlotStatus(hora).reason === 'PERSONAL'
                                        }">
                                        {{ getSlotStatus(hora).label }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Status Flotante -->
                    <div ref="bottomStatusRef"
                        class="mt-12 p-6 md:p-8 bg-surface-50 border border-surface-200 rounded-[2rem] flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-sm">
                        <div class="flex-1">
                            <div v-if="errorValidacion"
                                class="flex items-center gap-3 text-red-600 font-semibold text-sm bg-red-50 p-4 rounded-xl border border-red-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                {{ errorValidacion }}
                            </div>
                            <div v-else-if="esHorarioValidoParaPreview"
                                class="flex items-center gap-3 text-green-700 font-semibold text-sm bg-green-50 p-4 rounded-xl border border-green-200">
                                <div
                                    class="w-8 h-8 rounded-full bg-green-200 text-green-800 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="leading-tight text-sm">Aseguraremos tu lugar de <br class="md:hidden"><span
                                        class="text-green-800 font-bold">{{ formatearHora(horaInicioTemp) }} a {{
                                            formatearHora(horaFinTemp) }}</span></span>
                            </div>
                            <div v-else
                                class="text-surface-500 font-medium text-sm md:text-base flex items-center gap-3 bg-white p-4 rounded-xl border border-surface-200">
                                <i class="pi pi-info-circle text-xl"></i>
                                Selecciona tu bloque de horario inicial para continuar.
                            </div>
                        </div>
                        <button @click="validarHorario()" :disabled="!esHorarioValidoParaPreview || cargando"
                            class="w-full md:w-auto px-8 py-3.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl shadow-md transition-all flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed border-none text-base active:scale-95">
                            <template v-if="cargando">
                                <div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin">
                                </div>
                                Reservando...
                            </template>
                            <template v-else>
                                Confirmar Horario <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14" />
                                    <path d="m12 5 7 7-7 7" />
                                </svg>
                            </template>
                        </button>
                    </div>
                </div>

                <!-- PASO 4 -->
                <div v-if="pasoActual === '4'">
                    <StepAcompanantes />
                </div>

                <!-- PASO 5 -->
                <div v-if="pasoActual === '5'">
                    <StepConfirmacion />
                </div>

            </div>
        </div>
    </div>

</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-100%);
}
</style>