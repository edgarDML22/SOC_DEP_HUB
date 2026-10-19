<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import api from '@/services/api'
import { useInstructorStore } from '@/stores/admin/instructorStore'
import { useAlerts } from '@/composables/useAlerts'


import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import SearchInput from '@/components/gerente/ui/SearchInput.vue'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import AppModal from '@/components/ui/modal/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppField from '@/components/ui/form/AppField.vue'
import AppInput from '@/components/ui/form/AppInput.vue'
import AppSelect from '@/components/ui/form/AppSelect.vue'
import AppDatePicker from '@/components/ui/form/AppDatePicker.vue'
import AppTimePicker from '@/components/ui/form/AppTimePicker.vue'
import AppMultiSelect from '@/components/ui/form/AppMultiSelect.vue'
import FilterContainer from '@/components/gerente/ui/FilterContainer.vue'
import FilterSelect from '@/components/gerente/ui/FilterSelect.vue'
import { IconAlertCircle, IconTarget, IconUser, IconPhone, IconClock, IconCalendar } from '@/components/icons'
import IconUserPlus from '@/components/icons/IconUserPlus.vue'
import InstructorStatusModal from './InstructorStatusModal.vue'
import ManageDisciplinesModal from '@/views/admin/Disciplines/ManageDisciplinesModal.vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'
import { useformat } from '@/utils/formatters'

const router = useRouter()
const instructorStore = useInstructorStore()
const { toastSuccess } = useAlerts()
const { dateFormat } = useformat()

const { instructors, isLoading, error: errorMsg, listFilters } = storeToRefs(instructorStore)
const { fetchInstructors, updateInstructor } = instructorStore

// ── DISCIPLINAS ───────────────────────────────────────────────
const disciplinasList = ref([])
const fetchDisciplinas = async () => {
  try {
    const res = await api.get('/disciplinas/all')
    if (res.data?.success) {
      const list = res.data.data || []
      disciplinasList.value = [...list].sort((a, b) =>
        (a.nombre_disciplina || '').localeCompare(b.nombre_disciplina || '', 'es', { sensitivity: 'base' })
      )
    }
  } catch (e) {
    console.error('Error cargando disciplinas:', e)
  }
}

// ── FILTROS ────────────────────────────────────────────────────
// OPT_ESTATUS
const OPT_ESTATUS = [
  { label: 'Todos', value: null },
  { label: 'Activo', value: 'ACTIVO' },
  { label: 'Inactivo', value: 'INACTIVO' },
  { label: 'Baja Temporal', value: 'BAJA_TEMPORAL' },
]

const disciplinasOpts = computed(() => [
  { label: 'Todas', value: null },
  ...disciplinasList.value.map(d => ({ label: d.nombre_disciplina, value: d.id_disciplina })),
])

const filteredInstructors = computed(() => {
  let r = instructors.value
  const f = listFilters.value

  if (f.search) {
    const q = f.search.toLowerCase()
    r = r.filter(i => i.nombre_completo?.toLowerCase().includes(q))
  }
  if (f.estatus)
    r = r.filter(i => i.estatus === f.estatus)
  if (f.disciplina)
    r = r.filter(i => i.disciplinas?.some(d => d.id_disciplina == f.disciplina))

  // Ordenar alfabéticamente por nombre
  return [...r].sort((a, b) => (a.nombre_completo || '').localeCompare(b.nombre_completo || ''))
})

const hasActiveFilters = computed(() =>
  listFilters.value.search || listFilters.value.estatus || listFilters.value.disciplina
)
const clearFilters = () => {
  listFilters.value.search = ''
  listFilters.value.estatus = listFilters.value.disciplina = null
}
// ── FILTROS COLAPSABLES (móvil) ───────────────────────────────
const activeFiltersCount = computed(() =>
  [listFilters.value.estatus, listFilters.value.disciplina].filter(Boolean).length
)

