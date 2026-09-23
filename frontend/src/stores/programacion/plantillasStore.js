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
    plantillas.value.find(p => p.estatus_plantilla === true) ?? null
  )

  // Incrementado cada vez que se retiran sesiones — SesionesPublicadas lo observa para refrescar
  const sesionesRetiradas = ref(0)

  // Incrementado al publicar — ActividadesView lo observa para cambiar a la pestaña publicadas
  const sesionesPublicadas = ref(0)

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

      // Preservar campos del estado local que no deben ser sobreescritos por la respuesta
      const existing = plantillas.value.find(p => p.id_plantilla === id)
      if (existing) {
        updated.total_actividades = existing.total_actividades ?? 0
        // Preservar publicada — el PATCH no modifica este campo nunca
        if (!('publicada' in updated)) updated.publicada = existing.publicada ?? false
        // Si el payload no incluyó fechas (solo cambió estatus u otro campo),
        // conservar las fechas actuales en lugar de las que devuelva la API.
        if (!('fecha_inicio' in clean)) updated.fecha_inicio = existing.fecha_inicio
        if (!('fecha_fin'    in clean)) updated.fecha_fin    = existing.fecha_fin
      }

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

  // ─── Publicar plantilla a sesiones activas ────────────────────────────────
  // POST /programacion/plantillas/{id}/publicar
  // Retorna { id_plantilla, semana_inicio, semana_fin, sesiones_creadas }
  // notificarCambio=true solo en la última semana para disparar navegación una sola vez
  async function publicarPlantilla(idPlantilla, semanaInicio, notificarCambio = true) {
    isSaving.value = true
    try {
      const { data } = await api.post(
        `/programacion/plantillas/${idPlantilla}/publicar`,
        { semana_inicio: semanaInicio }
      )
      const p = plantillas.value.find(p => p.id_plantilla === idPlantilla)
      if (p) {
        p.estatus_plantilla = true
        p.publicada         = true
        // Mantener el rango más amplio si ya hay fechas previas (publicación de 2 semanas)
        const nuevaInicio = data?.data?.semana_inicio
        const nuevaFin    = data?.data?.semana_fin
        if (nuevaInicio) {
          p.fecha_inicio = !p.fecha_inicio || nuevaInicio < p.fecha_inicio ? nuevaInicio : p.fecha_inicio
        }
        if (nuevaFin) {
          p.fecha_fin = !p.fecha_fin || nuevaFin > p.fecha_fin ? nuevaFin : p.fecha_fin
        }
      }
      if (notificarCambio) sesionesPublicadas.value++
      return data
    } finally {
      isSaving.value = false
    }
  }

  // ─── Exportar PDF ───────────────────────────────────────────────────────────
  async function exportarPdf(idPlantilla) {
    isSaving.value = true
    try {
      const response = await api.get(`/programacion/plantillas/${idPlantilla}/exportar-pdf`, {
        responseType: 'blob'
      })
      const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `Plantilla_Programacion_${idPlantilla}.pdf`)
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    } finally {
      isSaving.value = false
    }
  }

  // ─── Despublicar plantilla (eliminar sesiones activas) ───────────────────
  // DELETE /programacion/plantillas/{id}/sesiones
  // Retorna { sesiones_eliminadas }
  async function despublicarPlantilla(idPlantilla) {
    isSaving.value = true
    try {
      const { data } = await api.delete(`/programacion/plantillas/${idPlantilla}/sesiones`)
      // Resetea estatus y fechas en el estado local sin refetch
      const p = plantillas.value.find(p => p.id_plantilla === idPlantilla)
      if (p) {
        p.estatus_plantilla = false
        p.publicada    = false
        p.fecha_inicio = null
        p.fecha_fin    = null
      }
      sesionesRetiradas.value++
      return data
    } finally {
      isSaving.value = false
    }
  }

  return {
    plantillas, plantillaActiva,
    isLoading, isSaving, isDeleting, error,
    fetchPlantillas, createPlantilla, updatePlantilla, deletePlantilla,
    actualizarTotalActividades, publicarPlantilla, despublicarPlantilla, exportarPdf,
    sesionesRetiradas, sesionesPublicadas,
  }
})
