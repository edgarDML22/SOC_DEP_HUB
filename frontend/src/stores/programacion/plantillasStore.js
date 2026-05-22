import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const usePlantillasStore = defineStore('plantillas', () => {
  const plantillas  = ref([])
  const isLoading   = ref(false)
  const isSaving    = ref(false)
  const isDeleting  = ref(false)
  const error       = ref(null)

  // Plantilla activa en este momento (usada por el Wizard)
  const plantillaActiva = computed(() =>
    plantillas.value.find(p => p.estatus_plantilla === 'ACTIVO') ?? null
  )

  // ─── Fetch list ───────────────────────────────────────────────────────────────
  async function fetchPlantillas() {
    isLoading.value = true
    error.value     = null
    try {
      const { data } = await api.get('/programacion/plantillas')
      plantillas.value = data.data
    } catch {
      error.value = 'No se pudieron cargar las plantillas.'
    } finally {
      isLoading.value = false
    }
  }

  // ─── Update (nombre, fechas, estatus) ────────────────────────────────────────
  async function updatePlantilla(id, payload) {
    isSaving.value = true
    // Omite fechas vacías/nulas del payload — el backend usa `sometimes` y no las toca
    const clean = Object.fromEntries(
      Object.entries(payload).filter(([k, v]) => {
        if (k === 'fecha_inicio' || k === 'fecha_fin') return v !== '' && v !== null
        return true
      })
    )
    try {
      const { data } = await api.patch(`/programacion/plantillas/${id}`, clean)
      const updated = data.data

      // La API no devuelve total_actividades — lo preservamos del estado local
      const existing = plantillas.value.find(p => p.id_plantilla === id)
      if (existing) updated.total_actividades = existing.total_actividades ?? 0

      const idx = plantillas.value.findIndex(p => p.id_plantilla === id)
      if (idx !== -1) plantillas.value[idx] = updated

      return updated
    } finally {
      isSaving.value = false
    }
  }

  // ─── Create ───────────────────────────────────────────────────────────────────
  async function createPlantilla(payload) {
    isSaving.value = true
    try {
      const { data } = await api.post('/programacion/plantillas', payload)
      const nueva = { ...data.data, total_actividades: 0 }
      plantillas.value.push(nueva)
      return nueva
    } finally {
      isSaving.value = false
    }
  }

  // ─── Delete ───────────────────────────────────────────────────────────────────
  // Retorna el mensaje contextual del servidor (hard vs soft delete)
  async function deletePlantilla(id) {
    isDeleting.value = true
    try {
      const { data } = await api.delete(`/programacion/plantillas/${id}`)
      plantillas.value = plantillas.value.filter(p => p.id_plantilla !== id)
      return data
    } finally {
      isDeleting.value = false
    }
  }

  // Actualiza el contador local sin refetch — llamado tras consolidar/publicar
  function actualizarTotalActividades(idPlantilla, total) {
    const p = plantillas.value.find(p => p.id_plantilla === idPlantilla)
    if (p) p.total_actividades = total
  }

  return {
    plantillas, plantillaActiva,
    isLoading, isSaving, isDeleting, error,
    fetchPlantillas, createPlantilla, updatePlantilla, deletePlantilla,
    actualizarTotalActividades,
  }
})
