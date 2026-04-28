<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useSocioStore } from '@/stores/socioStore';
import { useAlerts } from '@/composables/useAlerts';
import IconWarning from '@/components/icons/IconWarning.vue';

const route = useRoute();
const router = useRouter();
const socioId = route.params.id;

const socioStore = useSocioStore();
const { fetchSocioDetails, updateSocio, penalizeSocio } = socioStore;
const { confirmWarning, toastInfo } = useAlerts();

// Usar propiedad computada vinculada al store para máxima reactividad
const socio = computed(() => socioStore.getSocioById(socioId));

const isLoading = ref(true);
const errorMsg = ref('');



// Modal de Penalizaciones
const showPenaltyModal = ref(false);
const isSaving = ref(false);
const editForm = ref({
    estatus_cuenta: 'AL_CORRIENTE',
    contador_no_shows: 0,
    retrasos_ludoteca: 0,
});

onMounted(async () => {
    try {
        // fetchSocioDetails se encarga de cargar el socio en el store si no está o si le faltan detalles
        await fetchSocioDetails(socioId);
        // El watcher de arriba se encargará de llamar a poblarFormulario() automáticamente
    } catch (error) {
        console.error("Error al cargar socio:", error);
        errorMsg.value = "Hubo un problema al cargar los detalles de este socio.";
    } finally {
        isLoading.value = false;
    }
});

const poblarFormulario = () => {
    if (!socio.value) return;
    editForm.value = {
        estatus_cuenta: socio.value.estatus_cuenta || 'AL_CORRIENTE',
        contador_no_shows: socio.value.contador_no_shows || 0,
        retrasos_ludoteca: socio.value.retrasos_ludoteca || 0,
    };
};

const saveSocioUpdates = async () => {
    isSaving.value = true;
    try {
        const res = await updateSocio(socioId, editForm.value);
        if (res.success) {
            showPenaltyModal.value = false;
            toastInfo("Éxito", "Penalizaciones actualizadas.", "success");
        } else {
            toastInfo("Error", res.error, "error");
        }
    } catch (error) {
        console.error("Error al actualizar socio:", error);
    } finally {
        isSaving.value = false;
    }
};

const handlePenalizeAction = async () => {
    const confirm = await confirmWarning(
        "¿Desea penalizar al socio?",
        `Se aplicará una penalización de 7 días a ${socio.value.nombre_completo}. Esta acción bloqueará sus reservas.`,
        "Sí, Penalizar"
    );

    if (confirm.isConfirmed) {
        isSaving.value = true;
        try {
            const res = await penalizeSocio(socioId, editForm.value);
            if (res.success) {
                poblarFormulario();
                toastInfo("Penalizado", "Socio penalizado correctamente.", "success");
            } else {
                toastInfo("Error", res.error, "error");
            }
        } catch (error) {
            console.error("Error al penalizar socio:", error);
        } finally {
            isSaving.value = false;
        }
    }
};

