<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useGuestStore } from '@/stores/guestStore'
import { useAlerts } from '@/composables/useAlerts' 

const router = useRouter()
const guestStore = useGuestStore()
const { toastInfo } = useAlerts() 

const form = ref({ nombre: '', correo: '', telefono: '' })
const loadingBtn = ref(false)

const guardarInvitado = async () => {
  loadingBtn.value = true

  try {
    await guestStore.addInvitado({
      nombre_invitado: form.value.nombre,
      correo: form.value.correo,
      telefono: form.value.telefono
    })

    // Alerta global de éxito
    toastInfo('¡Éxito!', 'Invitado creado y correo enviado', 'success')

    form.value = { nombre: '', correo: '', telefono: '' }

    // Redirigir de vuelta a la lista
    setTimeout(() => {
      router.push({ name: 'guests-list' })
    }, 1500)

  } catch (error) {

    const errorMsg = error.response?.data?.message || 'Error al guardar el invitado'
    
    // Alerta global de error
    toastInfo('Error', errorMsg, 'error')
  } finally {
    loadingBtn.value = false
  }
}

// Función para el botón Volver
const volver = () => {
  router.push({ name: 'guests-list' })
}
</script>

<template>
  <div class="wrapper">
    <div class="card">
      <h2>Agregar Invitado</h2>
      <div class="form">
        
        <div class="form-group">
          <label>Nombre Completo</label>
          <input v-model="form.nombre" placeholder="Ej. Ana Gómez" />
        </div>

        <div class="form-group">
          <label>Correo Electrónico</label>
          <input type="email" v-model="form.correo" placeholder="correo@ejemplo.com" />
        </div>

        <div class="form-group">
          <label>Teléfono</label>
          <input v-model="form.telefono" placeholder="10 dígitos" />
        </div>

        <div class="button-group">
          <button class="btn-secondary" @click="volver" :disabled="loadingBtn">
            Volver
          </button>
          <button class="btn-primary" @click="guardarInvitado" :disabled="loadingBtn">
            {{ loadingBtn ? 'Guardando...' : 'Guardar Invitado' }}
          </button>
        </div>

        </div>
    </div>
  </div>
</template>

<style scoped>
/* CENTRADO TOTAL */
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
  border-radius: var(--p-border-radius-medium, 16px);
  width: 100%;
  max-width: 450px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}

/* FORM & GROUPS */
.form { display: flex; flex-direction: column; gap: 16px; margin-top: 15px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 13px; font-weight: 600; color: var(--p-surface-900, #374151); }

/* INPUTS */
input {
  padding: 10px;
  border: 1px solid var(--p-surface-200, #ddd);
  border-radius: 8px;
  outline: none;
  transition: 0.2s;
  font-family: inherit;
  font-size: 14px;
}

input:focus { border-color: var(--p-primary-700); }

/* BOTONES */
.button-group { display: flex; gap: 12px; margin-top: 10px; }
.btn-primary { flex: 1; background: var(--p-primary-700); color: white; border: none; padding: 10px; border-radius: var(--p-border-radius-medium); cursor: pointer; transition: 0.2s; font-weight: 500; font-size: 14px;}
.btn-primary:hover { background: var(--p-primary-800); }
.btn-secondary { flex: 1; background: white; color: var(--p-surface-900); border: 1px solid var(--p-surface-300, #d1d5db); padding: 10px; border-radius: var(--p-border-radius-medium); cursor: pointer; transition: 0.2s; font-weight: 500; font-size: 14px;}
.btn-secondary:hover { background: var(--p-surface-100, #f3f4f6); }
</style>