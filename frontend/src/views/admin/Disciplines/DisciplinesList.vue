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
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/modal/AppModal.vue'
import AppField from '@/components/ui/form/AppField.vue'
import AppInput from '@/components/ui/form/AppInput.vue'
import AppSelect from '@/components/ui/form/AppSelect.vue'
import IconPlus from '@/components/icons/IconPlus.vue'
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
  { label: 'Todas', value: null },
  ...categories.value.map(c => ({ label: formatCategoryEnum(c.nombre), value: c.id_categoria })),
])
const OPT_STATUS = [
  { label: 'Todos', value: null },
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
const activeFiltersCount = computed(() => [listFilters.value.categoria, listFilters.value.estatus].filter(Boolean).length)

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

const submitted = ref(false)
const categoryOptsForm = computed(() => categories.value.map(c => ({ label: formatText(c.nombre), value: c.id_categoria })))

const pickIcon = (icon) => {
  newDiscipline.value.icono = icon
  showIconSelectorModal.value = false
}

const openNewModal = () => {
  newDiscipline.value = EMPTY_DISCIPLINE()
  formError.value = ''
  submitted.value = false
  showNewModal.value = true
}

const saveNewDiscipline = async () => {
  submitted.value = true
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
      <AdminPageHeader title="Disciplinas" subtitle="Gestiona los deportes y actividades del club." />

      <!-- Acción principal a la izquierda y el conteo justo a su derecha -->
      <div class="flex flex-wrap items-center gap-3 sm:gap-4">
        <AppButton class="shrink-0" @click="openNewModal">
          <template #icon><IconPlus class="h-4 w-4" /></template>
          Nueva Disciplina
        </AppButton>
        <AppButton variant="secondary" class="shrink-0" @click="router.push({ name: 'disciplines-categories' })">
          <template #icon><IconGrid class="h-4 w-4" /></template>
          Categorías
        </AppButton>
        <span class="min-w-0 whitespace-nowrap text-sm font-semibold text-surface-600">
          <span class="font-bold tabular-nums text-surface-900">{{ filteredDisciplines.length }}</span>
          de <span class="tabular-nums">{{ disciplines.length }}</span><span class="hidden sm:inline"> disciplinas</span>
        </span>
      </div>

      <!-- FILTROS -->
      <FilterContainer :has-active-filters="hasActiveFilters" :active-count="activeFiltersCount" @clear="clearFilters">
        <template #search>
          <SearchInput v-model="listFilters.search" placeholder="Buscar por nombre o categoría…" />
        </template>

        <FilterSelect label="Categoría" v-model="listFilters.categoria" :options="categoryOpts">
          <template #icon>
            <IconGrid />
          </template>
        </FilterSelect>

        <FilterSelect label="Estatus" v-model="listFilters.estatus" :options="OPT_STATUS">
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
          <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.5">
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
      <template v-else>
        <div class="hidden md:block bg-white rounded-2xl border border-surface-200 shadow-sm overflow-x-auto min-h-96">
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
                  <span
                    class="text-[10px] font-bold tracking-wider text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100 uppercase">
                    {{ formatCategoryEnum(discipline.categorias?.[0]?.nombre || discipline.categoria_disciplina || '—')
                    }}
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

        <!-- Tablas Móviles -->
        <div class="md:hidden flex flex-col gap-3">
          <div v-for="discipline in filteredDisciplines" :key="discipline.id_disciplina"
            class="bg-white border border-surface-200 rounded-2xl shadow-sm px-4 py-3">

            <div class="flex items-center gap-3.5">
              <!-- Barra lateral de acento con width reducido -->
              <div class="w-1 h-14 shrink-0 rounded-full"
                :class="categoryPalette(discipline.categorias?.[0]?.nombre || discipline.categoria_disciplina || '').left" />

              <!-- Icono agrandado proporcionalmente -->
              <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-sm shrink-0"
                :class="categoryPalette(discipline.categorias?.[0]?.nombre || discipline.categoria_disciplina || '').icon">
                <DisciplineIcon :name="discipline.nombre_disciplina" :icon="discipline.icono" class="w-7 h-7" />
              </div>

              <!-- Contenido central: Título y fila de categoría + estatus alineados a la izquierda -->
              <div class="min-w-0 flex-1 flex flex-col gap-2.5 justify-center">
                <!-- Fila 1: Título de la disciplina agrandado -->
                <p class="text-base sm:text-lg font-black text-surface-900 truncate leading-tight">
                  {{ discipline.nombre_disciplina }}
                </p>

                <!-- Fila 2: Categoría y Estatus juntos alineados hacia la izquierda -->
                <div class="flex items-center justify-start gap-2 flex-wrap">
                  <span
                    class="inline-flex items-center text-[10px] font-bold tracking-wider text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100 uppercase whitespace-nowrap">
                    {{ formatCategoryEnum(discipline.categorias?.[0]?.nombre || discipline.categoria_disciplina || '—') }}
                  </span>
                  <BadgeStatus :status="discipline.estatus" size="sm" />
                </div>
              </div>

              <!-- Botón de los tres puntos centrado verticalmente en la card -->
              <div class="self-center shrink-0">
                <ActionMenu :items="buildMenuItems(discipline)" align="right" />
              </div>
            </div>

            <!-- Descripción opcional compacta si existe -->
            <p v-if="discipline.descripcion" class="text-xs text-surface-500 line-clamp-2 mt-2 pt-2 border-t border-surface-100">
              {{ discipline.descripcion }}
            </p>
          </div>
        </div>

      </template>

    </div>

    <!-- MODAL: NUEVA DISCIPLINA -->
    <AppModal
      v-model="showNewModal"
      title="Nueva disciplina"
      subtitle="Registra un deporte o actividad del club"
      :icon="IconLayers"
      :persistent="isSaving"
    >
      <form id="new-discipline-form" class="flex flex-col gap-4" novalidate @submit.prevent="saveNewDiscipline">
        <div v-if="formError" role="alert"
          class="flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
          <IconAlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
          {{ formError }}
        </div>

        <AppField label="Nombre" for="disc-nombre" required>
          <AppInput id="disc-nombre" v-model="newDiscipline.nombre_disciplina" :icon="IconLayers" placeholder="Ej. Tenis, Natación…"
            :invalid="submitted && !newDiscipline.nombre_disciplina.trim()" />
        </AppField>

        <AppField label="Icono de la disciplina">
          <button type="button"
            class="flex w-full cursor-pointer items-center justify-between gap-3 rounded-xl border border-surface-200 bg-white p-3 text-left transition-colors hover:border-surface-300 hover:bg-surface-50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-100"
            @click="showIconSelectorModal = true">
            <span class="flex min-w-0 items-center gap-3">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                <DisciplineIcon :name="newDiscipline.icono || 'default'" class="h-6 w-6" />
              </span>
              <span class="min-w-0">
                <span class="block truncate text-sm font-bold text-surface-900">{{ newDiscipline.icono ? iconLabel(newDiscipline.icono) : 'Automático' }}</span>
                <span class="block text-xs font-medium text-surface-500">Toca para elegir otro icono</span>
              </span>
            </span>
            <IconChevronDown class="h-4 w-4 shrink-0 -rotate-90 text-surface-400" />
          </button>
        </AppField>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <AppField label="Categoría" required>
            <AppSelect v-model="newDiscipline.categorias_ids[0]" :options="categoryOptsForm" placeholder="Seleccionar…"
              :invalid="submitted && !newDiscipline.categorias_ids[0]" />
          </AppField>
          <AppField label="Estatus">
            <AppSelect v-model="newDiscipline.estatus" :options="OPT_ESTATUS_FORM" />
          </AppField>
        </div>
      </form>

      <template #footer>
        <AppButton variant="secondary" :disabled="isSaving" @click="showNewModal = false">Cancelar</AppButton>
        <AppButton type="submit" form="new-discipline-form" :loading="isSaving">Crear disciplina</AppButton>
      </template>
    </AppModal>

    <!-- MODAL SECUNDARIO: SELECTOR DE ICONO -->
    <AppModal v-model="showIconSelectorModal" title="Seleccionar icono" subtitle="Iconos ya utilizados en el sistema" :icon="IconGrid">
      <div class="grid grid-cols-4 gap-2.5 sm:grid-cols-5">
        <button type="button" title="Automático" :aria-pressed="newDiscipline.icono === ''"
          class="group relative flex aspect-square cursor-pointer items-center justify-center rounded-2xl border-2 p-2 transition-all focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-100"
          :class="newDiscipline.icono === '' ? 'border-primary-600 bg-primary-600 text-white' : 'border-surface-200 bg-white text-surface-700 hover:border-primary-300 hover:bg-primary-50'"
          @click="pickIcon('')">
          <DisciplineIcon name="default" class="h-7 w-7" />
        </button>
        <button v-for="icon in usedIcons" :key="icon" type="button" :title="iconLabel(icon)" :aria-pressed="newDiscipline.icono === icon"
          class="group relative flex aspect-square cursor-pointer items-center justify-center rounded-2xl border-2 p-2 transition-all focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-100"
          :class="newDiscipline.icono === icon ? 'border-primary-600 bg-primary-600 text-white' : 'border-surface-200 bg-white text-surface-700 hover:border-primary-300 hover:bg-primary-50'"
          @click="pickIcon(icon)">
          <DisciplineIcon :name="icon" class="h-7 w-7" />
        </button>
      </div>
      <template #footer>
        <AppButton variant="secondary" @click="showIconSelectorModal = false">Cerrar</AppButton>
      </template>
    </AppModal>

    <!-- ══════════════════════════════════════════════════════════
         MODAL: CAMBIAR ESTATUS
    ══════════════════════════════════════════════════════════ -->
    <CambiarEstatusModal v-model="showStatusModal" :discipline="selectedStatusDiscipline"
      @status-updated="handleStatusUpdated" />

  </main>
</template>
