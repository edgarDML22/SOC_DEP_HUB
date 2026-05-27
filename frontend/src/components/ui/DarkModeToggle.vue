<script setup>
import { useDarkMode } from '@/composables/useDarkMode'

const { isDark, toggle } = useDarkMode()
</script>

<template>
  <button
    @click="toggle"
    class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 border border-surface-200/40 hover:bg-surface-100 hover:text-surface-900 active:scale-95 transition-all shadow-[0_2px_10px_rgba(0,0,0,0.03)] cursor-pointer overflow-hidden group"
    title="Alternar tema claro/oscuro"
  >
    <!-- Contenedor con efecto de rotación suave -->
    <div class="relative w-5 h-5 transition-transform duration-500 ease-out transform group-hover:rotate-45 flex items-center justify-center">
      
      <!-- ICONO LUNA (Modo Claro) -->
      <Transition
        enter-active-class="transition duration-300 ease-out absolute"
        enter-from-class="opacity-0 scale-50 rotate-90"
        enter-to-class="opacity-100 scale-100 rotate-0"
        leave-active-class="transition duration-200 ease-in absolute"
        leave-from-class="opacity-100 scale-100 rotate-0"
        leave-to-class="opacity-0 scale-50 -rotate-90"
      >
        <svg
          v-if="!isDark"
          xmlns="http://www.w3.org/2000/svg"
          class="w-5 h-5"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2.2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
        </svg>
      </Transition>

      <!-- ICONO SOL (Modo Oscuro) -->
      <Transition
        enter-active-class="transition duration-300 ease-out absolute"
        enter-from-class="opacity-0 scale-50 -rotate-90"
        enter-to-class="opacity-100 scale-100 rotate-0"
        leave-active-class="transition duration-200 ease-in absolute"
        leave-from-class="opacity-100 scale-100 rotate-0"
        leave-to-class="opacity-0 scale-50 rotate-90"
      >
        <svg
          v-if="isDark"
          xmlns="http://www.w3.org/2000/svg"
          class="w-5 h-5 text-amber-400"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2.2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <circle cx="12" cy="12" r="4"/>
          <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
        </svg>
      </Transition>

    </div>
  </button>
</template>

<style scoped>
/* Las transiciones suaves ya se manejan mediante clases de Vue transition y transformaciones de Tailwind */
</style>