// ── AVATAR ────────────────────────────────────────────────────
const GRADIENTS = [
  'from-indigo-400 to-indigo-600',
  'from-emerald-400 to-emerald-600',
  'from-purple-400 to-purple-600',
  'from-sky-400 to-sky-600',
  'from-rose-400 to-rose-600',
  'from-amber-400 to-amber-600',
]
const avatarGradient = (name = '') => GRADIENTS[(name.charCodeAt(0) ?? 0) % GRADIENTS.length]
const initials = (name = '') => {
  const parts = name.trim().split(' ').filter(Boolean)
  return parts.length >= 2
    ? (parts[0][0] + parts[1][0]).toUpperCase()
    : (parts[0]?.[0] ?? '?').toUpperCase()
}

// ── VER DETALLES (reutilizable) ───────────────────────────────
const verDetalles = (instructor) => {
  if (typeof instructorStore.setCurrentInstructor === 'function') {
    instructorStore.setCurrentInstructor(instructor)
  } else {
    instructorStore.currentInstructor = instructor
  }
  router.push(`/admin/instructors/${instructor.id_instructor}`)
}

// ── MENU ITEMS ─────────────────────────────────────────────────
const buildMenuItems = (instructor) => [
  {
    label: 'Ver perfil completo',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
             <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
           </svg>`,
    action: () => {
      if (typeof instructorStore.setCurrentInstructor === 'function') {
        instructorStore.setCurrentInstructor(instructor);
      } else {
        instructorStore.currentInstructor = instructor;
      }
      router.push(`/admin/instructors/${instructor.id_instructor}`);
    },
  },
  {
    label: 'Gestionar disciplinas',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
             <path d="M11 6h9"/>
             <path d="M11 12h9"/>
             <path d="M11 18h9"/>
             <polyline points="3 6 4 7 6 5"/>
             <polyline points="3 12 4 13 6 11"/>
             <polyline points="3 18 4 19 6 17"/>
           </svg>`,
    action: () => openDisciplinesModal(instructor),
  },
  {
    label: 'Cambiar estatus',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
             <circle cx="12" cy="7" r="4"/>
           </svg>`,
    action: () => openStatusModal(instructor),
  },
]

// ── MODAL: CAMBIAR ESTATUS ─────────────────────────────────────
const showStatusModal = ref(false)
const statusModalInstructorId = ref(null)

const showDisciplinesModal = ref(false)
const selectedInstructor = ref(null)

const openDisciplinesModal = (instructor) => {
  selectedInstructor.value = instructor
  showDisciplinesModal.value = true
}

const openStatusModal = (instructor) => {
  if (typeof instructorStore.setCurrentInstructor === 'function') {
    instructorStore.setCurrentInstructor(instructor);
  } else {
    instructorStore.currentInstructor = instructor;
  }
  statusModalInstructorId.value = instructor.id_instructor
  showStatusModal.value = true
}

const onStatusUpdated = async () => {
  await fetchInstructors(true)
}

// ── MODAL: NUEVO INSTRUCTOR ────────────────────────────────────
const showNewModal = ref(false)
const isSaving = ref(false)
const formError = ref('')
const formErrors = ref({})

const EMPTY_FORM = () => ({
  nombre_completo: '',
  telefono: '',
  correo_electronico: '',
  fecha_nacimiento: '',
  fecha_afiliacion: '',
  hora_entrada: '',
  hora_salida: '',
  estatus: 'ACTIVO',
  disciplinas: [],
})
const newInstructor = ref(EMPTY_FORM())

const OPT_ESTATUS_FORM = [
  { label: 'Activo', value: 'ACTIVO' },
  { label: 'Inactivo', value: 'INACTIVO' },
  { label: 'Baja Temporal', value: 'BAJA_TEMPORAL' },
]

const disciplinaOptions = computed(() =>
  disciplinasList.value.map(d => ({ label: d.nombre_disciplina, value: d.id_disciplina }))
)

const hoyIso = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Mexico_City' }).format(new Date())

const validateNewInstructor = () => {
  const f = newInstructor.value
  const errors = {}
  if (!f.nombre_completo.trim()) errors.nombre_completo = 'Escribe el nombre completo.'
  if (!f.telefono?.trim()) errors.telefono = 'Escribe el teléfono.'
  else if (!/^\d{10}$/.test(f.telefono.trim())) errors.telefono = 'El teléfono debe tener 10 dígitos.'
  if (!f.estatus) errors.estatus = 'Selecciona el estatus.'
  if (f.hora_entrada && f.hora_salida && f.hora_salida <= f.hora_entrada) errors.hora_salida = 'Debe ser posterior a la hora de entrada.'
  formErrors.value = errors
  return Object.keys(errors).length === 0
}

// Al corregir un campo marcado, su error desaparece
watch(newInstructor, () => {
  const prev = formErrors.value
  if (!Object.keys(prev).length) return
  validateNewInstructor()
  formErrors.value = Object.fromEntries(Object.entries(formErrors.value).filter(([key]) => key in prev))
}, { deep: true })

const saveNewInstructor = async () => {
  formError.value = ''
  if (!validateNewInstructor()) return
  isSaving.value = true
  try {
    const payload = {
      ...newInstructor.value,
      fecha_nacimiento: newInstructor.value.fecha_nacimiento || null,
      fecha_afiliacion: newInstructor.value.fecha_afiliacion || null,
      hora_entrada: newInstructor.value.hora_entrada || null,
      hora_salida: newInstructor.value.hora_salida || null,
    }
    const res = await api.post('/instructors/create', payload)
    if (res.data?.success) {
      toastSuccess(`${newInstructor.value.nombre_completo.trim()} se registró como instructor`)
      showNewModal.value = false
      newInstructor.value = EMPTY_FORM()
      await fetchInstructors(true)
    } else {
      formError.value = res.data?.message ?? 'Ocurrió un error al crear.'
    }
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Ocurrió un error inesperado.'
  } finally {
    isSaving.value = false
  }
}

const openNewModal = () => {
  newInstructor.value = EMPTY_FORM()
  formError.value = ''
  formErrors.value = {}
  showNewModal.value = true
}

// ── INIT ──────────────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([fetchDisciplinas(), fetchInstructors()])
})
</script>

<template>
  <main class="min-h-screen bg-surface-50 px-4 py-5 pb-16 font-sans sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-5 sm:space-y-6">

      <!-- CABECERA -->
      <AdminPageHeader title="Instructores" subtitle="Gestión de instructores, disciplinas y estatus de cuenta." />

      <!-- Acción principal a la izquierda y el conteo justo a su derecha -->
      <div class="flex items-center gap-3 sm:gap-4">
        <AppButton class="shrink-0" @click="openNewModal">
          <template #icon><IconUserPlus class="h-4 w-4" /></template>
          Nuevo Instructor
        </AppButton>
        <span class="min-w-0 whitespace-nowrap text-sm font-semibold text-surface-600">
          <span class="font-bold tabular-nums text-surface-900">{{ filteredInstructors.length }}</span>
          de <span class="tabular-nums">{{ instructors.length }}</span><span class="hidden sm:inline"> instructores</span>
        </span>
      </div>

      <!-- BARRA DE FILTROS -->
      <FilterContainer :has-active-filters="hasActiveFilters" :active-count="activeFiltersCount" @clear="clearFilters">
        <template #search>
          <SearchInput v-model="listFilters.search" placeholder="Buscar instructor por nombre…" />
        </template>

        <FilterSelect label="Estatus" v-model="listFilters.estatus" :options="OPT_ESTATUS">
          <template #icon>
            <IconAlertCircle />
          </template>
        </FilterSelect>

        <FilterSelect label="Disciplina" v-model="listFilters.disciplina" :options="disciplinasOpts">
          <template #icon>
            <IconTarget />
          </template>
        </FilterSelect>
      </FilterContainer>

      <!-- TABLA -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-visible min-h-96">

        <!-- Estado: cargando -->
        <TableSkeleton v-if="isLoading" :rows="6" :columns="4" :has-avatar="true" />

        <!-- Estado: error -->
        <div v-else-if="errorMsg" class="p-8 text-center text-red-700 font-semibold text-sm">
          {{ errorMsg }}
        </div>

        <!-- Estado: vacío -->
        <div v-else-if="filteredInstructors.length === 0"
          class="p-16 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 rounded-2xl bg-surface-100 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="1.5">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
              <circle cx="12" cy="7" r="4" />
            </svg>
          </div>
          <h3 class="text-base font-black text-surface-900">Sin resultados</h3>
          <p class="text-sm text-surface-500 mt-1 max-w-xs">No se encontraron instructores con los filtros actuales.</p>
          <button @click="clearFilters" class="mt-4 text-sm font-bold text-primary-600 hover:underline">
            Limpiar filtros
          </button>
        </div>

        <!-- Tabla con datos -->
        <template v-else>
          <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
                <tr>
                  <th class="px-6 py-4 text-left font-extrabold rounded-tl-2xl">Instructor</th>
                  <th class="px-6 py-4 text-left font-extrabold">Estatus</th>
                  <th class="px-6 py-4 text-left font-extrabold hidden md:table-cell">Disciplinas</th>
                  <th class="px-6 py-4 text-left font-extrabold hidden lg:table-cell">Horario</th>
                  <th class="px-6 py-4 text-left font-extrabold hidden lg:table-cell">Afiliación</th>
                  <th class="px-6 py-4 text-right font-extrabold rounded-tr-2xl">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-surface-100">
                <tr v-for="instructor in filteredInstructors" :key="instructor.id_instructor"
                  class="bg-white border-b border-surface-100 hover:bg-surface-50/50 transition-colors group">
                  <!-- Avatar + nombre + email -->
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-xl bg-linear-to-br flex items-center justify-center
                             text-white font-black text-xs shrink-0 shadow-sm"
                        :class="avatarGradient(instructor.nombre_completo)">
                        {{ initials(instructor.nombre_completo) }}
                      </div>
                      <div class="min-w-0">
                        <p class="font-semibold text-surface-900 truncate max-w-[180px] leading-tight">
                          {{ instructor.nombre_completo }}
                        </p>
                        <p class="text-xs text-surface-400 truncate max-w-[180px]">
                          {{ instructor.correo_electronico ?? 'Sin correo' }}
                        </p>
                      </div>
                    </div>
                  </td>
                  <!-- Estatus -->
                  <td class="px-6 py-4">
                    <BadgeStatus :status="instructor.estatus" />
                  </td>
                  <!-- Disciplinas chips -->
                  <td class="px-6 py-4 hidden md:table-cell">
                    <div v-if="instructor.disciplinas?.length" class="flex flex-wrap gap-1">
                      <span v-for="d in instructor.disciplinas.slice(0, 2)" :key="d.id_disciplina"
                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-primary-50 text-primary-700 border border-primary-100 text-[10px] font-bold">
                        <DisciplineIcon :name="d.nombre_disciplina" class="w-3.5 h-3.5 shrink-0" />
                        {{ d.nombre_disciplina }}
                      </span>
                      <span v-if="instructor.disciplinas.length > 2"
                        class="px-2 py-0.5 rounded-lg bg-surface-100 text-surface-500 text-[10px] font-bold">
                        +{{ instructor.disciplinas.length - 2 }}
                      </span>
                    </div>
                    <span v-else class="text-xs text-surface-400 italic">Sin disciplinas</span>
                  </td>
                  <!-- Horario -->
                  <td class="px-6 py-4 hidden lg:table-cell">
                    <span v-if="instructor.hora_entrada || instructor.hora_salida"
                      class="inline-flex items-center gap-1.5 whitespace-nowrap px-2.5 py-1 rounded-lg text-xs font-semibold bg-surface-50 text-surface-700 border border-surface-200/50 font-sans tracking-wide">
                      <IconClock class="w-3.5 h-3.5 text-surface-400 shrink-0" />
                      <span>
                        {{ instructor.hora_entrada?.substring(0, 5) ?? '--' }} – {{ instructor.hora_salida?.substring(0,
                          5) ?? '--' }}
                      </span>
                    </span>
                    <span v-else
                      class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-surface-100/50 text-surface-400 border border-surface-200/30 font-sans">
                      Sin horario
                    </span>
                  </td>
                  <!-- Fecha afiliación -->
                  <td class="px-6 py-4 hidden lg:table-cell">
                    <span v-if="instructor.fecha_afiliacion"
                      class="inline-flex items-center gap-1.5 whitespace-nowrap px-2.5 py-1 rounded-lg text-xs font-semibold bg-surface-50 text-surface-700 border border-surface-200/50 font-sans tracking-wide">
                      <IconCalendar class="w-3.5 h-3.5 text-surface-400 shrink-0" />
                      <span>
                        {{ dateFormat(instructor.fecha_afiliacion) }}
                      </span>
                    </span>
                    <span v-else
                      class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-surface-100/50 text-surface-400 border border-surface-200/30 font-sans">
                      Sin fecha
                    </span>
                  </td>
                  <!-- Menú acciones -->
                  <td class="px-6 py-4 text-right">
                    <ActionMenu :items="buildMenuItems(instructor)" align="right" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- VISTA TARJETAS (móvil / tablet) -->
          <div class="lg:hidden flex flex-col gap-3.5 p-4">
            <div v-for="instructor in filteredInstructors" :key="instructor.id_instructor"
              class="bg-white border border-surface-200 rounded-2xl shadow-sm">
              <!-- Encabezado: avatar, nombre, correo y acciones -->
              <div class="flex items-center gap-3 px-4 py-3">
                <div
                  class="w-10 h-10 rounded-xl bg-linear-to-br flex items-center justify-center text-white font-black text-xs shrink-0 shadow-sm"
                  :class="avatarGradient(instructor.nombre_completo)">
                  {{ initials(instructor.nombre_completo) }}
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-bold text-surface-900 truncate leading-tight">
                    {{ instructor.nombre_completo }}
                  </p>
                  <p class="text-xs text-surface-400 truncate">
                    {{ instructor.correo_electronico ?? 'Sin correo' }}
                  </p>
                </div>
                <ActionMenu :items="buildMenuItems(instructor)" align="right" />
              </div>

              <!-- Cuerpo: horario y teléfono (sin línea separadora superior) -->
              <div class="px-4 py-3 space-y-3">
                <div class="flex items-center justify-between gap-3">
                  <span v-if="instructor.hora_entrada || instructor.hora_salida"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-surface-50 text-surface-700 border border-surface-200/50">
                    <IconClock class="w-3.5 h-3.5 text-surface-400 shrink-0" />
                    <span>
                      {{ instructor.hora_entrada?.substring(0, 5) ?? '--' }} – {{ instructor.hora_salida?.substring(0, 5) ?? '--' }}
                    </span>
                  </span>
                  <span v-else class="text-xs font-medium text-surface-400">Sin horario</span>

                  <a v-if="instructor.telefono" :href="`tel:${instructor.telefono}`"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 hover:underline">
                    <IconPhone class="w-4 h-4 shrink-0" />
                    {{ instructor.telefono }}
                  </a>
                  <span v-else class="inline-flex items-center gap-1.5 text-sm text-surface-400">
                    <IconPhone class="w-4 h-4 shrink-0" />
                    Sin teléfono
                  </span>
                </div>
              </div>

              <!-- Disciplinas a la izquierda y estatus cargado a la derecha -->
              <div class="px-4 py-3 border-t border-surface-100 flex items-center justify-between gap-3">
                <div v-if="instructor.disciplinas?.length" class="flex flex-wrap items-center gap-1.5 min-w-0 flex-1">
                  <span v-for="d in instructor.disciplinas.slice(0, 3)" :key="d.id_disciplina"
                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-primary-50 text-primary-700 border border-primary-100 text-[10px] font-bold">
                    <DisciplineIcon :name="d.nombre_disciplina" class="w-3.5 h-3.5 shrink-0" />
                    {{ d.nombre_disciplina }}
                  </span>
                  <span v-if="instructor.disciplinas.length > 3"
                    class="px-2 py-1 rounded-lg bg-surface-100 text-surface-500 text-[10px] font-bold">
                    +{{ instructor.disciplinas.length - 3 }} más
                  </span>
                </div>
                <span v-else class="text-xs text-surface-400 italic">Sin disciplinas</span>

                <div class="shrink-0 ml-auto">
                  <BadgeStatus :status="instructor.estatus" />
                </div>
              </div>


            </div>
          </div>
        </template>
      </div>
    </div><!-- /max-w -->

    <!-- MODAL: NUEVO INSTRUCTOR -->
    <AppModal
      v-model="showNewModal"
      title="Nuevo instructor"
      subtitle="Completa la información para registrarlo en el club"
      :icon="IconUserPlus"
      size="lg"
      :persistent="isSaving"
    >
      <form id="instructor-form" class="grid grid-cols-1 gap-4 sm:grid-cols-2" novalidate @submit.prevent="saveNewInstructor">
        <div v-if="formError" role="alert"
          class="flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 sm:col-span-2 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
          <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" /><path d="M12 8v4M12 16h.01" /></svg>
          {{ formError }}
        </div>

        <AppField label="Nombre completo" for="ins-nombre" required :error="formErrors.nombre_completo" class="sm:col-span-2">
          <AppInput id="ins-nombre" v-model="newInstructor.nombre_completo" :icon="IconUser" placeholder="Ej. Juan Pérez García" autocomplete="off" :invalid="!!formErrors.nombre_completo" />
        </AppField>

        <AppField label="Teléfono" for="ins-telefono" required :error="formErrors.telefono">
          <AppInput id="ins-telefono" v-model="newInstructor.telefono" type="tel" inputmode="numeric" maxlength="10" :icon="IconPhone" placeholder="10 dígitos" autocomplete="off" :invalid="!!formErrors.telefono" />
        </AppField>

        <AppField label="Estatus" required :error="formErrors.estatus">
          <AppSelect v-model="newInstructor.estatus" :options="OPT_ESTATUS_FORM" :invalid="!!formErrors.estatus" />
        </AppField>

        <AppField label="Fecha de nacimiento" optional>
          <AppDatePicker v-model="newInstructor.fecha_nacimiento" :max-date="hoyIso" />
        </AppField>

        <AppField label="Fecha de afiliación" optional>
          <AppDatePicker v-model="newInstructor.fecha_afiliacion" />
        </AppField>

        <AppField label="Hora de entrada" optional>
          <AppTimePicker v-model="newInstructor.hora_entrada" />
        </AppField>

        <AppField label="Hora de salida" optional :error="formErrors.hora_salida">
          <AppTimePicker v-model="newInstructor.hora_salida" :invalid="!!formErrors.hora_salida" />
        </AppField>

        <AppField label="Disciplinas que imparte" hint="Puedes elegir varias; búscalas por nombre." class="sm:col-span-2">
          <AppMultiSelect
            v-model="newInstructor.disciplinas"
            :options="disciplinaOptions"
            :loading="disciplinasList.length === 0"
            placeholder="Seleccionar disciplinas"
            items-label="disciplinas seleccionadas"
          >
            <template #option="{ option }">
              <span class="flex items-center gap-2.5">
                <DisciplineIcon :name="option.label" class="h-5 w-5 shrink-0 text-primary-600" />
                {{ option.label }}
              </span>
            </template>
            <template #chip-icon="{ option }">
              <DisciplineIcon :name="option.label" class="h-3.5 w-3.5 shrink-0" />
            </template>
          </AppMultiSelect>
        </AppField>
      </form>

      <template #footer>
        <AppButton variant="secondary" :disabled="isSaving" @click="showNewModal = false">Cancelar</AppButton>
        <AppButton type="submit" form="instructor-form" :loading="isSaving">Guardar instructor</AppButton>
      </template>
    </AppModal>

    <!-- MODAL: CAMBIAR ESTATUS -->
    <InstructorStatusModal :show="showStatusModal" :instructor-id="statusModalInstructorId"
      @close="showStatusModal = false" @updated="onStatusUpdated" />

    <!-- MODAL: GESTIONAR DISCIPLINAS -->
    <ManageDisciplinesModal :show="showDisciplinesModal" type="instructor" :item="selectedInstructor"
      @close="showDisciplinesModal = false" @saved="fetchInstructors(true)" />

  </main>
</template>