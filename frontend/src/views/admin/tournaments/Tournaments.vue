<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useTournamentStore } from '@/stores/tournamentStore'
import { useScheduleStore } from '@/stores/admin/scheduleStore'
import { storeToRefs } from 'pinia'
import { useAlerts } from '@/composables/useAlerts'

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import SearchInput from '@/components/gerente/ui/SearchInput.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import ExportCsvButton from '@/components/gerente/ui/ExportCsvButton.vue'
import { IconFilter, IconChevronDown } from '@/components/icons'
import CreateTournamentModal from '@/components/tournaments/CreateTournamentModal.vue'
import TournamentStatusModal from '@/components/tournaments/TournamentStatusModal.vue'

const router = useRouter()
const route = useRoute()
const store = useTournamentStore()
const { torneos, loading, error: errorMsg, filtros } = storeToRefs(store)
const { toastSuccess, toastError, toastInfo } = useAlerts()

// Obtener rol para mostrar opciones exclusivas
const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
const esSubgerente = computed(() => userData.rol === 'subgerente')

// ── FILTROS ────────────────────────────────────────────────────
const STATUS_OPTS = [
  { label: 'Todos los estados', value: null },
  { label: 'En Planificación', value: 'EN_PLANIFICACION' },
  { label: 'En Inscripción', value: 'EN_INSCRIPCION' },
  { label: 'Programado', value: 'PROGRAMADO' },
  { label: 'En Curso', value: 'EN_CURSO' },
  { label: 'Finalizado', value: 'FINALIZADO' },
  { label: 'Cancelado', value: 'CANCELADO' },
]

const ACCESS_OPTS = [
  { label: 'Todos', value: null },
  { label: 'Interno', value: 'INTERNO' },
  { label: 'Abierto', value: 'ABIERTO' },
]

// Disciplinas dinámicas desde los torneos cargados
const disciplineOpts = computed(() => {
  const unique = [...new Set(torneos.value.map(t => t.disciplina).filter(Boolean))]
  return [{ label: 'Todas las disciplinas', value: null }, ...unique.map(d => ({ label: d, value: d }))]
})

// Debounce para búsqueda
let debounceTimer = null
const searchQuery = ref(filtros.value.search || '')
watch(searchQuery, (val) => {
  filtros.value.search = val
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    store.fetchTorneos()
  }, 300)
})

// Watchers para otros filtros
watch([() => filtros.value.estatus, () => filtros.value.disciplina, () => filtros.value.tipo_acceso], () => {
  store.fetchTorneos()
})

const hasActiveFilters = computed(() => filtros.value.search || filtros.value.estatus || filtros.value.disciplina || filtros.value.tipo_acceso)
const clearFilters = () => {
  searchQuery.value = ''
  filtros.value.search = ''
  filtros.value.estatus = null
  filtros.value.disciplina = null
  filtros.value.tipo_acceso = null
}

// ── MODALES Y ACCIONES ──────────────────────────────────────────
const showCreateModal = ref(false)
const showStatusModal = ref(false)
const statusModalTorneo = ref(null)

const openStatusModal = (torneo) => {
  statusModalTorneo.value = torneo
  showStatusModal.value = true
}

const handleTorneoCreated = () => {
  store.fetchTorneos()
}

const handleStatusUpdated = () => {
  store.fetchTorneos()
}

