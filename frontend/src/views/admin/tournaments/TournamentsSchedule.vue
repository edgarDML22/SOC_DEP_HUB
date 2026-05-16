<script setup>
import { useRouter, useRoute } from 'vue-router'
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'

const router = useRouter()
const route = useRoute()
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">
      
      <!-- CABECERA -->
      <AdminPageHeader title="Calendario de Torneos" subtitle="Visualiza la programación temporal de todos los eventos.">
        <div class="flex items-center gap-1.5 p-1 bg-surface-100 rounded-xl mr-4">
          <button @click="router.push('/admin/tournaments')" 
                  class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                  :class="route.name === 'tournaments-list' ? 'bg-white shadow-sm text-primary-600' : 'text-surface-500 hover:text-surface-700'">
            Tabla
          </button>
          <button @click="router.push('/admin/tournaments/cards')" 
                  class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                  :class="route.name === 'tournaments-cards' ? 'bg-white shadow-sm text-primary-600' : 'text-surface-500 hover:text-surface-700'">
            Tarjetas
          </button>
          <button @click="router.push('/admin/tournaments/schedule')" 
                  class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                  :class="route.name === 'tournaments-schedule' ? 'bg-white shadow-sm text-primary-600' : 'text-surface-500 hover:text-surface-700'">
            Calendario
          </button>
        </div>
        
        <button @click="router.push('/admin/tournaments/create')" class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-surface-900 text-white
                 text-sm font-bold hover:bg-primary-600 transition-colors shadow-sm">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="16" />
            <line x1="8" y1="12" x2="16" y2="12" />
          </svg>
          Crear Torneo
        </button>
      </AdminPageHeader>

      <div class="p-20 flex flex-col items-center justify-center text-center bg-white rounded-3xl border-2 border-dashed border-surface-200">
        <div class="w-20 h-20 rounded-3xl bg-surface-50 flex items-center justify-center mb-6">
          <svg class="w-10 h-10 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <h3 class="text-xl font-black text-surface-900">Calendario en desarrollo</h3>
        <p class="text-sm text-surface-500 mt-2 max-w-sm">Estamos trabajando en una vista de calendario interactiva para que puedas visualizar mejor la carga de eventos.</p>
      </div>

    </div>
  </main>
</template>