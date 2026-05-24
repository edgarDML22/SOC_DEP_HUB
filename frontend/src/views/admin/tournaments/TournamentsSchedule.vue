<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useTournamentStore } from '@/stores/tournamentStore'
import { useScheduleStore } from '@/stores/admin/scheduleStore'
import { storeToRefs } from 'pinia'
import { useAlerts } from '@/composables/useAlerts'

import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import resourceTimelinePlugin from '@fullcalendar/resource-timeline'

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import CreateTournamentModal from '@/components/tournaments/CreateTournamentModal.vue'
import SidebarArbitros from '@/components/tournaments/SidebarArbitros.vue'
import ModalAsignacionEncuentro from '@/components/tournaments/ModalAsignacionEncuentro.vue'

const router = useRouter()
const route = useRoute()
const tournamentStore = useTournamentStore()
const scheduleStore = useScheduleStore()
const { toastInfo } = useAlerts()

const {
  torneoSeleccionado, encuentros, arbitrosPool,
  loadingStates, calendarEvents, allTournamentsEvents,
  todosLosTorneos, TOURNAMENT_COLORS, espaciosActivos
} = storeToRefs(scheduleStore)

// ── UI STATE ────────────────────────────────────────────────
const showCreateModal = ref(false)
const showAssignModal = ref(false)
const selectedEncuentro = ref(null)
const rangoActivo = ref(null)
const calendarRef = ref(null)
const searchQuery = ref('')
const filterTab = ref('ALL') // 'ALL' | 'SCHEDULED' | 'UNSCHEDULED'
const currentMiniView = ref('timeGridDay')

// ── COMPUTED ────────────────────────────────────────────────
const torneoIdFromQuery = computed(() => route.query.torneo ? Number(route.query.torneo) : null)
const isDetailMode = computed(() => !!torneoIdFromQuery.value)

const calendarPlugins = [dayGridPlugin, timeGridPlugin, interactionPlugin]

// Events for the general overview calendar
const overviewEvents = computed(() => allTournamentsEvents.value)

// Events for the detail calendar (single tournament)
const detailEvents = computed(() => calendarEvents.value.filter(e => e.start))

// Glow preview and scheduled events combined for the mini calendar
const miniCalendarEvents = computed(() => {
  const list = [...detailEvents.value]
  if (rangoActivo.value && rangoActivo.value.inicio && rangoActivo.value.fin) {
    list.push({
      id: 'glow-preview',
      start: rangoActivo.value.inicio,
      end: rangoActivo.value.fin,
      display: 'background',
      classNames: ['preview-highlight-pulse']
    })
  }
  return list
})

// Color legend for overview
const colorLegend = computed(() => {
  return todosLosTorneos.value.map(t => ({
    id: t.id_torneo,
    nombre: t.nombre_torneo,
    color: scheduleStore.getTournamentColor(t.id_torneo),
  }))
})

// Filtered encounters table list (excluding Bye matches)
const filteredEncuentrosTable = computed(() => {
  return encuentros.value
    .filter(e => !e.es_bye)
    .map(enc => {
      const comp1 = enc.competidor1?.equipo?.nombre_equipo
        || enc.competidor1?.participante?.nombre_equipo
        || enc.competidor1?.participante?.nombre_completo
        || enc.competidor1?.participante?.nombre
        || enc.competidor1?.nombre_completo
        || (enc.competidor1?.id_interno ? `Participante #${enc.competidor1.id_interno}` : 'TBD');
      const comp2 = enc.competidor2?.equipo?.nombre_equipo
        || enc.competidor2?.participante?.nombre_equipo
        || enc.competidor2?.participante?.nombre_completo
        || enc.competidor2?.participante?.nombre
        || enc.competidor2?.nombre_completo
        || (enc.competidor2?.id_interno ? `Participante #${enc.competidor2.id_interno}` : 'TBD');

      return {
        ...enc,
        comp1Name: comp1,
        comp2Name: comp2,
        isAssigned: !!enc.id_arbitro_asignado && !!enc.fecha_hora_inicio && !!enc.id_espacio,
        faseLabel: formatFase(enc.fase_bracket || enc.fase)
      }
    })
    .filter(e => {
      // 1. Search filter
      const q = searchQuery.value.toLowerCase().trim()
      if (q) {
        const matchComp1 = e.comp1Name.toLowerCase().includes(q)
        const matchComp2 = e.comp2Name.toLowerCase().includes(q)
        const matchFase = e.faseLabel.toLowerCase().includes(q)
        if (!matchComp1 && !matchComp2 && !matchFase) return false
      }

      // 2. Tab filter
      if (filterTab.value === 'SCHEDULED') return e.isAssigned
      if (filterTab.value === 'UNSCHEDULED') return !e.isAssigned

      return true
    })
})

// Encounters without date for detail view
const unscheduledEncuentros = computed(() =>
  encuentros.value.filter(e => !e.es_bye && !e.fecha_hora_inicio)
)

// ── CALENDAR OPTIONS ────────────────────────────────────────
const overviewCalendarOptions = computed(() => ({
  plugins: calendarPlugins,
  initialView: 'timeGridWeek',
  locale: 'es',
  headerToolbar: {
    left: 'prev,today,next',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },
  height: 'auto',
  slotMinTime: '06:00:00',
  slotMaxTime: '23:00:00',
  allDaySlot: false,
  nowIndicator: true,
  events: overviewEvents.value,
  eventClick: handleOverviewEventClick,
  editable: false,
  selectable: false,
  eventDisplay: 'block',
  slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
  dayHeaderFormat: { weekday: 'short', day: 'numeric', month: 'short' },
  buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día' },
}))

const detailCalendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: currentMiniView.value,
  initialDate: torneoSeleccionado.value?.fecha_inicio || undefined,
  locale: 'es',
  headerToolbar: {
    left: 'prev,today,next',
    center: 'title',
    right: '' // Custom view pills are used instead
  },
  height: 'auto',
  slotMinTime: '06:00:00',
  slotMaxTime: '23:00:00',
  allDaySlot: false,
  nowIndicator: true,
  events: miniCalendarEvents.value,
  eventClick: handleDetailEventClick,
  editable: false,
  selectable: true,
  select: handleSlotSelect,
  eventDisplay: 'block',
  slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
  dayHeaderFormat: { weekday: 'short', day: 'numeric', month: 'short' },
  buttonText: { today: 'Hoy' },
}))

// ── EVENT HANDLERS ──────────────────────────────────────────
function handleOverviewEventClick(info) {
  const idTorneo = info.event.extendedProps?.id_torneo
  if (idTorneo) {
    router.push({ query: { torneo: idTorneo } })
  }
}

function handleSlotSelect(selectionInfo) {
  rangoActivo.value = {
    inicio: selectionInfo.start,
    fin: selectionInfo.end,
    id_espacio: null
  }
}

const parseDateSafe = (dateStr) => {
  if (!dateStr) return null
  if (dateStr instanceof Date) return dateStr
  if (typeof dateStr === 'object' && typeof dateStr.getTime === 'function') return dateStr
  const normalized = typeof dateStr === 'string' ? dateStr.replace(' ', 'T') : dateStr
  const parsed = new Date(normalized)
  return isNaN(parsed.getTime()) ? null : parsed
}

function handleDetailEventClick(info) {
  if (info.event.id === 'glow-preview') return
  const enc = info.event.extendedProps
  if (enc) {
    selectEncuentroSlot(enc)
  }
}

function selectEncuentroSlot(enc) {
  if (enc.fecha_hora_inicio && enc.fecha_hora_fin) {
    rangoActivo.value = {
      inicio: parseDateSafe(enc.fecha_hora_inicio),
      fin: parseDateSafe(enc.fecha_hora_fin),
      id_espacio: enc.id_espacio ? String(enc.id_espacio) : null
    }
  } else {
    rangoActivo.value = null
  }
}

function openAssignModal(enc) {
  const enrichedEnc = { ...enc }
  
  if (enc.fecha_hora_inicio && enc.fecha_hora_fin) {
    // If it's already scheduled, parse dates directly without touching rangoActivo
    enrichedEnc.fecha_hora_inicio = parseDateSafe(enc.fecha_hora_inicio)
    enrichedEnc.fecha_hora_fin = parseDateSafe(enc.fecha_hora_fin)
  } else {
    // If it's not scheduled yet, we use the active calendar range if available
    if (rangoActivo.value) {
      enrichedEnc.fecha_hora_inicio = rangoActivo.value.inicio
      enrichedEnc.fecha_hora_fin = rangoActivo.value.fin
      if (rangoActivo.value.id_espacio && rangoActivo.value.id_espacio !== 'sin-asignar') {
        enrichedEnc.id_espacio = Number(rangoActivo.value.id_espacio)
      }
    }
  }
  
  selectedEncuentro.value = enrichedEnc
  showAssignModal.value = true
}

function handleAssignmentSaved() {
  if (torneoIdFromQuery.value) {
    scheduleStore.fetchEncuentrosTorneo(torneoIdFromQuery.value)
  }
}

function goToOverview() {
  rangoActivo.value = null
  scheduleStore.clearSelection()
  router.push({ query: {} })
}

function setMiniCalendarView(viewName) {
  currentMiniView.value = viewName
  if (calendarRef.value) {
    const calendarApi = calendarRef.value.getApi()
    if (calendarApi) {
      calendarApi.changeView(viewName)
    }
  }
}

