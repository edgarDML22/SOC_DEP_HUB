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

// 1. Inscripciones por Categoría (Barras Verticales)
const chartCategorias = computed(() => {
  if (!stats.value?.inscripciones_categoria?.labels?.length) return null;
  return {
    labels: stats.value.inscripciones_categoria.labels,
    datasets: [{
      label: 'Jugadores Inscritos',
      data: stats.value.inscripciones_categoria.data,
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        if (!chartArea) return '#3b82f6';
        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        gradient.addColorStop(0, '#2563eb'); // Blue-600
        gradient.addColorStop(1, '#60a5fa'); // Blue-400
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
        if (!chartArea) return colors[context.dataIndex % colors.length];

        const colorsMap = [
          ['#6366f1', '#4338ca'], // Socio Titular (Indigo)
          ['#a855f7', '#7c3aed'], // Miembro Familiar (Purple)
          ['#ec4899', '#be185d']  // Competidor Externo (Pink)
        ];

        const pair = colorsMap[context.dataIndex % colorsMap.length];
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
    legend: {
      position: 'bottom',
      labels: { boxWidth: 12, padding: 12 }
    }
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
        <div class="flex bg-white shadow-sm border border-surface-200 rounded-xl p-1">
          <button v-for="r in ['hoy', 'semana', 'mes']" :key="r" 
            @click="filtroRango = r"
            :disabled="isLoading"
            class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all capitalize disabled:opacity-50"
            :class="filtroRango === r ? 'bg-surface-900 text-white shadow-md' : 'text-surface-500 hover:bg-surface-50'">
            {{ r }}
          </button>
        </div>
      </div>
    </div>

    <!-- BARRA DE FILTROS -->
    <div class="bg-surface-50 border border-surface-200 rounded-3xl p-5 space-y-4 shadow-inner">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        
        <!-- Rango Fecha Inicio -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Fecha Inicio</label>
          <div class="relative">
            <IconCalendar class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <input type="date" v-model="filterDateStart" @change="filtroRango = ''; loadStats()"
                   class="w-full pl-10 pr-4 py-2 bg-white border border-surface-200 rounded-xl text-xs font-semibold text-surface-700 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer" />
          </div>
        </div>

        <!-- Rango Fecha Fin -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Fecha Fin</label>
          <div class="relative">
            <IconCalendar class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <input type="date" v-model="filterDateEnd" @change="filtroRango = ''; loadStats()"
                   class="w-full pl-10 pr-4 py-2 bg-white border border-surface-200 rounded-xl text-xs font-semibold text-surface-700 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer" />
          </div>
        </div>

        <!-- Torneo específico -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Torneo Específico</label>
          <div class="relative">
            <IconFilter class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <select v-model="selectedTorneo"
                    class="w-full pl-10 pr-8 py-2 bg-white border border-surface-200 rounded-xl text-xs font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
              <option value="">Todos los torneos</option>
              <option v-for="t in (torneos || []).filter(item => item !== null)" :key="t.id_torneo" :value="t.id_torneo">{{ t.nombre_torneo }}</option>
            </select>
          </div>
        </div>

        <!-- Disciplina -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Disciplina</label>
          <div class="relative">
            <IconFilter class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <select v-model="selectedDisciplina"
                    class="w-full pl-10 pr-8 py-2 bg-white border border-surface-200 rounded-xl text-xs font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
              <option value="">Todas las disciplinas</option>
              <option v-for="d in disciplines" :key="d.id_disciplina" :value="d.id_disciplina">{{ d.nombre_disciplina }}</option>
            </select>
          </div>
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
      <div class="lg:col-span-2 bg-white border border-surface-200 rounded-3xl p-6 lg:p-8 flex flex-col h-[400px]">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Inscripciones por Categoría de Convocatoria</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Volumen total de jugadores inscritos desglosados por ramas y categorías de torneo.</p>
        </div>
        <div class="flex-1 min-h-0">
          <BaseChart v-if="chartCategorias" type="bar" :data="chartCategorias" :options="optionsCategorias" />
          <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
            Sin inscripciones de categorías registradas en este periodo
          </div>
        </div>
      </div>

      <!-- Gráfico 2: Origen de Competidores (Doughnut) -->
      <div class="bg-white border border-surface-200 rounded-3xl p-6 lg:p-8 flex flex-col h-[400px]">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Origen de los Competidores</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Proporción de jugadores pertenecientes a Socios Titulares vs. Familiares vs. Externos.</p>
        </div>
        <div class="flex-1 min-h-0 flex items-center justify-center">
          <BaseChart v-if="chartOrigen" type="doughnut" :data="chartOrigen" :options="optionsOrigen" />
          <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
            Sin competidores inscritos en este periodo
          </div>
        </div>
      </div>

      <!-- Gráfico 3: Participación por Disciplina Histórico (Full Width) -->
      <div class="lg:col-span-3 bg-white border border-surface-200 rounded-3xl p-6 lg:p-8 flex flex-col h-[400px]">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Participación por Disciplina (Histórico Anual)</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Tendencia de participación y número de competidores activos a lo largo del último año.</p>
        </div>
        <div class="flex-1 min-h-0">
          <BaseChart v-if="chartHistorico" type="line" :data="chartHistorico" :options="optionsHistorico" />
          <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
            Sin tendencias de torneos disponibles
          </div>
        </div>
      </div>

    </div>

  </div>
</template>