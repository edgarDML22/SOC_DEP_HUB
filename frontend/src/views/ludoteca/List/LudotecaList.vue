<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useProfileStore } from '@/stores/profiles/socioStore'

const router = useRouter()
const profileStore = useProfileStore()

const miembros = ref([])
const search = ref('')
const loading = ref(false)

const mensaje = ref('')
const tipoMensaje = ref('')

// detectar gerente
const esGerente = computed(() => {
  return ['gerente', 'subgerente'].includes(profileStore.profileData?.tipo_socio)
})

const idSocio = computed(() => profileStore.profileData?.id_socio)

// formato fecha
const formatearFecha = (fecha) => {
  if (!fecha) return 'Sin datos'
  return new Date(fecha).toLocaleString('es-MX', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// cargar lista
const fetchMiembros = async () => {
  if (!idSocio.value) return

  loading.value = true
  try {
    const res = await api.get(`validar-tutor?id_socio=${idSocio.value}`)
    miembros.value = res.data.data || []
  } catch (error) {
    console.log(error)
    mensaje.value = 'No hay registros'
    tipoMensaje.value = 'error'
  } finally {
    loading.value = false
  }
}

watch(idSocio, (val) => {
  if (val) fetchMiembros()
}, { immediate: true })

// filtro
const miembrosFiltrados = computed(() => {
  if (!search.value) return miembros.value
  return miembros.value.filter(m =>
    (m.menor || '').toLowerCase().includes(search.value.toLowerCase())
  )
})

// estado visual
const getEstado = (m) => {
  if (m.estatus_visita === 'ACTIVA') {
    return { texto: 'Activo', clase: 'estado-activo' }
  }
  return { texto: 'Sin registro', clase: 'estado-inactivo' }
}


const irAUpdate = (m, tipo) => {
  router.push({
    name: 'ludoteca/update',
    query: {
      id_registro: m.id_registro,
      tipo: tipo
    }
  })
}
</script>

<template>
  <div class="container">

    <div class="header">
      <div>
        <h2>Lista ludoteca</h2>
        <p>Consulta y controla accesos</p>
      </div>
    </div>

    <!-- BUSCADOR -->
    <input
      v-model="search"
      placeholder="Buscar..."
      class="search"
    />

    <!-- MENSAJE -->
    <div v-if="mensaje" :class="['alert', tipoMensaje]">
      {{ mensaje }}
    </div>

    <!-- LISTA SCROLL -->
    <div class="scroll-container">

      <div v-if="loading" class="loading">Cargando...</div>

      <div v-else>
        <div v-for="m in miembrosFiltrados" :key="m.id_registro" class="card">

          <div class="left">
            <div class="avatar">
              {{ m.menor?.charAt(0) }}
            </div>

            <div>
              <div class="nombre">{{ m.menor }}</div>

              <div class="info">
                Ingreso: {{ formatearFecha(m.hora_ingreso) }}
              </div>

              <div class="info">
                Límite: {{ formatearFecha(m.hora_limite) }}
              </div>

              <div class="estado-container">
                <div :class="['estado-chip', getEstado(m).clase]">
                  {{ getEstado(m).texto }}
                </div>
              </div>
            </div>
          </div>

          <!-- BOTONES -->
          <div class="actions" v-if="esGerente">

            <button
              v-if="m.estatus_visita !== 'ACTIVA'"
              type="button"
              class="btn-entrada"
              @click="irAUpdate(m, 'in')"
            >
              Entrada
            </button>

            <button
              v-if="m.estatus_visita === 'ACTIVA'"
              type="button"
              class="btn-salida"
              @click="irAUpdate(m, 'out')"
            >
              Salida
            </button>

</div>

        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>

.container {
  padding: 20px;
}

.header {
  margin-bottom: 16px;
}

.search {
  width: 100%;
  padding: 10px;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  margin-bottom: 12px;
}

/* SCROLL */
.scroll-container {
  max-height: 500px;
  overflow-y: auto;
  padding-right: 5px;
}

/* CARD */
.card {
  display: flex;
  justify-content: space-between;
  background: white;
  border-radius: 12px;
  padding: 14px;
  margin-bottom: 10px;
  border: 1px solid #e5e7eb;
}

.left {
  display: flex;
  gap: 12px;
}

.avatar {
  width: 42px;
  height: 42px;
  background: #dbeafe;
  color: #1d4ed8;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nombre {
  font-weight: 600;
}

.info {
  font-size: 13px;
  color: #6b7280;
}

.estado-chip {
  margin-top: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 13px;
}

.estado-activo {
  background: #d1fae5;
  color: #15803d;
}

.estado-inactivo {
  background: #e5e7eb;
  color: #374151;
}

/* BOTONES */
.actions {
  display: flex;
  gap: 8px;
}

.btn-entrada {
  background: #2563eb;
  color: white;
  padding: 6px 12px;
  border-radius: 8px;
}
.btn-entrada:hover {
  background: #1d4ed8;
}

.btn-salida {
  background: #dc2626;
  color: white;
  padding: 6px 12px;
  border-radius: 8px;
}
.btn-salida:hover {
  background: #b91c1c;
}

/* ALERTAS */
.alert {
  margin-bottom: 10px;
  padding: 10px;
  border-radius: 8px;
  text-align: center;
}

.error {
  background: #fee2e2;
  border: 2px solid #dc2626;
  color: #dc2626;
}

.success {
  background: #dcfce7;
  border: 2px solid #16a34a;
  color: #16a34a;
}

.loading {
  text-align: center;
}

</style>