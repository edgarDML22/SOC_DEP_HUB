<script setup>
/**
 * AppTimePicker — selector de hora homogéneo (PrimeVue DatePicker en modo hora, 24 h).
 * v-model: string 'HH:MM:SS' (formato que guarda el backend); acepta 'HH:MM' de entrada.
 */
import { computed } from 'vue';
import DatePicker from 'primevue/datepicker';
import { IconClock } from '@/components/icons';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '--:--' },
  stepMinute: { type: Number, default: 5 },
  invalid: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const pad = (n) => String(n).padStart(2, '0');

const value = computed({
  get: () => {
    if (!props.modelValue) return null;
    const [h, m] = String(props.modelValue).split(':').map(Number);
    if (Number.isNaN(h) || Number.isNaN(m)) return null;
    const date = new Date();
    date.setHours(h, m, 0, 0);
    return date;
  },
  set: (date) => emit('update:modelValue', date ? `${pad(date.getHours())}:${pad(date.getMinutes())}:00` : ''),
});
</script>

<template>
  <DatePicker
    v-bind="$attrs"
    v-model="value"
    time-only
    hour-format="24"
    :step-minute="stepMinute"
    :placeholder="placeholder"
    :invalid="invalid"
    :manual-input="false"
    show-icon
    icon-display="input"
    append-to="body"
    fluid
    class="app-field"
  >
    <template #inputicon="{ clickCallback }">
      <IconClock class="h-4 w-4 cursor-pointer text-surface-500" @click="clickCallback" />
    </template>
  </DatePicker>
</template>
