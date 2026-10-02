<script setup>
/**
 * Esqueleto de carga que replica la anatomía de las tarjetas reales del Hub de
 * Torneos del socio (disponibles e historial) para evitar saltos de diseño (CLS)
 * mientras llegan los datos del backend.
 *
 * Las alturas y los bloques miroir la tarjeta real 1:1: cabecera, título,
 * etiquetas, bloque de fecha, barra de cupos y CTA.
 */
defineProps({
  variant: {
    type: String,
    default: 'disponible',
    validator: (value) => ['disponible', 'historial'].includes(value),
  },
});
</script>

<template>
  <div
    class="bg-white rounded-3xl border border-surface-200 p-6 flex flex-col justify-between relative overflow-hidden animate-pulse"
    aria-hidden="true"
  >
    <!-- ── VARIANTE: TARJETA DE TORNEO DISPONIBLE ── -->
    <template v-if="variant === 'disponible'">
      <!-- Top Accent Line -->
      <div class="absolute top-0 left-0 right-0 h-1.5 bg-surface-200" />

      <div class="space-y-4">
        <!-- Header & Access Badge -->
        <div class="flex justify-between items-start pt-1 gap-3">
          <div class="h-3 w-24 rounded-lg bg-surface-200" />
          <div class="h-6 w-20 rounded-full bg-primary-50 border border-primary-100 shrink-0" />
        </div>

        <!-- Title & Category -->
        <div>
          <div class="h-5 w-4/5 rounded-lg bg-surface-200" />
          <div class="h-3 w-1/2 rounded-lg bg-surface-100 mt-2" />
        </div>

        <!-- Details Badges (Age/Gender) -->
        <div class="flex flex-wrap gap-2">
          <div class="h-7 w-32 rounded-lg bg-surface-50 border border-surface-200" />
          <div class="h-7 w-28 rounded-lg bg-surface-50 border border-surface-200" />
        </div>

        <!-- Date Block -->
        <div class="bg-surface-50/50 rounded-2xl p-3 border border-surface-200 flex items-center gap-3">
          <div class="h-8 w-8 rounded-xl bg-surface-200 shrink-0" />
          <div class="flex-1 space-y-2">
            <div class="h-2.5 w-2/3 rounded bg-surface-200" />
            <div class="h-2.5 w-1/2 rounded bg-surface-100" />
          </div>
        </div>

        <!-- Progress Bar / Cupos -->
        <div class="space-y-2 pt-1">
          <div class="flex justify-between">
            <div class="h-2.5 w-16 rounded bg-surface-200" />
            <div class="h-2.5 w-12 rounded bg-surface-200" />
          </div>
          <div class="w-full bg-surface-100 rounded-full h-2.5" />
        </div>
      </div>

      <!-- CTA Button -->
      <div class="mt-6 h-11 w-full rounded-2xl bg-surface-200" />
    </template>

    <!-- ── VARIANTE: TARJETA DE HISTORIAL ── -->
    <template v-else>
      <!-- Left Accent Border -->
      <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-surface-200" />

      <div class="space-y-4">
        <!-- Header Block & Status Badge -->
        <div class="flex justify-between items-start pt-1 pl-1 gap-3">
          <div class="flex-1 space-y-2">
            <div class="h-3 w-20 rounded-lg bg-surface-200" />
            <div class="h-5 w-3/5 rounded-lg bg-surface-200" />
          </div>
          <div class="h-6 w-20 rounded-full bg-surface-100 shrink-0" />
        </div>

        <!-- Date Block -->
        <div class="pl-1 flex items-center gap-2">
          <div class="h-4 w-4 rounded bg-surface-100 shrink-0" />
          <div class="h-2.5 w-40 rounded bg-surface-100" />
        </div>

        <!-- Achievement/Fase Maxima Badge -->
        <div class="pt-3 pl-1 border-t border-surface-200 flex items-center gap-2">
          <div class="h-2.5 w-20 rounded bg-surface-200" />
          <div class="h-7 w-24 rounded-xl bg-amber-50 border border-amber-100" />
        </div>

        <!-- Team Status Section -->
        <div class="mt-3 pt-3 pl-1 border-t border-surface-200 space-y-2">
          <div class="h-2.5 w-3/4 rounded bg-surface-100" />
          <div class="h-9 w-full rounded-xl bg-surface-100" />
        </div>
      </div>
    </template>
  </div>
</template>
