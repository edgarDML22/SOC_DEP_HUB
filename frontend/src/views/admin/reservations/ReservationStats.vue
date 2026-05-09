<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useReservacionAdminStore } from "@/stores/admin/reservationAdminStore";
import Chart from "primevue/chart";

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
        legend: { display: false },
        tooltip: { bodyFont: { family: 'sans-serif' }, titleFont: { family: 'sans-serif' } }
    },
    scales: {
        y: { 
            beginAtZero: true, 
            ticks: { precision: 0, color: '#64748b', font: { family: 'sans-serif' } }, 
            grid: { color: '#f1f5f9' }, 
            title: { display: true, text: 'Cantidad', font: { size: 11, weight: '600', family: 'sans-serif' }, color: '#475569' } 
        },
        x: { 
            grid: { display: false }, 
            ticks: { color: '#64748b', font: { family: 'sans-serif' } } 
        }
    }
};

const kpi2Data = computed(() => {
    const rawStats = store.statsCache[filtroStats.value]?.kpi2_por_estatus;
    const labels = rawStats?.labels || [];
    const data = rawStats?.data || [];

    const colorsMap = {
        'ACTIVA': '#22c55e',
        'CANCELADA': '#ef4444',
        'FINALIZADA': '#64748b',
        'NO_SHOW': '#f97316',
        'NO SHOW': '#f97316',
        'PENDIENTE': '#3b82f6'
    };

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
            backgroundColor: labels.map(label => colorsMap[label?.toUpperCase().replace(' ', '_')] || '#cbd5e1'),
            borderWidth: 0,
            hoverOffset: 15
        }]
    };
});

const kpi3Data = computed(() => {
    const rawStats = store.statsCache[filtroStats.value]?.kpi3_composicion_acompanantes;
    const labels = rawStats?.labels || [];
    const data = rawStats?.data || [];
    
    const pieColors = ['#8b5cf6', '#ec4899', '#14b8a6', '#f59e0b', '#06b6d4'];

    return {
        labels: labels.map(l => l?.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())),
        datasets: [{ 
            data: data, 
            backgroundColor: data.map((_, i) => pieColors[i % pieColors.length]),
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
                font: { size: 12, family: 'sans-serif' } 
            } 
        },
        tooltip: {
            bodyFont: { family: 'sans-serif' },
            titleFont: { family: 'sans-serif' }
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
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-2xl font-black text-slate-800 m-0 tracking-tight">Estadísticas y BI</h2>
                <p class="text-slate-500 m-0 mt-1 font-medium text-sm">Visualización de métricas y rendimiento de reservas</p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="flex bg-slate-50 border border-slate-200 rounded-xl p-1 shadow-inner">
                    <button v-for="r in ['hoy', 'semana', 'mes']" :key="r" 
                        @click="filtroStats = r"
                        :disabled="store.loading.stats"
                        class="px-4 py-2 rounded-lg text-xs font-bold transition-all capitalize disabled:opacity-50 focus:outline-none"
                        :class="filtroStats === r ? 'bg-white text-slate-800 shadow-md border border-slate-100' : 'text-slate-500 hover:text-slate-700'">
                        {{ r === 'hoy' ? 'Hoy' : (r === 'semana' ? 'Esta Semana' : 'Este Mes') }}
                    </button>
                </div>
                <button @click="store.fetchStats(filtroStats, false, true)" 
                    :disabled="store.loading.stats"
                    class="p-2.5 rounded-xl bg-white border border-slate-200 shadow-sm hover:bg-slate-50 transition-all active:scale-95 disabled:opacity-50 text-slate-500 hover:text-slate-700 focus:outline-none">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
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
                <div v-if="store.loading.stats" class="absolute inset-0 z-50 flex items-center justify-center bg-white/50 backdrop-blur-[2px] rounded-3xl">
                    <div class="bg-white p-8 rounded-3xl shadow-xl flex flex-col items-center gap-4 border border-slate-100">
                        <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                        <div class="text-center">
                            <p class="text-xs font-black text-slate-800 uppercase tracking-widest m-0">Actualizando</p>
                            <p class="text-[10px] font-bold text-slate-400 m-0 mt-1">Sincronizando métricas...</p>
                        </div>
                    </div>
                </div>
            </Transition>

            <div class="space-y-8" :class="{'pointer-events-none': store.loading.stats}">
                <!-- Gráfica Principal: Barras (Reservas) -->
                <div class="bg-white p-6 md:p-8 rounded-5xl border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-1 bg-linear-to-r from-blue-500 to-blue-400"></div>
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 m-0 tracking-tight">Evolución de Reservas</h3>
                            <p class="text-slate-500 text-sm mt-1 font-medium">Flujo de reservaciones en el periodo seleccionado</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                    </div>
                    
                    <div class="h-[300px] w-full" v-if="kpi1Data.datasets[0].data.length > 0">
                        <Chart type="bar" :data="kpi1Data" :options="chartOptionsBar" class="h-full w-full" />
                    </div>
                    <div v-else class="h-[300px] flex items-center justify-center text-slate-400 font-medium bg-slate-50 rounded-2xl border border-slate-100 border-dashed">
                        No hay datos de evolución para este periodo
                    </div>
                </div>

                <!-- Gráficas Secundarias: Estatus y Acompañantes -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Doughnut: Estatus -->
                    <div class="bg-white p-6 md:p-8 rounded-5xl border border-slate-100 shadow-sm relative overflow-hidden group flex flex-col">
                        <div class="absolute top-0 left-0 w-full h-1 bg-linear-to-r from-emerald-500 to-emerald-400"></div>
                        <div class="mb-6">
                            <h3 class="text-lg font-black text-slate-800 m-0 tracking-tight">Distribución por Estatus</h3>
                            <p class="text-slate-500 text-sm mt-1 font-medium">Proporción del estado de las reservas</p>
                        </div>
                        <div class="h-[280px] flex items-center justify-center flex-1" v-if="kpi2Data.datasets[0].data.length > 0">
                            <Chart type="doughnut" :data="kpi2Data" :options="chartOptionsPie" :plugins="[pieLabelsPlugin]" class="w-full h-full max-h-[250px]" />
                        </div>
                        <div v-else class="h-[280px] flex items-center justify-center text-slate-400 font-medium bg-slate-50 rounded-2xl border border-slate-100 border-dashed mt-4">
                            No hay datos de estatus
                        </div>
                    </div>

                    <!-- Pie: Composición de Acompañantes -->
                    <div class="bg-white p-6 md:p-8 rounded-5xl border border-slate-100 shadow-sm relative overflow-hidden group flex flex-col">
                        <div class="absolute top-0 left-0 w-full h-1 bg-linear-to-r from-purple-500 to-purple-400"></div>
                        <div class="mb-6">
                            <h3 class="text-lg font-black text-slate-800 m-0 tracking-tight">Composición de Acompañantes</h3>
                            <p class="text-slate-500 text-sm mt-1 font-medium">Tipos de visitantes en reservas acompañadas</p>
                        </div>
                        <div class="h-[280px] flex items-center justify-center flex-1" v-if="kpi3Data.datasets[0].data.length > 0">
                            <Chart type="pie" :data="kpi3Data" :options="chartOptionsPie" :plugins="[pieLabelsPlugin]" class="w-full h-full max-h-[250px]" />
                        </div>
                        <div v-else class="h-[280px] flex items-center justify-center text-slate-400 font-medium bg-slate-50 rounded-2xl border border-slate-100 border-dashed mt-4">
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
