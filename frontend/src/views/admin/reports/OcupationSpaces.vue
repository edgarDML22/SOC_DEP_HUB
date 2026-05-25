<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import api from '@/services/api';
import BaseChart from '@/components/admin/BaseChart.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import { IconFilter, IconCalendar } from '@/components/icons';
import { useReportsBIStore } from '@/stores/admin/reportsBIStore';

const reportsStore = useReportsBIStore();

const isLoading = ref(!reportsStore.spacesStats);
const errorMsg = ref('');
const stats = computed(() => reportsStore.spacesStats);

const spacesList = ref([]);

// Rango dinámico y filtros
const filtroRango = ref(reportsStore.spacesFilters.rango || 'semana');
const filterDateStart = ref(reportsStore.spacesFilters.fecha_inicio);
const filterDateEnd = ref(reportsStore.spacesFilters.fecha_fin);
const selectedSpace = ref(reportsStore.spacesFilters.id_espacio);

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
    reportsStore.spacesFilters.id_espacio === selectedSpace.value;

  if (isSameFilters && !forceRefresh) {
    return;
  }

  try {
    isLoading.value = true;
    errorMsg.value = '';
    await reportsStore.fetchSpacesStats({
      fecha_inicio: filterDateStart.value,
      fecha_fin: filterDateEnd.value,
      id_espacio: selectedSpace.value,
      rango: filtroRango.value || undefined
    }, forceRefresh);
  } catch (error) {
    console.error('Error loading spaces stats:', error);
    errorMsg.value = 'Imposible conectar con el servicio analítico de infraestructura.';
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
  await loadSpacesList();
  if (reportsStore.spacesStats) {
    filtroRango.value = reportsStore.spacesFilters.rango;
    filterDateStart.value = reportsStore.spacesFilters.fecha_inicio;
    filterDateEnd.value = reportsStore.spacesFilters.fecha_fin;
    selectedSpace.value = reportsStore.spacesFilters.id_espacio;
    loadStats();
  } else {
    updateDatesFromRango(filtroRango.value);
    await loadStats();
  }
});

watch(selectedSpace, loadStats);

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

// 2. Ocupación por Espacio (Doughnut)
const chartOcupacionTipo = computed(() => {
  if (!stats.value?.ocupacion_por_tipo?.labels?.length) return null;
  return {
    labels: stats.value.ocupacion_por_tipo.labels,
    datasets: [{
      data: stats.value.ocupacion_por_tipo.data,
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        const colors = ['#6366f1', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#8b5cf6', '#f43f5e', '#64748b'];
        if (!chartArea) return colors[context.dataIndex % colors.length];

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

const optionsOcupacionTipo = {
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        boxWidth: 10,
        padding: 14,
        font: { family: 'Helvetica, Arial, sans-serif', size: 11, weight: 'bold' },
        color: '#334155'
      }
    }
  }
};
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
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        
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

        <!-- Espacio Físico -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Espacio Físico</label>
          <div class="relative">
            <IconFilter class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <select v-model="selectedSpace"
                    class="w-full pl-10 pr-8 py-2 bg-white border border-surface-200 rounded-xl text-xs font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
              <option value="">Todos los espacios</option>
              <option v-for="space in spacesList" :key="space.id_espacio" :value="space.id_espacio">{{ space.nombre_espacio }}</option>
            </select>
          </div>
        </div>

      </div>
    </div>

    <!-- CARGANDO / ERROR -->
    <div class="relative min-h-96 flex items-center justify-center">
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white">
        <LoadingSpinner />
        <span class="text-sm font-bold text-surface-500 mt-4">Analizando saturación de instalaciones…</span>
      </div>

      <div v-else-if="errorMsg" class="p-8 text-center bg-red-50 border border-red-200 rounded-3xl text-red-700 font-semibold w-full">
        {{ errorMsg }}
      </div>

      <!-- REPORTES GRÁFICOS -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8 w-full">
        
        <!-- Gráfico 1: Saturación Horaria Heatmap (Bubble) -->
        <div class="lg:col-span-2 bg-white border border-surface-200 rounded-3xl p-6 lg:p-8 flex flex-col h-[400px]">
          <div class="mb-4">
            <h3 class="text-base font-black text-surface-900 leading-tight">Mapa de Saturación Horaria General</h3>
            <p class="text-xs font-medium text-surface-400 mt-0.5">Cruza horas y días de afluencia para detectar horarios pico y canchas subutilizadas.</p>
          </div>
          <div class="flex-1 min-h-0">
            <BaseChart v-if="chartSaturacion" type="bubble" :data="chartSaturacion" :options="optionsSaturacion" />
            <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
              Sin datos de saturación disponibles en este periodo
            </div>
          </div>
        </div>

        <!-- Gráfico 2: Proporción de Ocupación por Tipo de Cancha -->
        <div class="bg-white border border-surface-200 rounded-3xl p-6 lg:p-8 flex flex-col h-[400px]">
          <div class="mb-4">
            <h3 class="text-base font-black text-surface-900 leading-tight">Uso de Instalaciones Físicas</h3>
            <p class="text-xs font-medium text-surface-400 mt-0.5">Distribución proporcional de ocupación por tipo de área deportiva.</p>
          </div>
          <div class="flex-1 min-h-0 flex items-center justify-center">
            <BaseChart v-if="chartOcupacionTipo" type="doughnut" :data="chartOcupacionTipo" :options="optionsOcupacionTipo" />
            <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
              Sin datos de ocupación para este periodo
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>