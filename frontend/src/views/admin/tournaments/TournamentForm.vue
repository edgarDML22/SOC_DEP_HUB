<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useTournamentStore } from '@/stores/tournamentStore'
import { useScheduleStore } from '@/stores/admin/scheduleStore'
import { storeToRefs } from 'pinia'

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import Select from 'primevue/select'
import CreateTournamentModal from '@/components/tournaments/CreateTournamentModal.vue'
import EditTournamentModal from '@/components/tournaments/EditTournamentModal.vue'
import TournamentStatusModal from '@/components/tournaments/TournamentStatusModal.vue'

const router = useRouter()
const route = useRoute()
const store = useTournamentStore()
const scheduleStore = useScheduleStore()
const { torneos, loading, filtros } = storeToRefs(store)

// Obtener rol para mostrar opciones exclusivas
const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
const esSubgerente = computed(() => userData.rol === 'subgerente')

// ── FILTROS ────────────────────────────────────────────────────
const search = ref('')
const STATUS_OPTS = [
  { label: 'Todos los estados', value: null },
  { label: 'En Planificación', value: 'EN_PLANIFICACION' },
  { label: 'En Inscripción', value: 'EN_INSCRIPCION' },
  { label: 'Programado', value: 'PROGRAMADO' },
  { label: 'En Curso', value: 'EN_CURSO' },
  { label: 'Finalizado', value: 'FINALIZADO' },
  { label: 'Cancelado', value: 'CANCELADO' },
]

const filteredTorneos = computed(() => {
  let r = torneos.value
  if (search.value) {
    const q = search.value.toLowerCase()
    r = r.filter(t =>
      t.nombre_torneo?.toLowerCase().includes(q) ||
      t.disciplina?.toLowerCase().includes(q) ||
      t.categoria?.toLowerCase().includes(q)
    )
  }
  if (filtros.value.estatus) r = r.filter(t => (t.estado || t.estatus_torneo) === filtros.value.estatus)
  return r
})

const hasActiveFilters = computed(() => search.value || filtros.value.estatus)
const clearFilters = () => { search.value = ''; filtros.value.estatus = null }

// ── ESTILOS DINÁMICOS ──────────────────────────────────────────
const estadoAccent = (estado) => ({
  PROGRAMADO: 'border-t-blue-500',
  EN_PLANIFICACION: 'border-t-amber-500',
  EN_INSCRIPCION: 'border-t-sky-500',
  EN_CURSO: 'border-t-purple-500',
  FINALIZADO: 'border-t-emerald-500',
  CANCELADO: 'border-t-red-500',
}[estado] ?? 'border-t-surface-200')

const estadoIconBg = (estado) => ({
  PROGRAMADO: 'bg-blue-50 text-blue-600',
  EN_PLANIFICACION: 'bg-amber-50 text-amber-600',
  EN_INSCRIPCION: 'bg-sky-50 text-sky-600',
  EN_CURSO: 'bg-purple-50 text-purple-600',
  FINALIZADO: 'bg-emerald-50 text-emerald-600',
  CANCELADO: 'bg-red-50 text-red-600',
}[estado] ?? 'bg-surface-100 text-surface-400')

// ── FORMATEO DE FECHAS AMIGABLE (es-MX) ──────────────────────────
const capitalize = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1) : ''

const parseFecha = (f) => {
  if (!f) return null
  if (typeof f === 'string' && !f.includes('T')) {
    return new Date(f + 'T12:00:00')
  }
  return new Date(f)
}

/**
 * Formatea una fecha a estilo amigable usando Intl.DateTimeFormat en español (es-MX).
 * Ej. "15 Oct 2026". Si opciones.relative es true y la fecha está en rango, devuelve fecha relativa.
 */
const formatFecha = (fecha, opciones = {}) => {
  if (!fecha) return '—'
  const d = parseFecha(fecha)
  if (!d || isNaN(d.getTime())) return '—'

  // `relative` es una opción nuestra, no de Intl: se separa para no contaminar el formateador.
  const { relative = false, ...intlOpciones } = opciones

  if (relative) {
    const hoy = new Date()
    hoy.setHours(12, 0, 0, 0)
    const diffDays = Math.round((d.getTime() - hoy.getTime()) / (1000 * 60 * 60 * 24))
    if (Math.abs(diffDays) <= 30) {
      const rtf = new Intl.RelativeTimeFormat('es-MX', { numeric: 'auto' })
      return capitalize(rtf.format(diffDays, 'day'))
    }
  }

  const formatter = new Intl.DateTimeFormat('es-MX', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    ...intlOpciones
  })

  const parts = formatter.format(d).replace(/\./g, '').split(' ')
  return parts.map((p, idx) => (idx === 1 ? capitalize(p) : p)).join(' ')
}

