<script setup>
import { useRoute, useRouter } from 'vue-router'
import { ref } from 'vue'
import axios from 'axios'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

// datos ocultos
const torneo = ref({
  nombre_torneo: route.query.nombre_torneo,
  fecha_inicio: route.query.fecha_inicio,
  categoria: route.query.categoria,
  disciplina: route.query.disciplina,
})

//  banner
const banner = ref({
  show: false,
  message: '',
  type: '' // success | error
})

// confirmar torneo
const confirmarTorneo = async () => {
  try {
    await api.post('torneos/update-status', {
  nombre_torneo: torneo.value.nombre_torneo,
  fecha_inicio: torneo.value.fecha_inicio,
  nombre_categoria: torneo.value.categoria,
  nombre_disciplina: torneo.value.disciplina
})

    //  éxito → regresar
    router.push('/admin/tournaments')

  } catch (error) {
    console.error(error)

    //  error → banner rojo
    banner.value = {
      show: true,
      message: error.response?.data?.message || 'Error al confirmar torneo',
      type: 'error'
    }

    // opcional: ocultar después de 3s
    setTimeout(() => {
      banner.value.show = false
    }, 3000)
  }
}
</script>

<template>
  <!--  BANNER -->
  <div 
    v-if="banner.show" 
    class="alert-banner"
    :class="{
      'alert-banner-error': banner.type === 'error',
      'alert-banner-success': banner.type === 'success'
    }"
  >
    {{ banner.message }}
  </div>

  <!--  HEADER -->
  <div class="header">
    <div>
      <h1>Detalle Torneo</h1>
      <p class="subtitle">Confirmación del torneo</p>
    </div>

    <button class="btn-create" @click="confirmarTorneo">
      Confirmar Torneo
    </button>
  </div>
</template>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.subtitle {
  font-size: 13px;
  color: #6b7280;
}

/*  BOTÓN */
.btn-create {
  background: #16a34a;
  color: white;
  padding: 8px 14px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.btn-create:hover {
  background: #15803d;
}

/* BANNER BASE */
.alert-banner {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 14px;
  border: 1px solid;
}

/* ERROR */
.alert-banner-error {
  background-color: #fef2f2;
  color: #991b1b;
  border-color: #f87171;
}


</style>