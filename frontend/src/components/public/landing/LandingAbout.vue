<template>
  <section
    id="nosotros"
    class="relative scroll-mt-20 overflow-hidden bg-lp-paper py-16 md:py-24"
    aria-label="Sobre el club"
  >
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-12">
      <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12 lg:gap-16">
        <!-- Imagen -->
        <div class="lg:col-span-6">
          <div class="relative mx-auto max-w-xl lg:max-w-none">
            <!-- Bloque azul desplazado para dar profundidad -->
            <div
              class="absolute -bottom-4 -right-4 h-full w-full rounded-[2rem] bg-gradient-to-br from-primary-200 to-sky-200 sm:-bottom-6 sm:-right-6"
              aria-hidden="true"
            />

            <div class="relative overflow-hidden rounded-[2rem] bg-primary-100 shadow-lp-lift">
              <img
                v-if="!imageError"
                :src="aboutImage"
                alt="Instalaciones y academia formativa del Britania Club"
                class="h-[300px] w-full object-cover object-center sm:h-[440px]"
                loading="lazy"
                decoding="async"
                width="1132"
                height="440"
                @error="imageError = true"
              />
              <div
                v-else
                class="h-[300px] w-full bg-gradient-to-br from-primary-600 via-primary-500 to-sky-400 sm:h-[440px]"
                aria-hidden="true"
              />

              <!-- Tarjeta flotante de la academia -->
              <div
                class="absolute inset-x-3 bottom-3 flex items-center justify-between gap-3 rounded-2xl bg-lp-paper/95 p-4 shadow-lp-soft ring-1 ring-primary-100 backdrop-blur-md sm:inset-x-5 sm:bottom-5"
              >
                <div class="min-w-0">
                  <span class="lp-display block text-[11px] font-bold uppercase tracking-[0.14em] text-primary-600">
                    Academia Formativa
                  </span>
                  <span class="lp-display mt-0.5 block truncate text-base font-bold tracking-tight text-lp-ink sm:text-lg">
                    Britania Junior Champions
                  </span>
                </div>
                <div
                  class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-600 to-sky-500 text-white shadow-[0_8px_18px_-8px_rgba(29,78,216,0.8)]"
                >
                  <IconStar class="h-4 w-4" aria-hidden="true" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Texto -->
        <div class="lg:col-span-6">
          <LandingSectionHeading eyebrow="Sobre el Club" align="left">
            <template #title>
              Un espacio diseñado para tu bienestar y
              <span class="text-primary-600">familia</span>
            </template>
          </LandingSectionHeading>

          <p class="mt-6 text-base leading-relaxed text-lp-body sm:text-lg">
            Britania Club combina instalaciones deportivas premium con elegantes
            áreas sociales, creando un entorno único donde nuestros miembros pueden
            entrenar con tecnología de punta, competir en torneos oficiales,
            relajarse y conectar con una gran comunidad apasionada por la excelencia.
          </p>
          <p class="mt-4 text-sm leading-relaxed text-lp-muted sm:text-base">
            Desde canchas profesionales de tenis y piscinas semiolímpicas, hasta
            eventos sociales exclusivos y campamentos de verano para niños, cada
            detalle está pensado para ofrecer el máximo confort, seguridad y distinción.
          </p>

          <dl class="mt-8 grid grid-cols-2 gap-3 sm:gap-4">
            <div
              v-for="stat in stats"
              :key="stat.label"
              class="flex flex-col-reverse justify-end gap-2 rounded-2xl bg-lp-mist p-4 ring-1 ring-primary-100 sm:p-5"
            >
              <dt class="text-[11px] font-semibold uppercase leading-snug tracking-[0.06em] text-lp-muted sm:text-xs">
                {{ stat.label }}
              </dt>
              <dd class="lp-display text-3xl font-bold leading-none tracking-tight text-primary-700 sm:text-4xl">
                {{ stat.value }}<span class="text-sky-500">+</span>
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { IconStar } from '@/components/icons'
import LandingSectionHeading from './ui/LandingSectionHeading.vue'

/**
 * FOTOGRAFÍAS — Sobre el Club
 * Coloca tus imágenes en: frontend/src/assets/nosotros/
 * Se carga la primera (orden alfabético). Si está vacía, se usa la de respaldo.
 */
const localModules = import.meta.glob('/src/assets/nosotros/*.{jpg,jpeg,png,webp,avif}', {
  eager: true,
  import: 'default'
}) as Record<string, string>

const fallbackImage =
  'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=1132&q=80&auto=format&fit=crop'

const entries = Object.entries(localModules).sort(([a], [b]) => a.localeCompare(b))
const aboutImage = entries.length > 0 ? entries[0][1] : fallbackImage

const imageError = ref(false)

const stats = [
  { value: '20', label: 'Disciplinas Federadas' },
  { value: '5,000', label: 'Socios y Familias' }
]
</script>
