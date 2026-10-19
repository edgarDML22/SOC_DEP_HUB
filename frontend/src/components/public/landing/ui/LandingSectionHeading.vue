<template>
  <div
    class="flex max-w-3xl flex-col gap-3"
    :class="align === 'center' ? 'mx-auto items-center text-center' : 'items-start text-left'"
  >
    <span
      class="lp-display inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.16em] text-primary-700 ring-1 ring-primary-100"
    >
      <span class="h-1.5 w-1.5 rounded-full bg-sky-500" aria-hidden="true" />
      {{ eyebrow }}
    </span>
    <h2
      class="lp-display text-[1.875rem] font-bold leading-[1.1] tracking-[-0.03em] text-lp-ink sm:text-4xl lg:text-5xl"
    >
      <slot name="title">{{ title }}</slot>
    </h2>
    <p v-if="description" class="lp-body max-w-2xl text-base leading-relaxed text-lp-body sm:text-lg">
      {{ description }}
    </p>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    eyebrow: string
    title?: string
    description?: string
    align?: 'center' | 'left'
  }>(),
  { align: 'center' }
)
</script>
