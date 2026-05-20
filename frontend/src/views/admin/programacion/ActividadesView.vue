<script setup>
import { ref, onMounted } from 'vue'
import { IconLayers, IconGrid } from '@/components/icons'
import { usePlantillasStore } from '@/stores/programacion/plantillasStore'
import PlantillasGestion from './PlantillasGestion.vue'
import WizardProgramacionView from './WizardProgramacionView.vue'

const activeTab = ref('plantillas')
const plantillasStore = usePlantillasStore()
const plantillasRef = ref(null)

const tabs = [
  { name: 'plantillas', label: 'Gestión de Plantillas', icon: IconLayers },
  { name: 'wizard',     label: 'Gestión de Sesiones',   icon: IconGrid   },
]

// Fetch único al montar el contenedor — no se repite al cambiar de pestaña
onMounted(() => plantillasStore.fetchPlantillas())
</script>

<template>
  <!-- Wrapper: full height column, no padding so wizard can go edge-to-edge -->
  <div class="flex flex-col h-full font-sans">

    <!-- Top bar: title + tabs + action button — always visible -->
    <div class="px-6 py-4 bg-white border-b border-slate-200 shrink-0">
      <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-black text-slate-800 tracking-tight whitespace-nowrap">Programación de Actividades</h1>

        <div class="flex items-center gap-3 flex-1 justify-center max-w-md">
          <!-- Segmented Control -->
          <div class="flex p-1 bg-slate-100 rounded-xl w-full shadow-inner border border-slate-200">
            <button
              v-for="tab in tabs"
              :key="tab.name"
              @click="activeTab = tab.name"
              class="flex-1 py-2 px-3 text-sm text-center transition-all whitespace-nowrap flex items-center justify-center gap-1.5 focus:outline-none rounded-lg"
              :class="activeTab === tab.name
                ? 'bg-blue-600 text-white font-extrabold shadow-sm'
                : 'text-slate-500 font-bold hover:bg-white/60 hover:text-slate-700'"
            >
              <component :is="tab.icon" class="w-4 h-4 shrink-0" />
              {{ tab.label }}
            </button>
          </div>
        </div>

        <Transition name="fade-down">
          <button
            v-if="activeTab === 'plantillas'"
            @click="plantillasRef?.openCreate()"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-extrabold text-white bg-blue-600 hover:bg-blue-700 active:scale-95 rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 whitespace-nowrap"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Plantilla
          </button>
          <!-- Spacer so layout doesn't shift when button is absent -->
          <div v-else class="w-[152px]" />
        </Transition>
      </div>
    </div>

    <!-- Content area: fills remaining height -->
    <div class="flex-1 overflow-hidden">
      <!-- Plantillas tab: scrollable, padded -->
      <div
        v-show="activeTab === 'plantillas'"
        class="h-full overflow-y-auto p-6"
      >
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 min-h-[400px]">
          <PlantillasGestion ref="plantillasRef" />
        </div>
      </div>

      <!-- Wizard tab: full-bleed, no padding, no scroll (internal panels scroll) -->
      <div v-show="activeTab === 'wizard'" class="h-full overflow-hidden">
        <WizardProgramacionView />
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

.fade-down-enter-active,
.fade-down-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-down-enter-from,
.fade-down-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
