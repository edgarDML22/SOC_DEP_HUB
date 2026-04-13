<script setup>
import { ref, onMounted, computed } from 'vue'
import { useFriendStore } from '@/stores/community/friendStore'
import { useAlerts } from '@/composables/useAlerts' 
import IconTrash from '@/components/icons/IconTrash.vue' 

const friendStore = useFriendStore()
const { toastInfo, confirmDelete, confirmWarning } = useAlerts() 

const search = ref('')
const filtro = ref('AMIGO') 

// ARIABLES PARA CONTROLAR LOS BOTONES DE CARGA
const actionLoadingId = ref(null)
const actionTypeLoading = ref('') // Puede ser: 'eliminar', 'cancelar', 'aceptar', 'rechazar'

const amigosFiltrados = computed(() => {
  let list = Array.isArray(friendStore.friends) ? friendStore.friends : []

  if (filtro.value === 'AMIGO') {
    list = list.filter(f => f.estado === 'ACEPTADA')
  } else if (filtro.value === 'SOLICITUD_ENVIADA') {
    list = list.filter(f => f.estado === 'PENDIENTE' && f.solicitado_por_mi)
  } else if (filtro.value === 'SOLICITUD_RECIBIDA') {
    list = list.filter(f => f.estado === 'PENDIENTE' && !f.solicitado_por_mi)
  }

  if (search.value) {
    const lower = search.value.toLowerCase()
    list = list.filter(f => f.nombre_amigo?.toLowerCase().includes(lower))
  }

  return list
})

onMounted(() => {
  friendStore.fetchFriends()
})

function cambiarFiltro(nuevoFiltro) {
  filtro.value = nuevoFiltro
}

const handleEliminarAmigo = async (amigo) => {
  const result = await confirmDelete(
    'Eliminar Amigo',
    `¿Estás seguro de que deseas eliminar a ${amigo.nombre_amigo} de tu lista de amigos?`
  )
  if (result.isConfirmed) {
    // Prendemos el loading
    actionLoadingId.value = amigo.id_amistad
    actionTypeLoading.value = 'eliminar'
    try {
      await friendStore.removeFriend({ id_amistad: amigo.id_amistad })
      toastInfo('Eliminado', 'Amigo eliminado correctamente', 'success')
    } catch (error) {
      toastInfo('Error', 'Hubo un error al eliminar al amigo', 'error')
    } finally {
      // Apagamos el loading
      actionLoadingId.value = null
      actionTypeLoading.value = ''
    }
  }
}

const handleCancelarSolicitud = async (amigo) => {
  const result = await confirmWarning(
    'Cancelar Solicitud',
    `¿Deseas cancelar la solicitud de amistad enviada a ${amigo.nombre_amigo}?`,
    'Sí, cancelar'
  )
  if (result.isConfirmed) {
    actionLoadingId.value = amigo.id_amistad
    actionTypeLoading.value = 'cancelar'
    try {
      await friendStore.removeFriend({ id_amistad: amigo.id_amistad })
      toastInfo('Cancelada', 'Solicitud cancelada correctamente', 'success')
    } catch (error) {
      toastInfo('Error', 'Hubo un error al cancelar la solicitud', 'error')
    } finally {
      actionLoadingId.value = null
      actionTypeLoading.value = ''
    }
  }
}

const aceptarSolicitud = async (amigo) => {
  actionLoadingId.value = amigo.id_amistad
  actionTypeLoading.value = 'aceptar'
  try {
    await friendStore.acceptFriend({ id_amistad: amigo.id_amistad })
    toastInfo('¡Aceptada!', `Ahora eres amigo de ${amigo.nombre_amigo}`, 'success')
  } catch (e) {
    toastInfo('Error', 'Error al aceptar la solicitud', 'error')
  } finally {
    actionLoadingId.value = null
    actionTypeLoading.value = ''
  }
}

const rechazarSolicitud = async (amigo) => {
  const result = await confirmDelete(
    'Rechazar Solicitud',
    `¿Estás seguro de rechazar la solicitud de ${amigo.nombre_amigo}?`
  )
  if (result.isConfirmed) {
    actionLoadingId.value = amigo.id_amistad
    actionTypeLoading.value = 'rechazar'
    try {
      await friendStore.rejectFriend({ id_amistad: amigo.id_amistad })
      toastInfo('Rechazada', 'Solicitud de amistad rechazada', 'info')
    } catch (e) {
      toastInfo('Error', 'Error al rechazar la solicitud', 'error')
    } finally {
      actionLoadingId.value = null
      actionTypeLoading.value = ''
    }
  }
}
</script>

