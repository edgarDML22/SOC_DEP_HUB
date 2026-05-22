<script setup>
/**
 * ModalAsignacionEncuentro — modal de asignación en cascada para encuentros de torneo.
 *
 * Flujo:
 *   1. Select de Espacio (solo activos)
 *   2. DateTimePicker de Inicio y Fin
 *   3. Select de Árbitro (habilitado tras elegir horario, con badges de disponibilidad)
 *   4. Guardar asignación
 *
 * Props:
 *   encuentro — objeto del encuentro a asignar
 *   idTorneo  — ID del torneo padre
 *
 * Emits:
 *   close   — cerrar modal
 *   saved   — asignación exitosa
 */
import { ref, computed, watch, onMounted } from 'vue'
import { useScheduleStore } from '@/stores/admin/scheduleStore'
import { useAlerts } from '@/composables/useAlerts'
import { storeToRefs } from 'pinia'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'

const props = defineProps({
    encuentro: { type: Object, required: true },
    idTorneo: { type: [Number, String], required: true },
})

const emit = defineEmits(['close', 'saved'])

const store = useScheduleStore()
const { espaciosActivos, arbitrosPool, loadingStates } = storeToRefs(store)
const { toastSuccess, toastError, confirmWarning } = useAlerts()

// ── FORM STATE ──────────────────────────────────────────────
const selectedEspacio = ref(null)
const fechaInicio = ref(null)
const fechaFin = ref(null)
const selectedArbitro = ref(null)

// ── MODES: 'RESUMEN', 'HOT_SWAP', 'FULL_FORM' ────────────────
const isEditing = computed(() => !!props.encuentro?.id_arbitro_asignado)
const currentMode = ref('FULL_FORM')

// ── COMPUTED ────────────────────────────────────────────────
const espacioOptions = computed(() =>
    espaciosActivos.value.map(e => ({
        label: e.nombre_espacio,
        value: e.id_espacio,
    }))
)

const activeSpaceName = computed(() => {
    const sp = espaciosActivos.value.find(e => e.id_espacio === selectedEspacio.value)
    return sp ? sp.nombre_espacio : 'Sin asignar'
})

const formattedRange = computed(() => {
    if (!fechaInicio.value || !fechaFin.value) return '—'
    return `${formatDateDisplay(fechaInicio.value)} - ${formatDateDisplay(fechaFin.value)}`
})

const currentRefereeName = computed(() => {
    const enc = props.encuentro
    const refObj = enc?.arbitro || enc?.extendedProps?.arbitro
    if (refObj?.nombre) return refObj.nombre
    if (refObj?.nombre_completo) return refObj.nombre_completo
    
    // Find in total referees
    const refInPool = store.arbitrosTotales?.find(a => a.id_instructor === selectedArbitro.value)
    if (refInPool?.nombre) return refInPool.nombre
    return 'Sin árbitro'
})

const getRefereeInitial = (nombre) => {
    if (!nombre) return '?'
    return nombre.charAt(0).toUpperCase()
}

const getRefereeBg = (nombre) => {
    const colors = [
        'bg-blue-100 text-blue-700',
        'bg-purple-100 text-purple-700',
        'bg-pink-100 text-pink-700',
        'bg-amber-100 text-amber-700',
        'bg-emerald-100 text-emerald-700',
        'bg-cyan-100 text-cyan-700',
    ]
    const idx = nombre ? nombre.charCodeAt(0) % colors.length : 0
    return colors[idx]
}

const arbitrosDisponiblesOptions = computed(() =>
    (arbitrosPool.value?.disponibles || []).map(a => ({
        label: a.nombre,
        value: a.id_instructor,
        ocupado: false,
    }))
)

const arbitrosOcupadosOptions = computed(() =>
    (arbitrosPool.value?.ocupados || []).map(a => ({
        label: `${a.nombre} — Ocupado (${a.motivo || 'Conflicto'})`,
        value: a.id_instructor,
        ocupado: true,
    }))
)

