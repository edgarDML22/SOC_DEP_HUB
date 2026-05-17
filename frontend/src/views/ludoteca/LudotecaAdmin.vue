<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { useAdminLudotecaStore } from "@/stores/ludoteca/adminLudotecaStore";
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';

import Card     from "primevue/card";
import Select   from "primevue/select";
import DatePicker from "primevue/datepicker";
import Button   from "primevue/button";
import Tag      from "primevue/tag";
import Message  from "primevue/message";
import Chart    from "primevue/chart";
import { IconHome, IconCalendar, IconClock, IconUser, IconBell, IconBaby } from '@/components/icons';

// FullCalendar
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import esLocale from "@fullcalendar/core/locales/es";

// Store
const store = useAdminLudotecaStore();

// Navegación entre vistas
import { storeToRefs } from "pinia";
const { viewActive } = storeToRefs(store);


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
            // Extraer solo la hora si viene como "HH:mm:ss" o similar
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
    const bgColors = { '1': '#ef4444', '2': '#f97316', '3': '#eab308', '4': '#84cc16', '5': '#22c55e' };
    
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

// Configuración del Calendario Semanal
const calendarView = ref('timeGridWeek');
const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: calendarView.value,
    locale: esLocale,
    timeZone: 'America/Mexico_City',
    headerToolbar: false,
    allDaySlot: false,
    slotMinTime: '00:00:00',
    slotMaxTime: '24:00:00',
    scrollTime: '08:00:00',
    height: 520,
    expandRows: true,
    nowIndicator: true,
    dayHeaderFormat: { weekday: 'short', day: 'numeric', month: 'short', omitCommas: true },
    displayEventTime: false,
    slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    events: store.turnosAsignados.map(t => ({
        id: t.id_turno,
        title: t.instructor,
        start: `${t.fecha}T${t.hora_inicio}`,
        end: `${t.fecha}T${t.hora_fin}`,
    }))
}));

// Turnos de hoy filtrados
const hoyStr = computed(() => {
    return new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Mexico_City' }).format(new Date());
});
const turnosHoy = computed(() => {
    return store.turnosAsignados.filter(t => t.fecha === hoyStr.value);
});

// Formulario
const form = ref({ instructor: null, fecha: null, horaInicio: null, horaFin: null });
const conflictoMsg  = ref(null);
const submitSuccess = ref(false);

const horasInvalidas = computed(() => {
    if (!form.value.horaInicio || !form.value.horaFin) return false;
    return form.value.horaFin <= form.value.horaInicio;
});

// Auto-completar hora de fin (1 hora después del inicio)
watch(() => form.value.horaInicio, (newVal) => {
    if (newVal && !form.value.horaFin) {
        const end = new Date(newVal.getTime() + 60 * 60 * 1000); // +1 hora
        form.value.horaFin = end;
    }
});

const formValido = computed(() =>
    form.value.instructor && form.value.fecha && form.value.horaInicio && form.value.horaFin && !horasInvalidas.value
);

