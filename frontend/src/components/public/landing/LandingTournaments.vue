<template>
  <section
    id="torneos"
    class="relative w-full overflow-hidden border-y border-blue-500/20 bg-gradient-to-b from-[#162238] via-[#131F38] to-[#0F172A] py-16 md:py-24 scroll-mt-20"
    role="region"
    aria-label="Torneo destacado y pre-registro"
  >
    <div class="mx-auto max-w-7xl px-6 md:px-12">
      <div
        class="relative overflow-hidden rounded-3xl border border-blue-400/30 bg-gradient-to-r from-[#131F38] via-[#1A2849] to-[#131F38] p-8 shadow-[0_20px_60px_rgba(11,19,38,0.70),0_0_35px_rgba(59,130,246,0.18)] md:p-14"
      >
        <div
          class="pointer-events-none absolute inset-0 opacity-30 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.35),transparent_65%)]"
          aria-hidden="true"
        />

        <div class="relative grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-10">
          <!-- Left: copy -->
          <div class="flex flex-col gap-6 lg:col-span-7">
            <span
              class="inline-flex w-fit items-center gap-2 rounded-full border border-blue-400/40 bg-[#1E3A8A]/60 px-3.5 py-1.5 shadow-[0_1px_2px_rgba(0,0,0,0.05)]"
            >
              <span class="relative flex h-2 w-2">
                <span
                  class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#60A5FA] opacity-75"
                />
                <span class="relative inline-flex h-2 w-2 rounded-full bg-[#60A5FA]" />
              </span>
              <span class="lp-display text-xs font-semibold uppercase tracking-[0.05em] text-[#BFDBFE]">
                Inscripciones Abiertas 2025
              </span>
            </span>

            <div class="flex flex-col gap-1">
              <h2
                class="lp-display text-3xl font-bold leading-[1.12] tracking-[-0.025em] text-white sm:text-4xl lg:text-[44px]"
              >
                Gran Torneo Anual Britania
              </h2>
              <span
                class="lp-display bg-gradient-to-r from-[#60A5FA] to-[#7DD3FC] bg-clip-text text-3xl font-bold leading-[1.12] tracking-[-0.025em] text-transparent sm:text-4xl lg:text-[44px]"
              >
                Circuito Tenis &amp; Pádel
              </span>
            </div>

            <p class="lp-body max-w-xl text-base leading-relaxed text-[#CBD5E1]">
              El evento deportivo más prestigioso de la temporada. Más de 400 raqueteros
              compitiendo por puntos del ranking nacional y una bolsa acumulada
              garantizada.
            </p>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
              <div
                v-for="stat in stats"
                :key="stat.label"
                class="flex flex-col gap-1 rounded-xl border border-blue-400/20 bg-[#0B1326]/80 px-4 py-3"
              >
                <span class="lp-body text-xs font-semibold uppercase tracking-wide text-[#94A3B8]">
                  {{ stat.label }}
                </span>
                <span class="lp-display text-lg font-bold" :class="stat.valueClass">
                  {{ stat.value }}
                </span>
                <span
                  v-if="stat.sub"
                  class="lp-display text-sm font-bold"
                  :class="stat.valueClass"
                >
                  {{ stat.sub }}
                </span>
              </div>
            </div>

            <div class="mt-2 flex flex-col gap-4 sm:flex-row">
              <RouterLink
                :to="preRegisterLink"
                class="lp-body inline-flex items-center justify-center gap-2 rounded-xl bg-[#E61C24] px-6 py-3.5 text-sm font-bold uppercase tracking-[0.03em] text-white shadow-[0_10px_15px_-3px_rgba(0,0,0,0.10),0_4px_6px_-4px_rgba(0,0,0,0.10)] transition-colors hover:bg-[#c4161d] focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400/60"
              >
                <svg width="18" height="18" viewBox="0 0 18 18" fill="currentColor" aria-hidden="true">
                  <path
                    d="M6.75 9C5.925 9 5.21875 8.70625 4.63125 8.11875C4.04375 7.53125 3.75 6.825 3.75 6C3.75 5.175 4.04375 4.46875 4.63125 3.88125C5.21875 3.29375 5.925 3 6.75 3C7.575 3 8.28125 3.29375 8.86875 3.88125C9.45625 4.46875 9.75 5.175 9.75 6C9.75 6.825 9.45625 7.53125 8.86875 8.11875C8.28125 8.70625 7.575 9 6.75 9ZM0 15.75V13.65C0 13.225 0.10625 12.8438 0.31875 12.5063C0.53125 12.1688 0.8125 11.9 1.1625 11.7C1.9125 11.275 2.725 10.9375 3.6 10.6875C4.475 10.4375 5.4 10.3125 6.375 10.3125C7.35 10.3125 8.275 10.4375 9.15 10.6875C10.025 10.9375 10.8375 11.275 11.5875 11.7C11.9375 11.9 12.2188 12.1688 12.4313 12.5063C12.6438 12.8438 12.75 13.225 12.75 13.65V15.75H0ZM14.25 9C13.6 9 13.0375 8.7625 12.5625 8.2875C12.0875 7.8125 11.85 7.25 11.85 6.6C11.85 5.925 12.0875 5.35 12.5625 4.875C13.0375 4.4 13.6 4.1625 14.25 4.1625C14.9 4.1625 15.4625 4.4 15.9375 4.875C16.4125 5.35 16.65 5.925 16.65 6.6C16.65 7.25 16.4125 7.8125 15.9375 8.2875C15.4625 8.7625 14.9 9 14.25 9ZM13.5 15.75V13.65C13.5 13.025 13.3938 12.4188 13.1813 11.8313C12.9688 11.2438 12.675 10.7 12.3 10.2C12.75 10.325 13.175 10.475 13.575 10.65C13.975 10.825 14.35 11.025 14.7 11.25C15.1 11.5 15.4063 11.7813 15.6188 12.0938C15.8313 12.4063 15.9375 12.75 15.9375 13.125V15.75H13.5Z"
                  />
                </svg>
                Pre-Registrarme al Torneo
              </RouterLink>

              <button
                type="button"
                class="lp-body inline-flex items-center justify-center gap-2 rounded-xl border border-blue-400/40 bg-[#1E3A8A]/40 px-6 py-3.5 text-sm font-bold uppercase tracking-[0.03em] text-white transition-colors hover:bg-[#1E3A8A]/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/60"
                @click="onDownload"
              >
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                  <path
                    d="M8 12L3 7L4.4 5.6L7 8.2V0H9V8.2L11.6 5.6L13 7L8 12ZM2 16C1.45 16 0.979167 15.8042 0.5875 15.4125C0.195833 15.0208 0 14.55 0 14V11H2V14H14V11H16V14C16 14.55 15.8042 15.0208 15.4125 15.4125C15.0208 15.8042 14.55 16 14 16H2Z"
                  />
                </svg>
                Descargar Convocatoria
              </button>
            </div>
          </div>

          <!-- Right: quick pre-registration form -->
          <div class="lg:col-span-5">
            <div
              class="rounded-2xl border border-blue-400/30 bg-gradient-to-b from-[#0F172A] to-[#0B1326] p-7 shadow-[0_20px_50px_-20px_rgba(11,19,38,0.9)] backdrop-blur md:p-8"
            >
              <div
                class="flex items-start justify-between gap-4 border-b border-blue-500/20 pb-4"
              >
                <div class="flex flex-col gap-0.5">
                  <h3 class="lp-display text-xl font-bold text-white">Pre-Registro Rápido</h3>
                  <p class="lp-body text-xs text-[#94A3B8]">Asegura tu cuadro eliminatorio</p>
                </div>
                <span
                  class="lp-body shrink-0 rounded-full border border-blue-400/40 bg-[#2563EB]/30 px-3 py-1 text-xs font-semibold text-[#BFDBFE]"
                >
                  Fase 1
                </span>
              </div>

              <form class="mt-5 flex flex-col gap-4" @submit.prevent="onQuickRegister">
                <div class="flex flex-col gap-1.5">
                  <label for="qr-name" class="lp-body text-xs font-semibold uppercase tracking-wide text-[#CBD5E1]">
                    Nombre Completo
                  </label>
                  <input
                    id="qr-name"
                    v-model="quickForm.name"
                    type="text"
                    required
                    placeholder="Ej. Carlos Mendoza"
                    class="lp-body w-full rounded-lg border border-blue-400/25 bg-[#131F38] px-4 py-3 text-sm text-white placeholder:text-[#64748B] focus:border-blue-400/60 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                  />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label for="qr-email" class="lp-body text-xs font-semibold uppercase tracking-wide text-[#CBD5E1]">
                    Correo Electrónico
                  </label>
                  <input
                    id="qr-email"
                    v-model="quickForm.email"
                    type="email"
                    required
                    placeholder="socio@britaniaclub.mx"
                    class="lp-body w-full rounded-lg border border-blue-400/25 bg-[#131F38] px-4 py-3 text-sm text-white placeholder:text-[#64748B] focus:border-blue-400/60 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                  />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div class="flex flex-col gap-1.5">
                    <label for="qr-discipline" class="lp-body text-xs font-semibold uppercase tracking-wide text-[#CBD5E1]">
                      Disciplina
                    </label>
                    <select
                      id="qr-discipline"
                      v-model="quickForm.discipline"
                      class="lp-body w-full rounded-lg border border-blue-400/25 bg-[#131F38] px-4 py-3 text-sm text-white focus:border-blue-400/60 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                    >
                      <option>Tenis Singles</option>
                      <option>Tenis Dobles</option>
                      <option>Pádel</option>
                      <option>Natación</option>
                    </select>
                  </div>

                  <div class="flex flex-col gap-1.5">
                    <label for="qr-category" class="lp-body text-xs font-semibold uppercase tracking-wide text-[#CBD5E1]">
                      Categoría
                    </label>
                    <select
                      id="qr-category"
                      v-model="quickForm.category"
                      class="lp-body w-full rounded-lg border border-blue-400/25 bg-[#131F38] px-4 py-3 text-sm text-white focus:border-blue-400/60 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                    >
                      <option>1ra Fuerza</option>
                      <option>2da Fuerza</option>
                      <option>3ra Fuerza</option>
                      <option>Juvenil</option>
                    </select>
                  </div>
                </div>

                <button
                  type="submit"
                  class="lp-body mt-1 inline-flex items-center justify-center rounded-xl bg-[#E61C24] px-6 py-3.5 text-sm font-bold uppercase tracking-[0.03em] text-white shadow-[0_10px_15px_-3px_rgba(0,0,0,0.10),0_4px_6px_-4px_rgba(0,0,0,0.10)] transition-colors hover:bg-[#c4161d] focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400/60"
                >
                  Confirmar Pre-Registro
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue'
import { useAlerts } from '@/composables/useAlerts'

const { toastInfo, successModal } = useAlerts()

const featuredTournamentId = 'gran-torneo-britania-2026'
const preRegisterLink = computed(() => `/torneos/${featuredTournamentId}/pre-registro`)

interface Stat {
  label: string
  value: string
  sub?: string
  valueClass: string
}

const stats: Stat[] = [
  { label: 'Bolsa a Repartir', value: '$150,000 MXN', valueClass: 'text-white' },
  { label: 'Fechas Oficiales', value: '15 - 24 Nov', valueClass: 'text-[#60A5FA]' },
  { label: 'Categorías', value: '1ra a 5ta', sub: 'Fuerza', valueClass: 'text-[#93C5FD]' }
]

const quickForm = reactive({
  name: '',
  email: '',
  discipline: 'Tenis Singles',
  category: '1ra Fuerza'
})

const onQuickRegister = async () => {
  await successModal(
    '¡Pre-registro recibido!',
    'Gracias, recibimos tus datos. Nuestro equipo de admisiones te contactará para confirmar tu lugar en el torneo.'
  )
  quickForm.name = ''
  quickForm.email = ''
  quickForm.discipline = 'Tenis Singles'
  quickForm.category = '1ra Fuerza'
}

const onDownload = () => {
  toastInfo('Convocatoria', 'La convocatoria oficial estará disponible muy pronto.')
}
</script>
