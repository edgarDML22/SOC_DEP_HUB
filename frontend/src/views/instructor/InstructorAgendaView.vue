<script setup>
import { IconCalendar } from '@/components/icons';
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans p-4 md:p-6 lg:p-8 pb-24 lg:pb-8 flex justify-center">
    
    <div class="w-full max-w-5xl flex flex-col gap-6 animate-fade-in">

      <div class="flex flex-col md:flex-row md:items-end justify-between gap-5 border-b border-surface-200 pb-5">
          <div>
              <h1 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight" >Agenda Semanal</h1>
              <p class="text-sm md:text-base font-medium text-surface-500 m-0 mt-2">Próximos eventos y calendario de disponibilidad</p>
          </div>
      </div>

      <div class="h-64 flex flex-col items-center justify-center rounded-3xl border border-dashed border-surface-300 bg-white shadow-sm transition-colors mt-4">
        <div class="w-16 h-16 bg-surface-100 text-surface-400 rounded-full flex items-center justify-center mb-4">
            <IconCalendar class="w-8 h-8" />
        </div>
        <h3 class="text-lg font-bold text-surface-900 mb-1">Sin agenda configurada</h3>
        <p class="text-surface-500 font-medium text-sm text-center px-4">Aquí se mostrará tu agenda de actividades cuando se asignen nuevos eventos.</p>
      </div>

    </div>
  </main>
</template>
