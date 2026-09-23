<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { QrcodeStream } from 'vue-qrcode-reader';
import api from '@/services/api';
import { IconArrowLeft } from '@/components/icons';

const route = useRoute();
const router = useRouter();
const id_sesion = route.params.id || route.query.sesion || ''; // Verifica tanto parámetros como query params
const fase = ref('ingreso');
const id_instructor = route.params.id_instructor || route.query.instructor || '';

const isProcessing = ref(false);
const errorAlert = ref('');
const successAlert = ref('');
const isErrorState = ref(false);
let successTimer = null;

const playSuccessBeep = () => {
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();
    oscillator.type = 'sine';
    oscillator.frequency.value = 880;
    oscillator.connect(gainNode);
    gainNode.connect(audioCtx.destination);
    oscillator.start();
    gainNode.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.2);
    setTimeout(() => oscillator.stop(), 200);
  } catch (e) {
    console.warn("Audio Web API no soportado");
  }
};

const playErrorSound = () => {
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();
    oscillator.type = 'sawtooth';
    oscillator.frequency.value = 150;
    oscillator.connect(gainNode);
    gainNode.connect(audioCtx.destination);
    oscillator.start();
    gainNode.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.5);
    setTimeout(() => oscillator.stop(), 500);
  } catch (e) {
    console.warn("Audio Web API no soportado");
  }
};

const onDetect = async (detectedCodes) => {
  if (isProcessing.value) return;
  if (!detectedCodes || detectedCodes.length === 0) return;

  const rawValue = detectedCodes[0].rawValue;
  isProcessing.value = true;
  isErrorState.value = false;
  errorAlert.value = '';

  if (successTimer) clearTimeout(successTimer);

  try {
    if (!id_sesion) {
      throw new Error("No hay id_sesion en la ruta.");
    }

    // Enviamos al backend los datos que solicita RegisterEventController
    const payload = {
      id: rawValue, // Asume que el QR contiene el id, si está encriptado requerirá ajuste en backend o validarlo antes
      id_sesion: id_sesion,
      id_instructor: id_instructor,
      fase: fase.value
    };

    const response = await api.post('instructor/register-event', payload);
    playSuccessBeep();

    successAlert.value = response.data.message || 'Registro exitoso';

    // Alerta viva por 2 segundos
    successTimer = setTimeout(() => {
      successAlert.value = '';
    }, 2000);

  } catch (error) {
    playErrorSound();
    isErrorState.value = true;
    console.log(error.response?.data?.message);
    errorAlert.value = error.response?.data?.message || 'Error al validar el código QR.';
  } finally {
    // Cooldown de 3 segundos
    setTimeout(() => {
      isProcessing.value = false;
      isErrorState.value = false; // reset error border when ready again
    }, 3000);
  }
};

const onError = (err) => {
  const errorName = err.name;

  // Opcional: Mostramos el borde del escáner en rojo si le negaron los permisos
  isErrorState.value = true;

  if (errorName === 'NotAllowedError') {
    errorAlert.value = "Permiso de cámara denegado. Habilita el acceso en tu navegador.";
  } else if (errorName === 'NotFoundError') {
    errorAlert.value = "No se encontró ninguna cámara en el dispositivo.";
  } else if (errorName === 'NotSupportedError' || errorName === 'InsecureContextError') {
    errorAlert.value = "Se requiere conexión segura (HTTPS) para el acceso a la cámara.";
  } else if (errorName === 'NotReadableError') {
    errorAlert.value = "La cámara ya está en uso por otra aplicación.";
  } else {
    errorAlert.value = `Error de cámara inesperado: ${err.message}`;
  }
};
</script>

