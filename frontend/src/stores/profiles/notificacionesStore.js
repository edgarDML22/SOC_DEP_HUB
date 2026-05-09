import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useNotificacionesStore = defineStore('notificaciones', () => {
  const notificaciones = ref([])
  const isLoading      = ref(false)

  const noLeidas = computed(() => notificaciones.value.filter(n => !n.leida).length)
  const tieneNoLeidas = computed(() => noLeidas.value > 0)

  const fetchNotificaciones = async ({ force = false } = {}) => {
    if (!force && notificaciones.value.length > 0) return

    isLoading.value = true
    try {
      const res = await api.get('/notificaciones')
      if (res.data?.success) {
        notificaciones.value = res.data.notificaciones
      }
    } catch (err) {
      console.error('Error al cargar notificaciones:', err)
    } finally {
      isLoading.value = false
    }
  }

  const marcarLeida = async (id) => {
    try {
      await api.patch(`/notificaciones/${id}/leer`)
      const n = notificaciones.value.find(n => n.id === id)
      if (n) n.leida = true
    } catch (err) {
      console.error('Error al marcar notificación como leída:', err)
    }
  }

  const marcarTodasLeidas = async () => {
    try {
      await api.patch('/notificaciones/leer-todas')
      notificaciones.value.forEach(n => (n.leida = true))
    } catch (err) {
      console.error('Error al marcar todas como leídas:', err)
    }
  }

  const reset = () => {
    notificaciones.value = []
    isLoading.value = false
  }

  return {
    notificaciones,
    isLoading,
    noLeidas,
    tieneNoLeidas,
    fetchNotificaciones,
    marcarLeida,
    marcarTodasLeidas,
    reset,
  }
})
