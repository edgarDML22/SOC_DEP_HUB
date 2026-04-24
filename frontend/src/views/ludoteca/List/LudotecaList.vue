<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useProfileStore } from '@/stores/profiles/socioStore'

const router = useRouter()
const profileStore = useProfileStore()

const miembros = ref([])
const search = ref('')
const loading = ref(false)

const mensaje = ref('')
const tipoMensaje = ref('')

// detectar gerente
const esGerente = computed(() => {
  return ['gerente', 'subgerente'].includes(profileStore.profileData?.tipo_socio)
})

const idSocio = computed(() => profileStore.profileData?.id_socio)

// formato fecha
const formatearFecha = (fecha) => {
  if (!fecha) return 'Sin datos'
  return new Date(fecha).toLocaleString('es-MX', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// cargar lista
const fetchMiembros = async () => {
  if (!idSocio.value) return

  loading.value = true
  try {
    const res = await api.get(`validar-tutor?id_socio=${idSocio.value}`)
    miembros.value = res.data.data || []
  } catch (error) {
    console.log(error)
    mensaje.value = 'No hay registros'
    tipoMensaje.value = 'error'
  } finally {
    loading.value = false
  }
}

watch(idSocio, (val) => {
  if (val) fetchMiembros()
}, { immediate: true })

// filtro
const miembrosFiltrados = computed(() => {
  if (!search.value) return miembros.value
  return miembros.value.filter(m =>
    (m.menor || '').toLowerCase().includes(search.value.toLowerCase())
  )
})

const irAUpdate = (m, tipo) => {
  router.push({
    name: 'ludoteca/update',
    query: {
      id_registro: m.id_registro,
      tipo: tipo
    }
  })
}
</script>

<template>
  <div class="flex flex-col gap-6 font-sans">

    <!-- BUSCADOR -->
    <div class="w-full md:max-w-md">
      <input
        v-model="search"
        placeholder="Buscar por nombre de menor..."
        class="w-full px-4 py-3 bg-white border border-surface-200 rounded-xl outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition-colors text-surface-900 font-medium shadow-sm"
      />
    </div>

    <!-- MENSAJE -->
    <div v-if="mensaje" :class="tipoMensaje === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'" class="p-4 rounded-xl border text-sm font-semibold shadow-sm animate-pulse">
      {{ mensaje }}
    </div>

    <!-- ESTADO CARGA -->
    <div v-if="loading" class="text-center py-12 text-surface-500 font-medium">
      <span class="animate-pulse">Cargando registros...</span>
    </div>

    <!-- Empty State -->
    <div v-else-if="miembrosFiltrados.length === 0" class="bg-white rounded-2xl md:rounded-3xl p-8 md:p-12 text-center text-surface-600 shadow-sm border border-surface-200 flex flex-col items-center justify-center min-h-[250px]">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-surface-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
      </svg>
      <p class="text-lg font-medium">No hay registros de menores en este momento</p>
    </div>

    <!-- LISTA GRID -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      
      <div v-for="m in miembrosFiltrados" :key="m.id_registro" class="bg-white rounded-2xl md:rounded-3xl p-6 shadow-sm border border-surface-200 transition-all hover:shadow-md hover:border-primary-200 flex flex-col h-full group pb-7">

        <!-- Header tarjeta -->
        <div class="flex items-start gap-4 mb-5">
          <div class="w-14 h-14 rounded-2xl flex items-center justify-center bg-primary-50 text-primary-700 font-bold text-xl uppercase shrink-0 border border-primary-100 shadow-sm group-hover:scale-105 transition-transform">
            {{ m.menor?.charAt(0) || '?' }}
          </div>
          
          <div class="flex-1 min-w-0 pt-1">
            <h3 class="text-lg font-bold text-surface-900 m-0 truncate" :title="m.menor">{{ m.menor }}</h3>
            
            <div class="mt-2 flex align-center">
               <span 
                 class="inline-flex items-center px-3 py-1 rounded-full text-[10px] sm:text-[11px] font-bold tracking-wider"
                 :class="m.estatus_visita === 'ACTIVA' ? 'bg-green-50 text-green-700 border border-green-200 shadow-sm' : 'bg-surface-100 text-surface-600 border border-surface-200 shadow-sm'"
               >
                 {{ m.estatus_visita === 'ACTIVA' ? 'ACTIVO EN SALA' : 'SIN REGISTRO ACTIVO' }}
               </span>
            </div>
          </div>
        </div>

        <!-- Body tarjeta -->
        <div class="flex-1 flex flex-col gap-2.5 text-sm text-surface-600 font-medium mb-6">
          <div class="flex items-center justify-between p-3 rounded-xl bg-surface-50 border border-surface-100 group-hover:border-surface-200 transition-colors">
              <span class="text-[11px] font-semibold uppercase tracking-wider text-surface-500">Ingreso:</span>
              <span class="font-bold text-surface-900">{{ formatearFecha(m.hora_ingreso) }}</span>
          </div>
          <div class="flex items-center justify-between p-3 rounded-xl bg-surface-50 border border-surface-100 group-hover:border-surface-200 transition-colors">
              <span class="text-[11px] font-semibold uppercase tracking-wider text-surface-500">Límite:</span>
              <span class="font-bold text-red-600">{{ formatearFecha(m.hora_limite) }}</span>
          </div>
        </div>

        <!-- Botones (Solo si es gerente) -->
        <div v-if="esGerente" class="mt-auto flex gap-3 flex-col sm:flex-row w-full pt-3 border-t border-surface-100">
          <button
            v-if="m.estatus_visita !== 'ACTIVA'"
            type="button"
            class="w-full sm:flex-1 bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-3 font-semibold transition-all active:scale-95 shadow-sm flex items-center justify-center gap-2"
            @click="irAUpdate(m, 'in')"
          >
            Registrar Entrada
          </button>

          <button
            v-if="m.estatus_visita === 'ACTIVA'"
            type="button"
            class="w-full sm:flex-1 bg-transparent border-2 border-red-500 hover:bg-red-50 text-red-600 hover:text-red-700 rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 shadow-sm flex items-center justify-center gap-2"
            @click="irAUpdate(m, 'out')"
          >
            Registrar Salida
          </button>
        </div>

      </div>

    </div>

  </div>
</template>