<template>
  <section
    id="hero"
    class="relative w-full overflow-hidden bg-gradient-to-b from-[#0B1326] via-[#0E172E] to-[#0B1326]"
    role="region"
    aria-label="Presentación del Britania Club"
    aria-roledescription="carousel"
    aria-live="polite"
  >
    <div
      ref="slidesContainer"
      class="relative w-full min-h-[720px] lg:min-h-[880px] select-none"
      style="touch-action: pan-y"
      tabindex="0"
      @mouseenter="onMouseEnter"
      @mouseleave="onMouseLeave"
      @focusin="onFocusIn"
      @focusout="onFocusOut"
      @keydown="onKeyDown"
    >
      <!-- ============ Background images (rotating) ============ -->
      <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
        <div
          v-for="(image, index) in heroImages"
          :key="image.id"
          class="absolute inset-0 transition-opacity duration-[1200ms] ease-out"
          :class="index === currentIndex ? 'opacity-100 z-[1]' : 'opacity-0 z-0'"
        >
          <!-- Full-opacity, no grayscale/blend: keep the photo crisp -->
          <img
            v-if="!imageError[image.id]"
            :src="image.src"
            :alt="image.alt"
            class="absolute inset-0 h-full w-full object-cover object-center"
            :fetchpriority="index === 0 ? 'high' : undefined"
            :loading="index === 0 ? 'eager' : 'lazy'"
            decoding="async"
            width="1920"
            height="1080"
            draggable="false"
            @error="onImageError(image.id)"
          />
          <!-- Elegant fallback if a photo fails to load -->
          <div
            v-else
            class="absolute inset-0 bg-gradient-to-br from-[#0B1326] via-[#131f38] to-[#0B1326]"
          />
        </div>

        <!-- Cinematic vertical gradient (legibility + fuse with next section) -->
        <div
          class="absolute inset-0 z-[2] bg-[linear-gradient(180deg,rgba(11,19,38,0.50)_0%,rgba(11,19,38,0.40)_40%,rgba(11,19,38,0.85)_90%,#0B1326_100%)]"
        />

        <!-- Luminous glow elements -->
        <div
          class="pointer-events-none absolute left-1/2 top-[-128px] z-[3] h-[450px] w-[800px] -translate-x-1/2 rounded-full bg-[linear-gradient(90deg,rgba(37,99,235,0.25)_0%,rgba(14,165,233,0.20)_50%,rgba(37,99,235,0.25)_100%)] blur-[70px]"
        />
        <div
          class="pointer-events-none absolute right-[8%] top-[48%] z-[3] h-[420px] w-[420px] rounded-full bg-[rgba(59,130,246,0.15)] blur-[60px]"
        />
        <div
          class="pointer-events-none absolute left-[4%] top-[33%] z-[3] h-[350px] w-[350px] rounded-full bg-[rgba(6,182,212,0.10)] blur-[55px]"
        />
      </div>

      <!-- ============ Centered content ============ -->
      <div
        class="relative z-10 mx-auto flex min-h-[720px] w-full max-w-7xl flex-col items-center justify-center px-6 py-24 text-center md:px-12 lg:min-h-[880px]"
      >
        <!-- Eyebrow pill -->
        <div class="pb-6">
          <span
            class="inline-flex items-center gap-2 rounded-full border border-blue-400/40 bg-[#172554]/80 px-4 py-1.5 shadow-[0_0_20px_rgba(59,130,246,0.30)] backdrop-blur-md"
          >
            <span class="h-2 w-2 animate-pulse rounded-full bg-[#60A5FA] shadow-[0_0_8px_#60A5FA] motion-reduce:animate-none" />
            <span class="lp-display text-xs font-semibold uppercase leading-tight tracking-[0.18em] text-[#BFDBFE]">
              Deporte • Familia • Exclusividad
            </span>
          </span>
        </div>

        <!-- Headline -->
        <h1
          class="lp-display max-w-4xl break-words text-4xl font-bold leading-[1.05] tracking-[-0.03em] text-white [text-shadow:0_4px_24px_rgba(0,0,0,0.90),0_2px_8px_rgba(0,0,0,0.80)] sm:text-6xl lg:text-[72px]"
        >
          <span class="block">Vive la Experiencia</span>
          <span
            class="block bg-gradient-to-r from-[#EF4444] via-white to-[#60A5FA] bg-clip-text text-transparent drop-shadow-[0_4px_30px_rgba(59,130,246,0.50)]"
          >
            Britania
          </span>
        </h1>

        <!-- Subheadline -->
        <p
          class="lp-body mt-4 max-w-2xl text-base leading-relaxed text-slate-300 [text-shadow:0_2px_10px_rgba(0,0,0,0.85),0_4px_20px_rgba(11,19,38,0.95)] sm:text-lg"
        >
          Disfruta de instalaciones deportivas de primer nivel, espacios familiares
          exclusivos y una comunidad apasionada por el bienestar, la recreación y el
          alto rendimiento.
        </p>

        <!-- Twin CTA group -->
        <div class="mt-10 flex w-full flex-col items-center justify-center gap-4 sm:flex-row">
          <RouterLink
            to="/torneos"
            class="lp-body inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#E61C24] px-8 py-4 text-[15px] font-bold uppercase tracking-[0.05em] text-white shadow-[0_8px_30px_rgba(230,28,36,0.50)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_12px_40px_rgba(230,28,36,0.65)] active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E61C24]/60 sm:w-auto"
          >
            <IconGrid class="h-3.5 w-3.5" aria-hidden="true" />
            Pre-Registro de Torneos
          </RouterLink>
        
        </div>

        <!-- Glassmorphic stat ribbon -->
        <div
          class="mt-16 grid w-full max-w-4xl grid-cols-2 gap-y-6 rounded-2xl border border-blue-400/25 bg-gradient-to-b from-[#131f38]/95 to-[#0d1527]/95 p-2 shadow-[0_12px_40px_rgba(11,19,38,0.70),0_0_25px_rgba(59,130,246,0.15)] backdrop-blur-xl md:grid-cols-4 md:gap-y-0"
        >
          <div
            v-for="(stat, index) in stats"
            :key="stat.label"
            class="min-w-0 px-5 py-3 text-left"
            :class="[
              index % 2 === 0 ? 'border-r border-blue-500/20' : '',
              index < 3 ? 'md:border-r md:border-blue-500/20' : 'md:border-r-0'
            ]"
          >
            <div class="lp-display flex flex-wrap items-baseline gap-x-1 text-2xl font-bold leading-tight tracking-[-0.02em] text-white sm:text-[32px]">
              <span>{{ stat.value }}</span>
              <span :class="stat.unitTone === 'red' ? 'text-[#E61C24]' : 'text-[#60A5FA]'">
                {{ stat.unit }}
              </span>
            </div>
            <div class="lp-body mt-1 text-xs font-semibold uppercase leading-snug tracking-[0.05em] text-slate-300">
              {{ stat.label }}
            </div>
          </div>
        </div>
      </div>

      <!-- ============ Navigation arrows (desktop) ============ -->
      <button
        v-if="heroImages.length > 1"
        type="button"
        class="absolute left-4 top-1/2 z-20 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/25 bg-black/40 text-white backdrop-blur-md transition hover:border-blue-400 hover:bg-black/60 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 md:flex"
        aria-label="Fotografía anterior"
        @click="prevSlide"
      >
        <IconChevronLeft class="h-5 w-5" aria-hidden="true" />
      </button>
      <button
        v-if="heroImages.length > 1"
        type="button"
        class="absolute right-4 top-1/2 z-20 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-white/25 bg-black/40 text-white backdrop-blur-md transition hover:border-blue-400 hover:bg-black/60 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 md:flex"
        aria-label="Fotografía siguiente"
        @click="nextSlide"
      >
        <IconChevronRight class="h-5 w-5" aria-hidden="true" />
      </button>

      <!-- ============ Dots ============ -->
      <div
        v-if="heroImages.length > 1"
        class="absolute bottom-6 left-1/2 z-20 flex -translate-x-1/2 items-center gap-2"
        role="tablist"
        aria-label="Seleccionar fotografía"
      >
        <button
          v-for="(image, index) in heroImages"
          :key="'dot-' + image.id"
          type="button"
          role="tab"
          :aria-selected="index === currentIndex"
          :aria-label="`Ir a la fotografía ${index + 1}`"
          class="h-2.5 rounded-full transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
          :class="index === currentIndex ? 'w-8 bg-white' : 'w-2.5 bg-white/50 hover:bg-white/80'"
          @click="goToSlide(index)"
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, reactive } from 'vue'
import { IconGrid, IconUser, IconChevronLeft, IconChevronRight } from '@/components/icons'

