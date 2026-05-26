<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAdminStore } from '@/stores/profiles/adminStore';
import { useActividadesStore } from '@/stores/actividadesStore';
import { useSocioTorneoStore } from '@/stores/socioTorneoStore';
import { IconGuests, IconTarget, IconCalendar, IconGrid, IconBaby, IconTrophy } from '@/components/icons';
import api from '@/services/api';
import BaseChart from '@/components/admin/BaseChart.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';

const profileStore = useAdminStore();
const actividadesStore = useActividadesStore();
const torneoStore = useSocioTorneoStore();
const isLoading = ref(true);
const errorMsg = ref('');
const statsData = ref(null);
const activeFusedTab = ref('afluencia');

const todasLasSesionesAdmin = ref([]);
const isLoadingActividades = ref(true);

const getHoyMexicoString = () => {
  const formatter = new Intl.DateTimeFormat('en-US', {
    timeZone: 'America/Mexico_City',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
  const parts = formatter.formatToParts(new Date());
  const pv = {};
  parts.forEach(p => { pv[p.type] = p.value });
  return `${pv.year}-${pv.month}-${pv.day}`;
};

const getAhoraMexicoString = () => {
  const formatter = new Intl.DateTimeFormat('en-US', {
    timeZone: 'America/Mexico_City',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false
  });
  const parts = formatter.formatToParts(new Date());
  const pv = {};
  parts.forEach(p => { pv[p.type] = p.value });
  const h = pv.hour || '00';
  const m = pv.minute || '00';
  const s = pv.second || '00';
  return `${h}:${m}:${s}`;
};

const clasesDeHoy = computed(() => {
  const hoyMexico = getHoyMexicoString();
  const ahoraHora = getAhoraMexicoString();

  const list = (todasLasSesionesAdmin.value || []).filter(s => s.fecha_sesion === hoyMexico);

  return [...list].sort((a, b) => {
    // 1. Estatus "EN_CURSO" goes first
    const isAEnCurso = a.estatus_sesion === 'EN_CURSO';
    const isBEnCurso = b.estatus_sesion === 'EN_CURSO';
    if (isAEnCurso && !isBEnCurso) return -1;
    if (!isAEnCurso && isBEnCurso) return 1;

    // 2. If both or neither are in progress, see if they are upcoming or past
    const aTime = a.hora_inicio || '00:00:00';
    const bTime = b.hora_inicio || '00:00:00';

    const isAFuture = aTime >= ahoraHora;
    const isBFuture = bTime >= ahoraHora;

    if (isAFuture && !isBFuture) return -1; // Future before past
    if (!isAFuture && isBFuture) return 1;

    // Sort chronologically ascending
    return aTime.localeCompare(bTime);
  });
});

const noShowsHoy = computed(() => {
  if (!statsData.value?.estatus_operativo) return 0;
  const idx = statsData.value.estatus_operativo.labels.findIndex(
    l => l.toUpperCase() === 'NO_SHOW'
  );
  return idx !== -1 ? statsData.value.estatus_operativo.data[idx] : 0;
});

const reservasActivasHoy = computed(() => {
  if (!statsData.value?.estatus_operativo) return 0;
  const idx = statsData.value.estatus_operativo.labels.findIndex(
    l => l.toUpperCase() === 'ACTIVA'
  );
  return idx !== -1 ? statsData.value.estatus_operativo.data[idx] : 0;
});

// Saludo dinámico según la hora
const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Buenos días';
  if (hour < 19) return 'Buenas tardes';
  return 'Buenas noches';
});

// Fetch Dashboard BI data
const loadDashboardStats = async () => {
  try {
    isLoading.value = true;
    errorMsg.value = '';
    const res = await api.get('/admin/bi/dashboard');
    if (res.data && res.data.success) {
      statsData.value = res.data.data;
    } else {
      errorMsg.value = 'No se pudo cargar la información analítica.';
    }
  } catch (error) {
    console.error('Error al cargar estadísticas de BI:', error);
    errorMsg.value = 'Error de conexión al cargar el panel de Business Intelligence.';
  } finally {
    isLoading.value = false;
  }
};

