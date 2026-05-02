<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useSocioStore } from '@/stores/admin/socioStore'
import { storeToRefs } from 'pinia'
import { useAlerts } from '@/composables/useAlerts'

import Select from 'primevue/select'

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus     from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu      from '@/components/gerente/ui/ActionMenu.vue'

const router     = useRouter()
const socioStore = useSocioStore()
const { toastInfo } = useAlerts()

const { socios, isLoading, error: errorMsg } = storeToRefs(socioStore)
const { fetchSocios, fetchSocioDetails, updateSocio } = socioStore

// ── FILTROS ────────────────────────────────────────────────────
const search          = ref('')
const filterTipo      = ref(null)
const filterModalidad = ref(null)
const filterGenero    = ref(null)
const filterEstatus   = ref(null)

const OPT_TIPO = [
  { label: 'Todos los tipos',   value: null },
  { label: 'Accionista',        value: 'ACCIONISTA' },
  { label: 'Rentista',          value: 'RENTISTA' },
]
const OPT_MODALIDAD = [
  { label: 'Todas las modalidades', value: null },
  { label: 'Individual',            value: 'INDIVIDUAL' },
  { label: 'Familiar',              value: 'FAMILIAR' },
]
const OPT_GENERO = [
  { label: 'Todos los géneros', value: null },
  { label: 'Masculino',         value: 'M' },
  { label: 'Femenino',          value: 'F' },
]
const OPT_ESTATUS = [
  { label: 'Todos los estatus',        value: null },
  { label: 'Al Corriente',             value: 'AL_CORRIENTE' },
  { label: 'Moroso',                   value: 'MOROSO' },
  { label: 'Suspendido',               value: 'SUSPENDIDO' },
  { label: 'Penalizado (General)',      value: 'PENALIZADO' },
  { label: 'Pen. Ambos',               value: 'PENALIZADO_AMBOS' },
  { label: 'Pen. Reservas',            value: 'PENALIZADO_RESERVA' },
  { label: 'Pen. Ludoteca',            value: 'PENALIZADO_LUDOTECA' },
]

const filteredSocios = computed(() => {
  let r = socios.value

  if (search.value) {
    const q = search.value.toLowerCase()
    r = r.filter(s =>
      s.nombre_completo?.toLowerCase().includes(q) ||
      String(s.numero_accion ?? '').includes(q)
    )
  }
  if (filterTipo.value)      r = r.filter(s => s.tipo_socio      === filterTipo.value)
  if (filterModalidad.value) r = r.filter(s => s.modalidad_plan  === filterModalidad.value)
  if (filterGenero.value)    r = r.filter(s => s.genero          === filterGenero.value)
  if (filterEstatus.value)   r = r.filter(s => s.estatus_cuenta  === filterEstatus.value)

  return r
})

const hasActiveFilters = computed(() =>
  search.value || filterTipo.value || filterModalidad.value || filterGenero.value || filterEstatus.value
)

const clearFilters = () => {
  search.value = filterTipo.value = filterModalidad.value = filterGenero.value = filterEstatus.value = null
  search.value = ''
}

// ── AVATAR ────────────────────────────────────────────────────
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

