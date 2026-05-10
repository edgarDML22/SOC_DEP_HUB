import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAdminFamilyStore = defineStore('adminFamily', () => {
  // State
  const miembros = ref([])
  const currentSocioId = ref(null)
  const loading = ref({
    fetch: false,
    create: false,
    update: false,
    delete: false
  })
  const error = ref(null)

  // Getters/Computed
  const totalMiembros = computed(() => miembros.value.length)

  // Actions
  const fetchMiembros = async (socioId, force = false) => {
    const hasCache = String(currentSocioId.value) === String(socioId) && miembros.value.length > 0
    
    if (!force && hasCache) {
      // Refrescar en segundo plano de manera silenciosa para mantener sincronía
      api.get(`/admin/socios/${socioId}/familiares`).then(response => {
        if (response.data && response.data.success !== undefined) {
          miembros.value = response.data.data || []
        } else {
          miembros.value = response.data || []
        }
      }).catch(err => console.error("Error refreshing family list silently:", err))
      return
    }

    currentSocioId.value = socioId
    loading.value.fetch = true
    error.value = null
    try {
      const response = await api.get(`/admin/socios/${socioId}/familiares`)
      if (response.data && response.data.success !== undefined) {
        miembros.value = response.data.data || []
      } else {
        miembros.value = response.data || []
      }
    } catch (err) {
      error.value = 'Error al cargar los miembros familiares.'
      console.error(err)
    } finally {
      loading.value.fetch = false
    }
  }

  const crearMiembro = async (socioId, payload) => {
    loading.value.create = true
    error.value = null
    try {
      const response = await api.post(`/admin/socios/${socioId}/familiares`, payload)
      let newMiembro = response.data.data || response.data
      
      miembros.value.push(newMiembro)
      miembros.value.sort((a, b) => a.nombre_completo.localeCompare(b.nombre_completo))
      
      return newMiembro
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al crear el miembro familiar.'
      console.error(err)
      throw err
    } finally {
      loading.value.create = false
    }
  }

  const actualizarMiembro = async (socioId, miembroId, payload) => {
    loading.value.update = true
    error.value = null
    try {
      const response = await api.put(`/admin/socios/${socioId}/familiares/${miembroId}`, payload)
      const updatedData = response.data.data || response.data
      
      const index = miembros.value.findIndex(m => m.id_miembro === miembroId)
      if (index !== -1) {
        miembros.value[index] = {
          ...miembros.value[index],
          ...updatedData
        }
        miembros.value.sort((a, b) => a.nombre_completo.localeCompare(b.nombre_completo))
      }
      return updatedData
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al actualizar el miembro familiar.'
      console.error(err)
      throw err
    } finally {
      loading.value.update = false
    }
  }

  const eliminarMiembro = async (socioId, miembroId) => {
    loading.value.delete = true
    error.value = null
    try {
      await api.delete(`/admin/socios/${socioId}/familiares/${miembroId}`)
      miembros.value = miembros.value.filter(m => m.id_miembro !== miembroId)
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al eliminar el miembro familiar.'
      console.error(err)
      throw err
    } finally {
      loading.value.delete = false
    }
  }

  return {
    miembros,
    currentSocioId,
    loading,
    error,
    totalMiembros,
    fetchMiembros,
    crearMiembro,
    actualizarMiembro,
    eliminarMiembro
  }
})
