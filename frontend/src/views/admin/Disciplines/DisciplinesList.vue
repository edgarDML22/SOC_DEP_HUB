<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useDisciplinesStore } from '@/stores/admin/disciplines'
import { useCategoryStore } from '@/stores/admin/categoryStore'
import { useAlerts } from '@/composables/useAlerts'
import { useformat } from '@/utils/formatters'

import Select from 'primevue/select'

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import SearchInput from '@/components/gerente/ui/SearchInput.vue'
import { IconGrid, IconAlertCircle, IconChevronDown } from '@/components/icons'

const router = useRouter()
const disciplinesStore = useDisciplinesStore()
const categoryStore = useCategoryStore()
const { formatText } = useformat()
const { toastInfo } = useAlerts()

const { disciplines, isLoading } = storeToRefs(disciplinesStore)
const { categories: storeCategories } = storeToRefs(categoryStore)

// ── FILTROS ────────────────────────────────────────────────────
const search = ref('')
const filterStatus = ref(null)
const filterCategory = ref(null)

const categories = computed(() =>
  (storeCategories.value || []).filter(c => c.nombre !== 'INFANTIL')
)
const categoryOpts = computed(() => [
  { label: 'Todas las categorías', value: null },
  ...categories.value.map(c => ({ label: formatText(c.nombre), value: c.id_categoria })),
])
const OPT_STATUS = [
  { label: 'Todos los estados', value: null },
  { label: 'Activo', value: 'ACTIVO' },
  { label: 'Inactivo', value: 'INACTIVO' },
  { label: 'Mantenimiento', value: 'MANTENIMIENTO' },
  { label: 'Deshabilitado', value: 'DESHABILITADO' },
]

const filteredDisciplines = computed(() => {
  let r = [...disciplines.value]
  if (search.value) {
    const q = search.value.toLowerCase()
    r = r.filter(d => {
      // La API devuelve categorias como array (many-to-many)
      const catName = d.categorias?.[0]?.nombre || d.categoria_disciplina || ''
      return d.nombre_disciplina.toLowerCase().includes(q) || catName.toLowerCase().includes(q)
    })
  }
  if (filterStatus.value) r = r.filter(d => d.estatus === filterStatus.value)
  if (filterCategory.value) {
    r = r.filter(d => d.categorias?.some(c => String(c.id_categoria) === String(filterCategory.value)))
  }
  return r.sort((a, b) => {
    const catA = a.categorias?.[0]?.nombre || a.categoria_disciplina || ''
    const catB = b.categorias?.[0]?.nombre || b.categoria_disciplina || ''
    const c = catA.localeCompare(catB)
    return c !== 0 ? c : a.nombre_disciplina.localeCompare(b.nombre_disciplina)
  })
})

const hasActiveFilters = computed(() => search.value || filterStatus.value || filterCategory.value)
const clearFilters = () => {
  search.value = ''
  filterStatus.value = filterCategory.value = null
}

// ── PALETA POR CATEGORÍA ───────────────────────────────────────
const CATEGORY_COLORS = [
  { left: 'bg-blue-500', icon: 'bg-blue-50 text-blue-600' },
  { left: 'bg-purple-500', icon: 'bg-purple-50 text-purple-600' },
  { left: 'bg-emerald-500', icon: 'bg-emerald-50 text-emerald-600' },
  { left: 'bg-orange-500', icon: 'bg-orange-50 text-orange-600' },
  { left: 'bg-rose-500', icon: 'bg-rose-50 text-rose-600' },
  { left: 'bg-cyan-500', icon: 'bg-cyan-50 text-cyan-600' },
  { left: 'bg-amber-500', icon: 'bg-amber-50 text-amber-600' },
  { left: 'bg-indigo-500', icon: 'bg-indigo-50 text-indigo-600' },
]
const categoryPalette = (catName = '') => {
  const code = catName.length > 0 ? catName.charCodeAt(0) : 0
  const idx = code % CATEGORY_COLORS.length
  return CATEGORY_COLORS[idx]
}

