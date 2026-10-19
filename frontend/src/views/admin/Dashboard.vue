<script setup>
import { ref, onMounted, onUnmounted, onBeforeUnmount, computed } from 'vue';
import { useAdminStore } from '@/stores/profiles/adminStore';
import { useTournamentStore } from '@/stores/tournamentStore';
import { useReservacionAdminStore } from '@/stores/admin/reservationAdminStore';
import { IconGuests, IconCalendar, IconGrid, IconTrophy, IconBaby, IconWarning, IconClock } from '@/components/icons';
import IconBookmark from '@/components/icons/IconBookmark.vue';
import IconSportCourt from '@/components/icons/IconSportCourt.vue';
import api from '@/services/api';
import BaseChart from '@/components/admin/BaseChart.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import DashboardKpiCard from '@/components/admin/DashboardKpiCard.vue';
import DashboardQuickActions from '@/components/admin/DashboardQuickActions.vue';
import DashboardHero from '@/components/admin/dashboard/DashboardHero.vue';
import DashboardSection from '@/components/admin/dashboard/DashboardSection.vue';
import DashboardModuleCard from '@/components/admin/dashboard/DashboardModuleCard.vue';
import DashboardSessionRow from '@/components/admin/dashboard/DashboardSessionRow.vue';
import DashboardReservationRow from '@/components/admin/dashboard/DashboardReservationRow.vue';

const profileStore = useAdminStore();
const torneoStore = useTournamentStore();
const reservacionAdminStore = useReservacionAdminStore();

let abortController = new AbortController();
const isMounted = ref(false);
const isLoading = ref(true);
const errorMsg = ref('');
const statsData = ref(null);
const activeFusedTab = ref('afluencia'); // 'afluencia' | 'ocupacion'

const todasLasSesionesAdmin = ref([]);
const isLoadingActividades = ref(true);

const lastUpdated = ref(null);
const nowTick = ref(Date.now());
let tickTimer = null;

// ------------------------------------------------------------------
// Zona horaria México (helpers existentes — NO modificar)
// ------------------------------------------------------------------
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

// Helpers nuevos (hora de México, sin tocar los existentes)
const getHoraMexicoNumber = (date = new Date()) => {
  const formatter = new Intl.DateTimeFormat('en-US', {
    timeZone: 'America/Mexico_City',
    hour: '2-digit',
    hour12: false,
  });
  const parts = formatter.formatToParts(date);
  const pv = {};
  parts.forEach(p => { pv[p.type] = p.value });
  return parseInt(pv.hour, 10) % 24;
};

