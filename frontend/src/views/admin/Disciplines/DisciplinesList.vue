<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useDisciplinesStore } from '@/stores/admin/disciplines'
import { useCategoryStore } from '@/stores/admin/categoryStore'
import { useAlerts } from '@/composables/useAlerts'
import { useformat } from '@/utils/formatters'
import { 
  AdminPageHeader, BadgeStatus, ActionMenu, SearchInput, 
  LoadingSpinner, ConfirmButton, CancelButton,
  FilterContainer, FilterSelect
} from '@/components/gerente/ui'

import CambiarEstatusModal from '@/components/admin/disciplines/CambiarEstatusModal.vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import { IconLayers, IconChevronDown, IconGrid, IconAlertCircle } from '@/components/icons'

const router = useRouter()
const disciplinesStore = useDisciplinesStore()
const categoryStore = useCategoryStore()
const { formatText, formatCategoryEnum } = useformat()
const { toastInfo } = useAlerts()

const { disciplines, isLoading, listFilters } = storeToRefs(disciplinesStore)
const { categories: storeCategories } = storeToRefs(categoryStore)

const categories = computed(() =>
  (storeCategories.value || []).filter(c => c.nombre !== 'INFANTIL')
)
const categoryOpts = computed(() => [
  { label: 'Todas las categorías', value: null },
  ...categories.value.map(c => ({ label: formatCategoryEnum(c.nombre), value: c.id_categoria })),
])
const OPT_STATUS = [
  { label: 'Todos los estados', value: null },
  { label: 'Activo', value: 'ACTIVO' },
  { label: 'En Pausa', value: 'PAUSA' },
  { label: 'Cancelado', value: 'CANCELADO' },
]

const filteredDisciplines = computed(() => {
  let r = [...disciplines.value]
  const f = listFilters.value
  if (f.search) {
    const q = f.search.toLowerCase()
    r = r.filter(d => {
      // La API devuelve categorias como array (many-to-many)
      const catName = d.categorias?.[0]?.nombre || d.categoria_disciplina || ''
      return d.nombre_disciplina.toLowerCase().includes(q) || catName.toLowerCase().includes(q)
    })
  }
  if (f.estatus) r = r.filter(d => d.estatus === f.estatus)
  if (f.categoria) {
    r = r.filter(d => d.categorias?.some(c => String(c.id_categoria) === String(f.categoria)))
  }
  return r.sort((a, b) => {
    const catA = a.categorias?.[0]?.nombre || a.categoria_disciplina || ''
    const catB = b.categorias?.[0]?.nombre || b.categoria_disciplina || ''
    const c = catA.localeCompare(catB)
    return c !== 0 ? c : a.nombre_disciplina.localeCompare(b.nombre_disciplina)
  })
})

