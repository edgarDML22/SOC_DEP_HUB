<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';

const route = useRoute();
const router = useRouter();
const idHistorial = route.params.idHistorial;

const rating = ref(0);
const comments = ref('');
const loading = ref(true);
const submitting = ref(false);
const error = ref(null);
const success = ref(false);
const hoverRating = ref(0);

const fetchSurveyStatus = async () => {
    try {
        const response = await api.get(`ludoteca/encuesta/${idHistorial}`);
        if (!response.data.success) {
            error.value = response.data.message;
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo cargar la encuesta.';
    } finally {
        loading.value = false;
    }
};

const submitSurvey = async () => {
    if (rating.value === 0) {
        error.value = 'Por favor selecciona una calificación.';
        return;
    }

    submitting.value = true;
    error.value = null;

    try {
        await api.post(`ludoteca/encuesta/${idHistorial}`, {
            calificacion_servicio: rating.value,
            comentarios_padre: comments.value
        });
        success.value = true;
    } catch (err) {
        error.value = err.response?.data?.message || 'Error al enviar la encuesta.';
    } finally {
        submitting.value = false;
    }
};

onMounted(() => {
    if (idHistorial) {
        fetchSurveyStatus();
    } else {
        error.value = 'ID de encuesta no proporcionado.';
        loading.value = false;
    }
});
</script>

<template>
    <div class="min-h-screen w-full bg-surface-50 font-sans p-4 md:p-6 lg:p-8 flex flex-col items-center">
        <div class="w-full max-w-2xl flex flex-col gap-6 animate-fade-in">
            
            <!-- Encabezado con Sistema de Pestañas (Diseño del Sistema) -->
            <div class="w-full">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">Ludoteca</h2>
                        <p class="text-surface-500 font-medium text-sm md:text-base m-0 mt-1">Encuesta de Satisfacción</p>
                    </div>
                </div>

                <!-- Pestañas (Estilo del sistema) -->
                <div class="flex border-b border-surface-200 mb-6">
                    <button class="flex-1 py-3 text-center font-bold text-sm md:text-base border-b-4 border-primary-600 text-primary-600 bg-primary-50/50 rounded-t-xl transition-all">
                        Responder Encuesta
                    </button>
                    <!-- Espacio para más pestañas si fuera necesario -->
                    <div class="flex-1 border-b-4 border-transparent"></div>
                    <div class="flex-1 border-b-4 border-transparent"></div>
                </div>
            </div>

            <!-- Estado de Carga -->
            <div v-if="loading" class="flex flex-col items-center justify-center p-20 bg-white rounded-3xl border border-surface-200 shadow-sm">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mb-4"></div>
                <p class="text-surface-500 font-medium">Cargando encuesta...</p>
            </div>

            <!-- Error o Mensaje de "Ya contestada" -->
            <div v-else-if="error" class="bg-white rounded-3xl border border-surface-200 p-8 md:p-12 shadow-sm text-center">
                <div class="bg-red-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-surface-900 mb-2">{{ error }}</h3>
                <p class="text-surface-500 mb-8">Si crees que esto es un error, por favor contacta al administrador.</p>
                <button @click="router.push('/socio/home')" class="px-8 py-3 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl transition-all active:scale-95 shadow-lg shadow-primary-600/20">
                    Volver al Inicio
                </button>
            </div>

            <!-- Éxito -->
            <div v-else-if="success" class="bg-white rounded-3xl border border-surface-200 p-8 md:p-12 shadow-sm text-center animate-scale-in">
                <div class="bg-green-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-surface-900 mb-2">¡Muchas gracias!</h3>
                <p class="text-surface-500 mb-8">Tus comentarios nos ayudan a mejorar nuestro servicio día con día.</p>
                <button @click="router.push('/socio/home')" class="px-8 py-3 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl transition-all active:scale-95 shadow-lg shadow-primary-600/20">
                    Ir a mi Dashboard
                </button>
            </div>

            <!-- Formulario de Encuesta -->
            <div v-else class="bg-white rounded-3xl border border-surface-200 p-6 md:p-10 shadow-sm flex flex-col gap-8">
                
                <!-- Calificación con Estrellas -->
                <div class="text-center">
                    <label class="block text-lg font-bold text-surface-900 mb-4">¿Cómo calificarías el servicio hoy?</label>
                    <div class="flex justify-center gap-2">
                        <button 
                            v-for="star in 5" 
                            :key="star"
                            @click="rating = star"
                            @mouseenter="hoverRating = star"
                            @mouseleave="hoverRating = 0"
                            class="p-1 transition-all duration-200 focus:outline-none transform"
                            :class="[
                                (hoverRating || rating) >= star ? 'text-yellow-400 scale-110' : 'text-surface-200',
                                'hover:scale-125'
                            ]"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 fill-current" viewBox="0 0 24 24">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-4 text-sm font-medium" :class="rating > 0 ? 'text-primary-600' : 'text-surface-400'">
                        {{ rating > 0 ? `Seleccionaste ${rating} ${rating === 1 ? 'estrella' : 'estrellas'}` : 'Selecciona una puntuación' }}
                    </p>
                </div>

                <!-- Comentarios -->
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-surface-700">Comentarios adicionales (opcional)</label>
                    <textarea 
                        v-model="comments"
                        rows="4"
                        placeholder="Cuéntanos más sobre tu experiencia..."
                        class="w-full bg-surface-50 border border-surface-300 rounded-2xl px-5 py-4 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all resize-none text-surface-900"
                    ></textarea>
                    <div class="flex justify-end">
                        <span class="text-xs text-surface-400">{{ comments.length }}/500</span>
                    </div>
                </div>

                <!-- Botón Enviar -->
                <button 
                    @click="submitSurvey"
                    :disabled="submitting || rating === 0"
                    class="w-full py-4 bg-primary-600 hover:bg-primary-700 disabled:bg-surface-300 text-white font-bold rounded-2xl transition-all shadow-lg shadow-primary-600/20 active:scale-[0.98] flex items-center justify-center gap-2"
                >
                    <span v-if="submitting" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
                    {{ submitting ? 'Enviando...' : 'Enviar Encuesta' }}
                </button>
            </div>

        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

.animate-scale-in {
    animation: scaleIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}
</style>