const getFechaLargaMexico = (date = new Date()) => {
  const s = new Intl.DateTimeFormat('es-MX', {
    timeZone: 'America/Mexico_City',
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date);
  return s.charAt(0).toUpperCase() + s.slice(1);
};

const getHoraMinutoMexico = (date = new Date()) =>
  new Intl.DateTimeFormat('es-MX', {
    timeZone: 'America/Mexico_City',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).format(date);

// ------------------------------------------------------------------
// Tema oscuro reactivo (para opciones de Chart.js)
// ------------------------------------------------------------------
const isDark = ref(false);
let themeObserver = null;
const syncTheme = () => {
  const root = document.documentElement;
  isDark.value = root.classList.contains('dark') || root.classList.contains('app-dark');
};

// ------------------------------------------------------------------
// Sesiones de hoy (orden estricto: EN_CURSO → futuras → pasadas)
// ------------------------------------------------------------------
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

const reservasProximasHoy = computed(() => {
  const list = reservacionAdminStore.reservaciones || [];
  return [...list].sort((a, b) => {
    const order = { 'ACTIVA': 1, 'COMPLETADA': 2, 'NO_SHOW': 3, 'CANCELADA': 4 };
    const oA = order[a.estatus_operativo] || 99;
    const oB = order[b.estatus_operativo] || 99;
    if (oA !== oB) return oA - oB;

    const aTime = a.hora_inicio || '00:00:00';
    const bTime = b.hora_inicio || '00:00:00';
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

const reservasCompletadasHoy = computed(() => {
  if (!statsData.value?.estatus_operativo) return 0;
  const idx = statsData.value.estatus_operativo.labels.findIndex(
    l => l.toUpperCase() === 'COMPLETADA'
  );
  return idx !== -1 ? statsData.value.estatus_operativo.data[idx] : 0;
});

// Afluencia del día calculada en el front (no existe en el backend)
const afluenciaHoy = computed(() => {
  const data = statsData.value?.reservas_hoy_hora?.data;
  if (!Array.isArray(data)) return 0;
  return data.reduce((a, b) => a + (Number(b) || 0), 0);
});

const clasesMatutinas = computed(() =>
  clasesDeHoy.value.filter(s => (s.hora_inicio || '23:59:59') < '12:00:00').length
);
const clasesEnCursoHoy = computed(() =>
  clasesDeHoy.value.filter(s => s.estatus_sesion === 'EN_CURSO').length
);

// ------------------------------------------------------------------
// KPIs (footnotes)
// ------------------------------------------------------------------
const noShowEspacio = computed(() => {
  const list = reservacionAdminStore.reservaciones || [];
  const mapa = {};
  list.forEach(r => {
    if (r.estatus_operativo !== 'NO_SHOW') return;
    const nombre = r.espacio_fisico?.nombre_espacio || r.nombre_espacio || 'Sin espacio';
    mapa[nombre] = (mapa[nombre] || 0) + 1;
  });
  const entries = Object.entries(mapa).sort((a, b) => b[1] - a[1]);
  if (!entries.length) return null;
  return { espacio: entries[0][0], count: entries[0][1] };
});

const kpis = computed(() => {
  const kpisData = [
    {
      key: 'afluencia',
      label: 'Socios Activos / Afluencia',
      value: afluenciaHoy.value,
      unit: 'reservas hoy',
      icon: IconGuests,
      tone: 'blue',
      footnote: '',
    },
    {
      key: 'clases',
      label: 'Clases de Hoy',
      value: clasesDeHoy.value.length,
      unit: 'sesiones totales',
      icon: IconCalendar,
      tone: 'amber',
      footnote: `${clasesMatutinas.value} en horario matutino · ${clasesEnCursoHoy.value} en curso`,
    },
    {
      key: 'reservas',
      label: 'Reservas del Día',
      value: reservasActivasHoy.value,
      unit: 'slots confirmados',
      icon: IconBookmark,
      tone: 'emerald',
      footnote: `${reservasCompletadasHoy.value} completadas`,
    },
    {
      key: 'noshow',
      label: 'No-Shows / Incidencias',
      value: noShowsHoy.value,
      unit: 'reportes acumulados',
      icon: IconWarning,
      tone: 'rose',
      badge: noShowsHoy.value > 0 ? 'Requiere revisión' : '',
      badgeTone: 'rose',
      footnote: noShowsHoy.value > 0
        ? (noShowEspacio.value ? `${noShowEspacio.value.count} en ${noShowEspacio.value.espacio}` : '')
        : 'Sin incidencias',
      footnoteTone: noShowsHoy.value > 0 ? 'warning' : 'success',
    },
  ];
  return kpisData;
});

const showKpiSkeleton = computed(() => isLoading.value && !statsData.value);
const showPanelSkeleton = computed(() => isLoading.value && !statsData.value);
const showSesionesSkeleton = computed(() => isLoadingActividades.value && !todasLasSesionesAdmin.value.length);

// ------------------------------------------------------------------
// Header
// ------------------------------------------------------------------
const firstName = computed(() => (profileStore.fullName || 'Administrador').split(' ')[0]);
const roleBadge = computed(() => {
  const r = profileStore.position || profileStore.role || '';
  return r ? String(r).toUpperCase() : '';
});
const sede = computed(() => profileStore.profileData?.sede || profileStore.profileData?.nombre_sede || '');
const fechaLarga = computed(() => getFechaLargaMexico(new Date(nowTick.value)));

const greeting = computed(() => {
  const h = getHoraMexicoNumber(new Date(nowTick.value)); // reactividad temporal cada 30 s
  if (h < 12) return 'Buenos días';
  if (h < 19) return 'Buenas tardes';
  return 'Buenas noches';
});

const lastReadTime = computed(() => lastUpdated.value ? getHoraMinutoMexico(lastUpdated.value) : '');
const relativeReadText = computed(() => {
  if (!lastUpdated.value) return null;
  const diffMs = nowTick.value - lastUpdated.value.getTime();
  const mins = Math.max(0, Math.floor(diffMs / 60000));
  return mins <= 0 ? 'Hace un momento' : `Hace ${mins} min`;
});

// ------------------------------------------------------------------
// Carga de datos
// ------------------------------------------------------------------
const loadDashboardStats = async () => {
  try {
    isLoading.value = true;
    errorMsg.value = '';
    const res = await api.get('/admin/bi/dashboard', { signal: abortController.signal });
    if (!isMounted.value) return;
    if (res.data && res.data.success) {
      statsData.value = res.data.data;
    } else {
      errorMsg.value = 'No se pudo cargar la información analítica.';
    }
  } catch (error) {
    if (error.name === 'CanceledError' || error.code === 'ERR_CANCELED') return;
    if (!isMounted.value) return;
    console.error('Error al cargar estadísticas de BI:', error);
    errorMsg.value = 'Error de conexión al cargar el panel de Business Intelligence.';
  } finally {
    if (isMounted.value) isLoading.value = false;
  }
};

const loadAdminSessions = async () => {
  isLoadingActividades.value = true;
  try {
    const res = await api.get('/programacion/sesiones-activas?per_page=1000', {
      signal: abortController.signal
    });
    if (!isMounted.value) return;
    todasLasSesionesAdmin.value = res.data?.data ?? [];
  } catch (err) {
    if (err.name === 'CanceledError' || err.code === 'ERR_CANCELED') return;
    if (!isMounted.value) return;
    console.error('Error fetching admin sessions:', err);
  } finally {
    if (isMounted.value) isLoadingActividades.value = false;
  }
};

const refreshAll = async () => {
  const hoyMexico = getHoyMexicoString();
  await Promise.all([
    loadDashboardStats(),
    loadAdminSessions(),
    torneoStore.fetchTorneos({ estatus: 'EN_CURSO' }),
    reservacionAdminStore.fetchReservaciones({ fecha_inicio: hoyMexico, fecha_fin: hoyMexico })
  ]);
  if (isMounted.value) lastUpdated.value = new Date();
};

onMounted(() => {
  isMounted.value = true;
  syncTheme();
  themeObserver = new MutationObserver(syncTheme);
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
  tickTimer = setInterval(() => { nowTick.value = Date.now(); }, 30000);
  refreshAll();
});

onBeforeUnmount(() => {
  if (themeObserver) {
    themeObserver.disconnect();
    themeObserver = null;
  }
});

onUnmounted(() => {
  isMounted.value = false;
  abortController.abort();
  if (tickTimer) clearInterval(tickTimer);
});

// ------------------------------------------------------------------
// Gráficas BI
// ------------------------------------------------------------------
const chartPalette = computed(() => isDark.value
  ? { grid: 'rgba(148,163,184,0.14)', ticks: '#94a3b8', title: '#cbd5e1', tooltipBg: '#1e293b' }
  : { grid: '#eef2ff', ticks: '#64748b', title: '#334155', tooltipBg: '#1e3a8a' }
);

// Pestaña "Afluencia Horaria" → línea con área
const chartReservasHoy = computed(() => {
  const src = statsData.value?.reservas_hoy_hora;
  if (!src || !src.labels?.length) return null;
  return {
    labels: src.labels,
    datasets: [{
      label: 'Hoy',
      data: src.data,
      borderColor: '#2563eb',
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        if (!chartArea) return 'rgba(37,99,235,0.15)';
        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        gradient.addColorStop(0, 'rgba(37,99,235,0.32)');
        gradient.addColorStop(0.6, 'rgba(14,165,233,0.10)');
        gradient.addColorStop(1, 'rgba(14,165,233,0)');
        return gradient;
      },
      fill: true,
      tension: 0.4,
      borderWidth: 3,
      pointRadius: 0,
      pointHitRadius: 16,
      pointBackgroundColor: '#2563eb',
      pointBorderColor: '#ffffff',
      pointBorderWidth: 3,
      pointHoverRadius: 6,
    }]
  };
});

const optionsReservasHoy = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: chartPalette.value.tooltipBg,
      titleFont: { size: 11, weight: '700' },
      bodyFont: { size: 12, weight: '600' },
      padding: 10,
      cornerRadius: 8,
      displayColors: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { precision: 0, color: chartPalette.value.ticks, font: { size: 11, weight: '600' } },
      grid: { color: chartPalette.value.grid, drawBorder: false },
      border: { display: false },
    },
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: { color: chartPalette.value.ticks, font: { size: 11, weight: '600' }, maxRotation: 0, autoSkipPadding: 14 }
    }
  }
}));

