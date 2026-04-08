<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">

      <!-- Header -->
      <header class="modal-header">
        <div class="header-left">
          <div class="qr-icon-wrap">
            <svg class="qr-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="7" height="7" rx="1"/>
              <rect x="14" y="3" width="7" height="7" rx="1"/>
              <rect x="3" y="14" width="7" height="7" rx="1"/>
              <path d="M14 14h1v1h-1zM17 14h1v1h-1zM14 17h1v1h-1zM17 17h1v1h-1zM20 14v.5M20 17h.5M20 20H14v-3"/>
            </svg>
          </div>
          <div>
            <h3 class="modal-title">Tu Pase QR</h3>
            <p class="modal-subtitle">Pase de acceso al club</p>
          </div>
        </div>
        <button class="close-btn" @click="$emit('close')" aria-label="Cerrar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </header>

      <!-- Body -->
      <div class="modal-body">

        <!-- Estado: Cargando -->
        <div v-if="isLoading" class="qr-loading">
          <div class="spinner"></div>
          <p>Generando código QR...</p>
        </div>

        <!-- Estado: QR listo -->
        <div v-else class="qr-content">
          <div class="qr-wrapper">
            <qrcode-vue
              :value="payloadText"
              :size="200"
              level="H"
              class="qr-canvas"
            />
          </div>
          <p class="qr-instruction">
            Muestra este código en la entrada del club para registrar tu acceso.
          </p>
          <div class="qr-badge">
            <span class="badge-dot"></span>
            Válido por sesión activa
          </div>
        </div>

      </div>

    </div>
  </div>
</template>

<script setup>
import QrcodeVue from 'qrcode.vue';

defineProps({
  payloadText: {
    type: String,
    required: true
  },
  isLoading: {
    type: Boolean,
    default: false
  }
});

defineEmits(['close']);
</script>

<style scoped>
/* Overlay */
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  padding: 1rem;
}

/* Card */
.modal-card {
  background: white;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.18);
  width: 100%;
  max-width: 360px;
  overflow: hidden;
  animation: slide-up 0.25s ease;
}

@keyframes slide-up {
  from { transform: translateY(20px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

/* Header */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 20px 16px;
  border-bottom: 1px solid #f1f5f9;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.qr-icon-wrap {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #dbeafe, #ede9fe);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.qr-icon {
  width: 22px;
  height: 22px;
  color: #4f46e5;
}

.modal-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}

.modal-subtitle {
  margin: 0;
  font-size: 0.78rem;
  color: #64748b;
}

.close-btn {
  background: #f1f5f9;
  border: none;
  border-radius: 8px;
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #64748b;
  transition: background 0.15s, color 0.15s;
  flex-shrink: 0;
}

.close-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

/* Body */
.modal-body {
  padding: 24px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* Loading */
.qr-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  padding: 32px 0;
  color: #64748b;
  font-size: 0.9rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* QR content */
.qr-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  width: 100%;
}

.qr-wrapper {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Forzar que qrcode-vue no rompa el layout */
.qr-canvas :deep(canvas) {
  display: block;
  max-width: 100%;
  height: auto !important;
}

.qr-instruction {
  margin: 0;
  font-size: 0.85rem;
  color: #64748b;
  text-align: center;
  line-height: 1.5;
}

.qr-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #f0fdf4;
  color: #15803d;
  font-size: 0.78rem;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 999px;
  border: 1px solid #bbf7d0;
}

.badge-dot {
  width: 7px;
  height: 7px;
  background: #22c55e;
  border-radius: 50%;
  display: inline-block;
  animation: pulse 1.5s ease infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}
</style>