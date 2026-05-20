<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useWizardStore } from '@/stores/programacion/wizardStore'
import { usePlantillasStore } from '@/stores/programacion/plantillasStore'
import { useAlerts } from '@/composables/useAlerts'
import CalendarioGrid from './CalendarioGrid.vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'
import IconSportCourt from '@/components/icons/IconSportCourt.vue'

const router  = useRouter()
const store   = useWizardStore()
const plantillasStore = usePlantillasStore()
const { toastSuccess, toastError, actionToast } = useAlerts()

// Day order for sorting the local draft list
const DIA_ORDER = { LUNES: 0, MARTES: 1, MIERCOLES: 2, JUEVES: 3, VIERNES: 4, SABADO: 5, DOMINGO: 6 }

// Sorted view of borradorLocal (by day Mon→Sun), preserving original index for actions
const borradorOrdenado = computed(() =>
  store.borradorLocal
    .map((s, i) => ({ ...s, _originalIdx: i }))
    .sort((a, b) => (DIA_ORDER[a.dia_semana] ?? 7) - (DIA_ORDER[b.dia_semana] ?? 7))
)

// ─── Form state ──────────────────────────────────────────────────────────────
const form = ref({
  id_disciplina:      null,
  id_espacio:         null,
  id_instructor:      null,
  dias:               [],
  hora_inicio:        '',
  hora_fin:           '',
  cupo_maximo:        null,
  requiere_inscripcion: false,
})
const formErrors = ref({})

const DIAS_SEMANA = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO']
const DIAS_SHORT  = { LUNES: 'L', MARTES: 'M', MIERCOLES: 'X', JUEVES: 'J', VIERNES: 'V', SABADO: 'S', DOMINGO: 'D' }
const DIAS_LABEL  = { LUNES: 'Lun', MARTES: 'Mar', MIERCOLES: 'Mié', JUEVES: 'Jue', VIERNES: 'Vie', SABADO: 'Sáb', DOMINGO: 'Dom' }

const HORAS = Array.from({ length: 17 }, (_, i) => {
  const h = 6 + i
  return `${String(h).padStart(2, '0')}:00`
})

const horasInicio = computed(() =>
  form.value.hora_fin
    ? HORAS.filter(h => h < form.value.hora_fin)
    : HORAS
)
const horasFin = computed(() =>
  form.value.hora_inicio
    ? HORAS.filter(h => h > form.value.hora_inicio)
    : HORAS
)

// ─── Texto dinámico del botón agregar ────────────────────────────────────────
const textoBotonAgregar = computed(() => {
  const n = form.value.dias.length
  if (n === 0) return '+ Agregar Sesión'
  if (n === 1) return '+ Agregar Sesión'
  return `+ Agregar ${n} Sesiones`
})

// ─── Custom dropdowns ─────────────────────────────────────────────────────────
const openDropdown = ref(null)

function toggleDropdown(name) {
  openDropdown.value = openDropdown.value === name ? null : name
  if (openDropdown.value === 'disciplina') {
    keyBuffer.value = ''
    keyBufferTimer = null
  }
  if (openDropdown.value === 'instructor') {
    keyBufferInst.value = ''
    keyBufferInstTimer = null
  }
}

function closeDropdowns() {
  openDropdown.value = null
}

function onClickOutside(e) {
  if (!e.target.closest('[data-dropdown]')) closeDropdowns()
}

// ─── Búsqueda por teclado en selector de disciplinas ─────────────────────────
const keyBuffer = ref('')
let keyBufferTimer = null
const disciplinaHighlight = ref(null)

function onDisciplinaKey(e) {
  if (openDropdown.value !== 'disciplina') return
  if (e.key === 'Escape') { closeDropdowns(); return }
  if (e.key === 'Enter') {
    e.preventDefault()
    const id = disciplinaHighlight.value ?? form.value.id_disciplina
    if (id !== null) {
      seleccionarDisciplina(id)
    } else {
      closeDropdowns()
    }
    return
  }
  if (e.key.length !== 1) return

  clearTimeout(keyBufferTimer)
  keyBuffer.value += e.key.toLowerCase()
  keyBufferTimer = setTimeout(() => { keyBuffer.value = '' }, 800)

  const match = disciplinasFiltradas.value.find(d =>
    d.nombre_disciplina.toLowerCase().startsWith(keyBuffer.value)
  )
  if (match) {
    disciplinaHighlight.value = match.id_disciplina
    const el = document.getElementById(`disc-opt-${match.id_disciplina}`)
    el?.scrollIntoView({ block: 'nearest' })
  }
}

// ─── Búsqueda por teclado en selector de instructores ────────────────────────
const keyBufferInst = ref('')
let keyBufferInstTimer = null
const instructorHighlight = ref(null)

function onInstructorKey(e) {
  if (openDropdown.value !== 'instructor') return
  if (e.key === 'Escape') { closeDropdowns(); return }
  if (e.key === 'Enter') {
    e.preventDefault()
    const id = instructorHighlight.value ?? form.value.id_instructor
    if (id !== null) {
      seleccionarInstructor(id)
    } else {
      closeDropdowns()
    }
    return
  }
  if (e.key.length !== 1) return

  clearTimeout(keyBufferInstTimer)
  keyBufferInst.value += e.key.toLowerCase()
  keyBufferInstTimer = setTimeout(() => { keyBufferInst.value = '' }, 800)

  const match = instructoresFiltrados.value.find(i =>
    i.nombre_completo.toLowerCase().startsWith(keyBufferInst.value)
  )
  if (match) {
    instructorHighlight.value = match.id_instructor
    const el = document.getElementById(`inst-opt-${match.id_instructor}`)
    el?.scrollIntoView({ block: 'nearest' })
  }
}

onMounted(() => {
  document.addEventListener('click', onClickOutside)
  document.addEventListener('keydown', onDisciplinaKey)
  document.addEventListener('keydown', onInstructorKey)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  document.removeEventListener('keydown', onDisciplinaKey)
  document.removeEventListener('keydown', onInstructorKey)
})

// ─── Publish modal ────────────────────────────────────────────────────────────
const showPublishModal = ref(false)