// Pestaña "Ocupación y Capacidad" → barras horizontales de espacios
const chartTopEspacios = computed(() => {
  if (!statsData.value?.top_espacios) return null;
  return {
    labels: statsData.value.top_espacios.labels,
    datasets: [{
      label: 'Reservas hoy',
      data: statsData.value.top_espacios.data,
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        if (!chartArea) return '#2563eb';
        const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
        gradient.addColorStop(0, '#1d4ed8');
        gradient.addColorStop(1, '#38bdf8');
        return gradient;
      },
      borderRadius: 8,
      borderSkipped: false,
      maxBarThickness: 22
    }]
  };
});

const optionsTopEspacios = computed(() => ({
  indexAxis: 'y',
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: chartPalette.value.tooltipBg,
      titleFont: { size: 11, weight: '700' },
      bodyFont: { size: 12, weight: '600' },
      padding: 10,
      cornerRadius: 8,
      displayColors: false
    }
  },
  scales: {
    x: {
      beginAtZero: true,
      ticks: { precision: 0, color: chartPalette.value.ticks, font: { size: 11, weight: '600' } },
      grid: { color: chartPalette.value.grid, drawBorder: false },
      border: { display: false },
    },
    y: {
      grid: { display: false },
      border: { display: false },
      ticks: { color: chartPalette.value.title, font: { size: 11, weight: '700' } }
    }
  }
}));

