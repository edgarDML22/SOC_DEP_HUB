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
const filtroRango = ref('mes');
const filterDateStart = ref('');
const filterDateEnd = ref('');
const selectedDisciplina = ref('');
const selectedInstructor = ref('');

const isAnyFilterSelected = computed(() => {
  return !!(filterDateStart.value || filterDateEnd.value || selectedDisciplina.value || selectedInstructor.value || (filtroRango.value && filtroRango.value !== 'mes'));
});

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
  try {
    isLoading.value = true;
    errorMsg.value = '';

    const params = {
      id_disciplina: selectedDisciplina.value || undefined,
      id_instructor: selectedInstructor.value || undefined
    };

    if (filterDateStart.value && filterDateEnd.value) {
      params.fecha_inicio = filterDateStart.value;
      params.fecha_fin = filterDateEnd.value;
    } else if (filtroRango.value) {
      params.rango = filtroRango.value;
    } else {
      params.rango = 'mes';
    }

    await reportsStore.fetchAcademicStats(params, forceRefresh);
  } catch (error) {
    console.error('Error loading academic stats:', error);
    errorMsg.value = 'Conexión interrumpida con el servidor de BI.';
  } finally {
    isLoading.value = false;
  }
};

const selectPreset = (rango) => {
  filterDateStart.value = '';
  filterDateEnd.value = '';
  filtroRango.value = rango;
  loadStats(true);
};

const clearFilters = () => {
  if (!isAnyFilterSelected.value) return;
  filterDateStart.value = '';
  filterDateEnd.value = '';
  selectedDisciplina.value = '';
  selectedInstructor.value = '';
  filtroRango.value = 'mes';
  loadStats(true);
};

watch([filterDateStart, filterDateEnd], ([newStart, newEnd]) => {
  if (newStart || newEnd) {
    filtroRango.value = '';
  }
  if (newStart && newEnd) {
    if (newStart > newEnd) {
      filterDateStart.value = newEnd;
      filterDateEnd.value = newStart;
      return;
    }
    loadStats(true);
  }
});

watch([selectedDisciplina, selectedInstructor], () => loadStats(true));

onMounted(async () => {
  await loadMetadata();
  filtroRango.value = 'mes';
  filterDateStart.value = '';
  filterDateEnd.value = '';
  selectedDisciplina.value = '';
  selectedInstructor.value = '';
  loadStats(true);
});

// --- Configuración de Gráficos ---

// 1. Matriz de Demanda (Bubble Chart/Scatter)
// Mostramos los puntos calientes: Eje X = Dia Semana (1..7), Eje Y = Bloque Horario, Tamaño (r) = Porcentaje de ocupación
const chartMatrizDemanda = computed(() => {
  if (!stats.value?.matriz_demanda?.length) return null;

  const diaMap = {
    'LUNES': 1, 'MARTES': 2, 'MIERCOLES': 3, 'MIÉRCOLES': 3,
    'JUEVES': 4, 'VIERNES': 5, 'SABADO': 6, 'SÁBADO': 6, 'DOMINGO': 7
  };

  const bubbleData = stats.value.matriz_demanda.map(item => {
    const diaNum = typeof item.dia === 'number' ? item.dia : (diaMap[String(item.dia).toUpperCase().trim()] || 1);
    const horaInt = typeof item.hora_num === 'number' ? item.hora_num : parseInt(String(item.hora).split(':')[0]);
    return {
      x: diaNum,
      y: horaInt,
      r: Math.max(5, Math.min(22, (item.ocupacion_porcentaje || 10) / 4)),
      ocupacion: item.ocupacion_porcentaje,
      disciplina: item.disciplina,
      inscritos: item.avg_inscritos
    };
  });

  return {
    datasets: [{
      label: 'Demanda de Clases',
      data: bubbleData,
      backgroundColor: (context) => {
        const raw = context.raw;
        if (!raw) return 'rgba(14, 165, 233, 0.7)';
        const occ = raw.ocupacion || 0;
        if (occ >= 75) return 'rgba(239, 68, 68, 0.75)'; // Alta demanda / saturado (>=75%)
        if (occ >= 40) return 'rgba(99, 102, 241, 0.75)'; // Demanda moderada (40-74%)
        return 'rgba(14, 165, 233, 0.7)'; // Demanda baja (<40%)
      },
      borderColor: (context) => {
        const raw = context.raw;
        if (!raw) return '#0284c7';
        const occ = raw.ocupacion || 0;
        if (occ >= 75) return '#b91c1c';
        if (occ >= 40) return '#4338ca';
        return '#0284c7';
      },
      borderWidth: 1.5
    }]
  };
});

