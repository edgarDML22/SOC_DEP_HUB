<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '@/services/api';
import { useAlerts } from '@/composables/useAlerts';
import { useTournamentStore } from '@/stores/tournamentStore';
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';

const router = useRouter();
const route = useRoute();
const { toastSuccess, toastError } = useAlerts();
const tournamentStore = useTournamentStore();

// --- State Variables ---
const encuentros = ref([]);
const loading = ref(true);
const actionLoading = ref(null); // Stores encounter ID currently being processed

// --- Modals State ---
const showConfirmModal = ref(false);
const showRejectModal = ref(false);
const activeMatch = ref(null);

const torneoIdQuery = computed(() => {
  return route.query.torneo_id ? Number(route.query.torneo_id) : null;
});

const filteredEncuentros = computed(() => {
  if (!torneoIdQuery.value) return encuentros.value;
  return encuentros.value.filter(e => Number(e.id_torneo) === torneoIdQuery.value);
});

const activeTournamentName = computed(() => {
  if (!torneoIdQuery.value || filteredEncuentros.value.length === 0) return '';
  return filteredEncuentros.value[0]?.torneo?.nombre_torneo ?? '';
});

// --- Dictionary Translations ---
const FASE_LABELS = {
  '16VOS':        'Dieciseisavos de Final',
  '8VOS':         'Octavos de Final',
  'CUARTOS':      'Cuartos de Final',
  'SEMIFINALES':  'Semifinales',
  'TERCER_LUGAR': 'Tercer Lugar',
  'FINAL':        'Gran Final',
};

const getFaseLabel = (fase) => FASE_LABELS[fase] ?? fase;

