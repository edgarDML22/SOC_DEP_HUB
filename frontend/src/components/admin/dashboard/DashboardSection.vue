<script setup>
import { computed } from 'vue';
import { toneOf } from '@/components/ui/tones';

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  icon: { type: [Object, Function], default: null },
  tone: { type: String, default: 'blue' },
});

const t = computed(() => toneOf(props.tone));
</script>

<template>
  <section class="rounded-3xl bg-white p-4 shadow-[0_16px_32px_-24px_rgba(15,23,42,0.35)] ring-1 ring-surface-200/70 sm:p-6">
    <header class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex min-w-0 items-start gap-3">
        <span
          v-if="icon"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white"
          :class="[t.solid, t.shadow]"
        >
          <component :is="icon" class="h-5 w-5" />
        </span>
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h2 class="m-0 text-base font-bold text-surface-900 sm:text-lg">{{ title }}</h2>
            <slot name="badge" />
          </div>
          <p v-if="subtitle" class="m-0 mt-0.5 text-xs font-medium text-surface-600">{{ subtitle }}</p>
        </div>
      </div>
      <slot name="actions" />
    </header>
    <slot />
  </section>
</template>
