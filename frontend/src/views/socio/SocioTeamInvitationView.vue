<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSocioTorneoStore } from '@/stores/socioTorneoStore';
import api from '@/services/api';

const route = useRoute();
const router = useRouter();
const tournamentStore = useSocioTorneoStore();

const teamId = route.params.id_equipo;
const loading = ref(true);
const error = ref(null);
const teamDetails = ref(null);
const processing = ref(false);

const fetchTeamDetails = async () => {
    loading.value = true;
    error.value = null;
    try {
        // Assuming there is an endpoint to get team details by ID
        // If not, this serves as the contract for the backend
        const response = await api.get(`/equipos/${teamId}`);
        teamDetails.value = response.data?.data || response.data;
    } catch (err) {
        error.value = err.response?.data?.message || "Error loading invitation details";
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchTeamDetails();
});

const handleResponse = async (decision) => {
    if (!confirm(`Are you sure you want to ${decision === 'ACEPTADA' ? 'Accept' : 'Reject'} this invitation?`)) {
        return;
    }

    processing.value = true;
    try {
        const id_torneo = teamDetails.value?.torneo?.id_torneo || 0; // Fallback to 0 if not present, though backend might need it
        await tournamentStore.responderInvitacion(id_torneo, teamId, { decision });
        // Redirect to tournaments page or show success message
        alert(`Invitation ${decision === 'ACEPTADA' ? 'Accepted' : 'Rejected'} successfully.`);
        router.push('/socio/tournaments');
    } catch (err) {
        console.error("Failed to respond to invitation", err);
        alert(tournamentStore.error || "An error occurred");
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-8 text-center">
                <h2 class="text-3xl font-extrabold text-white tracking-tight">Tournament Invitation</h2>
                <p class="mt-2 text-indigo-100 font-medium">You have been invited to join a team!</p>
            </div>

            <!-- Content -->
            <div class="p-8">
                <!-- Loading -->
                <div v-if="loading" class="flex justify-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                </div>

                <!-- Error -->
                <div v-else-if="error" class="bg-red-50 text-red-700 p-6 rounded-xl border border-red-100 text-center">
                    <svg class="mx-auto h-12 w-12 text-red-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-lg font-medium">{{ error }}</p>
                    <button @click="router.push('/socio/tournaments')" class="mt-4 text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                        Return to Tournaments
                    </button>
                </div>

                <!-- Details -->
                <div v-else-if="teamDetails" class="space-y-8">
                    <!-- Captain Info -->
                    <div class="bg-indigo-50 rounded-xl p-6 flex items-center space-x-6 border border-indigo-100">
                        <div class="h-20 w-20 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-white text-3xl font-bold shadow-md">
                            {{ teamDetails.capitan?.nombre_completo?.charAt(0).toUpperCase() || '?' }}
                        </div>
                        <div>
                            <p class="text-sm text-indigo-600 font-semibold uppercase tracking-wider mb-1">Team Captain</p>
                            <h3 class="text-2xl font-bold text-gray-900">{{ teamDetails.capitan?.nombre_completo || 'Unknown Captain' }}</h3>
                            <p class="text-gray-600 mt-1">Invites you to be their partner</p>
                        </div>
                    </div>

                    <!-- Tournament Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="border rounded-xl p-5 hover:border-indigo-300 transition-colors">
                            <p class="text-sm text-gray-500 font-medium uppercase mb-1">Tournament</p>
                            <p class="text-lg font-semibold text-gray-800">{{ teamDetails.torneo?.nombre || 'Unknown Tournament' }}</p>
                        </div>
                        <div class="border rounded-xl p-5 hover:border-indigo-300 transition-colors">
                            <p class="text-sm text-gray-500 font-medium uppercase mb-1">Category</p>
                            <p class="text-lg font-semibold text-gray-800">{{ teamDetails.categoria?.nombre || 'Unknown Category' }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-6 flex flex-col sm:flex-row gap-4 justify-center">
                        <button @click="handleResponse('ACEPTADA')" :disabled="processing"
                            class="flex-1 max-w-xs bg-green-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-green-700 hover:shadow-lg transition-all disabled:opacity-50 flex justify-center items-center">
                            <span v-if="processing">Processing...</span>
                            <span v-else class="flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Accept
                            </span>
                        </button>
                        <button @click="handleResponse('RECHAZADA')" :disabled="processing"
                            class="flex-1 max-w-xs bg-white text-red-600 border-2 border-red-600 px-8 py-4 rounded-xl font-bold text-lg hover:bg-red-50 hover:shadow-lg transition-all disabled:opacity-50 flex justify-center items-center">
                            <span v-if="processing">Processing...</span>
                            <span v-else class="flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                Reject
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