const allArbitroOptions = computed(() => {
    // In Hot-Swap mode, filter exclusively to AVAILABLE referees only!
    if (currentMode.value === 'HOT_SWAP') {
        return arbitrosDisponiblesOptions.value
    }
    return [
        ...arbitrosDisponiblesOptions.value,
        ...arbitrosOcupadosOptions.value,
    ]
})

const horarioCompleto = computed(() =>
    fechaInicio.value && fechaFin.value
)

const fechaFinValida = computed(() => {
    if (!fechaInicio.value || !fechaFin.value) return true
    return new Date(fechaFin.value) > new Date(fechaInicio.value)
})

const canSave = computed(() => {
    if (currentMode.value === 'HOT_SWAP') {
        return selectedArbitro.value && !loadingStates.value.asignando
    }
    return selectedEspacio.value &&
        fechaInicio.value &&
        fechaFin.value &&
        fechaFinValida.value &&
        selectedArbitro.value &&
        !loadingStates.value.asignando
})

const encounterTitle = computed(() => {
    const enc = props.encuentro
    const c1 = enc?.comp1Name || enc?.extendedProps?.comp1Name || enc?.competidor1?.nombre_equipo || enc?.competidor1?.nombre_completo || enc?.competidor1?.nombre || 'TBD'
    const c2 = enc?.comp2Name || enc?.extendedProps?.comp2Name || enc?.competidor2?.nombre_equipo || enc?.competidor2?.nombre_completo || enc?.competidor2?.nombre || 'TBD'
    return `${c1} vs ${c2}`
})

const encounterFase = computed(() =>
    props.encuentro?.fase_bracket
    || props.encuentro?.extendedProps?.faseBracket
    || props.encuentro?.fase
    || 'N/A'
)

// ── WATCHERS ────────────────────────────────────────────────
// Cuando se completa el horario, obtener disponibilidad de árbitros
watch(horarioCompleto, async (complete) => {
    if (complete && fechaFinValida.value) {
        const inicio = formatDateForApi(fechaInicio.value)
        const fin = formatDateForApi(fechaFin.value)
        const torneoId = props.idTorneo || props.encuentro?.extendedProps?.id_torneo || props.encuentro?.id_torneo
        if (torneoId) {
            await store.fetchArbitrosDisponibles(torneoId, inicio, fin)
        }
    }
})

// ── HELPERS ─────────────────────────────────────────────────
const parseDateSafe = (dateStr) => {
    if (!dateStr) return null
    if (dateStr instanceof Date) return dateStr
    const normalized = typeof dateStr === 'string' ? dateStr.replace(' ', 'T') : dateStr
    const parsed = new Date(normalized)
    return isNaN(parsed.getTime()) ? null : parsed
}

