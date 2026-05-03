<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useSpacesStore }     from '@/stores/admin/spaces'
import { useDisciplinesStore } from '@/stores/admin/disciplines'
import { useAlerts }           from '@/composables/useAlerts'

import Select from 'primevue/select'

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus     from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu      from '@/components/gerente/ui/ActionMenu.vue'

import IconFutbol     from '@/components/icons/sports/IconFutbol.vue'
import IconBasquetbol from '@/components/icons/sports/IconBasquetbol.vue'
import IconTenis      from '@/components/icons/sports/IconTenis.vue'
import IconVoleibol   from '@/components/icons/sports/IconVoleibol.vue'
import IconSquash     from '@/components/icons/sports/IconSquash.vue'
import IconFrontenis  from '@/components/icons/sports/IconFrontenis.vue'
import IconPadel      from '@/components/icons/sports/IconPadel.vue'
import IconDefault    from '@/components/icons/sports/IconDefault.vue'

const router           = useRouter()
const spacesStore      = useSpacesStore()
const disciplinesStore = useDisciplinesStore()
const { toastInfo }    = useAlerts()

const { spaces, isLoading }      = storeToRefs(spacesStore)
const { disciplines }            = storeToRefs(disciplinesStore)

// ── FILTROS ────────────────────────────────────────────────────
const search       = ref('')
const filterTipo   = ref(null)
const filterStatus = ref(null)

const OPT_TIPO = [
  { label: 'Todos los tipos',      value: null },
  { label: 'Reserva On Demand',    value: 'RESERVA_ON_DEMAND' },
  { label: 'Clase Programada',     value: 'CLASE_PROGRAMADA' },
  { label: 'Uso Libre',            value: 'USO_LIBRE' },
]
const OPT_STATUS = [
  { label: 'Todos los estados',    value: null },
  { label: 'Activo',               value: 'ACTIVO' },
  { label: 'Mantenimiento',        value: 'MANTENIMIENTO' },
  { label: 'Deshabilitado',        value: 'DESHABILITADO' },
]

const filteredSpaces = computed(() => {
  let r = [...spaces.value]
  if (search.value) {
    const q = search.value.toLowerCase()
    r = r.filter(s => s.nombre_espacio.toLowerCase().includes(q))
  }
  if (filterTipo.value === 'RESERVA_ON_DEMAND') r = r.filter(s => s.es_reserva_on_demand)
  else if (filterTipo.value === 'CLASE_PROGRAMADA') r = r.filter(s => s.es_clase_programada)
  else if (filterTipo.value === 'USO_LIBRE')  r = r.filter(s => s.es_uso_libre)
  if (filterStatus.value) r = r.filter(s => s.estatus === filterStatus.value)
  return r.sort((a, b) => a.nombre_espacio.localeCompare(b.nombre_espacio))
})

const hasActiveFilters = computed(() => search.value || filterTipo.value || filterStatus.value)
const clearFilters = () => {
  search.value = ''
  filterTipo.value = filterStatus.value = null
}

// ── ICONO DEPORTE ──────────────────────────────────────────────
const getIcon = (name = '') => {
  const n = name.toLowerCase()
  if (n.includes('futbol'))    return IconFutbol
  if (n.includes('basquet'))   return IconBasquetbol
  if (n.includes('tenis') && !n.includes('padel') && !n.includes('squash')) return IconTenis
  if (n.includes('voleibol'))  return IconVoleibol
  if (n.includes('squash'))    return IconSquash
  if (n.includes('frontenis')) return IconFrontenis
  if (n.includes('padel'))     return IconPadel
  return IconDefault
}

// ── FRANJA DE COLOR ESTATUS ────────────────────────────────────
const statusAccentLeft = (estatus) => ({
  ACTIVO:        'bg-emerald-500',
  MANTENIMIENTO: 'bg-amber-500',
  DESHABILITADO: 'bg-red-500',
}[estatus] ?? 'bg-slate-300')

