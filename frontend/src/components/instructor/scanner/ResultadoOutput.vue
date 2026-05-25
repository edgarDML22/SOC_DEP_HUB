<script setup>
import { computed } from 'vue'
import { useScannerStore } from '@/stores/profiles/scannerStore'

const store = useScannerStore()

const ultimoResultado = computed(() => store.resultados[0] ?? null)
const exito = computed(() => ultimoResultado.value?.success === true)

const LABEL_CATEGORIA = {
  CLASES:        'Mis Clases',
  RESERVACIONES: 'Reservaciones',
  TORNEO:        'Encuentros Torneo',
}

const labelCategoria = computed(() => LABEL_CATEGORIA[store.categoriaActiva] ?? '')

function continuarEscaneando() {
  store.irAtras() // OUTPUT → ESCANER_ACTIVO, preservando sesión
}
</script>

<template>
  <div class="space-y-5">

    <!-- Tarjeta de resultado principal -->
    <div
      class="rounded-3xl border p-6 flex flex-col items-center text-center gap-4"
      :class="exito
        ? 'bg-green-50 border-green-200'
        : 'bg-red-50 border-red-200'"
    >
      <!-- Icono de estado -->
      <div
        class="w-16 h-16 rounded-full flex items-center justify-center"
        :class="exito ? 'bg-green-100' : 'bg-red-100'"
      >
        <svg v-if="exito" xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </div>

      <div>
        <p class="font-bold text-lg" :class="exito ? 'text-green-800' : 'text-red-800'">
          {{ exito ? 'Registro exitoso' : 'Error al registrar' }}
        </p>
        <p class="text-sm mt-1" :class="exito ? 'text-green-600' : 'text-red-500'">
          {{ ultimoResultado?.message ?? store.error ?? '' }}
        </p>
      </div>

      <!-- Código escaneado -->
      <div v-if="store.codigoEscaneado" class="px-4 py-2 rounded-xl bg-white/70 border"
        :class="exito ? 'border-green-200' : 'border-red-200'"
      >
        <p class="text-xs font-bold tracking-widest" :class="exito ? 'text-green-700' : 'text-red-600'">
          {{ store.codigoEscaneado }}
        </p>
      </div>
    </div>

    <!-- Historial de registros de la sesión actual -->
    <div v-if="store.resultados.length > 1">
      <p class="text-xs font-bold text-surface-500 uppercase tracking-widest mb-2">
        Historial de esta sesión ({{ store.resultados.length }})
      </p>
      <ul class="space-y-2 max-h-52 overflow-y-auto">
        <li
          v-for="(r, i) in store.resultados"
          :key="i"
          class="flex items-center justify-between px-4 py-3 rounded-xl border bg-white text-sm"
          :class="r.success ? 'border-green-100' : 'border-red-100'"
        >
          <span class="font-bold text-surface-900 tracking-wider">{{ r.data?.codigo ?? '—' }}</span>
          <span class="text-xs font-semibold" :class="r.success ? 'text-green-600' : 'text-red-500'">
            {{ r.success ? '✓ OK' : '✗ Error' }}
          </span>
        </li>
      </ul>
    </div>

    <!-- Chip de contexto -->
    <div class="flex items-center justify-center gap-2">
      <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-surface-100 text-surface-500 text-xs font-semibold">
        <span class="w-1.5 h-1.5 rounded-full bg-primary-400"></span>
        {{ labelCategoria }}
      </span>
    </div>

    <!-- CTA principal -->
    <button
      @click="continuarEscaneando"
      class="w-full py-4 rounded-2xl font-bold text-sm bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-600/20 active:scale-[0.98] transition-all focus:outline-none"
    >
      Escanear otro código
    </button>

  </div>
</template>