const loadAdminSessions = async () => {
  try {
    isLoadingActividades.value = true;
    const res = await api.get('/programacion/sesiones-activas?per_page=1000');
    todasLasSesionesAdmin.value = res.data?.data ?? [];
  } catch (err) {
    console.error('Error fetching admin sessions:', err);
  } finally {
    isLoadingActividades.value = false;
  }
};

onMounted(async () => {
  await loadDashboardStats();
  await Promise.all([
    loadAdminSessions(),
    torneoStore.fetchDisponibles()
  ]);
});

// --- Configuración de Gráficos ---

// 1. Reservaciones de Hoy (Barras)
const chartReservasHoy = computed(() => {
  if (!statsData.value?.reservas_hoy_hora) return null;
  return {
    labels: statsData.value.reservas_hoy_hora.labels,
    datasets: [{
      label: 'Reservas por Hora',
      data: statsData.value.reservas_hoy_hora.data,
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
          ['#64748b', '#475569']  // Slate
        ];
        const pair = colorsMap[context.dataIndex % colorsMap.length];
        if (!chartArea) return pair[0];
        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        gradient.addColorStop(0, pair[1]);
        gradient.addColorStop(1, pair[0]);
        return gradient;
      },
      borderRadius: 8,
      borderSkipped: false
    }]
  };
});

const optionsReservasHoy = {
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
      title: {
        display: true,
        text: 'Reservaciones',
        font: { size: 11, weight: '600' },
        color: '#475569'
      }
    },
    x: {
      grid: { display: false },
      ticks: { color: '#64748b' }
    }
  }
};

// 2. Estatus Operativo (Doughnut)
const chartEstatusOperativo = computed(() => {
  if (!statsData.value?.estatus_operativo) return null;
  const labels = statsData.value.estatus_operativo.labels.map(l => l.replace('_', ' '));
  const data = statsData.value.estatus_operativo.data;
  
  return {
    labels,
    datasets: [{
      data,
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        const colorsMap = {
          'CONFIRMADA': ['#60a5fa', '#2563eb'], // Blue gradient
          'ACTIVA': ['#34d399', '#059669'],     // Emerald gradient
          'FINALIZADA': ['#cbd5e1', '#94a3b8'], // Slate/Gray gradient
          'PENDIENTE': ['#fcd34d', '#d97706'],  // Amber gradient
          'NO_SHOW': ['#fda4af', '#e11d48']     // Rose gradient
        };
        const rawLabel = statsData.value.estatus_operativo.labels[context.dataIndex];
        const key = String(rawLabel || '').toUpperCase();
        const pair = colorsMap[key] || ['#cbd5e1', '#94a3b8'];
        if (!chartArea) return pair[0];
        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        gradient.addColorStop(0, pair[1]);
        gradient.addColorStop(1, pair[0]);
        return gradient;
      },
      borderWidth: 2,
      borderColor: '#ffffff',
      hoverOffset: 6
    }]
  };
});

const optionsEstatusOperativo = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    x: { display: false },
    y: { display: false }
  }
};

// 3. Top 5 Espacios más Demandados Hoy (Barras Horizontales)
const chartTopEspacios = computed(() => {
  if (!statsData.value?.top_espacios) return null;
  return {
    labels: statsData.value.top_espacios.labels,
    datasets: [{
      label: 'Reservas hoy',
      data: statsData.value.top_espacios.data,
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
          ['#64748b', '#475569']  // Slate
        ];
        const pair = colorsMap[context.dataIndex % colorsMap.length];
        if (!chartArea) return pair[0];
        const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0); // Horizontal gradient!
        gradient.addColorStop(0, pair[1]);
        gradient.addColorStop(1, pair[0]);
        return gradient;
      },
      borderRadius: 6,
      barThickness: 20
    }]
  };
});

const optionsTopEspacios = {
  indexAxis: 'y',
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    x: {
      beginAtZero: true,
      ticks: { precision: 0, color: '#64748b' },
      grid: { color: '#f1f5f9' },
      title: {
        display: true,
        text: 'Reservas',
        font: { size: 11, weight: '600' },
        color: '#475569'
      }
    },
    y: {
      grid: { display: false },
      ticks: { color: '#64748b' }
    }
  }
};

