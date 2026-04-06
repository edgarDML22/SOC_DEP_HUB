<script setup>
import { ref } from 'vue'
import { useProfileStore } from '@/stores/profileStore'
import api from '@/services/api'
import QrCredentialModal from '@/components/socio/QrCredentialModal.vue'

const profileStore = useProfileStore();

const qrPayload = ref('');
const isQrModalOpen = ref(false);
const errorQr = ref('');

const handleClick = async (action) => {
  if (action === 'qr') {
    if (profileStore.isAccountInactive) return;

    try {
      errorQr.value = '';
      const response = await api.get('/api/v1/profile/qr-data');
      if (response.data.success) {
        qrPayload.value = response.data.data.qr_payload;
        isQrModalOpen.value = true;
      }
    } catch (error) {
      console.error('Error al generar QR:', error);
      errorQr.value = 'No se pudo generar el código QR en este momento.';
    }
  } else {
    console.log('Action:', action);
  }
};
</script>

<template>
  <main class="main-content">
    <div class="container">
      <h2>Hola, {{ profileStore.fullName }}</h2>
      <p class="subtitle">Bienvenido de vuelta al Club Deportivo</p>
      <div v-if="profileStore.isAccountInactive" class="alert-banner">
        ⚠️ Atención: El estatus de esta cuenta es <strong>{{ profileStore.statusAccount }} </strong> no puede realizar
        reservas ni consultar código QR.
      </div>
      <div v-if="errorQr" class="alert warning mt-4">
        {{ errorQr }}
      </div>

      <div class="card card-blue">
        <div class="card-header">
          <span>Próxima Reserva</span>
          <span class="status inactive">Inactivo</span>
        </div>

        <div class="torneos-body">
          <p class="empty">No hay Reservas disponibles</p>
        </div>

        <div v-if="!profileStore.isAccountInactive" class="card-actions">
          <button class="btn-gray" @click="handleClick('detalle')">Ver detalle</button>
          <button v-if="!profileStore.isAccountInactive" class="btn-blue" @click="handleClick('qr')">
            Presentar Pase QR
          </button>
        </div>
      </div>

      <h3 class="section-title">Acciones rápidas</h3>
      <div class="actions">
        <router-link v-if="!profileStore.isAccountInactive" to="/socio/reservations" class="action-card"> Hacer
          Reservación</router-link>
        <router-link to="/socio/tournaments" class="action-card"> Consultar Torneos</router-link>
        <router-link to="/socio/guests" class="action-card"> Gestionar Invitados</router-link>
        <router-link to="/socio/history" class="action-card"> Consultar Historial</router-link>
      </div>

      <div class="card torneos">
        <div class="torneos-header">
          <h3>Torneos activos</h3>
          <button class="btn-link" @click="handleClick('ver torneos')">
            Ver todos →
          </button>
        </div>

        <div class="torneos-body">
          <p class="empty">NO HAY TORNEOS ACTIVOS</p>
        </div>
      </div>

    </div>

    <!-- Modal para mostrar el QR -->
    <QrCredentialModal v-if="isQrModalOpen" :payloadText="qrPayload" @close="isQrModalOpen = false" />
  </main>
</template>


<style scoped>
.container {
  max-width: 752px;
  margin: auto;
  padding: 20px;
}

.subtitle {
  color: var(--p-surface-500);
  margin-bottom: 16px;
}

.alert.warning {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 24px;
  border: 1px solid #f87171;
  font-weight: 500;
  font-size: 0.95rem;
}

.mt-4 {
  margin-top: 1rem;
}

.card {
  background: white;
  border-radius: 16px;
  padding: 18px;
  border: 1px solid var(--p-surface-200);
  margin-bottom: 24px;
}

.card-blue {
  background: #dbeafe;
  border: 1px solid #bfdbfe;
  min-height: 299px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.status.inactive {
  background: var(--p-surface-200);
  color: var(--p-surface-500);
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
}

.card-actions {
  display: flex;
  gap: 10px;
}

.btn-gray {
  background: var(--p-surface-200);
  border-radius: var(--p-border-radius);
  padding: 6px 12px;
}

.btn-blue {
  background: var(--p-primary-600);
  color: white;
  border-radius: var(--p-border-radius);
  padding: 6px 12px;
}

.actions {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.action-card {
  height: 160px;
  border-radius: 16px;
  border: 1px solid var(--p-surface-200);
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;

  color: var(--p-surface-900);
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s ease;
}

.action-card:hover {
  background-color: var(--p-surface-100);
  border-color: #d1d5db;
}

.torneos-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-link {
  background: none;
  border: none;
  color: var(--p-primary-600);
  cursor: pointer;
}

.torneos-body {
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty {
  color: var(--p-surface-500);
  text-align: center;
}

.alert-banner {
  background-color: #fef2f2;
  color: #991b1b;
  padding: 12px 16px;
  border: 1px solid #f87171;
  border-radius: var(--p-border-radius);
  margin-bottom: 24px;
  font-size: 14px;
}
</style>