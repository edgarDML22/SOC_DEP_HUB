<script setup>
import { ref, watch, onMounted } from 'vue'
import { IconLayers, IconGrid, IconCalendar } from '@/components/icons'
import { usePlantillasStore } from '@/stores/programacion/plantillasStore'
import { useWizardStore } from '@/stores/programacion/wizardStore'
import PlantillasGestion from './PlantillasGestion.vue'
import WizardProgramacionView from './WizardProgramacionView.vue'
import SesionesPublicadas from './SesionesPublicadas.vue'

const activeTab      = ref('plantillas')
const wizardReady    = ref(false)
const isChangingTab  = ref(false)

const plantillasStore = usePlantillasStore()
const wizardStore     = useWizardStore()
const plantillasRef   = ref(null)
const sesionesRef     = ref(null)

const tabs = [
  { name: 'plantillas', label: 'Gestión de Plantillas', icon: IconLayers },
  { name: 'wizard',     label: 'Gestión de Sesiones',   icon: IconGrid   },
  { name: 'publicadas', label: 'Sesiones Publicadas',   icon: IconCalendar },
]

async function initWizard(plantillaActiva) {
  wizardStore.resetWizard()
  wizardStore.setPlantillaActiva(plantillaActiva.id_plantilla)
  try {
    await Promise.all([
      wizardStore.fetchDependencias(),
      wizardStore.fetchActividadesConfirmadas(plantillaActiva.id_plantilla),
    ])
    await wizardStore.verificarDraftActivo()
    wizardReady.value = true
  } catch { /* errores manejados dentro del store */ }
}

async function selectTab(name) {
  if (activeTab.value === name) return
  isChangingTab.value = true
  activeTab.value = name
  try {
    if (name === 'plantillas') {
      await plantillasStore.fetchPlantillas()
    } else if (name === 'wizard') {
      if (!wizardReady.value) {
        const plantillaActiva = plantillasStore.plantillaActiva
        if (plantillaActiva) {
          await initWizard(plantillaActiva)
        }
      }
    }
  } finally {
    setTimeout(() => {
      isChangingTab.value = false
    }, 250)
  }
}

// Cuando la plantilla activa cambia (el usuario activó otra desde Gestión de Plantillas),
// reinicia el wizard completo independientemente de en qué pestaña esté.
// Los filtros del calendario se resetean SOLO aquí — no al cambiar de pestaña.
watch(
  () => plantillasStore.plantillaActiva?.id_plantilla,
  (nuevoId, anteriorId) => {
    if (!nuevoId || nuevoId === anteriorId) return
    wizardStore.resetFiltros()
    wizardReady.value = false
    if (activeTab.value === 'wizard') {
      initWizard(plantillasStore.plantillaActiva)
    }
  }
)

// Al publicar desde PlantillasGestion: ir a pestaña publicadas y refrescar las sesiones
watch(
  () => plantillasStore.sesionesPublicadas,
  (val) => {
    if (val > 0) {
      activeTab.value = 'publicadas'
      sesionesRef.value?.fetchSesiones()
    }
  }
)

onMounted(() => plantillasStore.fetchPlantillas())
</script>

<template>
  <div class="p-6 font-sans bg-surface-50 min-h-screen animate-fade-in">
    <div class="max-w-[1550px] mx-auto space-y-8">
      
      <!-- CABECERA: TÍTULO + BOTÓN ACCIÓN -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-surface-200/60 pb-6">
        <div>
          <h1 class="text-3xl md:text-4xl font-black text-surface-900 tracking-tight m-0">Programación de Actividades</h1>
          <p class="text-sm font-medium text-surface-500 mt-2 m-0 max-w-xl">
            Administra las plantillas de horarios semanales y publica la programación oficial de clases y actividades del club.
          </p>
        </div>
        <div class="shrink-0">
          <Transition name="fade-down">
            <button
              v-if="activeTab === 'plantillas'"
              @click="plantillasRef?.openCreate()"
              class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-extrabold text-white bg-surface-900 hover:bg-primary-600 active:scale-95 rounded-xl shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 whitespace-nowrap border-none cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
              </svg>
              Nueva Plantilla
            </button>
          </Transition>
        </div>
      </div>

      <!-- Segmented Control (Pills) -->
      <div class="flex p-1.5 bg-surface-100/50 rounded-2xl w-full mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-8">
        <button
          v-for="tab in tabs"
          :key="tab.name"
          @click="selectTab(tab.name)"
          class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all duration-200 ease-out whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none active:scale-[0.99] rounded-xl border-none cursor-pointer"
          :class="activeTab === tab.name
            ? 'bg-surface-900 text-white font-black shadow-md transform scale-[1.02]'
            : 'text-surface-500 font-bold hover:bg-white hover:text-surface-700'"
        >
          <component :is="tab.icon" class="w-5 h-5 shrink-0" />
          {{ tab.label }}
        </button>
      </div>

      <!-- Área de Contenido -->
      <div class="bg-white rounded-[2.2rem] border border-surface-200/80 shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] overflow-hidden min-h-[500px] relative">
        <!-- Local loading overlay -->
        <Transition
            enter-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isChangingTab" class="absolute inset-0 bg-white/70 backdrop-blur-[1px] z-20 flex items-center justify-center">
                <div class="w-8 h-8 border-4 border-slate-200 border-t-slate-900 rounded-full animate-spin"></div>
            </div>
        </Transition>

        <!-- Plantillas tab -->
        <div v-show="activeTab === 'plantillas'" class="p-6 md:p-8">
          <PlantillasGestion ref="plantillasRef" />
        </div>

        <!-- Wizard tab -->
        <div v-show="activeTab === 'wizard'">
          <WizardProgramacionView />
        </div>

        <!-- Sesiones Publicadas tab -->
        <div v-show="activeTab === 'publicadas'">
          <SesionesPublicadas ref="sesionesRef" />
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-down-enter-active,
.fade-down-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-down-enter-from,
.fade-down-leave-to { opacity: 0; transform: translateY(-6px); }
</style>

