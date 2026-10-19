<template>
  <div
    class="relative aspect-[4/3] w-full select-none overflow-hidden rounded-[1.75rem] bg-primary-100 shadow-lp-lift ring-1 ring-primary-100 outline-none focus-visible:ring-4 focus-visible:ring-primary-200 sm:aspect-[16/10] lg:aspect-[5/6] xl:aspect-square"
    style="touch-action: pan-y"
    role="region"
    aria-roledescription="carrusel"
    aria-label="Disciplinas del club"
    tabindex="0"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
    @focusin="isFocused = true"
    @focusout="isFocused = false"
    @keydown.right.prevent="next"
    @keydown.left.prevent="prev"
    @touchstart.passive="onTouchStart"
    @touchend.passive="onTouchEnd"
  >
    <!-- Fotografías apiladas con fundido + zoom lento -->
    <figure
      v-for="(slide, index) in slides"
      :key="slide.id"
      class="absolute inset-0 m-0 transition-opacity duration-700 ease-out"
      :class="index === currentIndex ? 'z-[1] opacity-100' : 'z-0 opacity-0'"
      :aria-hidden="index !== currentIndex"
    >
      <img
        :src="slide.src"
        :alt="`${slide.title} en Britania Club`"
        class="h-full w-full object-cover object-center"
        :class="{ 'lp-kenburns': (index === currentIndex || index === previousIndex) && !reducedMotion }"
        :style="{ animationDuration: `${interval + 1500}ms` }"
        :loading="index === 0 ? 'eager' : 'lazy'"
        :fetchpriority="index === 0 ? 'high' : undefined"
        decoding="async"
        draggable="false"
      />
    </figure>

    <!-- Degradado azul para leer el texto sobre la foto -->
    <div
      class="pointer-events-none absolute inset-0 z-[2] bg-gradient-to-t from-primary-900/75 via-primary-900/10 to-transparent"
      aria-hidden="true"
    />

    <!-- Barras de progreso tipo "historias" -->
    <div v-if="slides.length > 1" class="absolute inset-x-3 top-2 z-[3] flex gap-1.5 sm:inset-x-5 sm:top-3">
      <button
        v-for="(slide, index) in slides"
        :key="'bar-' + slide.id"
        type="button"
        class="group flex-1 py-2 focus:outline-none"
        :aria-label="`Ver ${slide.title}`"
        :aria-current="index === currentIndex"
        @click="goTo(index)"
      >
        <span class="block h-1 overflow-hidden rounded-full bg-white/35 transition-colors group-hover:bg-white/55">
          <span
            v-if="index === currentIndex"
            :key="progressKey"
            class="block h-full rounded-full bg-lp-paper"
            :class="{ 'lp-progress': canAutoplay }"
            :style="{ animationDuration: `${interval}ms`, animationPlayState: isPaused ? 'paused' : 'running' }"
            @animationend="next"
          />
          <span v-else-if="index < currentIndex" class="block h-full rounded-full bg-lp-paper" />
        </span>
      </button>
    </div>

    <!-- Pie de foto + controles -->
    <div class="absolute inset-x-0 bottom-0 z-[3] flex items-end justify-between gap-3 p-4 sm:p-6">
      <div class="min-w-0" aria-live="polite">
        <span class="lp-body text-[11px] font-bold uppercase tracking-[0.18em] text-sky-200">
          Disciplina {{ String(currentIndex + 1).padStart(2, '0') }} / {{ String(slides.length).padStart(2, '0') }}
        </span>
        <p class="lp-display mt-1 text-2xl font-bold leading-tight tracking-tight text-white sm:text-3xl">
          {{ current?.title }}
        </p>
        <p class="lp-body mt-0.5 line-clamp-1 text-sm text-white/85 sm:text-[15px]">
          {{ current?.tagline }}
        </p>
      </div>

      <div v-if="slides.length > 1" class="flex shrink-0 gap-2">
        <button
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-full bg-lp-paper/90 text-primary-700 shadow-lp-soft transition hover:bg-lp-paper active:scale-95 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/50 sm:h-11 sm:w-11"
          aria-label="Fotografía anterior"
          @click="prev"
        >
          <IconChevronLeft class="h-5 w-5" aria-hidden="true" />
        </button>
        <button
          type="button"
          class="flex h-10 w-10 items-center justify-center rounded-full bg-lp-paper/90 text-primary-700 shadow-lp-soft transition hover:bg-lp-paper active:scale-95 focus:outline-none focus-visible:ring-4 focus-visible:ring-white/50 sm:h-11 sm:w-11"
          aria-label="Fotografía siguiente"
          @click="next"
        >
          <IconChevronRight class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { IconChevronLeft, IconChevronRight } from '@/components/icons'
import type { SportSlide } from './sportsMedia'

const props = withDefaults(defineProps<{ slides: SportSlide[]; interval?: number }>(), {
  interval: 5500
})

const currentIndex = ref(0)
// La foto saliente conserva su zoom mientras se desvanece (evita un salto visual)
const previousIndex = ref(-1)
// Reinicia la barra de progreso aunque se vuelva a elegir la misma foto
const progressKey = ref(0)
const isHovered = ref(false)
const isFocused = ref(false)
const reducedMotion = ref(false)

const current = computed(() => props.slides[currentIndex.value])
const isPaused = computed(() => isHovered.value || isFocused.value)
// El avance automático lo dispara el fin de la animación de la barra,
// así pausar (hover / foco) congela barra y carrusel a la vez.
const canAutoplay = computed(() => props.slides.length > 1 && !reducedMotion.value)

const goTo = (index: number) => {
  const total = props.slides.length
  if (total === 0) return
  previousIndex.value = currentIndex.value
  currentIndex.value = (index + total) % total
  progressKey.value++
}

const next = () => goTo(currentIndex.value + 1)
const prev = () => goTo(currentIndex.value - 1)

let touchStartX = 0
let touchStartY = 0

const onTouchStart = (event: TouchEvent) => {
  touchStartX = event.touches[0].clientX
  touchStartY = event.touches[0].clientY
}

const onTouchEnd = (event: TouchEvent) => {
  const deltaX = event.changedTouches[0].clientX - touchStartX
  const deltaY = event.changedTouches[0].clientY - touchStartY
  if (Math.abs(deltaX) < 40 || Math.abs(deltaX) < Math.abs(deltaY)) return
  deltaX < 0 ? next() : prev()
}

onMounted(() => {
  reducedMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches
})
</script>

<style scoped>
.lp-progress {
  width: 0;
  animation-name: lp-progress;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}

@keyframes lp-progress {
  to {
    width: 100%;
  }
}

.lp-kenburns {
  animation-name: lp-kenburns;
  animation-timing-function: ease-out;
  animation-fill-mode: forwards;
}

@keyframes lp-kenburns {
  from {
    transform: scale(1);
  }
  to {
    transform: scale(1.08);
  }
}
</style>
