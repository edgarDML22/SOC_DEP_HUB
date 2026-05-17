import { ref } from 'vue'
import { defineStore } from 'pinia'

export const useQrStore = defineStore('qr', () => {
  const qrPayload = ref('')
  const qrImageUrl = ref('')
  const loading = ref(false)
  const error = ref('')

  // Llamado desde profileStore después de cargar el perfil — no hace su propio fetch
  const setFromProfile = (payload, imageUrl) => {
    qrPayload.value = payload ?? ''
    qrImageUrl.value = imageUrl ?? ''
    loading.value = false
    error.value = ''
  }

  const setError = (msg) => {
    error.value = msg
    loading.value = false
  }

  const setLoading = () => {
    loading.value = true
    error.value = ''
  }

  const reset = () => {
    qrPayload.value = ''
    qrImageUrl.value = ''
    loading.value = false
    error.value = ''
  }

  return { qrPayload, qrImageUrl, loading, error, setFromProfile, setError, setLoading, reset }
})