interface HeroImage {
  id: string
  src: string
  alt: string
}

interface StatItem {
  value: string
  unit: string
  unitTone: 'blue' | 'red'
  label: string
}

const props = defineProps<{
  autoplay?: boolean
  interval?: number
}>()

const autoplay = computed(() => props.autoplay ?? true)
const intervalMs = computed(() => props.interval ?? 6000)

/**
 * FOTOGRAFÍAS DEL HERO
 * Coloca tus imágenes en: frontend/src/assets/hero/
 * Se cargan automáticamente (orden alfabético por nombre de archivo).
 * Si la carpeta está vacía, se usan estas URLs de respaldo.
 */
const localModules = import.meta.glob('../../assets/hero/*.{jpg,jpeg,png,webp,avif}', {
  eager: true,
  import: 'default'
}) as Record<string, string>

const fallbackImages: HeroImage[] = [
  {
    id: 'tenis',
    src: 'https://images.unsplash.com/photo-1510925758641-869d353cecc7?w=1920&q=80&auto=format&fit=crop',
    alt: 'Cancha de tenis del Britania Club Deportivo'
  },
  {
    id: 'padel',
    src: 'https://images.unsplash.com/photo-1612534847738-b3af9bc31f0c?w=1920&q=80&auto=format&fit=crop',
    alt: 'Cancha de pádel del Britania Club Deportivo'
  },
  {
    id: 'natacion',
    src: 'https://images.unsplash.com/photo-1530549387789-4c1017266635?w=1920&q=80&auto=format&fit=crop',
    alt: 'Alberca climatizada del Britania Club Deportivo'
  },
  {
    id: 'gimnasio',
    src: 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=1920&q=80&auto=format&fit=crop',
    alt: 'Gimnasio del Britania Club Deportivo'
  }
]

