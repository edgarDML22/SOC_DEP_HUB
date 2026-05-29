<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import api from '@/services/api'
import { useInstructorStore } from '@/stores/admin/instructorStore'
import { useAlerts } from '@/composables/useAlerts'


import DatePicker from 'primevue/datepicker'

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import SearchInput from '@/components/gerente/ui/SearchInput.vue'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import FilterContainer from '@/components/gerente/ui/FilterContainer.vue'
import FilterSelect from '@/components/gerente/ui/FilterSelect.vue'
import { IconAlertCircle, IconTarget, IconChevronDown, IconUser, IconPhone, IconMail, IconClock, IconCalendar } from '@/components/icons'
import InstructorStatusModal from './InstructorStatusModal.vue'
import ManageDisciplinesModal from '@/views/admin/Disciplines/ManageDisciplinesModal.vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'

const router = useRouter()
const instructorStore = useInstructorStore()
const { toastInfo } = useAlerts()

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
  { label: 'Todos los estatus', value: null },
  { label: 'Activo', value: 'ACTIVO' },
  { label: 'Inactivo', value: 'INACTIVO' },
  { label: 'Baja Temporal', value: 'BAJA_TEMPORAL' },
]

const disciplinasOpts = computed(() => [
  { label: 'Todas las disciplinas', value: null },
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
const showSuccessOverlay = ref(false)
const isSaving = ref(false)
const formError = ref('')

const EMPTY_FORM = () => ({
  nombre_completo: '',
  telefono: '',
  correo_electronico: '',
  fecha_nacimiento: null,
  fecha_afiliacion: null,
  hora_entrada: null,
  hora_salida: null,
  estatus: 'ACTIVO',
  disciplinas: [],
})
const newInstructor = ref(EMPTY_FORM())

const OPT_ESTATUS_FORM = [
  { label: 'Activo', value: 'ACTIVO' },
  { label: 'Inactivo', value: 'INACTIVO' },
  { label: 'Baja Temporal', value: 'BAJA_TEMPORAL' },
]

const toggleDisciplina = (id) => {
  const idx = newInstructor.value.disciplinas.indexOf(id)
  idx > -1
    ? newInstructor.value.disciplinas.splice(idx, 1)
    : newInstructor.value.disciplinas.push(id)
}

const toDateStr = (d) => {
  if (!d) return null
  return new Intl.DateTimeFormat('en-CA').format(d)
}
const toTimeStr = (d) => {
  if (!d) return null
  const hh = String(d.getHours()).padStart(2, '0')
  const mm = String(d.getMinutes()).padStart(2, '0')
  return `${hh}:${mm}:00`
}

const saveNewInstructor = async () => {
  formError.value = ''
  if (!newInstructor.value.nombre_completo.trim()) {
    formError.value = 'El nombre completo es requerido.'
    return
  }
  if (!newInstructor.value.telefono?.trim()) {
    formError.value = 'El teléfono es requerido.'
    return
  }
  if (!newInstructor.value.estatus) {
    formError.value = 'El estatus es requerido.'
    return
  }
  isSaving.value = true
  try {
    const payload = {
      ...newInstructor.value,
      fecha_nacimiento: toDateStr(newInstructor.value.fecha_nacimiento),
      fecha_afiliacion: toDateStr(newInstructor.value.fecha_afiliacion),
      hora_entrada: toTimeStr(newInstructor.value.hora_entrada),
      hora_salida: toTimeStr(newInstructor.value.hora_salida),
    }
    const res = await api.post('/instructors/create', payload)
    if (res.data?.success) {
      showSuccessOverlay.value = true
      await fetchInstructors(true)
      setTimeout(() => {
        showSuccessOverlay.value = false
        showNewModal.value = false
        newInstructor.value = EMPTY_FORM()
      }, 1500)
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
  showNewModal.value = true
}

// ── INIT ──────────────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([fetchDisciplinas(), fetchInstructors()])
})
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader title="Instructores" subtitle="Gestión de instructores, disciplinas y estatus de cuenta.">
        <span class="text-sm font-bold text-surface-500">
          {{ filteredInstructors.length }}
          <span class="font-medium text-surface-400">de {{ instructors.length }}</span>
        </span>
        <button @click="openNewModal" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-surface-900 text-white
                 text-sm font-bold hover:bg-primary-600 transition-colors shadow-sm cursor-pointer">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="16" />
            <line x1="8" y1="12" x2="16" y2="12" />
          </svg>
          Nuevo Instructor
        </button>
      </AdminPageHeader>

      <!-- BARRA DE FILTROS -->
      <FilterContainer :hasActiveFilters="hasActiveFilters" @clear="clearFilters">
        <template #search>
          <SearchInput v-model="listFilters.search" placeholder="Buscar instructor por nombre…" />
        </template>

        <FilterSelect
          label="Estatus"
          v-model="listFilters.estatus"
          :options="OPT_ESTATUS"
        >
          <template #icon>
            <IconAlertCircle />
          </template>
        </FilterSelect>

        <FilterSelect
          label="Disciplina"
          v-model="listFilters.disciplina"
          :options="disciplinasOpts"
        >
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
        <div v-else class="overflow-x-auto">
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
                      class="px-2 py-0.5 rounded-lg bg-primary-50 text-primary-700 border border-primary-100 text-[10px] font-bold">
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
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-surface-50 text-surface-700 border border-surface-200/50 font-sans tracking-wide">
                    <IconClock class="w-3.5 h-3.5 text-surface-400 shrink-0" />
                    <span>
                      {{ instructor.hora_entrada?.substring(0, 5) ?? '--' }} – {{ instructor.hora_salida?.substring(0, 5) ?? '--' }}
                    </span>
                  </span>
                  <span v-else class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-surface-100/50 text-surface-400 border border-surface-200/30 font-sans">
                    Sin horario
                  </span>
                </td>
                <!-- Fecha afiliación -->
                <td class="px-6 py-4 hidden lg:table-cell">
                  <span v-if="instructor.fecha_afiliacion"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-surface-50 text-surface-700 border border-surface-200/50 font-sans tracking-wide">
                    <IconCalendar class="w-3.5 h-3.5 text-surface-400 shrink-0" />
                    <span>
                      {{ instructor.fecha_afiliacion }}
                    </span>
                  </span>
                  <span v-else class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-surface-100/50 text-surface-400 border border-surface-200/30 font-sans">
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
      </div>
    </div><!-- /max-w -->

    <!-- ══════════════════════════════════════════════════════════
         MODAL: NUEVO INSTRUCTOR
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showNewModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showNewModal = false">
          <Transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0">
            <div v-if="showNewModal" class="bg-white w-full max-w-2xl rounded-4xl shadow-2xl shadow-surface-900/20
                     flex flex-col max-h-[92vh] overflow-hidden relative">
              <!-- Overlay de éxito (animate-scale-in) -->
              <Transition
                enter-active-class="transition-opacity duration-150 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
              >
                <div v-if="showSuccessOverlay" class="absolute inset-0 z-50 bg-white/95 backdrop-blur-[2px] rounded-4xl flex flex-col items-center justify-center gap-4">
                  <div class="w-16 h-16 rounded-full bg-emerald-600 flex items-center justify-center shadow-lg animate-scale-in">
                    <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12" />
                    </svg>
                  </div>
                  <p class="text-base font-black text-slate-800">Instructor registrado correctamente</p>
                </div>
              </Transition>

              <!-- Cabecera -->
              <div class="flex items-center justify-between px-8 py-6 bg-white border-b border-surface-100">
                <div>
                  <h2 class="text-xl font-black text-surface-900 leading-tight">Nuevo Instructor</h2>
                  <p class="text-xs font-bold text-surface-500 mt-1 uppercase tracking-wider">
                    Completa la información para registrar al instructor.
                  </p>
                </div>
                <button @click="showNewModal = false" class="w-10 h-10 rounded-xl bg-surface-100 hover:bg-surface-200
                         flex items-center justify-center text-surface-500 transition-colors">
                  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6L6 18M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Cuerpo -->
              <div class="overflow-y-auto p-7 space-y-6 bg-surface-50/30">

                <!-- Error banner -->
                <Transition enter-active-class="transition-all duration-200 ease-out"
                  enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                  <div v-if="formError" class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-2xl
                           text-red-700 text-sm font-semibold">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                      stroke-width="2">
                      <circle cx="12" cy="12" r="10" />
                      <line x1="12" y1="8" x2="12" y2="12" />
                      <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    {{ formError }}
                  </div>
                </Transition>

                <!-- Nombre -->
                <div class="space-y-1.5">
                  <label class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1 mb-1">
                    Nombre Completo <span class="text-red-400">*</span>
                  </label>
                  <div class="relative">
                    <IconUser class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                    <input v-model="newInstructor.nombre_completo" placeholder="Ej. Juan Pérez García"
                      class="w-full pl-11 pr-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-semibold
                             text-surface-900 placeholder:text-surface-400 shadow-sm
                             focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all" />
                  </div>
                </div>

                <!-- Teléfono + Estatus -->
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-1.5">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1 mb-1">
                      Teléfono <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                      <IconPhone class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400" />
                      <input v-model="newInstructor.telefono" placeholder="Ej. 5512345678"
                        class="w-full pl-11 pr-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-semibold
                               text-surface-900 placeholder:text-surface-400 shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all" />
                    </div>
                  </div>
                  <div class="space-y-1.5">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1 mb-1">
                      Estatus <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                      <select v-model="newInstructor.estatus"
                        class="w-full px-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-sm font-bold text-surface-900 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer shadow-xs">
                        <option v-for="opt in OPT_ESTATUS_FORM" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                      </select>
                      <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                  </div>
                </div>

                <!-- Correo -->


                <!-- Fechas -->
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-1.5">
                    <label
                      class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1 mb-1">Fecha de
                      Nacimiento</label>
                    <DatePicker v-model="newInstructor.fecha_nacimiento" dateFormat="yy-mm-dd" showIcon
                      iconDisplay="input" placeholder="yyyy-mm-dd" class="w-full" :manualInput="false" />
                  </div>
                  <div class="space-y-1.5">
                    <label
                      class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1 mb-1">Fecha de
                      Afiliación</label>
                    <DatePicker v-model="newInstructor.fecha_afiliacion" dateFormat="yy-mm-dd" showIcon
                      iconDisplay="input" placeholder="yyyy-mm-dd" class="w-full" :manualInput="false" />
                  </div>
                </div>

                <!-- Horario -->
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-1.5">
                    <label
                      class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1 mb-1">Hora
                      Entrada</label>
                    <DatePicker v-model="newInstructor.hora_entrada" timeOnly hourFormat="24" placeholder="00:00"
                      class="w-full" :manualInput="false" showOnFocus fluid />
                  </div>
                  <div class="space-y-1.5">
                    <label
                      class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1 mb-1">Hora
                      Salida</label>
                    <DatePicker v-model="newInstructor.hora_salida" timeOnly hourFormat="24" placeholder="00:00"
                      class="w-full" :manualInput="false" showOnFocus fluid />
                  </div>
                </div>

                <!-- Selector de disciplinas — estilo premium -->
                <div class="space-y-2 mt-4">
                  <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                    Disciplinas que Imparte
                  </label>
                  <div v-if="disciplinasList.length === 0"
                    class="text-xs font-bold text-surface-400 italic p-4 text-center bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
                    Cargando disciplinas…
                  </div>
                  <div v-else
                    class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-56 overflow-y-auto p-1 pr-2 custom-scrollbar">
                      <button v-for="d in disciplinasList" :key="d.id_disciplina" type="button"
                        @click="toggleDisciplina(d.id_disciplina)"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 text-sm font-bold transition-all text-left shadow-sm group relative overflow-hidden"
                        :class="newInstructor.disciplinas.includes(d.id_disciplina)
                          ? 'bg-blue-600 text-white border-blue-600'
                          : 'bg-surface-50 text-surface-600 border-surface-200 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md'">
                        <div class="w-8 h-8 flex items-center justify-center shrink-0 transition-colors">
                          <DisciplineIcon :name="d.nombre_disciplina" class="w-6 h-6 shrink-0" />
                        </div>
                        <span class="truncate flex-1">{{ d.nombre_disciplina }}</span>

                        <!-- Icono de Check Dinámico -->
                        <div
                          class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 transition-all border-2"
                          :class="newInstructor.disciplinas.includes(d.id_disciplina) ? 'border-white text-white' : 'border-surface-300 bg-surface-50 text-transparent group-hover:border-blue-400 group-hover:text-blue-300'">
                          <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                          </svg>
                        </div>
                      </button>
                  </div>
                </div>

              </div><!-- /cuerpo -->

              <!-- Pie del modal -->
              <div class="flex items-center justify-end gap-3 px-7 py-4 border-t border-surface-100">
                <CancelButton @click="showNewModal = false" />
                <ConfirmButton label="Guardar Instructor" :loading="isSaving" @click="saveNewInstructor" />
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

    <!-- MODAL: CAMBIAR ESTATUS -->
    <InstructorStatusModal
      :show="showStatusModal"
      :instructor-id="statusModalInstructorId"
      @close="showStatusModal = false"
      @updated="onStatusUpdated"
    />

    <!-- MODAL: GESTIONAR DISCIPLINAS -->
    <ManageDisciplinesModal
      :show="showDisciplinesModal"
      type="instructor"
      :item="selectedInstructor"
      @close="showDisciplinesModal = false"
      @saved="fetchInstructors(true)"
    />

  </main>
</template>
