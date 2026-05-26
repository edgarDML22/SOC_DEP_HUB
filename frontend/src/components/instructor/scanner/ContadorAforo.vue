<script setup>
import { computed } from 'vue'

const props = defineProps({
    actual: { type: Number, required: true },
    maximo: { type: Number, required: true },
})

const estaLleno = computed(() => props.maximo > 0 && props.actual >= props.maximo)

const pct = computed(() =>
    props.maximo > 0 ? Math.min(100, Math.round((props.actual / props.maximo) * 100)) : 0
)
</script>

<template>
  <div
    class="rounded-2xl border p-4 transition-all duration-300"
    :class="estaLleno
      ? 'bg-red-50 border-red-200'
      : 'bg-surface-50 border-surface-200'"
  >
    <div class="flex items-end justify-between gap-3">
      <!-- Contador numérico -->
      <div class="flex items-baseline gap-1">
        <span
          class="text-4xl font-black tabular-nums leading-none transition-colors duration-300"
          :class="estaLleno ? 'text-red-600' : 'text-surface-900'"
        >{{ actual }}</span>
        <span class="text-xl font-semibold text-surface-400">/</span>
        <span class="text-xl font-semibold text-surface-500 tabular-nums">{{ maximo }}</span>
      </div>

      <!-- Label -->
      <p class="text-xs font-bold uppercase tracking-widest"
        :class="estaLleno ? 'text-red-500' : 'text-surface-400'"
      >
        {{ estaLleno ? 'Cupo lleno' : 'Asistentes' }}
      </p>
    </div>

    <!-- Barra de progreso -->
    <div class="mt-3 h-2 bg-surface-100 rounded-full overflow-hidden">
      <div
        class="h-full rounded-full transition-all duration-500"
        :class="estaLleno ? 'bg-red-400' : pct >= 90 ? 'bg-amber-400' : 'bg-primary-400'"
        :style="{ width: pct + '%' }"
      />
    </div>

    <!-- Banner de bloqueo — sin botón de cierre, es estado permanente -->
    <Transition
      enter-active-class="transition-all duration-300"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
    >
      <div v-if="estaLleno" class="mt-3 flex items-start gap-2.5">
        <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0 mt-0.5">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-xs font-semibold text-red-700 leading-relaxed">
          Aforo máximo alcanzado. No se pueden registrar más asistentes.
        </p>
      </div>
    </Transition>
  </div>
</template>