const heroImages = computed<HeroImage[]>(() => {
  const entries = Object.entries(localModules).sort(([a], [b]) => a.localeCompare(b))
  if (entries.length === 0) return fallbackImages
  return entries.map(([path, src]) => {
    const file = path.split('/').pop() ?? 'hero'
    const id = file.replace(/\.[^.]+$/, '')
    return {
      id,
      src,
      alt: `Britania Club - ${id.replace(/[-_]+/g, ' ')}`
    }
  })
})

const stats: StatItem[] = [
  { value: '20', unit: '+', unitTone: 'blue', label: 'Disciplinas Deportivas' },
  { value: '5,000', unit: '+', unitTone: 'red', label: 'Socios Activos' },
  { value: '18', unit: 'Canchas', unitTone: 'blue', label: 'Arcilla, Pádel & Rápido' },
  { value: '2', unit: 'Albercas', unitTone: 'red', label: 'Olímpicas Climatizadas' }
]

const currentIndex = ref(0)
const imageError = reactive<Record<string, boolean>>({})
const slidesContainer = ref<HTMLElement | null>(null)

let autoplayTimer: number | null = null
let isInteracting = false
let touchStartX = 0
let touchStartY = 0
let touchMoved = false
let pointerDown = false
let pointerStartX = 0
let prefersReducedMotion = false

const onImageError = (id: string) => {
  imageError[id] = true
}

const nextSlide = () => {
  const total = heroImages.value.length
  if (total <= 1) return
  currentIndex.value = (currentIndex.value + 1) % total
  resetAutoplay()
}

const prevSlide = () => {
  const total = heroImages.value.length
  if (total <= 1) return
  currentIndex.value = (currentIndex.value - 1 + total) % total
  resetAutoplay()
}

const goToSlide = (index: number) => {
  currentIndex.value = index
  resetAutoplay()
}

const startAutoplay = () => {
  if (!autoplay.value || prefersReducedMotion) return
  stopAutoplay()
  autoplayTimer = window.setInterval(() => {
    if (!isInteracting && !document.hidden && heroImages.value.length > 1) {
      currentIndex.value = (currentIndex.value + 1) % heroImages.value.length
    }
  }, intervalMs.value)
}

