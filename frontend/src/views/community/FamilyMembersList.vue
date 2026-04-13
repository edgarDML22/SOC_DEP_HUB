<script setup>
import { ref, onMounted, computed } from 'vue'
import { useFamilyStore } from '@/stores/community/familyStore'
import { useAlerts } from '@/composables/useAlerts' 
import Select from 'primevue/select'
import IconQR from '@/components/icons/IconQr.vue'
import IconEdit from '@/components/icons/IconEdit.vue'      
import IconTrash from '@/components/icons/IconTrash.vue'

const familyStore = useFamilyStore()
const { toastInfo, confirmDelete } = useAlerts() 

const search = ref('')

// VARIABLE PARA CONTROLAR EL BOTÓN DE ELIMINAR 
const actionLoadingId = ref(null)

// ESTADO PARA EL MODAL DE EDICIÓN 
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
      m.nombre_completo.toLowerCase().includes(s)
    )
  }
  return resultado
})

// --- FUNCIONES ---
const abrirModalEditar = (m) => {
  formMiembro.value = {
    id: m.id_miembro,
    nombre: m.nombre_completo,
    parentesco: m.parentesco,
    fecha_nacimiento: m.fecha_nacimiento,
    genero: m.genero,
    correo: m.correo || ''
  }
  showModal.value = true
}

const abrirModalEliminar = async (m) => {
  const result = await confirmDelete(
    'Eliminar Miembro Familiar',
    `¿Estás seguro de que deseas eliminar a ${m.nombre_completo}? Esta acción no se puede deshacer.`
  )
  
  if (result.isConfirmed) {
    // Prendemos el loading para este ID
    actionLoadingId.value = m.id_miembro

    try {
      await familyStore.deleteMiembroFamiliar(m.id_miembro)
      toastInfo('Eliminado', 'Miembro familiar removido de tu cuenta', 'success')
    } catch (error) {
      toastInfo('Error', 'No se pudo eliminar al familiar', 'error')
    } finally {
      // Apagamos el loading
      actionLoadingId.value = null
    }
  }
}

const cerrarModal = () => {
  showModal.value = false
  setTimeout(() => {
    formMiembro.value = { id: null, nombre: '', parentesco: '', fecha_nacimiento: '', genero: '' }
  }, 200)
}

const confirmarEdicion = async () => {
  modalLoading.value = true
  try {
    await familyStore.updateMiembroFamiliar(formMiembro.value.id, {
      nombre_completo: formMiembro.value.nombre,
      parentesco: formMiembro.value.parentesco,
      fecha_nacimiento: formMiembro.value.fecha_nacimiento,
      genero: formMiembro.value.genero
    })
    toastInfo('Actualizado', 'Miembro familiar editado con éxito', 'success')
    cerrarModal()
  } catch (error) {
    toastInfo('Error', 'No se pudo editar el familiar', 'error')
  } finally {
    modalLoading.value = false
  }
}

// 2. Variables de estado para el Modal del QR
const showQrModal = ref(false)
const selectedMember = ref(null)

// 3. Función para abrir el modal del QR
const abrirModalQR = (m) => {
  selectedMember.value = m
  showQrModal.value = true
}

const cerrarModalQR = () => {
  showQrModal.value = false
  selectedMember.value = null
}

