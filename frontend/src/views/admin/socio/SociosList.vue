<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useSocioStore } from '@/stores/admin/socioStore'
import { storeToRefs } from 'pinia'
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import SearchInput from '@/components/gerente/ui/SearchInput.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import ExportCsvButton from '@/components/gerente/ui/ExportCsvButton.vue'
import PenalizacionModal from '@/components/admin/socio/PenalizacionModal.vue'
import { IconFilter, IconChevronDown, IconAlertCircle, IconWarning } from '@/components/icons'
import EstatusCuentaModal from '@/components/admin/socio/EstatusCuentaModal.vue'

const router = useRouter()
const socioStore = useSocioStore()

const { socios, isLoading, error: errorMsg } = storeToRefs(socioStore)
const { fetchSocios, fetchSocioDetails } = socioStore

// ── FILTROS ────────────────────────────────────────────────────
const search = ref('')
const filterTipo = ref(null)
const filterModalidad = ref(null)
const filterGenero = ref(null)
const filterEstatus = ref(null)
const filterPenalizacion = ref(null)

const OPT_TIPO = [
  { label: 'Todos los tipos', value: null },
  { label: 'Accionista', value: 'ACCIONISTA' },
  { label: 'Rentista', value: 'RENTISTA' },
]
const OPT_MODALIDAD = [
  { label: 'Todas las modalidades', value: null },
  { label: 'Individual', value: 'INDIVIDUAL' },
  { label: 'Familiar', value: 'FAMILIAR' },
]
const OPT_GENERO = [
  { label: 'Todos los géneros', value: null },
  { label: 'Masculino', value: 'M' },
  { label: 'Femenino', value: 'F' },
]
const OPT_ESTATUS_CUENTA = [
  { label: 'Todos los estatus', value: null },
  { label: 'Al Corriente', value: 'AL_CORRIENTE' },
  { label: 'Moroso', value: 'MOROSO' },
  { label: 'Suspendido', value: 'SUSPENDIDO' },
]
const OPT_ESTATUS_PENALIZACION = [
  { label: 'Todos', value: null },
  { label: 'Sin Penalización', value: 'SIN_PENALIZACION' },
  { label: 'Penalización Reservas', value: 'PENALIZADO_RESERVA' },
  { label: 'Penalización Ludoteca', value: 'PENALIZADO_LUDOTECA' },
  { label: 'Penalización Ambos', value: 'PENALIZADO_AMBOS' },
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
  if (filterTipo.value) r = r.filter(s => s.tipo_socio === filterTipo.value)
  if (filterModalidad.value) r = r.filter(s => s.modalidad_plan === filterModalidad.value)
  if (filterGenero.value) r = r.filter(s => s.genero === filterGenero.value)
  if (filterEstatus.value) r = r.filter(s => s.estatus_cuenta === filterEstatus.value)
  if (filterPenalizacion.value) r = r.filter(s => (s.estatus_penalizacion ?? 'SIN_PENALIZACION') === filterPenalizacion.value)

  // Ordenar alfabéticamente por nombre
  return [...r].sort((a, b) => (a.nombre_completo || '').localeCompare(b.nombre_completo || ''))
})

const hasActiveFilters = computed(() =>
  search.value || filterTipo.value || filterModalidad.value || filterGenero.value ||
  filterEstatus.value || filterPenalizacion.value
)

const clearFilters = () => {
  search.value = ''
  filterTipo.value = filterModalidad.value = filterGenero.value =
    filterEstatus.value = filterPenalizacion.value = null
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
    action: () => {
      if (typeof socioStore.setCurrentSocio === 'function') {
        socioStore.setCurrentSocio(socio);
      } else {
        socioStore.currentSocio = socio;
      }
      router.push({ path: `/admin/socios/${socio.id_socio}` });
    },
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
    action: () => { openFamily(socio) },
  },
  {
    label: 'Pases de invitados',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
             <circle cx="9" cy="7" r="4"/>
             <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
           </svg>`,
    action: () => { openGuests(socio) },
  },
  {
    label: socio.estatus_cuenta === 'SUSPENDIDO' ? 'Reactivar cuenta' : 'Suspender cuenta',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
           </svg>`,
    action: () => openEstatus(socio),
  },
]

