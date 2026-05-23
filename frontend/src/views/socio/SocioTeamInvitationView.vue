<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSocioTorneoStore } from '@/stores/socioTorneoStore';
import api from '@/services/api';
import Swal from 'sweetalert2';
import { useAlerts } from '@/composables/useAlerts';

const route = useRoute();
const router = useRouter();
const tournamentStore = useSocioTorneoStore();
const { toastInfo, toastError } = useAlerts();

const teamId = route.params.id_equipo;
const loading = ref(true);
const error = ref(null);
const teamDetails = ref(null);
const processing = ref(false);

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
};

const fetchTeamDetails = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await api.get(`/equipos/${teamId}`);
        teamDetails.value = response.data?.data || response.data;
    } catch (err) {
        error.value = err.response?.data?.message || "Error al cargar los detalles de la invitación";
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchTeamDetails();
});

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
        await tournamentStore.responderInvitacion(id_torneo, teamId, { decision });
        
        toastInfo('¡Listo!', `Invitación ${isAccept ? 'aceptada' : 'rechazada'} exitosamente.`, 'success');
        
        router.push('/socio/tournaments');
    } catch (err) {
        console.error("Error al responder a la invitación", err);
        toastError(tournamentStore.error || "Ocurrió un error al procesar tu respuesta");
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary-600 to-primary-800 px-6 py-8 text-center">
                <h2 class="text-3xl font-extrabold text-white tracking-tight">Invitación a Torneo</h2>
                <p class="mt-2 text-primary-100 font-medium">{{ teamDetails?.capitan?.nombre_completo || '' }} te ha invitado a ser su pareja</p>
            </div>

            <!-- Content -->
            <div class="p-8">
                <!-- Loading -->
                <div v-if="loading" class="flex justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
                </div>

                <!-- Error -->
                <div v-else-if="error" class="bg-rose-50 text-rose-700 p-6 rounded-xl border border-rose-100 text-center">
                    <svg class="mx-auto h-12 w-12 text-rose-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-lg font-medium">{{ error }}</p>
                    <button @click="router.push('/socio/home')" class="mt-4 text-primary-600 hover:text-primary-800 font-bold transition-colors">
                        Volver al inicio
                    </button>
                </div>

                <!-- Details -->
                <div v-else-if="teamDetails" class="space-y-8">
                    <!-- Captain Info -->
                    <div class="bg-primary-50 rounded-xl p-6 flex items-center space-x-6 border border-primary-100">
                        <div class="h-20 w-20 rounded-full bg-gradient-to-r from-primary-500 to-primary-700 flex items-center justify-center text-white text-3xl font-bold shadow-md">
                            {{ teamDetails.capitan?.nombre_completo?.charAt(0).toUpperCase() || '?' }}
                        </div>
                        <div>
                            <p class="text-sm text-primary-600 font-semibold uppercase tracking-wider mb-1">Capitán del Equipo</p>
                            <h3 class="text-2xl font-bold text-gray-900">{{ teamDetails.capitan?.nombre_completo || 'Capitán desconocido' }}</h3>
                            <p class="text-gray-600 mt-1">Te invita a ser su compañero/a de equipo</p>
                        </div>
                    </div>

                    <!-- Tournament Info -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Detalles del Torneo</h4>
                        
                        <div class="bg-gray-50 border rounded-xl p-5 hover:border-primary-300 transition-colors">
                            <p class="text-sm text-gray-500 font-medium uppercase mb-1">Torneo</p>
                            <p class="text-lg font-semibold text-gray-800">{{ teamDetails.torneo?.nombre_torneo || 'Desconocido' }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="border rounded-xl p-5 hover:border-primary-300 transition-colors">
                                <p class="text-sm text-gray-500 font-medium uppercase mb-1">Disciplina</p>
                                <p class="text-lg font-semibold text-gray-800">{{ teamDetails.torneo?.disciplina?.nombre_disciplina || 'N/A' }}</p>
                            </div>
                            <div class="border rounded-xl p-5 hover:border-primary-300 transition-colors">
                                <p class="text-sm text-gray-500 font-medium uppercase mb-1">Categoría</p>
                                <p class="text-lg font-semibold text-gray-800">{{ teamDetails.torneo?.categoria?.nombre_categoria || 'N/A' }}</p>
                            </div>
                            <div v-if="teamDetails.torneo?.categoria?.edad_minima != null" class="border rounded-xl p-5 hover:border-primary-300 transition-colors">
                                <p class="text-sm text-gray-500 font-medium uppercase mb-1">Rango de Edad</p>
                                <p class="text-lg font-semibold text-gray-800">{{ teamDetails.torneo?.categoria?.edad_minima }} – {{ teamDetails.torneo?.categoria?.edad_maxima }} años</p>
                            </div>
                            <div v-if="teamDetails.torneo?.categoria?.genero_requerido" class="border rounded-xl p-5 hover:border-primary-300 transition-colors">
                                <p class="text-sm text-gray-500 font-medium uppercase mb-1">Rama</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    {{ teamDetails.torneo?.categoria?.genero_requerido === 'M' ? 'Varonil' : (teamDetails.torneo?.categoria?.genero_requerido === 'F' ? 'Femenil' : 'Mixto') }}
                                </p>
                            </div>
                            <div v-if="teamDetails.torneo?.fecha_inicio" class="border rounded-xl p-5 hover:border-primary-300 transition-colors">
                                <p class="text-sm text-gray-500 font-medium uppercase mb-1">Fecha Inicio</p>
                                <p class="text-lg font-semibold text-gray-800">{{ formatDate(teamDetails.torneo.fecha_inicio) }}</p>
                            </div>
                            <div v-if="teamDetails.torneo?.fecha_fin" class="border rounded-xl p-5 hover:border-primary-300 transition-colors">
                                <p class="text-sm text-gray-500 font-medium uppercase mb-1">Fecha Fin</p>
                                <p class="text-lg font-semibold text-gray-800">{{ formatDate(teamDetails.torneo.fecha_fin) }}</p>
                            </div>
                        </div>

                        <div v-if="teamDetails.nombre_equipo" class="bg-gray-50 border rounded-xl p-5 hover:border-primary-300 transition-colors">
                            <p class="text-sm text-gray-500 font-medium uppercase mb-1">Nombre del Equipo</p>
                            <p class="text-lg font-semibold text-gray-800">{{ teamDetails.nombre_equipo }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 flex flex-col sm:flex-row gap-4 justify-center">
                        <button @click="handleResponse('ACEPTAR')" :disabled="processing"
                            class="flex-1 max-w-xs bg-emerald-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-emerald-700 hover:shadow-lg transition-all disabled:opacity-50 flex justify-center items-center">
                            <span v-if="processing">Procesando...</span>
                            <span v-else class="flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Aceptar
                            </span>
                        </button>
                        <button @click="handleResponse('RECHAZAR')" :disabled="processing"
                            class="flex-1 max-w-xs bg-white text-rose-600 border-2 border-rose-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-rose-50 hover:shadow-lg transition-all disabled:opacity-50 flex justify-center items-center">
                            <span v-if="processing">Procesando...</span>
                            <span v-else class="flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                Rechazar
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
