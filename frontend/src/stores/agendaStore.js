import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

/**
 * Filtrado de encuentros cancelados: lo realiza el backend.
 * Este store solo consume la respuesta ya filtrada.
 */
export const useAgendaStore = defineStore('agenda', () => {
  // ── Socio ────────────────────────────────────────────────────────────────
  const encuentrosSocio = ref([])
  const proximaActividadSocio = ref(null)
  const loadingSocio = ref(false)
  const errorSocio = ref(null)

  // ── Instructor ───────────────────────────────────────────────────────────
  const itemsInstructor = ref([])            // lista plana de todos los ítems
  const proximaActividadInstructor = ref(null)
  const loadingInstructor = ref(false)
  const errorInstructor = ref(null)

  // ── Legacy (solo encuentros de torneo del instructor, endpoint viejo) ────
  const encuentrosInstructor = ref([])

  // ── Computed ─────────────────────────────────────────────────────────────
  const encuentrosSocioVisibles = computed(() => encuentrosSocio.value)
  const encuentrosInstructorVisibles = computed(() => encuentrosInstructor.value)

  /**
   * Próxima actividad expuesta al componente que la consuma:
   * devuelve la del instructor si está cargada, si no la del socio.
   */
  const proximaActividad = computed(() =>
    proximaActividadInstructor.value ?? proximaActividadSocio.value
  )

  // ── Acciones: Socio ───────────────────────────────────────────────────────
  const fetchSocioAgenda = async () => {
    loadingSocio.value = true
    errorSocio.value = null
    try {
      const res = await api.get('/socio/mi-agenda')
      const groupedAgenda = res.data?.data?.agenda || []
      proximaActividadSocio.value = res.data?.data?.proxima_actividad || null
      const flatItems = []
      groupedAgenda.forEach(group => {
        group.items.forEach(item => flatItems.push(item))
      })
      encuentrosSocio.value = flatItems
    } catch (err) {
      console.error('Error fetching socio agenda:', err)
      errorSocio.value = err.response?.data?.message || 'Error al cargar la agenda.'
      encuentrosSocio.value = []
      proximaActividadSocio.value = null
    } finally {
      loadingSocio.value = false
    }
  }

  // ── Acciones: Instructor all-in-one ───────────────────────────────────────
  const fetchInstructorAgenda = async () => {
    loadingInstructor.value = true
    errorInstructor.value = null
    try {
      const res = await api.get('/instructor/mi-agenda')
      const groupedAgenda = res.data?.data?.agenda || []
      proximaActividadInstructor.value = res.data?.data?.proxima_actividad || null
      const flatItems = []
      groupedAgenda.forEach(group => {
        group.items.forEach(item => flatItems.push(item))
      })
      itemsInstructor.value = flatItems
    } catch (err) {
      console.error('Error fetching instructor agenda:', err)
      errorInstructor.value = err.response?.data?.message || 'Error al cargar la agenda.'
      itemsInstructor.value = []
      proximaActividadInstructor.value = null
    } finally {
      loadingInstructor.value = false
    }
  }

  // ── Acción legacy (encuentros torneo del instructor, endpoint viejo) ──────
  const fetchInstructorEncuentros = async () => {
    try {
      const res = await api.get('/instructor/encuentros-torneo')
      encuentrosInstructor.value = res.data?.data ?? []
    } catch (err) {
      console.error('Error fetching instructor encuentros:', err)
      encuentrosInstructor.value = []
    }
  }

  return {
    // Socio
    encuentrosSocio,
    proximaActividadSocio,
    encuentrosSocioVisibles,
    loadingSocio,
    errorSocio,
    fetchSocioAgenda,

    // Instructor (all-in-one)
    itemsInstructor,
    proximaActividadInstructor,
    loadingInstructor,
    errorInstructor,
    fetchInstructorAgenda,

    // Legacy
    encuentrosInstructor,
    encuentrosInstructorVisibles,
    fetchInstructorEncuentros,

    // Genérico (para componentes que lo usen indistintamente)
    proximaActividad,
  }
})
