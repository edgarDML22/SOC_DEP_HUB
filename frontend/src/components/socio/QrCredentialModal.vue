<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-card">
      <header class="modal-header">
        <h3>Tu Pase QR</h3>
        <button class="close-btn" @click="$emit('close')">X</button>
      </header>

      <div class="modal-body">
        <qrcode-vue 
          :value="payloadText" 
          :size="220" 
          level="H" 
          class="responsive-qr"
        />
        <p class="qr-instruction">Muestra este código en la entrada del club.</p>
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
  }
});

defineEmits(['close']);
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.modal-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  width: 90%;
  max-width: 350px;
  text-align: center;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.modal-header h3 {
  margin: 0;
  color: #333;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.2rem;
  font-weight: bold;
  cursor: pointer;
  color: #666;
}

/* Requisito del ticket SDH-1402 */
.responsive-qr {
  max-width: 100%;
  height: auto;
  margin: 0 auto;
}

.qr-instruction {
  margin-top: 15px;
  font-size: 0.9rem;
  color: #666;
}
</style>