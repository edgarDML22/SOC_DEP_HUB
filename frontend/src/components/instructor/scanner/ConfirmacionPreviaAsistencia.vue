<script setup>
import { computed } from 'vue'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import ListaAsistencia from './ListaAsistencia.vue'

const store = useScannerStore()

const sesion       = computed(() => store.sesionActiva)
const confirmados  = computed(() => store.inscritosConfirmados)
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
    <div class="mx-4 mt-4 bg-blue-50 border border-blue-100 rounded-2xl px-4 py-3 flex gap-3">
      <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div class="min-w-0">
        <p class="text-sm font-bold text-blue-900 leading-tight">Lista final de asistencia</p>
        <p class="text-xs text-blue-700/80 mt-1 leading-relaxed">
          {{ totalConfirm }} participante{{ totalConfirm === 1 ? '' : 's' }} será{{ totalConfirm === 1 ? '' : 'n' }}
          registrado{{ totalConfirm === 1 ? '' : 's' }}. Esta acción no puede deshacerse.
        </p>
      </div>
    </div>

    <!-- ── Contexto de sesión ─────────────────────────────────────────────── -->
    <div v-if="sesion" class="flex items-center gap-2 px-5 mt-3">
      <span class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">
        {{ sesion.disciplina }}
      </span>
      <span class="text-surface-300">·</span>
      <span class="text-[10px] font-bold text-surface-400 tabular-nums">
        {{ sesion.hora_inicio }}–{{ sesion.hora_fin }}
      </span>
    </div>

    <!-- ── Lista de confirmados ───────────────────────────────────────────── -->
    <div class="flex-1 overflow-y-auto px-4 mt-3 pb-2">
      <ListaAsistencia
        :participantes="confirmados"
        empty-titulo="No hay confirmados"
        empty-mensaje="Vuelve atrás para registrar asistencia."
      />
    </div>

    <!-- ── Acciones ───────────────────────────────────────────────────────── -->
    <div class="px-4 pb-6 pt-3 border-t border-primary-950/8 space-y-2 bg-white">

      <button
        type="button"
        @click="volverAEditar"
        :disabled="store.loading"
        class="w-full py-3 rounded-2xl border border-primary-200 bg-primary-50 text-primary-700 font-bold text-sm hover:bg-primary-100 active:scale-[0.98] transition-all duration-150 focus:outline-none disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100"
      >
        Volver a editar
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