// ── MODALES ───────────────────────────────────────────────────
const showPenaltyModal = ref(false)
const showFamilyModal = ref(false)
const showGuestsModal = ref(false)
const showEstatusModal = ref(false)
const selectedSocio = ref(null)
const isModalLoading = ref(false)

const openPenalty = (socio) => {
  selectedSocio.value = socio
  showPenaltyModal.value = true
}

const openFamily = async (socio) => {
  selectedSocio.value = socio
  showFamilyModal.value = true

  // Si ya tenemos la info en caché, no mostramos el spinner global ni bloqueamos
  const cached = socioStore.getSocioById(socio.id_socio)
  if (!cached?.miembros_familiares) {
    isModalLoading.value = true
  }

  try {
    // Silent fetch: no dispara el isLoading global del store
    await fetchSocioDetails(socio.id_socio, true, true)
    selectedSocio.value = socioStore.getSocioById(socio.id_socio) ?? socio
  } finally {
    isModalLoading.value = false
  }
}

const openGuests = async (socio) => {
  selectedSocio.value = socio
  showGuestsModal.value = true

  const cached = socioStore.getSocioById(socio.id_socio)
  if (!cached?.invitados) {
    isModalLoading.value = true
  }

  try {
    await fetchSocioDetails(socio.id_socio, true, true)
    selectedSocio.value = socioStore.getSocioById(socio.id_socio) ?? socio
  } finally {
    isModalLoading.value = false
  }
}

const openEstatus = (socio) => {
  selectedSocio.value = socio
  showEstatusModal.value = true
}