<template>
  <div class="container">
    <div class="header">
      <div>
        <h2>Mis Amigos</h2>
        <p>Mantente conectado con las personas que más quieres</p>
      </div>

      <router-link class="btn-primary" :to="{ name: 'friends-add' }">
        + Agregar Amigo
      </router-link>
    </div>

    <input v-model="search" placeholder="Buscar amigo por nombre..." class="search" />

    <div class="tabs">
      <button @click="cambiarFiltro('AMIGO')" :class="['tab', filtro === 'AMIGO' && 'active']">Mis Amigos</button>
      <button @click="cambiarFiltro('SOLICITUD_ENVIADA')"
        :class="['tab', filtro === 'SOLICITUD_ENVIADA' && 'active']">Solicitudes Enviadas</button>
      <button @click="cambiarFiltro('SOLICITUD_RECIBIDA')"
        :class="['tab', filtro === 'SOLICITUD_RECIBIDA' && 'active']">Solicitudes Recibidas</button>
    </div>

    <div v-if="friendStore.loading" class="loading">Cargando...</div>

    <div v-else-if="amigosFiltrados.length === 0" class="empty-state">
      <p v-if="filtro === 'AMIGO'">Aún no tienes amigos en tu lista.</p>
      <p v-else-if="filtro === 'SOLICITUD_ENVIADA'">No tienes solicitudes enviadas pendientes.</p>
      <p v-else-if="filtro === 'SOLICITUD_RECIBIDA'">No tienes solicitudes de amistad recibidas.</p>
    </div>

    <div v-else>
      <div v-for="amigo in amigosFiltrados" :key="amigo.id_amistad" class="card">
        <div class="left">
          <div class="avatar">{{ amigo.nombre_amigo?.charAt(0) || '?' }}</div>
          <div>
            <div class="nombre">
              {{ amigo.nombre_amigo }}
              <span v-if="amigo.estado === 'ACEPTADA'" class="badge activo">AMIGO</span>
              <span v-else-if="amigo.estado === 'PENDIENTE' && amigo.solicitado_por_mi"
                class="badge amarillo">ENVIADA</span>
              <span v-else-if="amigo.estado === 'PENDIENTE' && !amigo.solicitado_por_mi"
                class="badge expirado">RECIBIDA</span>
            </div>
            <div class="info" v-if="amigo.created_at">Fecha: {{ new Date(amigo.created_at).toLocaleDateString() }}</div>
          </div>
        </div>

        <div class="actions">
          <template v-if="filtro === 'AMIGO'">
            <button 
              class="btn-delete" 
              @click="handleEliminarAmigo(amigo)"
              :disabled="actionLoadingId === amigo.id_amistad"
            >
              <IconTrash v-if="!(actionLoadingId === amigo.id_amistad && actionTypeLoading === 'eliminar')" class="icon-svg" />
              <span>{{ actionLoadingId === amigo.id_amistad && actionTypeLoading === 'eliminar' ? 'Eliminando...' : 'Eliminar' }}</span>
            </button>
          </template>

          <template v-else-if="filtro === 'SOLICITUD_ENVIADA'">
            <button 
              class="btn-revoke" 
              @click="handleCancelarSolicitud(amigo)"
              :disabled="actionLoadingId === amigo.id_amistad"
            >
              {{ actionLoadingId === amigo.id_amistad && actionTypeLoading === 'cancelar' ? 'Cancelando...' : 'Cancelar Solicitud' }}
            </button>
          </template>

          <template v-else-if="filtro === 'SOLICITUD_RECIBIDA'">
            <button 
              class="btn-accept" 
              @click="aceptarSolicitud(amigo)"
              :disabled="actionLoadingId === amigo.id_amistad"
            >
              {{ actionLoadingId === amigo.id_amistad && actionTypeLoading === 'aceptar' ? 'Aceptando...' : 'Aceptar' }}
            </button>
            
            <button 
              class="btn-delete" 
              @click="rechazarSolicitud(amigo)"
              :disabled="actionLoadingId === amigo.id_amistad"
            >
              <IconTrash v-if="!(actionLoadingId === amigo.id_amistad && actionTypeLoading === 'rechazar')" class="icon-svg" />
              <span>{{ actionLoadingId === amigo.id_amistad && actionTypeLoading === 'rechazar' ? 'Rechazando...' : 'Rechazar' }}</span>
            </button>
          </template>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.container { padding: 20px; font-family: var(--p-font-family); }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.header h2 { font-size: 22px; font-weight: 700; color: var(--p-surface-900); margin: 0; }
