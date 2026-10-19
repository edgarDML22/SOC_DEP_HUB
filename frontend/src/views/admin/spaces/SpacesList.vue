<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useSpacesStore } from '@/stores/admin/spaces'
import { useDisciplinesStore } from '@/stores/admin/disciplines'
import { useAlerts } from '@/composables/useAlerts'


import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import SearchInput from '@/components/gerente/ui/SearchInput.vue'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import AppModal from '@/components/ui/modal/AppModal.vue'
import AppConfirmModal from '@/components/ui/modal/AppConfirmModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import SpaceFormFields from '@/components/admin/spaces/SpaceFormFields.vue'
import { emptySpaceForm, validateSpaceForm } from '@/components/admin/spaces/spaceForm'
import FilterContainer from '@/components/gerente/ui/FilterContainer.vue'
import FilterSelect from '@/components/gerente/ui/FilterSelect.vue'
import { IconLayers, IconAlertCircle, IconWarning } from '@/components/icons'
import IconPlus from '@/components/icons/IconPlus.vue'
import ModificarEstatusModal from '@/components/admin/ModificarEstatusModal.vue'
import ManageDisciplinesModal from '@/views/admin/Disciplines/ManageDisciplinesModal.vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'

const router = useRouter()
const spacesStore = useSpacesStore()
const disciplinesStore = useDisciplinesStore()
const { toastSuccess, toastError } = useAlerts()

const { spaces, isLoading, listFilters } = storeToRefs(spacesStore)
const { disciplines } = storeToRefs(disciplinesStore)

const OPT_TIPO = [
  { label: 'Todos', value: null },
  { label: 'Reserva On Demand', value: 'RESERVA_ON_DEMAND' },
  { label: 'Clase Programada', value: 'CLASE_PROGRAMADA' },
  { label: 'Uso Libre', value: 'USO_LIBRE' },
]
const OPT_STATUS = [
  { label: 'Todos', value: null },
  { label: 'Activo', value: 'ACTIVO' },
  { label: 'Mantenimiento', value: 'MANTENIMIENTO' },
  { label: 'Deshabilitado', value: 'DESHABILITADO' },
]

const filteredSpaces = computed(() => {
  let r = [...spaces.value]
  const f = listFilters.value
  if (f.search) {
    const q = f.search.toLowerCase()
    r = r.filter(s => s.nombre_espacio.toLowerCase().includes(q))
  }
  if (f.tipo === 'RESERVA_ON_DEMAND') r = r.filter(s => s.es_reserva_on_demand)
  else if (f.tipo === 'CLASE_PROGRAMADA') r = r.filter(s => s.es_clase_programada)
  else if (f.tipo === 'USO_LIBRE') r = r.filter(s => s.es_uso_libre)
  if (f.estatus) r = r.filter(s => s.estatus === f.estatus)
  return r.sort((a, b) => a.nombre_espacio.localeCompare(b.nombre_espacio))
})

const hasActiveFilters = computed(() => listFilters.value.search || listFilters.value.tipo || listFilters.value.estatus)
const clearFilters = () => {
  listFilters.value.search = ''
  listFilters.value.tipo = listFilters.value.estatus = null
}
const activeFiltersCount = computed(() => [listFilters.value.tipo, listFilters.value.estatus].filter(Boolean).length)

// ── FRANJA DE COLOR ESTATUS ────────────────────────────────────
const statusAccentLeft = (estatus) => ({
  ACTIVO: 'bg-emerald-500',
  MANTENIMIENTO: 'bg-amber-500',
  DESHABILITADO: 'bg-red-500',
}[estatus] ?? 'bg-surface-300')

// ── BADGES DE TIPO ─────────────────────────────────────────────
const tipoBadges = (space) => {
  const b = []
  if (space.es_reserva_on_demand) b.push({ label: 'On Demand', classes: 'bg-indigo-50 text-indigo-700 border-indigo-100' })
  if (space.es_clase_programada) b.push({ label: 'Clase', classes: 'bg-blue-50 text-blue-700 border-blue-100' })
  if (space.es_uso_libre) b.push({ label: 'Uso Libre', classes: 'bg-emerald-50 text-emerald-700 border-emerald-100' })
  return b
}

// ── MENU ITEMS ─────────────────────────────────────────────────
const showEstatusModal = ref(false)
const spaceToEdit = ref(null)
const showDisciplinesModal = ref(false)
const selectedSpace = ref(null)

