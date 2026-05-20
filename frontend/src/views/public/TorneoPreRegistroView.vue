<template>
  <div class="min-h-screen w-full bg-slate-900 text-white relative overflow-x-hidden py-8 flex items-center">
    <!-- Blobs de Fondo -->
    <div class="fixed inset-0 pointer-events-none z-0">
      <div class="absolute w-[400px] h-[400px] bg-primary-600 top-[-100px] right-[-100px] blur-[80px] opacity-20 rounded-full"></div>
      <div class="absolute w-[300px] h-[300px] bg-indigo-500 bottom-[-50px] left-[-50px] blur-[80px] opacity-20 rounded-full"></div>
    </div>

    <div class="max-w-4xl mx-auto px-6 w-full relative z-10">
      <!-- Encabezado (Logo a la derecha) -->
      <header class="flex justify-between items-center mb-10">
        <div class="bg-white/5 backdrop-blur-md px-5 py-2 rounded-full flex items-center gap-3 border border-white/10 shadow-sm">
          <i class="fas fa-trophy text-amber-500"></i>
          <span class="text-xs font-semibold uppercase tracking-wider">
            Pre-registro: {{ tournament ? formatText(tournament.nombre_torneo) : 'Cargando Torneo...' }}
          </span>
        </div>
        <h1 class="text-xl md:text-2xl font-black tracking-widest text-primary-600">SOC_DEP_HUB</h1>
      </header>

      <main class="bg-white/[0.02] backdrop-blur-2xl border border-white/5 rounded-3xl p-6 md:p-12 shadow-2xl shadow-black/50">
        <!-- Progress Bar -->
        <div v-if="!store.exito" class="mb-12 relative">
          <div class="flex justify-between relative z-10">
            <div 
              v-for="n in totalSteps" 
              :key="n" 
              class="flex flex-col items-center gap-2 flex-1"
              :class="{ 'opacity-30 pointer-events-none': isStepSkipped(n) }"
            >
              <div 
                class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-300 border"
                :class="{ 
                  'border-primary-600 bg-primary-600/10 text-primary-600 shadow-[0_0_15px_rgba(37,99,235,0.3)]': store.paso === n, 
                  'bg-primary-600 border-primary-600 text-white': store.paso > n,
                  'bg-slate-800 border-white/10 text-slate-400': store.paso < n
                }"
              >
                <i v-if="store.paso > n" class="fas fa-check"></i>
                <span v-else>{{ n }}</span>
              </div>
              <span 
                class="text-[10px] font-extrabold tracking-wider uppercase text-center"
                :class="store.paso === n ? 'text-primary-500' : 'text-slate-400'"
              >
                {{ stepLabels[n-1] }}
              </span>
            </div>
          </div>
          <div class="absolute top-5 left-[5%] right-[5%] h-[2px] bg-white/10 z-0">
            <div class="h-full bg-primary-600 transition-all duration-500 shadow-[0_0_10px_rgba(37,99,235,0.5)]" :style="{ width: progressPercentage + '%' }"></div>
          </div>
        </div>

        <!-- Wizard Content -->
        <div class="min-h-[400px]">
          <component :is="currentStepComponent" />
        </div>
      </main>

      <footer class="mt-12 text-center text-slate-500 text-xs">
        <p>&copy; 2024 SOC_DEP_HUB. Todos los derechos reservados.</p>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { usePreRegisterStore } from "@/stores/preRegisterStore";
import api from "@/services/api";
import WizardStep1Tipo from "@/components/public/wizard/WizardStep1Tipo.vue";
import WizardStep2DatosCapitan from "@/components/public/wizard/WizardStep2DatosCapitan.vue";
import WizardStep3DatosCompanero from "@/components/public/wizard/WizardStep3DatosCompanero.vue";
import WizardStep4Documentos from "@/components/public/wizard/WizardStep4Documentos.vue";
import WizardStep5Confirmacion from "@/components/public/wizard/WizardStep5Confirmacion.vue";

const route = useRoute();
const store = usePreRegisterStore();
const tournament = ref(null);
const loadingTournament = ref(true);

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

// Cargar información del torneo
const fetchTournamentDetails = async () => {
  try {
    const id = route.params.id;
    if (id) {
      const response = await api.get(`/torneos/${id}`);
      tournament.value = response.data.data;
    }
  } catch (err) {
    console.error("Error fetching tournament details in wizard:", err);
  } finally {
    loadingTournament.value = false;
  }
};

// Formateador de texto
const formatText = (text) => {
  if (!text) return "";
  return text.trim()
             .split(/\s+/)
             .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
             .join(' ');
};

onMounted(() => {
  store.resetStore();
  fetchTournamentDetails();
});
</script>
