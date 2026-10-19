<script setup>
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';

defineProps({
  greeting: { type: String, required: true },
  firstName: { type: String, required: true },
  roleBadge: { type: String, default: '' },
  fechaLarga: { type: String, default: '' },
  sede: { type: String, default: '' },
  relativeReadText: { type: String, default: null },
  lastReadTime: { type: String, default: '' },
  loading: { type: Boolean, default: false },
});

defineEmits(['refresh']);
</script>

<template>
  <section class="relative rounded-3xl px-5 pb-20 pt-6 text-white sm:px-8 sm:pb-24 sm:pt-8">
    <!-- Fondo: se desvanece hacia abajo para que las KPIs se asienten sobre él.
         Va en una capa aparte para que la máscara no afecte al texto. -->
    <div
      class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl bg-gradient-to-br from-primary-700 via-primary-600 to-sky-500 [mask-image:linear-gradient(to_bottom,black_50%,transparent_100%)]"
      aria-hidden="true"
    >
      <div class="absolute -right-16 -top-24 h-72 w-72 rounded-full bg-white/10"></div>
      <div class="absolute -bottom-28 right-1/3 h-64 w-64 rounded-full bg-sky-300/25 blur-2xl"></div>
      <div class="absolute bottom-6 right-6 hidden h-24 w-24 rounded-full border-[10px] border-white/10 sm:block"></div>
    </div>

    <div class="relative flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
      <div class="min-w-0">
        <p class="m-0 text-xs font-semibold text-white/90 sm:text-sm">
          {{ fechaLarga }}<template v-if="sede"> · {{ sede }}</template>
        </p>
        <h1 class="m-0 mt-1.5 text-2xl font-bold leading-tight tracking-tight sm:text-[2rem]">
          {{ greeting }}, <span class="text-sky-100">{{ firstName }}</span>
        </h1>
        <span
          v-if="roleBadge"
          class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-wider ring-1 ring-white/25 backdrop-blur-sm"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
          {{ roleBadge }}
        </span>
      </div>

      <div class="flex items-center justify-between gap-3 md:justify-end">
        <div class="min-w-0 md:text-right" aria-live="polite">
          <p class="m-0 whitespace-nowrap text-[11px] font-bold uppercase tracking-wider text-white/85">Última lectura</p>
          <p class="m-0 text-xs font-bold text-white">
            <template v-if="relativeReadText">{{ relativeReadText }} <span class="font-semibold text-white/85">({{ lastReadTime }})</span></template>
            <template v-else>Sin lectura</template>
          </p>
        </div>
        <button
          type="button"
          :disabled="loading"
          class="inline-flex min-h-[44px] shrink-0 cursor-pointer items-center gap-2 rounded-xl bg-white/95 px-4 py-2 text-xs font-bold text-primary-700 shadow-[0_10px_20px_-12px_rgba(15,23,42,0.6)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-white focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-white/40 disabled:cursor-not-allowed disabled:opacity-70"
          @click="$emit('refresh')"
        >
          <LoadingSpinner v-if="loading" size="sm" />
          <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
          <template v-if="loading">Actualizando…</template>
          <template v-else>Actualizar<span class="hidden sm:inline"> datos</span></template>
        </button>
      </div>
    </div>
  </section>
</template>
