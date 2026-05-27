<script setup>
import { ref, computed } from 'vue'
import { QrcodeStream } from 'vue-qrcode-reader'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import ManualInput from './ManualInput.vue'

const store = useScannerStore()
const camaraError        = ref('')
const streamReady        = ref(false)
const isProcessing       = ref(false)
const detected           = ref(false)   // flash inmediato al leer el QR
const solicitandoPermiso = ref(false)
const streamKey          = ref(0)

const isManual = ref(store.metodoIngreso === 'MANUAL')
const scannerPaused = computed(() => store.aforoLleno)

// ── Web Audio beep ─────────────────────────────────────────────────────────
function playBeep(type = 'success') {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)()

        const osc  = ctx.createOscillator()
        const gain = ctx.createGain()
        osc.connect(gain)
        gain.connect(ctx.destination)

        if (type === 'success') {
            // Doble tono ascendente limpio — clásico beep de escáner
            osc.type      = 'sine'
            osc.frequency.setValueAtTime(880, ctx.currentTime)
            osc.frequency.setValueAtTime(1320, ctx.currentTime + 0.08)
            gain.gain.setValueAtTime(0.35, ctx.currentTime)
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.22)
            osc.start(ctx.currentTime)
            osc.stop(ctx.currentTime + 0.22)
        } else {
            // Tono descendente breve — indica problema
            osc.type      = 'sawtooth'
            osc.frequency.setValueAtTime(520, ctx.currentTime)
            osc.frequency.exponentialRampToValueAtTime(180, ctx.currentTime + 0.18)
            gain.gain.setValueAtTime(0.25, ctx.currentTime)
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.22)
            osc.start(ctx.currentTime)
            osc.stop(ctx.currentTime + 0.22)
        }

        osc.onended = () => ctx.close()
    } catch {
        // Silencio en entornos sin Web Audio (SSR, safari restrictivo, etc.)
    }
}

// ── Handlers de cámara ─────────────────────────────────────────────────────
function onCameraOn() {
    streamReady.value = true
}

async function onDetect(detectedCodes) {
    if (isProcessing.value || !detectedCodes?.length) return

    // Flash inmediato de detección
    detected.value = true
    playBeep('success')
    setTimeout(() => { detected.value = false }, 500)

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
}

function switchToCamera() {
    camaraError.value = ''
    streamReady.value = false
    isManual.value    = false
}

async function solicitarPermiso() {
    solicitandoPermiso.value = true
    try {
        if (navigator.permissions) {
            const result = await navigator.permissions.query({ name: 'camera' })
            if (result.state === 'denied') {
                camaraError.value = 'Permiso de cámara denegado. Actívalo manualmente en Configuración del sitio en tu navegador.'
                solicitandoPermiso.value = false
                return
            }
        }
        const stream = await navigator.mediaDevices.getUserMedia({ video: true })
        stream.getTracks().forEach(t => t.stop())
        camaraError.value = ''
        streamReady.value = false
        streamKey.value++
    } catch (err) {
        if (err.name === 'NotAllowedError') {
            camaraError.value = 'Permiso de cámara denegado. Actívalo manualmente en Configuración del sitio en tu navegador.'
        } else {
            camaraError.value = 'No se pudo acceder a la cámara. Verifica que esté disponible.'
        }
    } finally {
        solicitandoPermiso.value = false
    }
}

function switchToManual() {
    isManual.value = true
}

async function onSubmitManual(codigo) {
    playBeep('success')
    await store.procesarCodigo(codigo)
}
</script>