// Desglose e Indicadores (Afluencia, Espacios, Estatus)
const individualReservasHora = computed(() => {
  if (!statsData.value?.reservas_hoy_hora) return [];
  const labels = statsData.value.reservas_hoy_hora.labels;
  const data = statsData.value.reservas_hoy_hora.data;
  const colorsMap = [
    '#6366f1', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#8b5cf6', '#f43f5e', '#64748b'
  ];
  return labels.map((label, idx) => ({
    label: `${label}h`,
    value: data[idx],
    color: colorsMap[idx % colorsMap.length]
  }));
});

const totalReservasHora = computed(() => {
  if (!statsData.value?.reservas_hoy_hora) return 0;
  return statsData.value.reservas_hoy_hora.data.reduce((a, b) => a + b, 0);
});

const individualTopEspacios = computed(() => {
  if (!statsData.value?.top_espacios) return [];
  const labels = statsData.value.top_espacios.labels;
  const data = statsData.value.top_espacios.data;
  const colorsMap = [
    '#6366f1', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#8b5cf6', '#f43f5e', '#64748b'
  ];
  return labels.map((label, idx) => ({
    label,
    value: data[idx],
    color: colorsMap[idx % colorsMap.length]
  }));
});

const totalTopEspacios = computed(() => {
  if (!statsData.value?.top_espacios) return 0;
  return statsData.value.top_espacios.data.reduce((a, b) => a + b, 0);
});

const individualEstatusOperativo = computed(() => {
  if (!statsData.value?.estatus_operativo) return [];
  const labels = statsData.value.estatus_operativo.labels;
  const data = statsData.value.estatus_operativo.data;
  const colorsMap = {
    'CONFIRMADA': '#2563eb',
    'ACTIVA': '#059669',
    'FINALIZADA': '#94a3b8',
    'PENDIENTE': '#d97706',
    'NO_SHOW': '#e11d48'
  };
  return labels.map((label, idx) => {
    const key = String(label || '').toUpperCase();
    return {
      label: label.replace('_', ' '),
      value: data[idx],
      color: colorsMap[key] || '#94a3b8'
    };
  });
});

const totalEstatusOperativo = computed(() => {
  if (!statsData.value?.estatus_operativo) return 0;
  return statsData.value.estatus_operativo.data.reduce((a, b) => a + b, 0);
});

// Torneos Activos helpers
const torneosActivos = computed(() => {
  return (torneoStore.disponibles || []).slice(0, 4);
});

const getCupoPercentage = (torneo) => {
  if (!torneo.cupo_maximo) return 0;
  return Math.min(100, Math.round((torneo.inscritos_actual / torneo.cupo_maximo) * 100));
};

const getProgressBarColor = (percentage) => {
  if (percentage >= 90) return 'bg-rose-500';
  if (percentage >= 70) return 'bg-amber-500';
  return 'bg-emerald-500';
};

const formatFecha = (iso) => {
  if (!iso) return '—';
  const [y, m, d] = iso.split('-');
  const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  return `${d} ${meses[parseInt(m) - 1]} ${y}`;
};

const formatEstatus = (estatus) => {
  const labels = {
    'DISPONIBLE': 'Disponible',
    'EN_CURSO': 'En Curso',
    'FINALIZADA': 'Finalizada',
    'CANCELADA': 'Cancelada'
  };
  return labels[estatus] || estatus;
};

