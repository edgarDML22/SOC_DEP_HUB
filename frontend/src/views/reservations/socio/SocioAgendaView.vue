<script setup>
import { ref, onMounted, computed } from 'vue'
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
    label: 'Semanal',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>`,
  },
]

onMounted(() => {
  agendaStore.fetchSocioAgenda()
})

const formatHora = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
}

const formatFecha = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' })
}

const encuentrosHoy = computed(() => {
  const hoy = new Date().toDateString()
  return encuentrosSocioVisibles.value.filter(
    (e) => e.fecha_hora_inicio && new Date(e.fecha_hora_inicio).toDateString() === hoy
  )
})

const hayEncuentros = computed(() => encuentrosSocioVisibles.value.length > 0)
</script>

<template>
  <div class="w-full font-sans bg-surface-50 min-h-screen">

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

    <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8">
      <div class="max-w-7xl mx-auto">

        <div v-if="loadingSocio" class="flex justify-center py-16">
          <div class="w-10 h-10 rounded-full border-3 border-surface-200 border-t-primary-500 animate-spin"/>
        </div>

        <div v-else-if="errorSocio" class="p-6 bg-red-50 border border-red-200 rounded-2xl text-center text-red-700 text-sm font-medium">
          {{ errorSocio }}
        </div>

        <div v-else-if="activeView === 'hoy'" class="space-y-4">
          <div v-if="encuentrosHoy.length === 0" class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm">
            <p class="text-surface-900 font-bold text-lg">Sin actividades para hoy</p>
            <p class="text-surface-500 font-medium text-sm text-center max-w-xs mt-1">
              No tienes encuentros de torneo programados para hoy.
            </p>
          </div>
          <div
            v-for="enc in encuentrosHoy"
            :key="enc.id_encuentro"
            class="bg-white rounded-2xl border border-surface-200 p-5 shadow-sm"
          >
            <p class="text-sm font-bold text-surface-900">{{ enc.nombre_torneo }}</p>
            <p class="text-xs text-surface-500 mt-1">{{ enc.espacio || 'Espacio por confirmar' }} · {{ formatHora(enc.fecha_hora_inicio) }}</p>
            <span class="inline-block mt-2 text-[10px] font-bold uppercase tracking-wider text-primary-600 bg-primary-50 px-2 py-0.5 rounded-lg">
              {{ enc.fase_bracket }} #{{ enc.numero_encuentro }}
            </span>
          </div>
        </div>

        <div v-else class="space-y-4">
          <div v-if="!hayEncuentros" class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm">
            <p class="text-surface-900 font-bold text-lg">Vista Semanal</p>
            <p class="text-surface-500 font-medium text-sm text-center max-w-xs mt-1">
              No tienes encuentros de torneo próximos en tu agenda.
            </p>
          </div>
          <div
            v-for="enc in encuentrosSocioVisibles"
            :key="enc.id_encuentro"
            class="bg-white rounded-2xl border border-surface-200 p-5 shadow-sm"
          >
            <p class="text-sm font-bold text-surface-900">{{ enc.nombre_torneo }}</p>
            <p class="text-xs text-surface-500 mt-1">
              {{ formatFecha(enc.fecha_hora_inicio) }} · {{ formatHora(enc.fecha_hora_inicio) }} — {{ enc.espacio || 'Por confirmar' }}
            </p>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>
