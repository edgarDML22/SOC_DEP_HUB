<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import api from '@/services/api';
import BaseChart from '@/components/admin/BaseChart.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import { IconFilter, IconCalendar } from '@/components/icons';
import { useReportsBIStore } from '@/stores/admin/reportsBIStore';

const reportsStore = useReportsBIStore();

const isLoading = ref(!reportsStore.academicStats);
const errorMsg = ref('');
const stats = computed(() => reportsStore.academicStats);

const disciplines = ref([]);
const instructors = ref([]);

// Rango dinámico y filtros
const filtroRango = ref(reportsStore.academicFilters.rango || 'semana');
const filterDateStart = ref(reportsStore.academicFilters.fecha_inicio);
const filterDateEnd = ref(reportsStore.academicFilters.fecha_fin);
const selectedDisciplina = ref(reportsStore.academicFilters.id_disciplina);
const selectedInstructor = ref(reportsStore.academicFilters.id_instructor);

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
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    filterDateStart.value = firstDay.toISOString().split('T')[0];
    filterDateEnd.value = lastDay.toISOString().split('T')[0];
  }
};

const loadMetadata = async () => {
  try {
    const [resDisc, resInst] = await Promise.all([
      api.get('/disciplinas/all'),
      api.get('/instructors/all')
    ]);
    disciplines.value = resDisc.data?.data || [];
    instructors.value = resInst.data?.data || [];
  } catch (error) {
    console.error('Error loading metadata:', error);
  }
};

const loadStats = async (forceRefresh = false) => {
  const isSameFilters =
    reportsStore.academicStats &&
    reportsStore.academicFilters.rango === (filtroRango.value || undefined) &&
    reportsStore.academicFilters.fecha_inicio === filterDateStart.value &&
    reportsStore.academicFilters.fecha_fin === filterDateEnd.value &&
    reportsStore.academicFilters.id_disciplina === selectedDisciplina.value &&
    reportsStore.academicFilters.id_instructor === selectedInstructor.value;

  if (isSameFilters && !forceRefresh) {
    return;
  }

  try {
    isLoading.value = true;
    errorMsg.value = '';
    await reportsStore.fetchAcademicStats({
      fecha_inicio: filterDateStart.value,
      fecha_fin: filterDateEnd.value,
      id_disciplina: selectedDisciplina.value,
      id_instructor: selectedInstructor.value,
      rango: filtroRango.value || undefined
    }, forceRefresh);
  } catch (error) {
    console.error('Error loading academic stats:', error);
    errorMsg.value = 'Conexión interrumpida con el servidor de BI.';
  } finally {
    isLoading.value = false;
  }
};

const clearFilters = () => {
  filtroRango.value = 'semana';
  selectedDisciplina.value = '';
  selectedInstructor.value = '';
  updateDatesFromRango('semana');
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
  if (reportsStore.academicStats) {
    filtroRango.value = reportsStore.academicFilters.rango;
    filterDateStart.value = reportsStore.academicFilters.fecha_inicio;
    filterDateEnd.value = reportsStore.academicFilters.fecha_fin;
    selectedDisciplina.value = reportsStore.academicFilters.id_disciplina;
    selectedInstructor.value = reportsStore.academicFilters.id_instructor;
    await loadStats();
  } else {
    updateDatesFromRango(filtroRango.value);
    await loadStats();
  }
});

watch([selectedDisciplina, selectedInstructor], () => loadStats());

// --- Configuración de Gráficos ---