// --- Helpers ---
const getCompName = (comp) => {
  if (!comp) return 'Por definir';
  return comp.nombre_completo || comp.participante?.nombre_completo || `Participante #${comp.id_interno ?? comp.id_participante_torneo}`;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('es-MX', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// --- API Calls ---
const fetchResultadosPendientes = async () => {
  loading.value = true;
  try {
    const response = await api.get('/subgerente/resultados-pendientes');
    encuentros.value = response.data.data ?? response.data ?? [];
  } catch (err) {
    console.error('Error fetching pending results:', err);
    toastError('No se pudo cargar la lista de resultados pendientes.');
  } finally {
    loading.value = false;
  }
};

const confirmApprove = (match) => {
  activeMatch.value = match;
  showConfirmModal.value = true;
};

const handleApprove = async () => {
  if (!activeMatch.value) return;
  const matchId = activeMatch.value.id_encuentro;
  const torneoId = activeMatch.value.id_torneo; // Guardar antes de limpiar
  actionLoading.value = matchId;
  showConfirmModal.value = false;

  try {
    await api.patch(`/encuentros/${matchId}/validar`);
    toastSuccess('Resultado validado con éxito. El bracket ha avanzado.');
    // Remove from local list
    encuentros.value = encuentros.value.filter(e => e.id_encuentro !== matchId);
    // Refrescar el bracket en el store para que BracketView refleje el ganador
    if (torneoId) {
      tournamentStore.fetchBracket(torneoId);
    }
  } catch (err) {
    console.error('Error validating match:', err);
    const msg = err.response?.data?.message ?? 'Ocurrió un error al validar el encuentro.';
    toastError(msg);
  } finally {
    actionLoading.value = null;
    activeMatch.value = null;
  }
};

const confirmReject = (match) => {
  activeMatch.value = match;
  showRejectModal.value = true;
};

const handleReject = async () => {
  if (!activeMatch.value) return;
  const matchId = activeMatch.value.id_encuentro;
  actionLoading.value = matchId;
  showRejectModal.value = false;

  try {
    await api.patch(`/encuentros/${matchId}/rechazar`);
    toastSuccess('Resultado rechazado. El encuentro ha vuelto a estar En Curso.');
    // Remove from local list
    encuentros.value = encuentros.value.filter(e => e.id_encuentro !== matchId);
  } catch (err) {
    console.error('Error rejecting match:', err);
    const msg = err.response?.data?.message ?? 'Ocurrió un error al rechazar el encuentro.';
    toastError(msg);
  } finally {
    actionLoading.value = null;
    activeMatch.value = null;
  }
};

// --- Initialization ---
onMounted(() => {
  // Check if role is subgerente
  const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
  if (userData.rol !== 'subgerente') {
    toastError('Acceso denegado. Esta sección es exclusiva para el Subgerente.');
    router.push('/admin/tournaments');
    return;
  }
  fetchResultadosPendientes();
});
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-6xl mx-auto space-y-8">
      
      <!-- CABECERA -->
      <AdminPageHeader 
        title="Resultados por Validar" 
        :subtitle="torneoIdQuery && activeTournamentName ? `Mostrando resultados pendientes para el torneo: ${activeTournamentName}` : 'Valida o rechaza los resultados preliminares reportados por los árbitros.'"
      >
        <button @click="router.push('/admin/tournaments')" 
                class="flex items-center gap-2 px-4 py-2 rounded-xl border border-surface-200 bg-white text-surface-700
                 text-sm font-bold hover:bg-surface-50 transition-colors shadow-sm cursor-pointer">
          <i class="fas fa-arrow-left text-surface-500"></i>
          Volver a Torneos
        </button>
      </AdminPageHeader>

      <!-- VISTA DE CARGA -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20">
        <LoadingSpinner />
        <p class="text-sm text-surface-500 mt-4 font-semibold">Cargando resultados pendientes...</p>
      </div>

      <!-- VISTA VACÍA GENERAL -->
      <div v-else-if="encuentros.length === 0" class="bg-white border border-surface-200 rounded-2xl shadow-sm p-16 flex flex-col items-center justify-center text-center">
        <div class="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-lg font-black text-surface-900">¡Al corriente!</h3>
        <p class="text-sm text-surface-500 mt-1 max-w-sm">No hay resultados de encuentros pendientes de validación en este momento.</p>
        <button @click="router.push('/admin/tournaments')" class="mt-5 px-5 py-2.5 bg-surface-950 text-white rounded-xl text-xs font-bold hover:bg-surface-800 transition-colors">
          Ir a Torneos
        </button>
      </div>

      <!-- VISTA VACÍA FILTRADA POR TORNEO -->
      <div v-else-if="filteredEncuentros.length === 0" class="bg-white border border-surface-200 rounded-2xl shadow-sm p-16 flex flex-col items-center justify-center text-center">
        <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <h3 class="text-lg font-black text-surface-900">Sin pendientes para este torneo</h3>
        <p class="text-sm text-surface-500 mt-1 max-w-sm font-medium">No hay resultados de encuentros pendientes de validación en este torneo.</p>
        <div class="flex gap-3 justify-center mt-5">
          <button @click="router.push({ path: '/admin/tournaments/resultados-pendientes' })" class="px-5 py-2.5 bg-surface-900 hover:bg-surface-800 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
            Ver Todos los Pendientes
          </button>
          <button @click="router.push('/admin/tournaments')" class="px-5 py-2.5 bg-white border border-surface-200 text-surface-700 rounded-xl text-xs font-bold hover:bg-surface-50 transition-all shadow-sm">
            Ir a Torneos
          </button>
        </div>
      </div>

      <!-- LISTA DE ENCUENTROS -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="enc in filteredEncuentros" :key="enc.id_encuentro" 
             class="bg-white border border-surface-200 rounded-2xl shadow-sm overflow-hidden flex flex-col hover:border-surface-300 hover:shadow-md transition-all duration-200">
          
          <!-- Header Encuentro -->
          <div class="bg-surface-50/80 px-5 py-4 border-b border-surface-100 flex items-center justify-between">
            <div class="min-w-0">
              <h4 class="font-extrabold text-surface-900 text-sm truncate leading-tight">
                {{ enc.torneo?.nombre_torneo ?? 'Torneo' }}
              </h4>
              <p class="text-[10px] text-surface-400 font-bold uppercase tracking-wider mt-0.5">
                {{ getFaseLabel(enc.fase_bracket) }}
              </p>
            </div>
            <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full whitespace-nowrap">
              Pendiente
            </span>
          </div>

          <!-- Marcadores y Competidores -->
          <div class="p-6 flex-1 flex flex-col justify-center">
            <div class="flex items-center justify-between gap-4">
              <!-- Competidor 1 -->
              <div class="flex-1 min-w-0">
                <p class="text-[9px] font-black uppercase tracking-widest text-surface-400 mb-1">Competidor A</p>
                <h5 class="font-black text-surface-900 text-sm md:text-base truncate leading-tight" :title="getCompName(enc.competidor1)">
                  {{ getCompName(enc.competidor1) }}
                </h5>
                <span class="text-3xl font-black text-primary-600 block mt-2 tabular-nums">
                  {{ enc.resultado_comp1 ?? 0 }}
                </span>
              </div>

              <!-- Separador VS -->
              <div class="flex flex-col items-center shrink-0 px-2 select-none">
                <div class="w-px h-5 bg-surface-200"></div>
                <span class="text-[11px] font-black text-surface-300 tracking-wider">VS</span>
                <div class="w-px h-5 bg-surface-200"></div>
              </div>

              <!-- Competidor 2 -->
              <div class="flex-1 min-w-0 text-right">
                <p class="text-[9px] font-black uppercase tracking-widest text-surface-400 mb-1">Competidor B</p>
                <h5 class="font-black text-surface-900 text-sm md:text-base truncate leading-tight" :title="getCompName(enc.competidor2)">
                  {{ getCompName(enc.competidor2) }}
                </h5>
                <span class="text-3xl font-black text-primary-600 block mt-2 tabular-nums">
                  {{ enc.resultado_comp2 ?? 0 }}
                </span>
              </div>
            </div>

            <!-- Detalles Adicionales -->
            <div class="mt-6 pt-4 border-t border-surface-100 grid grid-cols-2 gap-4 text-xs font-semibold text-surface-500">
              <div class="flex items-center gap-1.5 min-w-0">
                <i class="fas fa-map-marker-alt text-surface-400 shrink-0"></i>
                <span class="truncate">{{ enc.espacio_fisico?.nombre_espacio ?? `Espacio #${enc.id_espacio}` }}</span>
              </div>
              <div class="flex items-center gap-1.5 justify-end">
                <i class="fas fa-calendar-alt text-surface-400 shrink-0"></i>
                <span>{{ formatDate(enc.fecha_hora_inicio) }}</span>
              </div>
            </div>
          </div>

          <!-- Botones de Acción -->
          <div class="bg-surface-50 px-5 py-4 border-t border-surface-100 flex items-center gap-3">
            <button @click="confirmReject(enc)" 
                    :disabled="actionLoading !== null"
                    class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl border border-red-200 bg-red-50 text-red-700
                     text-xs font-black hover:bg-red-100 transition-colors shadow-sm cursor-pointer disabled:opacity-50">
              <i class="fas fa-times-circle"></i>
              Rechazar
            </button>
            <button @click="confirmApprove(enc)"
                    :disabled="actionLoading !== null"
                    class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-600 text-white
                     text-xs font-black hover:bg-emerald-700 transition-colors shadow-sm cursor-pointer disabled:opacity-50">
              <i class="fas fa-check-circle"></i>
              Aprobar
            </button>
          </div>

        </div>
      </div>

    </div>

    <!-- MODAL DE CONFIRMACIÓN APROBAR -->
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-xs">
        <div class="bg-white border border-surface-200 rounded-2xl max-w-md w-full shadow-2xl p-6 space-y-4">
          <div class="flex items-center gap-3 text-emerald-600">
            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center">
              <i class="fas fa-check-circle text-lg"></i>
            </div>
            <h3 class="text-base font-black text-surface-900">Validar Resultado</h3>
          </div>
          <p class="text-sm text-surface-500 leading-relaxed">
            ¿Estás seguro de que deseas aprobar este resultado? Al hacerlo, se formalizará el marcador y el competidor ganador avanzará a la siguiente fase del torneo de forma definitiva.
          </p>
          <div class="flex justify-end gap-3 pt-2">
            <button @click="showConfirmModal = false" class="px-4 py-2 rounded-xl border border-surface-200 text-surface-700 text-xs font-bold hover:bg-surface-50 cursor-pointer">
              Cancelar
            </button>
            <button @click="handleApprove" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer">
              Aprobar y Avanzar
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- MODAL DE CONFIRMACIÓN RECHAZAR -->
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-xs">
        <div class="bg-white border border-surface-200 rounded-2xl max-w-md w-full shadow-2xl p-6 space-y-4">
          <div class="flex items-center gap-3 text-red-600">
            <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center">
              <i class="fas fa-exclamation-triangle text-lg"></i>
            </div>
            <h3 class="text-base font-black text-surface-900">Rechazar Resultado</h3>
          </div>
          <p class="text-sm text-surface-500 leading-relaxed">
            ¿Estás seguro de que deseas rechazar este resultado preliminar? Al hacerlo, los marcadores volverán a estar vacíos y el estatus del encuentro regresará a <strong>En Curso</strong> para que sea reportado nuevamente.
          </p>
          <div class="flex justify-end gap-3 pt-2">
            <button @click="showRejectModal = false" class="px-4 py-2 rounded-xl border border-surface-200 text-surface-700 text-xs font-bold hover:bg-surface-50 cursor-pointer">
              Cancelar
            </button>
            <button @click="handleReject" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold cursor-pointer">
              Rechazar y Revertir
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </main>
</template>