/**
 * Formatea un rango de fechas y calcula la duración total del torneo en días.
 * Ej. "15 Oct 2026 – 20 Oct 2026 (6 días)"
 */
const formatRangoFechas = (inicio, fin) => {
  if (!inicio && !fin) return '—'
  if (inicio && !fin) return formatFecha(inicio)
  if (!inicio && fin) return formatFecha(fin)

  const dInicio = parseFecha(inicio)
  const dFin = parseFecha(fin)

  const fmtInicio = formatFecha(inicio)
  const fmtFin = formatFecha(fin)

  if (!dInicio || !dFin || isNaN(dInicio.getTime()) || isNaN(dFin.getTime())) {
    return `${fmtInicio} – ${fmtFin}`
  }

  const diffTime = dFin.getTime() - dInicio.getTime()
  const diffDays = Math.max(1, Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1)
  const diasTxt = diffDays === 1 ? '1 día' : `${diffDays} días`

  if (fmtInicio === fmtFin) {
    return `${fmtInicio} (${diasTxt})`
  }

  return `${fmtInicio} – ${fmtFin} (${diasTxt})`
}

// ── TOOLTIPS CONTEXTUALES ────────────────────────────────────────
const tooltipEstado = (estado) => {
  const tooltips = {
    EN_PLANIFICACION: 'Parámetros del torneo en preparación, registro aún no abierto',
    EN_INSCRIPCION: 'Pre-registro habilitado para socios y competidores',
    PROGRAMADO: 'Bracket generado y horarios de partidos asignados en espacios deportivos',
    EN_CURSO: 'Competencia activa, encuentros disputándose y captura de resultados',
    FINALIZADO: 'Torneo concluido exitosamente con resultados oficiales',
    CANCELADO: 'Torneo suspendido. Haz clic en la tarjeta para ver el motivo registrado'
  }
  return tooltips[estado] ?? `Estado: ${estado || 'Sin definir'}`
}

// ── MODALES Y ACCIONES ──────────────────────────────────────────
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingTorneo = ref(null)
const showStatusModal = ref(false)
const statusModalTorneo = ref(null)

const openStatusModal = (torneo) => {
  statusModalTorneo.value = torneo
  showStatusModal.value = true
}

const handleTorneoCreated = () => {
  store.fetchTorneos()
}

const handleTorneoUpdated = () => {
  store.fetchTorneos()
}

const handleStatusUpdated = () => {
  store.fetchTorneos()
}

const buildMenuItems = (torneo) => {
  const actions = []

  actions.push({
    label: 'Ver detalle',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>`,
    action: () => {
      store.torneoActivo = torneo
      router.push({ path: `/admin/tournaments/${torneo.id_torneo}` })
    }
  })

  actions.push({
    label: 'Gestionar Estado',
    icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
           </svg>`,
    action: () => openStatusModal(torneo)
  })

  if ((torneo.estado || torneo.estatus_torneo) === 'EN_PLANIFICACION') {
    actions.push({
      label: 'Editar Torneo',
      icon: `<svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
               <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
             </svg>`,
      action: () => {
        editingTorneo.value = torneo
        showEditModal.value = true
      }
    })
  }

  if ((torneo.estado || torneo.estatus_torneo) === 'EN_INSCRIPCION') {
    actions.push({
      label: 'Ver pre-registros',
      icon: `<svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
               <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m9-4h.01M12 17h.01" />
             </svg>`,
      action: () => {
        router.push({ path: '/admin/tournaments/pre-registros', query: { torneo_id: torneo.id_torneo } })
      }
    })
  }

  if (esSubgerente.value) {
    actions.push({
      label: 'Validar Resultados',
      icon: `<svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
               <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
             </svg>`,
      action: () => {
        router.push({ path: '/admin/tournaments/resultados-pendientes', query: { torneo_id: torneo.id_torneo } })
      }
    })
  }

  return actions
}

// ── EXPANSIÓN (torneos cancelados) ─────────────────────────────
const expandedTorneoId = ref(null)

const estatusTorneo = (t) => t.estado || t.estatus_torneo
const isCancelado = (t) => estatusTorneo(t) === 'CANCELADO'

