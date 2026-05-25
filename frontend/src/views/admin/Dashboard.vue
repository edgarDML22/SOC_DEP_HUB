<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAdminStore } from '@/stores/profiles/adminStore';
import { IconGuests, IconTarget, IconCalendar, IconGrid, IconBaby, IconTrophy } from '@/components/icons';
import api from '@/services/api';
import BaseChart from '@/components/admin/BaseChart.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';

const profileStore = useAdminStore();
const isLoading = ref(true);
const errorMsg = ref('');
const statsData = ref(null);

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

onMounted(loadDashboardStats);

// --- Configuración de Gráficos ---

// 1. Reservaciones de Hoy (Barras)
const chartReservasHoy = computed(() => {
  if (!statsData.value?.reservas_hoy_hora) return null;
  return {
    labels: statsData.value.reservas_hoy_hora.labels,
    datasets: [{
      label: 'Reservas por Hora',
      data: statsData.value.reservas_hoy_hora.data,
      backgroundColor: 'rgba(99, 102, 241, 0.85)', // Indigo-500
      hoverBackgroundColor: 'rgba(79, 70, 229, 1)',  // Indigo-600
      borderRadius: 8,
      borderSkipped: false
    }]
  };
});

const optionsReservasHoy = {
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { stepSize: 1 }
    }
  }
};

// 2. Estatus Operativo (Doughnut)
const chartEstatusOperativo = computed(() => {
  if (!statsData.value?.estatus_operativo) return null;
  const labels = statsData.value.estatus_operativo.labels.map(l => l.replace('_', ' '));
  const data = statsData.value.estatus_operativo.data;
  
  // Colores Premium según estatus
  const backgroundColors = statsData.value.estatus_operativo.labels.map(l => {
    switch (l.toUpperCase()) {
      case 'CONFIRMADA': return '#3b82f6'; // Blue-500
      case 'ACTIVA': return '#10b981';     // Emerald-500
      case 'FINALIZADA': return '#64748b'; // Slate-500
      case 'PENDIENTE': return '#f59e0b';  // Amber-500
      case 'NO_SHOW': return '#f43f5e';    // Rose-500
      default: return '#cbd5e1';
    }
  });

  return {
    labels,
    datasets: [{
      data,
      backgroundColor: backgroundColors,
      borderWidth: 2,
      borderColor: '#ffffff',
      hoverOffset: 4
    }]
  };
});

const optionsEstatusOperativo = {
  plugins: {
    legend: {
      position: 'right',
      labels: { boxWidth: 12, padding: 12 }
    }
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
      backgroundColor: 'rgba(236, 72, 153, 0.85)', // Pink-500
      hoverBackgroundColor: 'rgba(219, 39, 119, 1)',  // Pink-600
      borderRadius: 6,
      barThickness: 20
    }]
  };
});