// ── UTILS / RESOLVERS ────────────────────────────────────────
const getRgbaFromHex = (hex, alpha = 0.15) => {
  if (!hex) return 'rgba(0,0,0,0.1)'
  const cleanHex = hex.replace('#', '')
  const r = parseInt(cleanHex.substring(0, 2), 16)
  const g = parseInt(cleanHex.substring(2, 4), 16)
  const b = parseInt(cleanHex.substring(4, 6), 16)
  return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

const getRefereeName = (row) => {
  const refObj = row.arbitro
  if (refObj?.nombre) return refObj.nombre
  if (refObj?.nombre_completo) return refObj.nombre_completo
  
  if (row.id_arbitro_asignado) {
    const refInTotals = scheduleStore.arbitrosTotales?.find(a => a.id_instructor === row.id_arbitro_asignado)
    if (refInTotals?.nombre) return refInTotals.nombre

    const refInPool = scheduleStore.arbitrosPool?.disponibles?.find(a => a.id_instructor === row.id_arbitro_asignado)
      || scheduleStore.arbitrosPool?.ocupados?.find(a => a.id_instructor === row.id_arbitro_asignado)
    if (refInPool?.nombre) return refInPool.nombre
  }
  return 'Por asignar'
}

const getRefereeInitial = (nombre) => {
  if (!nombre) return '?'
  return nombre.charAt(0).toUpperCase()
}

const getRefereeBg = (nombre) => {
  const colors = [
    'bg-blue-100 text-blue-750 border border-blue-200',
    'bg-purple-100 text-purple-750 border border-purple-200',
    'bg-pink-100 text-pink-750 border border-pink-200',
    'bg-amber-100 text-amber-750 border border-amber-200',
    'bg-emerald-100 text-emerald-750 border border-emerald-200',
    'bg-cyan-100 text-cyan-750 border border-cyan-200',
  ]
  const idx = nombre ? nombre.charCodeAt(0) % colors.length : 0
  return colors[idx]
}

const getEspacioName = (idEspacio) => {
  if (!idEspacio) return 'Sin asignar'
  const sp = espaciosActivos.value.find(e => e.id_espacio === idEspacio)
  return sp ? sp.nombre_espacio : `Espacio #${idEspacio}`
}

function handleTorneoCreated() {
  tournamentStore.fetchTorneos()
  if (!isDetailMode.value) {
    scheduleStore.fetchTodosLosTorneos()
  }
}

const formatFase = (fase) => {
  if (!fase || fase === 'N/A') return 'ENCUENTRO'
  return fase.replace(/_/g, ' ').toUpperCase()
}

const formatFecha = (f) => {
  if (!f) return '—'
  return new Date(f).toLocaleString('es-MX', {
    day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
  })
}

const formatDateForApi = (date) => {
  if (!date) return null
  const d = new Date(date)
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`
}

const formatWeekdayAbbreviation = (date) => {
  if (!date) return ''
  const weekdays = ['DOM', 'LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB']
  return weekdays[new Date(date).getDay()]
}

const isToday = (date) => {
  if (!date) return false
  const today = new Date()
  const d = new Date(date)
  return today.getDate() === d.getDate() &&
         today.getMonth() === d.getMonth() &&
         today.getFullYear() === d.getFullYear()
}

const formatHour12 = (date) => {
  if (!date) return ''
  const d = new Date(date)
  let hours = d.getHours()
  const ampm = hours >= 12 ? 'PM' : 'AM'
  hours = hours % 12
  hours = hours ? hours : 12
  return `${hours} ${ampm}`
}

const formatTimeRange = (start, end) => {
  if (!start || !end) return ''
  const formatTime = (date) => {
    const d = new Date(date)
    const pad = (n) => String(n).padStart(2, '0')
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`
  }
  return `${formatTime(start)} - ${formatTime(end)}`
}

const getEventCardStyle = (event) => {
  if (event.id === 'glow-preview') return {}
  
  const ext = event.extendedProps || {}
  const isAssigned = ext.isAssigned
  const isFinalizado = ext.estatus_encuentro === 'FINALIZADO'
  const hasConflicto = ext.has_conflicto
  
  if (hasConflicto) {
    return {
      background: 'linear-gradient(135deg, #f59e0b, #d97706)',
      border: '1px solid #d97706',
      borderLeft: '4px solid #b45309',
      color: '#ffffff',
    }
  }
  
  if (isFinalizado) {
    return {
      background: 'linear-gradient(135deg, #f43f5e, #e11d48)',
      border: '1px solid #e11d48',
      borderLeft: '4px solid #be123c',
      color: '#ffffff',
    }
  }
  
  const tColor = scheduleStore.getTournamentColor(ext.id_torneo)
  if (isAssigned) {
    const bg1 = tColor.gradient ? tColor.gradient[0] : tColor.bg
    const bg2 = tColor.gradient ? tColor.gradient[1] : tColor.bg
    return {
      background: `linear-gradient(135deg, ${bg1}, ${bg2})`,
      border: `1px solid ${bg2}`,
      borderLeft: `4px solid ${bg2}`,
      color: '#ffffff',
    }
  } else {
    // Unassigned: slate gray dashed with 4px left border
    return {
      background: 'linear-gradient(135deg, #f8fafc, #f1f5f9)',
      border: '1px dashed #cbd5e1',
      borderLeft: '4px dashed #94a3b8',
      color: '#475569',
    }
  }
}

const getEventTitleStyle = (event) => {
  const ext = event.extendedProps || {}
  const isAssigned = ext.isAssigned
  
  return (isAssigned || ext.has_conflicto || ext.estatus_encuentro === 'FINALIZADO')
    ? { color: '#ffffff', fontWeight: '900' }
    : { color: '#1e293b', fontWeight: '900' }
}

const getEventSubTitleStyle = (event) => {
  const ext = event.extendedProps || {}
  const isAssigned = ext.isAssigned
  
  return (isAssigned || ext.has_conflicto || ext.estatus_encuentro === 'FINALIZADO')
    ? { color: 'rgba(255, 255, 255, 0.85)', fontSize: '7.5px' }
    : { color: '#64748b', fontSize: '7.5px' }
}

const getDividerStyle = (event) => {
  const ext = event.extendedProps || {}
  const isAssigned = ext.isAssigned
  
  return (isAssigned || ext.has_conflicto || ext.estatus_encuentro === 'FINALIZADO')
    ? { borderColor: 'rgba(255, 255, 255, 0.15)' }
    : { borderColor: '#cbd5e1' }
}

// ── WATCHERS ────────────────────────────────────────────────
watch(rangoActivo, async (newRange) => {
  if (newRange?.inicio && newRange?.fin) {
    const inicioStr = formatDateForApi(newRange.inicio)
    const finStr = formatDateForApi(newRange.fin)
    const torneoId = torneoIdFromQuery.value
    if (torneoId) {
      await scheduleStore.fetchArbitrosDisponibles(torneoId, inicioStr, finStr)
    }
  }
}, { deep: true })

