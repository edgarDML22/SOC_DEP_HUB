<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useProfileStore } from '@/stores/profiles/socioStore'
import { useAlerts } from '@/composables/useAlerts'
import { IconBaby } from '@/components/icons'

const profileStore = useProfileStore()
const { showLoading, closeLoading, successModal, errorModal } = useAlerts()

const miembros = ref([])
const idSeleccionado = ref(null)
const loading = ref(false)

// Cargar miembros familiares
const cargarMiembros = async () => {
  try {
    const res = await api.get('ludoteca/list', {
      params: {
        id_socio: profileStore.profileData?.id_socio
      }
    })
    miembros.value = res.data
  } catch (error) {
    errorModal('Error', 'No se pudieron cargar los miembros familiares.')
  }
}

// Registrar en ludoteca
const registrar = async () => {
  if (!idSeleccionado.value) {
    errorModal('Campo requerido', 'Por favor selecciona un menor antes de continuar.')
    return
  }

  loading.value = true
  showLoading('Registrando...')

  try {
    const res = await api.post('/ludoteca/register', {
      id_miembro: idSeleccionado.value,
      id_socio: profileStore.profileData?.id_socio
    })

    const msg = res.data.message || ''
    closeLoading()

    if (msg.toLowerCase().includes('ingreso registrado')) {
      await successModal('¡Registro exitoso!', msg || 'El menor fue ingresado a la ludoteca correctamente.')
    } else {
      await errorModal('Aviso', msg || 'No se pudo completar el registro.')
    }

    idSeleccionado.value = null

  } catch (error) {
    closeLoading()
    const msg =
      error.response?.data?.message ||
      error.response?.data?.error ||
      'Ocurrió un error al intentar registrar. Inténtalo de nuevo.'
    await errorModal('Error al registrar', msg)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  cargarMiembros()
})
</script>

<template>
  <div class="wrapper">
    <div class="card shadow-2xl">
      <!-- HEADER -->
      <div class="header">
        <div class="header-icon">
          <IconBaby class="w-8 h-8 text-white" />
        </div>
        <h2>Registro Ludoteca</h2>
        <p class="subtitle">Selecciona al menor para ingresar</p>
      </div>

      <div class="form">
        <!-- SECCIÓN MENOR -->
        <div class="section">
          <label class="section-label">¿Quién ingresará hoy?</label>
          
          <div v-if="miembros.length > 0" class="kids-grid">
            <div 
              v-for="m in miembros" 
              :key="m.id_miembro"
              class="kid-card"
              :class="{ 'selected': idSeleccionado === m.id_miembro }"
              @click="idSeleccionado = m.id_miembro"
            >
              <div class="avatar-box">
                <IconBaby class="avatar-icon" />
                <div class="check-badge">
                  <i class="pi pi-check"></i>
                </div>
              </div>
              <span class="kid-name">{{ m.nombre_completo }}</span>
            </div>
          </div>
          
          <div v-else-if="!loading" class="empty-state">
            <div class="empty-icon">
              <i class="pi pi-users text-4xl"></i>
            </div>
            <p>No se encontraron menores registrados en tu cuenta.</p>
          </div>
        </div>

        <!-- BOTÓN -->
        <button 
          class="btn-submit" 
          @click="registrar"
          :disabled="loading || !idSeleccionado"
        >
          <span v-if="!loading">Ingresar al Club</span>
          <span v-else class="flex items-center justify-center gap-3">
            <i class="pi pi-spin pi-spinner"></i>
            Registrando...
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ESTILOS PREMIUM */
.wrapper {
  min-height: 80vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  background: radial-gradient(circle at top right, #f8fafc, #f1f5f9);
}

.card {
  background: white;
  padding: 0;
  border-radius: 32px;
  width: 100%;
  max-width: 440px;
  overflow: hidden;
  box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.7);
  animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.header {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  padding: 40px 20px 30px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  position: relative;
}

.header::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 40px;
  background: linear-gradient(to top, rgba(0,0,0,0.05), transparent);
  pointer-events: none;
}

.header-icon {
  background: rgba(255, 255, 255, 0.2);
  padding: 14px;
  border-radius: 20px;
  backdrop-filter: blur(8px);
  box-shadow: 0 8px 16px rgba(0,0,0,0.1);
  margin-bottom: 4px;
}

h2 {
  color: white;
  margin: 0;
  font-size: 28px;
  font-weight: 900;
  letter-spacing: -1px;
}

.subtitle {
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
  font-size: 14px;
  font-weight: 500;
}

.form {
  padding: 30px;
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.section-label {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  color: #94a3b8;
  font-weight: 800;
  margin-left: 4px;
}

/* GRID DE NIÑOS */
.kids-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
  gap: 14px;
}

.kid-card {
  background: #f8fafc;
  border: 2px solid #f1f5f9;
  border-radius: 24px;
  padding: 20px 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
}

.kid-card:hover {
  border-color: #cbd5e1;
  transform: translateY(-6px);
  background: white;
  box-shadow: 0 12px 24px rgba(0,0,0,0.06);
}

.kid-card.selected {
  background: #eff6ff;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px #3b82f6, 0 10px 25px rgba(59, 130, 246, 0.15);
}

.avatar-box {
  width: 64px;
  height: 64px;
  background: white;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 12px rgba(0,0,0,0.04);
  position: relative;
  transition: all 0.4s ease;
}

.avatar-icon {
  width: 32px;
  height: 32px;
  color: #94a3b8;
  transition: all 0.4s ease;
}

.kid-card.selected .avatar-box {
  background: #3b82f6;
  transform: scale(1.1);
  box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

.kid-card.selected .avatar-icon {
  color: white;
}

.kid-name {
  font-size: 14px;
  font-weight: 700;
  color: #475569;
  text-align: center;
  line-height: 1.3;
  transition: all 0.3s ease;
}

.kid-card.selected .kid-name {
  color: #1d4ed8;
}

/* BADGE DE CHECK */
.check-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 26px;
  height: 26px;
  background: #10b981;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  border: 3px solid white;
  opacity: 0;
  transform: scale(0.5) rotate(-45deg);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

.kid-card.selected .check-badge {
  opacity: 1;
  transform: scale(1) rotate(0);
}

/* BOTÓN DE ENVÍO */
.btn-submit {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
  border: none;
  padding: 18px;
  border-radius: 20px;
  font-weight: 800;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.25);
  margin-top: 10px;
  position: relative;
  overflow: hidden;
}

.btn-submit::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: 0.5s;
}

.btn-submit:hover:not(:disabled)::before {
  left: 100%;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 18px 36px rgba(37, 99, 235, 0.35);
  filter: brightness(1.1);
}

.btn-submit:active:not(:disabled) {
  transform: translateY(-1px);
}

.btn-submit:disabled {
  background: #f1f5f9;
  color: #cbd5e1;
  cursor: not-allowed;
  box-shadow: none;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  background: #f8fafc;
  border-radius: 24px;
  color: #94a3b8;
  border: 2px dashed #e2e8f0;
}

.empty-icon {
  margin-bottom: 12px;
  color: #e2e8f0;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.5;
}

/* RESPONSIVE */
@media (max-width: 480px) {
  .kids-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>