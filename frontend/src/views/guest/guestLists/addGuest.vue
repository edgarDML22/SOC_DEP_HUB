<script setup>
import { ref } from 'vue'
import api from '@/services/api'
import { useProfileStore } from '@/stores/profiles/socioStore'

const profileStore = useProfileStore()

const form = ref({
  nombre: '',
  correo: '',
  telefono: ''
})

const loading = ref(false)
const mensaje = ref('')
const tipoMensaje = ref('') 

const guardarInvitado = async () => {
  loading.value = true
  mensaje.value = ''

  try {
    await api.post('guest-pass', {
      id: profileStore.profileData?.id_socio,
      nombre_invitado: form.value.nombre,
      correo: form.value.correo,
      telefono: form.value.telefono
    })

    mensaje.value = 'Invitado creado y correo enviado'
    tipoMensaje.value = 'success' 

    form.value = {
      nombre: '',
      correo: '',
      telefono: ''
    }

  } catch (error) {
    mensaje.value = error.response?.data?.message || 'Error al guardar'
    tipoMensaje.value = 'error' 
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="wrapper">

    <div class="card">
      <h2>Agregar Invitado</h2>

      <div class="form">
        <input v-model="form.nombre" placeholder="Nombre completo" />
        <input v-model="form.correo" placeholder="Correo electrónico" />
        <input v-model="form.telefono" placeholder="Teléfono" />

        <button class="btn-primary" @click="guardarInvitado">
          {{ loading ? 'Guardando...' : 'Guardar invitado' }}
        </button>

        <!--  BADGE DINÁMICO -->
        <div
          v-if="mensaje"
          :class="['badge', tipoMensaje]"
        >
          {{ mensaje }}
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
  border-radius: 16px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

/* FORM */
.form {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 15px;
}

/* INPUTS */
input {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 8px;
  outline: none;
  transition: 0.2s;
}

input:focus {
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

/* BADGE BASE */
.badge {
  margin-top: 10px;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 500;
  text-align: center;
}

/*  ERROR */
.error {
  border: 1px solid #ef4444;
  color: #ef4444;
}

/*  SUCCESS */
.success {
  border: 1px solid #22c55e;
  color: #22c55e;
}

</style>