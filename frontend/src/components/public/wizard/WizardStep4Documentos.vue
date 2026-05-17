<template>
  <div class="wizard-step">
    <h2 class="step-title">Documentación</h2>
    <p class="step-description">Sube tus documentos en formato PDF para validar tu registro.</p>

    <div class="documents-container">
      <!-- Documentos Capitán -->
      <div class="doc-section">
        <h3>{{ store.tipo === 'EQUIPO' ? 'Documentos del Capitán' : 'Tus Documentos' }}</h3>
        <div class="doc-grid">
          <div class="file-input-group">
            <label>INE (PDF)</label>
            <div class="file-drop-zone" :class="{ has_file: store.archivos.ine }">
              <input type="file" @change="handleFile($event, 'ine')" accept=".pdf" />
              <div class="drop-zone-content">
                <i class="fas" :class="store.archivos.ine ? 'fa-file-pdf' : 'fa-cloud-upload-alt'"></i>
                <span>{{ store.archivos.ine ? store.archivos.ine.name : 'Seleccionar archivo' }}</span>
              </div>
            </div>
          </div>
          <div class="file-input-group">
            <label>CURP (PDF)</label>
            <div class="file-drop-zone" :class="{ has_file: store.archivos.curp }">
              <input type="file" @change="handleFile($event, 'curp')" accept=".pdf" />
              <div class="drop-zone-content">
                <i class="fas" :class="store.archivos.curp ? 'fa-file-pdf' : 'fa-cloud-upload-alt'"></i>
                <span>{{ store.archivos.curp ? store.archivos.curp.name : 'Seleccionar archivo' }}</span>
              </div>
            </div>
          </div>
          <div class="file-input-group">
            <label>Carta Responsiva (PDF)</label>
            <div class="file-drop-zone" :class="{ has_file: store.archivos.carta }">
              <input type="file" @change="handleFile($event, 'carta')" accept=".pdf" />
              <div class="drop-zone-content">
                <i class="fas" :class="store.archivos.carta ? 'fa-file-pdf' : 'fa-cloud-upload-alt'"></i>
                <span>{{ store.archivos.carta ? store.archivos.carta.name : 'Seleccionar archivo' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Documentos Compañero (si aplica) -->
      <div v-if="store.tipo === 'EQUIPO'" class="doc-section mt-4">
        <h3>Documentos del Compañero</h3>
        <div class="doc-grid">
          <div class="file-input-group">
            <label>INE (PDF)</label>
            <div class="file-drop-zone" :class="{ has_file: store.archivos.companero_ine }">
              <input type="file" @change="handleFile($event, 'companero_ine')" accept=".pdf" />
              <div class="drop-zone-content">
                <i class="fas" :class="store.archivos.companero_ine ? 'fa-file-pdf' : 'fa-cloud-upload-alt'"></i>
                <span>{{ store.archivos.companero_ine ? store.archivos.companero_ine.name : 'Seleccionar archivo' }}</span>
              </div>
            </div>
          </div>
          <div class="file-input-group">
            <label>CURP (PDF)</label>
            <div class="file-drop-zone" :class="{ has_file: store.archivos.companero_curp }">
              <input type="file" @change="handleFile($event, 'companero_curp')" accept=".pdf" />
              <div class="drop-zone-content">
                <i class="fas" :class="store.archivos.companero_curp ? 'fa-file-pdf' : 'fa-cloud-upload-alt'"></i>
                <span>{{ store.archivos.companero_curp ? store.archivos.companero_curp.name : 'Seleccionar archivo' }}</span>
              </div>
            </div>
          </div>
          <div class="file-input-group">
            <label>Carta Responsiva (PDF)</label>
            <div class="file-drop-zone" :class="{ has_file: store.archivos.companero_carta }">
              <input type="file" @change="handleFile($event, 'companero_carta')" accept=".pdf" />
              <div class="drop-zone-content">
                <i class="fas" :class="store.archivos.companero_carta ? 'fa-file-pdf' : 'fa-cloud-upload-alt'"></i>
                <span>{{ store.archivos.companero_carta ? store.archivos.companero_carta.name : 'Seleccionar archivo' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="step-actions">
      <button type="button" class="btn-secondary" @click="prevStep">
        <i class="fas fa-arrow-left"></i>
        Atrás
      </button>
      <button type="button" class="btn-primary" :disabled="!allFilesSelected" @click="nextStep">
        Siguiente
        <i class="fas fa-arrow-right"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { usePreRegisterStore } from "@/stores/preRegisterStore";

const store = usePreRegisterStore();

const handleFile = (event, key) => {
  const file = event.target.files[0];
  if (file && file.type === "application/pdf") {
    store.setArchivo(key, file);
  } else {
    alert("Por favor selecciona un archivo PDF válido.");
    event.target.value = null;
  }
};

const allFilesSelected = computed(() => {
  const capitanFiles = store.archivos.ine && store.archivos.curp && store.archivos.carta;
  if (store.tipo === "INDIVIDUAL") return capitanFiles;
  
  const companeroFiles = store.archivos.companero_ine && store.archivos.companero_curp && store.archivos.companero_carta;
  return capitanFiles && companeroFiles;
});

const prevStep = () => {
  store.setPaso(store.tipo === "EQUIPO" ? 3 : 2);
};

const nextStep = () => {
  if (allFilesSelected.value) {
    store.setPaso(5);
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

.doc-section {
  background: rgba(255, 255, 255, 0.03);
  border-radius: 1.2rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.doc-section h3 {
  font-size: 1.1rem;
  margin-bottom: 1.2rem;
  color: var(--primary-color);
  font-weight: 600;
}

.doc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}

.file-input-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.file-input-group label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-secondary);
}

.file-drop-zone {
  position: relative;
  height: 100px;
  border: 2px dashed rgba(255, 255, 255, 0.1);
  border-radius: 0.8rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  cursor: pointer;
  background: rgba(255, 255, 255, 0.02);
}

.file-drop-zone:hover {
  border-color: var(--primary-color);
  background: rgba(var(--primary-rgb), 0.05);
}

.file-drop-zone.has_file {
  border-color: #4caf50;
  background: rgba(76, 175, 80, 0.05);
}

.file-drop-zone input[type="file"] {
  position: absolute;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.drop-zone-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  text-align: center;
  padding: 0.5rem;
}

.drop-zone-content i {
  font-size: 1.5rem;
  color: var(--text-secondary);
}

.has_file .drop-zone-content i {
  color: #4caf50;
}

.drop-zone-content span {
  font-size: 0.75rem;
  color: var(--text-secondary);
  word-break: break-all;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.mt-4 {
  margin-top: 1.5rem;
}

.step-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 2rem;
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

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
