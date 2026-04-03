<script setup>
import { ref } from 'vue'
import api from '@/services/api'
import { useRouter } from 'vue-router'

const router = useRouter()

const form = ref({
  nombre_torneo: '',
  nombre_categoria: '',
  nombre_disciplina: '',
  fecha_inicio: '',
  fecha_fin: '',
  tipo_acceso: '',
  formato_competencia: '',
  cupo_maximo: 1,
  descripcion: ''
})

const loading = ref(false)

/* 🔥 BANNER */
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
    const res = await api.post('/api/v1/torneos', form.value)

    if (res.data.success) {
      showBanner('Torneo creado correctamente', 'success')

      setTimeout(() => {
        router.push('/admin/tournaments')
      }, 1500)
    }

  } catch (err) {
    console.error(err.response?.data)

    if (err.response?.status === 422) {
      showBanner('Error de validación: revisa los campos', 'error')
    } else if (err.response?.status === 409) {
      showBanner('Ya existe un torneo con ese nombre en esa fecha', 'error')
    } else {
      showBanner('Error al crear torneo', 'error')
    }

  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="container">
    <h2>Crear Torneo</h2>

    <!-- 🔥 BANNER -->
    <div 
      v-if="banner.show" 
      :class="['alert-banner', banner.type === 'success' ? 'success' : 'error']"
    >
      {{ banner.message }}
    </div>

    <form @submit.prevent="submit" class="form">

      <!-- Nombre -->
      <div class="group">
        <label>Nombre del torneo</label>
        <input v-model="form.nombre_torneo" required />
      </div>

      <!-- Categoria -->
      <div class="group">
        <label>Categoría</label>
        <input v-model="form.nombre_categoria" placeholder="Ej: Juvenil" required />
      </div>

      <!-- Disciplina -->
      <div class="group">
        <label>Disciplina</label>
        <input v-model="form.nombre_disciplina" placeholder="Ej: Tenis" required />
      </div>

      <!-- Fechas -->
      <div class="row">
        <div class="group">
          <label>Fecha inicio</label>
          <input type="date" v-model="form.fecha_inicio" required />
        </div>

        <div class="group">
          <label>Fecha fin</label>
          <input type="date" v-model="form.fecha_fin" required />
        </div>
      </div>

      <!-- Tipo acceso -->
      <div class="group">
        <label>Tipo de acceso</label>
        <select v-model="form.tipo_acceso" required>
          <option disabled value="">Selecciona</option>
          <option value="INTERNO">INTERNO</option>
          <option value="ABIERTO">ABIERTO</option>
        </select>
      </div>

      <!-- Formato -->
      <div class="group">
        <label>Formato de competencia</label>
        <select v-model="form.formato_competencia" required>
          <option disabled value="">Selecciona</option>
          <option value="ELIMINACION_DIRECTA">Eliminación directa</option>
          <option value="FASE_GRUPOS">Fase de grupos</option>
        </select>
      </div>

      <!-- Cupo -->
      <div class="group">
        <label>Cupo máximo</label>
        <input type="number" min="1" v-model="form.cupo_maximo" required />
      </div>

      <!-- Descripción -->
      <div class="group">
        <label>Descripción</label>
        <textarea v-model="form.descripcion"></textarea>
      </div>

      <!-- BOTÓN -->
      <button type="submit" :disabled="loading">
        {{ loading ? 'Creando...' : 'Crear torneo' }}
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

/* 🔥 BANNER BASE */
.alert-banner {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 14px;
  border: 1px solid;
}

/* 🔴 ERROR */
.alert-banner.error {
  background-color: #fef2f2;
  color: #991b1b;
  border-color: #f87171;
}

/* 🟢 SUCCESS */
.alert-banner.success {
  background-color: #ecfdf5;
  color: #065f46;
  border-color: #34d399;
}
</style>