// ─── Objetos seleccionados ────────────────────────────────────────────────────
const disciplinaSeleccionada = computed(() =>
  store.disciplinas.find(d => d.id_disciplina === form.value.id_disciplina) ?? null
)
const espacioSeleccionado = computed(() =>
  store.espacios.find(e => e.id_espacio === form.value.id_espacio) ?? null
)
const instructorSeleccionado = computed(() =>
  store.instructores.find(i => i.id_instructor === form.value.id_instructor) ?? null
)

// ─── Filtrado cruzado multidireccional ────────────────────────────────────────
const disciplinasFiltradas = computed(() => {
  const { id_instructor, id_espacio } = form.value
  return store.disciplinas.filter(d => {
    const okInstructor = id_instructor
      ? store.instructores.find(i => i.id_instructor === id_instructor)
          ?.disciplinas_ids?.includes(d.id_disciplina) ?? false
      : true
    const okEspacio = id_espacio
      ? store.espacios.find(e => e.id_espacio === id_espacio)
          ?.disciplinas_ids?.includes(d.id_disciplina) ?? false
      : true
    return okInstructor && okEspacio
  })
})

const instructoresFiltrados = computed(() => {
  const { id_disciplina } = form.value
  const base = store.instructores.filter(i => {
    const okDisciplina = id_disciplina
      ? Array.isArray(i.disciplinas_ids) && i.disciplinas_ids.includes(id_disciplina)
      : true
    return okDisciplina
  })
  return base.sort((a, b) =>
    a.nombre_completo.localeCompare(b.nombre_completo, 'es', { sensitivity: 'base' })
  )
})

const espaciosFiltrados = computed(() => {
  const { id_disciplina } = form.value
  const base = store.espacios.filter(e => {
    if (e.es_clase_programada === false) return false
    const okDisciplina = id_disciplina
      ? Array.isArray(e.disciplinas_ids) && e.disciplinas_ids.includes(id_disciplina)
      : true
    return okDisciplina
  })
  return [...base].sort((a, b) =>
    a.nombre_espacio.localeCompare(b.nombre_espacio, 'es', { numeric: true, sensitivity: 'base' })
  )
})

const placeholderDisciplina = computed(() => {
  if (!disciplinasFiltradas.value.length) return 'Sin disciplinas para esta combinación'
  return 'Seleccionar disciplina...'
})
const placeholderInstructor = computed(() => {
  if (!instructoresFiltrados.value.length) return 'Sin instructores disponibles'
  return 'Seleccionar instructor...'
})
const placeholderEspacio = computed(() => {
  if (!espaciosFiltrados.value.length) return 'Sin espacios para esta combinación'
  return 'Seleccionar espacio...'
})

// ─── Acciones de selección ────────────────────────────────────────────────────
function seleccionarDisciplina(id) {
  if (form.value.id_disciplina !== id) {
    if (id !== null && form.value.id_instructor !== null) {
      const instSigueValido = store.instructores
        .find(i => i.id_instructor === form.value.id_instructor)
        ?.disciplinas_ids?.includes(id) ?? false
      if (!instSigueValido) form.value.id_instructor = null
    }
    if (id !== null && form.value.id_espacio !== null) {
      const espacioSigueValido = store.espacios
        .find(e => e.id_espacio === form.value.id_espacio)
        ?.disciplinas_ids?.includes(id) ?? false
      if (!espacioSigueValido) form.value.id_espacio = null
    }
    form.value.id_disciplina = id
  }
  disciplinaHighlight.value = null
  keyBuffer.value = ''
  closeDropdowns()
}

function seleccionarEspacio(id) {
  if (id !== null && form.value.id_disciplina !== null) {
    const compatible = store.espacios
      .find(e => e.id_espacio === id)
      ?.disciplinas_ids?.includes(form.value.id_disciplina) ?? false
    if (!compatible) form.value.id_disciplina = null
  }
  form.value.id_espacio = id
  closeDropdowns()
}

function seleccionarInstructor(id) {
  if (id !== null && form.value.id_disciplina !== null) {
    const imparte = store.instructores
      .find(i => i.id_instructor === id)
      ?.disciplinas_ids?.includes(form.value.id_disciplina) ?? false
    if (!imparte) form.value.id_disciplina = null
  }
  form.value.id_instructor = id
  instructorHighlight.value = null
  keyBufferInst.value = ''
  closeDropdowns()
}

function resetSelects() {
  form.value.id_disciplina = null
  form.value.id_espacio    = null
  form.value.id_instructor = null
  disciplinaHighlight.value = null
  instructorHighlight.value = null
  keyBuffer.value = ''
  keyBufferInst.value = ''
  closeDropdowns()
}

function seleccionarHora(campo, hora) {
  form.value[campo] = hora
  if (campo === 'hora_inicio' && form.value.hora_fin && form.value.hora_fin <= hora) {
    form.value.hora_fin = ''
  }
  if (campo === 'hora_fin' && form.value.hora_inicio && form.value.hora_inicio >= hora) {
    form.value.hora_inicio = ''
  }
  closeDropdowns()
}

// ─── Iniciales del instructor (avatar) ───────────────────────────────────────
function getInitials(nombre) {
  if (!nombre) return '?'
  return nombre.trim().split(/\s+/).slice(0, 2).map(p => p[0].toUpperCase()).join('')
}

const AVATAR_GRADIENTS = [
  'from-blue-500 to-indigo-600',
  'from-violet-500 to-purple-600',
  'from-emerald-500 to-teal-600',
  'from-rose-500 to-pink-600',
  'from-amber-500 to-orange-600',
  'from-cyan-500 to-sky-600',
]

function avatarGradient(nombre) {
  if (!nombre) return AVATAR_GRADIENTS[0]
  const idx = nombre.charCodeAt(0) % AVATAR_GRADIENTS.length
  return AVATAR_GRADIENTS[idx]
}

