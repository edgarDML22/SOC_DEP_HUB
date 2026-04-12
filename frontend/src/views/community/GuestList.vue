<script setup>
import { ref, onMounted, computed } from 'vue'
import { useGuestStore } from '@/stores/guestStore'
import { useAlerts } from '@/composables/useAlerts' // <-- Importas tu nueva herramienta perrona
import api from '@/services/api'

// Alertas personalizadas
const { toastInfo, confirmDelete, confirmWarning, successModal } = useAlerts()
const guestStore = useGuestStore()

const filtro = ref('TODOS')
const search = ref('')

const cancelingId = ref(null)

// --- ESTADO PARA EL MODAL DE EDICIÓN ---
const showModal = ref(false)
const modalLoading = ref(false)

const formInvitado = ref({
  id: null,
  nombre: '',
  correo: '',
  telefono: ''
})

onMounted(() => {
  guestStore.fetchInvitados()
})

const activeCount = computed(() => {
  return guestStore.invitados.filter(inv => inv.estatus_acceso === 'ACTIVO').length
})

const cambiarFiltro = (nuevo) => {
  filtro.value = nuevo
}

const invitadosFiltrados = computed(() => {
  let resultado = guestStore.invitados

  if (filtro.value !== 'TODOS') {
    resultado = resultado.filter(inv => inv.estatus_acceso === filtro.value)
  }

  if (search.value) {
    const s = search.value.toLowerCase()
    resultado = resultado.filter(inv => inv.nombre.toLowerCase().includes(s))
  }
  return resultado
})

const handleCancel = async (inv) => {

  const result = await confirmWarning(
    '¿Estás seguro?', 
    `Se cancelará el pase de ${inv.nombre}. Esta acción liberará tu cuota de invitados.`,
    'Sí, cancelar pase'
  )

  if (result.isConfirmed) {
    cancelingId.value = inv.id 
    try {
      await api.put(`guests/passes/${inv.id}/cancel`)
      inv.estatus_acceso = 'EXPIRADO'
      
      successModal('¡Cancelado!', 'El pase ha sido revocado correctamente.')
    } catch (error) {
      const errorMsg = error.response?.data?.message || 'No se pudo procesar la cancelación.'
      toastInfo('Error', errorMsg, 'error')
    } finally {
      cancelingId.value = null
    }
  }
}

// --- FUNCIONES (Editar usa Vue HTML, Eliminar usa SWAL de diseño) ---
const abrirModalEditar = (inv) => {
  formInvitado.value = { id: inv.id, nombre: inv.nombre, correo: inv.correo, telefono: inv.telefono }
  showModal.value = true
}

const abrirModalEliminar = async (inv) => {
  // SweetAlert para eliminar
  const result = await confirmDelete(
    'Eliminar Invitado',
    `¿Estás seguro de que deseas eliminar a ${inv.nombre}? Esta acción no se puede deshacer.`
  )
  
  if (result.isConfirmed) {
    try {
      await guestStore.deleteInvitado(inv.id)
      toastInfo('Eliminado', 'El invitado ha sido removido de la comunidad', 'success')
    } catch (error) {
      toastInfo('Error', 'No se pudo completar la operación', 'error')
    }
  }
}

const cerrarModal = () => {
  showModal.value = false
  setTimeout(() => {
    formInvitado.value = { id: null, nombre: '', correo: '', telefono: '' }
  }, 200) 
}

const confirmarEdicion = async () => {
  modalLoading.value = true
  try {
    await guestStore.updateInvitado(formInvitado.value.id, {
      nombre_invitado: formInvitado.value.nombre,
      correo: formInvitado.value.correo,
      telefono: formInvitado.value.telefono
    })
    toastInfo('Actualizado', 'El invitado se editó con éxito', 'success')
    cerrarModal()
  } catch (error) {
    toastInfo('Error', 'No se pudo editar el invitado', 'error')
  } finally {
    modalLoading.value = false
  }
}
</script>

