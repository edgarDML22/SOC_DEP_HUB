<script setup>
import { computed } from 'vue';
import { toneOf } from '@/components/ui/tones';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], default: 0 },
  unit: { type: String, default: '' },
  icon: { type: [Object, Function], default: null },
  tone: { type: String, default: 'blue' }, // blue | emerald | amber | rose | violet
  trend: { type: Object, default: null }, // { text, direction: 'up' | 'down' | 'flat' }
  badge: { type: String, default: '' },
  badgeTone: { type: String, default: 'rose' },
  footnote: { type: String, default: '' },
  footnoteTone: { type: String, default: 'muted' }, // muted | success | danger | warning
  loading: { type: Boolean, default: false },
});

const t = computed(() => toneOf(props.tone));
const badgeT = computed(() => toneOf(props.badgeTone === 'red' ? 'rose' : props.badgeTone));

const footnoteClasses = computed(() => ({
  muted: 'text-surface-600',
  success: 'text-emerald-600 dark:text-emerald-300',
  danger: 'text-rose-600 dark:text-rose-300',
  warning: 'text-amber-600 dark:text-amber-300',
}[props.footnoteTone] || 'text-surface-600'));

const displayValue = computed(() => {
  const v = props.value;
  if (v === null || v === undefined || v === '') return '0';
  if (typeof v === 'number') {
    if (Number.isNaN(v)) return '0';
    return new Intl.NumberFormat('es-MX').format(v);
  }
  return v;
});

const trendClasses = computed(() => ({
  up: 'text-emerald-700 bg-emerald-50 dark:bg-emerald-500/10 dark:text-emerald-300',
  down: 'text-rose-700 bg-rose-50 dark:bg-rose-500/10 dark:text-rose-300',
  flat: 'text-surface-600 bg-surface-100',
}[props.trend?.direction] || 'text-surface-600 bg-surface-100'));
</script>

<template>
  <div
    class="group relative flex min-h-[148px] flex-col overflow-hidden rounded-2xl bg-white p-4 shadow-[0_16px_32px_-20px_rgba(15,23,42,0.35)] ring-1 ring-surface-200/70 transition-transform duration-300 hover:-translate-y-0.5 sm:p-5"
  >
    <template v-if="loading">
      <div class="h-11 w-11 animate-pulse rounded-xl bg-surface-200"></div>
      <div class="mt-4 h-3 w-24 animate-pulse rounded-full bg-surface-200"></div>
      <div class="mt-3 h-8 w-16 animate-pulse rounded-lg bg-surface-200"></div>
    </template>

    <template v-else>
      <!-- Brillo de color en la esquina -->
      <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full blur-2xl" :class="t.glow" aria-hidden="true"></div>

      <div
        class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white"
        :class="[t.solid, t.shadow]"
      >
        <component :is="icon" v-if="icon" class="h-5 w-5" />
      </div>

      <p class="relative m-0 mt-3 text-[11px] font-bold uppercase leading-tight tracking-wider text-surface-600">
        {{ label }}
      </p>

      <div class="relative mt-1.5 flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
        <span class="text-[1.75rem] font-extrabold leading-none tabular-nums text-surface-900 sm:text-3xl">
          {{ displayValue }}
        </span>
        <span v-if="unit" class="text-xs font-semibold text-surface-600">{{ unit }}</span>
      </div>

      <div v-if="trend || badge" class="relative mt-2 flex flex-wrap items-center gap-2">
        <span
          v-if="trend"
          class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold"
          :class="trendClasses"
        >
          {{ trend.direction === 'up' ? '↑' : trend.direction === 'down' ? '↓' : '→' }}
          {{ trend.text }}
        </span>
        <span
          v-if="badge"
          class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide"
          :class="[badgeT.soft, badgeT.text]"
        >
          <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current motion-reduce:animate-none"></span>
          {{ badge }}
        </span>
      </div>

      <p v-if="footnote" class="relative m-0 mt-auto pt-3 text-xs font-semibold leading-snug" :class="footnoteClasses">
        {{ footnote }}
      </p>

      <!-- Acento inferior -->
      <div class="absolute inset-x-0 bottom-0 h-1 opacity-80" :class="t.bar" aria-hidden="true"></div>
    </template>
  </div>
</template>
