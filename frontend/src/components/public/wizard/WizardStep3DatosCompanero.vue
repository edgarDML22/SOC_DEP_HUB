<template>
  <div class="wizard-step">
    <h2 class="step-title">Datos del Compañero</h2>
    <p class="step-description">Ingresa la información de tu pareja de equipo.</p>

    <form @submit.prevent="nextStep" class="wizard-form">
      <div class="form-grid">
        <div class="form-group">
          <label for="nombre">Nombre(s)</label>
          <input 
            type="text" 
            id="nombre" 
            v-model="formData.nombre" 
            placeholder="Ej. María" 
            required
          />
        </div>
        <div class="form-group">
          <label for="apellido">Apellido(s)</label>
          <input 
            type="text" 
            id="apellido" 
            v-model="formData.apellido" 
            placeholder="Ej. García" 
            required
          />
        </div>
        <div class="form-group">
          <label for="email">Correo Electrónico</label>
          <input 
            type="email" 
            id="email" 
            v-model="formData.email" 
            placeholder="maria.garcia@ejemplo.com" 
            required
          />
        </div>
        <div class="form-group">
          <label for="telefono">Teléfono de Contacto</label>
          <input 
            type="tel" 
            id="telefono" 
            v-model="formData.telefono" 
            placeholder="10 dígitos" 
            required
          />
        </div>
        <div class="form-group">
          <label for="fecha_nacimiento">Fecha de Nacimiento</label>
          <input 
            type="date" 
            id="fecha_nacimiento" 
            v-model="formData.fecha_nacimiento" 
            required
          />
        </div>
        <div class="form-group">
          <label for="genero">Género</label>
          <select id="genero" v-model="formData.genero" required>
            <option value="" disabled>Selecciona una opción</option>
            <option value="M">Masculino</option>
            <option value="F">Femenino</option>
            <option value="X">No Binario / Otro</option>
          </select>
        </div>
        <div class="form-group full-width">
          <label for="ranking">Ranking Declarado (0 - 500)</label>
          <div class="ranking-input-wrapper">
            <input 
              type="number" 
              id="ranking" 
              v-model.number="formData.ranking_declarado" 
              min="0" 
              max="500" 
              placeholder="Ej. 210" 
              required
            />
            <span class="input-info">Ingresa el nivel estimado de tu compañero.</span>
          </div>
          <p v-if="rankingError" class="error-msg">{{ rankingError }}</p>
        </div>
      </div>

      <div class="step-actions">
        <button type="button" class="btn-secondary" @click="prevStep">
          <i class="fas fa-arrow-left"></i>
          Atrás
        </button>
        <button type="submit" class="btn-primary" :disabled="!isFormValid">
          Siguiente
          <i class="fas fa-arrow-right"></i>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from "vue";
import { usePreRegisterStore } from "@/stores/preRegisterStore";

const store = usePreRegisterStore();

const formData = reactive({
  nombre: store.datosCompanero.nombre,
  apellido: store.datosCompanero.apellido,
  email: store.datosCompanero.email,
  telefono: store.datosCompanero.telefono,
  ranking_declarado: store.datosCompanero.ranking_declarado,
  fecha_nacimiento: store.datosCompanero.fecha_nacimiento,
  genero: store.datosCompanero.genero,
});

const rankingError = computed(() => {
  if (formData.ranking_declarado !== null) {
    if (formData.ranking_declarado < 0 || formData.ranking_declarado > 500) {
      return "Ingresa un valor entre 0 y 500";
    }
  }
  return "";
});

const isFormValid = computed(() => {
  return (
    formData.nombre &&
    formData.apellido &&
    formData.email &&
    formData.telefono &&
    formData.fecha_nacimiento &&
    formData.genero &&
    formData.ranking_declarado !== null &&
    !rankingError.value
  );
});

const prevStep = () => {
  store.setDatos("companero", formData);
  store.setPaso(2);
};

const nextStep = () => {
  if (isFormValid.value) {
    store.setDatos("companero", formData);
    store.setPaso(4);
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
  margin-bottom: 2rem;
}

.wizard-form {
  max-width: 600px;
  margin: 0 auto;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

@media (max-width: 600px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

input, select {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.8rem;
  padding: 0.8rem 1rem;
  color: var(--text-primary);
  transition: all 0.3s ease;
}

select option {
  background: #1e293b;
  color: white;
}

input:focus, select:focus {
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

.error-msg {
  color: #ff4d4d;
  font-size: 0.85rem;
  margin-top: 0.3rem;
}

.step-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 1rem;
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

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: var(--text-primary);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.05);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
