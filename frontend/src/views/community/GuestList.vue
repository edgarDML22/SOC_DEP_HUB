<script setup>
import { ref, onMounted, computed } from 'vue'
import { useGuestStore } from '@/stores/guestStore'
import { useToast } from 'primevue/usetoast' 
const toast = useToast()

const guestStore = useGuestStore()
const filtro = ref('TODOS')
const search = ref('')

// --- ESTADO PARA LOS MODALES ---
const showModal = ref(false)
const modalType = ref('') // Puede ser 'edit' o 'delete'
const modalLoading = ref(false)

// Formulario temporal para editar/eliminar
const formInvitado = ref({
  id: null,
  nombre: '',
  correo: '',
  telefono: ''
})

onMounted(() => {
  guestStore.fetchInvitados()
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
    resultado = resultado.filter(inv =>
      inv.nombre.toLowerCase().includes(s) ||
      String(inv.id).includes(s)
    )
  }
  return resultado
})

// --- FUNCIONES DEL MODAL ---
const abrirModalEditar = (inv) => {
  formInvitado.value = { id: inv.id, nombre: inv.nombre, correo: inv.correo, telefono: inv.telefono }
  modalType.value = 'edit'
  showModal.value = true
}

const abrirModalEliminar = (inv) => {
  formInvitado.value = { id: inv.id, nombre: inv.nombre }
  modalType.value = 'delete'
  showModal.value = true
}

const cerrarModal = () => {
  showModal.value = false
  setTimeout(() => {
    formInvitado.value = { id: null, nombre: '', correo: '', telefono: '' }
  }, 200) // Pequeño delay para que no se vea el cambio mientras desaparece
}