// ── BADGES DE TIPO ─────────────────────────────────────────────
const tipoBadges = (space) => {
  const b = []
  if (space.es_reserva_on_demand) b.push({ label: 'On Demand',  classes: 'bg-indigo-50 text-indigo-700 border-indigo-100' })
  if (space.es_clase_programada)  b.push({ label: 'Clase',      classes: 'bg-blue-50 text-blue-700 border-blue-100' })
  if (space.es_uso_libre)         b.push({ label: 'Uso Libre',  classes: 'bg-emerald-50 text-emerald-700 border-emerald-100' })
  return b
}

// ── MENU ITEMS ─────────────────────────────────────────────────
const buildMenuItems = (space) => [
  {
    label:  'Ver detalles',
    icon:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
               <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
             </svg>`,
    action: () => router.push({ name: 'spaces-details', params: { id: space.id_espacio } }),
  },
  {
    label:  'Editar espacio',
    icon:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
               <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
             </svg>`,
    action: () => router.push({ name: 'spaces-details', params: { id: space.id_espacio }, query: { edit: 'true' } }),
  },
  {
    label:  'Gestionar disciplinas',
    icon:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
               <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
               <line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>
             </svg>`,
    action: () => router.push({ name: 'spaces-disciplines', params: { id: space.id_espacio } }),
  },
  { separator: true },
  {
    label:       'Deshabilitar espacio',
    icon:        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                  </svg>`,
    action:      () => openDisableModal(space),
    destructive: true,
    disabled:    space.estatus === 'DESHABILITADO',
  },
]

// ── MODAL: NUEVO ESPACIO ───────────────────────────────────────
const showNewModal = ref(false)
const isSaving     = ref(false)
const formError    = ref('')

const EMPTY_SPACE = () => ({
  nombre_espacio:       '',
  capacidad_maxima:     10,
  es_reserva_on_demand: true,
  es_clase_programada:  false,
  es_uso_libre:         false,
  estatus:              'ACTIVO',
  descripcion:          '',
  disciplinas:          [],
})
const newSpace = ref(EMPTY_SPACE())

const toggleDisciplina = (id) => {
  const idx = newSpace.value.disciplinas.indexOf(id)
  idx > -1 ? newSpace.value.disciplinas.splice(idx, 1) : newSpace.value.disciplinas.push(id)
}

const toggleUso = (tipo) => {
  if (tipo === 'on_demand') {
    newSpace.value.es_reserva_on_demand = !newSpace.value.es_reserva_on_demand
    if (newSpace.value.es_reserva_on_demand) newSpace.value.es_uso_libre = false
  } else if (tipo === 'clase') {
    newSpace.value.es_clase_programada = !newSpace.value.es_clase_programada
    if (newSpace.value.es_clase_programada) newSpace.value.es_uso_libre = false
  } else if (tipo === 'libre') {
    newSpace.value.es_uso_libre = !newSpace.value.es_uso_libre
    if (newSpace.value.es_uso_libre) {
      newSpace.value.es_reserva_on_demand = false
      newSpace.value.es_clase_programada  = false
    }
  }
}

const openNewModal = () => {
  newSpace.value = EMPTY_SPACE()
  formError.value = ''
  showNewModal.value = true
}

const saveNewSpace = async () => {
  formError.value = ''
  if (!newSpace.value.nombre_espacio.trim()) {
    formError.value = 'El nombre del espacio es requerido.'
    return
  }
  isSaving.value = true
  const res = await spacesStore.createSpace(newSpace.value)
  isSaving.value = false
  if (res?.success) {
    showNewModal.value = false
    toastInfo('Espacio creado', `"${newSpace.value.nombre_espacio}" fue registrado.`, 'success')
  } else {
    formError.value = res?.error ?? 'Ocurrió un error al crear el espacio.'
  }
}

// ── MODAL: DESHABILITAR ────────────────────────────────────────
const showDisableModal = ref(false)
const selectedSpace    = ref(null)

const openDisableModal = (space) => {
  selectedSpace.value    = space
  showDisableModal.value = true
}

const confirmDisable = async () => {
  if (!selectedSpace.value) return
  isSaving.value = true
  const res = await spacesStore.deleteSpace(selectedSpace.value.id_espacio)
  isSaving.value = false
  if (res?.success) {
    showDisableModal.value = false
    toastInfo('Espacio deshabilitado', selectedSpace.value.nombre_espacio, 'success')
  } else {
    toastInfo('Error', res?.error ?? 'No se pudo deshabilitar el espacio.', 'error')
  }
}

