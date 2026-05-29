<script setup>
import { ref, computed, watch } from 'vue'
import { useWizardStore } from '@/stores/programacion/wizardStore'
import { useAlerts } from '@/composables/useAlerts'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'
import SelectDisciplina from './SelectDisciplina.vue'
import SelectEspacio    from './SelectEspacio.vue'
import SelectInstructor from './SelectInstructor.vue'
import SelectHora       from './SelectHora.vue'

const store = useWizardStore()
const { toastSuccess, confirmDelete, actionToast } = useAlerts()

// El modal se controla por el store: store.sesionEnDetalle = { origen, index }
const isOpen = computed(() =>
  store.sesionEnDetalle.origen !== null && store.sesionEnDetalle.index !== null
)

const sesionOriginal = computed(() => {
  const { origen, index } = store.sesionEnDetalle
  if (origen === null || index === null) return null
  if (origen === 'confirmada') return store.actividadesConfirmadas[index] ?? null
  if (origen === 'draft')      return store.draft.actividades[index]      ?? null
  return store.borradorLocal[index] ?? null
})

const esConfirmada = computed(() => store.sesionEnDetalle.origen === 'confirmada')

// ─── Modo lectura / edición ──────────────────────────────────────────────
const modoEdicion = ref(false)

// Buffer editable (clon de la sesión original)
const form = ref(null)

const DIAS_SEMANA = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO']
const DIAS_SHORT  = { LUNES: 'L', MARTES: 'M', MIERCOLES: 'X', JUEVES: 'J', VIERNES: 'V', SABADO: 'S', DOMINGO: 'D' }
const DIAS_LABEL  = { LUNES: 'Lunes', MARTES: 'Martes', MIERCOLES: 'Miércoles', JUEVES: 'Jueves', VIERNES: 'Viernes', SABADO: 'Sábado', DOMINGO: 'Domingo' }

const HORAS = Array.from({ length: 17 }, (_, i) => {
  const h = 6 + i
  return `${String(h).padStart(2, '0')}:00`
})

// Cada vez que cambia la sesión seleccionada, resetea el buffer y vuelve a lectura
watch(sesionOriginal, (s) => {
  if (s) {
    form.value = { ...s }
    modoEdicion.value = false
  } else {
    form.value = null
  }
}, { immediate: true })

// En edición el usuario debe poder cambiar la disciplina libremente:
// mostramos el catálogo completo sin filtro cruzado.
// (El cruce instructor/espacio se aplica HACIA AFUERA: si cambia disciplina,
// el espacio/instructor quedan desincronizados y el usuario los reselecciona.)
const disciplinasFiltradas = computed(() => {
  if (!form.value) return []
  return [...store.disciplinas].sort((a, b) =>
    a.nombre_disciplina.localeCompare(b.nombre_disciplina, 'es', { sensitivity: 'base' })
  )
})

const espaciosFiltrados = computed(() => {
  if (!form.value) return []
  const { id_disciplina } = form.value
  return store.espacios.filter(e => {
    if (e.es_clase_programada === false) return false
    return id_disciplina
      ? Array.isArray(e.disciplinas_ids) && e.disciplinas_ids.includes(id_disciplina)
      : true
  }).sort((a, b) =>
    a.nombre_espacio.localeCompare(b.nombre_espacio, 'es', { numeric: true, sensitivity: 'base' })
  )
})

const instructoresFiltrados = computed(() => {
  if (!form.value) return []
  const { id_disciplina } = form.value
  return store.instructores.filter(i =>
    id_disciplina
      ? Array.isArray(i.disciplinas_ids) && i.disciplinas_ids.includes(id_disciplina)
      : true
  ).sort((a, b) =>
    a.nombre_completo.localeCompare(b.nombre_completo, 'es', { sensitivity: 'base' })
  )
})

