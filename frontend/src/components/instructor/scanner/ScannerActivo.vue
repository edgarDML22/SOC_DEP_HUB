<script setup>
import { ref } from 'vue'
import { QrcodeStream } from 'vue-qrcode-reader'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import ManualInput from './ManualInput.vue'

const store        = useScannerStore()
const camaraError  = ref('')
const streamReady  = ref(false)   // evita el artefacto azul antes de que la cámara arranque
const isProcessing = ref(false)

const isManual = ref(store.metodoIngreso === 'MANUAL')

function onCameraOn() {
    streamReady.value = true
}

async function onDetect(detectedCodes) {
    if (isProcessing.value || !detectedCodes?.length) return
    isProcessing.value = true
    await store.procesarCodigo(detectedCodes[0].rawValue)
    setTimeout(() => { isProcessing.value = false }, 2000)
}

function onError(err) {
    const MENSAJES = {
        NotAllowedError:      'Permiso de cámara denegado. Activa el acceso en tu navegador.',
        NotFoundError:        'No se encontró ninguna cámara en este dispositivo.',
        NotSupportedError:    'Se requiere conexión HTTPS para acceder a la cámara.',
        InsecureContextError: 'Se requiere conexión HTTPS para acceder a la cámara.',
        NotReadableError:     'La cámara ya está siendo usada por otra aplicación.',
    }
    camaraError.value = MENSAJES[err.name] ?? `Error de cámara: ${err.message}`
    streamReady.value = false
    isManual.value    = true
}

function switchToCamera() {
    isManual.value    = false
    camaraError.value = ''
    streamReady.value = false
}

async function onSubmitManual(codigo) {
    await store.procesarCodigo(codigo)
}
</script>

<template>
  <div class="space-y-4">

    <!-- Tab switcher Cámara / Manual -->
    <div class="flex bg-surface-100 p-1 rounded-2xl gap-1">
      <button
        @click="switchToCamera"
        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all focus:outline-none"
        :class="!isManual ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-400 hover:text-surface-600'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Cámara
      </button>
      <button
        @click="isManual = true"
        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all focus:outline-none"
        :class="isManual ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-400 hover:text-surface-600'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        Manual
      </button>
    </div>

    <!-- ── PANEL CÁMARA ──────────────────────────────────── -->
    <template v-if="!isManual">

      <!-- Error de permisos / hardware -->
      <div v-if="camaraError" class="bg-red-50 border border-red-100 rounded-2xl p-4 flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p class="text-xs font-semibold text-red-700 leading-relaxed">{{ camaraError }}</p>
      </div>

      <!-- Feed de cámara + overlay solo cuando el stream está activo -->
      <div v-else class="w-full aspect-square bg-surface-900 rounded-3xl overflow-hidden relative shadow-inner">

        <!-- Stream QR — ocupa todo el contenedor sin borde propio -->
        <qrcode-stream
          class="absolute inset-0 w-full h-full"
          @detect="onDetect"
          @error="onError"
          @camera-on="onCameraOn"
        />

        <!-- Spinner de inicialización (mientras streamReady = false) -->
        <Transition enter-active-class="transition-opacity duration-300" leave-active-class="transition-opacity duration-300" enter-from-class="opacity-0" leave-to-class="opacity-0">
          <div v-if="!streamReady" class="absolute inset-0 flex flex-col items-center justify-center gap-3 z-10 bg-surface-900">
            <div class="w-8 h-8 rounded-full border-2 border-white/20 border-t-white animate-spin" />
            <p class="text-white/60 text-xs font-medium">Iniciando cámara…</p>
          </div>
        </Transition>

        <!-- Overlay de visor: SOLO se monta cuando el stream está listo -->
        <template v-if="streamReady">
          <!-- Sombra perimetral -->
          <div class="absolute inset-0 pointer-events-none"
            style="background: radial-gradient(ellipse 62% 62% at 50% 50%, transparent 58%, rgba(0,0,0,0.55) 100%)"
          />

          <!-- Corner brackets — visor centrado 64% × 64% -->
          <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
            <div
              class="relative"
              style="width: 64%; height: 64%"
            >
              <!-- Lados del visor (solo corners, no borde completo) -->
              <!-- top-left -->
              <span class="absolute top-0 left-0 w-7 h-7 border-t-[3px] border-l-[3px] rounded-tl-xl transition-colors duration-300"
                :class="isProcessing ? 'border-green-400' : 'border-primary-400'" />
              <!-- top-right -->
              <span class="absolute top-0 right-0 w-7 h-7 border-t-[3px] border-r-[3px] rounded-tr-xl transition-colors duration-300"
                :class="isProcessing ? 'border-green-400' : 'border-primary-400'" />
              <!-- bottom-left -->
              <span class="absolute bottom-0 left-0 w-7 h-7 border-b-[3px] border-l-[3px] rounded-bl-xl transition-colors duration-300"
                :class="isProcessing ? 'border-green-400' : 'border-primary-400'" />
              <!-- bottom-right -->
              <span class="absolute bottom-0 right-0 w-7 h-7 border-b-[3px] border-r-[3px] rounded-br-xl transition-colors duration-300"
                :class="isProcessing ? 'border-green-400' : 'border-primary-400'" />

              <!-- Línea de escaneo -->
              <div
                v-if="!isProcessing"
                class="absolute left-2 right-2 h-0.5 rounded-full bg-primary-400 shadow-[0_0_10px_rgba(96,165,250,0.9)] animate-scan-line"
              />
            </div>
          </div>

          <!-- Overlay de procesando -->
          <Transition enter-active-class="transition-opacity duration-150" enter-from-class="opacity-0" enter-to-class="opacity-100">
            <div v-if="isProcessing" class="absolute inset-0 bg-black/50 flex items-center justify-center z-20 pointer-events-none">
              <div class="flex flex-col items-center gap-3">
                <div class="w-10 h-10 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                <p class="text-white text-xs font-bold tracking-wide">Procesando…</p>
              </div>
            </div>
          </Transition>
        </template>
      </div>

      <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest text-center">
        Asegúrate de tener buena iluminación y la cámara habilitada
      </p>
    </template>

    <!-- ── PANEL MANUAL ──────────────────────────────────── -->
    <template v-else>
      <ManualInput @submit="onSubmitManual" />
    </template>

    <!-- Spinner de envío -->
    <div v-if="store.loading" class="flex items-center justify-center gap-2 text-surface-500 text-sm font-medium py-2">
      <div class="w-4 h-4 rounded-full border-2 border-surface-300 border-t-primary-500 animate-spin" />
      Registrando asistencia…
    </div>

  </div>
</template>

<style scoped>
.animate-scan-line {
  animation: scan 2s linear infinite;
}
@keyframes scan {
  0%   { top: 0%;   opacity: 0; }
  8%   { opacity: 1; }
  92%  { opacity: 1; }
  100% { top: 100%; opacity: 0; }
}
</style>
