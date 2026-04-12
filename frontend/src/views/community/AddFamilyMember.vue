<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useFamilyStore } from '@/stores/community/familyStore'
import Select from 'primevue/select'
import { useToast } from "primevue/usetoast";
const toast = useToast();

const router = useRouter()
const familyStore = useFamilyStore()

const form = ref({
  nombre: '',
  parentesco: '',
  fecha_nacimiento: '',
  genero: ''
})

const loadingBtn = ref(false)
const mensaje = ref('')
const tipoMensaje = ref('')

const opcionesParentesco = ref(['CONYUGE', 'HIJO/A', 'OTRO'])
const opcionesGenero = ref([
  { label: 'MASCULINO', value: 'M' },
  { label: 'FEMENINO', value: 'F' },
  { label: 'OTRO', value: 'OTRO' }
])

const guardarMiembroFamiliar = async () => {
  loadingBtn.value = true
  mensaje.value = ''

  try {
    await familyStore.addMiembroFamiliar({
      nombre_completo: form.value.nombre,
      parentesco: form.value.parentesco,
      fecha_nacimiento: form.value.fecha_nacimiento,
      genero: form.value.genero
    })

    toast.add({
      severity: 'success',
      summary: '¡Éxito!',
      detail: 'Miembro Familiar guardado correctamente',
      life: 6000
    })
    form.value = { nombre: '', parentesco: '', fecha_nacimiento: '', genero: '' }

    // Redirigir de vuelta a la lista tras 1.5 segundos
    setTimeout(() => {
      router.push({ name: 'family-members-list' })
    }, 1500)

  } catch (error) {
    mensaje.value = error.response?.data?.message || 'Error al guardar'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: msg,
      life: 6000
    })
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

        <div v-if="mensaje" :class="['badge', tipoMensaje]">
          {{ mensaje }}
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
  border-radius: 16px;
  width: 100%;
  max-width: 450px;
  /* Un poco más ancho para respirar mejor */
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}

/* FORM & GROUPS */
.form {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 15px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: var(--p-surface-700, #374151);
}

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

input:focus {
  border-color: var(--p-primary-600);
}

/* BOTONES */
.button-group {
  display: flex;
  gap: 12px;
  margin-top: 10px;
}

.btn-primary {
  flex: 1;
  background: var(--p-primary-600);
  color: white;
  border: none;
  padding: 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.2s;
  font-weight: 500;
}

.btn-primary:hover {
  background: var(--p-primary-700);
}

.btn-secondary {
  flex: 1;
  background: white;
  color: var(--p-surface-900, #111827);
  border: 1px solid var(--p-surface-300, #d1d5db);
  padding: 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.2s;
  font-weight: 500;
}

.btn-secondary:hover {
  background: var(--p-surface-100, #f3f4f6);
}

/* BADGE BASE */
.badge {
  margin-top: 10px;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  text-align: center;
}

/* ERROR & SUCCESS */
.error {
  border: 1px solid #ef4444;
  color: #ef4444;
  background: #fef2f2;
}

.success {
  border: 1px solid #22c55e;
  color: #22c55e;
  background: #f0fdf4;
}

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
:deep(.custom-select.p-focus) {
  border-color: var(--p-primary-600) !important;
}

:deep(.p-select-list-container) {
  padding: 0.5rem !important;
}

:deep(.p-select-option) {
  padding: 0.75rem 1.25rem !important;
  font-size: 1rem !important;
  border-radius: 0.5rem !important;
}
</style>