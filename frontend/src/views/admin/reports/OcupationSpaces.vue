<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import api from '@/services/api';
import BaseChart from '@/components/admin/BaseChart.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import { IconFilter, IconCalendar } from '@/components/icons';
import { useReportsBIStore } from '@/stores/admin/reportsBIStore';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';
import { useDisciplinesStore } from '@/stores/admin/disciplines';

const reportsStore = useReportsBIStore();
const disciplinesStore = useDisciplinesStore();

const isInitialLoading = ref(!reportsStore.spacesStats);
const isUpdating = ref(false);
const errorMsg = ref('');
const stats = computed(() => reportsStore.spacesStats);

const spacesList = ref([]);

// Computed para obtener dinámicamente todas las disciplinas activas de la base de datos
const activeDisciplinesList = computed(() => {
  return disciplinesStore.disciplines.filter(d => d.estatus === 'ACTIVO');
});

// Rango dinámico y filtros
const filtroRango = ref(reportsStore.spacesFilters.rango || 'semana');
const filterDateStart = ref(reportsStore.spacesFilters.fecha_inicio);
const filterDateEnd = ref(reportsStore.spacesFilters.fecha_fin);
const selectedSpace = ref(reportsStore.spacesFilters.id_espacio);
const selectedDisciplina = ref(reportsStore.spacesFilters.id_disciplina || '');

// Filtramos la lista de disciplinas activas para mostrar únicamente las que tienen reservaciones (conteo > 0) y las ordenamos alfabéticamente
const activeDisciplinesWithCounts = computed(() => {
  const active = activeDisciplinesList.value;
  let filtered = active;
  if (stats.value?.ocupacion_por_tipo?.ids) {
    const idsWithCounts = stats.value.ocupacion_por_tipo.ids;
    filtered = active.filter(d => idsWithCounts.includes(d.id_disciplina));
  }
  return [...filtered].sort((a, b) => a.nombre_disciplina.localeCompare(b.nombre_disciplina));
});

// Si la disciplina seleccionada deja de tener registros al cambiar otro filtro, restablecemos a "Todas"
watch(activeDisciplinesWithCounts, (newList) => {
  if (selectedDisciplina.value && !newList.some(d => d.id_disciplina === selectedDisciplina.value)) {
    selectedDisciplina.value = '';
  }
});

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

const loadSpacesList = async () => {
  try {
    const res = await api.get('/spaces/all');
    spacesList.value = res.data?.data || [];
  } catch (error) {
    console.error('Error loading physical spaces list:', error);
  }
};

const loadStats = async (forceRefresh = false) => {
  const isSameFilters =
    reportsStore.spacesStats &&
    reportsStore.spacesFilters.rango === (filtroRango.value || undefined) &&
    reportsStore.spacesFilters.fecha_inicio === filterDateStart.value &&
    reportsStore.spacesFilters.fecha_fin === filterDateEnd.value &&
    reportsStore.spacesFilters.id_espacio === selectedSpace.value &&
    reportsStore.spacesFilters.id_disciplina === selectedDisciplina.value;

  if (isSameFilters && !forceRefresh) {
    return;
  }

  try {
    if (!stats.value) {
      isInitialLoading.value = true;
    } else {
      isUpdating.value = true;
    }
    errorMsg.value = '';
    await reportsStore.fetchSpacesStats({
      fecha_inicio: filterDateStart.value,
      fecha_fin: filterDateEnd.value,
      id_espacio: selectedSpace.value,
      id_disciplina: selectedDisciplina.value,
      rango: filtroRango.value || undefined
    }, forceRefresh);
  } catch (error) {
    console.error('Error loading spaces stats:', error);
    errorMsg.value = 'Imposible conectar con el servicio analítico de infraestructura.';
  } finally {
    isInitialLoading.value = false;
    isUpdating.value = false;
  }
};

const clearFilters = () => {
  filtroRango.value = 'semana';
  selectedSpace.value = '';
  selectedDisciplina.value = '';
  updateDatesFromRango('semana');
  loadStats(true);
};

const selectDisciplina = (id) => {
  selectedDisciplina.value = id;
};

watch(filtroRango, (newVal) => {
  if (newVal) {
    updateDatesFromRango(newVal);
    loadStats();
  }
});

