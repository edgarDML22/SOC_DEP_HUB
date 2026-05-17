<template>
  <div class="wizard-step">
    <h2 class="step-title">Selecciona el tipo de registro</h2>
    <p class="step-description">¿Participarás de forma individual o con un compañero?</p>

    <div class="cards-container">
      <div 
        class="type-card" 
        :class="{ active: store.tipo === 'INDIVIDUAL' }"
        @click="selectTipo('INDIVIDUAL')"
      >
        <div class="card-icon">
          <i class="fas fa-user"></i>
        </div>
        <h3>Individual</h3>
        <p>Registro para un solo jugador.</p>
        <div class="selection-indicator">
          <i class="fas fa-check-circle"></i>
        </div>
      </div>

      <div 
        class="type-card" 
        :class="{ active: store.tipo === 'EQUIPO' }"
        @click="selectTipo('EQUIPO')"
      >
        <div class="card-icon">
          <i class="fas fa-users"></i>
        </div>
        <h3>En Pareja</h3>
        <p>Registro para un equipo de dos jugadores.</p>
        <div class="selection-indicator">
          <i class="fas fa-check-circle"></i>
        </div>
      </div>
    </div>

    <div v-if="store.tipo === 'EQUIPO'" class="team-name-container">
      <div class="form-group">
        <label for="nombre_equipo">Nombre del Equipo</label>
        <input 
          type="text" 
          id="nombre_equipo" 
          v-model="store.nombre_equipo" 
          placeholder="Ej. Los Guerreros del Padel" 
          required
        />
        <span class="input-info">Ingresa el nombre con el que se identificará a tu pareja en el torneo.</span>
      </div>
    </div>

    <div class="step-actions">
      <button 
        class="btn-primary" 
        :disabled="!isTipoValido"
        @click="nextStep"
      >
        Continuar
        <i class="fas fa-arrow-right"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { usePreRegisterStore } from "@/stores/preRegisterStore";

const store = usePreRegisterStore();

const selectTipo = (tipo) => {
  store.setTipo(tipo);
};

const isTipoValido = computed(() => {
  if (!store.tipo) return false;
  if (store.tipo === "EQUIPO") {
    return store.nombre_equipo && store.nombre_equipo.trim().length > 0;
  }
  return true;
});

const nextStep = () => {
  if (isTipoValido.value) {
    store.setPaso(2);
  }
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
  margin-bottom: 2.5rem;
}

.cards-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.type-card {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border: 2px solid rgba(255, 255, 255, 0.1);
  border-radius: 1.5rem;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.type-card:hover {
  transform: translateY(-5px);
  border-color: var(--primary-color);
  background: rgba(var(--primary-rgb), 0.05);
}

.type-card.active {
  border-color: var(--primary-color);
  background: rgba(var(--primary-rgb), 0.1);
  box-shadow: 0 0 20px rgba(var(--primary-rgb), 0.2);
}

.card-icon {
  font-size: 3rem;
  color: var(--primary-color);
  margin-bottom: 1.5rem;
}

.type-card h3 {
  font-size: 1.4rem;
  margin-bottom: 0.5rem;
  color: var(--text-primary);
}

.type-card p {
  color: var(--text-secondary);
  font-size: 0.95rem;
}

.selection-indicator {
  position: absolute;
  top: 1rem;
  right: 1rem;
  font-size: 1.2rem;
  color: var(--primary-color);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.type-card.active .selection-indicator {
  opacity: 1;
}

.step-actions {
  display: flex;
  justify-content: flex-end;
}

.btn-primary {
  padding: 0.8rem 2rem;
  border-radius: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.8rem;
  transition: all 0.3s ease;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.team-name-container {
  max-width: 500px;
  margin: 0 auto 2.5rem auto;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 1.2rem;
  padding: 1.5rem;
  animation: fadeIn 0.4s ease-out;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  text-align: left;
}

.form-group label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.form-group input {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.8rem;
  padding: 0.8rem 1rem;
  color: var(--text-primary);
  transition: all 0.3s ease;
}

.form-group input:focus {
  outline: none;
  border-color: var(--primary-color);
  background: rgba(255, 255, 255, 0.08);
}

.input-info {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 0.3rem;
  display: block;
}
</style>
