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

const filters = [
  { 
    id: 'TODOS', 
    label: 'Todos', 
    icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>` 
  },
  { 
    id: 'ACTIVO', 
    label: 'Activos', 
    icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>` 
  },
  { 
    id: 'EXPIRADO', 
    label: 'Expirados', 
    icon: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>` 
  },
]

// REMOVED onMounted fetch since it's handled in SocioLayout

const activeCount = computed(() => {
  return guestStore.invitados.filter(inv => inv.estatus_acceso === 'ACTIVO').length
})

const cambiarFiltro = (nuevo) => {
  filtro.value = nuevo
}

const invitadosFiltrados = computed(() => {
  let resultado = guestStore.invitados

  if (filtro.value !== 'TODOS') {
    resultado = resultado.filter(inv => inv.estatus_acceso?.toUpperCase() === filtro.value)
  }

  if (search.value) {
    const s = search.value.toLowerCase()
    resultado = resultado.filter(inv => inv.nombre.toLowerCase().includes(s))
  }

  // Ordenar: ACTIVO primero, luego el resto (EXPIRADO, etc.)
  return [...resultado].sort((a, b) => {
    const order = { 'ACTIVO': 0, 'EXPIRADO': 1, 'INACTIVO': 2, 'SIN_PASE': 3 };
    const statusA = a.estatus_acceso?.toUpperCase() || 'SIN_PASE';
    const statusB = b.estatus_acceso?.toUpperCase() || 'SIN_PASE';
    return (order[statusA] ?? 99) - (order[statusB] ?? 99);
  });
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

      <!-- Encabezado -->
      <div class="flex flex-col gap-2">
        <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">Mis Invitados</h2>
        <p class="text-surface-500 text-sm md:text-base m-0 font-medium">Consulta el estatus de tus invitados</p>
        <div class="mt-1">
           <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold border" :class="activeCount >= 5 ? 'bg-red-50 text-red-600 border-red-200' : 'bg-primary-50 text-primary-700 border-primary-100'">
             Activos: {{ activeCount }} / 5
           </span>
        </div>
      </div>

      <!-- Buscador y Filtros Nivel 2 -->
      <div class="flex flex-col gap-4">
        <input 
          v-model="search" 
          placeholder="Buscar invitado por nombre..." 
          class="w-full md:max-w-md px-4 py-3 bg-white border border-surface-200 rounded-xl outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition-colors text-surface-900 font-medium shadow-sm" 
        />
        
        <!-- FILTROS PILL (scroll horizontal) -->
        <div class="flex gap-2 overflow-x-auto scrollbar-none pb-1 -mx-1 px-1">
          <button
            v-for="filter in filters"
            :key="filter.id"
            @click="cambiarFiltro(filter.id)"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-all focus:outline-none shrink-0 border"
            :class="filtro === filter.id
              ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-200'
              : 'bg-white text-surface-600 border-surface-200 hover:border-primary-300 hover:text-primary-700 hover:bg-primary-50'"
          >
            <span v-html="filter.icon" class="[&>svg]:w-3.5 [&>svg]:h-3.5 shrink-0"></span>
            {{ filter.label }}
          </button>
        </div>
      </div>

      <!-- Estado de Carga -->
      <div v-if="guestStore.loading" class="text-center py-12 text-surface-500 font-medium">
        <span class="animate-pulse">Cargando invitados...</span>
      </div>

      <!-- Empty State -->
      <div v-else-if="invitadosFiltrados.length === 0" class="bg-white rounded-2xl p-8 md:p-12 text-center text-surface-600 shadow-sm border border-surface-200 flex flex-col items-center justify-center min-h-[250px]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-surface-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <p v-if="filtro === 'TODOS'" class="text-base font-medium">No hay invitados registrados en este momento.</p>
        <p v-else-if="filtro === 'ACTIVO'" class="text-base font-medium">No hay invitados con pase activo en este momento.</p>
        <p v-else-if="filtro === 'EXPIRADO'" class="text-base font-medium">No hay invitados con pase expirado en este momento.</p>
      </div>

      <!-- Lista de Tarjetas (Grid) -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div 
          v-for="inv in invitadosFiltrados" 
          :key="inv.id" 
          class="bg-white rounded-[24px] p-6 shadow-sm border border-surface-100 transition-all hover:shadow-md hover:-translate-y-1 flex flex-col h-full group"
        >
          <!-- Header de tarjeta -->
          <div class="flex items-start gap-4 mb-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center bg-primary-600 text-white font-bold text-lg uppercase shrink-0">
              {{ inv.nombre?.charAt(0) || '?' }}
            </div>
            <div class="flex-1 flex flex-col min-w-0">
              <h3 class="text-base font-bold text-surface-900 m-0 truncate" :title="inv.nombre">{{ inv.nombre }}</h3>
              <div class="mt-1">
                  <span 
                    v-if="inv.estatus_acceso === 'ACTIVO'"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold border bg-green-50 text-green-700 border-green-200 tracking-wide uppercase"
                  >
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse shrink-0"></span>
                    Activo
                  </span>
                  <span 
                    v-else
                    class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold border bg-red-50 text-red-700 border-red-200 tracking-wide uppercase"
                  >
                    {{ inv.estatus_acceso === 'EXPIRADO' ? 'EXPIRADO' : inv.estatus_acceso }}
                  </span>
              </div>
            </div>
          </div>

          <!-- Body de tarjeta -->
          <div class="flex-1 flex flex-col gap-2 text-xs md:text-sm text-surface-600 mb-4 font-medium min-w-0">
            <div v-if="inv.telefono" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
              </svg>
              <span class="truncate">{{ inv.telefono }}</span>
            </div>
            <div v-if="inv.correo" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
              </svg>
              <span class="truncate" :title="inv.correo">{{ inv.correo }}</span>
            </div>
            <div v-if="inv.fecha_expiracion" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
              </svg>
              <span>Expira: {{ inv.fecha_expiracion }}</span>
            </div>
          </div>

          <!-- Footer de tarjeta -->
          <div class="mt-auto pt-4 border-t border-surface-50 flex justify-end" v-if="inv.estatus_acceso === 'ACTIVO'">
            <button 
              @click="abrirModalQR(inv)" 
              title="Ver codigo QR"
              class="w-10 h-10 bg-primary-50 hover:bg-primary-100 text-primary-600 border border-primary-100 rounded-xl transition-all flex items-center justify-center active:scale-90 focus:outline-none shadow-sm group/btn"
            >
              <IconQR class="w-5 h-5 group-hover/btn:scale-110 transition-transform" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal QR -->
    <div v-if="showQrModal" class="fixed inset-0 z-200 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all" @mousedown.self="cerrarModalQR">
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
