<script setup>
/**
 * AppDatePicker — calendario homogéneo (PrimeVue DatePicker en español).
 * El v-model es un string 'YYYY-MM-DD' (formato que espera el backend);
 * se muestra como dd/mm/aaaa.
 */
import { computed } from 'vue';
import DatePicker from 'primevue/datepicker';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'dd/mm/aaaa' },
  minDate: { type: [String, Date], default: null },
  maxDate: { type: [String, Date], default: null },
  invalid: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

// 'YYYY-MM-DD' -> Date local (new Date('YYYY-MM-DD') la interpreta en UTC y resta un día en México)
const toDate = (value) => {
  if (!value) return null;
  if (value instanceof Date) return value;
  const [y, m, d] = String(value).slice(0, 10).split('-').map(Number);
  return y && m && d ? new Date(y, m - 1, d) : null;
};

const toIso = (date) => {
  if (!date) return '';
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};

const value = computed({
  get: () => toDate(props.modelValue),
  set: (date) => emit('update:modelValue', toIso(date)),
});
</script>

<template>
  <DatePicker
    v-bind="$attrs"
    v-model="value"
    :placeholder="placeholder"
    :min-date="toDate(minDate)"
    :max-date="toDate(maxDate)"
    :invalid="invalid"
    :manual-input="false"
    date-format="dd/mm/yy"
    show-icon
    icon-display="input"
    append-to="body"
    fluid
    class="app-field"
  />
</template>
