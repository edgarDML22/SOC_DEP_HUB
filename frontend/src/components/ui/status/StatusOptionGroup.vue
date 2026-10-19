<script setup>
/**
 * StatusOptionGroup — opciones de estatus como tarjetas seleccionables (homogéneo en todo el panel).
 *
 * options: [{ value, label, description?, icon?, tone }]  tone: emerald | amber | rose | blue | violet
 * current: valor del estatus vigente (se marca con la etiqueta "Actual")
 */
import { toneOf } from '@/components/ui/tones';

defineProps({
  modelValue: { type: [String, Number, null], default: null },
  options: { type: Array, required: true },
  current: { type: [String, Number, null], default: null },
  label: { type: String, default: 'Estatus' },
});

const emit = defineEmits(['update:modelValue']);

const RING = {
  emerald: 'border-emerald-500 bg-emerald-50/70 ring-4 ring-emerald-100 dark:bg-emerald-500/10 dark:ring-emerald-500/15',
  amber: 'border-amber-500 bg-amber-50/70 ring-4 ring-amber-100 dark:bg-amber-500/10 dark:ring-amber-500/15',
  rose: 'border-rose-500 bg-rose-50/70 ring-4 ring-rose-100 dark:bg-rose-500/10 dark:ring-rose-500/15',
  blue: 'border-primary-500 bg-primary-50/70 ring-4 ring-primary-100 dark:bg-primary-500/10 dark:ring-primary-500/15',
  violet: 'border-violet-500 bg-violet-50/70 ring-4 ring-violet-100 dark:bg-violet-500/10 dark:ring-violet-500/15',
};
const DOT = { emerald: 'bg-emerald-500', amber: 'bg-amber-500', rose: 'bg-rose-500', blue: 'bg-primary-600', violet: 'bg-violet-500' };
</script>

<template>
  <div role="radiogroup" :aria-label="label" class="flex flex-col gap-2.5">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      role="radio"
      :aria-checked="modelValue === option.value"
      class="group flex w-full cursor-pointer items-center gap-3.5 rounded-2xl border-2 bg-white p-3.5 text-left transition-all duration-150 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-100 sm:gap-4 sm:p-4"
      :class="modelValue === option.value ? RING[option.tone] || RING.blue : 'border-surface-200 hover:border-surface-300 hover:bg-surface-50'"
      @click="emit('update:modelValue', option.value)"
    >
      <span
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-colors"
        :class="modelValue === option.value
          ? [toneOf(option.tone).solid, toneOf(option.tone).shadow, 'text-white']
          : [toneOf(option.tone).soft, toneOf(option.tone).text]"
      >
        <component :is="option.icon" v-if="option.icon" class="h-5 w-5" />
        <span v-else class="h-2.5 w-2.5 rounded-full" :class="modelValue === option.value ? 'bg-white' : DOT[option.tone]"></span>
      </span>

      <span class="min-w-0 flex-1">
        <span class="flex flex-wrap items-center gap-2">
          <span class="text-sm font-bold text-surface-900 sm:text-[15px]">{{ option.label }}</span>
          <span
            v-if="current === option.value"
            class="rounded-full bg-surface-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-surface-700"
          >
            Actual
          </span>
        </span>
        <span v-if="option.description" class="mt-0.5 block text-xs font-medium leading-snug text-surface-600 sm:text-[13px]">
          {{ option.description }}
        </span>
      </span>

      <span
        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
        :class="modelValue === option.value ? [DOT[option.tone], 'border-transparent'] : 'border-surface-300'"
        aria-hidden="true"
      >
        <span v-if="modelValue === option.value" class="h-1.5 w-1.5 rounded-full bg-white"></span>
      </span>
    </button>
  </div>
</template>
