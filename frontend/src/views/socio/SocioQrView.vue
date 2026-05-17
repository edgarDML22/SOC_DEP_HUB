<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useProfileStore } from '@/stores/profiles/socioStore'
import { useQrStore } from '@/stores/profiles/qrStore'
import { useAlerts } from '@/composables/useAlerts'

const router = useRouter()
const profileStore = useProfileStore()
const qrStore = useQrStore()
const { toastInfo } = useAlerts()

const qrPayload = computed(() => qrStore.qrPayload)
const qrImageUrl = computed(() => qrStore.qrImageUrl)
const loading = computed(() => qrStore.loading)
const error = computed(() => qrStore.error)

const copiarImagenAlPortapapeles = async (url) => {
  try {
    toastInfo('Procesando', 'Preparando imagen...', 'info')
    const response = await fetch(url)
    const blob = await response.blob()
    await navigator.clipboard.write([
      new ClipboardItem({ [blob.type]: blob })
    ])
    toastInfo('¡Listo!', 'Imagen del QR copiada al portapapeles', 'success')
  } catch (err) {
    toastInfo('Error', 'Usa clic derecho para copiar la imagen.', 'error')
  }
}
</script>

<template>
  <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8 pt-4 lg:pt-6 font-sans">
    <div class="max-w-3xl mx-auto flex flex-col gap-6">

      <button @click="router.back()"
        class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-2 focus:outline-none w-fit">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m15 18-6-6 6-6" />
        </svg>
        Volver
      </button>

      <div class="flex flex-col gap-2">
        <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">Mi Código QR</h2>
        <p class="text-surface-500 text-sm md:text-base m-0 font-medium">Usa este código para registrar tu asistencia en
          actividades y reservaciones.</p>
      </div>

      <div
        class="bg-white rounded-2xl p-6 md:p-10 shadow-sm border border-surface-200 flex flex-col items-center text-center relative overflow-hidden">

        <div v-if="profileStore.isAccountInactive"
          class="absolute inset-0 bg-white/90 backdrop-blur-sm z-20 flex flex-col items-center justify-center p-8 text-red-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 15v2m0 0v2m0-2h2m-2 0H10m11 3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h3 class="text-xl font-bold mb-2">Acceso Restringido</h3>
          <p class="font-medium text-sm text-surface-600">Tu cuenta figura como <strong>{{ profileStore.statusAccount
          }}</strong>. Contacta a administración para activar tu pase.</p>
        </div>

        <div v-if="loading" class="py-20 flex flex-col items-center gap-4">
          <div class="w-12 h-12 border-4 border-surface-100 border-t-primary-600 rounded-full animate-spin"></div>
          <p class="text-surface-500 font-medium animate-pulse">Obteniendo credencial...</p>
        </div>

        <div v-else-if="qrPayload" class="w-full flex flex-col items-center">

          <div v-if="qrPayload" class="mb-4 text-center">
            <span class="text-2xl md:text-3xl font-normal text-surface-900 tracking-widest uppercase font-sans">
              {{ qrPayload }}
            </span>
          </div>

          <div
            class="bg-surface-50 p-6 border-2 border-dashed border-surface-300 rounded-3xl mb-8 flex justify-center w-fit">
            <img :src="qrImageUrl" alt="Mi Código QR"
              class="w-56 h-56 md:w-64 md:h-64 rounded-xl bg-white shadow-inner object-contain" />
          </div>

          <div class="max-w-sm flex flex-col gap-6 w-full">
            <div class="flex flex-col gap-2">
              <div
                class="inline-flex items-center justify-center gap-2 px-4 py-1.5 rounded-full bg-green-50 text-green-700 border border-green-100 text-xs font-bold uppercase tracking-wider mx-auto">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                Activo
              </div>
              <p class="text-surface-600 text-sm font-medium leading-relaxed mt-2">
                Este código es personal e intransferible.
              </p>
            </div>

            <div class="flex flex-col gap-3">
              <button @click="copiarImagenAlPortapapeles(qrImageUrl)"
                class="w-full rounded-xl px-4 py-3 font-bold transition-all flex items-center justify-center gap-2 active:scale-95 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white shadow-md shadow-primary-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                  <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                </svg>
                Copiar Código QR
              </button>

            </div>
          </div>

        </div>

        <div v-else class="py-12 flex flex-col items-center gap-4">
          <div class="p-4 bg-red-50 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <p class="text-red-600 font-bold">{{ error || 'Error de conexión' }}</p>
          <button @click="fetchQrData" class="text-primary-600 font-bold underline">Reintentar</button>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* Transición suave para el hover de los botones gradientes */
button {
  background-size: 200% auto;
}

button:hover {
  background-position: right center;
}
</style>