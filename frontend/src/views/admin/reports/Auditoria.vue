<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useReportsBIStore } from '@/stores/admin/reportsBIStore';
import BaseChart from '@/components/admin/BaseChart.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import ExportCsvButton from '@/components/gerente/ui/ExportCsvButton.vue';
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue';
import { IconCalendar, IconAlertCircle } from '@/components/icons';

const reportsStore = useReportsBIStore();

const isLoading = ref(!reportsStore.auditoriaStats);
const errorMsg = ref('');
const stats = computed(() => reportsStore.auditoriaStats);

// Rango dinámico y filtros
const filtroRango = ref(reportsStore.auditoriaFilters.rango || 'mes');
const filterDateStart = ref(reportsStore.auditoriaFilters.fecha_inicio);
const filterDateEnd = ref(reportsStore.auditoriaFilters.fecha_fin);

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
    // Para auditoría, por defecto mostramos 6 meses atrás si es rango mes para que la tendencia se vea fluida, 
    // pero si es exactamente este mes actual:
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    filterDateStart.value = firstDay.toISOString().split('T')[0];
    filterDateEnd.value = lastDay.toISOString().split('T')[0];
  }
};

const loadStats = async (forceRefresh = false) => {
  const isSameFilters =
    reportsStore.auditoriaStats &&
    reportsStore.auditoriaFilters.rango === (filtroRango.value || undefined) &&
    reportsStore.auditoriaFilters.fecha_inicio === filterDateStart.value &&
    reportsStore.auditoriaFilters.fecha_fin === filterDateEnd.value;

  if (isSameFilters && !forceRefresh) {
    return;
  }

  try {
    isLoading.value = true;
    errorMsg.value = '';
    await reportsStore.fetchAuditoriaStats({
      fecha_inicio: filterDateStart.value,
      fecha_fin: filterDateEnd.value,
      rango: filtroRango.value || undefined
    }, forceRefresh);
  } catch (error) {
    console.error('Error loading auditoria stats:', error);
    errorMsg.value = 'Conexión fallida al servidor de BI.';
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

onMounted(() => {
  if (reportsStore.auditoriaStats) {
    filtroRango.value = reportsStore.auditoriaFilters.rango;
    filterDateStart.value = reportsStore.auditoriaFilters.fecha_inicio;
    filterDateEnd.value = reportsStore.auditoriaFilters.fecha_fin;
    loadStats();
  } else {
    updateDatesFromRango(filtroRango.value);
    const now = new Date();
    const sixMonthsAgo = new Date(now.getFullYear(), now.getMonth() - 5, 1);
    filterDateStart.value = sixMonthsAgo.toISOString().split('T')[0];
    loadStats();
  }
});

// --- Configuración de Gráficos ---

// 1. Tasa de Abandono (No-Shows) - Línea de Tendencia
const chartTendenciaNoShows = computed(() => {
  if (!stats.value?.tendencia_no_shows?.labels?.length) return null;
  return {
    labels: stats.value.tendencia_no_shows.labels,
    datasets: [{
      label: 'No-Shows Totales',
      data: stats.value.tendencia_no_shows.data,
      borderColor: '#f43f5e', // Rose-500
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        if (!chartArea) return 'rgba(244, 63, 94, 0.1)';
        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        gradient.addColorStop(0, 'rgba(244, 63, 94, 0)');
        gradient.addColorStop(1, 'rgba(244, 63, 94, 0.25)');
        return gradient;
      },
      borderWidth: 3.5,
      fill: true,
      tension: 0.35,
      pointRadius: 5,
      pointBackgroundColor: '#ffffff',
      pointBorderColor: '#f43f5e',
      pointBorderWidth: 2
    }]
  };
});

const optionsTendenciaNoShows = {
  responsive: true,
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
</script>

<template>
  <div class="space-y-8 font-sans">
    
    <!-- ENCABEZADO Y FILTROS DE RANGO -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-xl font-black text-surface-900 m-0 tracking-tight">Periodo de Auditoría</h2>
        <p class="text-xs text-surface-500 m-0 mt-0.5 font-medium">Ajusta el rango de tiempo de consulta para recalcular fidelización, cancelaciones y lista negra.</p>
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
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
        
        <!-- Rango Fecha Inicio -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Rango Tendencia: Desde</label>
          <div class="relative">
            <IconCalendar class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <input type="date" v-model="filterDateStart" @change="filtroRango = ''; loadStats()"
                   class="w-full pl-10 pr-4 py-2 bg-white border border-surface-200 rounded-xl text-xs font-semibold text-surface-700 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer" />
          </div>
        </div>

        <!-- Rango Fecha Fin -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Hasta</label>
          <div class="relative">
            <IconCalendar class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            <input type="date" v-model="filterDateEnd" @change="filtroRango = ''; loadStats()"
                   class="w-full pl-10 pr-4 py-2 bg-white border border-surface-200 rounded-xl text-xs font-semibold text-surface-700 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer" />
          </div>
        </div>

      </div>
    </div>

    <!-- CARGANDO / ERROR -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white">
      <LoadingSpinner />
      <span class="text-sm font-bold text-surface-500 mt-4">Analizando auditorías e infracciones de socios…</span>
    </div>

    <div v-else-if="errorMsg" class="p-8 text-center bg-red-50 border border-red-200 rounded-3xl text-red-700 font-semibold">
      {{ errorMsg }}
    </div>

    <!-- REPORTES DE AUDITORÍA Y TABLAS -->
    <div v-else class="space-y-8">
      
      <!-- Gráfico 1: Tasa de Abandono (No-Shows) -->
      <div class="bg-white border border-surface-200 rounded-3xl p-6 lg:p-8 flex flex-col h-[350px]">
        <div class="mb-4">
          <h3 class="text-base font-black text-surface-900 leading-tight">Tendencia de Cancelaciones Imprevistas (No-Shows)</h3>
          <p class="text-xs font-medium text-surface-400 mt-0.5">Línea temporal acumulada de inasistencias en clases programadas y reservas on-demand.</p>
        </div>
        <div class="flex-1 min-h-0">
          <BaseChart v-if="chartTendenciaNoShows" type="line" :data="chartTendenciaNoShows" :options="optionsTendenciaNoShows" />
          <div v-else class="h-full flex items-center justify-center text-slate-400 text-sm font-bold">
            Sin abandonos de reservas en este periodo
          </div>
        </div>
      </div>

      <!-- Sección Tablas Fidelización (Heavy Users vs. Fantasmas) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Tabla A1: Heavy Users -->
        <div class="bg-white border border-surface-200 rounded-3xl p-6 flex flex-col h-[400px]">
          <div class="flex justify-between items-start mb-4">
            <div>
              <h3 class="text-sm font-black text-surface-900 leading-tight">Socios Destacados: Heavy Users</h3>
              <p class="text-[11px] font-medium text-surface-400 mt-0.5">Socios con mayor cantidad de asistencias confirmadas.</p>
            </div>
            <ExportCsvButton :data="stats.heavy_users" filename="heavy-users"
                             :columns="[
                               { label: 'ID Socio', field: 'id_socio' },
                               { label: 'Número Acción', field: 'numero_accion' },
                               { label: 'Nombre Completo', field: 'nombre_completo' },
                               { label: 'Total Asistencias', field: 'total_asistencias' }
                             ]" />
          </div>
          <div class="flex-1 min-h-0 overflow-y-auto border border-surface-100 rounded-xl">
            <table class="w-full text-xs text-left text-surface-600">
              <thead class="bg-slate-900 text-white text-[10px] uppercase font-bold sticky top-0">
                <tr>
                  <th scope="col" class="px-4 py-2.5">Socio</th>
                  <th scope="col" class="px-4 py-2.5">Acción</th>
                  <th scope="col" class="px-4 py-2.5 text-right">Asistencias</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-surface-100">
                <tr v-for="user in stats.heavy_users" :key="user.id_socio" class="hover:bg-surface-50/50 transition-colors">
                  <td class="px-4 py-3 font-semibold text-surface-950">{{ user.nombre_completo }}</td>
                  <td class="px-4 py-3 font-mono text-surface-500">{{ user.numero_accion }}</td>
                  <td class="px-4 py-3 text-right font-black text-emerald-600">{{ user.total_asistencias }}</td>
                </tr>
                <tr v-if="!stats.heavy_users.length">
                  <td colspan="3" class="px-4 py-8 text-center text-slate-400 font-bold">Sin usuarios detectados</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tabla A2: Fantasmas -->
        <div class="bg-white border border-surface-200 rounded-3xl p-6 flex flex-col h-[400px]">
          <div class="flex justify-between items-start mb-4">
            <div>
              <h3 class="text-sm font-black text-surface-900 leading-tight">Membresías Fantasmas</h3>
              <p class="text-[11px] font-medium text-surface-400 mt-0.5">Socios al corriente pero con 0 reservaciones en el periodo.</p>
            </div>
            <ExportCsvButton :data="stats.fantasmas" filename="membresias-fantasmas"
                             :columns="[
                               { label: 'ID Socio', field: 'id_socio' },
                               { label: 'Número Acción', field: 'numero_accion' },
                               { label: 'Nombre Completo', field: 'nombre_completo' }
                             ]" />
          </div>
          <div class="flex-1 min-h-0 overflow-y-auto border border-surface-100 rounded-xl">
            <table class="w-full text-xs text-left text-surface-600">
              <thead class="bg-slate-900 text-white text-[10px] uppercase font-bold sticky top-0">
                <tr>
                  <th scope="col" class="px-4 py-2.5">Socio</th>
                  <th scope="col" class="px-4 py-2.5">Acción</th>
                  <th scope="col" class="px-4 py-2.5 text-center">Estatus</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-surface-100">
                <tr v-for="user in stats.fantasmas" :key="user.id_socio" class="hover:bg-surface-50/50 transition-colors">
                  <td class="px-4 py-3 font-semibold text-surface-950">{{ user.nombre_completo }}</td>
                  <td class="px-4 py-3 font-mono text-surface-500">{{ user.numero_accion }}</td>
                  <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-700 font-bold rounded-full text-[9px] uppercase tracking-wider">Inactivo</span>
                  </td>
                </tr>
                <tr v-if="!stats.fantasmas.length">
                  <td colspan="3" class="px-4 py-8 text-center text-slate-400 font-bold">Sin membresías inactivas</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Tabla B: La "Lista Negra" (Reincidentes) - Full Width -->
      <div class="bg-white border border-surface-200 rounded-3xl p-6 lg:p-8 flex flex-col h-[400px]">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-base font-black text-surface-900 leading-tight">La Lista Negra (Reincidentes y Sancionados)</h3>
            <p class="text-xs font-medium text-surface-400 mt-0.5">Cruza socios titulares con el mayor volumen acumulado de infracciones reglamentarias.</p>
          </div>
          <ExportCsvButton :data="stats.lista_negra" filename="lista-negra-sancionados"
                           :columns="[
                             { label: 'ID Socio', field: 'id_socio' },
                             { label: 'Número Acción', field: 'numero_accion' },
                             { label: 'Nombre Completo', field: 'nombre_completo' },
                             { label: 'No-Shows Reservas', field: 'contador_no_shows' },
                             { label: 'Retrasos Ludoteca', field: 'retrasos_ludoteca' },
                             { label: 'Estatus Penalización', field: 'estatus_penalizacion' }
                           ]" />
        </div>
        <div class="flex-1 min-h-0 overflow-y-auto border border-surface-100 rounded-xl">
          <table class="w-full text-xs text-left text-surface-600">
            <thead class="bg-slate-900 text-white text-[10px] uppercase font-bold sticky top-0">
              <tr>
                <th scope="col" class="px-6 py-3">Socio</th>
                <th scope="col" class="px-6 py-3">Acción</th>
                <th scope="col" class="px-6 py-3 text-center">No-Shows (Reservas)</th>
                <th scope="col" class="px-6 py-3 text-center">Retrasos (Ludoteca)</th>
                <th scope="col" class="px-6 py-3 text-center">Estatus Penalización</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
              <tr v-for="user in stats.lista_negra" :key="user.id_socio" class="hover:bg-surface-50/50 transition-colors">
                <td class="px-6 py-3 font-semibold text-surface-950">{{ user.nombre_completo }}</td>
                <td class="px-6 py-3 font-mono text-surface-500">{{ user.numero_accion }}</td>
                <td class="px-6 py-3 text-center font-bold" :class="user.contador_no_shows >= 3 ? 'text-red-600' : 'text-slate-600'">
                  {{ user.contador_no_shows }}
                </td>
                <td class="px-6 py-3 text-center font-bold" :class="user.retrasos_ludoteca >= 3 ? 'text-red-600' : 'text-slate-600'">
                  {{ user.retrasos_ludoteca }}
                </td>
                <td class="px-6 py-3 text-center">
                  <BadgeStatus :status="user.estatus_penalizacion ?? 'SIN_PENALIZACION'" />
                </td>
              </tr>
              <tr v-if="!stats.lista_negra.length">
                <td colspan="5" class="px-6 py-8 text-center text-slate-400 font-bold">Sin infractores registrados</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>
</template>