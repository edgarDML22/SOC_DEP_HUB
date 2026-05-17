<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useReservacionAdminStore } from "@/stores/admin/reservationAdminStore";
import Chart from "primevue/chart";
import LoadingSpinner from "@/components/gerente/ui/LoadingSpinner.vue";

const store = useReservacionAdminStore();
const filtroStats = ref("hoy");

watch(filtroStats, (newVal) => {
    store.fetchStats(newVal);
});

const pieLabelsPlugin = {
    id: 'pieLabels',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        chart.data.datasets.forEach((dataset, i) => {
            const meta = chart.getDatasetMeta(i);
            if (!meta.visible) return;
            
            const total = dataset.data.reduce((a, b) => a + b, 0);
            if (total <= 0) return;

            meta.data.forEach((element, index) => {
                const value = dataset.data[index];
                if (value <= 0) return;
                
                const percentage = Math.round((value / total) * 100) + '%';
                
                const { x, y } = element.getCenterPoint();
                
                ctx.save();
                ctx.fillStyle = 'white';
                ctx.font = 'bold 12px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                
                ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
                ctx.shadowBlur = 3;
                ctx.shadowOffsetX = 1;
                ctx.shadowOffsetY = 1;
                
                ctx.fillText(percentage, x, y);
                ctx.restore();
            });
        });
    }
};