watch(torneoIdFromQuery, async (id) => {
  rangoActivo.value = null
  if (id) {
    await scheduleStore.fetchEncuentrosTorneo(id)
    await scheduleStore.fetchEspacios()    
    await scheduleStore.fetchArbitrosTorneo(id)  // Obtener todos los árbitros del torneo
  } else {
    scheduleStore.clearSelection()
    scheduleStore.fetchTodosLosTorneos()
  }
}, { immediate: false })

watch(torneoSeleccionado, (newTorneo) => {
  if (newTorneo?.fecha_inicio && calendarRef.value) {
    const calendarApi = calendarRef.value.getApi()
    if (calendarApi) {
      calendarApi.gotoDate(newTorneo.fecha_inicio)
    }
  }
}, { immediate: true })

// ── INIT ────────────────────────────────────────────────────
onMounted(async () => {
  if (torneoIdFromQuery.value) {
    await scheduleStore.fetchEncuentrosTorneo(torneoIdFromQuery.value)
    await scheduleStore.fetchEspacios()
    await scheduleStore.fetchArbitrosTorneo(torneoIdFromQuery.value)  // Obtener todos los árbitros del torneo
  } else {
    await scheduleStore.fetchTodosLosTorneos()
  }
})
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- CABECERA -->
      <AdminPageHeader title="Calendario de Torneos" subtitle="Visualiza y administra la programación de encuentros.">
        <div class="flex items-center gap-1.5 p-1 bg-surface-100 rounded-xl mr-4">
          <button @click="router.push('/admin/tournaments')"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
            :class="route.name === 'tournaments-list' ? 'bg-white shadow-sm text-primary-600' : 'text-surface-500 hover:text-surface-700'">
            Tabla
          </button>
          <button @click="router.push('/admin/tournaments/cards')"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
            :class="route.name === 'tournaments-cards' ? 'bg-white shadow-sm text-primary-600' : 'text-surface-500 hover:text-surface-700'">
            Tarjetas
          </button>
          <button @click="router.push('/admin/tournaments/schedule')"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
            :class="route.name === 'tournaments-schedule' ? 'bg-white shadow-sm text-primary-600' : 'text-surface-500 hover:text-surface-700'">
            Calendario
          </button>
        </div>

        <button @click="showCreateModal = true" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-surface-900 text-white
                 text-sm font-bold hover:bg-primary-600 transition-colors shadow-sm">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="16" />
            <line x1="8" y1="12" x2="16" y2="12" />
          </svg>
          Crear Torneo
        </button>
      </AdminPageHeader>

      <!-- ═══════════════════════════════════════════════════ -->
      <!-- MODO DETALLE: torneo seleccionado via query param  -->
      <!-- ═══════════════════════════════════════════════════ -->
      <template v-if="isDetailMode">
        <!-- Chip de contexto -->
        <div class="flex items-center gap-3 flex-wrap">
          <button @click="goToOverview"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-surface-200 text-xs font-bold text-surface-600 hover:bg-surface-50 hover:text-surface-900 transition-all shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path d="M19 12H5M12 19l-7-7 7-7" />
            </svg>
            Vista general
          </button>

          <div v-if="torneoSeleccionado"
            class="flex items-center gap-2 px-4 py-2 rounded-xl border border-surface-200 bg-white shadow-sm">
            <span class="w-3 h-3 rounded-full"
              :style="{ backgroundColor: scheduleStore.getTournamentColor(torneoSeleccionado.id_torneo).bg }"></span>
            <span class="text-sm font-black text-surface-900">{{ torneoSeleccionado.nombre_torneo }}</span>
            <span class="text-[10px] font-bold text-surface-400 uppercase tracking-wider ml-1">
              {{ torneoSeleccionado.disciplina }} · {{ torneoSeleccionado.estado }}
            </span>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loadingStates.encuentros" class="flex flex-col items-center justify-center py-24">
          <LoadingSpinner size="lg" />
          <p class="text-sm font-bold text-surface-400 mt-4 animate-pulse">Cargando encuentros del torneo...</p>
        </div>

        <!-- Layout de doble columna: 7/12 columnas para calendario + 5/12 para buscador y pool -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

          <!-- ══════════════════════════════════════════ -->
          <!-- COLUMNA IZQUIERDA: Buscador, Tabla y Pool  -->
          <!-- ══════════════════════════════════════════ -->
          <div class="lg:col-span-7 min-w-0 space-y-5">
            <!-- Buscador + filtros -->
            <div class="bg-white rounded-2xl border border-surface-200 shadow-sm px-5 py-4 space-y-3">
              <!-- Search bar -->
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input v-model="searchQuery" type="text" placeholder="Buscar equipos o fase..."
                  class="w-full pl-10 pr-4 py-2 rounded-xl border border-surface-200 bg-surface-50 text-sm text-surface-800 placeholder-surface-400 font-medium focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-400 transition-all" />
              </div>
              <!-- Filter pills -->
              <div class="flex items-center gap-2">
                <button v-for="tab in [{ key: 'ALL', label: 'Todos' }, { key: 'SCHEDULED', label: 'Programados' }, { key: 'UNSCHEDULED', label: 'Por programar' }]"
                  :key="tab.key" @click="filterTab = tab.key"
                  class="px-3 py-1 rounded-lg text-[11px] font-bold transition-all border"
                  :class="filterTab === tab.key
                    ? 'bg-surface-900 text-white border-surface-900 shadow-sm'
                    : 'bg-white text-surface-500 border-surface-200 hover:border-surface-300 hover:text-surface-700'">
                  {{ tab.label }}
                </button>
                <span class="ml-auto text-[10px] font-bold text-surface-400">
                  {{ filteredEncuentrosTable.length }} encuentro{{ filteredEncuentrosTable.length !== 1 ? 's' : '' }}
                </span>
              </div>
            </div>

            <!-- Tabla/Lista de encuentros descongestionada -->
            <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">
              <!-- Encabezado de tabla -->
              <div class="grid grid-cols-[24px_2.5fr_1.2fr_1.2fr_90px] gap-4 px-5 py-4 bg-surface-50 border-b border-surface-100">
                <div class="text-[10px] font-black uppercase tracking-widest text-surface-400 col-span-2">Encuentro</div>
                <div class="text-[10px] font-black uppercase tracking-widest text-surface-400">Espacio</div>
                <div class="text-[10px] font-black uppercase tracking-widest text-surface-400">Árbitro</div>
                <div class="text-[10px] font-black uppercase tracking-widest text-surface-400 text-right">Acción</div>
              </div>

              <!-- Empty state -->
              <div v-if="filteredEncuentrosTable.length === 0"
                class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-12 h-12 rounded-2xl bg-surface-50 flex items-center justify-center mb-3">
                  <svg class="w-6 h-6 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                </div>
                <p class="text-sm font-bold text-surface-400">Sin resultados</p>
                <p class="text-xs text-surface-400 mt-1">Intenta ajustar los filtros o la búsqueda.</p>
              </div>

              <!-- Filas de encuentros amplias y descongestionadas -->
              <div v-else class="divide-y divide-surface-50 max-h-[480px] overflow-y-auto">
                <div v-for="enc in filteredEncuentrosTable" :key="enc.id_encuentro"
                  class="grid grid-cols-[24px_2.5fr_1.2fr_1.2fr_90px] gap-4 items-center px-5 py-4 hover:bg-surface-50/70 transition-colors group cursor-pointer"
                  @click="selectEncuentroSlot(enc)">

                  <!-- Status dot + fase -->
                  <div class="flex flex-col items-center gap-1">
                    <div class="w-2.5 h-2.5 rounded-full shrink-0 transition-all"
                      :class="enc.isAssigned ? 'bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.7)]' : 'bg-surface-300'"></div>
                  </div>

                  <!-- Nombres de competidores -->
                  <div class="min-w-0">
                    <p class="text-[12.5px] font-bold text-surface-800 truncate leading-tight">
                      <span class="text-surface-700">{{ enc.comp1Name }}</span>
                      <span class="text-surface-400 mx-1 font-normal">vs</span>
                      <span class="text-surface-700">{{ enc.comp2Name }}</span>
                    </p>
                    <div class="flex items-center gap-1.5 mt-1">
                      <span class="text-[10px] font-black uppercase tracking-wider text-surface-400 truncate max-w-[130px]">{{ enc.faseLabel }}</span>
                      <span v-if="enc.fecha_hora_inicio" class="text-[10px] font-bold text-surface-400">
                        · {{ formatFecha(enc.fecha_hora_inicio) }}
                      </span>
                    </div>
                  </div>

                  <!-- Espacio -->
                  <div class="shrink-0">
                    <span v-if="enc.id_espacio"
                      class="inline-flex items-center gap-0.5 px-2 py-1 rounded bg-blue-50 border border-blue-100 text-[10px] font-black text-blue-700 whitespace-nowrap">
                      <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      </svg>
                      <span class="max-w-[130px] truncate">{{ getEspacioName(enc.id_espacio) }}</span>
                    </span>
                    <span v-else
                      class="inline-flex items-center px-2 py-1 rounded bg-surface-100 text-[10px] font-black text-surface-400 whitespace-nowrap">
                      Sin asignar
                    </span>
                  </div>

                  <!-- Árbitro -->
                  <div class="shrink-0">
                    <div v-if="enc.id_arbitro_asignado" class="flex items-center gap-1.5">
                      <div class="w-5 h-5 rounded flex items-center justify-center text-[9px] font-black shrink-0"
                        :class="getRefereeBg(getRefereeName(enc))">
                        {{ getRefereeInitial(getRefereeName(enc)) }}
                      </div>
                      <span class="text-[10px] font-bold text-surface-700 max-w-[130px] truncate">{{ getRefereeName(enc) }}</span>
                    </div>
                    <span v-else
                      class="inline-flex items-center px-2 py-1 rounded bg-amber-50 border border-amber-100 text-[10px] font-black text-amber-600 whitespace-nowrap">
                      Por asignar
                    </span>
                  </div>

                  <!-- Acción -->
                  <div class="shrink-0 text-right">
                    <button
                      @click.stop="openAssignModal(enc)"
                      class="px-2.5 py-1.5 rounded border border-surface-200 bg-white text-[10px] font-black text-surface-600 hover:bg-surface-900 hover:text-white hover:border-surface-900 transition-all whitespace-nowrap shadow-sm">
                      {{ enc.isAssigned ? 'Editar' : 'Asignar' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pool del torneo (Sidebar de árbitros) abajo de donde se eligen -->
            <SidebarArbitros :torneo="torneoSeleccionado" :arbitros-pool="arbitrosPool" :loading="loadingStates.arbitros"
              :rango-activo="rangoActivo" />
          </div>

          <!-- ══════════════════════════════════════════ -->
          <!-- COLUMNA DERECHA: Calendario                -->
          <!-- ══════════════════════════════════════════ -->
          <div class="w-full lg:col-span-5 space-y-4 lg:sticky lg:top-6">
            <!-- Mini Calendar Card -->
            <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">
              <!-- Card header con pestañas de vista -->
              <div class="flex items-center justify-between px-4 py-3 border-b border-surface-100">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-lg bg-primary-50 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <span class="text-xs font-black text-surface-900">Calendario</span>
                  <span v-if="rangoActivo" class="flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-50 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[9px] font-black text-emerald-700">Rango activo</span>
                  </span>
                </div>
                <!-- View toggle pills -->
                <div class="flex items-center gap-1 p-0.5 bg-surface-100 rounded-lg">
                  <button @click="setMiniCalendarView('timeGridWeek')"
                    class="px-2.5 py-1 rounded-md text-[10px] font-bold transition-all"
                    :class="currentMiniView === 'timeGridWeek' ? 'bg-white shadow-sm text-surface-900' : 'text-surface-500 hover:text-surface-700'">
                    Semana
                  </button>
                  <button @click="setMiniCalendarView('timeGridDay')"
                    class="px-2.5 py-1 rounded-md text-[10px] font-bold transition-all"
                    :class="currentMiniView === 'timeGridDay' ? 'bg-white shadow-sm text-surface-900' : 'text-surface-500 hover:text-surface-700'">
                    Día
                  </button>
                </div>
              </div>

              <!-- Hint de interacción cuando no hay rango -->
              <div v-if="!rangoActivo" class="mx-4 mt-3 flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-50 border border-blue-100">
                <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-[9px] font-bold text-blue-700">Haz clic y arrastra en el calendario para seleccionar un rango horario.</p>
              </div>

              <!-- FullCalendar mini instance -->
              <div class="mini-calendar-container p-3">
                <FullCalendar ref="calendarRef" :options="detailCalendarOptions">
                  <!-- Day Header Slot -->
                  <template #dayHeaderContent="arg">
                    <div class="flex flex-col items-center py-1 select-none gap-0.5">
                      <span
                        :class="[
                          'text-[9px] font-extrabold uppercase tracking-wider',
                          isToday(arg.date) ? 'text-primary-600' : 'text-surface-500'
                        ]"
                      >
                        {{ formatWeekdayAbbreviation(arg.date) }}
                      </span>
                      <div
                        class="w-7 h-7 rounded-full flex items-center justify-center text-xs transition-colors duration-150 font-extrabold shadow-xs"
                        :class="isToday(arg.date)
                          ? 'bg-primary-600 text-white font-black shadow-sm'
                          : 'text-surface-700 hover:bg-surface-100'"
                      >
                        {{ arg.date.getDate() }}
                      </div>
                    </div>
                  </template>

                  <!-- Hour / Slot Label Slot -->
                  <template #slotLabelContent="arg">
                    <div class="text-[9px] font-black text-surface-400 uppercase tracking-wider pr-1">
                      {{ formatHour12(arg.date) }}
                    </div>
                  </template>

                  <!-- Event Content Slot -->
                  <template #eventContent="arg">
                    <div v-if="arg.event.id === 'glow-preview'"
                         class="w-full h-full p-2 rounded-xl border border-dashed border-primary-500 bg-primary-50/20 text-primary-800 animate-pulse flex flex-col justify-center items-center overflow-hidden">
                      <span class="text-[8px] font-black uppercase tracking-wider">Bloque Seleccionado</span>
                    </div>
                    <div v-else
                         class="w-full h-full rounded-xl border flex transition-all font-bold cursor-pointer hover:scale-[1.01] hover:shadow-md overflow-hidden"
                         :class="arg.event.start && arg.event.end && ((arg.event.end - arg.event.start) / 3600000 <= 1.2) ? 'flex-row items-center justify-between gap-3 px-3 py-1.5' : 'flex-col justify-between p-2.5'"
                         :style="getEventCardStyle(arg.event)">
                      
                      <!-- Si el encuentro es de corta duración (menor o igual a 1 hora / 1.2 hrs) -->
                      <template v-if="arg.event.start && arg.event.end && ((arg.event.end - arg.event.start) / 3600000 <= 1.2)">
                        <!-- Izquierda: Fase -->
                        <div class="min-w-0">
                          <p class="font-black uppercase tracking-wider text-[11px] leading-none truncate" :style="getEventTitleStyle(arg.event)">
                            {{ formatFase(arg.event.extendedProps.faseBracket || arg.event.extendedProps.fase) }}
                          </p>
                        </div>
                        
                        <!-- Derecha: Datos en horizontal -->
                        <div class="flex items-center gap-3 shrink-0">
                          <!-- Hora -->
                          <div class="flex items-center gap-1.5 text-[9.5px] leading-none" :style="getEventTitleStyle(arg.event)">
                            <svg class="w-3.5 h-3.5 shrink-0 opacity-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-bold">{{ formatTimeRange(arg.event.start, arg.event.end) }}</span>
                          </div>
                          
                          <!-- Árbitro -->
                          <div v-if="arg.event.extendedProps.id_arbitro_asignado" class="flex items-center gap-1.5 text-[9.5px] leading-none" :style="getEventTitleStyle(arg.event)">
                            <svg class="w-3.5 h-3.5 shrink-0 opacity-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="font-bold truncate max-w-[90px]">{{ getRefereeName(arg.event.extendedProps) }}</span>
                          </div>
                        </div>
                      </template>

                      <!-- Si el encuentro es largo (> 1.2 horas) -->
                      <template v-else>
                        <!-- Top: Fase -->
                        <div class="min-w-0">
                          <p class="font-black uppercase tracking-wider text-[11px] leading-tight" :style="getEventTitleStyle(arg.event)">
                            {{ formatFase(arg.event.extendedProps.faseBracket || arg.event.extendedProps.fase) }}
                          </p>
                        </div>

                        <!-- Bottom: Time & Referee with Icons -->
                        <div class="space-y-1.5 mt-2">
                          <!-- Time with Clock Icon -->
                          <div class="flex items-center gap-1.5 text-[9.5px] leading-none" :style="getEventTitleStyle(arg.event)">
                            <svg class="w-3.5 h-3.5 shrink-0 opacity-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="font-bold">{{ formatTimeRange(arg.event.start, arg.event.end) }}</span>
                          </div>
                          
                          <!-- Referee with Person Icon -->
                          <div v-if="arg.event.extendedProps.id_arbitro_asignado" class="flex items-center gap-1.5 text-[9.5px] leading-none" :style="getEventTitleStyle(arg.event)">
                            <svg class="w-3.5 h-3.5 shrink-0 opacity-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="font-bold truncate">{{ getRefereeName(arg.event.extendedProps) }}</span>
                          </div>
                        </div>
                      </template>

                    </div>
                  </template>
                </FullCalendar>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- ═══════════════════════════════════════════════════ -->
      <!-- MODO GENERAL: vista overview de todos los torneos  -->
      <!-- ═══════════════════════════════════════════════════ -->
      <template v-else>
        <!-- Loading -->
        <div v-if="loadingStates.torneos" class="flex flex-col items-center justify-center py-24">
          <LoadingSpinner size="lg" />
          <p class="text-sm font-bold text-surface-400 mt-4 animate-pulse">Cargando calendario general...</p>
        </div>

        <template v-else>
          <!-- Overview Calendar -->
          <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5 overflow-hidden font-sans">
            <FullCalendar :options="overviewCalendarOptions">
              <!-- Day Header Slot -->
              <template #dayHeaderContent="arg">
                <div class="flex flex-col items-center py-1.5 select-none gap-1">
                  <span
                    :class="[
                      'text-[10px] font-extrabold uppercase tracking-wider',
                      isToday(arg.date) ? 'text-primary-600' : 'text-slate-700'
                    ]"
                  >
                    {{ formatWeekdayAbbreviation(arg.date) }}
                  </span>
                  <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-sm transition-colors duration-150 font-extrabold"
                    :class="isToday(arg.date)
                      ? 'bg-primary-600 text-white font-black shadow-sm'
                      : 'text-slate-800 hover:bg-slate-100'"
                  >
                    {{ arg.date.getDate() }}
                  </div>
                </div>
              </template>

              <!-- Hour / Slot Label Slot -->
              <template #slotLabelContent="arg">
                <div class="text-[9px] font-black text-surface-400 uppercase tracking-wider pr-1">
                  {{ formatHour12(arg.date) }}
                </div>
              </template>

              <!-- Event Content Slot -->
              <template #eventContent="arg">
                <div class="w-full h-full p-1.5 rounded-lg border flex flex-col justify-between overflow-hidden transition-all text-[8.5px] leading-tight font-bold cursor-pointer hover:scale-[1.01] hover:shadow-sm"
                     :style="{
                       background: `linear-gradient(135deg, ${scheduleStore.getTournamentColor(arg.event.extendedProps.id_torneo).bg}, ${scheduleStore.getTournamentColor(arg.event.extendedProps.id_torneo).gradient ? scheduleStore.getTournamentColor(arg.event.extendedProps.id_torneo).gradient[1] : scheduleStore.getTournamentColor(arg.event.extendedProps.id_torneo).bg})`,
                       borderColor: scheduleStore.getTournamentColor(arg.event.extendedProps.id_torneo).gradient ? scheduleStore.getTournamentColor(arg.event.extendedProps.id_torneo).gradient[1] : scheduleStore.getTournamentColor(arg.event.extendedProps.id_torneo).bg,
                       borderLeft: `4px solid ${scheduleStore.getTournamentColor(arg.event.extendedProps.id_torneo).gradient ? scheduleStore.getTournamentColor(arg.event.extendedProps.id_torneo).gradient[1] : scheduleStore.getTournamentColor(arg.event.extendedProps.id_torneo).bg}`,
                       color: '#ffffff'
                     }">
                  <div class="space-y-0.5 min-w-0">
                    <p class="text-[7px] font-black uppercase tracking-wider opacity-85 truncate">
                      {{ arg.event.extendedProps.nombre_torneo }}
                    </p>
                    <p class="font-black truncate uppercase tracking-tight text-[8.5px] leading-tight">
                      {{ arg.event.title }}
                    </p>
                  </div>
                  <div class="flex items-center gap-1 opacity-90 text-[7.5px] leading-none mt-0.5">
                    <svg class="w-2 h-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ formatTimeRange(arg.event.start, arg.event.end) }}</span>
                  </div>
                </div>
              </template>
            </FullCalendar>
          </div>

          <!-- Leyenda de colores -->
          <div v-if="colorLegend.length > 0"
            class="bg-white rounded-2xl border border-surface-200 shadow-sm px-5 py-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-surface-400 mb-3">Leyenda de Torneos</p>
            <div class="flex flex-wrap gap-2">
              <button v-for="item in colorLegend" :key="item.id"
                @click="router.push({ query: { torneo: item.id } })"
                class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-surface-100 hover:border-surface-300 hover:shadow-sm transition-all text-xs font-bold text-surface-700 cursor-pointer">
                <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: item.color.bg }"></span>
                {{ item.nombre }}
              </button>
            </div>
          </div>

          <!-- Empty state -->
          <div v-if="todosLosTorneos.length === 0 && !loadingStates.torneos"
            class="p-20 flex flex-col items-center justify-center text-center bg-white rounded-3xl border-2 border-dashed border-surface-200">
            <div class="w-20 h-20 rounded-3xl bg-surface-50 flex items-center justify-center mb-6">
              <svg class="w-10 h-10 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-black text-surface-900">Sin torneos activos</h3>
            <p class="text-sm text-surface-500 mt-2 max-w-sm mx-auto">No hay torneos programados o en curso. Crea uno
              para empezar a calendarizar encuentros.</p>
          </div>
        </template>
      </template>
    </div>

    <!-- MODALES -->
    <CreateTournamentModal v-if="showCreateModal" @close="showCreateModal = false" @created="handleTorneoCreated" />

    <ModalAsignacionEncuentro v-if="showAssignModal && selectedEncuentro" :key="selectedEncuentro.id_encuentro" :encuentro="selectedEncuentro"
      :id-torneo="torneoIdFromQuery" @close="showAssignModal = false; selectedEncuentro = null"
      @saved="handleAssignmentSaved" />
  </main>
