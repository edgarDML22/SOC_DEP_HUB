<script setup>
defineProps({
  rows: {
    type: Number,
    default: 5
  },
  columns: {
    type: Number,
    default: 4
  },
  hasAvatar: {
    type: Boolean,
    default: true
  }
});
</script>

<template>
  <div class="p-6 md:p-8 space-y-3 w-full">
    <div v-for="r in rows" :key="r" class="flex items-center gap-4 animate-pulse py-3 border-b border-surface-100 last:border-b-0 w-full">
      <!-- Columna 1: Avatar opcional -->
      <div v-if="hasAvatar" class="w-10 h-10 rounded-xl bg-surface-200 shrink-0" />
      
      <!-- Columna 2: Texto principal (título + subtítulo) -->
      <div v-if="columns >= 1" class="flex-1 space-y-2 min-w-0 pr-4">
        <div class="h-3.5 bg-surface-200 rounded-lg w-32 sm:w-44" />
        <div class="h-3 bg-surface-100 rounded-lg w-24 sm:w-32" />
      </div>

      <!-- Columnas extra: se ocultan progresivamente en pantallas pequeñas -->
      <!-- Columna 3 -->
      <div v-if="columns >= 2" class="flex-1 hidden sm:flex items-center">
        <div class="h-5 w-16 bg-surface-100 rounded-full" />
      </div>

      <!-- Columna 4 -->
      <div v-if="columns >= 3" class="flex-1 hidden md:flex gap-1.5 items-center">
        <div class="h-5 w-14 bg-primary-50 rounded-lg" />
        <div class="h-5 w-14 bg-primary-50 rounded-lg" />
      </div>

      <!-- Columna 5+ -->
      <div v-for="i in Math.max(0, columns - 4)" :key="'extra-'+i" class="flex-1 hidden lg:flex items-center">
        <div class="h-3 w-20 bg-surface-100 rounded-lg" />
      </div>

      <!-- Botón de acciones (3 puntitos simulación) -->
      <div class="w-8 h-8 rounded-lg bg-surface-100 shrink-0 flex items-center justify-center">
        <div class="flex gap-1">
          <div class="w-1 h-1 rounded-full bg-surface-300"></div>
          <div class="w-1 h-1 rounded-full bg-surface-300"></div>
          <div class="w-1 h-1 rounded-full bg-surface-300"></div>
        </div>
      </div>
    </div>
  </div>
</template>
