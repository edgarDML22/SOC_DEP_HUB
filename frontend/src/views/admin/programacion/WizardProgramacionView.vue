<script setup>
import { ref, computed, watch } from 'vue'
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

const DIAS_RECOVERY_LABEL = { LUNES: 'Lun', MARTES: 'Mar', MIERCOLES: 'Mié', JUEVES: 'Jue', VIERNES: 'Vie', SABADO: 'Sáb', DOMINGO: 'Dom' }

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
  store.setFiltro('id_disciplina', id)
}

function onSeleccionarEspacio(id) {
  if (id !== null && form.value.id_disciplina !== null) {
    const esp = store.espacios.find(e => e.id_espacio === id)
    if (!esp?.disciplinas_ids?.includes(form.value.id_disciplina)) form.value.id_disciplina = null
  }
  form.value.id_espacio = id
  store.setFiltro('id_espacio', id)
}

function onSeleccionarInstructor(id) {
  if (id !== null && form.value.id_disciplina !== null) {
    const inst = store.instructores.find(i => i.id_instructor === id)
    if (!inst?.disciplinas_ids?.includes(form.value.id_disciplina)) form.value.id_disciplina = null
  }
  form.value.id_instructor = id
  store.setFiltro('id_instructor', id)
}

function resetSelects() {
  form.value.id_disciplina = null
  form.value.id_espacio    = null
  form.value.id_instructor = null
  store.resetFiltros()
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
  if (form.value.requiere_inscripcion) {
    if (!form.value.cupo_maximo || form.value.cupo_maximo < 1) e.cupo_maximo = 'Mínimo 1 para clase cerrada'
    else if (form.value.cupo_maximo > 40) e.cupo_maximo = 'Máximo 40'
  } else if (form.value.cupo_maximo && form.value.cupo_maximo > 40) {
    e.cupo_maximo = 'Máximo 40'
  }
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

// Botones deshabilitados si hay conflicto de preview o colisión global activa
const puedeAgregar = computed(() => formularioCompleto.value && !tieneConflictoPreview.value && !store.hayColisionActiva)

// Los filtros globales se sincronizan con el formulario en onSeleccionarDisciplina/Espacio/Instructor,
// por lo que al haber conflicto las sesiones implicadas ya son visibles.

// Mapa de id_disciplina → tipo de conflicto ('confirmada' | 'borrador') para las sesiones
// infractoras detectadas contra el preview activo del formulario.
const disciplinasEnConflictoPreview = computed(() => {
  const map = new Map()
  if (!tieneConflictoPreview.value) return map
  for (const c of colisionesPreview.value) {
    const id = c.sesionInfractora?.id_disciplina
    if (!id) continue
    const tipo = c.sesionInfractora?.origen === 'confirmada' ? 'confirmada' : 'borrador'
    // Prevalece 'confirmada' sobre 'borrador' si ya existe la clave
    if (!map.has(id) || map.get(id) !== 'confirmada') map.set(id, tipo)
  }
  return map
})

// Mapa de id_disciplina → tipo de conflicto para colisiones entre sesiones existentes
// (no vinculadas al preview). Consulta el Map sesionesEnConflicto del store.
const disciplinasEnConflictoExistente = computed(() => {
  const map = new Map()
  const sesMap = store.sesionesEnConflicto
  if (!sesMap.size) return map

  const listar = (lista, origen) => {
    lista.forEach((s, i) => {
      const tipo = sesMap.get(`${origen}-${i}`)
      if (!tipo) return
      const id = s.id_disciplina
      if (!id) return
      if (!map.has(id) || map.get(id) !== 'confirmada') map.set(id, tipo)
    })
  }
  listar(store.actividadesConfirmadas, 'confirmada')
  listar(store.draft.actividades, 'draft')
  listar(store.borradorLocal, 'borrador')
  return map
})

// Retorna 'confirmada' | 'borrador' | null para el grupo de Borradores.
// Combina conflictos de preview y conflictos entre sesiones existentes.
function grupoBorradoresEnConflicto(idGrupo) {
  const porPreview    = disciplinasEnConflictoPreview.value.get(idGrupo)    ?? null
  const porExistente  = disciplinasEnConflictoExistente.value.get(idGrupo)  ?? null
  if (porPreview === 'confirmada' || porExistente === 'confirmada') return 'confirmada'
  if (porPreview === 'borrador'   || porExistente === 'borrador')   return 'borrador'
  return null
}

// Retorna 'confirmada' | 'borrador' | null para el grupo de Confirmadas.
function grupoConfirmadasEnConflicto(idGrupo) {
  const porPreview    = disciplinasEnConflictoPreview.value.get(idGrupo)    ?? null
  const porExistente  = disciplinasEnConflictoExistente.value.get(idGrupo)  ?? null
  if (porPreview === 'confirmada' || porExistente === 'confirmada') return 'confirmada'
  if (porPreview === 'borrador'   || porExistente === 'borrador')   return 'borrador'
  return null
}

// ─── Conflicto a nivel de sesión individual ───────────────────────────────
// Determina si una sesión ESPECÍFICA de borradorLocal está en conflicto y de qué tipo.
// Combina conflictos vs preview (candidata del form) + conflictos entre existentes.
function sesionBorradorEnConflicto(s) {
  // El índice original en borradorLocal determina su clave en el store
  const idx = s._originalIdx
  // Conflicto entre sesiones existentes (Map del store)
  const vsExistente = store.sesionesEnConflicto.get(`borrador-${idx}`) ?? null
  // Conflicto vs preview activo: verificamos si esta sesión específica choca contra el preview
  let vsPreview = null
  if (tieneConflictoPreview.value) {
    for (const c of colisionesPreview.value) {
      if (c.sesionInfractora?.origen === 'borrador' && c.sesionInfractora?.index === idx) {
        vsPreview = c.sesionInfractora?.origen === 'confirmada' ? 'confirmada' : 'borrador'
        break
      }
    }
  }
  if (vsExistente === 'confirmada' || vsPreview === 'confirmada') return 'confirmada'
  if (vsExistente === 'borrador'   || vsPreview === 'borrador')   return 'borrador'
  return null
}

// Ídem para sesiones de actividadesConfirmadas.
function sesionConfirmadaEnConflicto(s) {
  const idx = s._originalIdx
  const vsExistente = store.sesionesEnConflicto.get(`confirmada-${idx}`) ?? null
  let vsPreview = null
  if (tieneConflictoPreview.value) {
    for (const c of colisionesPreview.value) {
      if (c.sesionInfractora?.origen === 'confirmada' && c.sesionInfractora?.index === idx) {
        vsPreview = 'confirmada'
        break
      }
    }
  }
  if (vsExistente === 'confirmada' || vsPreview === 'confirmada') return 'confirmada'
  if (vsExistente === 'borrador'   || vsPreview === 'borrador')   return 'borrador'
  return null
}

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
    cupo_maximo:          (form.value.cupo_maximo > 0) ? form.value.cupo_maximo : null,
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
  store.resetFiltros()
}

function handleCeldaClick({ dia, horaInicio, horaFin }) {
  store.setPanelVisible(true)
  seccionCrearAbiertaPorCelda = !seccionCrear.value
  seccionCrear.value = true
  form.value.dias = [dia]
  form.value.hora_inicio = horaInicio
  form.value.hora_fin = horaFin

  if (formErrors.value.dias) delete formErrors.value.dias
  if (formErrors.value.hora_inicio) delete formErrors.value.hora_inicio
  if (formErrors.value.hora_fin) delete formErrors.value.hora_fin
}