<template>
  <main class="home-instructor flex items-center justify-center pt-8">
    <div class="w-full max-w-md bg-white border border-surface-200 rounded-3xl p-8 flex flex-col items-center text-center shadow-xl shadow-surface-900/5 relative mt-12 md:mt-0">
      
      <!-- Botón Volver -->
      <button @click="router.back()" class="absolute top-6 left-6 flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors focus:outline-none w-fit group">
          <IconArrowLeft class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" /> Volver
      </button>

      <div class="w-16 h-16 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center mb-6 mt-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
        </svg>
      </div>

      <h2 class="text-2xl md:text-3xl font-bold text-surface-900 tracking-tight mb-2">Escáner de Accesos</h2>
      <p class="text-sm text-surface-500 font-medium mb-8 leading-relaxed px-4">
        Apunta el código QR del socio en el recuadro para registrar su entrada o salida.
      </p>

      <!-- Selector de Fase -->
      <div class="flex bg-surface-100 p-1.5 rounded-2xl gap-1 mb-8 w-full max-w-[280px]">
        <label class="flex-1 py-3 px-4 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-2"
          :class="fase === 'ingreso' ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-400 hover:text-surface-600'">
          <input type="radio" value="ingreso" v-model="fase" class="hidden" /> 
          <span class="w-2 h-2 rounded-full" :class="fase === 'ingreso' ? 'bg-primary-600' : 'bg-surface-300'"></span>
          Ingreso
        </label>
        <label class="flex-1 py-3 px-4 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-2"
          :class="fase === 'cierre' ? 'bg-white text-primary-600 shadow-sm' : 'text-surface-400 hover:text-surface-600'">
          <input type="radio" value="cierre" v-model="fase" class="hidden" /> 
          <span class="w-2 h-2 rounded-full" :class="fase === 'cierre' ? 'bg-primary-600' : 'bg-surface-300'"></span>
          Cierre
        </label>
      </div>

      <!-- Alerta de Extio Flotante -->
      <transition name="fade">
        <div v-if="successAlert" class="absolute top-4 inset-x-8 bg-green-500 text-white p-4 rounded-2xl font-black text-sm shadow-lg shadow-green-500/30 z-50 flex items-center gap-3">
          <span class="text-xl">✅</span> {{ successAlert }}
        </div>
      </transition>

      <!-- Alerta de Error -->
      <div v-if="errorAlert" class="w-full bg-red-50 border border-red-100 p-4 rounded-2xl flex items-start gap-3 text-left mb-6 relative">
        <span class="text-lg">❌</span>
        <div class="flex flex-col gap-1">
          <p class="text-xs font-black text-red-700 leading-tight">{{ errorAlert }}</p>
          <span class="text-[10px] font-bold text-red-400 uppercase tracking-tight">Sesión: {{ id_sesion || 'Ninguna' }}</span>
        </div>
        <button @click="errorAlert = ''" class="absolute top-2 right-2 text-red-300 hover:text-red-500 text-xl font-black">&times;</button>
      </div>

      <div class="w-full aspect-square bg-surface-900 rounded-3xl overflow-hidden relative shadow-inner mb-8">
        <qrcode-stream @detect="onDetect" @error="onError"></qrcode-stream>
        
        <!-- Overlay del Escáner -->
        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
          <!-- Sombras exteriores -->
          <div class="absolute inset-0 bg-black/40"></div>
          
          <!-- Recuadro de escaneo -->
          <div class="w-[70%] h-[70%] relative z-10">
            <!-- Hueco transparente -->
            <div class="absolute inset-0 bg-transparent mix-blend-multiply"></div>
            
            <!-- Bordes del recuadro -->
            <div class="absolute inset-0 border-2 rounded-3xl transition-colors duration-300"
              :class="{ 
                'border-primary-500 shadow-[0_0_0_2px_rgba(59,130,246,0.3)]': !isErrorState && !successAlert,
                'border-red-500 shadow-[0_0_0_4px_rgba(239,68,68,0.4)]': isErrorState,
                'border-green-500 shadow-[0_0_0_4px_rgba(34,197,94,0.4)]': successAlert 
              }">
              
              <!-- Esquinas -->
              <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 rounded-tl-xl" :class="isErrorState ? 'border-red-500' : (successAlert ? 'border-green-500' : 'border-primary-500')"></div>
              <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 rounded-tr-xl" :class="isErrorState ? 'border-red-500' : (successAlert ? 'border-green-500' : 'border-primary-500')"></div>
              <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 rounded-bl-xl" :class="isErrorState ? 'border-red-500' : (successAlert ? 'border-green-500' : 'border-primary-500')"></div>
              <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 rounded-br-xl" :class="isErrorState ? 'border-red-500' : (successAlert ? 'border-green-500' : 'border-primary-500')"></div>
              
              <!-- Línea de escaneo animada -->
              <div v-if="!isProcessing && !successAlert" class="absolute top-0 left-0 w-full h-0.5 bg-primary-500 shadow-[0_0_15px_rgba(59,130,246,0.8)] animate-scan-line"></div>
            </div>
          </div>
        </div>
      </div>

      <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest px-8">
        Asegúrate de dar permisos de cámara y tener buena iluminación.
      </p>
    </div>
  </main>
</template>

<style scoped>
.animate-scan-line {
  animation: scan 2s linear infinite;
}

@keyframes scan {
  0% { top: 0%; opacity: 0; }
  10% { opacity: 1; }
  90% { opacity: 1; }
  100% { top: 100%; opacity: 0; }
}

.fade-enter-active, .fade-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.fade-enter-from { opacity: 0; transform: translate(-50%, -20px) scale(0.9); }
.fade-leave-to { opacity: 0; transform: translate(-50%, -10px) scale(0.95); }
</style>
