<script setup>
import { computed } from 'vue'

const props = defineProps({
    search: {
        type: String,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Buscar...'
    }
})

const emit = defineEmits(['update:search'])

const search = computed({
    get: () => props.search,
    set: (value) => emit('update:search', value)
})
</script>

<template>
    <div class="relative">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
        </svg>
        <input v-model="search" :placeholder="placeholder"
            class="w-full pl-11 pr-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-sm
                   font-medium text-surface-900 placeholder:text-surface-400
                   focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all" />
    </div>
</template>