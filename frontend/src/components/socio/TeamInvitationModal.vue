<script setup>
import { ref, watch } from 'vue';
import { useSocioTorneoStore } from '@/stores/socioTorneoStore';
import api from '@/services/api';
import Swal from 'sweetalert2';
import { useAlerts } from '@/composables/useAlerts';

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
};

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  teamId: {
    type: [Number, String],
    required: true
  }
});

const emit = defineEmits(['close', 'responded']);

const tournamentStore = useSocioTorneoStore();
const { toastInfo, toastError } = useAlerts();

const isLoading = ref(true);
const error = ref(null);
const teamDetails = ref(null);
const processing = ref(false);

const fetchTeamDetails = async () => {
  isLoading.value = true;
  error.value = null;
  console.debug('[TeamInvitationModal] fetchTeamDetails teamId=', props.teamId);
  try {
    const response = await api.get(`/equipos/${props.teamId}`);
    teamDetails.value = response.data?.data || response.data;
  } catch (err) {
    error.value = err.response?.data?.message || "Error al cargar los detalles de la invitación";
    console.error('[TeamInvitationModal] fetch error', err);
  } finally {
    isLoading.value = false;
  }
};

watch(() => props.isOpen, (newVal) => {
  console.debug('[TeamInvitationModal] watch isOpen=', newVal, ' teamId=', props.teamId);
  if (newVal && props.teamId) {
    fetchTeamDetails();
  } else {
    teamDetails.value = null;
    error.value = null;
  }
}, { immediate: true });

const handleResponse = async (decision) => {
  const isAccept = decision === 'ACEPTAR';
  
  const result = await Swal.fire({
    title: isAccept ? '¿Aceptar Invitación?' : '¿Rechazar Invitación?',
    text: isAccept 
      ? `Estás a punto de aceptar la invitación para unirte al equipo en el torneo "${teamDetails.value?.torneo?.nombre_torneo || ''}".`
      : 'Esta acción notificará al capitán que has rechazado la invitación.',
    icon: isAccept ? 'question' : 'warning',
    showCancelButton: true,
    confirmButtonText: isAccept ? 'Sí, aceptar' : 'Sí, rechazar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: isAccept ? '#059669' : '#e11d48',
    customClass: {
      popup: '!rounded-3xl !shadow-2xl !p-6 border border-surface-200',
      title: '!text-xl !font-black !text-surface-900 !m-0 !pb-2',
      htmlContainer: '!text-sm !font-medium !text-surface-500 !m-0',
      actions: '!mt-6 !flex !gap-3 !w-full !justify-center',
      confirmButton: '!px-6 !py-3 !text-white !font-bold !rounded-xl !shadow-sm !w-full sm:!w-auto transition-colors',
      cancelButton: '!px-6 !py-3 !bg-white !border !border-surface-200 !text-surface-700 hover:!bg-surface-50 !font-bold !rounded-xl !m-0 !w-full sm:!w-auto transition-colors'
    }
  });

  if (!result.isConfirmed) {
    return;
  }

  processing.value = true;
  try {
    const id_torneo = teamDetails.value?.torneo?.id_torneo || 0;
    await tournamentStore.responderInvitacion(id_torneo, props.teamId, { decision });
    
    toastInfo('¡Listo!', `Invitación ${isAccept ? 'aceptada' : 'rechazada'} exitosamente.`, 'success');
    
    emit('responded', decision);
    handleClose();
  } catch (err) {
    console.error("Failed to respond to invitation", err);
    toastError(tournamentStore.error || "Ocurrió un error al procesar tu respuesta");
  } finally {
    processing.value = false;
  }
};

