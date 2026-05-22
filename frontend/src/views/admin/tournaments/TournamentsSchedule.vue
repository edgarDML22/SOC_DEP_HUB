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
  todosLosTorneos, TOURNAMENT_COLORS
} = storeToRefs(scheduleStore)

// ── UI STATE ────────────────────────────────────────────────
const showCreateModal = ref(false)
const showAssignModal = ref(false)
const selectedEncuentro = ref(null)
const rangoActivo = ref(null)
const calendarRef = ref(null)

// ── COMPUTED ────────────────────────────────────────────────
const torneoIdFromQuery = computed(() => route.query.torneo ? Number(route.query.torneo) : null)
const isDetailMode = computed(() => !!torneoIdFromQuery.value)

const calendarPlugins = [dayGridPlugin, timeGridPlugin, interactionPlugin]

// Events for the general overview calendar
const overviewEvents = computed(() => allTournamentsEvents.value)

// Events for the detail calendar (single tournament)
const detailEvents = computed(() => calendarEvents.value.filter(e => e.start))

// Color legend for overview
const colorLegend = computed(() => {
  return todosLosTorneos.value.map(t => ({
    id: t.id_torneo,
    nombre: t.nombre_torneo,
    color: scheduleStore.getTournamentColor(t.id_torneo),
  }))
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
  plugins: calendarPlugins,
  initialView: 'timeGridWeek',
  locale: 'es',
  headerToolbar: {
    left: 'prev,today,next',
    center: 'title',
    right: 'timeGridWeek,timeGridDay'
  },
  height: 'auto',
  slotMinTime: '06:00:00',
  slotMaxTime: '23:00:00',
  allDaySlot: false,
  nowIndicator: true,
  events: detailEvents.value,
  eventClick: handleDetailEventClick,
  editable: false,
  selectable: false,
  eventDisplay: 'block',
  slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
  dayHeaderFormat: { weekday: 'short', day: 'numeric', month: 'short' },
  buttonText: { today: 'Hoy', week: 'Semana', day: 'Día' },
}))

// ── EVENT HANDLERS ──────────────────────────────────────────
function handleOverviewEventClick(info) {
  const idTorneo = info.event.extendedProps?.id_torneo
  if (idTorneo) {
    router.push({ query: { torneo: idTorneo } })
  }
}

function handleDetailEventClick(info) {
  const enc = info.event.extendedProps
  if (enc) {
    selectedEncuentro.value = enc
    showAssignModal.value = true
  }
}

function openAssignModal(enc) {
  selectedEncuentro.value = enc
  showAssignModal.value = true
}

function handleAssignmentSaved() {
  if (torneoIdFromQuery.value) {
    scheduleStore.fetchEncuentrosTorneo(torneoIdFromQuery.value)
  }
}

function goToOverview() {
  scheduleStore.clearSelection()
  router.push({ query: {} })
}

function handleTorneoCreated() {
  tournamentStore.fetchTorneos()
  if (!isDetailMode.value) {
    scheduleStore.fetchTodosLosTorneos()
  }
}

const formatFase = (fase) => {
  if (!fase || fase === 'N/A') return 'Sin fase'
  return fase.replace(/_/g, ' ')
}

const formatFecha = (f) => {
  if (!f) return '—'
  return new Date(f).toLocaleString('es-MX', {
    day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
  })
}

// ── WATCHERS ────────────────────────────────────────────────
watch(torneoIdFromQuery, async (id) => {
  if (id) {
    await scheduleStore.fetchEncuentrosTorneo(id)
    await scheduleStore.fetchEspacios()    
    await scheduleStore.fetchArbitrosTorneo(id)  // Obtener todos los árbitros del torneo  } else {
    scheduleStore.clearSelection()
    scheduleStore.fetchTodosLosTorneos()
  }
}, { immediate: false })

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

        <!-- Layout: Calendario + Sidebar -->
        <div v-else class="flex gap-6 items-start">
          <!-- Calendario principal -->
          <div class="flex-1 min-w-0 space-y-5">
            <!-- Calendar Card -->
            <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5 overflow-hidden">
              <FullCalendar ref="calendarRef" :options="detailCalendarOptions" />
            </div>

            <!-- Encuentros sin programar -->
            <div v-if="unscheduledEncuentros.length > 0"
              class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">
              <div class="px-5 py-4 border-b border-surface-100 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center">
                  <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h3 class="text-sm font-black text-surface-900">Encuentros sin programar</h3>
                <span
                  class="ml-auto px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 text-[10px] font-black">
                  {{ unscheduledEncuentros.length }}
                </span>
              </div>
              <div class="divide-y divide-surface-100">
                <div v-for="enc in unscheduledEncuentros" :key="enc.id_encuentro"
                  class="px-5 py-3 flex items-center justify-between hover:bg-surface-50 transition-colors group cursor-pointer"
                  @click="openAssignModal(enc)">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-2 h-2 rounded-full bg-surface-300 shrink-0"></div>
                    <div class="min-w-0">
                      <p class="text-xs font-bold text-surface-800 truncate">
                        {{ enc.competidor1?.equipo?.nombre_equipo || enc.competidor1?.participante?.nombre_equipo || enc.competidor1?.participante?.nombre_completo || enc.competidor1?.nombre_completo || (enc.competidor1?.id_interno ? `Participante #${enc.competidor1.id_interno}` : 'TBD') }}
                        <span class="text-surface-400 mx-1">vs</span>
                        {{ enc.competidor2?.equipo?.nombre_equipo || enc.competidor2?.participante?.nombre_equipo || enc.competidor2?.participante?.nombre_completo || enc.competidor2?.nombre_completo || (enc.competidor2?.id_interno ? `Participante #${enc.competidor2.id_interno}` : 'TBD') }}
                      </p>
                      <p class="text-[10px] text-surface-400 font-bold uppercase">{{ formatFase(enc.fase_bracket || enc.fase) }}</p>
                    </div>
                  </div>
                  <button
                    class="px-3 py-1.5 rounded-lg bg-surface-900 text-white text-[10px] font-bold opacity-0 group-hover:opacity-100 transition-opacity hover:bg-primary-600 shrink-0">
                    Asignar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="w-72 shrink-0 sticky top-6">
            <SidebarArbitros :torneo="torneoSeleccionado" :arbitros-pool="arbitrosPool" :loading="loadingStates.arbitros"
              :rango-activo="rangoActivo" />
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
          <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5 overflow-hidden">
            <FullCalendar :options="overviewCalendarOptions" />
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

    <ModalAsignacionEncuentro v-if="showAssignModal && selectedEncuentro" :encuentro="selectedEncuentro"
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
  border-radius: 0.5rem !important;
  border-width: 2px !important;
  padding: 2px 6px !important;
  font-size: 0.7rem !important;
  font-weight: 700 !important;
  cursor: pointer !important;
  transition: box-shadow 0.2s ease, transform 0.15s ease !important;
}

.fc .fc-event:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
  transform: scale(1.02) !important;
  z-index: 10 !important;
}

.fc .fc-event.event-unassigned {
  border-style: dashed !important;
  border-color: #64748b !important;
  opacity: 0.85;
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
</style>