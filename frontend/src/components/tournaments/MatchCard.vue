<script setup>
import { ref, computed } from "vue";
import ReportResultModal from "./ReportResultModal.vue";

const props = defineProps({
  match: {
    type: Object,
    required: true,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["updated"]);

const showReportModal = ref(false);

// Obtener datos del usuario logueado
const userDataStr = localStorage.getItem("user_data");
const userData = userDataStr ? JSON.parse(userDataStr) : null;
const currentUserId = computed(() => (userData ? Number(userData.id) : null));

// Nombres de los competidores
const getCompetidorName = (comp, esBye) => {
  if (esBye) return "BYE";
  if (!comp) return "Por definir";
  return comp.participante?.nombre_completo || `Participante #${comp.id_interno || comp.id_participante_torneo}`;
};

const comp1Name = computed(() => getCompetidorName(props.match.competidor1, props.match.es_bye));
const comp2Name = computed(() => getCompetidorName(props.match.competidor2, props.match.es_bye));

// Ganador del encuentro
const isComp1Winner = computed(() => {
  return props.match.estatus_encuentro === "FINALIZADO" && props.match.id_ganador === props.match.competidor_1_id;
});

const isComp2Winner = computed(() => {
  return props.match.estatus_encuentro === "FINALIZADO" && props.match.id_ganador === props.match.competidor_2_id;
});

// Marcadores
const score1 = computed(() => props.match.resultado_comp1 ?? "—");
const score2 = computed(() => props.match.resultado_comp2 ?? "—");

// Estatus del encuentro
const statusLabel = computed(() => {
  const map = {
    PENDIENTE: "Pendiente",
    EN_CURSO: "En curso",
    BYE: "Bye",
    RESULTADO_PENDIENTE_VALIDACION: "Pendiente validación",
    FINALIZADO: "Finalizado",
  };
  return map[props.match.estatus_encuentro] ?? props.match.estatus_encuentro;
});

const statusClasses = computed(() => {
  const map = {
    PENDIENTE: "bg-surface-100 text-surface-600 border-surface-200",
    EN_CURSO: "bg-blue-50 text-blue-700 border-blue-200",
    BYE: "bg-purple-50 text-purple-700 border-purple-200",
    RESULTADO_PENDIENTE_VALIDACION: "bg-amber-50 text-amber-700 border-amber-200",
    FINALIZADO: "bg-emerald-50 text-emerald-700 border-emerald-200",
  };
  return map[props.match.estatus_encuentro] ?? "bg-surface-100 text-surface-600 border-surface-200";
});

// Condición para mostrar el botón de reporte
const isAssignedReferee = computed(() => {
  if (!props.match.id_arbitro_asignado || !currentUserId.value) return false;
  return Number(props.match.id_arbitro_asignado) === currentUserId.value;
});

const canReport = computed(() => {
  return !props.readonly && props.match.estatus_encuentro === "EN_CURSO" && isAssignedReferee.value;
});
</script>

<template>
  <div
    class="bg-white border rounded-2xl p-4 shadow-sm hover:shadow-md hover:border-surface-300 transition-all space-y-3 relative group"
    :class="{ 'opacity-50 select-none': match.es_bye }"
  >
    <!-- Encabezado de la Card -->
    <div class="flex items-center justify-between gap-2 border-b border-surface-100 pb-2">
      <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Encuentro #{{ match.numero_encuentro }}</span>
      <span class="px-2 py-0.5 rounded-lg text-[9px] font-extrabold uppercase tracking-wider border" :class="statusClasses">
        {{ statusLabel }}
      </span>
    </div>

    <!-- Competidores y Marcadores -->
    <div class="space-y-2">
      <!-- Competidor 1 -->
      <div class="flex items-center justify-between gap-4 py-1">
        <div class="flex items-center gap-1.5 min-w-0">
          <span
            class="text-xs font-bold truncate"
            :class="{ 'text-emerald-600 font-extrabold': isComp1Winner, 'text-surface-800': !isComp1Winner, 'text-surface-400 font-medium italic': !match.competidor1 }"
            :title="comp1Name"
          >
            {{ comp1Name }}
          </span>
          <svg v-if="isComp1Winner" class="w-4 h-4 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>
        <span class="text-xs font-mono font-black" :class="{ 'text-emerald-600': isComp1Winner, 'text-surface-500': !isComp1Winner }">
          {{ score1 }}
        </span>
      </div>

      <!-- Divisor sutil -->
      <div class="h-[1px] bg-surface-100"></div>

      <!-- Competidor 2 -->
      <div class="flex items-center justify-between gap-4 py-1">
        <div class="flex items-center gap-1.5 min-w-0">
          <span
            class="text-xs font-bold truncate"
            :class="{ 'text-emerald-600 font-extrabold': isComp2Winner, 'text-surface-800': !isComp2Winner, 'text-surface-400 font-medium italic': !match.competidor2 }"
            :title="comp2Name"
          >
            {{ comp2Name }}
          </span>
          <svg v-if="isComp2Winner" class="w-4 h-4 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>
        <span class="text-xs font-mono font-black" :class="{ 'text-emerald-600': isComp2Winner, 'text-surface-500': !isComp2Winner }">
          {{ score2 }}
        </span>
      </div>
    </div>

    <!-- Botón de Reporte (Condicional para Árbitro Asignado) -->
    <div v-if="canReport" class="pt-2">
      <button
        @click="showReportModal = true"
        class="w-full py-2 bg-primary-50 text-primary-700 hover:bg-primary-100 border border-primary-200 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all"
      >
        Reportar Marcador
      </button>
    </div>

    <!-- Indicador de Árbitro -->
    <div v-else-if="isAssignedReferee && match.estatus_encuentro === 'PENDIENTE'" class="text-[9px] font-semibold text-primary-500 text-center italic mt-1">
      Árbitro asignado
    </div>

    <!-- Modal Reportar Resultado -->
    <ReportResultModal v-if="showReportModal" :match="match" @close="showReportModal = false" @updated="emit('updated')" />
  </div>
</template>
