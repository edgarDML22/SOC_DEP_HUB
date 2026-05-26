<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useProfileStore } from '@/stores/profiles/socioStore'
import { useAlerts } from '@/composables/useAlerts'
import { IconBaby, IconUser, IconAlertCircle } from '@/components/icons'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'

const profileStore = useProfileStore()
const { toastInfo, showLoading, closeLoading, successModal, errorModal } = useAlerts()

const miembros = ref([])
const idSeleccionado = ref(null)
const loading = ref(false)

const alertModal = ref({
  visible: false,
  type: 'success',
  title: '',
  message: ''
})

const mostrarAlerta = (title, message, type = 'success') => {
  alertModal.value = { visible: true, type, title, message }
}

const cerrarAlerta = () => {
  alertModal.value.visible = false
}

// Cargar miembros familiares
const cargarMiembros = async () => {
  try {
    const res = await api.get('ludoteca/list', {
      params: {
        id_socio: profileStore.profileData?.id_socio
      }
    })
    miembros.value = res.data
  } catch (error) {
    mostrarAlerta('Error', 'No se pudieron cargar los miembros familiares.', 'error')
  }
}

// Registrar en ludoteca
const registrar = async () => {
  if (!idSeleccionado.value) {
    mostrarAlerta('Campo requerido', 'Por favor selecciona un menor antes de continuar.', 'error')
    return
  }

  loading.value = true

  try {
    const res = await api.post('/ludoteca/register', {
      id_miembro: idSeleccionado.value,
      id_socio: profileStore.profileData?.id_socio
    })

    const msg = res.data.message || ''

    if (msg.toLowerCase().includes('registro exitoso') || msg.toLowerCase().includes('ingreso registrado')) {
      mostrarAlerta('¡Registro exitoso!', 'El menor fue registrado en la ludoteca correctamente.', 'success')
    } else {
      mostrarAlerta('Aviso', msg || 'No se pudo completar el registro.', 'error')
    }

    idSeleccionado.value = null

  } catch (error) {
    const msg =
      error.response?.data?.message ||
      error.response?.data?.error ||
      'Ocurrió un error al intentar registrar. Inténtalo de nuevo.'
    mostrarAlerta('Error al registrar', msg, 'error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  cargarMiembros()
})
</script>

<template>
  <div class="max-w-xl mx-auto w-full animate-fade-in py-2">
    
    <div class="bg-white w-full rounded-3xl shadow-xl shadow-surface-900/5 flex flex-col overflow-hidden border border-surface-200">
      
      <!-- Cabecera Premium -->
      <div class="flex flex-col items-center justify-center px-8 py-8 bg-linear-to-br from-primary-600 to-primary-800 border-b border-surface-100 relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
        <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white mb-4 shadow-lg border border-white/30 relative z-10">
          <IconBaby class="w-8 h-8" />
        </div>
        <h2 class="text-2xl font-black text-white leading-tight relative z-10">Registro Ludoteca</h2>
        <p class="text-xs font-bold text-primary-100 mt-1 uppercase tracking-widest relative z-10">
          Selecciona al menor para ingresar
        </p>
      </div>

      <!-- Cuerpo -->
      <div class="p-8 space-y-6 bg-surface-50/50">
        
        <div class="space-y-4">
          <label class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
            ¿Quién ingresará hoy? <span class="text-red-400">*</span>
          </label>
          
          <div v-if="miembros.length > 0" class="grid grid-cols-2 gap-4">
            <button 
              v-for="m in miembros" 
              :key="m.id_miembro"
              @click="idSeleccionado = m.id_miembro"
              class="flex flex-col items-center gap-3 p-4 rounded-2xl border-2 transition-all shadow-sm relative overflow-hidden group text-left"
              :class="idSeleccionado === m.id_miembro
                ? 'bg-primary-50 border-primary-500 shadow-md'
                : 'bg-white border-surface-200 hover:border-primary-300 hover:bg-primary-50/50'"
            >
              <div class="w-12 h-12 rounded-full flex items-center justify-center transition-colors shadow-inner"
                   :class="idSeleccionado === m.id_miembro ? 'bg-primary-500 text-white' : 'bg-surface-100 text-surface-400 group-hover:bg-primary-100 group-hover:text-primary-600'">
                <IconBaby class="w-6 h-6" />
              </div>
              <span class="text-sm font-bold truncate w-full text-center"
                    :class="idSeleccionado === m.id_miembro ? 'text-primary-900' : 'text-surface-700'">
                {{ m.nombre_completo }}
              </span>

              <!-- Icono Check -->
              <div class="absolute top-3 right-3 w-5 h-5 rounded-full flex items-center justify-center transition-all border-2"
                   :class="idSeleccionado === m.id_miembro ? 'bg-primary-500 border-white text-white scale-100 opacity-100' : 'border-surface-300 bg-surface-50 text-transparent scale-50 opacity-0'">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
              </div>
            </button>
          </div>

          <div v-else-if="!loading" class="flex flex-col items-center justify-center py-10 text-center bg-white rounded-2xl border border-surface-200 border-dashed">
            <div class="w-16 h-16 bg-surface-50 rounded-2xl flex items-center justify-center mb-4 text-surface-300 shadow-inner">
              <IconUser class="w-8 h-8" />
            </div>
            <h3 class="text-lg font-black text-surface-900 mb-1">Sin Menores Registrados</h3>
            <p class="text-sm font-medium text-surface-500 max-w-xs">No se encontraron menores registrados en tu cuenta.</p>
          </div>

        </div>

      </div>

      <!-- Pie del modal -->
      <div class="flex items-center justify-end px-8 py-5 bg-white border-t border-surface-100">
        <button 
          @click="registrar" 
          :disabled="loading || !idSeleccionado"
          class="w-full flex justify-center items-center gap-2 px-6 py-3.5 rounded-xl bg-primary-600 text-white text-sm font-bold transition-all shadow-md hover:bg-primary-700 hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-md disabled:hover:bg-primary-600"
        >
          <svg v-if="!loading" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
          </svg>
          <svg v-else class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ loading ? 'Registrando Ingreso...' : 'Confirmar Ingreso al Club' }}
        </button>
      </div>

    </div>

    <!-- Modal Alerta Minimalista (Éxito / Fallo) -->
    <div v-if="alertModal.visible" class="fixed inset-0 z-50 flex items-center justify-center bg-[#111111]/30 backdrop-blur-[2px] p-4" @click.self="cerrarAlerta">
      <div class="bg-white w-full max-w-sm rounded-2xl border border-[#EAEAEA] p-6 shadow-lg animate-fade-in flex flex-col items-center text-center">
        <!-- Icono de Éxito -->
        <div v-if="alertModal.type === 'success'" class="w-12 h-12 rounded-xl bg-[#EDF3EC] flex items-center justify-center text-[#346538] mb-4">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <!-- Icono de Error -->
        <div v-else class="w-12 h-12 rounded-xl bg-[#FDEBEC] flex items-center justify-center text-[#9F2F2D] mb-4">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>

        <h3 class="text-base font-bold text-[#111111] mb-1.5 tracking-tight font-sans">{{ alertModal.title }}</h3>
        <p class="text-sm text-[#787774] mb-6 font-sans leading-relaxed">{{ alertModal.message }}</p>

        <button @click="cerrarAlerta" class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 active:scale-[0.98] text-white text-sm font-semibold rounded-xl transition-all text-center focus:outline-none font-sans">
          Aceptar
        </button>
      </div>
    </div>

  </div>
</template>