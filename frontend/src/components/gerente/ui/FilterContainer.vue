<script setup>
/**
 * FilterContainer — barra de búsqueda + filtros homogénea para todas las listas de admin.
 *
 * En móvil / tablet (< lg) solo se ve la barra de búsqueda y un botón que despliega los filtros
 * (con un contador de filtros activos). Desde lg se muestra todo a la vista.
 *
 * Slots: search (barra de búsqueda) · default (los FilterSelect u otros controles) ·
 *        actions (botones extra, p. ej. importar / exportar; van dentro del panel)
 * Props: hasActiveFilters (muestra "Limpiar filtros") · activeCount (contador del botón;
 *        si no se pasa, el botón solo marca un punto cuando hasActiveFilters)
 */
import { ref } from 'vue'

defineProps({
  hasActiveFilters: { type: Boolean, default: false },
  activeCount: { type: Number, default: null },
})

const emit = defineEmits(['clear'])

const open = ref(false)
</script>

<template>
  <div class="rounded-2xl border border-surface-200 bg-white p-3 shadow-sm sm:p-5">
    <!-- Búsqueda + botón de filtros (el botón solo existe en < lg) -->
    <div class="flex items-center gap-2">
      <div class="min-w-0 flex-1">
        <slot name="search"></slot>
      </div>

      <button
        v-if="$slots.default || $slots.actions"
        type="button"
        class="relative flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-xl border transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-100 lg:hidden"
        :class="open || hasActiveFilters
          ? 'border-primary-200 bg-primary-50 text-primary-700 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-300'
          : 'border-surface-200 bg-white text-surface-600 hover:bg-surface-50'"
        :aria-expanded="open"
        aria-controls="filter-panel"
        :aria-label="open ? 'Ocultar filtros' : 'Mostrar filtros'"
        @click="open = !open"
      >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
        </svg>
        <span
          v-if="activeCount"
          class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-600 px-1 text-[10px] font-black text-white"
        >
          {{ activeCount }}
        </span>
        <span
          v-else-if="hasActiveFilters && activeCount === null"
          class="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full border-2 border-white bg-primary-600"
        ></span>
      </button>
    </div>

    <!-- Filtros: colapsables en < lg, siempre visibles desde lg -->
    <div
      v-if="$slots.default || $slots.actions"
      id="filter-panel"
      :class="open ? 'mt-3 block' : 'hidden lg:mt-4 lg:block'"
      class="border-t border-surface-100 pt-3 lg:border-t-0 lg:pt-0"
    >
      <div v-if="$slots.default" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <slot></slot>
      </div>
      <div v-if="$slots.actions" class="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end">
        <slot name="actions"></slot>
      </div>
    </div>

    <!-- Limpiar filtros -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-1"
    >
      <div v-if="hasActiveFilters" class="mt-3 flex justify-end" :class="open ? '' : 'max-lg:hidden'">
        <button
          type="button"
          class="flex cursor-pointer items-center gap-1.5 text-xs font-bold text-primary-600 transition-colors hover:text-primary-800"
          @click="emit('clear')"
        >
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 6L6 18M6 6l12 12" />
          </svg>
          Limpiar filtros
        </button>
      </div>
    </Transition>
  </div>
</template>
