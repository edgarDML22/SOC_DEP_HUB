<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useFamilyStore } from '@/stores/community/familyStore'
import { useAlerts } from '@/composables/useAlerts' 
import IconQR from '@/components/icons/IconQr.vue'

const router = useRouter()
const familyStore = useFamilyStore()
const { toastInfo } = useAlerts() 

const search = ref('')

// Helper para formatear fecha
const formatearFecha = (fecha) => {
  if (!fecha) return 'N/A'
  // Si la fecha viene con la Z o formato ISO largo, la limpiamos
  const d = new Date(fecha)
  if (isNaN(d.getTime())) return fecha
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}

// Helper para género
const formatearGenero = (g) => {
  if (!g) return 'No especificado'
  const gen = g.toUpperCase()
  if (gen === 'M') return 'Masculino'
  if (gen === 'F') return 'Femenino'
  return g
}

// Helper para color de parentesco
const getParentescoColor = (p) => {
  const parent = p?.toUpperCase() || ''
  if (parent.includes('HIJO')) return 'bg-blue-50 text-blue-700 border-blue-100'
  if (parent.includes('CONYUGE') || parent.includes('ESPOS')) return 'bg-purple-50 text-purple-700 border-purple-100'
  return 'bg-surface-50 text-surface-700 border-surface-200'
}

// REMOVED onMounted fetch since it's handled in SocioLayout

const miembrosFiltrados = computed(() => {
  let resultado = familyStore.miembrosFamiliares || []

  if (search.value) {
    const s = search.value.toLowerCase()
    resultado = resultado.filter(m =>
      m.nombre_completo?.toLowerCase().includes(s)
    )
  }
  return resultado
})

const showQrModal = ref(false)
const selectedMember = ref(null)

const abrirModalQR = (m) => {
  selectedMember.value = m
  showQrModal.value = true
}

const cerrarModalQR = () => {
  showQrModal.value = false
  selectedMember.value = null
}

