<script setup>
/**
 * AppConfirmModal — confirmación homogénea (eliminar, reactivar, activar/desactivar…).
 *
 * Props: modelValue, title, message, icon, tone (blue | rose | amber | emerald),
 *        confirmLabel, cancelLabel, loading, error (mensaje en línea si la acción falla)
 * Slot default: reemplaza el mensaje cuando se necesita texto con formato.
 * Emite: confirm, cancel, update:modelValue
 */
import AppModal from './AppModal.vue';
import AppButton from '@/components/ui/AppButton.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, required: true },
  message: { type: String, default: '' },
  icon: { type: [Object, Function], default: null },
  tone: { type: String, default: 'blue' },
  confirmLabel: { type: String, default: 'Confirmar' },
  cancelLabel: { type: String, default: 'Cancelar' },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel']);

const cancel = () => {
  emit('update:modelValue', false);
  emit('cancel');
};
</script>

<template>
  <AppModal
    :model-value="modelValue"
    :title="title"
    :icon="icon"
    :tone="tone"
    size="sm"
    :persistent="loading"
    @update:model-value="(v) => !v && cancel()"
  >
    <div class="text-[15px] leading-relaxed text-surface-700">
      <slot>{{ message }}</slot>
    </div>

    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-1"
    >
      <div
        v-if="error"
        class="mt-4 flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30"
        role="alert"
      >
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="12" cy="12" r="10" /><path d="M12 8v4M12 16h.01" />
        </svg>
        {{ error }}
      </div>
    </Transition>

    <template #footer>
      <AppButton variant="secondary" :disabled="loading" @click="cancel">{{ cancelLabel }}</AppButton>
      <AppButton :tone="props.tone" :loading="loading" @click="emit('confirm')">{{ confirmLabel }}</AppButton>
    </template>
  </AppModal>
</template>
