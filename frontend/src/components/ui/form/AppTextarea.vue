<script setup>
/**
 * AppTextarea — área de texto homogénea (mismo estilo que AppInput).
 */
defineOptions({ inheritAttrs: false });

defineProps({
  modelValue: { type: String, default: '' },
  rows: { type: [Number, String], default: 4 },
  invalid: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
  <textarea
    v-bind="$attrs"
    :value="modelValue"
    :rows="rows"
    :aria-invalid="invalid || undefined"
    class="w-full resize-none rounded-xl border bg-white px-3.5 py-3 text-base font-medium leading-relaxed text-surface-900 transition-[border-color,box-shadow] placeholder:font-normal placeholder:text-surface-400 focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:bg-surface-100 sm:text-sm"
    :class="invalid
      ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-100 dark:focus:ring-rose-500/20'
      : 'border-surface-200 hover:border-surface-300 focus:border-primary-500 focus:ring-primary-100 dark:focus:ring-primary-500/20'"
    @input="$emit('update:modelValue', $event.target.value)"
  ></textarea>
</template>