// 4. Lógica para generar la URL del QR
const generarQrUrl = (codigo) => {
  const data = JSON.stringify({ codigo_qr: codigo, tipo: 'familiar' })
  return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(data)}`
}

// 5. Función para copiar el código al portapapeles
const copiarImagenAlPortapapeles = async (url) => {
  try {
    toastInfo('Procesando', 'Preparando imagen...', 'info')

    const response = await fetch(url)
    const blob = await response.blob()

    await navigator.clipboard.write([
      new ClipboardItem({ [blob.type]: blob })
    ])

    toastInfo('¡Listo!', 'Imagen del QR copiada al portapapeles', 'success')
  } catch (err) {
    console.error('Error al copiar imagen:', err)
    toastInfo('Error', 'Tu navegador no permite copiar imágenes directamente. Intenta con clic derecho.', 'error')
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

    <input v-model="search" placeholder="Buscar familiar por nombre..." class="search" />

    <div v-if="familyStore.loading" class="loading">Cargando familiares...</div>

    <div v-else-if="miembrosFiltrados.length === 0" class="empty-state">
      <p>No tienes miembros familiares registrados o no coinciden con la búsqueda.</p>
    </div>

    <div v-else>
      <div v-for="m in miembrosFiltrados" :key="m.id_miembro" class="card">
        <div class="left">
          <div class="avatar">{{ m.nombre_completo.charAt(0) }}</div>
          <div>
            <div class="nombre">{{ m.nombre_completo }}</div>
            <div class="info" v-if="m.parentesco">Parentesco: {{ m.parentesco }}</div>
            <div class="info" v-if="m.fecha_nacimiento">Nacimiento: {{ m.fecha_nacimiento }}</div>
            <div class="info" v-if="m.genero">Género: {{ m.genero }}</div>
            <div class="info" v-if="m.correo">Correo Electrónico: {{ m.correo }}</div>
          </div>
        </div>
        <div class="actions">
          <button class="btn-qr" @click="abrirModalQR(m)" title="Ver Código QR">
            <IconQR class="icon-svg" />
            <span>QR</span>
          </button>
          
          <button class="btn-edit" @click="abrirModalEditar(m)">
            <IconEdit class="icon-svg" />
            <span>Editar</span>
          </button>
          
          <button 
            class="btn-delete" 
            @click="abrirModalEliminar(m)"
            :disabled="actionLoadingId === m.id_miembro"
          >
            <IconTrash v-if="actionLoadingId !== m.id_miembro" class="icon-svg" />
            <span>{{ actionLoadingId === m.id_miembro ? 'Eliminando...' : 'Eliminar' }}</span>
          </button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal-overlay" @mousedown.self="cerrarModal">
      <div class="modal-card">
        <h3>Editar Miembro Familiar</h3>

        <div class="form-group-modal">
          <label>Nombre Completo</label>
          <input v-model="formMiembro.nombre" class="modal-input" />

          <label>Parentesco</label>
          <Select v-model="formMiembro.parentesco" :options="opcionesParentesco" class="modal-select" appendTo="self" />

          <label>Fecha de Nacimiento</label>
          <input type="date" v-model="formMiembro.fecha_nacimiento" class="modal-input" />

          <label>Correo Electrónico (Opcional)</label>
          <input type="email" v-model="formMiembro.correo" class="modal-input" placeholder="correo@ejemplo.com" />

          <label>Género</label>
          <Select v-model="formMiembro.genero" :options="opcionesGenero" optionLabel="label" optionValue="value"
            class="modal-select" appendTo="self" />
        </div>

        <div class="modal-actions">
          <button class="btn-cancel" @click="cerrarModal" :disabled="modalLoading">Cancelar</button>
          <button class="btn-primary-modal" @click="confirmarEdicion" :disabled="modalLoading">
            {{ modalLoading ? 'Procesando...' : 'Guardar Cambios' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showQrModal" class="modal-overlay" @mousedown.self="cerrarModalQR">
      <div class="modal-card qr-modal">
        <h3>Código QR de Acceso</h3>
        <p class="text-muted">Este es el código QR de <strong>{{ selectedMember.nombre_completo }}</strong></p>
        
        <div class="qr-display-container">
          <img 
            :src="generarQrUrl(selectedMember.codigo_qr)" 
            alt="QR Code" 
            class="qr-image-large" 
          />
          </div>

        <div class="modal-actions-center">
          <button class="btn-copy" @click="copiarImagenAlPortapapeles(generarQrUrl(selectedMember.codigo_qr))">
            Copiar Imagen QR
          </button>
          <button class="btn-cancel" @click="cerrarModalQR" style="width: 100%; margin-top: 5px;">Cerrar</button>
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

.search { width: 100%; padding: 10px; border-radius: var(--p-border-radius-medium); border: 1px solid var(--p-surface-200); margin-bottom: 12px; outline: none;}
.search:focus { border-color: var(--p-primary-700); }

/* ESTADO VACÍO */
.empty-state {
  background: white;
  border-radius: var(--p-border-radius-medium);
  padding: 20px 40px;
  text-align: center;
  color: var(--p-surface-900);
  font-size: 16px;
  font-weight: 500;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
  border: 1px solid var(--p-surface-200);
  margin: 40px auto;
  max-width: 600px;
}

.card { display: flex; justify-content: space-between; align-items: center; background: white; border-radius: var(--p-border-radius-medium); padding: 14px; margin-bottom: 12px; border: 1px solid var(--p-surface-200); }
.left { display: flex; gap: 12px; }
.avatar { width: 42px; height: 42px; background: var(--p-surface-100); color: var(--p-primary-700); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
.nombre { font-weight: 600; font-size: 15px; color: var(--p-surface-900); }
.info { font-size: 13px; color: var(--p-surface-500); }
.actions { display: flex; gap: 8px; flex-wrap: wrap; }

.btn-edit, .btn-delete { 
  display: flex;
  align-items: center;
  gap: 6px;
  border: none; 
  padding: 6px 10px; 
  border-radius: var(--p-border-radius-medium); 
  cursor: pointer; 
  transition: background 0.2s; 
  font-weight: 600;
  font-size: 13px;
}
.btn-edit { background: var(--p-surface-200); color: var(--p-surface-900); }
.btn-edit:hover { background: #d1d5db; }

/* AJUSTE PARA ESTADO DISABLED EN ELIMINAR */
.btn-delete { background: #fee2e2; color: #b91c1c; transition: 0.2s; }
.btn-delete:hover:not(:disabled) { background: #fecaca; }
.btn-delete:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-qr {
  display: flex;
  align-items: center;
  gap: 6px;
  background: var(--p-surface-100);
  color: var(--p-primary-700);
  border: 1px solid var(--p-primary-700);
  padding: 6px 10px;
  border-radius: var(--p-border-radius-medium);
  cursor: pointer;
  transition: 0.2s;
  font-weight: 600;
  font-size: 13px;
}
.btn-qr:hover {
  color: white;
  background: var(--p-primary-800);
  border-color: var(--p-primary-500);
}

.icon-svg {
  width: 16px;
  height: 16px;
}
.btn-qr .icon-svg { fill: currentColor; stroke: none; }
.btn-edit .icon-svg, .btn-delete .icon-svg { fill: none; stroke: currentColor; }
.btn-edit .icon-svg *, .btn-delete .icon-svg * { fill: none; stroke: currentColor; }

.btn-primary {
  background-color: var(--p-primary-700);
  color: white;
  padding: 8px 14px;
  border-radius: var(--p-border-radius-medium);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
}

/*  ESTILOS DEL MODAL */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(2px); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-card { background: white; padding: 24px; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
.modal-card h3 { margin-top: 0; margin-bottom: 16px; font-size: 20px; font-weight: 700; color: var(--p-surface-900); }
.form-group-modal { display: flex; flex-direction: column; gap: 8px; }
.form-group-modal label { font-size: 14px; font-weight: 600; margin-top: 8px; color: var(--p-surface-900); }
.form-group-modal label:first-child { margin-top: 0; }
.modal-input { padding: 10px 12px; border: 1px solid var(--p-surface-200); border-radius: 8px; outline: none; font-family: inherit; font-size: 14px; color: var(--p-surface-900); transition: border-color 0.2s, box-shadow 0.2s; }
.modal-input:focus { border-color: var(--p-primary-700); box-shadow: 0 0 0 2px rgba(29, 78, 216, 0.2); }

:deep(.modal-select) { width: 100%; border-radius: 8px !important; border: 1px solid var(--p-surface-200) !important; font-family: inherit !important; background-color: white; transition: border-color 0.2s, box-shadow 0.2s; box-shadow: none !important; }
:deep(.modal-select:hover), :deep(.modal-select.p-focus) { border-color: var(--p-primary-700) !important; box-shadow: 0 0 0 2px rgba(29, 78, 216, 0.2) !important; }
:deep(.modal-select .p-select-label) { padding: 10px 12px !important; font-size: 14px !important; color: var(--p-surface-900) !important; }

.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px; }
.btn-cancel { background: white; color: var(--p-surface-900); border: 1px solid var(--p-surface-200); padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 500; transition: 0.2s; }
.btn-cancel:hover { background: var(--p-surface-100); }
.btn-primary-modal { background: var(--p-primary-700); color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 500; transition: 0.2s; }
.btn-primary-modal:hover { background: var(--p-primary-800); }
.loading { text-align: center; padding: 20px; color: var(--p-surface-500); }

/* ESTILOS PARA EL MODAL DE QR */
.qr-modal {
  text-align: center;
  max-width: 350px !important;
}
.qr-display-container {
  margin: 20px 0;
  padding: 15px;
  background: var(--p-surface-50);
  border-radius: 12px;
  border: 1px dashed var(--p-surface-300);
}
.qr-image-large {
  width: 200px;
  height: 200px;
  border-radius: 8px;
}
.modal-actions-center {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
}
.btn-copy {
  background: var(--p-primary-700);
  color: white;
  border: none;
  padding: 10px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.2s;
}
.btn-copy:hover {
  background: var(--p-primary-800);
}
</style>