// Resumen del día (columna derecha del panel BI)
const resumenDia = computed(() => {
  const src = statsData.value?.reservas_hoy_hora;
  if (!src || !src.labels?.length) return null;
  const data = (src.data || []).map(v => Number(v) || 0);
  if (!data.length) return null;
  const total = data.reduce((a, b) => a + b, 0);
  let idx = 0;
  data.forEach((v, i) => { if (v > data[idx]) idx = i; });
  return {
    horaPico: src.labels[idx],
    total,
    pico: data[idx] || 0,
    picoPct: total > 0 ? Math.round((data[idx] / total) * 100) : 0,
  };
});

// ------------------------------------------------------------------
// Sesiones de hoy — presentación
// ------------------------------------------------------------------
const primeraFuturaId = computed(() => {
  const ahora = getAhoraMexicoString();
  for (const s of clasesDeHoy.value) {
    if (s.estatus_sesion === 'EN_CURSO') continue;
    if ((s.hora_inicio || '00:00:00') >= ahora) return s.id_sesion;
  }
  return null;
});

const sesionesMostradas = computed(() => {
  const ahora = getAhoraMexicoString();
  const primeraId = primeraFuturaId.value;
  return clasesDeHoy.value.slice(0, 3).map(s => {
    let estadoVisual;
    if (s.estatus_sesion === 'EN_CURSO') estadoVisual = 'EN_CURSO';
    else if (s.id_sesion === primeraId) estadoVisual = 'PROXIMA';
    else if ((s.hora_inicio || '00:00:00') >= ahora) estadoVisual = 'CONFIRMADA';
    else estadoVisual = 'FINALIZADA';
    return { ...s, _estadoVisual: estadoVisual };
  });
});

const sesionesEnVivo = computed(() => clasesEnCursoHoy.value > 0);

// ------------------------------------------------------------------
// Top espacios / Ludoteca / Torneos
// ------------------------------------------------------------------
const topEspacios = computed(() => {
  const src = statsData.value?.top_espacios;
  if (!src?.labels?.length) return [];
  const data = (src.data || []).map(v => Number(v) || 0);
  const max = Math.max(...data, 1);
  return src.labels.slice(0, 3).map((label, i) => ({
    label,
    count: data[i] ?? 0,
    pct: Math.round(((data[i] ?? 0) / max) * 100),
  }));
});

const ludoteca = computed(() => statsData.value?.ludoteca_kpis || null);

const formatMXN = (n) => new Intl.NumberFormat('es-MX', {
  style: 'currency', currency: 'MXN', maximumFractionDigits: 0,
}).format(Number(n) || 0);

const torneoPrincipal = computed(() => (torneoStore.torneos || [])[0] || null);
const torneoEstado = computed(() => {
  const t = torneoPrincipal.value;
  if (!t) return '';
  return t.estatus_torneo || t.estado || '';
});
const torneoLink = computed(() => {
  const t = torneoPrincipal.value;
  if (!t) return '/admin/tournaments';
  const id = t.id_torneo ?? t.id;
  return id ? `/admin/tournaments/${id}` : '/admin/tournaments';
});
const torneoPct = computed(() => {
  const t = torneoPrincipal.value;
  if (!t?.cupo_maximo || t.inscritos_actual == null) return null;
  return Math.min(100, Math.round((t.inscritos_actual / t.cupo_maximo) * 100));
});

</script>

