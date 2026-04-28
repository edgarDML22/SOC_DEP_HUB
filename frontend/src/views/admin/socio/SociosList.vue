<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useSocioStore } from '@/stores/socioStore';
import { storeToRefs } from 'pinia';
import { useAlerts } from '@/composables/useAlerts';

const router = useRouter();
const socioStore = useSocioStore();
const { confirmWarning, toastInfo } = useAlerts();

const { socios, isLoading, error: errorMsg } = storeToRefs(socioStore);
const { fetchSocios, updateSocio } = socioStore;

// FILTERS
const search = ref('');
const filterTipo = ref('TODAS');
const filterModalidad = ref('TODAS');
const filterGenero = ref('TODAS');
const filterEstatus = ref('TODAS');

// MODALS STATE
const showPenaltyModal = ref(false);
const showFamilyModal = ref(false);
const showGuestsModal = ref(false);
const selectedSocio = ref(null);
const isSaving = ref(false);

const editForm = ref({
  estatus_cuenta: 'AL_CORRIENTE',
  contador_no_shows: 0,
  retrasos_ludoteca: 0,
});

onMounted(async () => {
  await fetchSocios();
});

const filteredSocios = computed(() => {
  let result = socios.value;

  if (search.value) {
    const q = search.value.toLowerCase();
    result = result.filter(s =>
      (s.nombre_completo && s.nombre_completo.toLowerCase().includes(q)) ||
      (s.numero_accion && String(s.numero_accion).toLowerCase().includes(q))
    );
  }

  if (filterTipo.value !== 'TODAS') result = result.filter(s => s.tipo_socio === filterTipo.value);
  if (filterModalidad.value !== 'TODAS') result = result.filter(s => s.modalidad_plan === filterModalidad.value);
  if (filterGenero.value !== 'TODAS') result = result.filter(s => s.genero === filterGenero.value);
  if (filterEstatus.value !== 'TODAS') result = result.filter(s => s.estatus_cuenta === filterEstatus.value);

  return result;
});

// ACTIONS
const openDetails = (id) => {
  router.push({ path: `/admin/socios/${id}` });
};

const openPenalty = (socio) => {
  selectedSocio.value = socio;
  editForm.value = {
    estatus_cuenta: socio.estatus_cuenta || 'AL_CORRIENTE',
    contador_no_shows: socio.contador_no_shows || 0,
    retrasos_ludoteca: socio.retrasos_ludoteca || 0,
  };
  showPenaltyModal.value = true;
};

const openFamily = async (socio) => {
  selectedSocio.value = socio;
  showFamilyModal.value = true;
  await fetchSocioDetails(socio.id_socio);
};

const openGuests = async (socio) => {
  selectedSocio.value = socio;
  showGuestsModal.value = true;
  await fetchSocioDetails(socio.id_socio);
};

