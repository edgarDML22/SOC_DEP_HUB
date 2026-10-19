<script setup>
/**
 * SpaceUsageSelector — tipos de uso de un espacio como tarjetas con casilla.
 * Regla del negocio: "Uso libre" es exclusivo; al activarlo se desactivan On Demand y Clase,
 * y al activar cualquiera de esos dos se desactiva Uso libre.
 *
 * Uso: <SpaceUsageSelector v-model:on-demand="f.es_reserva_on_demand" v-model:clase="f.es_clase_programada" v-model:libre="f.es_uso_libre" />
 */
import { computed } from 'vue';

const onDemand = defineModel('onDemand', { type: Boolean, default: false });
const clase = defineModel('clase', { type: Boolean, default: false });
const libre = defineModel('libre', { type: Boolean, default: false });

defineProps({ disabled: { type: Boolean, default: false } });

const options = computed(() => [
  { key: 'onDemand', label: 'Reserva On Demand', description: 'Los socios reservan el espacio por horario.', active: onDemand.value, tone: 'indigo' },
  { key: 'clase', label: 'Clase programada', description: 'Se usa para clases con instructor.', active: clase.value, tone: 'blue' },
  { key: 'libre', label: 'Uso libre', description: 'Acceso sin reserva. Excluye las otras opciones.', active: libre.value, tone: 'emerald' },
]);

const ACTIVE = {
  indigo: 'border-indigo-500 bg-indigo-50/70 ring-4 ring-indigo-100 dark:bg-indigo-500/10 dark:ring-indigo-500/15',
  blue: 'border-primary-500 bg-primary-50/70 ring-4 ring-primary-100 dark:bg-primary-500/10 dark:ring-primary-500/15',
  emerald: 'border-emerald-500 bg-emerald-50/70 ring-4 ring-emerald-100 dark:bg-emerald-500/10 dark:ring-emerald-500/15',
};
const CHECK = { indigo: 'bg-indigo-600', blue: 'bg-primary-600', emerald: 'bg-emerald-600' };

// Se calcula el valor nuevo antes de emitirlo: con defineModel, leer la ref justo después de
// asignarla devuelve el valor anterior hasta que el padre se actualiza.
const toggle = (key) => {
  if (key === 'onDemand') {
    const next = !onDemand.value;
    onDemand.value = next;
    if (next) libre.value = false;
  } else if (key === 'clase') {
    const next = !clase.value;
    clase.value = next;
    if (next) libre.value = false;
  } else {
    const next = !libre.value;
    libre.value = next;
    if (next) {
      onDemand.value = false;
      clase.value = false;
    }
  }
};
</script>

<template>
  <div class="flex flex-col gap-2" role="group" aria-label="Tipo de uso">
    <button
      v-for="option in options"
      :key="option.key"
      type="button"
      role="checkbox"
      :aria-checked="option.active"
      :disabled="disabled"
      class="flex w-full cursor-pointer items-center gap-3 rounded-xl border-2 bg-white px-3.5 py-3 text-left transition-all duration-150 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-100 disabled:cursor-not-allowed disabled:opacity-60"
      :class="option.active ? ACTIVE[option.tone] : 'border-surface-200 hover:border-surface-300 hover:bg-surface-50'"
      @click="toggle(option.key)"
    >
      <span
        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border-2 transition-colors"
        :class="option.active ? [CHECK[option.tone], 'border-transparent text-white'] : 'border-surface-300'"
        aria-hidden="true"
      >
        <svg v-if="option.active" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 6 9 17l-5-5" />
        </svg>
      </span>
      <span class="min-w-0">
        <span class="block text-sm font-bold text-surface-900">{{ option.label }}</span>
        <span class="block text-xs font-medium text-surface-600">{{ option.description }}</span>
      </span>
    </button>
  </div>
</template>
