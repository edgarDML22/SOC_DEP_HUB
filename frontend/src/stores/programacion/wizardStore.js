import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

// Time string "HH:MM" or "HH:MM:SS" → minutes from midnight
function toMinutes(t) {
  const [h, m] = t.split(':').map(Number)
  return h * 60 + m
}

// Returns true if [aStart, aEnd) overlaps [bStart, bEnd)
function overlaps(aStart, aEnd, bStart, bEnd) {
  return aStart < bEnd && bStart < aEnd
}

export const useWizardStore = defineStore('wizardProgramacion', () => {
  // ─── Dependencias (catálogos) ─────────────────────────────────────────────
  const disciplinas = ref([])
  const espacios = ref([])
  const instructores = ref([])
  const isLoadingDeps = ref(false)
  const errorDeps = ref(null)

  // ─── Draft (persistido en backend) ───────────────────────────────────────
  const draftId = ref(null)
  const draft = ref({
    nombre_plantilla: '',
    fecha_inicio: '',
    fecha_fin: '',
    actividades: [],
  })

  // ─── Borrador local (acumulación antes de guardar) ────────────────────────
  const borradorLocal = ref([])
  const sesionSeleccionada = ref(null)   // índice dentro de borradorLocal

  // ─── UI state ─────────────────────────────────────────────────────────────
  const isCreatingDraft = ref(false)
  const isSavingDraft = ref(false)
  const isSavingProgress = ref(false)
  const isPublishing = ref(false)
  const publishSuccess = ref(false)
  const conflictosPublicacion = ref([])
  const colisionesLocales = ref([])
  const errorInit = ref(null)

  // ─── Getters ──────────────────────────────────────────────────────────────
  const tieneActividades = computed(() => draft.value.actividades.length > 0)
  const totalActividades = computed(() => draft.value.actividades.length)
  const tieneBorradorLocal = computed(() => borradorLocal.value.length > 0)

  // Includes spaces from both persisted draft AND local draft
  const espaciosEnDraft = computed(() => {
    const ids = new Set([
      ...draft.value.actividades.map(a => a.id_espacio),
      ...borradorLocal.value.map(a => a.id_espacio),
    ])
    return espacios.value.filter(e => ids.has(e.id_espacio))
  })

  // ─── fetchDependencias ────────────────────────────────────────────────────
  async function fetchDependencias() {
    if (disciplinas.value.length > 0) return
    isLoadingDeps.value = true
    errorDeps.value = null
    try {
      const { data } = await api.get('/programacion/dependencias')
      disciplinas.value = data.disciplinas
      espacios.value    = data.espacios
      instructores.value = data.instructores
    } catch (err) {
      errorDeps.value = 'No se pudieron cargar los catálogos. Intenta de nuevo.'
      throw err
    } finally {
      isLoadingDeps.value = false
    }
  }

  // ─── crearDraft ───────────────────────────────────────────────────────────
  async function crearDraft() {
    isCreatingDraft.value = true
    errorInit.value = null
    try {
      const { data } = await api.post('/programacion/drafts')
      draftId.value = data.data.id
      const payload  = data.data.payload ?? {}
      draft.value = {
        nombre_plantilla: payload.nombre_plantilla ?? '',
        fecha_inicio:     payload.fecha_inicio     ?? '',
        fecha_fin:        payload.fecha_fin        ?? '',
        actividades:      payload.actividades      ?? [],
      }
    } catch (err) {
      if (err.response?.status === 409) {
        const payload = err.response.data.data.payload ?? {}
        draftId.value = err.response.data.data.id
        draft.value = {
          nombre_plantilla: payload.nombre_plantilla ?? '',
          fecha_inicio:     payload.fecha_inicio     ?? '',
          fecha_fin:        payload.fecha_fin        ?? '',
          actividades:      payload.actividades      ?? [],
        }
      } else {
        errorInit.value = 'Error al inicializar el borrador. Intenta de nuevo.'
        throw err
      }
    } finally {
      isCreatingDraft.value = false
    }
  }

  // ─── guardarDraft (PATCH interno) ─────────────────────────────────────────
  async function guardarDraft() {
    if (!draftId.value) return
    isSavingDraft.value = true
    try {
      await api.patch(`/programacion/drafts/${draftId.value}`, {
        payload: {
          nombre_plantilla: draft.value.nombre_plantilla,
          fecha_inicio:     draft.value.fecha_inicio,
          fecha_fin:        draft.value.fecha_fin,
          actividades:      draft.value.actividades.map(cleanActividad),
        },
      })
    } finally {
      isSavingDraft.value = false
    }
  }

  // ─── guardarProgreso (acción de usuario: vuelca borrador local → backend) ─
  async function guardarProgreso() {
    if (!draftId.value || borradorLocal.value.length === 0) return
    isSavingProgress.value = true
    try {
      draft.value.actividades.push(...borradorLocal.value)
      await api.patch(`/programacion/drafts/${draftId.value}`, {
        payload: {
          nombre_plantilla: draft.value.nombre_plantilla,
          fecha_inicio:     draft.value.fecha_inicio,
          fecha_fin:        draft.value.fecha_fin,
          actividades:      draft.value.actividades.map(cleanActividad),
        },
      })
      borradorLocal.value = []
      sesionSeleccionada.value = null
    } finally {
      isSavingProgress.value = false
    }
  }

  function cleanActividad(a) {
    const { _espacio_nombre, _disciplina_nombre, _instructor_nombre, ...rest } = a
    return rest
  }

  // ─── validarColisionLocal ─────────────────────────────────────────────────
  function validarColisionLocal(nuevasSesiones) {
    const conflictos = []
    // Combina persistidas + borrador local para la validación completa
    const existentes = [...draft.value.actividades, ...borradorLocal.value]

    for (const nuevo of nuevasSesiones) {
      const nuevoStart = toMinutes(nuevo.hora_inicio)
      const nuevoEnd   = toMinutes(nuevo.hora_fin)

      for (const ex of existentes) {
        if (ex.dia_semana !== nuevo.dia_semana) continue

        const exStart = toMinutes(ex.hora_inicio)
        const exEnd   = toMinutes(ex.hora_fin)

        if (!overlaps(nuevoStart, nuevoEnd, exStart, exEnd)) continue

        if (ex.id_espacio === nuevo.id_espacio) {
          conflictos.push({
            tipo: 'espacio',
            dia:  nuevo.dia_semana,
            nombre: nuevo._espacio_nombre,
            horario_nuevo: `${nuevo.hora_inicio}–${nuevo.hora_fin}`,
            horario_existente: `${ex.hora_inicio}–${ex.hora_fin}`,
          })
        }
        if (ex.id_instructor === nuevo.id_instructor) {
          conflictos.push({
            tipo: 'instructor',
            dia:  nuevo.dia_semana,
            nombre: nuevo._instructor_nombre,
            horario_nuevo: `${nuevo.hora_inicio}–${nuevo.hora_fin}`,
            horario_existente: `${ex.hora_inicio}–${ex.hora_fin}`,
          })
        }
      }
    }
    return conflictos
  }

  // ─── agregarSesiones ──────────────────────────────────────────────────────
  // Expands dias[] → individual sessions, validates, pushes to borradorLocal
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
    return true
  }

  // ─── eliminarDeBorradorLocal ──────────────────────────────────────────────
  function eliminarDeBorradorLocal(index) {
    borradorLocal.value.splice(index, 1)
    if (sesionSeleccionada.value === index) sesionSeleccionada.value = null
    else if (sesionSeleccionada.value > index) sesionSeleccionada.value--
    colisionesLocales.value = []
  }

  // ─── eliminarActividad (persistidas) ──────────────────────────────────────
  function eliminarActividad(index) {
    draft.value.actividades.splice(index, 1)
    colisionesLocales.value = []
  }

  // ─── seleccionarSesion ────────────────────────────────────────────────────
  function seleccionarSesion(index) {
    sesionSeleccionada.value = sesionSeleccionada.value === index ? null : index
  }

  // ─── publicarProgramacion ─────────────────────────────────────────────────
  async function publicarProgramacion() {
    if (!draftId.value) return
    isPublishing.value = true
    conflictosPublicacion.value = []
    publishSuccess.value = false
    try {
      // If there's unsaved local draft, flush it first
      if (borradorLocal.value.length > 0) {
        await guardarProgreso()
      }
      await guardarDraft()
      await api.post(`/programacion/drafts/${draftId.value}/publicar`)
      publishSuccess.value = true
      draftId.value = null
      draft.value = { nombre_plantilla: '', fecha_inicio: '', fecha_fin: '', actividades: [] }
      borradorLocal.value = []
    } catch (err) {
      if (err.response?.status === 422) {
        const raw = err.response.data.errors ?? err.response.data.conflictos ?? []
        conflictosPublicacion.value = Array.isArray(raw)
          ? raw
          : [{ message: err.response.data.message ?? 'Conflicto de horario detectado.' }]
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

  // Keep backward compat alias
  const agregarBloques = agregarSesiones

  return {
    // catálogos
    disciplinas, espacios, instructores, isLoadingDeps, errorDeps,
    // draft (persistido)
    draftId, draft,
    // borrador local
    borradorLocal, sesionSeleccionada, tieneBorradorLocal,
    // ui state
    isCreatingDraft, isSavingDraft, isSavingProgress, isPublishing, publishSuccess,
    conflictosPublicacion, colisionesLocales, errorInit,
    // getters
    tieneActividades, totalActividades, espaciosEnDraft,
    // actions
    fetchDependencias, crearDraft, guardarDraft, guardarProgreso,
    agregarSesiones, agregarBloques, eliminarActividad,
    eliminarDeBorradorLocal, seleccionarSesion,
    publicarProgramacion, resetPublish,
  }
})