const openEstatusModal = (space) => {
  spaceToEdit.value = space
  showEstatusModal.value = true
}

const buildMenuItems = (space) => [
  {
    label: 'Ver Detalles',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
               <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
               <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
             </svg>`,
    action: () => {
      if (typeof spacesStore.setCurrentSpace === 'function') {
        spacesStore.setCurrentSpace(space);
      } else {
        spacesStore.currentSpace = space;
      }
      router.push({ name: 'spaces-details', params: { id: space.id_espacio } });
    },
  },
  {
    label: 'Gestionar Disciplinas',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
               <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
               <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
               <line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>
             </svg>`,
    action: () => {
      selectedSpace.value = space;
      showDisciplinesModal.value = true;
    },
  },
  {
    label: 'Modificar Estatus',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
               <circle cx="12" cy="12" r="10"/>
               <line x1="12" y1="8" x2="12" y2="12"/>
               <line x1="12" y1="16" x2="12.01" y2="16"/>
             </svg>`,
    action: () => openEstatusModal(space),
  }
]

// ── MODAL: NUEVO ESPACIO ───────────────────────────────────────
const showNewModal = ref(false)
const isSaving = ref(false)
const formError = ref('')
const formErrors = ref({})
const newSpace = ref(emptySpaceForm())

// Al corregir un campo marcado, su error desaparece
watch(newSpace, () => {
  const prev = formErrors.value
  if (!Object.keys(prev).length) return
  formErrors.value = Object.fromEntries(Object.entries(validateSpaceForm(newSpace.value)).filter(([key]) => key in prev))
}, { deep: true })

const openNewModal = () => {
  newSpace.value = emptySpaceForm()
  formError.value = ''
  formErrors.value = {}
  showNewModal.value = true
}

const saveNewSpace = async () => {
  formError.value = ''
  formErrors.value = validateSpaceForm(newSpace.value)
  if (Object.keys(formErrors.value).length) return
  isSaving.value = true
  const res = await spacesStore.createSpace(newSpace.value)
  isSaving.value = false
  if (res?.success) {
    showNewModal.value = false
    toastSuccess(`${newSpace.value.nombre_espacio.trim()} fue registrado`)
  } else {
    formError.value = res?.error ?? 'Ocurrió un error al crear el espacio.'
  }
}

// ── MODAL: DESHABILITAR ────────────────────────────────────────
const showDisableModal = ref(false)

const openDisableModal = (space) => {
  selectedSpace.value = space
  showDisableModal.value = true
}

const confirmDisable = async () => {
  if (!selectedSpace.value) return
  isSaving.value = true
  const res = await spacesStore.deleteSpace(selectedSpace.value.id_espacio)
  isSaving.value = false
  if (res?.success) {
    showDisableModal.value = false
    toastSuccess(`${selectedSpace.value.nombre_espacio} fue deshabilitado`)
  } else {
    toastError(res?.error ?? 'No se pudo deshabilitar el espacio')
  }
}

// ── INIT ──────────────────────────────────────────────────────
onMounted(() => {
  spacesStore.fetchSpaces()
  disciplinesStore.fetchDisciplines()
})
</script>

<template>
  <main class="min-h-screen bg-surface-50 px-4 py-5 pb-16 font-sans sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-5 sm:space-y-6">

      <!-- CABECERA -->
      <AdminPageHeader title="Espacios Físicos" subtitle="Gestiona las canchas, salones e instalaciones del club." />

      <!-- Acción principal a la izquierda y el conteo justo a su derecha -->
      <div class="flex items-center gap-3 sm:gap-4">
        <AppButton class="shrink-0" @click="openNewModal">
          <template #icon><IconPlus class="h-4 w-4" /></template>
          Nuevo Espacio
        </AppButton>
        <span class="min-w-0 whitespace-nowrap text-sm font-semibold text-surface-600">
          <span class="font-bold tabular-nums text-surface-900">{{ filteredSpaces.length }}</span>
          de <span class="tabular-nums">{{ spaces.length }}</span><span class="hidden sm:inline"> espacios</span>
        </span>
      </div>

      <!-- BARRA DE FILTROS -->
      <FilterContainer :has-active-filters="hasActiveFilters" :active-count="activeFiltersCount" @clear="clearFilters">
        <template #search>
          <SearchInput v-model="listFilters.search" placeholder="Buscar espacio por nombre…" />
        </template>

        <FilterSelect label="Tipo de uso" v-model="listFilters.tipo" :options="OPT_TIPO">
          <template #icon>
            <IconLayers />
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
        <TableSkeleton :rows="4" :columns="3" :has-avatar="true" />
      </div>

      <!-- VACÍO -->
      <div v-else-if="filteredSpaces.length === 0" class="bg-white rounded-2xl border-2 border-dashed border-surface-200 p-16
               flex flex-col items-center justify-center text-center">
        <div class="w-16 h-16 rounded-2xl bg-surface-100 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.5">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
            <polyline points="9 22 9 12 15 12 15 22" />
          </svg>
        </div>
        <h3 class="text-base font-black text-surface-900">Sin resultados</h3>
        <p class="text-sm text-surface-500 mt-1 max-w-xs">No se encontraron espacios con los filtros actuales.</p>
        <button @click="clearFilters" class="mt-4 text-sm font-bold text-blue-600 hover:underline">Limpiar
          filtros</button>
      </div>

      <!-- TABLA + TARJETAS -->
      <template v-else>

        <!-- TABLA (desktop) -->
        <div class="hidden lg:block bg-white rounded-2xl border border-surface-200 shadow-sm overflow-x-auto min-h-96">
          <table class="w-full text-sm text-left text-slate-600">
            <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
              <tr>
                <th scope="col" class="px-6 py-4 text-left font-extrabold rounded-tl-2xl">Espacio</th>
                <th scope="col" class="px-6 py-4 text-left font-extrabold hidden md:table-cell">Tipo de Uso</th>
                <th scope="col" class="px-6 py-4 text-left font-extrabold hidden lg:table-cell">Disciplinas Permitidas
                </th>
                <th scope="col" class="px-6 py-4 text-left font-extrabold">Estatus</th>
                <th scope="col" class="px-6 py-4 text-right font-extrabold rounded-tr-2xl">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
              <tr v-for="space in filteredSpaces" :key="space.id_espacio"
                class="bg-white border-b border-surface-100 hover:bg-surface-50/50 transition-colors group">

                <!-- Espacio, Capacidad, e Icono -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-1.5 h-10 shrink-0 rounded-full" :class="statusAccentLeft(space.estatus)" />
                    <div
                      class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm shrink-0">
                      <DisciplineIcon :name="space.nombre_espacio" class="w-5 h-5" />
                    </div>
                    <div>
                      <h3 class="font-bold text-surface-900 leading-tight">
                        {{ space.nombre_espacio }}
                      </h3>
                      <p class="text-[10px] font-bold text-surface-400 mt-0.5">
                        {{ space.capacidad_maxima ?? '—' }} pers.
                      </p>
                    </div>
                  </div>
                </td>

                <!-- Tipo de Uso -->
                <td class="px-6 py-4 hidden md:table-cell">
                  <div class="flex flex-wrap items-center gap-1.5">
                    <span v-for="badge in tipoBadges(space)" :key="badge.label"
                      class="text-[10px] font-bold px-2 py-0.5 rounded-lg border uppercase tracking-wide"
                      :class="badge.classes">
                      {{ badge.label }}
                    </span>
                  </div>
                </td>

                <!-- Disciplinas -->
                <td class="px-6 py-4 hidden lg:table-cell">
                  <div v-if="space.disciplinas?.length" class="flex flex-wrap items-center gap-1.5">
                    <span v-for="d in space.disciplinas.slice(0, 3)" :key="d.id_disciplina"
                      class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-primary-50 text-primary-700 border border-primary-100 text-[10px] font-bold">
                      <DisciplineIcon :name="d.nombre_disciplina" class="w-3.5 h-3.5 shrink-0" />
                      {{ d.nombre_disciplina }}
                    </span>
                    <span v-if="space.disciplinas.length > 3"
                      class="px-2 py-1 rounded-lg bg-surface-100 text-surface-500 text-[10px] font-bold">
                      +{{ space.disciplinas.length - 3 }} más
                    </span>
                  </div>
                  <span v-else class="text-xs text-surface-400 italic">Sin disciplinas</span>
                </td>

                <!-- Estatus -->
                <td class="px-6 py-4">
                  <BadgeStatus :status="space.estatus" size="sm" />
                </td>

                <!-- Acciones -->
                <td class="px-6 py-4 text-right">
                  <ActionMenu :items="buildMenuItems(space)" align="right" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- TARJETAS (móvil / tablet) -->
        <div class="lg:hidden flex flex-col gap-3">
          <div v-for="space in filteredSpaces" :key="space.id_espacio"
            class="bg-white border border-surface-200 rounded-2xl shadow-sm p-4 space-y-3">

            <!-- Fila 1: franja de estatus, icono, nombre, capacidad y acciones -->
            <div class="flex items-center gap-3">
              <div class="w-1.5 h-10 shrink-0 rounded-full" :class="statusAccentLeft(space.estatus)" />
              <div
                class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm shrink-0">
                <DisciplineIcon :name="space.nombre_espacio" class="w-5 h-5" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-surface-900 truncate leading-tight">
                  {{ space.nombre_espacio }}
                </p>
                <p class="text-xs font-bold text-surface-400 mt-0.5">
                  {{ space.capacidad_maxima ?? '—' }} pers.
                </p>
              </div>
              <ActionMenu :items="buildMenuItems(space)" align="right" />
            </div>

            <!-- Fila 2: Disciplinas a la izquierda + Estatus y modalidad cargados a la derecha -->
            <div class="flex items-center justify-between gap-2 flex-wrap pt-0.5">
              <!-- Izquierda: Disciplinas -->
              <div class="flex items-center gap-1.5 flex-wrap min-w-0 flex-1">
                <template v-if="space.disciplinas?.length">
                  <span v-for="d in space.disciplinas.slice(0, 2)" :key="d.id_disciplina"
                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-primary-50 text-primary-700 border border-primary-100 text-[10px] font-bold">
                    <DisciplineIcon :name="d.nombre_disciplina" class="w-3.5 h-3.5 shrink-0" />
                    {{ d.nombre_disciplina }}
                  </span>
                  <span v-if="space.disciplinas.length > 2"
                    class="px-2 py-1 rounded-lg bg-surface-100 text-surface-500 text-[10px] font-bold">
                    +{{ space.disciplinas.length - 2 }} más
                  </span>
                </template>
                <span v-else class="text-xs text-surface-400 italic">Sin disciplinas</span>
              </div>

              <!-- Derecha: Tipo de uso (modalidad) y Estatus -->
              <div class="flex items-center justify-end gap-2 shrink-0 ml-auto flex-wrap">
                <span v-for="badge in tipoBadges(space)" :key="badge.label"
                  class="text-[10px] font-bold px-2 py-0.5 rounded-lg border uppercase tracking-wide"
                  :class="badge.classes">
                  {{ badge.label }}
                </span>
                <BadgeStatus :status="space.estatus" size="sm" />
              </div>
            </div>

          </div>
        </div>

      </template>

    </div>

    <!-- MODAL: NUEVO ESPACIO -->
    <AppModal v-model="showNewModal" title="Nuevo espacio" subtitle="Registra una cancha, salón o instalación"
      :icon="IconLayers" size="lg" :persistent="isSaving">
      <form id="space-create-form" class="flex flex-col gap-4" novalidate @submit.prevent="saveNewSpace">
        <div v-if="formError" role="alert"
          class="flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
          <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" /><path d="M12 8v4M12 16h.01" /></svg>
          {{ formError }}
        </div>
        <SpaceFormFields v-model="newSpace" :errors="formErrors" :disciplines="disciplines" show-disciplinas />
      </form>
      <template #footer>
        <AppButton variant="secondary" :disabled="isSaving" @click="showNewModal = false">Cancelar</AppButton>
        <AppButton type="submit" form="space-create-form" :loading="isSaving">Crear espacio</AppButton>
      </template>
    </AppModal>

    <!-- MODAL: CONFIRMAR DESHABILITAR -->
    <AppConfirmModal v-model="showDisableModal" title="¿Deshabilitar espacio?" :icon="IconWarning" tone="rose"
      confirm-label="Sí, deshabilitar" :loading="isSaving" @confirm="confirmDisable">
      Se deshabilitará <strong class="text-surface-900">{{ selectedSpace?.nombre_espacio }}</strong>.
      El sistema validará que no tenga actividades pendientes.
    </AppConfirmModal>

    <ModificarEstatusModal :show="showEstatusModal" :space="spaceToEdit" @close="showEstatusModal = false" />

    <ManageDisciplinesModal :show="showDisciplinesModal" type="space" :item="selectedSpace"
      @close="showDisciplinesModal = false" @saved="spacesStore.fetchSpaces()" />

  </main>
</template>