// Watcher para poblar el formulario cuando el socio se cargue en el store
watch(socio, (newSocio) => {
    if (newSocio) {
        poblarFormulario();
    }
}, { immediate: true });

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
                    'bar-info': socio.estatus_cuenta === 'PENALIZADO'
                }"></div>

                <div class="featured-body">
                    <div class="featured-header">
                        <span class="status-badge" :class="{
                            'badge-success': socio.estatus_cuenta === 'AL_CORRIENTE',
                            'badge-warning': socio.estatus_cuenta === 'MOROSO',
                            'badge-danger': socio.estatus_cuenta === 'SUSPENDIDO',
                            'badge-info': socio.estatus_cuenta === 'PENALIZADO'
                        }">
                            {{ socio.estatus_cuenta }}
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
                    </div>

                    <!-- Penalizaciones -->
                    <div class="mt-6 border-t pt-4">
                        <h4 class="font-bold text-gray-700 mb-4">Penalizaciones / Infracciones</h4>
                        <div class="penalties-grid">
                            <div class="penalty-card">
                                <div class="penalty-icon bg-red-100 text-red-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="penalty-info">
                                    <span class="penalty-count"
                                        :class="{ 'text-red-600': socio.contador_no_shows > 0 }">{{
                                            socio.contador_no_shows }}</span>
                                    <span class="penalty-name">No Shows</span>
                                </div>
                            </div>

                            <div class="penalty-card">
                                <div class="penalty-icon bg-yellow-100 text-yellow-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="penalty-info">
                                    <span class="penalty-count"
                                        :class="{ 'text-yellow-600': socio.retrasos_ludoteca > 0 }">{{
                                            socio.retrasos_ludoteca }}</span>
                                    <span class="penalty-name">Retrasos Ludoteca</span>
                                </div>
                            </div>
                        </div>

                        <!-- Banner de alerta cuando el socio supera el umbral de penalizaciones -->
                        <div v-if="socio.contador_no_shows >= 3 && socio.estatus_cuenta !== 'PENALIZADO' && socio.estatus_cuenta !== 'SUSPENDIDO'"
                            class="alert-penalty">
                            <div class="alert-penalty-icon">
                                <IconWarning class="w-6 h-6 text-amber-600" />
                            </div>
                            <div class="alert-penalty-body">
                                <p class="alert-penalty-title">Umbral de No Shows superado</p>
                                <p class="alert-penalty-desc">Este socio tiene <strong>{{ socio.contador_no_shows }} No
                                        Shows</strong>. Puedes aplicarle una penalización de 7 días desde "Gestionar
                                    Socio".</p>
                            </div>
                            <button @click="handlePenalizeAction" class="alert-penalty-btn">
                                Penalizar
                            </button>
                        </div>
                    </div>

                    <div class="actions-group mt-8 flex gap-3">
                        <button @click="showPenaltyModal = true" class="btn-primary flex-1">Gestionar
                            Penalizaciones</button>
                        <button v-if="socio.estatus_cuenta !== 'PENALIZADO'" @click="handlePenalizeAction"
                            class="btn-outline-danger flex-1">
                            Aplicar Penalización 7 Días
                        </button>
                    </div>
                </div>
            </article>

            <!-- Tarjeta Familiares -->
            <article class="featured-card mt-6" v-if="socio.modalidad_plan === 'FAMILIAR'">
                <div class="featured-body">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Miembros Familiares Asignados</h3>

                    <div v-if="!socio.miembros_familiares || socio.miembros_familiares.length === 0"
                        class="empty-state text-center py-6 text-gray-500 italic">
                        No tiene miembros familiares registrados.
                    </div>

                    <div class="family-list" v-else>
                        <div v-for="fam in socio.miembros_familiares" :key="fam.id_miembro" class="family-card">
                            <div class="family-icon">{{ fam.nombre_completo.charAt(0) }}</div>
                            <div class="family-info">
                                <h4>{{ fam.nombre_completo }}</h4>
                                <p>{{ fam.parentesco }} | {{ fam.genero }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

        </section>

        <!-- MODAL GESTIONAR PENALIZACIONES -->
        <div v-if="showPenaltyModal" class="modal-backdrop" @click.self="showPenaltyModal = false">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Gestionar Penalizaciones e Infracciones</h2>
                    <button @click="showPenaltyModal = false" class="btn-close">×</button>
                </div>

                <div class="modal-body">
                    <div class="form-section">
                        <h4 class="section-title text-red-600">Control de Estatus y Penalizaciones</h4>

                        <div class="form-group mb-6">
                            <label>Estatus de Cuenta / Penalización Manual</label>
                            <p class="text-[10px] text-gray-500 mb-2">Cambiar a 'PENALIZADO' activará el bloqueo
                                temporal (7 días por defecto).</p>
                            <select v-model="editForm.estatus_cuenta" class="status-select font-bold text-base p-4"
                                :class="{
                                    'text-green-600': editForm.estatus_cuenta === 'AL_CORRIENTE',
                                    'text-yellow-600': editForm.estatus_cuenta === 'MOROSO',
                                    'text-red-600': editForm.estatus_cuenta === 'SUSPENDIDO',
                                    'text-blue-600': editForm.estatus_cuenta.startsWith('PENALIZADO')
                                }">
                                <option value="AL_CORRIENTE">✅ AL CORRIENTE (Sin Bloqueos)</option>
                                <option value="PENALIZADO_AMBOS">⏳ PENALIZADO AMBOS (Ludoteca + Reservas)</option>
                                <option value="PENALIZADO_RESERVA">⏳ PENALIZADO RESERVAS (No Show)</option>
                                <option value="PENALIZADO_LUDOTECA">⏳ PENALIZADO LUDOTECA (Retrasos)</option>
                                <option value="PENALIZADO">⏳ PENALIZADO (General)</option>
                                <option value="MOROSO">⚠️ MOROSO (Deuda Pendiente)</option>
                                <option value="SUSPENDIDO">🚫 SUSPENDIDO (Bloqueo Permanente)</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group half">
                                <label>Contador No Shows</label>
                                <div class="counter-input">
                                    <button @click="editForm.contador_no_shows--"
                                        :disabled="editForm.contador_no_shows <= 0">-</button>
                                    <input type="number" v-model.number="editForm.contador_no_shows" min="0" />
                                    <button @click="editForm.contador_no_shows++">+</button>
                                </div>
                            </div>
                            <div class="form-group half">
                                <label>Retrasos Ludoteca</label>
                                <div class="counter-input">
                                    <button @click="editForm.retrasos_ludoteca--"
                                        :disabled="editForm.retrasos_ludoteca <= 0">-</button>
                                    <input type="number" v-model.number="editForm.retrasos_ludoteca" min="0" />
                                    <button @click="editForm.retrasos_ludoteca++">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="showPenaltyModal = false" class="btn-secondary">Cancelar</button>
                    <button @click="saveSocioUpdates" class="btn-primary" :disabled="isSaving">
                        {{ isSaving ? 'Guardando...' : 'Aplicar Cambios' }}
                    </button>
                </div>
            </div>
        </div>

    </main>
