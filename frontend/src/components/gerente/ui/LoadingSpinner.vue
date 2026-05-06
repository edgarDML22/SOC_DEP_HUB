<script setup>
import { computed } from 'vue'

const props = defineProps({
  size: { type: String, default: 'md' }, // sm, md, lg, xl
  color: { type: String, default: 'primary' } // primary, white, slate, danger, success
})

const spinnerClasses = computed(() => {
  const sizes = {
    sm: 'w-4 h-4 border-2',
    md: 'w-8 h-8 border-[3px]',
    lg: 'w-12 h-12 border-4',
    xl: 'w-14 h-14 border-4',
  }
  
  const colors = {
    primary: 'border-surface-200 border-t-primary-600',
    white: 'border-white/30 border-t-white',
    slate: 'border-slate-200 border-t-slate-600',
    danger: 'border-red-200 border-t-red-600',
    success: 'border-green-200 border-t-green-600',
  }

  return `${sizes[props.size] || sizes.md} ${colors[props.color] || colors.primary}`
})
</script>

<template>
  <div class="rounded-full animate-spin shrink-0 mx-auto" :class="spinnerClasses"></div>
</template>
