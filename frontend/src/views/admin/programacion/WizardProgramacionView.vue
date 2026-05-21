<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useWizardStore } from '@/stores/programacion/wizardStore'
import { usePlantillasStore } from '@/stores/programacion/plantillasStore'
import { useAlerts } from '@/composables/useAlerts'
import CalendarioGrid       from './CalendarioGrid.vue'
import SelectDisciplina     from '@/components/admin/programacion/SelectDisciplina.vue'
import SelectEspacio        from '@/components/admin/programacion/SelectEspacio.vue'
import SelectInstructor     from '@/components/admin/programacion/SelectInstructor.vue'
import SelectHora           from '@/components/admin/programacion/SelectHora.vue'
import SesionDetalleModal   from '@/components/admin/programacion/SesionDetalleModal.vue'
import CollapsibleSection   from '@/components/gerente/ui/CollapsibleSection.vue'
import DisciplineIcon       from '@/components/icons/disciplines/DisciplineIcon.vue'
import IconGuests           from '@/components/icons/IconGuests.vue'

const router  = useRouter()
const store   = useWizardStore()
const plantillasStore = usePlantillasStore()
const { toastSuccess, toastError, actionToast } = useAlerts()

// ─── Constantes de día ────────────────────────────────────────────────────
const DIAS_SEMANA = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO']
const DIAS_SHORT  = { LUNES: 'L', MARTES: 'M', MIERCOLES: 'X', JUEVES: 'J', VIERNES: 'V', SABADO: 'S', DOMINGO: 'D' }
const DIAS_LABEL  = { LUNES: 'Lun', MARTES: 'Mar', MIERCOLES: 'Mié', JUEVES: 'Jue', VIERNES: 'Vie', SABADO: 'Sáb', DOMINGO: 'Dom' }
const DIA_ORDER   = { LUNES: 0, MARTES: 1, MIERCOLES: 2, JUEVES: 3, VIERNES: 4, SABADO: 5, DOMINGO: 6 }

const HORAS = Array.from({ length: 17 }, (_, i) => {
  const h = 6 + i
  return `${String(h).padStart(2, '0')}:00`
})

// ─── Form state (nueva sesión) ────────────────────────────────────────────
const form = ref({
  id_disciplina:        null,
  id_espacio:           null,
  id_instructor:        null,
  dias:                 [],
  hora_inicio:          '',
  hora_fin:             '',
  cupo_maximo:          null,
  requiere_inscripcion: false,
})
const formErrors = ref({})

const horasInicio = computed(() =>
  form.value.hora_fin ? HORAS.filter(h => h < form.value.hora_fin) : HORAS
)
const horasFin = computed(() =>
  form.value.hora_inicio ? HORAS.filter(h => h > form.value.hora_inicio) : HORAS
)

const textoBotonAgregar = computed(() => {
  const n = form.value.dias.length
  if (n <= 1) return '+ Agregar Sesión'
  return `+ Agregar ${n} Sesiones`
})

// ─── Filtrado cruzado del formulario (igual que antes) ────────────────────
const disciplinasFormulario = computed(() => {
  const { id_instructor, id_espacio } = form.value
  return store.disciplinas.filter(d => {
    const okInst = id_instructor
      ? store.instructores.find(i => i.id_instructor === id_instructor)
          ?.disciplinas_ids?.includes(d.id_disciplina) ?? false
      : true
    const okEsp = id_espacio
      ? store.espacios.find(e => e.id_espacio === id_espacio)
          ?.disciplinas_ids?.includes(d.id_disciplina) ?? false
      : true
    return okInst && okEsp
  })
})

const instructoresFormulario = computed(() => {
  const { id_disciplina } = form.value
  const base = store.instructores.filter(i =>
    id_disciplina
      ? Array.isArray(i.disciplinas_ids) && i.disciplinas_ids.includes(id_disciplina)
      : true
  )
  return [...base].sort((a, b) =>
    a.nombre_completo.localeCompare(b.nombre_completo, 'es', { sensitivity: 'base' })
  )
})