<template>
  <div class="container">
    <div class="header">
      <div>
        <h2>Mis Invitados</h2>
        <p>Consulta el estatus de tus invitados</p>
        
        <div class="quota-info" :class="{ 'limit-reached': activeCount >= 5 }">
           Cupo utilizado: <strong>{{ activeCount }} / 5</strong>
        </div>
      </div>
      
      <router-link class="btn-primary" :to="{ name: 'guests-add' }">
        + Agregar Invitado
      </router-link>
    </div>

    <input v-model="search" placeholder="Buscar invitado por nombre..." class="search" />

    <div class="tabs">
      <button @click="cambiarFiltro('TODOS')" :class="['tab', filtro === 'TODOS' && 'active']">Todos</button>
      <button @click="cambiarFiltro('ACTIVO')" :class="['tab', filtro === 'ACTIVO' && 'active']">Activos</button>
      <button @click="cambiarFiltro('EXPIRADO')" :class="['tab', filtro === 'EXPIRADO' && 'active']">Expirados</button>
    </div>

    <div v-if="guestStore.loading" class="loading">Cargando invitados...</div>

    <div v-else-if="invitadosFiltrados.length === 0" class="empty-state">
      <p v-if="filtro === 'TODOS'">No hay invitados registrados en este momento</p>
      <p v-else-if="filtro === 'ACTIVO'">No hay invitados con su pase activo en este momento</p>
      <p v-else-if="filtro === 'EXPIRADO'">No hay invitados con pase expirado en este momento</p>
    </div>

    <div v-else>
      <div v-for="inv in invitadosFiltrados" :key="inv.id" class="card">
        <div class="left">
          <div class="avatar">{{ inv.nombre.charAt(0) }}</div>
          <div>
            <div class="nombre">
              {{ inv.nombre }}
              <span class="badge" :class="inv.estatus_acceso.toLowerCase()">
                {{ inv.estatus_acceso === 'EXPIRADO' ? 'EXPIRADO' : inv.estatus_acceso }}
              </span>
            </div>
            <div class="info" v-if="inv.telefono">Teléfono: {{ inv.telefono }}</div>
            <div class="info" v-if="inv.correo">Correo: {{ inv.correo }}</div>
            <div class="info" v-if="inv.fecha_expiracion">Expira: {{ inv.fecha_expiracion }}</div>
          </div>
        </div>

        <div class="actions">
          <button class="btn-edit" @click="abrirModalEditar(inv)">Editar</button>
          <button class="btn-delete" @click="abrirModalEliminar(inv)">Eliminar</button>
          
          <button
            v-if="inv.estatus_acceso === 'ACTIVO'"
            class="btn-revoke"
            @click="handleCancel(inv)"
            :disabled="cancelingId === inv.id"
          >
            {{ cancelingId === inv.id ? 'Cancelando...' : 'Cancelar Pase' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal-overlay" @mousedown.self="cerrarModal">
      <div class="modal-card">
        <h3>Editar Invitado</h3>
        <div class="form-group">
          <label>Nombre</label>
          <input v-model="formInvitado.nombre" class="modal-input" />
          <label>Correo</label>
          <input v-model="formInvitado.correo" class="modal-input" />
          <label>Teléfono</label>
          <input v-model="formInvitado.telefono" class="modal-input" />
        </div>

        <div class="modal-actions">
          <button class="btn-cancel" @click="cerrarModal" :disabled="modalLoading">Cancelar</button>
          <button class="btn-primary" @click="confirmarEdicion" :disabled="modalLoading">
            {{ modalLoading ? 'Procesando...' : 'Guardar Cambios' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>

.container { padding: 20px; font-family: var(--p-font-family); }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.header h2 { font-size: 22px; font-weight: 700; color: var(--p-surface-900); margin:0; }
.header p { color: var(--p-surface-500); font-size: 14px; margin-top: 4px;}

.quota-info { margin-top: 8px; font-size: 14px; color: var(--p-primary-700); }
.limit-reached { color: var(--state-error); }

.search { width: 100%; padding: 10px; border-radius: var(--p-border-radius-medium); border: 1px solid var(--p-surface-200); margin-bottom: 12px; outline: none;}
.search:focus { border-color: var(--p-primary-700); }

.tabs { display: flex; gap: 10px; margin-bottom: 16px; }
.tab { flex: 1; padding: 10px; border-radius: var(--p-border-radius-medium); background: var(--p-surface-100); color: var(--p-surface-900); border: none; cursor: pointer; transition: background 0.2s ease; }
.tab:hover { background: var(--p-surface-200); }
.tab.active { background: var(--p-primary-100); color: var(--p-primary-700); font-weight: 600; }

/* ESTILO PARA PESTAÑAS VACÍAS */
.empty-state {
  background: white;
  border-radius: var(--p-border-radius-medium);
  padding: 20px 40px;
  text-align: center;
  color: var(--p-surface-900);
  font-size: 18px;
  font-weight: 500;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
  border: 1px solid var(--p-surface-200);
  margin: 40px auto;
  max-width: 600px;
}

.card { display: flex; justify-content: space-between; align-items: center; background: white; border-radius: var(--p-border-radius-medium); padding: 14px; margin-bottom: 12px; border: 1px solid var(--p-surface-200); }
.left { display: flex; gap: 12px; align-items: center; }
.avatar { width: 42px; height: 42px; background: var(--p-surface-100); color: var(--p-primary-700); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
.nombre { font-weight: 600; font-size: 15px; color: var(--p-surface-900); margin-bottom: 4px; }
.info { font-size: 13px; color: var(--p-surface-500); line-height: 1.4; }
.actions { display: flex; gap: 8px; flex-wrap: wrap; }

.btn-edit { background: var(--p-surface-200); color: var(--p-surface-900); border: none; padding: 6px 10px; border-radius: var(--p-border-radius-medium); cursor: pointer; font-weight: 500;}
.btn-edit:hover { background: #d1d5db; }
.btn-delete { background: #fee2e2; border: none; padding: 6px 10px; border-radius: var(--p-border-radius-medium); color: #b91c1c; cursor: pointer; font-weight: 500;}
.btn-delete:hover { background: #fecaca; }
.btn-revoke { background: #fef08a; border: none; padding: 6px 10px; border-radius: var(--p-border-radius-medium); color: #854d0e; cursor: pointer; font-weight: 600; transition: 0.2s; }
.btn-revoke:hover { background: #fde047; }
.btn-revoke:disabled { background: #fef9c3; cursor: not-allowed; opacity: 0.7; }

.badge { margin-left: 10px; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: transparent; border: 1.5px solid; }
.badge.activo { color: var(--state-success); border-color: var(--state-success); }
.badge.expirado { color: var(--state-error); border-color: var(--state-error); }

/* MODAL DE VUE (Para Editar) */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(2px); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-card { background: white; padding: 24px; border-radius: var(--p-border-radius-medium); width: 90%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
.modal-card h3 { margin-top: 0; margin-bottom: 16px; font-size: 18px; color: var(--p-surface-900); }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-group label { font-size: 13px; font-weight: 600; color: var(--p-surface-900); margin-top: 4px; }
.modal-input { padding: 10px; border: 1px solid var(--p-surface-200); border-radius: var(--p-border-radius-medium); outline: none; transition: border-color 0.2s; color: var(--p-surface-900); }
.modal-input:focus { border-color: var(--p-primary-700); }
.modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
.loading { text-align: center; padding: 20px; color: var(--p-surface-500); }
</style>