// ─── Validación del formulario ────────────────────────────────────────────────
function validarForm() {
  const e = {}
  if (!form.value.id_disciplina)      e.id_disciplina  = 'Selecciona una disciplina'
  if (!form.value.id_espacio)         e.id_espacio     = 'Selecciona un espacio'
  if (!form.value.id_instructor)      e.id_instructor  = 'Selecciona un instructor'
  if (form.value.dias.length === 0)   e.dias           = 'Selecciona al menos un día'
  if (!form.value.hora_inicio)        e.hora_inicio    = 'Selecciona hora de inicio'
  if (!form.value.hora_fin)           e.hora_fin       = 'Selecciona hora de fin'
  if (form.value.hora_inicio && form.value.hora_fin && form.value.hora_inicio >= form.value.hora_fin) {
    e.hora_fin = 'Debe ser mayor a la hora de inicio'
  }
  if (!form.value.cupo_maximo || form.value.cupo_maximo < 1) {
    e.cupo_maximo = 'El cupo mínimo es 1'
  } else if (form.value.cupo_maximo > 40) {
    e.cupo_maximo = 'El cupo máximo permitido es 40'
  }
  return e
}

// ─── Agregar sesiones al borrador local ──────────────────────────────────────
function handleAgregarSesiones() {
  formErrors.value = validarForm()
  if (Object.keys(formErrors.value).length > 0) return

  const espacio    = store.espacios.find(e => e.id_espacio       === form.value.id_espacio)
  const disciplina = store.disciplinas.find(d => d.id_disciplina === form.value.id_disciplina)
  const instructor = store.instructores.find(i => i.id_instructor === form.value.id_instructor)

  const ok = store.agregarSesiones({
    id_disciplina:      form.value.id_disciplina,
    id_espacio:         form.value.id_espacio,
    id_instructor:      form.value.id_instructor,
    dias:               [...form.value.dias],
    hora_inicio:        form.value.hora_inicio,
    hora_fin:           form.value.hora_fin,
    cupo_maximo:        form.value.cupo_maximo,
    requiere_inscripcion: form.value.requiere_inscripcion,
    _espacio_nombre:    espacio?.nombre_espacio    ?? '',
    _disciplina_nombre: disciplina?.nombre_disciplina ?? '',
    _instructor_nombre: instructor?.nombre_completo   ?? '',
  })

  if (ok) {
    const count = form.value.dias.length
    resetForm()
    actionToast(
      count === 1 ? '1 sesión agregada al borrador local' : `${count} sesiones agregadas al borrador local`,
      'success'
    )
  }
}

function resetForm() {
  form.value = {
    id_disciplina: null,
    id_espacio: null,
    id_instructor: null,
    dias: [],
    hora_inicio: '',
    hora_fin: '',
    cupo_maximo: null,
    requiere_inscripcion: false,
  }
  formErrors.value = {}
}

function toggleDia(dia) {
  const idx = form.value.dias.indexOf(dia)
  if (idx === -1) form.value.dias.push(dia)
  else form.value.dias.splice(idx, 1)
}

// ─── Click en tarjeta del borrador local → resalta en calendario ─────────────
function handleClickTarjeta(idx) {
  store.seleccionarSesion(idx)
  resetForm()
}

// ─── Guardar progreso ─────────────────────────────────────────────────────────
async function handleGuardarProgreso() {
  try {
    await store.guardarProgreso()
    toastSuccess('Progreso guardado correctamente')
  } catch {
    toastError('Error al guardar el progreso. Intenta de nuevo.')
  }
}

// ─── Publicar ─────────────────────────────────────────────────────────────────
async function confirmarPublicacion() {
  showPublishModal.value = false
  try {
    await store.publicarProgramacion()
    if (store.publishSuccess) {
      toastSuccess('Programación publicada exitosamente')
      router.push({ name: 'admin-dashboard' })
    }
  } catch {
    toastError('Error del servidor. Intenta de nuevo.')
  }
}

// ─── Init ─────────────────────────────────────────────────────────────────────
onMounted(async () => {
  if (plantillasStore.plantillas.length === 0) {
    await plantillasStore.fetchPlantillas()
  }
  if (!plantillasStore.plantillaActiva) return
  try {
    await Promise.all([store.fetchDependencias(), store.crearDraft()])
  } catch {
    // errorInit and errorDeps handled by store
  }
})
</script>