// 1. Matriz de Demanda (Bubble Chart/Scatter)
// Mostramos los puntos calientes: Eje X = Dia Semana, Eje Y = Bloque Horario, Tamaño (r) = Porcentaje de ocupación
const chartMatrizDemanda = computed(() => {
  if (!stats.value?.matriz_demanda?.length) return null;

  const bubbleData = stats.value.matriz_demanda.map(item => {
    const horaInt = parseInt(item.hora.split(':')[0]);
    return {
      x: item.dia,
      y: horaInt,
      r: Math.max(4, Math.min(25, item.ocupacion_porcentaje / 4)), // escala de burbuja
      ocupacion: item.ocupacion_porcentaje,
      disciplina: item.disciplina,
      inscritos: item.avg_inscritos
    };
  });

  return {
    datasets: [{
      label: 'Demanda de Clases',
      data: bubbleData,
      backgroundColor: 'rgba(99, 102, 241, 0.45)', // Indigo-500 traslúcido
      borderColor: 'rgba(79, 70, 229, 0.9)',       // Indigo-600
      borderWidth: 1.5
    }]
  };
});

const optionsMatrizDemanda = {
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (context) => {
          const raw = context.raw;
          const diaLabels = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
          return `${raw.disciplina} (${diaLabels[raw.x - 1]} ${raw.y}:00): Llenado ${raw.ocupacion}% (promedio ${raw.inscritos} alumnos)`;
        }
      }
    }
  },
  scales: {
    x: {
      type: 'linear',
      min: 0.5,
      max: 7.5,
      ticks: {
        stepSize: 1,
        callback: (value) => {
          const labels = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
          return labels[value - 1] || '';
        }
      },
      title: {
        display: true,
        text: 'Día de la Semana',
        color: '#64748b',
        font: { size: 11, weight: 'bold' }
      }
    },
    y: {
      type: 'linear',
      min: 6,
      max: 22,
      ticks: {
        stepSize: 2,
        callback: (value) => `${value}:00`
      },
      title: {
        display: true,
        text: 'Bloque Horario',
        color: '#64748b',
        font: { size: 11, weight: 'bold' }
      }
    }
  }
};

// 2. Ranking de Convocatoria (Horizontal Bar)
const chartRanking = computed(() => {
  if (!stats.value?.ranking_instructores?.labels?.length) return null;
  return {
    labels: stats.value.ranking_instructores.labels,
    datasets: [{
      label: 'Llenado Promedio %',
      data: stats.value.ranking_instructores.data,
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        if (!chartArea) return '#a855f7';
        const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
        gradient.addColorStop(0, '#7c3aed'); // Violet-600
        gradient.addColorStop(1, '#c084fc'); // Purple-400
        return gradient;
      },
      borderColor: '#6d28d9',
      borderWidth: 1,
      borderRadius: 6,
      barThickness: 16
    }]
  };
});

const optionsRanking = {
  indexAxis: 'y',
  plugins: {
    legend: { display: false }
  },
  scales: {
    x: {
      min: 0,
      max: 100,
      ticks: { callback: (val) => `${val}%` }
    }
  }
};

// 3. Asistencia vs Abandono (Stacked Bar 100%)
const chartAsistencia = computed(() => {
  if (!stats.value?.asistencia_disciplina?.labels?.length) return null;
  return {
    labels: stats.value.asistencia_disciplina.labels,
    datasets: [
      {
        label: 'Asistencia (Check-in)',
        data: stats.value.asistencia_disciplina.asistencias,
        backgroundColor: (context) => {
          const { ctx, chartArea } = context.chart;
          if (!chartArea) return '#10b981';
          const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
          gradient.addColorStop(0, '#059669'); // Emerald-600
          gradient.addColorStop(1, '#34d399'); // Emerald-400
          return gradient;
        },
        borderRadius: 6
      },
      {
        label: 'No-Show / Inasistencias',
        data: stats.value.asistencia_disciplina.no_shows,
        backgroundColor: (context) => {
          const { ctx, chartArea } = context.chart;
          if (!chartArea) return '#f43f5e';
          const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
          gradient.addColorStop(0, '#e11d48'); // Rose-600
          gradient.addColorStop(1, '#fb7185'); // Rose-400
          return gradient;
        },
        borderRadius: 6
      }
    ]
  };
});

