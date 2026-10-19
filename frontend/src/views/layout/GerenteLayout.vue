<script setup>
import GerenteSideBar from '@/components/gerente/GerenteSideBar.vue'
import GerenteTopBar from '@/components/gerente/GerenteTopBar.vue'
import { useAdminStore } from '@/stores/profiles/adminStore'
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { isRouteLoading } from '@/router'
import { useHideOnScroll } from '@/composables/useHideOnScroll'

const profileStore = useAdminStore()
const route = useRoute()
const isMobileSidebarOpen = ref(false)

// Topbar que se esconde al bajar y reaparece al subir (solo móvil/tablet, < lg)
const scrollContainer = ref(null)
const { hidden: isTopbarHidden, reset: showTopbar } = useHideOnScroll(scrollContainer)
watch(() => route.fullPath, showTopbar)

const toggleMobileSidebar = () => {
  isMobileSidebarOpen.value = !isMobileSidebarOpen.value
}

const closeMobileSidebar = () => {
  isMobileSidebarOpen.value = false
}

// Bloquea el scroll cuando el drawer móvil está abierto
watch(isMobileSidebarOpen, (val) => {
  document.body.style.overflow = val ? 'hidden' : ''
})

// Cierra el drawer móvil y restaura el scroll al pasar a resolución de escritorio
const handleResize = () => {
  if (window.innerWidth >= 1024 && isMobileSidebarOpen.value) {
    isMobileSidebarOpen.value = false
    document.body.style.overflow = ''
  }
}

onMounted(() => {
  window.scrollTo(0, 0)
  profileStore.fetchProfile()
  window.addEventListener('resize', handleResize)
})

// Limpieza de listeners y restauración del scroll al desmontar
onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="fixed inset-0 flex overflow-hidden bg-surface-50">
    
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

    <GerenteSideBar
      v-model:isMobileOpen="isMobileSidebarOpen"
      @close="closeMobileSidebar"
    />

    <!-- min-w-0: sin él, contenido ancho (gráficas, tablas) estira el main más allá del viewport en móvil -->
    <main class="flex-1 flex flex-col min-h-0 min-w-0 relative h-full">
      <!-- Al ocultarse se desliza hacia arriba con margen negativo (sigue en el flujo,
           así no tapa los encabezados sticky de tablas y calendarios).
           La animación va en este div: la regla global de main.css sobre <header>
           pisaría el transition si se pusiera directo en la topbar. -->
      <div
        class="shrink-0 transition-[margin] duration-300 ease-out lg:mt-0"
        :class="isTopbarHidden ? '-mt-16 sm:-mt-[70px]' : 'mt-0'"
      >
        <GerenteTopBar @toggle-sidebar="toggleMobileSidebar" />
      </div>
      
      <div ref="scrollContainer" class="flex-1 overflow-y-auto min-h-0 relative bg-surface-50">
        
        <router-view />
        
      </div>
    </main>
  </div>
</template>

<style scoped>
@keyframes progress {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}
.animate-progress {
  animation: progress 1.5s infinite linear;
}
</style>