onMounted(async () => {
  await loadSpacesList();
  await disciplinesStore.fetchDisciplines();
  if (reportsStore.spacesStats) {
    filtroRango.value = reportsStore.spacesFilters.rango;
    filterDateStart.value = reportsStore.spacesFilters.fecha_inicio;
    filterDateEnd.value = reportsStore.spacesFilters.fecha_fin;
    selectedSpace.value = reportsStore.spacesFilters.id_espacio;
    selectedDisciplina.value = reportsStore.spacesFilters.id_disciplina || '';
    loadStats();
  } else {
    updateDatesFromRango(filtroRango.value);
    await loadStats();
  }
});

watch([selectedSpace, selectedDisciplina], () => loadStats());

// --- Configuración de Gráficos ---

// 1. Mapa de Calor de Saturación (Bubble Chart)
const chartSaturacion = computed(() => {
  if (!stats.value?.saturacion_heatmap?.length) return null;

  const bubbleData = stats.value.saturacion_heatmap.map(item => {
    return {
      x: item.dia,
      y: parseInt(item.hora.split(':')[0]),
      r: Math.max(3, Math.min(22, item.total * 3)), // escala de burbuja
      total: item.total
    };
  });

  return {
    datasets: [{
      label: 'Saturación de Espacios',
      data: bubbleData,
      backgroundColor: 'rgba(245, 158, 11, 0.55)', // Amber-500 traslúcido
      borderColor: 'rgba(217, 119, 6, 0.95)',      // Amber-600
      borderWidth: 1.5
    }]
  };
});

