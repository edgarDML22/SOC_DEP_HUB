<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import api from '@/services/api';
import BaseChart from '@/components/admin/BaseChart.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import { IconFilter, IconCalendar } from '@/components/icons';
import { useReportsBIStore } from '@/stores/admin/reportsBIStore';

const reportsStore = useReportsBIStore();

const isLoading = ref(!reportsStore.tournamentsStats);
const errorMsg = ref('');
const stats = computed(() => reportsStore.tournamentsStats);

const torneos = ref([]);
const disciplines = ref([]);

// Rango dinámico y filtros
const filtroRango = ref(reportsStore.tournamentsFilters.rango || 'mes');
const filterDateStart = ref(reportsStore.tournamentsFilters.fecha_inicio);
const filterDateEnd = ref(reportsStore.tournamentsFilters.fecha_fin);
const selectedTorneo = ref(reportsStore.tournamentsFilters.id_torneo);
const selectedDisciplina = ref(reportsStore.tournamentsFilters.id_disciplina);

const updateDatesFromRango = (rango) => {
  const now = new Date();
  
  if (rango === 'hoy') {
    const todayStr = now.toISOString().split('T')[0];
    filterDateStart.value = todayStr;
    filterDateEnd.value = todayStr;
  } else if (rango === 'semana') {
    // Calcular lunes de esta semana
    const currentDay = now.getDay();
    const distanceToMonday = currentDay === 0 ? -6 : 1 - currentDay;
    const monday = new Date(now);
    monday.setDate(now.getDate() + distanceToMonday);
    
    // Calcular domingo de esta semana
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);
    
    filterDateStart.value = monday.toISOString().split('T')[0];
    filterDateEnd.value = sunday.toISOString().split('T')[0];
  } else if (rango === 'mes') {
    // Para tendencias, mostramos desde 6 meses atrás para que tenga sentido el gráfico mensual
    const sixMonthsAgo = new Date(now.getFullYear(), now.getMonth() - 5, 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    filterDateStart.value = sixMonthsAgo.toISOString().split('T')[0];
    filterDateEnd.value = lastDay.toISOString().split('T')[0];
  }
};

const loadMetadata = async () => {
  try {
    const [resTorneos, resDisc] = await Promise.all([
      api.get('/torneos'),
      api.get('/disciplinas/all')
    ]);
    
    const torneosData = Array.isArray(resTorneos.data?.data)
      ? resTorneos.data.data
      : (Array.isArray(resTorneos.data?.data?.data)
          ? resTorneos.data.data.data
          : (Array.isArray(resTorneos.data) ? resTorneos.data : []));
          
    const discData = Array.isArray(resDisc.data?.data)
      ? resDisc.data.data
      : (Array.isArray(resDisc.data) ? resDisc.data : []);

    torneos.value = torneosData.filter(t => t !== null);
    disciplines.value = discData.filter(d => d !== null);
  } catch (error) {
    console.error('Error loading metadata for tournaments:', error);
  }
};

const loadStats = async (forceRefresh = false) => {
  const isSameFilters =
    reportsStore.tournamentsStats &&
    reportsStore.tournamentsFilters.rango === (filtroRango.value || undefined) &&
    reportsStore.tournamentsFilters.fecha_inicio === filterDateStart.value &&
    reportsStore.tournamentsFilters.fecha_fin === filterDateEnd.value &&
    reportsStore.tournamentsFilters.id_torneo === selectedTorneo.value &&
    reportsStore.tournamentsFilters.id_disciplina === selectedDisciplina.value;

  if (isSameFilters && !forceRefresh) {
    return;
  }

  try {
    isLoading.value = true;
    errorMsg.value = '';
    await reportsStore.fetchTournamentsStats({
      fecha_inicio: filterDateStart.value,
      fecha_fin: filterDateEnd.value,
      id_torneo: selectedTorneo.value,
      id_disciplina: selectedDisciplina.value,
      rango: filtroRango.value || undefined
    }, forceRefresh);
  } catch (error) {
    console.error('Error loading tournament stats:', error);
    errorMsg.value = 'Fallo de comunicación con la central analítica de torneos.';
  } finally {
    isLoading.value = false;
  }
};

