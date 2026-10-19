<template>
  <section
    id="galeria"
    class="relative scroll-mt-20 bg-lp-paper py-16 md:py-24"
    aria-label="Galería del club"
  >
    <div class="mx-auto flex max-w-7xl flex-col gap-10 px-4 sm:px-6 md:gap-14 lg:px-12">
      <LandingSectionHeading
        eyebrow="Galería"
        title="Experiencias en el Club"
        description="La energía, la pasión y los momentos que definen la vida diaria de nuestros socios en Britania Club."
      />

      <!-- Mosaico: 2 columnas en móvil, 4 en escritorio -->
      <div class="grid grid-flow-dense auto-rows-[150px] grid-cols-2 gap-3 sm:auto-rows-[190px] sm:gap-4 md:grid-cols-4 md:auto-rows-[200px]">
        <figure
          v-for="item in galleryItems"
          :key="item.id"
          class="group relative m-0 overflow-hidden rounded-3xl bg-primary-100"
          :class="item.span"
        >
          <img
            v-if="!imageErrorMap[item.id]"
            :src="item.image"
            :alt="item.alt"
            class="h-full w-full object-cover object-center transition-transform duration-700 group-hover:scale-105"
            loading="lazy"
            decoding="async"
            @error="imageErrorMap[item.id] = true"
          />
          <div
            v-else
            class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary-100 to-sky-100"
            aria-hidden="true"
          >
            <span class="lp-display text-sm uppercase tracking-[0.2em] text-primary-400">Britania Club</span>
          </div>

          <figcaption
            class="absolute bottom-2.5 left-2.5 max-w-[calc(100%-1.25rem)] truncate rounded-full bg-lp-paper/90 px-3 py-1.5 text-xs font-semibold text-lp-ink shadow-lp-soft backdrop-blur-sm transition-transform duration-300 group-hover:-translate-y-0.5 sm:bottom-4 sm:left-4 sm:text-sm"
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
import LandingSectionHeading from './ui/LandingSectionHeading.vue'

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
    span: 'col-span-2 md:row-span-2',
    caption: 'Canchas de tenis al atardecer',
    alt: 'Canchas de tenis del club',
    fallback:
      'https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?w=800&q=80&auto=format&fit=crop'
  },
  {
    id: 'g2',
    span: '',
    caption: 'Alberca climatizada',
    alt: 'Alberca climatizada del club',
    fallback:
      'https://images.unsplash.com/photo-1519315901367-f34ff9154487?w=800&q=80&auto=format&fit=crop'
  },
  {
    id: 'g3',
    span: 'md:row-span-2',
    caption: 'Sesión de pádel',
    alt: 'Jugadores de pádel en el club',
    fallback:
      'https://images.unsplash.com/photo-1612534847738-b3af9bc31f0c?w=800&q=80&auto=format&fit=crop'
  },
  {
    id: 'g4',
    span: 'col-span-2 md:col-span-1',
    caption: 'Entrenamiento funcional',
    alt: 'Área de entrenamiento funcional',
    fallback:
      'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&q=80&auto=format&fit=crop'
  },
  {
    id: 'g5',
    span: 'col-span-2',
    caption: 'Eventos y vida social',
    alt: 'Evento social en el club',
    fallback:
      'https://images.unsplash.com/photo-1519671482749-fd09be7ccebf?w=800&q=80&auto=format&fit=crop'
  },
  {
    id: 'g6',
    span: 'col-span-2',
    caption: 'Áreas verdes y familia',
    alt: 'Áreas verdes y familia en el club',
    fallback:
      'https://images.unsplash.com/photo-1502086223501-7ea6ecd79368?w=800&q=80&auto=format&fit=crop'
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
