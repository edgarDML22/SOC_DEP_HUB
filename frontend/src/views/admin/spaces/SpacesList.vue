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
import SearchInput     from '@/components/gerente/ui/SearchInput.vue'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import FilterContainer from '@/components/gerente/ui/FilterContainer.vue'
import FilterSelect from '@/components/gerente/ui/FilterSelect.vue'
import { IconLayers, IconAlertCircle, IconChevronDown, IconUser } from '@/components/icons'
import ModificarEstatusModal from '@/components/admin/ModificarEstatusModal.vue'
import ManageDisciplinesModal from '@/views/admin/Disciplines/ManageDisciplinesModal.vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'

const router           = useRouter()
const spacesStore      = useSpacesStore()
const disciplinesStore = useDisciplinesStore()
const { toastInfo }    = useAlerts()

const { spaces, isLoading, listFilters }      = storeToRefs(spacesStore)
const { disciplines }            = storeToRefs(disciplinesStore)

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
  const f = listFilters.value
  if (f.search) {
    const q = f.search.toLowerCase()
    r = r.filter(s => s.nombre_espacio.toLowerCase().includes(q))
  }
  if (f.tipo === 'RESERVA_ON_DEMAND') r = r.filter(s => s.es_reserva_on_demand)
  else if (f.tipo === 'CLASE_PROGRAMADA') r = r.filter(s => s.es_clase_programada)
  else if (f.tipo === 'USO_LIBRE')  r = r.filter(s => s.es_uso_libre)
  if (f.estatus) r = r.filter(s => s.estatus === f.estatus)
  return r.sort((a, b) => a.nombre_espacio.localeCompare(b.nombre_espacio))
})

const hasActiveFilters = computed(() => listFilters.value.search || listFilters.value.tipo || listFilters.value.estatus)
const clearFilters = () => {
  listFilters.value.search = ''
  listFilters.value.tipo = listFilters.value.estatus = null
}

// ── FRANJA DE COLOR ESTATUS ────────────────────────────────────
const statusAccentLeft = (estatus) => ({
  ACTIVO:        'bg-emerald-500',
  MANTENIMIENTO: 'bg-amber-500',
  DESHABILITADO: 'bg-red-500',
}[estatus] ?? 'bg-surface-300')