const optionsAsistencia = {
  responsive: true,
  scales: {
    x: { stacked: true },
    y: { stacked: true }
  }
};

// Computes de Totales y Desgloses para copiar el estilo premium de Ludoteca
const totalDemandaOcupacion = computed(() => {
  if (!stats.value?.matriz_demanda?.length) return 0;
  const total = stats.value.matriz_demanda.reduce((sum, item) => sum + (item.ocupacion_porcentaje || 0), 0);
  return Math.round(total / stats.value.matriz_demanda.length);
});

const individualDemanda = computed(() => {
  const diaLabels = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
  const days = Array.from({ length: 7 }, (_, i) => ({ label: diaLabels[i], sum: 0, count: 0 }));
  stats.value?.matriz_demanda?.forEach(item => {
    if (item.dia >= 1 && item.dia <= 7) {
      days[item.dia - 1].sum += (item.ocupacion_porcentaje || 0);
      days[item.dia - 1].count++;
    }
  });
  return days.map(d => ({
    label: d.label,
    value: d.count > 0 ? `${Math.round(d.sum / d.count)}%` : '0%',
    color: '#6366f1'
  })).filter(d => d.value !== '0%');
});

const totalRankingOcupacion = computed(() => {
  if (!stats.value?.ranking_instructores?.data?.length) return 0;
  const sum = stats.value.ranking_instructores.data.reduce((a, b) => a + Number(b), 0);
  return Math.round(sum / stats.value.ranking_instructores.data.length);
});

const individualRanking = computed(() => {
  const labels = stats.value?.ranking_instructores?.labels || [];
  const data = stats.value?.ranking_instructores?.data || [];
  return labels.map((label, idx) => ({
    label,
    value: `${data[idx]}%`,
    color: '#7c3aed'
  })).filter(item => parseInt(item.value) > 0);
});

const totalAsistenciaNoShows = computed(() => {
  const asists = stats.value?.asistencia_disciplina?.asistencias?.reduce((a, b) => a + Number(b), 0) || 0;
  const noshows = stats.value?.asistencia_disciplina?.no_shows?.reduce((a, b) => a + Number(b), 0) || 0;
  return asists + noshows;
});

const individualAsistencia = computed(() => {
  const labels = stats.value?.asistencia_disciplina?.labels || [];
  const asistencias = stats.value?.asistencia_disciplina?.asistencias || [];
  const noShows = stats.value?.asistencia_disciplina?.no_shows || [];
  return labels.map((label, idx) => ({
    label,
    value: `${asistencias[idx] || 0} Check-ins / ${noShows[idx] || 0} No-shows`,
    color: '#10b981'
  })).filter(item => asistencias[labels.indexOf(item.label)] > 0 || noShows[labels.indexOf(item.label)] > 0);
});
</script>

