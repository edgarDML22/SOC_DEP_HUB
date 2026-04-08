<script setup>
import { ref } from 'vue';
import { QrcodeStream } from 'vue-qrcode-reader';
import api from '@/services/api';

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
    const response = await api.post('/api/v1/asistencia/validar-qr', { qr_payload: rawValue });
    playSuccessBeep();
    
    successAlert.value = `¡Acceso concedido! ${response.data.nombre_completo}`;
    
    // Alerta viva por 2 segundos
    successTimer = setTimeout(() => {
      successAlert.value = '';
    }, 2000);
    
  } catch (error) {
    playErrorSound();
    isErrorState.value = true;
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
  <main class="scanner-page">
    <div class="scanner-container">
      <h2>Escáner de Accesos</h2>
      <p class="subtitle">Apunta el código QR del socio en el recuadro para registrar su entrada.</p>
      
      <!-- Alerta de Extio Flotante -->
      <transition name="fade">
        <div v-if="successAlert" class="floating-alert success">
          ✅ {{ successAlert }}
        </div>
      </transition>

      <!-- Alerta de Error Manual -->
      <div v-if="errorAlert" class="static-alert error">
        <span>❌ {{ errorAlert }}</span>
        <button @click="errorAlert = ''" class="close-btn">&times;</button>
      </div>

      <div class="camera-wrapper">
        <qrcode-stream 
          @detect="onDetect" 
          @error="onError"
        ></qrcode-stream>
        <div class="scanner-overlay">
          <div :class="['scan-area', { 'error-border': isErrorState, 'success-border': successAlert }]"></div>
        </div>
      </div>

      <div class="instructions">
        <p>Asegúrate de dar permisos de cámara al navegador y de tener buena iluminación para un escaneo rápido.</p>
      </div>
    </div>
  </main>
</template>

<style scoped>
.scanner-page {
  padding: 1rem;
  background-color: #f9fafb;
  min-height: calc(100vh - 70px);
  display: flex;
  justify-content: center;
  align-items: flex-start;
}

.scanner-container {
  max-width: 500px;
  width: 100%;
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  text-align: center;
  margin-top: 1rem;
  border: 1px solid #f3f4f6;
}

.scanner-container h2 {
  margin: 0 0 0.5rem 0;
  color: #111827;
  font-size: 1.5rem;
  font-weight: 700;
}

.subtitle {
  color: #6b7280;
  margin-bottom: 1.5rem;
  font-size: 0.95rem;
}

.camera-wrapper {
  width: 100%;
  aspect-ratio: 1 / 1;
  background-color: #111827;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 1.5rem;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.camera-wrapper .qrcode-stream-wrapper {
  width: 100%;
  height: 100%;
}

.scanner-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: inset 0 0 0 1000px rgba(0, 0, 0, 0.4);
}

.scan-area {
  width: 60%;
  height: 60%;
  border: 3px solid #2563eb;
  border-radius: 12px;
  box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.5); /* Oscurece el exterior del recuadro */
  position: relative;
}

/* Esquinas destacadas opcionales */
.scan-area::before, .scan-area::after {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  border-color: #3b82f6;
  border-style: solid;
}

.scan-area::before {
  top: -3px; left: -3px;
  border-width: 3px 0 0 3px;
}

.scan-area::after {
  bottom: -3px; right: -3px;
  border-width: 0 3px 3px 0;
}

.error-border {
  border-color: #ef4444 !important;
}
.error-border::before, .error-border::after {
  border-color: #ef4444 !important;
}

.success-border {
  border-color: #10b981 !important;
}
.success-border::before, .success-border::after {
  border-color: #10b981 !important;
}

.floating-alert {
  position: absolute;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  background-color: #d1fae5;
  color: #065f46;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: bold;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  z-index: 1000;
  white-space: nowrap;
}

.static-alert {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 1rem;
  border: 1px solid #f87171;
  display: flex;
  justify-content: space-between;
  align-items: center;
  text-align: left;
}

.close-btn {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  color: #b91c1c;
  cursor: pointer;
  line-height: 1;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.instructions {
  color: #9ca3af;
  font-size: 0.85rem;
  font-weight: 500;
}
</style>
