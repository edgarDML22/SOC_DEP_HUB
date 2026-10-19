<script setup>
/**
 * AppStepper — indicador de pasos para modals con varios pasos.
 * steps: ['Situación', 'Clases', 'Revisar']   current: número de paso actual (empieza en 1)
 */
defineProps({
  steps: { type: Array, required: true },
  current: { type: Number, default: 1 },
});
</script>

<template>
  <ol class="m-0 flex list-none items-start p-0" aria-label="Progreso">
    <li
      v-for="(step, index) in steps"
      :key="step"
      class="flex items-start"
      :class="index < steps.length - 1 ? 'flex-1' : ''"
      :aria-current="current === index + 1 ? 'step' : undefined"
    >
      <div class="flex flex-col items-center gap-1.5">
        <span
          class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition-all duration-300"
          :class="current > index + 1
            ? 'bg-primary-600 text-white'
            : current === index + 1
              ? 'bg-primary-600 text-white ring-4 ring-primary-100 dark:ring-primary-500/20'
              : 'bg-surface-100 text-surface-500 ring-1 ring-inset ring-surface-200'"
        >
          <svg v-if="current > index + 1" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6 9 17l-5-5" />
          </svg>
          <template v-else>{{ index + 1 }}</template>
        </span>
        <span
          class="whitespace-nowrap text-[11px] font-bold"
          :class="current >= index + 1 ? 'text-primary-700 dark:text-primary-300' : 'text-surface-500'"
        >
          {{ step }}
        </span>
      </div>
      <span
        v-if="index < steps.length - 1"
        class="mx-2 mt-4 h-0.5 flex-1 rounded-full transition-colors duration-300"
        :class="current > index + 1 ? 'bg-primary-600' : 'bg-surface-200'"
        aria-hidden="true"
      ></span>
    </li>
  </ol>
</template>
