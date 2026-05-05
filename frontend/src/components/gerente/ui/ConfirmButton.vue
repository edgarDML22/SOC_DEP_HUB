<script setup>
import LoadingSpinner from './LoadingSpinner.vue'

defineProps({
  label: { type: String, required: true },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  type: { type: String, default: 'button' }
})

defineEmits(['click'])
</script>

<template>
  <button
    @click="$emit('click')"
    :disabled="disabled || loading"
    class="px-7 py-3 rounded-xl bg-primary-600 text-white text-sm font-black
           hover:bg-primary-700 active:bg-primary-800 transition-colors shadow-sm
           disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
    :type="type"
  >
    <LoadingSpinner v-if="loading" size="sm" color="white" />
    <slot v-else name="icon" />
    <span>{{ label }}</span>
  </button>
</template>
