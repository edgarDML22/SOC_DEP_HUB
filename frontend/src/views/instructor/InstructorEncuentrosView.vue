<script setup>
import { onMounted, computed } from 'vue'
import { useAgendaStore } from '@/stores/agendaStore'
import { storeToRefs } from 'pinia'

const agendaStore = useAgendaStore()
const { encuentrosInstructorVisibles, loadingInstructor, errorInstructor } = storeToRefs(agendaStore)

onMounted(() => {
  agendaStore.fetchInstructorEncuentros()
})

const formatHora = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
}

const hayEncuentros = computed(() => encuentrosInstructorVisibles.value.length > 0)
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans p-4 md:p-6 lg:p-8 pb-24 lg:pb-8 flex justify-center">
    <div class="w-full max-w-5xl flex flex-col gap-6">

      <div class="border-b border-surface-200 pb-5">
        <p class="text-sm font-bold text-surface-400 uppercase tracking-widest mb-1">Hoy</p>
        <h1 class="text-2xl md:text-3xl font-bold text-surface-900 tracking-tight">Encuentros de Torneo</h1>
        <p class="text-sm text-surface-500 mt-2">Partidos asignados como árbitro</p>
      </div>

      <section v-if="loadingInstructor" class="flex flex-col items-center py-16">
        <div class="w-10 h-10 rounded-full border-3 border-surface-200 border-t-primary-500 animate-spin"/>
        <p class="text-surface-500 font-semibold mt-4 text-sm">Cargando encuentros...</p>
      </section>

      <section v-else-if="errorInstructor" class="p-8 bg-red-50 rounded-3xl border border-red-200 text-center">
        <p class="text-red-700 font-bold">{{ errorInstructor }}</p>
      </section>

      <div v-else-if="hayEncuentros" class="flex flex-col gap-4">
        <div
          v-for="enc in encuentrosInstructorVisibles"
          :key="enc.id_encuentro"
          class="bg-white border border-surface-200 rounded-3xl p-5 md:p-6 shadow-sm"
        >
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h4 class="font-bold text-surface-900 text-base">{{ enc.nombre_torneo }}</h4>
              <p class="text-xs text-surface-500 mt-1">{{ enc.espacio || 'Espacio por confirmar' }}</p>
              <span class="inline-block mt-2 text-[10px] font-bold uppercase tracking-wider text-primary-600 bg-primary-50 px-2 py-0.5 rounded-lg">
                {{ enc.fase_bracket }} · Encuentro #{{ enc.numero_encuentro }}
              </span>
            </div>
            <div class="bg-surface-100 rounded-xl px-4 py-2 border border-surface-200 text-center shrink-0">
              <span class="font-bold text-surface-900 text-sm">{{ formatHora(enc.fecha_hora_inicio) }}</span>
              <span v-if="enc.fecha_hora_fin" class="block text-[10px] text-surface-500">a {{ formatHora(enc.fecha_hora_fin) }}</span>
            </div>
          </div>
        </div>
      </div>

      <section v-else class="h-64 flex flex-col items-center justify-center rounded-3xl border border-dashed border-surface-300 bg-white">
        <h3 class="text-lg font-bold text-surface-900 mb-1">Sin encuentros hoy</h3>
        <p class="text-surface-500 font-medium text-sm text-center px-4">
          No tienes partidos de torneo programados para el día de hoy.
        </p>
      </section>

    </div>
  </main>
</template>
