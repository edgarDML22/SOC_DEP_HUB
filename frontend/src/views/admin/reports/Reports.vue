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
      <h1 class="text-3xl font-bold text-slate-800 tracking-tight mb-8">Reportes y Analíticas</h1>

      <!-- Segmented Control (Pills) -->
      <div class="flex p-1.5 bg-slate-100 rounded-2xl w-full mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-slate-200 mb-8">
        <button
          v-for="tab in tabs"
          :key="tab.name"
          @click="router.push({ name: tab.name })"
          class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
          :class="route.name === tab.name
            ? 'bg-blue-600 text-white font-extrabold rounded-xl shadow-md transform scale-[1.02]'
            : 'text-slate-500 font-bold hover:bg-white/60 hover:text-slate-700 rounded-xl'"
        >
          <component :is="tab.icon" class="w-5 h-5 shrink-0" />
          {{ tab.label }}
        </button>
      </div>

      <!-- Área de Contenido -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 min-h-[400px]">
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