<script setup>
import { ref, onMounted, computed } from 'vue'
import { useFriendStore } from '@/stores/community/friendStore'
import { useAlerts } from '@/composables/useAlerts'
import { IconCalendar } from '@/components/icons'

const friendStore = useFriendStore()
const { toastInfo, showLoading, closeLoading, successModal, errorModal, confirmDelete, confirmWarning } = useAlerts()

const search = ref('')
const filtro = ref('AMIGO')
const actionLoadingId = ref(null)
const actionTypeLoading = ref('')

const formatearFecha = (fecha) => {
  if (!fecha) return 'N/A'
  const d = new Date(fecha)
  if (isNaN(d.getTime())) return fecha
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}

const filters = [
  {
    id: 'AMIGO',
    label: 'Todos',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`
  },
  {
    id: 'SOLICITUD_ENVIADA',
    label: 'Enviadas',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>`
  },
  {
    id: 'SOLICITUD_RECIBIDA',
    label: 'Recibidas',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9a2 2 0 0 1-2 2H6l-4 4V4c0-1.1.9-2 2-2h8a2 2 0 0 1 2 2v5Z"/><path d="M18 9h2a2 2 0 0 1 2 2v11l-4-4h-6a2 2 0 0 1-2-2v-1"/></svg>`
  },
]

const amigosFiltrados = computed(() => {
  let list = Array.isArray(friendStore.friends) ? friendStore.friends : []

  if (filtro.value === 'AMIGO') {
    list = list.filter(f => f.estado?.toUpperCase() === 'ACEPTADA')
  } else if (filtro.value === 'SOLICITUD_ENVIADA') {
    list = list.filter(f => f.estado?.toUpperCase() === 'PENDIENTE' && f.solicitado_por_mi)
  } else if (filtro.value === 'SOLICITUD_RECIBIDA') {
    list = list.filter(f => f.estado?.toUpperCase() === 'PENDIENTE' && !f.solicitado_por_mi)
  }

  if (search.value) {
    const lower = search.value.toLowerCase()
    list = list.filter(f => f.nombre_amigo?.toLowerCase().includes(lower))
  }

  return list
})

// Caché: solo hace fetch si aún no se ha cargado
onMounted(() => {
  friendStore.fetchFriends()
})

// ── Aceptar solicitud (Fase 8C: optimistic — tarjeta desaparece al instante) ──
const aceptarSolicitud = async (amigo) => {
  const result = await confirmWarning(
    '¿Aceptar solicitud?',
    `¿Quieres aceptar la solicitud de amistad de ${amigo.nombre_amigo}?`,
    'Sí, Aceptar'
  )
  if (!result.isConfirmed) return

  actionLoadingId.value = amigo.id_amistad
  actionTypeLoading.value = 'aceptar'
  try {
    await friendStore.acceptFriend({ id_amistad: amigo.id_amistad })
    toastInfo('¡Solicitud aceptada!', `Ahora eres amigo de ${amigo.nombre_amigo}.`, 'success')
  } catch (e) {
    // Rollback optimista: refetch
    await friendStore.fetchFriends(true)
    await errorModal('Error', 'No se pudo aceptar la solicitud. Inténtalo de nuevo.')
  } finally {
    actionLoadingId.value = null
    actionTypeLoading.value = ''
  }
}

// ── Rechazar solicitud (Fase 8C: optimistic) ──
const rechazarSolicitud = async (amigo) => {
  const result = await confirmDelete(
    '¿Rechazar solicitud?',
    `¿Seguro que quieres rechazar la solicitud de ${amigo.nombre_amigo}?`,
    'Sí, Rechazar'
  )
  if (!result.isConfirmed) return

  actionLoadingId.value = amigo.id_amistad
  actionTypeLoading.value = 'rechazar'
  try {
    await friendStore.rejectFriend({ id_amistad: amigo.id_amistad })
    toastInfo('Solicitud rechazada', 'La solicitud de amistad fue rechazada.', 'info')
  } catch (e) {
    await friendStore.fetchFriends(true)
    await errorModal('Error', 'No se pudo rechazar la solicitud. Inténtalo de nuevo.')
  } finally {
    actionLoadingId.value = null
    actionTypeLoading.value = ''
  }
}

