<script setup>
import { computed } from 'vue'
import { IconChevronDown } from '@/components/icons'

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean],
    default: null
  },
  label: {
    type: String,
    required: true
  },
  options: {
    type: Array,
    required: true,
  },
  id: {
    type: String,
    default: () => `filter-select-${Math.random().toString(36).substr(2, 9)}`
  }
})

const emit = defineEmits(['update:modelValue'])

const selected = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})
</script>

<template>
  <div class="flex flex-col gap-1.5">
    <label :for="id" class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
      {{ label }}
    </label>
    <div class="relative">
      <!-- Slot for internal left icon -->
      <div v-if="$slots.icon" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none flex items-center justify-center">
        <slot name="icon"></slot>
      </div>
      
      <select 
        :id="id"
        v-model="selected"
        class="w-full pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer"
        :class="$slots.icon ? 'pl-10' : 'pl-4'"
      >
        <option v-for="opt in options" :key="opt.value" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
      
      <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
    </div>
  </div>
</template>