const optionsSaturacion = {
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (context) => {
          const raw = context.raw;
          const diaLabels = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
          return `Saturación (${diaLabels[raw.x - 1]} ${raw.y}:00): ${raw.total} reservaciones activas`;
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

// 2. Ocupación por Disciplina (Bar Chart Horizontal styled like Convocatoria por Instructor)
const chartOcupacionTipo = computed(() => {
  if (!stats.value?.ocupacion_por_tipo?.labels?.length) return null;
  return {
    labels: stats.value.ocupacion_por_tipo.labels,
    datasets: [{
      label: 'Reservaciones registradas',
      data: stats.value.ocupacion_por_tipo.data,
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        if (!chartArea) return '#7c3aed';
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

const optionsOcupacionTipo = {
  indexAxis: 'y',
  plugins: {
    legend: { display: false }
  },
  scales: {
    x: {
      min: 0,
      ticks: { precision: 0 }
    }
  }
};

// 3. Ocupación por Espacio Físico (Bar Chart Vertical)
const chartOcupacionEspacio = computed(() => {
  if (!stats.value?.ocupacion_por_espacio?.labels?.length) return null;
  return {
    labels: stats.value.ocupacion_por_espacio.labels,
    datasets: [{
      label: 'Reservaciones registradas',
      data: stats.value.ocupacion_por_espacio.data,
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
          ['#3b82f6', '#1d4ed8'], // Blue
          ['#f97316', '#c2410c'], // Orange
          ['#84cc16', '#4d7c0f'], // Lime
          ['#14b8a6', '#0f766e'], // Teal
          ['#a855f7', '#7e22ce'], // Purple
          ['#64748b', '#334155']  // Slate
        ];
        const pair = colorsMap[context.dataIndex % colorsMap.length];
        if (!chartArea) return pair[0];
        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        gradient.addColorStop(0, pair[1]);
        gradient.addColorStop(1, pair[0]);
        return gradient;
      },
      borderColor: (context) => {
        const colorsMap = [
          '#4338ca', // Indigo
          '#047857', // Emerald
          '#b45309', // Amber
          '#be185d', // Pink
          '#0e7490', // Cyan
          '#6d28d9', // Violet
          '#be123c', // Rose
          '#1d4ed8', // Blue
          '#c2410c', // Orange
          '#4d7c0f', // Lime
          '#0f766e', // Teal
          '#7e22ce', // Purple
          '#334155'  // Slate
        ];
        return colorsMap[context.dataIndex % colorsMap.length];
      },
      borderWidth: 1,
      borderRadius: 6,
      barThickness: 24
    }]
  };
});

const optionsOcupacionEspacio = {
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: {
      min: 0,
      ticks: { precision: 0 }
    }
  }
};

// Computes de Totales y Desgloses para copiar el estilo premium de Ludoteca
const totalReservas = computed(() => {
  return stats.value?.saturacion_heatmap?.reduce((sum, item) => sum + (item.total || 0), 0) || 0;
});

const individualSaturacion = computed(() => {
  const diaLabels = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
  const days = Array.from({ length: 7 }, (_, i) => ({ label: diaLabels[i], value: 0, color: '#f59e0b' }));
  stats.value?.saturacion_heatmap?.forEach(item => {
    if (item.dia >= 1 && item.dia <= 7) {
      days[item.dia - 1].value += (item.total || 0);
    }
  });
  return days.filter(d => d.value > 0);
});

const totalOcupacion = computed(() => {
  return stats.value?.ocupacion_por_tipo?.data?.reduce((sum, val) => sum + Number(val), 0) || 0;
});

const individualOcupacion = computed(() => {
  const labels = stats.value?.ocupacion_por_tipo?.labels || [];
  const data = stats.value?.ocupacion_por_tipo?.data || [];
  return labels.map((label, idx) => ({
    label,
    value: data[idx] || 0,
    color: '#7c3aed'
  })).filter(item => item.value > 0);
});

const totalOcupacionEspacio = computed(() => {
  return stats.value?.ocupacion_por_espacio?.data?.reduce((sum, val) => sum + Number(val), 0) || 0;
});

const individualOcupacionEspacio = computed(() => {
  const labels = stats.value?.ocupacion_por_espacio?.labels || [];
  const data = stats.value?.ocupacion_por_espacio?.data || [];
  const colorsMap = [
    '#6366f1', // Indigo
    '#10b981', // Emerald
    '#f59e0b', // Amber
    '#ec4899', // Pink
    '#06b6d4', // Cyan
    '#8b5cf6', // Violet
    '#f43f5e', // Rose
    '#3b82f6', // Blue
    '#f97316', // Orange
    '#84cc16', // Lime
    '#14b8a6', // Teal
    '#a855f7', // Purple
    '#64748b'  // Slate
  ];
  return labels.map((label, idx) => ({
    label,
    value: data[idx] || 0,
    color: colorsMap[idx % colorsMap.length]
  })).filter(item => item.value > 0);
});
</script>

<template>
  <div class="space-y-8 font-sans">
    
    <!-- ENCABEZADO Y FILTROS DE RANGO -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-xl font-black text-surface-900 m-0 tracking-tight">Periodo de Análisis</h2>
        <p class="text-xs text-surface-500 m-0 mt-0.5 font-medium">Ajusta el rango de tiempo de consulta para recalcular el uso de infraestructura.</p>
      </div>
      <div class="flex items-center gap-3">
        <div class="flex p-1 bg-slate-100 rounded-2xl shadow-inner border border-surface-200">
          <button v-for="r in ['hoy', 'semana', 'mes']" :key="r" 
            @click="filtroRango = r"
            :disabled="isInitialLoading || isUpdating"
            class="py-1.5 px-4 rounded-xl text-xs font-black transition-all duration-200 ease-out capitalize disabled:opacity-50 cursor-pointer border-none"
            :class="filtroRango === r ? 'bg-surface-900 text-white shadow-md transform scale-[1.01]' : 'text-surface-500 hover:bg-white hover:text-surface-700'">
            {{ r }}
          </button>
        </div>
      </div>
    </div>

    <!-- BARRA DE FILTROS -->
    <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        
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
    <div class="relative min-h-96 w-full">
      <!-- Loader Inicial a pantalla completa -->
      <div v-if="isInitialLoading" class="flex flex-col items-center justify-center py-20 bg-white min-h-[400px] w-full">
        <LoadingSpinner />
        <span class="text-sm font-bold text-surface-500 mt-4">Analizando saturación de instalaciones…</span>
      </div>

      <!-- Mensaje de Error -->
      <div v-else-if="errorMsg" class="p-8 text-center bg-red-50 border border-red-200 rounded-3xl text-red-700 font-semibold w-full">
        {{ errorMsg }}
      </div>

      <!-- REPORTES GRÁFICOS CON CONTENEDOR REACTIVO -->
      <div v-else class="relative w-full transition-all duration-300">
        <!-- Loader superpuesto sutil durante la actualización incremental de filtros -->
        <div v-if="isUpdating" class="absolute inset-0 z-10 bg-white/40 backdrop-blur-[1px] flex items-center justify-center rounded-[2.2rem] transition-all duration-300">
          <div class="flex flex-col items-center gap-3 bg-white/95 shadow-xl border border-slate-100 px-7 py-5 rounded-2xl">
            <LoadingSpinner class="!w-8 !h-8" />
            <span class="text-xs font-black text-slate-600">Actualizando analíticas...</span>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 w-full" :class="{ 'opacity-60 pointer-events-none': isUpdating }">
          
          <!-- Gráfico 1: Mapa de Calor de Saturación Horaria Heatmap (Bubble) -->
          <div class="lg:col-span-3 bg-white border border-surface-200 rounded-[2.2rem] shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 lg:p-8 flex flex-col hover:shadow-lg transition-all duration-300">
            <div class="mb-4">
              <h3 class="text-base font-black text-surface-900 leading-tight">Mapa de Saturación Horaria General</h3>
              <p class="text-xs font-medium text-surface-400 mt-0.5">Cruza horas y días de afluencia para detectar horarios pico y canchas subutilizadas.</p>
            </div>
            <div class="h-[280px] w-full" v-if="chartSaturacion">
              <BaseChart type="bubble" :data="chartSaturacion" :options="optionsSaturacion" />
            </div>
            <div v-else class="h-[280px] flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
              Sin datos de saturación disponibles en este periodo
            </div>
            
            <!-- Desglose de Totales y Métricas Individuales -->
            <div class="mt-6 border-t border-surface-100 pt-4" v-if="chartSaturacion && individualSaturacion.length">
              <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Desglose por Día</span>
                <span class="text-xs font-bold text-surface-500 bg-surface-50 px-3 py-1 rounded-full border border-surface-100 shadow-sm">
                  Total Reservas: <span class="text-surface-900 font-black">{{ totalReservas }}</span>
                </span>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-[160px] overflow-y-auto scrollbar-thin pr-1">
                <div v-for="(val, idx) in individualSaturacion" :key="idx" 
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

          <!-- Gráfico 2: Proporción de Ocupación por Disciplina -->
          <div class="lg:col-span-2 bg-white border border-surface-200 rounded-[2.2rem] shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 lg:p-8 flex flex-col hover:shadow-lg transition-all duration-300">
            <div class="mb-4">
              <h3 class="text-base font-black text-surface-900 leading-tight">Ocupación por Disciplina</h3>
              <p class="text-xs font-medium text-surface-400 mt-0.5">Distribución proporcional de uso de canchas desglosada por disciplinas deportivas.</p>
            </div>
            <div class="h-[280px] w-full" v-if="chartOcupacionTipo">
              <BaseChart type="bar" :data="chartOcupacionTipo" :options="optionsOcupacionTipo" />
            </div>
            <div v-else class="h-[280px] flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
              Sin datos de ocupación para este periodo
            </div>

            <!-- Desglose de Totales y Métricas Individuales -->
            <div class="mt-6 border-t border-surface-100 pt-4" v-if="chartOcupacionTipo && individualOcupacion.length">
              <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Desglose por Disciplina</span>
                <span class="text-xs font-bold text-surface-500 bg-surface-50 px-3 py-1 rounded-full border border-surface-100 shadow-sm">
                  Total Usos: <span class="text-surface-900 font-black">{{ totalOcupacion }}</span>
                </span>
              </div>
              <div class="grid grid-cols-1 gap-2.5 max-h-[160px] overflow-y-auto scrollbar-thin pr-1">
                <div v-for="(val, idx) in individualOcupacion" :key="idx" 
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

          <!-- Gráfico 3: Ocupación por Espacio Físico (Bar Chart Horizontal styled like Convocatoria por Instructor) -->
          <div class="bg-white border border-surface-200 rounded-[2.2rem] shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 lg:p-8 flex flex-col hover:shadow-lg transition-all duration-300 w-full col-span-1 lg:col-span-5">
            <div class="mb-6">
              <h3 class="text-base font-black text-surface-900 leading-tight">Uso de Canchas y Espacios Físicos</h3>
              <p class="text-xs font-medium text-surface-400 mt-0.5">Visualización del volumen total de reservaciones por cada instalación, filtrable por disciplinas deportivas.</p>
            </div>

            <div class="flex flex-col lg:flex-row gap-8 items-start">
              
              <!-- SIDEBAR DE FILTROS DE DISCIPLINAS (Estilo /admin/activities) -->
              <!-- Solo se muestra si hay disciplinas con conteo en este periodo de análisis -->
              <div v-if="activeDisciplinesWithCounts.length > 0" class="w-full lg:w-56 shrink-0 flex flex-col gap-1 max-h-[380px] overflow-y-auto pr-2 scrollbar-thin border border-surface-200 rounded-2xl p-3 bg-surface-50 transition-all duration-300">
                <div class="flex items-center gap-2 px-2 py-1.5 rounded-xl bg-white border border-surface-200 shrink-0 w-full mb-2">
                  <span class="text-[10px] font-black uppercase tracking-widest text-surface-500">Filtrar Disciplina</span>
                </div>

                <!-- Opción "Todas" -->
                <button
                  type="button"
                  @click="selectDisciplina('')"
                  :class="[
                    'w-full text-left px-2.5 py-2 rounded-xl text-[11px] font-bold transition-all border flex items-center gap-2 cursor-pointer',
                    selectedDisciplina === ''
                      ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
                      : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900'
                  ]"
                >
                  <span class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 border border-slate-200 bg-white">
                    <svg class="w-3.5 h-3.5" :class="selectedDisciplina === '' ? 'text-slate-900' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25A2.25 2.25 0 0113.5 8.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                  </span>
                  <span class="truncate">Todas</span>
                  <svg v-if="selectedDisciplina === ''" class="w-3 h-3 shrink-0 ml-auto text-white/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                </button>

                <!-- Disciplinas con conteo activo (se ocultan las que no tienen registros) -->
                <button
                  v-for="disc in activeDisciplinesWithCounts"
                  :key="disc.id_disciplina"
                  type="button"
                  @click="selectDisciplina(disc.id_disciplina)"
                  :class="[
                    'w-full text-left px-2.5 py-2 rounded-xl text-[11px] font-bold transition-all truncate border flex items-center gap-2 cursor-pointer',
                    selectedDisciplina === disc.id_disciplina
                      ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
                      : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900'
                  ]"
                >
                  <span
                    class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 shadow-sm"
                    :class="selectedDisciplina === disc.id_disciplina ? 'bg-white' : 'bg-white border border-slate-200'"
                  >
                    <DisciplineIcon
                      :name="disc.nombre_disciplina"
                      class="w-3.5 h-3.5"
                      :class="selectedDisciplina === disc.id_disciplina ? 'text-slate-900' : 'text-slate-500'"
                    />
                  </span>
                  <span class="truncate">{{ disc.nombre_disciplina }}</span>
                  <svg
                    v-if="selectedDisciplina === disc.id_disciplina"
                    class="w-3 h-3 shrink-0 ml-auto text-white/70"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                </button>
              </div>

              <!-- CONTENEDOR DE LA GRÁFICA Y DETALLES -->
              <div class="flex-1 w-full flex flex-col">
                <div class="h-[280px] w-full" v-if="chartOcupacionEspacio">
                  <BaseChart type="bar" :data="chartOcupacionEspacio" :options="optionsOcupacionEspacio" />
                </div>
                <div v-else class="h-[280px] flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
                  Sin datos de uso para espacios físicos en este periodo
                </div>

                <!-- Desglose de Totales y Métricas Individuales de Espacios Físicos -->
                <div class="mt-6 border-t border-surface-100 pt-4" v-if="chartOcupacionEspacio && individualOcupacionEspacio.length">
                  <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                    <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Detalle por Espacio</span>
                    <span class="text-xs font-bold text-surface-500 bg-surface-50 px-3 py-1 rounded-full border border-surface-100 shadow-sm">
                      Total Usos en Canchas: <span class="text-surface-900 font-black">{{ totalOcupacionEspacio }}</span>
                    </span>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-[160px] overflow-y-auto scrollbar-thin pr-1">
                    <div v-for="(val, idx) in individualOcupacionEspacio" :key="idx" 
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

        </div>
      </div>
    </div>

  </div>
</template>