const generarQrUrl = (codigo) => {
  return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(codigo)}`
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
  <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8 pt-4 lg:pt-6 font-sans">
    <div class="max-w-6xl mx-auto flex flex-col gap-6">
      
      <!-- Botón Volver (Estilo Amigos) -->
      <div>
        <button @click="router.back()" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 focus:outline-none w-fit group">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg> 
            Volver
        </button>

        <!-- Encabezado -->
        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
          <div class="flex flex-col gap-1">
            <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">Miembros Familiares</h2>
            <p class="text-surface-500 font-medium text-sm md:text-base m-0">Gestiona los accesos de tu círculo familiar</p>
          </div>
          
          <router-link 
            :to="{ name: 'family-members-add' }" 
            class="bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-5 py-2.5 font-bold transition-all active:scale-95 shadow-sm text-center w-full md:w-auto flex items-center justify-center gap-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
            Agregar Familiar
          </router-link>
        </div>
      </div>

      <!-- Buscador -->
      <div class="relative w-full md:max-w-md">
        <input 
          v-model="search" 
          placeholder="Buscar por nombre..." 
          class="w-full pl-11 pr-4 py-3 bg-white border border-surface-200 rounded-2xl outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition-all text-surface-900 font-medium shadow-sm" 
        />
        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-surface-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      </div>

      <!-- Estado de Carga -->
      <div v-if="familyStore.loading" class="text-center py-16 flex flex-col items-center gap-4">
        <div class="w-10 h-10 border-4 border-surface-100 border-t-primary-600 rounded-full animate-spin"></div>
        <p class="text-surface-500 font-semibold animate-pulse">Cargando familiares...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="miembrosFiltrados.length === 0" class="bg-white rounded-3xl p-12 text-center text-surface-600 shadow-sm border border-surface-100 flex flex-col items-center justify-center min-h-[300px]">
        <div class="p-4 bg-surface-50 rounded-full mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </div>
        <p class="text-lg font-bold text-surface-900">Sin registros</p>
        <p class="text-surface-500 font-medium max-w-xs mx-auto">No hay miembros familiares registrados que coincidan con tu búsqueda.</p>
      </div>

      <!-- Lista de Tarjetas (Grid) -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div 
          v-for="m in miembrosFiltrados" 
          :key="m.id_miembro" 
          class="bg-white rounded-[24px] p-6 shadow-sm border border-surface-100 transition-all hover:shadow-md hover:-translate-y-1 flex flex-col h-full group"
        >
          <!-- Header de tarjeta -->
          <div class="flex justify-between items-start mb-5">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-full flex items-center justify-center bg-primary-50 text-primary-700 font-bold text-xl uppercase shrink-0 border border-primary-100 shadow-inner">
                {{  m.nombre_completo ? m.nombre_completo.charAt(0) : '?' }}
              </div>
              <div class="flex flex-col min-w-0">
                <h3 class="text-lg font-bold text-surface-900 m-0 truncate leading-tight" :title="m.nombre_completo">
                  {{ m.nombre_completo }}
                </h3>
              </div>
            </div>
            <span :class="getParentescoColor(m.parentesco)" class="inline-flex items-center px-3 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wider border">
              {{ m.parentesco || 'FAMILIAR' }}
            </span>
          </div>

          <!-- Body de tarjeta -->
          <div class="flex-1 flex flex-col gap-3.5 text-[13px] text-surface-600 mb-6 font-semibold">
            <div v-if="m.fecha_nacimiento" class="flex items-center gap-3">
              <div class="p-1.5 bg-surface-50 rounded-lg text-surface-400 group-hover:text-primary-500 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                </svg>
              </div>
              <span class="text-surface-500 font-medium">Nacimiento:</span>
              <span class="text-surface-900 font-bold ml-auto">{{ formatearFecha(m.fecha_nacimiento) }}</span>
            </div>
            
            <div v-if="m.genero" class="flex items-center gap-3">
              <div class="p-1.5 bg-surface-50 rounded-lg text-surface-400 group-hover:text-primary-500 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
              </div>
              <span class="text-surface-500 font-medium">Género:</span>
              <span class="text-surface-900 font-bold ml-auto">{{ formatearGenero(m.genero) }}</span>
            </div>

            <div v-if="m.correo" class="flex items-center gap-3">
              <div class="p-1.5 bg-surface-50 rounded-lg text-surface-400 group-hover:text-primary-500 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
              </div>
              <span class="text-surface-900 font-bold truncate max-w-[150px]" :title="m.correo">{{ m.correo }}</span>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="mt-auto pt-4 border-t border-surface-50 flex justify-end">
            <button 
              @click="abrirModalQR(m)" 
              title="Ver código QR"
              class="w-10 h-10 bg-primary-50 hover:bg-primary-100 text-primary-600 border border-primary-100 rounded-xl transition-all flex items-center justify-center active:scale-90 focus:outline-none shadow-sm group/btn"
            >
              <IconQR class="w-5 h-5 group-hover/btn:scale-110 transition-transform" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal QR (Estilo Socio Titular) -->
    <div v-if="showQrModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all" @mousedown.self="cerrarModalQR">
      <div class="bg-white rounded-[32px] p-8 md:p-10 w-full max-w-sm shadow-2xl flex flex-col items-center text-center relative animate-in fade-in zoom-in duration-300">
        
        <p class="text-surface-600 font-medium text-lg mb-8 leading-relaxed">
          Miembro Familiar: <strong class="text-surface-900 font-bold">{{ selectedMember.nombre_completo }}</strong>
        </p>
        <div class="mb-6 text-center">
          <span class="text-2xl md:text-3xl font-normal text-surface-900 tracking-[0.2em] uppercase font-sans">
            {{ selectedMember.codigo_qr }}
          </span>
        </div>
        
        <div class="bg-surface-50 p-6 border-2 border-dashed border-surface-300 rounded-[32px] mb-8 flex justify-center w-fit shadow-inner">
          <img 
            :src="generarQrUrl(selectedMember.codigo_qr)" 
            alt="QR Code" 
            class="w-48 h-48 md:w-56 md:h-56 rounded-xl bg-white object-contain shadow-sm" 
          />
        </div>

        <div class="w-full flex flex-col gap-3">
          <button 
            @click="copiarImagenAlPortapapeles(generarQrUrl(selectedMember.codigo_qr))"
            class="w-full bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white rounded-2xl px-4 py-3.5 font-bold transition-all active:scale-95 shadow-lg shadow-primary-200 focus:outline-none"
          >
            Copiar Código QR
          </button>
          <button 
            @click="cerrarModalQR"
            class="w-full bg-surface-50 hover:bg-surface-100 text-surface-700 border border-surface-200 rounded-2xl px-4 py-3 font-bold transition-all active:scale-95 focus:outline-none"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animaciones suaves */
.animate-in {
  animation-duration: 0.3s;
  animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

.zoom-in {
  from { transform: scale(0.95); }
  to { transform: scale(1); }
}

/* Scrollbar para buscador en móviles */
input::placeholder {
  color: #94a3b8;
  font-weight: 500;
}
</style>