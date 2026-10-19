<template>
  <section
    class="relative overflow-hidden bg-gradient-to-r from-primary-700 via-primary-600 to-sky-500 py-5 sm:py-6"
    aria-label="Disciplinas disponibles"
  >
    <!-- Desvanecido en los bordes -->
    <div
      class="pointer-events-none absolute inset-y-0 left-0 z-10 w-10 bg-gradient-to-r from-primary-700 to-transparent sm:w-24"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute inset-y-0 right-0 z-10 w-10 bg-gradient-to-l from-sky-500 to-transparent sm:w-24"
      aria-hidden="true"
    />

    <div class="lp-marquee-track flex w-max" aria-hidden="true">
      <!-- Dos mitades idénticas: la animación recorre -50% y el ciclo no se nota -->
      <ul v-for="half in 2" :key="half" class="flex shrink-0 items-center gap-3 pr-3 sm:gap-4 sm:pr-4">
        <li
          v-for="(sport, index) in loopItems"
          :key="`${half}-${index}`"
          class="flex shrink-0 items-center gap-3 rounded-full bg-white/12 py-1.5 pl-1.5 pr-5 ring-1 ring-white/25 backdrop-blur-sm"
        >
          <img
            :src="sport.src"
            alt=""
            class="h-10 w-10 rounded-full object-cover ring-2 ring-white/70 sm:h-11 sm:w-11"
            loading="lazy"
            decoding="async"
            draggable="false"
          />
          <span class="lp-display text-base font-semibold tracking-tight text-white sm:text-lg">
            {{ sport.title }}
          </span>
        </li>
      </ul>
    </div>

    <!-- Lista accesible (la marquesina es decorativa) -->
    <p class="sr-only">Disciplinas: {{ sports.map((sport) => sport.title).join(', ') }}</p>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { SportSlide } from './sportsMedia'

const props = defineProps<{ sports: SportSlide[] }>()

// Se duplica la lista para que cada mitad sea más ancha que cualquier pantalla
const loopItems = computed(() => [...props.sports, ...props.sports])
</script>

<style scoped>
.lp-marquee-track {
  animation: lp-marquee 40s linear infinite;
}

.lp-marquee-track:hover {
  animation-play-state: paused;
}

@keyframes lp-marquee {
  to {
    transform: translate3d(-50%, 0, 0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .lp-marquee-track {
    animation: none;
  }
}
</style>
