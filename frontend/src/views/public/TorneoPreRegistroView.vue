<template>
  <div class="pre-registro-page">
    <div class="background-elements">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
    </div>

    <div class="container">
      <header class="page-header">
        <h1 class="logo">SOC_DEP_HUB</h1>
        <div class="tournament-badge">
          <i class="fas fa-trophy"></i>
          <span>Pre-registro al Torneo</span>
        </div>
      </header>

      <main class="wizard-container">
        <!-- Progress Bar -->
        <div v-if="!store.exito" class="progress-wrapper">
          <div class="progress-steps">
            <div 
              v-for="n in totalSteps" 
              :key="n" 
              class="step-indicator"
              :class="{ 
                active: store.paso === n, 
                completed: store.paso > n,
                skipped: isStepSkipped(n)
              }"
            >
              <div class="step-number">
                <i v-if="store.paso > n" class="fas fa-check"></i>
                <span v-else>{{ n }}</span>
              </div>
              <span class="step-label">{{ stepLabels[n-1] }}</span>
            </div>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: progressPercentage + '%' }"></div>
          </div>
        </div>

        <!-- Wizard Content -->
        <div class="wizard-content">
          <component :is="currentStepComponent" />
        </div>
      </main>

      <footer class="page-footer">
        <p>&copy; 2024 SOC_DEP_HUB. Todos los derechos reservados.</p>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { usePreRegisterStore } from "@/stores/preRegisterStore";
import WizardStep1Tipo from "@/components/public/wizard/WizardStep1Tipo.vue";
import WizardStep2DatosCapitan from "@/components/public/wizard/WizardStep2DatosCapitan.vue";
import WizardStep3DatosCompanero from "@/components/public/wizard/WizardStep3DatosCompanero.vue";
import WizardStep4Documentos from "@/components/public/wizard/WizardStep4Documentos.vue";
import WizardStep5Confirmacion from "@/components/public/wizard/WizardStep5Confirmacion.vue";

const store = usePreRegisterStore();

const totalSteps = 5;
const stepLabels = [
  "Modalidad",
  "Tus Datos",
  "Compañero",
  "Documentos",
  "Confirmar"
];

const currentStepComponent = computed(() => {
  switch (store.paso) {
    case 1: return WizardStep1Tipo;
    case 2: return WizardStep2DatosCapitan;
    case 3: return WizardStep3DatosCompanero;
    case 4: return WizardStep4Documentos;
    case 5: return WizardStep5Confirmacion;
    default: return WizardStep1Tipo;
  }
});

const progressPercentage = computed(() => {
  return ((store.paso - 1) / (totalSteps - 1)) * 100;
});

const isStepSkipped = (n) => {
  return n === 3 && store.tipo === "INDIVIDUAL";
};

onMounted(() => {
  store.resetStore();
});
</script>

<style scoped>
.pre-registro-page {
  min-height: 100vh;
  background: #0f172a;
  color: white;
  position: relative;
  overflow-x: hidden;
  padding: 2rem 0;
  display: flex;
  align-items: center;
}

.container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 0 1.5rem;
  width: 100%;
  position: relative;
  z-index: 10;
}

.background-elements {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.blob {
  position: absolute;
  filter: blur(80px);
  opacity: 0.2;
  border-radius: 50%;
}

.blob-1 {
  width: 400px;
  height: 400px;
  background: var(--primary-color);
  top: -100px;
  right: -100px;
}

.blob-2 {
  width: 300px;
  height: 300px;
  background: #6366f1;
  bottom: -50px;
  left: -50px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 3rem;
}

.logo {
  font-size: 1.5rem;
  font-weight: 900;
  letter-spacing: 2px;
  color: var(--primary-color);
}

.tournament-badge {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(5px);
  padding: 0.5rem 1.2rem;
  border-radius: 2rem;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.tournament-badge i {
  color: #fbbf24;
}

.wizard-container {
  background: rgba(255, 255, 255, 0.02);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 2rem;
  padding: 3rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

@media (max-width: 768px) {
  .wizard-container {
    padding: 1.5rem;
  }
}

.progress-wrapper {
  margin-bottom: 4rem;
  position: relative;
}

.progress-steps {
  display: flex;
  justify-content: space-between;
  position: relative;
  z-index: 2;
}

.step-indicator {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.8rem;
  flex: 1;
}

.step-indicator.skipped {
  opacity: 0.3;
  pointer-events: none;
}

.step-number {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  background: #1e293b;
  border: 2px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  transition: all 0.3s ease;
  color: var(--text-secondary);
}

.step-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.step-indicator.active .step-number {
  border-color: var(--primary-color);
  background: rgba(var(--primary-rgb), 0.1);
  color: var(--primary-color);
  box-shadow: 0 0 15px rgba(var(--primary-rgb), 0.3);
}

.step-indicator.active .step-label {
  color: var(--primary-color);
}

.step-indicator.completed .step-number {
  background: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
}

.progress-bar {
  position: absolute;
  top: 1.25rem;
  left: 5%;
  right: 5%;
  height: 2px;
  background: rgba(255, 255, 255, 0.1);
  z-index: 1;
}

.progress-fill {
  height: 100%;
  background: var(--primary-color);
  transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 0 10px var(--primary-color);
}

.wizard-content {
  min-height: 400px;
}

.page-footer {
  margin-top: 3rem;
  text-align: center;
  color: var(--text-secondary);
  font-size: 0.85rem;
}
</style>