// ── INIT ──────────────────────────────────────────────────────
onMounted(fetchSocios)
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader title="Socios Titulares"
        subtitle="Gestión de membresías, penalizaciones e información de cuenta.">
        <span class="text-sm font-bold text-surface-500">
          {{ filteredSocios.length }}
          <span class="font-medium text-surface-400">de {{ socios.length }} socios</span>
        </span>
        <ExportCsvButton :data="filteredSocios" filename="socios-titulares"
          :columns="[
            { label: 'ID Socio', field: 'id_socio' },
            { label: 'Número Acción', field: 'numero_accion' },
            { label: 'Nombre Completo', field: 'nombre_completo' },
            { label: 'Tipo', field: 'tipo_socio' },
            { label: 'Modalidad', field: 'modalidad_plan' },
            { label: 'Estatus', field: 'estatus_cuenta' }
          ]" />
      </AdminPageHeader>

      <!-- BARRA DE FILTROS -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5 space-y-4">
        <SearchInput v-model="search" placeholder="Buscar por nombre o número de acción…" />
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Tipo</label>
            <div class="relative">
              <IconFilter
                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="filterTipo"
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_TIPO" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown
                class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Modalidad</label>
            <div class="relative">
              <IconFilter
                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="filterModalidad"
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_MODALIDAD" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown
                class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Género</label>
            <div class="relative">
              <IconFilter
                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="filterGenero"
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_GENERO" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown
                class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            </div>
          </div>
          <!-- Fila 2: los 2 filtros de estatus -->
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estatus Cuenta</label>
            <div class="relative">
              <IconAlertCircle
                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="filterEstatus"
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_ESTATUS_CUENTA" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown
                class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estatus
              Penalización</label>
            <div class="relative">
              <IconWarning
                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="filterPenalizacion"
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_ESTATUS_PENALIZACION" :key="opt.value" :value="opt.value">{{ opt.label }}
                </option>
              </select>
              <IconChevronDown
                class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            </div>
          </div>
        </div>
        <Transition enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -transurface-y-1" enter-to-class="opacity-100 transurface-y-0"
          leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100 transurface-y-0"
          leave-to-class="opacity-0 -transurface-y-1">
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
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">

        <!-- Estado: cargando -->
        <div v-if="isLoading" class="p-8 space-y-3">
          <div v-for="n in 6" :key="n" class="flex items-center gap-4 animate-pulse py-3 border-b border-surface-100">
            <div class="w-10 h-10 rounded-xl bg-surface-200 shrink-0" />
            <div class="flex-1 space-y-2">
              <div class="h-3.5 bg-surface-200 rounded-lg w-48" />
              <div class="h-3 bg-surface-100 rounded-lg w-28" />
            </div>
            <div class="h-5 w-20 bg-surface-100 rounded-full" />
            <div class="h-5 w-24 bg-surface-100 rounded-full hidden xl:block" />
            <div class="h-3 w-16 bg-surface-100 rounded-lg hidden sm:block" />
            <div class="h-3 w-16 bg-surface-100 rounded-lg hidden lg:block" />
            <div class="h-3 w-16 bg-surface-100 rounded-lg hidden lg:block" />
          </div>
        </div>

        <!-- Estado: error -->
        <div v-else-if="errorMsg" class="p-8 text-center text-red-700 font-semibold text-sm">
          {{ errorMsg }}
        </div>

        <!-- Estado: vacío -->
        <div v-else-if="filteredSocios.length === 0" class="p-16 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 rounded-2xl bg-surface-100 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="1.5">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
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
              <th class="px-5 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700">Socio</th>
              <th
                class="px-4 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700 hidden sm:table-cell">
                Acción</th>
              <th
                class="px-4 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700 hidden md:table-cell">
                Tipo</th>
              <th
                class="px-4 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700 hidden lg:table-cell">
                Modalidad</th>
              <th
                class="px-4 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700 hidden lg:table-cell">
                Género</th>
              <th class="px-4 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700">Estatus
                Cuenta</th>
              <th
                class="px-4 py-3.5 text-left text-xs font-black uppercase tracking-widest text-surface-700 hidden xl:table-cell">
                Estatus Penalización</th>
              <th class="px-4 py-3.5 text-right text-xs font-black uppercase tracking-widest text-surface-700">Acciones
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-100">
            <tr v-for="socio in filteredSocios" :key="socio.id_socio"
              class="hover:bg-surface-50/70 transition-colors group">
              <!-- Nombre + avatar -->
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-linear-to-br flex items-center justify-center
                           text-white font-black text-xs shrink-0 shadow-sm"
                    :class="avatarGradient(socio.nombre_completo)">
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
                <BadgeStatus :status="socio.tipo_socio" />
              </td>
              <!-- Modalidad -->
              <td class="px-4 py-3.5 hidden lg:table-cell">
                <BadgeStatus :status="socio.modalidad_plan" />
              </td>
              <!-- Género -->
              <td class="px-4 py-3.5 hidden lg:table-cell">
                <BadgeStatus :status="socio.genero" />
              </td>
              <!-- Estatus Cuenta -->
              <td class="px-4 py-3.5" @click.stop>
                <BadgeStatus :status="socio.estatus_cuenta" />
              </td>
              <!-- Estatus Penalización -->
              <td class="px-4 py-3.5 hidden xl:table-cell" @click.stop>
                <BadgeStatus :status="socio.estatus_penalizacion ?? 'SIN_PENALIZACION'" />
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
    <PenalizacionModal v-model="showPenaltyModal" :socio="selectedSocio" />

    <!-- ══════════════════════════════════════════════════════════
         MODAL: ESTATUS DE CUENTA
    ══════════════════════════════════════════════════════════ -->
    <EstatusCuentaModal v-model="showEstatusModal" :socio="selectedSocio" />

    <!-- ══════════════════════════════════════════════════════════
         MODAL: MIEMBROS FAMILIARES
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showFamilyModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showFamilyModal = false">
          <div class="bg-white w-full max-w-md rounded-4xl shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">

            <!-- Cabecera -->
            <div class="flex items-center justify-between px-7 py-5 border-b border-surface-100">
              <div>
                <h2 class="text-lg font-black text-surface-900 leading-tight">Miembros Familiares</h2>
                <p class="text-xs text-surface-500 font-medium mt-0.5">{{ selectedSocio?.nombre_completo }}</p>
              </div>
              <button @click="showFamilyModal = false" class="w-9 h-9 rounded-xl bg-surface-100 hover:bg-surface-200
                       flex items-center justify-center text-surface-500 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M18 6L6 18M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Cuerpo -->
            <div class="overflow-y-auto p-6 bg-surface-50/30">

              <!-- Cargando -->
              <div v-if="isModalLoading" class="flex justify-center py-12">
                <LoadingSpinner />
              </div>

              <!-- Vacío -->
              <div v-else-if="!selectedSocio?.miembros_familiares?.length" class="flex flex-col items-center justify-center py-14 text-center
                       bg-white rounded-2xl border-2 border-dashed border-surface-200">
                <div class="w-14 h-14 rounded-2xl bg-surface-100 flex items-center justify-center mb-3">
                  <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5">
                    <path d="M17 21v-2a4 4 0 0 0-3-3.87M9 21v-2a4 4 0 0 0-3-3.87" />
                    <circle cx="9" cy="7" r="4" />
                    <circle cx="17" cy="7" r="4" />
                  </svg>
                </div>
                <p class="text-sm font-bold text-surface-700">Sin miembros familiares</p>
                <p class="text-xs text-surface-400 mt-1 max-w-[200px]">
                  No hay miembros familiares registrados para este socio.
                </p>
              </div>

              <!-- Lista -->
              <div v-else class="flex flex-col gap-2.5">
                <div v-for="fam in selectedSocio.miembros_familiares" :key="fam.id_miembro" class="flex items-center gap-4 p-4 rounded-2xl bg-white border border-surface-200
                         hover:border-primary-200 hover:shadow-sm transition-all">
                  <div class="w-10 h-10 rounded-xl bg-linear-to-br from-purple-400 to-purple-600
                              text-white flex items-center justify-center font-bold text-sm shrink-0">
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
              <CancelButton label="Cerrar" @click="showFamilyModal = false" />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: INVITADOS
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showGuestsModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showGuestsModal = false">
          <div class="bg-white w-full max-w-lg rounded-4xl shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">

            <!-- Cabecera -->
            <div class="flex items-center justify-between px-7 py-5 border-b border-surface-100">
              <div>
                <h2 class="text-lg font-black text-surface-900 leading-tight">Pases de Invitados</h2>
                <p class="text-xs text-surface-500 font-medium mt-0.5">{{ selectedSocio?.nombre_completo }}</p>
              </div>
              <button @click="showGuestsModal = false" class="w-9 h-9 rounded-xl bg-surface-100 hover:bg-surface-200
                       flex items-center justify-center text-surface-500 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M18 6L6 18M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Cuerpo -->
            <div class="overflow-y-auto p-6 bg-surface-50/30">

              <!-- Cargando -->
              <div v-if="isModalLoading" class="flex justify-center py-12">
                <LoadingSpinner />
              </div>

              <!-- Vacío — mismo esqueleto que Miembros Familiares -->
              <div v-else-if="!selectedSocio?.invitados?.length" class="flex flex-col items-center justify-center py-14 text-center
                       bg-white rounded-2xl border-2 border-dashed border-surface-200">
                <div class="w-14 h-14 rounded-2xl bg-primary-50 flex items-center justify-center mb-3">
                  <svg class="w-7 h-7 text-primary-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                </div>
                <p class="text-sm font-bold text-surface-700">Sin pases registrados</p>
                <p class="text-xs text-surface-400 mt-1 max-w-[200px]">
                  Este socio no tiene pases de invitados activos ni históricos.
                </p>
              </div>

              <!-- Lista -->
              <div v-else class="flex flex-col gap-2.5">
                <div v-for="guest in selectedSocio.invitados" :key="guest.id_invitado" class="flex items-center justify-between p-4 rounded-2xl bg-white border border-surface-200
                         hover:border-primary-200 hover:shadow-sm transition-all">
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
                    <BadgeStatus :status="guest.pase?.estatus_acceso === 'ACTIVO' ? 'ACTIVO' :
                      guest.pase?.estatus_acceso === 'USADO' ? 'USADO' : 'EXPIRADO'" />
                    <p v-if="guest.pase?.fecha_expiracion" class="text-[10px] font-medium text-surface-400">
                      {{ guest.pase.fecha_expiracion }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pie -->
            <div class="px-7 py-4 border-t border-surface-100 flex justify-end">
              <CancelButton label="Cerrar" @click="showGuestsModal = false" />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </main>
</template>