const clearFilters = () => {
  filtroRango.value = 'mes';
  selectedTorneo.value = '';
  selectedDisciplina.value = '';
  updateDatesFromRango('mes');
  loadStats(true);
};

watch(filtroRango, (newVal) => {
  if (newVal) {
    updateDatesFromRango(newVal);
    loadStats();
  }
});

onMounted(async () => {
  await loadMetadata();
  if (reportsStore.tournamentsStats) {
    filtroRango.value = reportsStore.tournamentsFilters.rango;
    filterDateStart.value = reportsStore.tournamentsFilters.fecha_inicio;
    filterDateEnd.value = reportsStore.tournamentsFilters.fecha_fin;
    selectedTorneo.value = reportsStore.tournamentsFilters.id_torneo;
    selectedDisciplina.value = reportsStore.tournamentsFilters.id_disciplina;
    await loadStats();
  } else {
    updateDatesFromRango(filtroRango.value);
    await loadStats();
  }
});

watch([selectedTorneo, selectedDisciplina], () => loadStats());

// --- Configuración de Gráficos ---

// 1. Inscripciones por Categoría (Barras Verticales con Distinción de Color)
const chartCategorias = computed(() => {
  if (!stats.value?.inscripciones_categoria?.labels?.length) return null;
  return {
    labels: stats.value.inscripciones_categoria.labels,
    datasets: [{
      label: 'Jugadores Inscritos',
      data: stats.value.inscripciones_categoria.data,
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        const colorsMap = [
          ['#6366f1', '#4338ca'], // Indigo
          ['#10b981', '#047857'], // Emerald
          ['#f59e0b', '#b45309'], // Amber
          ['#ec4899', '#be185d'], // Pink
          ['#06b6d4', '#0e7490'], // Cyan
          ['#8b5cf6', '#6d28d9'], // Violet
          ['#f43f5e', '#be123c'], // Rose
          ['#64748b', '#475569']  // Slate
        ];
        const index = typeof context.dataIndex === 'number' ? context.dataIndex : 0;
        const pair = colorsMap[index % colorsMap.length];
        if (!chartArea) return pair[0];
        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        gradient.addColorStop(0, pair[1]);
        gradient.addColorStop(1, pair[0]);
        return gradient;
      },
      borderRadius: 6
    }]
  };
});

const optionsCategorias = {
  plugins: { legend: { display: false } },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { stepSize: 1 }
    }
  }
};

// 2. Origen de Competidores (Doughnut)
const chartOrigen = computed(() => {
  if (!stats.value?.origen_competidores?.labels?.length) return null;
  return {
    labels: stats.value.origen_competidores.labels,
    datasets: [{
      data: stats.value.origen_competidores.data,
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        const colors = ['#6366f1', '#a855f7', '#ec4899'];
        const index = typeof context.dataIndex === 'number' ? context.dataIndex : 0;
        if (!chartArea) return colors[index % colors.length];

        const colorsMap = [
          ['#6366f1', '#4338ca'], // Socio Titular (Indigo)
          ['#a855f7', '#7c3aed'], // Miembro Familiar (Purple)
          ['#ec4899', '#be185d']  // Competidor Externo (Pink)
        ];

        const pair = colorsMap[index % colorsMap.length];
        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        gradient.addColorStop(0, pair[1]);
        gradient.addColorStop(1, pair[0]);
        return gradient;
      },
      borderWidth: 2,
      borderColor: '#ffffff',
      hoverOffset: 12
    }]
  };
});

const optionsOrigen = {
  plugins: {
    legend: { display: false }
  },
  scales: {
    x: { display: false },
    y: { display: false }
  }
};

