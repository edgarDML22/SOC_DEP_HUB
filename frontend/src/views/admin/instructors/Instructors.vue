<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import api from '@/services/api'
import { useInstructorStore } from '@/stores/admin/instructorStore'
import { useAlerts } from '@/composables/useAlerts'

import Select   from 'primevue/select'
import DatePicker from 'primevue/datepicker'

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus     from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu      from '@/components/gerente/ui/ActionMenu.vue'
import SearchInput     from '@/components/gerente/ui/SearchInput.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import { IconAlertCircle, IconTarget, IconChevronDown } from '@/components/icons'

// Iconos de deportes para el selector de disciplinas
import IconFutbol     from '@/components/icons/disciplines/IconFutbol.vue'
import IconBasquetbol from '@/components/icons/disciplines/IconBasquetbol.vue'
import IconTenis      from '@/components/icons/disciplines/IconTenis.vue'
import IconVoleibol   from '@/components/icons/disciplines/IconVoleibol.vue'
import IconSquash     from '@/components/icons/disciplines/IconSquash.vue'
import IconFrontenis  from '@/components/icons/disciplines/IconFrontenis.vue'
import IconPadel      from '@/components/icons/disciplines/IconPadel.vue'
import IconDefault    from '@/components/icons/disciplines/IconDefault.vue'

const router          = useRouter()
const instructorStore = useInstructorStore()
const { toastInfo }   = useAlerts()

const { instructors, isLoading, error: errorMsg } = storeToRefs(instructorStore)
const { fetchInstructors, updateInstructor } = instructorStore

// ── DISCIPLINAS ───────────────────────────────────────────────
const disciplinasList = ref([])
const fetchDisciplinas = async () => {
  try {
    const res = await api.get('/disciplinas/all')
    if (res.data?.success) disciplinasList.value = res.data.data
  } catch (e) {
    console.error('Error cargando disciplinas:', e)
  }
}

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

// ── FILTROS ────────────────────────────────────────────────────
const search           = ref('')
const filterEstatus    = ref(null)
const filterDisciplina = ref(null)

const OPT_ESTATUS = [
  { label: 'Todos los estatus',  value: null },
  { label: 'Activo',             value: 'ACTIVO' },
  { label: 'Inactivo',           value: 'INACTIVO' },
  { label: 'Baja Temporal',      value: 'BAJA_TEMPORAL' },
]

const disciplinasOpts = computed(() => [
  { label: 'Todas las disciplinas', value: null },
  ...disciplinasList.value.map(d => ({ label: d.nombre_disciplina, value: d.id_disciplina })),
])

const filteredInstructors = computed(() => {
  let r = instructors.value

  if (search.value) {
    const q = search.value.toLowerCase()
    r = r.filter(i => i.nombre_completo?.toLowerCase().includes(q))
  }
  if (filterEstatus.value)
    r = r.filter(i => i.estatus === filterEstatus.value)
  if (filterDisciplina.value)
    r = r.filter(i => i.disciplinas?.some(d => d.id_disciplina == filterDisciplina.value))

  return r
})