const toDateStr = (d) => {
    if (!d) return null;
    // Forzamos formato YYYY-MM-DD usando la zona horaria de CDMX
    return new Intl.DateTimeFormat('en-CA', { 
        timeZone: 'America/Mexico_City',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).format(d);
};

const toTimeStr = (d) => {
    if (!d) return null;
    const parts = new Intl.DateTimeFormat('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'America/Mexico_City' }).formatToParts(d);
    const get = (type) => parts.find(p => p.type === type)?.value ?? '00';
    return `${get('hour')}:${get('minute')}:${get('second')}`;
};

const handleSubmit = async () => {
    conflictoMsg.value  = null;
    submitSuccess.value = false;

    const result = await store.crearTurno({
        id_instructor: form.value.instructor.id_instructor,
        fecha:         toDateStr(form.value.fecha),
        hora_inicio:   toTimeStr(form.value.horaInicio),
        hora_fin:      toTimeStr(form.value.horaFin),
    });

    if (result.success) {
        submitSuccess.value = true;
        form.value.fecha      = null;
        form.value.horaInicio = null;
        form.value.horaFin    = null;
        setTimeout(() => (submitSuccess.value = false), 4000);
    } else if (result.conflicto) {
        conflictoMsg.value = `El instructor ya tiene una actividad programada en ese horario.`;
    }
};

onMounted(async () => {
    await Promise.all([store.fetchStats(filtroStats.value), store.fetchInstructores(), store.fetchTurnos()]);
});
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans pb-8 pt-4 md:pt-0">
    <div class="max-w-7xl mx-auto p-4 md:p-8">

      
      <Transition 
        enter-active-class="transition-all duration-500 ease-out"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-4"
        mode="out-in"
      >
        <!-- VISTA 1: DASHBOARD -->
        <div v-if="viewActive === 'dashboard'" key="dashboard" class="space-y-8">
          
          <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
              <h1 class="text-3xl font-black text-surface-900 m-0 tracking-tight">Panel Ludoteca</h1>
              <p class="text-surface-500 m-0 mt-1 font-medium">Analíticas en tiempo real y métricas de servicio</p>
            </div>
            
            <div class="flex items-center gap-3">
              <div class="flex bg-white shadow-sm border border-surface-200 rounded-xl p-1">
                <button v-for="r in ['hoy', 'semana', 'mes']" :key="r" 
                  @click="filtroStats = r"
                  :disabled="store.loading.stats"
                  class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all capitalize disabled:opacity-50"
                  :class="filtroStats === r ? 'bg-surface-900 text-white shadow-md' : 'text-surface-500 hover:bg-surface-50'">
                  {{ r }}
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

          <div class="relative min-h-[600px]">
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
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div class="group bg-white p-6 rounded-4xl border border-surface-200 shadow-sm hover:shadow-xl transition-all relative overflow-hidden">
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

            <div class="group bg-white p-6 rounded-4xl border border-surface-200 shadow-sm hover:shadow-xl transition-all relative overflow-hidden">
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

            <div class="group bg-white p-6 rounded-4xl border border-surface-200 shadow-sm hover:shadow-xl transition-all relative overflow-hidden">
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

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-8 rounded-[2.5rem] border border-surface-200 shadow-sm">
              <h3 class="text-xl font-black text-surface-900 mb-6">Afluencia Temporal</h3>
              <div class="h-[350px]" v-if="hasAfluenciaData">
                <Chart type="bar" :data="afluenciaData" :options="chartOptionsBar" class="h-full" />
              </div>
              <div v-else class="h-[350px] flex items-center justify-center text-surface-400 font-medium bg-surface-50 rounded-3xl border border-surface-200 border-dashed text-sm">
                No hay registros de afluencia para este periodo
              </div>
            </div>
            <div class="bg-white p-8 rounded-[2.5rem] border border-surface-200 shadow-sm flex flex-col justify-between">
              <div>
                <h3 class="text-xl font-black text-surface-900 mb-6">Distribución de Calificaciones</h3>
                <div class="h-[350px] flex items-center justify-center" v-if="hasCalificacionesData">
                  <Chart type="doughnut" :data="calificacionesData" :options="chartOptionsPie" class="w-full max-w-[300px]" />
                </div>
                <div v-else class="h-[350px] flex items-center justify-center text-surface-400 font-medium bg-surface-50 rounded-3xl border border-surface-200 border-dashed text-sm">
                  No hay calificaciones registradas para este periodo
                </div>
              </div>
              <div class="mt-4 text-center">
                <p class="text-sm font-bold text-surface-500 m-0 bg-surface-50 inline-block px-4 py-2 rounded-full border border-surface-100 shadow-sm">Total de encuestas hechas: <span class="text-surface-900">{{ totalEncuestas }}</span></p>
              </div>
            </div>
          </div>
          <div class="flex justify-center mt-8">
            <div class="bg-white p-8 rounded-[2.5rem] border border-surface-200 shadow-sm w-full lg:w-2/3">
              <h3 class="text-xl font-black text-surface-900 mb-6 text-center">Tiempo de Uso Promedio</h3>
              <div class="h-[350px]" v-if="hasTiempoUsoData">
                <Chart type="bar" :data="tiempoUsoData" :options="chartOptionsLine" class="h-full" />
              </div>
              <div v-else class="h-[350px] flex items-center justify-center text-surface-400 font-medium bg-surface-50 rounded-3xl border border-surface-200 border-dashed text-sm">
                No hay registros de tiempo de uso para este periodo
              </div>
            </div>
          </div>

            </div>
          </div>

          <div class="pt-8 pb-4">
          </div>
        </div>

        <!-- VISTA 2: GESTIÓN DE TURNOS -->
        <div v-else key="turnos" class="space-y-8">
          <div class="flex items-center justify-between">
            <button @click="viewActive = 'dashboard'" class="flex items-center gap-2 text-surface-500 hover:text-surface-900 font-bold transition-colors">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
              </svg>
              <span>Volver al Dashboard</span>
            </button>
            <h1 class="text-2xl font-black text-surface-900 m-0">Gestión de Turnos</h1>
          </div>

          <div class="bg-white rounded-[2.5rem] border border-surface-200 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-surface-100 flex items-center justify-between bg-surface-50/30">
              <div>
                <h2 class="text-2xl font-black text-surface-900 m-0">Asignar Nuevo Turno</h2>
                <p class="text-sm text-surface-400 m-0">Registra la jornada de un instructor en la ludoteca</p>
              </div>
              <div class="w-14 h-14 rounded-2xl bg-primary-600 text-white flex items-center justify-center shadow-lg shadow-primary-600/20">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/>
                </svg>
              </div>
            </div>

            <div class="p-10">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 items-end">
                <div class="space-y-2">
                  <label class="text-[10px] uppercase font-black text-surface-400 tracking-widest px-1">Instructor</label>
                  <Select v-model="form.instructor" :options="store.instructoresHabilitados" optionLabel="nombre_completo" placeholder="Seleccionar instructor..." class="w-full" panelClass="custom-instructor-select-panel rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.08)] border-surface-100 p-2">
                    <template #value="slotProps">
                      <div v-if="slotProps.value" class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-[10px]">
                          {{ slotProps.value.nombre_completo.charAt(0) }}
                        </div>
                        <span class="font-medium text-surface-800 tracking-tight">{{ slotProps.value.nombre_completo }}</span>
                      </div>
                      <span v-else class="text-surface-400 font-medium">{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                      <div class="flex items-center gap-3 py-2 px-2 group w-full">
                        <div class="w-8 h-8 rounded-full bg-surface-100 text-surface-500 flex items-center justify-center font-bold text-xs transition-colors group-hover:bg-primary-100 group-hover:text-primary-600">
                          {{ slotProps.option.nombre_completo.charAt(0) }}
                        </div>
                        <span class="font-medium text-surface-700 transition-colors group-hover:text-surface-900">{{ slotProps.option.nombre_completo }}</span>
                      </div>
                    </template>
                  </Select>
                </div>
                <div class="space-y-2">
                  <label class="text-[10px] uppercase font-black text-surface-400 tracking-widest px-1">Fecha</label>
                  <DatePicker v-model="form.fecha" dateFormat="yy-mm-dd" showIcon iconDisplay="input" placeholder="yyyy-mm-dd" class="w-full" :manualInput="false" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <label class="text-[10px] uppercase font-black text-surface-400 tracking-widest px-1">Inicio</label>
                    <DatePicker v-model="form.horaInicio" timeOnly hourFormat="24" placeholder="00:00" class="w-full" :manualInput="false" showOnFocus fluid />
                  </div>
                  <div class="space-y-2">
                    <label class="text-[10px] uppercase font-black text-surface-400 tracking-widest px-1">Fin</label>
                    <DatePicker v-model="form.horaFin" timeOnly hourFormat="24" placeholder="00:00" class="w-full" :manualInput="false" showOnFocus fluid />
                  </div>
                </div>
                <button @click="handleSubmit" :disabled="!formValido || store.loading.submit" class="h-[52px] rounded-2xl bg-primary-600 text-white font-bold hover:bg-primary-700 transition-all flex items-center justify-center gap-2 shadow-lg shadow-primary-600/20 disabled:opacity-40">
                  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                  </svg>
                  {{ store.loading.submit ? 'Asignando...' : 'Asignar Turno' }}
                </button>
              </div>
              <Transition 
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
              ><div v-if="horasInvalidas" class="mt-6 p-4 bg-red-50 text-red-700 rounded-2xl text-xs font-bold border border-red-100 flex items-center gap-2"><i class="pi pi-exclamation-circle text-lg"></i>La hora de fin debe ser posterior a la de inicio.</div></Transition>
              <Transition 
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
              >
                <div v-if="conflictoMsg" class="mt-6 p-4 bg-red-50 text-red-700 rounded-2xl text-sm font-bold border border-red-100 flex items-center justify-between shadow-sm">
                  <div class="flex items-center gap-3">
                    <i class="pi pi-times-circle text-xl text-red-500"></i>
                    <span class="font-sans tracking-tight">{{ conflictoMsg }}</span>
                  </div>
                  <button @click="conflictoMsg = null" class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-red-100 text-red-400 hover:text-red-700 transition-colors active:scale-95">
                    <i class="pi pi-times"></i>
                  </button>
                </div>
              </Transition>
              
              <Transition 
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
              >
                <div v-if="submitSuccess" class="mt-6 p-4 bg-green-50 text-green-700 rounded-2xl text-sm font-bold border border-green-100 flex items-center justify-between shadow-sm">
                  <div class="flex items-center gap-3">
                    <i class="pi pi-check-circle text-xl text-green-500"></i>
                    <span class="font-sans tracking-tight">Turno asignado con éxito.</span>
                  </div>
                  <button @click="submitSuccess = false" class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-green-100 text-green-400 hover:text-green-700 transition-colors active:scale-95">
                    <i class="pi pi-times"></i>
                  </button>
                </div>
              </Transition>
            </div>
          </div>

          <div class="bg-white rounded-[2.5rem] border border-surface-200 shadow-sm overflow-hidden">
            <div class="p-8 border-b border-surface-100 bg-surface-50/30 flex items-center justify-between">
              <div><h2 class="text-2xl font-black text-surface-900 m-0">Turnos Programados</h2><p class="text-sm text-surface-400 m-0">Gestión de cobertura de instructores</p></div>
              <div class="flex items-center bg-surface-100 rounded-xl p-1 border border-surface-200 shadow-inner">
                <button 
                  @click="calendarView = 'timeGridDay'"
                  class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all"
                  :class="calendarView === 'timeGridDay' ? 'bg-white text-surface-900 shadow-sm' : 'text-surface-500 hover:text-surface-700'"
                >Hoy</button>
                <button 
                  @click="calendarView = 'timeGridWeek'"
                  class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all"
                  :class="calendarView === 'timeGridWeek' ? 'bg-white text-surface-900 shadow-sm' : 'text-surface-500 hover:text-surface-700'"
                >Semana</button>
              </div>
            </div>
            <div class="p-8">
              <Transition name="fade" mode="out-in">
                <!-- Vista de Hoy: Tarjetas -->
                <div v-if="calendarView === 'timeGridDay'" key="day" class="min-h-[400px]">
                  <div v-if="turnosHoy.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="t in turnosHoy" :key="t.id_turno" 
                      class="p-6 bg-white rounded-3xl border border-surface-200 shadow-sm hover:shadow-xl hover:border-blue-200 transition-all group relative overflow-hidden"
                    >
                      <div class="absolute top-0 left-0 w-1.5 h-full bg-blue-600"></div>
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                          <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-2xl shadow-inner group-hover:scale-110 transition-transform">
                            {{ t.instructor.charAt(0) }}
                          </div>
                          <div>
                            <h4 class="text-lg font-black text-surface-900 m-0 tracking-tight">{{ t.instructor }}</h4>
                            <div class="flex items-center gap-2 mt-1.5">
                              <div class="flex items-center gap-1.5 px-2.5 py-1 bg-surface-50 rounded-lg border border-surface-100">
                                <svg class="w-3.5 h-3.5 text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                  <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span class="text-xs font-bold text-surface-600">{{ t.hora_inicio.substring(0,5) }} - {{ t.hora_fin.substring(0,5) }}</span>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-surface-50 flex items-center justify-center text-surface-300 group-hover:bg-blue-600 group-hover:text-white transition-all cursor-help" title="Instructor Asignado">
                          <i class="pi pi-check-circle text-lg"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="flex flex-col items-center justify-center py-20 text-center bg-surface-50/50 rounded-4xl border-2 border-dashed border-surface-200">
                    <div class="w-20 h-20 bg-white rounded-3xl shadow-sm flex items-center justify-center text-surface-300 mb-4">
                      <i class="pi pi-calendar-times text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-black text-surface-900 m-0">Sin turnos para hoy</h3>
                    <p class="text-surface-400 text-sm mt-1 max-w-xs">No hay instructores programados para la jornada de hoy todavía.</p>
                  </div>
                </div>

                <!-- Vista de Semana: Calendario FullCalendar -->
                <div v-else key="week" class="[&_.fc]:font-sans [&_.fc]:text-[0.65rem] [&_.fc]:[--fc-border-color:#f1f5f9] [&_.fc]:[--fc-today-bg-color:#f8fafc] [&_.fc-theme-standard_td]:border-[#f1f5f9]! [&_.fc-timegrid-slot]:h-[2.2rem] [&_.fc-timegrid-slot]:border-b-[#f8fafc]! [&_.fc-event]:rounded-xl! [&_.fc-event]:border-none! [&_.fc-event]:bg-linear-to-br! [&_.fc-event]:from-[#3b82f6] [&_.fc-event]:to-[#1d4ed8] [&_.fc-event]:shadow-lg! [&_.fc-event]:shadow-blue-500/30 [&_.fc-event]:mt-1! [&_.fc-event]:mx-1! [&_.fc-event-main]:flex! [&_.fc-event-main]:items-center! [&_.fc-event-main]:justify-center! [&_.fc-event-main]:text-center! [&_.fc-event-main]:font-bold! [&_.fc-event-main]:p-2! [&_.fc-col-header-cell]:bg-[#f8fafc] [&_.fc-col-header-cell]:py-3 [&_.fc-col-header-cell-cushion]:text-sm! [&_.fc-col-header-cell-cushion]:capitalize! [&_.fc-col-header-cell-cushion]:font-black! [&_.fc-col-header-cell-cushion]:text-surface-600! [&_.fc-timegrid-axis-cushion]:text-[0.7rem]! [&_.fc-timegrid-now-indicator-line]:border-[#ef4444]!">
                  <FullCalendar :key="calendarView" :options="calendarOptions" />
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </main>
</template>

<style scoped>
/* Estilos premium para Select y DatePicker de PrimeVue */
:deep(.p-select) {
  height: 52px !important;
  display: flex !important;
  align-items: center !important;
  background-color: var(--p-surface-50, #f8fafc) !important;
  border: 1px solid var(--p-surface-200, #e2e8f0) !important;
  border-radius: 1rem !important; /* rounded-2xl */
  box-shadow: none !important;
  outline: none !important;
  transition: all 0.2s ease !important;
  padding-left: 0.5rem !important;
  padding-right: 0.5rem !important;
  width: 100% !important;
}

:deep(.p-select:hover) {
  border-color: var(--p-surface-300, #cbd5e1) !important;
}

:deep(.p-select.p-focus),
:deep(.p-select:focus-within) {
  border-color: var(--p-primary-500, #3b82f6) !important;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
  outline: none !important;
}

/* Eliminar outlines y shadows de foco internos de PrimeVue */
:deep(.p-select *),
:deep(.p-select *:focus),
:deep(.p-select *:focus-visible) {
  outline: none !important;
  box-shadow: none !important;
}

:deep(.p-select-label) {
  padding-left: 0.75rem !important;
  padding-right: 0.75rem !important;
  font-size: 0.875rem !important; /* text-sm */
  font-weight: 500 !important;
  color: var(--p-surface-800, #1e293b) !important;
}

/* Contenedor del DatePicker de PrimeVue (solo ancho) */
:deep(.p-datepicker) {
  width: 100% !important;
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  outline: none !important;
}

/* DatePicker e Inputs de Texto */
:deep(.p-datepicker-input),
:deep(.p-inputtext) {
  height: 52px !important;
  background-color: var(--p-surface-50, #f8fafc) !important;
  border: 1px solid var(--p-surface-200, #e2e8f0) !important;
  border-radius: 1rem !important; /* rounded-2xl */
  box-shadow: none !important;
  outline: none !important;
  padding-left: 1rem !important;
  padding-right: 1rem !important;
  font-size: 0.875rem !important; /* text-sm */
  font-weight: 500 !important;
  color: var(--p-surface-800, #1e293b) !important;
  transition: all 0.2s ease !important;
  width: 100% !important;
}

:deep(.p-datepicker-input:hover),
:deep(.p-inputtext:hover) {
  border-color: var(--p-surface-300, #cbd5e1) !important;
}

:deep(.p-datepicker-input:focus),
:deep(.p-inputtext:focus) {
  border-color: var(--p-primary-500, #3b82f6) !important;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
  outline: none !important;
}
</style>

<style>
/* Estilos globales seguros para el panel flotante de selección de instructores */
.custom-instructor-select-panel .p-select-option {
  padding: 0.65rem 1rem !important;
  margin: 0.25rem 0.4rem !important;
  border-radius: 0.75rem !important; /* rounded-xl */
  font-size: 0.875rem !important; /* text-sm */
  font-weight: 500 !important;
  color: var(--p-surface-700, #334155) !important;
  transition: all 0.15s ease !important;
}

.custom-instructor-select-panel .p-select-option:hover,
.custom-instructor-select-panel .p-select-option.p-focus {
  background-color: var(--p-primary-50, #eff6ff) !important;
  color: var(--p-primary-700, #1d4ed8) !important;
}
</style>