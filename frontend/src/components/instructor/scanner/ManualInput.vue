<script setup>
import { ref, computed } from 'vue'
import { QR_REGEX } from '@/utils/qrValidator'

const emit = defineEmits(['submit'])

const codigo = ref('')

function onInput(event) {
  codigo.value = event.target.value.toUpperCase()
}

const esValido    = computed(() => QR_REGEX.test(codigo.value))
const mostrarError = computed(() => codigo.value.length === 8 && !esValido.value)

const claseInput = computed(() => {
  if (codigo.value.length === 0) return 'border-surface-300 focus:border-primary-400 focus:ring-primary-100'
  if (esValido.value)             return 'border-green-400 ring-1 ring-green-100 focus:border-green-500'
  if (mostrarError.value)         return 'border-red-400 ring-1 ring-red-100 focus:border-red-500'
  return 'border-surface-300 focus:border-primary-400 focus:ring-primary-100'
})

function validar() {
  if (!esValido.value) return
  emit('submit', codigo.value)
  codigo.value = ''
}
</script>

<template>
  <div class="space-y-4">
    <div class="relative">
      <input
        v-model="codigo"
        @input="onInput"
        @keyup.enter="validar"
        maxlength="8"
        autocomplete="off"
        autocorrect="off"
        spellcheck="false"
        placeholder="Ej: OS1A2B3C"
        class="w-full px-5 py-4 text-2xl font-bold tracking-widest text-center rounded-2xl border-2 bg-white transition-all duration-150 focus:outline-none focus:ring-4"
        :class="claseInput"
        style="text-transform: uppercase"
      />

      <!-- Indicador de caracteres -->
      <span
        class="absolute right-4 bottom-3.5 text-[11px] font-bold tabular-nums transition-colors"
        :class="codigo.length === 8 ? (esValido ? 'text-green-500' : 'text-red-400') : 'text-surface-300'"
      >
        {{ codigo.length }}/8
      </span>

      <!-- Check verde al completar válido -->
      <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 scale-75" enter-to-class="opacity-100 scale-100">
        <div v-if="esValido" class="absolute left-4 top-1/2 -translate-y-1/2 text-green-500">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
      </Transition>
    </div>

    <!-- Mensaje de error -->
    <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
      <p v-if="mostrarError" class="text-xs font-semibold text-red-500 flex items-center gap-1.5 px-1">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        Debe iniciar con OS, MF u OI seguido de 6 caracteres alfanuméricos
      </p>
    </Transition>

    <!-- Pista de formato -->
    <p v-if="!mostrarError" class="text-[11px] font-medium text-surface-400 text-center">
      Formato: <span class="font-bold tracking-wider">OS</span> · <span class="font-bold tracking-wider">MF</span> · <span class="font-bold tracking-wider">OI</span> + 6 caracteres
    </p>

    <!-- Botón Validar -->
    <button
      @click="validar"
      :disabled="!esValido"
      class="w-full py-4 rounded-2xl font-bold text-sm transition-all duration-150 focus:outline-none"
      :class="esValido
        ? 'bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-600/20 active:scale-[0.98]'
        : 'bg-surface-100 text-surface-300 cursor-not-allowed'"
    >
      Validar Código
    </button>
  </div>
</template>
