<template>
  <div class="qr-view-container">
    <div class="qr-card">
      <header class="qr-header">
        <h2>Pase de Acceso</h2>
        <p>Muestra este código.</p>
      </header>

      <div class="qr-body">
        <qrcode-vue 
          :value="qrPayload" 
          :size="250" 
          level="H" 
          class="responsive-qr"
        />
      </div>

      <div class="qr-footer">
        <p class="qr-instruction">El código se actualiza por tu seguridad.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import QrcodeVue from 'qrcode.vue';
import { useProfileStore } from '@/stores/profiles/socioStore';

const profileStore = useProfileStore();

const qrPayload = computed(() => {
  
  return `SOCDEP-ACCESO-${profileStore.userInitials}-${Date.now()}`;
});
</script>

<style scoped>

.qr-view-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(100vh - 70px); 
  background-color: #f9fafb; 
  padding: 1.5rem;
}


.qr-card {
  background: white;
  padding: 2rem 1.5rem;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  width: 100%;
  max-width: 380px;
  text-align: center;
  border: 1px solid #f3f4f6;
}

.qr-header {
  margin-bottom: 2rem;
}

.qr-header h2 {
  margin: 0 0 0.5rem 0;
  color: #111827;
  font-size: 1.5rem;
  font-weight: 700;
}

.qr-header p {
  margin: 0;
  color: #6b7280;
  font-size: 0.95rem;
}

.qr-body {
  background: #ffffff;
  padding: 1.5rem;
  border-radius: 12px;
  border: 2px dashed #e5e7eb;
  display: inline-block;
  margin-bottom: 1.5rem;
}


.responsive-qr {
  max-width: 100%;
  height: auto !important;
  margin: 0 auto;
  display: block;
}

.qr-footer {
  border-top: 1px solid #e5e7eb;
  padding-top: 1rem;
}

.qr-instruction {
  margin: 0;
  font-size: 0.85rem;
  color: #9ca3af;
  font-weight: 500;
}
</style>