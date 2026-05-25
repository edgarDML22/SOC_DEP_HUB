<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import CalendarioPublicadas from './CalendarioPublicadas.vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'
import { useAlerts } from '@/composables/useAlerts'
import { usePlantillasStore } from '@/stores/programacion/plantillasStore'
import api from '@/services/api'

const { toastSuccess, toastError } = useAlerts()
const plantillasStore = usePlantillasStore()

// ─── Dataset completo (cargado una sola vez, sin paginación backend) ─────────
// Filtros y paginación se aplican en el cliente sobre este array.
// plantilla_activa_encontrada distingue "sin plantilla" de "sin resultados".
const sesiones  = ref([])
const isLoading = ref(false)
const loadError = ref('')
const plantillaEncontrada = ref(null)   // null=desconocido, true, false

const activeView         = ref('tabla')
const searchFilter       = ref('')
const selectedEstatus    = ref('TODOS')
const selectedCategoria  = ref('TODAS')
const selectedDisciplina = ref('TODAS')
const selectedInstructor = ref('TODOS')
const selectedEspacio    = ref('TODOS')
const selectedDia        = ref('TODOS')
const selectedTipoClase  = ref('TODOS')
const horaDesde          = ref('')
const horaHasta          = ref('')

// ─── Carga completa del dataset (una sola vez, sin filtros backend) ──────────
async function fetchSesiones() {
  isLoading.value = true
  loadError.value = ''
  try {
    const { data } = await api.get('/programacion/sesiones-activas', {
      params: { per_page: 500, page: 1 },
    })
    plantillaEncontrada.value = data.meta?.plantilla_activa_encontrada ?? null
    sesiones.value = (data.data ?? []).map(s => ({
      ...s,
      requiere_inscripcion: s.requiere_inscripcion === true || s.requiere_inscripcion === 1,
    }))
  } catch (e) {
    loadError.value = e?.response?.data?.message ?? 'No se pudieron cargar las sesiones publicadas.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => fetchSesiones())

watch(() => plantillasStore.sesionesRetiradas, (val) => {
  if (val > 0) fetchSesiones()
})

// ─── Catálogos de filtros derivados del dataset completo ─────────────────────
const disciplinasUnicas  = computed(() => [...new Set(sesiones.value.map(s => s.disciplina).filter(Boolean))].sort())
const instructoresUnicos = computed(() => [...new Set(sesiones.value.map(s => s.instructor).filter(Boolean))].sort())
const espaciosUnicos     = computed(() => [...new Set(sesiones.value.map(s => s.espacio).filter(Boolean))].sort())
const categoriasUnicas   = computed(() => [...new Set(sesiones.value.map(s => s.categoria).filter(Boolean))].sort())

// ─── Filtrado client-side ─────────────────────────────────────────────────────
const sesionesFiltradas = computed(() => {
  const buscar  = searchFilter.value.trim().toLowerCase()
  return sesiones.value.filter(s => {
    if (selectedEstatus.value !== 'TODOS'    && s.estatus_sesion !== selectedEstatus.value)    return false
    if (selectedDisciplina.value !== 'TODAS' && s.disciplina !== selectedDisciplina.value)     return false
    if (selectedCategoria.value !== 'TODAS'  && s.categoria !== selectedCategoria.value)       return false
    if (selectedInstructor.value !== 'TODOS' && s.instructor !== selectedInstructor.value)     return false
    if (selectedEspacio.value !== 'TODOS'    && s.espacio !== selectedEspacio.value)           return false
    if (selectedDia.value !== 'TODOS'        && s.dia_semana !== selectedDia.value)            return false
    if (selectedTipoClase.value === 'CERRADA' && !s.requiere_inscripcion)                      return false
    if (selectedTipoClase.value === 'ABIERTA' && s.requiere_inscripcion)                       return false
    if (horaDesde.value && (s.hora_inicio ?? '') < horaDesde.value)                            return false
    if (horaHasta.value && (s.hora_inicio ?? '') > horaHasta.value)                            return false
    if (buscar && ![s.disciplina, s.instructor, s.espacio, String(s.id_sesion ?? '')]
        .some(v => (v ?? '').toLowerCase().includes(buscar)))                                  return false
    return true
  })
})

// ─── Paginación visual (client-side) ─────────────────────────────────────────
const PAGE_SIZE   = 30
const currentPage = ref(1)

// Resetear a página 1 solo cuando cambien los filtros, NO cuando cambie la página
watch(
  [searchFilter, selectedEstatus, selectedCategoria, selectedDisciplina,
   selectedInstructor, selectedEspacio, selectedDia, selectedTipoClase,
   horaDesde, horaHasta],
  () => { currentPage.value = 1 }
)

const lastPage     = computed(() => Math.max(1, Math.ceil(sesionesFiltradas.value.length / PAGE_SIZE)))
const sesionesEnPagina = computed(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE
  return sesionesFiltradas.value.slice(start, start + PAGE_SIZE)
})

const hayFiltrosActivos = computed(() =>
  searchFilter.value.trim() !== '' ||
  selectedEstatus.value !== 'TODOS' ||
  selectedCategoria.value !== 'TODAS' ||
  selectedDisciplina.value !== 'TODAS' ||
  selectedInstructor.value !== 'TODOS' ||
  selectedEspacio.value !== 'TODOS' ||
  selectedDia.value !== 'TODOS' ||
  selectedTipoClase.value !== 'TODOS' ||
  horaDesde.value !== '' ||
  horaHasta.value !== ''
)

function limpiarFiltros() {
  searchFilter.value       = ''
  selectedEstatus.value    = 'TODOS'
  selectedCategoria.value  = 'TODAS'
  selectedDisciplina.value = 'TODAS'
  selectedInstructor.value = 'TODOS'
  selectedEspacio.value    = 'TODOS'
  selectedDia.value        = 'TODOS'
  selectedTipoClase.value  = 'TODOS'
  horaDesde.value          = ''
  horaHasta.value          = ''
}

// ─── Modal de gestión de estatus ────────────────────────────────────────────
const showModal          = ref(false)
const sesionSeleccionada = ref(null)
const tempEstatus        = ref('DISPONIBLE')
const isSaving           = ref(false)

// ─── Modal de confirmación de cancelación ───────────────────────────────────
const showConfirmCancelacion = ref(false)

// ─── Asistencia (lazy) ──────────────────────────────────────────────────────
const asistencia        = ref([])
const isLoadingAsistencia = ref(false)

// Toggles colapsables por grupo
const grupoExpandido = ref({ INSCRITO: true, ASISTENCIA: true, NO_SHOW: true })
function toggleGrupo(key) { grupoExpandido.value[key] = !grupoExpandido.value[key] }

// Agrupación por estatus
const grupoInscritos = computed(() =>
  asistencia.value.filter(i => i.estatus_inscripcion === 'CONFIRMADA')
)
const grupoAsistencia = computed(() =>
  asistencia.value.filter(i => i.estatus_inscripcion === 'ASISTIO')
)
const grupoNoShow = computed(() =>
  asistencia.value.filter(i => i.estatus_inscripcion === 'FALTA')
)
const totalInscritos = computed(() => asistencia.value.length)

async function fetchAsistencia(id_sesion) {
  isLoadingAsistencia.value = true
  asistencia.value = []
  try {
    const { data } = await api.get(`/programacion/sesiones-activas/${id_sesion}/asistencia`)
    asistencia.value = data.data ?? []
  } catch {
    asistencia.value = []
  } finally {
    isLoadingAsistencia.value = false
  }
}

function abrirDetalle(sesion) {
  sesionSeleccionada.value = { ...sesion }
  tempEstatus.value        = sesion.estatus_sesion
  asistencia.value         = []
  grupoExpandido.value     = { INSCRITO: true, ASISTENCIA: true, NO_SHOW: true }
  showModal.value          = true
  fetchAsistencia(sesion.id_sesion)
}

function cerrarModal() {
  showModal.value          = false
  sesionSeleccionada.value = null
  asistencia.value         = []
}

async function guardarCambios() {
  if (!sesionSeleccionada.value) return

  // Si se está cancelando y hay inscritos, pedir confirmación primero
  const cancelando = tempEstatus.value === 'CANCELADA' &&
    sesionSeleccionada.value.estatus_sesion !== 'CANCELADA'
  const hayInscritos = (sesionSeleccionada.value.cantidad_inscritos ?? 0) > 0

  if (cancelando && hayInscritos) {
    showConfirmCancelacion.value = true
    return
  }

  await ejecutarGuardado()
}

async function ejecutarGuardado() {
  if (!sesionSeleccionada.value) return
  showConfirmCancelacion.value = false
  isSaving.value = true
  try {
    await api.patch(`/programacion/sesiones-activas/${sesionSeleccionada.value.id_sesion}`, {
      estatus_sesion: tempEstatus.value,
    })
    const original = sesiones.value.find(s => s.id_sesion === sesionSeleccionada.value.id_sesion)
    if (original) original.estatus_sesion = tempEstatus.value
    toastSuccess(`Sesión #${sesionSeleccionada.value.id_sesion} actualizada a ${tempEstatus.value}.`)
    cerrarModal()
  } catch (e) {
    toastError(e?.response?.data?.message ?? 'Error al actualizar el estatus.')
  } finally {
    isSaving.value = false
  }
}

// ─── Helpers ────────────────────────────────────────────────────────────────
const DIAS_LABEL = {
  LUNES: 'LUNES', MARTES: 'MARTES', MIERCOLES: 'MIÉRCOLES',
  JUEVES: 'JUEVES', VIERNES: 'VIERNES', SABADO: 'SÁBADO', DOMINGO: 'DOMINGO',
}

const DIAS_COLORS = {
  LUNES:     'bg-rose-50 text-rose-700 border-rose-200',
  MARTES:    'bg-amber-50 text-amber-700 border-amber-200',
  MIERCOLES: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  JUEVES:    'bg-sky-50 text-sky-700 border-sky-200',
  VIERNES:   'bg-violet-50 text-violet-700 border-violet-200',
  SABADO:    'bg-orange-50 text-orange-700 border-orange-200',
  DOMINGO:   'bg-slate-100 text-slate-700 border-slate-200',
}

const ESTATUS_COLORS = {
  DISPONIBLE: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  EN_CURSO:   'bg-orange-50 text-orange-700 border-orange-200',
  FINALIZADA: 'bg-blue-50 text-blue-700 border-blue-200',
  CANCELADA:  'bg-red-50 text-red-700 border-red-200',
}

const ESTATUS_LABELS = {
  DISPONIBLE: 'DISPONIBLE',
  EN_CURSO:   'EN CURSO',
  FINALIZADA: 'FINALIZADA',
  CANCELADA:  'CANCELADA',
}

function formatEstatus(val) {
  return ESTATUS_LABELS[val] ?? val
}

// Mapa estático con los nombres exactos de la BD → color + etiqueta legible
const CATEGORIAS_MAP = {
  'MENTE_CUERPO':              { color: 'bg-violet-50 text-violet-700 border-violet-200',   label: 'MENTE CUERPO' },
  'DEPORTES_RAQUETA':          { color: 'bg-sky-50 text-sky-700 border-sky-200',             label: 'DEPORTES RAQUETA' },
  'DEPORTES_EQUIPO':           { color: 'bg-emerald-50 text-emerald-700 border-emerald-200', label: 'DEPORTES EQUIPO' },
  'ACONDICIONAMIENTO_FISICO':  { color: 'bg-amber-50 text-amber-700 border-amber-200',       label: 'ACONDICIONAMIENTO FÍSICO' },
  'GIMNASIA':                  { color: 'bg-pink-50 text-pink-700 border-pink-200',          label: 'GIMNASIA' },
  'ACUATICO':                  { color: 'bg-cyan-50 text-cyan-700 border-cyan-200',          label: 'ACUÁTICO' },
  'INFANTIL':                  { color: 'bg-lime-50 text-lime-700 border-lime-200',          label: 'INFANTIL' },
  'Artes Marciales Mixtas':    { color: 'bg-red-50 text-red-700 border-red-200',             label: 'ARTES MARCIALES MIXTAS' },
  'Recreación':                { color: 'bg-orange-50 text-orange-700 border-orange-200',    label: 'RECREACIÓN' },
}

function categoriaColor(nombre) {
  if (!nombre) return 'bg-slate-50 text-slate-500 border-slate-200'
  return CATEGORIAS_MAP[nombre]?.color ?? 'bg-slate-50 text-slate-500 border-slate-200'
}

function formatCategoria(nombre) {
  if (!nombre) return '—'
  return CATEGORIAS_MAP[nombre]?.label ?? nombre.replace(/_/g, ' ').toUpperCase()
}

const HORAS_OPCIONES = Array.from({ length: 16 }, (_, i) => {
  const h = String(i + 6).padStart(2, '0')
  return `${h}:00`
})

function formatDate(d) {
  if (!d) return '—'
  const [y, m, day] = d.split('-')
  return `${day}/${m}/${y}`
}

function formatTipoUsuario(tipo) {
  if (!tipo) return '—'
  return tipo.replace(/_/g, ' ')
}

// Tokens de color para el badge de estatus dentro del header del modal
const ESTATUS_MODAL_BADGE = {
  DISPONIBLE: 'bg-emerald-500/20 text-emerald-100 border-emerald-400/30',
  EN_CURSO:   'bg-orange-500/20 text-orange-100 border-orange-400/30',
  FINALIZADA: 'bg-blue-500/20 text-blue-100 border-blue-400/30',
  CANCELADA:  'bg-red-500/20 text-red-100 border-red-400/30',
}

defineExpose({ fetchSesiones })
</script>

<template>
  <div class="flex flex-col h-full bg-slate-50 font-sans min-h-0 relative">

    <!-- ══════════ HEADER ══════════ -->
    <div
      v-if="isLoading || loadError || plantillaEncontrada === true"
      class="px-6 py-4 bg-white border-b border-slate-200 shrink-0 shadow-sm"
    >
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-black text-slate-800 tracking-tight">Monitoreo de Sesiones Semanales</h2>
          <p class="text-xs text-slate-500 font-medium">Visualiza las sesiones publicadas y gestiona cancelaciones.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <!-- Recargar -->
          <button
            @click="fetchSesiones()"
            :disabled="isLoading"
            class="p-2 text-slate-500 hover:text-slate-900 bg-white border border-slate-200 hover:border-slate-400 rounded-xl transition-all focus:outline-none disabled:opacity-50"
            title="Recargar sesiones"
          >
            <svg class="w-4 h-4" :class="isLoading ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>

          <!-- Switcher Tabla / Calendario -->
          <div class="flex p-1 bg-slate-100 rounded-2xl shadow-inner border border-slate-200">
            <button
              @click="activeView = 'tabla'"
              class="py-1.5 px-4 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all"
              :class="activeView === 'tabla'
                ? 'bg-slate-900 text-white shadow-sm'
                : 'text-slate-500 hover:text-slate-800'"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
              </svg>
              Tabla
            </button>
            <button
              @click="activeView = 'calendario'"
              class="py-1.5 px-4 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all"
              :class="activeView === 'calendario'
                ? 'bg-slate-900 text-white shadow-sm'
                : 'text-slate-500 hover:text-slate-800'"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75" />
              </svg>
              Calendario
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════ CONTENIDO ══════════ -->
    <div class="flex-1 overflow-hidden p-6 min-h-0">

      <!-- Skeleton — solo en carga inicial cuando no hay datos previos -->
      <div v-if="isLoading && sesiones.length === 0" class="h-full flex flex-col gap-3">
        <div class="h-12 bg-slate-200 rounded-2xl animate-pulse" />
        <div v-for="i in 5" :key="i" class="h-14 bg-white border border-slate-200 rounded-2xl animate-pulse" :style="`opacity:${1 - i * 0.12}`" />
      </div>

      <!-- Error -->
      <div v-else-if="loadError && sesiones.length === 0" class="h-full flex items-center justify-center">
        <div class="text-center max-w-sm">
          <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
          </div>
          <p class="text-sm font-extrabold text-slate-800 mb-1">Error al cargar sesiones</p>
          <p class="text-xs text-slate-500 mb-4">{{ loadError }}</p>
          <button
            @click="fetchSesiones"
            class="px-4 py-2 text-xs font-extrabold text-white bg-slate-900 hover:bg-slate-800 rounded-xl transition-colors"
          >
            Reintentar
          </button>
        </div>
      </div>

      <!-- Empty global — no existe plantilla activa publicada (independiente de filtros) -->
      <div v-else-if="plantillaEncontrada === false" class="h-full flex items-center justify-center">
        <div class="w-full max-w-lg">
          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="h-1.5 w-full bg-gradient-to-r from-violet-600 via-purple-500 to-purple-400" />
            <div class="p-8 text-center">
              <div class="relative mx-auto mb-6 w-20 h-20">
                <div class="absolute inset-0 rounded-2xl bg-slate-50 border border-slate-200 rotate-6" />
                <div class="absolute inset-0 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center">
                  <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                  </svg>
                </div>
              </div>
              <h3 class="text-xl font-black text-slate-800 mb-2 tracking-tight">Sin programación publicada</h3>
              <p class="text-sm text-slate-500 leading-relaxed max-w-sm mx-auto">
                Aún no se han generado sesiones para esta semana. Publica una plantilla desde
                <span class="font-bold text-slate-700">Gestión de Plantillas</span> para verlas aquí.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Contenido — se muestra cuando hay plantilla activa (incluso si los filtros dejan 0 resultados) -->
      <template v-else-if="plantillaEncontrada === true">

        <!-- ══════════ VISTA TABLA ══════════ -->
        <div
          v-show="activeView === 'tabla'"
          class="h-full overflow-hidden flex flex-col gap-4"
        >

          <!-- ── BARRA DE FILTROS ── -->
          <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-2.5 shrink-0 border-t-2 border-t-slate-900">

            <!-- Fila 1: Buscador + Disciplina (alineados al fondo del label) -->
            <div class="flex items-end gap-2.5">
              <!-- Buscador (más ancho) -->
              <div class="relative flex-1">
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 px-1 mb-0.5">Buscar</label>
                <span class="absolute bottom-0 left-0 flex items-center pb-[9px] pl-3.5 pointer-events-none">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </span>
                <input
                  v-model="searchFilter"
                  type="text"
                  placeholder="Instructor, espacio, ID…"
                  class="w-full pl-9 pr-4 py-2 text-xs font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 focus:bg-white transition-all"
                />
              </div>
              <!-- Disciplina (compacto) -->
              <div class="flex flex-col gap-0.5 w-52 shrink-0">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Disciplina</label>
                <select v-model="selectedDisciplina"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 cursor-pointer transition-all">
                  <option value="TODAS">Todas las disciplinas</option>
                  <option v-for="d in disciplinasUnicas" :key="d" :value="d">{{ d }}</option>
                </select>
              </div>
            </div>

            <!-- Fila 2: Categoría + Instructor + Espacio + Día + Tipo + Estatus + Hora -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-2.5">

              <div class="flex flex-col gap-0.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Categoría</label>
                <select v-model="selectedCategoria"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 cursor-pointer transition-all">
                  <option value="TODAS">Todas</option>
                  <option v-for="c in categoriasUnicas" :key="c" :value="c">{{ formatCategoria(c) }}</option>
                </select>
              </div>

              <div class="flex flex-col gap-0.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Instructor</label>
                <select v-model="selectedInstructor"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 cursor-pointer transition-all">
                  <option value="TODOS">Todos</option>
                  <option v-for="i in instructoresUnicos" :key="i" :value="i">{{ i }}</option>
                </select>
              </div>

              <div class="flex flex-col gap-0.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Espacio</label>
                <select v-model="selectedEspacio"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 cursor-pointer transition-all">
                  <option value="TODOS">Todos</option>
                  <option v-for="e in espaciosUnicos" :key="e" :value="e">{{ e }}</option>
                </select>
              </div>

              <div class="flex flex-col gap-0.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Día</label>
                <select v-model="selectedDia"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 cursor-pointer transition-all">
                  <option value="TODOS">Todos</option>
                  <option v-for="(label, key) in DIAS_LABEL" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>

              <div class="flex flex-col gap-0.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Tipo Clase</label>
                <select v-model="selectedTipoClase"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 cursor-pointer transition-all">
                  <option value="TODOS">Todas</option>
                  <option value="ABIERTA">Abierta</option>
                  <option value="CERRADA">Cerrada</option>
                </select>
              </div>

              <div class="flex flex-col gap-0.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Estatus</label>
                <select v-model="selectedEstatus"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 cursor-pointer transition-all">
                  <option value="TODOS">Todos</option>
                  <option value="DISPONIBLE">Disponible</option>
                  <option value="EN_CURSO">En Curso</option>
                  <option value="FINALIZADA">Finalizada</option>
                  <option value="CANCELADA">Cancelada</option>
                </select>
              </div>

              <div class="flex flex-col gap-0.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Rango Hora</label>
                <div class="flex items-center gap-1">
                  <select v-model="horaDesde"
                    class="w-full min-w-0 px-2 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 cursor-pointer transition-all tabular-nums">
                    <option value="">Desde</option>
                    <option v-for="h in HORAS_OPCIONES" :key="h" :value="h">{{ h }}</option>
                  </select>
                  <span class="text-slate-400 text-[10px] font-black shrink-0">–</span>
                  <select v-model="horaHasta"
                    class="w-full min-w-0 px-2 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 cursor-pointer transition-all tabular-nums">
                    <option value="">Hasta</option>
                    <option v-for="h in HORAS_OPCIONES" :key="h" :value="h">{{ h }}</option>
                  </select>
                </div>
              </div>
            </div>

            <Transition enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0" enter-to-class="opacity-100"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100" leave-to-class="opacity-0">
              <div v-if="hayFiltrosActivos" class="flex justify-end pt-0.5">
                <button @click="limpiarFiltros"
                  class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1.5 transition-colors">
                  <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6L6 18M6 6l12 12" />
                  </svg>
                  Limpiar filtros
                </button>
              </div>
            </Transition>
          </div>

          <!-- ── TABLA ── -->
          <div v-if="sesionesFiltradas.length === 0" class="flex-1 flex flex-col items-center justify-center bg-white rounded-2xl border border-slate-200 shadow-sm py-16 gap-3">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <p class="text-sm font-extrabold text-slate-600">Sin resultados para los filtros actuales</p>
            <p class="text-xs text-slate-400">Prueba ajustando los filtros o el término de búsqueda.</p>
            <button @click="limpiarFiltros"
              class="mt-1 px-4 py-2 text-xs font-extrabold text-white bg-slate-900 hover:bg-slate-800 rounded-xl transition-colors">
              Limpiar filtros
            </button>
          </div>

          <div v-else class="flex-1 overflow-hidden flex flex-col bg-white rounded-2xl border border-slate-200 shadow-sm relative">
            <div class="flex-1 table-scroll">
              <table class="w-full border-collapse text-left min-w-[1350px]">
                <thead>
                  <tr class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
                    <th class="py-3.5 px-5 font-extrabold">ID</th>
                    <th class="py-3.5 px-4 text-center font-extrabold">Estatus</th>
                    <th class="py-3.5 px-4 text-center font-extrabold">Tipo</th>
                    <th class="py-3.5 px-4 font-extrabold">Fecha</th>
                    <th class="py-3.5 px-4 font-extrabold">Disciplina</th>
                    <th class="py-3.5 px-4 font-extrabold">Categoría</th>
                    <th class="py-3.5 px-4 font-extrabold">Instructor</th>
                    <th class="py-3.5 px-4 font-extrabold">Espacio</th>
                    <th class="py-3.5 px-4 text-center font-extrabold">Día</th>
                    <th class="py-3.5 px-4 text-center font-extrabold">Hora Inicio</th>
                    <th class="py-3.5 px-4 text-center font-extrabold">Hora Fin</th>
                    <th class="py-3.5 px-4 text-center font-extrabold">Inscritos</th>
                    <th class="py-3.5 px-5 text-right font-extrabold">Acción</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
                  <tr
                    v-for="s in sesionesEnPagina"
                    :key="s.id_sesion"
                    class="hover:bg-slate-50/60 transition-colors"
                    :class="s.estatus_sesion === 'CANCELADA' ? 'bg-red-50/20' : ''"
                  >
                    <td class="py-3.5 px-5 font-mono font-bold text-slate-400">#{{ s.id_sesion }}</td>

                    <td class="py-3.5 px-4 text-center">
                      <span
                        class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border"
                        :class="ESTATUS_COLORS[s.estatus_sesion] ?? 'bg-slate-50 text-slate-600 border-slate-200'"
                      >
                        {{ formatEstatus(s.estatus_sesion) }}
                      </span>
                    </td>

                    <td class="py-3.5 px-4 text-center">
                      <span
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border"
                        :class="s.requiere_inscripcion
                          ? 'bg-violet-50 text-violet-700 border-violet-200'
                          : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                      >
                        <span class="w-1.5 h-1.5 rounded-full shrink-0"
                          :class="s.requiere_inscripcion ? 'bg-violet-500' : 'bg-emerald-500'" />
                        {{ s.requiere_inscripcion ? 'CERRADA' : 'ABIERTA' }}
                      </span>
                    </td>

                    <td class="py-3.5 px-4 text-slate-500 font-medium tabular-nums">{{ formatDate(s.fecha_sesion) }}</td>

                    <td class="py-3.5 px-4 text-slate-800 font-extrabold">{{ s.disciplina ?? '—' }}</td>

                    <td class="py-3.5 px-4">
                      <span
                        v-if="s.categoria"
                        class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border tracking-wide"
                        :class="categoriaColor(s.categoria)"
                      >
                        {{ formatCategoria(s.categoria) }}
                      </span>
                      <span v-else class="text-slate-300 text-[10px] font-bold">—</span>
                    </td>

                    <td class="py-3.5 px-4 text-slate-600 font-bold">{{ s.instructor ?? '—' }}</td>
                    <td class="py-3.5 px-4 text-slate-500 font-medium">{{ s.espacio ?? '—' }}</td>

                    <td class="py-3.5 px-4 text-center">
                      <span
                        class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border"
                        :class="DIAS_COLORS[s.dia_semana] ?? 'bg-slate-50 text-slate-600 border-slate-200'"
                      >
                        {{ DIAS_LABEL[s.dia_semana] ?? s.dia_semana }}
                      </span>
                    </td>

                    <td class="py-3.5 px-4 text-center tabular-nums text-slate-700 font-bold">{{ (s.hora_inicio ?? '').slice(0,5) }}</td>
                    <td class="py-3.5 px-4 text-center tabular-nums text-slate-700 font-bold">{{ (s.hora_fin ?? '').slice(0,5) }}</td>

                    <td class="py-3.5 px-4 text-center">
                      <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-extrabold tabular-nums">
                        {{ s.cantidad_inscritos ?? 0 }}
                      </span>
                    </td>

                    <td class="py-3.5 px-5 text-right">
                      <button
                        @click="abrirDetalle(s)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-extrabold text-white bg-slate-900 hover:bg-slate-800 rounded-xl transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                        </svg>
                        Gestionar
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Footer — paginación visual client-side -->
            <div class="px-6 py-3 bg-slate-900 border-t border-slate-700 flex items-center justify-between text-xs text-slate-400 font-bold shrink-0">
              <span class="tabular-nums text-slate-300">
                {{ sesionesFiltradas.length }} {{ sesionesFiltradas.length === 1 ? 'sesión' : 'sesiones' }}
                <span class="text-slate-600 mx-1">·</span>
                página <span class="text-white">{{ currentPage }}</span> de <span class="text-white">{{ lastPage }}</span>
              </span>
              <div class="flex items-center gap-2">
                <button
                  @click="currentPage--"
                  :disabled="currentPage <= 1"
                  class="w-7 h-7 rounded-lg border border-slate-700 bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-slate-700 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                  </svg>
                </button>
                <span class="tabular-nums text-white font-black">{{ currentPage }}</span>
                <button
                  @click="currentPage++"
                  :disabled="currentPage >= lastPage"
                  class="w-7 h-7 rounded-lg border border-slate-700 bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-slate-700 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ══════════ VISTA CALENDARIO ══════════ -->
        <div v-show="activeView === 'calendario'" class="h-full bg-white rounded-3xl border border-slate-200 shadow-sm p-4">
          <CalendarioPublicadas
            :sesiones="sesiones"
            :todas-las-disciplinas="disciplinasUnicas"
            :is-loading="isLoading"
            @select-sesion="abrirDetalle"
          />
        </div>

      </template>
    </div>

    <!-- ══════════ MODAL DE GESTIÓN ══════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95"
      >
        <div
          v-if="showModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="cerrarModal"
        >
          <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden max-h-[92vh] flex flex-col">

            <!-- ── Encabezado ── -->
            <div
              class="px-7 pt-6 pb-5 text-white relative shrink-0"
              :class="tempEstatus === 'CANCELADA'
                ? 'bg-linear-to-br from-rose-500 to-red-700'
                : sesionSeleccionada?.requiere_inscripcion
                  ? 'bg-linear-to-br from-violet-600 to-purple-800'
                  : 'bg-linear-to-br from-emerald-500 to-teal-700'"
            >
              <button
                @click="cerrarModal"
                class="absolute top-4 right-4 text-white/70 hover:text-white bg-white/10 hover:bg-white/20 p-1.5 rounded-full transition-colors focus:outline-none"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>

              <div class="flex items-start gap-4">
                <!-- Ícono de disciplina -->
                <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-sm flex items-center justify-center shadow-inner shrink-0">
                  <DisciplineIcon :name="sesionSeleccionada?.disciplina ?? ''" class="w-7 h-7 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-white/60 text-[10px] uppercase font-black tracking-widest mb-0.5">Gestión de Sesión</p>
                  <h3 class="text-white text-2xl font-black leading-tight truncate">{{ sesionSeleccionada?.disciplina }}</h3>
                  <!-- Fila de badges: categoría · estatus · ID -->
                  <div class="flex items-center flex-wrap gap-2 mt-2">
                    <span
                      class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-bold border"
                      :class="categoriaColor(sesionSeleccionada?.categoria)"
                    >{{ formatCategoria(sesionSeleccionada?.categoria) }}</span>
                    <!-- Badge estatus operativo real -->
                    <span
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black border tracking-wide"
                      :class="ESTATUS_MODAL_BADGE[sesionSeleccionada?.estatus_sesion] ?? 'bg-white/10 text-white/70 border-white/20'"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-current opacity-80" />
                      {{ formatEstatus(sesionSeleccionada?.estatus_sesion) ?? '—' }}
                    </span>
                    <span class="text-white/40 text-[11px] font-mono">#{{ sesionSeleccionada?.id_sesion }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- ── Cuerpo ── -->
            <div class="p-6 space-y-4 overflow-y-auto flex-1">

              <!-- Fila 1: Fecha + Horario (inicio/fin en misma fila) -->
              <div class="grid grid-cols-3 gap-3">

                <!-- Fecha -->
                <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-2xl p-3.5">
                  <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-4.5 h-4.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Fecha</p>
                    <p class="text-base font-extrabold text-slate-800 leading-tight">{{ formatDate(sesionSeleccionada?.fecha_sesion) }}</p>
                    <p class="text-xs font-semibold text-slate-500 mt-0.5">{{ DIAS_LABEL[sesionSeleccionada?.dia_semana] ?? sesionSeleccionada?.dia_semana }}</p>
                  </div>
                </div>

                <!-- Hora inicio -->
                <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-2xl p-3.5">
                  <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-4.5 h-4.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" />
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Hora inicio</p>
                    <p class="text-xl font-black text-slate-800 leading-none tabular-nums tracking-tighter">
                      {{ (sesionSeleccionada?.hora_inicio ?? '').slice(0,5) }}
                    </p>
                  </div>
                </div>

                <!-- Hora fin -->
                <div class="flex items-start gap-2.5 bg-slate-50 border border-slate-100 rounded-2xl p-3.5">
                  <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-4.5 h-4.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Hora fin</p>
                    <p class="text-xl font-black text-slate-800 leading-none tabular-nums tracking-tighter">
                      {{ (sesionSeleccionada?.hora_fin ?? '').slice(0,5) }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Fila 2: Inscritos + Tipo de clase -->
              <div class="grid grid-cols-2 gap-3">

                <!-- Inscritos -->
                <div class="flex items-start gap-3 bg-slate-50 border border-slate-100 rounded-2xl p-3.5">
                  <div class="w-9 h-9 rounded-xl bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-sm">
                    <!-- users icon -->
                    <svg class="w-4.5 h-4.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Inscritos</p>
                    <p class="text-2xl font-black text-slate-800 leading-none tabular-nums">{{ sesionSeleccionada?.cantidad_inscritos ?? 0 }}</p>
                    <p class="text-xs font-semibold text-slate-500 mt-0.5">
                      {{ sesionSeleccionada?.requiere_inscripcion ? `de ${sesionSeleccionada?.cupo_maximo ?? '—'} cupo` : '' }}
                    </p>
                  </div>
                </div>

                <!-- Tipo de clase -->
                <div
                  class="flex items-start gap-3 border rounded-2xl p-3.5"
                  :class="sesionSeleccionada?.requiere_inscripcion
                    ? 'bg-violet-50/60 border-violet-100'
                    : 'bg-emerald-50/60 border-emerald-100'"
                >
                  <div
                    class="w-9 h-9 rounded-xl border flex items-center justify-center shrink-0 shadow-sm"
                    :class="sesionSeleccionada?.requiere_inscripcion
                      ? 'bg-white border-violet-200'
                      : 'bg-white border-emerald-200'"
                  >
                    <svg class="w-4.5 h-4.5" :class="sesionSeleccionada?.requiere_inscripcion ? 'text-violet-500' : 'text-emerald-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path v-if="!sesionSeleccionada?.requiere_inscripcion" stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                      <path v-else stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4" />
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <p class="text-[9px] font-black uppercase tracking-widest mb-0.5"
                      :class="sesionSeleccionada?.requiere_inscripcion ? 'text-violet-400' : 'text-emerald-400'">
                      Tipo de clase
                    </p>
                    <p class="text-base font-black leading-tight"
                      :class="sesionSeleccionada?.requiere_inscripcion ? 'text-violet-700' : 'text-emerald-700'">
                      {{ sesionSeleccionada?.requiere_inscripcion ? 'CERRADA' : 'ABIERTA' }}
                    </p>
                    <p class="text-xs font-semibold mt-0.5"
                      :class="sesionSeleccionada?.requiere_inscripcion ? 'text-violet-500' : 'text-emerald-500'">
                      {{ sesionSeleccionada?.requiere_inscripcion ? 'Requiere inscripción' : 'Acceso libre' }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Instructor + Espacio -->
              <div class="grid grid-cols-2 gap-3">
                <div class="flex items-center gap-3 bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3">
                  <div class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Instructor</p>
                    <p class="text-sm font-bold text-slate-800 truncate">{{ sesionSeleccionada?.instructor ?? '—' }}</p>
                  </div>
                </div>

                <div class="flex items-center gap-3 bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3">
                  <div class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Espacio</p>
                    <p class="text-sm font-bold text-slate-800 truncate">{{ sesionSeleccionada?.espacio ?? '—' }}</p>
                  </div>
                </div>
              </div>

              <!-- Lista de asistencia (lazy + colapsable por grupos) -->
              <div>
                <!-- Header con total -->
                <div class="flex items-center justify-between mb-2.5">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Lista de asistencia</p>
                  <span v-if="!isLoadingAsistencia"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-900 text-white text-[11px] font-black tracking-wider">
                    <!-- user-group icon (Heroicons outline) -->
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                    {{ sesionSeleccionada?.cantidad_inscritos ?? 0 }} TOTAL
                  </span>
                </div>

                <!-- Skeleton -->
                <div v-if="isLoadingAsistencia" class="space-y-2">
                  <div v-for="i in 3" :key="i" class="h-11 bg-slate-100 rounded-xl animate-pulse" :style="`opacity:${1 - i * 0.25}`" />
                </div>

                <!-- Sin inscritos -->
                <div v-else-if="totalInscritos === 0"
                  class="flex flex-col items-center justify-center gap-2 py-6 bg-slate-50 border border-slate-100 rounded-2xl">
                  <!-- Icono personas corriendo (disciplinas) -->
                  <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75a3.75 3.75 0 110 0zM19.5 19.875a7.5 7.5 0 00-7.5-7.5" />
                  </svg>
                  <p class="text-xs font-bold text-slate-400">Sin usuarios registrados en esta sesión</p>
                </div>

                <!-- Grupos -->
                <div v-else class="space-y-2">

                  <!-- CERRADA (requiere_inscripcion=true): muestra INSCRITO + ASISTENCIA + NO SHOW -->
                  <!-- ABIERTA  (requiere_inscripcion=false): muestra INSCRITO + ASISTENCIA -->

                  <!-- INSCRITO (azul) — siempre visible -->
                  <div class="rounded-2xl border border-blue-100 overflow-hidden">
                    <button type="button" @click="toggleGrupo('INSCRITO')"
                      class="w-full flex items-center gap-2.5 px-3.5 py-2.5 bg-blue-50/60 hover:bg-blue-50 transition-colors text-left">
                      <div class="w-7 h-7 rounded-lg bg-white border border-blue-200 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                      </div>
                      <span class="flex-1 text-[11px] font-black uppercase tracking-wider text-blue-700">Inscrito</span>
                      <span class="inline-flex items-center justify-center min-w-[26px] h-5 px-1.5 rounded-full bg-blue-600 text-white text-[10px] font-black tabular-nums">
                        {{ grupoInscritos.length }}
                      </span>
                      <svg class="w-3.5 h-3.5 text-blue-500 shrink-0 transition-transform duration-150"
                        :class="grupoExpandido.INSCRITO ? 'rotate-90' : ''"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                      </svg>
                    </button>
                    <div v-if="grupoExpandido.INSCRITO" class="bg-white max-h-44 overflow-y-auto divide-y divide-slate-50">
                      <div v-if="grupoInscritos.length === 0" class="px-3.5 py-3 text-[11px] font-bold text-slate-400 text-center">
                        Sin usuarios en este grupo
                      </div>
                      <div v-for="ins in grupoInscritos" :key="ins.id_inscripcion"
                        class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-blue-50/40 transition-colors">
                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px] font-black shrink-0">
                          {{ (ins.nombre_completo ?? '?').charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-[11px] font-bold text-slate-700 truncate">{{ ins.nombre_completo ?? '—' }}</p>
                          <p class="text-[9px] font-semibold text-slate-400 font-mono">Acción #{{ ins.numero_accion ?? '—' }}</p>
                        </div>
                        <span class="px-1.5 py-0.5 rounded-md text-[8px] font-black uppercase shrink-0 border"
                          :class="ins.tipo_usuario === 'SOCIO_TITULAR'
                            ? 'bg-sky-50 text-sky-700 border-sky-200'
                            : ins.tipo_usuario === 'MIEMBRO_FAMILIAR'
                              ? 'bg-purple-50 text-purple-700 border-purple-200'
                              : 'bg-teal-50 text-teal-700 border-teal-200'">
                          {{ formatTipoUsuario(ins.tipo_usuario) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- ASISTENCIA (verde) — siempre visible -->
                  <div class="rounded-2xl border border-emerald-100 overflow-hidden">
                    <button type="button" @click="toggleGrupo('ASISTENCIA')"
                      class="w-full flex items-center gap-2.5 px-3.5 py-2.5 bg-emerald-50/60 hover:bg-emerald-50 transition-colors text-left">
                      <div class="w-7 h-7 rounded-lg bg-white border border-emerald-200 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <span class="flex-1 text-[11px] font-black uppercase tracking-wider text-emerald-700">Asistencia</span>
                      <span class="inline-flex items-center justify-center min-w-[26px] h-5 px-1.5 rounded-full bg-emerald-600 text-white text-[10px] font-black tabular-nums">
                        {{ grupoAsistencia.length }}
                      </span>
                      <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0 transition-transform duration-150"
                        :class="grupoExpandido.ASISTENCIA ? 'rotate-90' : ''"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                      </svg>
                    </button>
                    <div v-if="grupoExpandido.ASISTENCIA" class="bg-white max-h-44 overflow-y-auto divide-y divide-slate-50">
                      <div v-if="grupoAsistencia.length === 0" class="px-3.5 py-3 text-[11px] font-bold text-slate-400 text-center">
                        Sin usuarios en este grupo
                      </div>
                      <div v-for="ins in grupoAsistencia" :key="ins.id_inscripcion"
                        class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-emerald-50/40 transition-colors">
                        <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px] font-black shrink-0">
                          {{ (ins.nombre_completo ?? '?').charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-[11px] font-bold text-slate-700 truncate">{{ ins.nombre_completo ?? '—' }}</p>
                          <p class="text-[9px] font-semibold text-slate-400 font-mono">Acción #{{ ins.numero_accion ?? '—' }}</p>
                        </div>
                        <span class="px-1.5 py-0.5 rounded-md text-[8px] font-black uppercase shrink-0 border"
                          :class="ins.tipo_usuario === 'SOCIO_TITULAR'
                            ? 'bg-sky-50 text-sky-700 border-sky-200'
                            : ins.tipo_usuario === 'MIEMBRO_FAMILIAR'
                              ? 'bg-purple-50 text-purple-700 border-purple-200'
                              : 'bg-teal-50 text-teal-700 border-teal-200'">
                          {{ formatTipoUsuario(ins.tipo_usuario) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- NO SHOW (rojo) — solo CERRADAS -->
                  <div v-if="sesionSeleccionada?.requiere_inscripcion"
                    class="rounded-2xl border border-red-100 overflow-hidden">
                    <button type="button" @click="toggleGrupo('NO_SHOW')"
                      class="w-full flex items-center gap-2.5 px-3.5 py-2.5 bg-red-50/60 hover:bg-red-50 transition-colors text-left">
                      <div class="w-7 h-7 rounded-lg bg-white border border-red-200 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <span class="flex-1 text-[11px] font-black uppercase tracking-wider text-red-700">No Show</span>
                      <span class="inline-flex items-center justify-center min-w-[26px] h-5 px-1.5 rounded-full bg-red-600 text-white text-[10px] font-black tabular-nums">
                        {{ grupoNoShow.length }}
                      </span>
                      <svg class="w-3.5 h-3.5 text-red-500 shrink-0 transition-transform duration-150"
                        :class="grupoExpandido.NO_SHOW ? 'rotate-90' : ''"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                      </svg>
                    </button>
                    <div v-if="grupoExpandido.NO_SHOW" class="bg-white max-h-44 overflow-y-auto divide-y divide-slate-50">
                      <div v-if="grupoNoShow.length === 0" class="px-3.5 py-3 text-[11px] font-bold text-slate-400 text-center">
                        Sin usuarios en este grupo
                      </div>
                      <div v-for="ins in grupoNoShow" :key="ins.id_inscripcion"
                        class="flex items-center gap-2.5 px-3.5 py-2 hover:bg-red-50/40 transition-colors">
                        <div class="w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-[10px] font-black shrink-0">
                          {{ (ins.nombre_completo ?? '?').charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-[11px] font-bold text-slate-700 truncate">{{ ins.nombre_completo ?? '—' }}</p>
                          <p class="text-[9px] font-semibold text-slate-400 font-mono">Acción #{{ ins.numero_accion ?? '—' }}</p>
                        </div>
                        <span class="px-1.5 py-0.5 rounded-md text-[8px] font-black uppercase shrink-0 border"
                          :class="ins.tipo_usuario === 'SOCIO_TITULAR'
                            ? 'bg-sky-50 text-sky-700 border-sky-200'
                            : ins.tipo_usuario === 'MIEMBRO_FAMILIAR'
                              ? 'bg-purple-50 text-purple-700 border-purple-200'
                              : 'bg-teal-50 text-teal-700 border-teal-200'">
                          {{ formatTipoUsuario(ins.tipo_usuario) }}
                        </span>
                      </div>
                    </div>
                  </div>

                </div>
              </div>

              <!-- Estatus selector — solo DISPONIBLE / CANCELADA -->
              <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Cambiar estatus</p>
                <div class="flex p-1 bg-slate-100 rounded-2xl border border-slate-200 gap-1">
                  <button type="button" @click="tempEstatus = 'DISPONIBLE'"
                    class="flex-1 py-2.5 text-[11px] font-black rounded-xl border transition-all flex items-center justify-center gap-1.5 focus:outline-none"
                    :class="tempEstatus === 'DISPONIBLE' ? 'bg-emerald-600 border-emerald-500 text-white shadow-sm' : 'border-transparent text-slate-500 hover:bg-white/60 hover:text-slate-700'">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    DISPONIBLE
                  </button>
                  <button type="button" @click="tempEstatus = 'CANCELADA'"
                    class="flex-1 py-2.5 text-[11px] font-black rounded-xl border transition-all flex items-center justify-center gap-1.5 focus:outline-none"
                    :class="tempEstatus === 'CANCELADA' ? 'bg-red-600 border-red-500 text-white shadow-sm' : 'border-transparent text-slate-500 hover:bg-white/60 hover:text-slate-700'">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    CANCELADA
                  </button>
                </div>
              </div>

            </div>

            <!-- ── Footer ── -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/60 shrink-0">
              <button
                type="button"
                @click="cerrarModal"
                :disabled="isSaving"
                class="px-5 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition-all disabled:opacity-50"
              >
                Cancelar
              </button>
              <button
                type="button"
                @click="guardarCambios"
                :disabled="isSaving"
                class="px-6 py-2.5 text-xs font-black text-white bg-slate-900 hover:bg-slate-800 active:scale-95 rounded-xl transition-all shadow-sm flex items-center gap-2 disabled:opacity-50"
              >
                <svg v-if="isSaving" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Guardar cambios
              </button>
            </div>

          </div>
        </div>
      </Transition>

      <!-- ══════════ MODAL DE CONFIRMACIÓN DE CANCELACIÓN ══════════ -->
      <Transition
        enter-active-class="transition-all duration-150 ease-out"
        enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
        leave-active-class="transition-all duration-100 ease-in"
        leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95"
      >
        <div
          v-if="showConfirmCancelacion"
          class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm"
          @click.self="showConfirmCancelacion = false"
        >
          <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden">

            <!-- Header rojo -->
            <div class="bg-linear-to-br from-rose-500 to-red-700 px-7 pt-6 pb-5 text-white">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
                  <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                  </svg>
                </div>
                <div>
                  <p class="text-white/60 text-[10px] uppercase font-black tracking-widest mb-0.5">Acción irreversible</p>
                  <h3 class="text-white text-xl font-black leading-tight">Cancelar sesión</h3>
                </div>
              </div>
            </div>

            <!-- Cuerpo -->
            <div class="px-7 py-5 space-y-4">

              <!-- Datos de la sesión -->
              <div class="bg-red-50 border border-red-100 rounded-2xl px-4 py-3 text-sm text-red-800">
                <p class="font-black text-red-700 mb-1.5">{{ sesionSeleccionada?.disciplina }}</p>
                <div class="space-y-0.5 text-xs font-semibold text-red-600">
                  <p>Instructor: {{ sesionSeleccionada?.instructor ?? '—' }}</p>
                  <p>Espacio: {{ sesionSeleccionada?.espacio ?? '—' }}</p>
                  <p>Fecha: {{ formatDate(sesionSeleccionada?.fecha_sesion) }} · {{ (sesionSeleccionada?.hora_inicio ?? '').slice(0,5) }} – {{ (sesionSeleccionada?.hora_fin ?? '').slice(0,5) }}</p>
                </div>
              </div>

              <!-- Advertencia de notificaciones -->
              <div class="bg-amber-50 border border-amber-200 rounded-2xl px-4 py-3 flex gap-3">
                <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                <div>
                  <p class="text-xs font-black text-amber-800 mb-0.5">Se enviarán notificaciones automáticamente</p>
                  <p class="text-xs font-semibold text-amber-700">
                    Los <strong>{{ sesionSeleccionada?.cantidad_inscritos ?? 0 }} inscrito{{ (sesionSeleccionada?.cantidad_inscritos ?? 0) !== 1 ? 's' : '' }}</strong> recibirán una notificación in-app y correo electrónico con los detalles de la cancelación. El instructor también será notificado por correo.
                  </p>
                </div>
              </div>

              <p class="text-xs text-slate-500 font-semibold">
                Esta acción no se puede deshacer. ¿Confirmas que deseas cancelar esta sesión?
              </p>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 px-7 py-4 border-t border-slate-100 bg-slate-50/60">
              <button
                type="button"
                @click="showConfirmCancelacion = false"
                :disabled="isSaving"
                class="px-5 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-200/60 rounded-xl transition-all disabled:opacity-50"
              >
                Volver
              </button>
              <button
                type="button"
                @click="ejecutarGuardado"
                :disabled="isSaving"
                class="px-6 py-2.5 text-xs font-black text-white bg-red-600 hover:bg-red-700 active:scale-95 rounded-xl transition-all shadow-sm flex items-center gap-2 disabled:opacity-50"
              >
                <svg v-if="isSaving" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Sí, cancelar sesión
              </button>
            </div>

          </div>
        </div>
      </Transition>

    </Teleport>

  </div>
</template>

<style scoped>
.overflow-auto::-webkit-scrollbar { width: 6px; height: 6px; }
.overflow-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.overflow-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

/* Scroll horizontal nativo con rueda del ratón */
.table-scroll {
  overflow: auto;
  overscroll-behavior-x: contain;
}
.table-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
.table-scroll::-webkit-scrollbar-track { background: #f8fafc; border-radius: 3px; }
.table-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.table-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
