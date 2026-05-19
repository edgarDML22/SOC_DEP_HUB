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
const isHotSwap = ref(false)

// ── COMPUTED ────────────────────────────────────────────────
const isEditing = computed(() => !!props.encuentro?.id_arbitro_asignado)

const espacioOptions = computed(() =>
    espaciosActivos.value.map(e => ({
        label: e.nombre_espacio,
        value: e.id_espacio,
    }))
)

const arbitrosDisponiblesOptions = computed(() =>
    (arbitrosPool.value?.disponibles || []).map(a => ({
        label: a.nombre,
        value: a.id_instructor,
        ocupado: false,
    }))
)

const arbitrosOcupadosOptions = computed(() =>
    (arbitrosPool.value?.ocupados || []).map(a => ({
        label: `${a.nombre} — Ocupado`,
        value: a.id_instructor,
        ocupado: true,
    }))
)

const allArbitroOptions = computed(() => [
    ...arbitrosDisponiblesOptions.value,
    ...arbitrosOcupadosOptions.value,
])

const horarioCompleto = computed(() =>
    fechaInicio.value && fechaFin.value
)

const fechaFinValida = computed(() => {
    if (!fechaInicio.value || !fechaFin.value) return true
    return new Date(fechaFin.value) > new Date(fechaInicio.value)
})

const canSave = computed(() =>
    selectedEspacio.value &&
    fechaInicio.value &&
    fechaFin.value &&
    fechaFinValida.value &&
    selectedArbitro.value &&
    !loadingStates.value.asignando
)

const encounterTitle = computed(() => {
    const enc = props.encuentro
    const c1 = enc?.comp1Name || enc?.extendedProps?.comp1Name || enc?.competidor1?.nombre || 'TBD'
    const c2 = enc?.comp2Name || enc?.extendedProps?.comp2Name || enc?.competidor2?.nombre || 'TBD'
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
        await store.fetchArbitrosDisponibles(props.idTorneo, inicio, fin)
    }
})

// ── HELPERS ─────────────────────────────────────────────────
const formatDateForApi = (date) => {
    if (!date) return null
    const d = new Date(date)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`
}

const formatDateDisplay = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleString('es-MX', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    })
}

// ── ACTIONS ─────────────────────────────────────────────────
const handleSave = async () => {
    if (!canSave.value) return

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

const handleHotSwap = async () => {
    if (!selectedArbitro.value) return

    const confirmed = await confirmWarning(
        '¿Cambiar árbitro?',
        'Se reemplazará el árbitro asignado actualmente a este encuentro.',
        'Sí, cambiar'
    )

    if (!confirmed.isConfirmed) return

    const enc = props.encuentro
    const payload = {
        id_arbitro: selectedArbitro.value,
        id_espacio: enc.id_espacio || selectedEspacio.value,
        fecha_hora_inicio: enc.fecha_hora_inicio || formatDateForApi(fechaInicio.value),
        fecha_hora_fin: enc.fecha_hora_fin || formatDateForApi(fechaFin.value),
    }

    const idEncuentro = enc?.id_encuentro || enc?.extendedProps?.id_encuentro

    const result = await store.asignarEncuentro(idEncuentro, payload)

    if (result.success) {
        toastSuccess('Árbitro actualizado correctamente')
        emit('saved')
        emit('close')
    } else {
        toastError(result.message || 'Error al cambiar el árbitro')
    }
}

// ── INIT ────────────────────────────────────────────────────
onMounted(async () => {
    await store.fetchEspacios()

    const enc = props.encuentro
    if (enc) {
        // Pre-rellenar si ya tiene datos
        if (enc.id_espacio) selectedEspacio.value = enc.id_espacio
        if (enc.fecha_hora_inicio) fechaInicio.value = new Date(enc.fecha_hora_inicio)
        if (enc.fecha_hora_fin) fechaFin.value = new Date(enc.fecha_hora_fin)
        if (enc.id_arbitro_asignado) {
            selectedArbitro.value = enc.id_arbitro_asignado
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
                                {{ isEditing ? 'Editar Asignación' : 'Asignar Encuentro' }}
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
                <div class="px-7 py-6 space-y-5">
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
                            <!-- Hot-swap mode para encuentros ya asignados -->
                            <div v-if="isEditing && !isHotSwap"
                                class="p-4 rounded-xl bg-surface-50 border border-surface-200 space-y-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <p class="text-xs font-bold text-surface-700">
                                        Árbitro actual: <span class="text-surface-900">{{
                                            encuentro?.arbitro_nombre || `ID: ${encuentro?.id_arbitro_asignado}`
                                            }}</span>
                                    </p>
                                </div>
                                <button @click="isHotSwap = true"
                                    class="w-full py-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold hover:bg-amber-100 transition-colors">
                                    Cambiar Árbitro
                                </button>
                            </div>

                            <!-- Select de árbitro (nueva asignación o hot-swap) -->
                            <div v-if="!isEditing || isHotSwap">
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

                <!-- Footer -->
                <div class="sticky bottom-0 bg-white px-7 py-5 border-t border-surface-100 flex gap-3">
                    <button @click="emit('close')"
                        class="flex-1 py-3 rounded-xl bg-white border border-surface-200 text-surface-700 text-sm font-bold hover:bg-surface-50 transition-colors">
                        Cancelar
                    </button>
                    <button v-if="isEditing && isHotSwap" @click="handleHotSwap" :disabled="!selectedArbitro || loadingStates.asignando"
                        class="flex-1 py-3 rounded-xl bg-amber-500 text-white text-sm font-bold hover:bg-amber-600 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <LoadingSpinner v-if="loadingStates.asignando" size="sm" color="white" />
                        <span>{{ loadingStates.asignando ? 'Cambiando...' : 'Cambiar Árbitro' }}</span>
                    </button>
                    <button v-else @click="handleSave" :disabled="!canSave"
                        class="flex-1 py-3 rounded-xl bg-surface-900 text-white text-sm font-bold hover:bg-primary-600 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <LoadingSpinner v-if="loadingStates.asignando" size="sm" color="white" />
                        <span>{{ loadingStates.asignando ? 'Asignando...' : 'Guardar Asignación' }}</span>
                    </button>
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
