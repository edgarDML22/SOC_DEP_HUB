<template>
  <RouterLink
    to="/"
    class="group inline-flex items-center gap-2.5 rounded-xl focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-200"
    aria-label="Ir a inicio - Britania Club Deportivo"
  >
    <img
      :src="logoSrc"
      alt=""
      class="h-9 w-9 shrink-0 rounded-xl shadow-lp-soft transition-transform duration-300 group-hover:-rotate-6"
      width="36"
      height="36"
      decoding="async"
    />
    <span class="lp-display text-lg font-bold leading-none tracking-tight text-lp-ink md:text-xl">
      Britania <span class="text-primary-600">Club</span>
    </span>
  </RouterLink>
</template>

<script setup lang="ts">
import logoSrc from '@/assets/LogoSocDep.png'
</script>
