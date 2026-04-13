<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useFamilyStore } from '@/stores/community/familyStore'
import { useAlerts } from '@/composables/useAlerts'
import Select from 'primevue/select'

const router = useRouter()
const familyStore = useFamilyStore()
const { toastInfo } = useAlerts() 

const form = ref({
  nombre: '',
  parentesco: '',
  fecha_nacimiento: '',
  genero: '',
  correo: ''
})

const loadingBtn = ref(false)

const opcionesParentesco = ref(['CONYUGE', 'HIJO/A', 'OTRO'])
const opcionesGenero = ref([
  { label: 'MASCULINO', value: 'M' },
  { label: 'FEMENINO', value: 'F' },
  { label: 'OTRO', value: 'OTRO' }
])

const guardarMiembroFamiliar = async () => {
  loadingBtn.value = true

  try {
    await familyStore.addMiembroFamiliar({
      nombre_completo: form.value.nombre,
      parentesco: form.value.parentesco,
      fecha_nacimiento: form.value.fecha_nacimiento,
      genero: form.value.genero,
      correo: form.value.correo
    })

    // Alerta global de éxito
    toastInfo('¡Éxito!', 'Miembro Familiar guardado correctamente', 'success')
    
    form.value = { nombre: '', parentesco: '', fecha_nacimiento: '', genero: '' }

    // Redirigir de vuelta a la lista tras 1.5 segundos
    setTimeout(() => {
      router.push({ name: 'family-members-list' })
    }, 1500)

  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Error al guardar el miembro familiar'
    
    // Alerta global de error
    toastInfo('Error', errorMsg, 'error')
  } finally {
    loadingBtn.value = false
  }
}

const volver = () => {
  router.push({ name: 'family-members-list' })
}
</script>

<template>
  <div class="wrapper">
    <div class="card">
      <h2>Agregar Miembro Familiar</h2>
      <div class="form">

        <div class="form-group">
          <label>Nombre Completo</label>
          <input v-model="form.nombre" placeholder="Ej. Juan Pérez" />
        </div>

        <div class="form-group">
          <label>Parentesco</label>
          <Select v-model="form.parentesco" :options="opcionesParentesco" placeholder="Selecciona el parentesco"
            class="custom-select" appendTo="self" />
        </div>

        <div class="form-group">
          <label>Fecha de Nacimiento</label>
          <input type="date" v-model="form.fecha_nacimiento" />
        </div>

        <div class="form-group">
          <label>Correo Electrónico (Opcional)</label>
          <input type="email" v-model="form.correo" placeholder="ejemplo@correo.com" />
        </div>

        <div class="form-group">
          <label>Género</label>
          <Select v-model="form.genero" :options="opcionesGenero" optionLabel="label" optionValue="value"
            placeholder="Selecciona el género" class="custom-select" appendTo="self" />
        </div>

        <div class="button-group">
          <button class="btn-secondary" @click="volver" :disabled="loadingBtn">
            Volver
          </button>
          <button class="btn-primary" @click="guardarMiembroFamiliar" :disabled="loadingBtn">
            {{ loadingBtn ? 'Guardando...' : 'Guardar Miembro Familiar' }}
          </button>
        </div>

        </div>
    </div>
  </div>
</template>

<style scoped>
/* CENTRADO TOTAL */
.wrapper {
  min-height: 80vh;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* TARJETA */
.card {
  background: white;
  padding: 30px;
  border-radius: var(--p-border-radius-medium, 16px);
  width: 100%;
  max-width: 450px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}

/* FORM & GROUPS */
.form { display: flex; flex-direction: column; gap: 16px; margin-top: 15px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 13px; font-weight: 600; color: var(--p-surface-900, #374151); }

/* INPUTS */
input {
  padding: 10px;
  border: 1px solid var(--p-surface-200, #ddd);
  border-radius: 8px;
  outline: none;
  transition: 0.2s;
  font-family: inherit;
  font-size: 14px;
}

input:focus { border-color: var(--p-primary-700); }

/* BOTONES */
.button-group { display: flex; gap: 12px; margin-top: 10px; }
.btn-primary { flex: 1; background: var(--p-primary-700); color: white; border: none; padding: 10px; border-radius: var(--p-border-radius-medium); cursor: pointer; transition: 0.2s; font-weight: 500; font-size: 14px;}
.btn-primary:hover { background: var(--p-primary-800); }
.btn-secondary { flex: 1; background: white; color: var(--p-surface-900); border: 1px solid var(--p-surface-300, #d1d5db); padding: 10px; border-radius: var(--p-border-radius-medium); cursor: pointer; transition: 0.2s; font-weight: 500; font-size: 14px;}
.btn-secondary:hover { background: var(--p-surface-100, #f3f4f6); }

/* ESTILOS PARA EL SELECT */
:deep(.custom-select) {
  width: 100%;
  box-sizing: border-box;
  border-radius: 8px !important;
  border: 1px solid var(--p-surface-200, #ddd) !important;
  font-family: inherit !important;
  display: flex;
  align-items: center;
  height: 42px;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  background-color: white;
  transition: 0.2s;
}

:deep(.custom-select:hover),
:deep(.custom-select.p-focus) { border-color: var(--p-primary-700) !important; }
:deep(.p-select-list-container) { padding: 0.5rem !important; }
:deep(.p-select-option) { padding: 0.75rem 1.25rem !important; font-size: 1rem !important; border-radius: 0.5rem !important; }
</style>