</template>

<style scoped>
.home-socio {
    background-color: var(--p-surface-50, #f8fafc);
    padding: 1rem;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    font-family: inherit;
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
    border: 1px solid var(--p-surface-200, #e2e8f0);
    border-radius: 12px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--p-surface-700, #334155);
    cursor: pointer;
    transition: all 0.2s;
}

.btn-back:hover {
    background-color: var(--p-surface-100, #f1f5f9);
}

.icon-back {
    width: 24px;
    height: 24px;
}

.header-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    color: var(--p-surface-900, #111827);
}

.detail-content {
    display: flex;
    flex-direction: column;
}

.featured-card {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--p-surface-200, #e2e8f0);
    overflow: hidden;
    position: relative;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.card-accent-bar {
    height: 6px;
}

.bar-success {
    background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
}

.bar-warning {
    background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
}

.bar-danger {
    background: linear-gradient(90deg, #ef4444 0%, #f87171 100%);
}

.featured-body {
    padding: 1.5rem;
}

.featured-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
}

.badge-success {
    background-color: #10b981;
}

.badge-warning {
    background-color: #f59e0b;
}

.badge-danger {
    background-color: #ef4444;
}

.session-type {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0 0 0.5rem 0;
    color: var(--p-surface-900, #111827);
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    background-color: var(--p-surface-50, #f8fafc);
    border-radius: 12px;
    padding: 1rem;
}

.info-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    color: var(--p-surface-500, #64748b);
    font-weight: 600;
    text-transform: uppercase;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--p-surface-900, #111827);
}

/* Penalties */
.penalties-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.penalty-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--p-surface-50, #f8fafc);
    padding: 1rem;
    border-radius: 12px;
    border: 1px solid var(--p-surface-200, #e2e8f0);
}

.penalty-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.penalty-info {
    display: flex;
    flex-direction: column;
}

.penalty-count {
    font-size: 1.25rem;
    font-weight: 800;
    line-height: 1;
}

.penalty-name {
    font-size: 0.75rem;
    color: var(--p-surface-500, #64748b);
    font-weight: 600;
}

/* Familiares */
.family-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.family-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border-radius: 12px;
    border: 1px solid var(--p-surface-200, #e2e8f0);
}

.family-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--p-primary-100, #dbeafe);
    color: var(--p-primary-700, #1d4ed8);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.family-info h4 {
    margin: 0;
    font-size: 0.95rem;
    color: #111827;
}

.family-info p {
    margin: 0;
    font-size: 0.75rem;
    color: #64748b;
}

/* Buttons */
.btn-primary {
    background-color: var(--p-primary-600, #2563eb);
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    text-align: center;
}

.btn-primary:hover {
    background-color: var(--p-primary-700, #1d4ed8);
}

.btn-secondary {
    background-color: white;
    color: var(--p-surface-700, #334155);
    border: 1px solid var(--p-surface-300, #cbd5e1);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background-color: var(--p-surface-100, #f1f5f9);
}

.btn-outline-danger {
    background-color: white;
    color: #ef4444;
    border: 1px solid #fca5a5;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.btn-outline-danger:hover {
    background-color: #fef2f2;
    border-color: #ef4444;
}

.flex {
    display: flex;
}

.flex-1 {
    flex: 1;
}

.gap-3 {
    gap: 0.75rem;
}

.w-full {
    width: 100%;
}

.mt-4 {
    margin-top: 1rem;
}

.mt-6 {
    margin-top: 1.5rem;
}

.mt-8 {
    margin-top: 2rem;
}

.pt-4 {
    padding-top: 1rem;
}

.border-t {
    border-top: 1px solid var(--p-surface-200, #e2e8f0);
}

/* Modales */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-content {
    background: white;
    width: 100%;
    max-width: 500px;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}

.modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--p-surface-200, #e2e8f0);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.2rem;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.section-title {
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: #111827;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    margin-bottom: 0.75rem;
}

.form-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--p-surface-700, #334155);
}

.form-group input,
.form-group select {
    padding: 0.6rem;
    border-radius: 8px;
    border: 1px solid var(--p-surface-300, #cbd5e1);
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--p-primary-500, #3b82f6);
}

.form-row {
    display: flex;
    gap: 1rem;
}

.half {
    flex: 1;
}

.counter-input {
    display: flex;
    border: 1px solid var(--p-surface-300, #cbd5e1);
    border-radius: 8px;
    overflow: hidden;
}

.counter-input button {
    background: var(--p-surface-100, #f1f5f9);
    border: none;
    padding: 0.5rem 1rem;
    font-weight: bold;
    color: var(--p-surface-700, #334155);
    cursor: pointer;
}

.counter-input button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.counter-input input {
    flex: 1;
    border: none !important;
    border-radius: 0 !important;
    text-align: center;
    width: 100%;
    font-weight: bold;
}

.modal-footer {
    padding: 1.25rem 1.5rem;
    border-top: 1px solid var(--p-surface-200, #e2e8f0);
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

/* Loading y Error */
.loading-state {
    text-align: center;
    padding: 3rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--p-surface-200, #e2e8f0);
    border-top-color: var(--p-primary-600, #2563eb);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    100% {
        transform: rotate(360deg);
    }
}

.error-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #ef4444;
    font-weight: bold;
}

/* Banner de alerta de penalización */
.alert-penalty {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 12px;
    background: linear-gradient(135deg, #fef2f2, #fff7ed);
    border: 1px solid #fca5a5;
    animation: pulse-border 2s ease-in-out infinite;
}

@keyframes pulse-border {

    0%,
    100% {
        border-color: #fca5a5;
    }

    50% {
        border-color: #f87171;
    }
}

.alert-penalty-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.alert-penalty-body {
    flex: 1;
}

.alert-penalty-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #991b1b;
    margin: 0 0 0.2rem 0;
}

.alert-penalty-desc {
    font-size: 0.75rem;
    color: #b91c1c;
    margin: 0;
}

.alert-penalty-btn {
    background-color: #ef4444;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.8rem;
    cursor: pointer;
    white-space: nowrap;
    transition: background-color 0.2s;
    flex-shrink: 0;
}

.alert-penalty-btn:hover {
    background-color: #dc2626;
}

.home-socio {
    background-color: var(--p-surface-50, #f8fafc);
    padding: 1rem;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    font-family: inherit;
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
    border: 1px solid var(--p-surface-200, #e2e8f0);
    border-radius: 12px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--p-surface-700, #334155);
    cursor: pointer;
    transition: all 0.2s;
}

.btn-back:hover {
    background-color: var(--p-surface-100, #f1f5f9);
}

.icon-back {
    width: 24px;
    height: 24px;
}

.header-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    color: var(--p-surface-900, #111827);
}

.detail-content {
    display: flex;
    flex-direction: column;
}

.featured-card {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--p-surface-200, #e2e8f0);
    overflow: hidden;
    position: relative;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.card-accent-bar {
    height: 6px;
}

.bar-success {
    background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
}

.bar-warning {
    background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
}

.bar-danger {
    background: linear-gradient(90deg, #ef4444 0%, #f87171 100%);
}

.featured-body {
    padding: 1.5rem;
}

.featured-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
}

.badge-success {
    background-color: #10b981;
}

.badge-warning {
    background-color: #f59e0b;
}

.badge-danger {
    background-color: #ef4444;
}

.session-type {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0 0 0.5rem 0;
    color: var(--p-surface-900, #111827);
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    background-color: var(--p-surface-50, #f8fafc);
    border-radius: 12px;
    padding: 1rem;
}

.info-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    color: var(--p-surface-500, #64748b);
    font-weight: 600;
    text-transform: uppercase;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--p-surface-900, #111827);
}

/* Penalties */
.penalties-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.penalty-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--p-surface-50, #f8fafc);
    padding: 1rem;
    border-radius: 12px;
    border: 1px solid var(--p-surface-200, #e2e8f0);
}

.penalty-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.penalty-info {
    display: flex;
    flex-direction: column;
}

.penalty-count {
    font-size: 1.25rem;
    font-weight: 800;
    line-height: 1;
}

.penalty-name {
    font-size: 0.75rem;
    color: var(--p-surface-500, #64748b);
    font-weight: 600;
}

/* Familiares */
.family-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.family-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border-radius: 12px;
    border: 1px solid var(--p-surface-200, #e2e8f0);
}

.family-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--p-primary-100, #dbeafe);
    color: var(--p-primary-700, #1d4ed8);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.family-info h4 {
    margin: 0;
    font-size: 0.95rem;
    color: #111827;
}

.family-info p {
    margin: 0;
    font-size: 0.75rem;
    color: #64748b;
}

/* Buttons */
.btn-primary {
    background-color: var(--p-primary-600, #2563eb);
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    text-align: center;
}

.btn-primary:hover {
    background-color: var(--p-primary-700, #1d4ed8);
}

.btn-secondary {
    background-color: white;
    color: var(--p-surface-700, #334155);
    border: 1px solid var(--p-surface-300, #cbd5e1);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background-color: var(--p-surface-100, #f1f5f9);
}

.btn-outline-danger {
    background-color: white;
    color: #ef4444;
    border: 1px solid #fca5a5;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.btn-outline-danger:hover {
    background-color: #fef2f2;
    border-color: #ef4444;
}

.flex {
    display: flex;
}

.flex-1 {
    flex: 1;
}

.gap-3 {
    gap: 0.75rem;
}

.w-full {
    width: 100%;
}

.mt-4 {
    margin-top: 1rem;
}

.mt-6 {
    margin-top: 1.5rem;
}

.mt-8 {
    margin-top: 2rem;
}

.pt-4 {
    padding-top: 1rem;
}

.border-t {
    border-top: 1px solid var(--p-surface-200, #e2e8f0);
}

/* Modales */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-content {
    background: white;
    width: 100%;
    max-width: 500px;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}

.modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--p-surface-200, #e2e8f0);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.2rem;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.section-title {
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: #111827;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    margin-bottom: 0.75rem;
}

.form-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--p-surface-700, #334155);
}

.form-group input,
.form-group select {
    padding: 0.6rem;
    border-radius: 8px;
    border: 1px solid var(--p-surface-300, #cbd5e1);
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--p-primary-500, #3b82f6);
}

.form-row {
    display: flex;
    gap: 1rem;
}

.half {
    flex: 1;
}

.counter-input {
    display: flex;
    border: 1px solid var(--p-surface-300, #cbd5e1);
    border-radius: 8px;
    overflow: hidden;
}

.counter-input button {
    background: var(--p-surface-100, #f1f5f9);
    border: none;
    padding: 0.5rem 1rem;
    font-weight: bold;
    color: var(--p-surface-700, #334155);
    cursor: pointer;
}

.counter-input button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.counter-input input {
    flex: 1;
    border: none !important;
    border-radius: 0 !important;
    text-align: center;
    width: 100%;
    font-weight: bold;
}

.modal-footer {
    padding: 1.25rem 1.5rem;
    border-top: 1px solid var(--p-surface-200, #e2e8f0);
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

/* Loading y Error */
.loading-state {
    text-align: center;
    padding: 3rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--p-surface-200, #e2e8f0);
    border-top-color: var(--p-primary-600, #2563eb);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    100% {
        transform: rotate(360deg);
    }
}

.error-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #ef4444;
    font-weight: bold;
}

/* Banner de alerta de penalización */
.alert-penalty {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 12px;
    background: linear-gradient(135deg, #fef2f2, #fff7ed);
    border: 1px solid #fca5a5;
    animation: pulse-border 2s ease-in-out infinite;
}

@keyframes pulse-border {

    0%,
    100% {
        border-color: #fca5a5;
    }

    50% {
        border-color: #f87171;
    }
}

.alert-penalty-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.alert-penalty-body {
    flex: 1;
}

.alert-penalty-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #991b1b;
    margin: 0 0 0.2rem 0;
}

.alert-penalty-desc {
    font-size: 0.75rem;
    color: #b91c1c;
    margin: 0;
}

.alert-penalty-btn {
    background-color: #ef4444;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.8rem;
    cursor: pointer;
    white-space: nowrap;
    transition: background-color 0.2s;
    flex-shrink: 0;
}

.alert-penalty-btn:hover {
    background-color: #dc2626;
}
</style>