// ── MENU ITEMS ─────────────────────────────────────────────────
const buildMenuItems = (discipline) => [
  {
    label: 'Ver detalles',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
               <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
             </svg>`,
    action: () => router.push({ name: 'disciplines-details', params: { id: discipline.id_disciplina } }),
  },
  {
    label: 'Editar disciplina',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
               <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
             </svg>`,
    action: () => router.push({ name: 'disciplines-details', params: { id: discipline.id_disciplina }, query: { edit: 'true' } }),
  },
  {
    label: 'Ver instructores',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
               <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
               <circle cx="9" cy="7" r="4"/>
               <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
             </svg>`,
    action: () => openInstructorsModal(discipline.id_disciplina),
  },
  { separator: true },
  {
    label: 'Deshabilitar',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                  </svg>`,
    action: () => openDisableModal(discipline),
    destructive: true,
    disabled: discipline.estatus === 'DESHABILITADO',
  },
]

// ── MODAL: NUEVA DISCIPLINA ────────────────────────────────────
const showNewModal = ref(false)
const isSaving = ref(false)
const formError = ref('')

const EMPTY_DISCIPLINE = () => ({ nombre_disciplina: '', id_categoria: null, descripcion: '', estatus: 'ACTIVO' })
const newDiscipline = ref(EMPTY_DISCIPLINE())

const OPT_ESTATUS_FORM = [
  { label: 'Activo', value: 'ACTIVO' },
  { label: 'Inactivo', value: 'INACTIVO' },
]

const openNewModal = () => {
  newDiscipline.value = EMPTY_DISCIPLINE()
  formError.value = ''
  showNewModal.value = true
}

const saveNewDiscipline = async () => {
  formError.value = ''
  if (!newDiscipline.value.nombre_disciplina.trim()) {
    formError.value = 'El nombre de la disciplina es requerido.'
    return
  }
  if (!newDiscipline.value.id_categoria) {
    formError.value = 'Debes seleccionar una categoría.'
    return
  }
  isSaving.value = true
  const res = await disciplinesStore.createDiscipline(newDiscipline.value)
  isSaving.value = false
  if (res?.success) {
    showNewModal.value = false
    toastInfo('Disciplina creada', `"${newDiscipline.value.nombre_disciplina}" fue registrada.`, 'success')
  } else {
    formError.value = res?.error ?? 'Ocurrió un error al crear la disciplina.'
  }
}

// ── MODAL: INSTRUCTORES ────────────────────────────────────────
const showInstructorsModal = ref(false)
const selectedInstructors = ref([])
const isFetchingInstructors = ref(false)

const openInstructorsModal = async (id) => {
  selectedInstructors.value = []
  isFetchingInstructors.value = true
  showInstructorsModal.value = true
  try {
    const data = await disciplinesStore.fetchDisciplineDetails(id)
    selectedInstructors.value = data?.instructores ?? []
  } catch {
    selectedInstructors.value = []
  } finally {
    isFetchingInstructors.value = false
  }
}

// ── MODAL: DESHABILITAR ────────────────────────────────────────
const showDisableModal = ref(false)
const selectedDiscipline = ref(null)

const openDisableModal = (discipline) => {
  selectedDiscipline.value = discipline
  showDisableModal.value = true
}

const confirmDisable = async () => {
  if (!selectedDiscipline.value) return
  isSaving.value = true
  const res = await disciplinesStore.deleteDiscipline(selectedDiscipline.value.id_disciplina)
  isSaving.value = false
  if (res?.success) {
    showDisableModal.value = false
    toastInfo('Disciplina deshabilitada', selectedDiscipline.value.nombre_disciplina, 'success')
  } else {
    toastInfo('Error', res?.error ?? 'No se pudo deshabilitar.', 'error')
  }
}

// ── INIT ──────────────────────────────────────────────────────
onMounted(() => {
  disciplinesStore.fetchDisciplines()
  categoryStore.fetchCategories()
})
</script>