</template>

<style>
/* FullCalendar custom overrides */
.fc {
  --fc-border-color: #e2e8f0;
  --fc-today-bg-color: #f0f9ff;
  --fc-page-bg-color: transparent;
  --fc-neutral-bg-color: #f8fafc;
  font-family: inherit;
}

.fc .fc-toolbar-title {
  font-size: 1rem !important;
  font-weight: 900 !important;
  color: #0f172a;
}

.fc .fc-button {
  background: #f1f5f9 !important;
  border: 1px solid #e2e8f0 !important;
  color: #475569 !important;
  font-weight: 700 !important;
  font-size: 0.75rem !important;
  padding: 0.375rem 0.75rem !important;
  border-radius: 0.5rem !important;
  box-shadow: none !important;
  text-transform: capitalize !important;
}

.fc .fc-button:hover {
  background: #e2e8f0 !important;
  color: #1e293b !important;
}

.fc .fc-button-active {
  background: #0f172a !important;
  color: #fff !important;
  border-color: #0f172a !important;
}

.fc .fc-col-header-cell-cushion {
  font-weight: 700;
  font-size: 0.75rem;
  color: #64748b;
  text-transform: capitalize;
  padding: 0.5rem 0;
}

.fc .fc-timegrid-slot-label-cushion {
  font-size: 0.65rem;
  font-weight: 600;
  color: #94a3b8;
}

