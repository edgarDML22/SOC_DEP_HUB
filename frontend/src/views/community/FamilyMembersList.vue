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

// REMOVED onMounted fetch since it's handled in SocioLayout

const miembrosFiltrados = computed(() => {
  let resultado = familyStore.miembrosFamiliares

  if (search.value) {
    const s = search.value.toLowerCase()
    resultado = resultado.filter(m =>
      m.nombre_completo.toLowerCase().includes(s)
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
  const data = JSON.stringify({ codigo_qr: codigo, tipo: 'familiar' })
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
  <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8 pt-4 lg:pt-6 font-sans">
    <div class="max-w-7xl mx-auto flex flex-col gap-6">
      
      <!-- BOTÓN VOLVER UNIVERSAL -->
      <button @click="router.back()" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 focus:outline-none w-fit">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Volver
      </button>

      <!-- Encabezado -->
      <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
        <div class="flex flex-col gap-2">
          <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">Miembros Familiares</h2>
          <p class="text-surface-500 text-sm md:text-base m-0 font-medium">Consulta los miembros asociados a tu cuenta</p>
        </div>
        
        <router-link 
          :to="{ name: 'family-members-add' }" 
          class="bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 shadow-sm text-center w-full md:w-auto flex items-center justify-center"
        >
          + Agregar Familiar
        </router-link>
      </div>

      <!-- Buscador -->
      <div class="flex flex-col gap-4">
        <input 
          v-model="search" 
          placeholder="Buscar familiar por nombre..." 
          class="w-full md:max-w-md px-4 py-3 bg-white border border-surface-200 rounded-xl outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition-colors text-surface-900 font-medium shadow-sm" 
        />
      </div>

      <!-- Estado de Carga -->
      <div v-if="familyStore.loading" class="text-center py-12 text-surface-500 font-medium">
        <span class="animate-pulse">Cargando familiares...</span>
      </div>

      <!-- Empty State -->
      <div v-else-if="miembrosFiltrados.length === 0" class="bg-white rounded-2xl p-8 md:p-12 text-center text-surface-600 shadow-sm border border-surface-200">
        <p class="text-lg font-medium">No tienes miembros familiares registrados o no coinciden con la búsqueda.</p>
      </div>

      <!-- Lista de Tarjetas (Grid) -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div 
          v-for="m in miembrosFiltrados" 
          :key="m.id_miembro" 
          class="bg-white rounded-2xl p-5 shadow-sm border border-surface-200 transition-all hover:shadow-md flex flex-col h-full"
        >
          <!-- Header de tarjeta -->
          <div class="flex items-start gap-4 mb-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center bg-surface-100 text-primary-700 font-medium text-lg uppercase shrink-0">
              {{ m.nombre_completo.charAt(0) }}
            </div>
            <div class="flex-1 flex flex-col min-w-0">
              <h3 class="text-base font-bold text-surface-900 m-0 truncate" :title="m.nombre_completo">{{ m.nombre_completo }}</h3>
              <div class="mt-1">
                 <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-medium border bg-primary-50 text-primary-700 border-primary-100">
                   {{ m.parentesco || 'FAMILIAR' }}
                 </span>
              </div>
            </div>
          </div>

          <!-- Body de tarjeta -->
          <div class="flex-1 flex flex-col gap-3 text-sm text-surface-600 mb-5 font-medium">
            <div v-if="m.fecha_nacimiento" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-surface-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
              </svg>
              <span>Nacimiento: {{ m.fecha_nacimiento }}</span>
            </div>
            <div v-if="m.genero" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-surface-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
              </svg>
              <span>Género: {{ m.genero }}</span>
            </div>
            <div v-if="m.correo" class="flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-surface-400" viewBox="0 0 20 20" fill="currentColor">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
              </svg>
              <span class="truncate" :title="m.correo">{{ m.correo }}</span>
            </div>
          </div>

          <!-- Footer de tarjeta -->
          <div class="mt-auto">
            <button 
              @click="abrirModalQR(m)" 
              class="w-full rounded-xl px-4 py-2.5 font-semibold transition-all flex items-center justify-center gap-2 active:scale-95 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white shadow-md focus:outline-none"
            >
              <IconQR class="w-4 h-4 text-white" />
              Ver código QR
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal QR -->
    <div v-if="showQrModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all" @mousedown.self="cerrarModalQR">
      <div class="bg-white rounded-2xl p-6 md:p-8 w-full max-w-sm shadow-xl flex flex-col items-center text-center">
        <h3 class="text-xl font-bold text-surface-900 mb-2">Código QR de Acceso</h3>
        <p class="text-surface-600 font-medium text-sm mb-6">Este es el código QR de <strong class="text-surface-900 font-medium">{{ selectedMember.nombre_completo }}</strong></p>
        
        <div class="bg-surface-50 p-4 border border-dashed border-surface-300 rounded-xl mb-6 flex justify-center w-full">
          <img 
            :src="generarQrUrl(selectedMember.codigo_qr)" 
            alt="QR Code" 
            class="w-48 h-48 md:w-56 md:h-56 rounded-lg bg-white object-contain" 
          />
        </div>

        <div class="w-full flex flex-col gap-3">
          <button 
            @click="copiarImagenAlPortapapeles(generarQrUrl(selectedMember.codigo_qr))"
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