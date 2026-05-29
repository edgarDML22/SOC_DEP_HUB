<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useGuestStore } from '@/stores/community/guestStore'
import { useAlerts } from '@/composables/useAlerts' 
import IconQR from '@/components/icons/IconQr.vue'
import { IconPhone, IconMail, IconCalendar } from '@/components/icons'

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
  document.body.style.overflow = 'hidden'
}

const cerrarModalQR = () => {
  showQrModal.value = false
  selectedGuest.value = null
  document.body.style.overflow = ''
}

onUnmounted(() => {
  document.body.style.overflow = ''
})

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
          <div class="flex-1 flex flex-col gap-2.5 text-xs md:text-sm text-surface-600 mb-4 font-medium min-w-0">
            <div v-if="inv.telefono" class="flex items-center gap-2.5">
              <IconPhone class="w-4 h-4 shrink-0 text-primary-600" />
              <span class="truncate">{{ inv.telefono }}</span>
            </div>
            <div v-if="inv.correo" class="flex items-center gap-2.5">
              <IconMail class="w-4 h-4 shrink-0 text-primary-600" />
              <span class="truncate" :title="inv.correo">{{ inv.correo }}</span>
            </div>
            <div v-if="inv.fecha_expiracion" class="flex items-center gap-2.5">
              <IconCalendar class="w-4 h-4 shrink-0 text-primary-600" />
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
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showQrModal && selectedGuest" class="fixed inset-0 z-200 flex items-end sm:items-center justify-center p-0 sm:p-4">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="cerrarModalQR"></div>
          <div class="relative bg-white w-full sm:max-w-md sm:rounded-3xl rounded-t-3xl shadow-2xl overflow-hidden border border-slate-100 animate-scale-in flex flex-col">
            
            <!-- Header premium -->
            <div class="bg-primary-600 px-6 pt-6 pb-5 flex items-center gap-4 text-white">
              <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-white shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <rect x="3" y="3" width="7" height="7" rx="1"/>
                  <rect x="14" y="3" width="7" height="7" rx="1"/>
                  <rect x="3" y="14" width="7" height="7" rx="1"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14 14h1v1h-1zM17 14h1v1h-1zM14 17h1v1h-1zM17 17h1v1h-1zM20 14v.5M20 17h.5M20 20H14v-3"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-base font-extrabold text-white leading-none">Pase QR de Invitado</h3>
                <p class="text-primary-100 text-xs font-semibold mt-1.5 truncate">{{ selectedGuest.nombre }}</p>
              </div>
              <button @click="cerrarModalQR"
                class="w-8 h-8 bg-white/15 hover:bg-white/25 rounded-full flex items-center justify-center transition-colors focus:outline-none shrink-0 cursor-pointer"
              >
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 flex flex-col items-center">
              <div class="mb-4 text-center">
                <span class="text-2xl font-black text-slate-800 uppercase qr-code-text">
                  {{ selectedGuest.codigo_qr }}
                </span>
              </div>

              <div class="bg-slate-50 p-6 border-2 border-dashed border-slate-200 rounded-[28px] mb-5 flex justify-center w-fit shadow-inner">
                <img 
                  :src="generarQrUrl(selectedGuest.codigo_qr)" 
                  alt="QR Code" 
                  class="w-48 h-48 md:w-56 md:h-56 rounded-xl bg-white object-contain shadow-sm border border-slate-100" 
                />
              </div>

              <p class="text-xs text-slate-400 font-semibold text-center mb-5 leading-relaxed max-w-xs">
                Muestra este código QR en la entrada del club para registrar el acceso del invitado.
              </p>

              <div class="flex justify-center mb-6">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border bg-emerald-50 text-emerald-700 border-emerald-200">
                  <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse shrink-0"></span>
                  Pase Activo
                </span>
              </div>

              <!-- Footer Actions -->
              <div class="w-full flex flex-col gap-2.5">
                <button 
                  @click="copiarImagenAlPortapapeles(generarQrUrl(selectedGuest.codigo_qr))"
                  class="w-full bg-primary-600 hover:bg-primary-700 text-white rounded-2xl py-3.5 font-bold transition-all active:scale-95 shadow-md shadow-primary-100 focus:outline-none cursor-pointer text-center text-sm border-none"
                >
                  Copiar Código QR
                </button>
                <button 
                  @click="cerrarModalQR"
                  class="w-full bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-2xl py-3 font-bold transition-all active:scale-95 focus:outline-none cursor-pointer text-center text-sm"
                >
                  Cerrar
                </button>
              </div>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700;800&display=swap');

.qr-code-text {
  font-family: 'JetBrains Mono', monospace;
  font-weight: 700;
  letter-spacing: 0.25em;
}

/* Animaciones suaves */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@keyframes scale-in {
  from { opacity: 0; transform: scale(0.97) translateY(8px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-scale-in { animation: scale-in 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
