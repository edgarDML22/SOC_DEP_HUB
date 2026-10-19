<script setup>
/**
 * AppMultiSelect — selección múltiple homogénea: lista desplegable con buscador y casillas,
 * y la selección como chips removibles debajo del campo (no se corta cuando hay muchos).
 *
 * Slots: option ({ option }) para personalizar cada fila; chip-icon ({ option }) para el ícono del chip.
 */
import { computed } from 'vue';
import MultiSelect from 'primevue/multiselect';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  options: { type: Array, default: () => [] },
  optionLabel: { type: String, default: 'label' },
  optionValue: { type: String, default: 'value' },
  placeholder: { type: String, default: 'Seleccionar' },
  itemsLabel: { type: String, default: 'seleccionados' },
  invalid: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const selectedOptions = computed(() =>
  props.options.filter((o) => props.modelValue.includes(o[props.optionValue]))
);

const remove = (value) => emit('update:modelValue', props.modelValue.filter((v) => v !== value));
</script>

<template>
  <div class="flex flex-col gap-2.5">
    <MultiSelect
      v-bind="$attrs"
      :model-value="modelValue"
      :options="options"
      :option-label="optionLabel"
      :option-value="optionValue"
      :placeholder="loading ? 'Cargando…' : placeholder"
      :loading="loading"
      :invalid="invalid"
      :max-selected-labels="0"
      :selected-items-label="`{0} ${itemsLabel}`"
      filter
      filter-placeholder="Buscar…"
      :show-toggle-all="false"
      scroll-height="16rem"
      append-to="body"
      fluid
      class="app-field"
      @update:model-value="emit('update:modelValue', $event)"
    >
      <template v-if="$slots.option" #option="slotProps">
        <slot name="option" v-bind="slotProps" />
      </template>
    </MultiSelect>

    <TransitionGroup
      v-if="selectedOptions.length"
      tag="div"
      class="flex flex-wrap gap-1.5"
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 scale-90"
      leave-active-class="transition duration-100 ease-in"
      leave-to-class="opacity-0 scale-90"
    >
      <span
        v-for="option in selectedOptions"
        :key="option[optionValue]"
        class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 py-1 pl-2.5 pr-1 text-xs font-semibold text-primary-700 ring-1 ring-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/20"
      >
        <slot name="chip-icon" :option="option" />
        {{ option[optionLabel] }}
        <button
          type="button"
          class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-full text-primary-500 transition-colors hover:bg-primary-100 hover:text-primary-800 dark:hover:bg-primary-500/20"
          :aria-label="`Quitar ${option[optionLabel]}`"
          @click="remove(option[optionValue])"
        >
          <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
            <path d="M18 6 6 18M6 6l12 12" />
          </svg>
        </button>
      </span>
    </TransitionGroup>
  </div>
</template>
