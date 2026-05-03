<script setup>
import { ref, computed, watch } from 'vue'
import { useSocioStore } from '@/stores/admin/socioStore'
import { useAlerts } from '@/composables/useAlerts'

const props = defineProps({
  socio: { type: Object, default: null },
  modelValue: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const socioStore = useSocioStore()
const { toastInfo } = useAlerts()

const isSaving       = ref(false)
const selectedStatus = ref('SIN_PENALIZACION')
const diasReserva    = ref(7)
const diasLudoteca   = ref(7)

const DIAS_OPTIONS = [1, 3, 5, 7]

// Resetear al abrir con un socio distinto
watch(() => props.socio, (s) => {
  if (s) {
    selectedStatus.value = s.estatus_penalizacion ?? 'SIN_PENALIZACION'
    diasReserva.value    = 7
    diasLudoteca.value   = 7
  }
}, { immediate: true })

const close = () => emit('update:modelValue', false)

// Controla qué selectores están habilitados
const reservaActiva  = computed(() => ['PENALIZADO_RESERVA',  'PENALIZADO_AMBOS'].includes(selectedStatus.value))
const ludotecaActiva = computed(() => ['PENALIZADO_LUDOTECA', 'PENALIZADO_AMBOS'].includes(selectedStatus.value))

// ¿Hubo algún cambio real respecto al estado guardado?
const hasChanges = computed(() => {
  if (selectedStatus.value !== (props.socio?.estatus_penalizacion ?? 'SIN_PENALIZACION')) return true
  // Si el status no cambió pero era una penalización activa, los días sí pueden cambiar
  if (reservaActiva.value  && diasReserva.value   !== 7) return true
  if (ludotecaActiva.value && diasLudoteca.value  !== 7) return true
  return false
})

const PENALTY_CARDS = [
  {
    label:    'Sin Penalización',
    sublabel: 'Acceso completo a reservas y ludoteca',
    status:   'SIN_PENALIZACION',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
             <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
             <polyline points="22 4 12 14.01 9 11.01"/>
           </svg>`,
    bg:       'bg-emerald-50',
    bgActive: 'bg-emerald-100',
    border:   'border-emerald-200',
    ring:     'ring-emerald-400',
    text:     'text-emerald-700',
    dot:      'bg-emerald-500',
  },
  {
    label:    'Penalizar Reservas',
    sublabel: 'Bloquea reservaciones de cancha (No Show)',
    status:   'PENALIZADO_RESERVA',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
             <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
             <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
             <line x1="3" y1="10" x2="21" y2="10"/>
             <line x1="9" y1="15" x2="15" y2="15"/>
           </svg>`,
    bg:       'bg-red-50',
    bgActive: 'bg-red-100',
    border:   'border-red-200',
    ring:     'ring-red-400',
    text:     'text-red-700',
    dot:      'bg-red-500',
  },
  {
    label:    'Penalizar Ludoteca',
    sublabel: 'Bloquea acceso a ludoteca (Retrasos)',
    status:   'PENALIZADO_LUDOTECA',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
             <circle cx="12" cy="12" r="10"/>
             <polyline points="12 6 12 12 16 14"/>
           </svg>`,
    bg:       'bg-amber-50',
    bgActive: 'bg-amber-100',
    border:   'border-amber-200',
    ring:     'ring-amber-400',
    text:     'text-amber-700',
    dot:      'bg-amber-500',
  },
  {
    label:    'Penalizar Ambos',
    sublabel: 'Bloquea reservas y ludoteca simultáneamente',
    status:   'PENALIZADO_AMBOS',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
             <path d="M12 9v4M12 17h.01"/>
             <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
           </svg>`,
    bg:       'bg-purple-50',
    bgActive: 'bg-purple-100',
    border:   'border-purple-200',
    ring:     'ring-purple-400',
    text:     'text-purple-700',
    dot:      'bg-purple-500',
  },
]

const activeCard = computed(() => PENALTY_CARDS.find(c => c.status === selectedStatus.value))

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

const save = async () => {
  if (!props.socio) return
  isSaving.value = true
  try {
    const res = await socioStore.updatePenalizacion(props.socio.id_socio, {
      estatus_penalizacion:        selectedStatus.value,
      dias_penalizacion_reserva:   reservaActiva.value  ? diasReserva.value  : undefined,
      dias_penalizacion_ludoteca:  ludotecaActiva.value ? diasLudoteca.value : undefined,
    })
    if (res.success) {
      toastInfo('Actualizado', 'Penalización guardada correctamente.', 'success')
      close()
    } else {
      toastInfo('Error', res.error, 'error')
    }
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100" leave-to-class="opacity-0"
    >
      <div v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-surface-900/60 backdrop-blur-sm"
        @click.self="close"
      >
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          enter-to-class="opacity-100 scale-100 translate-y-0"
        >
          <div v-if="modelValue"
            class="bg-white w-full max-w-4xl rounded-[2rem] shadow-2xl overflow-hidden flex flex-col max-h-[94vh]"
          >
            <!-- ── Cabecera ── -->
            <div class="flex items-center justify-between px-8 py-6 bg-white border-b border-surface-100">
              <div class="flex items-center gap-4">
                <div
                  class="w-12 h-12 rounded-2xl bg-linear-to-br flex items-center justify-center
                         text-white font-black text-base shadow-sm shrink-0"
                  :class="avatarGradient(socio?.nombre_completo)"
                >
                  {{ initials(socio?.nombre_completo ?? '') }}
                </div>
                <div>
                  <h2 class="text-xl font-black text-surface-900 leading-tight">Gestionar Penalizaciones</h2>
                  <p class="text-xs font-extrabold text-surface-500 mt-0.5 uppercase tracking-widest truncate max-w-xs">
                    {{ socio?.nombre_completo }}
                    <span class="text-surface-300 font-bold normal-case tracking-normal"> · </span>
                    <span class="font-mono text-surface-400 normal-case tracking-normal">#{{ socio?.numero_accion }}</span>
                  </p>
                </div>
              </div>
              <button @click="close"
                class="w-10 h-10 rounded-xl bg-surface-100 hover:bg-surface-200
                       flex items-center justify-center text-surface-500 transition-colors shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- ── Cuerpo ── -->
            <div class="overflow-y-auto bg-surface-50/40">
              <div class="p-8 grid grid-cols-1 lg:grid-cols-[1fr_1.6fr] gap-8">

                <!-- COLUMNA IZQUIERDA: KPIs + selectores de días -->
                <div class="flex flex-col gap-6">
                  <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-surface-500 mb-3">Historial de Incidencias</p>

                    <!-- KPI: No Shows -->
                    <div class="bg-white rounded-[1.5rem] border border-surface-200 shadow-sm overflow-hidden mb-4">
                      <div class="h-1.5 transition-all duration-500"
                           :class="(socio?.contador_no_shows ?? 0) > 0 ? 'bg-red-500' : 'bg-surface-100'"/>
                      <div class="p-6 flex items-center justify-between">
                        <div>
                          <p class="text-[10px] font-black uppercase tracking-widest text-surface-500 mb-1">No Shows</p>
                          <p class="text-5xl font-black leading-none transition-colors"
                             :class="(socio?.contador_no_shows ?? 0) > 0 ? 'text-red-600' : 'text-surface-300'">
                            {{ socio?.contador_no_shows ?? 0 }}
                          </p>
                          <p class="text-xs font-semibold mt-2"
                             :class="(socio?.contador_no_shows ?? 0) > 0 ? 'text-red-500 font-bold' : 'text-surface-400'">
                            {{ (socio?.contador_no_shows ?? 0) > 0 ? 'Reservas canceladas sin aviso' : 'Sin incidencias' }}
                          </p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 transition-colors"
                             :class="(socio?.contador_no_shows ?? 0) > 0 ? 'bg-red-50 text-red-500' : 'bg-surface-100 text-surface-300'">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-7 h-7">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                            <line x1="9" y1="15" x2="15" y2="15"/>
                          </svg>
                        </div>
                      </div>
                    </div>

                    <!-- KPI: Retrasos Ludoteca -->
                    <div class="bg-white rounded-[1.5rem] border border-surface-200 shadow-sm overflow-hidden">
                      <div class="h-1.5 transition-all duration-500"
                           :class="(socio?.retrasos_ludoteca ?? 0) > 0 ? 'bg-amber-500' : 'bg-surface-100'"/>
                      <div class="p-6 flex items-center justify-between">
                        <div>
                          <p class="text-[10px] font-black uppercase tracking-widest text-surface-500 mb-1">Retrasos Ludoteca</p>
                          <p class="text-5xl font-black leading-none transition-colors"
                             :class="(socio?.retrasos_ludoteca ?? 0) > 0 ? 'text-amber-600' : 'text-surface-300'">
                            {{ socio?.retrasos_ludoteca ?? 0 }}
                          </p>
                          <p class="text-xs font-semibold mt-2"
                             :class="(socio?.retrasos_ludoteca ?? 0) > 0 ? 'text-amber-500 font-bold' : 'text-surface-400'">
                            {{ (socio?.retrasos_ludoteca ?? 0) > 0 ? 'Incidencias de puntualidad' : 'Sin incidencias' }}
                          </p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 transition-colors"
                             :class="(socio?.retrasos_ludoteca ?? 0) > 0 ? 'bg-amber-50 text-amber-500' : 'bg-surface-100 text-surface-300'">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-7 h-7">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                          </svg>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- ── SELECTORES DE DÍAS ── -->
                  <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-surface-100">
                      <p class="text-[10px] font-black uppercase tracking-widest text-surface-500">Duración de la Sanción</p>
                    </div>
                    <div class="p-5 flex flex-col gap-4">

                      <!-- Días Reserva -->
                      <div>
                        <div class="flex items-center justify-between mb-2">
                          <label class="text-xs font-black text-surface-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-400 inline-block"/>
                            Días Reserva
                          </label>
                          <span v-if="!reservaActiva" class="text-[10px] font-bold text-surface-300 uppercase tracking-wide">
                            No aplica
                          </span>
                        </div>
                        <div class="flex gap-2">
                          <button
                            v-for="d in DIAS_OPTIONS"
                            :key="'r' + d"
                            :disabled="!reservaActiva || isSaving"
                            @click="diasReserva = d"
                            class="flex-1 py-2 rounded-xl text-xs font-black border-2 transition-all duration-150
                                   disabled:opacity-30 disabled:cursor-not-allowed"
                            :class="diasReserva === d && reservaActiva
                              ? 'bg-red-500 border-red-500 text-white shadow-sm'
                              : 'bg-surface-50 border-surface-200 text-surface-500 hover:border-red-300 hover:text-red-600'"
                          >
                            {{ d }}d
                          </button>
                        </div>
                      </div>

                      <!-- Días Ludoteca -->
                      <div>
                        <div class="flex items-center justify-between mb-2">
                          <label class="text-xs font-black text-surface-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-400 inline-block"/>
                            Días Ludoteca
                          </label>
                          <span v-if="!ludotecaActiva" class="text-[10px] font-bold text-surface-300 uppercase tracking-wide">
                            No aplica
                          </span>
                        </div>
                        <div class="flex gap-2">
                          <button
                            v-for="d in DIAS_OPTIONS"
                            :key="'l' + d"
                            :disabled="!ludotecaActiva || isSaving"
                            @click="diasLudoteca = d"
                            class="flex-1 py-2 rounded-xl text-xs font-black border-2 transition-all duration-150
                                   disabled:opacity-30 disabled:cursor-not-allowed"
                            :class="diasLudoteca === d && ludotecaActiva
                              ? 'bg-amber-500 border-amber-500 text-white shadow-sm'
                              : 'bg-surface-50 border-surface-200 text-surface-500 hover:border-amber-300 hover:text-amber-600'"
                          >
                            {{ d }}d
                          </button>
                        </div>
                      </div>

                    </div>
                  </div>

                  <!-- Estatus de cuenta (solo lectura) -->
                  <div class="bg-white rounded-2xl border border-surface-200 p-5 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest text-surface-500 mb-2">Estatus de Cuenta</p>
                    <div class="flex items-center gap-2.5">
                      <div class="w-2 h-2 rounded-full"
                           :class="{
                             'bg-emerald-500': socio?.estatus_cuenta === 'AL_CORRIENTE',
                             'bg-red-500':     socio?.estatus_cuenta === 'MOROSO',
                             'bg-surface-400': socio?.estatus_cuenta === 'SUSPENDIDO',
                           }"/>
                      <span class="text-sm font-black text-surface-800">{{ socio?.estatus_cuenta ?? '—' }}</span>
                    </div>
                    <p class="text-[11px] text-surface-400 font-medium mt-2">
                      Este campo es independiente de las penalizaciones y se gestiona desde el módulo de pagos.
                    </p>
                  </div>
                </div>

                <!-- COLUMNA DERECHA: Tarjetas de penalización -->
                <div class="flex flex-col gap-4">
                  <p class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Estatus de Penalización</p>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button
                      v-for="card in PENALTY_CARDS"
                      :key="card.status"
                      @click="selectedStatus = card.status"
                      :disabled="isSaving"
                      class="relative flex flex-col gap-3 p-5 rounded-[1.5rem] border-2 text-left
                             transition-all duration-200 disabled:opacity-40 cursor-pointer
                             hover:-translate-y-0.5 hover:shadow-md focus:outline-none"
                      :class="selectedStatus === card.status
                        ? [card.bgActive, card.border, 'ring-2', card.ring, 'shadow-md', '-translate-y-0.5']
                        : [card.bg, card.border, 'opacity-80 hover:opacity-100']"
                    >
                      <!-- Icono + check activo -->
                      <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors"
                             :class="selectedStatus === card.status
                               ? [card.bgActive, card.text]
                               : ['bg-white/60', card.text]">
                          <span v-html="card.icon"/>
                        </div>
                        <Transition
                          enter-active-class="transition-all duration-200 ease-out"
                          enter-from-class="opacity-0 scale-50"
                          enter-to-class="opacity-100 scale-100"
                          leave-active-class="transition-all duration-150 ease-in"
                          leave-from-class="opacity-100 scale-100"
                          leave-to-class="opacity-0 scale-50"
                        >
                          <div v-if="selectedStatus === card.status"
                               class="w-6 h-6 rounded-full flex items-center justify-center text-white shadow-sm"
                               :class="card.dot">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="w-3.5 h-3.5">
                              <polyline points="20 6 9 17 4 12"/>
                            </svg>
                          </div>
                        </Transition>
                      </div>
                      <!-- Textos -->
                      <div>
                        <p class="text-sm font-black leading-tight" :class="card.text">{{ card.label }}</p>
                        <p class="text-xs font-semibold mt-1 leading-relaxed"
                           :class="selectedStatus === card.status ? card.text + ' opacity-70' : 'text-surface-500'">
                          {{ card.sublabel }}
                        </p>
                      </div>
                    </button>
                  </div>

                  <!-- Resumen de lo que se va a aplicar -->
                  <div class="mt-2 rounded-2xl bg-white border border-surface-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-3 flex items-center gap-3 border-b border-surface-100">
                      <div class="w-2.5 h-2.5 rounded-full shrink-0" :class="activeCard?.dot ?? 'bg-surface-300'"/>
                      <div class="flex-1 min-w-0">
                        <p class="text-[10px] font-black uppercase tracking-widest text-surface-400">Penalización a aplicar</p>
                        <p class="text-sm font-black text-surface-900 truncate">{{ activeCard?.label ?? selectedStatus }}</p>
                      </div>
                      <span
                        v-if="!hasChanges"
                        class="text-[10px] font-black uppercase tracking-widest text-surface-400 bg-surface-100 px-2.5 py-1 rounded-full shrink-0"
                      >
                        Sin cambios
                      </span>
                      <span v-else
                        class="text-[10px] font-black uppercase tracking-widest text-primary-600 bg-primary-50 px-2.5 py-1 rounded-full shrink-0"
                      >
                        Modificado
                      </span>
                    </div>

                    <!-- Detalle de días cuando aplica -->
                    <div v-if="selectedStatus !== 'SIN_PENALIZACION'" class="px-5 py-3 flex gap-6">
                      <div v-if="reservaActiva" class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"/>
                        <span class="text-xs font-semibold text-surface-600">Reservas:</span>
                        <span class="text-xs font-black text-red-600">{{ diasReserva }} día{{ diasReserva > 1 ? 's' : '' }}</span>
                      </div>
                      <div v-if="ludotecaActiva" class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"/>
                        <span class="text-xs font-semibold text-surface-600">Ludoteca:</span>
                        <span class="text-xs font-black text-amber-600">{{ diasLudoteca }} día{{ diasLudoteca > 1 ? 's' : '' }}</span>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- ── Pie ── -->
            <div class="flex items-center justify-between gap-3 px-8 py-5 border-t border-surface-100 bg-white">
              <p class="text-xs font-semibold text-surface-400 hidden sm:block">
                Solo se actualiza <span class="font-black text-surface-600">estatus_penalizacion</span> — el estatus de cuenta no se modifica.
              </p>
              <div class="flex items-center gap-3 ml-auto">
                <button @click="close"
                  class="px-6 py-3 rounded-xl border border-surface-200 bg-white
                         text-sm font-bold text-surface-700 hover:bg-surface-50 transition-colors">
                  Cancelar
                </button>
                <button
                  @click="save"
                  :disabled="isSaving || !hasChanges"
                  class="px-7 py-3 rounded-xl bg-primary-600 text-white text-sm font-black
                         hover:bg-primary-700 active:bg-primary-800 transition-colors shadow-sm
                         disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
                >
                  <svg v-if="isSaving" class="w-4 h-4 animate-spin"
                       viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                  </svg>
                  {{ isSaving ? 'Guardando…' : 'Guardar cambios' }}
                </button>
              </div>
            </div>

          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