// ─── Resolución de nombres ─────────────────────────────────────────────────
function hidratarNombres(parcial) {
  const d = store.disciplinas.find(x => x.id_disciplina === parcial.id_disciplina)
  const e = store.espacios.find(x => x.id_espacio       === parcial.id_espacio)
  const i = store.instructores.find(x => x.id_instructor === parcial.id_instructor)
  return {
    ...parcial,
    _disciplina_nombre: d?.nombre_disciplina ?? parcial._disciplina_nombre ?? '',
    _disciplina_icono:  d?.icono             ?? parcial._disciplina_icono  ?? '',
    _espacio_nombre:    e?.nombre_espacio    ?? parcial._espacio_nombre    ?? '',
    _instructor_nombre: i?.nombre_completo   ?? parcial._instructor_nombre ?? '',
  }
}

// ─── Colisión live (solo en modo edición) ─────────────────────────────────
const conflictosLive = computed(() => {
  if (!form.value || !modoEdicion.value) return []
  const { origen, index } = store.sesionEnDetalle
  const candidata = hidratarNombres(form.value)
  return store.detectarColisionEnEdicion(candidata, origen, index)
})

const tieneConflicto = computed(() => conflictosLive.value.length > 0)

// Auto-limpiar filtros globales cuando aparece un conflicto en el modal de edición
watch(tieneConflicto, (tiene) => { if (tiene) store.resetFiltros() })

// Agrupa los conflictos por tipo para el diagnóstico detallado
const conflictosPorTipo = computed(() => {
  const espacio    = conflictosLive.value.filter(c => c.tipo === 'espacio')
  const instructor = conflictosLive.value.filter(c => c.tipo === 'instructor')
  return { espacio, instructor, ambos: espacio.length > 0 && instructor.length > 0 }
})

const formErrors = computed(() => {
  if (!form.value) return {}
  const e = {}
  if (!form.value.id_disciplina) e.id_disciplina = 'Selecciona una disciplina'
  if (!form.value.id_espacio)    e.id_espacio    = 'Selecciona un espacio'
  if (!form.value.id_instructor) e.id_instructor = 'Selecciona un instructor'
  if (!form.value.dia_semana)    e.dia_semana    = 'Selecciona un día'
  if (!form.value.hora_inicio)   e.hora_inicio   = 'Hora inicio requerida'
  if (!form.value.hora_fin)      e.hora_fin      = 'Hora fin requerida'
  if (form.value.hora_inicio && form.value.hora_fin && form.value.hora_inicio >= form.value.hora_fin) {
    e.hora_fin = 'Debe ser mayor a la hora de inicio'
  }
  if (form.value.requiere_inscripcion) {
    if (!form.value.cupo_maximo || form.value.cupo_maximo < 1) e.cupo_maximo = 'Mínimo 1 para clase cerrada'
    else if (form.value.cupo_maximo > 40) e.cupo_maximo = 'Cupo máximo 40'
  } else if (form.value.cupo_maximo && form.value.cupo_maximo > 40) {
    e.cupo_maximo = 'Cupo máximo 40'
  }
  return e
})

const tieneErrores  = computed(() => Object.keys(formErrors.value).length > 0)
const puedeGuardar  = computed(() => modoEdicion.value && !tieneErrores.value && !tieneConflicto.value)

// ─── Tokens visuales del HEADER ──────────────────────────────────────────
// Paleta saturada: emerald intenso para abierta, rose/red intenso para cerrada,
// amber intenso para conflicto. Gradiente como PenalizacionModal.
const headerTokens = computed(() => {
  if (!sesionOriginal.value) return {
    gradient: 'from-slate-500 to-slate-700',
    badgeBg: 'bg-slate-500/20',
    badgeText: 'text-slate-100',
    label: '—',
    sublabel: '',
  }

  const inscripcion = !!sesionOriginal.value.requiere_inscripcion
  const origen      = store.sesionEnDetalle.origen
  const sublabel    = origen === 'confirmada' ? 'Confirmada' : origen === 'borrador' ? 'Borrador' : 'Draft'

  if (tieneConflicto.value) {
    return {
      gradient:   'from-amber-500 to-orange-600',
      badgeBg:    'bg-amber-400/25',
      badgeText:  'text-amber-50',
      label:      'Conflicto de horario',
      sublabel,
    }
  }

  if (inscripcion) {
    return {
      gradient:   'from-violet-500 to-purple-700',
      badgeBg:    'bg-violet-400/25',
      badgeText:  'text-violet-50',
      label:      'Clase cerrada · Con inscripción',
      sublabel,
    }
  }

  return {
    gradient:   'from-emerald-500 to-teal-700',
    badgeBg:    'bg-emerald-400/25',
    badgeText:  'text-emerald-50',
    label:      'Clase abierta · Libre',
    sublabel,
  }
})

