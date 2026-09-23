<template>
  <div class="min-h-screen w-full bg-slate-900 text-white relative overflow-x-hidden py-8 flex items-center">
    <!-- Blobs de Fondo -->
    <div class="fixed inset-0 pointer-events-none z-0">
      <div class="absolute w-[400px] h-[400px] bg-primary-600 top-[-100px] right-[-100px] blur-[80px] opacity-20 rounded-full"></div>
      <div class="absolute w-[300px] h-[300px] bg-indigo-500 bottom-[-50px] left-[-50px] blur-[80px] opacity-20 rounded-full"></div>
    </div>

    <div class="max-w-5xl mx-auto px-6 w-full relative z-10">
      <!-- Encabezado (Logo a la derecha) -->
      <header class="flex justify-between items-center mb-10">
        <div class="bg-white/5 backdrop-blur-md px-5 py-2 rounded-full flex items-center gap-3 border border-white/10 shadow-sm">
          <i class="fas fa-trophy text-amber-500"></i>
          <span class="text-xs font-semibold uppercase tracking-wider">Explorar Torneos</span>
        </div>
        <h1 class="text-xl md:text-2xl font-black tracking-widest text-primary-600">SOC_DEP_HUB</h1>
      </header>

      <!-- Contenedor Principal (Glassmorphism) -->
      <main class="bg-white/[0.02] backdrop-blur-2xl border border-white/5 rounded-3xl p-6 md:p-12 shadow-2xl shadow-black/50">
        <div class="text-center mb-10">
          <h2 class="text-2xl md:text-3xl font-extrabold text-white mb-2 tracking-tight">Torneos Disponibles</h2>
          <p class="text-slate-400 text-sm md:text-base font-medium">Selecciona el torneo al que deseas inscribirte para iniciar tu pre-registro.</p>
        </div>

        <!-- Barra de Búsqueda y Filtro de Disciplina (Filtro de Estado Eliminado) -->
        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-6 mb-10 bg-white/[0.03] p-5 rounded-2xl border border-white/5">
          <!-- Búsqueda -->
          <div class="relative flex items-center w-full">
            <i class="fas fa-search absolute left-4 text-slate-400 text-lg pointer-events-none"></i>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Buscar por nombre de torneo..."
              class="w-full bg-white/5 border border-white/10 rounded-xl py-3 pl-12 pr-4 text-white placeholder-slate-400 focus:outline-none focus:border-primary-600 focus:bg-white/[0.08] transition-all duration-300 text-sm font-medium" 
            />
          </div>

          <!-- Filtro Disciplina -->
          <div class="flex flex-col gap-1">
            <label for="filter-discipline" class="font-bold text-[10px] uppercase tracking-wider text-slate-400">Disciplina</label>
            <select 
              id="filter-discipline" 
              v-model="selectedDiscipline" 
              class="bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white min-w-[200px] cursor-pointer focus:outline-none focus:border-primary-600 transition-all duration-300 text-sm font-medium"
            >
              <option value="" class="bg-slate-800 text-white">Todas las disciplinas</option>
              <option v-for="d in uniqueDisciplines" :key="d" :value="d" class="bg-slate-800 text-white">{{ formatText(d) }}</option>
            </select>
          </div>
        </div>

        <!-- Estado de carga -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-16 px-6 text-center text-slate-400">
          <i class="fas fa-circle-notch fa-spin text-4xl text-primary-600 mb-4"></i>
          <p class="text-sm font-medium">Cargando torneos disponibles...</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex flex-col items-center justify-center py-16 px-6 text-center text-slate-400">
          <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i>
          <p class="text-sm font-medium">{{ error }}</p>
          <button @click="fetchTournaments" class="mt-4 bg-primary-600 hover:bg-primary-700 text-white rounded-xl py-2 px-6 font-semibold transition-all duration-300 shadow-md shadow-primary-600/25">
            Reintentar <i class="fas fa-redo ml-1"></i>
          </button>
        </div>

        <!-- No hay resultados -->
        <div v-else-if="filteredTournaments.length === 0" class="flex flex-col items-center justify-center py-16 px-6 text-center text-slate-400">
          <i class="fas fa-folder-open text-4xl text-slate-500 mb-4"></i>
          <p class="text-sm font-medium">No se encontraron torneos con inscripciones disponibles en este momento.</p>
        </div>

        <!-- Listado en Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="torneo in filteredTournaments" 
            :key="torneo.id_torneo" 
            class="group relative bg-white/[0.02] border border-white/5 rounded-3xl overflow-hidden transition-all duration-300 hover:-translate-y-2 hover:border-primary-500/30 hover:shadow-2xl hover:shadow-black/50 flex flex-col justify-between"
          >
            <!-- Efecto Glow -->
            <div class="absolute inset-0 bg-gradient-to-b from-primary-600/10 to-transparent pointer-events-none z-0 transition-opacity duration-300 group-hover:opacity-100 opacity-60 animate-fade-in"></div>
            
            <div class="p-6 relative z-10 flex flex-col justify-between h-full min-h-[280px]">
              <div>
                <div class="flex justify-between items-center mb-4">
                  <span class="bg-primary-600/10 text-primary-600 border border-primary-600/20 px-3 py-1 rounded-full text-[10px] font-bold flex items-center gap-1.5 shadow-sm">
                    <i :class="getDisciplineIcon(torneo.disciplina)"></i>
                    {{ formatText(torneo.disciplina) || 'General' }}
                  </span>
                  
                  <span 
                    class="px-3 py-1 rounded-full text-[9px] font-extrabold uppercase tracking-wide border bg-green-500/10 text-green-400 border-green-500/20"
                  >
                    {{ getStatusLabel(torneo.estado) }}
                  </span>
                </div>

                <h3 class="text-lg font-bold text-white mb-4 leading-snug group-hover:text-primary-400 transition-colors duration-300">
                  {{ torneo.nombre_torneo }}
                </h3>
                
                <div class="flex flex-col gap-2 mb-6">
                  <p class="text-sm text-slate-400 flex items-center gap-2">
                    <i class="fas fa-layer-group text-primary-600 w-4 text-center shrink-0"></i> 
                    <strong>Categoría:</strong> <span class="text-slate-300">{{ formatText(torneo.categoria) || 'Sin categoría' }}</span>
                  </p>
                  <p class="text-sm text-slate-400 flex items-center gap-2">
                    <i class="fas fa-users text-primary-600 w-4 text-center shrink-0"></i> 
                    <strong>Modalidad:</strong> <span class="text-slate-300">{{ formatModality(torneo.modalidad) }}</span>
                  </p>
                  <p class="text-sm text-slate-400 flex items-center gap-2">
                    <i class="fas fa-calendar-alt text-primary-600 w-4 text-center shrink-0"></i> 
                    <strong>Inicio:</strong> <span class="text-slate-300">{{ formatDate(torneo.fecha_inicio) }}</span>
                  </p>
                </div>
              </div>

              <div class="mt-auto">
                <router-link 
                  :to="{ name: 'torneo-pre-registro', params: { id: torneo.id_torneo } }" 
                  class="bg-primary-600 text-white rounded-xl py-3 px-6 font-bold shadow-lg shadow-primary-600/25 transition-all duration-300 hover:scale-[1.02] hover:bg-primary-700 hover:shadow-primary-700/40 flex items-center justify-center gap-2 w-full cursor-pointer"
                >
                  Pre-registrarme
                  <i class="fas fa-arrow-right"></i>
                </router-link>
              </div>
            </div>
          </div>
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
import api from "@/services/api";

