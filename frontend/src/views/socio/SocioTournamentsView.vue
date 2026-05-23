<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useSocioTorneoStore } from '@/stores/socioTorneoStore';
import { useAlerts } from '@/composables/useAlerts';
import ModalSeleccionParticipante from '@/components/socio/ModalSeleccionParticipante.vue';
import PartnerSelectionModal from '@/components/socio/PartnerSelectionModal.vue';
import TeamInvitationModal from '@/components/socio/TeamInvitationModal.vue';
import { IconArrowLeft, IconCalendar, IconTrophy, IconHistory, IconStar, IconBaby, IconGender } from '@/components/icons';

const router = useRouter();
const torneoStore = useSocioTorneoStore();
const { toastInfo } = useAlerts();

const activeView = ref('inscripcion'); // Default: 'inscripcion'
const selectedTorneo = ref(null);
const isModalOpen = ref(false);
const isPartnerModalOpen = ref(false);
const teamToReassign = ref(null);

const isTeamInvitationModalOpen = ref(false);
const invitationTeamId = ref(null);

const tabs = [
  { key: 'inscripcion', label: 'Inscripción' },
  { key: 'historial', label: 'Historial' },
];

// Cambiar de pestaña y cargar datos según corresponda
const changeTab = async (key) => {
  activeView.value = key;
  if (key === 'inscripcion') {
    await torneoStore.fetchDisponibles();
  } else {
    await torneoStore.fetchHistorial();
  }
};

onMounted(async () => {
  await torneoStore.fetchDisponibles();
});

// Abrir modal de inscripción
const openEnrollment = (torneo, equipoReasignar = null) => {
  selectedTorneo.value = torneo;
  teamToReassign.value = equipoReasignar;
  // Si no es INDIVIDUAL, asumimos que es modalidad en conjunto (PAREJAS, EQUIPOS, etc.)
  if (torneo.modalidad !== 'INDIVIDUAL') {
    isPartnerModalOpen.value = true;
  } else {
    isModalOpen.value = true;
  }
};

// Cerrar modal individual
const closeEnrollment = () => {
  selectedTorneo.value = null;
  isModalOpen.value = false;
};

// Cerrar modal de equipo
const closePartnerModal = () => {
  selectedTorneo.value = null;
  teamToReassign.value = null;
  isPartnerModalOpen.value = false;
};

// Confirmar inscripción en backend
const confirmEnrollment = async (payload) => {
  if (!selectedTorneo.value) return;
  const id_torneo = selectedTorneo.value.id_torneo;

  const res = await torneoStore.inscribir(id_torneo, payload);
  if (res.success) {
    toastInfo('¡Éxito!', res.message, 'success');
    closeEnrollment();
  } else {
    toastInfo('Error al inscribir', res.error, 'error');
  }
};

const handleInviteSent = () => {
  toastInfo('¡Éxito!', 'Invitación enviada correctamente', 'success');
  // Refresh list to update UI
  torneoStore.fetchDisponibles();
  closePartnerModal();
};

const openInvitationModal = (teamId) => {
  console.debug('[UI] openInvitationModal called, teamId=', teamId);
  invitationTeamId.value = teamId;
  isTeamInvitationModalOpen.value = true;
};

const closeInvitationModal = () => {
  isTeamInvitationModalOpen.value = false;
  invitationTeamId.value = null;
};

const handleInvitationResponded = (decision) => {
  // Re-fetch the history so it reflects the new status
  torneoStore.fetchHistorial();
};

// Formatear fechas legibles
const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
};

// Helper para colores de barra de progreso de cupos
const getCupoPercentage = (torneo) => {
  if (!torneo.cupo_maximo) return 0;
  return Math.min(100, Math.round((torneo.inscritos_actual / torneo.cupo_maximo) * 100));
};

const getProgressBarColor = (pct) => {
  if (pct >= 100) return 'bg-rose-500';
  if (pct >= 75) return 'bg-amber-500';
  return 'bg-emerald-500';
};
</script>

