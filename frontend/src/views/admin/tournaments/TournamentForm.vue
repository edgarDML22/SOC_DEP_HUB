<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useTournamentStore } from '@/stores/tournamentStore'
import { storeToRefs } from 'pinia'
import { useAlerts } from '@/composables/useAlerts'

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import Select from 'primevue/select'

const router = useRouter()
const route = useRoute()
const store = useTournamentStore()
const { torneos, loading, error: errorMsg, filtros } = storeToRefs(store)
const { toastSuccess, toastError, toastInfo } = useAlerts()

// ── FILTROS ────────────────────────────────────────────────────
const search = ref('')
const STATUS_OPTS = [
  { label: 'Todos los estados', value: null },
  { label: 'En Planificación', value: 'EN_PLANIFICACION' },
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
  EN_CURSO: 'border-t-purple-500',
  FINALIZADO: 'border-t-emerald-500',
  CANCELADO: 'border-t-red-500',
}[estado] ?? 'border-t-surface-200')

const estadoIconBg = (estado) => ({
  PROGRAMADO: 'bg-blue-50 text-blue-600',
  EN_PLANIFICACION: 'bg-amber-50 text-amber-600',
  EN_CURSO: 'bg-purple-50 text-purple-600',
  FINALIZADO: 'bg-emerald-50 text-emerald-600',
  CANCELADO: 'bg-red-50 text-red-600',
}[estado] ?? 'bg-surface-100 text-surface-400')

const formatFecha = (f) => {
  if (!f) return '—'
  return new Date(f + 'T12:00:00').toLocaleDateString('es-MX', {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}

// ── ACCIONES ───────────────────────────────────────────────────
const showCancelModal = ref(false)
const selectedTorneo = ref(null)
const isActionLoading = ref(false)

const openCancelModal = (torneo) => {
  selectedTorneo.value = torneo
  showCancelModal.value = true
}

const buildMenuItems = (torneo) => {
  const actions = []
  const status = torneo.estado || torneo.estatus_torneo

  actions.push({
    label: 'Ver detalle',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>`,
    action: () => {
      store.torneoActivo = torneo
      router.push({ path: `/admin/tournaments/${torneo.id_torneo}` })
    }
  })

  if (status !== 'CANCELADO' && status !== 'FINALIZADO') {
    actions.push({
      label: 'Cancelar',
      destructive: true,
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
      action: () => openCancelModal(torneo)
    })
  }

  return actions
}

const confirmCancel = async () => {
  if (!selectedTorneo.value) return
  isActionLoading.value = true
  try {
    await store.transicionarEstatus(selectedTorneo.value.id_torneo, 'CANCELADO', 'Cancelado desde vista de tarjetas')
    showCancelModal.value = false
    toastInfo('Torneo cancelado', selectedTorneo.value.nombre_torneo, 'success')
  } catch (err) {
    toastError(store.error || 'No se pudo cancelar el torneo.')
  } finally {
    isActionLoading.value = false
  }
}

// ── INIT ───────────────────────────────────────────────────────
onMounted(() => {
  store.fetchTorneos()
})
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader title="Gestión de Torneos" subtitle="Monitorea y administra el ciclo de vida de los torneos.">
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
        
        <button @click="router.push('/admin/tournaments/create')" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-surface-900 text-white
                 text-sm font-bold hover:bg-primary-600 transition-colors shadow-sm">
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
          <button v-if="hasActiveFilters" @click="clearFilters" class="text-xs font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1.5 transition-colors pb-3">
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
             :class="estadoAccent(torneo.estado || torneo.estatus_torneo)">
          
          <div class="p-6 flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                 :class="estadoIconBg(torneo.estado || torneo.estatus_torneo)">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6M18 9h1.5a2.5 2.5 0 0 0 0-5H18M4 22h16M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22M18 2H6v7a6 6 0 0 0 12 0V2z" />
              </svg>
            </div>
            <div class="flex-1 min-w-0 pt-0.5">
              <h3 class="text-sm font-black text-surface-900 truncate leading-tight">{{ torneo.nombre_torneo }}</h3>
              <div class="text-[11px] text-surface-500 font-bold font-mono uppercase mt-0.5 tracking-tight">ID: {{ torneo.id_torneo }}</div>
              <p class="text-[10px] font-black text-surface-400 mt-1 uppercase tracking-widest">{{ torneo.disciplina || '—' }}</p>
              <div class="mt-2.5">
                <BadgeStatus :status="torneo.estado || torneo.estatus_torneo" />
              </div>
            </div>
            <ActionMenu :items="buildMenuItems(torneo)" align="right" />
          </div>

          <div class="px-6 pb-2 space-y-2">
            <div class="flex items-center justify-between py-2.5 px-3 rounded-xl bg-surface-50 border border-surface-100">
              <span class="text-[10px] font-black uppercase tracking-wider text-surface-400">Categoría</span>
              <span class="text-xs font-bold text-surface-700">{{ torneo.categoria || '—' }}</span>
            </div>
            <div class="flex items-center justify-between py-2.5 px-3 rounded-xl bg-surface-50 border border-surface-100">
              <span class="text-[10px] font-black uppercase tracking-wider text-surface-400">Inicio</span>
              <span class="text-xs font-bold text-surface-700">{{ formatFecha(torneo.fecha_inicio) }}</span>
            </div>
          </div>

          <div class="p-5 mt-auto">
            <button @click="router.push(`/admin/tournaments/${torneo.id_torneo}`)" 
                    class="w-full py-2.5 rounded-xl bg-surface-900 text-white text-xs font-bold hover:bg-primary-600 transition-colors shadow-sm flex items-center justify-center gap-2">
              Ver detalles
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL CANCELACIÓN -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm" @click.self="showCancelModal = false">
          <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-8 text-center space-y-6">
            <h3 class="text-xl font-black text-surface-900">¿Cancelar torneo?</h3>
            <p class="text-sm text-surface-500">Esta acción no se puede deshacer fácilmente.</p>
            <div class="flex gap-3">
              <CancelButton label="Volver" @click="showCancelModal = false" class="flex-1" />
              <ConfirmButton label="Sí, cancelar" :loading="isActionLoading" @click="confirmCancel" class="flex-1 bg-red-600! hover:bg-red-700!" />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </main>
</template>