const formatDateForApi = (date) => {
    if (!date) return null
    const d = new Date(date)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`
}

const formatDateDisplay = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleString('es-MX', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    })
}

// ── ACTIONS ─────────────────────────────────────────────────
const startHotSwap = async () => {
    currentMode.value = 'HOT_SWAP'
    if (fechaInicio.value && fechaFin.value) {
        const inicio = formatDateForApi(fechaInicio.value)
        const fin = formatDateForApi(fechaFin.value)
        const torneoId = props.idTorneo || props.encuentro?.extendedProps?.id_torneo || props.encuentro?.id_torneo
        if (torneoId) {
            await store.fetchArbitrosDisponibles(torneoId, inicio, fin)
        }
    }
}

const cancelHotSwap = () => {
    const enc = props.encuentro
    if (enc && enc.id_arbitro_asignado) {
        selectedArbitro.value = enc.id_arbitro_asignado
    }
    currentMode.value = 'RESUMEN'
}

const startFullForm = async () => {
    currentMode.value = 'FULL_FORM'
    if (fechaInicio.value && fechaFin.value) {
        const inicio = formatDateForApi(fechaInicio.value)
        const fin = formatDateForApi(fechaFin.value)
        const torneoId = props.idTorneo || props.encuentro?.extendedProps?.id_torneo || props.encuentro?.id_torneo
        if (torneoId) {
            await store.fetchArbitrosDisponibles(torneoId, inicio, fin)
        }
    }
}

const handleSave = async () => {
    if (!canSave.value) return

    // Hot-Swap specific validation via SweetAlert confirmation
    if (currentMode.value === 'HOT_SWAP') {
        const res = await confirmWarning(
            '¿Confirmar cambio de árbitro?',
            'Se reasignará este encuentro al árbitro seleccionado de forma inmediata.',
            'Sí, reasignar'
        )
        if (!res.isConfirmed) return
    }

    const payload = {
        id_arbitro: selectedArbitro.value,
        id_espacio: selectedEspacio.value,
        fecha_hora_inicio: formatDateForApi(fechaInicio.value),
        fecha_hora_fin: formatDateForApi(fechaFin.value),
    }

    const idEncuentro = props.encuentro?.id_encuentro
        || props.encuentro?.extendedProps?.id_encuentro

    const result = await store.asignarEncuentro(idEncuentro, payload)

    if (result.success) {
        toastSuccess('Encuentro asignado correctamente')
        emit('saved')
        emit('close')
    } else if (result.status === 409) {
        toastError(result.message)
    } else {
        toastError(result.message || 'Error al asignar el encuentro')
    }
}

// ── INIT ────────────────────────────────────────────────────
onMounted(async () => {
    await store.fetchEspacios()

    const enc = props.encuentro
    if (enc) {
        // Pre-rellenar si ya tiene datos
        if (enc.id_espacio) selectedEspacio.value = enc.id_espacio
        if (enc.fecha_hora_inicio) fechaInicio.value = parseDateSafe(enc.fecha_hora_inicio)
        if (enc.fecha_hora_fin) fechaFin.value = parseDateSafe(enc.fecha_hora_fin)
        if (enc.id_arbitro_asignado) {
            selectedArbitro.value = enc.id_arbitro_asignado
        }

        // Pre-cargar disponibilidad de árbitros si ya tenemos horario completo
        if (fechaInicio.value && fechaFin.value) {
            const inicio = formatDateForApi(fechaInicio.value)
            const fin = formatDateForApi(fechaFin.value)
            const torneoId = props.idTorneo || enc.extendedProps?.id_torneo || enc.id_torneo
            if (torneoId) {
                await store.fetchArbitrosDisponibles(torneoId, inicio, fin)
            }
        }
    }
})
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="emit('close')"></div>

            <!-- Modal -->
            <div
                class="relative bg-white rounded-3xl shadow-2xl border border-surface-200 w-full max-w-lg max-h-[90vh] overflow-y-auto z-10 animate-in">
                <!-- Header -->
                <div class="sticky top-0 bg-white z-10 px-7 pt-7 pb-4 border-b border-surface-100">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-surface-400 mb-1">
                                {{ currentMode === 'RESUMEN' ? 'Resumen de Programación' : (currentMode === 'HOT_SWAP' ? 'Intercambio Rápido de Árbitro' : 'Formulario de Programación') }}
                            </p>
                            <h2 class="text-lg font-black text-surface-900 leading-snug">
                                {{ encounterTitle }}
                            </h2>
                            <p class="text-xs font-bold text-surface-500 mt-0.5">
                                Fase: {{ encounterFase }}
                            </p>
                        </div>
                        <button @click="emit('close')"
                            class="w-8 h-8 rounded-xl bg-surface-100 hover:bg-surface-200 flex items-center justify-center transition-colors shrink-0">
                            <svg class="w-4 h-4 text-surface-500" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path d="M18 6L6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-7 py-6">
                    <!-- MODE 1: RESUMEN -->
                    <div v-if="currentMode === 'RESUMEN'" class="space-y-6">
                        <!-- Resumen Card -->
                        <div class="bg-surface-50 rounded-2xl border border-surface-200 p-5 space-y-4 shadow-inner">
                            <!-- Espacio -->
                            <div class="flex items-center justify-between border-b border-surface-100 pb-3">
                                <span class="text-[10px] font-black uppercase tracking-widest text-surface-400">Espacio Físico</span>
                                <span class="text-xs font-bold text-surface-800 bg-white border border-surface-200 px-3 py-1.5 rounded-lg shadow-sm">
                                    {{ activeSpaceName }}
                                </span>
                            </div>
                            <!-- Horario -->
                            <div class="flex items-center justify-between border-b border-surface-100 pb-3">
                                <span class="text-[10px] font-black uppercase tracking-widest text-surface-400">Horario</span>
                                <span class="text-xs font-bold text-surface-800 bg-white border border-surface-200 px-3 py-1.5 rounded-lg shadow-sm">
                                    {{ formattedRange }}
                                </span>
                            </div>
                            <!-- Árbitro -->
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-widest text-surface-400">Árbitro Asignado</span>
                                <div class="flex items-center gap-2 bg-white border border-surface-200 px-3 py-1.5 rounded-lg shadow-sm">
                                    <div class="w-6 h-6 rounded-md flex items-center justify-center text-[10px] font-black shrink-0"
                                         :class="getRefereeBg(currentRefereeName)">
                                        {{ getRefereeInitial(currentRefereeName) }}
                                    </div>
                                    <span class="text-xs font-bold text-surface-800">{{ currentRefereeName }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones en Resumen -->
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <button @click="startHotSwap"
                                class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 border-dashed border-primary-300 hover:border-primary-500 bg-primary-50/20 hover:bg-primary-50 text-primary-700 text-xs font-bold transition-all shadow-sm cursor-pointer">
                                <svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Cambiar Árbitro
                            </button>
                            <button @click="startFullForm"
                                class="flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-surface-200 hover:border-surface-300 hover:bg-surface-50 text-surface-700 text-xs font-bold transition-all shadow-sm cursor-pointer">
                                <svg class="w-4 h-4 text-surface-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Modificar Espacio/Hora
                            </button>
                        </div>
                    </div>

                    <!-- MODE 2: HOT_SWAP -->
                    <div v-else-if="currentMode === 'HOT_SWAP'" class="space-y-5">
                        <!-- Info alert -->
                        <div class="flex items-start gap-3 p-4 rounded-2xl bg-amber-50 border border-amber-100">
                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <p class="text-xs font-bold text-amber-800">Intercambio Rápido de Árbitro</p>
                                <p class="text-[11px] text-amber-700 mt-0.5">Se modificará únicamente el árbitro para este encuentro. El espacio físico y el horario programados se mantendrán fijos.</p>
                            </div>
                        </div>

                        <!-- Selector del árbitro (solo disponibles) -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400">
                                Árbitros Libres Disponibles
                            </label>
                            
                            <div v-if="loadingStates.arbitros" class="flex items-center gap-3 py-4 justify-center">
                                <LoadingSpinner size="sm" />
                                <span class="text-xs font-bold text-surface-400">Consultando árbitros disponibles...</span>
                            </div>

                            <template v-else>
                                <Select v-model="selectedArbitro" :options="allArbitroOptions" option-label="label"
                                    option-value="value" placeholder="Seleccionar nuevo árbitro..." class="w-full" />
                                <p class="text-[10px] text-surface-400 mt-1">
                                    Para evitar colisiones, solo se listan los instructores 100% libres en el bloque horario del encuentro ({{ formattedRange }}).
                                </p>
                            </template>
                        </div>
                    </div>

                    <!-- MODE 3: FULL_FORM -->
                    <div v-else class="space-y-5">
                        <!-- Paso 1: Espacio -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400">
                                <span
                                    class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-surface-900 text-white text-[9px] font-black mr-1.5">1</span>
                                Espacio Físico
                            </label>
                            <Select v-model="selectedEspacio" :options="espacioOptions" option-label="label"
                                option-value="value" placeholder="Seleccionar espacio..." class="w-full"
                                :loading="loadingStates.espacios" />
                        </div>

                        <!-- Paso 2: Fechas -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400">
                                <span
                                    class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-surface-900 text-white text-[9px] font-black mr-1.5">2</span>
                                Horario
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[10px] font-bold text-surface-500 mb-1">Inicio</p>
                                    <DatePicker v-model="fechaInicio" showTime hourFormat="24" dateFormat="dd/mm/yy"
                                        placeholder="Inicio" class="w-full" :manualInput="false" />
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-surface-500 mb-1">Fin</p>
                                    <DatePicker v-model="fechaFin" showTime hourFormat="24" dateFormat="dd/mm/yy"
                                        placeholder="Fin" class="w-full" :manualInput="false" />
                                </div>
                            </div>
                            <p v-if="!fechaFinValida" class="text-[11px] font-bold text-red-500 mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                La fecha de fin debe ser posterior al inicio.
                            </p>
                        </div>

                        <!-- Paso 3: Árbitro -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400">
                                <span
                                    class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-surface-900 text-white text-[9px] font-black mr-1.5">3</span>
                                Árbitro
                            </label>

                            <div v-if="!horarioCompleto"
                                class="flex items-center gap-2 px-4 py-3 rounded-xl bg-surface-50 border border-surface-200">
                                <svg class="w-4 h-4 text-surface-400 shrink-0" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <p class="text-xs font-medium text-surface-500">Selecciona un horario para ver los
                                    árbitros disponibles.</p>
                            </div>

                            <div v-else-if="loadingStates.arbitros" class="flex items-center gap-3 py-4 justify-center">
                                <LoadingSpinner size="sm" />
                                <span class="text-xs font-bold text-surface-400">Verificando disponibilidad...</span>
                            </div>

                            <template v-else>
                                <!-- Select de árbitro -->
                                <div>
                                    <Select v-model="selectedArbitro" :options="allArbitroOptions" option-label="label"
                                        option-value="value" placeholder="Seleccionar árbitro..." class="w-full"
                                        :optionDisabled="(opt) => opt.ocupado" />
                                    <p v-if="arbitrosOcupadosOptions.length > 0" class="text-[10px] text-surface-400 mt-1">
                                        Los árbitros marcados como "Ocupado" no están disponibles en el horario
                                        seleccionado.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Footer Adaptable por Modo -->
                <div class="sticky bottom-0 bg-white px-7 py-5 border-t border-surface-100 flex gap-3">
                    <!-- Resumen Mode Footer -->
                    <template v-if="currentMode === 'RESUMEN'">
                        <button @click="emit('close')"
                            class="w-full py-3 rounded-xl bg-surface-900 hover:bg-surface-800 text-white text-sm font-bold transition-all shadow-sm cursor-pointer text-center">
                            Cerrar Resumen
                        </button>
                    </template>

                    <!-- Hot-Swap Mode Footer -->
                    <template v-else-if="currentMode === 'HOT_SWAP'">
                        <button @click="cancelHotSwap"
                            class="flex-1 py-3 rounded-xl bg-white border border-surface-200 text-surface-700 text-sm font-bold hover:bg-surface-50 transition-colors cursor-pointer">
                            Regresar
                        </button>
                        <button @click="handleSave" :disabled="!canSave"
                            class="flex-1 py-3 rounded-xl bg-surface-900 text-white text-sm font-bold hover:bg-primary-600 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer">
                            <LoadingSpinner v-if="loadingStates.asignando" size="sm" color="white" class="!mx-0" />
                            <span>{{ loadingStates.asignando ? 'Guardando...' : 'Reasignar Árbitro' }}</span>
                        </button>
                    </template>

                    <!-- Full Form Mode Footer -->
                    <template v-else>
                        <button @click="isEditing ? currentMode = 'RESUMEN' : emit('close')"
                            class="flex-1 py-3 rounded-xl bg-white border border-surface-200 text-surface-700 text-sm font-bold hover:bg-surface-50 transition-colors cursor-pointer">
                            {{ isEditing ? 'Atrás' : 'Cancelar' }}
                        </button>
                        <button @click="handleSave" :disabled="!canSave"
                            class="flex-1 py-3 rounded-xl bg-surface-900 text-white text-sm font-bold hover:bg-primary-600 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer">
                            <LoadingSpinner v-if="loadingStates.asignando" size="sm" color="white" class="!mx-0" />
                            <span>{{ loadingStates.asignando ? 'Guardando...' : (isEditing ? 'Guardar Cambios' : 'Guardar Asignación') }}</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.animate-in {
    animation: modalIn 0.3s ease-out;
}

@keyframes modalIn {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
