<script setup>
import { computed } from 'vue';
import { IconChevronRight } from '@/components/icons';
import { toneOf } from '@/components/ui/tones';

const props = defineProps({
  title: { type: String, required: true },
  icon: { type: [Object, Function], default: null },
  tone: { type: String, default: 'blue' },
  to: { type: String, required: true },
  linkLabel: { type: String, required: true },
});

const t = computed(() => toneOf(props.tone));
</script>

<template>
  <article class="flex flex-col overflow-hidden rounded-3xl bg-white shadow-[0_16px_32px_-24px_rgba(15,23,42,0.35)] ring-1 ring-surface-200/70">
    <header class="flex items-center justify-between gap-2 px-5 py-4" :class="t.soft">
      <div class="flex min-w-0 items-center gap-2.5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white" :class="[t.solid, t.shadow]">
          <component :is="icon" v-if="icon" class="h-4 w-4" />
        </span>
        <h3 class="m-0 line-clamp-2 text-sm font-bold leading-tight text-surface-900">{{ title }}</h3>
      </div>
      <slot name="badge" />
    </header>

    <div class="flex flex-1 flex-col p-5">
      <slot />
    </div>

    <RouterLink
      :to="to"
      class="mx-5 mb-5 inline-flex items-center justify-between rounded-xl px-4 py-2.5 text-xs font-bold transition-all hover:gap-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
      :class="[t.soft, t.text]"
    >
      {{ linkLabel }}
      <IconChevronRight class="h-4 w-4" />
    </RouterLink>
  </article>
</template>