const optionsMatrizDemanda = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0f172a',
      titleFont: { size: 12, weight: 'bold' },
      bodyFont: { size: 12 },
      padding: 12,
      cornerRadius: 10,
      callbacks: {
        label: (context) => {
          const raw = context.raw;
          const diaLabels = {
            1: 'Lunes', 2: 'Martes', 3: 'Miércoles',
            4: 'Jueves', 5: 'Viernes', 6: 'Sábado', 7: 'Domingo'
          };
          const diaText = diaLabels[raw.x] || `Día ${raw.x}`;
          return ` ${raw.disciplina} (${diaText} ${raw.y}:00): Llenado ${raw.ocupacion}% (promedio ${raw.inscritos} alumnos)`;
        }
      }
    }
  },
  scales: {
    x: {
      type: 'linear',
      min: 0.5,
      max: 7.5,
      afterBuildTicks: (axis) => {
        axis.ticks = [1, 2, 3, 4, 5, 6, 7].map(v => ({ value: v }));
      },
      ticks: {
        stepSize: 1,
        autoSkip: false,
        callback: (value) => {
          const labels = {
            1: 'Lunes',
            2: 'Martes',
            3: 'Miércoles',
            4: 'Jueves',
            5: 'Viernes',
            6: 'Sábado',
            7: 'Domingo'
          };
          return labels[value] || '';
        },
        font: { size: 11, weight: 'bold' },
        color: '#1e293b'
      },
      grid: {
        color: '#f1f5f9',
        drawBorder: false
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
      grid: {
        color: '#f8fafc',
        drawBorder: false
      },
      ticks: {
        stepSize: 2,
        callback: (value) => `${value}:00`,
        font: { size: 10, weight: 'bold' },
        color: '#64748b'
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

// 2. Ranking de Convocatoria (Horizontal Bar - Rework estético con mayor espacio y gradientes)
const chartRanking = computed(() => {
  if (!stats.value?.ranking_instructores?.labels?.length) return null;
  return {
    labels: stats.value.ranking_instructores.labels,
    datasets: [{
      label: 'Llenado Promedio %',
      data: stats.value.ranking_instructores.data,
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        if (!chartArea) return '#6366f1';
        const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
        gradient.addColorStop(0, '#4f46e5'); // Indigo-600
        gradient.addColorStop(0.6, '#6366f1'); // Indigo-500
        gradient.addColorStop(1, '#a855f7'); // Purple-500
        return gradient;
      },
      borderColor: '#4338ca',
      borderWidth: 1,
      borderRadius: 8,
      barThickness: 20,
      maxBarThickness: 24,
      categoryPercentage: 0.8,
      barPercentage: 0.85
    }]
  };
});

const optionsRanking = {
  indexAxis: 'y',
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0f172a',
      titleFont: { size: 12, weight: 'bold' },
      bodyFont: { size: 12 },
      padding: 12,
      cornerRadius: 10,
      callbacks: {
        label: (ctx) => ` Convocatoria: ${ctx.parsed.x}% de llenado promedio`
      }
    }
  },
  scales: {
    x: {
      min: 0,
      max: 100,
      grid: {
        color: '#f1f5f9',
        drawBorder: false
      },
      ticks: {
        callback: (val) => `${val}%`,
        font: { size: 11, weight: 'bold' },
        color: '#64748b'
      }
    },
    y: {
      grid: {
        display: false,
        drawBorder: false
      },
      ticks: {
        font: { size: 12, weight: '600' },
        color: '#0f172a',
        padding: 10
      }
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
  maintainAspectRatio: false,
  plugins: {
    tooltip: {
      backgroundColor: '#0f172a',
      titleFont: { size: 12, weight: 'bold' },
      bodyFont: { size: 12 },
      padding: 12,
      cornerRadius: 10
    }
  },
  scales: {
    x: {
      stacked: true,
      grid: {
        display: false,
        drawBorder: false
      },
      ticks: {
        font: { size: 11, weight: 'bold' },
        color: '#334155'
      }
    },
    y: {
      stacked: true,
      grid: {
        color: '#f1f5f9',
        drawBorder: false
      },
      ticks: {
        font: { size: 10, weight: 'bold' },
        color: '#64748b',
        precision: 0
      }
    }
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
  const diaMap = {
    'LUNES': 1, 'MARTES': 2, 'MIERCOLES': 3, 'MIÉRCOLES': 3,
    'JUEVES': 4, 'VIERNES': 5, 'SABADO': 6, 'SÁBADO': 6, 'DOMINGO': 7
  };
  const days = Array.from({ length: 7 }, (_, i) => ({ label: diaLabels[i], sum: 0, count: 0 }));
  stats.value?.matriz_demanda?.forEach(item => {
    const diaNum = typeof item.dia === 'number' ? item.dia : (diaMap[String(item.dia).toUpperCase().trim()] || 0);
    if (diaNum >= 1 && diaNum <= 7) {
      days[diaNum - 1].sum += (item.ocupacion_porcentaje || 0);
      days[diaNum - 1].count++;
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
    asistencias: Number(asistencias[idx] || 0),
    noShows: Number(noShows[idx] || 0),
    color: '#2563eb' // Color azul principal para el indicador
  })).filter(item => item.asistencias > 0 || item.noShows > 0);
});
</script>

<template>
  <div class="space-y-8 font-sans">
    
    <!-- ENCABEZADO Y FILTROS DE RANGO -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-xl font-black text-surface-900 m-0 tracking-tight">Periodo de Análisis</h2>
        <p class="text-xs text-surface-500 m-0 mt-0.5 font-medium">Ajusta el rango de tiempo de consulta para recalcular las métricas de actividades e instructores.</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="flex p-1 bg-slate-100 rounded-2xl shadow-inner border border-surface-200">
          <button v-for="r in ['hoy', 'semana', 'mes']" :key="r" 
            @click="selectPreset(r)"
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
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">DESDE</label>
          <div class="relative">
            <IconCalendar class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <input type="date" v-model="filterDateStart"
                   class="w-full pl-10 pr-4 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-xs font-bold text-surface-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 focus:border-slate-400 focus:bg-white transition-all cursor-pointer" />
          </div>
        </div>

        <!-- Rango Fecha Fin -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">HASTA</label>
          <div class="relative">
            <IconCalendar class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <input type="date" v-model="filterDateEnd"
                   :min="filterDateStart || undefined"
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
              <option value="">Todas</option>
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
              <option value="">Todos</option>
              <option v-for="i in instructors" :key="i.id_instructor" :value="i.id_instructor">{{ i.nombre_completo }}</option>
            </select>
          </div>
        </div>

        <!-- Botón Limpiar Filtro -->
        <div class="flex flex-col gap-1.5">
          <button @click="clearFilters"
                  :disabled="!isAnyFilterSelected || isLoading"
                  :class="!isAnyFilterSelected ? 'opacity-40 cursor-not-allowed pointer-events-none' : 'hover:bg-white text-surface-700 cursor-pointer shadow-xs active:scale-[0.98]'"
                  class="w-full py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 h-[42px]">
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
      <span class="text-sm font-bold text-surface-500 mt-4">Analizando analíticas de actividades…</span>
    </div>

    <div v-else-if="errorMsg" class="p-8 text-center bg-red-50 border border-red-200 rounded-3xl text-red-700 font-semibold">
      {{ errorMsg }}
    </div>

    <!-- REPORTES GRÁFICOS -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      
      <!-- Gráfico 1: Burbujas de Horarios -->
      <div class="bg-white border border-surface-200 rounded-[2.2rem] shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 lg:p-8 flex flex-col hover:shadow-lg transition-all duration-300">
        <div class="mb-4 flex items-start justify-between flex-wrap gap-2">
          <div>
            <h3 class="text-base font-black text-surface-900 leading-tight">Matriz de Demanda (Horas y Días)</h3>
            <p class="text-xs font-medium text-surface-400 mt-0.5">Muestra los puntos con mayor afluencia. El tamaño de la burbuja representa la saturación.</p>
          </div>
          <div class="flex items-center gap-2 text-[10px] font-bold text-surface-500 bg-surface-50 px-3 py-1.5 rounded-xl border border-surface-200">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span> &lt;40%</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span> 40-74%</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> ≥75%</span>
          </div>
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
        
        <!-- Contenedor espacioso con altura dinámica según instructores -->
        <div class="w-full max-h-[380px] overflow-y-auto scrollbar-thin pr-1" v-if="chartRanking">
          <div :style="{ height: `${Math.max(260, (stats?.ranking_instructores?.labels?.length || 0) * 48)}px`, minHeight: '260px' }" class="w-full">
            <BaseChart type="bar" :data="chartRanking" :options="optionsRanking" />
          </div>
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
              Total Reservas: <span class="text-primary-600 font-black">{{ totalAsistenciaNoShows }}</span>
            </span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-[160px] overflow-y-auto scrollbar-thin pr-1">
            <div v-for="(val, idx) in individualAsistencia" :key="idx" 
              class="flex items-center justify-between p-2.5 bg-surface-50/50 hover:bg-surface-50 hover:border-surface-200 rounded-2xl border border-surface-100 transition-all">
              <div class="flex items-center gap-2 truncate min-w-0">
                <span class="w-2.5 h-2.5 rounded-full bg-primary-600 shrink-0"></span>
                <span class="text-xs font-semibold text-black truncate">{{ val.label }}</span>
              </div>
              <div class="flex items-center gap-1.5 text-xs ml-2 bg-white px-2.5 py-0.5 rounded-lg border border-surface-100 shadow-2xs font-medium shrink-0">
                <span class="text-emerald-600">{{ val.asistencias }} Check-ins</span>
                <span class="text-surface-300">/</span>
                <span class="text-red-600">{{ val.noShows }} No-shows</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</template>