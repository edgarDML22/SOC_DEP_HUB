<script setup>
/**
 * AppField — etiqueta + control + ayuda/error con el mismo estilo en todos los formularios.
 * El control va en el slot default. `for` debe coincidir con el id del control.
 */
defineProps({
  label: { type: String, default: '' },
  for: { type: String, default: undefined },
  required: { type: Boolean, default: false },
  optional: { type: Boolean, default: false },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
});
</script>

<template>
  <div class="flex min-w-0 flex-col gap-1.5">
    <label v-if="label" :for="$props.for" class="flex items-center gap-1 text-[13px] font-semibold text-surface-800">
      {{ label }}
      <span v-if="required" class="text-rose-500" aria-hidden="true">*</span>
      <span v-else-if="optional" class="font-medium text-surface-500">(opcional)</span>
    </label>
    <slot />
    <p v-if="error" class="m-0 flex items-center gap-1 text-xs font-semibold text-rose-600 dark:text-rose-400" role="alert">
      <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <circle cx="12" cy="12" r="10" /><path d="M12 8v4M12 16h.01" />
      </svg>
      {{ error }}
    </p>
    <p v-else-if="hint" class="m-0 text-xs font-medium text-surface-500">{{ hint }}</p>
  </div>
</template>
