<script setup>
defineProps({
  label: { type: String, default: 'Cancelar' },
  disabled: { type: Boolean, default: false }
})

defineEmits(['click'])
</script>

<template>
  <button
    @click="$emit('click')"
    :disabled="disabled"
    class="px-6 py-3 rounded-xl border border-surface-200 bg-white flex items-center justify-center gap-2
           text-sm font-bold text-surface-700 hover:bg-surface-50 transition-colors
           disabled:opacity-40 disabled:cursor-not-allowed"
    type="button"
  >
    <slot name="icon" />
    <span>{{ label }}</span>
  </button>
</template>
