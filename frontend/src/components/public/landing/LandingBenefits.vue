<template>
  <section
    id="beneficios"
    class="relative w-full overflow-hidden border-y border-[#BFDBFE]/50 bg-gradient-to-b from-[#F1F5F9] to-[#E2E8F0] py-16 md:py-24 scroll-mt-20"
    role="region"
    aria-label="Beneficios exclusivos"
  >
    <!-- Ambient blurred blobs -->
    <div
      class="pointer-events-none absolute left-10 top-0 h-96 w-96 rounded-full bg-[#BFDBFE]/50 blur-[55px]"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute right-10 bottom-0 h-96 w-96 rounded-full bg-[#FECACA]/40 blur-[55px]"
      aria-hidden="true"
    />

    <div class="relative mx-auto flex max-w-7xl flex-col items-center gap-12 px-6 md:px-12 md:gap-16">
      <!-- Header -->
      <div class="flex w-full max-w-3xl flex-col items-center gap-2 text-center">
        <span class="lp-display text-xs font-bold uppercase tracking-[0.18em] text-[#2563EB]">
          Beneficios Exclusivos
        </span>
        <h2
          class="lp-display text-3xl font-bold leading-[1.15] tracking-[-0.025em] text-[#0F172A] sm:text-4xl lg:text-[48px]"
        >
          ¿Por qué elegir Britania Club?
        </h2>
        <p class="lp-body mt-2 text-base leading-relaxed text-[#475569]">
          Pertenecer a Britania es acceder a un ecosistema de privilegios deportivos,
          médicos y sociales pensado para cada etapa de tu vida.
        </p>
      </div>

      <!-- Cards -->
      <div class="grid w-full grid-cols-1 gap-8 md:grid-cols-3">
        <article
          v-for="benefit in benefits"
          :key="benefit.title"
          class="flex flex-col gap-3 rounded-2xl border border-[#DBEAFE]/90 bg-white p-8 shadow-[0_10px_30px_rgba(15,23,42,0.06)] transition-transform duration-300 hover:-translate-y-1"
        >
          <div
            class="flex h-14 w-14 items-center justify-center rounded-2xl text-white"
            :class="
              benefit.tone === 'red'
                ? 'bg-[#E61C24] shadow-[0_8px_20px_rgba(230,28,36,0.35)]'
                : 'bg-[#2563EB] shadow-[0_8px_20px_rgba(37,99,235,0.35)]'
            "
          >
            <component :is="benefit.icon" />
          </div>
          <h3 class="lp-display pt-3 text-[22px] font-bold leading-[1.3] tracking-[-0.01em] text-[#0F172A]">
            {{ benefit.title }}
          </h3>
          <p class="lp-body text-base leading-[1.625] text-[#475569]">
            {{ benefit.description }}
          </p>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { h } from 'vue'

const IconBuilding = {
  render: () =>
    h(
      'svg',
      { width: 25, height: 23, viewBox: '0 0 25 23', fill: 'currentColor', 'aria-hidden': 'true' },
      [
        h('path', {
          d: 'M0 22.5V0H12.5V5H25V22.5H0ZM2.5 20H5V17.5H2.5V20ZM2.5 15H5V12.5H2.5V15ZM2.5 10H5V7.5H2.5V10ZM2.5 5H5V2.5H2.5V5ZM7.5 20H10V17.5H7.5V20ZM7.5 15H10V12.5H7.5V15ZM7.5 10H10V7.5H7.5V10ZM7.5 5H10V2.5H7.5V5ZM12.5 20H22.5V7.5H12.5V10H15V12.5H12.5V15H15V17.5H12.5V20ZM17.5 12.5V10H20V12.5H17.5ZM17.5 17.5V15H20V17.5H17.5Z'
        })
      ]
    )
}

const IconPeople = {
  render: () =>
    h(
      'svg',
      { width: 30, height: 15, viewBox: '0 0 30 15', fill: 'currentColor', 'aria-hidden': 'true' },
      [
        h('path', {
          d: 'M0 15V13.0312C0 12.1354 0.458333 11.4062 1.375 10.8438C2.29167 10.2812 3.5 10 5 10C5.27083 10 5.53125 10.0052 5.78125 10.0156C6.03125 10.026 6.27083 10.0521 6.5 10.0938C6.20833 10.5312 5.98958 10.9896 5.84375 11.4688C5.69792 11.9479 5.625 12.4479 5.625 12.9688V15H0ZM7.5 15V12.9688C7.5 12.3021 7.68229 11.6927 8.04688 11.1406C8.41146 10.5885 8.92708 10.1042 9.59375 9.6875C10.2604 9.27083 11.0573 8.95833 11.9844 8.75C12.9115 8.54167 13.9167 8.4375 15 8.4375C16.1042 8.4375 17.1198 8.54167 18.0469 8.75C18.974 8.95833 19.7708 9.27083 20.4375 9.6875C21.1042 10.1042 21.6146 10.5885 21.9688 11.1406C22.3229 11.6927 22.5 12.3021 22.5 12.9688V15H7.5ZM24.375 15V12.9688C24.375 12.4271 24.3073 11.9167 24.1719 11.4375C24.0365 10.9583 23.8333 10.5104 23.5625 10.0938C23.7917 10.0521 24.026 10.026 24.2656 10.0156C24.5052 10.0052 24.75 10 25 10C26.5 10 27.7083 10.276 28.625 10.8281C29.5417 11.3802 30 12.1146 30 13.0312V15H24.375ZM5 8.75C4.3125 8.75 3.72396 8.50521 3.23438 8.01562C2.74479 7.52604 2.5 6.9375 2.5 6.25C2.5 5.54167 2.74479 4.94792 3.23438 4.46875C3.72396 3.98958 4.3125 3.75 5 3.75C5.70833 3.75 6.30208 3.98958 6.78125 4.46875C7.26042 4.94792 7.5 5.54167 7.5 6.25C7.5 6.9375 7.26042 7.52604 6.78125 8.01562C6.30208 8.50521 5.70833 8.75 5 8.75ZM25 8.75C24.3125 8.75 23.724 8.50521 23.2344 8.01562C22.7448 7.52604 22.5 6.9375 22.5 6.25C22.5 5.54167 22.7448 4.94792 23.2344 4.46875C23.724 3.98958 24.3125 3.75 25 3.75C25.7083 3.75 26.3021 3.98958 26.7812 4.46875C27.2604 4.94792 27.5 5.54167 27.5 6.25C27.5 6.9375 27.2604 7.52604 26.7812 8.01562C26.3021 8.50521 25.7083 8.75 25 8.75ZM15 7.5C13.9583 7.5 13.0729 7.13542 12.3438 6.40625C11.6146 5.67708 11.25 4.79167 11.25 3.75C11.25 2.6875 11.6146 1.79688 12.3438 1.07812C13.0729 0.359375 13.9583 0 15 0C16.0625 0 16.9531 0.359375 17.6719 1.07812C18.3906 1.79688 18.75 2.6875 18.75 3.75C18.75 4.79167 18.3906 5.67708 17.6719 6.40625C16.9531 7.13542 16.0625 7.5 15 7.5Z'
        })
      ]
    )
}

const IconTrophy = {
  render: () =>
    h(
      'svg',
      { width: 23, height: 23, viewBox: '0 0 23 23', fill: 'currentColor', 'aria-hidden': 'true' },
      [
        h('path', {
          d: 'M5 22.5V20H10V16.125C8.97917 15.8958 8.06771 15.4635 7.26562 14.8281C6.46354 14.1927 5.875 13.3958 5.5 12.4375C3.9375 12.25 2.63021 11.5677 1.57812 10.3906C0.526042 9.21354 0 7.83333 0 6.25V5C0 4.3125 0.244792 3.72396 0.734375 3.23438C1.22396 2.74479 1.8125 2.5 2.5 2.5H5V0H17.5V2.5H20C20.6875 2.5 21.276 2.74479 21.7656 3.23438C22.2552 3.72396 22.5 4.3125 22.5 5V6.25C22.5 7.83333 21.974 9.21354 20.9219 10.3906C19.8698 11.5677 18.5625 12.25 17 12.4375C16.625 13.3958 16.0365 14.1927 15.2344 14.8281C14.4323 15.4635 13.5208 15.8958 12.5 16.125V20H17.5V22.5H5ZM5 9.75V5H2.5V6.25C2.5 7.04167 2.72917 7.75521 3.1875 8.39062C3.64583 9.02604 4.25 9.47917 5 9.75ZM17.5 9.75C18.25 9.47917 18.8542 9.02604 19.3125 8.39062C19.7708 7.75521 20 7.04167 20 6.25V5H17.5V9.75Z'
        })
      ]
    )
}

interface Benefit {
  title: string
  description: string
  tone: 'blue' | 'red'
  icon: unknown
}

const benefits: Benefit[] = [
  {
    title: 'Instalaciones Premium',
    description:
      'Acceso total a canchas de tenis y pádel, albercas climatizadas, gimnasio equipado de última generación y extensas áreas verdes privadas con máxima seguridad 24/7.',
    tone: 'blue',
    icon: IconBuilding
  },
  {
    title: 'Comunidad Exclusiva',
    description:
      'Un entorno seguro, honorable y familiar ideal para conectar con otros deportistas, empresarios y líderes de la región a través de dinámicas sociales y de networking.',
    tone: 'red',
    icon: IconPeople
  },
  {
    title: 'Eventos y Torneos',
    description:
      'Participación preferencial en ligas internas, circuitos federados abiertos de tenis y pádel, exhibiciones de maestros y celebraciones de temporada todo el año.',
    tone: 'blue',
    icon: IconTrophy
  }
]
</script>