.header p { color: var(--p-surface-500); font-size: 14px; margin-top: 4px; }

.search { width: 100%; padding: 10px; border-radius: var(--p-border-radius-medium); border: 1px solid var(--p-surface-200); margin-bottom: 12px; outline: none; }
.search:focus { border-color: var(--p-primary-700); }

.tabs { display: flex; gap: 10px; margin-bottom: 16px; }
.tab { flex: 1; padding: 10px; border-radius: var(--p-border-radius-medium); background: var(--p-surface-100); color: var(--p-surface-900); border: none; cursor: pointer; transition: background 0.2s ease; }
.tab:hover { background: var(--p-surface-200); }
.tab.active { background: var(--p-primary-100); color: var(--p-primary-700); font-weight: 600; }

.empty-state { background: white; border-radius: var(--p-border-radius-medium); padding: 20px 40px; text-align: center; color: var(--p-surface-900); font-size: 16px; font-weight: 500; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--p-surface-200); margin: 40px auto; max-width: 600px; }

.card { display: flex; justify-content: space-between; align-items: center; background: white; border-radius: var(--p-border-radius-medium); padding: 14px; margin-bottom: 12px; border: 1px solid var(--p-surface-200); }
.left { display: flex; gap: 12px; align-items: center;}
.avatar { width: 42px; height: 42px; background: var(--p-surface-100); color: var(--p-primary-700); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; text-transform: uppercase; }
.nombre { font-weight: 600; font-size: 15px; color: var(--p-surface-900); margin-bottom: 4px; }
.info { font-size: 13px; color: var(--p-surface-500); }
.actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* ======== BOTONES ======== */
.btn-primary { background-color: var(--p-primary-700); color: white; border: none; padding: 8px 14px; border-radius: var(--p-border-radius-medium); cursor: pointer; text-decoration: none; transition: background-color 0.2s ease; font-weight: 500; font-size: 14px;}
.btn-primary:hover { background-color: var(--p-primary-800); }

.btn-accept { background: var(--state-success, #16a34a); color: white; border: none; padding: 6px 14px; border-radius: var(--p-border-radius-medium); cursor: pointer; font-weight: 600; font-size: 13px; transition: 0.2s;}
.btn-accept:hover:not(:disabled) { background: #15803d; }
.btn-accept:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-delete { display: flex; align-items: center; gap: 6px; background: #fee2e2; border: none; padding: 6px 10px; border-radius: var(--p-border-radius-medium); color: #b91c1c; cursor: pointer; transition: 0.2s; font-weight: 600; font-size: 13px;}
.btn-delete:hover:not(:disabled) { background: #fecaca; }
.btn-delete:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-revoke { background: #fef08a; border: none; padding: 6px 10px; border-radius: var(--p-border-radius-medium); color: #854d0e; cursor: pointer; font-weight: 600; font-size: 13px; transition: 0.2s; }
.btn-revoke:hover:not(:disabled) { background: #fde047; }
.btn-revoke:disabled { opacity: 0.6; cursor: not-allowed; }

/* ICONOS */
.icon-svg { width: 16px; height: 16px; }
.btn-delete .icon-svg { fill: none; stroke: currentColor; }
.btn-delete .icon-svg * { fill: none; stroke: currentColor; }

.badge { margin-left: 10px; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: transparent; border: 1.5px solid; }
.badge.activo { color: var(--state-success, #16a34a); border-color: var(--state-success, #16a34a); }
.badge.expirado { color: var(--state-error, #dc2626); border-color: var(--state-error, #dc2626); }
.badge.amarillo { color: #ca8a04; border-color: #ca8a04; }

.loading { text-align: center; padding: 20px; color: var(--p-surface-500); }
</style>