<script setup>
import { computed } from 'vue'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import ListaAsistencia from './ListaAsistencia.vue'

const store = useScannerStore()

const sesion       = computed(() => store.sesionActiva)
// Solo los nuevos confirmados de ESTE Hub viajan al backend.
// Los 'YA_REGISTRADO' (asistencia previa) NO se incluyen aquí para evitar
// duplicados / envíos en cero.
const confirmados  = computed(() => store.inscritosNuevos)
const totalConfirm = computed(() => confirmados.value.length)
const puedeEnviar  = computed(() => totalConfirm.value > 0 && !store.loading)

function volverAEditar() {
    store.paso = 'PASE_LISTA'
}

function enviar() {
    if (!puedeEnviar.value) return
    store.enviarListaFinal()
}
</script>

<template>
  <div class="flex flex-col h-full">

    <!-- ── Banner informativo ─────────────────────────────────────────────── -->
    <div class="mx-4 mt-4 bg-primary-50 border border-primary-100 rounded-2xl px-4 py-3.5">

      <!-- Sesión: etiqueta secundaria -->
      <p v-if="sesion" class="text-[10px] font-bold text-primary-400 uppercase tracking-widest mb-2">
        {{ sesion.disciplina }}&ensp;·&ensp;{{ sesion.hora_inicio }}–{{ sesion.hora_fin }}
      </p>

      <!-- Contador + label: 30% del protagonismo -->
      <div class="flex items-baseline gap-2">
        <span class="text-3xl font-extrabold text-primary-700 tabular-nums leading-none">{{ totalConfirm }}</span>
        <span class="text-sm font-semibold text-primary-600">
          asistencia{{ totalConfirm === 1 ? '' : 's' }} nueva{{ totalConfirm === 1 ? '' : 's' }} por enviar
        </span>
      </div>

      <!-- Aviso mínimo -->
      <p class="text-[11px] text-primary-400 mt-2 leading-relaxed">
        Los registros previos no se reenvían. Esta acción no puede deshacerse.
      </p>
    </div>

    <!-- ── Lista de confirmados (solo nuevos) ─────────────────────────────── -->
    <div class="flex-1 overflow-y-auto px-4 mt-3 pb-2">
      <ListaAsistencia
        :participantes="confirmados"
        empty-titulo="No hay nuevos por confirmar"
        empty-mensaje="Vuelve atrás para registrar asistencia."
      />
    </div>

    <!-- ── Acciones ───────────────────────────────────────────────────────── -->
    <div class="px-4 pb-6 pt-3 border-t border-primary-950/8 space-y-2 bg-white">

      <button
        type="button"
        @click="volverAEditar"
        :disabled="store.loading"
        class="w-full py-3 rounded-2xl border border-primary-200 bg-primary-50 text-primary-700 font-bold text-sm hover:bg-primary-100 active:scale-[0.98] transition-all duration-150 focus:outline-none disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100 flex items-center justify-center gap-2"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Volver a escanear
      </button>

      <button
        type="button"
        @click="enviar"
        :disabled="!puedeEnviar"
        class="w-full py-4 rounded-2xl font-bold text-sm transition-all duration-150 focus:outline-none flex items-center justify-center gap-2"
        :class="!puedeEnviar
          ? 'bg-surface-100 text-surface-300 cursor-not-allowed'
          : 'bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-600/20 active:scale-[0.98]'"
      >
        <template v-if="store.loading">
          <span class="w-4 h-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
          <span>Enviando…</span>
        </template>
        <template v-else>
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span>Enviar Asistencia</span>
          <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white/20 tabular-nums">
            {{ totalConfirm }}
          </span>
        </template>
      </button>
    </div>

  </div>
</template>
