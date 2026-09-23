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
  <div class="flex h-[100dvh] overflow-hidden bg-surface-50 relative">
    
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-300"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="isRouteLoading" class="fixed top-0 left-0 right-0 h-[3px] bg-slate-900/10 z-[9999] overflow-hidden pointer-events-none">
        <div class="h-full bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-500 animate-progress w-full"></div>
      </div>
    </Transition>

    <GerenteSideBar />

    <main class="flex-1 flex flex-col min-h-0 relative">
      <GerenteTopBar class="shrink-0" />
      
      <div class="flex-1 overflow-y-auto min-h-0 relative bg-surface-50">
        
        <router-view />
        
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Animación premium de alta gama para la barra de progreso superior */
@keyframes progress {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}
.animate-progress {
  animation: progress 1.5s infinite linear;
}
</style>