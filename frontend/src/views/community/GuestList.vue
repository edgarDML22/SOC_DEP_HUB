<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useGuestStore } from '@/stores/community/guestStore'
import { useAlerts } from '@/composables/useAlerts' 
import IconQR from '@/components/icons/IconQr.vue'

const router = useRouter()
const { toastInfo } = useAlerts()
const guestStore = useGuestStore()

const filtro = ref('TODOS')
const search = ref('')

onMounted(() => {
  guestStore.fetchInvitados()
})

const activeCount = computed(() => {
  return guestStore.invitados.filter(inv => inv.estatus_acceso === 'ACTIVO').length
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
    resultado = resultado.filter(inv => inv.nombre.toLowerCase().includes(s))
  }
  return resultado
})

const showQrModal = ref(false)
const selectedGuest = ref(null)

const abrirModalQR = (inv) => {
  selectedGuest.value = inv
  showQrModal.value = true
}

const cerrarModalQR = () => {
  showQrModal.value = false
  selectedGuest.value = null
}

const generarQrUrl = (codigo) => {
  const data = JSON.stringify({ codigo_qr: codigo, tipo: 'invitado' })
  return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(data)}`
}

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
  <!-- Quité bg-surface-50 y min-h-screen aquí para que no se duplique con el Layout -->
  <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8 pt-4 lg:pt-6 font-sans">
    <div class="max-w-7xl mx-auto flex flex-col gap-6">
      
      <!-- BOTÓN VOLVER UNIVERSAL -->
      <button @click="router.back()" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 focus:outline-none w-fit">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Volver
      </button>

      <!-- Encabezado -->
      <div class="flex flex-col gap-2">
        <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">Mis Invitados</h2>
        <p class="text-surface-500 text-sm md:text-base m-0 font-medium">Consulta el estatus de tus invitados</p>
        <div class="mt-2 text-sm" :class="activeCount >= 5 ? 'text-red-500 font-medium' : 'text-primary-700'">
           Invitados Activos: <span class="font-bold">{{ activeCount }} / 5</span>
        </div>
      </div>

      <!-- Buscador y Filtros Nivel 2 -->
      <div class="flex flex-col gap-4">
        <input 
          v-model="search" 
          placeholder="Buscar invitado por nombre..." 
          class="w-full md:max-w-md px-4 py-3 bg-white border border-surface-200 rounded-xl outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition-colors text-surface-900 font-medium shadow-sm" 
        />
        
        <!-- Pestañas Nivel 2 (Minimalistas) -->
        <div class="flex gap-6 border-b border-surface-200 w-full overflow-x-auto scrollbar-thin">
          <button 
            @click="cambiarFiltro('TODOS')" 
            class="pb-3 text-sm font-medium transition-all whitespace-nowrap focus:outline-none"
            :class="filtro === 'TODOS' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-surface-400 border-b-2 border-transparent hover:text-surface-600'"
          >
            Todos
          </button>
          <button 
            @click="cambiarFiltro('ACTIVO')" 
            class="pb-3 text-sm font-medium transition-all whitespace-nowrap focus:outline-none"
            :class="filtro === 'ACTIVO' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-surface-400 border-b-2 border-transparent hover:text-surface-600'"
          >
            Activos
          </button>
          <button 
            @click="cambiarFiltro('EXPIRADO')" 
            class="pb-3 text-sm font-medium transition-all whitespace-nowrap focus:outline-none"
            :class="filtro === 'EXPIRADO' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-surface-400 border-b-2 border-transparent hover:text-surface-600'"
          >
            Expirados
          </button>
        </div>
      </div>

      <!-- Estado de Carga -->
      <div v-if="guestStore.loading" class="text-center py-12 text-surface-500 font-medium">
        <span class="animate-pulse">Cargando invitados...</span>
      </div>

      <!-- Empty State -->
      <div v-else-if="invitadosFiltrados.length === 0" class="bg-white rounded-2xl p-8 md:p-12 text-center text-surface-600 shadow-sm border border-surface-200">
        <p v-if="filtro === 'TODOS'" class="text-lg font-medium">No hay invitados registrados en este momento</p>
        <p v-else-if="filtro === 'ACTIVO'" class="text-lg font-medium">No hay invitados con su pase activo en este momento</p>
        <p v-else-if="filtro === 'EXPIRADO'" class="text-lg font-medium">No hay invitados con pase expirado en este momento</p>
      </div>

      <!-- Lista de Tarjetas (Grid) -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div 
          v-for="inv in invitadosFiltrados" 
          :key="inv.id" 
          class="bg-white rounded-2xl p-5 shadow-sm border border-surface-200 transition-all hover:shadow-md flex flex-col h-full"
        >
          <!-- Header de tarjeta -->
          <div class="flex items-start gap-4 mb-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center bg-surface-100 text-primary-700 font-medium text-lg uppercase shrink-0">
              {{ inv.nombre.charAt(0) }}
            </div>
            <div class="flex-1 flex flex-col min-w-0">
              <h3 class="text-base font-bold text-surface-900 m-0 truncate" :title="inv.nombre">{{ inv.nombre }}</h3>
              <div class="mt-1">
                 <span 
                   class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-medium border"
                   :class="{
                     'bg-green-50 text-green-700 border-green-200': inv.estatus_acceso === 'ACTIVO',
                     'bg-red-50 text-red-700 border-red-200': inv.estatus_acceso === 'EXPIRADO' || inv.estatus_acceso === 'INACTIVO'
                   }"
                 >
                   {{ inv.estatus_acceso === 'EXPIRADO' ? 'EXPIRADO' : inv.estatus_acceso }}
                 </span>
              </div>
            </div>
          </div>

          <!-- Body de tarjeta -->
          <div class="flex-1 flex flex-col gap-3 text-sm text-surface-600 mb-5 font-medium">
            <div v-if="inv.telefono" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-surface-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
              </svg>
              <span class="truncate">{{ inv.telefono }}</span>
            </div>
            <div v-if="inv.correo" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-surface-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
              </svg>
              <span class="truncate" :title="inv.correo">{{ inv.correo }}</span>
            </div>
            <div v-if="inv.fecha_expiracion" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-surface-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
              </svg>
              <span>Expira: {{ inv.fecha_expiracion }}</span>
            </div>
          </div>

          <!-- Footer de tarjeta -->
          <div class="mt-auto">
            <button 
              @click="abrirModalQR(inv)" 
              class="w-full bg-surface-50 hover:bg-surface-100 text-surface-700 border border-surface-200 rounded-xl px-4 py-2.5 font-semibold transition-all flex items-center justify-center gap-2 active:scale-95"
            >
              <IconQR class="w-4 h-4 text-surface-500" />
              Ver código QR
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal QR -->
    <div v-if="showQrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm transition-all" @mousedown.self="cerrarModalQR">
      <div class="bg-white rounded-2xl p-6 md:p-8 w-full max-w-sm shadow-xl flex flex-col items-center text-center">
        <h3 class="text-xl font-bold text-surface-900 mb-2">Código QR de Acceso</h3>
        <p class="text-surface-600 font-medium text-sm mb-6">Este es el código QR de <strong class="text-surface-900 font-medium">{{ selectedGuest.nombre }}</strong></p>
        
        <div class="bg-surface-50 p-4 border border-dashed border-surface-300 rounded-xl mb-6 flex justify-center w-full">
          <img 
            :src="generarQrUrl(selectedGuest.codigo_qr)" 
            alt="QR Code" 
            class="w-48 h-48 md:w-56 md:h-56 rounded-lg bg-white object-contain" 
          />
        </div>

        <div class="w-full flex flex-col gap-3">
          <button 
            @click="copiarImagenAlPortapapeles(generarQrUrl(selectedGuest.codigo_qr))"
            class="w-full bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 shadow-sm focus:outline-none"
          >
            Copiar Imagen QR
          </button>
          <button 
            @click="cerrarModalQR"
            class="w-full bg-surface-50 hover:bg-surface-100 text-surface-700 border border-surface-200 rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 focus:outline-none"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>