// 3. Participación por Disciplina (Histórico) - Líneas de tendencia
const chartHistorico = computed(() => {
  if (!stats.value?.historico_disciplina || Object.keys(stats.value.historico_disciplina).length === 0) return null;
  
  const colors = [
    '#6366f1', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#8b5cf6'
  ];

  let colorIdx = 0;
  const datasets = [];
  let allLabels = [];

  for (const [discName, dataObj] of Object.entries(stats.value.historico_disciplina)) {
    if (dataObj.labels && dataObj.labels.length > 0) {
      allLabels = dataObj.labels; // Asumiendo etiquetas unificadas (meses)
      datasets.push({
        label: discName,
        data: dataObj.data,
        borderColor: colors[colorIdx % colors.length],
        backgroundColor: 'transparent',
        borderWidth: 3,
        tension: 0.35,
        pointRadius: 4,
        pointBackgroundColor: '#ffffff',
        pointBorderWidth: 2,
        pointBorderColor: colors[colorIdx % colors.length]
      });
      colorIdx++;
    }
  }

  return {
    labels: allLabels,
    datasets
  };
});

const optionsHistorico = {
  responsive: true,
  scales: {
    y: {
      beginAtZero: true,
      ticks: { stepSize: 1 }
    }
  }
};

// Computes de Totales y Desgloses para copiar el estilo premium de Ludoteca
const totalInscritosCategorias = computed(() => {
  return stats.value?.inscripciones_categoria?.data?.reduce((sum, val) => sum + Number(val), 0) || 0;
});

const individualInscritosCategorias = computed(() => {
  const labels = stats.value?.inscripciones_categoria?.labels || [];
  const data = stats.value?.inscripciones_categoria?.data || [];
  const colors = ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#8b5cf6', '#f43f5e', '#64748b'];
  return labels.map((label, idx) => ({
    label,
    value: data[idx] || 0,
    color: colors[idx % colors.length]
  })).filter(item => item.value > 0);
});

const totalCompetidores = computed(() => {
  return stats.value?.origen_competidores?.data?.reduce((sum, val) => sum + Number(val), 0) || 0;
});

const individualOrigen = computed(() => {
  const labels = stats.value?.origen_competidores?.labels || [];
  const data = stats.value?.origen_competidores?.data || [];
  const colors = ['#6366f1', '#a855f7', '#ec4899'];
  return labels.map((label, idx) => ({
    label,
    value: data[idx] || 0,
    color: colors[idx % colors.length]
  })).filter(item => item.value > 0);
});

const totalHistorico = computed(() => {
  let sum = 0;
  if (stats.value?.historico_disciplina) {
    for (const val of Object.values(stats.value.historico_disciplina)) {
      sum += (val.data || []).reduce((a, b) => a + Number(b), 0);
    }
  }
  return sum;
});

const individualHistorico = computed(() => {
  if (!stats.value?.historico_disciplina) return [];
  const colors = ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#8b5cf6'];
  return Object.entries(stats.value.historico_disciplina).map(([name, dataObj], idx) => ({
    label: name,
    value: (dataObj.data || []).reduce((a, b) => a + Number(b), 0),
    color: colors[idx % colors.length]
  })).filter(item => item.value > 0);
});
</script>

