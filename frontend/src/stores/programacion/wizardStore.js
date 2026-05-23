import { defineStore } from 'pinia'
import { ref, reactive, computed } from 'vue'
import api from '@/services/api'

function toMinutes(t) {
  const [h, m] = t.split(':').map(Number)
  return h * 60 + m
}

function overlaps(aStart, aEnd, bStart, bEnd) {
  return aStart < bEnd && bStart < aEnd
}

// Debounce module-level para coalescerse en clicks rápidos
let _autoguardaTimer = null
function scheduleAutosave(fn) {
  clearTimeout(_autoguardaTimer)
  _autoguardaTimer = setTimeout(fn, 400)
}

// Elimina solo campos internos de layout/UI — preserva _*_nombre para el display del modal de recovery
function cleanActividad(a) {
  const {
    // eslint-disable-next-line no-unused-vars
    _originalIdx, _origen, _srcIdx,
    _startSlot, _endSlot, _lane, _totalLanes,
    ...rest
  } = a
  return rest
}

export const useWizardStore = defineStore('wizardProgramacion', () => {
  // ─── Catálogos ────────────────────────────────────────────────────────────
  const disciplinas   = ref([])
  const espacios      = ref([])
  const instructores  = ref([])
  const isLoadingDeps = ref(false)
  const errorDeps     = ref(null)

  // ─── Draft persistido en BD ───────────────────────────────────────────────
  // Fuente de verdad: todo lo que está aquí está en la BD.
  const draft = ref({
    nombre_plantilla: '',
    fecha_inicio: '',
    fecha_fin: '',
    actividades: [],   // sesiones ya guardadas (origen 'draft' en el calendario)
  })

  // ─── Plantilla activa que se está editando ────────────────────────────────
  // La vista la inyecta vía setPlantillaActiva(id) para que todos los PUT
  // incluyan el id_plantilla y el backend pueda asociar el draft correctamente.
  const idPlantillaActiva = ref(null)
  function setPlantillaActiva(id) { idPlantillaActiva.value = id ?? null }

  // ─── Borrador local ───────────────────────────────────────────────────────
  // Sesiones recién agregadas, visibles en el panel "Borradores locales".
  // Se persisten AL MISMO TIEMPO que se agregan mediante PUT silencioso.
  // "Guardar progreso" las mueve formalmente a draft.actividades en el estado.
  const borradorLocal      = ref([])
  const sesionSeleccionada = ref(null)

  // ─── Actividades confirmadas en actividades_plantilla ────────────────────
  const actividadesConfirmadas = ref([])
  const isLoadingConfirmadas   = ref(false)

  // ─── Draft recovery modal ─────────────────────────────────────────────────
  const draftPendiente     = ref(null)
  const showDraftRecovery  = ref(false)
  const showDiscardConfirm = ref(false)
  const isDiscardingDraft  = ref(false)

  // ─── UI state ─────────────────────────────────────────────────────────────
  const isLoadingDraft    = ref(false)
  const isCreatingDraft   = isLoadingDraft   // alias compat con la vista
  const isSavingDraft     = ref(false)
  const isSavingProgress  = ref(false)
  const isPublishing      = ref(false)
  const publishSuccess    = ref(false)
  const conflictosPublicacion = ref([])
  const colisionesLocales     = ref([])
  const errorInit             = ref(null)

  // ─── Panel + filtros + modal detalle ──────────────────────────────────────
  const panelVisible    = ref(false)
  const filtros         = ref({ id_espacio: null, id_instructor: null, id_disciplina: null })
  const sesionEnDetalle = ref({ origen: null, index: null })

  // ─── Estado de grupos expandidos del panel borrador ───────────────────────
  // Vive en el store para sobrevivir al cambio de pestaña.
  // Ausencia de clave = primer acceso (colapsado por defecto); true = expandido.
  // Se usa reactive (no ref) para que la mutación de propiedades dispare rerender.
  const gruposBorradorExpandidos    = reactive({})
  const gruposConfirmadasExpandidos = reactive({})

  // ─── Visibilidad granular para el calendario (ojitos) ─────────────────────
  // Arquitectura de intersección limpia — el maestro NUNCA muta los individuales:
  //   visible_en_calendario = maestro.encendido && disciplina.encendida_individualmente
  //
  // mostrarConfirmadas / mostrarBorradores → flag de capa (solo apaga/enciende la capa
  //   completa sin tocar la selección individual del usuario).
  // disciplinasEncendidasConfirmadas / disciplinasEncendidasBorradores → set de
  //   disciplinas que el usuario activó individualmente. Solo una activa a la vez.
  //   El maestro nunca los modifica; se conservan al apagar/encender el maestro.
  const mostrarConfirmadas               = ref(true)
  const mostrarBorradores                = ref(true)
  const disciplinasEncendidasConfirmadas = reactive({})
  const disciplinasEncendidasBorradores  = reactive({})

  // Toggle maestro: solo invierte el flag de capa, preserva selecciones individuales.
  function toggleMaestroConfirmadas() { mostrarConfirmadas.value = !mostrarConfirmadas.value }
  function toggleMaestroBorradores()  { mostrarBorradores.value  = !mostrarBorradores.value }

  // Visibilidad individual: intersección maestro + selección propia.
  function disciplinaConfirmadasVisible(id) {
    return mostrarConfirmadas.value && !!disciplinasEncendidasConfirmadas[id]
  }
  function disciplinaBorradoresVisible(id) {
    return mostrarBorradores.value && !!disciplinasEncendidasBorradores[id]
  }

  // Toggle individual: exclusivo (solo una activa por origen). El maestro no interviene.
  function toggleDisciplinaConfirmadas(id) {
    if (disciplinasEncendidasConfirmadas[id]) {
      delete disciplinasEncendidasConfirmadas[id]
    } else {
      Object.keys(disciplinasEncendidasConfirmadas).forEach(k => delete disciplinasEncendidasConfirmadas[k])
      disciplinasEncendidasConfirmadas[id] = true
    }
  }
  function toggleDisciplinaBorradores(id) {
    if (disciplinasEncendidasBorradores[id]) {
      delete disciplinasEncendidasBorradores[id]
    } else {
      Object.keys(disciplinasEncendidasBorradores).forEach(k => delete disciplinasEncendidasBorradores[k])
      disciplinasEncendidasBorradores[id] = true
    }
  }

  function toggleGrupoConfirmadas(idDisciplina) {
    gruposConfirmadasExpandidos[idDisciplina] = !gruposConfirmadasExpandidos[idDisciplina]
  }
  function grupoConfirmadasExpandido(idDisciplina) {
    return gruposConfirmadasExpandidos[idDisciplina] === true
  }

  function togglePanel()           { panelVisible.value = !panelVisible.value }
  function setPanelVisible(v)      { panelVisible.value = !!v }
  function setFiltro(campo, valor) { if (campo in filtros.value) filtros.value[campo] = valor }
  function resetFiltros()          { filtros.value = { id_espacio: null, id_instructor: null, id_disciplina: null } }
  function abrirDetalleSesion(o, i) { sesionEnDetalle.value = { origen: o, index: i } }
  function cerrarDetalleSesion()     { sesionEnDetalle.value = { origen: null, index: null } }

  function toggleGrupoBorrador(idDisciplina) {
    gruposBorradorExpandidos[idDisciplina] = !gruposBorradorExpandidos[idDisciplina]
  }
  function grupoEstaExpandido(idDisciplina) {
    return gruposBorradorExpandidos[idDisciplina] === true
  }

  // ─── Getters ──────────────────────────────────────────────────────────────
  const tieneActividades   = computed(() => draft.value.actividades.length > 0)
  const totalActividades   = computed(() => draft.value.actividades.length)
  const tieneBorradorLocal = computed(() => borradorLocal.value.length > 0)

  const espaciosEnDraft = computed(() => {
    const ids = new Set([
      ...draft.value.actividades.map(a => a.id_espacio),
      ...borradorLocal.value.map(a => a.id_espacio),
    ])
    return espacios.value.filter(e => ids.has(e.id_espacio))
  })

  const hayColisionActiva = computed(() => {
    const todos = [
      ...actividadesConfirmadas.value.map((s, i) => ({ s, origen: 'confirmada', i })),
      ...draft.value.actividades.map((s, i)        => ({ s, origen: 'draft',     i })),
      ...borradorLocal.value.map((s, i)             => ({ s, origen: 'borrador',  i })),
    ]
    for (const { s, origen, i } of todos) {
      if (detectarColisionEnEdicion(s, origen, i).length > 0) return true
    }
    return false
  })

  // ─── fetchDependencias ────────────────────────────────────────────────────
  async function fetchDependencias() {
    if (disciplinas.value.length > 0) return
    isLoadingDeps.value = true
    errorDeps.value = null
    try {
      const { data } = await api.get('/programacion/dependencias')
      disciplinas.value  = data.disciplinas
      espacios.value     = data.espacios
      instructores.value = data.instructores
    } catch (err) {
      errorDeps.value = 'No se pudieron cargar los catálogos. Intenta de nuevo.'
      throw err
    } finally {
      isLoadingDeps.value = false
    }
  }

  // ─── fetchActividadesConfirmadas ──────────────────────────────────────────
  async function fetchActividadesConfirmadas(idPlantilla) {
    if (!idPlantilla) return
    isLoadingConfirmadas.value = true
    try {
      const { data } = await api.get(`/programacion/plantillas/${idPlantilla}`)
      actividadesConfirmadas.value = (data.data?.actividades ?? []).map(a => ({
        id_actividad_plantilla: a.id_actividad_plantilla,
        id_disciplina:          a.disciplina?.id  ?? null,
        id_espacio:             a.espacio?.id     ?? null,
        id_instructor:          a.instructor?.id  ?? null,
        dia_semana:             a.dia_semana,
        hora_inicio:            a.hora_inicio,
        hora_fin:               a.hora_fin,
        cupo_maximo:            a.cupo_maximo,
        requiere_inscripcion:   a.requiere_inscripcion,
        _disciplina_nombre:     a.disciplina?.nombre ?? '',
        _espacio_nombre:        a.espacio?.nombre    ?? '',
        _instructor_nombre:     a.instructor?.nombre ?? '',
      }))
    } catch {
      actividadesConfirmadas.value = []
    } finally {
      isLoadingConfirmadas.value = false
    }
  }

  // ─── resetWizard ─────────────────────────────────────────────────────────
  // Limpia todo el estado UI del wizard. Llamado al cambiar de plantilla activa
  // para garantizar que no queden datos de la plantilla anterior en pantalla.
  function resetWizard() {
    draft.value              = { nombre_plantilla: '', fecha_inicio: '', fecha_fin: '', actividades: [] }
    borradorLocal.value      = []
    actividadesConfirmadas.value = []
    sesionSeleccionada.value = null
    draftPendiente.value     = null
    showDraftRecovery.value  = false
    showDiscardConfirm.value = false
    colisionesLocales.value  = []
    conflictosPublicacion.value = []
    publishSuccess.value     = false
    errorInit.value          = null
    Object.keys(gruposBorradorExpandidos).forEach(k => delete gruposBorradorExpandidos[k])
    Object.keys(gruposConfirmadasExpandidos).forEach(k => delete gruposConfirmadasExpandidos[k])
    Object.keys(disciplinasEncendidasConfirmadas).forEach(k => delete disciplinasEncendidasConfirmadas[k])
    Object.keys(disciplinasEncendidasBorradores).forEach(k => delete disciplinasEncendidasBorradores[k])
    mostrarConfirmadas.value = true
    mostrarBorradores.value  = true
    clearTimeout(_autoguardaTimer)
  }

  // ─── verificarDraftActivo ─────────────────────────────────────────────────
  async function verificarDraftActivo() {
    isLoadingDraft.value = true
    errorInit.value = null
    try {
      const params = idPlantillaActiva.value !== null
        ? `?id_plantilla=${idPlantillaActiva.value}`
        : ''
      const { data } = await api.get(`/programacion/drafts/activo${params}`)
      const payload     = data.data?.payload
      // El backend puede devolver [] (array PHP vacío) o {} sin actividades
      const actividades = Array.isArray(payload)
        ? []
        : (payload?.actividades ?? [])

      if (actividades.length === 0) {
        _adoptarDraft(data.data)
      } else {
        draftPendiente.value    = data.data
        showDraftRecovery.value = true
      }
    } catch (err) {
      errorInit.value = 'Error al verificar el borrador. Intenta de nuevo.'
      throw err
    } finally {
      isLoadingDraft.value = false
    }
  }

  // ─── _adoptarDraft ────────────────────────────────────────────────────────
  // Cuando NO hay sesiones pendientes (draft vacío al inicio), solo inicializa
  // el estado UI. Las sesiones recuperadas van siempre a borradorLocal.
  function _adoptarDraft(rawDraft) {
    draft.value = {
      nombre_plantilla: '',
      fecha_inicio:     '',
      fecha_fin:        '',
      actividades:      [],
    }
  }

  // ─── continuarDraftPendiente ──────────────────────────────────────────────
  // Mueve las actividades del draft pendiente al borradorLocal para que
  // aparezcan en el panel "Borradores locales — Aún no guardadas" y sean
  // completamente editables/eliminables igual que sesiones recién creadas.
  function continuarDraftPendiente() {
    if (!draftPendiente.value) return
    const payload    = draftPendiente.value?.payload
    const payloadObj = Array.isArray(payload) ? {} : (payload ?? {})
    const actividades = Array.isArray(payloadObj.actividades) ? payloadObj.actividades : []

    // Enriquecer con nombres resueltos desde los catálogos ya cargados
    const sesionesEnriquecidas = actividades.map(a => {
      const disc = disciplinas.value.find(d => d.id_disciplina === a.id_disciplina)
      const esp  = espacios.value.find(e => e.id_espacio === a.id_espacio)
      const inst = instructores.value.find(i => i.id_instructor === a.id_instructor)
      return {
        ...a,
        _disciplina_nombre: disc?.nombre_disciplina ?? a._disciplina_nombre ?? '',
        _espacio_nombre:    esp?.nombre_espacio     ?? a._espacio_nombre    ?? '',
        _instructor_nombre: inst?.nombre_completo   ?? a._instructor_nombre ?? '',
      }
    })

    draft.value       = { nombre_plantilla: '', fecha_inicio: '', fecha_fin: '', actividades: [] }
    borradorLocal.value = sesionesEnriquecidas
    showDraftRecovery.value = false
    draftPendiente.value    = null
  }

  // ─── descartarDraftPendiente ──────────────────────────────────────────────
  async function descartarDraftPendiente() {
    isDiscardingDraft.value = true
    try {
      const body = { payload: { actividades: [] } }
      if (idPlantillaActiva.value !== null) body.id_plantilla = idPlantillaActiva.value
      await api.put('/programacion/drafts/activo', body)
      draftPendiente.value     = null
      showDiscardConfirm.value = false
      showDraftRecovery.value  = false
      draft.value              = { nombre_plantilla: '', fecha_inicio: '', fecha_fin: '', actividades: [] }
      borradorLocal.value      = []
    } catch {
      // mantener modal abierto si falla
    } finally {
      isDiscardingDraft.value = false
    }
  }

  // ─── _buildPayload ────────────────────────────────────────────────────────
  // Solo persiste actividades — los metadatos de plantilla no forman parte del draft.
  function _buildPayload() {
    return {
      actividades: [
        ...draft.value.actividades.map(cleanActividad),
        ...borradorLocal.value.map(cleanActividad),
      ],
    }
  }

  // ─── _putActivo (escritura al backend, sin bloquear UI) ───────────────────
  async function _putActivo(payload) {
    try {
      const body = { payload }
      if (idPlantillaActiva.value !== null) body.id_plantilla = idPlantillaActiva.value
      await api.put('/programacion/drafts/activo', body)
    } catch {
      // background — fallas ignoradas salvo en guardarProgreso/publicar
    }
  }

  // ─── autoguardarSilencioso ────────────────────────────────────────────────
  // Debounced. Persiste draft.actividades + borradorLocal en cada mutación.
  function autoguardarSilencioso() {
    scheduleAutosave(() => _putActivo(_buildPayload()))
  }

  // ─── guardarProgreso ──────────────────────────────────────────────────────
  // 1. Sincroniza el draft completo (draft + borrador) al backend via PUT
  // 2. Llama a /consolidar: INSERT masivo en actividades_plantilla + vacía payload
  // 3. Limpia el estado UI: borradorLocal, draft.actividades y actividadesConfirmadas
  //    se recargan desde la BD para reflejar el estado real post-INSERT
  // Devuelve { actividades_creadas, total_actividades } para que la vista
  // actualice el contador de la plantilla activa sin un refetch completo.
  async function guardarProgreso() {
    if (borradorLocal.value.length === 0) return
    clearTimeout(_autoguardaTimer)
    isSavingProgress.value = true
    try {
      // Paso 1 — sincronizar estado completo al draft antes del INSERT
      const body = { payload: _buildPayload() }
      if (idPlantillaActiva.value !== null) body.id_plantilla = idPlantillaActiva.value
      await api.put('/programacion/drafts/activo', body)

      // Paso 2 — consolidar: INSERT masivo + vaciar payload en BD
      const { data } = await api.post('/programacion/drafts/consolidar', {
        id_plantilla: idPlantillaActiva.value,
      })

      // Paso 3 — limpiar estado UI
      draft.value.actividades  = []
      borradorLocal.value      = []
      sesionSeleccionada.value = null

      // Recargar actividades confirmadas para que el calendario las muestre
      // como 'confirmada' (ya están en actividades_plantilla)
      await fetchActividadesConfirmadas(idPlantillaActiva.value)

      return data.data  // { actividades_creadas, total_actividades }
    } finally {
      isSavingProgress.value = false
    }
  }

  // ─── Colisiones ───────────────────────────────────────────────────────────
  function validarColisionLocal(nuevasSesiones) {
    const conflictos = []
    const existentes = [
      ...actividadesConfirmadas.value,
      ...draft.value.actividades,
      ...borradorLocal.value,
    ]
    for (const nuevo of nuevasSesiones) {
      const nS = toMinutes(nuevo.hora_inicio)
      const nE = toMinutes(nuevo.hora_fin)
      for (const ex of existentes) {
        if (ex.dia_semana !== nuevo.dia_semana) continue
        if (!overlaps(nS, nE, toMinutes(ex.hora_inicio), toMinutes(ex.hora_fin))) continue
        if (ex.id_espacio === nuevo.id_espacio) {
          conflictos.push({ tipo: 'espacio', dia: nuevo.dia_semana, nombre: nuevo._espacio_nombre,
            horario_nuevo: `${nuevo.hora_inicio}–${nuevo.hora_fin}`,
            horario_existente: `${ex.hora_inicio}–${ex.hora_fin}` })
        }
        if (ex.id_instructor === nuevo.id_instructor) {
          conflictos.push({ tipo: 'instructor', dia: nuevo.dia_semana, nombre: nuevo._instructor_nombre,
            horario_nuevo: `${nuevo.hora_inicio}–${nuevo.hora_fin}`,
            horario_existente: `${ex.hora_inicio}–${ex.hora_fin}` })
        }
      }
    }
    return conflictos
  }

  function detectarColisionEnEdicion(candidata, origen = null, indexExcluido = null) {
    if (!candidata?.hora_inicio || !candidata?.hora_fin || !candidata?.dia_semana) return []
    const cS = toMinutes(candidata.hora_inicio)
    const cE = toMinutes(candidata.hora_fin)
    if (cE <= cS) return []

    const conflictos = []
    const lista = [
      ...actividadesConfirmadas.value.map((s, i) => ({ s, origen: 'confirmada', i })),
      ...draft.value.actividades.map((s, i)        => ({ s, origen: 'draft',     i })),
      ...borradorLocal.value.map((s, i)             => ({ s, origen: 'borrador',  i })),
    ]
    for (const { s, origen: o, i } of lista) {
      if (o === origen && i === indexExcluido) continue
      if (s.dia_semana !== candidata.dia_semana) continue
      if (!overlaps(cS, cE, toMinutes(s.hora_inicio), toMinutes(s.hora_fin))) continue
      // Datos completos de la sesión infractora para el diagnóstico preciso
      const sesionInfractora = {
        origen: o, index: i,
        id_disciplina: s.id_disciplina ?? null,
        disciplina:  s._disciplina_nombre ?? '',
        espacio:     s._espacio_nombre    ?? '',
        instructor:  s._instructor_nombre ?? '',
        dia:         s.dia_semana,
        hora_inicio: s.hora_inicio,
        hora_fin:    s.hora_fin,
      }
      if (s.id_espacio === candidata.id_espacio) {
        conflictos.push({
          tipo: 'espacio',
          dia: candidata.dia_semana,
          nombre: candidata._espacio_nombre ?? s._espacio_nombre ?? '',
          horario_nuevo: `${candidata.hora_inicio}–${candidata.hora_fin}`,
          horario_existente: `${s.hora_inicio}–${s.hora_fin}`,
          sesionInfractora,
        })
      }
      if (s.id_instructor === candidata.id_instructor) {
        conflictos.push({
          tipo: 'instructor',
          dia: candidata.dia_semana,
          nombre: candidata._instructor_nombre ?? s._instructor_nombre ?? '',
          horario_nuevo: `${candidata.hora_inicio}–${candidata.hora_fin}`,
          horario_existente: `${s.hora_inicio}–${s.hora_fin}`,
          sesionInfractora,
        })
      }
    }
    return conflictos
  }

  // Mapa de claves "origen-index" → tipo de sesión causante del conflicto.
  // Valor: 'confirmada' si choca contra una sesión de actividades_plantilla (sólido),
  //        'borrador'   si choca contra un draft/borradorLocal (punteado).
  // Cuando hay colisión mixta (causa confirmada Y borrador), prevalece 'confirmada' (más severo).
  // Usado por CalendarioGrid y el panel para aplicar la paleta ámbar diferenciada.
  const sesionesEnConflicto = computed(() => {
    const map = new Map()
    const todos = [
      ...actividadesConfirmadas.value.map((s, i) => ({ s, origen: 'confirmada', i })),
      ...draft.value.actividades.map((s, i)        => ({ s, origen: 'draft',     i })),
      ...borradorLocal.value.map((s, i)             => ({ s, origen: 'borrador',  i })),
    ]
    for (const { s, origen, i } of todos) {
      const colisiones = detectarColisionEnEdicion(s, origen, i)
      if (colisiones.length === 0) continue
      const key = `${origen}-${i}`
      // Determina el peor tipo de causa: confirmada > borrador/draft
      const tieneConfirmadaCausante = colisiones.some(c => c.sesionInfractora?.origen === 'confirmada')
      const tipoCausa = tieneConfirmadaCausante ? 'confirmada' : 'borrador'
      // Si ya existe la clave con 'confirmada', no degradar a 'borrador'
      if (!map.has(key) || map.get(key) !== 'confirmada') {
        map.set(key, tipoCausa)
      }
    }
    return map
  })

  // ─── sesionesVisibles ─────────────────────────────────────────────────────
  // Estado por defecto: calendario vacío. Las sesiones solo aparecen cuando el
  // usuario activa explícitamente algún filtro:
  //   a) Selecciona disciplina/espacio/instructor en el header → muestra todo lo
  //      que pase ese filtro, respetando además los ojitos maestros e individuales.
  //   b) Enciende un ojito individual desde el panel (confirmadas o borradores)
  //      → muestra solo esa disciplina, sin necesidad de usar el header.
  //   c) Activa un ojito maestro → equivale a encender todas las disciplinas
  //      de ese origen de golpe (solo si ningún filtro de header está activo).
  // Cuando hay filtro de header activo, los ojitos actúan sustractivamente sobre
  // el resultado ya filtrado.
  const sesionesVisibles = computed(() => {
    const { id_espacio, id_instructor, id_disciplina } = filtros.value
    const hayFiltroHeader = id_espacio !== null || id_instructor !== null || id_disciplina !== null

    const pasaFiltrosHeader = (s) => {
      if (id_espacio    !== null && s.id_espacio    !== id_espacio)    return false
      if (id_instructor !== null && s.id_instructor !== id_instructor) return false
      if (id_disciplina !== null && s.id_disciplina !== id_disciplina) return false
      return true
    }

    // Intersección limpia: maestro encendido AND (filtro header O disciplina individual encendida).
    // El maestro nunca muta los sets individuales — solo actúa como capa de bloqueo global.
    const visConfirmadas = (s) => {
      if (!mostrarConfirmadas.value) return false
      if (hayFiltroHeader) return true   // el filtro del header ya restringe en pasaFiltrosHeader
      return !!disciplinasEncendidasConfirmadas[s.id_disciplina]
    }

    const visBorradores = (s) => {
      if (!mostrarBorradores.value) return false
      if (hayFiltroHeader) return true
      return !!disciplinasEncendidasBorradores[s.id_disciplina]
    }

    return [
      ...actividadesConfirmadas.value
        .map((s, i) => ({ ...s, _origen: 'confirmada', _srcIdx: i }))
        .filter(s => pasaFiltrosHeader(s) && visConfirmadas(s)),
      ...draft.value.actividades
        .map((s, i) => ({ ...s, _origen: 'draft', _srcIdx: i }))
        .filter(s => pasaFiltrosHeader(s) && visBorradores(s)),
      ...borradorLocal.value
        .map((s, i) => ({ ...s, _origen: 'borrador', _srcIdx: i }))
        .filter(s => pasaFiltrosHeader(s) && visBorradores(s)),
    ]
  })

  // ─── agregarSesiones ──────────────────────────────────────────────────────
  // Push al borradorLocal + PUT inmediato al backend (no espera a guardarProgreso).
  function agregarSesiones(formData) {
    const { dias, ...rest } = formData
    const sesiones = dias.map(dia => ({ ...rest, dia_semana: dia }))

    const colisiones = validarColisionLocal(sesiones)
    if (colisiones.length > 0) {
      colisionesLocales.value = colisiones
      return false
    }
    colisionesLocales.value = []
    borradorLocal.value.push(...sesiones)
    autoguardarSilencioso()   // persiste draft + borradorLocal juntos
    return true
  }

  // ─── eliminarDeBorradorLocal ──────────────────────────────────────────────
  function eliminarDeBorradorLocal(index) {
    borradorLocal.value.splice(index, 1)
    if (sesionSeleccionada.value === index) sesionSeleccionada.value = null
    else if (sesionSeleccionada.value > index) sesionSeleccionada.value--
    colisionesLocales.value = []
    autoguardarSilencioso()   // sincroniza la eliminación
  }

  // ─── eliminarActividad (del draft persistido) ─────────────────────────────
  function eliminarActividad(index) {
    draft.value.actividades.splice(index, 1)
    colisionesLocales.value = []
    autoguardarSilencioso()
  }

  // ─── actualizarSesion ─────────────────────────────────────────────────────
  async function actualizarSesion(origen, index, parcial) {
    if (origen === 'confirmada') {
      const sesion = actividadesConfirmadas.value[index]
      if (!sesion) return
      await api.patch(`/programacion/actividades/${sesion.id_actividad_plantilla}`, parcial)
      actividadesConfirmadas.value[index] = { ...sesion, ...parcial }
      return
    }
    const lista = origen === 'draft' ? draft.value.actividades : borradorLocal.value
    if (!lista[index]) return
    lista[index] = { ...lista[index], ...parcial }
    autoguardarSilencioso()
  }

  // ─── eliminarSesion ───────────────────────────────────────────────────────
  async function eliminarSesion(origen, index) {
    if (origen === 'confirmada') {
      const sesion = actividadesConfirmadas.value[index]
      if (!sesion) return
      await api.delete(`/programacion/actividades/${sesion.id_actividad_plantilla}`)
      actividadesConfirmadas.value.splice(index, 1)
      cerrarDetalleSesion()
      return
    }
    if (origen === 'draft')    return eliminarActividad(index)
    if (origen === 'borrador') return eliminarDeBorradorLocal(index)
  }

  function seleccionarSesion(index) {
    sesionSeleccionada.value = sesionSeleccionada.value === index ? null : index
  }

  // ─── publicarProgramacion ─────────────────────────────────────────────────
  async function publicarProgramacion() {
    clearTimeout(_autoguardaTimer)
    isPublishing.value = true
    conflictosPublicacion.value = []
    publishSuccess.value = false
    try {
      if (borradorLocal.value.length > 0) await guardarProgreso()
      // PUT final para garantizar estado canónico antes de publicar
      await api.put('/programacion/drafts/activo', { payload: _buildPayload() })
      await api.post('/programacion/drafts/publicar')
      publishSuccess.value = true
      draft.value = { nombre_plantilla: '', fecha_inicio: '', fecha_fin: '', actividades: [] }
      borradorLocal.value          = []
      actividadesConfirmadas.value = []
    } catch (err) {
      if (err.response?.status === 422) {
        const raw = err.response.data.errors ?? err.response.data.conflictos ?? []
        conflictosPublicacion.value = Array.isArray(raw)
          ? raw
          : [{ message: err.response.data.message ?? 'Conflicto detectado.' }]
      } else {
        throw err
      }
    } finally {
      isPublishing.value = false
    }
  }

  function resetPublish() {
    conflictosPublicacion.value = []
    publishSuccess.value = false
  }

  const agregarBloques = agregarSesiones

  return {
    // catálogos
    disciplinas, espacios, instructores, isLoadingDeps, errorDeps,
    // draft
    draft,
    // plantilla activa
    idPlantillaActiva, setPlantillaActiva,
    // confirmadas
    actividadesConfirmadas, isLoadingConfirmadas,
    // borrador local
    borradorLocal, sesionSeleccionada, tieneBorradorLocal,
    // recovery
    draftPendiente, showDraftRecovery, showDiscardConfirm, isDiscardingDraft,
    // ui state
    isCreatingDraft, isLoadingDraft, isSavingDraft, isSavingProgress,
    isPublishing, publishSuccess, conflictosPublicacion, colisionesLocales, errorInit,
    // panel + filtros + detalle
    panelVisible, filtros, sesionEnDetalle,
    togglePanel, setPanelVisible, setFiltro, resetFiltros,
    abrirDetalleSesion, cerrarDetalleSesion,
    // grupos borrador (estado persistido entre pestañas)
    gruposBorradorExpandidos, toggleGrupoBorrador, grupoEstaExpandido,
    // grupos confirmadas
    gruposConfirmadasExpandidos, toggleGrupoConfirmadas, grupoConfirmadasExpandido,
    // visibilidad granular (ojitos maestros + por disciplina)
    mostrarConfirmadas, mostrarBorradores,
    disciplinasEncendidasConfirmadas, disciplinasEncendidasBorradores,
    toggleMaestroConfirmadas, toggleMaestroBorradores,
    toggleDisciplinaConfirmadas, toggleDisciplinaBorradores,
    disciplinaConfirmadasVisible, disciplinaBorradoresVisible,
    // getters
    tieneActividades, totalActividades, espaciosEnDraft, sesionesVisibles, hayColisionActiva, sesionesEnConflicto,
    // actions
    fetchDependencias, fetchActividadesConfirmadas,
    resetWizard,
    verificarDraftActivo, continuarDraftPendiente, descartarDraftPendiente,
    guardarProgreso, autoguardarSilencioso,
    agregarSesiones, agregarBloques, eliminarActividad,
    eliminarDeBorradorLocal, seleccionarSesion,
    publicarProgramacion, resetPublish,
    detectarColisionEnEdicion, actualizarSesion, eliminarSesion,
  }
})
