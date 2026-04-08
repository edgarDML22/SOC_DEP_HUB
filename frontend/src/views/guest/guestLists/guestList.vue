<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { useProfileStore } from '@/stores/profileStore'

const profileStore = useProfileStore()

const invitados = ref([])
const filtro = ref('TODOS')
const search = ref('')
const loading = ref(false)

const idSocio = computed(() => {
  return profileStore.profileData?.id_socio || null
})

const fetchInvitados = async () => {
  if (!idSocio.value) return

  loading.value = true

  try {
    let data = []

    if (filtro.value === 'TODOS') {
      const [activos, expirados] = await Promise.all([
        api.post('guest-status', { id: idSocio.value, status: 'ACTIVO' }),
        api.post('guest-status', { id: idSocio.value, status: 'EXPIRADO' })
      ])

      data = [
        ...(activos.data.data || []),
        ...(expirados.data.data || [])
      ]
    } else {
      const res = await api.post('guest-status', {
        id: idSocio.value,
        status: filtro.value
      })

      data = res.data.data || []
    }

    invitados.value = data
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const cambiarFiltro = (nuevo) => {
  filtro.value = nuevo
  fetchInvitados()
}

const invitadosFiltrados = () => {
  if (!search.value) return invitados.value

  const s = search.value.toLowerCase()

  return invitados.value.filter(i =>
    i.nombre.toLowerCase().includes(s) ||
    String(i.id).includes(s)
  )
}

onMounted(fetchInvitados)
</script>

<template>
  <div class="container">

    <!-- HEADER -->
    <div class="header">
      <div>
        <h2>Mis Invitados</h2>
        <p>Consulta el estatus de tus invitados</p>
      </div>

      <!-- Botón dentro de SocioGuestsView.vue -->
<router-link
  class="btn-primary"
  :to="{ name: 'guests-add' }"
>+ Agregar invitado
</router-link>
    </div>

    <!-- BUSCADOR -->
    <input
      v-model="search"
      placeholder="Buscar invitado por nombre o ID..."
      class="search"
    />

    <!-- FILTROS -->
    <div class="tabs">
      <button @click="cambiarFiltro('TODOS')" :class="['tab', filtro === 'TODOS' && 'active']">Todos</button>
      <button @click="cambiarFiltro('ACTIVO')" :class="['tab', filtro === 'ACTIVO' && 'active']">Activos</button>
      <button @click="cambiarFiltro('EXPIRADO')" :class="['tab', filtro === 'EXPIRADO' && 'active']">Cancelados</button>
    </div>

    <!-- LISTA -->
    <div v-if="loading" class="loading">Cargando...</div>

    <div v-else>
      <div v-for="inv in invitadosFiltrados()" :key="inv.id" class="card">

        <div class="left">

          <div class="avatar">
            {{ inv.nombre.charAt(0) }}
          </div>

          <div>
            <!-- NOMBRE + STATUS -->
            <div class="nombre">
              {{ inv.nombre }}

              <span
  class="badge"
  :class="{
    activo: inv.estatus_acceso === 'ACTIVO',
    expirado: inv.estatus_acceso === 'EXPIRADO'
  }"
>
  {{
    inv.estatus_acceso === 'ACTIVO'
      ? 'Activo'
      : inv.estatus_acceso === 'EXPIRADO'
      ? 'Cancelado'
      : ''
  }}
</span>
            </div>

            <!-- INFO -->
            <div class="info">ID: {{ inv.id }}</div>

            <div class="info" v-if="inv.telefono">
               {{ inv.telefono }}
            </div>

            <div class="info" v-if="inv.correo">
               {{ inv.correo }}
            </div>

            <div class="info" v-if="inv.fecha_expiracion">
               Expira: {{ inv.fecha_expiracion }}
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

/* BOTÓN */
.btn-primary {
  background-color: #2563eb;
  color: white;
  border: none;
  padding: 8px 14px;
  border-radius: 8px;
  cursor: pointer;
  text-decoration: none;
}

/* SEARCH */
.search {
  width: 100%;
  padding: 10px;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  margin-bottom: 12px;
}

/* TABS */
.tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
}

.tab {
  flex: 1;
  padding: 10px;
  border-radius: 10px;
  background: #f3f4f6;
  border: none;
  cursor: pointer;
}

.tab.active {
  background: #dbeafe;
  color: #1d4ed8;
  font-weight: 600;
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

/* BADGE BASE */
.badge {
  margin-left: 10px;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  background: transparent;
  border: 1.5px solid;
}

/* VERDE */
.badge.activo {
  color: #16a34a;
  border-color: #16a34a;
}

/* ROJO */
.badge.expirado {
  color: #dc2626;
  border-color: #dc2626;
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