// ─── Acciones ─────────────────────────────────────────────────────────────
function entrarEdicion()   { modoEdicion.value = true }
function cancelarEdicion() {
  form.value = sesionOriginal.value ? { ...sesionOriginal.value } : null
  modoEdicion.value = false
}

async function guardarCambios() {
  if (!puedeGuardar.value) return
  const { origen, index } = store.sesionEnDetalle
  const hidratado = hidratarNombres({
    ...form.value,
    cupo_maximo: (form.value.cupo_maximo > 0) ? form.value.cupo_maximo : null,
  })
  await store.actualizarSesion(origen, index, hidratado)
  toastSuccess('Sesión actualizada')
  modoEdicion.value = false
}

async function pedirEliminar() {
  const { origen, index } = store.sesionEnDetalle
  if (origen === null || index === null) return
  const nombre = sesionOriginal.value?._disciplina_nombre ?? 'esta sesión'
  const result = await confirmDelete(
    `¿Eliminar "${nombre}"?`,
    'Esta acción es irreversible. La sesión será removida del calendario.',
    'Sí, Eliminar'
  )
  if (result.isConfirmed) {
    store.eliminarSesion(origen, index)
    actionToast('Sesión eliminada', 'success')
    cerrar()
  }
}

function cerrar() {
  store.cerrarDetalleSesion()
  modoEdicion.value = false
}

function setTipoClase(requiereInscripcion) {
  if (!form.value) return
  form.value.requiere_inscripcion = requiereInscripcion
}

function setDia(dia) {
  if (!form.value) return
  form.value.dia_semana = dia
}

function setHora(campo, hora) {
  if (!form.value) return
  form.value[campo] = hora
  if (campo === 'hora_inicio' && form.value.hora_fin && form.value.hora_fin <= hora) {
    form.value.hora_fin = ''
  }
}

