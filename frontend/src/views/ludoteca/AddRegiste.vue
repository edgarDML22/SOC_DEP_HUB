<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useProfileStore } from '@/stores/profileStore'

const profileStore = useProfileStore()

const miembros = ref([])
const idSeleccionado = ref(null)

const loading = ref(false)
const mensaje = ref('')
const tipoMensaje = ref('')

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
  mensaje.value = ''

  if (!idSeleccionado.value) {
    mensaje.value = 'Selecciona un menor'
    tipoMensaje.value = 'error'
    return
  }

  loading.value = true

  try {
    const res = await api.post('/ludoteca/register', {
      id_miembro: idSeleccionado.value,
      id_socio: profileStore.profileData?.id_socio
    })

    // Éxito
    if(res.data?.message == 'Ingreso registrado correctamente' ){
       tipoMensaje.value = 'success'
    }else{
      tipoMensaje.value = 'error'
    }
    mensaje.value = res.data?.message
   

    idSeleccionado.value = null

  } catch (error) {
    // Error controlado del backend
    mensaje.value =
      error.response?.data?.message ||
      error.response?.data?.error ||
      'Error al registrar'

    tipoMensaje.value = 'error'
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

        <!-- MENSAJE -->
        <div
          v-if="mensaje"
          :class="['alert', tipoMensaje]"
        >
          {{ mensaje }}
        </div>

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

/* ERROR */
.error {
  background: #fee2e2;
  border: 2px solid #dc2626;
  color: #dc2626;
}

/* SUCCESS */
.success {
  background: #dcfce7;
  border: 2px solid #16a34a;
  color: #16a34a;
}

</style>