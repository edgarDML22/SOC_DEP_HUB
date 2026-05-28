<script setup>
import { ref, computed } from 'vue'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import ContadorAforo from './ContadorAforo.vue'
import ListaAsistencia from './ListaAsistencia.vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'

const store = useScannerStore()

// Segmento activo: 'INSCRITOS' (pendientes) | 'CONFIRMADOS' (históricos + nuevos)
const segmento = ref('INSCRITOS')

const sesion = computed(() => store.sesionActiva)

const participantesVisibles = computed(() =>
    segmento.value === 'INSCRITOS'
        ? store.inscritosPendientes
        : [...store.inscritosHistoricos, ...store.inscritosNuevos]
)

const totalPendientes  = computed(() => store.inscritosPendientes.length)
// Sumar históricos (asistencia previa) + nuevos (escaneados en esta sesión del Hub)
const totalConfirmados = computed(
    () => store.inscritosHistoricos.length + store.inscritosNuevos.length
)
// El botón "Confirmar Lista" SOLO envía los nuevos escaneados en este Hub
const totalNuevos      = computed(() => store.inscritosNuevos.length)

function seguirEscaneando() {
    store.paso = 'ESCANER_ACTIVO'
}

function irAConfirmacion() {
    if (totalNuevos.value === 0) return
    store.paso = 'CONFIRMACION_PREVIA'
}
</script>

<template>
  <div class="flex flex-col h-full">

    <!-- ── Chip de sesión activa ──────────────────────────────────────────── -->
    <div
      v-if="sesion"
      class="mx-4 mt-4 relative bg-gradient-to-r from-primary-600 to-primary-700 rounded-2xl p-4 shadow-lg shadow-primary-900/15 overflow-hidden border border-primary-500"
    >
      <!-- Decorative background -->
      <div class="absolute -right-6 -top-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
      
      <div class="relative z-10 flex items-start gap-3.5">
        <!-- Animated Discipline Icon -->
        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm border border-white/30 flex items-center justify-center shrink-0 shadow-inner relative">
          <!-- Glow pulse -->
          <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-xl bg-white opacity-40"></span>
          </div>
          <DisciplineIcon :name="sesion.disciplina" class="w-7 h-7 text-white drop-shadow-md z-10" />
        </div>

        <div class="min-w-0 flex-1 pt-0.5">
          <p class="text-[9px] font-extrabold text-primary-200 uppercase tracking-[0.15em] mb-1">
            Pase de lista activo
          </p>
          <p class="text-base font-black text-white truncate leading-none drop-shadow-sm">
            {{ sesion.disciplina }}
          </p>
          <div class="flex items-center gap-2 mt-2">
            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-white/90 bg-black/15 px-2 py-0.5 rounded-md backdrop-blur-sm">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ sesion.hora_inicio }} – {{ sesion.hora_fin }}
            </span>
          </div>
        </div>

        <span
          class="shrink-0 text-[9px] font-bold uppercase tracking-wider px-2 py-1 rounded-md shadow-sm"
          :class="store.esSesionCerrada
            ? 'bg-violet-600 text-white'
            : 'bg-emerald-100 text-emerald-800'"
        >
          {{ store.esSesionCerrada ? 'Cerrada' : 'Abierta' }}
        </span>
      </div>
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

    <!-- ── Spacer para botones fijos ──────────────────────────────────────── -->
    <div class="h-36 shrink-0"></div>

    <!-- ── Acciones inferiores (Fijas) ────────────────────────────────────── -->
    <div class="fixed bottom-[84px] md:bottom-8 left-0 right-0 max-w-lg mx-auto px-4 pb-4 pt-4 border-t md:border border-primary-950/10 space-y-2.5 bg-white/90 backdrop-blur-xl md:rounded-3xl shadow-[0_-12px_24px_rgba(0,0,0,0.06)] z-40">
      <button
        type="button"
        @click="seguirEscaneando"
        :disabled="store.aforoLleno"
        class="w-full py-3.5 rounded-2xl border border-primary-200 bg-primary-50 text-primary-700 font-bold text-sm hover:bg-primary-100 active:scale-[0.98] transition-all duration-150 focus:outline-none disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100 shadow-sm"
      >
        Escanear
      </button>

      <button
        type="button"
        @click="irAConfirmacion"
        :disabled="totalNuevos === 0"
        class="w-full py-4 rounded-2xl font-bold text-sm transition-all duration-150 focus:outline-none flex items-center justify-center gap-2"
        :class="totalNuevos === 0
          ? 'bg-surface-100 text-surface-400 cursor-not-allowed border border-surface-200'
          : 'bg-primary-600 hover:bg-primary-700 text-white shadow-lg shadow-primary-600/30 active:scale-[0.98]'"
      >
        <span>Confirmar Lista</span>
        <span
          class="text-[11px] font-bold px-2 py-0.5 rounded-full tabular-nums shadow-inner"
          :class="totalNuevos === 0 ? 'bg-surface-200 text-surface-500' : 'bg-white/25 text-white'"
        >
          {{ totalNuevos }}
        </span>
      </button>
    </div>

  </div>
</template>
