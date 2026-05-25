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

        <!-- Instructor -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Instructor</label>
          <div class="relative">
            <IconFilter class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <select v-model="selectedInstructor"
                    class="w-full pl-10 pr-8 py-2 bg-white border border-surface-200 rounded-xl text-xs font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
              <option value="">Todos los instructores</option>
              <option v-for="i in instructors" :key="i.id_instructor" :value="i.id_instructor">{{ i.nombre_completo }}</option>
            </select>
          </div>
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
      <div class="bg-white border border-surface-200 rounded-3xl p-6 lg:p-8 flex flex-col h-[400px]">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Matriz de Demanda (Horas y Días)</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Muestra los puntos con mayor afluencia. El tamaño de la burbuja representa la saturación.</p>
        </div>
        <div class="flex-1 min-h-0">
          <BaseChart v-if="chartMatrizDemanda" type="bubble" :data="chartMatrizDemanda" :options="optionsMatrizDemanda" />
          <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
            Sin datos de reservas registradas para este periodo
          </div>
        </div>
      </div>

      <!-- Gráfico 2: Ranking Convocatoria Instructores -->
      <div class="bg-white border border-surface-200 rounded-3xl p-6 lg:p-8 flex flex-col h-[400px]">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Convocatoria por Instructor</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Porcentaje promedio de llenado de cupo por clase programada.</p>
        </div>
        <div class="flex-1 min-h-0">
          <BaseChart v-if="chartRanking" type="bar" :data="chartRanking" :options="optionsRanking" />
          <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
            Sin sesiones activas en este periodo
          </div>
        </div>
      </div>

      <!-- Gráfico 3: Stacked Bar Asistencia Disciplina (Full Width) -->
      <div class="lg:col-span-2 bg-white border border-surface-200 rounded-3xl p-6 lg:p-8 flex flex-col h-[400px]">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Asistencia vs. Abandono (No-Shows) por Disciplina</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Analiza el compromiso de asistencia. Identifica clases que reservan pero no asisten.</p>
        </div>
        <div class="flex-1 min-h-0">
          <BaseChart v-if="chartAsistencia" type="bar" :data="chartAsistencia" :options="optionsAsistencia" />
          <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
            Sin datos de asistencias registrados en este periodo
          </div>
        </div>
      </div>

    </div>

  </div>
</template>