const buildActions = (torneo) => {
  const actions = []

  actions.push({
    label: 'Ver detalle',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
             <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
           </svg>`,
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

  return actions;
}

// ── EXPANSIÓN (torneos cancelados) ─────────────────────────────
const expandedTorneoId = ref(null)

const estatusTorneo = (torneo) => torneo.estado || torneo.estatus_torneo

const isCancelado = (torneo) => estatusTorneo(torneo) === 'CANCELADO'

const toggleExpand = (torneo) => {
  if (!isCancelado(torneo)) return
  const id = torneo.id_torneo
  expandedTorneoId.value = expandedTorneoId.value === id ? null : id
}

// ── INIT ───────────────────────────────────────────────────────
onMounted(() => {
  store.fetchTorneos()
  const scheduleStore = useScheduleStore()
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
        
        <button @click="router.push('/admin/tournaments/pre-registros')" 
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl border border-surface-200 bg-white text-surface-700
                 text-sm font-bold hover:bg-surface-50 transition-colors shadow-sm mr-3 cursor-pointer">
          <i class="fas fa-inbox text-surface-500"></i>
          Ver Pre-registros
        </button>
        
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

      <!-- BARRA DE FILTROS -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5 space-y-4">
        <SearchInput v-model="searchQuery" placeholder="Buscar por nombre..." />
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Estatus -->
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estatus</label>
            <div class="relative">
              <IconFilter class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="filtros.estatus"
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
                <option v-for="opt in STATUS_OPTS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            </div>
          </div>

          <!-- Disciplina -->
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Disciplina</label>
            <div class="relative">
              <IconFilter class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="filtros.disciplina"
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
                <option v-for="opt in disciplineOpts" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            </div>
          </div>

          <!-- Tipo Acceso -->
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Acceso</label>
            <div class="relative">
              <IconFilter class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="filtros.tipo_acceso"
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
                <option v-for="opt in ACCESS_OPTS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            </div>
          </div>
        </div>

        <Transition enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1">
          <div v-if="hasActiveFilters" class="flex justify-end">
            <button @click="clearFilters"
              class="text-xs font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1.5 transition-colors">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 6L6 18M6 6l12 12" />
              </svg>
              Limpiar filtros
            </button>
          </div>
        </Transition>
      </div>

      <!-- TABLA -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-visible min-h-96">
        
        <!-- Estado: cargando -->
        <div v-if="loading && torneos.length === 0" class="p-8 space-y-3">
          <div v-for="n in 6" :key="n" class="flex items-center gap-4 animate-pulse py-3 border-b border-surface-100">
            <div class="flex-1 space-y-2">
              <div class="h-3.5 bg-surface-200 rounded-lg w-48" />
              <div class="h-3 bg-surface-100 rounded-lg w-28" />
            </div>
            <div class="h-5 w-20 bg-surface-100 rounded-full" />
            <div class="h-5 w-24 bg-surface-100 rounded-full" />
          </div>
        </div>

        <!-- Estado: vacío -->
        <div v-else-if="torneos.length === 0" class="p-16 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 rounded-2xl bg-surface-100 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <h3 class="text-base font-black text-surface-900">Sin torneos</h3>
          <p class="text-sm text-surface-500 mt-1 max-w-xs">No se encontraron torneos con los criterios seleccionados.</p>
          <button @click="clearFilters" class="mt-4 text-sm font-bold text-primary-600 hover:underline">
            Limpiar filtros
          </button>
        </div>

        <!-- Tabla con datos -->
        <table v-else class="w-full text-sm">
          <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
            <tr>
              <th class="px-5 py-3.5 text-left font-extrabold rounded-tl-2xl">Torneo</th>
              <th class="px-4 py-3.5 text-left font-extrabold hidden md:table-cell">Disciplina</th>
              <th class="px-4 py-3.5 text-left font-extrabold hidden lg:table-cell">Categoría</th>
              <th class="px-4 py-3.5 text-left font-extrabold hidden sm:table-cell">Acceso</th>
              <th class="px-4 py-3.5 text-left font-extrabold">Estado</th>
              <th class="px-4 py-3.5 text-right font-extrabold rounded-tr-2xl">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-100">
            <template v-for="torneo in torneos" :key="torneo.id_torneo">
              <tr
                class="hover:bg-surface-50/70 transition-colors group"
                :class="{ 'cursor-pointer': isCancelado(torneo) }"
                @click="toggleExpand(torneo)"
              >
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-2">
                    <button
                      v-if="isCancelado(torneo)"
                      type="button"
                      class="w-6 h-6 rounded-lg border border-surface-200 flex items-center justify-center text-surface-500 hover:text-primary-600 shrink-0"
                      :aria-expanded="expandedTorneoId === torneo.id_torneo"
                      @click.stop="toggleExpand(torneo)"
                    >
                      <svg
                        class="w-3.5 h-3.5 transition-transform"
                        :class="{ 'rotate-180': expandedTorneoId === torneo.id_torneo }"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                      >
                        <path d="M6 9l6 6 6-6" />
                      </svg>
                    </button>
                    <div>
                      <div class="font-bold text-surface-900">{{ torneo.nombre_torneo }}</div>
                      <div class="text-[11px] text-surface-500 font-bold font-mono uppercase mt-0.5 tracking-tight">ID: {{ torneo.id_torneo }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3.5 hidden md:table-cell text-surface-600 font-medium">
                  {{ torneo.disciplina || '—' }}
                </td>
                <td class="px-4 py-3.5 hidden lg:table-cell text-surface-600 font-medium">
                  {{ torneo.categoria || '—' }}
                </td>
                <td class="px-4 py-3.5 hidden sm:table-cell">
                  <BadgeStatus v-if="torneo.tipo_acceso" :status="torneo.tipo_acceso" />
                </td>
                <td class="px-4 py-3.5">
                  <div class="flex flex-col items-start gap-1">
                    <BadgeStatus v-if="estatusTorneo(torneo)" :status="estatusTorneo(torneo)" />
                  </div>
                </td>
                <td class="px-4 py-3.5 text-right" @click.stop>
                  <ActionMenu :items="buildActions(torneo)" :disabled="loading" align="right" />
                </td>
              </tr>
              <tr
                v-if="isCancelado(torneo) && expandedTorneoId === torneo.id_torneo"
                class="bg-surface-50/80"
              >
                <td colspan="6" class="px-5 py-3 border-t border-surface-100">
                  <p class="text-xs text-surface-500 font-medium leading-relaxed">
                    {{ torneo.motivo_cancelacion || 'Sin motivo de cancelación registrado.' }}
                  </p>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

    </div>

    <!-- MODALES DE TORNEO -->
    <CreateTournamentModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="handleTorneoCreated"
    />

    <TournamentStatusModal
      v-if="showStatusModal"
      :torneo="statusModalTorneo"
      @close="showStatusModal = false"
      @updated="handleStatusUpdated"
    />

  </main>
</template>