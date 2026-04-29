<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSocioStore } from '@/stores/socioStore';

const route = useRoute();
const router = useRouter();
const socioId = route.params.id;

const socioStore = useSocioStore();
const { fetchSocioDetails } = socioStore;

// Usar propiedad computada vinculada al store para máxima reactividad
const socio = computed(() => socioStore.getSocioById(socioId));

const isLoading = ref(true);
const errorMsg = ref('');

onMounted(async () => {
    try {
        await fetchSocioDetails(socioId);
    } catch (error) {
        console.error("Error al cargar socio:", error);
        errorMsg.value = "Hubo un problema al cargar los detalles de este socio.";
    } finally {
        isLoading.value = false;
    }
});

const goBack = () => {
    router.push('/admin/socios');
};
</script>

<template>
    <main class="home-socio">
        <!-- Navegación Superior -->
        <header class="app-header">
            <button @click="goBack" class="btn-back">
                <svg class="icon-back" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </button>
            <h1 class="header-title">Detalles del Socio Titular</h1>
        </header>

        <section v-if="errorMsg" class="error-state">
            <p class="error-text">{{ errorMsg }}</p>
            <button @click="goBack" class="btn-outline mt-4">Volver al listado</button>
        </section>

        <section v-if="isLoading" class="loading-state">
            <div class="spinner"></div>
            <p class="text-gray-500 font-medium">Cargando detalles...</p>
        </section>

        <!-- Contenido Detallado -->
        <section v-if="socio && !isLoading" class="detail-content">

            <!-- Tarjeta Principal Visual -->
            <article class="featured-card">
                <div class="card-accent-bar" :class="{
                    'bar-success': socio.estatus_cuenta === 'AL_CORRIENTE',
                    'bar-warning': socio.estatus_cuenta === 'MOROSO',
                    'bar-danger': socio.estatus_cuenta === 'SUSPENDIDO',
                    'bar-info': socio.estatus_cuenta && socio.estatus_cuenta.startsWith('PENALIZADO')
                }"></div>

                <div class="featured-body">
                    <div class="featured-header">
                        <span class="status-badge" :class="{
                            'badge-success': socio.estatus_cuenta === 'AL_CORRIENTE',
                            'badge-warning': socio.estatus_cuenta === 'MOROSO',
                            'badge-danger': socio.estatus_cuenta === 'SUSPENDIDO',
                            'badge-info': socio.estatus_cuenta && socio.estatus_cuenta.startsWith('PENALIZADO')
                        }">
                            {{ socio.estatus_cuenta ? socio.estatus_cuenta.replace('_', ' ') : 'S/E' }}
                        </span>
                        <span class="font-bold text-gray-500 text-sm">Acción: {{ socio.numero_accion }}</span>
                    </div>

                    <h2 class="session-type">{{ socio.nombre_completo }}</h2>
                    <p class="session-location text-gray-500 mt-2">
                        📧 {{ socio.correo_electronico || 'Sin correo registrado' }}
                    </p>

                    <div class="info-grid mt-6">
                        <div class="info-cell">
                            <span class="info-label">Tipo Socio</span>
                            <div class="info-value">
                                <span>{{ socio.tipo_socio }}</span>
                            </div>
                        </div>

                        <div class="info-cell">
                            <span class="info-label">Modalidad</span>
                            <div class="info-value">
                                <span>{{ socio.modalidad_plan }}</span>
                            </div>
                        </div>

                        <div class="info-cell">
                            <span class="info-label">Género</span>
                            <div class="info-value">
                                <span>{{ socio.genero }}</span>
                            </div>
                        </div>

                        <div class="info-cell">
                            <span class="info-label">Fecha Nacimiento</span>
                            <div class="info-value">
                                <span>{{ socio.fecha_nacimiento || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Datos Adicionales (Read-Only) -->
                    <div class="mt-8 border-t pt-6">
                        <h4 class="font-bold text-gray-700 mb-4">Información de Cuenta</h4>
                        <div class="stats-row">
                            <div class="stat-item">
                                <span class="stat-label">No Shows</span>
                                <span class="stat-value" :class="{ 'text-red-600': socio.contador_no_shows > 0 }">
                                    {{ socio.contador_no_shows }}
                                </span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Retrasos Ludoteca</span>
                                <span class="stat-value" :class="{ 'text-yellow-600': socio.retrasos_ludoteca > 0 }">
                                    {{ socio.retrasos_ludoteca }}
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
            </article>

        </section>
    </main>
</template>

<style scoped>
.home-socio {
    background-color: #f8fafc;
    padding: 1rem;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    padding-bottom: 90px;
}

.app-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.btn-back {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #334155;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-back:hover {
    background-color: #f1f5f9;
}

.icon-back {
    width: 24px;
    height: 24px;
}

.header-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    color: #111827;
}

.detail-content {
    max-width: 800px;
    width: 100%;
    margin: 0 auto;
}

.featured-card {
    background: white;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.card-accent-bar {
    height: 8px;
}

.bar-success { background: #10b981; }
.bar-warning { background: #f59e0b; }
.bar-danger { background: #ef4444; }
.bar-info { background: #3b82f6; }

.featured-body {
    padding: 2.5rem;
}

.featured-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
}

.badge-success { background-color: #10b981; }
.badge-warning { background-color: #f59e0b; }
.badge-danger { background-color: #ef4444; }
.badge-info { background-color: #3b82f6; }

.session-type {
    font-size: 2rem;
    font-weight: 800;
    color: #111827;
    margin: 0;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-top: 2rem;
    padding: 1.5rem;
    background-color: #f8fafc;
    border-radius: 20px;
}

.info-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.7rem;
    color: #64748b;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
}

.stats-row {
    display: flex;
    gap: 2rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #111827;
}

.loading-state {
    text-align: center;
    padding: 4rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e2e8f0;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin { 100% { transform: rotate(360deg); } }

.error-state {
    text-align: center;
    padding: 4rem;
    color: #ef4444;
}

.border-t { border-top: 1px solid #e2e8f0; }
.mt-4 { margin-top: 1rem; }
.mt-6 { margin-top: 1.5rem; }
.mt-8 { margin-top: 2rem; }
.pt-6 { padding-top: 1.5rem; }
</style>