// ── BADGES DE TIPO ─────────────────────────────────────────────
const tipoBadges = (space) => {
  const b = []
  if (space.es_reserva_on_demand) b.push({ label: 'On Demand',  classes: 'bg-indigo-50 text-indigo-700 border-indigo-100' })
  if (space.es_clase_programada)  b.push({ label: 'Clase',      classes: 'bg-blue-50 text-blue-700 border-blue-100' })
  if (space.es_uso_libre)         b.push({ label: 'Uso Libre',  classes: 'bg-emerald-50 text-emerald-700 border-emerald-100' })
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
    label:  'Ver Detalles',
    icon:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
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
    label:  'Gestionar Disciplinas',
    icon:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
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
    label:  'Modificar Estatus',
    icon:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
               <circle cx="12" cy="12" r="10"/>
               <line x1="12" y1="8" x2="12" y2="12"/>
               <line x1="12" y1="16" x2="12.01" y2="16"/>
             </svg>`,
    action: () => openEstatusModal(space),
  }
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
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader
        title="Espacios Físicos"
        subtitle="Gestiona las canchas, salones e instalaciones del club."
      >
        <span class="text-sm font-bold text-surface-500">
          {{ filteredSpaces.length }}
          <span class="font-medium text-surface-400">de {{ spaces.length }}</span>
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
      <FilterContainer :hasActiveFilters="hasActiveFilters" @clear="clearFilters">
        <template #search>
          <SearchInput v-model="listFilters.search" placeholder="Buscar espacio por nombre…" />
        </template>

        <FilterSelect
          label="Tipo de uso"
          v-model="listFilters.tipo"
          :options="OPT_TIPO"
        >
          <template #icon>
            <IconLayers />
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
        <TableSkeleton :rows="4" :columns="3" :has-avatar="true" />
      </div>

      <!-- VACÍO -->
      <div v-else-if="filteredSpaces.length === 0"
        class="bg-white rounded-2xl border-2 border-dashed border-surface-200 p-16
               flex flex-col items-center justify-center text-center">
        <div class="w-16 h-16 rounded-2xl bg-surface-100 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
          </svg>
        </div>
        <h3 class="text-base font-black text-surface-900">Sin resultados</h3>
        <p class="text-sm text-surface-500 mt-1 max-w-xs">No se encontraron espacios con los filtros actuales.</p>
        <button @click="clearFilters" class="mt-4 text-sm font-bold text-blue-600 hover:underline">Limpiar filtros</button>
      </div>

      <!-- TABLA -->
      <div v-else class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-x-auto min-h-96">
        <table class="w-full text-sm text-left text-slate-600">
          <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
            <tr>
              <th scope="col" class="px-6 py-4 text-left font-extrabold rounded-tl-2xl">Espacio</th>
              <th scope="col" class="px-6 py-4 text-left font-extrabold hidden md:table-cell">Tipo de Uso</th>
              <th scope="col" class="px-6 py-4 text-left font-extrabold hidden lg:table-cell">Disciplinas Permitidas</th>
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
                  <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm shrink-0">
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
                  <span
                    v-for="badge in tipoBadges(space)"
                    :key="badge.label"
                    class="text-[10px] font-bold px-2 py-0.5 rounded-lg border uppercase tracking-wide"
                    :class="badge.classes"
                  >
                    {{ badge.label }}
                  </span>
                </div>
              </td>

              <!-- Disciplinas -->
              <td class="px-6 py-4 hidden lg:table-cell">
                <div v-if="space.disciplinas?.length" class="flex flex-wrap items-center gap-1">
                  <span
                    v-for="d in space.disciplinas.slice(0, 3)"
                    :key="d.id_disciplina"
                    class="flex items-center gap-1 px-2 py-0.5 rounded-lg bg-surface-50 border border-surface-200 text-[10px] font-bold text-surface-600"
                  >
                    <DisciplineIcon :name="d.nombre_disciplina" class="w-3 h-3 shrink-0" />
                    {{ d.nombre_disciplina }}
                  </span>
                  <span v-if="space.disciplinas.length > 3" class="px-2 py-0.5 rounded-lg bg-surface-100 text-surface-500 text-[10px] font-bold">
                    +{{ space.disciplinas.length - 3 }}
                  </span>
                </div>
                <span v-else class="text-[11px] text-surface-400 italic">Sin disciplinas asignadas</span>
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
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
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
              <div class="flex items-center justify-between px-7 py-5 border-b border-surface-100">
                <div>
                  <h2 class="text-lg font-black text-surface-900 leading-tight">Nuevo Espacio</h2>
                  <p class="text-xs text-surface-500 font-medium mt-0.5">Registra una cancha, salón o instalación.</p>
                </div>
                <button @click="showNewModal = false"
                  class="w-9 h-9 rounded-xl bg-surface-100 hover:bg-surface-200 flex items-center justify-center text-surface-500 transition-colors">
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6L6 18M6 6l12 12"/>
                  </svg>
                </button>
              </div>

              <!-- Cuerpo -->
              <div class="overflow-y-auto p-7 space-y-6 bg-surface-50/30">

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
                      <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                        Nombre del Espacio <span class="text-red-400">*</span>
                      </label>
                      <div class="relative">
                        <IconLayers class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                        <input v-model="newSpace.nombre_espacio" placeholder="Ej. Cancha de Tenis 1"
                          class="w-full pl-11 pr-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-semibold
                                 text-surface-900 placeholder:text-surface-400 shadow-sm
                                 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all"/>
                      </div>
                    </div>

                    <div class="space-y-1.5">
                      <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Capacidad Máxima</label>
                      <div class="relative">
                        <IconUser class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                        <input v-model.number="newSpace.capacidad_maxima" type="number" min="1" placeholder="Ej. 10"
                          class="w-full pl-11 pr-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-semibold
                                 text-surface-900 placeholder:text-surface-400 shadow-sm
                                 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all"/>
                      </div>
                    </div>

                    <!-- Tipo de uso -->
                    <div class="space-y-2">
                      <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Tipo de Uso</label>
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
                            : 'bg-white text-surface-500 border-surface-200 hover:border-blue-200 hover:text-blue-600'"
                        >
                          <span>{{ uso.label }}</span>
                          <svg v-if="uso.active" class="w-4 h-4 shrink-0"
                               viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <path d="M20 6L9 17l-5-5"/>
                          </svg>
                          <div v-else class="w-4 h-4 rounded-full border-2 border-surface-300 shrink-0"/>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Columna derecha: disciplinas — selector azul institucional -->
                  <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Disciplinas Permitidas</label>
                    <div class="h-72 overflow-y-auto bg-white rounded-2xl p-2 border border-surface-200 space-y-1.5">
                      <button
                        v-for="d in disciplines"
                        :key="d.id_disciplina"
                        type="button"
                        @click="toggleDisciplina(d.id_disciplina)"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl border text-xs font-bold transition-all text-left group relative overflow-hidden"
                        :class="newSpace.disciplinas.includes(d.id_disciplina)
                          ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                          : 'bg-surface-50 text-surface-600 border-surface-200 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-400 hover:shadow-sm'"
                      >
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 transition-all"
                          :class="newSpace.disciplinas.includes(d.id_disciplina)
                            ? 'bg-white/20 text-white'
                            : 'bg-surface-200 text-surface-500 group-hover:bg-white group-hover:text-blue-600'">
                          <DisciplineIcon :name="d.nombre_disciplina" class="w-4 h-4" />
                        </div>
                        <span class="truncate flex-1">{{ d.nombre_disciplina }}</span>
                        
                        <!-- Icono de Check con la misma lógica que Instructors -->
                        <div
                          class="w-4 h-4 rounded-full flex items-center justify-center shrink-0 transition-all border-2"
                          :class="newSpace.disciplinas.includes(d.id_disciplina) ? 'border-white text-white' : 'border-surface-300 bg-surface-50 text-transparent group-hover:border-blue-400 group-hover:text-blue-300'">
                          <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                          </svg>
                        </div>
                      </button>
                    </div>
                  </div>

                </div>
              </div>

              <!-- Pie del modal -->
              <div class="flex items-center justify-end gap-3 px-7 py-4 border-t border-surface-100">
                <CancelButton @click="showNewModal = false" />
                <ConfirmButton
                  label="Crear Espacio"
                  :loading="isSaving"
                  @click="saveNewSpace"
                />
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
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showDisableModal = false"
        >
          <div class="bg-white w-full max-w-md rounded-4xl shadow-2xl p-8 text-center">
            <div class="w-16 h-16 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center mx-auto mb-5">
              <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v4M12 17h.01"/>
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
              </svg>
            </div>
            <h3 class="text-xl font-black text-surface-900 mb-2">¿Deshabilitar espacio?</h3>
            <p class="text-sm text-surface-500 mb-8">
              ¿Confirmas deshabilitar <span class="font-bold text-surface-800">{{ selectedSpace?.nombre_espacio }}</span>?
              El sistema validará que no haya actividades pendientes.
            </p>
            <div class="flex gap-3">
              <CancelButton @click="showDisableModal = false" class="flex-1" />
              <ConfirmButton
                label="Deshabilitar"
                :loading="isSaving"
                @click="confirmDisable"
                class="flex-1 bg-red-600! hover:bg-red-700!"
              />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
    
    <ModificarEstatusModal 
      :show="showEstatusModal" 
      :space="spaceToEdit" 
      @close="showEstatusModal = false" 
    />

    <ManageDisciplinesModal
      :show="showDisciplinesModal"
      type="space"
      :item="selectedSpace"
      @close="showDisciplinesModal = false"
      @saved="spacesStore.fetchSpaces()"
    />

  </main>
</template>
