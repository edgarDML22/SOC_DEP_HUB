<script setup>
import { ref } from 'vue'
import api from '@/services/api'
import { useRouter } from 'vue-router'

const router = useRouter()

const form = ref({
  nombre_categoria: '',
  edad_maxima: 1,
  edad_minima: 1,
  genero_requerido: '',
})

const loading = ref(false)

//  BANNER
const banner = ref({
  show: false,
  message: '',
  type: '' // success | error
})

const showBanner = (msg, type = 'success') => {
  banner.value = {
    show: true,
    message: msg,
    type
  }

  setTimeout(() => {
    banner.value.show = false
  }, 4000)
}

const submit = async () => {
  loading.value = true

  try {
    const res = await api.post('categories', form.value)

    if (res.data.success) {
      showBanner('Categoria creada correctamente', 'success')

      setTimeout(() => {
        router.push('/admin/categories/create')  
      }, 1500)
    }

  } catch (err) {
    console.error(err.response?.data)

    if (err.response?.status === 422) {
      showBanner('Error de validación: revisa los campos', 'error')
    } else if (err.response?.status === 409) {
      showBanner('Ya existe una categoria con ese nombre en esa fecha', 'error')
    } else {
      showBanner('Error al crear categoria', 'error')
    }

  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="container">
    <h2>Crear Categoria</h2>

    <!-- BANNER -->
    <div 
      v-if="banner.show" 
      :class="['alert-banner', banner.type === 'success' ? 'success' : 'error']"
    >
      {{ banner.message }}
    </div>

    <form @submit.prevent="submit" class="form">

      <!-- Nombre del grupo-->
      <div class="group">
        <label>Nombre de la categoria</label>
        <input v-model="form.nombre_categoria" required />
      </div>

      <!-- Edad maxima  -->
      <div class="group">
        <label>Edad máxima</label>
        <input type="number" min="1" v-model="form.edad_maxima" required />
      </div>

      <!-- Edad minima -->
      <div class="group">
        <label>Edad minima</label>
        <input type="number" min="1" v-model="form.edad_minima" required />
      </div>


      <!-- Genero -->
      <div class="group">
        <label>Genero</label>
        <select v-model="form.genero_requerido" required>
          <option disabled value="">Selecciona</option>
          <option value="M">Masculino</option>
          <option value="F">Femenino</option>
          <option value="MIXTO">Mixto</option>
        </select>
      </div>

      

      <!-- BOTÓN -->
      <button type="submit" :disabled="loading">
        {{ loading ? 'Creando...' : 'Crear categoria' }}
      </button>

    </form>
  </div>
</template>

<style scoped>
.container {
  max-width: 600px;
  margin: auto;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.group {
  display: flex;
  flex-direction: column;
}

.row {
  display: flex;
  gap: 10px;
}

input, select, textarea {
  padding: 8px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
}

button {
  background: #2563eb;
  color: white;
  padding: 10px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
}

button:disabled {
  background: #93c5fd;
}


.alert-banner {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 14px;
  border: 1px solid;
}


.alert-banner.error {
  background-color: #fef2f2;
  color: #991b1b;
  border-color: #f87171;
}


.alert-banner.success {
  background-color: #ecfdf5;
  color: #065f46;
  border-color: #34d399;
}
</style>