// ─── Borrador local (agrupado por disciplina) ─────────────────────────────
const borradorOrdenado = computed(() =>
  store.borradorLocal
    .map((s, i) => ({ ...s, _originalIdx: i }))
    .sort((a, b) => {
      const dDia = (DIA_ORDER[a.dia_semana] ?? 7) - (DIA_ORDER[b.dia_semana] ?? 7)
      if (dDia !== 0) return dDia
      return (a.hora_inicio ?? '').localeCompare(b.hora_inicio ?? '')
    })
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

// Estado de grupos delegado al store (sobrevive cambios de pestaña)
function toggleGrupoBorrador(idDisciplina) { store.toggleGrupoBorrador(idDisciplina) }
function grupoEstaExpandido(idDisciplina)  { return store.grupoEstaExpandido(idDisciplina) }

function limpiarSeleccionBorradores() {
  Object.keys(store.disciplinasEncendidasBorradores).forEach(k => delete store.disciplinasEncendidasBorradores[k])
}
function limpiarSeleccionConfirmadas() {
  Object.keys(store.disciplinasEncendidasConfirmadas).forEach(k => delete store.disciplinasEncendidasConfirmadas[k])
}

// Ojito por disciplina — bloqueado si maestro apagado o cualquier filtro de header activo.
// Con filtro activo el calendario ya lo maneja automáticamente; los ojitos individuales
// quedan en modo lectura para no generar inconsistencias visuales.
function toggleVisibilidadBorradorDesdeOjo(idDisciplina) {
  if (!store.mostrarBorradores) return
  if (hayFiltroHeader.value) return
  store.toggleDisciplinaBorradores(idDisciplina)
}
function toggleVisibilidadConfirmadaDesdeOjo(idDisciplina) {
  if (!store.mostrarConfirmadas) return
  if (hayFiltroHeader.value) return
  store.toggleDisciplinaConfirmadas(idDisciplina)
}

// ─── Computed auxiliares de filtro ───────────────────────────────────────────
// Centraliza la detección de filtros activos para usarla en helpers y template.
const hayFiltroHeader = computed(() =>
  store.filtros.id_disciplina !== null ||
  store.filtros.id_instructor !== null ||
  store.filtros.id_espacio    !== null
)

// Devuelve true si la sesión s pasa todos los filtros activos del header.
function sesionPasaFiltros(s) {
  const { id_disciplina, id_instructor, id_espacio } = store.filtros
  if (id_disciplina !== null && s.id_disciplina !== id_disciplina) return false
  if (id_instructor !== null && s.id_instructor !== id_instructor) return false
  if (id_espacio    !== null && s.id_espacio    !== id_espacio)    return false
  return true
}

// Un grupo de disciplina queda activo (azul) si:
//   a) Sin filtro → ojito individual encendido.
//   b) Con filtro → al menos una sesión del grupo pasa todos los filtros activos.
function grupoBorradoresActivo(idGrupo) {
  if (!store.mostrarBorradores) return false
  if (!hayFiltroHeader.value) return store.disciplinaBorradoresVisible(idGrupo)
  const grupo = borradorPorDisciplina.value.find(g => g.id === idGrupo)
  return !!grupo && grupo.sesiones.some(sesionPasaFiltros)
}
function grupoConfirmadasActivo(idGrupo) {
  if (!store.mostrarConfirmadas) return false
  if (!hayFiltroHeader.value) return store.disciplinaConfirmadasVisible(idGrupo)
  const grupo = confirmadasPorDisciplina.value.find(g => g.id === idGrupo)
  return !!grupo && grupo.sesiones.some(sesionPasaFiltros)
}

// Resalta una sesión individual dentro del panel cuando coincide con el filtro activo.
function sesionResaltadaEnPanel(s) {
  if (!hayFiltroHeader.value) return false
  return sesionPasaFiltros(s)
}

function handleClickTarjeta(idx) {
  store.seleccionarSesion(idx)
}

// Abre el modal de detalle (modo lectura) para una sesión confirmada
function verDetalleConfirmada(idx) {
  store.abrirDetalleSesion('confirmada', idx)
}

// ─── Confirmadas: agrupado por disciplina + paginación progresiva ─────────
const confirmadasPorDisciplina = computed(() => {
  const map = new Map()
  for (let i = 0; i < store.actividadesConfirmadas.length; i++) {
    const s = store.actividadesConfirmadas[i]
    const item = { ...s, _originalIdx: i }
    if (!map.has(s.id_disciplina)) {
      map.set(s.id_disciplina, {
        id: s.id_disciplina,
        nombre: s._disciplina_nombre || 'Disciplina',
        sesiones: [],
      })
    }
    map.get(s.id_disciplina).sesiones.push(item)
  }
  // Ordena sesiones por día + hora dentro de cada grupo
  for (const g of map.values()) {
    g.sesiones.sort((a, b) => {
      const dDia = (DIA_ORDER[a.dia_semana] ?? 7) - (DIA_ORDER[b.dia_semana] ?? 7)
      if (dDia !== 0) return dDia
      return (a.hora_inicio ?? '').localeCompare(b.hora_inicio ?? '')
    })
  }
  return [...map.values()].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'))
})

// Paginación progresiva por disciplina: máx 15 inicial, +15 por click.
// Trunca el árbol del DOM para evitar congelamientos con catálogos grandes.
const LIMITE_INICIAL_CONFIRMADAS = 15
const limitesConfirmadas = ref({})
function limiteDisciplina(id) {
  return limitesConfirmadas.value[id] ?? LIMITE_INICIAL_CONFIRMADAS
}
function ampliarLimiteDisciplina(id) {
  limitesConfirmadas.value[id] = limiteDisciplina(id) + LIMITE_INICIAL_CONFIRMADAS
}

function handleGuardarProgreso() {
  showGuardarProgresoModal.value = true
}

// ─── Guardar progreso — modal de confirmación ─────────────────────────────
const showGuardarProgresoModal = ref(false)
const guardarProgresoSuccess   = ref(false)

async function confirmarGuardarProgreso() {
  try {
    const resultado = await store.guardarProgreso()
    if (resultado?.total_actividades !== undefined) {
      plantillasStore.actualizarTotalActividades(
        plantillasStore.plantillaActiva.id_plantilla,
        resultado.total_actividades
      )
    }
    guardarProgresoSuccess.value = true
    setTimeout(() => {
      guardarProgresoSuccess.value   = false
      showGuardarProgresoModal.value = false
    }, 900)
  } catch {
    toastError('Error al guardar el progreso')
  }
}

// ─── Discard draft — input de confirmación ────────────────────────────────
const discardInput = ref('')
const discardInputValido = computed(() => discardInput.value.trim() === 'ELIMINAR')

function abrirDiscardConfirm() {
  discardInput.value = ''
  store.showDiscardConfirm = true
}

async function confirmarDescartar() {
  if (!discardInputValido.value) return
  await store.descartarDraftPendiente()
  discardInput.value = ''
}

// ─── Recovery — grupos por disciplina del draft pendiente ─────────────────
// Resuelve nombres desde los catálogos cargados (igual que los selects del wizard).
// No depende de _*_nombre en el payload, que puede no estar presente.
const recoveryPorDisciplina = computed(() => {
  const actividades = store.draftPendiente?.payload?.actividades ?? []
  const map = new Map()
  for (const s of actividades) {
    const disc  = store.disciplinas.find(d => d.id_disciplina === s.id_disciplina)
    const esp   = store.espacios.find(e => e.id_espacio === s.id_espacio)
    const nombre = disc?.nombre_disciplina ?? `Disciplina ${s.id_disciplina}`
    if (!map.has(s.id_disciplina)) map.set(s.id_disciplina, { id: s.id_disciplina, nombre, sesiones: [] })
    map.get(s.id_disciplina).sesiones.push({
      ...s,
      _disciplina_nombre: nombre,
      _espacio_nombre:    esp?.nombre_espacio ?? s._espacio_nombre ?? '',
    })
  }
  return [...map.values()].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'))
})
const recoveryGruposExpandidos = ref({})
// Inicializa todos los grupos como colapsados (false) cada vez que llegan datos nuevos.
// Evita el estado `undefined` que hacía que el primer click no tuviera efecto visible
// y que los grupos aparecieran expandidos por defecto.
watch(recoveryPorDisciplina, (grupos) => {
  const estado = {}
  for (const g of grupos) estado[g.id] = false
  recoveryGruposExpandidos.value = estado
}, { immediate: true })

function toggleRecoveryGrupo(id) {
  recoveryGruposExpandidos.value[id] = !recoveryGruposExpandidos.value[id]
}
function recoveryGrupoExpandido(id) {
  return recoveryGruposExpandidos.value[id] === true
}

// ─── Continuar draft — animación de éxito breve ───────────────────────────
const showContinuarSuccess = ref(false)
async function handleContinuarDraft() {
  store.continuarDraftPendiente()
  showContinuarSuccess.value = true
  await new Promise(r => setTimeout(r, 1400))
  showContinuarSuccess.value = false
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
const seccionCrear        = ref(false)
const seccionConfirmadas  = ref(false)
const seccionBorrador     = ref(false)

// ─── Refs para cerrar selects mutuamente exclusivos ───────────────────────
// Panel "Nueva sesión"
const selectDiscipForm = ref(null)
const selectEspForm    = ref(null)
const selectInstForm   = ref(null)
// Header filtros
const selectDiscipHdr  = ref(null)
const selectEspHdr     = ref(null)
const selectInstHdr    = ref(null)

function cerrarOtrosForm(abierto) {
  if (abierto !== 'disc') selectDiscipForm.value?.close()
  if (abierto !== 'esp')  selectEspForm.value?.close()
  if (abierto !== 'inst') selectInstForm.value?.close()
}
function cerrarOtrosHdr(abierto) {
  if (abierto !== 'disc') selectDiscipHdr.value?.close()
  if (abierto !== 'esp')  selectEspHdr.value?.close()
  if (abierto !== 'inst') selectInstHdr.value?.close()
}

// ─── Descartar formulario ─────────────────────────────────────────────────
const formTieneDatos = computed(() =>
  !!form.value.id_disciplina ||
  !!form.value.id_espacio    ||
  !!form.value.id_instructor ||
  form.value.dias.length > 0 ||
  !!form.value.hora_inicio   ||
  !!form.value.hora_fin      ||
  !!form.value.cupo_maximo
)

// true si el toggle estaba cerrado cuando el calendario lo abrió automáticamente
let seccionCrearAbiertaPorCelda = false

function descartarCelda() {
  resetForm()
  if (seccionCrearAbiertaPorCelda) {
    seccionCrear.value = false
    seccionCrearAbiertaPorCelda = false
  }
}

// Init delegado a ActividadesView.selectTab para que el modal de recovery
// solo aparezca al cambiar a esta pestaña, no al montar el componente.
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

        <template v-if="plantillasStore.plantillaActiva">
        <div class="w-px h-8 bg-slate-200 shrink-0" />

        <!-- ── Filtros del calendario (Disciplina / Espacio / Instructor) ── -->
        <div class="flex items-center gap-2 flex-1 min-w-0">
          <div class="flex-1 min-w-40 max-w-60">
            <SelectDisciplina
              ref="selectDiscipHdr"
              :model-value="store.filtros.id_disciplina"
              @update:model-value="(v) => store.setFiltro('id_disciplina', v)"
              @open="cerrarOtrosHdr('disc')"
              :opciones="store.disciplinas"
              size="sm"
              :show-label="false"
              placeholder="Filtrar disciplina"
            />
          </div>
          <div class="flex-1 min-w-40 max-w-60">
            <SelectEspacio
              ref="selectEspHdr"
              :model-value="store.filtros.id_espacio"
              @update:model-value="(v) => store.setFiltro('id_espacio', v)"
              @open="cerrarOtrosHdr('esp')"
              :opciones="store.espacios"
              size="sm"
              :show-label="false"
              placeholder="Filtrar espacio"
            />
          </div>
          <div class="flex-1 min-w-40 max-w-60">
            <SelectInstructor
              ref="selectInstHdr"
              :model-value="store.filtros.id_instructor"
              @update:model-value="(v) => store.setFiltro('id_instructor', v)"
              @open="cerrarOtrosHdr('inst')"
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

        <!-- ── Contadores: borradores y confirmadas + ojitos maestros ── -->
        <div class="hidden md:flex items-center gap-2 shrink-0">

          <!-- Borradores -->
          <button
            type="button"
            @click="store.toggleMaestroBorradores()"
            :title="store.mostrarBorradores ? 'Ocultar todos los borradores del calendario' : 'Mostrar todos los borradores'"
            class="group flex items-center gap-2 px-3 py-1.5 rounded-xl border transition-all duration-200"
            :class="store.mostrarBorradores
              ? 'bg-amber-500 border-amber-500 shadow-sm shadow-amber-200'
              : 'bg-white border-slate-200 hover:border-slate-300'"
          >
            <!-- Ojo -->
            <span
              class="flex items-center justify-center transition-colors"
              :class="store.mostrarBorradores ? 'text-white' : 'text-slate-300 group-hover:text-slate-400'"
            >
              <svg v-if="store.mostrarBorradores" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
              </svg>
            </span>
            <!-- Badge + label -->
            <span class="flex items-center gap-1.5">
              <span
                class="text-xs font-black tabular-nums leading-none transition-colors"
                :class="store.mostrarBorradores ? 'text-white' : 'text-slate-400'"
              >{{ store.borradorLocal.length }}</span>
              <span
                class="text-[10px] font-bold uppercase tracking-wide leading-none transition-colors"
                :class="store.mostrarBorradores ? 'text-amber-100' : 'text-slate-300'"
              >{{ store.borradorLocal.length !== 1 ? 'Borradores' : 'Borrador' }}</span>
            </span>
          </button>

          <!-- Confirmadas -->
          <button
            type="button"
            @click="store.toggleMaestroConfirmadas()"
            :title="store.mostrarConfirmadas ? 'Ocultar todas las confirmadas del calendario' : 'Mostrar todas las confirmadas'"
            class="group flex items-center gap-2 px-3 py-1.5 rounded-xl border transition-all duration-200"
            :class="store.mostrarConfirmadas
              ? 'bg-emerald-500 border-emerald-500 shadow-sm shadow-emerald-200'
              : 'bg-white border-slate-200 hover:border-slate-300'"
          >
            <!-- Ojo -->
            <span
              class="flex items-center justify-center transition-colors"
              :class="store.mostrarConfirmadas ? 'text-white' : 'text-slate-300 group-hover:text-slate-400'"
            >
              <svg v-if="store.mostrarConfirmadas" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
              </svg>
            </span>
            <!-- Badge + label -->
            <span class="flex items-center gap-1.5">
              <span
                class="text-xs font-black tabular-nums leading-none transition-colors"
                :class="store.mostrarConfirmadas ? 'text-white' : 'text-slate-400'"
              >{{ store.actividadesConfirmadas.length }}</span>
              <span
                class="text-[10px] font-bold uppercase tracking-wide leading-none transition-colors"
                :class="store.mostrarConfirmadas ? 'text-emerald-100' : 'text-slate-300'"
              >{{ store.actividadesConfirmadas.length !== 1 ? 'Confirmadas' : 'Confirmada' }}</span>
            </span>
          </button>

        </div>

        <!-- ── Contador de sesiones ── -->
        <div v-if="store.tieneActividades" class="hidden sm:flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 shrink-0">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
          <span class="text-xs font-bold text-slate-600">
            {{ store.totalActividades }} sesión{{ store.totalActividades !== 1 ? 'es' : '' }}
          </span>
        </div>
        </template>
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
          class="w-[440px] shrink-0 border-r-2 border-primary-100 bg-white flex flex-col min-h-0 overflow-hidden"
        >
          <div class="flex-1 min-h-0 overflow-y-auto">

            <!-- ═══ Sección: Crear nueva sesión ═══ -->
            <CollapsibleSection
              v-model="seccionCrear"
              title="Nueva sesión"
              hint="Creación rápida"
              :dot-class="form.requiere_inscripcion ? 'bg-violet-500' : 'bg-emerald-500'"
            >
              <template #icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
              </template>
              <template #actions>
                <button
                  v-if="formTieneDatos"
                  type="button"
                  @click="descartarCelda"
                  class="flex items-center gap-1 text-[10px] font-bold text-slate-400 hover:text-red-500 transition-colors"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  Descartar
                </button>
              </template>
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
                  ref="selectDiscipForm"
                  :model-value="form.id_disciplina"
                  @update:model-value="onSeleccionarDisciplina"
                  @open="cerrarOtrosForm('disc')"
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
                  ref="selectEspForm"
                  :model-value="form.id_espacio"
                  @update:model-value="onSeleccionarEspacio"
                  @open="cerrarOtrosForm('esp')"
                  :opciones="espaciosFormulario"
                  :error="formErrors.id_espacio ?? ''"
                />

                <SelectInstructor
                  ref="selectInstForm"
                  :model-value="form.id_instructor"
                  @update:model-value="onSeleccionarInstructor"
                  @open="cerrarOtrosForm('inst')"
                  :opciones="instructoresFormulario"
                  :error="formErrors.id_instructor ?? ''"
                />
              </div>

              <!-- Días -->
              <div class="mt-5">
                <div class="flex items-center justify-between mb-2.5">
                  <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600">Días de la semana</label>
                  <button
                    v-if="form.dias.length > 0"
                    type="button"
                    @click="form.dias = []"
                    class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400 hover:text-red-500 transition-colors"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpiar
                  </button>
                </div>
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
                  <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">
                    Cupo máx.
                    <span v-if="!form.requiere_inscripcion" class="normal-case font-semibold text-slate-400 tracking-normal ml-1">(opcional)</span>
                  </label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                      <IconGuests :class="['w-4 h-4', formErrors.cupo_maximo ? 'text-red-400' : 'text-slate-400']" />
                    </span>
                    <input
                      v-model.number="form.cupo_maximo"
                      type="number" min="1" max="40"
                      :placeholder="form.requiere_inscripcion ? 'Requerido' : 'Sin límite'"
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
                          ? 'bg-violet-600 text-white shadow-sm'
                          : 'text-slate-500 hover:text-slate-700'
                      ]"
                    >Cerrada</button>
                  </div>
                </div>
              </div>

              <!-- Alerta de conflicto LIVE — diagnóstico detallado -->
              <div v-if="tieneConflictoPreview" class="mt-4 rounded-xl overflow-hidden border border-amber-300">
                <!-- Cabecera -->
                <div class="bg-amber-500 px-3 py-2.5 flex items-center gap-2">
                  <svg class="w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
                  </svg>
                  <p class="text-white text-xs font-black uppercase tracking-wide flex-1">
                    {{ colisionesPreviewPorTipo.ambos ? 'Conflicto de espacio e instructor' : colisionesPreviewPorTipo.espacio.length ? 'Espacio ocupado' : 'Instructor duplicado' }}
                  </p>
                  <span class="text-amber-100 text-xs font-bold tabular-nums">
                    {{ colisionesPreview.length }} conflicto{{ colisionesPreview.length !== 1 ? 's' : '' }}
                  </span>
                </div>
                <!-- Filas -->
                <div class="bg-amber-50 divide-y divide-amber-100">
                  <div v-if="colisionesPreviewPorTipo.espacio.length > 0">
                    <div class="px-3 pt-2.5 pb-1.5 flex items-center gap-1.5">
                      <span class="w-4 h-4 rounded bg-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                      </span>
                      <p class="text-[10px] font-black uppercase tracking-widest text-amber-700">Espacio ocupado</p>
                    </div>
                    <div v-for="(c, idx) in colisionesPreviewPorTipo.espacio.slice(0, 2)" :key="'e' + idx" class="px-3 pb-2.5 ml-6">
                      <p class="text-xs font-extrabold text-amber-900 truncate">{{ c.nombre }}</p>
                      <p class="text-xs font-semibold text-amber-700 tabular-nums leading-snug mt-0.5">
                        Choque con <span class="font-black">{{ c.sesionInfractora?.disciplina || '—' }}</span>
                        con <span class="font-black">{{ c.sesionInfractora?.instructor || 'sin instructor' }}</span>
                        el {{ DIAS_LABEL[c.dia] }}
                        de {{ c.sesionInfractora?.hora_inicio }} a {{ c.sesionInfractora?.hora_fin }}
                      </p>
                    </div>
                  </div>
                  <div v-if="colisionesPreviewPorTipo.instructor.length > 0">
                    <div class="px-3 pt-2.5 pb-1.5 flex items-center gap-1.5">
                      <span class="w-4 h-4 rounded bg-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" />
                        </svg>
                      </span>
                      <p class="text-[10px] font-black uppercase tracking-widest text-amber-700">Instructor duplicado</p>
                    </div>
                    <div v-for="(c, idx) in colisionesPreviewPorTipo.instructor.slice(0, 2)" :key="'i' + idx" class="px-3 pb-2.5 ml-6">
                      <p class="text-xs font-extrabold text-amber-900 truncate">{{ c.nombre }}</p>
                      <p class="text-xs font-semibold text-amber-700 tabular-nums leading-snug mt-0.5">
                        Ya asignado a <span class="font-black">{{ c.sesionInfractora?.disciplina || '—' }}</span>
                        en <span class="font-black">{{ c.sesionInfractora?.espacio || 'sin espacio' }}</span>
                        el {{ DIAS_LABEL[c.dia] }}
                        de {{ c.sesionInfractora?.hora_inicio }} a {{ c.sesionInfractora?.hora_fin }}
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
              title="Borradores"
              hint="Sin Confirmar"
              :badge="store.borradorLocal.length || null"
              badge-tone="primary"
            >
              <template #icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
              </template>
              <template #actions>
                <button
                  v-if="Object.keys(store.disciplinasEncendidasBorradores).length > 0"
                  type="button"
                  @click="limpiarSeleccionBorradores"
                  class="flex items-center gap-1 text-[10px] font-bold text-slate-400 hover:text-red-500 transition-colors"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  Limpiar
                </button>
              </template>
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
                  class="rounded-xl border border-dashed overflow-hidden transition-colors"
                  :class="grupoBorradoresEnConflicto(grupo.id) === 'confirmada'
                    ? 'border-amber-500'
                    : grupoBorradoresEnConflicto(grupo.id) === 'borrador'
                      ? 'border-amber-400 border-dashed'
                      : grupoBorradoresActivo(grupo.id) ? 'border-primary-400' : 'border-slate-200'"
                >
                  <!-- Cabecera del grupo -->
                  <div
                    class="flex items-center gap-2 px-2.5 py-2 cursor-pointer select-none transition-colors"
                    :class="grupoBorradoresEnConflicto(grupo.id) === 'confirmada'
                      ? 'bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700'
                      : grupoBorradoresEnConflicto(grupo.id) === 'borrador'
                        ? 'bg-amber-50 hover:bg-amber-100'
                        : grupoBorradoresActivo(grupo.id)
                          ? 'bg-primary-600 hover:bg-primary-700'
                          : 'bg-slate-50 hover:bg-slate-100'"
                    @click="toggleGrupoBorrador(grupo.id)"
                  >
                    <div
                      class="w-6 h-6 rounded-md flex items-center justify-center shrink-0 border"
                      :class="grupoBorradoresEnConflicto(grupo.id) === 'confirmada'
                        ? 'bg-amber-400 border-amber-300 text-white'
                        : grupoBorradoresEnConflicto(grupo.id) === 'borrador'
                          ? 'bg-amber-100 border-amber-300 text-amber-700'
                          : grupoBorradoresActivo(grupo.id)
                            ? 'bg-primary-500 border-primary-400 text-white'
                            : 'bg-white border-slate-200 text-slate-500'"
                    >
                      <DisciplineIcon :name="grupo.nombre" class="w-3.5 h-3.5" />
                    </div>
                    <span
                      class="flex-1 text-sm font-black truncate"
                      :class="grupoBorradoresEnConflicto(grupo.id) === 'confirmada'
                        ? 'text-white'
                        : grupoBorradoresEnConflicto(grupo.id) === 'borrador'
                          ? 'text-amber-800'
                          : grupoBorradoresActivo(grupo.id) ? 'text-white' : 'text-slate-700'"
                    >{{ grupo.nombre }}</span>

                    <!-- Contador -->
                    <span
                      class="text-[10px] font-black px-1.5 py-0.5 rounded-full tabular-nums"
                      :class="grupoBorradoresEnConflicto(grupo.id) === 'confirmada'
                        ? 'bg-amber-400 text-white'
                        : grupoBorradoresEnConflicto(grupo.id) === 'borrador'
                          ? 'bg-amber-200 text-amber-800'
                          : grupoBorradoresActivo(grupo.id) ? 'bg-primary-500 text-white' : 'bg-slate-200 text-slate-600'"
                    >
                      {{ grupo.sesiones.length }}
                    </span>

                    <!-- Ojo: visibilidad de esta disciplina (borradores) en calendario -->
                    <button
                      type="button"
                      @click.stop="toggleVisibilidadBorradorDesdeOjo(grupo.id)"
                      :disabled="!store.mostrarBorradores || hayFiltroHeader"
                      :title="!store.mostrarBorradores ? 'El maestro de borradores está bloqueado' : hayFiltroHeader ? 'Limpia los filtros del header para usar los ojitos individuales' : grupoBorradoresActivo(grupo.id) ? 'Ocultar en el calendario' : 'Mostrar en el calendario'"
                      class="w-6 h-6 rounded-md flex items-center justify-center transition-colors shrink-0"
                      :class="grupoBorradoresEnConflicto(grupo.id) === 'confirmada'
                        ? 'text-white hover:bg-amber-400'
                        : grupoBorradoresEnConflicto(grupo.id) === 'borrador'
                          ? 'text-amber-600 hover:bg-amber-200'
                          : grupoBorradoresActivo(grupo.id)
                            ? 'text-white hover:bg-primary-500'
                            : 'text-slate-300 hover:text-slate-500 hover:bg-slate-200'"
                    >
                      <svg v-if="grupoBorradoresActivo(grupo.id)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                      </svg>
                    </button>

                    <!-- Chevron expand/collapse -->
                    <svg
                      class="w-3.5 h-3.5 shrink-0 transition-transform duration-150"
                      :class="[
                        grupoEstaExpandido(grupo.id) ? 'rotate-90' : '',
                        grupoBorradoresEnConflicto(grupo.id) === 'confirmada'
                          ? 'text-white/80'
                          : grupoBorradoresEnConflicto(grupo.id) === 'borrador'
                            ? 'text-amber-500'
                            : grupoBorradoresActivo(grupo.id) ? 'text-white/70' : 'text-slate-400'
                      ]"
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
                        // Prioridad 1: conflicto activo en el grupo
                        grupoBorradoresEnConflicto(grupo.id)
                          ? sesionBorradorEnConflicto(s) === 'confirmada'
                            ? 'border-amber-500 bg-gradient-to-r from-amber-400 to-amber-600 shadow-sm'
                            : sesionBorradorEnConflicto(s) === 'borrador'
                              ? 'border-amber-300 bg-amber-50'
                              : 'border-slate-100 bg-slate-50/50 opacity-35'
                          // Prioridad 2: filtro de header
                          : hayFiltroHeader && sesionResaltadaEnPanel(s)
                            ? 'border-primary-200 bg-primary-50/60'
                            : hayFiltroHeader
                              ? 'border-slate-100 bg-slate-50/50 opacity-40'
                              // Prioridad 3: estado normal
                              : s.requiere_inscripcion
                                ? 'border-violet-100 bg-violet-50/50'
                                : 'border-emerald-100 bg-emerald-50/50'
                      ]"
                    >
                      <div class="flex-1 min-w-0">
                        <p class="text-xs font-black tabular-nums truncate"
                          :class="sesionBorradorEnConflicto(s) === 'confirmada'
                            ? 'text-white'
                            : sesionBorradorEnConflicto(s) === 'borrador'
                              ? 'text-amber-700'
                              : grupoBorradoresEnConflicto(grupo.id)
                                ? 'text-slate-400'
                                : hayFiltroHeader && sesionResaltadaEnPanel(s)
                                  ? 'text-primary-700'
                                  : s.requiere_inscripcion ? 'text-violet-700' : 'text-emerald-700'">
                          {{ DIAS_LABEL[s.dia_semana] }} · {{ s.hora_inicio }}–{{ s.hora_fin }}
                        </p>
                        <p class="text-[11px] font-semibold truncate"
                          :class="sesionBorradorEnConflicto(s) === 'confirmada'
                            ? 'text-orange-100'
                            : sesionBorradorEnConflicto(s) === 'borrador'
                              ? 'text-amber-500'
                              : 'text-slate-400'">
                          {{ s._espacio_nombre }}
                        </p>
                      </div>
                      <!-- Icono de alerta — izquierda del botón, colores rojo/naranja contrastantes -->
                      <span
                        v-if="sesionBorradorEnConflicto(s)"
                        class="w-5 h-5 rounded-md flex items-center justify-center shrink-0"
                        :class="sesionBorradorEnConflicto(s) === 'confirmada'
                          ? 'bg-red-600 text-white'
                          : 'bg-orange-100 text-orange-600'"
                        title="Sesión en conflicto"
                      >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                      </span>
                      <button
                        type="button"
                        @click.stop="sesionResaltadaEnPanel(s) || !hayFiltroHeader ? store.abrirDetalleSesion('borrador', s._originalIdx) : null"
                        :disabled="hayFiltroHeader && !sesionResaltadaEnPanel(s)"
                        :class="[
                          'w-5 h-5 rounded-md flex items-center justify-center transition-colors shrink-0 border',
                          sesionBorradorEnConflicto(s) === 'confirmada'
                            ? 'bg-white/20 hover:bg-white/40 text-white border-white/30'
                            : sesionBorradorEnConflicto(s) === 'borrador'
                              ? 'bg-white/80 hover:bg-amber-100 text-amber-500 hover:text-amber-700 border-amber-200'
                              : hayFiltroHeader && sesionResaltadaEnPanel(s)
                                ? 'bg-white/80 hover:bg-primary-100 text-primary-500 hover:text-primary-700 border-primary-200'
                                : hayFiltroHeader
                                  ? 'bg-transparent text-slate-200 border-slate-100 cursor-not-allowed'
                                  : s.requiere_inscripcion
                                    ? 'bg-white/80 hover:bg-violet-100 text-slate-400 hover:text-violet-600 border-slate-100'
                                    : 'bg-white/80 hover:bg-emerald-100 text-slate-400 hover:text-emerald-600 border-slate-100'
                        ]"
                        title="Ver detalle"
                      >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <button
                  type="button"
                  @click="handleGuardarProgreso"
                  :disabled="store.isSavingProgress || tieneConflictoPreview || store.hayColisionActiva"
                  :class="[
                    'w-full mt-1 py-2.5 rounded-xl text-xs font-black transition-all duration-150 flex items-center justify-center gap-2',
                    !tieneConflictoPreview && !store.isSavingProgress && !store.hayColisionActiva
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
                  {{ store.isSavingProgress ? 'Guardando...' : store.hayColisionActiva ? 'Resuelve conflictos' : 'Guardar progreso' }}
                </button>
              </div>
            </CollapsibleSection>

            <!-- ═══ Sección: Sesiones confirmadas (BD) ═══ -->
            <CollapsibleSection
              v-model="seccionConfirmadas"
              title="Sesiones confirmadas"
              hint="Guardadas en BD"
              :badge="store.actividadesConfirmadas.length || null"
              badge-tone="emerald"
            >
              <template #icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </template>
              <template #actions>
                <button
                  v-if="Object.keys(store.disciplinasEncendidasConfirmadas).length > 0"
                  type="button"
                  @click="limpiarSeleccionConfirmadas"
                  class="flex items-center gap-1 text-[10px] font-bold text-slate-400 hover:text-red-500 transition-colors"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  Limpiar
                </button>
              </template>
              <div v-if="store.isLoadingConfirmadas" class="flex items-center justify-center py-6">
                <div class="animate-spin rounded-full h-6 w-6 border-2 border-slate-200 border-t-emerald-500" />
              </div>

              <div v-else-if="confirmadasPorDisciplina.length === 0" class="text-center py-6">
                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-2">
                  <svg class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <p class="text-xs font-bold text-slate-400">Sin sesiones confirmadas</p>
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="grupo in confirmadasPorDisciplina"
                  :key="grupo.id"
                  class="rounded-xl border overflow-hidden transition-colors"
                  :class="grupoConfirmadasEnConflicto(grupo.id) === 'confirmada'
                    ? 'border-amber-500'
                    : grupoConfirmadasEnConflicto(grupo.id) === 'borrador'
                      ? 'border-amber-400 border-dashed'
                      : grupoConfirmadasActivo(grupo.id) ? 'border-primary-400' : 'border-slate-200'"
                >
                  <!-- Cabecera del grupo -->
                  <div
                    class="flex items-center gap-2 px-2.5 py-2 cursor-pointer select-none transition-colors"
                    :class="grupoConfirmadasEnConflicto(grupo.id) === 'confirmada'
                      ? 'bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700'
                      : grupoConfirmadasEnConflicto(grupo.id) === 'borrador'
                        ? 'bg-amber-50 hover:bg-amber-100'
                        : grupoConfirmadasActivo(grupo.id)
                          ? 'bg-primary-600 hover:bg-primary-700'
                          : 'bg-slate-50 hover:bg-slate-100'"
                    @click="store.toggleGrupoConfirmadas(grupo.id)"
                  >
                    <div
                      class="w-6 h-6 rounded-md flex items-center justify-center shrink-0 border"
                      :class="grupoConfirmadasEnConflicto(grupo.id) === 'confirmada'
                        ? 'bg-amber-400 border-amber-300 text-white'
                        : grupoConfirmadasEnConflicto(grupo.id) === 'borrador'
                          ? 'bg-amber-100 border-amber-300 text-amber-700'
                          : grupoConfirmadasActivo(grupo.id)
                            ? 'bg-primary-500 border-primary-400 text-white'
                            : 'bg-white border-slate-200 text-slate-500'"
                    >
                      <DisciplineIcon :name="grupo.nombre" class="w-3.5 h-3.5" />
                    </div>
                    <span
                      class="flex-1 text-sm font-black truncate"
                      :class="grupoConfirmadasEnConflicto(grupo.id) === 'confirmada'
                        ? 'text-white'
                        : grupoConfirmadasEnConflicto(grupo.id) === 'borrador'
                          ? 'text-amber-800'
                          : grupoConfirmadasActivo(grupo.id) ? 'text-white' : 'text-slate-700'"
                    >{{ grupo.nombre }}</span>

                    <!-- Contador -->
                    <span
                      class="text-[10px] font-black px-1.5 py-0.5 rounded-full tabular-nums"
                      :class="grupoConfirmadasEnConflicto(grupo.id) === 'confirmada'
                        ? 'bg-amber-400 text-white'
                        : grupoConfirmadasEnConflicto(grupo.id) === 'borrador'
                          ? 'bg-amber-200 text-amber-800'
                          : grupoConfirmadasActivo(grupo.id) ? 'bg-primary-500 text-white' : 'bg-slate-200 text-slate-600'"
                    >
                      {{ grupo.sesiones.length }}
                    </span>

                    <!-- Ojo: visibilidad de esta disciplina (confirmadas) en calendario -->
                    <button
                      type="button"
                      @click.stop="toggleVisibilidadConfirmadaDesdeOjo(grupo.id)"
                      :disabled="!store.mostrarConfirmadas || hayFiltroHeader"
                      :title="!store.mostrarConfirmadas ? 'El maestro de confirmadas está bloqueado' : hayFiltroHeader ? 'Limpia los filtros del header para usar los ojitos individuales' : grupoConfirmadasActivo(grupo.id) ? 'Ocultar en el calendario' : 'Mostrar en el calendario'"
                      class="w-6 h-6 rounded-md flex items-center justify-center transition-colors shrink-0"
                      :class="grupoConfirmadasEnConflicto(grupo.id) === 'confirmada'
                        ? 'text-white hover:bg-amber-400'
                        : grupoConfirmadasEnConflicto(grupo.id) === 'borrador'
                          ? 'text-amber-600 hover:bg-amber-200'
                          : grupoConfirmadasActivo(grupo.id)
                            ? 'text-white hover:bg-primary-500'
                            : 'text-slate-300 hover:text-slate-500 hover:bg-slate-200'"
                    >
                      <svg v-if="grupoConfirmadasActivo(grupo.id)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                      </svg>
                    </button>

                    <!-- Chevron -->
                    <svg
                      class="w-3.5 h-3.5 shrink-0 transition-transform duration-150"
                      :class="[
                        store.grupoConfirmadasExpandido(grupo.id) ? 'rotate-90' : '',
                        grupoConfirmadasEnConflicto(grupo.id) === 'confirmada'
                          ? 'text-white/80'
                          : grupoConfirmadasEnConflicto(grupo.id) === 'borrador'
                            ? 'text-amber-500'
                            : grupoConfirmadasActivo(grupo.id) ? 'text-white/70' : 'text-slate-400'
                      ]"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                  </div>

                  <!-- Sesiones del grupo (truncado progresivo) -->
                  <div v-if="store.grupoConfirmadasExpandido(grupo.id)" class="px-2 pb-2 pt-1 space-y-1.5 bg-white">
                    <div
                      v-for="s in grupo.sesiones.slice(0, limiteDisciplina(grupo.id))"
                      :key="s.id_actividad_plantilla ?? s._originalIdx"
                      :class="[
                        'flex items-center gap-2 p-2 rounded-lg border transition-all duration-150',
                        // Prioridad 1: conflicto activo en el grupo
                        grupoConfirmadasEnConflicto(grupo.id)
                          ? sesionConfirmadaEnConflicto(s) === 'confirmada'
                            ? 'border-amber-500 bg-gradient-to-r from-amber-400 to-amber-600 shadow-sm'
                            : sesionConfirmadaEnConflicto(s) === 'borrador'
                              ? 'border-amber-300 bg-amber-50'
                              : 'border-slate-100 bg-slate-50/50 opacity-35'
                          // Prioridad 2: filtro de header
                          : hayFiltroHeader && sesionResaltadaEnPanel(s)
                            ? 'border-primary-200 bg-primary-50/60'
                            : hayFiltroHeader
                              ? 'border-slate-100 bg-slate-50/50 opacity-40'
                              // Prioridad 3: estado normal
                              : s.requiere_inscripcion
                                ? 'border-violet-100 bg-violet-50/40'
                                : 'border-emerald-100 bg-emerald-50/40'
                      ]"
                    >
                      <div class="flex-1 min-w-0">
                        <p class="text-xs font-black tabular-nums truncate"
                          :class="sesionConfirmadaEnConflicto(s) === 'confirmada'
                            ? 'text-white'
                            : sesionConfirmadaEnConflicto(s) === 'borrador'
                              ? 'text-amber-700'
                              : grupoConfirmadasEnConflicto(grupo.id)
                                ? 'text-slate-400'
                                : hayFiltroHeader && sesionResaltadaEnPanel(s)
                                  ? 'text-primary-700'
                                  : s.requiere_inscripcion ? 'text-violet-700' : 'text-emerald-700'">
                          {{ DIAS_LABEL[s.dia_semana] }} · {{ s.hora_inicio }}–{{ s.hora_fin }}
                        </p>
                        <p class="text-[11px] font-semibold truncate"
                          :class="sesionConfirmadaEnConflicto(s) === 'confirmada'
                            ? 'text-orange-100'
                            : sesionConfirmadaEnConflicto(s) === 'borrador'
                              ? 'text-amber-500'
                              : 'text-slate-400'">
                          {{ s._espacio_nombre }}
                        </p>
                      </div>
                      <!-- Icono de alerta — izquierda del ojito, colores rojo/naranja contrastantes -->
                      <span
                        v-if="sesionConfirmadaEnConflicto(s)"
                        class="w-5 h-5 rounded-md flex items-center justify-center shrink-0"
                        :class="sesionConfirmadaEnConflicto(s) === 'confirmada'
                          ? 'bg-red-600 text-white'
                          : 'bg-orange-100 text-orange-600'"
                        title="Sesión en conflicto"
                      >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                      </span>
                      <!-- Ojito visor: abre el detalle de la sesión en modo lectura -->
                      <button
                        type="button"
                        @click.stop="sesionResaltadaEnPanel(s) || !hayFiltroHeader ? verDetalleConfirmada(s._originalIdx) : null"
                        :disabled="hayFiltroHeader && !sesionResaltadaEnPanel(s)"
                        :class="[
                          'w-5 h-5 rounded-md flex items-center justify-center transition-colors shrink-0 border',
                          sesionConfirmadaEnConflicto(s) === 'confirmada'
                            ? 'bg-white/20 hover:bg-white/40 text-white border-white/30'
                            : sesionConfirmadaEnConflicto(s) === 'borrador'
                              ? 'bg-white/80 hover:bg-amber-100 text-amber-500 hover:text-amber-700 border-amber-200'
                              : hayFiltroHeader && sesionResaltadaEnPanel(s)
                                ? 'bg-white/80 hover:bg-primary-100 text-primary-500 hover:text-primary-700 border-primary-200'
                                : hayFiltroHeader
                                  ? 'bg-transparent text-slate-200 border-slate-100 cursor-not-allowed'
                                  : 'bg-white/80 hover:bg-emerald-100 text-slate-400 hover:text-emerald-600 border-slate-100'
                        ]"
                        title="Ver detalle"
                      >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                      </button>
                    </div>

                    <!-- Botón "Ver más" — paginación progresiva para proteger el DOM -->
                    <button
                      v-if="grupo.sesiones.length > limiteDisciplina(grupo.id)"
                      type="button"
                      @click.stop="ampliarLimiteDisciplina(grupo.id)"
                      class="w-full py-1.5 text-[11px] font-black uppercase tracking-widest text-slate-400 hover:text-emerald-600 hover:bg-emerald-50/60 rounded-lg transition-colors"
                    >
                      Ver más (+{{ grupo.sesiones.length - limiteDisciplina(grupo.id) }})
                    </button>
                  </div>
                </div>
              </div>
            </CollapsibleSection>

          </div>
        </aside>
      </Transition>

      <!-- ════ CALENDARIO ════ -->
      <main class="flex-1 min-w-0 overflow-hidden p-4">
        <CalendarioGrid
          :sesion-resaltada-index="store.sesionSeleccionada"
          :sesion-preview="form"
          @click-slot="handleCeldaClick"
        />
      </main>
    </div>

    <!-- ══════════ MODAL: RECUPERACIÓN DE DRAFT PENDIENTE ══════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div
          v-if="store.showDraftRecovery"
          class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/75 backdrop-blur-sm"
        >
          <!-- Animación de éxito al continuar -->
          <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 scale-75"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0"
          >
            <div v-if="showContinuarSuccess" class="flex flex-col items-center gap-4">
              <div class="w-20 h-20 rounded-full bg-emerald-500 flex items-center justify-center shadow-2xl">
                <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <p class="text-white font-black text-lg">Borrador restaurado</p>
            </div>
          </Transition>

          <div v-if="!showContinuarSuccess" class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header amber -->
            <div class="bg-gradient-to-br from-amber-500 to-orange-600 px-6 py-5">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
                  <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" />
                  </svg>
                </div>
                <div>
                  <p class="text-amber-100 text-[10px] uppercase font-black tracking-widest">Sesión anterior detectada</p>
                  <h2 class="text-white text-xl font-black leading-tight">Borrador pendiente</h2>
                </div>
              </div>
            </div>

            <!-- Body -->
            <div class="p-5">
              <p class="text-sm text-slate-600 font-medium mb-4">
                Existe un borrador sin guardar de una sesión anterior con
                <strong class="text-slate-800">{{ store.draftPendiente?.payload?.actividades?.length ?? 0 }} sesión{{ (store.draftPendiente?.payload?.actividades?.length ?? 0) !== 1 ? 'es' : '' }}</strong>.
                ¿Deseas continuarlo?
              </p>

              <!-- Grupos por disciplina (toggles) -->
              <div class="space-y-2 max-h-52 overflow-y-auto pr-1">
                <div
                  v-for="grupo in recoveryPorDisciplina"
                  :key="grupo.id"
                  class="rounded-xl border border-slate-200 overflow-hidden"
                >
                  <button
                    type="button"
                    @click="toggleRecoveryGrupo(grupo.id)"
                    class="w-full flex items-center gap-2 px-3 py-2.5 bg-slate-50 hover:bg-slate-100 transition-colors text-left"
                  >
                    <DisciplineIcon :name="grupo.nombre" class="w-4 h-4 text-slate-500 shrink-0" />
                    <span class="flex-1 text-sm font-black text-slate-700 truncate">{{ grupo.nombre }}</span>
                    <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full bg-primary-100 text-primary-700 tabular-nums">{{ grupo.sesiones.length }}</span>
                    <svg
                      class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-150"
                      :class="recoveryGrupoExpandido(grupo.id) ? 'rotate-90' : ''"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                  </button>
                  <div v-if="recoveryGrupoExpandido(grupo.id)" class="px-3 pb-2 pt-1 space-y-1 bg-white">
                    <div
                      v-for="(s, i) in grupo.sesiones"
                      :key="i"
                      :class="[
                        'flex items-center gap-2 p-2 rounded-lg border text-xs font-semibold',
                        s.requiere_inscripcion
                          ? 'border-violet-100 bg-violet-50/50 text-violet-700'
                          : 'border-emerald-100 bg-emerald-50/50 text-emerald-700'
                      ]"
                    >
                      <span class="font-black tabular-nums">{{ DIAS_RECOVERY_LABEL[s.dia_semana] ?? s.dia_semana }}</span>
                      <span class="text-slate-400">·</span>
                      <span class="tabular-nums">{{ s.hora_inicio }}–{{ s.hora_fin }}</span>
                      <span class="ml-auto text-slate-400 truncate">{{ s._espacio_nombre ?? '' }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-t border-slate-100 bg-slate-50">
              <button
                type="button"
                @click="abrirDiscardConfirm"
                class="px-4 py-2.5 rounded-xl border border-red-200 bg-white text-sm font-bold text-red-600 hover:bg-red-50 transition-colors"
              >Descartar</button>
              <button
                type="button"
                @click="handleContinuarDraft"
                class="px-6 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-black hover:bg-emerald-700 transition-colors shadow-sm flex items-center gap-2"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Continuar borrador
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════ MODAL: DESCARTAR DRAFT (peligro) ══════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div
          v-if="store.showDiscardConfirm"
          class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
          @click.self="store.showDiscardConfirm = false"
        >
          <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl p-6">
            <!-- Icono de peligro -->
            <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
              </svg>
            </div>
            <p class="text-[10px] uppercase font-black tracking-widest text-red-500 mb-1">Acción irreversible</p>
            <h2 class="text-xl font-black text-slate-900 mb-2">Eliminar borrador</h2>
            <p class="text-sm text-slate-500 font-medium mb-5 leading-relaxed">
              Se eliminará permanentemente el borrador y <strong class="text-slate-700">todas sus sesiones</strong>.
              Esta acción no se puede deshacer.
            </p>

            <!-- Input de confirmación -->
            <div class="mb-5">
              <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-2">
                Escribe <span class="text-red-600">ELIMINAR</span> para confirmar
              </label>
              <input
                v-model="discardInput"
                type="text"
                placeholder="ELIMINAR"
                autofocus
                :class="[
                  'w-full px-4 py-3 rounded-xl border text-sm font-bold transition-all duration-150 tracking-widest',
                  discardInputValido
                    ? 'border-red-400 bg-red-50 text-red-700'
                    : 'border-slate-200 bg-white text-slate-800'
                ]"
                @keyup.enter="confirmarDescartar"
              />
            </div>

            <div class="flex gap-3 justify-end">
              <button
                @click="store.showDiscardConfirm = false; discardInput = ''"
                class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors"
              >Cancelar</button>
              <button
                @click="confirmarDescartar"
                :disabled="!discardInputValido || store.isDiscardingDraft"
                :class="[
                  'px-6 py-2.5 rounded-xl text-sm font-black transition-colors shadow-sm flex items-center gap-2',
                  discardInputValido && !store.isDiscardingDraft
                    ? 'bg-red-600 text-white hover:bg-red-700'
                    : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                ]"
              >
                <svg v-if="store.isDiscardingDraft" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                {{ store.isDiscardingDraft ? 'Eliminando...' : 'Eliminar borrador' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════ MODAL: CONFIRMAR GUARDAR PROGRESO ══════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div
          v-if="showGuardarProgresoModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showGuardarProgresoModal = false"
        >
          <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl p-6 relative overflow-hidden">

            <!-- Overlay de éxito (idéntico a PlantillasGestion) -->
            <Transition
              enter-active-class="transition-opacity duration-150 ease-out"
              enter-from-class="opacity-0" enter-to-class="opacity-100"
              leave-active-class="transition-opacity duration-150 ease-in"
              leave-from-class="opacity-100" leave-to-class="opacity-0"
            >
              <div v-if="guardarProgresoSuccess" class="absolute inset-0 z-10 bg-white/90 backdrop-blur-[2px] rounded-3xl flex flex-col items-center justify-center gap-4">
                <div class="w-16 h-16 rounded-full bg-emerald-600 flex items-center justify-center shadow-lg animate-scale-in">
                  <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                </div>
                <p class="text-base font-black text-slate-800">Sesiones guardadas</p>
              </div>
            </Transition>

            <div class="w-12 h-12 rounded-2xl bg-slate-800 text-white flex items-center justify-center mb-4">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h2 class="text-xl font-black text-slate-900 mb-2">Guardar progreso</h2>
            <p class="text-sm font-medium text-slate-500 mb-4 leading-relaxed">
              Se consolidarán <strong class="text-slate-700">{{ store.borradorLocal.length }} sesión{{ store.borradorLocal.length !== 1 ? 'es' : '' }}</strong>
              del borrador actual al draft guardado. Podrás modificarlas o eliminarlas libremente en cualquier momento, siempre que no generen conflictos de horario.
            </p>
            <div class="flex items-start gap-2 px-3 py-2.5 rounded-xl bg-sky-50 border border-sky-100 mb-5">
              <svg class="w-4 h-4 text-sky-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
              </svg>
              <p class="text-xs font-medium text-sky-700 leading-relaxed">
                Nada será visible para los socios ni publicado hasta que confirmes la publicación final de la programación.
              </p>
            </div>
            <div class="flex gap-3 justify-end">
              <button
                @click="showGuardarProgresoModal = false"
                :disabled="store.isSavingProgress"
                class="px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
              >Cancelar</button>
              <button
                @click="confirmarGuardarProgreso"
                :disabled="store.isSavingProgress"
                class="px-6 py-2.5 rounded-xl bg-slate-800 text-white text-sm font-black hover:bg-slate-900 transition-colors shadow-sm flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <svg v-if="store.isSavingProgress" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                {{ store.isSavingProgress ? 'Guardando...' : 'Confirmar' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

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

<style scoped>
.animate-scale-in {
  animation: scaleIn 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}
@keyframes scaleIn {
  from { transform: scale(0); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}
</style>
