<script setup>
import GerenteSideBar from '@/components/gerente/GerenteSideBar.vue'
import GerenteTopBar from '@/components/gerente/GerenteTopBar.vue'
import { useAdminStore } from '@/stores/profiles/adminStore'
import { onMounted } from 'vue'
import { isRouteLoading } from '@/router'

const profileStore = useAdminStore()
onMounted(() => {
  profileStore.fetchProfile()
})
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-surface-50 relative">
    <!-- Barra de progreso linear de alta gama -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-300 delay-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="isRouteLoading" class="fixed top-0 left-0 right-0 h-[3px] bg-slate-900/10 z-[9999] overflow-hidden pointer-events-none">
        <div class="h-full bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-500 animate-progress w-full"></div>
      </div>
    </Transition>

    <GerenteSideBar />

    <main class="flex-1 flex flex-col min-h-0">
      <GerenteTopBar class="shrink-0" />
      <!-- overflow-y-auto here lets normal pages scroll; views that want full-height
           declare h-full on their root element and control their own overflow -->
      <div class="flex-1 overflow-y-auto min-h-0 relative">
        <!-- Local page transition overlay (anti-freezing) -->
        <Transition
          enter-active-class="transition-opacity duration-150"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition-opacity duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div v-if="isRouteLoading" class="absolute inset-0 bg-white/70 backdrop-blur-[1px] z-20 flex items-center justify-center">
            <div class="w-10 h-10 border-4 border-slate-200 border-t-slate-900 rounded-full animate-spin"></div>
          </div>
        </Transition>

        <router-view />
      </div>
    </main>
  </div>
</template>

<style scoped>
@keyframes progress {
  0% {
    transform: translateX(-100%);
  }
  50% {
    transform: translateX(-20%);
  }
  100% {
    transform: translateX(100%);
  }
}
.animate-progress {
  animation: progress 1.2s infinite linear;
}
</style>