.fc .fc-event {
  background-color: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
  cursor: pointer !important;
}

.fc .fc-event-main {
  padding: 0 !important;
}

.fc .fc-daygrid-event-dot {
  display: none;
}

.fc .fc-scrollgrid {
  border: none !important;
}

.fc .fc-scrollgrid td {
  border-color: #f1f5f9 !important;
}

.fc .fc-timegrid-now-indicator-line {
  border-color: #ef4444 !important;
  border-width: 2px !important;
}

/* Glowing preview and mini-calendar styles */
@keyframes pulse-glowing {
  0% { opacity: 0.55; }
  50% { opacity: 0.85; }
  100% { opacity: 0.55; }
}

.preview-highlight-pulse {
  background: repeating-linear-gradient(
    45deg,
    rgba(16, 185, 129, 0.15),
    rgba(16, 185, 129, 0.15) 10px,
    rgba(59, 130, 246, 0.15) 10px,
    rgba(59, 130, 246, 0.15) 20px
  ) !important;
  border: 2px dashed #10b981 !important;
  animation: pulse-glowing 2s infinite ease-in-out !important;
  z-index: 50 !important;
}

/* Custom mini calendar styling */
.mini-calendar-container .fc {
  font-size: 0.72rem !important;
}

.mini-calendar-container .fc-header-toolbar {
  margin-bottom: 0.75rem !important;
  padding: 0 0.25rem !important;
}

