<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useProfileStore } from '@/stores/profiles/socioStore'
import { useAlerts } from '@/composables/useAlerts'

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
    mensaje.value = 'Error al cargar miembros'
    tipoMensaje.value = 'error'
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
    <div class="card">
      <h2>Registro Ludoteca</h2>

      <div class="form">

        <!-- SECCIÓN MENOR -->
        <div class="section">
          <label>Seleccionar menor</label>

          <select v-model="idSeleccionado">
            <option disabled value="">Selecciona un menor</option>
            <option 
              v-for="m in miembros" 
              :key="m.id_miembro" 
              :value="m.id_miembro"
            >
              {{ m.nombre_completo }}
            </option>
          </select>
        </div>

        <!-- BOTÓN -->
        <button 
          class="btn-primary" 
          @click="registrar"
          :disabled="loading"
        >
          {{ loading ? 'Registrando...' : 'Registrar' }}
        </button>

      </div>
    </div>
  </div>
</template>

<style scoped>

/* CENTRADO */
.wrapper {
  min-height: 80vh;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* TARJETA */
.card {
  background: white;
  padding: 30px;
  border-radius: 16px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

/* FORM */
.form {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 15px;
}

/* SECCIÓN */
.section {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

label {
  font-size: 14px;
  color: #374151;
  font-weight: 500;
}

/* SELECT */
select {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 8px;
  outline: none;
}

select:focus {
  border-color: #2563eb;
}

/* BOTÓN */
.btn-primary {
  margin-top: 10px;
  background: #2563eb;
  color: white;
  border: none;
  padding: 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.2s;
}

.btn-primary:hover {
  background: #1d4ed8;
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

/* ALERTAS */
.alert {
  margin-top: 12px;
  padding: 10px;
  border-radius: 10px;
  font-weight: 500;
  text-align: center;
}
/* VERDE */
.alert.success {
  background-color: #d4edda;
  color: #155724;
}

/* ROJO */
.alert.error {
  background-color: #f8d7da;
  color: #721c24;
}


</style>