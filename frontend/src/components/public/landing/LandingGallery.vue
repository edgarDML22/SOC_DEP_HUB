<template>
  <section
    id="galeria"
    class="relative w-full border-b border-blue-500/20 bg-gradient-to-b from-[#0F172A] via-[#131F38] to-[#0B1326] py-16 md:py-24 scroll-mt-20"
    role="region"
    aria-label="Galería del club"
  >
    <div class="mx-auto flex max-w-7xl flex-col items-center gap-12 px-6 md:gap-16 md:px-12">
      <!-- Header -->
      <div class="flex w-full max-w-3xl flex-col items-center gap-2 text-center">
        <span class="lp-display text-xs font-semibold uppercase tracking-[0.16em] text-[#60A5FA]">
          Galería
        </span>
        <h2
          class="lp-display text-3xl font-bold leading-[1.15] tracking-[-0.025em] text-white sm:text-4xl lg:text-[48px]"
        >
          Experiencias en el Club
        </h2>
        <p class="lp-body mt-2 text-base leading-relaxed text-[#CBD5E1]">
          La energía, la pasión y los momentos que definen la vida diaria de nuestros
          socios en Britania Club.
        </p>
      </div>

      <!-- Grid -->
      <div class="grid w-full grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <figure
          v-for="(item, index) in galleryItems"
          :key="item.id"
          class="group relative h-72 overflow-hidden rounded-2xl border border-blue-400/20 bg-[#131F38]"
        >
          <img
            v-if="!imageErrorMap[item.id]"
            :src="item.image"
            :alt="item.alt"
            class="h-full w-full object-cover object-center transition-transform duration-700 group-hover:scale-105"
            loading="lazy"
            decoding="async"
            width="408"
            height="288"
            @error="imageErrorMap[item.id] = true"
          />
          <div
            v-else
            class="flex h-full w-full items-center justify-center bg-gradient-to-br from-[#1A2849] to-[#0F172A]"
            aria-hidden="true"
          >
            <span class="lp-display text-sm uppercase tracking-[0.2em] text-blue-300/50">
              Britania Club
            </span>
          </div>

          <div
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(0deg,rgba(11,19,38,0.85)_0%,rgba(11,19,38,0)_55%)] opacity-0 transition-opacity duration-300 group-hover:opacity-100"
            aria-hidden="true"
          />
          <figcaption
            class="lp-body absolute bottom-0 left-0 right-0 translate-y-2 px-5 pb-4 text-sm font-medium text-white opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100"
          >
            {{ item.caption }}
          </figcaption>
        </figure>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue'

/**
 * FOTOGRAFÍAS — Galería
 * Coloca tus imágenes en: frontend/src/assets/galeria/
 * Se asignan a los mosaicos por orden alfabético. Si la carpeta está vacía,
 * se usan las imágenes de respaldo (Unsplash).
 */
const localModules = import.meta.glob('/src/assets/galeria/*.{jpg,jpeg,png,webp,avif}', {
  eager: true,
  import: 'default'
}) as Record<string, string>

const localList = Object.entries(localModules)
  .sort(([a], [b]) => a.localeCompare(b))
  .map(([, src]) => src)

const galleryData = [
  {
    id: 'g1',
    caption: 'Canchas de tenis al atardecer',
    alt: 'Canchas de tenis del club',
    fallback:
      'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=408&q=80&auto=format&fit=crop'
  },
  {
    id: 'g2',
    caption: 'Alberca climatizada',
    alt: 'Alberca climatizada del club',
    fallback:
      'https://images.unsplash.com/photo-1519315901367-f34ff9154487?w=408&q=80&auto=format&fit=crop'
  },
  {
    id: 'g3',
    caption: 'Sesión de pádel',
    alt: 'Jugadores de pádel en el club',
    fallback:
      'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=408&q=80&auto=format&fit=crop'
  },
  {
    id: 'g4',
    caption: 'Entrenamiento funcional',
    alt: 'Área de entrenamiento funcional',
    fallback:
      'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=408&q=80&auto=format&fit=crop'
  },
  {
    id: 'g5',
    caption: 'Eventos y vida social',
    alt: 'Evento social en el club',
    fallback:
      'https://images.unsplash.com/photo-1519671482749-fd09be7ccebf?w=408&q=80&auto=format&fit=crop'
  },
  {
    id: 'g6',
    caption: 'Áreas verdes y familia',
    alt: 'Áreas verdes y familia en el club',
    fallback:
      'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=408&q=80&auto=format&fit=crop'
  }
]

const galleryItems = computed(() =>
  galleryData.map((item, index) => ({
    ...item,
    image: localList[index] ?? item.fallback
  }))
)

const imageErrorMap = reactive<Record<string, boolean>>({})
</script>