<template>
  <main class="w-full min-h-screen bg-surface-50 pb-16 pt-4 font-sans sm:pt-6">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:space-y-8 sm:px-6 lg:px-8">

      <!-- 1) BIENVENIDA + KPIs flotantes (el banner se desvanece hacia abajo) -->
      <div>
        <DashboardHero
          :greeting="greeting"
          :first-name="firstName"
          :role-badge="roleBadge"
          :fecha-larga="fechaLarga"
          :sede="sede"
          :relative-read-text="relativeReadText"
          :last-read-time="lastReadTime"
          :loading="isLoading"
          @refresh="refreshAll"
        />

        <div class="relative z-10 -mt-12 grid grid-cols-2 gap-3 px-1 sm:-mt-16 sm:gap-4 sm:px-4 xl:grid-cols-4">
          <DashboardKpiCard
            v-for="kpi in kpis"
            :key="kpi.key"
            :label="kpi.label"
            :value="kpi.value"
            :unit="kpi.unit"
            :icon="kpi.icon"
            :tone="kpi.tone"
            :badge="kpi.badge"
            :badge-tone="kpi.badgeTone"
            :footnote="kpi.footnote"
            :footnote-tone="kpi.footnoteTone"
            :loading="showKpiSkeleton"
          />
        </div>
      </div>

      <!-- BANNER DE ERROR / REINTENTO (no invasivo) -->
      <div v-if="errorMsg && !isLoading" class="flex flex-col justify-between gap-3 rounded-2xl bg-rose-50 p-4 ring-1 ring-rose-200 sm:flex-row sm:items-center dark:bg-rose-500/10 dark:ring-rose-500/30">
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-500 text-white">
            <IconWarning class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <p class="m-0 text-sm font-bold text-rose-800 dark:text-rose-200">No pudimos conectar con BI</p>
            <p class="m-0 mt-0.5 text-xs text-rose-700 dark:text-rose-300">{{ errorMsg }}</p>
          </div>
        </div>
        <button
          @click="refreshAll"
          class="min-h-[44px] shrink-0 cursor-pointer rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white transition-all hover:bg-rose-700"
        >
          Reintentar conexión
        </button>
      </div>

      <!-- 2) PANEL RENDIMIENTO Y OCUPACIÓN -->
      <DashboardSection
        title="Rendimiento y Ocupación Operativa"
        subtitle="Métricas de flujo presencial y saturación de infraestructura deportiva."
        :icon="IconGrid"
      >
        <template #badge>
          <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500 motion-reduce:animate-none"></span>
            Telemetría en vivo
          </span>
        </template>
        <template #actions>
          <div class="grid grid-cols-2 gap-1 rounded-xl bg-surface-100 p-1 sm:inline-grid" role="tablist" aria-label="Métrica operativa">
            <button
              v-for="tab in [{ id: 'afluencia', label: 'Afluencia Horaria', short: 'Afluencia' }, { id: 'ocupacion', label: 'Ocupación y Capacidad', short: 'Ocupación' }]"
              :key="tab.id"
              role="tab"
              :aria-selected="activeFusedTab === tab.id"
              @click="activeFusedTab = tab.id"
              class="min-h-[40px] cursor-pointer whitespace-nowrap rounded-lg px-3.5 text-xs font-bold transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
              :class="activeFusedTab === tab.id ? 'bg-primary-600 text-white shadow-[0_8px_16px_-10px_rgba(29,78,216,0.9)]' : 'text-surface-600 hover:text-primary-600'"
            >
              <span class="sm:hidden">{{ tab.short }}</span>
              <span class="hidden sm:inline">{{ tab.label }}</span>
            </button>
          </div>
        </template>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 lg:gap-6">
          <!-- Gráfica -->
          <div class="min-w-0 lg:col-span-2">
            <div v-if="showPanelSkeleton" class="h-64 animate-pulse rounded-2xl bg-surface-100 sm:h-80"></div>

            <template v-else>
              <div class="mb-3 flex items-center justify-between">
                <p class="m-0 text-[11px] font-bold uppercase tracking-wider text-surface-600">
                  {{ activeFusedTab === 'afluencia' ? 'Curva de Flujo (06:00 - 22:00)' : 'Ocupación por Espacio' }}
                </p>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-surface-600">
                  <span class="h-2.5 w-2.5 rounded-full bg-gradient-to-r from-primary-600 to-sky-400"></span> Hoy
                </span>
              </div>
              <div class="h-64 sm:h-80">
                <template v-if="activeFusedTab === 'afluencia'">
                  <BaseChart
                    v-if="chartReservasHoy"
                    :key="'afluencia-' + (isDark ? 'd' : 'l')"
                    type="line"
                    :data="chartReservasHoy"
                    :options="optionsReservasHoy"
                    class="h-full w-full"
                  />
                  <div v-else class="flex h-full flex-col items-center justify-center rounded-2xl border border-dashed border-surface-200 bg-surface-50 text-surface-600">
                    <IconCalendar class="mb-2 h-8 w-8 text-surface-400" />
                    <p class="m-0 text-xs font-bold">Sin datos para hoy</p>
                  </div>
                </template>
                <template v-else>
                  <BaseChart
                    v-if="chartTopEspacios"
                    :key="'ocupacion-' + (isDark ? 'd' : 'l')"
                    type="bar"
                    :data="chartTopEspacios"
                    :options="optionsTopEspacios"
                    class="h-full w-full"
                  />
                  <div v-else class="flex h-full flex-col items-center justify-center rounded-2xl border border-dashed border-surface-200 bg-surface-50 text-surface-600">
                    <IconSportCourt class="mb-2 h-8 w-8 text-surface-400" />
                    <p class="m-0 text-xs font-bold">Sin datos para hoy</p>
                  </div>
                </template>
              </div>
            </template>
          </div>

          <!-- Resumen del día -->
          <div class="lg:col-span-1">
            <p class="m-0 mb-3 text-[11px] font-bold uppercase tracking-wider text-surface-600">Resumen del día</p>
            <!-- TODO: la comparativa semanal requiere un campo nuevo en BIController (ej. asistencia_semanal). -->
            <div v-if="resumenDia" class="grid grid-cols-2 gap-3 lg:grid-cols-1">
              <div class="relative col-span-2 overflow-hidden rounded-2xl bg-gradient-to-br from-primary-600 to-sky-500 p-4 text-white lg:col-span-1">
                <div class="pointer-events-none absolute -right-6 -top-6 h-20 w-20 rounded-full bg-white/15" aria-hidden="true"></div>
                <div class="flex items-center gap-2 text-xs font-semibold text-white/85">
                  <IconClock class="h-4 w-4" /> Hora pico
                </div>
                <p class="m-0 mt-1 text-3xl font-extrabold tabular-nums">{{ resumenDia.horaPico }}</p>
                <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-white/25">
                  <div class="h-full rounded-full bg-white/95 transition-all duration-500" :style="{ width: resumenDia.picoPct + '%' }"></div>
                </div>
                <p class="m-0 mt-1.5 text-[11px] font-semibold text-white/90">{{ resumenDia.picoPct }}% de las reservas del día</p>
              </div>
              <div class="rounded-2xl bg-primary-50 p-4 dark:bg-primary-500/10">
                <p class="m-0 text-xs font-semibold text-surface-600">Reservas en hora pico</p>
                <p class="m-0 mt-1 text-2xl font-extrabold tabular-nums text-primary-700 dark:text-primary-300">{{ resumenDia.pico }}</p>
              </div>
              <div class="rounded-2xl bg-sky-50 p-4 dark:bg-sky-500/10">
                <p class="m-0 text-xs font-semibold text-surface-600">Reservas totales</p>
                <p class="m-0 mt-1 text-2xl font-extrabold tabular-nums text-sky-700 dark:text-sky-300">{{ resumenDia.total }}</p>
              </div>
            </div>
            <p v-else class="m-0 rounded-2xl bg-surface-50 p-4 text-xs font-semibold text-surface-600">Sin datos para hoy</p>
          </div>
        </div>
      </DashboardSection>

      <!-- 3) SESIONES DE HOY -->
      <DashboardSection
        title="Sesiones de Hoy"
        subtitle="Monitoreo directo de cupos, docentes y desarrollo de actividades."
        :icon="IconCalendar"
        tone="emerald"
      >
        <template #badge>
          <span
            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider"
            :class="sesionesEnVivo ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-surface-100 text-surface-600'"
          >
            <span v-if="sesionesEnVivo" class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500 motion-reduce:animate-none"></span>
            <template v-if="sesionesEnVivo">En vivo · {{ sesionesMostradas.length }} de {{ clasesDeHoy.length }}</template>
            <template v-else>{{ clasesDeHoy.length }} programadas</template>
          </span>
        </template>
        <template #actions>
          <RouterLink to="/admin/activities" class="hidden items-center gap-1 text-xs font-bold text-primary-600 transition-colors hover:text-primary-700 sm:inline-flex">
            Ver todas →
          </RouterLink>
        </template>

        <div v-if="showSesionesSkeleton" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-[84px] animate-pulse rounded-2xl bg-surface-100"></div>
        </div>

        <div v-else-if="clasesDeHoy.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-surface-200 bg-surface-50 py-12 text-surface-600">
          <IconCalendar class="mb-2 h-10 w-10 text-surface-400" />
          <p class="m-0 text-sm font-bold">No hay sesiones programadas para hoy</p>
        </div>

        <template v-else>
          <div class="space-y-3">
            <DashboardSessionRow v-for="sesion in sesionesMostradas" :key="sesion.id_sesion" :sesion="sesion" />
          </div>
          <RouterLink to="/admin/activities" class="mt-4 flex items-center justify-center rounded-xl bg-primary-50 py-2.5 text-xs font-bold text-primary-700 sm:hidden dark:bg-primary-500/10 dark:text-primary-300">
            Ver todas las sesiones →
          </RouterLink>
        </template>
      </DashboardSection>

      <!-- 4) ACCIONES DIRECTAS -->
      <section class="space-y-3">
        <p class="m-0 text-[11px] font-bold uppercase tracking-wider text-surface-600">Acciones directas de dirección</p>
        <DashboardQuickActions />
      </section>

      <!-- 5) MÓDULOS COMPLEMENTARIOS -->
      <section class="space-y-4">
        <div class="flex items-center justify-between gap-3">
          <h2 class="m-0 text-sm font-bold text-surface-900">Módulos complementarios del club</h2>
          <span v-if="lastReadTime" class="shrink-0 whitespace-nowrap text-[11px] font-semibold text-surface-600">Corte {{ lastReadTime }} hrs</span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          <!-- Top Espacios -->
          <DashboardModuleCard title="Top Espacios Demandados" :icon="IconSportCourt" tone="blue" to="/admin/spaces/list" link-label="Ver espacios">
            <template #badge>
              <span class="shrink-0 rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-primary-700 dark:bg-white/10 dark:text-primary-300">Hoy</span>
            </template>
            <div v-if="showPanelSkeleton" class="space-y-3">
              <div v-for="i in 3" :key="i" class="h-9 animate-pulse rounded-lg bg-surface-100"></div>
            </div>
            <div v-else-if="topEspacios.length === 0" class="flex flex-1 items-center justify-center py-6 text-xs font-semibold text-surface-600">
              Sin datos para hoy
            </div>
            <ol v-else class="m-0 flex-1 list-none space-y-3.5 p-0">
              <li v-for="(esp, i) in topEspacios" :key="i" class="flex items-center gap-3">
                <span
                  class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-extrabold"
                  :class="i === 0 ? 'bg-gradient-to-br from-primary-500 to-primary-700 text-white' : 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300'"
                >
                  {{ i + 1 }}
                </span>
                <div class="min-w-0 flex-1">
                  <div class="mb-1 flex items-center justify-between gap-2">
                    <span class="truncate text-xs font-semibold text-surface-700" :title="esp.label">{{ esp.label }}</span>
                    <span class="text-xs font-bold tabular-nums text-surface-900">{{ esp.pct }}%</span>
                  </div>
                  <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-100" :title="`${esp.count} reservas`">
                    <div class="h-full rounded-full bg-gradient-to-r from-primary-600 to-sky-400" :style="{ width: esp.pct + '%' }"></div>
                  </div>
                </div>
              </li>
            </ol>
          </DashboardModuleCard>

          <!-- Ludoteca -->
          <DashboardModuleCard title="Ludoteca Britania Kids" :icon="IconBaby" tone="rose" to="/admin/ludoteca" link-label="Ver ludoteca">
            <template #badge>
              <span
                v-if="ludoteca"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider"
                :class="ludoteca.alertas_tiempo === 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                {{ ludoteca.alertas_tiempo === 0 ? 'Normal' : 'Atención' }}
              </span>
            </template>
            <div v-if="showPanelSkeleton" class="grid grid-cols-2 gap-3">
              <div v-for="i in 4" :key="i" class="h-16 animate-pulse rounded-xl bg-surface-100"></div>
            </div>
            <div v-else-if="!ludoteca" class="flex flex-1 items-center justify-center py-6 text-xs font-semibold text-surface-600">
              Sin datos disponibles
            </div>
            <div v-else class="grid flex-1 grid-cols-2 gap-3">
              <div class="rounded-xl bg-rose-50 p-3 dark:bg-rose-500/10">
                <p class="m-0 text-[11px] font-bold uppercase tracking-wider text-surface-600">Activos</p>
                <p class="m-0 mt-0.5 text-xl font-extrabold tabular-nums text-rose-600 dark:text-rose-300">{{ ludoteca.ninos_activos }} <span class="text-xs font-semibold text-surface-600">niños</span></p>
              </div>
              <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-500/10">
                <p class="m-0 text-[11px] font-bold uppercase tracking-wider text-surface-600">Entregados</p>
                <p class="m-0 mt-0.5 text-xl font-extrabold tabular-nums text-emerald-600 dark:text-emerald-300">{{ ludoteca.ninos_entregados }}</p>
              </div>
              <div class="rounded-xl bg-primary-50 p-3 dark:bg-primary-500/10">
                <p class="m-0 text-[11px] font-bold uppercase tracking-wider text-surface-600">Cuota día</p>
                <p class="m-0 mt-0.5 text-lg font-extrabold tabular-nums text-primary-700 dark:text-primary-300">{{ formatMXN(ludoteca.ingresos_hoy) }}</p>
              </div>
              <div class="rounded-xl p-3" :class="ludoteca.alertas_tiempo > 0 ? 'bg-amber-50 dark:bg-amber-500/10' : 'bg-surface-50'">
                <p class="m-0 text-[11px] font-bold uppercase tracking-wider text-surface-600">Con retraso</p>
                <p class="m-0 mt-0.5 text-xl font-extrabold tabular-nums" :class="ludoteca.alertas_tiempo > 0 ? 'text-amber-600 dark:text-amber-300' : 'text-surface-700'">{{ ludoteca.alertas_tiempo }}</p>
              </div>
            </div>
          </DashboardModuleCard>

          <!-- Torneos -->
          <DashboardModuleCard title="Torneos en Curso" :icon="IconTrophy" tone="amber" :to="torneoLink" link-label="Ver cuadro de llaves" class="md:col-span-2 lg:col-span-1">
            <div v-if="torneoStore.loading" class="flex flex-1 items-center justify-center py-6">
              <LoadingSpinner size="sm" />
            </div>
            <div v-else-if="!torneoPrincipal" class="flex flex-1 flex-col items-center justify-center gap-2 py-6 text-xs font-semibold text-surface-600">
              <IconTrophy class="h-8 w-8 text-surface-400" />
              Sin torneos en curso
            </div>
            <div v-else class="flex-1">
              <div class="mb-2 flex flex-wrap items-center gap-2">
                <span v-if="torneoEstado" class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                  <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-current motion-reduce:animate-none"></span>
                  {{ torneoEstado.replace(/_/g, ' ') }}
                </span>
                <span v-if="torneoPrincipal.disciplina?.nombre_disciplina" class="text-xs font-semibold text-surface-600">
                  {{ torneoPrincipal.disciplina.nombre_disciplina }}
                </span>
              </div>
              <h4 class="m-0 text-base font-bold leading-tight text-surface-900">{{ torneoPrincipal.nombre_torneo }}</h4>
              <template v-if="torneoPrincipal.inscritos_actual != null">
                <div class="mt-4 flex items-baseline justify-between">
                  <span class="text-xs font-semibold text-surface-600">Inscritos</span>
                  <span class="text-sm font-extrabold tabular-nums text-surface-900">
                    {{ torneoPrincipal.inscritos_actual }}<template v-if="torneoPrincipal.cupo_maximo"><span class="font-semibold text-surface-600"> / {{ torneoPrincipal.cupo_maximo }}</span></template>
                  </span>
                </div>
                <div v-if="torneoPct !== null" class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-amber-100 dark:bg-amber-500/15">
                  <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-orange-500" :style="{ width: torneoPct + '%' }"></div>
                </div>
              </template>
            </div>
          </DashboardModuleCard>
        </div>
      </section>

      <!-- 6) RESERVAS PRÓXIMAS (Hoy) -->
      <DashboardSection
        title="Reservas Próximas (Hoy)"
        subtitle="Listado de reservas On Demand del día."
        :icon="IconBookmark"
        tone="violet"
      >
        <template #badge>
          <span v-if="reservasProximasHoy.length" class="rounded-full bg-violet-50 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">
            {{ reservasProximasHoy.length }} hoy
          </span>
        </template>

        <div class="space-y-2.5 sm:max-h-[440px] sm:overflow-y-auto sm:pr-1">
          <div v-if="reservacionAdminStore.loading.reservaciones" class="flex flex-col items-center justify-center py-12 text-surface-600">
            <LoadingSpinner class="mb-2 h-8 w-8" />
            <span class="text-xs font-bold">Cargando reservas…</span>
          </div>

          <div v-else-if="reservasProximasHoy.length === 0" class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-surface-200 bg-surface-50 py-12 text-surface-600">
            <IconCalendar class="mb-2 h-10 w-10 text-surface-400" />
            <p class="m-0 text-xs font-bold">No hay reservas próximas hoy</p>
          </div>

          <template v-else>
            <DashboardReservationRow v-for="reserva in reservasProximasHoy" :key="reserva.id_reserva" :reserva="reserva" />
          </template>
        </div>
      </DashboardSection>

    </div>
  </main>
</template>