<template>
  <div class="w-full font-sans bg-surface-50 min-h-screen">
    <!-- Contenedor del Layout -->
    <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 pt-4 md:pt-6 lg:pt-8 bg-surface-50">

      <!-- Botón Volver -->
      <button @click="router.push('/socio/home')"
        class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-bold text-sm transition-colors mb-4 focus:outline-none w-fit group">
        <IconArrowLeft class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" />
        Volver
      </button>

      <!-- Título -->
      <h2 class="text-2xl md:text-3xl font-extrabold text-surface-900 m-0 mb-5 tracking-tight">Hub de Torneos</h2>

      <!-- Segmented Control (Pills) -->
      <div
        class="flex p-1.5 bg-surface-100 rounded-2xl w-full max-w-2xl mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-8">
        <button v-for="tab in tabs" :key="tab.key" @click="changeTab(tab.key)"
          class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
          :class="activeView === tab.key
            ? 'bg-primary-600 text-white font-extrabold rounded-xl shadow-md transform scale-[1.01]'
            : 'text-surface-500 font-bold hover:bg-white/60 hover:text-surface-700 rounded-xl'">
          <IconStar v-if="tab.key === 'inscripcion'" class="w-5 h-5 shrink-0" />
          <IconHistory v-else class="w-5 h-5 shrink-0" />
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Área de Contenido -->
    <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8">
      <div class="max-w-7xl mx-auto">

        <!-- Loading Spinner -->
        <div v-if="torneoStore.loading" class="flex justify-center items-center py-20">
          <svg class="animate-spin h-10 w-10 text-primary-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
          </svg>
        </div>

        <div v-else>
          <!-- TAB: INSCRIPCION (TORNEOS DISPONIBLES) -->
          <div v-if="activeView === 'inscripcion'">

            <div v-if="torneoStore.disponibles.length === 0"
              class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm text-center px-4">
              <div
                class="w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center text-primary-500 mb-4 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <p class="text-surface-900 font-extrabold text-lg">No hay torneos disponibles</p>
              <p class="text-surface-500 font-semibold text-sm max-w-sm mt-1">
                Actualmente no hay torneos abiertos en fase de inscripción. Por favor vuelve a verificar más tarde.
              </p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- Available Tournament Card -->
              <div v-for="torneo in torneoStore.disponibles" :key="torneo.id_torneo"
                class="bg-white rounded-3xl border border-surface-150 hover:border-surface-300 hover:shadow-md transition-all p-6 flex flex-col justify-between relative overflow-hidden">
                <!-- Top Accent Line -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-primary-600"></div>

                <div class="space-y-4">
                  <!-- Header & Access Badge -->
                  <div class="flex justify-between items-start pt-1">
                    <span class="text-xs font-extrabold text-primary-600 tracking-wide uppercase">
                      {{ torneo.disciplina?.nombre_disciplina || 'Deporte' }}
                    </span>
                    <span
                      class="bg-primary-50 text-primary-700 text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider border border-primary-100">
                      {{ torneo.tipo_acceso }}
                    </span>
                  </div>

                  <!-- Title -->
                  <div>
                    <h3 class="text-lg font-extrabold text-surface-900 line-clamp-1 leading-snug">
                      {{ torneo.nombre_torneo }}
                    </h3>
                    <p class="text-xs font-semibold text-surface-400 mt-1">
                      Categoría: <span class="text-surface-600 font-extrabold">{{ torneo.categoria?.nombre_categoria
                        }}</span>
                    </p>
                  </div>

                  <!-- Details Badges (Age/Gender) -->
                  <div class="flex flex-wrap gap-2 text-[11px] font-bold">
                    <span
                      class="bg-surface-50 border border-surface-150 text-surface-600 px-2.5 py-1 rounded-lg flex items-center gap-1">
                      <IconBaby class="w-3.5 h-3.5 text-surface-400 shrink-0" />
                      {{ torneo.categoria?.edad_minima }} - {{ torneo.categoria?.edad_maxima }} años
                    </span>
                    <span
                      class="bg-surface-50 border border-surface-150 text-surface-600 px-2.5 py-1 rounded-lg flex items-center gap-1">
                      <IconGender class="w-3.5 h-3.5 text-surface-400 shrink-0" />
                      Rama: {{ torneo.categoria?.genero_requerido === 'M' ? 'Varonil' :
                        (torneo.categoria?.genero_requerido === 'F' ? 'Femenil' : 'Mixto') }}
                    </span>
                  </div>

                  <!-- Date Block -->
                  <div class="bg-surface-50/50 rounded-2xl p-3 border border-surface-100 flex items-center gap-3">
                    <div class="p-2 bg-white text-surface-500 rounded-xl shrink-0 shadow-sm border border-surface-100">
                      <IconCalendar class="w-4 h-4 text-surface-400" />
                    </div>
                    <div class="text-[11px] font-semibold text-surface-500 space-y-0.5">
                      <p>Inicio: <span class="font-extrabold text-surface-700">{{ formatDate(torneo.fecha_inicio)
                          }}</span></p>
                      <p>Cierre: <span class="font-extrabold text-surface-700">{{ formatDate(torneo.fecha_fin) }}</span>
                      </p>
                    </div>
                  </div>

                  <!-- Progress Bar / Cupos -->
                  <div class="space-y-1.5 pt-1">
                    <div class="flex justify-between text-xs font-bold text-surface-500">
                      <span>Inscritos</span>
                      <span class="text-surface-850 font-extrabold">
                        {{ torneo.inscritos_actual }} / {{ torneo.cupo_maximo }}
                      </span>
                    </div>
                    <div class="w-full bg-surface-100 rounded-full h-2.5 overflow-hidden shadow-inner">
                      <div :class="getProgressBarColor(getCupoPercentage(torneo))"
                        class="h-full rounded-full transition-all duration-500"
                        :style="{ width: getCupoPercentage(torneo) + '%' }"></div>
                    </div>
                  </div>
                </div>

                <!-- CTA Button -->
                <div class="mt-6">
                  <!-- Case 1: Already enrolled -->
                  <button v-if="torneo.ya_inscrito" disabled
                    class="w-full py-3 px-4 rounded-2xl text-sm font-extrabold bg-emerald-50 text-emerald-800 border-2 border-emerald-200 cursor-not-allowed flex items-center justify-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 20 20"
                      fill="currentColor">
                      <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                    </svg>
                    Ya inscrito
                  </button>

                  <!-- Case 2: Full Capacity -->
                  <button v-else-if="torneo.inscritos_actual >= torneo.cupo_maximo" disabled
                    class="w-full py-3 px-4 rounded-2xl text-sm font-extrabold bg-surface-100 text-surface-400 border border-surface-200 cursor-not-allowed flex items-center justify-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24"
                      stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Cupo Lleno
                  </button>

                  <!-- Case 3: Available to enroll -->
                  <button v-else @click="openEnrollment(torneo)"
                    class="w-full py-3 px-4 rounded-2xl text-sm font-extrabold text-white bg-primary-600 hover:bg-primary-700 active:scale-[0.98] transition-all shadow-md shadow-primary-500/10 hover:shadow-lg focus:outline-none">
                    Inscribirme
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB: HISTORIAL -->
          <div v-else-if="activeView === 'historial'">

            <div v-if="torneoStore.historial.length === 0"
              class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm text-center px-4">
              <div
                class="w-16 h-16 bg-surface-50 rounded-full flex items-center justify-center text-surface-400 mb-4 border border-surface-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z" />
                </svg>
              </div>
              <p class="text-surface-900 font-extrabold text-lg">No tienes historial registrado</p>
              <p class="text-surface-500 font-semibold text-sm max-w-sm mt-1">
                Aún no has participado en torneos organizados por el club. ¡Inscríbete a tu primer torneo hoy!
              </p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- History Card -->
              <div v-for="item in torneoStore.historial" :key="item.id_torneo"
                class="bg-white rounded-3xl border border-surface-150 shadow-sm hover:shadow-md transition-all p-6 flex flex-col justify-between relative overflow-hidden">
                <!-- Left Accent Border -->
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-amber-500"></div>

                <div class="space-y-4">
                  <!-- Header Block -->
                  <div class="flex justify-between items-start pt-1 pl-1">
                    <div>
                      <span class="text-xs font-extrabold text-surface-400 uppercase tracking-wider">
                        {{ item.disciplina || 'Deporte' }}
                      </span>
                      <h3 class="text-lg font-extrabold text-surface-900 mt-1 line-clamp-1 leading-snug">
                        {{ item.nombre_torneo }}
                      </h3>
                    </div>
                    <!-- Status Badge -->
                    <span
                      class="text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase tracking-wider border shadow-sm"
                      :class="item.estatus_torneo === 'EN_INSCRIPCION'
                        ? 'bg-blue-50 text-blue-700 border-blue-100'
                        : (item.estatus_torneo === 'EN_CURSO'
                          ? 'bg-emerald-50 text-emerald-700 border-emerald-100'
                          : 'bg-surface-50 text-surface-500 border-surface-200')">
                      {{ item.estatus_torneo === 'EN_CURSO' ? 'En Juego' : (item.estatus_torneo === 'EN_INSCRIPCION' ?
                      'Abierto' : 'Finalizado') }}
                    </span>
                  </div>

                  <!-- Date Block -->
                  <div class="pl-1 text-xs font-semibold text-surface-400 flex items-center gap-1.5">
                    <IconCalendar class="w-4 h-4 shrink-0 text-surface-300" />
                    Fecha inicio: <span class="font-extrabold text-surface-600">{{ formatDate(item.fecha_inicio)
                      }}</span>
                  </div>

                  <!-- Achievement/Fase Maxima Badge -->
                  <div class="pt-2 pl-1 border-t border-surface-100 flex items-center gap-2">
                    <span class="text-xs font-bold text-surface-400">Rendimiento:</span>
                    <span v-if="item.fase_maxima_alcanzada"
                      class="bg-amber-50 text-amber-800 border border-amber-200 text-xs font-extrabold px-3 py-1 rounded-xl flex items-center gap-2 shadow-sm uppercase tracking-wide">
                      <IconTrophy class="w-4 h-4 text-amber-600 shrink-0" />
                      {{ item.fase_maxima_alcanzada }}
                    </span>
                    <span v-else
                      class="bg-surface-50 text-surface-500 border border-surface-150 text-xs font-semibold px-3 py-1 rounded-xl">
                      Fase grupal / Pendiente
                    </span>
                  </div>

                  <!-- Team Status Section -->
                  <div v-if="item.equipo" class="mt-3 pt-3 border-t border-surface-100">
                    <div v-if="item.equipo.estado === 'PENDIENTE'"
                      class="bg-orange-50 border border-orange-100 rounded-md p-3">

                      <!-- Si el usuario actual fue quien creó el equipo (Capitán) -->
                      <div v-if="item.equipo.soy_capitan" class="flex items-start">
                        <svg class="h-5 w-5 text-orange-400 mr-2 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                        </svg>
                        <div>
                          <p class="text-sm text-orange-800 font-medium">Esperando confirmación</p>
                          <p class="text-xs text-orange-700 mt-0.5">de {{ item.equipo.companero?.nombre_completo ||
                            'Compañero' }}</p>
                        </div>
                      </div>

                      <!-- Si el usuario actual es el Invitado (Socio B) -->
                      <div v-else class="text-center">
                        <p class="text-sm text-indigo-800 font-bold mb-2">¡Te han invitado a jugar este torneo!</p>
                        <button @click="openInvitationModal(item.equipo.id_equipo)"
                          class="w-full bg-indigo-600 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                          Ver y Responder Invitación
                        </button>
                      </div>
                    </div>

                    <div v-if="item.equipo.estado === 'RECHAZADA' && item.equipo.soy_capitan" class="mt-2 text-center">
                      <p class="text-xs text-red-600 mb-2 font-medium">La invitación fue rechazada.</p>
                      <button @click="openEnrollment(item, item.equipo)"
                        class="w-full bg-white border border-primary-600 text-primary-600 px-3 py-1.5 rounded-md text-sm font-medium hover:bg-primary-50 transition-colors">
                        Invitar a otro compañero
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Seleccion de Participante -->
    <ModalSeleccionParticipante :isOpen="isModalOpen" :torneo="selectedTorneo" @close="closeEnrollment"
      @confirm="confirmEnrollment" />

    <!-- The Modal for Partner Selection -->
    <PartnerSelectionModal v-if="isPartnerModalOpen" :isOpen="isPartnerModalOpen" :tournament="selectedTorneo"
      :category="selectedTorneo.categoria" :teamToReassign="teamToReassign" @close="closePartnerModal"
      @invite-sent="handleInviteSent" />

    <!-- Team Invitation Modal -->
    <TeamInvitationModal 
      v-if="isTeamInvitationModalOpen" 
      :isOpen="isTeamInvitationModalOpen" 
      :teamId="invitationTeamId" 
      @close="closeInvitationModal" 
      @responded="handleInvitationResponded" />
  </div>
</template>

<style scoped>
/* Scrollbar suave para segmented control en movil */
.scrollbar-thin::-webkit-scrollbar {
  height: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.25);
  border-radius: 99px;
}
</style>