// ── ACCIONES DE TABLA ─────────────────────────────────────────
const buildMenuItems = (socio) => [
  {
    label: 'Ver perfil completo',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
             <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
           </svg>`,
    action: () => router.push({ path: `/admin/socios/${socio.id_socio}` }),
  },
  {
    label: 'Gestionar penalizaciones',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M12 9v4M12 17h.01"/>
             <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
           </svg>`,
    action: () => openPenalty(socio),
  },
  {
    label: 'Miembros familiares',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M17 21v-2a4 4 0 0 0-3-3.87M9 21v-2a4 4 0 0 0-3-3.87"/>
             <circle cx="9" cy="7" r="4"/><circle cx="17" cy="7" r="4"/>
           </svg>`,
    action: () => openFamily(socio),
  },
  {
    label: 'Pases de invitados',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
             <circle cx="9" cy="7" r="4"/>
             <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
           </svg>`,
    action: () => openGuests(socio),
  },
  { separator: true },
  {
    label: 'Suspender cuenta',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <circle cx="12" cy="12" r="10"/>
             <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
           </svg>`,
    action: () => applySpecificPenalty(socio, 'SUSPENDIDO'),
    destructive: true,
    disabled: socio.estatus_cuenta === 'SUSPENDIDO',
  },
]

// ── MODALES ───────────────────────────────────────────────────
const showPenaltyModal = ref(false)
const showFamilyModal  = ref(false)
const showGuestsModal  = ref(false)
const selectedSocio    = ref(null)
const isSaving         = ref(false)

const editForm = ref({
  estatus_cuenta:     'AL_CORRIENTE',
  contador_no_shows:  0,
  retrasos_ludoteca:  0,
})

const openPenalty = (socio) => {
  selectedSocio.value = socio
  editForm.value = {
    estatus_cuenta:    socio.estatus_cuenta    ?? 'AL_CORRIENTE',
    contador_no_shows: socio.contador_no_shows ?? 0,
    retrasos_ludoteca: socio.retrasos_ludoteca ?? 0,
  }
  showPenaltyModal.value = true
}

const openFamily = async (socio) => {
  selectedSocio.value = socio
  showFamilyModal.value = true
  await fetchSocioDetails(socio.id_socio)
  selectedSocio.value = socioStore.getSocioById(socio.id_socio) ?? socio
}

const openGuests = async (socio) => {
  selectedSocio.value = socio
  showGuestsModal.value = true
  await fetchSocioDetails(socio.id_socio)
  selectedSocio.value = socioStore.getSocioById(socio.id_socio) ?? socio
}

const savePenaltyUpdates = async () => {
  if (!selectedSocio.value) return
  isSaving.value = true
  try {
    const res = await updateSocio(selectedSocio.value.id_socio, editForm.value)
    if (res.success) {
      showPenaltyModal.value = false
      toastInfo('Actualizado', 'Penalizaciones guardadas correctamente.', 'success')
    } else {
      toastInfo('Error', res.error, 'error')
    }
  } finally {
    isSaving.value = false
  }
}

const applySpecificPenalty = async (socio, status) => {
  selectedSocio.value = socio
  editForm.value = {
    estatus_cuenta:    status,
    contador_no_shows: socio.contador_no_shows ?? 0,
    retrasos_ludoteca: socio.retrasos_ludoteca ?? 0,
  }
  await savePenaltyUpdates()
}

const PENALTY_ACTIONS = [
  {
    label:    'Penalizar por Reservas',
    sublabel: 'Bloquea reservaciones 7 días',
    status:   'PENALIZADO_RESERVA',
    classes:  'border-red-200 text-red-700 hover:bg-red-50',
  },
  {
    label:    'Penalizar por Ludoteca',
    sublabel: 'Bloquea ludoteca 7 días',
    status:   'PENALIZADO_LUDOTECA',
    classes:  'border-amber-200 text-amber-700 hover:bg-amber-50',
  },
  {
    label:    'Quitar todas las penalizaciones',
    sublabel: 'Restablecer estatus "Al Corriente"',
    status:   'AL_CORRIENTE',
    classes:  'border-emerald-200 text-emerald-700 hover:bg-emerald-50',
  },
]

const OPT_ESTATUS_MODAL = [
  { label: 'Al Corriente (Sin Bloqueos)',         value: 'AL_CORRIENTE' },
  { label: 'Penalizado — Ambos',                  value: 'PENALIZADO_AMBOS' },
  { label: 'Penalizado — Reservas (No Show)',      value: 'PENALIZADO_RESERVA' },
  { label: 'Penalizado — Ludoteca (Retrasos)',     value: 'PENALIZADO_LUDOTECA' },
  { label: 'Penalizado (General)',                 value: 'PENALIZADO' },
  { label: 'Moroso (Deuda Pendiente)',             value: 'MOROSO' },
  { label: 'Suspendido (Bloqueo Permanente)',      value: 'SUSPENDIDO' },
]

// ── INIT ──────────────────────────────────────────────────────
onMounted(fetchSocios)
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader
        title="Socios Titulares"
        subtitle="Gestión de membresías, penalizaciones e información de cuenta."
      >
        <span class="text-sm font-bold text-surface-500">
          {{ filteredSocios.length }}
          <span class="font-medium text-surface-400">de {{ socios.length }} socios</span>
        </span>
      </AdminPageHeader>

      <!-- BARRA DE FILTROS -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5 space-y-4">
        <div class="relative">
          <svg class="absolute left-4 top-1/2 -transurface-y-1/2 w-4 h-4 text-surface-400 pointer-events-none"
               viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <input
            v-model="search"
            placeholder="Buscar por nombre o número de acción…"
            class="w-full pl-11 pr-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-sm
                   font-medium text-surface-900 placeholder:text-surface-400
                   focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all"
          />
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Tipo</label>
            <div class="relative">
              <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
              <select v-model="filterTipo" class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_TIPO" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Modalidad</label>
            <div class="relative">
              <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
              <select v-model="filterModalidad" class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_MODALIDAD" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Género</label>
            <div class="relative">
              <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
              <select v-model="filterGenero" class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_GENERO" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estatus</label>
            <div class="relative">
              <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
              <select v-model="filterEstatus" class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_ESTATUS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </div>
          </div>
        </div>
        <Transition
          enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -transurface-y-1"
          enter-to-class="opacity-100 transurface-y-0" leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 transurface-y-0" leave-to-class="opacity-0 -transurface-y-1"
        >
          <div v-if="hasActiveFilters" class="flex justify-end">
            <button @click="clearFilters"
              class="text-xs font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1.5 transition-colors">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 6L6 18M6 6l12 12"/>
              </svg>
              Limpiar filtros
            </button>
          </div>
        </Transition>
      </div>

      <!-- TABLA -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">

        <!-- Estado: cargando -->
        <div v-if="isLoading" class="p-8 space-y-3">
          <div v-for="n in 6" :key="n" class="flex items-center gap-4 animate-pulse py-3 border-b border-surface-100">
            <div class="w-10 h-10 rounded-xl bg-surface-200 shrink-0"/>
            <div class="flex-1 space-y-2">
              <div class="h-3.5 bg-surface-200 rounded-lg w-48"/>
              <div class="h-3 bg-surface-100 rounded-lg w-28"/>
            </div>
            <div class="h-5 w-20 bg-surface-100 rounded-full"/>
            <div class="h-3 w-16 bg-surface-100 rounded-lg hidden sm:block"/>
            <div class="h-3 w-16 bg-surface-100 rounded-lg hidden lg:block"/>
            <div class="h-3 w-16 bg-surface-100 rounded-lg hidden lg:block"/>
          </div>
        </div>

        <!-- Estado: error -->
        <div v-else-if="errorMsg" class="p-8 text-center text-red-700 font-semibold text-sm">
          {{ errorMsg }}
        </div>

        <!-- Estado: vacío -->
        <div v-else-if="filteredSocios.length === 0"
          class="p-16 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 rounded-2xl bg-surface-100 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <h3 class="text-base font-black text-surface-900">Sin resultados</h3>
          <p class="text-sm text-surface-500 mt-1 max-w-xs">No se encontraron socios con los filtros aplicados.</p>
          <button @click="clearFilters" class="mt-4 text-sm font-bold text-primary-600 hover:underline">
            Limpiar filtros
          </button>
        </div>

        <!-- Tabla con datos -->
        <table v-else class="w-full text-sm">
          <thead>
            <tr class="bg-surface-50 border-b border-surface-200">
              <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-widest text-surface-500">Socio</th>
              <th class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-widest text-surface-500 hidden sm:table-cell">Acción</th>
              <th class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-widest text-surface-500 hidden md:table-cell">Tipo</th>
              <th class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-widest text-surface-500 hidden lg:table-cell">Modalidad</th>
              <th class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-widest text-surface-500 hidden lg:table-cell">Género</th>
              <th class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-widest text-surface-500">Estatus</th>
              <th class="px-4 py-3.5 text-right text-xs font-extrabold uppercase tracking-widest text-surface-500">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-100">
            <tr
              v-for="socio in filteredSocios"
              :key="socio.id_socio"
              class="hover:bg-surface-50/70 transition-colors group cursor-pointer"
              @click="router.push({ path: `/admin/socios/${socio.id_socio}` })"
            >
              <!-- Nombre + avatar -->
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <div
                    class="w-9 h-9 rounded-xl bg-linear-to-br flex items-center justify-center
                           text-white font-black text-xs shrink-0 shadow-sm"
                    :class="avatarGradient(socio.nombre_completo)"
                  >
                    {{ initials(socio.nombre_completo) }}
                  </div>
                  <span class="font-semibold text-surface-900 truncate max-w-[180px]">
                    {{ socio.nombre_completo }}
                  </span>
                </div>
              </td>
              <!-- Acción -->
              <td class="px-4 py-3.5 hidden sm:table-cell">
                <span class="text-xs font-bold text-surface-500 font-mono">#{{ socio.numero_accion }}</span>
              </td>
              <!-- Tipo -->
              <td class="px-4 py-3.5 hidden md:table-cell">
                <span class="text-xs font-semibold text-surface-700">{{ socio.tipo_socio }}</span>
              </td>
              <!-- Modalidad -->
              <td class="px-4 py-3.5 hidden lg:table-cell">
                <span class="text-xs font-semibold text-surface-700">{{ socio.modalidad_plan }}</span>
              </td>
              <!-- Género -->
              <td class="px-4 py-3.5 hidden lg:table-cell">
                <span class="text-xs font-semibold text-surface-700">
                  {{ socio.genero === 'M' ? 'Masculino' : 'Femenino' }}
                </span>
              </td>
              <!-- Estatus -->
              <td class="px-4 py-3.5" @click.stop>
                <BadgeStatus :status="socio.estatus_cuenta" />
              </td>
              <!-- Menú acciones -->
              <td class="px-4 py-3.5 text-right" @click.stop>
                <ActionMenu :items="buildMenuItems(socio)" align="right" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div><!-- /max-w -->

    <!-- ══════════════════════════════════════════════════════════
         MODAL: PENALIZACIONES
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div v-if="showPenaltyModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showPenaltyModal = false"
        >
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
          >
            <div v-if="showPenaltyModal"
              class="bg-white w-full max-w-xl rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[92vh]"
            >
              <!-- Cabecera -->
              <div class="flex items-center justify-between px-8 py-6 bg-white border-b border-surface-100">
                <div>
                  <h2 class="text-xl font-black text-surface-900 leading-tight">Penalizaciones y Detalles</h2>
                  <p class="text-xs font-bold text-surface-500 mt-1 uppercase tracking-wider truncate max-w-[300px]">
                    {{ selectedSocio?.nombre_completo }}
                  </p>
                </div>
                <button @click="showPenaltyModal = false"
                  class="w-10 h-10 rounded-xl bg-surface-100 hover:bg-surface-200
                         flex items-center justify-center text-surface-500 transition-colors">
                  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6L6 18M6 6l12 12"/>
                  </svg>
                </button>
              </div>

              <!-- Cuerpo -->
              <div class="overflow-y-auto p-8 space-y-8 bg-surface-50/50">

                <!-- KPIs -->
                <div class="grid grid-cols-2 gap-6">
                  <div class="bg-white rounded-[1.5rem] p-6 border border-surface-200 text-center shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-red-500 opacity-0 transition-opacity" :class="editForm.contador_no_shows > 0 ? 'opacity-100' : ''"></div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-surface-500 mb-2">No Shows</p>
                    <p class="text-5xl font-black leading-none"
                       :class="editForm.contador_no_shows > 0 ? 'text-red-600' : 'text-surface-900'">
                      {{ editForm.contador_no_shows }}
                    </p>
                  </div>
                  <div class="bg-white rounded-[1.5rem] p-6 border border-surface-200 text-center shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-amber-500 opacity-0 transition-opacity" :class="editForm.retrasos_ludoteca > 0 ? 'opacity-100' : ''"></div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-surface-500 mb-2">Retrasos Ludoteca</p>
                    <p class="text-5xl font-black leading-none"
                       :class="editForm.retrasos_ludoteca > 0 ? 'text-amber-600' : 'text-surface-900'">
                      {{ editForm.retrasos_ludoteca }}
                    </p>
                  </div>
                </div>

                <!-- Selector estatus -->
                <div class="space-y-2">
                  <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">
                    Estatus de Penalización Actual
                  </label>
                  <Select
                    v-model="editForm.estatus_cuenta"
                    :options="OPT_ESTATUS_MODAL"
                    option-label="label"
                    option-value="value"
                    class="w-full shadow-sm"
                  />
                </div>

                <!-- Acciones rápidas -->
                <div class="space-y-3">
                  <p class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">Acciones Rápidas</p>
                  <div class="flex flex-col gap-3">
                    <button
                      v-for="pa in PENALTY_ACTIONS"
                      :key="pa.status"
                      @click="editForm.estatus_cuenta = pa.status"
                      :disabled="isSaving"
                      class="flex flex-col items-start px-5 py-4 rounded-[1.25rem] border-2 text-left
                             transition-all disabled:opacity-40 shadow-sm hover:-translate-y-0.5"
                      :class="[pa.classes, editForm.estatus_cuenta === pa.status ? 'ring-4 ring-offset-0 ring-current opacity-100 bg-white' : 'bg-surface-50 opacity-80 hover:bg-white']"
                    >
                      <p class="text-sm font-bold m-0" :class="editForm.estatus_cuenta === pa.status ? 'text-current' : 'text-surface-700'">{{ pa.label }}</p>
                      <p class="text-xs font-semibold opacity-70 mt-1">{{ pa.sublabel }}</p>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Pie del modal -->
              <div class="flex items-center justify-end gap-3 px-8 py-5 border-t border-surface-100 bg-white">
                <button @click="showPenaltyModal = false"
                  class="px-6 py-3 rounded-xl border border-surface-200 bg-white
                         text-sm font-bold text-surface-700 hover:bg-surface-50 transition-colors">
                  Cancelar
                </button>
                <button @click="savePenaltyUpdates" :disabled="isSaving"
                  class="px-6 py-3 rounded-xl bg-primary-600 text-white text-sm font-bold
                         hover:bg-primary-700 transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2">
                  <svg v-if="isSaving" class="w-4 h-4 animate-spin"
                       viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                  </svg>
                  {{ isSaving ? 'Guardando…' : 'Guardar cambios' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: MIEMBROS FAMILIARES
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div v-if="showFamilyModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showFamilyModal = false"
        >
          <div class="bg-white w-full max-w-md rounded-[2rem] shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">

            <!-- Cabecera -->
            <div class="flex items-center justify-between px-7 py-5 border-b border-surface-100">
              <div>
                <h2 class="text-lg font-black text-surface-900 leading-tight">Miembros Familiares</h2>
                <p class="text-xs text-surface-500 font-medium mt-0.5">{{ selectedSocio?.nombre_completo }}</p>
              </div>
              <button @click="showFamilyModal = false"
                class="w-9 h-9 rounded-xl bg-surface-100 hover:bg-surface-200
                       flex items-center justify-center text-surface-500 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Cuerpo -->
            <div class="overflow-y-auto p-6 bg-surface-50/30">

              <!-- Cargando -->
              <div v-if="isLoading" class="flex justify-center py-12">
                <div class="w-8 h-8 rounded-full border-2 border-surface-200 border-t-primary-600 animate-spin" />
              </div>

              <!-- Vacío -->
              <div v-else-if="!selectedSocio?.miembros_familiares?.length"
                class="flex flex-col items-center justify-center py-14 text-center
                       bg-white rounded-2xl border-2 border-dashed border-surface-200">
                <div class="w-14 h-14 rounded-2xl bg-surface-100 flex items-center justify-center mb-3">
                  <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M17 21v-2a4 4 0 0 0-3-3.87M9 21v-2a4 4 0 0 0-3-3.87"/>
                    <circle cx="9" cy="7" r="4"/><circle cx="17" cy="7" r="4"/>
                  </svg>
                </div>
                <p class="text-sm font-bold text-surface-700">Sin miembros familiares</p>
                <p class="text-xs text-surface-400 mt-1 max-w-[200px]">
                  No hay miembros familiares registrados para este socio.
                </p>
              </div>

              <!-- Lista -->
              <div v-else class="flex flex-col gap-2.5">
                <div
                  v-for="fam in selectedSocio.miembros_familiares"
                  :key="fam.id_miembro"
                  class="flex items-center gap-4 p-4 rounded-2xl bg-white border border-surface-200
                         hover:border-primary-200 hover:shadow-sm transition-all"
                >
                  <div class="w-10 h-10 rounded-xl bg-linear-to-br from-purple-400 to-purple-600
                              text-white flex items-center justify-center font-bold text-sm shrink-0 bg-linear-to-br">
                    {{ fam.nombre_completo?.charAt(0) ?? '?' }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-surface-900 truncate">{{ fam.nombre_completo }}</p>
                    <p class="text-xs text-surface-500 font-medium">{{ fam.parentesco }} · {{ fam.genero }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pie -->
            <div class="px-7 py-4 border-t border-surface-100 flex justify-end">
              <button @click="showFamilyModal = false"
                class="px-5 py-2.5 rounded-xl border border-surface-200 bg-white
                       text-sm font-semibold text-surface-700 hover:bg-surface-50 transition-colors">
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: INVITADOS
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div v-if="showGuestsModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showGuestsModal = false"
        >
          <div class="bg-white w-full max-w-lg rounded-[2rem] shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">

            <!-- Cabecera -->
            <div class="flex items-center justify-between px-7 py-5 border-b border-surface-100">
              <div>
                <h2 class="text-lg font-black text-surface-900 leading-tight">Pases de Invitados</h2>
                <p class="text-xs text-surface-500 font-medium mt-0.5">{{ selectedSocio?.nombre_completo }}</p>
              </div>
              <button @click="showGuestsModal = false"
                class="w-9 h-9 rounded-xl bg-surface-100 hover:bg-surface-200
                       flex items-center justify-center text-surface-500 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Cuerpo -->
            <div class="overflow-y-auto p-6 bg-surface-50/30">

              <!-- Cargando -->
              <div v-if="isLoading" class="flex justify-center py-12">
                <div class="w-8 h-8 rounded-full border-2 border-surface-200 border-t-primary-600 animate-spin" />
              </div>

              <!-- Vacío — mismo esqueleto que Miembros Familiares -->
              <div v-else-if="!selectedSocio?.invitados?.length"
                class="flex flex-col items-center justify-center py-14 text-center
                       bg-white rounded-2xl border-2 border-dashed border-surface-200">
                <div class="w-14 h-14 rounded-2xl bg-primary-50 flex items-center justify-center mb-3">
                  <svg class="w-7 h-7 text-primary-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                  </svg>
                </div>
                <p class="text-sm font-bold text-surface-700">Sin pases registrados</p>
                <p class="text-xs text-surface-400 mt-1 max-w-[200px]">
                  Este socio no tiene pases de invitados activos ni históricos.
                </p>
              </div>

              <!-- Lista -->
              <div v-else class="flex flex-col gap-2.5">
                <div
                  v-for="guest in selectedSocio.invitados"
                  :key="guest.id_invitado"
                  class="flex items-center justify-between p-4 rounded-2xl bg-white border border-surface-200
                         hover:border-primary-200 hover:shadow-sm transition-all"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-linear-to-br from-amber-400 to-amber-600
                                text-white flex items-center justify-center font-bold text-sm shrink-0">
                      {{ guest.nombre_invitado?.charAt(0) ?? '?' }}
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-bold text-surface-900 truncate">{{ guest.nombre_invitado }}</p>
                      <p class="text-xs text-surface-500">{{ guest.correo ?? 'Sin correo' }}</p>
                    </div>
                  </div>
                  <div class="flex flex-col items-end gap-1 shrink-0 ml-3">
                    <BadgeStatus
                      :status="guest.pase?.estatus_acceso === 'ACTIVO' ? 'ACTIVO' :
                               guest.pase?.estatus_acceso === 'USADO'  ? 'USADO'  : 'EXPIRADO'"
                    />
                    <p v-if="guest.pase?.fecha_expiracion"
                       class="text-[10px] font-medium text-surface-400">
                      {{ guest.pase.fecha_expiracion }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pie -->
            <div class="px-7 py-4 border-t border-surface-100 flex justify-end">
              <button @click="showGuestsModal = false"
                class="px-5 py-2.5 rounded-xl border border-surface-200 bg-white
                       text-sm font-semibold text-surface-700 hover:bg-surface-50 transition-colors">
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </main>
</template>
