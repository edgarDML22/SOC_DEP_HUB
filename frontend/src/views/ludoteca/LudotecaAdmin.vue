<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useAdminLudotecaStore } from "@/stores/ludoteca/adminLudotecaStore";
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';

import Chart    from "primevue/chart";
import { IconBaby } from '@/components/icons';

// Store
const store = useAdminLudotecaStore();

// Filtro de Estadísticas
const filtroStats = ref("hoy");

watch(filtroStats, (newVal) => {
    store.fetchStats(newVal);
});

const currentStats = computed(() => store.statsCache[filtroStats.value] || { kpis: {}, graficas: {} });

// Configuración de gráficas
const afluenciaData = computed(() => {
    let labels = currentStats.value.graficas?.afluencia_temporal?.labels || [];
    let data = currentStats.value.graficas?.afluencia_temporal?.data || [];

    if (filtroStats.value === 'hoy') {
        const fullLabels = Array.from({ length: 24 }, (_, i) => `${String(i).padStart(2, '0')}:00`);
        const fullData = Array(24).fill(0);

        labels.forEach((label, index) => {
            const hourStr = label.toString().includes(':') ? label.split(':')[0] : label;
            const hour = parseInt(hourStr);
            if (!isNaN(hour) && hour >= 0 && hour < 24) {
                fullData[hour] = data[index];
            }
        });

        labels = fullLabels;
        data = fullData;
    }

    return {
        labels,
        datasets: [
            {
                label: 'Ingresos',
                data: data,
                backgroundColor: (context) => {
                    const chart = context.chart;
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return '#3b82f6';
                    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                    gradient.addColorStop(0, '#2563eb');
                    gradient.addColorStop(1, '#60a5fa');
                    return gradient;
                },
                borderRadius: 8,
                borderWidth: 1,
                borderColor: '#1d4ed8',
            }
        ]
    };
});

const calificacionesData = computed(() => {
    const labels = currentStats.value.graficas?.calificaciones?.labels || [];
    const data = currentStats.value.graficas?.calificaciones?.data || [];
    
    return {
        labels: labels.map(l => `${l} Estrellas`),
        datasets: [{ 
            data: data, 
            backgroundColor: (context) => {
                const { ctx, chartArea } = context.chart;
                if (!chartArea) return '#cbd5e1';

                const colorsMap = { 
                    '1': ['#ef4444', '#b91c1c'], 
                    '2': ['#f97316', '#c2410c'], 
                    '3': ['#eab308', '#a16207'], 
                    '4': ['#84cc16', '#4d7c0f'], 
                    '5': ['#22c55e', '#15803d'] 
                };

                const rawLabel = labels[context.dataIndex];
                const l = String(rawLabel || '').split(' ')[0]; 
                const pair = colorsMap[l] || ['#cbd5e1', '#94a3b8'];
                
                const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                gradient.addColorStop(0, pair[1]);
                gradient.addColorStop(1, pair[0]);
                return gradient;
            },
            borderWidth: 0,
            hoverOffset: 15
        }]
    };
});

const chartOptionsBar = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' }, grid: { color: '#f1f5f9' }, title: { display: true, text: 'Ingresos (Niños)', font: { size: 11, weight: '600' }, color: '#475569' } },
        x: { grid: { display: false }, ticks: { color: '#64748b' } }
    }
};

const chartOptionsPie = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'right', labels: { usePointStyle: true } } }
};

const tiempoUsoData = computed(() => {
    let labels = currentStats.value.graficas?.tiempo_uso?.labels || [];
    let data = currentStats.value.graficas?.tiempo_uso?.data || [];

    if (filtroStats.value === 'hoy') {
        const fullLabels = Array.from({ length: 24 }, (_, i) => `${String(i).padStart(2, '0')}:00`);
        const fullData = Array(24).fill(0);

        labels.forEach((label, index) => {
            const hourStr = label.toString().includes(':') ? label.split(':')[0] : label;
            const hour = parseInt(hourStr);
            if (!isNaN(hour) && hour >= 0 && hour < 24) {
                fullData[hour] = data[index];
            }
        });

        labels = fullLabels;
        data = fullData;
    }

    return {
        labels,
        datasets: [
            {
                label: 'Tiempo Promedio (min)',
                data: data,
                backgroundColor: (context) => {
                    const chart = context.chart;
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return '#8b5cf6';
                    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                    gradient.addColorStop(0, '#7c3aed');
                    gradient.addColorStop(1, '#a78bfa');
                    return gradient;
                },
                borderRadius: 8,
                borderWidth: 1,
                borderColor: '#6d28d9',
            }
        ]
    };
});

