import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

/**
 * Filtrado de encuentros cancelados: lo realiza el backend
 * (GET /v1/socio/agenda y GET /v1/instructor/encuentros-torneo).
 * No duplicar filtros aquí; este store solo consume la respuesta ya filtrada.
 */
export const useAgendaStore = defineStore('agenda', () => {
  const encuentrosSocio = ref([])
  const encuentrosInstructor = ref([])
  const loadingSocio = ref(false)
  const loadingInstructor = ref(false)
  const errorSocio = ref(null)
  const errorInstructor = ref(null)

  const encuentrosSocioVisibles = computed(() => encuentrosSocio.value)

  const encuentrosInstructorVisibles = computed(() => encuentrosInstructor.value)

  const fetchSocioAgenda = async () => {
    loadingSocio.value = true
    errorSocio.value = null
    try {
      const res = await api.get('/socio/agenda')
      encuentrosSocio.value = res.data?.data ?? []
    } catch (err) {
      console.error('Error fetching socio agenda:', err)
      errorSocio.value = err.response?.data?.message || 'Error al cargar la agenda.'
      encuentrosSocio.value = []
    } finally {
      loadingSocio.value = false
    }
  }

  const fetchInstructorEncuentros = async () => {
    loadingInstructor.value = true
    errorInstructor.value = null
    try {
      const res = await api.get('/instructor/encuentros-torneo')
      encuentrosInstructor.value = res.data?.data ?? []
    } catch (err) {
      console.error('Error fetching instructor encuentros:', err)
      errorInstructor.value = err.response?.data?.message || 'Error al cargar encuentros de torneo.'
      encuentrosInstructor.value = []
    } finally {
      loadingInstructor.value = false
    }
  }

  return {
    encuentrosSocio,
    encuentrosInstructor,
    encuentrosSocioVisibles,
    encuentrosInstructorVisibles,
    loadingSocio,
    loadingInstructor,
    errorSocio,
    errorInstructor,
    fetchSocioAgenda,
    fetchInstructorEncuentros,
  }
})
