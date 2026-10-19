<script setup>
/**
 * AppButton — botón homogéneo para modals y formularios.
 *
 * variant: primary (sólido del tono) | secondary (blanco con borde) | soft (fondo suave del tono)
 * tone   : blue | emerald | amber | rose | violet
 * size   : md (44px) | sm (36px)
 */
import { computed } from 'vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';

const props = defineProps({
  variant: { type: String, default: 'primary' },
  tone: { type: String, default: 'blue' },
  size: { type: String, default: 'md' },
  type: { type: String, default: 'button' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

const SOLID = {
  blue: 'bg-primary-600 text-white hover:bg-primary-700 shadow-[0_10px_20px_-12px_rgba(29,78,216,0.9)] focus-visible:ring-primary-200',
  emerald: 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-[0_10px_20px_-12px_rgba(5,150,105,0.9)] focus-visible:ring-emerald-200',
  amber: 'bg-amber-500 text-white hover:bg-amber-600 shadow-[0_10px_20px_-12px_rgba(217,119,6,0.9)] focus-visible:ring-amber-200',
  rose: 'bg-rose-600 text-white hover:bg-rose-700 shadow-[0_10px_20px_-12px_rgba(225,29,72,0.9)] focus-visible:ring-rose-200',
  violet: 'bg-violet-600 text-white hover:bg-violet-700 shadow-[0_10px_20px_-12px_rgba(109,40,217,0.9)] focus-visible:ring-violet-200',
};

const SOFT = {
  blue: 'bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300 focus-visible:ring-primary-200',
  emerald: 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 focus-visible:ring-emerald-200',
  amber: 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-300 focus-visible:ring-amber-200',
  rose: 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300 focus-visible:ring-rose-200',
  violet: 'bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-500/10 dark:text-violet-300 focus-visible:ring-violet-200',
};

const classes = computed(() => {
  if (props.variant === 'secondary') {
    return 'bg-white text-surface-800 ring-1 ring-inset ring-surface-200 hover:bg-surface-50 focus-visible:ring-2 focus-visible:ring-primary-300';
  }
  if (props.variant === 'soft') return SOFT[props.tone] || SOFT.blue;
  return SOLID[props.tone] || SOLID.blue;
});
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex cursor-pointer select-none items-center justify-center gap-2 rounded-xl px-5 font-bold transition-all duration-150 focus-visible:outline-none focus-visible:ring-4 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100"
    :class="[classes, size === 'sm' ? 'h-9 text-xs' : 'h-11 text-sm']"
  >
    <LoadingSpinner
      v-if="loading"
      size="sm"
      :color="variant === 'primary' ? 'white' : 'slate'"
      class="!mx-0"
    />
    <slot v-else name="icon" />
    <slot />
  </button>
</template>
