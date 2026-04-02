<script setup>
import { QrcodeStream } from 'vue-qrcode-reader';

const onDetect = (detectedCodes) => {
  // vue-qrcode-reader devuelve un array de objetos cuando detecta códigos
  if (detectedCodes && detectedCodes.length > 0) {
    const rawValue = detectedCodes[0].rawValue;
    // Comportamiento temporal para visualizar el escaneo
    alert(`QR Detectado:\n\n${rawValue}`);
    console.log("QR Payload completo:", rawValue);
  }
};

const onError = (err) => {
  const errorName = err.name;
  if (errorName === 'NotAllowedError') {
    alert("Permiso de cámara denegado. Habilita el acceso en tu navegador.");
  } else if (errorName === 'NotFoundError') {
    alert("No se encontró ninguna cámara en el dispositivo.");
  } else if (errorName === 'NotSupportedError' || errorName === 'InsecureContextError') {
    alert("Se requiere conexión segura (HTTPS) para el acceso a la cámara.");
  } else if (errorName === 'NotReadableError') {
    alert("La cámara ya está en uso por otra aplicación.");
  } else {
    alert(`Error de cámara inesperado: ${err.message}`);
  }
};
</script>

<template>
  <main class="scanner-page">
    <div class="scanner-container">
      <h2>Escáner de Accesos</h2>
      <p class="subtitle">Apunta el código QR del socio en el recuadro para registrar su entrada.</p>
      
      <div class="camera-wrapper">
        <qrcode-stream 
          @detect="onDetect" 
          @error="onError"
        ></qrcode-stream>
        <div class="scanner-overlay">
          <div class="scan-area"></div>
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

.instructions {
  color: #9ca3af;
  font-size: 0.85rem;
  font-weight: 500;
}
</style>
