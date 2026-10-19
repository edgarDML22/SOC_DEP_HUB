<script setup>
/**
 * SpaceFormFields — campos del formulario de espacio, compartidos por "Nuevo espacio"
 * (SpacesList) y "Editar información" (SpaceDetails) para que ambos sean idénticos.
 *
 * v-model: objeto del formulario (nombre_espacio, capacidad_maxima, es_*, descripcion, disciplinas)
 * errors: { campo: mensaje } · showDisciplinas: muestra el selector de disciplinas permitidas
 */
import { computed } from 'vue';
import AppField from '@/components/ui/form/AppField.vue';
import AppInput from '@/components/ui/form/AppInput.vue';
import AppTextarea from '@/components/ui/form/AppTextarea.vue';
import AppMultiSelect from '@/components/ui/form/AppMultiSelect.vue';
import SpaceUsageSelector from './SpaceUsageSelector.vue';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';
import { IconLayers, IconUser } from '@/components/icons';

const form = defineModel({ type: Object, required: true });

const props = defineProps({
  errors: { type: Object, default: () => ({}) },
  disciplines: { type: Array, default: () => [] },
  showDisciplinas: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

const disciplineOptions = computed(() =>
  [...props.disciplines]
    .sort((a, b) => (a.nombre_disciplina || '').localeCompare(b.nombre_disciplina || '', 'es'))
    .map((d) => ({ label: d.nombre_disciplina, value: d.id_disciplina }))
);
</script>

<template>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <AppField label="Nombre del espacio" for="space-nombre" required :error="errors.nombre_espacio" class="sm:col-span-2">
      <AppInput id="space-nombre" v-model="form.nombre_espacio" :icon="IconLayers" placeholder="Ej. Cancha de Tenis 1"
        autocomplete="off" :disabled="disabled" :invalid="!!errors.nombre_espacio" />
    </AppField>

    <AppField label="Capacidad máxima" for="space-capacidad" required hint="Personas al mismo tiempo." :error="errors.capacidad_maxima">
      <AppInput id="space-capacidad" :model-value="form.capacidad_maxima" type="number" inputmode="numeric" min="1"
        :icon="IconUser" placeholder="Ej. 10" :disabled="disabled" :invalid="!!errors.capacidad_maxima"
        @update:model-value="(v) => (form.capacidad_maxima = v === '' ? '' : Number(v))" />
    </AppField>

    <AppField label="Tipo de uso" required :error="errors.uso" class="sm:col-span-2">
      <SpaceUsageSelector
        v-model:on-demand="form.es_reserva_on_demand"
        v-model:clase="form.es_clase_programada"
        v-model:libre="form.es_uso_libre"
        :disabled="disabled"
      />
    </AppField>

    <AppField v-if="showDisciplinas" label="Disciplinas permitidas" hint="Puedes elegir varias; búscalas por nombre." class="sm:col-span-2">
      <AppMultiSelect
        v-model="form.disciplinas"
        :options="disciplineOptions"
        :loading="disciplines.length === 0"
        placeholder="Seleccionar disciplinas"
        items-label="disciplinas seleccionadas"
      >
        <template #option="{ option }">
          <span class="flex items-center gap-2.5">
            <DisciplineIcon :name="option.label" class="h-5 w-5 shrink-0 text-primary-600" />
            {{ option.label }}
          </span>
        </template>
        <template #chip-icon="{ option }">
          <DisciplineIcon :name="option.label" class="h-3.5 w-3.5 shrink-0" />
        </template>
      </AppMultiSelect>
    </AppField>

    <AppField label="Descripción" for="space-descripcion" optional class="sm:col-span-2">
      <AppTextarea id="space-descripcion" v-model="form.descripcion" rows="3" :disabled="disabled"
        placeholder="Ej. Cancha techada con iluminación LED y gradas." />
    </AppField>
  </div>
</template>
