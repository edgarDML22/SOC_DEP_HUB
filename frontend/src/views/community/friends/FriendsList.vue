<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useFriendStore } from '@/stores/community/friendStore'
import { useAlerts } from '@/composables/useAlerts' 
import { IconArrowLeft } from '@/components/icons'

const router = useRouter()
const friendStore = useFriendStore()
const { toastInfo, showLoading, closeLoading, successModal, errorModal, confirmDelete } = useAlerts()

const search = ref('')
const filtro = ref('AMIGO') 

const actionLoadingId = ref(null)
const actionTypeLoading = ref('')

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

const aceptarSolicitud = async (amigo) => {
  actionLoadingId.value = amigo.id_amistad
  actionTypeLoading.value = 'aceptar'
  showLoading('Aceptando solicitud...')
  try {
    await friendStore.acceptFriend({ id_amistad: amigo.id_amistad })
    closeLoading()
    await successModal('¡Solicitud aceptada!', `Ahora eres amigo de ${amigo.nombre_amigo}.`)
  } catch (e) {
    closeLoading()
    await errorModal('Error', 'No se pudo aceptar la solicitud. Inténtalo de nuevo.')
  } finally {
    actionLoadingId.value = null
    actionTypeLoading.value = ''
  }
}

const rechazarSolicitud = async (amigo) => {
  actionLoadingId.value = amigo.id_amistad
  actionTypeLoading.value = 'rechazar'
  showLoading('Rechazando solicitud...')
  try {
    await friendStore.rejectFriend({ id_amistad: amigo.id_amistad })
    closeLoading()
    toastInfo('Solicitud rechazada', 'La solicitud de amistad fue rechazada.', 'info')
  } catch (e) {
    closeLoading()
    await errorModal('Error', 'No se pudo rechazar la solicitud. Inténtalo de nuevo.')
  } finally {
    actionLoadingId.value = null
    actionTypeLoading.value = ''
  }
}

const eliminarAmigo = async (amigo) => {
  const result = await confirmDelete(
    '¿Eliminar amigo?',
    `¿Estás seguro de que quieres eliminar a ${amigo.nombre_amigo} de tu lista de amigos?`
  )
  if (!result.isConfirmed) return

  actionLoadingId.value = amigo.id_amistad
  actionTypeLoading.value = 'eliminar'
  showLoading('Eliminando amigo...')
  try {
    await friendStore.removeFriend({ id_amistad: amigo.id_amistad })
    closeLoading()
    toastInfo('Amigo eliminado', `${amigo.nombre_amigo} fue eliminado de tu lista.`, 'info')
  } catch (e) {
    closeLoading()
    await errorModal('Error', 'No se pudo eliminar al amigo. Inténtalo de nuevo.')
  } finally {
    actionLoadingId.value = null
    actionTypeLoading.value = ''
  }
}
</script>