const handleClose = () => {
  teamDetails.value = null;
  error.value = null;
  emit('close');
};
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity duration-300"
    @click.self="handleClose"
  >
    <div
      class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl border border-surface-100 transform scale-100 transition-all duration-300 flex flex-col max-h-[90vh]"
    >
      <!-- Cabecera -->
      <div class="px-6 py-5 border-b border-surface-100 bg-surface-50 flex items-center justify-between shrink-0">
        <div>
          <h3 class="text-xl font-extrabold text-surface-900 m-0">Invitación a Torneo</h3>
          <p class="text-xs font-semibold text-primary-600 mt-1 uppercase tracking-wider">
            {{ teamDetails?.capitan?.nombre_completo || '' }} te ha invitado a ser su pareja
          </p>
        </div>
        <button
          @click="handleClose"
          class="p-2 text-surface-400 hover:text-surface-600 rounded-full hover:bg-surface-100 transition-colors focus:outline-none"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Cuerpo del Modal -->
      <div class="p-6 overflow-y-auto scrollbar-thin flex-1 space-y-6">
        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-8">
          <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="bg-rose-50 text-rose-700 p-5 rounded-2xl border border-rose-100 text-center space-y-3">
          <svg class="mx-auto h-10 w-10 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <p class="text-sm font-bold">{{ error }}</p>
        </div>

        <!-- Details -->
        <template v-else-if="teamDetails">
          <!-- Captain Info -->
          <div class="bg-primary-50/50 border border-primary-100 rounded-2xl p-5 flex items-center gap-5">
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-2xl font-extrabold shadow-md shrink-0">
              {{ teamDetails.capitan?.nombre_completo?.charAt(0).toUpperCase() || '?' }}
            </div>
            <div>
              <p class="text-xs text-primary-600 font-extrabold uppercase tracking-wider mb-1">Capitán del Equipo</p>
              <h3 class="text-lg font-extrabold text-surface-900">{{ teamDetails.capitan?.nombre_completo || 'Capitán desconocido' }}</h3>
              <p class="text-xs text-surface-500 font-semibold mt-0.5">Te invita a ser su compañero/a de equipo</p>
            </div>
          </div>

          <!-- Tournament Info -->
          <div class="border border-surface-150 rounded-2xl p-4 space-y-3">
            <span class="text-xs font-extrabold text-surface-500 uppercase tracking-wider">Detalles del Torneo</span>
            <div class="space-y-2">
              <!-- Tournament Name -->
              <div class="bg-surface-50 p-3 rounded-xl border border-surface-100">
                <p class="text-[11px] font-bold text-surface-400 uppercase tracking-wider mb-0.5">Torneo</p>
                <p class="text-sm font-extrabold text-surface-900">{{ teamDetails.torneo?.nombre_torneo || 'Desconocido' }}</p>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <!-- Discipline -->
                <div class="bg-surface-50 p-3 rounded-xl border border-surface-100">
                  <p class="text-[11px] font-bold text-surface-400 uppercase tracking-wider mb-0.5">Disciplina</p>
                  <p class="text-sm font-extrabold text-surface-900">{{ teamDetails.torneo?.disciplina?.nombre_disciplina || 'N/A' }}</p>
                </div>

                <!-- Category -->
                <div class="bg-surface-50 p-3 rounded-xl border border-surface-100">
                  <p class="text-[11px] font-bold text-surface-400 uppercase tracking-wider mb-0.5">Categoría</p>
                  <p class="text-sm font-extrabold text-surface-900">{{ teamDetails.torneo?.categoria?.nombre_categoria || 'N/A' }}</p>
                </div>

                <!-- Age Range -->
                <div v-if="teamDetails.torneo?.categoria?.edad_minima != null" class="bg-surface-50 p-3 rounded-xl border border-surface-100">
                  <p class="text-[11px] font-bold text-surface-400 uppercase tracking-wider mb-0.5">Rango de Edad</p>
                  <p class="text-sm font-extrabold text-surface-900">{{ teamDetails.torneo?.categoria?.edad_minima }} – {{ teamDetails.torneo?.categoria?.edad_maxima }} años</p>
                </div>

                <!-- Gender -->
                <div v-if="teamDetails.torneo?.categoria?.genero_requerido" class="bg-surface-50 p-3 rounded-xl border border-surface-100">
                  <p class="text-[11px] font-bold text-surface-400 uppercase tracking-wider mb-0.5">Rama</p>
                  <p class="text-sm font-extrabold text-surface-900">
                    {{ teamDetails.torneo?.categoria?.genero_requerido === 'M' ? 'Varonil' : (teamDetails.torneo?.categoria?.genero_requerido === 'F' ? 'Femenil' : 'Mixto') }}
                  </p>
                </div>
              </div>

              <!-- Dates -->
              <div v-if="teamDetails.torneo?.fecha_inicio" class="grid grid-cols-2 gap-2">
                <div class="bg-surface-50 p-3 rounded-xl border border-surface-100">
                  <p class="text-[11px] font-bold text-surface-400 uppercase tracking-wider mb-0.5">Fecha Inicio</p>
                  <p class="text-sm font-extrabold text-surface-900">{{ formatDate(teamDetails.torneo.fecha_inicio) }}</p>
                </div>
                <div v-if="teamDetails.torneo?.fecha_fin" class="bg-surface-50 p-3 rounded-xl border border-surface-100">
                  <p class="text-[11px] font-bold text-surface-400 uppercase tracking-wider mb-0.5">Fecha Fin</p>
                  <p class="text-sm font-extrabold text-surface-900">{{ formatDate(teamDetails.torneo.fecha_fin) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Team Name -->
          <div v-if="teamDetails.nombre_equipo" class="border border-surface-150 rounded-2xl p-4 space-y-2">
            <span class="text-xs font-extrabold text-surface-500 uppercase tracking-wider">Nombre del Equipo</span>
            <p class="text-sm font-extrabold text-surface-900 bg-surface-50 p-3 rounded-xl border border-surface-100">{{ teamDetails.nombre_equipo }}</p>
          </div>
        </template>
      </div>

      <!-- Pie del Modal -->
      <div v-if="teamDetails && !isLoading && !error" class="px-6 py-5 border-t border-surface-100 bg-surface-50 flex items-center justify-end gap-3 shrink-0">
        <button
          @click="handleResponse('RECHAZAR')"
          :disabled="processing"
          class="px-5 py-2.5 text-sm font-bold text-rose-600 hover:text-rose-800 rounded-xl hover:bg-rose-50 border border-rose-200 transition-colors focus:outline-none disabled:opacity-50"
        >
          <span v-if="processing" class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Procesando...
          </span>
          <span v-else class="flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            Rechazar
          </span>
        </button>
        <button
          @click="handleResponse('ACEPTAR')"
          :disabled="processing"
          class="px-6 py-2.5 text-sm font-bold text-white rounded-xl shadow-md transition-all focus:outline-none bg-emerald-600 hover:bg-emerald-700 active:scale-95 shadow-emerald-500/20 disabled:opacity-50"
        >
          <span v-if="processing" class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Procesando...
          </span>
          <span v-else class="flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            Aceptar
          </span>
        </button>
      </div>
    </div>
  </div>
</template>