<template>
  <div class="space-y-8 font-sans">
    
    <!-- ENCABEZADO Y FILTROS DE RANGO -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-xl font-black text-surface-900 m-0 tracking-tight">Periodo de Torneos</h2>
        <p class="text-xs text-surface-500 m-0 mt-0.5 font-medium">Ajusta el rango de tiempo de consulta para recalcular inscripciones, origen y participación en torneos.</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="flex p-1 bg-slate-100 rounded-2xl shadow-inner border border-surface-200">
          <button v-for="r in ['hoy', 'semana', 'mes']" :key="r" 
            @click="filtroRango = r"
            :disabled="isLoading"
            class="py-1.5 px-4 rounded-xl text-xs font-black transition-all duration-200 ease-out capitalize disabled:opacity-50 cursor-pointer border-none"
            :class="filtroRango === r ? 'bg-surface-900 text-white shadow-md transform scale-[1.01]' : 'text-surface-500 hover:bg-white hover:text-surface-700'">
            {{ r }}
          </button>
        </div>
      </div>
    </div>

    <!-- BARRA DE FILTROS -->
    <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        
        <!-- Rango Fecha Inicio -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Fecha Inicio</label>
          <div class="relative">
            <IconCalendar class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <input type="date" v-model="filterDateStart" @change="filtroRango = ''; loadStats()"
                   class="w-full pl-10 pr-4 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-xs font-bold text-surface-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 focus:bg-white transition-all cursor-pointer" />
          </div>
        </div>

        <!-- Rango Fecha Fin -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Fecha Fin</label>
          <div class="relative">
            <IconCalendar class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <input type="date" v-model="filterDateEnd" @change="filtroRango = ''; loadStats()"
                   class="w-full pl-10 pr-4 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-xs font-bold text-surface-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 focus:bg-white transition-all cursor-pointer" />
          </div>
        </div>

        <!-- Disciplina -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Disciplina</label>
          <div class="relative">
            <IconFilter class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <select v-model="selectedDisciplina"
                    class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-xs font-bold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 focus:bg-white transition-all cursor-pointer">
              <option value="">Todas las disciplinas</option>
              <option v-for="d in disciplines" :key="d.id_disciplina" :value="d.id_disciplina">{{ d.nombre_disciplina }}</option>
            </select>
          </div>
        </div>

        <!-- Botón Limpiar Filtro -->
        <div class="flex flex-col gap-1.5">
          <button @click="clearFilters"
                  class="w-full py-2.5 bg-surface-50 hover:bg-white text-surface-700 border border-surface-200 rounded-xl text-xs font-black transition-all cursor-pointer flex items-center justify-center gap-2 h-[42px] shadow-xs active:scale-[0.98]">
            <svg class="w-4 h-4 text-surface-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Limpiar Filtros
          </button>
        </div>

      </div>
    </div>

    <!-- CARGANDO / ERROR -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white">
      <LoadingSpinner />
      <span class="text-sm font-bold text-surface-500 mt-4">Analizando analíticas de torneos…</span>
    </div>

    <div v-else-if="errorMsg" class="p-8 text-center bg-red-50 border border-red-200 rounded-3xl text-red-700 font-semibold">
      {{ errorMsg }}
    </div>

    <!-- REPORTES GRÁFICOS -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- Gráfico 1: Inscripciones por Categoría (Barras) -->
      <div class="lg:col-span-2 bg-white border border-surface-200 rounded-[2.2rem] shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 lg:p-8 flex flex-col hover:shadow-lg transition-all duration-300">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Inscripciones por Categoría de Convocatoria</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Volumen total de jugadores inscritos desglosados por ramas y categorías de torneo.</p>
        </div>
        <div class="h-[280px] w-full" v-if="chartCategorias">
          <BaseChart type="bar" :data="chartCategorias" :options="optionsCategorias" />
        </div>
        <div v-else class="h-[280px] flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
          Sin inscripciones de categorías registradas en este periodo
        </div>

        <!-- Desglose de Totales y Métricas Individuales -->
        <div class="mt-6 border-t border-surface-100 pt-4" v-if="chartCategorias && individualInscritosCategorias.length">
          <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Desglose por Categoría</span>
            <span class="text-xs font-bold text-surface-500 bg-surface-50 px-3 py-1 rounded-full border border-surface-100 shadow-sm">
              Total Inscritos: <span class="text-surface-900 font-black">{{ totalInscritosCategorias }}</span>
            </span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-[160px] overflow-y-auto scrollbar-thin pr-1">
            <div v-for="(val, idx) in individualInscritosCategorias" :key="idx" 
              class="flex items-center justify-between p-2.5 bg-surface-50/50 hover:bg-surface-50 hover:border-surface-200 rounded-2xl border border-surface-100 transition-all">
              <div class="flex items-center gap-2 truncate min-w-0">
                <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: val.color || '#3b82f6' }"></span>
                <span class="text-xs font-bold text-surface-600 truncate">{{ val.label }}</span>
              </div>
              <span class="text-xs font-black text-surface-900 ml-2 bg-white px-2 py-0.5 rounded-lg border border-surface-100 shadow-2xs">{{ val.value }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfico 2: Origen de Competidores (Doughnut) -->
      <div class="bg-white border border-surface-200 rounded-[2.2rem] shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 lg:p-8 flex flex-col hover:shadow-lg transition-all duration-300">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Origen de los Competidores</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Proporción de jugadores pertenecientes a Socios Titulares vs. Familiares vs. Externos.</p>
        </div>
        <div class="h-[280px] w-full flex items-center justify-center" v-if="chartOrigen">
          <BaseChart type="doughnut" :data="chartOrigen" :options="optionsOrigen" />
        </div>
        <div v-else class="h-[280px] flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
          Sin competidores inscritos en este periodo
        </div>

        <!-- Desglose de Totales y Métricas Individuales -->
        <div class="mt-6 border-t border-surface-100 pt-4" v-if="chartOrigen && individualOrigen.length">
          <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Desglose por Origen</span>
            <span class="text-xs font-bold text-surface-500 bg-surface-50 px-3 py-1 rounded-full border border-surface-100 shadow-sm">
              Total Competidores: <span class="text-surface-900 font-black">{{ totalCompetidores }}</span>
            </span>
          </div>
          <div class="grid grid-cols-1 gap-2.5 max-h-[160px] overflow-y-auto scrollbar-thin pr-1">
            <div v-for="(val, idx) in individualOrigen" :key="idx" 
              class="flex items-center justify-between p-2.5 bg-surface-50/50 hover:bg-surface-50 hover:border-surface-200 rounded-2xl border border-surface-100 transition-all">
              <div class="flex items-center gap-2 truncate min-w-0">
                <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: val.color || '#94a3b8' }"></span>
                <span class="text-xs font-bold text-surface-600 truncate">{{ val.label }}</span>
              </div>
              <span class="text-xs font-black text-surface-900 ml-2 bg-white px-2 py-0.5 rounded-lg border border-surface-100 shadow-2xs">{{ val.value }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfico 3: Participación por Disciplina Histórico (Full Width) -->
      <div class="lg:col-span-3 bg-white border border-surface-200 rounded-[2.2rem] shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 lg:p-8 flex flex-col hover:shadow-lg transition-all duration-300">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Participación por Disciplina (Histórico Anual)</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Tendencia de participación y número de competidores activos a lo largo del último año.</p>
        </div>
        <div class="h-[280px] w-full" v-if="chartHistorico">
          <BaseChart type="line" :data="chartHistorico" :options="optionsHistorico" />
        </div>
        <div v-else class="h-[280px] flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
          Sin tendencias de torneos disponibles
        </div>

        <!-- Desglose de Totales y Métricas Individuales -->
        <div class="mt-6 border-t border-surface-100 pt-4" v-if="chartHistorico && individualHistorico.length">
          <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Desglose por Disciplina</span>
            <span class="text-xs font-bold text-surface-500 bg-surface-50 px-3 py-1 rounded-full border border-surface-100 shadow-sm">
              Participaciones Totales: <span class="text-surface-900 font-black">{{ totalHistorico }}</span>
            </span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-[160px] overflow-y-auto scrollbar-thin pr-1">
            <div v-for="(val, idx) in individualHistorico" :key="idx" 
              class="flex items-center justify-between p-2.5 bg-surface-50/50 hover:bg-surface-50 hover:border-surface-200 rounded-2xl border border-surface-100 transition-all">
              <div class="flex items-center gap-2 truncate min-w-0">
                <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: val.color || '#94a3b8' }"></span>
                <span class="text-xs font-bold text-surface-600 truncate">{{ val.label }}</span>
              </div>
              <span class="text-xs font-black text-surface-900 ml-2 bg-white px-2 py-0.5 rounded-lg border border-surface-100 shadow-2xs">{{ val.value }}</span>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>