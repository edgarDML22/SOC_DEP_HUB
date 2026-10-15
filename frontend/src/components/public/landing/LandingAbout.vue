<template>
  <section
    id="nosotros"
    class="relative w-full overflow-hidden border-y border-[#BFDBFE]/60 bg-gradient-to-b from-[#E8EDF5] to-[#F0F4F8] py-16 md:py-24 scroll-mt-20"
    role="region"
    aria-label="Sobre el club"
  >
    <!-- Ambient blurred blobs -->
    <div
      class="pointer-events-none absolute -left-24 top-10 h-96 w-96 rounded-full bg-[#BFDBFE]/40 blur-[50px]"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute -right-24 bottom-10 h-96 w-96 rounded-full bg-[#BAE6FD]/50 blur-[50px]"
      aria-hidden="true"
    />

    <div class="relative mx-auto max-w-7xl px-6 md:px-12">
      <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-12 lg:gap-12">
        <!-- Visual column -->
        <div class="lg:col-span-6">
          <div class="relative">
            <div class="relative overflow-hidden rounded-2xl border border-[#BFDBFE]/80 bg-white shadow-[0_20px_50px_rgba(15,23,42,0.12)]">
              <img
                v-if="!imageError"
                :src="aboutImage"
                alt="Instalaciones y academia formativa del Britania Club"
                class="h-[320px] w-full object-cover object-center sm:h-[440px]"
                loading="lazy"
                decoding="async"
                width="1132"
                height="440"
                @error="imageError = true"
              />
              <div
                v-else
                class="h-[320px] w-full bg-gradient-to-br from-[#2563EB] via-[#1d4ed8] to-[#0F172A] sm:h-[440px]"
                aria-hidden="true"
              />
              <div
                class="pointer-events-none absolute inset-0 bg-[linear-gradient(0deg,rgba(15,23,42,0.80)_0%,rgba(15,23,42,0)_50%,rgba(15,23,42,0)_100%)]"
                aria-hidden="true"
              />

              <!-- Floating academy card -->
              <div
                class="absolute inset-x-4 bottom-4 flex items-center justify-between gap-4 rounded-xl border border-[#DBEAFE] bg-white/95 p-4 shadow-[0_20px_25px_-5px_rgba(0,0,0,0.10),0_8px_10px_-6px_rgba(0,0,0,0.10)] backdrop-blur-md sm:inset-x-6"
              >
                <div class="min-w-0">
                  <span class="lp-display block text-xs font-bold uppercase tracking-[0.05em] text-[#2563EB]">
                    Academia Formativa
                  </span>
                  <span class="lp-display mt-0.5 block truncate text-lg font-bold leading-tight tracking-[-0.01em] text-[#0F172A]">
                    Britania Junior Champions
                  </span>
                </div>
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-[#E61C24] text-white shadow-[0_4px_14px_rgba(230,28,36,0.35)]">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2l2.4 7.4H22l-6 4.4 2.3 7.2L12 16.8 5.7 21l2.3-7.2-6-4.4h7.6L12 2z" />
                  </svg>
                </div>
              </div>
            </div>

            <div
              class="pointer-events-none absolute -bottom-8 -left-8 h-64 w-64 rounded-full bg-[#60A5FA]/15 blur-3xl"
              aria-hidden="true"
            />
          </div>
        </div>

        <!-- Text column -->
        <div class="lg:col-span-6">
          <span class="lp-display text-xs font-bold uppercase tracking-[0.18em] text-[#2563EB]">
            Sobre el Club
          </span>
          <h2
            class="lp-display mt-3 text-3xl font-bold leading-[1.15] tracking-[-0.025em] text-[#0F172A] sm:text-4xl lg:text-[48px]"
          >
            Un espacio diseñado para tu bienestar y
            <span class="text-[#2563EB]"> familia</span>
          </h2>

          <p class="lp-body mt-6 text-base leading-relaxed text-[#334155]">
            Britania Club combina instalaciones deportivas premium con elegantes
            áreas sociales, creando un entorno único donde nuestros miembros pueden
            entrenar con tecnología de punta, competir en torneos oficiales,
            relajarse y conectar con una gran comunidad apasionada por la excelencia.
          </p>
          <p class="lp-body mt-4 text-sm leading-relaxed text-[#475569]">
            Desde canchas profesionales de tenis y piscinas semiolímpicas, hasta
            eventos sociales exclusivos y campamentos de verano para niños, cada
            detalle está pensado para ofrecer el máximo confort, seguridad y distinción.
          </p>

          <div class="mt-8 flex flex-col gap-4 sm:flex-row">
            <div
              class="flex-1 rounded-xl border border-[#DBEAFE] bg-white p-5 shadow-[0_6px_20px_rgba(37,99,235,0.06)]"
            >
              <div class="lp-display flex items-baseline leading-none tracking-[-0.015em]">
                <span class="text-[32px] font-bold text-[#2563EB]">20</span>
                <span class="text-[32px] font-bold text-[#60A5FA]">+</span>
              </div>
              <p class="lp-body mt-2 text-xs font-semibold uppercase tracking-[0.05em] text-[#475569]">
                Disciplinas Federadas
              </p>
            </div>
            <div
              class="flex-1 rounded-xl border border-[#DBEAFE] bg-white p-5 shadow-[0_6px_20px_rgba(230,28,36,0.06)]"
            >
              <div class="lp-display flex items-baseline leading-none tracking-[-0.015em]">
                <span class="text-[32px] font-bold text-[#E61C24]">5,000</span>
                <span class="text-[32px] font-bold text-[#F87171]">+</span>
              </div>
              <p class="lp-body mt-2 text-xs font-semibold uppercase tracking-[0.05em] text-[#475569]">
                Socios y Familias
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'

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
</script>
