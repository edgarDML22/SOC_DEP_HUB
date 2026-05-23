import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

/**
 * SDH-308 – Store aislado para Actividades Programadas e Inscripciones.
 * NO comparte estado con otros módulos del sistema.
 */
export const useActividadesStore = defineStore('actividades', () => {
  // ── Estado ──────────────────────────────────────────────────────────────────
  const sesiones          = ref([])
  const misInscripciones  = ref([])
  const loading           = ref(false)
  const loadingInscripciones = ref(false)
  const loadingAccion     = ref(false)
  const error             = ref(null)
  const errorInscripciones = ref(null)

  // Filtros reactivos
  const filtroDisciplinaId = ref(null)
  const filtroHora         = ref('')

  // ── Computed ─────────────────────────────────────────────────────────────────

  /** Todas las sesiones (sin filtros) */
  const todasLasSesiones = computed(() => sesiones.value)

  /** Sesiones filtradas por disciplina y/o hora */
  const sesionesFiltradas = computed(() => {
    return sesiones.value.filter(s => {
      const matchDisciplina = !filtroDisciplinaId.value
        || s.disciplina?.id === filtroDisciplinaId.value
      const matchHora = !filtroHora.value
        || (s.hora_inicio && s.hora_inicio.startsWith(filtroHora.value))
      return matchDisciplina && matchHora
    })
  })

  /** Solo sesiones ABIERTAS (requiere_inscripcion = false), filtradas */
  const sesionesTipoAbierta = computed(() => {
    return sesionesFiltradas.value.filter(s => !s.requiere_inscripcion)
  })

  /** Lista de disciplinas únicas para el selector de filtros */
  const disciplinasDisponibles = computed(() => {
    const mapa = new Map()
    sesiones.value.forEach(s => {
      if (s.disciplina?.id && !mapa.has(s.disciplina.id)) {
        mapa.set(s.disciplina.id, s.disciplina)
      }
    })
    return Array.from(mapa.values())
  })

  /** Lista de horas únicas para el selector de filtros */
  const horasDisponibles = computed(() => {
    const set = new Set()
    sesiones.value.forEach(s => {
      if (s.hora_inicio) set.add(s.hora_inicio.slice(0, 5))
    })
    return Array.from(set).sort()
  })

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

  /** Carga el historial de inscripciones del usuario */
  async function fetchMisInscripciones() {
    loadingInscripciones.value = true
    errorInscripciones.value = null
    try {
      const res = await api.get('actividades/mis-inscripciones')
      misInscripciones.value = res.data?.data ?? []
    } catch (err) {
      errorInscripciones.value = err.response?.data?.message || 'Error al cargar tus inscripciones.'
      misInscripciones.value = []
    } finally {
      loadingInscripciones.value = false
    }
  }

  /**
   * Inscribe al usuario en una sesión.
   * Actualiza localmente el estado para evitar un refetch completo.
   * @returns {{ ok: boolean, message: string }}
   */
  async function inscribirse(id_sesion) {
    loadingAccion.value = true
    try {
      const res = await api.post(`actividades/sesiones/${id_sesion}/inscribir`)
      // Actualizar cantidad_inscritos localmente
      const idx = sesiones.value.findIndex(s => s.id_sesion === id_sesion)
      if (idx !== -1) {
        sesiones.value[idx] = {
          ...sesiones.value[idx],
          cantidad_inscritos: (sesiones.value[idx].cantidad_inscritos ?? 0) + 1,
          _inscrito: true,
        }
      }
      // Refrescar mis inscripciones
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
   * @returns {{ ok: boolean, message: string }}
   */
  async function cancelarInscripcion(id_inscripcion) {
    loadingAccion.value = true
    try {
      const res = await api.delete(`actividades/inscripciones/${id_inscripcion}`)
      // Refrescar ambas listas
      await Promise.all([fetchSesiones(), fetchMisInscripciones()])
      return { ok: true, message: res.data?.message ?? 'Inscripción cancelada.' }
    } catch (err) {
      return {
        ok: false,
        message: err.response?.data?.message || 'No se pudo cancelar la inscripción.',
      }
    } finally {
      loadingAccion.value = false
    }
  }

  /** Comprueba si el usuario ya está inscrito en una sesión (basado en misInscripciones) */
  function estaInscritoEn(id_sesion) {
    return misInscripciones.value.some(
      i => i.id_sesion === id_sesion // no disponible directamente, se usa el flag _inscrito
    )
  }

  function resetFiltros() {
    filtroDisciplinaId.value = null
    filtroHora.value = ''
  }

  return {
    // estado
    sesiones,
    misInscripciones,
    loading,
    loadingInscripciones,
    loadingAccion,
    error,
    errorInscripciones,
    // filtros
    filtroDisciplinaId,
    filtroHora,
    // computed
    todasLasSesiones,
    sesionesFiltradas,
    sesionesTipoAbierta,
    disciplinasDisponibles,
    horasDisponibles,
    // acciones
    fetchSesiones,
    fetchMisInscripciones,
    inscribirse,
    cancelarInscripcion,
    estaInscritoEn,
    resetFiltros,
  }
})
