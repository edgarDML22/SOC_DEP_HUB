<script setup>
import AdminProfileModal from './ui/AdminProfileModal.vue'

defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <AdminProfileModal
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>
