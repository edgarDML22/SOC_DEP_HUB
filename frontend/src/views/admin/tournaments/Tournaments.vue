<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useTournamentStore } from '@/stores/tournamentStore'
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

const router = useRouter()
const route = useRoute()
const store = useTournamentStore()
const { torneos, loading, error: errorMsg, filtros } = storeToRefs(store)
const { toastSuccess, toastError, toastInfo } = useAlerts()

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
const searchQuery = ref('')
watch(searchQuery, (val) => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    store.fetchTorneos({ search: val })
  }, 300)
})

// Watchers para otros filtros
watch([() => filtros.value.estatus, () => filtros.value.disciplina, () => filtros.value.tipo_acceso], () => {
  store.fetchTorneos()
})

const hasActiveFilters = computed(() => searchQuery.value || filtros.value.estatus || filtros.value.disciplina || filtros.value.tipo_acceso)
const clearFilters = () => {
  searchQuery.value = ''
  filtros.value.estatus = null
  filtros.value.disciplina = null
  filtros.value.tipo_acceso = null
}

// ── ACCIONES ───────────────────────────────────────────────────
const showCancelModal = ref(false)
const selectedTorneo = ref(null)
const motivoCancelacion = ref('')
const isActionLoading = ref(false)

const openCancelModal = (torneo) => {
  selectedTorneo.value = torneo
  motivoCancelacion.value = ''
  showCancelModal.value = true
}

const handleTransition = async (torneo, nuevoEstatus, label) => {
  if (nuevoEstatus === 'CANCELADO') {
    openCancelModal(torneo)
    return
  }

  const confirmMessage = `¿Estás seguro de que deseas ${label.toLowerCase()} el torneo "${torneo.nombre_torneo}"?`
  if (!confirm(confirmMessage)) return

  isActionLoading.value = true
  try {
    await store.transicionarEstatus(torneo.id_torneo, nuevoEstatus)
    toastSuccess(`Torneo actualizado: ${label}`)
  } catch (err) {
    toastError(store.error || 'No se pudo actualizar el estado del torneo.')
  } finally {
    isActionLoading.value = false
  }
}

const confirmCancel = async () => {
  if (motivoCancelacion.value.length < 20) return

  isActionLoading.value = true
  try {
    await store.transicionarEstatus(selectedTorneo.value.id_torneo, 'CANCELADO', motivoCancelacion.value)
    showCancelModal.value = false
    toastInfo('Torneo cancelado', selectedTorneo.value.nombre_torneo, 'success')
  } catch (err) {
    toastError(store.error || 'No se pudo cancelar el torneo.')
  } finally {
    isActionLoading.value = false
  }
}

const buildActions = (torneo) => {
  const actions = []
  const status = torneo.estado || torneo.estatus_torneo

  // Acción básica siempre presente (SocioList pattern)
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

  // Transiciones válidas
  if (status === 'EN_PLANIFICACION') {
    actions.push({
      label: 'Abrir Inscripción',
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>`,
      action: () => handleTransition(torneo, 'EN_INSCRIPCION', 'Abrir Inscripción')
    })
  } else if (status === 'EN_INSCRIPCION') {
    actions.push({
      label: 'Confirmar y Generar Bracket',
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
      action: () => handleTransition(torneo, 'PROGRAMADO', 'Confirmar y Generar Bracket')
    })
    actions.push({
      label: 'Cancelar',
      destructive: true,
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
      action: () => handleTransition(torneo, 'CANCELADO', 'Cancelar')
    })
  } else if (status === 'PROGRAMADO') {
    actions.push({
      label: 'Iniciar Torneo',
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
      action: () => handleTransition(torneo, 'EN_CURSO', 'Iniciar Torneo')
    })
    actions.push({
      label: 'Cancelar',
      destructive: true,
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
      action: () => handleTransition(torneo, 'CANCELADO', 'Cancelar')
    })
  } else if (status === 'EN_CURSO') {
    actions.push({
      label: 'Finalizar',
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`,
      action: () => handleTransition(torneo, 'FINALIZADO', 'Finalizar')
    })
    actions.push({
      label: 'Cancelar',
      destructive: true,
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
      action: () => handleTransition(torneo, 'CANCELADO', 'Cancelar')
    })
  }

  return actions
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
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
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
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
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
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
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
          <thead>
            <tr class="bg-surface-50 border-b border-surface-200">
              <th class="px-5 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700 rounded-tl-2xl">Torneo</th>
              <th class="px-4 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700 hidden md:table-cell">Disciplina</th>
              <th class="px-4 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700 hidden lg:table-cell">Categoría</th>
              <th class="px-4 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700 hidden sm:table-cell">Acceso</th>
              <th class="px-4 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700">Estado</th>
              <th class="px-4 py-3.5 text-right text-xs font-black uppercase tracking-widest text-surface-700 rounded-tr-2xl">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-100">
            <tr v-for="torneo in torneos" :key="torneo.id_torneo" class="hover:bg-surface-50/70 transition-colors group">
              <td class="px-5 py-3.5">
                <div class="font-bold text-surface-900">{{ torneo.nombre_torneo }}</div>
                <div class="text-[11px] text-surface-500 font-bold font-mono uppercase mt-0.5 tracking-tight">ID: {{ torneo.id_torneo }}</div>
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
                <BadgeStatus v-if="torneo.estado || torneo.estatus_torneo" :status="torneo.estado || torneo.estatus_torneo" />
              </td>
              <td class="px-4 py-3.5 text-right">
                <ActionMenu :items="buildActions(torneo)" :disabled="loading || isActionLoading" align="right" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- MODAL CANCELACIÓN -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showCancelModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showCancelModal = false">
          <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-8 space-y-6">
            <div class="text-center space-y-2">
              <div class="w-16 h-16 rounded-3xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <h3 class="text-xl font-black text-surface-900">¿Cancelar Torneo?</h3>
              <p class="text-sm text-surface-500">Esta acción es irreversible. Debes proporcionar un motivo detallado.</p>
            </div>

            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Motivo de Cancelación
                (Mín. 20 caracteres)</label>
              <textarea v-model="motivoCancelacion" rows="4"
                class="w-full p-4 bg-surface-50 border border-surface-200 rounded-2xl text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all resize-none font-semibold text-surface-700"
                placeholder="Ej: El torneo no alcanzó el cupo mínimo de participantes requeridos para la competencia..."></textarea>
              <div class="flex justify-end">
                <span class="text-[10px] font-bold"
                  :class="motivoCancelacion.length < 20 ? 'text-red-400' : 'text-emerald-500'">
                  {{ motivoCancelacion.length }} / 20 caracteres
                </span>
              </div>
            </div>

            <div class="flex gap-3 pt-2">
              <CancelButton label="Cerrar" @click="showCancelModal = false" class="flex-1" />
              <ConfirmButton label="Confirmar Cancelación" :loading="isActionLoading"
                :disabled="motivoCancelacion.length < 20" @click="confirmCancel"
                class="flex-1 bg-red-600! hover:bg-red-700!" />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </main>
</template>