const optionsTopEspacios = {
  indexAxis: 'y',
  plugins: {
    legend: { display: false }
  },
  scales: {
    x: {
      beginAtZero: true,
      ticks: { stepSize: 1 }
    }
  }
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
          class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-surface-200 rounded-xl text-xs font-bold text-surface-700 shadow-sm hover:bg-surface-50 transition-colors self-start md:self-auto"
        >
          <svg class="w-4 h-4" :class="{'animate-spin': isLoading}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
          Actualizar datos
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

      <!-- MONITOR EN VIVO: LUDOTECA KPIs -->
      <div v-if="!isLoading && statsData" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Tarjeta Niños en Sala -->
        <div class="bg-white border border-surface-200 shadow-sm rounded-3xl p-6 flex items-center gap-5 hover:shadow-md transition-all duration-300">
          <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center shrink-0">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.97 5.97 0 00-.75-2.985m-.001-2.985a3.921 3.921 0 00-1.896-3.279 3.97 3.97 0 00-3.354.001 3.92 3.92 0 00-1.897 3.28m0 0a3.001 3.001 0 004.5 2.585m-4.5-2.585a5.97 5.97 0 00-.75 2.985m0 0A6.07 6.07 0 010 18.72a3.001 3.001 0 014.682-2.72m0 0a3.92 3.92 0 011.897-3.28m0 0a3.97 3.97 0 013.354-.001 3.92 3.92 0 011.897 3.28m0 0a3.001 3.001 0 004.499-2.583" />
            </svg>
          </div>
          <div>
            <h4 class="text-xs font-black tracking-widest text-surface-400 uppercase leading-none mb-1">Niños Actualmente</h4>
            <div class="flex items-baseline gap-1.5">
              <span class="text-3xl font-black text-surface-900 leading-tight">{{ statsData.ludoteca_kpis.ninos_activos }}</span>
              <span class="text-xs font-bold text-surface-400">menores en sala</span>
            </div>
          </div>
        </div>

        <!-- Tarjeta Alertas Exceso de Tiempo -->
        <div class="bg-white border border-surface-200 shadow-sm rounded-3xl p-6 flex items-center gap-5 hover:shadow-md transition-all duration-300"
             :class="{'border-red-200 bg-red-50/10': statsData.ludoteca_kpis.alertas_tiempo > 0}">
          <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
               :class="statsData.ludoteca_kpis.alertas_tiempo > 0 ? 'bg-red-100 text-red-600' : 'bg-slate-50 text-slate-400'">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div>
            <h4 class="text-xs font-black tracking-widest uppercase leading-none mb-1"
                :class="statsData.ludoteca_kpis.alertas_tiempo > 0 ? 'text-red-500' : 'text-surface-400'">
              Alertas de Estancia
            </h4>
            <div class="flex items-baseline gap-1.5">
              <span class="text-3xl font-black leading-tight"
                    :class="statsData.ludoteca_kpis.alertas_tiempo > 0 ? 'text-red-600' : 'text-surface-900'">
                {{ statsData.ludoteca_kpis.alertas_tiempo }}
              </span>
              <span class="text-xs font-bold text-surface-400">tiempo excedido (>90m)</span>
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
      <div v-else-if="statsData" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Gráfico Afluencia Reservas (Barras) -->
        <div class="lg:col-span-2 bg-white rounded-[2rem] border border-surface-200 shadow-sm p-6 lg:p-8 flex flex-col h-[400px] hover:shadow-md transition-all duration-300">
          <div class="mb-4">
            <h3 class="text-base font-black text-surface-900 leading-tight">Afluencia Operativa de Hoy</h3>
            <p class="text-xs font-medium text-surface-400 mt-0.5">Reservaciones activas estimadas distribuidas por hora.</p>
          </div>
          <div class="flex-1 min-h-0">
            <BaseChart v-if="chartReservasHoy" type="bar" :data="chartReservasHoy" :options="optionsReservasHoy" />
            <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
              Sin reservaciones registradas para el día de hoy
            </div>
          </div>
        </div>

        <!-- Gráfico Estatus Operativo (Doughnut) -->
        <div class="bg-white rounded-[2rem] border border-surface-200 shadow-sm p-6 lg:p-8 flex flex-col h-[400px] hover:shadow-md transition-all duration-300">
          <div class="mb-4">
            <h3 class="text-base font-black text-surface-900 leading-tight">Estatus Operativo</h3>
            <p class="text-xs font-medium text-surface-400 mt-0.5">Asistencias vs No-Shows esperados para hoy.</p>
          </div>
          <div class="flex-1 min-h-0 flex items-center justify-center">
            <BaseChart v-if="chartEstatusOperativo" type="doughnut" :data="chartEstatusOperativo" :options="optionsEstatusOperativo" />
            <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
              Sin estatus registrados hoy
            </div>
          </div>
        </div>

        <!-- Gráfico Top Espacios (Horizontal Bar) -->
        <div class="lg:col-span-3 bg-white rounded-[2rem] border border-surface-200 shadow-sm p-6 lg:p-8 flex flex-col h-[400px] hover:shadow-md transition-all duration-300">
          <div class="mb-4">
            <h3 class="text-base font-black text-surface-900 leading-tight">Top 5 Espacios más Demandados Hoy</h3>
            <p class="text-xs font-medium text-surface-400 mt-0.5">Distribución de movimiento por instalaciones y áreas físicas.</p>
          </div>
          <div class="flex-1 min-h-0">
            <BaseChart v-if="chartTopEspacios" type="bar" :data="chartTopEspacios" :options="optionsTopEspacios" />
            <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
              Sin reservaciones de espacios hoy
            </div>
          </div>
        </div>

      </div>

    </div>
  </main>
</template>