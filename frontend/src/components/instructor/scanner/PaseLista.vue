<script setup>
import { ref, computed } from 'vue'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import ContadorAforo from './ContadorAforo.vue'
import ListaAsistencia from './ListaAsistencia.vue'

const store = useScannerStore()

// Segmento activo: 'INSCRITOS' (pendientes) | 'CONFIRMADOS'
const segmento = ref('INSCRITOS')

const sesion = computed(() => store.sesionActiva)

const participantesVisibles = computed(() =>
    segmento.value === 'INSCRITOS'
        ? store.inscritosNoConfirmados
        : store.inscritosConfirmados
)

const totalPendientes  = computed(() => store.inscritosNoConfirmados.length)
const totalConfirmados = computed(() => store.inscritosConfirmados.length)

function seguirEscaneando() {
    store.paso = 'ESCANER_ACTIVO'
}

function irAConfirmacion() {
    if (totalConfirmados.value === 0) return
    store.paso = 'CONFIRMACION_PREVIA'
}
</script>

<template>
  <div class="flex flex-col h-full">

    <!-- ── Chip de sesión activa ──────────────────────────────────────────── -->
    <div
      v-if="sesion"
      class="flex items-center gap-3 px-4 py-3 bg-primary-50 border border-primary-100 rounded-2xl mx-4 mt-4"
    >
      <div class="w-2 h-2 rounded-full bg-primary-500 shrink-0 animate-pulse" />
      <div class="min-w-0 flex-1">
        <p class="text-[10px] font-bold text-primary-500 uppercase tracking-widest">Pase de lista activo</p>
        <p class="text-sm font-bold text-primary-800 truncate leading-tight mt-0.5">
          {{ sesion.disciplina }} · {{ sesion.hora_inicio }}–{{ sesion.hora_fin }}
        </p>
      </div>
      <span
        class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full"
        :class="store.esSesionCerrada
          ? 'bg-primary-100 text-primary-700'
          : 'bg-surface-100 text-surface-500'"
      >
        {{ store.esSesionCerrada ? 'Clase cerrada' : 'Clase abierta' }}
      </span>
    </div>

    <!-- ── Contador de aforo (solo abierta con cupo) ──────────────────────── -->
    <div v-if="!store.esSesionCerrada && sesion?.cupo_maximo" class="px-4 mt-3">
      <ContadorAforo
        :actual="totalConfirmados"
        :maximo="sesion.cupo_maximo"
      />
    </div>

    <!-- ── Segmented Control ──────────────────────────────────────────────── -->
    <div class="flex bg-primary-950/5 p-1 rounded-2xl gap-1 mx-4 mt-3">
      <button
        type="button"
        @click="segmento = 'INSCRITOS'"
        class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 focus:outline-none"
        :class="segmento === 'INSCRITOS'
          ? 'bg-white text-primary-800 shadow-sm shadow-primary-900/5'
          : 'text-surface-500 hover:text-surface-700'"
      >
        <span>{{ store.esSesionCerrada ? 'Inscritos' : 'Pendientes' }}</span>
        <span
          class="text-[10px] font-bold px-1.5 py-0.5 rounded-md tabular-nums min-w-5"
          :class="segmento === 'INSCRITOS'
            ? 'bg-primary-100 text-primary-700'
            : 'bg-surface-200/60 text-surface-500'"
        >
          {{ totalPendientes }}
        </span>
      </button>

      <button
        type="button"
        @click="segmento = 'CONFIRMADOS'"
        class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 focus:outline-none"
        :class="segmento === 'CONFIRMADOS'
          ? 'bg-white text-primary-800 shadow-sm shadow-primary-900/5'
          : 'text-surface-500 hover:text-surface-700'"
      >
        <span>Confirmados</span>
        <span
          class="text-[10px] font-bold px-1.5 py-0.5 rounded-md tabular-nums min-w-5"
          :class="segmento === 'CONFIRMADOS'
            ? 'bg-green-100 text-green-700'
            : 'bg-surface-200/60 text-surface-500'"
        >
          {{ totalConfirmados }}
        </span>
      </button>
    </div>

    <!-- ── Lista / Loading ────────────────────────────────────────────────── -->
    <div class="flex-1 overflow-y-auto px-4 mt-3 pb-2">
      <div v-if="store.listaLoading" class="space-y-2">
        <div v-for="i in 4" :key="i" class="h-16 rounded-2xl bg-surface-100 animate-pulse" />
      </div>

      <ListaAsistencia
        v-else
        :participantes="participantesVisibles"
        :empty-titulo="segmento === 'INSCRITOS'
          ? (store.esSesionCerrada ? 'Sin inscritos pendientes' : 'Sin pendientes')
          : 'Aún no hay confirmados'"
        :empty-mensaje="segmento === 'CONFIRMADOS'
          ? 'Escanea un código QR para registrar asistencia.'
          : ''"
      />
    </div>

    <!-- ── Acciones inferiores ────────────────────────────────────────────── -->
    <div class="px-4 pb-6 pt-3 border-t border-primary-950/8 space-y-2 bg-white">
      <button
        type="button"
        @click="seguirEscaneando"
        :disabled="store.aforoLleno"
        class="w-full py-3 rounded-2xl border border-primary-200 bg-primary-50 text-primary-700 font-bold text-sm hover:bg-primary-100 active:scale-[0.98] transition-all duration-150 focus:outline-none disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100"
      >
        Seguir escaneando
      </button>

      <button
        type="button"
        @click="irAConfirmacion"
        :disabled="totalConfirmados === 0"
        class="w-full py-4 rounded-2xl font-bold text-sm transition-all duration-150 focus:outline-none flex items-center justify-center gap-2"
        :class="totalConfirmados === 0
          ? 'bg-surface-100 text-surface-300 cursor-not-allowed'
          : 'bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-600/20 active:scale-[0.98]'"
      >
        <span>Confirmar Lista</span>
        <span
          v-if="totalConfirmados > 0"
          class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white/20 tabular-nums"
        >
          {{ totalConfirmados }}
        </span>
      </button>
    </div>

  </div>
</template>
