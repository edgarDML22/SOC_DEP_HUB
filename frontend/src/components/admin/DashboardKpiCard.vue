<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], default: 0 },
  unit: { type: String, default: '' },
  icon: { type: [Object, Function], default: null },
  tone: { type: String, default: 'blue' }, // blue | amber | emerald | red
  trend: { type: Object, default: null }, // { text, direction: 'up' | 'down' | 'flat' }
  badge: { type: String, default: '' },
  badgeTone: { type: String, default: 'red' },
  footnote: { type: String, default: '' },
  footnoteTone: { type: String, default: 'muted' }, // muted | success | danger | warning
  loading: { type: Boolean, default: false },
});

const toneMap = {
  blue: 'bg-primary-50 text-primary-600 border-primary-100',
  amber: 'bg-amber-50 text-amber-600 border-amber-100',
  emerald: 'bg-emerald-50 text-emerald-600 border-emerald-100',
  red: 'bg-red-50 text-red-600 border-red-100',
};

const iconClasses = computed(() => toneMap[props.tone] || toneMap.blue);
const valueClasses = computed(() =>
  props.tone === 'red' ? 'text-red-600' : 'text-surface-900'
);

const badgeClasses = computed(() => ({
  red: 'bg-red-50 text-red-700 border-red-200',
  amber: 'bg-amber-50 text-amber-700 border-amber-200',
  emerald: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  blue: 'bg-primary-50 text-primary-700 border-primary-200',
}[props.badgeTone] || 'bg-red-50 text-red-700 border-red-200'));

const footnoteClasses = computed(() => ({
  muted: 'text-surface-600',
  success: 'text-emerald-700',
  danger: 'text-red-700',
  warning: 'text-amber-700',
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
  up: 'text-emerald-700 bg-emerald-50 border-emerald-200',
  down: 'text-red-700 bg-red-50 border-red-200',
  flat: 'text-surface-600 bg-surface-100 border-surface-200',
}[props.trend?.direction] || 'text-surface-600 bg-surface-100 border-surface-200'));
</script>

<template>
  <div class="relative flex flex-col bg-white border border-surface-200 rounded-2xl shadow-sm p-5 min-h-[152px]">
    <template v-if="loading">
      <div class="flex items-start justify-between gap-3">
        <div class="h-3 w-24 rounded-full bg-surface-200 animate-pulse"></div>
        <div class="w-11 h-11 rounded-xl bg-surface-200 animate-pulse"></div>
      </div>
      <div class="mt-4 h-8 w-20 rounded-lg bg-surface-200 animate-pulse"></div>
      <div class="mt-auto pt-4 h-3 w-28 rounded-full bg-surface-100 animate-pulse"></div>
    </template>

    <template v-else>
      <div class="flex items-start justify-between gap-3">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-surface-500 leading-tight pt-1">
          {{ label }}
        </p>
        <div
          class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border"
          :class="iconClasses"
        >
          <component :is="icon" v-if="icon" class="w-5 h-5" />
        </div>
      </div>

      <div class="mt-3 flex items-baseline gap-2 flex-wrap">
        <span class="text-3xl font-bold tabular-nums leading-none" :class="valueClasses">
          {{ displayValue }}
        </span>
        <span v-if="unit" class="text-[11px] font-semibold text-surface-500">{{ unit }}</span>
      </div>

      <div v-if="trend || badge" class="mt-2 flex items-center gap-2 flex-wrap">
        <span
          v-if="trend"
          class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md border text-[10px] font-bold"
          :class="trendClasses"
        >
          {{ trend.direction === 'up' ? '↑' : trend.direction === 'down' ? '↓' : '→' }}
          {{ trend.text }}
        </span>
        <span
          v-if="badge"
          class="inline-flex items-center px-2 py-0.5 rounded-md border text-[10px] font-bold uppercase tracking-wide"
          :class="badgeClasses"
        >
          {{ badge }}
        </span>
      </div>

      <p
        v-if="footnote"
        class="mt-auto pt-3 text-[11px] font-semibold leading-tight"
        :class="footnoteClasses"
      >
        {{ footnote }}
      </p>
    </template>
  </div>
</template>
