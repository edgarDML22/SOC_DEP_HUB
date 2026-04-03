<script setup>
import { useRouter } from 'vue-router'
import { ref, onMounted } from 'vue'
import axios from 'axios'

const router = useRouter()

const torneos = ref([])

const goToCreate = () => {
  router.push('/admin/tournaments/create')
}
const formatEstado = (estado) => {
  if (estado === 'PROGRAMADO') return 'Confirmado'
  if (estado === 'EN_PLANIFICACION') return 'En Planificación'
  return estado
}

const getStatusClass = (estado) => {
  if (estado === 'PROGRAMADO') return 'badge-green'
  if (estado === 'EN_PLANIFICACION') return 'badge-blue'
  return ''
}

// 🔥 NUEVO: obtener torneos del backend
const getTorneos = async () => {
  try {
    const res = await axios.get('http://localhost:8000/api/v1/torneos')
    torneos.value = res.data.data
  } catch (error) {
    console.error(error)
  }
}

onMounted(() => {
  getTorneos()
})
</script>

<template>
  <div class="header">

    <div>
      <h1>Torneos</h1>
      <p class="subtitle">Administra los torneos del club</p>
    </div>

    <button class="btn-create" @click="goToCreate">
      + Crear Torneo
    </button>

  </div>

  <!-- 🔥 NUEVO: tabla -->
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Disciplina</th>
          <th>Categoría</th>
          <th>Fecha Inicio</th>
          <th>Estado</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="torneo in torneos" :key="torneo.id_torneo">
          <td>{{ torneo.nombre_torneo }}</td>
          <td>{{ torneo.disciplina }}</td>
          <td>{{ torneo.categoria }}</td>
          <td>{{ torneo.fecha_inicio }}</td>
          <td>
  <span 
    class="badge"
    :class="getStatusClass(torneo.estado)"
  >
    {{ formatEstado(torneo.estado) }}
  </span>
</td>
        </tr>
      </tbody>
    </table>
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

.btn-create {
  background: #2563eb;
  color: white;
  padding: 8px 14px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.btn-create:hover {
  background: #1d4ed8;
}

/* 🔥 NUEVO */
.table-container {
  margin-top: 20px;
  background: white;
  border-radius: 12px;
  padding: 16px;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  text-align: left;
  font-size: 14px;
  color: #6b7280;
  padding: 10px;
}

td {
  padding: 10px;
  border-top: 1px solid #e5e7eb;
}
.badge {
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 500;
  display: inline-block;
}

/* 🔵 Próximo */
.badge-blue {
  background: #dbeafe;
  color: #1d4ed8;
  border: 1px solid #93c5fd;
}

/* 🟢 En curso */
.badge-green {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #6ee7b7;
}
</style>