<script setup>
import { ref, computed, watch } from 'vue'
import api from '@/services/api'
import { useProfileStore } from '@/stores/profileStore'

const profileStore = useProfileStore()

const miembros = ref([])
const search = ref('')
const loading = ref(false)
console.log(profileStore.profileData)

// 🔥 detectar gerente
const esGerente = computed(() => {
  return ['gerente', 'subgerente'].includes(profileStore.profileData?.tipo_socio)
})

// 🕒 formato fecha bonito
const formatearFecha = (fecha) => {
  if (!fecha) return 'Sin datos'

  return new Date(fecha).toLocaleString('es-MX', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const idSocio = computed(() => {
  return profileStore.profileData?.id_socio || null
})

const fetchMiembros = async () => {
  if (!idSocio.value) return

  loading.value = true

  try {
    const res = await api.get(`ludoteca/validar-tutor?id_socio=${idSocio.value}`)
    miembros.value = res.data.data || []
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

watch(idSocio, (newVal) => {
  if (newVal) fetchMiembros()
}, { immediate: true })

// 🔍 filtro
const miembrosFiltrados = computed(() => {
  if (!search.value) return miembros.value || []

  const s = search.value.toLowerCase()

  return (miembros.value || []).filter(m =>
    (m?.menor || '').toLowerCase().includes(s)
  )
})

// 🔥 estado visual
const getEstado = (m) => {
  if (m.estatus_visita === 'ACTIVA') {
    return {
      texto: 'Activo',
      clase: 'estado-activo'
    }
  }

  return {
    texto: 'Sin registro',
    clase: 'estado-inactivo'
  }
}

// 🚀 acciones
const registrarEntrada = (m) => {
  console.log('Entrada 👉', m)
}

const registrarSalida = (m) => {
  console.log('Salida 👉', m)
}
</script>

<template>
  <div class="container">

    <!-- HEADER -->
    <div class="header">
      <div>
        <h2>Lista ludoteca</h2>
        <p>Consulta los niños asociados a tu cuenta</p>
      </div>
     
    </div>

    <!-- BUSCADOR -->
    <input
      v-model="search"
      placeholder="Buscar por nombre o ID..."
      class="search"
    />

    <!-- LISTA -->
    <div v-if="loading" class="loading">Cargando...</div>

    <div v-else>
      <div v-for="m in miembrosFiltrados" :key="m.menor" class="card">

        <div class="left">
          <div class="avatar">
            {{ m.menor?.charAt(0) || '' }}
          </div>

          <div>
            <!-- NOMBRE -->
            <div class="nombre">
              {{ m.menor }}
            </div>

            <!-- INFO -->
            <div class="info">
               Ingreso: {{ formatearFecha(m.hora_ingreso) }}
            </div>

            <div class="info" v-if="m.hora_limite">
              Límite: {{ formatearFecha(m.hora_limite) }}
            </div>

          
            <div class="estado-container">

              <div :class="['estado-chip', getEstado(m).clase]">
                {{ getEstado(m).texto }}
              </div>

              <div class="estado-sub">
                Último registro: {{ m.estatus_visita || 'Sin datos' }}
              </div>

            </div>

          </div>
        </div>

        <!-- 🔥 BOTONES SOLO GERENTE -->
        <div class="actions" v-if="esGerente">
          <button class="btn-entrada" @click="registrarEntrada(m)">
            ⬅ Entrada
          </button>

          <button class="btn-salida" @click="registrarSalida(m)">
            ➡ Salida
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<style scoped>

/* CONTENEDOR */
.container {
  padding: 20px;
}

/* HEADER */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.header h2 {
  font-size: 22px;
  font-weight: 700;
}

.header p {
  color: #6b7280;
  font-size: 14px;
}

/* SEARCH */
.search {
  width: 100%;
  padding: 10px;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  margin-bottom: 12px;
}

/* CARD */
.card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  border-radius: 12px;
  padding: 14px;
  margin-bottom: 12px;
  border: 1px solid #e5e7eb;
}

/* LEFT */
.left {
  display: flex;
  gap: 12px;
}

/* AVATAR */
.avatar {
  width: 42px;
  height: 42px;
  background: #dbeafe;
  color: #1d4ed8;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

/* NOMBRE */
.nombre {
  font-weight: 600;
  font-size: 15px;
}

/* INFO */
.info {
  font-size: 13px;
  color: #6b7280;
}

/* ESTADO */
.estado-container {
  margin-top: 8px;
}

.estado-chip {
  display: inline-block;
  padding: 6px 14px;
  border-radius: 999px;
  font-weight: 600;
  font-size: 14px;
}

/* 🟢 ACTIVO */
.estado-activo {
  background: #d1fae5;
  color: #15803d;
  border: 1px solid #86efac;
}

/* ⚪ SIN REGISTRO */
.estado-inactivo {
  background: #e5e7eb;
  color: #374151;
  border: 1px solid #d1d5db;
}

.estado-sub {
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
}

/* BOTONES */
.actions {
  display: flex;
  gap: 10px;
}

/* 🔵 ENTRADA */
.btn-entrada {
  background: #2563eb;
  color: white;
  border: none;
  padding: 8px 14px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-entrada:hover {
  background: #1d4ed8;
}

/* 🔴 SALIDA */
.btn-salida {
  background: #dc2626;
  color: white;
  border: none;
  padding: 8px 14px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-salida:hover {
  background: #b91c1c;
}

/* LOADING */
.loading {
  text-align: center;
  padding: 20px;
}


</style>