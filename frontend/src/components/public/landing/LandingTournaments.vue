<template>
  <section
    id="torneos"
    class="relative scroll-mt-20 bg-lp-mist py-16 md:py-24"
    aria-label="Torneo destacado y pre-registro"
  >
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-12">
      <div
        class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-primary-700 via-primary-600 to-sky-500 p-6 shadow-lp-lift sm:p-10 lg:p-14"
      >
        <!-- Decoración: círculos y líneas de cancha -->
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
          <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-white/10" />
          <div class="absolute -bottom-32 left-1/3 h-72 w-72 rounded-full bg-sky-300/20 blur-2xl" />
          <svg class="absolute right-0 top-0 hidden h-full w-1/2 text-white/[0.07] lg:block" viewBox="0 0 400 400" fill="none" preserveAspectRatio="xMaxYMid slice">
            <rect x="40" y="40" width="320" height="320" stroke="currentColor" stroke-width="3" />
            <line x1="200" y1="40" x2="200" y2="360" stroke="currentColor" stroke-width="3" />
            <line x1="40" y1="200" x2="360" y2="200" stroke="currentColor" stroke-width="3" />
            <circle cx="200" cy="200" r="60" stroke="currentColor" stroke-width="3" />
          </svg>
        </div>

        <div class="relative grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-12">
          <!-- Texto -->
          <div class="flex flex-col gap-6 lg:col-span-7">
            <span
              class="inline-flex w-fit items-center gap-2 rounded-full bg-white/15 px-3.5 py-1.5 ring-1 ring-white/30 backdrop-blur-sm"
            >
              <span class="relative flex h-2 w-2" aria-hidden="true">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-lp-paper opacity-75 motion-reduce:animate-none" />
                <span class="relative inline-flex h-2 w-2 rounded-full bg-lp-paper" />
              </span>
              <span class="lp-display text-xs font-bold uppercase tracking-[0.12em] text-white">
                Inscripciones Abiertas 2026
              </span>
            </span>

            <h2 class="lp-display text-[1.875rem] font-bold leading-[1.08] tracking-[-0.03em] text-white sm:text-4xl lg:text-[2.875rem]">
              Gran Torneo Anual Britania
              <span class="block text-sky-100">Circuito Tenis &amp; Pádel</span>
            </h2>

            <p class="max-w-xl text-base leading-relaxed text-white/85">
              El evento deportivo más prestigioso de la temporada. Más de 400 raqueteros
              compitiendo por puntos del ranking nacional y una bolsa acumulada
              garantizada.
            </p>

            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3">
              <div
                v-for="stat in stats"
                :key="stat.label"
                class="flex flex-col-reverse justify-end gap-1 rounded-2xl bg-white/12 px-4 py-3 ring-1 ring-white/20 backdrop-blur-sm"
                :class="stat.wide ? 'col-span-2 sm:col-span-1' : ''"
              >
                <dt class="text-[11px] font-semibold uppercase tracking-[0.08em] text-white/75">
                  {{ stat.label }}
                </dt>
                <dd class="lp-display text-lg font-bold leading-tight text-white">
                  {{ stat.value }}
                </dd>
              </div>
            </dl>

            <div class="mt-1 flex flex-col gap-3 sm:flex-row">
              <LandingButton :to="preRegisterLink" variant="white" size="lg" block class="sm:w-auto">
                <IconUserPlus class="h-4 w-4" aria-hidden="true" />
                Pre-Registrarme al Torneo
              </LandingButton>
              <LandingButton variant="glass" size="lg" block class="sm:w-auto" @click="onDownload">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                  <path
                    d="M8 12L3 7L4.4 5.6L7 8.2V0H9V8.2L11.6 5.6L13 7L8 12ZM2 16C1.45 16 0.979167 15.8042 0.5875 15.4125C0.195833 15.0208 0 14.55 0 14V11H2V14H14V11H16V14C16 14.55 15.8042 15.0208 15.4125 15.4125C15.0208 15.8042 14.55 16 14 16H2Z"
                  />
                </svg>
                Descargar Convocatoria
              </LandingButton>
            </div>
          </div>

          <!-- Formulario de pre-registro rápido -->
          <div class="lg:col-span-5">
            <div class="rounded-3xl bg-lp-paper p-5 shadow-lp-lift sm:p-8">
              <div class="flex items-start justify-between gap-4 border-b border-lp-line pb-4">
                <div class="flex flex-col gap-0.5">
                  <h3 class="lp-display text-xl font-bold tracking-tight text-lp-ink">Pre-Registro Rápido</h3>
                  <p class="text-sm text-lp-muted">Asegura tu cuadro eliminatorio</p>
                </div>
                <span class="shrink-0 rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-700 ring-1 ring-primary-100">
                  Fase 1
                </span>
              </div>

              <form class="mt-5 flex flex-col gap-4" @submit.prevent="onQuickRegister">
                <div class="flex flex-col gap-1.5">
                  <label for="qr-name" :class="labelClass">Nombre Completo</label>
                  <input
                    id="qr-name"
                    v-model="quickForm.name"
                    type="text"
                    required
                    autocomplete="name"
                    placeholder="Ej. Carlos Mendoza"
                    :class="fieldClass"
                  />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label for="qr-email" :class="labelClass">Correo Electrónico</label>
                  <input
                    id="qr-email"
                    v-model="quickForm.email"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="socio@britaniaclub.mx"
                    :class="fieldClass"
                  />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div class="flex flex-col gap-1.5">
                    <label for="qr-discipline" :class="labelClass">Disciplina</label>
                    <div class="relative">
                      <select id="qr-discipline" v-model="quickForm.discipline" :class="[fieldClass, 'appearance-none pr-10']">
                        <option>Tenis Singles</option>
                        <option>Tenis Dobles</option>
                        <option>Pádel</option>
                        <option>Natación</option>
                      </select>
                      <IconChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-primary-500" aria-hidden="true" />
                    </div>
                  </div>

                  <div class="flex flex-col gap-1.5">
                    <label for="qr-category" :class="labelClass">Categoría</label>
                    <div class="relative">
                      <select id="qr-category" v-model="quickForm.category" :class="[fieldClass, 'appearance-none pr-10']">
                        <option>1ra Fuerza</option>
                        <option>2da Fuerza</option>
                        <option>3ra Fuerza</option>
                        <option>Juvenil</option>
                      </select>
                      <IconChevronDown class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-primary-500" aria-hidden="true" />
                    </div>
                  </div>
                </div>

                <LandingButton type="submit" size="lg" block class="mt-1">
                  Confirmar Pre-Registro
                </LandingButton>
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
import { IconChevronDown } from '@/components/icons'
import IconUserPlus from '@/components/icons/IconUserPlus.vue'
import LandingButton from './ui/LandingButton.vue'

const { toastInfo, successModal } = useAlerts()

const featuredTournamentId = 'gran-torneo-britania-2026'
const preRegisterLink = computed(() => `/torneos/${featuredTournamentId}/pre-registro`)

interface Stat {
  label: string
  value: string
  wide?: boolean
}

const stats: Stat[] = [
  { label: 'Bolsa a Repartir', value: '$150,000 MXN', wide: true },
  { label: 'Fechas Oficiales', value: '15 - 24 Nov' },
  { label: 'Categorías', value: '1ra a 5ta Fuerza' }
]

const labelClass = 'text-xs font-semibold uppercase tracking-[0.06em] text-lp-body'
const fieldClass =
  'w-full rounded-xl border border-lp-line bg-lp-mist px-4 py-3 text-[15px] text-lp-ink placeholder:text-lp-muted/70 transition focus:border-primary-400 focus:bg-lp-paper focus:outline-none focus:ring-4 focus:ring-primary-100'

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
