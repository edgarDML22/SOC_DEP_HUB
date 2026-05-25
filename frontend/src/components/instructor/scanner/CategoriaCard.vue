<script setup>
defineProps({
  titulo:        { type: String,  required: true },
  subtitulo:     { type: String,  default: '' },
  deshabilitado: { type: Boolean, default: false },
})

const emit = defineEmits(['click'])
</script>

<template>
  <button
    type="button"
    :disabled="deshabilitado"
    @click="!deshabilitado && emit('click')"
    class="w-full text-left flex items-center gap-4 p-5 rounded-2xl border transition-all duration-200 focus:outline-none group"
    :class="deshabilitado
      ? 'bg-slate-50 border-slate-200 cursor-not-allowed'
      : 'bg-white border-surface-200 hover:border-primary-400 hover:shadow-md hover:shadow-primary-600/8 active:scale-[0.98] cursor-pointer shadow-sm'"
  >
    <!-- Icono -->
    <div
      class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-colors"
      :class="deshabilitado
        ? 'bg-slate-100 text-slate-400'
        : 'bg-primary-50 text-primary-600 group-hover:bg-primary-100'"
    >
      <slot name="icon" />
    </div>

    <!-- Texto -->
    <div class="flex-1 min-w-0">
      <p
        class="font-bold text-base leading-tight"
        :class="deshabilitado ? 'text-slate-500' : 'text-surface-900'"
      >{{ titulo }}</p>
      <p
        class="text-sm mt-0.5 leading-snug"
        :class="deshabilitado ? 'text-slate-400' : 'text-surface-500'"
      >{{ subtitulo }}</p>
    </div>

    <!-- Flecha o candado -->
    <svg
      v-if="!deshabilitado"
      xmlns="http://www.w3.org/2000/svg"
      class="w-5 h-5 text-surface-300 group-hover:text-primary-500 group-hover:translate-x-0.5 transition-all shrink-0"
      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
    >
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
    </svg>
    <svg
      v-else
      xmlns="http://www.w3.org/2000/svg"
      class="w-4 h-4 text-slate-300 shrink-0"
      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
    >
      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
      <path d="M7 11V7a5 5 0 0110 0v4"/>
    </svg>
  </button>
</template>
