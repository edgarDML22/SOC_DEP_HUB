<script setup>
/**
 * QrCodeModal — modal homogéneo para mostrar el QR de acceso de familiares, invitados, etc.
 */
import AppModal from '@/components/ui/modal/AppModal.vue';
import AppButton from '@/components/ui/AppButton.vue';
import QrCodeDisplay from './QrCodeDisplay.vue';
import { IconQr } from '@/components/icons';

defineProps({
  modelValue: { type: Boolean, default: false },
  value: { type: String, default: '' },
  name: { type: String, default: '' },
  caption: { type: String, default: '' },
  title: { type: String, default: 'Código de acceso' },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
  <AppModal
    :model-value="modelValue"
    :title="title"
    subtitle="Pase digital para la entrada del club"
    :icon="IconQr"
    size="sm"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="pt-1">
      <QrCodeDisplay :value="value" :name="name" :caption="caption" />
    </div>
    <template #footer>
      <AppButton variant="secondary" @click="emit('update:modelValue', false)">Cerrar</AppButton>
    </template>
  </AppModal>
</template>