const hasActiveFilters = computed(() => listFilters.value.search || listFilters.value.estatus || listFilters.value.categoria)
const clearFilters = () => {
  listFilters.value.search = ''
  listFilters.value.estatus = listFilters.value.categoria = null
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
    action: () => {
      if (typeof disciplinesStore.setCurrentDiscipline === 'function') {
        disciplinesStore.setCurrentDiscipline(discipline);
      } else {
        disciplinesStore.currentDiscipline = discipline;
      }
      router.push({ name: 'disciplines-details', params: { id: discipline.id_disciplina } });
    },
  },
  {
    label: 'Cambiar estatus',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
           </svg>`,
    action: () => openStatusModal(discipline),
  }
]

// ── MODAL: NUEVA DISCIPLINA ────────────────────────────────────
const showNewModal = ref(false)
const showIconSelectorModal = ref(false)
const isSaving = ref(false)
const formError = ref('')

const EMPTY_DISCIPLINE = () => ({ nombre_disciplina: '', categorias_ids: [null], estatus: 'ACTIVO', icono: '' })
const newDiscipline = ref(EMPTY_DISCIPLINE())

const usedIcons = computed(() => {
  const icons = new Set();
  disciplines.value.forEach(d => {
    if (d.icono) {
      icons.add(d.icono);
      return;
    }
    const name = d.nombre_disciplina.toLowerCase();
    let resolved = null;
    if (name.includes('basquetbol') || name.includes('baloncesto') || name.includes('basketball')) resolved = 'basquetbol';
    else if (name.includes('frontenis')) resolved = 'frontenis';
    else if (name.includes('futbol') || name.includes('fútbol') || name.includes('soccer')) resolved = 'futbol';
    else if (name.includes('padel') || name.includes('pádel')) resolved = 'padel';
    else if (name.includes('squash')) resolved = 'squash';
    else if (name.includes('tenis') || name.includes('tennis')) resolved = 'tenis';
    else if (name.includes('voleibol') || name.includes('volleyball')) resolved = 'voleibol';
    else if (name.includes('aerobics') || name.includes('acondicionamiento') || name.includes('entrenamiento')) resolved = 'aerobics';
    else if (name.includes('jazz')) resolved = 'jazz';
    else if (name.includes('zumba')) resolved = 'zumba';
    else if (name.includes('baile')) resolved = 'baile';
    else if (name.includes('meditación') || name.includes('meditacion')) resolved = 'meditacion';
    else if (name.includes('pilates')) resolved = 'pilates';
    else if (name.includes('barre')) resolved = 'barre';
    else if (name.includes('yoga')) resolved = 'yoga';
    else if (name.includes('columna')) resolved = 'columna';
    else if (name.includes('gym') || name.includes('gimnasio') || name.includes('pesas') || name.includes('fuerza') || name.includes('crossfit') || name.includes('funcional')) resolved = 'gym';
    else if (name.includes('tae kwon do') || name.includes('artes marciales') || name.includes('karate') || name.includes('box')) resolved = 'martialarts';
    else if (name.includes('spinning') || name.includes('bici')) resolved = 'spinning';
    else if (name.includes('gimnasia')) resolved = 'gimnasia';
    else if (name.includes('natación') || name.includes('natacion') || name.includes('acuatico') || name.includes('acuático') || name.includes('alberca')) resolved = 'natacion';
    else if (name.includes('ludoteca')) resolved = 'ludoteca';
    
    if (resolved) {
      icons.add(resolved);
    }
  });
  
  if (icons.size === 0) {
    const fallback = ['futbol', 'basquetbol', 'tenis', 'voleibol', 'yoga', 'gym', 'natacion', 'spinning'];
    return fallback.sort((a, b) => iconLabel(a).localeCompare(iconLabel(b), 'es', { sensitivity: 'base' }));
  }
  
  const list = Array.from(icons);
  return list.sort((a, b) => iconLabel(a).localeCompare(iconLabel(b), 'es', { sensitivity: 'base' }));
});

const iconLabel = (iconName) => {
  const labels = {
    futbol: 'Fútbol',
    basquetbol: 'Basquetbol',
    tenis: 'Tenis',
    voleibol: 'Voleibol',
    squash: 'Squash',
    frontenis: 'Frontenis',
    padel: 'Pádel',
    aerobics: 'Aerobics',
    jazz: 'Jazz',
    zumba: 'Zumba',
    baile: 'Baile',
    meditacion: 'Meditación',
    pilates: 'Pilates',
    barre: 'Barre',
    yoga: 'Yoga',
    columna: 'Higiene Col.',
    gym: 'Gimnasio',
    martialarts: 'Artes Marc.',
    spinning: 'Spinning',
    gimnasia: 'Gimnasia',
    natacion: 'Natación',
    ludoteca: 'Ludoteca'
  };
  return labels[iconName] || iconName;
};

const OPT_ESTATUS_FORM = [
  { label: 'Activo', value: 'ACTIVO' },
  { label: 'En Pausa', value: 'PAUSA' },
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
  if (!newDiscipline.value.categorias_ids[0]) {
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

// ── MODAL: CAMBIAR ESTATUS ─────────────────────────────────────
const showStatusModal = ref(false)
const selectedStatusDiscipline = ref(null)

const openStatusModal = (discipline) => {
  selectedStatusDiscipline.value = discipline
  showStatusModal.value = true
}

const handleStatusUpdated = () => {
  disciplinesStore.fetchDisciplines()
}

// ── INIT ──────────────────────────────────────────────────────
onMounted(() => {
  disciplinesStore.fetchDisciplines()
  categoryStore.fetchCategories()
})
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader title="Disciplinas" subtitle="Gestiona los deportes y actividades del club.">
        <span class="text-sm font-bold text-surface-500">
          {{ filteredDisciplines.length }}
          <span class="font-medium text-surface-400">de {{ disciplines.length }}</span>
        </span>
        <button @click="router.push({ name: 'disciplines-categories' })" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-surface-200 shadow-sm
                 text-sm font-bold text-surface-700 hover:bg-surface-50 transition-colors">
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
      <FilterContainer :hasActiveFilters="hasActiveFilters" @clear="clearFilters">
        <template #search>
          <SearchInput v-model="listFilters.search" placeholder="Buscar por nombre o categoría…" />
        </template>

        <FilterSelect
          label="Categoría"
          v-model="listFilters.categoria"
          :options="categoryOpts"
        >
          <template #icon>
            <IconGrid />
          </template>
        </FilterSelect>

        <FilterSelect
          label="Estatus"
          v-model="listFilters.estatus"
          :options="OPT_STATUS"
        >
          <template #icon>
            <IconAlertCircle />
          </template>
        </FilterSelect>
      </FilterContainer>

      <!-- SKELETON -->
      <div v-if="isLoading" class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">
        <TableSkeleton :rows="4" :columns="4" :has-avatar="true" />
      </div>

      <!-- VACÍO -->
      <div v-else-if="filteredDisciplines.length === 0" class="bg-white rounded-2xl border-2 border-dashed border-surface-200 p-16
               flex flex-col items-center justify-center text-center">
        <div class="w-16 h-16 rounded-2xl bg-surface-100 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1" />
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z" />
            <line x1="6" y1="1" x2="6" y2="4" />
            <line x1="10" y1="1" x2="10" y2="4" />
            <line x1="14" y1="1" x2="14" y2="4" />
          </svg>
        </div>
        <h3 class="text-base font-black text-surface-900">Sin resultados</h3>
        <p class="text-sm text-surface-500 mt-1 max-w-xs">No se encontraron disciplinas con los filtros actuales.</p>
        <button @click="clearFilters" class="mt-4 text-sm font-bold text-blue-600 hover:underline">Limpiar
          filtros</button>
      </div>

      <!-- TABLA -->
      <div v-else class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-x-auto min-h-96">
        <table class="w-full text-sm text-left text-slate-600">
          <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
            <tr>
              <th scope="col" class="px-6 py-4 text-left font-extrabold rounded-tl-2xl">Disciplina</th>
              <th scope="col" class="px-6 py-4 text-left font-extrabold hidden md:table-cell">Categoría</th>
              <th scope="col" class="px-6 py-4 text-left font-extrabold">Estatus</th>
              <th scope="col" class="px-6 py-4 text-right font-extrabold rounded-tr-2xl">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-100">
            <tr v-for="discipline in filteredDisciplines" :key="discipline.id_disciplina"
              class="bg-white border-b border-surface-100 hover:bg-surface-50/50 transition-colors group">
              
              <!-- Disciplina y Color -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-1.5 h-10 shrink-0 rounded-full"
                    :class="categoryPalette(discipline.categorias?.[0]?.nombre || discipline.categoria_disciplina || '').left" />
                  <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm shrink-0"
                    :class="categoryPalette(discipline.categorias?.[0]?.nombre || discipline.categoria_disciplina || '').icon">
                    <DisciplineIcon :name="discipline.nombre_disciplina" :icon="discipline.icono" class="w-5 h-5" />
                  </div>
                  <span class="font-bold text-surface-900 leading-tight">
                    {{ discipline.nombre_disciplina }}
                  </span>
                </div>
              </td>

              <!-- Categoría -->
              <td class="px-6 py-4 hidden md:table-cell">
                <span class="text-[10px] font-bold tracking-wider text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100 uppercase">
                  {{ formatCategoryEnum(discipline.categorias?.[0]?.nombre || discipline.categoria_disciplina || '—') }}
                </span>
              </td>

              <!-- Estatus -->
              <td class="px-6 py-4">
                <BadgeStatus :status="discipline.estatus" size="sm" />
              </td>

              <!-- Acciones -->
              <td class="px-6 py-4 text-right">
                <ActionMenu :items="buildMenuItems(discipline)" align="right" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: NUEVA DISCIPLINA
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showNewModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showNewModal = false">
          <Transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0">
            <div v-if="showNewModal"
              class="bg-white w-full max-w-lg rounded-4xl shadow-2xl flex flex-col max-h-[92vh] overflow-hidden font-sans">

              <!-- Cabecera -->
              <div class="flex items-center justify-between px-8 py-6 bg-white border-b border-surface-100">
                <div>
                  <h2 class="text-xl font-black text-surface-900 leading-tight">Nueva Disciplina</h2>
                  <p class="text-xs font-bold text-surface-500 mt-1 uppercase tracking-wider">
                    Registra un deporte o actividad del club.
                  </p>
                </div>
                <button @click="showNewModal = false"
                  class="w-10 h-10 rounded-xl bg-surface-100 hover:bg-surface-200 flex items-center justify-center text-surface-500 transition-colors">
                  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6L6 18M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Cuerpo -->
              <div class="overflow-y-auto p-7 space-y-5 bg-surface-50/30">

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
                  <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                    Nombre <span class="text-red-400">*</span>
                  </label>
                  <div class="relative">
                    <IconLayers class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                    <input v-model="newDiscipline.nombre_disciplina" placeholder="Ej. Tenis, Natación…"
                      class="w-full pl-11 pr-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-semibold
                             text-surface-900 placeholder:text-surface-400 shadow-sm
                             focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all" />
                  </div>
                </div>

                <!-- Selector de Icono (Toggle Row) -->
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Icono de la Disciplina</label>
                  <div @click="showIconSelectorModal = true" 
                       class="flex items-center justify-between p-3.5 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-100 hover:border-slate-300 transition-all select-none shadow-xs">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-primary-600 shadow-xs shrink-0">
                        <DisciplineIcon :name="newDiscipline.icono || 'default'" class="w-6 h-6" />
                      </div>
                      <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-800 leading-tight">{{ newDiscipline.icono ? iconLabel(newDiscipline.icono) : 'Ninguno (Automático)' }}</p>
                        <p class="text-[10px] font-medium text-slate-400 uppercase tracking-wider mt-0.5">Haz clic para cambiar o seleccionar</p>
                      </div>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                      Categoría <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                      <select v-model="newDiscipline.categorias_ids[0]"
                        class="w-full px-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-sm font-bold text-surface-900 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer shadow-xs">
                        <option :value="null">Seleccionar…</option>
                        <option v-for="c in categories" :key="c.id_categoria" :value="c.id_categoria">{{ formatText(c.nombre) }}</option>
                      </select>
                      <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estatus</label>
                    <div class="relative">
                      <select v-model="newDiscipline.estatus"
                        class="w-full px-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-sm font-bold text-surface-900 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer shadow-xs">
                        <option v-for="opt in OPT_ESTATUS_FORM" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                      </select>
                      <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                  </div>
                </div>


              </div>

              <!-- Pie -->
              <div class="flex items-center justify-end gap-3 px-7 py-4 border-t border-surface-100">
                <CancelButton @click="showNewModal = false" />
                <ConfirmButton
                  label="Crear Disciplina"
                  :loading="isSaving"
                  @click="saveNewDiscipline"
                />
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>



    <!-- ══════════════════════════════════════════════════════════
         MODAL SECUNDARIO: SELECTOR DE ICONO
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showIconSelectorModal"
          class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-xs"
          @click.self="showIconSelectorModal = false">
          <Transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0">
            <div v-if="showIconSelectorModal"
              class="bg-white w-full max-w-lg rounded-4xl shadow-2xl flex flex-col max-h-[85vh] overflow-hidden font-sans border border-surface-200">

              <!-- Cabecera -->
              <div class="flex items-center justify-between px-8 py-5 bg-white border-b border-surface-100 shrink-0">
                <div>
                  <h3 class="text-lg font-black text-surface-900 leading-tight">Seleccionar Icono</h3>
                  <p class="text-xs font-bold text-surface-500 mt-0.5 uppercase tracking-wider">
                    Iconos ya utilizados en el sistema
                  </p>
                </div>
                <button @click="showIconSelectorModal = false"
                  class="w-8 h-8 rounded-lg bg-surface-100 hover:bg-surface-200 flex items-center justify-center text-surface-500 transition-colors">
                  <svg class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6L6 18M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Cuerpo de Rejilla -->
              <div class="overflow-y-auto p-6 bg-surface-50/30 flex-1">
                <div class="grid grid-cols-4 sm:grid-cols-5 gap-3">
                  <!-- Tarjeta "Ninguno" para deseleccionar -->
                  <div
                    @click="newDiscipline.icono = ''; showIconSelectorModal = false"
                    title="Automático"
                    class="cursor-pointer group relative transform transition-all duration-300 rounded-2xl p-3 flex items-center justify-center border shadow-xs aspect-square"
                    :class="newDiscipline.icono === '' 
                      ? 'bg-primary-600 border-primary-600 text-white shadow-md' 
                      : 'bg-white border-surface-200 hover:-translate-y-1 hover:bg-primary-600 hover:border-primary-600 hover:text-white hover:shadow-xs'"
                  >
                    <DisciplineIcon name="default" class="w-8 h-8" />
                  </div>

                  <!-- Demás Iconos -->
                  <div
                    v-for="icon in usedIcons"
                    :key="icon"
                    @click="newDiscipline.icono = icon; showIconSelectorModal = false"
                    :title="iconLabel(icon)"
                    class="cursor-pointer group relative transform transition-all duration-300 rounded-2xl p-3 flex items-center justify-center border shadow-xs aspect-square"
                    :class="newDiscipline.icono === icon 
                      ? 'bg-primary-600 border-primary-600 text-white shadow-md' 
                      : 'bg-white border-surface-200 hover:-translate-y-1 hover:bg-primary-600 hover:border-primary-600 hover:text-white hover:shadow-xs'"
                  >
                    <!-- Ícono -->
                    <DisciplineIcon :name="icon"
                      class="w-8 h-8 transition-all duration-300"
                      :class="newDiscipline.icono === icon ? 'opacity-100' : 'opacity-70 group-hover:opacity-100'" />

                    <!-- Check flotante -->
                    <div v-if="newDiscipline.icono === icon"
                      class="absolute -top-1 -right-1 bg-white text-primary-600 w-5 h-5 rounded-full flex items-center justify-center border-[2px] border-primary-600 shadow-xs animate-scale-in">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="4.5" stroke-linecap="round"
                        stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12" />
                      </svg>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Pie -->
              <div class="flex items-center justify-end px-8 py-4 border-t border-surface-100 bg-white shrink-0">
                <button
                  type="button"
                  @click="showIconSelectorModal = false"
                  class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-black transition-all active:scale-95 shadow-sm"
                >
                  Cerrar
                </button>
              </div>

            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>



    <!-- ══════════════════════════════════════════════════════════
         MODAL: CAMBIAR ESTATUS
    ══════════════════════════════════════════════════════════ -->
    <CambiarEstatusModal 
      v-model="showStatusModal" 
      :discipline="selectedStatusDiscipline"
      @status-updated="handleStatusUpdated"
    />

  </main>
</template>
