<template>
  <section
    id="deportes"
    class="relative scroll-mt-20 bg-lp-mist py-16 md:py-24"
    aria-label="Deportes y actividades"
  >
    <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 md:gap-12 lg:px-12">
      <!-- Encabezado + filtros -->
      <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <LandingSectionHeading
          eyebrow="Deportes y Actividades"
          title="Nuestras Instalaciones"
          description="Descubre los espacios de primer nivel que tenemos preparados para ti, tu familia y atletas de todos los niveles competitivos."
          align="left"
        />

        <!-- En móvil los filtros se desplazan en una sola fila -->
        <div class="lp-no-scrollbar -mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
          <div
            class="inline-flex gap-1 rounded-full bg-lp-paper p-1.5 shadow-lp-soft ring-1 ring-primary-100"
            role="tablist"
            aria-label="Filtrar instalaciones"
          >
            <button
              v-for="category in categories"
              :key="category.id"
              type="button"
              role="tab"
              :aria-selected="activeCategory === category.id"
              class="shrink-0 rounded-full px-4 py-2 text-[13px] font-semibold transition-colors duration-200 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-100"
              :class="
                activeCategory === category.id
                  ? 'bg-primary-700 text-white shadow-[0_8px_16px_-10px_rgba(29,78,216,0.9)]'
                  : 'text-lp-body hover:bg-primary-50 hover:text-primary-700'
              "
              @click="selectCategory(category.id)"
            >
              {{ category.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Tarjetas: carrusel deslizable en móvil, cuadrícula desde sm -->
      <div>
        <div
          ref="track"
          class="lp-no-scrollbar -mx-4 flex snap-x snap-mandatory scroll-px-4 gap-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-6 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-4"
          @scroll.passive="onTrackScroll"
        >
          <article
            v-for="facility in filteredFacilities"
            :key="facility.id"
            class="group flex w-[82%] shrink-0 snap-start flex-col overflow-hidden rounded-3xl bg-lp-paper shadow-lp-soft ring-1 ring-primary-100/80 transition-all duration-300 hover:-translate-y-1 hover:shadow-lp-lift sm:w-auto"
          >
            <div class="relative h-48 overflow-hidden bg-primary-100 sm:h-52">
              <img
                v-if="!imageErrorMap[facility.id]"
                :src="facility.image"
                :alt="facility.alt"
                class="h-full w-full object-cover object-center transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
                decoding="async"
                width="552"
                height="224"
                @error="imageErrorMap[facility.id] = true"
              />
              <div
                v-else
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary-100 to-sky-100"
                aria-hidden="true"
              >
                <component :is="facility.icon" class="h-12 w-12 text-primary-400" />
              </div>

              <span
                class="absolute left-3 top-3 rounded-full bg-lp-paper/95 px-3 py-1 text-xs font-bold text-primary-700 shadow-lp-soft backdrop-blur-sm"
              >
                {{ facility.badge }}
              </span>
            </div>

            <div class="relative flex flex-1 flex-col gap-3 px-5 pb-6 pt-9">
              <!-- Ícono que monta sobre la foto -->
              <div
                class="absolute -top-6 left-5 flex h-12 w-12 items-center justify-center rounded-2xl text-white ring-4 ring-lp-paper"
                :class="
                  facility.tone === 'sky'
                    ? 'bg-gradient-to-br from-sky-400 to-sky-600'
                    : 'bg-gradient-to-br from-primary-500 to-primary-700'
                "
              >
                <component :is="facility.icon" class="h-6 w-6" aria-hidden="true" />
              </div>

              <h3 class="lp-display text-xl font-bold tracking-tight text-lp-ink">
                {{ facility.name }}
              </h3>
              <p class="flex-1 text-sm leading-relaxed text-lp-body">
                {{ facility.description }}
              </p>
              <a
                href="#contacto"
                class="inline-flex items-center gap-1.5 text-sm font-bold text-primary-700 transition-colors hover:text-primary-500"
                @click="onFacilitySelect(facility)"
              >
                {{ facility.cta }}
                <IconChevronRight class="h-4 w-4 transition-transform group-hover:translate-x-1" aria-hidden="true" />
              </a>
            </div>
          </article>
        </div>

        <!-- Indicador de posición (solo móvil) -->
        <div v-if="filteredFacilities.length > 1" class="mt-5 flex justify-center gap-1.5 sm:hidden" aria-hidden="true">
          <span
            v-for="(facility, index) in filteredFacilities"
            :key="'dot-' + facility.id"
            class="h-1.5 rounded-full transition-all duration-300"
            :class="index === activeCard ? 'w-6 bg-primary-600' : 'w-1.5 bg-primary-200'"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, nextTick, reactive, ref } from 'vue'
import { IconChevronRight } from '@/components/icons'
import { IconTenis, IconSwimming, IconGym, IconDefault } from '@/components/icons/disciplines'
import LandingSectionHeading from './ui/LandingSectionHeading.vue'

interface Category {
  id: string
  label: string
}

interface Facility {
  id: string
  name: string
  badge: string
  description: string
  cta: string
  tone: 'primary' | 'sky'
  category: string
  icon: unknown
  fallback: string
  image: string
  alt: string
}

/**
 * FOTOGRAFÍAS — Instalaciones / Deportes
 * Coloca tus imágenes en: frontend/src/assets/instalaciones/
 * Se asignan a las tarjetas por orden alfabético. Si la carpeta está vacía,
 * se usan las imágenes de respaldo (Unsplash).
 */
const localModules = import.meta.glob('/src/assets/instalaciones/*.{jpg,jpeg,png,webp,avif}', {
  eager: true,
  import: 'default'
}) as Record<string, string>

const localList = Object.entries(localModules)
  .sort(([a], [b]) => a.localeCompare(b))
  .map(([, src]) => src)

const categories: Category[] = [
  { id: 'todos', label: 'Todos' },
  { id: 'raqueta', label: 'Raqueta' },
  { id: 'acuaticos', label: 'Acuáticos' },
  { id: 'fitness', label: 'Fitness' },
  { id: 'social', label: 'Social' }
]

const facilitiesData = [
  {
    id: 'tenis',
    name: 'Tenis',
    badge: '12 Canchas',
    description:
      'Canchas profesionales de arcilla y dura reglamentarias con iluminación LED de alta competencia y torneos para todas las categorías.',
    cta: 'Ver horarios & clínicas',
    tone: 'primary' as const,
    category: 'raqueta',
    icon: IconTenis,
    fallback:
      'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?w=552&q=80&auto=format&fit=crop',
    alt: 'Canchas de tenis'
  },
  {
    id: 'natacion',
    name: 'Natación',
    badge: 'Climatizada 28°C',
    description:
      'Alberca techada climatizada de 50 metros diseñada para entrenamiento olímpico, triatlón y escuela formativa infantil.',
    cta: 'Ver niveles de escuela',
    tone: 'sky' as const,
    category: 'acuaticos',
    icon: IconSwimming,
    fallback:
      'https://images.unsplash.com/photo-1530549387789-4c1017266635?w=552&q=80&auto=format&fit=crop',
    alt: 'Alberca climatizada'
  },
  {
    id: 'gimnasio',
    name: 'Gimnasio & Fitness',
    badge: '1,200 M²',
    description:
      'Área de pesas y cardiovascular de alta gama, zona funcional para Crossfit y más de 30 clases dirigidas semanales por coaches certificados.',
    cta: 'Programa de clases',
    tone: 'primary' as const,
    category: 'fitness',
    icon: IconGym,
    fallback:
      'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=552&q=80&auto=format&fit=crop',
    alt: 'Gimnasio y área de fitness'
  },
  {
    id: 'eventos',
    name: 'Eventos Familiares',
    badge: 'Terraza & Lounges',
    description:
      'Eventos sociales, terrazas gastronómicas, asadores privados y recreaciones exclusivas diseñadas para todos los miembros de la familia.',
    cta: 'Conoce el calendario',
    tone: 'sky' as const,
    category: 'social',
    icon: IconDefault,
    fallback:
      'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?w=552&q=80&auto=format&fit=crop',
    alt: 'Áreas sociales y eventos familiares'
  }
]

const facilities = computed<Facility[]>(() =>
  facilitiesData.map((facility, index) => ({
    ...facility,
    image: localList[index] ?? facility.fallback
  }))
)

const activeCategory = ref('todos')
const activeCard = ref(0)
const track = ref<HTMLElement | null>(null)
const imageErrorMap = reactive<Record<string, boolean>>({})

const filteredFacilities = computed(() =>
  activeCategory.value === 'todos'
    ? facilities.value
    : facilities.value.filter((facility) => facility.category === activeCategory.value)
)

const selectCategory = async (id: string) => {
  activeCategory.value = id
  activeCard.value = 0
  await nextTick()
  track.value?.scrollTo({ left: 0 })
}

const onTrackScroll = () => {
  const el = track.value
  const firstCard = el?.firstElementChild as HTMLElement | null
  if (!el || !firstCard) return
  const step = firstCard.offsetWidth + 16
  activeCard.value = Math.min(Math.round(el.scrollLeft / step), filteredFacilities.value.length - 1)
}

const emit = defineEmits<{
  select: [facility: Facility]
}>()

const onFacilitySelect = (facility: Facility) => {
  emit('select', facility)
}
</script>
