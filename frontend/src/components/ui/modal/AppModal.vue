<script setup>
/**
 * AppModal — caparazón único para todos los modals del panel.
 *
 * Props:
 *   modelValue : visible / oculto (v-model)
 *   title      : título del encabezado
 *   subtitle   : texto secundario bajo el título
 *   icon       : componente de ícono para la ficha de color
 *   tone       : blue | emerald | amber | rose | violet (color de la ficha)
 *   size       : sm (400px) | md (512px) | lg (672px)
 *   persistent : true = no se cierra con Esc ni clic fuera (p. ej. mientras guarda)
 *   hideClose  : oculta la X del encabezado
 *
 * Slots: default (cuerpo, con scroll), footer (botones), header (reemplaza el encabezado)
 * En móvil (< sm) se muestra como hoja que sube desde abajo; desde sm, centrado.
 */
import { computed, onBeforeUnmount, watch, useId } from 'vue';
import { toneOf } from '@/components/ui/tones';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  icon: { type: [Object, Function], default: null },
  tone: { type: String, default: 'blue' },
  size: { type: String, default: 'md' },
  persistent: { type: Boolean, default: false },
  hideClose: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'close']);

const titleId = useId();
const t = computed(() => toneOf(props.tone));
const sizeClass = computed(() => ({ sm: 'sm:max-w-[400px]', md: 'sm:max-w-lg', lg: 'sm:max-w-2xl' }[props.size] || 'sm:max-w-lg'));

const close = () => {
  emit('update:modelValue', false);
  emit('close');
};

const onBackdrop = () => {
  if (!props.persistent) close();
};

const onKeydown = (event) => {
  if (event.key === 'Escape' && !props.persistent) close();
};

watch(
  () => props.modelValue,
  (open) => {
    if (open) document.addEventListener('keydown', onKeydown);
    else document.removeEventListener('keydown', onKeydown);
  },
  { immediate: true }
);

onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-[60] flex items-end justify-center bg-slate-900/55 backdrop-blur-[3px] sm:items-center sm:p-4"
        @click.self="onBackdrop"
      >
        <div
          role="dialog"
          aria-modal="true"
          :aria-labelledby="title ? titleId : undefined"
          class="app-modal-panel relative flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-3xl bg-white shadow-[0_30px_60px_-20px_rgba(15,23,42,0.45)] sm:max-h-[88vh] sm:rounded-3xl"
          :class="sizeClass"
        >
          <!-- Asa visual de la hoja (solo móvil) -->
          <div class="flex justify-center pt-2.5 sm:hidden" aria-hidden="true">
            <span class="h-1 w-10 rounded-full bg-surface-300"></span>
          </div>

          <!-- Encabezado -->
          <slot name="header">
            <header
              v-if="title || !hideClose"
              class="relative flex items-start gap-3.5 overflow-hidden px-5 pb-4 pt-3 sm:px-6 sm:pt-5"
            >
              <!-- Brillo del tono (limitado al encabezado para no tapar el cuerpo) -->
              <div class="pointer-events-none absolute inset-0 opacity-70" :class="t.soft" aria-hidden="true"></div>

              <span
                v-if="icon"
                class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-white"
                :class="[t.solid, t.shadow]"
              >
                <component :is="icon" class="h-5 w-5" />
              </span>

              <div class="relative min-w-0 flex-1 pt-0.5">
                <h2 v-if="title" :id="titleId" class="m-0 text-lg font-bold leading-tight tracking-tight text-surface-900">
                  {{ title }}
                </h2>
                <p v-if="subtitle" class="m-0 mt-1 text-sm font-medium text-surface-600">{{ subtitle }}</p>
              </div>

              <button
                v-if="!hideClose"
                type="button"
                class="relative -mr-1 flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-xl text-surface-500 transition-colors hover:bg-surface-100 hover:text-surface-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-40"
                aria-label="Cerrar"
                :disabled="persistent"
                @click="close"
              >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                  <path d="M18 6 6 18M6 6l12 12" />
                </svg>
              </button>
            </header>
          </slot>

          <!-- Cuerpo -->
          <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-5 pt-4 sm:px-6">
            <slot />
          </div>

          <!-- Pie: en móvil los botones ocupan todo el ancho (el principal queda arriba) -->
          <footer
            v-if="$slots.footer"
            class="flex flex-col-reverse gap-2.5 border-t border-surface-200/70 bg-surface-50 px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:flex-row sm:items-center sm:justify-end sm:px-6 sm:pb-4 [&>*]:w-full sm:[&>*]:w-auto"
          >
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Entrada del panel: sube desde abajo en móvil, escala suave desde sm */
.app-modal-panel {
  animation: app-modal-in 260ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes app-modal-in {
  from {
    transform: translate3d(0, 24px, 0);
    opacity: 0;
  }
  to {
    transform: none;
    opacity: 1;
  }
}

@media (min-width: 640px) {
  @keyframes app-modal-in {
    from {
      transform: translate3d(0, 8px, 0) scale(0.97);
      opacity: 0;
    }
    to {
      transform: none;
      opacity: 1;
    }
  }
}

@media (prefers-reduced-motion: reduce) {
  .app-modal-panel {
    animation: none;
  }
}
</style>
