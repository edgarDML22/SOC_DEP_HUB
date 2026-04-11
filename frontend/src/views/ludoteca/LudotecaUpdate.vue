<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api'

const route = useRoute()

//  recibir parámetros
const idRegistro = route.query.id_registro
const tipo = route.query.tipo // 'in' o 'out'

// estado
const correo = ref('')
const loading = ref(false)
const mensaje = ref('')
const tipoMensaje = ref('')

// texto dinámico
const titulo = computed(() => {
  return tipo === 'in' ? 'Registrar entrada' : 'Registrar salida'
})

// enviar al backend
const enviar = async () => {
  mensaje.value = ''

  if (!correo.value) {
    mensaje.value = 'Ingresa un correo electrónico'
    tipoMensaje.value = 'error'
    return
  }

  loading.value = true

  try {
    const res = await api.post('/ludoteca/update-status', {
      id_registro: idRegistro,
      correo_electronico: correo.value,
      check_in: tipo
    })

    if(res.data?.message == 'Ingreso registrado correctamente' || res.data?.message == 'Salida registrada correctamente' ){
      tipoMensaje.value = 'success'
    }else{
      tipoMensaje.value = 'error'
    }
    mensaje.value = res.data?.message

    correo.value = ''

  } catch (error) {
    mensaje.value =
      error.response?.data?.message || 'Error al procesar la solicitud'
    tipoMensaje.value = 'error'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="wrapper">
    <div class="card">

      <h2>{{ titulo }}</h2>

      <div class="form">

        <!-- INFO -->
        <div class="info-box">
          ID Registro: {{ idRegistro }}
        </div>

        <!-- INPUT CORREO -->
        <input
          v-model="correo"
          placeholder="Correo electrónico del tutor"
        />

        <!-- BOTÓN -->
        <button class="btn-primary" @click="enviar">
          {{ loading ? 'Procesando...' : 'Confirmar' }}
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
  gap: 12px;
  margin-top: 15px;
}

/* INFO */
.info-box {
  background: #f3f4f6;
  padding: 8px;
  border-radius: 8px;
  font-size: 13px;
  color: #374151;
}

/* INPUT */
input {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 8px;
  outline: none;
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
}

.btn-primary:hover {
  background: #1d4ed8;
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