<template>
  <div class="space-y-4">

    <!-- Banner de aforo lleno -->
    <Transition
      enter-active-class="transition-all duration-300"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
    >
      <div v-if="store.aforoLleno" class="flex items-center gap-2.5 px-4 py-3 bg-red-50 border border-red-200 rounded-2xl">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-xs font-semibold text-red-700">Aforo máximo alcanzado. El escáner está deshabilitado.</p>
      </div>
    </Transition>

    <!-- Tab switcher -->
    <div class="flex bg-primary-950/5 p-1 rounded-2xl gap-1">
      <button
        @click="switchToCamera"
        :disabled="store.aforoLleno"
        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all duration-200 focus:outline-none"
        :class="!isManual
          ? 'bg-primary-600 text-white shadow-sm'
          : 'text-primary-800/50 hover:text-primary-700 hover:bg-primary-600/8'"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Cámara
      </button>
      <button
        @click="switchToManual"
        :disabled="store.aforoLleno"
        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all duration-200 focus:outline-none"
        :class="isManual
          ? 'bg-primary-600 text-white shadow-sm'
          : 'text-primary-800/50 hover:text-primary-700 hover:bg-primary-600/8'"
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
      <div v-if="camaraError" class="bg-red-50 border border-red-100 rounded-2xl p-4 space-y-3">
        <div class="flex items-start gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-xs font-semibold text-red-700 leading-relaxed">{{ camaraError }}</p>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <button
            @click="solicitarPermiso"
            :disabled="solicitandoPermiso"
            class="flex items-center justify-center gap-1.5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold active:scale-[0.98] transition-all focus:outline-none disabled:opacity-60"
          >
            <template v-if="solicitandoPermiso">
              <span class="w-3 h-3 rounded-full border-2 border-white/30 border-t-white animate-spin" />
              Solicitando…
            </template>
            <template v-else>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Solicitar permisos
            </template>
          </button>
          <button
            @click="switchToManual"
            class="py-2.5 rounded-xl bg-primary-50 border border-primary-200 text-xs font-bold text-primary-700 hover:bg-primary-100 active:scale-[0.98] transition-all focus:outline-none"
          >
            Usar entrada manual
          </button>
        </div>
      </div>

      <!-- Feed de cámara -->
      <div v-else class="w-full aspect-square bg-surface-900 rounded-3xl overflow-hidden relative shadow-inner">

        <qrcode-stream
          :key="streamKey"
          class="absolute inset-0 w-full h-full"
          :paused="scannerPaused"
          @detect="onDetect"
          @error="onError"
          @camera-on="onCameraOn"
        />

        <!-- Spinner de inicialización -->
        <Transition
          enter-active-class="transition-opacity duration-300"
          leave-active-class="transition-opacity duration-300"
          enter-from-class="opacity-0"
          leave-to-class="opacity-0"
        >
          <div v-if="!streamReady" class="absolute inset-0 flex flex-col items-center justify-center gap-3 z-10 bg-surface-900">
            <div class="w-8 h-8 rounded-full border-2 border-white/20 border-t-white animate-spin" />
            <p class="text-white/60 text-xs font-medium">Iniciando cámara…</p>
          </div>
        </Transition>

        <!-- Overlay del visor -->
        <template v-if="streamReady">
          <!-- Viñeta oscura en los bordes -->
          <div class="absolute inset-0 pointer-events-none"
            style="background: radial-gradient(ellipse 62% 62% at 50% 50%, transparent 58%, rgba(0,0,0,0.55) 100%)"
          />

          <!-- Flash de detección: pulso verde que cubre todo el visor -->
          <Transition name="detect-flash">
            <div
              v-if="detected"
              class="absolute inset-0 z-30 pointer-events-none rounded-3xl"
              style="background: rgba(34,197,94,0.28);"
            />
          </Transition>

          <!-- Marco esquinero + línea de escaneo -->
          <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
            <div class="relative" style="width: 64%; height: 64%">

              <!-- Esquinas — se agrandan y cambian a verde en detección -->
              <span
                class="absolute top-0 left-0 border-t-[4px] border-l-[4px] rounded-tl-xl transition-all duration-200"
                :class="detected
                  ? 'w-10 h-10 border-green-400 shadow-[0_0_14px_rgba(74,222,128,0.9)]'
                  : isProcessing
                    ? 'w-7 h-7 border-green-400'
                    : 'w-7 h-7 border-primary-400'"
              />
              <span
                class="absolute top-0 right-0 border-t-[4px] border-r-[4px] rounded-tr-xl transition-all duration-200"
                :class="detected
                  ? 'w-10 h-10 border-green-400 shadow-[0_0_14px_rgba(74,222,128,0.9)]'
                  : isProcessing
                    ? 'w-7 h-7 border-green-400'
                    : 'w-7 h-7 border-primary-400'"
              />
              <span
                class="absolute bottom-0 left-0 border-b-[4px] border-l-[4px] rounded-bl-xl transition-all duration-200"
                :class="detected
                  ? 'w-10 h-10 border-green-400 shadow-[0_0_14px_rgba(74,222,128,0.9)]'
                  : isProcessing
                    ? 'w-7 h-7 border-green-400'
                    : 'w-7 h-7 border-primary-400'"
              />
              <span
                class="absolute bottom-0 right-0 border-b-[4px] border-r-[4px] rounded-br-xl transition-all duration-200"
                :class="detected
                  ? 'w-10 h-10 border-green-400 shadow-[0_0_14px_rgba(74,222,128,0.9)]'
                  : isProcessing
                    ? 'w-7 h-7 border-green-400'
                    : 'w-7 h-7 border-primary-400'"
              />

              <!-- Línea de escaneo — se oculta mientras se procesa -->
              <div
                v-if="!isProcessing && !detected"
                class="absolute left-2 right-2 h-0.5 rounded-full bg-primary-400 shadow-[0_0_10px_rgba(96,165,250,0.9)] animate-scan-line"
              />
            </div>
          </div>

          <!-- Overlay de procesando (después del flash) -->
          <Transition
            enter-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
          >
            <div v-if="isProcessing && !detected" class="absolute inset-0 bg-black/50 flex items-center justify-center z-20 pointer-events-none">
              <div class="flex flex-col items-center gap-3">
                <div class="w-10 h-10 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                <p class="text-white text-xs font-bold tracking-wide">Procesando…</p>
              </div>
            </div>
          </Transition>
        </template>
      </div>

      <p v-if="streamReady && !camaraError" class="text-[11px] font-semibold text-slate-600 text-center uppercase">
        Asegúrate de tener buena iluminación y la cámara habilitada
      </p>
    </template>

    <!-- ── PANEL MANUAL ──────────────────────────────────── -->
    <template v-else>
      <ManualInput :disabled="store.aforoLleno" @submit="onSubmitManual" />
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

/* Flash de detección */
.detect-flash-enter-active { transition: opacity 0.05s ease-out; }
.detect-flash-leave-active { transition: opacity 0.35s ease-out; }
.detect-flash-enter-from   { opacity: 0; }
.detect-flash-leave-to     { opacity: 0; }
</style>