<template>
  <main class="min-h-screen bg-slate-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader title="Disciplinas" subtitle="Gestiona los deportes y actividades del club.">
        <span class="text-sm font-bold text-slate-500">
          {{ filteredDisciplines.length }}
          <span class="font-medium text-slate-400">de {{ disciplines.length }}</span>
        </span>
        <button @click="router.push({ name: 'disciplines-categories' })" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-200 shadow-sm
                 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7" />
            <rect x="14" y="3" width="7" height="7" />
            <rect x="14" y="14" width="7" height="7" />
            <rect x="3" y="14" width="7" height="7" />
          </svg>
          Categorías
        </button>
        <button @click="openNewModal" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white
                 text-sm font-bold hover:bg-blue-700 transition-colors shadow-sm">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="16" />
            <line x1="8" y1="12" x2="16" y2="12" />
          </svg>
          Nueva Disciplina
        </button>
      </AdminPageHeader>

      <!-- FILTROS -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
        <SearchInput v-model:search="search" placeholder="Buscar por nombre o categoría…" />
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Categoría</label>
            <div class="relative">
              <IconGrid class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
              <select v-model="filterCategory" class="w-full pl-10 pr-8 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all cursor-pointer">
                <option v-for="opt in categoryOpts" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Estatus</label>
            <div class="relative">
              <IconAlertCircle class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
              <select v-model="filterStatus" class="w-full pl-10 pr-8 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_STATUS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
            </div>
          </div>
        </div>
        <Transition enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1">
          <div v-if="hasActiveFilters" class="flex justify-end">
            <button @click="clearFilters"
              class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1.5 transition-colors">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 6L6 18M6 6l12 12" />
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
          <div class="w-14 h-14 rounded-2xl bg-slate-200 shrink-0" />
          <div class="flex-1 space-y-3 py-1">
            <div class="h-4 bg-slate-200 rounded-lg w-40" />
            <div class="h-3 bg-indigo-100 rounded-lg w-24" />
            <div class="h-3 bg-slate-100 rounded-lg w-full" />
            <div class="h-3 bg-slate-100 rounded-lg w-3/4" />
          </div>
          <div class="w-24 h-9 rounded-xl bg-slate-100 self-center shrink-0" />
        </div>
      </div>

      <!-- VACÍO -->
      <div v-else-if="filteredDisciplines.length === 0" class="bg-white rounded-2xl border-2 border-dashed border-slate-200 p-16
               flex flex-col items-center justify-center text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1" />
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z" />
            <line x1="6" y1="1" x2="6" y2="4" />
            <line x1="10" y1="1" x2="10" y2="4" />
            <line x1="14" y1="1" x2="14" y2="4" />
          </svg>
        </div>
        <h3 class="text-base font-black text-slate-900">Sin resultados</h3>
        <p class="text-sm text-slate-500 mt-1 max-w-xs">No se encontraron disciplinas con los filtros actuales.</p>
        <button @click="clearFilters" class="mt-4 text-sm font-bold text-blue-600 hover:underline">Limpiar
          filtros</button>
      </div>

      <!-- LISTA DE CARDS HORIZONTALES -->
      <TransitionGroup v-else tag="div" class="flex flex-col gap-4"
        enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-200 ease-in absolute"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-for="discipline in filteredDisciplines" :key="discipline.id_disciplina" class="bg-white rounded-2xl border border-slate-200 shadow-sm
                 hover:shadow-md hover:border-slate-300
                 transition-all duration-200 group overflow-hidden flex">
          <!-- Franja de color de categoría -->
          <div class="w-1.5 shrink-0"
            :class="categoryPalette(discipline.categorias?.[0]?.nombre || discipline.categoria_disciplina || '').left" />

          <!-- Ícono disciplina coloreado por categoría -->
          <div class="flex items-center justify-center px-5 py-4 shrink-0">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-sm
                     group-hover:scale-105 transition-transform duration-200"
              :class="categoryPalette(discipline.categorias?.[0]?.nombre || discipline.categoria_disciplina || '').icon">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M18 8h1a4 4 0 0 1 0 8h-1" />
                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z" />
                <line x1="6" y1="1" x2="6" y2="4" />
                <line x1="10" y1="1" x2="10" y2="4" />
                <line x1="14" y1="1" x2="14" y2="4" />
              </svg>
            </div>
          </div>

          <!-- Contenido principal -->
          <div class="flex-1 min-w-0 py-4 pr-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">

                <!-- Nombre -->
                <h3 class="text-sm font-black text-slate-900 truncate leading-tight">
                  {{ discipline.nombre_disciplina }}
                </h3>

                <!-- Categoría + Estatus pills -->
                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                  <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600
                               bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">
                    {{ formatText(discipline.categorias?.[0]?.nombre || discipline.categoria_disciplina || '—') }}
                  </span>
                  <BadgeStatus :status="discipline.estatus" size="sm" />
                </div>

                <!-- Descripción -->
                <p class="text-xs text-slate-500 font-medium line-clamp-2 leading-relaxed mt-1.5 max-w-2xl">
                  {{ discipline.descripcion || 'Sin descripción disponible.' }}
                </p>
              </div>

              <!-- Botón + menú -->
              <div class="flex items-center gap-2 shrink-0">
                <button @click="router.push({ name: 'disciplines-details', params: { id: discipline.id_disciplina } })"
                  class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100
                         text-xs font-bold text-slate-700 hover:bg-blue-50 hover:text-blue-600
                         transition-colors border border-slate-200 hover:border-blue-200">
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <path d="M14 2v6h6" />
                  </svg>
                  Ver detalles
                </button>
                <ActionMenu :items="buildMenuItems(discipline)" align="right" />
              </div>
            </div>
          </div>
        </div>
      </TransitionGroup>

    </div>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: NUEVA DISCIPLINA
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showNewModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="showNewModal = false">
          <Transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0">
            <div v-if="showNewModal"
              class="bg-white w-full max-w-lg rounded-4xl shadow-2xl flex flex-col max-h-[92vh] overflow-hidden">

              <!-- Cabecera -->
              <div class="flex items-center justify-between px-7 py-5 border-b border-slate-100">
                <div>
                  <h2 class="text-lg font-black text-slate-900 leading-tight">Nueva Disciplina</h2>
                  <p class="text-xs text-slate-500 font-medium mt-0.5">Registra un deporte o actividad del club.</p>
                </div>
                <button @click="showNewModal = false"
                  class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6L6 18M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Cuerpo -->
              <div class="overflow-y-auto p-7 space-y-5 bg-slate-50/30">

                <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-1"
                  enter-to-class="opacity-100 translate-y-0">
                  <div v-if="formError"
                    class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-2xl text-red-700 text-sm font-semibold">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                      stroke-width="2">
                      <circle cx="12" cy="12" r="10" />
                      <line x1="12" y1="8" x2="12" y2="12" />
                      <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    {{ formError }}
                  </div>
                </Transition>

                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">
                    Nombre <span class="text-red-400">*</span>
                  </label>
                  <input v-model="newDiscipline.nombre_disciplina" placeholder="Ej. Tenis, Natación…"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium
                           text-slate-900 placeholder:text-slate-400
                           focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">
                      Categoría <span class="text-red-400">*</span>
                    </label>
                    <Select v-model="newDiscipline.id_categoria"
                      :options="[{ label: 'Seleccionar…', value: null }, ...categories.map(c => ({ label: formatText(c.nombre), value: c.id_categoria }))]"
                      option-label="label" option-value="value" class="w-full" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Estatus</label>
                    <Select v-model="newDiscipline.estatus" :options="OPT_ESTATUS_FORM" option-label="label"
                      option-value="value" class="w-full" />
                  </div>
                </div>

                <div class="space-y-1.5">
                  <label
                    class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Descripción</label>
                  <textarea v-model="newDiscipline.descripcion" rows="4"
                    placeholder="Breve descripción de la actividad…"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium
                           text-slate-900 placeholder:text-slate-400 resize-none
                           focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all" />
                </div>
              </div>

              <!-- Pie -->
              <div class="flex items-center justify-end gap-3 px-7 py-4 border-t border-slate-100">
                <button @click="showNewModal = false"
                  class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                  Cancelar
                </button>
                <button @click="saveNewDiscipline" :disabled="isSaving"
                  class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2">
                  <svg v-if="isSaving" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                  </svg>
                  {{ isSaving ? 'Guardando…' : 'Crear Disciplina' }}
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: INSTRUCTORES ASIGNADOS
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showInstructorsModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="showInstructorsModal = false">
          <div class="bg-white w-full max-w-md rounded-4xl shadow-2xl flex flex-col max-h-[80vh] overflow-hidden">

            <!-- Cabecera -->
            <div class="flex items-center justify-between px-7 py-5 border-b border-slate-100">
              <div>
                <h2 class="text-lg font-black text-slate-900 leading-tight">Instructores Asignados</h2>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Instructores vinculados a esta disciplina</p>
              </div>
              <button @click="showInstructorsModal = false"
                class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M18 6L6 18M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Cuerpo -->
            <div class="overflow-y-auto p-6 bg-slate-50/30">

              <!-- Cargando -->
              <div v-if="isFetchingInstructors" class="flex justify-center py-12">
                <div class="w-8 h-8 rounded-full border-2 border-slate-200 border-t-blue-600 animate-spin" />
              </div>

              <!-- Vacío — mismo esqueleto que Miembros Familiares -->
              <div v-else-if="!selectedInstructors.length" class="flex flex-col items-center justify-center py-14 text-center
                       bg-white rounded-2xl border-2 border-dashed border-slate-200">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                  <svg class="w-7 h-7 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                </div>
                <p class="text-sm font-bold text-slate-700">Sin instructores asignados</p>
                <p class="text-xs text-slate-400 mt-1 max-w-[200px]">
                  Ningún instructor está vinculado a esta disciplina todavía.
                </p>
              </div>

              <!-- Lista -->
              <div v-else class="flex flex-col gap-2.5">
                <div v-for="ins in selectedInstructors" :key="ins.id_instructor" class="flex items-center gap-4 p-4 rounded-2xl bg-white border border-slate-200
                         hover:border-blue-200 hover:shadow-sm transition-all group">
                  <div class="w-10 h-10 rounded-xl bg-linear-to-br from-indigo-400 to-indigo-600
                              text-white flex items-center justify-center font-bold text-sm shrink-0
                              group-hover:scale-105 transition-transform">
                    {{ ins.nombre_completo?.charAt(0) ?? '?' }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-900 truncate">{{ ins.nombre_completo }}</p>
                    <p class="text-xs text-slate-500 font-medium">ID #{{ ins.id_instructor }}</p>
                  </div>
                  <div class="w-2 h-2 rounded-full bg-emerald-400 shrink-0" />
                </div>
              </div>
            </div>

            <!-- Pie -->
            <div class="px-7 py-4 border-t border-slate-100 flex justify-end">
              <button @click="showInstructorsModal = false"
                class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: CONFIRMAR DESHABILITAR
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showDisableModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="showDisableModal = false">
          <div class="bg-white w-full max-w-md rounded-4xl shadow-2xl p-8 text-center">
            <div class="w-16 h-16 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center mx-auto mb-5">
              <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v4M12 17h.01" />
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
              </svg>
            </div>
            <h3 class="text-xl font-black text-slate-900 mb-2">¿Deshabilitar disciplina?</h3>
            <p class="text-sm text-slate-500 mb-8">
              ¿Confirmas deshabilitar <span class="font-bold text-slate-800">{{ selectedDiscipline?.nombre_disciplina
              }}</span>?
              El sistema validará que no haya sesiones o torneos activos.
            </p>
            <div class="flex gap-3">
              <button @click="showDisableModal = false"
                class="flex-1 py-3 rounded-2xl border border-slate-200 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                Cancelar
              </button>
              <button @click="confirmDisable" :disabled="isSaving"
                class="flex-1 py-3 rounded-2xl bg-red-600 hover:bg-red-700 text-white text-sm font-bold transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                <svg v-if="isSaving" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2.5">
                  <path d="M21 12a9 9 0 1 1-6.219-8.56" />
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