<template>
  <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8 pt-4 lg:pt-6 font-sans">
    <div class="max-w-5xl mx-auto flex flex-col gap-6">
      
      <!-- Encabezado con Botón Volver -->
      <div>
        <button @click="router.back()" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 focus:outline-none w-fit group">
            <IconArrowLeft class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" /> Volver
        </button>
        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
          <div class="flex flex-col gap-1">
            <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">Mis Amigos</h2>
            <p class="text-surface-500 font-medium text-sm md:text-base m-0">Mantente conectado con las personas que más quieres</p>
          </div>
          
          <router-link 
            :to="{ name: 'friends-add' }" 
            class="bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 shadow-sm text-center w-full md:w-auto flex items-center justify-center"
          >
            + Agregar Amigo
          </router-link>
        </div>
      </div>

      <!-- Buscador y Filtros Nivel 2 -->
      <div class="flex flex-col gap-4">
        
        <input 
          v-model="search" 
          placeholder="Buscar amigo por nombre..." 
          class="w-full px-4 py-3 bg-white border border-surface-200 font-medium rounded-xl outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition-colors text-surface-900 shadow-sm" 
        />
        
        <!-- Pestañas Nivel 2 (Minimalistas) -->
        <div class="flex gap-6 border-b border-surface-200 w-full overflow-x-auto scrollbar-thin">
          <button 
            @click="cambiarFiltro('AMIGO')" 
            class="pb-3 text-sm font-medium transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
            :class="filtro === 'AMIGO' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-surface-400 border-b-2 border-transparent hover:text-surface-600'"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Mis Amigos
          </button>
          
          <button 
            @click="cambiarFiltro('SOLICITUD_ENVIADA')" 
            class="pb-3 text-sm font-medium transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
            :class="filtro === 'SOLICITUD_ENVIADA' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-surface-400 border-b-2 border-transparent hover:text-surface-600'"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
            Enviadas
          </button>
          
          <button 
            @click="cambiarFiltro('SOLICITUD_RECIBIDA')" 
            class="pb-3 text-sm font-medium transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
            :class="filtro === 'SOLICITUD_RECIBIDA' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-surface-400 border-b-2 border-transparent hover:text-surface-600'"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9a2 2 0 0 1-2 2H6l-4 4V4c0-1.1.9-2 2-2h8a2 2 0 0 1 2 2v5Z"/><path d="M18 9h2a2 2 0 0 1 2 2v11l-4-4h-6a2 2 0 0 1-2-2v-1"/></svg>
            Recibidas
          </button>
        </div>

      </div>

      <!-- Estado de Carga -->
      <div v-if="friendStore.loading" class="text-center py-12 text-surface-500 font-medium">
        <span class="animate-pulse">Cargando...</span>
      </div>

      <!-- Empty State -->
      <div v-else-if="amigosFiltrados.length === 0" class="bg-white rounded-2xl p-8 md:p-12 text-center text-surface-600 shadow-sm border border-surface-200 flex flex-col items-center justify-center min-h-[250px]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-surface-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <p v-if="filtro === 'AMIGO'" class="text-base font-medium">Aún no tienes amigos en tu lista.</p>
        <p v-else-if="filtro === 'SOLICITUD_ENVIADA'" class="text-base font-medium">No tienes solicitudes enviadas pendientes.</p>
        <p v-else-if="filtro === 'SOLICITUD_RECIBIDA'" class="text-base font-medium">No tienes solicitudes de amistad recibidas.</p>
      </div>

      <!-- Lista de Tarjetas (Grid) -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div 
          v-for="amigo in amigosFiltrados" 
          :key="amigo.id_amistad" 
          class="bg-white rounded-2xl p-5 shadow-sm border border-surface-200 transition-all hover:shadow-md flex flex-col h-full hover:-translate-y-0.5"
        >
          <!-- Header de tarjeta -->
          <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center bg-surface-100 text-primary-700 font-bold text-lg uppercase shrink-0">
              {{ amigo.nombre_amigo?.charAt(0) || '?' }}
            </div>
            
            <div class="flex-1 min-w-0">
              <h3 class="text-base font-bold text-surface-900 m-0 truncate" :title="amigo.nombre_amigo">
                {{ amigo.nombre_amigo }}
              </h3>
              
              <div class="mt-1.5 flex flex-wrap gap-2">
                <span v-if="amigo.estado === 'ACEPTADA'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider bg-green-50 text-green-700 border border-green-200">
                  AMIGO
                </span>
                <span v-else-if="amigo.estado === 'PENDIENTE' && amigo.solicitado_por_mi" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider bg-yellow-50 text-yellow-700 border border-yellow-200">
                  ENVIADA
                </span>
                <span v-else-if="amigo.estado === 'PENDIENTE' && !amigo.solicitado_por_mi" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider bg-primary-50 text-primary-700 border border-primary-200">
                  RECIBIDA
                </span>
              </div>
            </div>
          </div>

          <!-- Detalles Body -->
          <div class="flex-1 flex flex-col gap-2 text-sm text-surface-600 mb-5 font-medium">
            <div v-if="amigo.created_at" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-surface-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
              </svg>
              <span>{{ new Date(amigo.created_at).toLocaleDateString() }}</span>
            </div>
          </div>

          <!-- Footer Actions -->
          <!-- Acción: Eliminar (para amigos aceptados) -->
          <div class="mt-auto pt-2" v-if="filtro === 'AMIGO'">
            <button 
              @click="eliminarAmigo(amigo)"
              :disabled="actionLoadingId === amigo.id_amistad"
              class="w-full bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" x2="23" y1="11" y2="11"/></svg>
              {{ actionLoadingId === amigo.id_amistad && actionTypeLoading === 'eliminar' ? 'Eliminando...' : 'Eliminar amigo' }}
            </button>
          </div>

          <!-- Acción: Aceptar/Rechazar (para solicitudes recibidas) -->
          <div class="mt-auto pt-2" v-if="filtro === 'SOLICITUD_RECIBIDA'">
            <div class="flex gap-2 flex-col sm:flex-row w-full">
              <!-- Estandarización Primary -->
              <button 
                @click="aceptarSolicitud(amigo)"
                :disabled="actionLoadingId === amigo.id_amistad"
                class="flex-1 bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
              >
                {{ actionLoadingId === amigo.id_amistad && actionTypeLoading === 'aceptar' ? 'Aceptando...' : 'Aceptar' }}
              </button>
              
              <!-- Estandarización Secondary -->
              <button 
                @click="rechazarSolicitud(amigo)"
                :disabled="actionLoadingId === amigo.id_amistad"
                class="flex-1 bg-surface-50 hover:bg-surface-100 text-surface-700 border border-surface-200 rounded-xl px-4 py-2.5 font-semibold transition-all flex items-center justify-center active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ actionLoadingId === amigo.id_amistad && actionTypeLoading === 'rechazar' ? 'Rechazando...' : 'Rechazar' }}
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>