<template>
  <div class="space-y-8 font-sans">
    
    <!-- ENCABEZADO Y FILTROS DE RANGO -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-xl font-black text-surface-900 m-0 tracking-tight">Periodo de Análisis</h2>
        <p class="text-xs text-surface-500 m-0 mt-0.5 font-medium">Ajusta el rango de tiempo de consulta para recalcular el rendimiento académico.</p>
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
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        
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

        <!-- Instructor -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Instructor</label>
          <div class="relative">
            <IconFilter class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <select v-model="selectedInstructor"
                    class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-xs font-bold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 focus:bg-white transition-all cursor-pointer">
              <option value="">Todos los instructores</option>
              <option v-for="i in instructors" :key="i.id_instructor" :value="i.id_instructor">{{ i.nombre_completo }}</option>
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
      <span class="text-sm font-bold text-surface-500 mt-4">Analizando rendimientos académicos…</span>
    </div>

    <div v-else-if="errorMsg" class="p-8 text-center bg-red-50 border border-red-200 rounded-3xl text-red-700 font-semibold">
      {{ errorMsg }}
    </div>

    <!-- REPORTES GRÁFICOS -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      
      <!-- Gráfico 1: Burbujas de Horarios -->
      <div class="bg-white border border-surface-200 rounded-[2.2rem] shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 lg:p-8 flex flex-col hover:shadow-lg transition-all duration-300">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Matriz de Demanda (Horas y Días)</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Muestra los puntos con mayor afluencia. El tamaño de la burbuja representa la saturación.</p>
        </div>
        <div class="h-[280px] w-full" v-if="chartMatrizDemanda">
          <BaseChart type="bubble" :data="chartMatrizDemanda" :options="optionsMatrizDemanda" />
        </div>
        <div v-else class="h-[280px] flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
          Sin datos de reservas registradas para este periodo
        </div>

        <!-- Desglose de Totales y Métricas Individuales -->
        <div class="mt-6 border-t border-surface-100 pt-4" v-if="chartMatrizDemanda && individualDemanda.length">
          <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Llenado por Día</span>
            <span class="text-xs font-bold text-surface-500 bg-surface-50 px-3 py-1 rounded-full border border-surface-100 shadow-sm">
              Llenado Promedio: <span class="text-surface-900 font-black">{{ totalDemandaOcupacion }}%</span>
            </span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-[160px] overflow-y-auto scrollbar-thin pr-1">
            <div v-for="(val, idx) in individualDemanda" :key="idx" 
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

      <!-- Gráfico 2: Ranking Convocatoria Instructores -->
      <div class="bg-white border border-surface-200 rounded-[2.2rem] shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 lg:p-8 flex flex-col hover:shadow-lg transition-all duration-300">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Convocatoria por Instructor</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Porcentaje promedio de llenado de cupo por clase programada.</p>
        </div>
        <div class="h-[280px] w-full" v-if="chartRanking">
          <BaseChart type="bar" :data="chartRanking" :options="optionsRanking" />
        </div>
        <div v-else class="h-[280px] flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
          Sin sesiones activas en este periodo
        </div>

        <!-- Desglose de Totales y Métricas Individuales -->
        <div class="mt-6 border-t border-surface-100 pt-4" v-if="chartRanking && individualRanking.length">
          <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Desglose por Instructor</span>
            <span class="text-xs font-bold text-surface-500 bg-surface-50 px-3 py-1 rounded-full border border-surface-100 shadow-sm">
              Llenado Instructor: <span class="text-surface-900 font-black">{{ totalRankingOcupacion }}%</span>
            </span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-[160px] overflow-y-auto scrollbar-thin pr-1">
            <div v-for="(val, idx) in individualRanking" :key="idx" 
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

      <!-- Gráfico 3: Stacked Bar Asistencia Disciplina (Full Width) -->
      <div class="lg:col-span-2 bg-white border border-surface-200 rounded-[2.2rem] shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 lg:p-8 flex flex-col hover:shadow-lg transition-all duration-300">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Asistencia vs. Abandono (No-Shows) por Disciplina</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Analiza el compromiso de asistencia. Identifica clases que reservan pero no asisten.</p>
        </div>
        <div class="h-[280px] w-full" v-if="chartAsistencia">
          <BaseChart type="bar" :data="chartAsistencia" :options="optionsAsistencia" />
        </div>
        <div v-else class="h-[280px] flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
          Sin datos de asistencias registrados en este periodo
        </div>

        <!-- Desglose de Totales y Métricas Individuales -->
        <div class="mt-6 border-t border-surface-100 pt-4" v-if="chartAsistencia && individualAsistencia.length">
          <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Desglose por Disciplina</span>
            <span class="text-xs font-bold text-surface-500 bg-surface-50 px-3 py-1 rounded-full border border-surface-100 shadow-sm">
              Total Reservas: <span class="text-surface-900 font-black">{{ totalAsistenciaNoShows }}</span>
            </span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-[160px] overflow-y-auto scrollbar-thin pr-1">
            <div v-for="(val, idx) in individualAsistencia" :key="idx" 
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