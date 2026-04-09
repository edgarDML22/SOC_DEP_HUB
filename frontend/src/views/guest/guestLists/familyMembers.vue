<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { useProfileStore } from '@/stores/profileStore'

const profileStore = useProfileStore()

const miembros = ref([])
const search = ref('')
const loading = ref(false)

const idSocio = computed(() => {
  return profileStore.profileData?.id_socio || null
})

const fetchMiembros = async () => {
  if (!idSocio.value) return

  loading.value = true

  try {
    const res = await api.get(`miembros-familiares?id=${idSocio.value}`)
    miembros.value = res.data || []
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const miembrosFiltrados = () => {
  if (!search.value) return miembros.value

  const s = search.value.toLowerCase()

  return miembros.value.filter(m =>
    m.nombre_completo.toLowerCase().includes(s) ||
    String(m.id_miembro).includes(s)
  )
}

onMounted(fetchMiembros)
</script>

<template>
  <div class="container">

    <!-- HEADER -->
    <div class="header">
      <div>
        <h2>Miembros Familiares</h2>
        <p>Consulta los miembros asociados a tu cuenta</p>
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
      <div v-for="m in miembrosFiltrados()" :key="m.id_miembro" class="card">

        <div class="left">

          <div class="avatar">
            {{ m.nombre_completo.charAt(0) }}
          </div>

          <div>
            <!-- NOMBRE -->
            <div class="nombre">
              {{ m.nombre_completo }}
            </div>

            <!-- INFO -->
            <div class="info">ID: {{ m.id_miembro }}</div>

            <div class="info" v-if="m.parentesco">
              Parentesco: {{ m.parentesco }}
            </div>

            <div class="info" v-if="m.fecha_nacimiento">
              Nacimiento: {{ m.fecha_nacimiento }}
            </div>

            <div class="info" v-if="m.genero">
              Género: {{ m.genero }}
            </div>

          </div>
        </div>

        <!-- ACCIONES -->
        <div class="actions">
          <button class="btn-edit">Editar</button>
          <button class="btn-delete">Eliminar</button>
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

/* BOTONES */
.actions {
  display: flex;
  gap: 8px;
}

.btn-edit {
  background: #e5e7eb;
  border: none;
  padding: 6px 10px;
  border-radius: 6px;
}

.btn-delete {
  background: #fee2e2;
  border: none;
  padding: 6px 10px;
  border-radius: 6px;
  color: #b91c1c;
}

/* LOADING */
.loading {
  text-align: center;
  padding: 20px;
}

</style>