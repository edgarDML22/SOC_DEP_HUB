<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAlerts } from '@/composables/useAlerts'

import Select from 'primevue/select'

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'

import { useTournamentStore } from '@/stores/tournamentStore'

const router = useRouter()
const { toastInfo } = useAlerts()
const store = useTournamentStore()

// ── DATA ───────────────────────────────────────────────────────
const torneos = computed(() => store.torneos)
const isLoading = computed(() => store.loading)
const error = computed(() => store.error)

const getTorneos = async () => {
  await store.fetchTorneos()
}

// ── FILTROS ────────────────────────────────────────────────────
const search = ref('')
const filterEstado = ref(null)

const OPT_ESTADO = [
  { label: 'Todos los estados', value: null },
  { label: 'En Planificación', value: 'EN_PLANIFICACION' },
  { label: 'Confirmado', value: 'PROGRAMADO' },
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
  if (filterEstado.value) r = r.filter(t => t.estado === filterEstado.value)
  return r
})

const hasActiveFilters = computed(() => search.value || filterEstado.value)
const clearFilters = () => { search.value = ''; filterEstado.value = null }

// ── MAPAS DE ESTATUS ───────────────────────────────────────────
// BadgeStatus espera claves en mayúsculas; EN_PLANIFICACION no está en el mapa
// → lo normalizamos antes de pasarlo
const normalizarEstado = (estado) => {
  if (estado === 'EN_PLANIFICACION') return 'PROGRAMADO'
  return estado
}

// Etiqueta legible
const labelEstado = (estado) => ({
  PROGRAMADO: 'Confirmado',
  EN_PLANIFICACION: 'En Planificación',
  EN_CURSO: 'En Curso',
  FINALIZADO: 'Finalizado',
  CANCELADO: 'Cancelado',
}[estado] ?? estado)

// Acento top de la card según estado
const estadoAccent = (estado) => ({
  PROGRAMADO: 'border-t-blue-400',
  EN_PLANIFICACION: 'border-t-amber-400',
  EN_CURSO: 'border-t-purple-500',
  FINALIZADO: 'border-t-slate-300',
  CANCELADO: 'border-t-red-400',
}[estado] ?? 'border-t-slate-200')

// Icono decorativo por estado
const estadoIconBg = (estado) => ({
  PROGRAMADO: 'bg-blue-50   text-blue-600',
  EN_PLANIFICACION: 'bg-amber-50  text-amber-600',
  EN_CURSO: 'bg-purple-50 text-purple-600',
  FINALIZADO: 'bg-slate-100 text-slate-500',
  CANCELADO: 'bg-red-50    text-red-600',
}[estado] ?? 'bg-slate-100 text-slate-400')

