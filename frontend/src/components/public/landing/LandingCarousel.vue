<template>
  <section
    id="deportes"
    class="relative w-full border-b border-blue-500/20 bg-gradient-to-b from-[#131F38] to-[#0F172A] py-16 md:py-24 scroll-mt-20"
    role="region"
    aria-label="Deportes y actividades"
  >
    <div class="mx-auto flex max-w-7xl flex-col gap-10 px-6 md:gap-12 md:px-12">
      <!-- Header -->
      <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex max-w-2xl flex-col gap-2">
          <span class="lp-display text-xs font-semibold uppercase tracking-[0.16em] text-[#60A5FA]">
            Deportes y Actividades
          </span>
          <h2
            class="lp-display text-3xl font-bold leading-[1.15] tracking-[-0.025em] text-white sm:text-4xl lg:text-[48px]"
          >
            Nuestras Instalaciones
          </h2>
          <p class="lp-body max-w-xl text-base leading-relaxed text-[#CBD5E1]">
            Descubre los espacios de primer nivel que tenemos preparados para ti, tu
            familia y atletas de todos los niveles competitivos.
          </p>
        </div>

        <!-- Filter group -->
        <div
          class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-400/25 bg-[#0B1326]/90 p-1.5 shadow-[inset_0_2px_4px_1px_rgba(0,0,0,0.05)]"
          role="tablist"
          aria-label="Filtrar instalaciones"
        >
          <button
            v-for="category in categories"
            :key="category.id"
            type="button"
            role="tab"
            :aria-selected="activeCategory === category.id"
            class="lp-body rounded-lg px-4 py-1.5 text-xs font-bold uppercase tracking-[0.05em] transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
            :class="
              activeCategory === category.id
                ? 'bg-[#E61C24] text-white shadow-[0_1px_2px_rgba(0,0,0,0.05)]'
                : 'font-semibold tracking-[0.01em] text-[#CBD5E1] hover:bg-white/5 hover:text-white'
            "
            @click="selectCategory(category.id)"
          >
            {{ category.label }}
          </button>
        </div>
      </div>

      <!-- Cards -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <article
          v-for="facility in filteredFacilities"
          :key="facility.id"
          class="group flex flex-col overflow-hidden rounded-2xl border border-blue-400/30 bg-gradient-to-b from-[#1A2849] to-[#0F172A] shadow-[0_10px_15px_-3px_rgba(0,0,0,0.10),0_4px_6px_-4px_rgba(0,0,0,0.10)]"
        >
          <!-- Image -->
          <div class="relative h-56 overflow-hidden">
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
              class="flex h-full w-full items-center justify-center bg-gradient-to-br from-[#1A2849] to-[#0F172A]"
              aria-hidden="true"
            >
              <component :is="facility.icon" class="h-12 w-12 text-blue-300/70" />
            </div>

            <div
              class="pointer-events-none absolute inset-0 bg-[linear-gradient(0deg,#0F172A_0%,rgba(15,23,42,0)_50%,rgba(15,23,42,0)_100%)]"
              aria-hidden="true"
            />

            <span
              class="lp-display absolute right-3 top-3 rounded border bg-[#0B1326]/85 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.01em] shadow-[0_1px_2px_rgba(0,0,0,0.05)] backdrop-blur-sm"
              :class="
                facility.tone === 'red'
                  ? 'border-red-400/40 text-[#F87171]'
                  : 'border-blue-400/40 text-[#93C5FD]'
              "
            >
              {{ facility.badge }}
            </span>
          </div>

          <!-- Body -->
          <div class="flex flex-1 flex-col gap-4 p-6">
            <div class="flex flex-1 flex-col gap-2">
              <h3
                class="lp-display text-[22px] font-bold leading-[1.3] tracking-[-0.01em]"
                :class="facility.tone === 'red' ? 'text-[#F87171]' : 'text-[#60A5FA]'"
              >
                {{ facility.name }}
              </h3>
              <p class="lp-body text-sm leading-[1.625] text-[#CBD5E1]">
                {{ facility.description }}
              </p>
            </div>

            <a
              href="#contacto"
              class="lp-body inline-flex items-center gap-1 text-xs font-bold uppercase tracking-[0.05em] transition-opacity hover:opacity-80"
              :class="facility.tone === 'red' ? 'text-[#F87171]' : 'text-[#60A5FA]'"
              @click="onFacilitySelect(facility)"
            >
              {{ facility.cta }}
              <svg
                width="15"
                height="11"
                viewBox="0 0 15 11"
                fill="currentColor"
                aria-hidden="true"
                class="ml-1"
              >
                <path d="M12.1167 6H4V4.66667H12.1167L8.38333 0.933333L9.33333 0L14.6667 5.33333L9.33333 10.6667L8.38333 9.73333L12.1167 6Z" />
              </svg>
            </a>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { IconTenis, IconSwimming, IconGym, IconDefault } from '@/components/icons/disciplines'

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
  tone: 'blue' | 'red'
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
    tone: 'blue' as const,
    category: 'raqueta',
    icon: IconTenis,
    fallback:
      'https://images.unsplash.com/photo-1510925758641-869d353cecc7?w=552&q=80&auto=format&fit=crop',
    alt: 'Canchas de tenis'
  },
  {
    id: 'natacion',
    name: 'Natación',
    badge: 'Climatizada 28°C',
    description:
      'Alberca techada climatizada de 50 metros diseñada para entrenamiento olímpico, triatlón y escuela formativa infantil.',
    cta: 'Ver niveles de escuela',
    tone: 'red' as const,
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
    tone: 'blue' as const,
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
    tone: 'red' as const,
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
const imageErrorMap = reactive<Record<string, boolean>>({})

const filteredFacilities = computed(() =>
  activeCategory.value === 'todos'
    ? facilities.value
    : facilities.value.filter((facility) => facility.category === activeCategory.value)
)

const selectCategory = (id: string) => {
  activeCategory.value = id
}

const emit = defineEmits<{
  select: [facility: Facility]
}>()

const onFacilitySelect = (facility: Facility) => {
  emit('select', facility)
}
</script>
