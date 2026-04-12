<script setup>
import { ref, onMounted, computed } from 'vue'
import { useFamilyStore } from '@/stores/community/familyStore'
import { useToast } from 'primevue/usetoast'
import Select from 'primevue/select'

const familyStore = useFamilyStore()
const toast = useToast()

const search = ref('')

// --- ESTADO PARA LOS MODALES ---
const showModal = ref(false)
const modalType = ref('')
const modalLoading = ref(false)

const formMiembro = ref({
  id: null,
  nombre: '',
  parentesco: '',
  fecha_nacimiento: '',
  genero: ''
})

const opcionesParentesco = ref(['CONYUGE', 'HIJO/A', 'OTRO'])
const opcionesGenero = ref([
  { label: 'MASCULINO', value: 'M' },
  { label: 'FEMENINO', value: 'F' },
  { label: 'OTRO', value: 'OTRO' }
])

onMounted(() => {
  familyStore.fetchMiembrosFamiliares()
})

const miembrosFiltrados = computed(() => {
  let resultado = familyStore.miembrosFamiliares

  if (search.value) {
    const s = search.value.toLowerCase()
    resultado = resultado.filter(m =>
      m.nombre_completo.toLowerCase().includes(s) ||
      String(m.id_miembro).includes(s)
    )
  }
  return resultado
})

// --- FUNCIONES DEL MODAL ---
const abrirModalEditar = (m) => {
  formMiembro.value = {
    id: m.id_miembro,
    nombre: m.nombre_completo,
    parentesco: m.parentesco,
    fecha_nacimiento: m.fecha_nacimiento,
    genero: m.genero
  }
  modalType.value = 'edit'
  showModal.value = true
}

const abrirModalEliminar = (m) => {
  formMiembro.value = { id: m.id_miembro, nombre: m.nombre_completo }
  modalType.value = 'delete'
  showModal.value = true
}

const cerrarModal = () => {
  showModal.value = false
  setTimeout(() => {
    formMiembro.value = { id: null, nombre: '', parentesco: '', fecha_nacimiento: '', genero: '' }
  }, 200)
}

