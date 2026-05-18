<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useFriendStore } from '@/stores/community/friendStore';
import { useSocioTorneoStore } from '@/stores/socioTorneoStore';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    tournament: {
        type: Object,
        required: true
    },
    category: {
        type: Object,
        required: true
    },
    teamToReassign: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'invite-sent']);

const friendStore = useFriendStore();
const tournamentStore = useSocioTorneoStore();

const selectedPartner = ref(null);
const rankingCaptain = ref(0);
const rankingPartner = ref(0);
const teamName = ref('');
const processing = ref(false);

onMounted(() => {
    friendStore.fetchFriends();
});

const closeModal = () => {
    selectedPartner.value = null;
    rankingCaptain.value = 0;
    rankingPartner.value = 0;
    teamName.value = '';
    emit('close');
};

const calculateAge = (birthDateString) => {
    if (!birthDateString) return null;
    const today = new Date();
    const birthDate = new Date(birthDateString);
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
};

const checkEligibility = (friend) => {
    let reason = null;
    let isEligible = true;

    if (!props.category) {
        return { isEligible, reason }; // If no category requirements, everyone is eligible
    }

    // Check gender: if category requires M or F, the friend must match exactly.
    // If it's anything else (like MIXTO, AMBOS, etc.), any gender is accepted.
    const reqGender = props.category.genero_requerido;
    if (reqGender === 'M' || reqGender === 'F') {
        if (friend.genero !== reqGender) {
            isEligible = false;
            const genderName = reqGender === 'M' ? 'Varonil' : 'Femenil';
            reason = `No cumple género (Requiere ${genderName})`;
            return { isEligible, reason };
        }
    }

    // Check age
    const age = calculateAge(friend.fecha_nacimiento);
    if (age === null) {
        isEligible = false;
        reason = 'Unknown age';
        return { isEligible, reason };
    }

    const minAge = props.category.edad_minima;
    const maxAge = props.category.edad_maxima;

    if (minAge !== null && age < minAge) {
        isEligible = false;
        reason = `Under age (Min ${minAge})`;
        return { isEligible, reason };
    }

    if (maxAge !== null && age > maxAge) {
        isEligible = false;
        reason = `Over age (Max ${maxAge})`;
        return { isEligible, reason };
    }

    return { isEligible, reason };
};

const eligibleFriends = computed(() => {
    const acceptedFriends = friendStore.friends.filter(f => f.estado === 'ACEPTADA');
    return acceptedFriends.map(f => {
        const eligibility = checkEligibility(f);
        return {
            ...f,
            isEligible: eligibility.isEligible,
            ineligibleReason: eligibility.reason
        };
    });
});

const isFormValid = computed(() => {
    return selectedPartner.value &&
        rankingCaptain.value >= 0 && rankingCaptain.value <= 500 &&
        rankingPartner.value >= 0 && rankingPartner.value <= 500 &&
        !processing.value;
});

const sendInvitation = async () => {
    if (!isFormValid.value) return;

    processing.value = true;

    try {
        if (props.teamToReassign) {
            // Re-assign mode
            const payload = {
                id_equipo: props.teamToReassign.id_equipo_torneo || props.teamToReassign.id_equipo,
                id_nuevo_companero: selectedPartner.value.id_amigo,
                ranking_nuevo_companero: rankingPartner.value
            };
            await tournamentStore.reasignarCompanero(payload);
        } else {
            // Create mode
            const payload = {
                id_socio_companero: selectedPartner.value.id_amigo,
                ranking_capitan: rankingCaptain.value,
                ranking_companero: rankingPartner.value,
                nombre_equipo: teamName.value || null
            };
            await tournamentStore.crearEquipo(props.tournament.id_torneo, payload);
        }

        emit('invite-sent', selectedPartner.value);
        closeModal();
    } catch (error) {
        console.error("Failed to send invitation", error);
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-surface-900/30 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="bg-gray-50 px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-800">Select Tournament Partner</h3>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1">
                <p class="text-sm text-gray-600 mb-4">
                    Category Requirements: {{ category?.genero_requerido || 'None' }}
                    <span v-if="category?.edad_minima || category?.edad_maxima">
                        ({{ category?.edad_minima || 0 }} - {{ category?.edad_maxima || 'No limit' }} years)
                    </span>
                </p>

                <!-- Loading State -->
                <div v-if="friendStore.loading" class="flex justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                </div>

                <!-- Empty State -->
                <div v-else-if="eligibleFriends.length === 0" class="text-center py-8 text-gray-500">
                    You don't have any accepted friends to invite.
                </div>

                <!-- Friends List -->
                <div v-else class="space-y-3">
                    <div v-for="friend in eligibleFriends" :key="friend.id_amistad"
                        class="border rounded-lg p-3 flex items-center justify-between transition-colors cursor-pointer"
                        :class="{
                            'bg-gray-50 opacity-60 cursor-not-allowed': !friend.isEligible,
                            'border-indigo-500 bg-indigo-50': selectedPartner?.id_amistad === friend.id_amistad,
                            'hover:border-indigo-300': friend.isEligible && selectedPartner?.id_amistad !== friend.id_amistad
                        }" @click="friend.isEligible ? selectedPartner = friend : null">

                        <div class="flex items-center space-x-3">
                            <div
                                class="h-10 w-10 rounded-full bg-gradient-to-r from-indigo-400 to-purple-400 flex items-center justify-center text-white font-bold">
                                {{ friend.nombre_amigo.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">{{ friend.nombre_amigo }}</p>
                            </div>
                        </div>

                        <div>
                            <span v-if="friend.isEligible"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Eligible
                            </span>
                            <span v-else
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Ineligible: {{ friend.ineligibleReason }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Additional Configuration (Shows when partner selected) -->
                <div v-if="selectedPartner" class="mt-6 border-t pt-4 space-y-4">
                    <!-- Team Name -->
                    <div v-if="!teamToReassign">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Equipo (Opcional)</label>
                        <input type="text" v-model="teamName" placeholder="Ej. Los Invencibles"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- Rankings Configuration -->
                    <div>
                        <h4 class="font-medium text-gray-800 mb-3">Ranking Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Your Ranking</label>
                                <input type="number" v-model="rankingCaptain" min="0" max="500"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Partner Ranking</label>
                                <input type="number" v-model="rankingPartner" min="0" max="500"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <p v-if="rankingCaptain < 0 || rankingCaptain > 500 || rankingPartner < 0 || rankingPartner > 500"
                            class="text-sm text-red-600 mt-2">
                            Rankings must be between 0 and 500.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 border-t flex justify-end space-x-3">
                <button @click="closeModal"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button @click="sendInvitation" :disabled="!isFormValid"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors flex items-center justify-center min-w-[140px] disabled:opacity-50 disabled:cursor-not-allowed">
                    <span v-if="processing" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Sending...
                    </span>
                    <span v-else>Send Invitation</span>
                </button>
            </div>

            <div v-if="tournamentStore.error" class="px-6 pb-4">
                <div class="bg-red-50 text-red-700 p-3 rounded-md text-sm">
                    {{ tournamentStore.error }}
                </div>
            </div>
        </div>
    </div>
</template>