// Formato de fecha legible
const formatFecha = (f) => {
  if (!f) return '—'
  return new Date(f + 'T12:00:00').toLocaleDateString('es-MX', {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}

// ── ACCIONES MENU ──────────────────────────────────────────────
const buildMenuItems = (torneo) => [
  {
    label: 'Ver detalles',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
               <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
               <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
             </svg>`,
    action: () => router.push({
      path: '/tournaments/details',
      query: {
        id: torneo.id_torneo,
        nombre_torneo: torneo.nombre_torneo,
        fecha_inicio: torneo.fecha_inicio,
        categoria: torneo.categoria,
        disciplina: torneo.disciplina,
      }
    }),
  },
  {
    label: 'Confirmar torneo',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
               <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
               <polyline points="22 4 12 14.01 9 11.01"/>
             </svg>`,
    action: () => router.push({
      path: '/tournaments/details',
      query: {
        id: torneo.id_torneo,
        nombre_torneo: torneo.nombre_torneo,
        fecha_inicio: torneo.fecha_inicio,
        categoria: torneo.categoria,
        disciplina: torneo.disciplina,
      }
    }),
    disabled: torneo.estado !== 'EN_PLANIFICACION',
  },
  { separator: true },
  {
    label: 'Cancelar torneo',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                  </svg>`,
    action: () => openCancelModal(torneo),
    destructive: true,
    disabled: torneo.estado === 'CANCELADO' || torneo.estado === 'FINALIZADO',
  },
]

// ── MODAL: CANCELAR TORNEO ─────────────────────────────────────
const showCancelModal = ref(false)
const selectedTorneo = ref(null)
const isCancelling = ref(false)

const openCancelModal = (torneo) => {
  selectedTorneo.value = torneo
  showCancelModal.value = true
}

const confirmCancel = async () => {
  if (!selectedTorneo.value) return
  isCancelling.value = true
  try {
    await store.transicionarEstatus(selectedTorneo.value.id_torneo, 'CANCELADO')
    showCancelModal.value = false
    toastInfo('Torneo cancelado', selectedTorneo.value.nombre_torneo, 'success')
  } catch (e) {
    // El error ya lo maneja el store.error si queremos, pero aquí mostramos toast
    toastInfo('Error', store.error ?? 'No se pudo cancelar el torneo.', 'error')
  } finally {
    isCancelling.value = false
  }
}

// ── INIT ──────────────────────────────────────────────────────
onMounted(getTorneos)
</script>

<template>
  <main class="min-h-screen bg-slate-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader title="Torneos" subtitle="Administra los torneos y competencias del club.">
        <span class="text-sm font-bold text-slate-500">
          {{ filteredTorneos.length }}
          <span class="font-medium text-slate-400">de {{ torneos.length }}</span>
        </span>
        <!-- Botón Crear Categoría -->
        <button @click="router.push('/admin/categories/create')" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 shadow-sm
                 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7" />
            <rect x="14" y="3" width="7" height="7" />
            <rect x="14" y="14" width="7" height="7" />
            <rect x="3" y="14" width="7" height="7" />
          </svg>
          Crear Categoría
        </button>
        <!-- Botón Crear Torneo -->
        <button @click="router.push('/admin/tournaments/create')" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 text-white
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
        <div class="relative">
          <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input v-model="search" placeholder="Buscar por nombre, disciplina o categoría…" class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm
                   font-medium text-slate-900 placeholder:text-slate-400
                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all" />
        </div>
        <div class="flex flex-col sm:flex-row gap-3 items-end">
          <div class="flex flex-col gap-1.5 flex-1 sm:max-w-xs">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Estado</label>
            <Select v-model="filterEstado" :options="OPT_ESTADO" option-label="label" option-value="value"
              placeholder="Todos los estados" class="w-full text-sm" />
          </div>
          <Transition enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <button v-if="hasActiveFilters" @click="clearFilters"
              class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1.5 transition-colors pb-2.5">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 6L6 18M6 6l12 12" />
              </svg>
              Limpiar filtros
            </button>
          </Transition>
        </div>
      </div>

      <!-- ERROR -->
      <div v-if="error"
        class="bg-red-50 border border-red-200 rounded-3xl p-6 text-center text-red-700 text-sm font-semibold">
        {{ error }}
      </div>

      <!-- SKELETON -->
      <div v-else-if="isLoading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div v-for="n in 6" :key="n"
          class="bg-white rounded-3xl border border-t-4 border-slate-200 border-t-slate-200 p-6 animate-pulse space-y-4">
          <div class="flex items-start justify-between gap-3">
            <div class="w-12 h-12 rounded-2xl bg-slate-200 shrink-0" />
            <div class="flex-1 space-y-2 pt-1">
              <div class="h-4 bg-slate-200 rounded-lg w-3/4" />
              <div class="h-3 bg-slate-100 rounded-lg w-1/2" />
            </div>
          </div>
          <div class="space-y-2">
            <div class="h-7 bg-slate-100 rounded-xl w-full" />
            <div class="h-7 bg-slate-100 rounded-xl w-full" />
          </div>
        </div>
      </div>

      <!-- VACÍO -->
      <div v-else-if="filteredTorneos.length === 0" class="bg-white rounded-3xl border-2 border-dashed border-slate-200 p-16
               flex flex-col items-center justify-center text-center">
        <div class="w-20 h-20 rounded-3xl bg-slate-100 flex items-center justify-center mb-4">
          <svg class="w-9 h-9 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
            <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
            <path d="M4 22h16" />
            <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
            <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
            <path d="M18 2H6v7a6 6 0 0 0 12 0V2z" />
          </svg>
        </div>
        <h3 class="text-lg font-black text-slate-900">Sin torneos</h3>
        <p class="text-sm text-slate-500 mt-1 max-w-xs">
          {{ hasActiveFilters ? 'No hay torneos con los filtros actuales.' : 'Aún no hay torneos registrados.' }}
        </p>
        <button v-if="hasActiveFilters" @click="clearFilters"
          class="mt-4 text-sm font-bold text-blue-600 hover:underline">
          Limpiar filtros
        </button>
        <button v-else @click="router.push('/admin/tournaments/create')"
          class="mt-4 flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-purple-600 transition-colors">
          Crear primer torneo
        </button>
      </div>

      <!-- GRID DE CARDS -->
      <TransitionGroup v-else tag="div" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5"
        enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100" leave-active-class="transition-all duration-200 ease-in absolute"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-for="torneo in filteredTorneos" :key="torneo.id_torneo" class="bg-white rounded-3xl border border-t-4 border-slate-200 shadow-sm
                 hover:shadow-xl hover:shadow-slate-200/60 hover:border-slate-300
                 transition-all duration-300 group flex flex-col overflow-hidden" :class="estadoAccent(torneo.estado)">
          <!-- Header card -->
          <div class="p-5 flex items-start gap-4">
            <!-- Icono trofeo coloreado por estado -->
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0
                     group-hover:scale-105 transition-transform duration-300 shadow-sm"
              :class="estadoIconBg(torneo.estado)">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                <path d="M4 22h16" />
                <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
                <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
                <path d="M18 2H6v7a6 6 0 0 0 12 0V2z" />
              </svg>
            </div>

            <div class="flex-1 min-w-0 pt-0.5">
              <h3 class="text-sm font-black text-slate-900 truncate leading-tight">
                {{ torneo.nombre_torneo }}
              </h3>
              <p class="text-xs font-bold text-slate-400 mt-0.5 uppercase tracking-wider">
                {{ torneo.disciplina ?? '—' }}
              </p>
              <!-- Badge estado con soporte para EN_PLANIFICACION -->
              <div class="mt-2">
                <span
                  class="inline-flex items-center font-bold rounded-full border uppercase text-[10px] tracking-wider px-2.5 py-0.5 whitespace-nowrap shrink-0"
                  :class="{
                    'bg-blue-50   text-blue-700   border-blue-200': torneo.estado === 'PROGRAMADO',
                    'bg-amber-50  text-amber-700  border-amber-200': torneo.estado === 'EN_PLANIFICACION',
                    'bg-purple-50 text-purple-700 border-purple-200': torneo.estado === 'EN_CURSO',
                    'bg-slate-100 text-slate-500  border-slate-200': torneo.estado === 'FINALIZADO',
                    'bg-red-50    text-red-700    border-red-200': torneo.estado === 'CANCELADO',
                  }">
                  {{ labelEstado(torneo.estado) }}
                </span>
              </div>
            </div>

            <ActionMenu :items="buildMenuItems(torneo)" align="right" />
          </div>

          <!-- Cuerpo: metadata en filas -->
          <div class="px-5 pb-2 flex flex-col gap-1.5">
            <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Categoría</span>
              <span class="text-xs font-bold text-slate-700 truncate max-w-[140px]">{{ torneo.categoria ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Inicio</span>
              <span class="text-xs font-bold text-slate-700">{{ formatFecha(torneo.fecha_inicio) }}</span>
            </div>
            <div v-if="torneo.fecha_fin"
              class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Fin</span>
              <span class="text-xs font-bold text-slate-700">{{ formatFecha(torneo.fecha_fin) }}</span>
            </div>
            <div v-if="torneo.cupo_maximo"
              class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50 border border-slate-100">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Cupo</span>
              <span class="text-xs font-bold text-slate-700">{{ torneo.cupo_maximo }} participantes</span>
            </div>
          </div>

          <!-- Footer -->
          <div class="p-4 pt-3 mt-auto">
            <button @click="router.push({
              path: '/tournaments/details',
              query: {
                nombre_torneo: torneo.nombre_torneo,
                fecha_inicio: torneo.fecha_inicio,
                categoria: torneo.categoria,
                disciplina: torneo.disciplina,
              }
            })" class="w-full py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold
                     hover:bg-purple-600 transition-colors duration-200
                     flex items-center justify-center gap-2 shadow-sm">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <path d="M14 2v6h6" />
              </svg>
              Ver detalles
            </button>
          </div>
        </div>
      </TransitionGroup>

    </div>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: CONFIRMAR CANCELACIÓN
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showCancelModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="showCancelModal = false">
          <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-8 text-center">
            <div class="w-16 h-16 rounded-3xl bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-5">
              <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v4M12 17h.01" />
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-black text-slate-900 mb-2">¿Cancelar torneo?</h3>
            <p class="text-sm text-slate-500 mb-8">
              Esta acción marcará
              <span class="font-bold text-slate-800">{{ selectedTorneo?.nombre_torneo }}</span>
              como cancelado y no se podrá revertir fácilmente.
            </p>
            <div class="flex gap-3">
              <CancelButton label="Volver" @click="showCancelModal = false" class="flex-1" />
              <ConfirmButton label="Sí, cancelar" :loading="isCancelling" :disabled="isCancelling"
                @click="confirmCancel" class="flex-1 bg-red-600! hover:bg-red-700!" />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </main>
</template>