const savePenaltyUpdates = async () => {
  if (!selectedSocio.value) return;
  isSaving.value = true;
  try {
    const res = await updateSocio(selectedSocio.value.id_socio, editForm.value);
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

const applySpecificPenalty = async (status) => {
  editForm.value.estatus_cuenta = status;
  await savePenaltyUpdates();
};
</script>

<template>
  <main class="home-socio">

    <header class="home-header">
      <div>
        <p class="greeting-label">Administración</p>
        <h1 class="greeting-name">Socios Titulares</h1>
      </div>
    </header>

    <!-- Filtros -->
    <div class="filters-container">
      <input v-model="search" placeholder="Buscar por nombre o número de acción..." class="search-input" />

      <div class="filter-group">
        <div class="filter-item">
          <label>Tipo</label>
          <select v-model="filterTipo" class="filter-select">
            <option value="TODAS">Todos</option>
            <option value="ACCIONISTA">Accionista</option>
            <option value="RENTISTA">Rentista</option>
          </select>
        </div>

        <div class="filter-item">
          <label>Modalidad</label>
          <select v-model="filterModalidad" class="filter-select">
            <option value="TODAS">Todas</option>
            <option value="INDIVIDUAL">Individual</option>
            <option value="FAMILIAR">Familiar</option>
          </select>
        </div>

        <div class="filter-item">
          <label>Género</label>
          <select v-model="filterGenero" class="filter-select">
            <option value="TODAS">Todos</option>
            <option value="M">Masculino (M)</option>
            <option value="F">Femenino (F)</option>
          </select>
        </div>

        <div class="filter-item">
          <label>Estatus</label>
          <select v-model="filterEstatus" class="filter-select">
            <option value="TODAS">Todos</option>
            <option value="AL_CORRIENTE">Al Corriente</option>
            <option value="MOROSO">Moroso</option>
            <option value="SUSPENDIDO">Suspendido</option>
            <option v-for="st in ['PENALIZADO', 'PENALIZADO_AMBOS', 'PENALIZADO_LUDOTECA', 'PENALIZADO_RESERVA']" :key="st" :value="st">
              {{ st.replace('_', ' ') }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Lista de Socios -->
    <section v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
      <p class="text-surface-500 font-medium">Cargando socios...</p>
    </section>

    <section v-else-if="errorMsg" class="error-state">
      <p class="text-red-500 font-bold">{{ errorMsg }}</p>
    </section>

    <div v-else class="sessions-list">
      <div v-for="socio in filteredSocios" :key="socio.id_socio" class="session-card items-start py-4">

        <!-- Iniciales o Foto -->
        <div class="time-block">
          <span class="time-start">{{ socio.nombre_completo.split(' ')[0] }}</span>
        </div>

        <!-- Información -->
        <div class="session-details">
          <div class="flex justify-between items-start mb-1">
            <h4 class="client-name">{{ socio.nombre_completo }}</h4>
            <span class="status-badge text-[10px]" :class="{
              'badge-success': socio.estatus_cuenta === 'AL_CORRIENTE',
              'badge-warning': socio.estatus_cuenta === 'MOROSO',
              'badge-danger': socio.estatus_cuenta === 'SUSPENDIDO',
              'badge-info': socio.estatus_cuenta && socio.estatus_cuenta.startsWith('PENALIZADO')
            }">
              {{ socio.estatus_cuenta ? socio.estatus_cuenta.replace('_', ' ') : '' }}
            </span>
          </div>

          <p class="location-name">
            <span class="font-bold text-surface-900">Acción {{ socio.numero_accion }}</span>
            | {{ socio.tipo_socio }} | {{ socio.modalidad_plan }}
          </p>

          <!-- Acciones Rápidas (4 Botones) -->
          <div class="flex items-center gap-2 mt-4">
            <!-- 1. Más Detalles -->
            <button @click="openDetails(socio.id_socio)" class="w-9 h-9 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Ver Perfil Completo">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
            </button>

            <!-- 2. Penalizaciones -->
            <button @click="openPenalty(socio)" class="w-9 h-9 bg-red-50 text-red-600 rounded-xl flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Gestionar Penalizaciones">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="m4.93 4.93 14.14 14.14"/><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </button>

            <!-- 3. Invitados -->
            <button @click="openGuests(socio)" class="w-9 h-9 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center hover:bg-amber-600 hover:text-white transition-all shadow-sm" title="Gestionar Invitados">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </button>

            <!-- 4. Miembros Familiares -->
            <button @click="openFamily(socio)" class="w-9 h-9 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center hover:bg-purple-600 hover:text-white transition-all shadow-sm" title="Miembros Familiares">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-3-3.87"/><path d="M9 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/><circle cx="17" cy="7" r="4"/></svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Estado Vacío -->
      <section v-if="filteredSocios.length === 0" class="empty-state">
        <div class="empty-icon-circle">!</div>
        <h3 class="empty-title">Sin resultados</h3>
        <p class="empty-text">No se encontraron socios con esos filtros.</p>
      </section>
    </div>

    <!-- MODAL PENALIZACIONES -->
    <div v-if="showPenaltyModal" class="modal-backdrop" @click.self="showPenaltyModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Penalizaciones: {{ selectedSocio?.nombre_completo }}</h2>
          <button @click="showPenaltyModal = false" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group mb-6">
            <label>Estatus de Penalización</label>
            <select v-model="editForm.estatus_cuenta" class="status-select font-bold text-base p-4"
              :class="{
                'text-green-600': editForm.estatus_cuenta === 'AL_CORRIENTE',
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

          <div class="stats-grid mb-6">
            <div class="stat-box">
              <span class="stat-label">No Shows (Reservas)</span>
              <span class="stat-value" :class="{ 'text-red-600': editForm.contador_no_shows > 0 }">
                {{ editForm.contador_no_shows }}
              </span>
            </div>
            <div class="stat-box">
              <span class="stat-label">Retrasos Ludoteca</span>
              <span class="stat-value" :class="{ 'text-amber-600': editForm.retrasos_ludoteca > 0 }">
                {{ editForm.retrasos_ludoteca }}
              </span>
            </div>
          </div>

          <div class="penalty-actions">
            <h4 class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-3">Acciones de Penalización</h4>
            
            <div class="flex flex-col gap-3">
              <button @click="applySpecificPenalty('PENALIZADO_RESERVA')" 
                class="penalty-action-btn border-red-200 text-red-700 hover:bg-red-50"
                :disabled="isSaving">
                <span class="font-bold">Penalizar por Reservas</span>
                <span class="text-[10px] opacity-70">Bloquea reservaciones por 7 días</span>
              </button>

              <button @click="applySpecificPenalty('PENALIZADO_LUDOTECA')" 
                class="penalty-action-btn border-amber-200 text-amber-700 hover:bg-amber-50"
                :disabled="isSaving">
                <span class="font-bold">Penalizar por Ludoteca</span>
                <span class="text-[10px] opacity-70">Bloquea uso de ludoteca por 7 días</span>
              </button>

              <button @click="applySpecificPenalty('AL_CORRIENTE')" 
                class="penalty-action-btn border-green-200 text-green-700 hover:bg-green-50"
                :disabled="isSaving">
                <span class="font-bold">Quitar todas las penalizaciones</span>
                <span class="text-[10px] opacity-70">Restablecer estatus "Al Corriente"</span>
              </button>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showPenaltyModal = false" class="btn-secondary">Cerrar</button>
          <button @click="savePenaltyUpdates" class="btn-primary" :disabled="isSaving">
            {{ isSaving ? 'Guardando...' : 'Guardar Otros Cambios' }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL FAMILIARES -->
    <div v-if="showFamilyModal" class="modal-backdrop" @click.self="showFamilyModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Miembros Familiares: {{ selectedSocio?.nombre_completo }}</h2>
          <button @click="showFamilyModal = false" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <div v-if="!selectedSocio?.miembros_familiares || selectedSocio.miembros_familiares.length === 0" class="text-center py-8 text-surface-500">
            No hay miembros familiares registrados.
          </div>
          <div v-else class="flex flex-col gap-3">
            <div v-for="fam in selectedSocio.miembros_familiares" :key="fam.id_miembro" class="p-3 border border-surface-200 rounded-xl flex items-center gap-3">
              <div class="w-10 h-10 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center font-bold">
                {{ fam.nombre_completo.charAt(0) }}
              </div>
              <div>
                <p class="font-bold text-sm">{{ fam.nombre_completo }}</p>
                <p class="text-xs text-surface-500">{{ fam.parentesco }} | {{ fam.genero }}</p>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showFamilyModal = false" class="btn-secondary">Cerrar</button>
        </div>
      </div>
    </div>

    <!-- MODAL INVITADOS -->
    <div v-if="showGuestsModal" class="modal-backdrop" @click.self="showGuestsModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Pases de Invitados: {{ selectedSocio?.nombre_completo }}</h2>
          <button @click="showGuestsModal = false" class="btn-close">×</button>
        </div>
        <div class="modal-body">
          <div v-if="isLoading" class="flex flex-col items-center py-12">
            <div class="spinner mb-4"></div>
            <p class="text-surface-500 animate-pulse">Cargando pases de invitados...</p>
          </div>
          <div v-else-if="!selectedSocio?.invitados || selectedSocio.invitados.length === 0" class="text-center py-12 text-surface-500">
            <div class="text-4xl mb-4">🎫</div>
            <p class="font-medium">No se encontraron invitados</p>
            <p class="text-xs">Este socio no tiene pases de invitados registrados.</p>
          </div>
          <div v-else class="flex flex-col gap-4">
            <div v-for="guest in selectedSocio.invitados" :key="guest.id_invitado" class="p-4 border border-surface-200 rounded-2xl flex items-center justify-between hover:border-amber-200 hover:bg-amber-50/30 transition-all">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center font-bold text-lg shadow-inner">
                  {{ guest.nombre_invitado.charAt(0) }}
                </div>
                <div>
                  <p class="font-bold text-surface-900">{{ guest.nombre_invitado }}</p>
                  <p class="text-xs text-surface-500 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    {{ guest.correo || 'Sin correo' }}
                  </p>
                </div>
              </div>
              <div class="flex flex-col items-end gap-1">
                <span class="status-badge text-[10px]" :class="{
                  'badge-success': guest.pase?.estatus_acceso === 'ACTIVO',
                  'badge-danger': guest.pase?.estatus_acceso === 'USADO',
                  'badge-warning': guest.pase?.estatus_acceso === 'EXPIRADO' || !guest.pase?.estatus_acceso
                }">
                  {{ guest.pase?.estatus_acceso || 'SIN PASE' }}
                </span>
                <p class="text-[10px] font-medium text-surface-600" v-if="guest.pase?.fecha_expiracion">
                  {{ guest.pase.fecha_expiracion }}
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showGuestsModal = false" class="btn-secondary">Cerrar</button>
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

.home-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.greeting-label {
  font-size: 0.8rem;
  color: var(--p-surface-500, #6b7280);
  margin: 0 0 0.15rem 0;
}

.greeting-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--p-surface-900, #111827);
  margin: 0;
}

.filters-container {
  background-color: white;
  padding: 1.5rem;
  border-radius: 24px;
  border: 1px solid var(--p-surface-200, #e2e8f0);
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.search-input {
  width: 100%;
  padding: 1rem;
  border-radius: 16px;
  border: 1px solid var(--p-surface-200, #e2e8f0);
  font-size: 0.95rem;
  outline: none;
  background-color: var(--p-surface-50, #f8fafc);
}

.search-input:focus {
  border-color: var(--p-primary-500, #3b82f6);
}

.filter-group {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

@media (min-width: 768px) {
  .filter-group {
    grid-template-columns: repeat(4, 1fr);
  }
}

.filter-item {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.filter-item label {
  font-size: 0.65rem;
  font-weight: 800;
  color: var(--p-surface-400, #94a3b8);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.filter-select {
  padding: 0.75rem;
  border-radius: 12px;
  border: 1px solid var(--p-surface-200, #e2e8f0);
  background: white;
  font-size: 0.85rem;
  font-weight: 600;
  outline: none;
  cursor: pointer;
}

/* Modales */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
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
    border-radius: 2rem;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal-header {
    padding: 1.5rem;
    background: var(--p-primary-600, #2563eb);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
}

.btn-close {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 2rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--p-surface-500, #64748b);
    text-transform: uppercase;
}

.status-select {
    width: 100%;
    padding: 1rem;
    border-radius: 12px;
    border: 1px solid var(--p-surface-200, #e2e8f0);
    font-weight: 700;
    outline: none;
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
    border: 1px solid var(--p-surface-200, #e2e8f0);
    border-radius: 12px;
    overflow: hidden;
}

.counter-input button {
    background: var(--p-surface-50, #f8fafc);
    border: none;
    padding: 0.75rem 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s;
}

.counter-input button:hover:not(:disabled) {
    background: var(--p-surface-100, #f1f5f9);
}

.counter-input input {
    flex: 1;
    border: none;
    text-align: center;
    font-weight: 800;
    width: 100%;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid var(--p-surface-100, #f1f5f9);
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

.btn-secondary {
    background: white;
    border: 1px solid var(--p-surface-200, #e2e8f0);
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
}

.btn-primary {
    background: var(--p-primary-600, #2563eb);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Nuevos Estilos Penalizaciones */
.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.stat-box {
  background: var(--p-surface-50, #f8fafc);
  padding: 1rem;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  border: 1px solid var(--p-surface-100, #f1f5f9);
}

.stat-label {
  font-size: 0.65rem;
  font-weight: 700;
  color: var(--p-surface-500, #64748b);
  text-transform: uppercase;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--p-surface-900, #111827);
}

.penalty-action-btn {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  border: 1px solid;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.penalty-action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid var(--p-surface-200, #e2e8f0);
  border-top-color: var(--p-primary-500, #3b82f6);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
