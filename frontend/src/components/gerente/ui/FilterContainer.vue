<script setup>
const props = defineProps({
  hasActiveFilters: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['clear'])
</script>

<template>
  <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5 space-y-4">
    <!-- Search Input -->
    <slot name="search"></slot>
    
    <!-- Filter Selects Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
      <slot></slot>
    </div>

    <!-- Clear Filters Action -->
    <Transition 
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-1" 
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-150 ease-in" 
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-1"
    >
      <div v-if="hasActiveFilters" class="flex justify-end">
        <button @click="emit('clear')"
          class="text-xs font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1.5 transition-colors">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12" />
          </svg>
          Limpiar filtros
        </button>
      </div>
    </Transition>
  </div>
</template>
