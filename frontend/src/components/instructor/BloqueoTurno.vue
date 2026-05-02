<script setup>
import { IconClock } from '@/components/icons';
</script>

<template>
  <div class="min-h-[60vh] flex flex-col items-center justify-center px-4 animate-fade-in">
    <div class="bg-white rounded-[2.5rem] p-8 md:p-12 shadow-xl shadow-surface-200/50 border border-surface-200 text-center max-w-md w-full relative overflow-hidden">
      
      <!-- Decoración de fondo suave -->
      <div class="absolute -top-16 -right-16 w-32 h-32 bg-primary-50 rounded-full blur-2xl pointer-events-none"></div>

      <!-- Icono central -->
      <div class="w-20 h-20 bg-primary-50 text-primary-600 rounded-full flex items-center justify-center mx-auto mb-6 relative z-10">
        <IconClock class="w-10 h-10" />
      </div>

      <!-- Texto -->
      <div class="relative z-10 space-y-3">
        <h2 class="text-2xl font-bold text-surface-900 tracking-tight">Acceso Restringido</h2>
        <p class="text-surface-500 font-medium">
          No tienes turno asignado en este momento o tu turno ya ha finalizado.
        </p>
      </div>

      <!-- Botón de recarga manual (opcional, útil por si el backend se actualiza) -->
      <button @click="$emit('retry')" class="mt-8 bg-surface-100 hover:bg-surface-200 text-surface-700 font-semibold rounded-xl px-6 py-3 transition-all active:scale-95 border border-surface-200 relative z-10">
        Verificar nuevamente
      </button>

    </div>
  </div>
</template>