const confirmarAccion = async () => {
  modalLoading.value = true
  try {
    if (modalType.value === 'edit') {
      await familyStore.updateMiembroFamiliar(formMiembro.value.id, {
        nombre_completo: formMiembro.value.nombre,
        parentesco: formMiembro.value.parentesco,
        fecha_nacimiento: formMiembro.value.fecha_nacimiento,
        genero: formMiembro.value.genero
      })
      toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Miembro familiar editado con éxito', life: 3000 })
    } else if (modalType.value === 'delete') {
      await familyStore.deleteMiembroFamiliar(formMiembro.value.id)
      toast.add({ severity: 'info', summary: 'Eliminado', detail: 'Miembro familiar removido', life: 3000 })
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
        <h2>Miembros Familiares</h2>
        <p>Consulta los miembros asociados a tu cuenta</p>
      </div>
      <router-link class="btn-primary" :to="{ name: 'family-members-add' }">
        + Agregar Miembro Familiar
      </router-link>
    </div>

    <input v-model="search" placeholder="Buscar por nombre o ID..." class="search" />

    <div v-if="familyStore.loading" class="loading">Cargando familiares...</div>

    <div v-else>
      <div v-for="m in miembrosFiltrados" :key="m.id_miembro" class="card">
        <div class="left">
          <div class="avatar">{{ m.nombre_completo.charAt(0) }}</div>
          <div>
            <div class="nombre">{{ m.nombre_completo }}</div>
            <div class="info" v-if="m.parentesco">Parentesco: {{ m.parentesco }}</div>
            <div class="info" v-if="m.fecha_nacimiento">Nacimiento: {{ m.fecha_nacimiento }}</div>
            <div class="info" v-if="m.genero">Género: {{ m.genero }}</div>
          </div>
        </div>
        <div class="actions">
          <button class="btn-edit" @click="abrirModalEditar(m)">Editar</button>
          <button class="btn-delete" @click="abrirModalEliminar(m)">Eliminar</button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal-overlay" @mousedown.self="cerrarModal">
      <div class="modal-card">
        <h3 v-if="modalType === 'edit'">Editar Miembro Familiar</h3>
        <h3 v-else class="text-danger">Eliminar Miembro Familiar</h3>

        <div v-if="modalType === 'edit'" class="form-group-modal">
          <label>Nombre Completo</label>
          <input v-model="formMiembro.nombre" class="modal-input" />

          <label>Parentesco</label>
          <Select v-model="formMiembro.parentesco" :options="opcionesParentesco" class="modal-select" appendTo="self" />

          <label>Fecha de Nacimiento</label>
          <input type="date" v-model="formMiembro.fecha_nacimiento" class="modal-input" />

          <label>Género</label>
          <Select v-model="formMiembro.genero" :options="opcionesGenero" optionLabel="label" optionValue="value"
            class="modal-select" appendTo="self" />
        </div>

        <div v-else class="delete-warning">
          <p>¿Estás seguro de que deseas eliminar a <strong>{{ formMiembro.nombre }}</strong>?</p>
          <p class="text-muted">Esta acción no se puede deshacer.</p>
        </div>

        <div class="modal-actions">
          <button class="btn-cancel" @click="cerrarModal" :disabled="modalLoading">Cancelar</button>
          <button :class="modalType === 'edit' ? 'btn-primary-modal' : 'btn-delete-confirm'" @click="confirmarAccion"
            :disabled="modalLoading">
            {{ modalLoading ? 'Procesando...' : (modalType === 'edit' ? 'Guardar Cambios' : 'Sí, Eliminar') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Estilos base reutilizados de GuestList */
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

.btn-edit,
.btn-delete {
  border: none;
  padding: 6px 10px;
  border-radius: var(--p-border-radius-medium);
  cursor: pointer;
  transition: background 0.2s;
}

.btn-edit {
  background: var(--p-surface-200);
  color: var(--p-surface-900);
}

.btn-edit:hover {
  background: #d1d5db;
}

.btn-delete {
  background: #fee2e2;
  color: #b91c1c;
}

.btn-delete:hover {
  background: #fecaca;
}

.btn-primary {
  background-color: var(--p-primary-700);
  color: white;
  padding: 8px 14px;
  border-radius: var(--p-border-radius-medium);
  text-decoration: none;
  font-size: 14px;
}

/* ================= ESTILOS DEL MODAL (UNIFICADOS) ================= */
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
  border-radius: 12px;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-card h3 {
  margin-top: 0;
  margin-bottom: 16px;
  font-size: 20px;
  font-weight: 700;
  color: var(--p-surface-900);
}

.form-group-modal {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

/* Labels oscuros y con mejor peso visual */
.form-group-modal label {
  font-size: 14px;
  font-weight: 600;
  margin-top: 8px;
  color: var(--p-surface-900);
}

.form-group-modal label:first-child {
  margin-top: 0;
}

/* Inputs de texto nativos */
.modal-input {
  padding: 10px 12px;
  border: 1px solid var(--p-surface-200);
  border-radius: 8px;
  outline: none;
  font-family: inherit;
  font-size: 14px;
  color: var(--p-surface-900);
  transition: border-color 0.2s, box-shadow 0.2s;
}

.modal-input:focus {
  border-color: var(--p-primary-700);
  box-shadow: 0 0 0 2px rgba(29, 78, 216, 0.2);
}

/* Selects de PrimeVue adaptados al diseño del input */
:deep(.modal-select) {
  width: 100%;
  border-radius: 8px !important;
  border: 1px solid var(--p-surface-200) !important;
  font-family: inherit !important;
  background-color: white;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-shadow: none !important;
}

:deep(.modal-select:hover),
:deep(.modal-select.p-focus) {
  border-color: var(--p-primary-700) !important;
  box-shadow: 0 0 0 2px rgba(29, 78, 216, 0.2) !important;
}

/* Relleno interno del Select para que iguale al texto */
:deep(.modal-select .p-select-label) {
  padding: 10px 12px !important;
  font-size: 14px !important;
  color: var(--p-surface-900) !important;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 24px;
}

.btn-cancel {
  background: white;
  color: var(--p-surface-900);
  border: 1px solid var(--p-surface-200);
  padding: 10px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: 0.2s;
}

.btn-cancel:hover {
  background: var(--p-surface-100);
}

.btn-primary-modal {
  background: var(--p-primary-700);
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: 0.2s;
}

.btn-primary-modal:hover {
  background: var(--p-primary-800);
}

.btn-delete-confirm {
  background: #dc2626;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: 0.2s;
}

.btn-delete-confirm:hover {
  background: #b91c1c;
}

.text-danger {
  color: #dc2626;
}

.text-muted {
  font-size: 14px;
  color: var(--p-surface-500);
  margin-top: 4px;
}
</style>