const torneos = ref([]);
const loading = ref(true);
const error = ref(null);

const searchQuery = ref("");
const selectedDiscipline = ref("");

// Cargar Torneos (Soporta respuestas de listas directas y objetos de paginación de Laravel)
const fetchTournaments = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get("/torneos");
    const resData = response.data.data;
    if (Array.isArray(resData)) {
      torneos.value = resData;
    } else if (resData && Array.isArray(resData.data)) {
      torneos.value = resData.data;
    } else {
      torneos.value = [];
    }
  } catch (err) {
    console.error("Error fetching tournaments:", err);
    error.value = "No se pudieron cargar los torneos. Por favor intenta más tarde.";
  } finally {
    loading.value = false;
  }
};

// Disciplinas Únicas para el filtro
const uniqueDisciplines = computed(() => {
  const set = new Set();
  torneos.value.forEach(t => {
    // Solo consideramos disciplinas de torneos en inscripción activa
    const isEnInscripcion = t.estado === 'EN_INSCRIPCION' || t.estatus_torneo === 'EN_INSCRIPCION';
    if (isEnInscripcion && t.disciplina) {
      set.add(t.disciplina);
    }
  });
  return Array.from(set);
});

// Filtrado de Torneos - Muestra ÚNICAMENTE los torneos en periodo de inscripción activa
const filteredTournaments = computed(() => {
  return torneos.value.filter(t => {
    // Filtro estricto: Solo torneos con estatus "EN_INSCRIPCION"
    const isEnInscripcion = t.estado === 'EN_INSCRIPCION' || t.estatus_torneo === 'EN_INSCRIPCION';
    if (!isEnInscripcion) return false;

    const matchesSearch = t.nombre_torneo?.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesDiscipline = !selectedDiscipline.value || t.disciplina === selectedDiscipline.value;
    return matchesSearch && matchesDiscipline;
  });
});

// Formatters de texto
const formatText = (text) => {
  if (!text) return "";
  return text.trim()
             .split(/\s+/)
             .map(word => {
               // Capitalizar palabras individuales
               return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
             })
             .join(' ');
};

const formatModality = (mod) => {
  if (!mod) return "Por definir";
  const m = mod.toUpperCase().trim();
  if (m === "INDIVIDUAL") return "Individual";
  if (m === "EQUIPO" || m === "PAREJAS" || m === "PAREJA" || m === "DOBLES") return "En Parejas";
  return formatText(mod);
};

const getStatusLabel = (status) => {
  return "Inscripciones Abiertas";
};

const getDisciplineIcon = (disc) => {
  if (!disc) return "fa-tennis-ball";
  const name = disc.toLowerCase();
  if (name.includes("padel") || name.includes("pádel")) return "fa-table-tennis-paddle-ball";
  if (name.includes("squash")) return "fa-racquet";
  if (name.includes("tenis") || name.includes("tennis")) return "fa-baseball";
  return "fa-trophy";
};

const formatDate = (dateStr) => {
  if (!dateStr) return "Por definir";
  try {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateStr).toLocaleDateString('es-MX', options);
  } catch (e) {
    return dateStr;
  }
};

onMounted(() => {
  fetchTournaments();
});
</script>