const stopAutoplay = () => {
  if (autoplayTimer !== null) {
    window.clearInterval(autoplayTimer)
    autoplayTimer = null
  }
}

const resetAutoplay = () => {
  if (autoplay.value && !prefersReducedMotion) {
    startAutoplay()
  }
}

const onMouseEnter = () => {
  isInteracting = true
}

const onMouseLeave = () => {
  isInteracting = false
  resetAutoplay()
}

const onFocusIn = () => {
  isInteracting = true
}

const onFocusOut = () => {
  isInteracting = false
  resetAutoplay()
}

const onVisibilityChange = () => {
  if (document.hidden) {
    stopAutoplay()
  } else {
    resetAutoplay()
  }
}

const onKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'ArrowRight') {
    event.preventDefault()
    nextSlide()
  } else if (event.key === 'ArrowLeft') {
    event.preventDefault()
    prevSlide()
  }
}

const onTouchStart = (event: TouchEvent) => {
  if (event.touches.length !== 1) return
  const touch = event.touches[0]
  touchStartX = touch.clientX
  touchStartY = touch.clientY
  touchMoved = false
  isInteracting = true
  stopAutoplay()
}

const onTouchMove = (event: TouchEvent) => {
  if (event.touches.length !== 1) return
  const touch = event.touches[0]
  const deltaX = touch.clientX - touchStartX
  const deltaY = touch.clientY - touchStartY
  if (!touchMoved && Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
    touchMoved = true
    event.preventDefault()
  }
}

const onTouchEnd = (event: TouchEvent) => {
  if (!touchMoved) {
    isInteracting = false
    resetAutoplay()
    return
  }
  const touch = event.changedTouches[0]
  const deltaX = touch.clientX - touchStartX
  const threshold = 50
  if (deltaX > threshold) {
    prevSlide()
  } else if (deltaX < -threshold) {
    nextSlide()
  }
  touchMoved = false
  isInteracting = false
  resetAutoplay()
}

const onPointerDown = (event: PointerEvent) => {
  if (event.pointerType !== 'mouse') return
  pointerDown = true
  pointerStartX = event.clientX
  isInteracting = true
  stopAutoplay()
  ;(event.target as HTMLElement).setPointerCapture?.(event.pointerId)
  event.preventDefault()
}

const onPointerMove = (event: PointerEvent) => {
  if (!pointerDown || event.pointerType !== 'mouse') return
  event.preventDefault()
}

const onPointerUp = (event: PointerEvent) => {
  if (!pointerDown || event.pointerType !== 'mouse') return
  pointerDown = false
  const deltaX = event.clientX - pointerStartX
  const threshold = 50
  if (deltaX > threshold) {
    prevSlide()
  } else if (deltaX < -threshold) {
    nextSlide()
  }
  isInteracting = false
  resetAutoplay()
  ;(event.target as HTMLElement).releasePointerCapture?.(event.pointerId)
}

onMounted(() => {
  prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  startAutoplay()
  document.addEventListener('visibilitychange', onVisibilityChange)
  document.addEventListener('keydown', onKeyDown)
  const container = slidesContainer.value
  if (container) {
    container.addEventListener('touchstart', onTouchStart, { passive: false })
    container.addEventListener('touchmove', onTouchMove, { passive: false })
    container.addEventListener('touchend', onTouchEnd, { passive: true })
    container.addEventListener('pointerdown', onPointerDown, { passive: false })
    container.addEventListener('pointermove', onPointerMove, { passive: false })
    container.addEventListener('pointerup', onPointerUp, { passive: true })
  }
})

onBeforeUnmount(() => {
  stopAutoplay()
  document.removeEventListener('visibilitychange', onVisibilityChange)
  document.removeEventListener('keydown', onKeyDown)
  const container = slidesContainer.value
  if (container) {
    container.removeEventListener('touchstart', onTouchStart)
    container.removeEventListener('touchmove', onTouchMove)
    container.removeEventListener('touchend', onTouchEnd)
    container.removeEventListener('pointerdown', onPointerDown)
    container.removeEventListener('pointermove', onPointerMove)
    container.removeEventListener('pointerup', onPointerUp)
  }
})
</script>