const toggleExpand = (torneo) => {
  if (!isCancelado(torneo)) return
  const id = torneo.id_torneo
  expandedTorneoId.value = expandedTorneoId.value === id ? null : id
}

// ── INIT ───────────────────────────────────────────────────────
onMounted(() => {
  store.fetchTorneos()
  scheduleStore.fetchTodosLosTorneos() // Prefetch silent
})
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader title="Gestión de Torneos" subtitle="Monitorea y administra el ciclo de vida de los torneos.">
        <!-- Switcher Tabla / Tarjetas / Calendario -->
        <div class="flex p-1 bg-slate-100 rounded-2xl shadow-inner border border-surface-200 mr-4">
          <button @click="router.push('/admin/tournaments')" 
                  v-tooltip.bottom="'Cambiar a vista de tabla'"
                  class="py-1.5 px-4 rounded-xl text-xs font-black flex items-center gap-1.5 transition-all duration-200 ease-out active:scale-[0.97] border-none cursor-pointer"
                  :class="route.name === 'tournaments-list' 
                    ? 'bg-surface-900 text-white shadow-md transform scale-[1.01]' 
                    : 'text-surface-500 hover:bg-white hover:text-surface-700'">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            Tabla
          </button>
          <button @click="router.push('/admin/tournaments/cards')" 
                  v-tooltip.bottom="'Vista actual en cuadrícula de tarjetas'"
                  class="py-1.5 px-4 rounded-xl text-xs font-black flex items-center gap-1.5 transition-all duration-200 ease-out active:scale-[0.97] border-none cursor-pointer"
                  :class="route.name === 'tournaments-cards' 
                    ? 'bg-surface-900 text-white shadow-md transform scale-[1.01]' 
                    : 'text-surface-500 hover:bg-white hover:text-surface-700'">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
            </svg>
            Tarjetas
          </button>
          <button @click="router.push('/admin/tournaments/schedule')" 
                  v-tooltip.bottom="'Ver calendario mensual de torneos'"
                  class="py-1.5 px-4 rounded-xl text-xs font-black flex items-center gap-1.5 transition-all duration-200 ease-out active:scale-[0.97] border-none cursor-pointer"
                  :class="route.name === 'tournaments-schedule' 
                    ? 'bg-surface-900 text-white shadow-md transform scale-[1.01]' 
                    : 'text-surface-500 hover:bg-white hover:text-surface-700'">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75" />
            </svg>
            Calendario
          </button>
        </div>
        
        <button @click="showCreateModal = true" 
                v-tooltip.bottom="'Crear un nuevo torneo deportivo'"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-surface-900 text-white
                 text-sm font-bold hover:bg-primary-600 transition-colors shadow-sm cursor-pointer">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="16" />
            <line x1="8" y1="12" x2="16" y2="12" />
          </svg>
          Crear Torneo
        </button>
      </AdminPageHeader>

      <!-- FILTROS -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-6 space-y-4">
        <div class="relative">
          <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" />
          </svg>
          <input v-model="search" placeholder="Buscar por nombre, disciplina o categoría..." 
                 class="w-full pl-11 pr-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-sm font-medium text-surface-900 focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 outline-none transition-all" />
        </div>
        <div class="flex flex-col sm:flex-row gap-4 items-end">
          <div class="flex flex-col gap-1.5 flex-1 sm:max-w-xs">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estado</label>
            <Select v-model="filtros.estatus" :options="STATUS_OPTS" option-label="label" option-value="value" placeholder="Todos los estados" class="w-full text-sm" />
          </div>
          <button v-if="hasActiveFilters" @click="clearFilters" class="text-xs font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1.5 transition-colors pb-3 cursor-pointer">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12" /></svg>
            Limpiar filtros
          </button>
        </div>
      </div>

      <!-- GRID -->
      <div v-if="loading && torneos.length === 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="n in 6" :key="n" class="bg-white rounded-3xl border border-surface-200 p-6 animate-pulse space-y-4">
          <div class="flex items-start justify-between gap-3">
            <div class="w-12 h-12 rounded-2xl bg-surface-100 shrink-0" />
            <div class="flex-1 space-y-2 pt-1">
              <div class="h-4 bg-surface-200 rounded-lg w-3/4" />
              <div class="h-3 bg-surface-100 rounded-lg w-1/2" />
            </div>
          </div>
          <div class="h-20 bg-surface-50 rounded-2xl w-full" />
        </div>
      </div>

      <div v-else-if="filteredTorneos.length === 0" class="p-20 flex flex-col items-center justify-center text-center bg-white rounded-3xl border-2 border-dashed border-surface-200">
        <h3 class="text-lg font-black text-surface-900">Sin torneos</h3>
        <p class="text-sm text-surface-500 mt-1 max-w-xs">No se encontraron torneos con los criterios seleccionados.</p>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="torneo in filteredTorneos" :key="torneo.id_torneo" 
             class="bg-white rounded-3xl border border-t-4 border-surface-200 shadow-sm hover:shadow-xl hover:shadow-surface-200/40 transition-all duration-300 group flex flex-col overflow-hidden"
             :class="[estadoAccent(torneo.estado || torneo.estatus_torneo), { 'cursor-pointer': isCancelado(torneo) }]"
             @click="toggleExpand(torneo)">
          
          <div class="p-6 flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                 :class="estadoIconBg(torneo.estado || torneo.estatus_torneo)">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6M18 9h1.5a2.5 2.5 0 0 0 0-5H18M4 22h16M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22M18 2H6v7a6 6 0 0 0 12 0V2z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0 pt-0.5">
              <h3 class="text-sm font-black text-surface-900 truncate leading-tight">{{ torneo.nombre_torneo }}</h3>
              <p class="text-[10px] font-black text-surface-400 mt-1 uppercase tracking-widest">{{ torneo.disciplina || '—' }}</p>
              <div class="mt-2.5 flex flex-col items-start gap-1">
                <span v-tooltip.top="tooltipEstado(torneo.estado || torneo.estatus_torneo)" class="inline-block">
                  <BadgeStatus :status="torneo.estado || torneo.estatus_torneo" />
                </span>
                <p
                  v-if="isCancelado(torneo) && expandedTorneoId === torneo.id_torneo"
                  class="text-xs text-surface-500 font-medium leading-relaxed mt-1"
                >
                  {{ torneo.motivo_cancelacion || 'Sin motivo de cancelación registrado.' }}
                </p>
              </div>
            </div>
            <div @click.stop v-tooltip.left="'Opciones y gestión del torneo'">
              <ActionMenu :items="buildMenuItems(torneo)" align="right" />
            </div>
          </div>

          <div class="px-6 pb-2 space-y-2">
            <div class="flex items-center justify-between py-2.5 px-3 rounded-xl bg-surface-50 border border-surface-100">
              <span class="text-[10px] font-black uppercase tracking-wider text-surface-400">Categoría</span>
              <span class="text-xs font-bold text-surface-700">{{ torneo.categoria || '—' }}</span>
            </div>
            <div class="flex items-center justify-between py-2.5 px-3 rounded-xl bg-surface-50 border border-surface-100"
                 v-tooltip.top="formatFecha(torneo.fecha_inicio, { relative: true }) !== '—' ? `Inicia: ${formatFecha(torneo.fecha_inicio, { relative: true })}` : null">
              <span class="text-[10px] font-black uppercase tracking-wider text-surface-400">Inicio</span>
              <span class="text-xs font-bold text-surface-700">{{ formatFecha(torneo.fecha_inicio) }}</span>
            </div>
            <div v-if="torneo.fecha_fin" class="flex items-center justify-between py-2.5 px-3 rounded-xl bg-surface-50 border border-surface-100"
                 v-tooltip.top="'Periodo total del torneo'">
              <span class="text-[10px] font-black uppercase tracking-wider text-surface-400">Duración</span>
              <span class="text-xs font-bold text-surface-700 text-right">{{ formatRangoFechas(torneo.fecha_inicio, torneo.fecha_fin) }}</span>
            </div>
          </div>

          <div class="p-5 mt-auto">
            <button @click.stop="router.push(`/admin/tournaments/${torneo.id_torneo}`)" 
                    v-tooltip.bottom="'Consultar detalles completos y bracket del torneo'"
                    class="w-full py-2.5 rounded-xl bg-surface-900 text-white text-xs font-bold hover:bg-primary-600 transition-colors shadow-sm flex items-center justify-center gap-2 cursor-pointer">
              Ver detalles
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODALES DE TORNEO -->
    <CreateTournamentModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="handleTorneoCreated"
    />

    <EditTournamentModal
      v-if="showEditModal"
      :torneo="editingTorneo"
      @close="showEditModal = false"
      @updated="handleTorneoUpdated"
    />

    <TournamentStatusModal
      v-if="showStatusModal"
      :torneo="statusModalTorneo"
      @close="showStatusModal = false"
      @updated="handleStatusUpdated"
    />
  </main>
</template>
