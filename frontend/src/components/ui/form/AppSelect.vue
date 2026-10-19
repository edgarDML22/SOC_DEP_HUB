<script setup>
/**
 * AppSelect — lista desplegable homogénea (PrimeVue Select con el tema del proyecto).
 * Las opciones son objetos { label, value } por defecto; activa el buscador
 * automáticamente cuando hay muchas opciones.
 */
import { computed } from 'vue';
import Select from 'primevue/select';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  modelValue: { type: [String, Number, Boolean, Object, null], default: null },
  options: { type: Array, default: () => [] },
  optionLabel: { type: String, default: 'label' },
  optionValue: { type: String, default: 'value' },
  placeholder: { type: String, default: 'Seleccionar' },
  invalid: { type: Boolean, default: false },
  filter: { type: Boolean, default: undefined },
});

defineEmits(['update:modelValue']);

const useFilter = computed(() => props.filter ?? props.options.length > 8);
</script>

<template>
  <Select
    v-bind="$attrs"
    :model-value="modelValue"
    :options="options"
    :option-label="optionLabel"
    :option-value="optionValue"
    :placeholder="placeholder"
    :invalid="invalid"
    :filter="useFilter"
    filter-placeholder="Buscar…"
    append-to="body"
    fluid
    class="app-field"
    @update:model-value="$emit('update:modelValue', $event)"
  />
</template>
