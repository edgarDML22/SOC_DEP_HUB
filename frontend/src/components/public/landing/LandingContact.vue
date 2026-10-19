<template>
  <section
    id="contacto"
    class="relative scroll-mt-20 bg-lp-mist py-16 md:py-20"
    aria-label="Contacto y concierge"
  >
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-12">
      <div
        class="relative overflow-hidden rounded-[2rem] bg-lp-paper p-6 shadow-lp-soft ring-1 ring-primary-100 sm:p-10 lg:p-12"
      >
        <div
          class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-gradient-to-br from-primary-100 to-sky-100 blur-2xl"
          aria-hidden="true"
        />

        <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
          <div class="flex max-w-xl items-start gap-4 sm:gap-5">
            <div
              class="hidden h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-600 to-sky-500 text-white shadow-[0_12px_24px_-12px_rgba(29,78,216,0.8)] sm:flex"
              aria-hidden="true"
            >
              <IconMessage class="h-6 w-6" />
            </div>
            <div class="flex flex-col gap-2">
              <span class="lp-display text-xs font-bold uppercase tracking-[0.16em] text-primary-600">
                Atención a Socios &amp; Visitas
              </span>
              <h3 class="lp-display text-2xl font-bold leading-tight tracking-tight text-lp-ink sm:text-[2rem]">
                ¿Deseas conocer las instalaciones o afiliarte?
              </h3>
              <p class="mt-1 text-[15px] leading-relaxed text-lp-body">
                Agenda un recorrido guiado por nuestras canchas, albercas y áreas sociales con
                nuestro equipo de admisiones.
              </p>
            </div>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row sm:items-center lg:shrink-0">
            <LandingButton
              :href="whatsappUrl"
              target="_blank"
              rel="noopener noreferrer"
              variant="outline"
              size="lg"
              block
              class="sm:w-auto"
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" class="text-[#25D366]" aria-hidden="true">
                <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 18.13h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.24 8.24 0 0 1-1.26-4.36c0-4.54 3.7-8.23 8.25-8.23 2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 0 1 2.41 5.82c0 4.54-3.69 8.24-8.23 8.24Zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.13-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.14-1.18-.06-.11-.22-.17-.47-.29Z" />
              </svg>
              WhatsApp Concierge
            </LandingButton>

            <LandingButton :href="visitMailto" size="lg" block class="sm:w-auto">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M11.3333 14.6667V13.3333H13.3333V6.66667H2.66667V8H1.33333V6C1.33333 5.63333 1.46389 5.31944 1.725 5.05833C1.98611 4.79722 2.3 4.66667 2.66667 4.66667H5.33333V1.33333C5.33333 0.966667 5.46389 0.652778 5.725 0.391667C5.98611 0.130556 6.3 0 6.66667 0H9.33333C9.7 0 10.0139 0.130556 10.275 0.391667C10.5361 0.652778 10.6667 0.966667 10.6667 1.33333V4.66667H13.3333C13.7 4.66667 14.0139 4.79722 14.275 5.05833C14.5361 5.31944 14.6667 5.63333 14.6667 6V13.3333C14.6667 13.7 14.5361 14.0139 14.275 14.275C14.0139 14.5361 13.7 14.6667 13.3333 14.6667H11.3333ZM6.66667 4.66667H9.33333V1.33333H6.66667V4.66667ZM4 16C3.63333 16 3.31944 15.8694 3.05833 15.6083C2.79722 15.3472 2.66667 15.0333 2.66667 14.6667V9.33333H1.33333V8H9.33333V9.33333H8V14.6667C8 15.0333 7.86944 15.3472 7.60833 15.6083C7.34722 15.8694 7.03333 16 6.66667 16H4Z" />
              </svg>
              Agendar Visita
            </LandingButton>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { IconMessage } from '@/components/icons'
import LandingButton from './ui/LandingButton.vue'

const whatsappUrl =
  'https://wa.me/525512345678?text=' +
  encodeURIComponent('Hola, me gustaría conocer más sobre Britania Club.')

const visitMailto =
  'mailto:admisiones@britaniaclub.mx?subject=' + encodeURIComponent('Agendar visita a Britania Club')
</script>
