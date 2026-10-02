<script setup>
import { computed } from 'vue';

/**
 * Barra de paginación sincronizada con el estado de Pinia.
 *
 * Recibe el objeto de paginación del store ({ currentPage, lastPage, total, perPage })
 * y emite `change` con la página destino. Los controles se deshabilitan en los
 * extremos: "Anterior" en la página 1 y "Siguiente" en la última página.
 */
const props = defineProps({
  pagination: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  itemLabel: {
    type: String,
    default: 'resultados',
  },
});

const emit = defineEmits(['change']);

const currentPage = computed(() => Math.max(1, Number(props.pagination?.currentPage) || 1));
const lastPage = computed(() => Math.max(1, Number(props.pagination?.lastPage) || 1));
const perPage = computed(() => Math.max(1, Number(props.pagination?.perPage) || 1));
const total = computed(() => Math.max(0, Number(props.pagination?.total) || 0));

const hasPrev = computed(() => currentPage.value > 1);
const hasNext = computed(() => currentPage.value < lastPage.value);

const rangeStart = computed(() => (total.value ? (currentPage.value - 1) * perPage.value + 1 : 0));
const rangeEnd = computed(() =>
  total.value ? Math.min(currentPage.value * perPage.value, total.value) : 0
);

/** Valida los límites antes de emitir para evitar saltos fuera de rango. */
const goTo = (page) => {
  if (props.loading) return;
  if (!Number.isFinite(page) || page < 1 || page > lastPage.value) return;
  if (page === currentPage.value) return;
  emit('change', page);
};
</script>

<template>
  <nav
    class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white border border-surface-100 rounded-2xl px-4 md:px-5 py-3.5 shadow-sm"
    aria-label="Paginación de torneos"
  >
    <!-- Resumen del rango visible -->
    <p class="text-xs font-bold text-surface-500 tabular-nums text-center sm:text-left">
      <template v-if="total > 0">
        Mostrando <span class="text-surface-700">{{ rangeStart }}–{{ rangeEnd }}</span> de
        <span class="text-surface-700">{{ total }}</span> {{ itemLabel }}
      </template>
      <template v-else> Sin {{ itemLabel }} por mostrar </template>
    </p>

    <div class="flex items-center gap-2">
      <button
        type="button"
        :disabled="!hasPrev || loading"
        @click="goTo(currentPage - 1)"
        class="flex items-center gap-1.5 h-10 px-3 md:px-4 bg-white border border-surface-200 rounded-xl text-xs md:text-sm font-extrabold text-surface-600 hover:bg-surface-50 hover:text-surface-900 active:scale-[0.97] transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-surface-600 shadow-xs cursor-pointer"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="w-4 h-4 shrink-0"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
        <span class="hidden sm:inline">Anterior</span>
      </button>

      <span
        class="px-3 h-10 flex items-center text-xs md:text-sm font-extrabold text-surface-700 bg-surface-50 border border-surface-100 rounded-xl tabular-nums whitespace-nowrap"
      >
        Pág. {{ currentPage }} / {{ lastPage }}
      </span>

      <button
        type="button"
        :disabled="!hasNext || loading"
        @click="goTo(currentPage + 1)"
        class="flex items-center gap-1.5 h-10 px-3 md:px-4 bg-white border border-surface-200 rounded-xl text-xs md:text-sm font-extrabold text-surface-600 hover:bg-surface-50 hover:text-surface-900 active:scale-[0.97] transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:text-surface-600 shadow-xs cursor-pointer"
      >
        <span class="hidden sm:inline">Siguiente</span>
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="w-4 h-4 shrink-0"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M8.25 4.5l7.5 7.5-7.5 7.5" />
        </svg>
      </button>
    </div>
  </nav>
</template>