const horasInicio = computed(() =>
  form.value?.hora_fin ? HORAS.filter(h => h < form.value.hora_fin) : HORAS
)
const horasFin = computed(() =>
  form.value?.hora_inicio ? HORAS.filter(h => h > form.value.hora_inicio) : HORAS
)
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0" enter-to-class="opacity-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100" leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen && form"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-6 bg-slate-900/70 backdrop-blur-sm"
        @click.self="cerrar"
      >
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 scale-[0.97] translate-y-6"
          enter-to-class="opacity-100 scale-100 translate-y-0"
        >
          <div
            v-if="isOpen && form"
            class="bg-white w-full max-w-2xl rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]"
          >

            <!-- ══ HEADER — gradiente saturado ══ -->
            <div
              :class="[
                'bg-linear-to-br text-white px-6 py-5 shrink-0',
                headerTokens.gradient
              ]"
            >
              <div class="flex items-start justify-between gap-4">
                <!-- Identidad de la sesión -->
                <div class="flex items-center gap-4 min-w-0">
                  <!-- Ícono disciplina en círculo -->
                  <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center shrink-0 shadow-inner">
                    <DisciplineIcon :name="form._disciplina_nombre ?? ''" :icon="form._disciplina_icono" class="w-7 h-7 text-white" />
                  </div>
                  <div class="min-w-0">
                    <!-- Nombre disciplina — protagonista -->
                    <h2 class="text-xl font-black text-white leading-tight truncate tracking-tight">
                      {{ form._disciplina_nombre || 'Sin disciplina' }}
                    </h2>
                    <!-- Día + Hora — segunda en jerarquía, bien visible -->
                    <p class="text-white/90 font-extrabold text-base tabular-nums mt-0.5 tracking-wide">
                      {{ DIAS_LABEL[form.dia_semana] ?? form.dia_semana }}
                      <span class="mx-1.5 text-white/50 font-light">·</span>
                      <span>{{ form.hora_inicio }}</span>
                      <span class="mx-1 text-white/50">–</span>
                      <span>{{ form.hora_fin }}</span>
                    </p>
                    <!-- Badge de estado -->
                    <div class="flex items-center gap-2 mt-2">
                      <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black tracking-wide', headerTokens.badgeBg, headerTokens.badgeText]">
                        <span v-if="tieneConflicto" class="w-1.5 h-1.5 rounded-full bg-amber-200 animate-pulse" />
                        <span v-else class="w-1.5 h-1.5 rounded-full bg-white/70" />
                        {{ headerTokens.sublabel }} · {{ headerTokens.label }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex items-center gap-1.5 shrink-0 mt-0.5">
                  <button
                    v-if="!modoEdicion"
                    type="button"
                    @click="entrarEdicion"
                    class="w-9 h-9 rounded-xl bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors"
                    title="Editar sesión"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    @click="pedirEliminar"
                    class="w-9 h-9 rounded-xl bg-white/20 hover:bg-red-500/70 text-white flex items-center justify-center transition-colors"
                    title="Eliminar sesión"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    @click="cerrar"
                    class="w-9 h-9 rounded-xl bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors ml-1"
                    title="Cerrar"
                  >
                    <svg class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- ══ BODY ══ -->
            <div class="overflow-y-auto flex-1 bg-slate-50/60">

              <!-- ── DIAGNÓSTICO DE COLISIÓN (modo edición) ── -->
              <div v-if="modoEdicion && tieneConflicto" class="m-5 mb-0">
                <div class="rounded-2xl overflow-hidden border border-amber-300 shadow-sm">
                  <!-- Cabecera del bloque de colisión -->
                  <div class="bg-amber-500 px-4 py-2.5 flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
                    </svg>
                    <p class="text-white text-xs font-black uppercase tracking-widest flex-1">
                      {{ conflictosPorTipo.ambos ? 'Conflicto de espacio e instructor' : conflictosPorTipo.espacio.length ? 'Conflicto de espacio' : 'Conflicto de instructor' }}
                    </p>
                    <span class="text-amber-100 text-[10px] font-bold tabular-nums">
                      {{ conflictosLive.length }} problema{{ conflictosLive.length !== 1 ? 's' : '' }}
                    </span>
                  </div>
                  <!-- Filas de conflicto -->
                  <div class="bg-amber-50 divide-y divide-amber-100">
                    <!-- Conflictos de ESPACIO -->
                    <div v-if="conflictosPorTipo.espacio.length > 0">
                      <div class="px-4 pt-2.5 pb-1 flex items-center gap-2">
                        <span class="w-5 h-5 rounded-md bg-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                          </svg>
                        </span>
                        <p class="text-[10px] font-black uppercase tracking-widest text-amber-700">Espacio ocupado</p>
                      </div>
                      <div v-for="(c, idx) in conflictosPorTipo.espacio" :key="'esp-' + idx" class="px-4 pb-2.5 ml-7">
                        <p class="text-sm font-extrabold text-amber-900 truncate">{{ c.nombre }}</p>
                        <p class="text-xs font-semibold text-amber-700 tabular-nums leading-snug">
                          Choque con <span class="font-black">{{ c.sesionInfractora?.disciplina || '—' }}</span>
                          con <span class="font-black">{{ c.sesionInfractora?.instructor || 'sin instructor' }}</span>
                          el {{ DIAS_LABEL[c.dia] }}
                          de {{ c.sesionInfractora?.hora_inicio }} a {{ c.sesionInfractora?.hora_fin }}
                        </p>
                      </div>
                    </div>
                    <!-- Conflictos de INSTRUCTOR -->
                    <div v-if="conflictosPorTipo.instructor.length > 0">
                      <div class="px-4 pt-2.5 pb-1 flex items-center gap-2">
                        <span class="w-5 h-5 rounded-md bg-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" />
                          </svg>
                        </span>
                        <p class="text-[10px] font-black uppercase tracking-widest text-amber-700">Instructor duplicado</p>
                      </div>
                      <div v-for="(c, idx) in conflictosPorTipo.instructor" :key="'inst-' + idx" class="px-4 pb-2.5 ml-7">
                        <p class="text-sm font-extrabold text-amber-900 truncate">{{ c.nombre }}</p>
                        <p class="text-xs font-semibold text-amber-700 tabular-nums leading-snug">
                          Ya asignado a <span class="font-black">{{ c.sesionInfractora?.disciplina || '—' }}</span>
                          en <span class="font-black">{{ c.sesionInfractora?.espacio || 'sin espacio' }}</span>
                          el {{ DIAS_LABEL[c.dia] }}
                          de {{ c.sesionInfractora?.hora_inicio }} a {{ c.sesionInfractora?.hora_fin }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ─────────── MODO LECTURA ─────────── -->
              <div v-if="!modoEdicion" class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">

                <!-- Disciplina -->
                <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Disciplina</p>
                  <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                      <DisciplineIcon :name="form._disciplina_nombre ?? ''" :icon="form._disciplina_icono" class="w-5 h-5" />
                    </span>
                    <p class="text-base font-extrabold text-slate-800 truncate leading-tight">{{ form._disciplina_nombre || '—' }}</p>
                  </div>
                </div>

                <!-- Espacio -->
                <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Espacio</p>
                  <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center shrink-0">
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                      </svg>
                    </span>
                    <div class="min-w-0">
                      <p class="text-base font-extrabold text-slate-800 truncate leading-tight">{{ form._espacio_nombre || '—' }}</p>
                      <p class="text-xs font-bold text-slate-400 mt-0.5">Cupo máx: {{ form.cupo_maximo ?? 'Sin límite' }}</p>
                    </div>
                  </div>
                </div>

                <!-- Instructor -->
                <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Instructor</p>
                  <p class="text-base font-extrabold text-slate-800 truncate">{{ form._instructor_nombre || '—' }}</p>
                </div>

                <!-- Tipo de clase -->
                <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Tipo de clase</p>
                  <span
                    :class="[
                      'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-black',
                      form.requiere_inscripcion
                        ? 'bg-violet-50 text-violet-700 border border-violet-200'
                        : 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                    ]"
                  >
                    <span :class="['w-2 h-2 rounded-full', form.requiere_inscripcion ? 'bg-violet-500' : 'bg-emerald-500']" />
                    {{ form.requiere_inscripcion ? 'Cerrada (con inscripción)' : 'Abierta (libre)' }}
                  </span>
                </div>

                <!-- Horario — full width, protagonista secundario -->
                <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm sm:col-span-2">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Horario</p>
                  <div class="flex items-center gap-5">
                    <div class="text-center">
                      <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Inicio</p>
                      <span class="text-3xl font-black text-slate-900 tabular-nums tracking-tighter">{{ form.hora_inicio }}</span>
                    </div>
                    <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                    </svg>
                    <div class="text-center">
                      <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Fin</p>
                      <span class="text-3xl font-black text-slate-900 tabular-nums tracking-tighter">{{ form.hora_fin }}</span>
                    </div>
                    <div class="ml-auto text-center">
                      <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Día</p>
                      <span class="text-base font-black text-slate-700 bg-slate-100 px-3 py-1 rounded-full">
                        {{ DIAS_LABEL[form.dia_semana] }}
                      </span>
                    </div>
                  </div>
                </div>

              </div>

              <!-- ─────────── MODO EDICIÓN ─────────── -->
              <div v-else class="p-5 space-y-4">

                <SelectDisciplina
                  v-model="form.id_disciplina"
                  :opciones="disciplinasFiltradas"
                  :error="formErrors.id_disciplina ?? ''"
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <SelectEspacio
                    v-model="form.id_espacio"
                    :opciones="espaciosFiltrados"
                    :error="formErrors.id_espacio ?? ''"
                  />
                  <SelectInstructor
                    v-model="form.id_instructor"
                    :opciones="instructoresFiltrados"
                    :error="formErrors.id_instructor ?? ''"
                  />
                </div>

                <!-- Día -->
                <div>
                  <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-2.5">Día</label>
                  <div class="flex gap-1.5">
                    <button
                      v-for="dia in DIAS_SEMANA"
                      :key="dia"
                      type="button"
                      @click="setDia(dia)"
                      :title="DIAS_LABEL[dia]"
                      :class="[
                        'flex-1 h-10 rounded-xl text-xs font-black transition-all duration-150',
                        form.dia_semana === dia
                          ? 'bg-primary-600 text-white shadow-sm'
                          : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700'
                      ]"
                    >{{ DIAS_SHORT[dia] }}</button>
                  </div>
                  <p v-if="formErrors.dia_semana" class="text-red-500 text-xs mt-1 font-medium">{{ formErrors.dia_semana }}</p>
                </div>

                <!-- Horarios -->
                <div class="grid grid-cols-2 gap-3">
                  <SelectHora
                    :model-value="form.hora_inicio"
                    @update:model-value="(v) => setHora('hora_inicio', v)"
                    :opciones="horasInicio"
                    label="Hora inicio"
                    :error="formErrors.hora_inicio ?? ''"
                  />
                  <SelectHora
                    :model-value="form.hora_fin"
                    @update:model-value="(v) => setHora('hora_fin', v)"
                    :opciones="horasFin"
                    label="Hora fin"
                    :error="formErrors.hora_fin ?? ''"
                  />
                </div>

                <!-- Cupo + tipo -->
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">
                      Cupo máx.
                      <span v-if="!form.requiere_inscripcion" class="normal-case font-semibold text-slate-400 tracking-normal ml-1">(opcional)</span>
                    </label>
                    <input
                      v-model.number="form.cupo_maximo"
                      type="number" min="1" max="40"
                      :placeholder="form.requiere_inscripcion ? 'Requerido' : 'Sin límite'"
                      :class="[
                        'w-full px-3 py-3 rounded-xl border text-sm font-bold transition-all duration-150',
                        formErrors.cupo_maximo ? 'border-red-300 bg-red-50 text-red-700' : 'border-slate-200 bg-white text-slate-800'
                      ]"
                    />
                    <p v-if="formErrors.cupo_maximo" class="text-red-500 text-[10px] mt-1 font-medium">{{ formErrors.cupo_maximo }}</p>
                  </div>
                  <div>
                    <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">Tipo de clase</label>
                    <div class="flex gap-1.5 bg-slate-100 p-1 rounded-xl">
                      <button
                        type="button"
                        @click="setTipoClase(false)"
                        :class="[
                          'flex-1 py-2.5 rounded-lg text-xs font-black transition-all duration-150',
                          !form.requiere_inscripcion
                            ? 'bg-emerald-500 text-white shadow-sm'
                            : 'text-slate-500 hover:text-slate-700'
                        ]"
                      >Abierta</button>
                      <button
                        type="button"
                        @click="setTipoClase(true)"
                        :class="[
                          'flex-1 py-2.5 rounded-lg text-xs font-black transition-all duration-150',
                          form.requiere_inscripcion
                            ? 'bg-violet-600 text-white shadow-sm'
                            : 'text-slate-500 hover:text-slate-700'
                        ]"
                      >Cerrada</button>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- ══ FOOTER ══ -->
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-t border-slate-100 bg-white shrink-0">
              <p v-if="modoEdicion && tieneConflicto" class="text-xs font-bold text-amber-600 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.5-13.5c.866-1.5 3.032-1.5 3.898 0l1.208 2.162" />
                </svg>
                Resuelve los conflictos para guardar
              </p>
              <p v-else-if="modoEdicion && tieneErrores" class="text-xs font-bold text-slate-400">Completa todos los campos</p>
              <span v-else />

              <div class="flex items-center gap-3">
                <template v-if="!modoEdicion">
                  <button
                    @click="cerrar"
                    class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors"
                  >Cerrar</button>
                </template>
                <template v-else>
                  <button
                    @click="cancelarEdicion"
                    class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors"
                  >Cancelar</button>
                  <button
                    @click="guardarCambios"
                    :disabled="!puedeGuardar"
                    :class="[
                      'px-6 py-2.5 rounded-xl text-sm font-black transition-colors shadow-sm flex items-center gap-2',
                      puedeGuardar
                        ? 'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-800'
                        : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                    ]"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4">
                      <path d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar cambios
                  </button>
                </template>
              </div>
            </div>

          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
