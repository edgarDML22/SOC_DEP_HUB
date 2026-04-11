<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';


// Importando Iconos de tu proyecto
import {
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
    <main class="home-instructor">

        <!-- Navegación Superior -->
        <header class="app-header">
            <button @click="goBack" class="btn-back">
                <svg class="icon-back" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </button>
            <h1 class="header-title">Detalles de la Actividad</h1>
        </header>

        <!-- Estado de Error -->
        <section v-if="errorMsg" class="error-state">
            <p class="error-text">{{ errorMsg }}</p>
            <button @click="goBack" class="btn-outline">Volver a la Agenda</button>
        </section>

        <!-- Contenido Detallado -->
        <section v-if="sessionData && !isLoading" class="detail-content">

            <!-- Tarjeta Principal Visual -->
            <article class="session-card featured-card">
                <!-- Barra superior decorativa -->
                <div class="card-accent-bar"></div>

                <div class="featured-body">
                    <div class="featured-header">
                        <span class="status-badge" :class="`badge-${sessionData.statusType}`">
                            {{ sessionData.status }}
                        </span>
                        <span class="day-label">{{ sessionData.diaSemana }}</span>
                    </div>

                    <h2 class="session-type">{{ sessionData.tipo }}</h2>
                    <p class="session-location">
                        <svg class="icon-location" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        {{ sessionData.espacio }}
                    </p>

                    <div class="info-grid">
                        <div class="info-cell">
                            <span class="info-label">Horario</span>
                            <div class="info-value">
                                <IconClock class="icon-small" />
                                <span>{{ sessionData.horaInicio }} - {{ sessionData.horaFin }}</span>
                            </div>
                        </div>

                        <div class="info-cell">
                            <span class="info-label">Aforo Permitido</span>
                            <div class="info-value">
                                <IconUser class="icon-small" />
                                <span>MAX {{ sessionData.capacidadMaxima }} px</span>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Panel de Acciones -->
            <article class="actions-panel">
                <h3 class="panel-subtitle">Acciones Operativas</h3>

                <button @click="handleRegistrarAsistencia" class="btn-action btn-secondary">
                    <IconQr class="icon-action" />
                    Escanear Pases (QR)
                </button>

                <button @click="handleEmpezarSesion" class="btn-action btn-primary"
                    :class="{ 'btn-disabled': sessionData.status === 'En curso' }"
                    :disabled="sessionData.status === 'En curso'">
                    <IconStart class="icon-action" />
                    {{ sessionData.status === 'En curso' ? 'Sesión en Curso' : 'Empezar Clase' }}
                </button>
            </article>

        </section>

    </main>
</template>

<style scoped>
/* Contenedor Principal idéntico al Home */
.home-instructor {
    background-color: var(--p-surface-50, #f8fafc);
    padding: 1rem;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    font-family: inherit;
    padding-bottom: 100px;
}

/* Header Propio Navigation */
.app-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--p-surface-200, #e2e8f0);
}

.btn-back {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    background: transparent;
    border: none;
    border-radius: 50%;
    color: var(--p-surface-500, #64748b);
    cursor: pointer;
    transition: background-color 0.2s, color 0.2s;
}

.btn-back:hover {
    background-color: var(--p-surface-200, #e2e8f0);
    color: var(--p-surface-900, #111827);
}

.icon-back {
    width: 24px;
    height: 24px;
}

.header-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--p-surface-900, #111827);
    margin: 0;
}

/* Manejo de Contenido Detalle */
.detail-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Tarjeta Principal */
.featured-card {
    background-color: #ffffff;
    border: 1px solid var(--p-surface-200, #e2e8f0);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.card-accent-bar {
    height: 6px;
    width: 100%;
    background: linear-gradient(to right, var(--p-primary-400, #60a5fa), var(--p-primary-600, #2563eb));
}

.featured-body {
    padding: 1.25rem;
}

.featured-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.day-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--p-surface-400, #9ca3af);
    text-transform: uppercase;
}

.session-type {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--p-surface-900, #111827);
    margin: 0 0 0.5rem 0;
    line-height: 1.2;
}

.session-location {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    background-color: var(--p-surface-100, #f1f5f9);
    color: var(--p-surface-600, #475569);
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    border: 1px solid var(--p-surface-200, #e2e8f0);
}

.icon-location {
    width: 16px;
    height: 16px;
    color: var(--p-primary-500, #3b82f6);
}

/* Grid de Información */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    background-color: var(--p-surface-50, #f8fafc);
    padding: 1rem;
    border-radius: 12px;
    border: 1px dashed var(--p-surface-200, #e2e8f0);
}

.info-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.65rem;
    font-weight: 800;
    color: var(--p-surface-400, #9ca3af);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--p-surface-800, #1e293b);
}

.icon-small {
    width: 16px;
    height: 16px;
    color: var(--p-primary-600, #2563eb);
}

/* Panel de Acciones */
.actions-panel {
    background-color: #ffffff;
    border: 1px solid var(--p-surface-200, #e2e8f0);
    border-radius: 16px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.panel-subtitle {
    font-size: 0.75rem;
    font-weight: 800;
    color: var(--p-surface-400, #9ca3af);
    text-transform: uppercase;
    margin: 0 0 1rem 0;
}

.btn-action {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 1rem;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: transform 0.1s, background-color 0.2s, opacity 0.2s;
    margin-bottom: 0.75rem;
}

.btn-action:last-child {
    margin-bottom: 0;
}

.btn-action:active {
    transform: scale(0.98);
}

.icon-action {
    width: 20px;
    height: 20px;
}

/* Variables secundarias */
.btn-secondary {
    background-color: var(--p-surface-100, #f1f5f9);
    color: var(--p-primary-700, #1d4ed8);
    border: 1px solid var(--p-surface-200, #e2e8f0);
}

.btn-secondary:hover {
    background-color: var(--p-surface-200, #e2e8f0);
}

/* Variables primarias */
.btn-primary {
    background: linear-gradient(to right, var(--p-primary-500, #3b82f6), var(--p-primary-700, #1d4ed8));
    color: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
}

.btn-primary:hover {
    opacity: 0.9;
}

.btn-disabled {
    background: var(--p-surface-300, #cbd5e1);
    color: var(--p-surface-500, #64748b);
    cursor: not-allowed;
    box-shadow: none;
}

/* Estados de Error */
.error-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 3rem 1rem;
    background-color: #ffffff;
    border-radius: 12px;
    text-align: center;
}

.error-text {
    color: #ef4444;
    font-weight: bold;
    margin-bottom: 1rem;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--p-surface-300, #cbd5e1);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    color: var(--p-surface-700, #334155);
    font-weight: 600;
    cursor: pointer;
}

/* Variables compartidas para los Badges */
.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 800;
    color: white;
    text-transform: capitalize;
    white-space: nowrap;
}

.badge-success-dark {
    background-color: var(--p-primary-600, #2563eb);
}

.badge-success {
    background-color: #10b981;
}

.badge-info {
    background-color: #3b82f6;
    color: white;
}
</style>
