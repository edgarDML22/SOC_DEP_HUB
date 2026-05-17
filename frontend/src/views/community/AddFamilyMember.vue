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
  <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8 pt-4 lg:pt-6 font-sans">
    <div class="max-w-3xl mx-auto flex flex-col gap-6">
      
      <!-- Encabezado -->
      <div>
        <div class="flex flex-col gap-1">
          <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">Agregar Miembro Familiar</h2>
          <p class="text-surface-500 font-medium text-sm md:text-base m-0">Registra un nuevo integrante de tu familia.</p>
        </div>
      </div>

      <!-- Tarjeta Formulario -->
      <div class="bg-white rounded-2xl p-6 md:p-8 shadow-sm border border-surface-200">
        <div class="flex flex-col gap-5">
          
          <div class="flex flex-col gap-2">
            <label class="text-sm font-bold text-surface-900">Nombre Completo</label>
            <input v-model="form.nombre" placeholder="Ej. Juan Pérez" 
              class="w-full px-4 py-3 bg-white border border-surface-200 font-medium rounded-xl outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition-colors text-surface-900 shadow-sm" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-bold text-surface-900">Parentesco</label>
            <Select v-model="form.parentesco" :options="opcionesParentesco" placeholder="Selecciona el parentesco"
              class="w-full border-surface-200! rounded-xl! shadow-sm hover:border-primary-600! focus:border-primary-600! font-medium!" appendTo="self" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-bold text-surface-900">Fecha de Nacimiento</label>
            <input type="date" v-model="form.fecha_nacimiento" 
              class="w-full px-4 py-3 bg-white border border-surface-200 font-medium rounded-xl outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition-colors text-surface-900 shadow-sm" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-bold text-surface-900">Correo Electrónico (Opcional)</label>
            <input type="email" v-model="form.correo" placeholder="ejemplo@correo.com" 
              class="w-full px-4 py-3 bg-white border border-surface-200 font-medium rounded-xl outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition-colors text-surface-900 shadow-sm" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-bold text-surface-900">Género</label>
            <Select v-model="form.genero" :options="opcionesGenero" optionLabel="label" optionValue="value"
              placeholder="Selecciona el género" class="w-full border-surface-200! rounded-xl! shadow-sm hover:border-primary-600! focus:border-primary-600! font-medium!" appendTo="self" />
          </div>

          <div class="flex flex-col sm:flex-row gap-3 mt-4">
            <button class="w-full sm:w-1/2 bg-surface-50 hover:bg-surface-100 text-surface-700 border border-surface-200 rounded-xl px-4 py-3 font-bold transition-all active:scale-95 focus:outline-none" @click="volver" :disabled="loadingBtn">
              Volver
            </button>
            <button class="w-full sm:w-1/2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-3 font-bold transition-all active:scale-95 shadow-sm disabled:opacity-50 flex justify-center focus:outline-none" @click="guardarMiembroFamiliar" :disabled="loadingBtn">
              {{ loadingBtn ? 'Guardando...' : 'Guardar Familiar' }}
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ESTILOS PARA EL SELECT - Sobreescrituras ligeras a PrimeVue para que empate con Tailwind */
:deep(.p-select) {
  height: 48px;
  display: flex;
  align-items: center;
}
:deep(.p-select-label) {
  font-family: inherit;
}
:deep(.p-select-list-container) { padding: 0.5rem !important; }
:deep(.p-select-option) { padding: 0.75rem 1.25rem !important; font-size: 0.875rem !important; border-radius: 0.5rem !important; font-weight: 500;}
</style>