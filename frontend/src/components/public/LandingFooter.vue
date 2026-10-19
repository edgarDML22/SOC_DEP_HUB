<template>
  <footer
    class="relative w-full border-t border-lp-line bg-lp-mist"
    role="contentinfo"
    aria-label="Pie de página"
  >
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 md:py-16 lg:px-12">
      <div class="grid grid-cols-2 gap-x-6 gap-y-10 md:grid-cols-3 lg:grid-cols-5 md:gap-10">
        <!-- Brand Column -->
        <div class="col-span-2 flex flex-col gap-5 md:col-span-3 lg:col-span-2">
          <LandingLogo class="self-start" />
          <p class="max-w-md text-sm leading-relaxed text-lp-body sm:text-base">
            Un espacio para el deporte, la salud, la familia y la comunidad. Formamos una experiencia integral con instalaciones de primer nivel.
          </p>
          <div class="flex flex-col gap-2.5 text-sm text-lp-body">
            <div class="flex items-start gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="mt-0.5 flex-shrink-0 text-primary-600"
                aria-hidden="true"
              >
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                <circle cx="12" cy="10" r="3" />
              </svg>
              <span>Av. Principal 123, Col. Centro, México</span>
            </div>
            <div class="flex items-start gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="mt-0.5 flex-shrink-0 text-primary-600"
                aria-hidden="true"
              >
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
              <span>Lun – Dom 06:00 – 22:00</span>
            </div>
            <div class="flex items-start gap-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="mt-0.5 flex-shrink-0 text-primary-600"
                aria-hidden="true"
              >
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
              </svg>
              <span>+52 55 1234 5678</span>
            </div>
          </div>
          <div class="flex items-center gap-3 pt-1">
            <a
              href="https://facebook.com"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-lp-paper text-primary-700 shadow-lp-soft ring-1 ring-primary-100 transition-all hover:-translate-y-0.5 hover:bg-primary-700 hover:text-white focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-200"
              aria-label="Facebook"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
              >
                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.891h-2.33v6.988C18.343 21.128 22 16.991 22 12z" />
              </svg>
            </a>
            <a
              href="https://instagram.com"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-lp-paper text-primary-700 shadow-lp-soft ring-1 ring-primary-100 transition-all hover:-translate-y-0.5 hover:bg-primary-700 hover:text-white focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-200"
              aria-label="Instagram"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
              >
                <path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7zm5 3a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm4.75-2.75a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
              </svg>
            </a>
            <a
              href="https://youtube.com"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-lp-paper text-primary-700 shadow-lp-soft ring-1 ring-primary-100 transition-all hover:-translate-y-0.5 hover:bg-primary-700 hover:text-white focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-200"
              aria-label="YouTube"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
              >
                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
              </svg>
            </a>
          </div>
        </div>

        <!-- Deportes Column -->
        <div class="flex flex-col gap-4">
          <h3 class="lp-display text-sm font-bold uppercase tracking-[0.12em] text-lp-ink">Deportes</h3>
          <ul class="flex flex-col gap-2">
            <li v-for="sport in sports" :key="sport">
              <span class="cursor-default text-sm text-lp-body">{{ sport }}</span>
            </li>
          </ul>
        </div>

        <!-- Comunidad Column -->
        <div class="flex flex-col gap-4">
          <h3 class="lp-display text-sm font-bold uppercase tracking-[0.12em] text-lp-ink">Comunidad</h3>
          <ul class="flex flex-col gap-2">
            <li>
              <RouterLink to="/torneos" class="rounded text-sm text-lp-body transition-colors hover:text-primary-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-100">
                Torneos
              </RouterLink>
            </li>
            <li>
              <RouterLink to="/login" class="rounded text-sm text-lp-body transition-colors hover:text-primary-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-100">
                Portal de Socios
              </RouterLink>
            </li>
            <li>
              <a href="#nosotros" class="rounded text-sm text-lp-body transition-colors hover:text-primary-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-100">
                Nosotros
              </a>
            </li>
            <li>
              <a href="#contacto" class="rounded text-sm text-lp-body transition-colors hover:text-primary-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-100">
                Contacto
              </a>
            </li>
          </ul>
        </div>

        <!-- Legal Column -->
        <div class="flex flex-col gap-4">
          <h3 class="lp-display text-sm font-bold uppercase tracking-[0.12em] text-lp-ink">Marco legal</h3>
          <ul class="flex flex-col gap-2">
            <li>
              <a href="#" class="rounded text-sm text-lp-body transition-colors hover:text-primary-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-100">
                Aviso de privacidad
              </a>
            </li>
            <li>
              <a href="#" class="rounded text-sm text-lp-body transition-colors hover:text-primary-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-100">
                Términos y condiciones
              </a>
            </li>
            <li>
              <a href="#" class="rounded text-sm text-lp-body transition-colors hover:text-primary-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-100">
                Reglamento interno
              </a>
            </li>
          </ul>
        </div>
      </div>

      <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-lp-line pt-6 md:flex-row">
        <p class="text-center text-sm text-lp-muted md:text-left">
          © {{ currentYear }} Britania Club Deportivo &amp; Social. Todos los derechos reservados.
        </p>
        <p class="text-center text-sm text-lp-muted md:text-right">
          Hecho con pasión por la comunidad deportiva
        </p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import LandingLogo from '@/components/public/landing/ui/LandingLogo.vue'

const sports = ['Tenis', 'Pádel', 'Natación', 'Gimnasio', 'Squash', 'Actividad Física']

const currentYear = computed(() => new Date().getFullYear())
</script>



