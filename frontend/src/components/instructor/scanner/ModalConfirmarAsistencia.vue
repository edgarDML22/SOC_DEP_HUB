<script setup>
const props = defineProps({
    totalPresentes: { type: Number, required: true },
    totalInscritos: { type: Number, default: null },  // null en clases abiertas
})

const emit = defineEmits(['cancelar', 'confirmar'])
</script>

<template>
  <!-- Backdrop -->
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">

      <!-- Overlay -->
      <div
        class="absolute inset-0 bg-black/40 backdrop-blur-sm"
        @click="emit('cancelar')"
      />

      <!-- Panel -->
      <div class="relative w-full max-w-sm bg-white rounded-3xl shadow-2xl overflow-hidden">

        <!-- Franja superior -->
        <div class="h-1.5 bg-gradient-to-r from-primary-400 to-primary-600 w-full" />

        <div class="p-6 space-y-5">

          <!-- Ícono + Título -->
          <div class="flex flex-col items-center text-center gap-3">
            <div class="w-14 h-14 rounded-2xl bg-primary-50 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h2 class="text-lg font-bold text-surface-900 leading-tight">¿Confirmar lista de asistencia?</h2>
              <p class="text-sm text-surface-500 font-medium mt-1">Esta acción es irreversible.</p>
            </div>
          </div>

          <!-- Resumen numérico -->
          <div class="bg-surface-50 border border-surface-100 rounded-2xl p-4 space-y-2">
            <div class="flex items-center justify-between text-sm">
              <span class="text-surface-500 font-medium">Asistentes registrados</span>
              <span class="font-bold text-green-700">{{ totalPresentes }}</span>
            </div>
            <div v-if="totalInscritos !== null" class="flex items-center justify-between text-sm">
              <span class="text-surface-500 font-medium">Faltantes</span>
              <span class="font-bold text-red-500">{{ totalInscritos - totalPresentes }}</span>
            </div>
          </div>

          <!-- Advertencia -->
          <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-100 rounded-2xl p-3.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            <p class="text-xs font-semibold text-amber-800 leading-relaxed">
              Los socios ausentes serán marcados como falta y podrían recibir una sanción automática.
            </p>
          </div>

          <!-- Botones -->
          <div class="grid grid-cols-2 gap-3 pt-1">
            <button
              @click="emit('cancelar')"
              class="py-3.5 rounded-2xl border border-surface-200 font-bold text-sm text-surface-600 hover:bg-surface-50 active:scale-[0.97] transition-all focus:outline-none"
            >
              Cancelar
            </button>
            <button
              @click="emit('confirmar')"
              class="py-3.5 rounded-2xl bg-primary-600 hover:bg-primary-700 text-white font-bold text-sm shadow-md shadow-primary-600/20 active:scale-[0.97] transition-all focus:outline-none"
            >
              Confirmar
            </button>
          </div>

        </div>
      </div>
    </div>
  </Teleport>
</template>
