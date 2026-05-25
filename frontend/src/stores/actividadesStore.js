import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

/**
 * SDH-308 v2 – Store aislado para Actividades Programadas e Inscripciones.
 *
 * Añadidos respecto a v1:
 *   - filtroInstructorId / filtroDiaSemana
 *   - instructoresDisponibles / diasDisponibles (computed desde sesiones)
 *   - fetchInstructores() — para poblar selector desde backend
 *   - inscribirse() acepta { id_miembro_familiar, id_pase_invitado }
 *   - cancelarInscripcion() retorna { penalizacion: bool } para modal de advertencia
 */
export const useActividadesStore = defineStore('actividades', () => {
  // ── Estado ──────────────────────────────────────────────────────────────────
  const sesiones             = ref([])
  const misInscripciones     = ref([])
  const instructores         = ref([])   // lista para el filtro de instructores
  const loading              = ref(false)
  const loadingInscripciones = ref(false)
  const loadingAccion        = ref(false)
  const error                = ref(null)
  const errorInscripciones   = ref(null)

  // Filtros reactivos
  const filtroDisciplinaId  = ref(null)
  const filtroInstructorId  = ref(null)
  const filtroDiaSemana     = ref(null)
  const filtroHora          = ref('')

  // ── Computed ─────────────────────────────────────────────────────────────────

  /** Todas las sesiones sin filtrar */
  const todasLasSesiones = computed(() => sesiones.value)

  /** Sesiones filtradas por todos los filtros activos, ordenadas por hora de inicio y fecha */
  const sesionesFiltradas = computed(() => {
    const list = sesiones.value.filter(s => {
      const matchDisciplina  = !filtroDisciplinaId.value
        || s.disciplina?.id === filtroDisciplinaId.value
      const matchInstructor  = !filtroInstructorId.value
        || s.instructor?.id === filtroInstructorId.value
      const matchDia         = !filtroDiaSemana.value
        || s.dia_semana === filtroDiaSemana.value
      const matchHora        = !filtroHora.value
        || (s.hora_inicio && s.hora_inicio.startsWith(filtroHora.value))
      return matchDisciplina && matchInstructor && matchDia && matchHora
    })

    // Ordenar por hora_inicio ASC, y si es igual por fecha_sesion ASC
    return [...list].sort((a, b) => {
      const timeA = a.hora_inicio || '00:00:00'
      const timeB = b.hora_inicio || '00:00:00'
      const dateA = a.fecha_sesion || ''
      const dateB = b.fecha_sesion || ''
      return timeA.localeCompare(timeB) || dateA.localeCompare(dateB)
    })
  })

  /** Solo sesiones ABIERTAS (requiere_inscripcion = false), filtradas */
  const sesionesTipoAbierta = computed(() =>
    sesionesFiltradas.value.filter(s => !s.requiere_inscripcion)
  )

  /** Solo sesiones CERRADAS (requiere_inscripcion = true), filtradas */
  const sesionesTipoCerrada = computed(() =>
    sesionesFiltradas.value.filter(s => s.requiere_inscripcion)
  )

  /** Lista de disciplinas únicas para el select de filtros */
  const disciplinasDisponibles = computed(() => {
    const mapa = new Map()
    sesiones.value.forEach(s => {
      if (s.disciplina?.id && !mapa.has(s.disciplina.id)) {
        mapa.set(s.disciplina.id, s.disciplina)
      }
    })
    return Array.from(mapa.values())
  })

  /** Lista de instructores únicos desde las sesiones (fallback local) */
  const instructoresDisponibles = computed(() => {
    if (instructores.value.length) return instructores.value
    const mapa = new Map()
    sesiones.value.forEach(s => {
      if (s.instructor?.id && !mapa.has(s.instructor.id)) {
        mapa.set(s.instructor.id, s.instructor)
      }
    })
    return Array.from(mapa.values())
  })

  /** Lista de días de semana únicos en las sesiones */
  const diasDisponibles = computed(() => {
    const ORDEN = ['LUNES','MARTES','MIERCOLES','JUEVES','VIERNES','SABADO','DOMINGO']
    const set = new Set(sesiones.value.map(s => s.dia_semana).filter(Boolean))
    return ORDEN.filter(d => set.has(d))
  })

  /** Lista de horas únicas para el select de filtros */
  const horasDisponibles = computed(() => {
    const set = new Set()
    sesiones.value.forEach(s => {
      if (s.hora_inicio) set.add(s.hora_inicio.slice(0, 5))
    })
    return Array.from(set).sort()
  })

  /** ¿Hay algún filtro activo? */
  const hayFiltrosActivos = computed(() =>
    !!(filtroDisciplinaId.value || filtroInstructorId.value || filtroDiaSemana.value || filtroHora.value)
  )

  // ── Acciones ─────────────────────────────────────────────────────────────────

  /** Carga sesiones activas/programadas desde el backend */
  async function fetchSesiones() {
    loading.value = true
    error.value = null
    try {
      const res = await api.get('actividades/sesiones')
      sesiones.value = res.data?.data ?? []
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al cargar las actividades.'
      sesiones.value = []
    } finally {
      loading.value = false
    }
  }

  /** Carga la lista de instructores para el select de filtros */
  async function fetchInstructores() {
    try {
      const res = await api.get('actividades/instructores')
      instructores.value = res.data?.data ?? []
    } catch {
      // Silencioso — el computed instructoresDisponibles hace fallback desde sesiones
    }
  }

  /** Carga el historial de inscripciones del usuario, ordenado por fecha y hora más cercana al inicio */
  async function fetchMisInscripciones() {
    loadingInscripciones.value = true
    errorInscripciones.value = null
    try {
      const res = await api.get('actividades/mis-inscripciones')
      const data = res.data?.data ?? []
      
      // Ordenar cronológicamente (la más próxima al inicio primero)
      data.sort((a, b) => {
        const dateA = a.fecha_sesion || '9999-12-31'
        const dateB = b.fecha_sesion || '9999-12-31'
        const timeA = a.hora_inicio || '00:00:00'
        const timeB = b.hora_inicio || '00:00:00'
        return dateA.localeCompare(dateB) || timeA.localeCompare(timeB)
      })
      
      misInscripciones.value = data
    } catch (err) {
      errorInscripciones.value = err.response?.data?.message || 'Error al cargar tus inscripciones.'
      misInscripciones.value = []
    } finally {
      loadingInscripciones.value = false
    }
  }

  /**
   * Inscribe un participante en una sesión.
   *
   * @param {number} id_sesion
   * @param {object} opts  — { id_miembro_familiar?, id_pase_invitado? }
   * @returns {{ ok: boolean, message: string }}
   */
  async function inscribirse(id_sesion, opts = {}) {
    loadingAccion.value = true
    try {
      const payload = {}
      if (opts.id_miembro_familiar) payload.id_miembro_familiar = opts.id_miembro_familiar
      if (opts.id_pase_invitado)    payload.id_pase_invitado    = opts.id_pase_invitado

      const res = await api.post(`actividades/sesiones/${id_sesion}/inscribir`, payload)

      // Actualizar cantidad_inscritos localmente
      const idx = sesiones.value.findIndex(s => s.id_sesion === id_sesion)
      if (idx !== -1) {
        sesiones.value[idx] = {
          ...sesiones.value[idx],
          cantidad_inscritos: (sesiones.value[idx].cantidad_inscritos ?? 0) + 1,
          _inscrito: true,
        }
      }
      await fetchMisInscripciones()
      return { ok: true, message: res.data?.message ?? 'Inscripción realizada.' }
    } catch (err) {
      return {
        ok: false,
        message: err.response?.data?.message || 'No se pudo completar la inscripción.',
      }
    } finally {
      loadingAccion.value = false
    }
  }

  /**
   * Cancela una inscripción existente.
   *
   * @returns {{ ok: boolean, message: string, penalizacion: boolean }}
   */
  async function cancelarInscripcion(id_inscripcion) {
    loadingAccion.value = true
    try {
      const res = await api.delete(`actividades/inscripciones/${id_inscripcion}`)
      await Promise.all([fetchSesiones(), fetchMisInscripciones()])
      return {
        ok: true,
        message: res.data?.message ?? 'Inscripción cancelada.',
        penalizacion: res.data?.penalizacion ?? false,
      }
    } catch (err) {
      return {
        ok: false,
        message: err.response?.data?.message || 'No se pudo cancelar la inscripción.',
        penalizacion: false,
      }
    } finally {
      loadingAccion.value = false
    }
  }

  /** Comprueba si el usuario ya está inscrito en una sesión */
  function estaInscritoEn(id_sesion) {
    return misInscripciones.value.some(i => i.id_sesion === id_sesion)
  }

  function resetFiltros() {
    filtroDisciplinaId.value = null
    filtroInstructorId.value = null
    filtroDiaSemana.value    = null
    filtroHora.value         = ''
  }

  return {
    // estado
    sesiones,
    misInscripciones,
    instructores,
    loading,
    loadingInscripciones,
    loadingAccion,
    error,
    errorInscripciones,
    // filtros
    filtroDisciplinaId,
    filtroInstructorId,
    filtroDiaSemana,
    filtroHora,
    hayFiltrosActivos,
    // computed
    todasLasSesiones,
    sesionesFiltradas,
    sesionesTipoAbierta,
    sesionesTipoCerrada,
    disciplinasDisponibles,
    instructoresDisponibles,
    diasDisponibles,
    horasDisponibles,
    // acciones
    fetchSesiones,
    fetchInstructores,
    fetchMisInscripciones,
    inscribirse,
    cancelarInscripcion,
    estaInscritoEn,
    resetFiltros,
  }
})
