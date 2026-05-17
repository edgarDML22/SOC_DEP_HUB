import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'
import { useNotificacionesStore } from './notificacionesStore'
import { useFamilyStore } from '@/stores/community/familyStore'
import { useGuestStore } from '@/stores/community/guestStore'

export const useBootstrapStore = defineStore('bootstrap', () => {
  const loaded = ref(false)

  const fetchSocioData = async ({ force = false } = {}) => {
    if (!force && loaded.value) return

    const notifStore = useNotificacionesStore()
    const familyStore = useFamilyStore()
    const guestStore = useGuestStore()

    try {
      const res = await api.get('/bootstrap/socio-data')
      if (!res.data?.success) return

      const { notificaciones, miembros_familiares, invitados } = res.data.data

      notifStore.notificaciones = notificaciones ?? []
      familyStore.miembrosFamiliares = miembros_familiares ?? []
      guestStore.invitados = invitados ?? []

      loaded.value = true
    } catch (err) {
      console.error('Error en bootstrap:', err)
    }
  }

  const reset = () => {
    loaded.value = false
  }

  return { loaded, fetchSocioData, reset }
})
