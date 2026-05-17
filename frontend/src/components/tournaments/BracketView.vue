<script setup>
import { onMounted, computed, ref } from "vue";
import { useTournamentStore } from "@/stores/tournamentStore";
import MatchCard from "./MatchCard.vue";
import LoadingSpinner from "@/components/gerente/ui/LoadingSpinner.vue";

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
const isRefreshing = ref(false);

const torneo = computed(() => store.torneoActivo || {});
const estatus = computed(() => torneo.value.estado || torneo.value.estatus_torneo);

// Computar orden de fases a mostrar según existan en el bracket
const activePhases = computed(() => {
  const order = ["16VOS", "8VOS", "CUARTOS", "SEMIFINALES", "TERCER_LUGAR", "FINAL"];
  const keys = Object.keys(store.bracket || {});
  return order.filter((phase) => keys.includes(phase));
});

const hasBracket = computed(() => activePhases.value.length > 0);

const loadBracket = async () => {
  if (props.idTorneo) {
    isRefreshing.value = true;
    try {
      await store.fetchBracket(props.idTorneo);
    } catch (err) {
      console.error("Error al cargar bracket:", err);
    } finally {
      isRefreshing.value = false;
    }
  }
};

onMounted(() => {
  loadBracket();
});

const handleUpdated = () => {
  loadBracket();
};
</script>

<template>
  <div class="space-y-6">
    <!-- Banner Informativo cuando no hay Bracket -->
    <div
      v-if="!hasBracket && !['PROGRAMADO', 'EN_CURSO'].includes(estatus)"
      class="p-6 bg-amber-50 border border-amber-200 rounded-2xl flex items-center gap-4 text-amber-800 animate-scale-in"
    >
      <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-amber-100 text-amber-600 shrink-0">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      </div>
      <div>
        <h4 class="text-sm font-black uppercase tracking-wider">Bracket no generado</h4>
        <p class="text-xs font-semibold text-amber-700 mt-1 leading-relaxed">
          El bracket se generará automáticamente al confirmar y programar el torneo cuando se cumplan los cupos mínimos de participantes.
        </p>
      </div>
    </div>

    <!-- Bracket Layout -->
    <div v-else class="space-y-4">
      <!-- Acciones de Encabezado -->
      <div class="flex items-center justify-between border-b border-surface-100 pb-3">
        <span class="text-xs font-black text-surface-500 uppercase tracking-widest">Encuentros del Bracket</span>
        <button
          @click="loadBracket"
          :disabled="isRefreshing"
          class="flex items-center gap-2 px-3 py-1.5 bg-white border border-surface-200 text-surface-600 hover:text-primary-600 hover:border-primary-200 rounded-xl text-xs font-bold transition-all shadow-sm disabled:opacity-50"
        >
          <svg
            class="w-4 h-4"
            :class="{ 'animate-spin': isRefreshing }"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3z"
            />
          </svg>
          {{ isRefreshing ? "Actualizando..." : "Actualizar" }}
        </button>
      </div>

      <!-- Scroll Horizontal del Bracket -->
      <div class="overflow-x-auto pb-6 -mx-6 px-6 scrollbar-thin">
        <div class="flex gap-8 min-w-max items-start">
          <div
            v-for="phase in activePhases"
            :key="phase"
            class="flex flex-col gap-6 w-[260px] shrink-0"
          >
            <!-- Título de Fase -->
            <div class="sticky top-0 bg-surface-50/80 backdrop-blur-md py-2 border-b border-surface-200 z-10">
              <h3 class="text-center text-xs font-black text-surface-800 uppercase tracking-widest">
                {{ phase }}
              </h3>
            </div>

            <!-- Listado de Encuentros de la Fase -->
            <div class="flex flex-col gap-4">
              <MatchCard
                v-for="match in store.bracket[phase]"
                :key="match.id_encuentro"
                :match="match"
                :readonly="readonly"
                @updated="handleUpdated"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
  height: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