const espaciosFormulario = computed(() => {
  const { id_disciplina } = form.value
  const base = store.espacios.filter(e => {
    if (e.es_clase_programada === false) return false
    return id_disciplina
      ? Array.isArray(e.disciplinas_ids) && e.disciplinas_ids.includes(id_disciplina)
      : true
  })
  return [...base].sort((a, b) =>
    a.nombre_espacio.localeCompare(b.nombre_espacio, 'es', { numeric: true, sensitivity: 'base' })
  )
})

// ─── Selección — reset cruzado igual que original ────────────────────────
function onSeleccionarDisciplina(id) {
  if (id !== null && form.value.id_instructor !== null) {
    const inst = store.instructores.find(i => i.id_instructor === form.value.id_instructor)
    if (!inst?.disciplinas_ids?.includes(id)) form.value.id_instructor = null
  }
  if (id !== null && form.value.id_espacio !== null) {
    const esp = store.espacios.find(e => e.id_espacio === form.value.id_espacio)
    if (!esp?.disciplinas_ids?.includes(id)) form.value.id_espacio = null
  }
  form.value.id_disciplina = id
}

function onSeleccionarEspacio(id) {
  if (id !== null && form.value.id_disciplina !== null) {
    const esp = store.espacios.find(e => e.id_espacio === id)
    if (!esp?.disciplinas_ids?.includes(form.value.id_disciplina)) form.value.id_disciplina = null
  }
  form.value.id_espacio = id
}

function onSeleccionarInstructor(id) {
  if (id !== null && form.value.id_disciplina !== null) {
    const inst = store.instructores.find(i => i.id_instructor === id)
    if (!inst?.disciplinas_ids?.includes(form.value.id_disciplina)) form.value.id_disciplina = null
  }
  form.value.id_instructor = id
}

function resetSelects() {
  form.value.id_disciplina = null
  form.value.id_espacio    = null
  form.value.id_instructor = null
}

function toggleDia(dia) {
  const idx = form.value.dias.indexOf(dia)
  if (idx === -1) form.value.dias.push(dia)
  else form.value.dias.splice(idx, 1)
}

// ─── Validación ───────────────────────────────────────────────────────────
function validarForm() {
  const e = {}
  if (!form.value.id_disciplina)      e.id_disciplina  = 'Selecciona una disciplina'
  if (!form.value.id_espacio)         e.id_espacio     = 'Selecciona un espacio'
  if (!form.value.id_instructor)      e.id_instructor  = 'Selecciona un instructor'
  if (form.value.dias.length === 0)   e.dias           = 'Selecciona al menos un día'
  if (!form.value.hora_inicio)        e.hora_inicio    = 'Hora inicio'
  if (!form.value.hora_fin)           e.hora_fin       = 'Hora fin'
  if (form.value.hora_inicio && form.value.hora_fin && form.value.hora_inicio >= form.value.hora_fin) {
    e.hora_fin = 'Debe ser mayor a la hora inicio'
  }
  if (!form.value.cupo_maximo || form.value.cupo_maximo < 1) e.cupo_maximo = 'Mínimo 1'
  else if (form.value.cupo_maximo > 40) e.cupo_maximo = 'Máximo 40'
  return e
}

// ─── Preview live de colisiones para el form actual ───────────────────────
const colisionesPreview = computed(() => {
  const f = form.value
  if (!f.id_disciplina || !f.id_espacio || !f.id_instructor) return []
  if (!f.hora_inicio || !f.hora_fin || f.dias.length === 0) return []

  const disciplina = store.disciplinas.find(d => d.id_disciplina === f.id_disciplina)
  const espacio    = store.espacios.find(e => e.id_espacio       === f.id_espacio)
  const instructor = store.instructores.find(i => i.id_instructor === f.id_instructor)

  const conflictos = []
  for (const dia of f.dias) {
    const candidata = {
      id_disciplina: f.id_disciplina,
      id_espacio:    f.id_espacio,
      id_instructor: f.id_instructor,
      dia_semana:    dia,
      hora_inicio:   f.hora_inicio,
      hora_fin:      f.hora_fin,
      _disciplina_nombre: disciplina?.nombre_disciplina ?? '',
      _espacio_nombre:    espacio?.nombre_espacio    ?? '',
      _instructor_nombre: instructor?.nombre_completo ?? '',
    }
    conflictos.push(...store.detectarColisionEnEdicion(candidata, null, null))
  }
  return conflictos
})

