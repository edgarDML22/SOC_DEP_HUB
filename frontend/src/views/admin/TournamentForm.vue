<script setup>
import { useRouter } from 'vue-router'
import { ref, onMounted } from 'vue'
import axios from 'axios'

const router = useRouter()
const torneos = ref([])

const goToCreate = () => {
  router.push('/admin/tournaments/create')
}

//  FORMATEO DE ESTADOS
const formatEstado = (estado) => {
  if (estado === 'PROGRAMADO') return 'Confirmado'
  if (estado === 'EN_PLANIFICACION') return 'En Planificación'
  if (estado === 'EN_CURSO') return 'En Curso'
  if (estado === 'FINALIZADO') return 'Finalizado'
  if (estado === 'CANCELADO') return 'Cancelado'
  return estado
}
const goToDetails = (torneo) => {
  router.push({
    path: '/tournaments/details',
    query: {
      nombre_torneo: torneo.nombre_torneo,
      fecha_inicio: torneo.fecha_inicio,
      categoria: torneo.categoria,
      disciplina: torneo.disciplina
    }
  })
}

//  CLASES DE COLORES
const getStatusClass = (estado) => {
  if (estado === 'PROGRAMADO') return 'badge-green'
  if (estado === 'EN_PLANIFICACION') return 'badge-blue'
  if (estado === 'EN_CURSO') return 'badge-green'
  if (estado === 'FINALIZADO') return 'badge-gray'
  if (estado === 'CANCELADO') return 'badge-red'
  return ''
}

//  GET DATA
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

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Disciplina</th>
          <th>Categoría</th>
          <th>Fecha Inicio</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="torneo in torneos" :key="torneo.id_torneo">
          <td>{{ torneo.nombre_torneo }}</td>
          <td>{{ torneo.disciplina }}</td>
          <td>{{ torneo.categoria }}</td>
          <td>{{ torneo.fecha_inicio }}</td>

          <td>
            <span class="badge" :class="getStatusClass(torneo.estado)">
              {{ formatEstado(torneo.estado) }}
            </span>
          </td>

          <td>
            <a 
              href="#" 
              class="link"
              @click.prevent="goToDetails(torneo)"
            >
              Ver Detalles →
            </a>
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

/*  BADGES */
.badge {
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 500;
  display: inline-block;
}


.badge-blue {
  background: #dbeafe;
  color: #1d4ed8;
  border: 1px solid #93c5fd;
}


.badge-green {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #6ee7b7;
}


.badge-red {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #f87171;
}


.badge-gray {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}


.link {
  color: #111827;
  font-size: 14px;
  cursor: pointer;
  text-decoration: none;
}

.link:hover {
  text-decoration: underline;
}
</style>