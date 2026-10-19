<template>
  <section
    id="inicio"
    class="relative overflow-hidden bg-gradient-to-b from-lp-mist via-lp-paper to-lp-paper"
    aria-label="Presentación del Britania Club"
  >
    <!-- Fondo decorativo: luces suaves + retícula de puntos -->
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
      <div class="absolute -right-32 -top-40 h-[30rem] w-[30rem] rounded-full bg-primary-200/60 blur-3xl" />
      <div class="absolute -left-40 top-1/3 h-96 w-96 rounded-full bg-sky-200/60 blur-3xl" />
      <div
        class="absolute inset-0 bg-[radial-gradient(#c7d7fe_1px,transparent_1px)] [background-size:22px_22px] [mask-image:linear-gradient(to_bottom,black,transparent_75%)]"
      />
    </div>

    <div class="relative mx-auto max-w-7xl px-4 pb-12 pt-5 sm:px-6 sm:pt-8 lg:px-12 lg:pb-20 lg:pt-14">
      <div class="grid items-center gap-8 lg:grid-cols-12 lg:gap-14">
        <!-- Carrusel (primero en móvil) -->
        <div class="lg:order-2 lg:col-span-6">
          <LandingHeroCarousel v-if="sportSlides.length" :slides="sportSlides" />
        </div>

        <!-- Texto -->
        <div class="flex flex-col items-start lg:order-1 lg:col-span-6">
          <span
            class="lp-display inline-flex items-center gap-2 rounded-full bg-lp-paper px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-primary-700 shadow-lp-soft ring-1 ring-primary-100 sm:text-xs"
          >
            <span class="relative flex h-2 w-2" aria-hidden="true">
              <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-75 motion-reduce:animate-none" />
              <span class="relative inline-flex h-2 w-2 rounded-full bg-sky-500" />
            </span>
            Deporte • Familia • Exclusividad
          </span>

          <h1
            class="lp-display mt-5 text-[2.5rem] font-bold leading-[1.02] tracking-[-0.035em] text-lp-ink sm:text-6xl lg:text-[4.25rem]"
          >
            Vive la Experiencia
            <span class="relative inline-block">
              <span class="bg-gradient-to-r from-primary-700 via-primary-500 to-sky-500 bg-clip-text text-transparent">
                Britania
              </span>
              <svg
                class="absolute -bottom-2 left-0 h-3 w-full text-sky-400/70"
                viewBox="0 0 200 12"
                preserveAspectRatio="none"
                fill="none"
                aria-hidden="true"
              >
                <path d="M2 9C50 3 150 1 198 7" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
              </svg>
            </span>
          </h1>

          <p class="lp-body mt-6 max-w-xl text-base leading-relaxed text-lp-body sm:text-lg">
            Disfruta de instalaciones deportivas de primer nivel, espacios familiares
            exclusivos y una comunidad apasionada por el bienestar, la recreación y el
            alto rendimiento.
          </p>

          <LandingButton to="/torneos" size="lg" block class="mt-8 sm:w-auto">
            <IconTrophy class="h-4 w-4" aria-hidden="true" />
            Pre-Registro de Torneos
          </LandingButton>
        </div>
      </div>

      <!-- Cifras del club -->
      <dl class="mt-10 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4 lg:mt-14">
        <div
          v-for="stat in stats"
          :key="stat.label"
          class="flex flex-col-reverse justify-end gap-1 rounded-2xl bg-lp-paper p-4 shadow-lp-soft ring-1 ring-primary-100/80 sm:p-5"
        >
          <dt class="lp-body text-[11px] font-semibold uppercase leading-snug tracking-[0.06em] text-lp-muted sm:text-xs">
            {{ stat.label }}
          </dt>
          <dd class="lp-display flex flex-wrap items-baseline gap-x-1 text-2xl font-bold tracking-tight text-lp-ink sm:text-3xl">
            {{ stat.value }}
            <span class="text-lg text-primary-600 sm:text-xl">{{ stat.unit }}</span>
          </dd>
        </div>
      </dl>
    </div>
  </section>
</template>

<script setup lang="ts">
import { IconTrophy } from '@/components/icons'
import LandingHeroCarousel from '@/components/public/landing/LandingHeroCarousel.vue'
import LandingButton from '@/components/public/landing/ui/LandingButton.vue'
import { sportSlides } from '@/components/public/landing/sportsMedia'

const stats = [
  { value: '20', unit: '+', label: 'Disciplinas Deportivas' },
  { value: '5,000', unit: '+', label: 'Socios Activos' },
  { value: '18', unit: 'Canchas', label: 'Arcilla, Pádel & Rápido' },
  { value: '2', unit: 'Albercas', label: 'Olímpicas Climatizadas' }
]
</script>
