<script setup>
/**
 * AppInput — campo de texto homogéneo (text, email, tel, number…), con ícono opcional.
 * Usa 16px en móvil para que iOS no haga zoom al enfocar.
 */
defineOptions({ inheritAttrs: false });

defineProps({
  modelValue: { type: [String, Number], default: '' },
  icon: { type: [Object, Function], default: null },
  invalid: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
  <div class="relative">
    <component
      :is="icon"
      v-if="icon"
      class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-surface-500"
    />
    <input
      v-bind="$attrs"
      :value="modelValue"
      :aria-invalid="invalid || undefined"
      class="h-11 w-full rounded-xl border bg-white px-3.5 text-base font-medium text-surface-900 transition-[border-color,box-shadow] placeholder:font-normal placeholder:text-surface-400 focus:outline-none focus:ring-4 read-only:cursor-not-allowed read-only:bg-surface-100 read-only:text-surface-600 disabled:cursor-not-allowed disabled:bg-surface-100 disabled:text-surface-600 sm:text-sm"
      :class="[
        icon ? 'pl-10' : '',
        invalid
          ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-100 dark:focus:ring-rose-500/20'
          : 'border-surface-200 hover:border-surface-300 focus:border-primary-500 focus:ring-primary-100 dark:focus:ring-primary-500/20',
      ]"
      @input="$emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>