const hasActiveFilters = computed(() =>
  search.value || filterEstatus.value || filterDisciplina.value
)
const clearFilters = () => {
  search.value = ''
  filterEstatus.value = filterDisciplina.value = null
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
    action: () => router.push(`/admin/instructors/${instructor.id_instructor}`),
  },
  {
    label: 'Gestionar disciplinas',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
             <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
             <line x1="6" y1="1" x2="6" y2="4"/>
             <line x1="10" y1="1" x2="10" y2="4"/>
             <line x1="14" y1="1" x2="14" y2="4"/>
           </svg>`,
    action: () => router.push(`/admin/instructors/${instructor.id_instructor}/disciplines`),
  },
  {
    label: 'Cambiar estatus',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
             <circle cx="12" cy="7" r="4"/>
           </svg>`,
    action: () => router.push({ name: 'instructor-status', params: { id: instructor.id_instructor } }),
  },
  { separator: true },
  {
    label: 'Dar de baja',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <circle cx="12" cy="12" r="10"/>
             <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
           </svg>`,
    action: () => handleBaja(instructor),
    destructive: true,
    disabled: instructor.estatus === 'INACTIVO',
  },
]

const handleBaja = async (instructor) => {
  const res = await updateInstructor(instructor.id_instructor, { estatus: 'INACTIVO' })
  if (res?.success) {
    toastInfo('Instructor dado de baja', `${instructor.nombre_completo} marcado como Inactivo.`, 'success')
  } else {
    toastInfo('Error', res?.error ?? 'No se pudo actualizar el estatus.', 'error')
  }
}

// ── MODAL: NUEVO INSTRUCTOR ────────────────────────────────────
const showNewModal = ref(false)
const isSaving     = ref(false)
const formError    = ref('')

const EMPTY_FORM = () => ({
  nombre_completo:      '',
  telefono:             '',
  correo_electronico:   '',
  fecha_nacimiento:     null,
  fecha_afiliacion:     null,
  hora_entrada:         null,
  hora_salida:          null,
  estatus:              'ACTIVO',
  disciplinas:          [],
})
const newInstructor = ref(EMPTY_FORM())

const OPT_ESTATUS_FORM = [
  { label: 'Activo',        value: 'ACTIVO' },
  { label: 'Inactivo',      value: 'INACTIVO' },
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
  isSaving.value = true
  try {
    const payload = {
      ...newInstructor.value,
      fecha_nacimiento:  toDateStr(newInstructor.value.fecha_nacimiento),
      fecha_afiliacion:  toDateStr(newInstructor.value.fecha_afiliacion),
      hora_entrada:      toTimeStr(newInstructor.value.hora_entrada),
      hora_salida:       toTimeStr(newInstructor.value.hora_salida),
    }
    const res = await api.post('/instructors/create', payload)
    if (res.data?.success) {
      showNewModal.value  = false
      newInstructor.value = EMPTY_FORM()
      await fetchInstructors(true)
      toastInfo('Instructor creado', 'El instructor fue registrado exitosamente.', 'success')
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
  formError.value     = ''
  showNewModal.value  = true
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
      <AdminPageHeader
        title="Instructores"
        subtitle="Gestión de instructores, disciplinas y estatus de cuenta."
      >
        <span class="text-sm font-bold text-surface-500">
          {{ filteredInstructors.length }}
          <span class="font-medium text-surface-400">de {{ instructors.length }}</span>
        </span>
        <button
          @click="openNewModal"
          class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-600 text-white
                 text-sm font-bold hover:bg-primary-700 transition-colors shadow-sm"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
          </svg>
          Nuevo Instructor
        </button>
      </AdminPageHeader>

      <!-- BARRA DE FILTROS -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5 space-y-4">
        <SearchInput v-model="search" placeholder="Buscar instructor por nombre…" />
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estatus</label>
            <div class="relative">
              <IconAlertCircle class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="filterEstatus" class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_ESTATUS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            </div>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Disciplina</label>
            <div class="relative">
              <IconTarget class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="filterDisciplina" class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in disciplinasOpts" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
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
              <div class="h-3.5 bg-surface-200 rounded-lg w-44"/>
              <div class="h-3 bg-surface-100 rounded-lg w-32"/>
            </div>
            <div class="h-5 w-16 bg-surface-100 rounded-full hidden sm:block"/>
            <div class="hidden md:flex gap-1.5">
              <div class="h-5 w-14 bg-primary-50 rounded-lg"/>
              <div class="h-5 w-14 bg-primary-50 rounded-lg"/>
            </div>
            <div class="h-3 w-20 bg-surface-100 rounded-lg hidden lg:block"/>
          </div>
        </div>

        <!-- Estado: error -->
        <div v-else-if="errorMsg" class="p-8 text-center text-red-700 font-semibold text-sm">
          {{ errorMsg }}
        </div>

        <!-- Estado: vacío -->
        <div v-else-if="filteredInstructors.length === 0"
          class="p-16 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 rounded-2xl bg-surface-100 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <h3 class="text-base font-black text-surface-900">Sin resultados</h3>
          <p class="text-sm text-surface-500 mt-1 max-w-xs">No se encontraron instructores con los filtros actuales.</p>
          <button @click="clearFilters" class="mt-4 text-sm font-bold text-primary-600 hover:underline">
            Limpiar filtros
          </button>
        </div>

        <!-- Tabla con datos -->
        <table v-else class="w-full text-sm">
          <thead>
            <tr class="bg-surface-50 border-b border-surface-200">
              <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-widest text-surface-500">Instructor</th>
              <th class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-widest text-surface-500">Estatus</th>
              <th class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-widest text-surface-500 hidden md:table-cell">Disciplinas</th>
              <th class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-widest text-surface-500 hidden lg:table-cell">Horario</th>
              <th class="px-4 py-3.5 text-left text-xs font-extrabold uppercase tracking-widest text-surface-500 hidden lg:table-cell">Afiliación</th>
              <th class="px-4 py-3.5 text-right text-xs font-extrabold uppercase tracking-widest text-surface-500">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-100">
            <tr
              v-for="instructor in filteredInstructors"
              :key="instructor.id_instructor"
              class="hover:bg-surface-50/70 transition-colors group cursor-pointer"
              @click="router.push(`/admin/instructors/${instructor.id_instructor}`)"
            >
              <!-- Avatar + nombre + email -->
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <div
                    class="w-9 h-9 rounded-xl bg-linear-to-br flex items-center justify-center
                           text-white font-black text-xs shrink-0 shadow-sm"
                    :class="avatarGradient(instructor.nombre_completo)"
                  >
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
              <td class="px-4 py-3.5" @click.stop>
                <BadgeStatus :status="instructor.estatus" />
              </td>
              <!-- Disciplinas chips -->
              <td class="px-4 py-3.5 hidden md:table-cell" @click.stop>
                <div v-if="instructor.disciplinas?.length" class="flex flex-wrap gap-1">
                  <span
                    v-for="d in instructor.disciplinas.slice(0, 2)"
                    :key="d.id_disciplina"
                    class="px-2 py-0.5 rounded-lg bg-primary-50 text-primary-700 border border-primary-100 text-[10px] font-bold"
                  >
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
              <td class="px-4 py-3.5 hidden lg:table-cell">
                <span v-if="instructor.hora_entrada || instructor.hora_salida"
                  class="text-xs font-semibold text-surface-700 font-mono">
                  {{ instructor.hora_entrada?.substring(0,5) ?? '--' }} – {{ instructor.hora_salida?.substring(0,5) ?? '--' }}
                </span>
                <span v-else class="text-xs text-surface-400">—</span>
              </td>
              <!-- Fecha afiliación -->
              <td class="px-4 py-3.5 hidden lg:table-cell">
                <span class="text-xs font-semibold text-surface-700">
                  {{ instructor.fecha_afiliacion ?? '—' }}
                </span>
              </td>
              <!-- Menú acciones -->
              <td class="px-4 py-3.5 text-right" @click.stop>
                <ActionMenu :items="buildMenuItems(instructor)" align="right" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div><!-- /max-w -->

    <!-- ══════════════════════════════════════════════════════════
         MODAL: NUEVO INSTRUCTOR
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
            enter-from-class="opacity-0 scale-95 transurface-y-4"
            enter-to-class="opacity-100 scale-100 transurface-y-0"
          >
            <div v-if="showNewModal"
              class="bg-white w-full max-w-2xl rounded-4xl shadow-2xl shadow-surface-900/20
                     flex flex-col max-h-[92vh] overflow-hidden"
            >
              <!-- Cabecera -->
              <div class="flex items-center justify-between px-8 py-6 bg-white border-b border-surface-100">
                <div>
                  <h2 class="text-xl font-black text-surface-900 leading-tight">Nuevo Instructor</h2>
                  <p class="text-xs font-bold text-surface-500 mt-1 uppercase tracking-wider">
                    Completa la información para registrar al instructor.
                  </p>
                </div>
                <button @click="showNewModal = false"
                  class="w-10 h-10 rounded-xl bg-surface-100 hover:bg-surface-200
                         flex items-center justify-center text-surface-500 transition-colors">
                  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6L6 18M6 6l12 12"/>
                  </svg>
                </button>
              </div>

              <!-- Cuerpo -->
              <div class="overflow-y-auto p-7 space-y-6 bg-surface-50/30">

                <!-- Error banner -->
                <Transition
                  enter-active-class="transition-all duration-200 ease-out"
                  enter-from-class="opacity-0 -transurface-y-1" enter-to-class="opacity-100 transurface-y-0"
                >
                  <div v-if="formError"
                    class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-2xl
                           text-red-700 text-sm font-semibold">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    {{ formError }}
                  </div>
                </Transition>

                <!-- Nombre -->
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                    Nombre Completo <span class="text-red-400">*</span>
                  </label>
                  <input
                    v-model="newInstructor.nombre_completo"
                    placeholder="Ej. Juan Pérez García"
                    class="w-full px-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-medium
                           text-surface-900 placeholder:text-surface-400
                           focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all"
                  />
                </div>

                <!-- Teléfono + Estatus -->
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Teléfono</label>
                    <input
                      v-model="newInstructor.telefono"
                      placeholder="Opcional"
                      class="w-full px-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-medium
                             text-surface-900 placeholder:text-surface-400
                             focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all"
                    />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estatus</label>
                    <Select v-model="newInstructor.estatus" :options="OPT_ESTATUS_FORM"
                            option-label="label" option-value="value" class="w-full" />
                  </div>
                </div>

                <!-- Correo -->
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Correo Electrónico</label>
                  <input
                    v-model="newInstructor.correo_electronico"
                    type="email"
                    placeholder="instructor@socdep.com"
                    class="w-full px-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-medium
                           text-surface-900 placeholder:text-surface-400
                           focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all"
                  />
                </div>

                <!-- Fechas -->
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Fecha de Nacimiento</label>
                    <DatePicker v-model="newInstructor.fecha_nacimiento" dateFormat="yy-mm-dd"
                      showIcon iconDisplay="input" placeholder="yyyy-mm-dd" class="w-full" :manualInput="false" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Fecha de Afiliación</label>
                    <DatePicker v-model="newInstructor.fecha_afiliacion" dateFormat="yy-mm-dd"
                      showIcon iconDisplay="input" placeholder="yyyy-mm-dd" class="w-full" :manualInput="false" />
                  </div>
                </div>

                <!-- Horario -->
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Hora Entrada</label>
                    <DatePicker v-model="newInstructor.hora_entrada" timeOnly hourFormat="24"
                      placeholder="00:00" class="w-full" :manualInput="false" showOnFocus fluid />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Hora Salida</label>
                    <DatePicker v-model="newInstructor.hora_salida" timeOnly hourFormat="24"
                      placeholder="00:00" class="w-full" :manualInput="false" showOnFocus fluid />
                  </div>
                </div>

                <!-- Selector de disciplinas — estilo premium -->
                <div class="space-y-2 mt-4">
                  <label class="text-[10px] font-black uppercase tracking-widest text-surface-500 px-1">
                    Disciplinas que Imparte
                  </label>
                  <div v-if="disciplinasList.length === 0" class="text-xs font-bold text-surface-400 italic p-4 text-center bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
                    Cargando disciplinas…
                  </div>
                  <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-56 overflow-y-auto p-1 pr-2 custom-scrollbar">
                    <button
                      v-for="d in disciplinasList"
                      :key="d.id_disciplina"
                      type="button"
                      @click="toggleDisciplina(d.id_disciplina)"
                      class="flex items-center gap-3 px-4 py-3 rounded-[1.25rem] border-2 text-sm font-bold transition-all text-left shadow-sm group relative overflow-hidden"
                      :class="newInstructor.disciplinas.includes(d.id_disciplina)
                        ? 'bg-primary-50 text-primary-700 border-primary-500 ring-4 ring-primary-50 hover:bg-primary-100'
                        : 'bg-white text-surface-400 border-surface-200 hover:border-primary-300 hover:text-primary-600 hover:bg-surface-50 hover:shadow-md hover:-translate-y-0.5'"
                    >
                      <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                        :class="newInstructor.disciplinas.includes(d.id_disciplina)
                          ? 'bg-primary-600 text-white shadow-inner'
                          : 'bg-surface-100 text-surface-400 group-hover:bg-primary-100 group-hover:text-primary-600'">
                        <component :is="getIcon(d.nombre_disciplina)" class="w-4 h-4" />
                      </div>
                      <span class="truncate flex-1">{{ d.nombre_disciplina }}</span>
                      
                      <!-- Icono de Check Dinámico -->
                      <div class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 transition-all border-2"
                           :class="newInstructor.disciplinas.includes(d.id_disciplina) ? 'bg-primary-600 border-primary-600 text-white' : 'border-surface-200 bg-surface-50 text-transparent group-hover:border-primary-300'">
                          <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
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
                <ConfirmButton
                  label="Guardar Instructor"
                  :loading="isSaving"
                  @click="saveNewInstructor"
                />
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

  </main>
</template>
