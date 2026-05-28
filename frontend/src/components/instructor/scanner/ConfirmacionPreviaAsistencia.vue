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
    <div class="mx-4 mt-4 bg-gradient-to-br from-primary-600 to-primary-800 rounded-3xl p-5 shadow-lg shadow-primary-900/20 relative overflow-hidden">
      
      <!-- Decorative background elements -->
      <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-2xl"></div>
      <div class="absolute bottom-0 left-0 -mb-6 -ml-6 w-32 h-32 bg-primary-400 opacity-20 rounded-full blur-3xl"></div>

      <!-- Sesión: etiqueta secundaria -->
      <div class="relative z-10 flex items-center gap-2 mb-3">
        <div class="w-1.5 h-1.5 rounded-full bg-primary-300 animate-pulse"></div>
        <p v-if="sesion" class="text-[10px] font-bold text-primary-100 uppercase tracking-widest">
          {{ sesion.disciplina }}&ensp;·&ensp;{{ sesion.hora_inicio }}–{{ sesion.hora_fin }}
        </p>
      </div>

      <!-- Contador + label -->
      <div class="relative z-10 flex items-end gap-2.5">
        <span class="text-5xl font-black text-white tabular-nums leading-none tracking-tight drop-shadow-sm">{{ totalConfirm }}</span>
        <span class="text-sm font-semibold text-primary-100 leading-snug pb-1">
          asistencia{{ totalConfirm === 1 ? '' : 's' }} nueva{{ totalConfirm === 1 ? '' : 's' }} <br/>por enviar
        </span>
      </div>

      <!-- Aviso mínimo -->
      <div class="relative z-10 mt-4 flex items-start gap-2 bg-black/10 rounded-xl p-2.5 border border-white/5 backdrop-blur-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-primary-200 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-[10px] text-primary-100/90 leading-tight">
          Los registros previos no se reenvían. Esta acción no puede deshacerse.
        </p>
      </div>
    </div>

    <!-- ── Lista de confirmados (solo nuevos) ─────────────────────────────── -->
    <div class="flex-1 overflow-y-auto px-4 mt-3 pb-2">
      <ListaAsistencia
        :participantes="confirmados"
        empty-titulo="No hay nuevos por confirmar"
        empty-mensaje="Vuelve atrás para registrar asistencia."
      />
    </div>

    <!-- ── Spacer para botones fijos ──────────────────────────────────────── -->
    <div class="h-36 shrink-0"></div>

    <!-- ── Acciones (Fijas) ───────────────────────────────────────────────── -->
    <div class="fixed bottom-[84px] md:bottom-8 left-0 right-0 max-w-lg mx-auto px-4 pb-4 pt-4 border-t md:border border-primary-950/10 space-y-2.5 bg-white/90 backdrop-blur-xl md:rounded-3xl shadow-[0_-12px_24px_rgba(0,0,0,0.06)] z-40">
      
      <button
        type="button"
        @click="volverAEditar"
        :disabled="store.loading"
        class="w-full py-3.5 rounded-2xl border border-primary-200 bg-primary-50 text-primary-700 font-bold text-sm hover:bg-primary-100 active:scale-[0.98] transition-all duration-150 focus:outline-none disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100 flex items-center justify-center gap-2 shadow-sm"
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
          ? 'bg-surface-100 text-surface-400 cursor-not-allowed border border-surface-200'
          : 'bg-primary-600 hover:bg-primary-700 text-white shadow-lg shadow-primary-600/30 active:scale-[0.98]'"
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
          <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white/25 shadow-inner tabular-nums">
            {{ totalConfirm }}
          </span>
        </template>
      </button>
    </div>

  </div>
</template>