const ESTATUS_COLORS = {
  'DISPONIBLE': 'bg-blue-50 text-blue-700 border-blue-150',
  'EN_CURSO': 'bg-emerald-50 text-emerald-700 border-emerald-150',
  'FINALIZADA': 'bg-slate-50 text-slate-600 border-slate-200',
  'CANCELADA': 'bg-rose-50 text-rose-700 border-rose-150'
};
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans pb-16 pt-6">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-10">
      
      <!-- HEADER SALUDO -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-black text-surface-900 tracking-tight m-0">
            {{ greeting }}, <span class="text-primary-600">{{ profileStore.fullName?.split(' ')[0] || 'Administrador' }}</span> 👋
          </h1>
          <p class="text-sm font-medium text-surface-500 mt-2 m-0 max-w-xl">
            Te damos la bienvenida al Panel de Control de Gerencia. Aquí puedes administrar y monitorear las operaciones del club en vivo.
          </p>
        </div>
        <button 
          @click="loadDashboardStats" 
          :disabled="isLoading"
          class="inline-flex items-center gap-2 px-4 py-2 bg-surface-900 border border-surface-950 text-white rounded-xl text-xs font-bold shadow-sm hover:bg-slate-800 transition-all duration-300 self-start md:self-auto cursor-pointer disabled:opacity-50"
        >
          <svg class="w-4 h-4 text-white" :class="{'animate-spin': isLoading}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
          {{ isLoading ? 'Actualizando...' : 'Actualizar datos' }}
        </button>
      </div>

      <!-- CARDS MÓDULOS DE NAVEGACIÓN -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        
        <router-link to="/admin/socios/socios-list" class="group bg-white rounded-2xl p-5 border border-surface-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
          <div class="relative flex flex-col h-full justify-between">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-4">
              <IconGuests class="w-5 h-5" />
            </div>
            <div>
              <p class="text-[9px] uppercase font-black tracking-widest text-surface-400 mb-1">Socios</p>
              <h3 class="text-sm font-black text-surface-900 leading-tight">Activos</h3>
            </div>
          </div>
        </router-link>

        <router-link to="/admin/activities" class="group bg-white rounded-2xl p-5 border border-surface-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
          <div class="relative flex flex-col h-full justify-between">
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-4">
              <IconTarget class="w-5 h-5" />
            </div>
            <div>
              <p class="text-[9px] uppercase font-black tracking-widest text-surface-400 mb-1">Actividades</p>
              <h3 class="text-sm font-black text-surface-900 leading-tight">Programación</h3>
            </div>
          </div>
        </router-link>

        <router-link to="/admin/reservations" class="group bg-white rounded-2xl p-5 border border-surface-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
          <div class="relative flex flex-col h-full justify-between">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
              <IconCalendar class="w-5 h-5" />
            </div>
            <div>
              <p class="text-[9px] uppercase font-black tracking-widest text-surface-400 mb-1">Reservas</p>
              <h3 class="text-sm font-black text-surface-900 leading-tight">On-Demand</h3>
            </div>
          </div>
        </router-link>

        <router-link to="/admin/spaces/list" class="group bg-white rounded-2xl p-5 border border-surface-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
          <div class="relative flex flex-col h-full justify-between">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4">
              <IconGrid class="w-5 h-5" />
            </div>
            <div>
              <p class="text-[9px] uppercase font-black tracking-widest text-surface-400 mb-1">Espacios</p>
              <h3 class="text-sm font-black text-surface-900 leading-tight">Control</h3>
            </div>
          </div>
        </router-link>

        <router-link to="/admin/ludoteca" class="group bg-white rounded-2xl p-5 border border-surface-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
          <div class="relative flex flex-col h-full justify-between">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4">
              <IconBaby class="w-5 h-5" />
            </div>
            <div>
              <p class="text-[9px] uppercase font-black tracking-widest text-surface-400 mb-1">Ludoteca</p>
              <h3 class="text-sm font-black text-surface-900 leading-tight">Control Vivo</h3>
            </div>
          </div>
        </router-link>

        <router-link to="/admin/reports" class="group bg-white rounded-2xl p-5 border border-surface-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
          <div class="relative flex flex-col h-full justify-between">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4">
              <IconTrophy class="w-5 h-5" />
            </div>
            <div>
              <p class="text-[9px] uppercase font-black tracking-widest text-surface-400 mb-1">BI & Reportes</p>
              <h3 class="text-sm font-black text-surface-900 leading-tight">Estadísticas</h3>
            </div>
          </div>
        </router-link>

      </div>

      <!-- MONITOR EN VIVO: KPI CARDS (Diseño y proporciones idénticas a Ludoteca Estadísticas) -->
      <div v-if="!isLoading && statsData" class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        
        <!-- Tarjeta 1: Activos (Ludoteca) -->
        <div class="group bg-white p-6 rounded-3xl border border-surface-200 shadow-sm hover:shadow-lg transition-all relative overflow-hidden">
          <div class="relative flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-inner group-hover:scale-110 transition-transform">
              <IconBaby class="w-7 h-7" />
            </div>
            <div>
              <p class="text-[10px] uppercase font-bold tracking-widest text-surface-400 m-0">Activos</p>
              <h3 class="text-3xl font-black text-surface-900 m-0">{{ statsData.ludoteca_kpis.ninos_activos }}</h3>
            </div>
          </div>
        </div>

        <!-- Tarjeta 2: Entregados (Ludoteca) -->
        <div class="group bg-white p-6 rounded-3xl border border-surface-200 shadow-sm hover:shadow-lg transition-all relative overflow-hidden">
          <div class="relative flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-inner group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-[10px] uppercase font-bold tracking-widest text-surface-400 m-0">Entregados</p>
              <h3 class="text-3xl font-black text-surface-900 m-0">{{ statsData.ludoteca_kpis.ninos_entregados }}</h3>
            </div>
          </div>
        </div>

        <!-- Tarjeta 3: Con Retraso (Ludoteca) -->
        <div class="group bg-white p-6 rounded-3xl border border-surface-200 shadow-sm hover:shadow-lg transition-all relative overflow-hidden"
             :class="{'border-red-200 bg-red-50/5': statsData.ludoteca_kpis.alertas_tiempo > 0}">
          <div class="relative flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform"
                 :class="statsData.ludoteca_kpis.alertas_tiempo > 0 ? 'bg-red-100 text-red-600' : 'bg-slate-50 text-slate-400'">
              <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div>
              <p class="text-[10px] uppercase font-bold tracking-widest m-0"
                 :class="statsData.ludoteca_kpis.alertas_tiempo > 0 ? 'text-red-500' : 'text-surface-400'">
                Con Retraso
              </p>
              <h3 class="text-3xl font-black m-0"
                  :class="statsData.ludoteca_kpis.alertas_tiempo > 0 ? 'text-red-600' : 'text-surface-900'">
                {{ statsData.ludoteca_kpis.alertas_tiempo }}
              </h3>
            </div>
          </div>
        </div>

      </div>

      <!-- CARGANDO / ERROR -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-24 bg-white border border-surface-200 rounded-[2.5rem] shadow-sm">
        <LoadingSpinner />
        <span class="text-sm font-bold text-surface-500 mt-4">Analizando base de datos en tiempo real…</span>
      </div>

      <div v-else-if="errorMsg" class="p-10 text-center bg-red-50 border border-red-200 rounded-[2.5rem]">
        <h3 class="text-lg font-black text-red-800">No pudimos conectar con BI</h3>
        <p class="text-sm text-red-600 mt-2">{{ errorMsg }}</p>
        <button @click="loadDashboardStats" class="mt-4 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm">
          Reintentar conexión
        </button>
      </div>

      <!-- BENTO GRID DE GRÁFICOS -->
      <div v-else-if="statsData" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        
        <!-- Tarjeta Fused 60% (Afluencia + Top Espacios) -->
        <div class="lg:col-span-3 bg-white rounded-[2rem] border border-surface-200 shadow-sm p-6 lg:p-8 flex flex-col min-h-[400px] hover:shadow-md transition-all duration-300">
          <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div>
              <h3 class="text-base font-black text-surface-900 leading-tight">Monitoreo de Afluencia y Espacios</h3>
              <p class="text-xs font-medium text-surface-400 mt-0.5">Analíticas de movimiento y demanda de hoy.</p>
            </div>
            
            <!-- Selector de Tab -->
            <div class="flex bg-slate-100 rounded-xl p-0.5 border border-slate-200 shadow-inner">
              <button @click="activeFusedTab = 'afluencia'"
                      class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all border-none cursor-pointer"
                      :class="activeFusedTab === 'afluencia' ? 'bg-surface-900 text-white shadow-sm scale-[1.02]' : 'text-surface-500 hover:text-surface-700 bg-transparent'">
                Afluencia
              </button>
              <button @click="activeFusedTab = 'espacios'"
                      class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all border-none cursor-pointer"
                      :class="activeFusedTab === 'espacios' ? 'bg-surface-900 text-white shadow-sm scale-[1.02]' : 'text-surface-500 hover:text-surface-700 bg-transparent'">
                Espacios
              </button>
            </div>
          </div>
          
          <div class="flex-1 min-h-[220px]">
            <!-- Vista Afluencia -->
            <div v-show="activeFusedTab === 'afluencia'" class="h-full">
              <BaseChart v-if="chartReservasHoy" type="bar" :data="chartReservasHoy" :options="optionsReservasHoy" />
              <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
                Sin reservaciones registradas para el día de hoy
              </div>
            </div>
            
            <!-- Vista Top Espacios -->
            <div v-show="activeFusedTab === 'espacios'" class="h-full">
              <BaseChart v-if="chartTopEspacios" type="bar" :data="chartTopEspacios" :options="optionsTopEspacios" />
              <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed">
                Sin reservaciones de espacios hoy
              </div>
            </div>
          </div>

          <!-- Desglose de Totales y Métricas Individuales (Afluencia / Espacios) -->
          <div class="mt-6 border-t border-surface-100 pt-4" v-if="activeFusedTab === 'afluencia' ? (chartReservasHoy && individualReservasHora.length) : (chartTopEspacios && individualTopEspacios.length)">
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
              <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">
                {{ activeFusedTab === 'afluencia' ? 'Desglose por Hora' : 'Desglose por Espacio' }}
              </span>
              <span class="text-xs font-bold text-surface-500 bg-surface-50 px-3 py-1 rounded-full border border-surface-100 shadow-sm">
                Total Reservas: <span class="text-surface-900 font-black">
                  {{ activeFusedTab === 'afluencia' ? totalReservasHora : totalTopEspacios }}
                </span>
              </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-[160px] overflow-y-auto scrollbar-thin pr-1">
              <div v-for="(val, idx) in (activeFusedTab === 'afluencia' ? individualReservasHora : individualTopEspacios)" :key="idx" 
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

        <!-- Tarjeta Actividades de Hoy 40% (Alineado al lado) -->
        <div class="lg:col-span-2 bg-white rounded-[2rem] border border-surface-200 shadow-sm p-6 lg:p-8 flex flex-col min-h-[400px] hover:shadow-md transition-all duration-300">
          <div class="mb-4">
            <h3 class="text-base font-black text-surface-900 leading-tight">Actividades de Hoy</h3>
            <p class="text-xs font-medium text-surface-400 mt-0.5">Programación y disciplinas del día en vivo.</p>
          </div>
          
          <div class="flex-1 min-h-0 overflow-y-auto pr-1 scrollbar-thin space-y-3">
            <div v-if="isLoadingActividades" class="h-full flex flex-col items-center justify-center text-slate-400">
              <LoadingSpinner class="w-8 h-8 mb-2" />
              <span class="text-xs font-bold">Cargando actividades…</span>
            </div>
            
            <div v-else-if="clasesDeHoy.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 py-12">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <p class="text-xs font-bold text-slate-400">No hay actividades programadas hoy</p>
            </div>
            
            <div v-else class="space-y-2.5">
              <div v-for="sesion in clasesDeHoy" :key="sesion.id_sesion"
                   class="flex items-center justify-between p-3.5 bg-white hover:bg-slate-50/80 transition-all rounded-2xl border border-slate-200 gap-3 group shadow-2xs">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold uppercase shrink-0 border border-slate-200 shadow-2xs group-hover:scale-105 transition-transform">
                    {{ (sesion.nombre_actividad || sesion.disciplina)?.charAt(0) || 'A' }}
                  </div>
                  <div class="min-w-0">
                    <div class="font-bold text-slate-800 text-xs truncate" :title="sesion.nombre_actividad || sesion.disciplina">
                      {{ sesion.nombre_actividad || sesion.disciplina }}
                    </div>
                    <div class="text-[10px] text-slate-400 font-semibold mt-0.5 truncate">
                      {{ sesion.hora_inicio?.slice(0, 5) }} – {{ sesion.hora_fin?.slice(0, 5) }} · {{ (typeof sesion.instructor === 'object' ? sesion.instructor?.nombre : sesion.instructor) || 'Sin instructor' }}
                    </div>
                  </div>
                </div>
                <div class="flex flex-col items-end gap-1.5 shrink-0">
                  <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border shrink-0"
                        :class="ESTATUS_COLORS[sesion.estatus_sesion] || 'bg-slate-50 text-slate-600 border-slate-200'">
                    {{ formatEstatus(sesion.estatus_sesion) }}
                  </span>
                  <span class="px-2.5 py-1 rounded-full bg-slate-50 border border-slate-200 text-[9px] font-black text-slate-500 shadow-2xs">
                    {{ sesion.cantidad_inscritos }} inscritos
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráfico Estatus Operativo (Doughnut Chart) -->
        <div class="lg:col-span-2 bg-white rounded-[2rem] border border-surface-200 shadow-sm p-6 lg:p-8 flex flex-col hover:shadow-md transition-all duration-300 min-h-[360px]">
          <div class="mb-4">
            <h3 class="text-base font-black text-surface-900 leading-tight">Estatus Operativo de Hoy</h3>
            <p class="text-xs font-medium text-surface-400 mt-0.5">Asistencias vs No-Shows de hoy.</p>
          </div>
          <div class="flex-1 min-h-[180px] flex items-center justify-center">
            <BaseChart v-if="chartEstatusOperativo" type="doughnut" :data="chartEstatusOperativo" :options="optionsEstatusOperativo" class="max-w-xs w-full" />
            <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold bg-surface-50 rounded-2xl border border-surface-200 border-dashed w-full">
              Sin estatus registrados hoy
            </div>
          </div>

          <!-- Desglose de Estatus Operativo -->
          <div class="mt-6 border-t border-surface-100 pt-4" v-if="chartEstatusOperativo && individualEstatusOperativo.length">
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
              <span class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Desglose</span>
              <span class="text-xs font-bold text-surface-500 bg-surface-50 px-3 py-1 rounded-full border border-surface-100 shadow-sm">
                Total: <span class="text-surface-900 font-black">{{ totalEstatusOperativo }}</span>
              </span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div v-for="(val, idx) in individualEstatusOperativo" :key="idx" 
                class="flex items-center justify-between p-2 bg-surface-50/50 hover:bg-surface-50 hover:border-surface-200 rounded-xl border border-surface-100 transition-all">
                <div class="flex items-center gap-2 truncate min-w-0">
                  <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: val.color || '#94a3b8' }"></span>
                  <span class="text-[10px] font-black text-surface-600 truncate uppercase">{{ val.label }}</span>
                </div>
                <span class="text-[10px] font-black text-surface-900 ml-2 bg-white px-1.5 py-0.5 rounded-md border border-surface-100 shadow-2xs shrink-0">{{ val.value }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Seccion Bento de Torneos Activos (Alineado estéticamente) -->
        <div class="lg:col-span-3 bg-white rounded-[2rem] border border-surface-200 shadow-sm p-6 lg:p-8 flex flex-col hover:shadow-md transition-all duration-300 min-h-[360px]">
          <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div>
              <h3 class="text-base font-black text-surface-900 leading-tight">Torneos Activos</h3>
              <p class="text-xs font-medium text-surface-400 mt-0.5">Monitoreo de torneos y copas vigentes del club.</p>
            </div>
            <router-link to="/admin/reports/tournaments-analytics" class="text-xs font-black text-slate-900 hover:text-slate-700 transition-colors shrink-0">
              Ver analíticas &rarr;
            </router-link>
          </div>
          
          <div v-if="torneoStore.loading" class="flex-1 flex flex-col items-center justify-center py-8">
            <LoadingSpinner class="w-8 h-8 mb-2" />
            <span class="text-xs font-bold text-slate-400">Cargando torneos activos…</span>
          </div>

          <div v-else-if="torneosActivos.length === 0" class="flex-1 flex flex-col items-center justify-center py-8 text-slate-400">
            <IconTrophy class="w-10 h-10 text-slate-300 mb-2 shrink-0" />
            <span class="text-xs font-bold">No hay torneos activos en este momento</span>
          </div>

          <div v-else class="flex flex-col gap-3 flex-1 overflow-y-auto pr-1">
            <router-link
              v-for="torneo in torneosActivos"
              :key="torneo.id_torneo"
              to="/admin/reports/tournaments-analytics"
              class="group bg-slate-50/50 hover:bg-white rounded-2xl border border-slate-200 hover:border-slate-350 p-4 shadow-2xs hover:shadow-md hover:-translate-y-0.5 active:scale-99 transition-all duration-300 flex items-center justify-between text-left cursor-pointer relative overflow-hidden"
            >
              <!-- Left Accent Line -->
              <div class="absolute left-0 top-0 bottom-0 w-1 bg-slate-900"></div>

              <!-- Left part: Icon & Name & Category -->
              <div class="flex items-center gap-3.5 min-w-0 pl-2">
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center border border-slate-200 shrink-0 shadow-2xs">
                  <DisciplineIcon :name="torneo.disciplina?.nombre_disciplina" class="w-4.5 h-4.5 fill-current text-slate-700" />
                </div>
                <div class="min-w-0">
                  <div class="text-[9px] font-black text-slate-900 uppercase tracking-widest leading-none truncate">
                    {{ torneo.disciplina?.nombre_disciplina || 'Multi-Deporte' }}
                  </div>
                  <h4 class="text-xs font-black text-slate-950 mt-1 leading-tight tracking-tight truncate" :title="torneo.nombre_torneo">
                    {{ torneo.nombre_torneo }}
                  </h4>
                  <div class="text-[9px] font-bold text-slate-400 mt-0.5 leading-none">
                    Cat: {{ torneo.categoria?.nombre_categoria }} · Rama: {{ torneo.categoria?.genero_requerido === 'M' ? 'Varonil' : (torneo.categoria?.genero_requerido === 'F' ? 'Femenil' : 'Mixto') }}
                  </div>
                </div>
              </div>

              <!-- Right part: Date, progress, access & Action -->
              <div class="flex items-center gap-5 sm:gap-7 shrink-0 pr-3">
                <!-- Date Block -->
                <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500">
                  <IconCalendar class="w-4 h-4 text-slate-400 shrink-0" />
                  <span class="truncate">Inicia: <span class="text-slate-800 font-black">{{ formatFecha(torneo.fecha_inicio) }}</span></span>
                </div>

                <!-- Progress Bar / Cupos -->
                <div class="w-28 sm:w-36 flex flex-col justify-center">
                  <div class="flex justify-between text-[9px] font-bold text-slate-500 mb-1">
                    <span>Cupos</span>
                    <span class="text-slate-900 font-black">{{ torneo.inscritos_actual }}/{{ torneo.cupo_maximo }}</span>
                  </div>
                  <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden shadow-inner border border-slate-200/50">
                    <div
                      :class="getProgressBarColor(getCupoPercentage(torneo))"
                      class="h-full rounded-full transition-all duration-500"
                      :style="{ width: getCupoPercentage(torneo) + '%' }"
                    ></div>
                  </div>
                </div>

                <!-- Access badge & Action link -->
                <div class="flex items-center gap-3">
                  <span class="bg-slate-900 border border-slate-950 text-white text-[8px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider shadow-2xs whitespace-nowrap">
                    {{ torneo.tipo_acceso }}
                  </span>
                  <span class="text-[10px] font-black text-slate-900 group-hover:text-slate-700 transition-colors whitespace-nowrap">
                    Ver Detalles &rarr;
                  </span>
                </div>
              </div>
            </router-link>
          </div>
        </div>

      </div>

    </div>
  </main>
</template>