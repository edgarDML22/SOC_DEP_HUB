<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useTournamentStore } from '@/stores/tournamentStore'
import { useAlerts } from '@/composables/useAlerts'

import Select from 'primevue/select'
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import SearchInput from '@/components/gerente/ui/SearchInput.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'

const router = useRouter()
const store = useTournamentStore()
const { toastSuccess, toastError, toastInfo } = useAlerts()

// ── FILTROS ────────────────────────────────────────────────────
const searchQuery = ref('')
const filterStatus = ref(null)
const filterDiscipline = ref(null)
const filterAccess = ref(null)

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
  const unique = [...new Set(store.torneos.map(t => t.disciplina).filter(Boolean))]
  return [{ label: 'Todas las disciplinas', value: null }, ...unique.map(d => ({ label: d, value: d }))]
})

// Debounce para búsqueda
let debounceTimer = null
watch(searchQuery, (val) => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    store.fetchTorneos({ search: val })
  }, 300)
})

// Watchers para otros filtros
watch([filterStatus, filterDiscipline, filterAccess], () => {
  store.fetchTorneos({
    estatus: filterStatus.value,
    disciplina: filterDiscipline.value,
    tipo_acceso: filterAccess.value
  })
})

const hasActiveFilters = computed(() => searchQuery.value || filterStatus.value || filterDiscipline.value || filterAccess.value)
const clearFilters = () => {
  searchQuery.value = ''
  filterStatus.value = null
  filterDiscipline.value = null
  filterAccess.value = null
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

  // Confirmación para acciones irreversibles
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

  // Acción básica siempre presente
  actions.push({
    label: 'Ver detalle',
    icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`,
    action: () => router.push({ path: '/admin/tournaments/details', query: { id: torneo.id_torneo } })
  })

  // Transiciones válidas
  if (status === 'EN_PLANIFICACION') {
    actions.push({
      label: 'Abrir Inscripción',
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>`,
      action: () => handleTransition(torneo, 'EN_INSCRIPCION', 'Abrir Inscripción')
    })
  } else if (status === 'EN_INSCRIPCION') {
    actions.push({
      label: 'Confirmar y Generar Bracket',
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
      action: () => handleTransition(torneo, 'PROGRAMADO', 'Confirmar y Generar Bracket')
    })
    actions.push({
      label: 'Cancelar',
      destructive: true,
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
      action: () => handleTransition(torneo, 'CANCELADO', 'Cancelar')
    })
  } else if (status === 'PROGRAMADO') {
    actions.push({
      label: 'Iniciar Torneo',
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
      action: () => handleTransition(torneo, 'EN_CURSO', 'Iniciar Torneo')
    })
    actions.push({
      label: 'Cancelar',
      destructive: true,
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
      action: () => handleTransition(torneo, 'CANCELADO', 'Cancelar')
    })
  } else if (status === 'EN_CURSO') {
    actions.push({
      label: 'Finalizar',
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`,
      action: () => handleTransition(torneo, 'FINALIZADO', 'Finalizar')
    })
    actions.push({
      label: 'Cancelar',
      destructive: true,
      icon: `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
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
  <main class="min-h-screen bg-slate-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader title="Gestión de Torneos" subtitle="Monitorea y administra el ciclo de vida de los torneos.">
        <button @click="router.push('/admin/tournaments/create')" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white
                 text-sm font-bold hover:bg-purple-600 transition-colors shadow-sm">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="16" />
            <line x1="8" y1="12" x2="16" y2="12" />
          </svg>
          Crear Torneo
        </button>
      </AdminPageHeader>

      <!-- FILTROS -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="flex flex-col lg:flex-row gap-4">
          <div class="flex-1">
            <SearchInput v-model="searchQuery" placeholder="Buscar por nombre..." />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-[2]">
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Estatus</label>
              <Select v-model="filterStatus" :options="STATUS_OPTS" option-label="label" option-value="value"
                placeholder="Todos" class="w-full text-sm" />
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Disciplina</label>
              <Select v-model="filterDiscipline" :options="disciplineOpts" option-label="label" option-value="value"
                placeholder="Todas" class="w-full text-sm" />
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Acceso</label>
              <Select v-model="filterAccess" :options="ACCESS_OPTS" option-label="label" option-value="value"
                placeholder="Todos" class="w-full text-sm" />
            </div>
          </div>
        </div>

        <div v-if="hasActiveFilters" class="flex justify-end">
          <button @click="clearFilters"
            class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1.5 transition-colors">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M18 6L6 18M6 6l12 12" />
            </svg>
            Limpiar filtros
          </button>
        </div>
      </div>

      <!-- TABLA -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden overflow-x-auto relative">
        <div v-if="store.loading && store.torneos.length === 0" class="p-12 flex justify-center">
          <LoadingSpinner />
        </div>

        <table v-else class="w-full text-left border-collapse min-w-[800px]">
          <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Torneo</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Disciplina</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Categoría</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Tipo</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Cupo</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Estado</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="torneo in store.torneos" :key="torneo.id_torneo" class="hover:bg-slate-50/50 transition-colors group">
              <td class="px-6 py-4">
                <div class="font-bold text-slate-900">{{ torneo.nombre_torneo }}</div>
                <div class="text-[10px] text-slate-400 font-medium">ID: {{ torneo.id_torneo }}</div>
              </td>
              <td class="px-6 py-4 text-sm font-medium text-slate-600">{{ torneo.disciplina || '—' }}</td>
              <td class="px-6 py-4 text-sm font-medium text-slate-600">{{ torneo.categoria || '—' }}</td>
              <td class="px-6 py-4">
                <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 uppercase border border-slate-200">
                  {{ torneo.tipo_acceso }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm font-bold text-slate-700">{{ torneo.cupo_maximo || '—' }}</td>
              <td class="px-6 py-4 text-center">
                <BadgeStatus :status="torneo.estado || torneo.estatus_torneo" size="md" />
              </td>
              <td class="px-6 py-4 text-right">
                <ActionMenu :items="buildActions(torneo)" :disabled="store.loading || isActionLoading" align="right" />
              </td>
            </tr>
            <tr v-if="store.torneos.length === 0">
              <td colspan="7" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center">
                  <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-3">
                    <svg class="w-8 h-8 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                  </div>
                  <div class="font-bold text-slate-400">No se encontraron torneos</div>
                  <button v-if="hasActiveFilters" @click="clearFilters" class="text-xs text-blue-600 font-bold mt-2">Limpiar filtros</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Overlay de carga -->
        <div v-if="store.loading && store.torneos.length > 0" class="absolute inset-0 bg-white/40 backdrop-blur-[1px] flex items-center justify-center z-10">
          <LoadingSpinner />
        </div>
      </div>

    </div>

    <!-- MODAL CANCELACIÓN -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showCancelModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="showCancelModal = false">
          <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-8 space-y-6">
            <div class="text-center space-y-2">
              <div class="w-16 h-16 rounded-3xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              </div>
              <h3 class="text-xl font-black text-slate-900">¿Cancelar Torneo?</h3>
              <p class="text-sm text-slate-500">Esta acción es irreversible. Debes proporcionar un motivo detallado.</p>
            </div>

            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Motivo de Cancelación (Mín. 20 caracteres)</label>
              <textarea v-model="motivoCancelacion" rows="4"
                class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all resize-none"
                placeholder="Ej: El torneo no alcanzó el cupo mínimo de participantes requeridos para la competencia..."></textarea>
              <div class="flex justify-end">
                <span class="text-[10px] font-bold" :class="motivoCancelacion.length < 20 ? 'text-red-400' : 'text-emerald-500'">
                  {{ motivoCancelacion.length }} / 20 caracteres
                </span>
              </div>
            </div>

            <div class="flex gap-3 pt-2">
              <CancelButton label="Cerrar" @click="showCancelModal = false" class="flex-1" />
              <ConfirmButton label="Confirmar Cancelación" :loading="isActionLoading" :disabled="motivoCancelacion.length < 20"
                @click="confirmCancel" class="flex-1 bg-red-600! hover:bg-red-700!" />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </main>
</template>