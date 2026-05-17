<script setup>
import { useRoute, useRouter } from 'vue-router'
import {
  IconHistory,
  IconLayers,
  IconTrophy,
  IconTarget
} from '@/components/icons'

const route = useRoute()
const router = useRouter()

const tabs = [
  { name: 'auditoria', label: 'Auditoría', icon: IconHistory },
  { name: 'ocupation-spaces', label: 'Ocupación Espacios', icon: IconLayers },
  { name: 'tournaments-analytics', label: 'Analíticas Torneos', icon: IconTrophy },
  { name: 'academic-performance', label: 'Rendimiento Acad.', icon: IconTarget },
]
</script>

<template>
  <div class="p-6 font-sans">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-black text-surface-900 tracking-tight mb-8">Reportes y Analíticas</h1>

      <!-- Segmented Control (Pills) -->
      <div class="flex p-1.5 bg-surface-100/50 rounded-2xl w-full mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-8">
        <button
          v-for="tab in tabs"
          :key="tab.name"
          @click="router.push({ name: tab.name })"
          class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
          :class="route.name === tab.name
            ? 'bg-surface-900 text-white font-black rounded-xl shadow-md transform scale-[1.02]'
            : 'text-surface-500 font-bold hover:bg-white hover:text-surface-700 rounded-xl'"
        >
          <component :is="tab.icon" class="w-5 h-5 shrink-0" />
          {{ tab.label }}
        </button>
      </div>

      <!-- Área de Contenido -->
      <div class="bg-white rounded-[2.5rem] border border-surface-200 shadow-sm p-6 lg:p-10 min-h-[400px]">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>