const kpi1Data = computed(() => {
    const rawStats = store.statsCache[filtroStats.value]?.kpi1_reservas_por_dia;
    const rawLabels = rawStats?.labels || [];
    const data = rawStats?.data || [];

    const labels = rawLabels.map(label => {
        if (filtroStats.value === 'hoy') {
            return `${label}:00h`;
        } else if (typeof label === 'string' && label.includes('-')) {
            const parts = label.split('-');
            if (parts.length === 3) {
                const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                return `${parts[2]} ${meses[parseInt(parts[1]) - 1]}`;
            }
        }
        return label;
    });

    return {
        labels,
        datasets: [
            {
                label: 'Reservas',
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

const chartOptionsBar = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { 
        legend: { display: false }
    },
    scales: {
        y: { 
            beginAtZero: true, 
            ticks: { precision: 0, color: '#64748b' }, 
            grid: { color: '#f1f5f9' }, 
            title: { display: true, text: 'Cantidad', font: { size: 11, weight: '600' }, color: '#475569' } 
        },
        x: { 
            grid: { display: false }, 
            ticks: { color: '#64748b' } 
        }
    }
};

const kpi2Data = computed(() => {
    const rawStats = store.statsCache[filtroStats.value]?.kpi2_por_estatus;
    const labels = rawStats?.labels || [];
    const data = rawStats?.data || [];

    return {
        labels: labels.map(l => {
            const map = {
                'ACTIVA': 'Activa',
                'PENDIENTE': 'Pendiente',
                'FINALIZADA': 'Finalizada',
                'CANCELADA': 'Cancelada',
                'NO_SHOW': 'No Show'
            };
            return map[l?.toUpperCase()] || l;
        }),
        datasets: [{ 
            data: data, 
            backgroundColor: (context) => {
                const { ctx, chartArea } = context.chart;
                if (!chartArea) return '#cbd5e1';

                const colorsMap = {
                    'ACTIVA': ['#22c55e', '#15803d'],
                    'PENDIENTE': ['#3b82f6', '#1d4ed8'],
                    'FINALIZADA': ['#64748b', '#334155'],
                    'CANCELADA': ['#ef4444', '#b91c1c'],
                    'NO_SHOW': ['#f97316', '#c2410c'],
                    'NO SHOW': ['#f97316', '#c2410c']
                };

                const label = labels[context.dataIndex]?.toUpperCase().replace(' ', '_');
                const pair = colorsMap[label] || ['#cbd5e1', '#94a3b8'];
                
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

const kpi3Data = computed(() => {
    const rawStats = store.statsCache[filtroStats.value]?.kpi3_composicion_acompanantes;
    const labels = rawStats?.labels || [];
    const data = rawStats?.data || [];
    
    return {
        labels: labels.map(l => l?.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())),
        datasets: [{ 
            data: data, 
            backgroundColor: (context) => {
                const { ctx, chartArea } = context.chart;
                if (!chartArea) return '#cbd5e1';

                const pieGradients = [
                    ['#8b5cf6', '#5b21b6'], // Violet
                    ['#ec4899', '#9d174d'], // Pink
                    ['#14b8a6', '#0f766e'], // Teal
                    ['#f59e0b', '#b45309'], // Amber
                    ['#06b6d4', '#0369a1']  // Cyan
                ];

                const pair = pieGradients[context.dataIndex % pieGradients.length];
                
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

const chartOptionsPie = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { 
        legend: { 
            position: 'right', 
            labels: { 
                usePointStyle: true, 
                color: '#475569', 
                font: { size: 12 } 
            } 
        }
    }
};

onMounted(() => {
    store.fetchStats(filtroStats.value, true);
});
</script>

<template>
    <div class="space-y-8 relative">
        <!-- Header & Segmented Control -->
        <div class="flex items-center justify-between flex-wrap gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-black text-surface-900 m-0 tracking-tight">Estadísticas Reservas</h1>
                <p class="text-surface-500 m-0 mt-1 font-medium text-sm">Visualización de métricas y rendimiento de reservas</p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="flex bg-white shadow-sm border border-surface-200 rounded-xl p-1">
                    <button v-for="r in ['hoy', 'semana', 'mes']" :key="r" 
                        @click="filtroStats = r"
                        :disabled="store.loading.stats"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all capitalize disabled:opacity-50"
                        :class="filtroStats === r ? 'bg-surface-900 text-white shadow-md' : 'text-surface-500 hover:bg-surface-50'">
                        {{ r === 'hoy' ? 'Hoy' : (r === 'semana' ? 'Semana' : 'Mes') }}
                    </button>
                </div>
                <button @click="store.fetchStats(filtroStats, false, true)" 
                    :disabled="store.loading.stats"
                    class="p-2.5 rounded-xl bg-white border border-surface-200 shadow-sm hover:bg-surface-50 transition-all active:rotate-180 disabled:opacity-50">
                    <svg class="w-4 h-4 text-surface-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M21 2v6h-6M3 12a9 9 0 0115-6.7L21 8M3 22v-6h6M21 12a9 9 0 01-15 6.7L3 16"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="relative min-h-[400px]">
            <!-- Overlay de Carga -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="store.loading.stats" class="absolute inset-0 z-50 flex items-center justify-center bg-white/40 backdrop-blur-[2px] rounded-[2.5rem]">
                    <div class="bg-white p-8 rounded-3xl shadow-2xl flex flex-col items-center gap-4 border border-surface-100">
                        <LoadingSpinner />
                        <div class="text-center">
                            <p class="text-xs font-black text-surface-900 uppercase tracking-[0.2em] m-0">Sincronizando</p>
                            <p class="text-[10px] font-bold text-surface-400 m-0 mt-1">Obteniendo analíticas frescas...</p>
                        </div>
                    </div>
                </div>
            </Transition>

            <div class="space-y-8" :class="{'pointer-events-none': store.loading.stats}">
                <!-- Gráfica Principal: Barras (Reservas) -->
                <div class="bg-white p-8 rounded-[2.5rem] border border-surface-200 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-surface-900 m-0 tracking-tight">Evolución de Reservas</h3>
                            <p class="text-surface-500 text-sm mt-1 font-medium">Flujo de reservaciones en el periodo seleccionado</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                    </div>
                    
                    <div class="h-[300px] w-full" v-if="kpi1Data.datasets[0].data.some(val => Number(val) > 0)">
                        <Chart type="bar" :data="kpi1Data" :options="chartOptionsBar" class="h-full w-full" />
                    </div>
                    <div v-else class="h-[300px] flex items-center justify-center text-surface-400 font-medium bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
                        No hay datos de evolución para este periodo
                    </div>
                </div>

                <!-- Gráficas Secundarias: Estatus y Acompañantes -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Doughnut: Estatus -->
                    <div class="bg-white p-8 rounded-[2.5rem] border border-surface-200 shadow-sm flex flex-col">
                        <div class="mb-6">
                            <h3 class="text-lg font-black text-surface-900 m-0 tracking-tight">Distribución por Estatus</h3>
                            <p class="text-surface-500 text-sm mt-1 font-medium">Proporción del estado de las reservas</p>
                        </div>
                        <div class="h-[280px] flex items-center justify-center flex-1" v-if="kpi2Data.datasets[0].data.some(val => Number(val) > 0)">
                            <Chart type="doughnut" :data="kpi2Data" :options="chartOptionsPie" :plugins="[pieLabelsPlugin]" class="w-full h-full max-h-[250px]" />
                        </div>
                        <div v-else class="h-[280px] flex items-center justify-center text-surface-400 font-medium bg-surface-50 rounded-2xl border border-surface-200 border-dashed mt-4">
                            No hay datos de estatus
                        </div>
                    </div>

                    <!-- Pie: Composición de Acompañantes -->
                    <div class="bg-white p-8 rounded-[2.5rem] border border-surface-200 shadow-sm flex flex-col">
                        <div class="mb-6">
                            <h3 class="text-lg font-black text-surface-900 m-0 tracking-tight">Composición de Acompañantes</h3>
                            <p class="text-surface-500 text-sm mt-1 font-medium">Tipos de visitantes en reservas acompañadas</p>
                        </div>
                        <div class="h-[280px] flex items-center justify-center flex-1" v-if="kpi3Data.datasets[0].data.some(val => Number(val) > 0)">
                            <Chart type="pie" :data="kpi3Data" :options="chartOptionsPie" :plugins="[pieLabelsPlugin]" class="w-full h-full max-h-[250px]" />
                        </div>
                        <div v-else class="h-[280px] flex items-center justify-center text-surface-400 font-medium bg-surface-50 rounded-2xl border border-surface-200 border-dashed mt-4">
                            No hay acompañantes registrados
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
