<script setup>
import { computed } from 'vue'
import { IconSearch } from '@/components/icons'

const props = defineProps({
    modelValue: {
        type: String,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Buscar...'
    }
})

const emit = defineEmits(['update:modelValue'])

const search = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})
</script>

<template>
    <div class="relative">
        <IconSearch class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
        <input v-model="search" :placeholder="placeholder"
            class="w-full pl-11 pr-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-sm
                   font-medium text-surface-900 placeholder:text-surface-400
                   focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all" />
    </div>
</template>