// ── INIT ──────────────────────────────────────────────────────
onMounted(() => {
  spacesStore.fetchSpaces()
  disciplinesStore.fetchDisciplines()
})
</script>

<template>
  <main class="min-h-screen bg-slate-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader
        title="Espacios Físicos"
        subtitle="Gestiona las canchas, salones e instalaciones del club."
      >
        <span class="text-sm font-bold text-slate-500">
          {{ filteredSpaces.length }}
          <span class="font-medium text-slate-400">de {{ spaces.length }}</span>
        </span>
        <button
          @click="openNewModal"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white
                 text-sm font-bold hover:bg-blue-700 transition-colors shadow-sm"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
          </svg>
          Nuevo Espacio
        </button>
      </AdminPageHeader>

      <!-- BARRA DE FILTROS -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
        <div class="relative">
          <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
               viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <input
            v-model="search"
            placeholder="Buscar espacio por nombre…"
            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm
                   font-medium text-slate-900 placeholder:text-slate-400
                   focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Tipo de uso</label>
            <Select v-model="filterTipo" :options="OPT_TIPO" option-label="label" option-value="value"
                    placeholder="Todos los tipos" class="w-full text-sm" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Estatus</label>
            <Select v-model="filterStatus" :options="OPT_STATUS" option-label="label" option-value="value"
                    placeholder="Todos los estados" class="w-full text-sm" />
          </div>
        </div>
        <Transition
          enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1"
        >
          <div v-if="hasActiveFilters" class="flex justify-end">
            <button @click="clearFilters"
              class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1.5 transition-colors">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 6L6 18M6 6l12 12"/>
              </svg>
              Limpiar filtros
            </button>
          </div>
        </Transition>
      </div>

      <!-- SKELETON -->
      <div v-if="isLoading" class="flex flex-col gap-4">
        <div v-for="n in 4" :key="n"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex gap-5 animate-pulse">
          <div class="w-14 h-14 rounded-2xl bg-slate-200 shrink-0"/>
          <div class="flex-1 space-y-3 py-1">
            <div class="h-4 bg-slate-200 rounded-lg w-48"/>
            <div class="flex gap-2">
              <div class="h-5 w-20 bg-slate-100 rounded-lg"/>
              <div class="h-5 w-16 bg-slate-100 rounded-lg"/>
            </div>
            <div class="flex gap-1.5">
              <div class="h-5 w-16 bg-slate-100 rounded-lg"/>
              <div class="h-5 w-16 bg-slate-100 rounded-lg"/>
              <div class="h-5 w-16 bg-slate-100 rounded-lg"/>
            </div>
          </div>
          <div class="w-28 h-9 rounded-xl bg-slate-100 self-center shrink-0"/>
        </div>
      </div>

      <!-- VACÍO -->
      <div v-else-if="filteredSpaces.length === 0"
        class="bg-white rounded-2xl border-2 border-dashed border-slate-200 p-16
               flex flex-col items-center justify-center text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
          </svg>
        </div>
        <h3 class="text-base font-black text-slate-900">Sin resultados</h3>
        <p class="text-sm text-slate-500 mt-1 max-w-xs">No se encontraron espacios con los filtros actuales.</p>
        <button @click="clearFilters" class="mt-4 text-sm font-bold text-blue-600 hover:underline">Limpiar filtros</button>
      </div>

      <!-- LISTA DE CARDS HORIZONTALES -->
      <TransitionGroup
        v-else
        tag="div"
        class="flex flex-col gap-4"
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in absolute"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-for="space in filteredSpaces"
          :key="space.id_espacio"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm
                 hover:shadow-md hover:border-slate-300
                 transition-all duration-200 group overflow-hidden flex"
        >
          <!-- Franja color estatus (izquierda) -->
          <div class="w-1.5 shrink-0" :class="statusAccentLeft(space.estatus)" />

          <!-- Ícono instalación -->
          <div class="flex items-center justify-center px-5 py-4 shrink-0">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center
                        shadow-sm group-hover:scale-105 transition-transform duration-200">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
              </svg>
            </div>
          </div>

          <!-- Contenido principal -->
          <div class="flex-1 min-w-0 py-4 pr-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">

                <!-- Nombre + capacidad -->
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="text-sm font-black text-slate-900 truncate leading-tight">
                    {{ space.nombre_espacio }}
                  </h3>
                  <span class="text-[10px] font-bold text-slate-400 shrink-0">
                    · {{ space.capacidad_maxima ?? '—' }} pers.
                  </span>
                </div>

                <!-- Estatus + tipo (pills) -->
                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                  <BadgeStatus :status="space.estatus" size="sm" />
                  <span
                    v-for="badge in tipoBadges(space)"
                    :key="badge.label"
                    class="text-[10px] font-bold px-2 py-0.5 rounded-lg border uppercase tracking-wide"
                    :class="badge.classes"
                  >
                    {{ badge.label }}
                  </span>
                </div>

                <!-- Disciplinas -->
                <div v-if="space.disciplinas?.length" class="flex flex-wrap items-center gap-1 mt-2">
                  <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mr-1">
                    Disciplinas:
                  </span>
                  <span
                    v-for="d in space.disciplinas.slice(0, 5)"
                    :key="d.id_disciplina"
                    class="flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-50 border border-slate-200
                           text-[10px] font-bold text-slate-600"
                  >
                    <component :is="getIcon(d.nombre_disciplina)" class="w-3 h-3 shrink-0" />
                    {{ d.nombre_disciplina }}
                  </span>
                  <span v-if="space.disciplinas.length > 5"
                    class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-500 text-[10px] font-bold">
                    +{{ space.disciplinas.length - 5 }}
                  </span>
                </div>
                <p v-else class="text-[11px] text-slate-400 italic mt-2">Sin disciplinas asignadas</p>

              </div>

              <!-- Botón ver detalles + menú -->
              <div class="flex items-center gap-2 shrink-0">
                <button
                  @click="router.push({ name: 'spaces-details', params: { id: space.id_espacio } })"
                  class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100
                         text-xs font-bold text-slate-700 hover:bg-blue-50 hover:text-blue-600
                         transition-colors border border-slate-200 hover:border-blue-200"
                >
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <path d="M14 2v6h6"/>
                  </svg>
                  Ver detalles
                </button>
                <ActionMenu :items="buildMenuItems(space)" align="right" />
              </div>
            </div>
          </div>
        </div>
      </TransitionGroup>

    </div>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: NUEVO ESPACIO
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div v-if="showNewModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="showNewModal = false"
        >
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
          >
            <div v-if="showNewModal"
              class="bg-white w-full max-w-2xl rounded-4xl shadow-2xl flex flex-col max-h-[92vh] overflow-hidden">

              <!-- Cabecera -->
              <div class="flex items-center justify-between px-7 py-5 border-b border-slate-100">
                <div>
                  <h2 class="text-lg font-black text-slate-900 leading-tight">Nuevo Espacio</h2>
                  <p class="text-xs text-slate-500 font-medium mt-0.5">Registra una cancha, salón o instalación.</p>
                </div>
                <button @click="showNewModal = false"
                  class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6L6 18M6 6l12 12"/>
                  </svg>
                </button>
              </div>

              <!-- Cuerpo -->
              <div class="overflow-y-auto p-7 space-y-6 bg-slate-50/30">

                <!-- Error banner -->
                <Transition enter-active-class="transition-all duration-200"
                            enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                  <div v-if="formError"
                    class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-2xl text-red-700 text-sm font-semibold">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    {{ formError }}
                  </div>
                </Transition>

                <!-- Layout 2 columnas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                  <!-- Columna izquierda -->
                  <div class="space-y-5">

                    <div class="space-y-1.5">
                      <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">
                        Nombre del Espacio <span class="text-red-400">*</span>
                      </label>
                      <input v-model="newSpace.nombre_espacio" placeholder="Ej. Cancha de Tenis 1"
                        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium
                               text-slate-900 placeholder:text-slate-400
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all"/>
                    </div>

                    <div class="space-y-1.5">
                      <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Capacidad Máxima</label>
                      <input v-model.number="newSpace.capacidad_maxima" type="number" min="1"
                        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium
                               focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all"/>
                    </div>

                    <!-- Tipo de uso -->
                    <div class="space-y-2">
                      <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Tipo de Uso</label>
                      <div class="flex flex-col gap-2">
                        <button
                          v-for="uso in [
                            { key: 'on_demand', label: 'Reserva On Demand', active: newSpace.es_reserva_on_demand },
                            { key: 'clase',     label: 'Clase Programada',  active: newSpace.es_clase_programada  },
                            { key: 'libre',     label: 'Uso Libre',         active: newSpace.es_uso_libre         },
                          ]"
                          :key="uso.key"
                          type="button"
                          @click="toggleUso(uso.key)"
                          class="flex items-center justify-between px-4 py-3 rounded-xl border text-xs font-bold transition-all"
                          :class="uso.active
                            ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                            : 'bg-white text-slate-500 border-slate-200 hover:border-blue-200 hover:text-blue-600'"
                        >
                          <span>{{ uso.label }}</span>
                          <svg v-if="uso.active" class="w-4 h-4 shrink-0"
                               viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <path d="M20 6L9 17l-5-5"/>
                          </svg>
                          <div v-else class="w-4 h-4 rounded-full border-2 border-slate-300 shrink-0"/>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Columna derecha: disciplinas — selector azul institucional -->
                  <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Disciplinas Permitidas</label>
                    <div class="h-72 overflow-y-auto bg-white rounded-2xl p-2 border border-slate-200 space-y-1.5">
                      <button
                        v-for="d in disciplines"
                        :key="d.id_disciplina"
                        type="button"
                        @click="toggleDisciplina(d.id_disciplina)"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl border text-xs font-bold transition-all text-left"
                        :class="newSpace.disciplinas.includes(d.id_disciplina)
                          ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                          : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200'"
                      >
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 transition-all"
                          :class="newSpace.disciplinas.includes(d.id_disciplina)
                            ? 'bg-white/20 text-white'
                            : 'bg-slate-200 text-slate-500'">
                          <component :is="getIcon(d.nombre_disciplina)" class="w-4 h-4" />
                        </div>
                        <span class="truncate flex-1">{{ d.nombre_disciplina }}</span>
                        <svg v-if="newSpace.disciplinas.includes(d.id_disciplina)"
                          class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none"
                          stroke="currentColor" stroke-width="3">
                          <path d="M20 6L9 17l-5-5"/>
                        </svg>
                      </button>
                    </div>
                  </div>

                </div>
              </div>

              <!-- Pie del modal -->
              <div class="flex items-center justify-end gap-3 px-7 py-4 border-t border-slate-100">
                <button @click="showNewModal = false"
                  class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                  Cancelar
                </button>
                <button @click="saveNewSpace" :disabled="isSaving"
                  class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2">
                  <svg v-if="isSaving" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                  </svg>
                  {{ isSaving ? 'Guardando…' : 'Crear Espacio' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: CONFIRMAR DESHABILITAR
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div v-if="showDisableModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="showDisableModal = false"
        >
          <div class="bg-white w-full max-w-md rounded-4xl shadow-2xl p-8 text-center">
            <div class="w-16 h-16 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center mx-auto mb-5">
              <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v4M12 17h.01"/>
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
              </svg>
            </div>
            <h3 class="text-xl font-black text-slate-900 mb-2">¿Deshabilitar espacio?</h3>
            <p class="text-sm text-slate-500 mb-8">
              ¿Confirmas deshabilitar <span class="font-bold text-slate-800">{{ selectedSpace?.nombre_espacio }}</span>?
              El sistema validará que no haya actividades pendientes.
            </p>
            <div class="flex gap-3">
              <button @click="showDisableModal = false"
                class="flex-1 py-3 rounded-2xl border border-slate-200 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                Cancelar
              </button>
              <button @click="confirmDisable" :disabled="isSaving"
                class="flex-1 py-3 rounded-2xl bg-red-600 hover:bg-red-700 text-white text-sm font-bold transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                <svg v-if="isSaving" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                {{ isSaving ? 'Procesando…' : 'Deshabilitar' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </main>
</template>