.mini-calendar-container .fc-toolbar-title {
  font-size: 0.8rem !important;
  font-weight: 800 !important;
}

.mini-calendar-container .fc .fc-button {
  padding: 0.2rem 0.4rem !important;
  font-size: 0.68rem !important;
  font-weight: 700 !important;
}

/* Timezone GMT-6 indicator */
.fc .fc-timegrid-axis-cushion::after {
  content: "GMT-6";
  display: block;
  font-size: 8px;
  font-weight: 900;
  color: #94a3b8;
  text-transform: uppercase;
  text-align: center;
  margin-top: 2px;
}

/* Transparent wrapper overrides for custom eventContent slots */
.fc-v-event, .fc-timegrid-event, .fc-event {
  background-color: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
}

.fc-event-main, .fc-event-main-frame {
  padding: 0 !important;
  height: 100% !important;
  display: flex !important;
  flex-direction: column !important;
}

.fc-timegrid-event {
  height: 100% !important;
}

/* Custom timegrid axis slot layout to prevent hour clipping */
.fc .fc-timegrid-slot-label {
  width: 54px !important;
  min-width: 54px !important;
}

.fc .fc-timegrid-axis {
  width: 54px !important;
  min-width: 54px !important;
}

.fc .fc-timegrid-slot-label-cushion {
  font-size: 9px !important;
  font-weight: 800 !important;
  color: #94a3b8 !important;
  text-align: right !important;
  padding-right: 6px !important;
  text-transform: uppercase !important;
}
</style>