const confirmarAccion = async () => {
  modalLoading.value = true
  try {
    if (modalType.value === 'edit') {
      await guestStore.updateInvitado(formInvitado.value.id, {
        nombre_invitado: formInvitado.value.nombre,
        correo: formInvitado.value.correo,
        telefono: formInvitado.value.telefono
      })
      toast.add({ severity: 'success', summary: 'Actualizado', detail: 'El invitado se editó con éxito', life: 3000 })
    } else if (modalType.value === 'delete') {
      await guestStore.deleteInvitado(formInvitado.value.id)
      toast.add({ severity: 'info', summary: 'Eliminado', detail: 'El invitado ha sido removido', life: 3000 })
    }
    cerrarModal()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo completar la operación', life: 3000 })
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
      </div>
      <router-link class="btn-primary" :to="{ name: 'guests-add' }">
        + Agregar Invitado
      </router-link>
    </div>

    <input v-model="search" placeholder="Buscar invitado por nombre o ID..." class="search" />

    <div class="tabs">
      <button @click="cambiarFiltro('TODOS')" :class="['tab', filtro === 'TODOS' && 'active']">Todos</button>
      <button @click="cambiarFiltro('ACTIVO')" :class="['tab', filtro === 'ACTIVO' && 'active']">Activos</button>
      <button @click="cambiarFiltro('EXPIRADO')" :class="['tab', filtro === 'EXPIRADO' && 'active']">Expirados</button>
    </div>

    <div v-if="guestStore.loading" class="loading">Cargando invitados...</div>

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
          </div>
        </div>
        <div class="actions">
          <button class="btn-edit" @click="abrirModalEditar(inv)">Editar</button>
          <button class="btn-delete" @click="abrirModalEliminar(inv)">Eliminar</button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal-overlay" @mousedown.self="cerrarModal">
      <div class="modal-card">

        <h3 v-if="modalType === 'edit'">Editar Invitado</h3>
        <h3 v-else class="text-danger">Eliminar Invitado</h3>

        <div v-if="modalType === 'edit'" class="form-group">
          <label>Nombre</label>
          <input v-model="formInvitado.nombre" class="modal-input" />
          <label>Correo</label>
          <input v-model="formInvitado.correo" class="modal-input" />
          <label>Teléfono</label>
          <input v-model="formInvitado.telefono" class="modal-input" />
        </div>

        <div v-else class="delete-warning">
          <p>¿Estás seguro de que deseas eliminar a <strong>{{ formInvitado.nombre }}</strong>?</p>
          <p class="text-muted">Esta acción no se puede deshacer.</p>
        </div>

        <div class="modal-actions">
          <button class="btn-cancel" @click="cerrarModal" :disabled="modalLoading">Cancelar</button>
          <button :class="modalType === 'edit' ? 'btn-primary' : 'btn-delete-confirm'" @click="confirmarAccion"
            :disabled="modalLoading">
            {{ modalLoading ? 'Procesando...' : (modalType === 'edit' ? 'Guardar Cambios' : 'Sí, Eliminar') }}
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<style scoped>
.container {
  padding: 20px;
  font-family: var(--p-font-family);
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.header h2 {
  font-size: 22px;
  font-weight: 700;
  color: var(--p-surface-900);
}

.header p {
  color: var(--p-surface-500);
  font-size: 14px;
}

.search {
  width: 100%;
  padding: 10px;
  border-radius: var(--p-border-radius-medium);
  border: 1px solid var(--p-surface-200);
  margin-bottom: 12px;
}

.tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 16px;
}

.tab {
  flex: 1;
  padding: 10px;
  border-radius: var(--p-border-radius-medium);
  background: var(--p-surface-100);
  color: var(--p-surface-900);
  border: none;
  cursor: pointer;
  transition: background 0.2s ease;
}

.tab:hover {
  background: var(--p-surface-200);
}

.tab.active {
  background: var(--p-primary-100);
  /* Un azul muy claro */
  color: var(--p-primary-700);
  font-weight: 600;
}

.card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  border-radius: var(--p-border-radius-medium);
  padding: 14px;
  margin-bottom: 12px;
  border: 1px solid var(--p-surface-200);
}

.left {
  display: flex;
  gap: 12px;
}

.avatar {
  width: 42px;
  height: 42px;
  background: var(--p-surface-100);
  color: var(--p-primary-700);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.nombre {
  font-weight: 600;
  font-size: 15px;
  color: var(--p-surface-900);
}

.info {
  font-size: 13px;
  color: var(--p-surface-500);
}

.actions {
  display: flex;
  gap: 8px;
}

.btn-edit {
  background: var(--p-surface-200);
  color: var(--p-surface-900);
  border: none;
  padding: 6px 10px;
  border-radius: var(--p-border-radius-medium);
  cursor: pointer;
  transition: background 0.2s ease;
}

/* Hover añadido para Editar */
.btn-edit:hover {
  background: #d1d5db;
}

.btn-delete {
  background: #fee2e2;
  border: none;
  padding: 6px 10px;
  border-radius: var(--p-border-radius-medium);
  color: #b91c1c;
  cursor: pointer;
  transition: background 0.2s ease;
}

/* Hover añadido para Eliminar */
.btn-delete:hover {
  background: #fecaca;
}

.badge {
  margin-left: 10px;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  background: transparent;
  border: 1.5px solid;
}

.badge.activo {
  color: #16a34a;
  border-color: #16a34a;
}

.badge.expirado {
  color: #dc2626;
  border-color: #dc2626;
}

.btn-primary {
  background-color: var(--p-primary-700);
  color: white;
  border: none;
  padding: 8px 14px;
  border-radius: var(--p-border-radius-medium);
  cursor: pointer;
  text-decoration: none;
  transition: background-color 0.2s ease;
}

/* Hover principal desde el PDF */
.btn-primary:hover {
  background-color: var(--p-primary-800);
}

/* ================= NUEVOS ESTILOS PARA EL MODAL ================= */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(2px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-card {
  background: white;
  padding: 24px;
  border-radius: var(--p-border-radius-medium);
  width: 90%;
  max-width: 400px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-card h3 {
  margin-top: 0;
  margin-bottom: 16px;
  font-size: 18px;
  color: var(--p-surface-900);
}

.text-danger {
  color: #dc2626;
}

.text-muted {
  color: var(--p-surface-500);
  font-size: 14px;
  margin-top: 4px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: var(--p-surface-900);
  margin-top: 4px;
}

.modal-input {
  padding: 10px;
  border: 1px solid var(--p-surface-200);
  border-radius: var(--p-border-radius-medium);
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  color: var(--p-surface-900);
}

.modal-input:focus {
  border-color: var(--p-primary-700);
  box-shadow: 0 0 0 2px rgba(29, 78, 216, 0.2);
  /* rgba equivalente al hex #1d4ed8 de p-primary-700 */
}

.delete-warning p {
  margin: 0;
  font-size: 15px;
  color: var(--p-surface-900);
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
}

.btn-cancel {
  background: white;
  color: var(--p-surface-900);
  border: 1px solid var(--p-surface-200);
  padding: 8px 16px;
  border-radius: var(--p-border-radius-medium);
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s ease;
}

.btn-cancel:hover {
  background: var(--p-surface-100);
}

.btn-delete-confirm {
  background: #dc2626;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: var(--p-border-radius-medium);
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s ease;
}

.btn-delete-confirm:hover {
  background: #b91c1c;
}
</style>