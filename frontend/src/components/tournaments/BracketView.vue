<script setup>
import { onMounted, computed, ref } from "vue";
import { useTournamentStore } from "@/stores/tournamentStore";
import MatchCard from "./MatchCard.vue";
import LoadingSpinner from "@/components/gerente/ui/LoadingSpinner.vue";
import BadgeStatus from "@/components/gerente/ui/BadgeStatus.vue";

const props = defineProps({
  idTorneo: {
    type: Number,
    required: true,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
});

const store = useTournamentStore();
const isLoading    = ref(true);
const isRefreshing = ref(false);
const loadError    = ref(null);

const torneo  = computed(() => store.torneoActivo || {});
const estatus = computed(() => torneo.value.estado || torneo.value.estatus_torneo);

// Mapa de nombres de fase legibles
const FASE_LABELS = {
  "16VOS":       "Dieciseisavos",
  "8VOS":        "Octavos de Final",
  "CUARTOS":     "Cuartos de Final",
  "SEMIFINALES": "Semifinales",
  "FINAL":       "Final",
};

const getFaseLabel = (fase) => FASE_LABELS[fase] ?? fase;

// Orden canónico de fases
const FASE_ORDER = ["16VOS", "8VOS", "CUARTOS", "SEMIFINALES", "TERCER_LUGAR", "FINAL"];

const activePhases = computed(() => {
  const keys = Object.keys(store.bracket || {});
  return FASE_ORDER.filter((phase) => keys.includes(phase));
});

const hasBracket = computed(() => activePhases.value.length > 0);

// Contador de encuentros finalizados por fase
const getFaseStats = (fase) => {
  const matches = store.bracket[fase] ?? [];
  const total        = matches.length;
  const finalizados  = matches.filter(
    (m) => m.estatus_encuentro === "FINALIZADO" || m.estatus_encuentro === "BYE"
  ).length;
  return { total, finalizados };
};

const loadBracket = async (silent = false) => {
  if (!props.idTorneo) return;

  if (silent) {
    isRefreshing.value = true;
  } else {
    isLoading.value = true;
  }
  loadError.value = null;

  try {
    await store.fetchBracket(props.idTorneo);
  } catch (err) {
    console.error("Error al cargar bracket:", err);
    loadError.value = "No fue posible cargar el bracket. Intenta de nuevo.";
  } finally {
    isLoading.value    = false;
    isRefreshing.value = false;
  }
};

onMounted(() => {
  loadBracket(false);
});

const handleUpdated = () => {
  loadBracket(true);
};
</script>

<template>
  <div class="space-y-5">

    <!-- Carga inicial -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-16 gap-4">
      <LoadingSpinner size="lg" />
      <p class="text-xs font-black uppercase tracking-widest text-surface-400">Cargando bracket...</p>
    </div>

    <!-- Error de carga -->
    <div
      v-else-if="loadError"
      class="p-5 bg-red-50 border border-red-200 rounded-2xl flex items-start gap-4"
    >
      <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-red-100 text-red-600 shrink-0">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
      </div>
      <div class="min-w-0">
        <p class="text-sm font-black text-red-800">Error al cargar el bracket</p>
        <p class="text-xs text-red-600 font-medium mt-0.5">{{ loadError }}</p>
        <button
          @click="loadBracket(false)"
          class="mt-3 px-4 py-1.5 bg-white border border-red-200 text-red-700 rounded-xl text-xs font-bold hover:bg-red-50 transition-colors"
        >
          Reintentar
        </button>
      </div>
    </div>

    <!-- Sin bracket: torneo no programado aún -->
    <div
      v-else-if="!hasBracket && !['PROGRAMADO', 'EN_CURSO', 'FINALIZADO'].includes(estatus)"
      class="p-6 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-4"
    >
      <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-100 text-amber-600 shrink-0">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div>
        <h4 class="text-sm font-black text-amber-900 uppercase tracking-wider">Bracket no generado</h4>
        <p class="text-xs font-medium text-amber-700 mt-1 leading-relaxed">
          El bracket se genera automáticamente al confirmar y programar el torneo,
          siempre que se alcancen los cupos mínimos de participantes activos.
        </p>
      </div>
    </div>

    <!-- Sin bracket pero torneo ya está PROGRAMADO/EN_CURSO (error en generación) -->
    <div
      v-else-if="!hasBracket && ['PROGRAMADO', 'EN_CURSO', 'FINALIZADO'].includes(estatus)"
      class="p-6 bg-surface-50 border border-surface-200 rounded-2xl flex items-start gap-4"
    >
      <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-surface-100 text-surface-500 shrink-0">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M3.75 9h16.5m-16.5 6.75h16.5" />
        </svg>
      </div>
      <div>
        <h4 class="text-sm font-black text-surface-700 uppercase tracking-wider">Sin encuentros registrados</h4>
        <p class="text-xs font-medium text-surface-500 mt-1 leading-relaxed">
          El torneo está en estado <strong>{{ estatus }}</strong> pero no se encontraron encuentros.
          Puede que el bracket no se haya generado correctamente.
        </p>
      </div>
    </div>

    <!-- Bracket generado -->
    <div v-else class="space-y-4">

      <!-- Cabecera del bracket -->
      <div class="flex items-center justify-between border-b border-surface-100 pb-3">
        <div class="flex items-center gap-3">
          <span class="text-xs font-black text-surface-500 uppercase tracking-widest">
            Bracket del Torneo
          </span>
          <BadgeStatus :status="estatus" size="sm" />
        </div>

        <button
          @click="loadBracket(true)"
          :disabled="isRefreshing"
          class="flex items-center gap-2 px-3 py-1.5 bg-white border border-surface-200 text-surface-600 hover:text-primary-600 hover:border-primary-200 rounded-xl text-xs font-bold transition-all shadow-sm disabled:opacity-50"
        >
          <svg
            class="w-3.5 h-3.5"
            :class="{ 'animate-spin': isRefreshing }"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2.5"
          >
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3z" />
          </svg>
          {{ isRefreshing ? "Actualizando..." : "Actualizar" }}
        </button>
      </div>

      <!-- Scroll horizontal del bracket -->
      <div class="overflow-x-auto pb-4 -mx-2 px-2 bracket-scroll">
        <div class="flex gap-6 min-w-max items-start">
          <div
            v-for="(phase, phaseIndex) in activePhases"
            :key="phase"
            class="flex flex-col gap-0 shrink-0 w-[264px]"
          >
            <!-- Encabezado de fase -->
            <div class="mb-3 px-1">
              <div class="flex items-center justify-between">
                <h3 class="text-[11px] font-black text-surface-700 uppercase tracking-widest">
                  {{ getFaseLabel(phase) }}
                </h3>
                <span class="text-[10px] font-bold text-surface-400 tabular-nums">
                  {{ getFaseStats(phase).finalizados }}/{{ getFaseStats(phase).total }}
                </span>
              </div>
              <!-- Barra de progreso de la fase -->
              <div class="mt-1.5 h-1 bg-surface-100 rounded-full overflow-hidden">
                <div
                  class="h-full bg-primary-500 rounded-full transition-all duration-500"
                  :style="{
                    width: getFaseStats(phase).total > 0
                      ? `${(getFaseStats(phase).finalizados / getFaseStats(phase).total) * 100}%`
                      : '0%'
                  }"
                ></div>
              </div>
            </div>

            <!-- Encuentros de la fase -->
            <div class="flex flex-col gap-3 relative">
              <MatchCard
                v-for="match in store.bracket[phase]"
                :key="match.id_encuentro"
                :match="match"
                :readonly="readonly"
                @updated="handleUpdated"
              />
            </div>

            <!-- Conector visual hacia la siguiente fase -->
            <div
              v-if="phaseIndex < activePhases.length - 1"
              class="absolute pointer-events-none"
              aria-hidden="true"
            ></div>
          </div>
        </div>
      </div>

      <!-- Leyenda de estados -->
      <div class="flex flex-wrap items-center gap-x-5 gap-y-2 pt-2 border-t border-surface-100">
        <span class="text-[10px] font-black uppercase tracking-widest text-surface-400">Leyenda:</span>
        <div v-for="item in [
          { label: 'Pendiente',             classes: 'bg-surface-100 text-surface-600 border-surface-200' },
          { label: 'En curso',              classes: 'bg-blue-50 text-blue-700 border-blue-200' },
          { label: 'Pend. validación',      classes: 'bg-amber-50 text-amber-700 border-amber-200' },
          { label: 'Finalizado',            classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
          { label: 'Bye',                   classes: 'bg-purple-50 text-purple-700 border-purple-200' },
          { label: 'Cancelado',             classes: 'bg-red-50 text-red-700 border-red-200' },
        ]" :key="item.label"
          class="flex items-center gap-1.5"
        >
          <span
            class="inline-block px-2 py-0.5 rounded-full border text-[9px] font-extrabold uppercase tracking-wider"
            :class="item.classes"
          >{{ item.label }}</span>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.bracket-scroll::-webkit-scrollbar {
  height: 5px;
}
.bracket-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.bracket-scroll::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}
.bracket-scroll::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