<template>
  <div class="h-full flex flex-col bg-slate-50 font-sans">

    <!-- ── PAGE HEADER ── -->
    <div class="px-8 py-5 border-b border-slate-200 bg-white flex items-center justify-between shrink-0 shadow-sm">
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center shadow-sm">
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
          </svg>
        </div>
        <div>
          <p class="text-[10px] uppercase font-black tracking-widest text-slate-400 mb-0.5">Subgerencia Deportiva</p>
          <h1 class="text-xl font-black text-slate-800 tracking-tight leading-none">Diseñador de Sesiones</h1>
          <p class="text-xs font-medium text-slate-400 mt-0.5">Construye el cronograma operativo semanal</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <span v-if="store.isSavingDraft" class="text-xs text-slate-400 flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5">
          <svg class="animate-spin h-3 w-3 text-blue-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          <span class="font-semibold">Guardando borrador...</span>
        </span>

        <div v-if="store.tieneActividades" class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
          <span class="text-xs font-bold text-slate-600">{{ store.totalActividades }} sesión{{ store.totalActividades !== 1 ? 'es' : '' }}</span>
        </div>

        <button
          :disabled="!store.tieneActividades || store.isPublishing"
          @click="showPublishModal = true"
          :class="[
            'inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-extrabold transition-all duration-200',
            store.tieneActividades && !store.isPublishing
              ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm hover:shadow-md active:scale-[0.98]'
              : 'bg-slate-100 text-slate-400 cursor-not-allowed'
          ]"
        >
          <svg v-if="!store.isPublishing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          {{ store.isPublishing ? 'Publicando...' : 'Publicar programación' }}
        </button>
      </div>
    </div>

    <!-- ── LOADING PLANTILLAS ── -->
    <div v-if="plantillasStore.isLoading" class="flex-1 flex items-center justify-center">
      <div class="text-center text-slate-500">
        <div class="animate-spin rounded-full h-10 w-10 border-2 border-slate-200 border-t-blue-600 mx-auto mb-4" />
        <p class="text-sm font-semibold">Cargando plantillas...</p>
      </div>
    </div>

    <!-- ── SIN PLANTILLA ACTIVA ── -->
    <div v-else-if="!plantillasStore.plantillaActiva" class="flex-1 flex items-center justify-center p-8">
      <div class="max-w-sm w-full text-center">
        <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center mx-auto mb-5">
          <svg class="w-8 h-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
        </div>
        <p class="text-[10px] uppercase font-black tracking-widest text-amber-500 mb-2">Acción requerida</p>
        <h2 class="text-xl font-black text-slate-800 tracking-tight mb-2">Sin plantilla activa</h2>
        <p class="text-sm font-medium text-slate-500 leading-relaxed">
          Para diseñar sesiones, primero activa una plantilla desde
          <strong class="text-slate-700">Gestión de Plantillas</strong>.
        </p>
        <div class="mt-5 inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg text-xs font-bold text-amber-700">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          Gestión de Plantillas → Editar → Estatus Activo
        </div>
      </div>
    </div>

    <!-- ── INIT LOADING ── -->
    <div v-else-if="store.isCreatingDraft || store.isLoadingDeps" class="flex-1 flex items-center justify-center">
      <div class="text-center text-slate-500">
        <div class="animate-spin rounded-full h-10 w-10 border-2 border-slate-200 border-t-blue-600 mx-auto mb-4" />
        <p class="text-sm font-semibold">Inicializando diseñador...</p>
      </div>
    </div>

    <!-- ── INIT ERROR ── -->
    <div v-else-if="store.errorInit || store.errorDeps" class="flex-1 flex items-center justify-center">
      <div class="text-center bg-white rounded-3xl border border-slate-200 shadow-sm p-10">
        <p class="text-red-600 text-sm font-medium mb-4">{{ store.errorInit || store.errorDeps }}</p>
        <button
          @click="async () => { try { await Promise.all([store.fetchDependencias(), store.crearDraft()]) } catch {} }"
          class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-extrabold hover:bg-blue-700 transition-colors"
        >
          Reintentar
        </button>
      </div>
    </div>

    <!-- ── MAIN TWO-PANEL LAYOUT ── -->
    <div v-else class="flex-1 overflow-hidden grid grid-cols-12 gap-0 min-h-0">

      <!-- ════════════════════════════════════════════════
           LEFT PANEL — Formulario (col 1–4)
      ════════════════════════════════════════════════ -->
      <div class="col-span-4 border-r border-slate-200 bg-white flex flex-col min-h-0 overflow-hidden">

        <!-- Formulario scroll wrapper — takes exactly half the panel, rest for draft list -->
        <div class="overflow-y-auto shrink-0" style="max-height: 60%">

        <!-- ── Identificador de plantilla en edición ── -->
        <div class="px-6 pt-4 pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-100">
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <div class="min-w-0">
              <p class="text-[9px] uppercase font-black tracking-widest text-slate-400 leading-none mb-0.5">Editando plantilla</p>
              <p class="text-sm font-bold text-slate-700 truncate leading-snug">
                {{ plantillasStore.plantillaActiva?.nombre_plantilla ?? 'Plantilla sin nombre' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Formulario nueva sesión -->
        <div class="p-6 border-b border-slate-100">
          <div class="flex items-center gap-2.5 mb-5">
            <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
            </div>
            <div>
              <p class="text-[9px] uppercase font-black tracking-widest text-slate-400">Configuración</p>
              <h2 class="text-sm font-extrabold text-slate-800 leading-none">Nueva sesión</h2>
            </div>
          </div>

          <!-- ── SELECTOR DISCIPLINA ── -->
          <div class="mb-3" data-dropdown>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600">Disciplina</label>
              <button
                v-if="form.id_disciplina || form.id_espacio || form.id_instructor"
                type="button"
                @click="resetSelects"
                class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400 hover:text-red-500 transition-colors"
              >
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Limpiar selección
              </button>
            </div>
            <div class="relative" data-dropdown>
              <button
                type="button"
                data-dropdown
                @click.stop="toggleDropdown('disciplina')"
                :class="[
                  'w-full flex items-center gap-3 px-4 py-3 rounded-xl border text-sm font-semibold transition-all duration-150 text-left',
                  formErrors.id_disciplina
                    ? 'border-red-300 bg-red-50'
                    : openDropdown === 'disciplina'
                      ? 'border-blue-400 bg-white shadow-md ring-2 ring-blue-500/20'
                      : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white'
                ]"
              >
                <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-blue-50 text-blue-600">
                  <DisciplineIcon
                    :name="disciplinaSeleccionada?.nombre_disciplina ?? ''"
                    class="w-5 h-5"
                  />
                </span>
                <span :class="['flex-1 truncate', disciplinaSeleccionada ? 'text-slate-800' : 'text-slate-400']">
                  {{ disciplinaSeleccionada?.nombre_disciplina ?? placeholderDisciplina }}
                </span>
                <button
                  v-if="disciplinaSeleccionada"
                  type="button"
                  data-dropdown
                  @click.stop="seleccionarDisciplina(null)"
                  class="w-5 h-5 rounded-full bg-slate-200 hover:bg-red-100 hover:text-red-500 flex items-center justify-center text-slate-500 shrink-0 transition-colors"
                >
                  <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-150" :class="openDropdown === 'disciplina' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
              </button>

              <div
                v-if="openDropdown === 'disciplina'"
                data-dropdown
                class="absolute top-full left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl z-30 overflow-hidden"
              >
                <div class="max-h-52 overflow-y-auto py-1">
                  <button
                    type="button"
                    data-dropdown
                    @click.stop="seleccionarDisciplina(null)"
                    :class="[
                      'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors text-left',
                      form.id_disciplina === null ? 'bg-slate-100 text-slate-600' : 'text-slate-400 hover:bg-slate-50'
                    ]"
                  >
                    <span class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                      <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </span>
                    <span class="font-semibold italic">Sin disciplina</span>
                  </button>
                  <div class="border-t border-slate-100 my-1" />
                  <div v-if="disciplinasFiltradas.length === 0" class="px-4 py-4 text-xs text-slate-400 text-center font-medium">
                    Sin disciplinas para esta combinación
                  </div>
                  <button
                    v-for="d in disciplinasFiltradas"
                    :key="d.id_disciplina"
                    :id="`disc-opt-${d.id_disciplina}`"
                    type="button"
                    data-dropdown
                    @click.stop="seleccionarDisciplina(d.id_disciplina)"
                    :class="[
                      'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors text-left',
                      form.id_disciplina === d.id_disciplina
                        ? 'bg-blue-50 text-blue-700'
                        : disciplinaHighlight === d.id_disciplina
                          ? 'bg-blue-100/70 text-blue-600'
                          : 'text-slate-700 hover:bg-slate-100'
                    ]"
                  >
                    <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                      <DisciplineIcon :name="d.nombre_disciplina" class="w-4 h-4" />
                    </span>
                    <span class="font-semibold truncate">{{ d.nombre_disciplina }}</span>
                    <svg v-if="form.id_disciplina === d.id_disciplina" class="w-4 h-4 text-blue-600 ml-auto shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            <p v-if="formErrors.id_disciplina" class="text-red-500 text-xs mt-1 font-medium flex items-center gap-1">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
              {{ formErrors.id_disciplina }}
            </p>
          </div>

          <!-- ── SELECTOR ESPACIO ── -->
          <div class="mb-3" data-dropdown>
            <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">Espacio</label>
            <div class="relative" data-dropdown>
              <button
                type="button"
                data-dropdown
                @click.stop="toggleDropdown('espacio')"
                :class="[
                  'w-full flex items-center gap-3 px-4 py-3 rounded-xl border text-sm font-semibold transition-all duration-150 text-left',
                  formErrors.id_espacio
                    ? 'border-red-300 bg-red-50'
                    : openDropdown === 'espacio'
                      ? 'border-violet-500 bg-white shadow-md ring-2 ring-violet-500/25'
                      : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white'
                ]"
              >
                <span class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center shrink-0">
                  <IconSportCourt class="w-4 h-4 text-violet-700" />
                </span>
                <span :class="['flex-1 truncate', espacioSeleccionado ? 'text-slate-800' : 'text-slate-400']">
                  {{ espacioSeleccionado ? `${espacioSeleccionado.nombre_espacio} (cap. ${espacioSeleccionado.capacidad_maxima})` : placeholderEspacio }}
                </span>
                <button
                  v-if="espacioSeleccionado"
                  type="button"
                  data-dropdown
                  @click.stop="seleccionarEspacio(null)"
                  class="w-5 h-5 rounded-full bg-slate-200 hover:bg-red-100 hover:text-red-500 flex items-center justify-center text-slate-500 shrink-0 transition-colors"
                >
                  <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-150" :class="openDropdown === 'espacio' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
              </button>
              <div
                v-if="openDropdown === 'espacio'"
                data-dropdown
                class="absolute top-full left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl z-30 overflow-hidden"
              >
                <div class="max-h-52 overflow-y-auto py-1">
                  <button
                    type="button"
                    data-dropdown
                    @click.stop="seleccionarEspacio(null)"
                    :class="[
                      'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors text-left',
                      form.id_espacio === null ? 'bg-slate-100 text-slate-600' : 'text-slate-400 hover:bg-slate-50'
                    ]"
                  >
                    <span class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                      <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </span>
                    <span class="font-semibold italic">Sin espacio</span>
                  </button>
                  <div class="border-t border-slate-100 my-1" />
                  <div v-if="espaciosFiltrados.length === 0" class="px-4 py-4 text-xs text-slate-400 text-center font-medium">
                    Sin espacios disponibles para esta disciplina
                  </div>
                  <button
                    v-for="e in espaciosFiltrados"
                    :key="e.id_espacio"
                    type="button"
                    data-dropdown
                    @click.stop="seleccionarEspacio(e.id_espacio)"
                    :class="[
                      'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors text-left',
                      form.id_espacio === e.id_espacio ? 'bg-violet-100 text-violet-700' : 'text-slate-700 hover:bg-violet-100'
                    ]"
                  >
                    <div class="flex-1 min-w-0">
                      <p class="font-semibold truncate">{{ e.nombre_espacio }}</p>
                      <p class="text-xs text-slate-400 font-medium">Capacidad: {{ e.capacidad_maxima }}</p>
                    </div>
                    <svg v-if="form.id_espacio === e.id_espacio" class="w-4 h-4 text-violet-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            <p v-if="formErrors.id_espacio" class="text-red-500 text-xs mt-1 font-medium flex items-center gap-1">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
              {{ formErrors.id_espacio }}
            </p>
          </div>

          <!-- ── SELECTOR INSTRUCTOR ── -->
          <div class="mb-4" data-dropdown>
            <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">Instructor</label>
            <div class="relative" data-dropdown>
              <button
                type="button"
                data-dropdown
                @click.stop="toggleDropdown('instructor')"
                :class="[
                  'w-full flex items-center gap-3 px-4 py-3 rounded-xl border text-sm font-semibold transition-all duration-150 text-left',
                  formErrors.id_instructor
                    ? 'border-red-300 bg-red-50'
                    : openDropdown === 'instructor'
                      ? 'border-emerald-400 bg-white shadow-md ring-2 ring-emerald-400/20'
                      : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white'
                ]"
              >
                <div v-if="instructorSeleccionado"
                  :class="['w-8 h-8 rounded-full bg-linear-to-br flex items-center justify-center shrink-0 text-white text-xs font-black', avatarGradient(instructorSeleccionado.nombre_completo)]"
                >
                  {{ getInitials(instructorSeleccionado.nombre_completo) }}
                </div>
                <span v-else class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                  <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                  </svg>
                </span>
                <span :class="['flex-1 truncate', instructorSeleccionado ? 'text-slate-800' : 'text-slate-400']">
                  {{ instructorSeleccionado?.nombre_completo ?? placeholderInstructor }}
                </span>
                <button
                  v-if="instructorSeleccionado"
                  type="button"
                  data-dropdown
                  @click.stop="seleccionarInstructor(null)"
                  class="w-5 h-5 rounded-full bg-slate-200 hover:bg-red-100 hover:text-red-500 flex items-center justify-center text-slate-500 shrink-0 transition-colors"
                >
                  <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-150" :class="openDropdown === 'instructor' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
              </button>
              <div
                v-if="openDropdown === 'instructor'"
                data-dropdown
                class="absolute top-full left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl z-30 overflow-hidden"
              >
                <div class="max-h-52 overflow-y-auto py-1">
                  <button
                    type="button"
                    data-dropdown
                    @click.stop="seleccionarInstructor(null)"
                    :class="[
                      'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors text-left',
                      form.id_instructor === null ? 'bg-slate-100 text-slate-600' : 'text-slate-400 hover:bg-slate-50'
                    ]"
                  >
                    <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                      <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </span>
                    <span class="font-semibold italic">Sin instructor</span>
                  </button>
                  <div class="border-t border-slate-100 my-1" />
                  <div v-if="instructoresFiltrados.length === 0" class="px-4 py-4 text-xs text-slate-400 text-center font-medium">
                    Sin instructores disponibles para esta combinación
                  </div>
                  <button
                    v-for="inst in instructoresFiltrados"
                    :key="inst.id_instructor"
                    :id="`inst-opt-${inst.id_instructor}`"
                    type="button"
                    data-dropdown
                    @click.stop="seleccionarInstructor(inst.id_instructor)"
                    :class="[
                      'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors text-left',
                      form.id_instructor === inst.id_instructor
                        ? 'bg-emerald-50 text-emerald-700'
                        : instructorHighlight === inst.id_instructor
                          ? 'bg-emerald-50/70 text-emerald-600'
                          : 'text-slate-700 hover:bg-emerald-50'
                    ]"
                  >
                    <div :class="['w-8 h-8 rounded-full bg-linear-to-br flex items-center justify-center text-white text-xs font-black shrink-0', avatarGradient(inst.nombre_completo)]">
                      {{ getInitials(inst.nombre_completo) }}
                    </div>
                    <span class="font-semibold truncate flex-1">{{ inst.nombre_completo }}</span>
                    <svg v-if="form.id_instructor === inst.id_instructor" class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            <p v-if="formErrors.id_instructor" class="text-red-500 text-xs mt-1 font-medium flex items-center gap-1">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
              {{ formErrors.id_instructor }}
            </p>
          </div>

          <!-- ── SEPARADOR ── -->
          <div class="border-t border-slate-100 my-4" />

          <!-- ── DÍAS DE LA SEMANA ── -->
          <div class="mb-4">
            <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-2.5">Días de la semana</label>
            <div class="flex gap-1.5">
              <button
                v-for="dia in DIAS_SEMANA"
                :key="dia"
                type="button"
                @click="toggleDia(dia)"
                :title="DIAS_LABEL[dia]"
                :class="[
                  'relative flex-1 flex items-center justify-center h-9 rounded-lg text-xs font-black transition-all duration-150 select-none',
                  form.dias.includes(dia)
                    ? 'bg-blue-600 text-white shadow-sm shadow-blue-300'
                    : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700'
                ]"
              >
                {{ DIAS_SHORT[dia] }}
                <span
                  v-if="form.dias.includes(dia)"
                  class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-blue-400"
                />
              </button>
            </div>
            <p v-if="formErrors.dias" class="text-red-500 text-xs mt-1.5 font-medium flex items-center gap-1">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
              {{ formErrors.dias }}
            </p>
          </div>

          <!-- ── HORARIOS ── -->
          <div class="grid grid-cols-2 gap-2 mb-3">
            <!-- Hora inicio -->
            <div data-dropdown>
              <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">Hora inicio</label>
              <div class="relative" data-dropdown>
                <button
                  type="button"
                  data-dropdown
                  @click.stop="toggleDropdown('hora_inicio')"
                  :class="[
                    'w-full flex items-center gap-2 px-3 py-3 rounded-xl border text-sm font-bold transition-all duration-150',
                    formErrors.hora_inicio
                      ? 'border-red-300 bg-red-50 text-red-700'
                      : openDropdown === 'hora_inicio'
                        ? 'border-blue-400 bg-white shadow-md ring-2 ring-blue-500/20 text-slate-800'
                        : form.hora_inicio
                          ? 'border-slate-200 bg-white text-slate-800 hover:border-slate-300'
                          : 'border-slate-200 bg-slate-50 text-slate-400 hover:border-slate-300'
                  ]"
                >
                  <svg class="w-3.5 h-3.5 shrink-0" :class="form.hora_inicio ? 'text-blue-500' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span class="flex-1 text-center">{{ form.hora_inicio || '--:--' }}</span>
                  <svg class="w-3 h-3 text-slate-400 shrink-0 transition-transform duration-150" :class="openDropdown === 'hora_inicio' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                  </svg>
                </button>
                <div
                  v-if="openDropdown === 'hora_inicio'"
                  data-dropdown
                  class="absolute top-full left-0 right-0 mt-1.5 bg-white border border-slate-100 rounded-xl shadow-xl z-30 overflow-hidden"
                >
                  <div class="max-h-48 overflow-y-auto py-1">
                    <button
                      v-for="hora in horasInicio"
                      :key="hora"
                      type="button"
                      data-dropdown
                      @click.stop="seleccionarHora('hora_inicio', hora)"
                      :class="[
                        'w-full px-4 py-2 text-sm font-bold text-left transition-colors',
                        form.hora_inicio === hora ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-slate-100'
                      ]"
                    >{{ hora }}</button>
                  </div>
                </div>
              </div>
              <p v-if="formErrors.hora_inicio" class="text-red-500 text-[10px] mt-1 font-medium flex items-center gap-1">
                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ formErrors.hora_inicio }}
              </p>
            </div>

            <!-- Hora fin -->
            <div data-dropdown>
              <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">Hora fin</label>
              <div class="relative" data-dropdown>
                <button
                  type="button"
                  data-dropdown
                  @click.stop="toggleDropdown('hora_fin')"
                  :class="[
                    'w-full flex items-center gap-2 px-3 py-3 rounded-xl border text-sm font-bold transition-all duration-150',
                    formErrors.hora_fin
                      ? 'border-red-300 bg-red-50 text-red-700'
                      : openDropdown === 'hora_fin'
                        ? 'border-blue-400 bg-white shadow-md ring-2 ring-blue-500/20 text-slate-800'
                        : form.hora_fin
                          ? 'border-slate-200 bg-white text-slate-800 hover:border-slate-300'
                          : 'border-slate-200 bg-slate-50 text-slate-400 hover:border-slate-300'
                  ]"
                >
                  <svg class="w-3.5 h-3.5 shrink-0" :class="form.hora_fin ? 'text-blue-500' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span class="flex-1 text-center">{{ form.hora_fin || '--:--' }}</span>
                  <svg class="w-3 h-3 text-slate-400 shrink-0 transition-transform duration-150" :class="openDropdown === 'hora_fin' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                  </svg>
                </button>
                <div
                  v-if="openDropdown === 'hora_fin'"
                  data-dropdown
                  class="absolute top-full left-0 right-0 mt-1.5 bg-white border border-slate-100 rounded-xl shadow-xl z-30 overflow-hidden"
                >
                  <div class="max-h-48 overflow-y-auto py-1">
                    <button
                      v-for="hora in horasFin"
                      :key="hora"
                      type="button"
                      data-dropdown
                      @click.stop="seleccionarHora('hora_fin', hora)"
                      :class="[
                        'w-full px-4 py-2 text-sm font-bold text-left transition-colors',
                        form.hora_fin === hora ? 'bg-blue-600 text-white' : 'text-slate-700 hover:bg-slate-100'
                      ]"
                    >{{ hora }}</button>
                  </div>
                </div>
              </div>
              <p v-if="formErrors.hora_fin" class="text-red-500 text-[10px] mt-1 font-medium flex items-center gap-1">
                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ formErrors.hora_fin }}
              </p>
            </div>
          </div>

          <!-- ── CUPO + TIPO DE CLASE ── -->
          <div class="grid grid-cols-2 gap-2 mb-4">
            <div>
              <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">Cupo máximo</label>
              <div :class="[
                'flex items-center gap-2 px-3 py-3 rounded-xl border transition-all',
                formErrors.cupo_maximo ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-slate-50 focus-within:border-blue-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-500/20'
              ]">
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
                <input
                  type="number"
                  v-model.number="form.cupo_maximo"
                  min="1"
                  max="40"
                  placeholder="0"
                  class="flex-1 bg-transparent text-sm font-bold text-slate-800 placeholder-slate-400 focus:outline-none w-0"
                />
              </div>
              <p v-if="formErrors.cupo_maximo" class="text-red-500 text-[10px] mt-1 font-medium flex items-center gap-1">
                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ formErrors.cupo_maximo }}
              </p>
            </div>

            <div>
              <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">Tipo de clase</label>
              <div class="flex rounded-xl border border-slate-200 overflow-hidden bg-slate-50 p-0.5 gap-0.5">
                <button
                  type="button"
                  @click="form.requiere_inscripcion = false"
                  :class="[
                    'flex-1 flex items-center justify-center gap-1 py-2.5 rounded-lg text-xs font-extrabold transition-all duration-150',
                    !form.requiere_inscripcion ? 'bg-teal-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'
                  ]"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                  </svg>
                  Abierta
                </button>
                <button
                  type="button"
                  @click="form.requiere_inscripcion = true"
                  :class="[
                    'flex-1 flex items-center justify-center gap-1 py-2.5 rounded-lg text-xs font-extrabold transition-all duration-150',
                    form.requiere_inscripcion ? 'bg-rose-700 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'
                  ]"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                  </svg>
                  Inscripción
                </button>
              </div>
            </div>
          </div>

          <!-- ── COLISIONES ── -->
          <div v-if="store.colisionesLocales.length > 0" class="mb-3 space-y-1.5">
            <div class="flex items-center gap-1.5 mb-1">
              <svg class="w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
              <p class="text-xs font-extrabold text-red-700">Conflictos detectados</p>
            </div>
            <div
              v-for="(c, i) in store.colisionesLocales"
              :key="i"
              class="bg-red-50 border border-red-200 rounded-xl p-3 text-xs text-red-700"
            >
              <p class="font-bold capitalize">{{ c.tipo === 'espacio' ? 'Espacio' : 'Instructor' }}: {{ c.nombre }}</p>
              <p class="text-red-600 mt-0.5">{{ c.dia }} · Nuevo: {{ c.horario_nuevo }} · Existente: {{ c.horario_existente }}</p>
            </div>
          </div>

          <!-- ── BOTÓN AGREGAR (texto dinámico) ── -->
          <button
            @click="handleAgregarSesiones"
            class="w-full bg-blue-600 text-white py-3 rounded-xl text-sm font-extrabold hover:bg-blue-700 active:scale-[0.98] transition-all duration-150 shadow-sm hover:shadow-md flex items-center justify-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            {{ textoBotonAgregar }}
          </button>
        </div>

        </div><!-- end form scroll wrapper -->

        <!-- ── BORRADOR LOCAL ── -->
        <!-- min-h-0 is critical: lets flex children shrink below their content size -->
        <div class="flex flex-col min-h-0 flex-1">
          <!-- Header — never shrinks -->
          <div class="px-5 pt-4 pb-3 flex items-center justify-between shrink-0">
            <div>
              <p class="text-[9px] uppercase font-black tracking-widest text-slate-400">Sin guardar</p>
              <h2 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                Borrador local
                <span
                  :class="[
                    'inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-black',
                    store.tieneBorradorLocal ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-400'
                  ]"
                >
                  {{ store.borradorLocal.length }}
                </span>
              </h2>
            </div>
          </div>

          <!-- Scrollable list — takes remaining space between header and footer -->
          <div class="flex-1 overflow-y-auto px-5 min-h-0">
            <!-- Empty state -->
            <div
              v-if="!store.tieneBorradorLocal"
              class="border-2 border-dashed border-slate-100 rounded-2xl py-8 text-center"
            >
              <svg class="w-7 h-7 text-slate-200 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
              <p class="text-xs font-semibold text-slate-400">Sin sesiones en borrador</p>
              <p class="text-[10px] text-slate-300 mt-0.5">Usa el formulario para agregar</p>
            </div>

            <!-- Lista de tarjetas del borrador local — ordenadas L→D -->
            <div v-else class="space-y-1.5 pb-2 pr-0.5">
              <button
                v-for="act in borradorOrdenado"
                :key="act._originalIdx"
                type="button"
                @click="handleClickTarjeta(act._originalIdx)"
                :class="[
                  'w-full bg-white border rounded-2xl px-3.5 py-3 flex items-center justify-between text-left transition-all duration-150',
                  store.sesionSeleccionada === act._originalIdx
                    ? 'border-blue-300 shadow-md ring-2 ring-blue-200'
                    : 'border-slate-100 hover:border-slate-200 hover:shadow-sm'
                ]"
              >
                <div class="flex items-start gap-2.5 min-w-0">
                  <div :class="[
                    'w-2 h-2 rounded-full mt-1.5 shrink-0',
                    act.requiere_inscripcion ? 'bg-rose-400' : 'bg-teal-500'
                  ]" />
                  <div class="min-w-0">
                    <p class="text-xs font-extrabold text-slate-800 truncate">{{ act._disciplina_nombre }}</p>
                    <p class="text-xs font-semibold text-slate-500 truncate mt-0.5">
                      {{ act.dia_semana }} · {{ act.hora_inicio.slice(0,5) }}–{{ act.hora_fin.slice(0,5) }}
                    </p>
                    <p class="text-[10px] text-slate-400 truncate">{{ act._espacio_nombre }} · {{ act._instructor_nombre }}</p>
                  </div>
                </div>
                <button
                  type="button"
                  @click.stop="store.eliminarDeBorradorLocal(act._originalIdx)"
                  class="ml-2 w-7 h-7 rounded-lg flex items-center justify-center text-slate-300 hover:text-red-500 hover:bg-red-50 shrink-0 transition-all duration-150"
                  title="Eliminar sesión"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                  </svg>
                </button>
              </button>
            </div>
          </div>

          <!-- ── BOTÓN GUARDAR PROGRESO — always anchored to bottom ── -->
          <div class="shrink-0 px-5 pt-3 pb-5 border-t border-slate-100 bg-white">
            <button
              @click="handleGuardarProgreso"
              :disabled="!store.tieneBorradorLocal || store.isSavingProgress"
              :class="[
                'w-full py-3 rounded-xl text-sm font-extrabold transition-all duration-150 flex items-center justify-center gap-2',
                store.tieneBorradorLocal && !store.isSavingProgress
                  ? 'bg-amber-500 text-white hover:bg-amber-600 shadow-sm hover:shadow-md active:scale-[0.98]'
                  : 'bg-slate-100 text-slate-400 cursor-not-allowed'
              ]"
            >
              <svg v-if="!store.isSavingProgress" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
              </svg>
              <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              {{ store.isSavingProgress ? 'Guardando...' : 'Guardar Progreso' }}
            </button>
            <p v-if="store.tieneBorradorLocal" class="text-[10px] text-slate-400 text-center mt-1.5 font-medium">
              {{ store.borradorLocal.length }} sesión{{ store.borradorLocal.length !== 1 ? 'es' : '' }} pendiente{{ store.borradorLocal.length !== 1 ? 's' : '' }} de guardar
            </p>
          </div>
        </div>

        <!-- ── CONFLICTOS PUBLICACIÓN ── -->
        <div v-if="store.conflictosPublicacion.length > 0" class="p-5 border-t border-red-200 bg-red-50 shrink-0">
          <div class="flex items-center gap-1.5 mb-2">
            <svg class="w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="text-xs font-extrabold text-red-700">Conflictos al publicar</p>
          </div>
          <div
            v-for="(c, i) in store.conflictosPublicacion"
            :key="i"
            class="bg-white border border-red-200 rounded-xl p-2.5 mb-1.5 text-xs text-red-700"
          >
            <span v-if="c.espacio_nombre || c.id_espacio">
              {{ c.espacio_nombre ?? `Espacio #${c.id_espacio}` }} · {{ c.dia }}
            </span>
            <span v-else>{{ c.message }}</span>
            <span v-if="c.horario_a">
              — Ses. {{ (c.actividad_index_a ?? 0) + 1 }} ({{ c.horario_a }}) colisiona con Ses. {{ (c.actividad_index_b ?? 0) + 1 }} ({{ c.horario_b }})
            </span>
          </div>
          <p class="text-xs text-red-500 mt-1 font-medium">Corrige los conflictos y vuelve a intentarlo.</p>
        </div>
      </div>

      <!-- ════════════════════════════════════════════════
           RIGHT PANEL — Calendario (col 5–12)
      ════════════════════════════════════════════════ -->
      <div class="col-span-8 flex flex-col overflow-hidden bg-slate-50/50">
        <div class="px-6 py-4 border-b border-slate-100 bg-white flex items-center justify-between shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
              <svg class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
              </svg>
            </div>
            <div>
              <p class="text-[9px] uppercase font-black tracking-widest text-slate-400">Vista previa</p>
              <h2 class="text-sm font-extrabold text-slate-700">Calendario semanal</h2>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <!-- Badge borrador local en calendario -->
            <div v-if="store.tieneBorradorLocal" class="flex items-center gap-1.5 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1">
              <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse" />
              <span class="text-[10px] font-bold text-amber-700">{{ store.borradorLocal.length }} sin guardar</span>
            </div>
            <span class="text-xs font-semibold text-slate-400 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1">06:00 – 22:00</span>
          </div>
        </div>
        <div class="flex-1 overflow-hidden p-4">
          <CalendarioGrid :sesion-resaltada-index="store.sesionSeleccionada" />
        </div>
      </div>
    </div>

    <!-- ── PUBLISH MODAL ── -->
    <Teleport to="body">
      <div v-if="showPublishModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full mx-4 shadow-2xl border border-slate-200">
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center mb-5">
            <svg class="w-6 h-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <p class="text-[10px] uppercase font-black tracking-widest text-slate-400 mb-1">Confirmación</p>
          <h3 class="text-xl font-black text-slate-800 tracking-tight mb-3">Publicar programación</h3>
          <p class="text-sm text-slate-600 mb-1">
            Estás a punto de publicar
            <strong class="text-slate-900">{{ store.totalActividades }} sesión{{ store.totalActividades !== 1 ? 'es' : '' }}</strong>
            de la plantilla
            <strong class="text-slate-900">{{ store.draft.nombre_plantilla || 'sin nombre' }}</strong>.
          </p>
          <p v-if="store.tieneBorradorLocal" class="text-xs font-medium text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-4">
            Tienes {{ store.borradorLocal.length }} sesión{{ store.borradorLocal.length !== 1 ? 'es' : '' }} en borrador local que se guardarán automáticamente antes de publicar.
          </p>
          <p class="text-xs font-medium text-slate-400 mb-6">Esta acción generará las sesiones activas y no podrá deshacerse.</p>
          <div class="flex gap-3 justify-end">
            <button
              @click="showPublishModal = false"
              class="px-5 py-2.5 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="confirmarPublicacion"
              class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-extrabold hover:bg-emerald-700 shadow-sm transition-all flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
              Sí, publicar
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