const chartOptionsLine = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' }, grid: { color: '#f1f5f9' }, title: { display: true, text: 'Minutos', font: { size: 11, weight: '600' }, color: '#475569' } },
        x: { grid: { display: false }, ticks: { color: '#64748b' } }
    }
};

const totalEncuestas = computed(() => {
    const data = currentStats.value.graficas?.calificaciones?.data || [];
    return data.reduce((a, b) => a + Number(b), 0);
});

const hasAfluenciaData = computed(() => {
    const raw = currentStats.value.graficas?.afluencia_temporal?.data || [];
    return raw.length > 0 && raw.some(val => Number(val) > 0);
});

const hasCalificacionesData = computed(() => {
    const raw = currentStats.value.graficas?.calificaciones?.data || [];
    return raw.length > 0 && raw.some(val => Number(val) > 0);
});

const hasTiempoUsoData = computed(() => {
    const raw = currentStats.value.graficas?.tiempo_uso?.data || [];
    return raw.length > 0 && raw.some(val => Number(val) > 0);
});

onMounted(async () => {
    await store.fetchStats(filtroStats.value);
});
</script>

<template>
  <div class="space-y-8 animate-fade-in">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-2xl font-black text-surface-900 m-0 tracking-tight">Estadísticas</h2>
        <p class="text-surface-500 m-0 mt-1 font-medium text-sm">Analíticas en tiempo real y métricas de servicio</p>
      </div>
      
      <div class="flex items-center gap-3">
        <div class="flex bg-white shadow-sm border border-surface-200 rounded-xl p-1">
          <button v-for="r in ['hoy', 'semana', 'mes']" :key="r" 
            @click="filtroStats = r"
            :disabled="store.loading.stats"
            class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all capitalize disabled:opacity-50"
            :class="filtroStats === r ? 'bg-surface-900 text-white shadow-md' : 'text-surface-500 hover:bg-surface-50 cursor-pointer'">
            {{ r }}
          </button>
        </div>
        <button @click="store.fetchStats(filtroStats, false, true)" 
          :disabled="store.loading.stats"
          class="p-2.5 rounded-xl bg-white border border-surface-200 shadow-sm hover:bg-surface-50 transition-all active:rotate-180 disabled:opacity-50 cursor-pointer">
          <svg class="w-4 h-4 text-surface-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M21 2v6h-6M3 12a9 9 0 0115-6.7L21 8M3 22v-6h6M21 12a9 9 0 01-15 6.7L3 16"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="relative min-h-[500px]">
      <!-- Overlay de Carga -->
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div v-if="store.loading.stats" class="absolute inset-0 z-50 flex items-center justify-center bg-white/40 backdrop-blur-[2px] rounded-3xl">
          <div class="bg-white p-8 rounded-3xl shadow-2xl flex flex-col items-center gap-4 border border-surface-100">
            <LoadingSpinner />
            <div class="text-center">
              <p class="text-xs font-black text-surface-900 uppercase tracking-[0.2em] m-0">Sincronizando</p>
              <p class="text-[10px] font-bold text-surface-400 m-0 mt-1">Obteniendo analíticas frescas...</p>
            </div>
          </div>
        </div>
      </Transition>

      <div class="space-y-6" :class="{'pointer-events-none': store.loading.stats}">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <div class="group bg-white p-6 rounded-3xl border border-surface-200 shadow-sm hover:shadow-lg transition-all relative overflow-hidden">
              <div class="relative flex items-center gap-4">
                  <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-inner group-hover:scale-110 transition-transform">
                      <IconBaby class="w-7 h-7" />
                  </div>
                  <div>
                      <p class="text-[10px] uppercase font-bold tracking-widest text-surface-400 m-0">Número de Niños</p>
                      <h3 class="text-3xl font-black text-surface-900 m-0">{{ currentStats.kpis?.numero_ninos || 0 }}</h3>
                  </div>
              </div>
          </div>

          <div class="group bg-white p-6 rounded-3xl border border-surface-200 shadow-sm hover:shadow-lg transition-all relative overflow-hidden">
              <div class="relative flex items-center gap-4">
                  <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 shadow-inner group-hover:scale-110 transition-transform">
                      <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                      </svg>
                  </div>
                  <div>
                      <p class="text-[10px] uppercase font-bold tracking-widest text-surface-400 m-0">Calificación Promedio</p>
                      <h3 class="text-3xl font-black text-surface-900 m-0">{{ currentStats.kpis?.calificacion_promedio || 0 }} <span class="text-sm font-medium text-surface-300">/ 5</span></h3>
                  </div>
              </div>
          </div>

          <div class="group bg-white p-6 rounded-3xl border border-surface-200 shadow-sm hover:shadow-lg transition-all relative overflow-hidden">
              <div class="relative flex items-center gap-4">
                  <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center text-red-600 shadow-inner group-hover:scale-110 transition-transform">
                      <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                      </svg>
                  </div>
                  <div>
                      <p class="text-[10px] uppercase font-bold tracking-widest text-surface-400 m-0">Total Incidencias</p>
                      <h3 class="text-3xl font-black text-surface-900 m-0">{{ currentStats.kpis?.total_incidencias || 0 }}</h3>
                  </div>
              </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-white p-6 rounded-3xl border border-surface-200 shadow-sm">
            <h3 class="text-base font-black text-surface-900 mb-6">Afluencia Temporal</h3>
            <div class="h-[280px]" v-if="hasAfluenciaData">
              <Chart type="bar" :data="afluenciaData" :options="chartOptionsBar" class="h-full" />
            </div>
            <div v-else class="h-[280px] flex items-center justify-center text-surface-400 font-medium bg-surface-50 rounded-2xl border border-surface-200 border-dashed text-sm">
              No hay registros de afluencia para este periodo
            </div>
          </div>
          <div class="bg-white p-6 rounded-3xl border border-surface-200 shadow-sm flex flex-col justify-between">
            <div>
              <h3 class="text-base font-black text-surface-900 mb-6">Distribución de Calificaciones</h3>
              <div class="h-[280px] flex items-center justify-center" v-if="hasCalificacionesData">
                <Chart type="doughnut" :data="calificacionesData" :options="chartOptionsPie" class="w-full max-w-[240px]" />
              </div>
              <div v-else class="h-[280px] flex items-center justify-center text-surface-400 font-medium bg-surface-50 rounded-2xl border border-surface-200 border-dashed text-sm">
                No hay calificaciones registradas para este periodo
              </div>
            </div>
            <div class="mt-4 text-center">
              <p class="text-xs font-bold text-surface-500 m-0 bg-surface-50 inline-block px-4 py-1.5 rounded-full border border-surface-100 shadow-sm">Total encuestas: <span class="text-surface-900">{{ totalEncuestas }}</span></p>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pb-6">
          <div class="bg-white p-6 rounded-3xl border border-surface-200 shadow-sm">
            <h3 class="text-base font-black text-surface-900 mb-6">Tiempo de Uso Promedio</h3>
            <div class="h-[280px]" v-if="hasTiempoUsoData">
              <Chart type="bar" :data="tiempoUsoData" :options="chartOptionsLine" class="h-full" />
            </div>
            <div v-else class="h-[280px] flex items-center justify-center text-surface-400 font-medium bg-surface-50 rounded-2xl border border-surface-200 border-dashed text-sm">
              No hay registros de tiempo de uso para este periodo
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>