// ── Cancelar solicitud enviada (Fase 6A: hard delete con modal de confirmación) ──
const cancelarSolicitud = async (amigo) => {
  const result = await confirmDelete(
    '¿Cancelar solicitud?',
    `¿Estás seguro de que quieres cancelar la solicitud enviada a ${amigo.nombre_amigo}?`,
    'Sí, Cancelar'
  )
  if (!result.isConfirmed) return

  actionLoadingId.value = amigo.id_amistad
  actionTypeLoading.value = 'cancelar'
  try {
    await friendStore.cancelFriend({ id_amistad: amigo.id_amistad })
    toastInfo('Solicitud cancelada', `La solicitud a ${amigo.nombre_amigo} fue cancelada.`, 'info')
  } catch (e) {
    await friendStore.fetchFriends(true)
    await errorModal('Error', 'No se pudo cancelar la solicitud. Inténtalo de nuevo.')
  } finally {
    actionLoadingId.value = null
    actionTypeLoading.value = ''
  }
}

// ── Eliminar amigo (Fase 4: optimistic) ──
const eliminarAmigo = async (amigo) => {
  const result = await confirmDelete(
    '¿Eliminar amigo?',
    `¿Estás seguro de que quieres eliminar a ${amigo.nombre_amigo} de tu lista de amigos?`
  )
  if (!result.isConfirmed) return

  actionLoadingId.value = amigo.id_amistad
  actionTypeLoading.value = 'eliminar'
  try {
    await friendStore.removeFriend({ id_amistad: amigo.id_amistad })
    toastInfo('Amigo eliminado', `${amigo.nombre_amigo} fue eliminado de tu lista.`, 'info')
  } catch (e) {
    await friendStore.fetchFriends(true)
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

      <!-- Encabezado -->
      <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
        <div class="flex flex-col gap-1">
          <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">Mis Amigos</h2>
          <p class="text-surface-500 font-medium text-sm md:text-base m-0">Mantente conectado con las personas que más quieres</p>
        </div>
        <router-link
          :to="{ name: 'friends-add' }"
          class="bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 shadow-sm text-center w-full md:w-auto flex items-center justify-center gap-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
          Agregar Amigo
        </router-link>
      </div>

      <!-- Buscador + Filtros -->
      <div class="flex flex-col gap-4">
        <div v-if="filtro === 'AMIGO'" class="relative w-full md:max-w-md">
          <input
            v-model="search"
            placeholder="Buscar amigo por nombre..."
            class="w-full pl-11 pr-4 py-3 bg-white border border-surface-200 font-medium rounded-xl outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition-colors text-surface-900 shadow-sm"
          />
          <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </div>

        <div class="flex gap-2 overflow-x-auto scrollbar-none pb-1 -mx-1 px-1">
          <button
            v-for="filter in filters"
            :key="filter.id"
            @click="filtro = filter.id"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-all focus:outline-none shrink-0 border"
            :class="filtro === filter.id
              ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-200'
              : 'bg-white text-surface-600 border-surface-200 hover:border-primary-300 hover:text-primary-700 hover:bg-primary-50'"
          >
            <span v-html="filter.icon" class="[&>svg]:w-3.5 [&>svg]:h-3.5 shrink-0"/>
            {{ filter.label }}
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="friendStore.loading" class="flex flex-col items-center py-20">
        <div class="w-12 h-12 rounded-full border-4 border-slate-200 border-t-blue-600 animate-spin mb-4" />
        <p class="text-slate-500 font-semibold text-sm">Cargando...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="amigosFiltrados.length === 0"
        class="bg-white rounded-2xl p-8 md:p-12 text-center text-surface-600 shadow-sm border border-surface-200 flex flex-col items-center justify-center min-h-[250px]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-surface-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <p v-if="filtro === 'AMIGO'" class="text-base font-medium">Aún no tienes amigos en tu lista.</p>
        <p v-else-if="filtro === 'SOLICITUD_ENVIADA'" class="text-base font-medium">No tienes solicitudes enviadas pendientes.</p>
        <p v-else-if="filtro === 'SOLICITUD_RECIBIDA'" class="text-base font-medium">No tienes solicitudes de amistad recibidas.</p>
      </div>

      <!-- Grid de tarjetas -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="amigo in amigosFiltrados"
          :key="amigo.id_amistad"
          class="bg-white rounded-2xl p-5 shadow-sm border border-surface-200 transition-all hover:shadow-md flex flex-col h-full hover:-translate-y-0.5"
        >
          <!-- Header tarjeta -->
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center bg-primary-600 text-white font-bold text-lg uppercase shrink-0 relative overflow-hidden">
              <span class="z-0">{{ amigo.nombre_amigo?.charAt(0) || '?' }}</span>
              <img 
                v-if="amigo.foto_amigo" 
                :src="amigo.foto_amigo" 
                @error="$event.target.style.display = 'none'" 
                class="absolute inset-0 w-full h-full object-cover z-10 bg-white"
                alt="Perfil"
              />
            </div>
            <h3 class="text-base font-bold text-surface-900 m-0 truncate flex-1 min-w-0" :title="amigo.nombre_amigo">
              {{ amigo.nombre_amigo }}
            </h3>
            <span v-if="amigo.estado?.toUpperCase() === 'PENDIENTE' && amigo.solicitado_por_mi"
              class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-medium border bg-yellow-100 text-yellow-800 border-yellow-300 shrink-0">
              PENDIENTE
            </span>
          </div>

          <!-- Body -->
          <div class="flex-1 flex flex-col gap-2.5 text-sm text-surface-600 mb-4 font-medium">
            <div v-if="amigo.created_at" class="flex items-center gap-2.5">
              <IconCalendar class="w-4 h-4 shrink-0 text-primary-600" />
              <span>{{ formatearFecha(amigo.created_at) }}</span>
            </div>
          </div>

          <!-- ── Footer: Amigos aceptados → botón person-minus ── -->
          <div class="mt-auto pt-2 flex justify-end" v-if="filtro === 'AMIGO'">
            <button
              @click="eliminarAmigo(amigo)"
              :disabled="actionLoadingId === amigo.id_amistad"
              title="Eliminar amigo"
              class="w-9 h-9 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl transition-all flex items-center justify-center active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none"
            >
              <svg v-if="actionLoadingId !== amigo.id_amistad" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <line x1="17" x2="23" y1="11" y2="11"/>
              </svg>
              <span v-else class="w-4 h-4 border-2 border-red-300 border-t-red-600 rounded-full animate-spin"/>
            </button>
          </div>

          <!-- ── Footer: Solicitudes enviadas → botón X cancelar (Fase 6A) ── -->
          <div class="mt-auto pt-2 flex justify-end" v-if="filtro === 'SOLICITUD_ENVIADA'">
            <button
              @click="cancelarSolicitud(amigo)"
              :disabled="actionLoadingId === amigo.id_amistad"
              title="Cancelar solicitud"
              class="w-9 h-9 bg-red-50 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg v-if="actionLoadingId !== amigo.id_amistad" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              <span v-else class="w-4 h-4 border-2 border-red-300 border-t-red-500 rounded-full animate-spin"/>
            </button>
          </div>

          <!-- ── Footer: Solicitudes recibidas → check + X (Fase 8A, estilo Manage.vue) ── -->
          <div class="mt-auto pt-2 flex justify-end gap-2" v-if="filtro === 'SOLICITUD_RECIBIDA'">
            <!-- Aceptar: check verde (reciclado de Manage.vue botón continuar borrador) -->
            <button
              @click="aceptarSolicitud(amigo)"
              :disabled="actionLoadingId === amigo.id_amistad"
              title="Aceptar solicitud"
              class="w-9 h-9 bg-green-50 text-green-600 rounded-xl flex items-center justify-center hover:bg-green-600 hover:text-white transition-all shadow-sm focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg v-if="!(actionLoadingId === amigo.id_amistad && actionTypeLoading === 'aceptar')" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 6L9 17l-5-5"/>
              </svg>
              <span v-else class="w-4 h-4 border-2 border-green-300 border-t-green-600 rounded-full animate-spin"/>
            </button>

            <!-- Rechazar: X rojo (reciclado de Manage.vue botón cancelar activa) -->
            <button
              @click="rechazarSolicitud(amigo)"
              :disabled="actionLoadingId === amigo.id_amistad"
              title="Rechazar solicitud"
              class="w-9 h-9 bg-red-50 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg v-if="!(actionLoadingId === amigo.id_amistad && actionTypeLoading === 'rechazar')" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              <span v-else class="w-4 h-4 border-2 border-red-300 border-t-red-500 rounded-full animate-spin"/>
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>
