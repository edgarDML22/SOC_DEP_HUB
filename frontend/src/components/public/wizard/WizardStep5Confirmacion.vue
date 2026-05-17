<template>
  <div class="wizard-step">
    <div v-if="!store.exito" class="confirmation-view">
      <h2 class="step-title">Resumen de Registro</h2>
      <p class="step-description">Verifica que tus datos sean correctos antes de finalizar.</p>

      <!-- Banner de Equipo (si aplica) -->
      <div v-if="store.tipo === 'EQUIPO'" class="team-banner-summary">
        <i class="fas fa-users"></i>
        <span><strong>Equipo:</strong> {{ store.nombre_equipo }}</span>
      </div>

      <div class="summary-container">
        <!-- Resumen Capitán -->
        <div class="summary-section">
          <h3><i class="fas fa-user-tag"></i> {{ store.tipo === 'EQUIPO' ? 'Capitán' : 'Participante' }}</h3>
          <div class="summary-details">
            <p><strong>Nombre:</strong> {{ store.datosCapitan.nombre }} {{ store.datosCapitan.apellido }}</p>
            <p><strong>Email:</strong> {{ store.datosCapitan.email }}</p>
            <p><strong>Teléfono:</strong> {{ store.datosCapitan.telefono }}</p>
            <p><strong>F. Nacimiento:</strong> {{ store.datosCapitan.fecha_nacimiento }}</p>
            <p><strong>Género:</strong> {{ getGeneroLabel(store.datosCapitan.genero) }}</p>
            <p><strong>Ranking:</strong> {{ store.datosCapitan.ranking_declarado }}</p>
          </div>
        </div>

        <!-- Resumen Compañero -->
        <div v-if="store.tipo === 'EQUIPO'" class="summary-section">
          <h3><i class="fas fa-user-friends"></i> Compañero</h3>
          <div class="summary-details">
            <p><strong>Nombre:</strong> {{ store.datosCompanero.nombre }} {{ store.datosCompanero.apellido }}</p>
            <p><strong>Email:</strong> {{ store.datosCompanero.email }}</p>
            <p><strong>Teléfono:</strong> {{ store.datosCompanero.telefono }}</p>
            <p><strong>F. Nacimiento:</strong> {{ store.datosCompanero.fecha_nacimiento }}</p>
            <p><strong>Género:</strong> {{ getGeneroLabel(store.datosCompanero.genero) }}</p>
            <p><strong>Ranking:</strong> {{ store.datosCompanero.ranking_declarado }}</p>
          </div>
        </div>

        <!-- Resumen Documentos -->
        <div class="summary-section">
          <h3><i class="fas fa-file-alt"></i> Documentación</h3>
          <div class="summary-details">
            <ul class="file-list">
              <li><i class="fas fa-check"></i> INE (Capitán)</li>
              <li><i class="fas fa-check"></i> CURP (Capitán)</li>
              <li><i class="fas fa-check"></i> Carta Responsiva (Capitán)</li>
              <template v-if="store.tipo === 'EQUIPO'">
                <li><i class="fas fa-check"></i> INE (Compañero)</li>
                <li><i class="fas fa-check"></i> CURP (Compañero)</li>
                <li><i class="fas fa-check"></i> Carta Responsiva (Compañero)</li>
              </template>
            </ul>
          </div>
        </div>
      </div>

      <p v-if="store.error" class="error-banner">
        <i class="fas fa-exclamation-circle"></i>
        {{ store.error }}
      </p>

      <div class="step-actions">
        <button type="button" class="btn-secondary" :disabled="store.loading" @click="prevStep">
          <i class="fas fa-arrow-left"></i>
          Atrás
        </button>
        <button type="button" class="btn-primary btn-submit" :disabled="store.loading" @click="handleSubmit">
          <template v-if="store.loading">
            <i class="fas fa-spinner fa-spin"></i>
            Procesando...
          </template>
          <template v-else>
            Finalizar Registro
            <i class="fas fa-check"></i>
          </template>
        </button>
      </div>
    </div>

    <!-- Éxito -->
    <div v-else class="success-view">
      <div class="success-icon">
        <i class="fas fa-check-circle"></i>
      </div>
      <h2 class="step-title">¡Registro Recibido!</h2>
      <p class="success-msg">
        Tu solicitud de pre-registro se ha procesado correctamente.
      </p>
      <div class="success-info">
        <p>Hemos enviado los detalles a:</p>
        <p class="email-highlight">{{ store.datosCapitan.email }}</p>
        <p class="instruction">Recibirás confirmación y tu código QR en este correo una vez que sea validado.</p>
      </div>
      <button class="btn-primary" @click="finish">
        Volver al Inicio
      </button>
    </div>
  </div>
</template>

<script setup>
import { usePreRegisterStore } from "@/stores/preRegisterStore";
import { useRoute, useRouter } from "vue-router";

const store = usePreRegisterStore();
const route = useRoute();
const router = useRouter();

const getGeneroLabel = (g) => {
  if (g === "M") return "Masculino";
  if (g === "F") return "Femenino";
  if (g === "X") return "No Binario / Otro";
  return "No especificado";
};

const prevStep = () => {
  store.setPaso(4);
};

const handleSubmit = async () => {
  const id_torneo = route.params.id;
  try {
    await store.submitRegistro(id_torneo);
  } catch (error) {
    console.error("Error al enviar registro:", error);
  }
};

const finish = () => {
  store.resetStore();
  router.push("/");
};
</script>

<style scoped>
.wizard-step {
  animation: fadeIn 0.5s ease-out;
}

.step-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
  text-align: center;
}

.step-description {
  color: var(--text-secondary);
  text-align: center;
  margin-bottom: 2rem;
}

.summary-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-section {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 1.2rem;
  padding: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.summary-section h3 {
  font-size: 1rem;
  margin-bottom: 1rem;
  color: var(--primary-color);
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.summary-details p {
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
  color: var(--text-secondary);
}

.summary-details strong {
  color: var(--text-primary);
}

.file-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.file-list li {
  font-size: 0.85rem;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
}

.file-list li i {
  color: #4caf50;
  font-size: 0.8rem;
}

.error-banner {
  background: rgba(244, 67, 54, 0.1);
  border-left: 4px solid #f44336;
  color: #f44336;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  font-size: 0.9rem;
}

.success-view {
  text-align: center;
  padding: 2rem 0;
}

.success-icon {
  font-size: 5rem;
  color: #4caf50;
  margin-bottom: 1.5rem;
  animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.success-msg {
  font-size: 1.2rem;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
}

.success-info {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 1.2rem;
  padding: 2rem;
  margin-bottom: 2rem;
}

.email-highlight {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--primary-color);
  margin: 0.5rem 0;
}

.instruction {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin-top: 1rem;
}

.step-actions {
  display: flex;
  justify-content: space-between;
}

.btn-primary, .btn-secondary {
  padding: 0.8rem 2rem;
  border-radius: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  transition: all 0.3s ease;
}

.btn-submit {
  background: var(--primary-color);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes scaleIn {
  from { transform: scale(0); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.team-banner-summary {
  background: rgba(var(--primary-rgb), 0.1);
  border: 1px solid rgba(var(--primary-color), 0.2);
  border-radius: 1rem;
  padding: 1rem 1.5rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  font-size: 1.1rem;
  color: var(--text-primary);
  animation: fadeIn 0.4s ease-out;
}

.team-banner-summary i {
  color: var(--primary-color);
  font-size: 1.3rem;
}
</style>
