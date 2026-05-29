<script setup>
import { ref, computed } from 'vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'

const props = defineProps({
  sesiones: {
    type: Array,
    required: true,
  },
  todasLasDisciplinas: {
    type: Array,
    default: () => [],
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['select-sesion'])

// ─── Filtro lateral por disciplina (multi-select, máximo 3) ──────────────────
const panelAbierto = ref(true)

const disciplinasUnicas = computed(() => {
  if (props.todasLasDisciplinas.length > 0) return props.todasLasDisciplinas
  const seen = new Set()
  for (const s of props.sesiones) {
    if (s.disciplina) seen.add(s.disciplina)
  }
  return [...seen].sort()
})

const disciplinasActivas = ref([])
const MAX_DISCIPLINAS = 3

function estaActiva(nombre) {
  return disciplinasActivas.value.includes(nombre)
}

function toggleDisciplina(nombre) {
  if (estaActiva(nombre)) {
    disciplinasActivas.value = disciplinasActivas.value.filter(d => d !== nombre)
  } else {
    if (disciplinasActivas.value.length >= MAX_DISCIPLINAS) return
    disciplinasActivas.value = [...disciplinasActivas.value, nombre]
  }
}

function limpiarFiltros() {
  disciplinasActivas.value = []
  filtroBusqueda.value  = ''
  filtroEstatus.value   = ''
  filtroEspacio.value   = ''
  diaSeleccionado.value = null
}

const limitAlcanzado = computed(() => disciplinasActivas.value.length >= MAX_DISCIPLINAS)

// ─── Filtros superiores del calendario ───────────────────────────────────────
const diaSeleccionado = ref(null)   // string LUNES|MARTES|… o null
const filtroBusqueda  = ref('')
const filtroEstatus   = ref('')
const filtroEspacio   = ref('')

function toggleDia(dia) {
  diaSeleccionado.value = diaSeleccionado.value === dia ? null : dia
}

// Espacios únicos derivados de las sesiones recibidas (para el dropdown)
const espaciosUnicos = computed(() =>
  [...new Set(props.sesiones.map(s => s.espacio).filter(Boolean))].sort()
)

const hayFiltrosActivos = computed(() =>
  disciplinasActivas.value.length > 0 ||
  filtroBusqueda.value.trim() !== '' ||
  filtroEstatus.value !== '' ||
  filtroEspacio.value !== '' ||
  diaSeleccionado.value !== null
)

// ─── Computed principal: filtrado acumulativo ─────────────────────────────────
// diaSeleccionado NO filtra el array (solo controla opacidad visual de columnas).
const sesionesFiltradas = computed(() => {
  const hayDisciplinas = disciplinasActivas.value.length > 0
  if (!hayDisciplinas) return []                    // sin disciplina seleccionada → nada

  const buscar  = filtroBusqueda.value.trim().toLowerCase()
  const estatus = filtroEstatus.value
  const espacio = filtroEspacio.value

  return props.sesiones.filter(s => {
    // 1. Disciplinas (panel lateral)
    if (!disciplinasActivas.value.includes(s.disciplina)) return false

    // 2. Texto libre: ID de sesión o nombre de instructor
    if (buscar) {
      const idMatch       = String(s.id_sesion ?? '').includes(buscar)
      const instrMatch    = (s.instructor ?? '').toLowerCase().includes(buscar)
      if (!idMatch && !instrMatch) return false
    }

    // 3. Estatus
    if (estatus && s.estatus_sesion !== estatus) return false

    // 4. Espacio
    if (espacio && s.espacio !== espacio) return false

    return true
  })
})

// ─── Constantes de layout ─────────────────────────────────────────────────────
const DIAS = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO']
const DIAS_LABEL = {
  LUNES: 'Lun', MARTES: 'Mar', MIERCOLES: 'Mié',
  JUEVES: 'Jue', VIERNES: 'Vie', SABADO: 'Sáb', DOMINGO: 'Dom',
}

const HORA_INICIO    = 6
const HORA_FIN       = 22
const TOTAL_HORAS    = HORA_FIN - HORA_INICIO
const SLOT_MIN       = 30
const SLOTS_POR_HORA = 60 / SLOT_MIN
const TOTAL_SLOTS    = TOTAL_HORAS * SLOTS_POR_HORA
const SLOT_PX        = 30

const horas = Array.from({ length: TOTAL_HORAS }, (_, i) => {
  const h = HORA_INICIO + i
  return `${String(h).padStart(2, '0')}:00`
})

// ─── Time helpers ─────────────────────────────────────────────────────────────
function toMinutes(t) {
  const [h, m] = String(t).split(':').map(Number)
  return h * 60 + m
}

function clampSlot(s) {
  return Math.max(0, Math.min(TOTAL_SLOTS, s))
}

function sesionASlots(s) {
  const startMin  = toMinutes(s.hora_inicio) - HORA_INICIO * 60
  const endMin    = toMinutes(s.hora_fin)    - HORA_INICIO * 60
  const startSlot = clampSlot(Math.floor(startMin / SLOT_MIN))
  const endSlot   = clampSlot(Math.ceil(endMin    / SLOT_MIN))
  return { startSlot, endSlot: Math.max(endSlot, startSlot + 1) }
}

// ─── Layout de lanes por día ──────────────────────────────────────────────────
function layoutDia(dia) {
  const items = sesionesFiltradas.value
    .filter(b => b.dia_semana === dia)
    .map(b => {
      const { startSlot, endSlot } = sesionASlots(b)
      return { ...b, _startSlot: startSlot, _endSlot: endSlot }
    })
    .sort((a, b) => a._startSlot - b._startSlot || b._endSlot - a._endSlot)

  const lanes = []
  for (const it of items) {
    let asignado = false
    for (let i = 0; i < lanes.length; i++) {
      const last = lanes[i][lanes[i].length - 1]
      if (last._endSlot <= it._startSlot) {
        lanes[i].push(it)
        it._lane = i
        asignado = true
        break
      }
    }
    if (!asignado) {
      it._lane = lanes.length
      lanes.push([it])
    }
  }

  const totalLanes = Math.max(1, lanes.length)
  return items.map(it => ({ ...it, _totalLanes: totalLanes }))
}

const layoutPorDia = computed(() => {
  const out = {}
  for (const dia of DIAS) out[dia] = layoutDia(dia)
  return out
})

// ─── Matriz cromática por estado operativo ────────────────────────────────────
function clasesBloque(b) {
  const estatus = b.estatus_sesion
  const cerrada = !!b.requiere_inscripcion

  if (estatus === 'CANCELADA') {
    return {
      bg:          'bg-linear-to-br from-red-500 to-rose-700',
      borderStyle: 'border border-red-700 border-l-4 border-l-rose-800',
      text:        'text-white font-extrabold',
      sub:         'text-red-100 font-semibold',
    }
  }
  if (estatus === 'EN_CURSO') {
    return {
      bg:          'bg-linear-to-br from-orange-400 to-amber-600',
      borderStyle: 'border border-amber-600 border-l-4 border-l-amber-700',
      text:        'text-white font-extrabold',
      sub:         'text-amber-100 font-semibold',
    }
  }
  if (estatus === 'FINALIZADA') {
    return {
      bg:          'bg-linear-to-br from-blue-500 to-indigo-700',
      borderStyle: 'border border-indigo-700 border-l-4 border-l-indigo-800',
      text:        'text-white font-extrabold',
      sub:         'text-blue-100 font-semibold',
    }
  }
  if (cerrada) {
    return {
      bg:          'bg-linear-to-br from-violet-500 to-purple-700',
      borderStyle: 'border border-purple-700 border-l-4 border-l-purple-800',
      text:        'text-white font-extrabold',
      sub:         'text-violet-100 font-semibold',
    }
  }
  return {
    bg:          'bg-linear-to-br from-emerald-500 to-teal-700',
    borderStyle: 'border border-teal-700 border-l-4 border-l-teal-800',
    text:        'text-white font-extrabold',
    sub:         'text-emerald-100 font-semibold',
  }
}

// ─── Indicador de día actual ──────────────────────────────────────────────────
const now      = new Date()
const todayDia = DIAS[now.getDay() === 0 ? 6 : now.getDay() - 1]

const timezoneLabel = computed(() => {
  const offsetMinutes = new Date().getTimezoneOffset()
  const offsetHours   = -offsetMinutes / 60
  const sign          = offsetHours >= 0 ? '+' : ''
  return `GMT${sign}${offsetHours}`
})

const weekDays = computed(() => {
  const current          = new Date()
  const currentDay       = current.getDay()
  const distanceToMonday = currentDay === 0 ? 6 : currentDay - 1
  const monday           = new Date(current)
  monday.setDate(current.getDate() - distanceToMonday)

  return DIAS.map((dia, index) => {
    const d = new Date(monday)
    d.setDate(monday.getDate() + index)
    return { dia, fecha: d.getDate() }
  })
})

function formatGutterHour(h) {
  const [hStr] = h.split(':')
  const num    = parseInt(hStr, 10)
  if (num === 12) return '12 PM'
  if (num === 0)  return '12 AM'
  return num < 12 ? `${num} AM` : `${num - 12} PM`
}

// ─── Clases de opacidad por columna (dimming) ─────────────────────────────────
// Solo actúa cuando hay un día seleccionado; la columna no seleccionada se atenúa.
// No afecta el array de sesiones — las tarjetas siguen en su columna correcta.
function opacidadColumna(dia) {
  if (diaSeleccionado.value === null) return ''
  return diaSeleccionado.value === dia ? '' : 'opacity-30'
}
</script>

<template>
  <div class="flex h-full gap-3 min-h-0">

    <!-- ══════════ PANEL LATERAL DE DISCIPLINAS ══════════ -->
    <div
      :class="[
        'flex flex-col shrink-0 transition-all duration-200 ease-in-out',
        panelAbierto ? 'w-48' : 'w-10'
      ]"
    >
      <!-- Toggle del panel -->
      <button
        type="button"
        @click="panelAbierto = !panelAbierto"
        class="flex items-center gap-2 px-2.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 transition-colors text-slate-500 hover:text-slate-800 shrink-0 w-full mb-2 shadow-sm"
        :title="panelAbierto ? 'Colapsar panel' : 'Expandir panel'"
      >
        <svg
          class="w-4 h-4 shrink-0 transition-transform duration-200"
          :class="panelAbierto ? '' : 'rotate-180'"
          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 9l-3 3m0 0l3 3m-3-3h7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span v-if="panelAbierto" class="text-[10px] font-black uppercase tracking-widest truncate">Disciplinas</span>
      </button>

      <!-- Lista de disciplinas (solo visible si panel abierto) -->
      <div v-if="panelAbierto" class="flex flex-col gap-1 overflow-y-auto flex-1 pr-0.5">

        <!-- Aviso límite -->
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1"
        >
          <div
            v-if="limitAlcanzado"
            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-[9px] font-black text-amber-700 uppercase tracking-wide mb-0.5"
          >
            <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            Máx. {{ MAX_DISCIPLINAS }} activas
          </div>
        </Transition>

        <!-- Opción "Ninguna" (limpia la selección) -->
        <button
          type="button"
          @click="limpiarFiltros"
          :class="[
            'w-full text-left px-2.5 py-2 rounded-xl text-[11px] font-bold transition-all border flex items-center gap-2',
            !hayFiltrosActivos
              ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
              : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <span class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 border border-slate-200 bg-white">
            <svg class="w-3.5 h-3.5" :class="!hayFiltrosActivos ? 'text-slate-900' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
          </span>
          <span class="truncate">Ninguna</span>
          <svg v-if="!hayFiltrosActivos" class="w-3 h-3 shrink-0 ml-auto text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </button>

        <!-- Pills por disciplina -->
        <button
          v-for="disc in disciplinasUnicas"
          :key="disc"
          type="button"
          @click="toggleDisciplina(disc)"
          :disabled="!estaActiva(disc) && limitAlcanzado"
          :title="!estaActiva(disc) && limitAlcanzado ? `Límite de ${MAX_DISCIPLINAS} disciplinas alcanzado` : disc"
          :class="[
            'w-full text-left px-2.5 py-2 rounded-xl text-[11px] font-bold transition-all truncate border flex items-center gap-2',
            estaActiva(disc)
              ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
              : limitAlcanzado
                ? 'bg-white text-slate-300 border-slate-100 cursor-not-allowed opacity-50'
                : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <span
            class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 shadow-sm"
            :class="estaActiva(disc) ? 'bg-white' : 'bg-white border border-slate-200'"
          >
            <DisciplineIcon
              :name="disc"
              class="w-3.5 h-3.5"
              :class="estaActiva(disc) ? 'text-slate-900' : 'text-slate-500'"
            />
          </span>
          <span class="truncate">{{ disc }}</span>
          <svg
            v-if="estaActiva(disc)"
            class="w-3 h-3 shrink-0 ml-auto text-white/70"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </button>

        <!-- Botón limpiar filtros -->
        <button
          v-if="hayFiltrosActivos"
          type="button"
          @click="limpiarFiltros"
          class="mt-auto self-end text-[10px] font-bold text-slate-400 hover:text-red-500 transition-colors py-1"
        >
          Limpiar filtros
        </button>
      </div>
    </div>

    <!-- ══════════ ÁREA DERECHA: FILTROS SLIM + GRID + LEYENDA ══════════ -->
    <div class="flex flex-col flex-1 gap-2 min-h-0 min-w-0">

      <!-- ══════════ BARRA DE FILTROS SLIM ══════════ -->
      <div class="flex items-center gap-2 shrink-0 bg-white border border-slate-200 rounded-2xl shadow-sm px-3 py-2">

        <!-- Búsqueda: instructor o ID -->
        <div class="relative flex-1 min-w-0">
          <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none">
            <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </span>
          <input
            v-model.trim="filtroBusqueda"
            type="text"
            placeholder="Buscar instructor o ID de sesión..."
            class="w-full pl-7 pr-3 py-1.5 text-[11px] font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 focus:bg-white transition-all placeholder:text-slate-400"
          />
        </div>

        <!-- Separador vertical -->
        <div class="w-px h-5 bg-slate-200 shrink-0" />

        <!-- Selector de Estatus -->
        <select
          v-model="filtroEstatus"
          class="select-slim py-1.5 pl-2 pr-6 text-[11px] font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 cursor-pointer transition-all appearance-none shrink-0"
        >
          <option value="">Todos los estatus</option>
          <option value="DISPONIBLE">Disponible</option>
          <option value="EN_CURSO">En Curso</option>
          <option value="FINALIZADA">Finalizada</option>
          <option value="CANCELADA">Cancelada</option>
        </select>

        <!-- Separador vertical -->
        <div class="w-px h-5 bg-slate-200 shrink-0" />

        <!-- Selector de Espacio -->
        <select
          v-model="filtroEspacio"
          class="select-slim py-1.5 pl-2 pr-6 text-[11px] font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 cursor-pointer transition-all appearance-none shrink-0 max-w-[160px]"
        >
          <option value="">Todos los espacios</option>
          <option v-for="esp in espaciosUnicos" :key="esp" :value="esp">{{ esp }}</option>
        </select>

        <!-- Indicador de filtros activos -->
        <Transition
          enter-active-class="transition-all duration-150 ease-out"
          enter-from-class="opacity-0 scale-90"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-100 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-90"
        >
          <span
            v-if="filtroBusqueda || filtroEstatus || filtroEspacio"
            class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-900 text-white text-[10px] font-black"
          >
            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
            </svg>
            Filtros
          </span>
        </Transition>
      </div>

      <!-- ══════════ CALENDAR GRID ══════════ -->
      <div class="flex-1 min-h-0 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-sm calendar-scrollbar relative">

        <!-- Overlay de carga -->
        <Transition enter-active-class="transition-opacity duration-150" enter-from-class="opacity-0" enter-to-class="opacity-100"
          leave-active-class="transition-opacity duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
          <div v-if="isLoading" class="absolute inset-0 z-30 bg-white/70 backdrop-blur-[2px] flex items-center justify-center rounded-2xl pointer-events-none">
            <svg class="w-5 h-5 text-slate-400 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
          </div>
        </Transition>

        <div class="min-w-[820px] pb-4">

          <!-- Day headers (sticky) -->
          <div
            class="sticky top-0 z-20 bg-white/95 backdrop-blur-sm border-b border-slate-200 grid"
            :style="{ gridTemplateColumns: '64px repeat(7, minmax(0, 1fr))' }"
          >
            <!-- Gutter de zona horaria -->
            <div class="h-20 flex flex-col items-end justify-end pb-2 pr-3 border-r border-slate-100">
              <span class="text-[10px] font-semibold text-slate-600 tracking-wider">{{ timezoneLabel }}</span>
            </div>

            <!-- Cabeceras de día — clic para filtrar columna -->
            <div
              v-for="dayObj in weekDays"
              :key="dayObj.dia"
              class="h-20 border-l border-slate-100 flex flex-col items-center justify-center gap-1 cursor-pointer select-none transition-opacity duration-200"
              :class="opacidadColumna(dayObj.dia)"
              @click="toggleDia(dayObj.dia)"
              :title="diaSeleccionado === dayObj.dia ? 'Quitar filtro de día' : `Enfocar ${DIAS_LABEL[dayObj.dia]}`"
            >
              <span :class="[
                'text-[11px] font-bold uppercase tracking-wider transition-colors duration-150',
                dayObj.dia === todayDia ? 'text-primary-600' : 'text-slate-700'
              ]">
                {{ DIAS_LABEL[dayObj.dia] }}
              </span>
              <!-- Burbuja de fecha: activa (seleccionada) → bg-slate-900; hoy → bg-primary; normal → hover:bg-slate-100 -->
              <div :class="[
                'w-9 h-9 rounded-full flex items-center justify-center text-[15px] transition-all duration-150',
                diaSeleccionado === dayObj.dia
                  ? 'bg-slate-900 text-white font-bold shadow-md ring-2 ring-slate-900/20'
                  : dayObj.dia === todayDia
                    ? 'bg-primary-600 text-white font-bold shadow-sm'
                    : 'text-slate-800 font-semibold hover:bg-slate-100'
              ]">
                {{ dayObj.fecha }}
              </div>
            </div>
          </div>

          <!-- Body grid -->
          <div
            class="relative grid"
            :style="{
              gridTemplateColumns: '64px repeat(7, minmax(0, 1fr))',
              gridTemplateRows: `repeat(${TOTAL_SLOTS}, ${SLOT_PX}px)`,
            }"
          >
            <!-- Gutter de horas -->
            <div
              v-for="(hora, i) in horas"
              :key="'g-' + hora"
              class="pr-3 flex items-start justify-end relative"
              :style="{ gridColumn: 1, gridRow: `${i * SLOTS_POR_HORA + 1} / span ${SLOTS_POR_HORA}` }"
            >
              <span :class="['text-[10px] font-semibold text-slate-700 leading-none tracking-wide', i > 0 ? '-translate-y-1.5' : 'translate-y-1']">
                {{ formatGutterHour(hora) }}
              </span>
              <span
                v-if="i === horas.length - 1"
                class="absolute bottom-0 right-3 translate-y-1 text-[10px] font-semibold text-slate-700 leading-none tracking-wide"
              >
                {{ formatGutterHour(`${String(HORA_FIN).padStart(2, '0')}:00`) }}
              </span>
            </div>

            <!-- Columnas de día — fondo con dimming por día seleccionado -->
            <template v-for="(dia, diaIdx) in DIAS" :key="'col-' + dia">
              <div
                v-for="hIdx in TOTAL_HORAS"
                :key="`bg-${dia}-${hIdx}`"
                :class="[
                  'w-full h-full border-l border-l-slate-100 border-t border-t-slate-100 text-left align-top select-none transition-opacity duration-200',
                  hIdx === TOTAL_HORAS ? 'border-b border-b-slate-100' : '',
                  dia === todayDia ? 'bg-slate-50/50' : '',
                  opacidadColumna(dia),
                ]"
                :style="{
                  gridColumn: diaIdx + 2,
                  gridRow: `${(hIdx - 1) * SLOTS_POR_HORA + 1} / span ${SLOTS_POR_HORA}`,
                }"
              >
                <div class="h-1/2 border-b border-b-slate-100/30 border-dashed pointer-events-none" />
              </div>
            </template>

            <!-- Bloques de sesión — también con dimming -->
            <template v-for="(dia, diaIdx) in DIAS" :key="'sess-' + dia">
              <button
                v-for="b in layoutPorDia[dia]"
                :key="b.id_sesion"
                type="button"
                @click="emit('select-sesion', b)"
                :style="{
                  gridColumn: diaIdx + 2,
                  gridRow: `${b._startSlot + 1} / ${b._endSlot + 1}`,
                  width: `calc(${100 / b._totalLanes}% - 6px)`,
                  marginLeft: `calc(${(100 / b._totalLanes) * b._lane}% + 3px)`,
                }"
                :class="[
                  'relative z-10 rounded-xl overflow-hidden text-left transition-all duration-200 my-0.5',
                  'hover:shadow-md hover:-translate-y-px focus:outline-none focus:ring-2 focus:ring-white/40 active:scale-[0.98]',
                  clasesBloque(b).bg,
                  clasesBloque(b).borderStyle,
                  opacidadColumna(dia),
                ]"
              >
                <div class="pl-2.5 pr-2 py-2 h-full flex flex-col justify-between overflow-hidden">
                  <div class="min-w-0">
                    <div class="flex items-start justify-between gap-1">
                      <p :class="['text-[11px] font-bold truncate leading-tight', clasesBloque(b).text]">
                        {{ b.disciplina }}
                      </p>
                      <span
                        v-if="b.estatus_sesion !== 'DISPONIBLE'"
                        class="px-1 py-0.5 rounded text-[7px] font-black bg-black/20 text-white uppercase tracking-wide shrink-0"
                      >
                        {{ b.estatus_sesion === 'CANCELADA' ? 'Canc.' : b.estatus_sesion === 'EN_CURSO' ? 'En curso' : 'Final.' }}
                      </span>
                    </div>
                    <p :class="['text-[9px] font-medium truncate leading-tight mt-0.5 tabular-nums', clasesBloque(b).sub]">
                      {{ String(b.hora_inicio).slice(0,5) }}–{{ String(b.hora_fin).slice(0,5) }}
                    </p>
                    <p :class="['text-[9px] font-semibold truncate leading-tight mt-0.5', clasesBloque(b).sub]">
                      {{ b.instructor }}
                    </p>
                  </div>
                  <div class="flex items-center justify-between text-[9px] font-bold border-t border-white/10 pt-1 mt-1 shrink-0">
                    <span :class="['truncate', clasesBloque(b).sub]">{{ b.espacio }}</span>
                    <span :class="['shrink-0 flex items-center gap-0.5 px-1 rounded-md bg-black/10 text-[9px]', clasesBloque(b).text]">
                      <svg class="w-2.5 h-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                      </svg>
                      {{ b.cantidad_inscritos ?? 0 }}
                    </span>
                  </div>
                </div>
              </button>
            </template>
          </div>
        </div>
      </div>

      <!-- ══════════ LEYENDA CROMÁTICA + ESTADO DE FILTROS ══════════ -->
      <div class="flex gap-4 flex-wrap shrink-0 px-2 py-1.5 text-[10px] bg-white/70 rounded-xl border border-slate-100 items-center">
        <span class="flex items-center gap-1.5 font-bold text-slate-600">
          <span class="w-3 h-3 rounded border border-teal-700 bg-linear-to-br from-emerald-500 to-teal-700 shrink-0" />
          Abierta · Disponible
        </span>
        <span class="flex items-center gap-1.5 font-bold text-slate-600">
          <span class="w-3 h-3 rounded border border-purple-700 bg-linear-to-br from-violet-500 to-purple-700 shrink-0" />
          Cerrada · Disponible
        </span>
        <span class="flex items-center gap-1.5 font-bold text-slate-600">
          <span class="w-3 h-3 rounded border border-amber-600 bg-linear-to-br from-orange-400 to-amber-600 shrink-0" />
          En Curso
        </span>
        <span class="flex items-center gap-1.5 font-bold text-slate-600">
          <span class="w-3 h-3 rounded border border-indigo-700 bg-linear-to-br from-blue-500 to-indigo-700 shrink-0" />
          Finalizada
        </span>
        <span class="flex items-center gap-1.5 font-bold text-slate-600">
          <span class="w-3 h-3 rounded border border-rose-700 bg-linear-to-br from-red-500 to-rose-700 shrink-0" />
          Cancelada
        </span>

        <!-- Indicador de día enfocado -->
        <Transition
          enter-active-class="transition-all duration-150 ease-out"
          enter-from-class="opacity-0 -translate-x-1"
          enter-to-class="opacity-100 translate-x-0"
          leave-active-class="transition-all duration-100 ease-in"
          leave-from-class="opacity-100 translate-x-0"
          leave-to-class="opacity-0 -translate-x-1"
        >
          <span
            v-if="diaSeleccionado"
            class="flex items-center gap-1.5 font-bold text-slate-700 ml-auto cursor-pointer hover:text-red-500 transition-colors"
            @click="diaSeleccionado = null"
            title="Quitar filtro de día"
          >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75" />
            </svg>
            Enfocando: {{ DIAS_LABEL[diaSeleccionado] }}
            <svg class="w-2.5 h-2.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </span>
        </Transition>

        <!-- Indicador de disciplinas activas -->
        <span v-if="disciplinasActivas.length > 0 && !diaSeleccionado" class="flex items-center gap-1.5 font-bold text-violet-600 ml-auto">
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
          </svg>
          {{ disciplinasActivas.join(', ') }}
        </span>
      </div>

    </div><!-- /área derecha -->
  </div><!-- /contenedor principal -->
</template>

<style scoped>
.calendar-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
.calendar-scrollbar::-webkit-scrollbar-track { background: transparent; }
.calendar-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.calendar-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* Flecha del select sin SVG inline (evita el error de Vite con '/' en atributos style) */
.select-slim {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 6px center;
  background-size: 14px;
}
</style>