const tieneConflictoPreview = computed(() => colisionesPreview.value.length > 0)

// Agrupa para el diagnóstico detallado
const colisionesPreviewPorTipo = computed(() => {
  const espacio    = colisionesPreview.value.filter(c => c.tipo === 'espacio')
  const instructor = colisionesPreview.value.filter(c => c.tipo === 'instructor')
  return { espacio, instructor, ambos: espacio.length > 0 && instructor.length > 0 }
})

const formularioCompleto = computed(() => Object.keys(validarForm()).length === 0)

// Botones de "Agregar al borrador" y "Guardar progreso" deshabilitados si hay conflicto
const puedeAgregar = computed(() => formularioCompleto.value && !tieneConflictoPreview.value)

// ─── Agregar al borrador local ───────────────────────────────────────────
function handleAgregar() {
  formErrors.value = validarForm()
  if (Object.keys(formErrors.value).length > 0) return
  if (tieneConflictoPreview.value) return

  const espacio    = store.espacios.find(e => e.id_espacio       === form.value.id_espacio)
  const disciplina = store.disciplinas.find(d => d.id_disciplina === form.value.id_disciplina)
  const instructor = store.instructores.find(i => i.id_instructor === form.value.id_instructor)

  const ok = store.agregarSesiones({
    id_disciplina:        form.value.id_disciplina,
    id_espacio:           form.value.id_espacio,
    id_instructor:        form.value.id_instructor,
    dias:                 [...form.value.dias],
    hora_inicio:          form.value.hora_inicio,
    hora_fin:             form.value.hora_fin,
    cupo_maximo:          form.value.cupo_maximo,
    requiere_inscripcion: form.value.requiere_inscripcion,
    _espacio_nombre:      espacio?.nombre_espacio       ?? '',
    _disciplina_nombre:   disciplina?.nombre_disciplina ?? '',
    _instructor_nombre:   instructor?.nombre_completo   ?? '',
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
    id_disciplina:        null,
    id_espacio:           null,
    id_instructor:        null,
    dias:                 [],
    hora_inicio:          '',
    hora_fin:             '',
    cupo_maximo:          null,
    requiere_inscripcion: false,
  }
  formErrors.value = {}
}

// ─── Borrador local (agrupado por disciplina) ─────────────────────────────
const borradorOrdenado = computed(() =>
  store.borradorLocal
    .map((s, i) => ({ ...s, _originalIdx: i }))
    .sort((a, b) => (DIA_ORDER[a.dia_semana] ?? 7) - (DIA_ORDER[b.dia_semana] ?? 7))
)

// Grupos de borradores por disciplina para mostrar en la sección de borradores
const borradorPorDisciplina = computed(() => {
  const map = new Map() // id_disciplina → { nombre, icon, sesiones[] }
  for (const s of borradorOrdenado.value) {
    if (!map.has(s.id_disciplina)) {
      map.set(s.id_disciplina, { id: s.id_disciplina, nombre: s._disciplina_nombre, sesiones: [] })
    }
    map.get(s.id_disciplina).sesiones.push(s)
  }
  return [...map.values()].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'))
})

// Controla qué grupos están expandidos en la sección borrador
const gruposExpandidos = ref({})
function toggleGrupoBorrador(idDisciplina) {
  gruposExpandidos.value[idDisciplina] = !gruposExpandidos.value[idDisciplina]
}
function grupoEstaExpandido(idDisciplina) {
  return gruposExpandidos.value[idDisciplina] !== false // expandido por defecto
}

// Activa/desactiva filtro de calendario por disciplina desde el ojo
function toggleFiltroDesdeOjo(idDisciplina) {
  const actual = store.filtros.id_disciplina
  store.setFiltro('id_disciplina', actual === idDisciplina ? null : idDisciplina)
}

function handleClickTarjeta(idx) {
  store.seleccionarSesion(idx)
}

async function handleGuardarProgreso() {
  try {
    await store.guardarProgreso()
    toastSuccess('Progreso guardado correctamente')
  } catch {
    toastError('Error al guardar el progreso')
  }
}

// ─── Publicar ─────────────────────────────────────────────────────────────
const showPublishModal = ref(false)

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

// ─── Toggles de las secciones colapsables ─────────────────────────────────
const seccionCrear     = ref(true)
const seccionBorrador  = ref(true)

// ─── Init ─────────────────────────────────────────────────────────────────
onMounted(async () => {
  if (plantillasStore.plantillas.length === 0) {
    await plantillasStore.fetchPlantillas()
  }
  if (!plantillasStore.plantillaActiva) return
  try {
    await Promise.all([store.fetchDependencias(), store.crearDraft()])
  } catch { /* error handled in store */ }
})
</script>

<template>
  <div class="h-full flex flex-col bg-slate-50 font-sans">

    <!-- ══════════ HEADER ══════════ -->
    <header class="px-6 py-3 border-b border-slate-200 bg-white shrink-0 shadow-sm">
      <div class="flex items-center gap-4">

        <!-- Identidad -->
        <div class="flex items-center gap-3 shrink-0">
          <button
            type="button"
            @click="store.togglePanel"
            :title="store.panelVisible ? 'Ocultar panel' : 'Mostrar panel de creación'"
            :class="[
              'w-10 h-10 rounded-xl flex items-center justify-center transition-colors shadow-sm',
              store.panelVisible
                ? 'bg-primary-600 hover:bg-primary-700 text-white'
                : 'bg-white border border-slate-200 hover:border-primary-300 hover:bg-primary-50 text-primary-600'
            ]"
          >
            <svg v-if="!store.panelVisible" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <svg v-else class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
          </button>

          <div class="hidden md:block">
            <p class="text-[10px] uppercase font-black tracking-widest text-slate-400 leading-none mb-0.5">Diseñador</p>
            <h1 class="text-base font-black text-slate-800 leading-none">Gestión de Sesiones</h1>
          </div>
        </div>

        <div class="w-px h-8 bg-slate-200 shrink-0" />

        <!-- ── Filtros del calendario (Disciplina / Espacio / Instructor) ── -->
        <div class="flex items-center gap-2 flex-1 min-w-0">
          <div class="flex-1 min-w-40 max-w-60">
            <SelectDisciplina
              :model-value="store.filtros.id_disciplina"
              @update:model-value="(v) => store.setFiltro('id_disciplina', v)"
              :opciones="store.disciplinas"
              size="sm"
              :show-label="false"
              placeholder="Filtrar disciplina"
            />
          </div>
          <div class="flex-1 min-w-40 max-w-60">
            <SelectEspacio
              :model-value="store.filtros.id_espacio"
              @update:model-value="(v) => store.setFiltro('id_espacio', v)"
              :opciones="store.espacios"
              size="sm"
              :show-label="false"
              placeholder="Filtrar espacio"
            />
          </div>
          <div class="flex-1 min-w-40 max-w-60">
            <SelectInstructor
              :model-value="store.filtros.id_instructor"
              @update:model-value="(v) => store.setFiltro('id_instructor', v)"
              :opciones="store.instructores"
              size="sm"
              :show-label="false"
              placeholder="Filtrar instructor"
            />
          </div>
          <button
            v-if="store.filtros.id_disciplina || store.filtros.id_espacio || store.filtros.id_instructor"
            type="button"
            @click="store.resetFiltros"
            class="text-[10px] font-bold text-slate-400 hover:text-red-500 px-2 py-1 rounded-lg hover:bg-slate-50 transition-colors shrink-0"
          >Limpiar</button>
        </div>

        <!-- ── Estado + Publicar ── -->
        <div class="flex items-center gap-3 shrink-0">
          <div v-if="store.tieneActividades" class="hidden sm:flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
            <span class="text-xs font-bold text-slate-600">
              {{ store.totalActividades }} sesión{{ store.totalActividades !== 1 ? 'es' : '' }}
            </span>
          </div>

          <button
            :disabled="!store.tieneActividades || store.isPublishing"
            @click="showPublishModal = true"
            :class="[
              'inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-extrabold transition-all duration-200',
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
            <span class="hidden sm:inline">{{ store.isPublishing ? 'Publicando...' : 'Publicar' }}</span>
          </button>
        </div>
      </div>
    </header>

    <!-- ══════════ LOADING / SIN PLANTILLA / ERROR ══════════ -->
    <div v-if="plantillasStore.isLoading" class="flex-1 flex items-center justify-center">
      <div class="text-center text-slate-500">
        <div class="animate-spin rounded-full h-10 w-10 border-2 border-slate-200 border-t-primary-600 mx-auto mb-4" />
        <p class="text-sm font-semibold">Cargando plantillas...</p>
      </div>
    </div>

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
      </div>
    </div>

    <div v-else-if="store.isCreatingDraft || store.isLoadingDeps" class="flex-1 flex items-center justify-center">
      <div class="text-center text-slate-500">
        <div class="animate-spin rounded-full h-10 w-10 border-2 border-slate-200 border-t-primary-600 mx-auto mb-4" />
        <p class="text-sm font-semibold">Inicializando diseñador...</p>
      </div>
    </div>

    <div v-else-if="store.errorInit || store.errorDeps" class="flex-1 flex items-center justify-center">
      <div class="text-center bg-white rounded-3xl border border-slate-200 shadow-sm p-10">
        <p class="text-red-600 text-sm font-medium mb-4">{{ store.errorInit || store.errorDeps }}</p>
        <button
          @click="async () => { try { await Promise.all([store.fetchDependencias(), store.crearDraft()]) } catch {} }"
          class="px-5 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-extrabold hover:bg-primary-700 transition-colors"
        >Reintentar</button>
      </div>
    </div>

    <!-- ══════════ MAIN — Panel + Calendario ══════════ -->
    <div v-else class="flex-1 min-h-0 flex overflow-hidden">

      <!-- ════ PANEL IZQUIERDO (toggleable) ════ -->
      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="-translate-x-2 opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="-translate-x-2 opacity-0"
      >
        <aside
          v-if="store.panelVisible"
          class="w-[380px] shrink-0 border-r border-slate-200 bg-white flex flex-col min-h-0 overflow-hidden"
        >
          <div class="flex-1 min-h-0 overflow-y-auto">

            <!-- ═══ Sección: Crear nueva sesión ═══ -->
            <CollapsibleSection
              v-model="seccionCrear"
              title="Nueva sesión"
              hint="Configuración"
            >
              <!-- Plantilla activa -->
              <div class="mb-4 px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center gap-2">
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

              <!-- Selectores -->
              <div class="space-y-3">
                <SelectDisciplina
                  :model-value="form.id_disciplina"
                  @update:model-value="onSeleccionarDisciplina"
                  :opciones="disciplinasFormulario"
                  :error="formErrors.id_disciplina ?? ''"
                >
                  <template #label-action>
                    <button
                      v-if="form.id_disciplina || form.id_espacio || form.id_instructor"
                      type="button"
                      @click="resetSelects"
                      class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400 hover:text-red-500 transition-colors"
                    >
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      Limpiar
                    </button>
                  </template>
                </SelectDisciplina>

                <SelectEspacio
                  :model-value="form.id_espacio"
                  @update:model-value="onSeleccionarEspacio"
                  :opciones="espaciosFormulario"
                  :error="formErrors.id_espacio ?? ''"
                />

                <SelectInstructor
                  :model-value="form.id_instructor"
                  @update:model-value="onSeleccionarInstructor"
                  :opciones="instructoresFormulario"
                  :error="formErrors.id_instructor ?? ''"
                />
              </div>

              <!-- Días -->
              <div class="mt-5">
                <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-2.5">Días de la semana</label>
                <div class="flex gap-1.5">
                  <button
                    v-for="dia in DIAS_SEMANA"
                    :key="dia"
                    type="button"
                    @click="toggleDia(dia)"
                    :title="DIAS_LABEL[dia]"
                    :class="[
                      'flex-1 h-10 rounded-xl text-xs font-black transition-all duration-150',
                      form.dias.includes(dia)
                        ? 'bg-primary-600 text-white shadow-sm'
                        : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700'
                    ]"
                  >{{ DIAS_SHORT[dia] }}</button>
                </div>
                <p v-if="formErrors.dias" class="text-red-500 text-xs mt-1.5 font-medium">{{ formErrors.dias }}</p>
              </div>

              <!-- Horarios -->
              <div class="grid grid-cols-2 gap-3 mt-3">
                <SelectHora
                  :model-value="form.hora_inicio"
                  @update:model-value="(v) => { form.hora_inicio = v; if (form.hora_fin && v >= form.hora_fin) form.hora_fin = '' }"
                  :opciones="horasInicio"
                  label="Hora inicio"
                  :error="formErrors.hora_inicio ?? ''"
                />
                <SelectHora
                  :model-value="form.hora_fin"
                  @update:model-value="(v) => form.hora_fin = v"
                  :opciones="horasFin"
                  label="Hora fin"
                  :error="formErrors.hora_fin ?? ''"
                />
              </div>

              <!-- Cupo + tipo de clase -->
              <div class="grid grid-cols-2 gap-3 mt-3">
                <div>
                  <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">Cupo máx.</label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                      <IconGuests :class="['w-4 h-4', formErrors.cupo_maximo ? 'text-red-400' : 'text-slate-400']" />
                    </span>
                    <input
                      v-model.number="form.cupo_maximo"
                      type="number" min="1" max="40"
                      :class="[
                        'w-full pl-9 pr-3 py-3 rounded-xl border text-sm font-bold transition-all duration-150',
                        formErrors.cupo_maximo ? 'border-red-300 bg-red-50 text-red-700' : 'border-slate-200 bg-white text-slate-800'
                      ]"
                    />
                  </div>
                  <p v-if="formErrors.cupo_maximo" class="text-red-500 text-[10px] mt-1 font-medium">{{ formErrors.cupo_maximo }}</p>
                </div>
                <div>
                  <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">Tipo de clase</label>
                  <div class="flex gap-1.5 bg-slate-100 p-1 rounded-xl">
                    <button
                      type="button"
                      @click="form.requiere_inscripcion = false"
                      :class="[
                        'flex-1 py-2 rounded-lg text-xs font-black transition-all duration-150',
                        !form.requiere_inscripcion
                          ? 'bg-emerald-500 text-white shadow-sm'
                          : 'text-slate-500 hover:text-slate-700'
                      ]"
                    >Abierta</button>
                    <button
                      type="button"
                      @click="form.requiere_inscripcion = true"
                      :class="[
                        'flex-1 py-2 rounded-lg text-xs font-black transition-all duration-150',
                        form.requiere_inscripcion
                          ? 'bg-red-500 text-white shadow-sm'
                          : 'text-slate-500 hover:text-slate-700'
                      ]"
                    >Cerrada</button>
                  </div>
                </div>
              </div>

              <!-- Alerta de conflicto LIVE — diagnóstico detallado -->
              <div v-if="tieneConflictoPreview" class="mt-4 rounded-xl overflow-hidden border border-amber-300">
                <!-- Cabecera -->
                <div class="bg-amber-500 px-3 py-2 flex items-center gap-2">
                  <svg class="w-3.5 h-3.5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
                  </svg>
                  <p class="text-white text-[10px] font-black uppercase tracking-widest flex-1">
                    {{ colisionesPreviewPorTipo.ambos ? 'Conflicto de espacio e instructor' : colisionesPreviewPorTipo.espacio.length ? 'Espacio ocupado' : 'Instructor duplicado' }}
                  </p>
                  <span class="text-amber-100 text-[10px] font-bold tabular-nums">
                    {{ colisionesPreview.length }} conflicto{{ colisionesPreview.length !== 1 ? 's' : '' }}
                  </span>
                </div>
                <!-- Filas -->
                <div class="bg-amber-50 divide-y divide-amber-100">
                  <div v-if="colisionesPreviewPorTipo.espacio.length > 0">
                    <div class="px-3 pt-2 pb-1 flex items-center gap-1.5">
                      <span class="w-4 h-4 rounded bg-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                      </span>
                      <p class="text-[9px] font-black uppercase tracking-widest text-amber-700">Espacio ocupado</p>
                    </div>
                    <div v-for="(c, idx) in colisionesPreviewPorTipo.espacio.slice(0, 2)" :key="'e' + idx" class="px-3 pb-2 ml-6">
                      <p class="text-xs font-extrabold text-amber-900 truncate">{{ c.nombre }}</p>
                      <p class="text-[11px] font-semibold text-amber-700 tabular-nums">
                        {{ DIAS_LABEL[c.dia] }} · Ocupado {{ c.horario_existente }}
                      </p>
                    </div>
                  </div>
                  <div v-if="colisionesPreviewPorTipo.instructor.length > 0">
                    <div class="px-3 pt-2 pb-1 flex items-center gap-1.5">
                      <span class="w-4 h-4 rounded bg-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" />
                        </svg>
                      </span>
                      <p class="text-[9px] font-black uppercase tracking-widest text-amber-700">Instructor duplicado</p>
                    </div>
                    <div v-for="(c, idx) in colisionesPreviewPorTipo.instructor.slice(0, 2)" :key="'i' + idx" class="px-3 pb-2 ml-6">
                      <p class="text-xs font-extrabold text-amber-900 truncate">{{ c.nombre }}</p>
                      <p class="text-[11px] font-semibold text-amber-700 tabular-nums">
                        {{ DIAS_LABEL[c.dia] }} · Ya asignado {{ c.horario_existente }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Botón agregar -->
              <button
                type="button"
                @click="handleAgregar"
                :disabled="!puedeAgregar"
                :class="[
                  'w-full mt-4 py-3 rounded-xl text-sm font-black transition-all duration-150 flex items-center justify-center gap-2',
                  puedeAgregar
                    ? 'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-800 shadow-sm'
                    : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                ]"
              >
                {{ tieneConflictoPreview ? 'Resuelve conflictos' : textoBotonAgregar }}
              </button>
            </CollapsibleSection>

            <!-- ═══ Sección: Borrador local ═══ -->
            <CollapsibleSection
              v-model="seccionBorrador"
              title="Borradores locales"
              hint="Aún no guardadas"
              :badge="store.borradorLocal.length || null"
              badge-tone="primary"
            >
              <div v-if="!store.tieneBorradorLocal" class="text-center py-6">
                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-2">
                  <svg class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
                  </svg>
                </div>
                <p class="text-xs font-bold text-slate-400">Sin borradores</p>
              </div>

              <div v-else class="space-y-2">
                <!-- Grupos por disciplina -->
                <div
                  v-for="grupo in borradorPorDisciplina"
                  :key="grupo.id"
                  class="rounded-xl border border-dashed overflow-hidden"
                  :class="store.filtros.id_disciplina === grupo.id ? 'border-primary-300' : 'border-slate-200'"
                >
                  <!-- Cabecera del grupo -->
                  <div :class="[
                    'flex items-center gap-2 px-2.5 py-2 cursor-pointer select-none transition-colors',
                    store.filtros.id_disciplina === grupo.id ? 'bg-primary-50' : 'bg-slate-50 hover:bg-slate-100'
                  ]" @click="toggleGrupoBorrador(grupo.id)">
                    <div :class="[
                      'w-6 h-6 rounded-md flex items-center justify-center shrink-0',
                      store.filtros.id_disciplina === grupo.id ? 'bg-primary-100 text-primary-600' : 'bg-white text-slate-500 border border-slate-200'
                    ]">
                      <DisciplineIcon :name="grupo.nombre" class="w-3.5 h-3.5" />
                    </div>
                    <span :class="[
                      'flex-1 text-sm font-black truncate',
                      store.filtros.id_disciplina === grupo.id ? 'text-primary-700' : 'text-slate-700'
                    ]">{{ grupo.nombre }}</span>

                    <!-- Contador -->
                    <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600 tabular-nums">
                      {{ grupo.sesiones.length }}
                    </span>

                    <!-- Ojo: activa filtro de calendario para esta disciplina -->
                    <button
                      type="button"
                      @click.stop="toggleFiltroDesdeOjo(grupo.id)"
                      :title="store.filtros.id_disciplina === grupo.id ? 'Quitar filtro del calendario' : 'Filtrar calendario por esta disciplina'"
                      class="w-6 h-6 rounded-md flex items-center justify-center transition-colors shrink-0"
                      :class="store.filtros.id_disciplina === grupo.id
                        ? 'bg-primary-100 text-primary-600 hover:bg-primary-200'
                        : 'bg-transparent text-slate-400 hover:text-slate-600 hover:bg-slate-200'"
                    >
                      <!-- Eye open (filtro activo) -->
                      <svg v-if="store.filtros.id_disciplina === grupo.id" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <!-- Eye slash (sin filtro) -->
                      <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                      </svg>
                    </button>

                    <!-- Chevron expand/collapse -->
                    <svg
                      class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-150"
                      :class="grupoEstaExpandido(grupo.id) ? 'rotate-90' : ''"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                  </div>

                  <!-- Sesiones del grupo -->
                  <div v-if="grupoEstaExpandido(grupo.id)" class="px-2 pb-2 pt-1 space-y-1.5 bg-white">
                    <div
                      v-for="s in grupo.sesiones"
                      :key="s._originalIdx"
                      :class="[
                        'flex items-center gap-2 p-2 rounded-lg border transition-all duration-150',
                        s.requiere_inscripcion
                          ? 'border-red-100 bg-red-50/50'
                          : 'border-emerald-100 bg-emerald-50/50'
                      ]"
                    >
                      <div class="flex-1 min-w-0">
                        <p class="text-xs font-black tabular-nums truncate"
                          :class="s.requiere_inscripcion ? 'text-red-700' : 'text-emerald-700'">
                          {{ DIAS_LABEL[s.dia_semana] }} · {{ s.hora_inicio }}–{{ s.hora_fin }}
                        </p>
                        <p class="text-[11px] font-semibold text-slate-400 truncate">{{ s._espacio_nombre }}</p>
                      </div>
                      <button
                        type="button"
                        @click.stop="store.eliminarDeBorradorLocal(s._originalIdx)"
                        class="w-5 h-5 rounded-md bg-white/80 hover:bg-red-100 text-slate-300 hover:text-red-500 flex items-center justify-center transition-colors shrink-0 border border-slate-100"
                        title="Quitar del borrador"
                      >
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <button
                  type="button"
                  @click="handleGuardarProgreso"
                  :disabled="store.isSavingProgress || tieneConflictoPreview"
                  :class="[
                    'w-full mt-1 py-2.5 rounded-xl text-xs font-black transition-all duration-150 flex items-center justify-center gap-2',
                    !tieneConflictoPreview && !store.isSavingProgress
                      ? 'bg-slate-800 text-white hover:bg-slate-900 shadow-sm'
                      : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                  ]"
                >
                  <svg v-if="!store.isSavingProgress" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                  <svg v-else class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                  </svg>
                  {{ store.isSavingProgress ? 'Guardando...' : 'Guardar progreso' }}
                </button>
              </div>
            </CollapsibleSection>

          </div>
        </aside>
      </Transition>

      <!-- ════ CALENDARIO ════ -->
      <main class="flex-1 min-w-0 overflow-hidden p-4">
        <CalendarioGrid :sesion-resaltada-index="store.sesionSeleccionada" />
      </main>
    </div>

    <!-- ══════════ MODAL DE PUBLICACIÓN ══════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div
          v-if="showPublishModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showPublishModal = false"
        >
          <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-6">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h2 class="text-xl font-black text-slate-900 mb-1">Publicar programación</h2>
            <p class="text-sm font-medium text-slate-500 mb-5 leading-relaxed">
              Se publicarán <strong>{{ store.totalActividades }}</strong> sesión{{ store.totalActividades !== 1 ? 'es' : '' }} ya guardadas
              <span v-if="store.tieneBorradorLocal">y <strong>{{ store.borradorLocal.length }}</strong> del borrador local</span>.
            </p>
            <div class="flex gap-3 justify-end">
              <button
                @click="showPublishModal = false"
                class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors"
              >Cancelar</button>
              <button
                @click="confirmarPublicacion"
                class="px-6 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-black hover:bg-emerald-700 transition-colors shadow-sm"
              >Publicar</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════ MODAL DE DETALLE DE SESIÓN (Fase 1) ══════════ -->
    <SesionDetalleModal />
  </div>
</template>
