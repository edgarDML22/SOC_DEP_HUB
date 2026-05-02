<script setup>
import { ref, watch } from 'vue'
import { useFriendStore } from '@/stores/community/friendStore'
import { useProfileStore } from '@/stores/profiles/socioStore'
import { useAlerts } from '@/composables/useAlerts' 
import api from '@/services/api'
import { useRouter } from 'vue-router'
import { IconArrowLeft } from '@/components/icons'

const friendStore = useFriendStore()
const profileStore = useProfileStore()
const { toastInfo } = useAlerts() 
const { showLoading, closeLoading, successModal, errorModal } = useAlerts()
const router = useRouter()

const search = ref('')
const loadingSearch = ref(false)
const resultados = ref([])
const sendingId = ref(null)

let timeoutId = null

watch(search, (newVal) => {
  if (timeoutId) clearTimeout(timeoutId)

  if (newVal.trim().length < 2) {
    resultados.value = []
    return
  }

  timeoutId = setTimeout(() => {
    buscarSocios(newVal)
  }, 500)
})

async function buscarSocios(query) {
  loadingSearch.value = true
  try {
    const response = await api.get(`/socios/search?query=${query}`)
    console.log('🔍 Búsqueda:', query, '→ Resultados:', response.data)
    // Excluir al socio autenticado de los resultados
    const myId = profileStore.idSocio
    resultados.value = (response.data?.data || []).filter(item => item.id !== myId)
  } catch (e) {
    console.error('❌ Error en búsqueda:', e)
    toastInfo('Error', 'No se pudo completar la búsqueda', 'error')
  } finally {
    loadingSearch.value = false
  }
}

async function enviarSolicitud(socio) {
  sendingId.value = socio.id
  showLoading('Enviando solicitud...')
  try {
    await friendStore.addFriend({ receptor_id: socio.id })
    closeLoading()
    await successModal('¡Solicitud enviada!', `Tu solicitud de amistad fue enviada a ${socio.nombre} exitosamente.`)
    // Redirigir de regreso a la lista
    router.push({ name: 'friends-list' })
  } catch (e) {
    closeLoading()
    const errorMsg = e.response?.data?.message || 'Hubo un error al enviar la solicitud. Inténtalo de nuevo.'
    await errorModal('No se pudo enviar', errorMsg)
  } finally {
    sendingId.value = null
  }
}
</script>

<template>
  <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8 pt-4 lg:pt-6 font-sans">
    <div class="max-w-3xl mx-auto flex flex-col gap-6">
      
      <!-- Encabezado con Botón Volver -->
      <div>
        <button @click="router.back()" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 focus:outline-none w-fit group">
            <IconArrowLeft class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" /> Volver
        </button>
        <div class="flex flex-col gap-1">
          <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">Agregar Nuevo Amigo</h2>
          <p class="text-surface-500 font-medium text-sm md:text-base m-0">Busca a otros socios por nombre para enviarles una solicitud.</p>
        </div>
      </div>

      <!-- Buscador -->
      <div class="flex flex-col gap-4">
        <input 
          v-model="search" 
          placeholder="Buscar por nombre o número de acción..." 
          class="w-full px-4 py-3 bg-white border border-surface-200 font-medium rounded-xl outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition-colors text-surface-900 shadow-sm" 
        />
      </div>

      <!-- Estado de Búsqueda -->
      <div v-if="loadingSearch" class="text-center py-12 text-surface-500 font-medium">
        <span class="animate-pulse">Buscando socios...</span>
      </div>

      <div v-else-if="search.length >= 2">
        <!-- Sin Resultados -->
        <div v-if="resultados.length === 0" class="bg-white rounded-2xl p-8 md:p-12 text-center text-surface-600 shadow-sm border border-surface-200 flex flex-col items-center justify-center min-h-[200px]">
          <p class="text-base font-medium">No se encontraron socios que coincidan con "<strong>{{ search }}</strong>".</p>
        </div>

        <!-- Resultados (Lista Vertical Clean) -->
        <div v-else class="flex flex-col gap-3">
          <div 
            v-for="socio in resultados" 
            :key="socio.id" 
            class="bg-white rounded-2xl p-4 md:p-5 shadow-sm border border-surface-200 transition-all hover:shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4"
          >
            <!-- Info Izquierda -->
            <div class="flex items-center gap-4 min-w-0">
              <div class="w-12 h-12 rounded-full flex items-center justify-center bg-surface-100 text-primary-700 font-bold text-lg uppercase shrink-0">
                {{ socio.nombre?.charAt(0) || '?' }}
              </div>
              <div class="flex flex-col min-w-0">
                <h3 class="text-base font-bold text-surface-900 m-0 truncate" :title="socio.nombre">{{ socio.nombre }}</h3>
                <span class="text-sm font-medium text-surface-500 mt-0.5 truncate">Acción: {{ socio.numero_socio }}</span>
              </div>
            </div>

            <!-- Botón Derecha -->
            <button 
              @click="enviarSolicitud(socio)" 
              :disabled="sendingId === socio.id"
              class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center focus:outline-none shrink-0"
            >
              {{ sendingId === socio.id ? 'Enviando...' : 'Enviar Solicitud' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Estado Inicial -->
      <div v-else class="bg-white rounded-2xl p-8 md:p-12 text-center text-surface-500 shadow-sm border border-surface-200 flex flex-col items-center justify-center min-h-[200px]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-surface-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <p class="text-base font-medium">Ingresa al menos 2 caracteres para comenzar la búsqueda.</p>
      </div>

    </div>
  </div>
</template>