<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

// Importando Iconos de tu proyecto
import {
    IconArrowLeft,
    IconClock,
    IconUser,
    IconQr,
    IconStart
} from '@/components/icons';

const route = useRoute();
const router = useRouter();
const sessionId = route.params.id;

const sessionData = ref(null);
const isLoading = ref(true);
const errorMsg = ref('');

onMounted(() => {
    // Recuperamos la información pasada por la vista anterior
    if (history.state && history.state.sessionData) {
        try {
            sessionData.value = JSON.parse(history.state.sessionData);
        } catch (e) {
            console.error("Error parseando data:", e);
            errorMsg.value = 'Error al procesar la información de la sesión.';
        }
    } else {
        errorMsg.value = 'No se encontró la información de la sesión. Regresa e inténtalo de nuevo.';
    }

    isLoading.value = false;
});

const goBack = () => {
    router.back();
};

const handleRegistrarAsistencia = () => {
    // Redirigimos al escáner incluyendo tal vez el query parameter
    router.push({ path: '/instructor/scanner', query: { sesion: sessionId } });
};

const handleEmpezarSesion = () => {
    if (sessionData.value) {
        sessionData.value.status = 'En curso';
        sessionData.value.statusType = 'info'; // Cambiar color del badge
    }
    alert('¡Sesión en Curso iniciada!');
    // Aquí luego añadirás el llamado al API backend.
};
</script>

<template>
    <main class="w-full bg-surface-50 min-h-screen font-sans p-4 md:p-6 lg:p-8 pb-24 lg:pb-8 flex justify-center">
        
        <div class="w-full max-w-3xl flex flex-col gap-6 animate-fade-in">

            <!-- Header Volver -->
            <div class="mb-2">
                <button @click="goBack" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-6 focus:outline-none w-fit group">
                    <IconArrowLeft class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" /> Volver
                </button>
                <div class="border-b border-surface-200 pb-5">
                    <h1 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight" >Detalles de la Actividad</h1>
                </div>
            </div>

            <!-- Estado de Error -->
            <section v-if="errorMsg" class="flex flex-col items-center justify-center p-12 bg-red-50 rounded-3xl border border-red-200">
                <p class="text-red-600 font-bold mb-4">{{ errorMsg }}</p>
                <button @click="goBack" class="bg-white border border-surface-300 px-6 py-2 rounded-xl text-surface-700 font-semibold hover:bg-surface-50 transition-colors active:scale-95 shadow-sm">
                    Volver a la Agenda
                </button>
            </section>

            <!-- Contenido Detallado -->
            <section v-if="sessionData && !isLoading" class="flex flex-col gap-6">

                <!-- Tarjeta Principal Visual -->
                <article class="bg-white rounded-3xl border border-surface-200 p-6 md:p-8 shadow-sm relative overflow-hidden">
                    <!-- Barra superior decorativa -->
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-linear-to-r from-primary-400 to-primary-600"></div>

                    <div class="flex flex-col gap-5">
                        <div class="flex justify-between items-center mb-2">
                            <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider" 
                                  :class="{
                                    'bg-primary-100 text-primary-800': sessionData.statusType === 'info',
                                    'bg-green-100 text-green-800': sessionData.statusType === 'success',
                                    'bg-surface-100 text-surface-800': !sessionData.statusType || (sessionData.statusType !== 'info' && sessionData.statusType !== 'success')
                                  }">
                                {{ sessionData.status }}
                            </span>
                            <span class="text-xs font-bold text-surface-400 uppercase tracking-widest">{{ sessionData.diaSemana }}</span>
                        </div>

                        <div>
                            <h2 class="text-3xl font-extrabold text-surface-900 m-0 leading-tight mb-3">{{ sessionData.tipo }}</h2>
                            <p class="inline-flex items-center gap-2 bg-surface-100 text-surface-700 px-3 py-1.5 rounded-lg text-sm font-semibold border border-surface-200 w-fit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                                {{ sessionData.espacio }}
                            </p>
                        </div>

                        <!-- Grid de Info -->
                        <div class="grid grid-cols-2 gap-4 bg-surface-50 p-4 rounded-2xl border border-dashed border-surface-300 mt-2">
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-bold text-surface-500 uppercase tracking-widest">Horario</span>
                                <div class="flex items-center gap-2 text-surface-900 font-bold text-sm md:text-base">
                                    <IconClock class="w-4 h-4 text-primary-600 shrink-0" />
                                    <span>{{ sessionData.horaInicio }} - {{ sessionData.horaFin }}</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-bold text-surface-500 uppercase tracking-widest">Aforo Permitido</span>
                                <div class="flex items-center gap-2 text-surface-900 font-bold text-sm md:text-base">
                                    <IconUser class="w-4 h-4 text-primary-600 shrink-0" />
                                    <span>MAX {{ sessionData.capacidadMaxima }} px</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Panel de Acciones -->
                <article class="bg-white rounded-3xl border border-surface-200 p-6 md:p-8 shadow-sm flex flex-col gap-4">
                    <h3 class="text-xs font-bold text-surface-400 uppercase tracking-widest mb-2 m-0 border-b border-surface-100 pb-3">Acciones Operativas</h3>

                    <button @click="handleRegistrarAsistencia" class="w-full bg-primary-50 hover:bg-primary-100 text-primary-700 border border-primary-200 rounded-xl px-4 py-3.5 font-bold transition-all active:scale-95 flex items-center justify-center gap-3">
                        <IconQr class="w-5 h-5 shrink-0 text-primary-600" />
                        Escanear Pases QR
                    </button>

                    <button @click="handleEmpezarSesion" class="w-full text-white rounded-xl px-4 py-3.5 font-bold transition-all flex items-center justify-center gap-3 shadow-sm border"
                            :class="sessionData.status === 'En curso' ? 'bg-surface-300 border-surface-300 text-surface-600 cursor-not-allowed shadow-none' : 'bg-primary-600 hover:bg-primary-700 border-primary-500 active:scale-95 shadow-primary-600/20'"
                            :disabled="sessionData.status === 'En curso'">
                        <IconStart class="w-5 h-5 shrink-0" />
                        {{ sessionData.status === 'En curso' ? 'Sesión en Curso' : 'Empezar Clase' }}
                    </button>
                </article>

            </section>

        </div>
    </main>
</template>
