<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAgendaStore } from '@/stores/agendaStore'
import { storeToRefs } from 'pinia'

const router = useRouter()
const agendaStore = useAgendaStore()
const { encuentrosSocioVisibles, loadingSocio, errorSocio } = storeToRefs(agendaStore)

const props = defineProps({
  modo: {
    type: String,
    default: 'socio',
    validator: (v) => ['socio', 'instructor'].includes(v)
  }
})

const activeView = ref('hoy')

const tabs = [
  {
    key: 'hoy',
    label: 'Hoy',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
  },
  {
    key: 'semanal',
    label: 'Próximas',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>`,
  },
]

onMounted(() => {
  agendaStore.fetchSocioAgenda()
})

const formatHora = (hora) => {
  if (!hora) return '—'
  return hora.substring(0, 5)
}

const formatFechaLarga = (iso) => {
  if (!iso) return '—'
  return new Date(iso + 'T12:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const formatFecha = (iso) => {
  if (!iso) return '—'
  const [y, m, d] = iso.split('-')
  const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']
  return `${d} ${meses[parseInt(m) - 1]} ${y}`
}

const encuentrosHoy = computed(() => {
  const hoyStr = new Date().toLocaleDateString('en-CA') // formato YYYY-MM-DD
  return encuentrosSocioVisibles.value.filter(
    (e) => e.fecha === hoyStr
  )
})

// Modal State
const showModal = ref(false)
const selectedActivity = ref(null)

const openDetails = (activity) => {
  selectedActivity.value = activity
  showModal.value = true
}

const closeDetails = () => {
  showModal.value = false
  setTimeout(() => { selectedActivity.value = null }, 300)
}

const getActivityLabel = (tipo) => {
  const configs = {
    'RESERVA': 'Reserva de Espacio',
    'CLASE_ABIERTA': 'Clase Abierta',
    'CLASE_CERRADA': 'Clase Cerrada',
    'TORNEO': 'Encuentro Torneo'
  }
  return configs[tipo] || tipo
}

const getStatusBadge = (status) => {
  const s = String(status).toUpperCase()
  if (['ACTIVA', 'CONFIRMADA', 'PROGRAMADO', 'EN_CURSO'].includes(s)) {
    return 'bg-emerald-100 text-emerald-800 border-emerald-200'
  }
  if (['PENDIENTE', 'ESPERA'].includes(s)) {
    return 'bg-yellow-100 text-yellow-800 border-yellow-200'
  }
  return 'bg-surface-100 text-surface-800 border-surface-200'
}

// Agrupamos las próximas actividades por fecha
const agruparPorFecha = (actividades) => {
  const grupos = {}
  actividades.forEach(a => {
    if (!grupos[a.fecha]) grupos[a.fecha] = []
    grupos[a.fecha].push(a)
  })
  return Object.keys(grupos).sort().map(fecha => ({
    fecha,
    actividades: grupos[fecha]
  }))
}
</script>

<template>
  <div class="w-full font-sans bg-surface-50 min-h-screen">
    
    <!-- Encabezado Original Blanco -->
    <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 pt-4 md:pt-6 lg:pt-8 bg-surface-50">
      <button
        @click="router.push(props.modo === 'instructor' ? '/instructor/home' : '/socio/home')"
        class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 focus:outline-none w-fit group"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m15 18-6-6 6-6"/>
        </svg>
        Volver
      </button>

      <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 mb-5 tracking-tight">Mi Agenda</h2>

      <!-- Tabs -->
      <div class="flex p-1.5 bg-surface-100 rounded-2xl w-full max-w-2xl mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-6">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeView = tab.key"
          class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
          :class="activeView === tab.key
            ? 'bg-primary-600 text-white font-extrabold rounded-xl shadow-md transform scale-[1.02]'
            : 'text-surface-500 font-bold hover:bg-white/60 hover:text-surface-700 rounded-xl'"
        >
          <span v-html="tab.icon" class="flex-shrink-0"></span>
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Main Content Container -->
    <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8">
      <div class="max-w-7xl mx-auto">
      
        <!-- Loading State -->
        <div v-if="loadingSocio" class="flex justify-center py-16">
          <div class="w-10 h-10 rounded-full border-3 border-surface-200 border-t-primary-500 animate-spin"/>
        </div>

        <!-- Error State -->
        <div v-else-if="errorSocio" class="p-6 bg-red-50 border border-red-200 rounded-2xl text-center text-red-700 text-sm font-medium">
          {{ errorSocio }}
        </div>

        <!-- Agenda Hoy -->
        <div v-else-if="activeView === 'hoy'" class="space-y-4">
          <div v-if="encuentrosHoy.length === 0" class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm">
            <p class="text-surface-900 font-bold text-lg">Día Libre</p>
            <p class="text-surface-500 font-medium text-sm text-center max-w-xs mt-1">
              No tienes actividades programadas para hoy.
            </p>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="(enc, idx) in encuentrosHoy"
              :key="idx"
              class="bg-white rounded-3xl border border-surface-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden flex flex-col group"
            >
              <!-- Card Header - Gradient Azul (Animado) -->
              <div class="px-5 py-3.5 flex items-center justify-between bg-gradient-to-r from-blue-500 to-blue-700">
                <span class="text-white font-bold text-sm tracking-wide line-clamp-1 pr-2">
                  {{ enc.titulo }}
                </span>
                <span class="bg-white/20 text-white text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full shrink-0">
                  {{ getActivityLabel(enc.tipo) }}
                </span>
              </div>

              <!-- Body -->
              <div class="px-5 py-4 flex-1 space-y-3">
                
                <div class="flex items-center justify-between">
                  <!-- Fecha y hora -->
                  <div class="flex items-center gap-2 text-surface-600">
                    <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-semibold">{{ formatFecha(enc.fecha) }}</span>
                  </div>
                  <!-- Estatus -->
                  <span class="inline-block text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-lg border" :class="getStatusBadge(enc.estatus)">
                    {{ enc.estatus }}
                  </span>
                </div>

                <div class="flex items-center gap-2 text-surface-600">
                  <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                  </svg>
                  <span class="text-sm font-semibold tabular-nums">
                    {{ formatHora(enc.hora_inicio) }} – {{ formatHora(enc.hora_fin) }}
                  </span>
                </div>

                <!-- Instructor -->
                <div v-if="enc.instructor" class="flex items-center gap-2 text-surface-600">
                  <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span class="text-sm font-medium text-surface-500 truncate">{{ enc.instructor }}</span>
                </div>

                <!-- Espacio -->
                <div v-if="enc.espacio" class="flex items-center gap-2 text-surface-600">
                  <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span class="text-sm font-medium text-surface-500 truncate">{{ enc.espacio }}</span>
                </div>
              </div>

              <!-- Footer - Botón detalles -->
              <div class="px-5 pb-5 mt-auto flex justify-end">
                <button
                  @click="openDetails(enc)"
                  class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-2xl flex items-center justify-center transition-all duration-300 active:scale-95 cursor-pointer border border-blue-700 shadow-md shadow-blue-500/10"
                  title="Ver detalles"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Agenda Semanal (Próximas) -->
        <div v-else class="space-y-8">
          <div v-if="encuentrosSocioVisibles.length === 0" class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm">
            <p class="text-surface-900 font-bold text-lg">Agenda Vacía</p>
            <p class="text-surface-500 font-medium text-sm text-center max-w-xs mt-1">
              No tienes actividades próximas.
            </p>
          </div>
          
          <div v-else v-for="grupo in agruparPorFecha(encuentrosSocioVisibles)" :key="grupo.fecha" class="space-y-4">
            <h3 class="text-sm md:text-base font-extrabold text-blue-900 uppercase tracking-widest pl-2 border-l-4 border-blue-500">
              {{ formatFechaLarga(grupo.fecha) }}
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="(enc, idx) in grupo.actividades"
                :key="idx"
                class="bg-white rounded-3xl border border-surface-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden flex flex-col group"
              >
                <!-- Card Header - Gradient Azul (Animado) -->
                <div class="px-5 py-3.5 flex items-center justify-between bg-gradient-to-r from-blue-500 to-blue-700">
                  <span class="text-white font-bold text-sm tracking-wide line-clamp-1 pr-2">
                    {{ enc.titulo }}
                  </span>
                  <span class="bg-white/20 text-white text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full shrink-0">
                    {{ getActivityLabel(enc.tipo) }}
                  </span>
                </div>

                <!-- Body -->
                <div class="px-5 py-4 flex-1 space-y-3">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-surface-600">
                      <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span class="text-sm font-semibold">{{ formatFecha(enc.fecha) }}</span>
                    </div>
                    <!-- Estatus -->
                    <span class="inline-block text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-lg border" :class="getStatusBadge(enc.estatus)">
                      {{ enc.estatus }}
                    </span>
                  </div>

                  <div class="flex items-center gap-2 text-surface-600">
                    <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span class="text-sm font-semibold tabular-nums">
                      {{ formatHora(enc.hora_inicio) }} – {{ formatHora(enc.hora_fin) }}
                    </span>
                  </div>

                  <!-- Espacio -->
                  <div v-if="enc.espacio" class="flex items-center gap-2 text-surface-600">
                    <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-sm font-medium text-surface-500 truncate">{{ enc.espacio }}</span>
                  </div>
                </div>

                <!-- Footer - Botón detalles -->
                <div class="px-5 pb-5 mt-auto flex justify-end">
                  <button
                    @click="openDetails(enc)"
                    class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-2xl flex items-center justify-center transition-all duration-300 active:scale-95 cursor-pointer border border-blue-700 shadow-md shadow-blue-500/10"
                    title="Ver detalles"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL DE DETALLES -->
    <Transition name="fade">
      <div v-if="showModal && selectedActivity" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-surface-900/60 backdrop-blur-sm" @click="closeDetails"></div>
        <div class="relative bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/20 animate-scale-in max-h-full flex flex-col">
          
          <!-- Header Modal -->
          <div class="bg-blue-600 p-6 sm:p-8 text-white flex justify-between items-start shrink-0">
            <div>
              <div class="flex items-center gap-2 mb-2">
                <span class="px-2 py-0.5 rounded border border-white/30 text-[10px] font-bold uppercase tracking-wider bg-white/10 backdrop-blur-sm">
                  {{ getActivityLabel(selectedActivity.tipo) }}
                </span>
                <span class="px-2 py-0.5 rounded border border-white/30 text-[10px] font-bold uppercase tracking-wider bg-white/10 backdrop-blur-sm">
                  {{ selectedActivity.estatus }}
                </span>
              </div>
              <h3 class="text-xl sm:text-2xl font-bold tracking-tight leading-tight">{{ selectedActivity.titulo }}</h3>
              <p class="text-blue-100 text-sm font-medium opacity-90 mt-1 capitalize">{{ formatFechaLarga(selectedActivity.fecha) }}</p>
            </div>
            <button @click="closeDetails" class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors focus:outline-none shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Body Modal -->
          <div class="p-6 sm:p-8 flex flex-col gap-6 overflow-y-auto overflow-x-hidden scrollbar-thin">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <!-- Horario -->
              <div class="flex flex-col gap-1.5">
                <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest">Horario</span>
                <div class="flex items-center gap-3 text-surface-900 font-bold">
                  <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  </div>
                  <span class="truncate">{{ formatHora(selectedActivity.hora_inicio) }} - {{ formatHora(selectedActivity.hora_fin) }}</span>
                </div>
              </div>

              <!-- Espacio -->
              <div class="flex flex-col gap-1.5">
                <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest">Espacio</span>
                <div class="flex items-center gap-3 text-surface-900 font-bold">
                  <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                  </div>
                  <span class="truncate">{{ selectedActivity.espacio || 'Por confirmar' }}</span>
                </div>
              </div>
            </div>

            <!-- Información condicional -->
            
            <!-- Instructor / Árbitro (Solo para Clases o Torneos) -->
            <div v-if="selectedActivity.tipo !== 'RESERVA' && selectedActivity.instructor" class="flex flex-col gap-1.5 border-t border-surface-100 pt-6">
              <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest">Instructor / Responsable</span>
              <div class="flex items-center gap-3 text-surface-900 font-bold">
                <div class="w-10 h-10 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <span>{{ selectedActivity.instructor }}</span>
              </div>
            </div>

            <!-- Fase de Torneo -->
            <div v-if="selectedActivity.tipo === 'TORNEO' && selectedActivity.fase" class="flex flex-col gap-1.5 border-t border-surface-100 pt-6">
              <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest">Fase del Torneo</span>
              <div class="flex items-center gap-3 text-surface-900 font-bold">
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
                <span>{{ selectedActivity.fase }}</span>
              </div>
            </div>

            <!-- Acompañantes (Solo para Reservas) -->
            <div v-if="selectedActivity.tipo === 'RESERVA'" class="border-t border-surface-100 pt-6">
              <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest mb-3 block">Acompañantes</span>
              
              <div v-if="!selectedActivity.acompanantes || selectedActivity.acompanantes.length === 0" class="p-4 bg-surface-50 border border-surface-200 rounded-2xl text-center text-surface-500 text-sm font-medium">
                Reserva individual (Sin acompañantes)
              </div>
              <div v-else class="flex flex-col gap-2 max-h-40 overflow-y-auto pr-2 scrollbar-thin">
                <div v-for="(acomp, idx) in selectedActivity.acompanantes" :key="idx" class="flex items-center justify-between p-3.5 bg-white rounded-xl border border-surface-200 shadow-sm">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-extrabold uppercase shrink-0 shadow-sm">
                      {{ acomp.nombre?.charAt(0) || '?' }}
                    </div>
                    <span class="text-sm font-bold text-surface-900 truncate leading-snug">{{ acomp.nombre }}</span>
                  </div>
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border tracking-wide uppercase bg-blue-50 text-blue-700 border-blue-200">
                    {{ acomp.tipo }}
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
@keyframes scale-in {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
.animate-scale-in {
    animation: scale-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
