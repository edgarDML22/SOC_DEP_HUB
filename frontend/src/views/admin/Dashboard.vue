<script setup>
import { ref, onMounted, onUnmounted, onBeforeUnmount, computed } from 'vue';
import { useAdminStore } from '@/stores/profiles/adminStore';
import { useTournamentStore } from '@/stores/tournamentStore';
import { useReservacionAdminStore } from '@/stores/admin/reservationAdminStore';
import { IconGuests, IconCalendar, IconGrid, IconTrophy, IconBaby, IconWarning } from '@/components/icons';
import IconBookmark from '@/components/icons/IconBookmark.vue';
import IconSportCourt from '@/components/icons/IconSportCourt.vue';
import api from '@/services/api';
import BaseChart from '@/components/admin/BaseChart.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';
import DashboardKpiCard from '@/components/admin/DashboardKpiCard.vue';
import DashboardQuickActions from '@/components/admin/DashboardQuickActions.vue';

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
      tone: 'blue',
      footnote: `${reservasCompletadasHoy.value} completadas`,
    },
    {
      key: 'noshow',
      label: 'No-Shows / Incidencias',
      value: noShowsHoy.value,
      unit: 'reportes acumulados',
      icon: IconWarning,
      tone: 'red',
      badge: noShowsHoy.value > 0 ? 'Requiere revisión' : '',
      badgeTone: 'red',
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
  ? { grid: 'rgba(148,163,184,0.14)', ticks: '#94a3b8', title: '#94a3b8', tooltipBg: '#1e293b' }
  : { grid: '#e5e7eb', ticks: '#6b7280', title: '#6b7280', tooltipBg: '#111827' }
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
        gradient.addColorStop(0, 'rgba(37,99,235,0.22)');
        gradient.addColorStop(1, 'rgba(37,99,235,0.02)');
        return gradient;
      },
      fill: true,
      tension: 0.4,
      borderWidth: 2.5,
      pointRadius: 3,
      pointBackgroundColor: '#2563eb',
      pointBorderColor: '#ffffff',
      pointBorderWidth: 2,
      pointHoverRadius: 5,
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
      ticks: { color: chartPalette.value.ticks, font: { size: 11, weight: '600' } }
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
        gradient.addColorStop(0, '#1e40af');
        gradient.addColorStop(1, '#60a5fa');
        return gradient;
      },
      borderRadius: 5,
      barThickness: 18
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
const SESION_ESTADO_UI = {
  EN_CURSO: { chip: 'bg-emerald-50 text-emerald-700 border-emerald-200', label: 'En curso', row: 'border-l-[3px] border-l-emerald-500', btn: 'Ver lista' },
  PROXIMA: { chip: 'bg-primary-50 text-primary-700 border-primary-200', label: 'Próxima', row: 'border-l-[3px] border-l-primary-200', btn: 'Gestionar' },
  CONFIRMADA: { chip: 'bg-surface-100 text-surface-600 border-surface-200', label: 'Confirmada', row: '', btn: 'Gestionar' },
  FINALIZADA: { chip: 'bg-surface-100 text-surface-500 border-surface-200', label: 'Finalizada', row: '', btn: 'Gestionar' },
};

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

const formatHora = (h) => (h ? String(h).slice(0, 5) : '--:--');
const sesionInstructor = (s) => (typeof s.instructor === 'object' ? s.instructor?.nombre : s.instructor) || '';
const sesionCupos = (s) => {
  const inscritos = (typeof s.cantidad_inscritos === 'number') ? s.cantidad_inscritos : (s.cantidad_inscritos ?? 0);
  if (s.cupo_maximo) return `${inscritos} / ${s.cupo_maximo} cupos`;
  return `${inscritos} inscritos`;
};
const sesionPlazas = (s) => {
  if (!s.cupo_maximo) return null;
  const inscritos = (typeof s.cantidad_inscritos === 'number') ? s.cantidad_inscritos : 0;
  const libres = s.cupo_maximo - inscritos;
  if (libres <= 0) return 'GRUPO CERRADO';
  const pct = Math.round((inscritos / s.cupo_maximo) * 100);
  return `${pct}% LLENO`;
};

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

// ------------------------------------------------------------------
// Helpers de reservas (sección final)
// ------------------------------------------------------------------
const formatEstatus = (estatus) => {
  const labels = {
    'DISPONIBLE': 'Disponible',
    'EN_CURSO': 'En Curso',
    'FINALIZADA': 'Finalizada',
    'CANCELADA': 'Cancelada',
    'ACTIVA': 'Activa',
    'PENDIENTE': 'Pendiente',
    'COMPLETADA': 'Completada',
    'NO_SHOW': 'No Show'
  };
  return labels[estatus] || estatus;
};

const ESTATUS_COLORS = {
  'DISPONIBLE': 'bg-emerald-50 text-emerald-700 border-emerald-200',
  'EN_CURSO':   'bg-amber-50 text-amber-700 border-amber-200',
  'FINALIZADA': 'bg-primary-50 text-primary-700 border-primary-200',
  'CANCELADA':  'bg-red-50 text-red-700 border-red-200',
  'ACTIVA':     'bg-emerald-50 text-emerald-700 border-emerald-200',
  'PENDIENTE':  'bg-amber-50 text-amber-700 border-amber-200',
  'COMPLETADA': 'bg-primary-50 text-primary-700 border-primary-200',
  'NO_SHOW':    'bg-surface-100 text-surface-500 border-surface-200'
};

const getDisciplineColorClasses = (name) => {
  const cleanName = String(name || '').toUpperCase().trim();

  if (cleanName.includes('TENIS')) {
    return { bg: 'bg-emerald-50 border-emerald-200/60', icon: 'text-emerald-600' };
  }
  if (cleanName.includes('MEDITAC') || cleanName.includes('YOGA') || cleanName.includes('PILATES')) {
    return { bg: 'bg-purple-50 border-purple-200/60', icon: 'text-purple-600' };
  }
  if (cleanName.includes('SPINNING') || cleanName.includes('CYCLE') || cleanName.includes('BICI')) {
    return { bg: 'bg-primary-50 border-primary-200/60', icon: 'text-primary-600' };
  }
  if (cleanName.includes('ZUMBA') || cleanName.includes('BAILE') || cleanName.includes('DANCE')) {
    return { bg: 'bg-rose-50 border-rose-200/60', icon: 'text-rose-600' };
  }
  if (cleanName.includes('GYM') || cleanName.includes('FITNESS') || cleanName.includes('INSTRUCTOR') || cleanName.includes('FUNCIONAL') || cleanName.includes('CROSSFIT')) {
    return { bg: 'bg-amber-50 border-amber-200/60', icon: 'text-amber-600' };
  }
  if (cleanName.includes('NATAC') || cleanName.includes('ALBERCA') || cleanName.includes('SWIM')) {
    return { bg: 'bg-sky-50 border-sky-200/60', icon: 'text-sky-600' };
  }
  if (cleanName.includes('PADEL') || cleanName.includes('PÁDEL') || cleanName.includes('SQUASH')) {
    return { bg: 'bg-teal-50 border-teal-200/60', icon: 'text-teal-600' };
  }
  if (cleanName.includes('FUTBOL') || cleanName.includes('FÚTBOL') || cleanName.includes('SOCCER')) {
    return { bg: 'bg-green-50 border-green-200/60', icon: 'text-green-600' };
  }
  if (cleanName.includes('BASKET') || cleanName.includes('BÁSQUET') || cleanName.includes('BALONCESTO')) {
    return { bg: 'bg-orange-50 border-orange-200/60', icon: 'text-orange-600' };
  }
  if (cleanName.includes('BOX') || cleanName.includes('KICKBOX') || cleanName.includes('KARATE') || cleanName.includes('COMBATE')) {
    return { bg: 'bg-red-50 border-red-200/60', icon: 'text-red-600' };
  }

  const hashes = [
    { bg: 'bg-primary-50 border-primary-200/60', icon: 'text-primary-600' },
    { bg: 'bg-emerald-50 border-emerald-200/60', icon: 'text-emerald-600' },
    { bg: 'bg-purple-50 border-purple-200/60', icon: 'text-purple-600' },
    { bg: 'bg-rose-50 border-rose-200/60', icon: 'text-rose-600' },
    { bg: 'bg-amber-50 border-amber-200/60', icon: 'text-amber-600' },
    { bg: 'bg-sky-50 border-sky-200/60', icon: 'text-sky-600' },
    { bg: 'bg-teal-50 border-teal-200/60', icon: 'text-teal-600' }
  ];
  const charCodeSum = cleanName.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
  return hashes[charCodeSum % hashes.length];
};
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans pb-16 pt-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

      <!-- 1) HEADER -->
      <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-6">
        <div class="min-w-0">
          <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-2xl sm:text-3xl font-bold text-surface-900 tracking-tight m-0">
              {{ greeting }}, <span class="text-primary-600">{{ firstName }}</span>
            </h1>
            <span
              v-if="roleBadge"
              class="inline-flex items-center px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase tracking-wider"
            >
              {{ roleBadge }}
            </span>
          </div>
          <p class="text-sm font-medium text-surface-600 mt-2 m-0 max-w-2xl">
            {{ fechaLarga }}<template v-if="sede"> · {{ sede }}</template>
          </p>
        </div>

        <div class="flex items-center gap-4 shrink-0">
          <div class="text-right" aria-live="polite">
            <p class="text-[10px] font-bold uppercase tracking-wider text-surface-500 m-0">Última lectura</p>
            <p class="text-xs font-bold text-surface-700 m-0">
              <template v-if="relativeReadText">{{ relativeReadText }} <span class="text-surface-500 font-semibold">({{ lastReadTime }})</span></template>
              <template v-else>Sin lectura</template>
            </p>
          </div>
          <button
            @click="refreshAll"
            :disabled="isLoading"
            class="inline-flex items-center gap-2 min-h-[44px] px-4 py-2 bg-white border border-surface-200 text-surface-800 rounded-xl text-xs font-bold shadow-sm hover:bg-surface-50 hover:border-surface-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 transition-all duration-200 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <LoadingSpinner v-if="isLoading" size="sm" />
            <svg v-else class="w-4 h-4 text-surface-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            {{ isLoading ? 'Actualizando…' : 'Actualizar datos' }}
          </button>
        </div>
      </div>

      <!-- BANNER DE ERROR / REINTENTO (no invasivo) -->
      <div v-if="errorMsg && !isLoading" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 bg-red-50 border border-red-200 rounded-2xl">
        <div class="flex items-start gap-3 min-w-0">
          <IconWarning class="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
          <div class="min-w-0">
            <p class="text-sm font-bold text-red-800 m-0">No pudimos conectar con BI</p>
            <p class="text-xs text-red-700 m-0 mt-0.5">{{ errorMsg }}</p>
          </div>
        </div>
        <button
          @click="refreshAll"
          class="shrink-0 min-h-[44px] px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm cursor-pointer"
        >
          Reintentar conexión
        </button>
      </div>

      <!-- 2) CUATRO KPIs -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
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

      <!-- 3) PANEL RENDIMIENTO Y OCUPACIÓN -->
      <section class="bg-white border border-surface-200 rounded-2xl shadow-sm p-5 sm:p-6 space-y-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <h2 class="text-base font-bold text-surface-900 m-0">Rendimiento y Ocupación Operativa</h2>
              <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-surface-100 text-surface-600 border border-surface-200 text-[10px] font-bold uppercase tracking-wider">Telemetría en Vivo</span>
            </div>
            <p class="text-xs font-medium text-surface-600 m-0 mt-1">Métricas de flujo presencial y saturación de infraestructura deportiva.</p>
          </div>

          <div class="inline-flex p-1 bg-surface-100 rounded-xl border border-surface-200 self-start lg:self-auto" role="tablist" aria-label="Métrica operativa">
            <button
              role="tab"
              :aria-selected="activeFusedTab === 'afluencia'"
              @click="activeFusedTab = 'afluencia'"
              class="min-h-[44px] px-3.5 rounded-lg text-xs font-bold transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
              :class="activeFusedTab === 'afluencia' ? 'bg-white text-surface-900 shadow-sm' : 'text-surface-600 hover:text-surface-800'"
            >
              Afluencia Horaria
            </button>
            <button
              role="tab"
              :aria-selected="activeFusedTab === 'ocupacion'"
              @click="activeFusedTab = 'ocupacion'"
              class="min-h-[44px] px-3.5 rounded-lg text-xs font-bold transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
              :class="activeFusedTab === 'ocupacion' ? 'bg-white text-surface-900 shadow-sm' : 'text-surface-600 hover:text-surface-800'"
            >
              Ocupación y Capacidad
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Columna izquierda: gráfica -->
          <div class="lg:col-span-2">
            <div v-if="showPanelSkeleton" class="h-64 sm:h-80 md:h-96 rounded-2xl bg-surface-100 border border-surface-200 animate-pulse"></div>

            <template v-else>
              <template v-if="activeFusedTab === 'afluencia'">
                <div class="flex items-center justify-between mb-3">
                  <p class="text-[11px] font-bold uppercase tracking-wider text-surface-500 m-0">Curva de Flujo (06:00 - 22:00)</p>
                  <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-surface-600">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span> Hoy
                  </span>
                </div>
                <div class="h-64 sm:h-80 md:h-96">
                  <BaseChart
                    v-if="chartReservasHoy"
                    :key="'afluencia-' + (isDark ? 'd' : 'l')"
                    type="line"
                    :data="chartReservasHoy"
                    :options="optionsReservasHoy"
                    class="h-full w-full"
                  />
                  <div v-else class="h-full flex flex-col items-center justify-center text-surface-500 bg-surface-50 rounded-2xl border border-dashed border-surface-200">
                    <IconCalendar class="w-8 h-8 text-surface-300 mb-2" />
                    <p class="text-xs font-bold m-0">Sin datos para hoy</p>
                  </div>
                </div>
              </template>

              <template v-else>
                <div class="flex items-center justify-between mb-3">
                  <p class="text-[11px] font-bold uppercase tracking-wider text-surface-500 m-0">Ocupación por Espacio</p>
                  <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-surface-600">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span> Hoy
                  </span>
                </div>
                <div class="h-64 sm:h-80 md:h-96">
                  <BaseChart
                    v-if="chartTopEspacios"
                    :key="'ocupacion-' + (isDark ? 'd' : 'l')"
                    type="bar"
                    :data="chartTopEspacios"
                    :options="optionsTopEspacios"
                    class="h-full w-full"
                  />
                  <div v-else class="h-full flex flex-col items-center justify-center text-surface-500 bg-surface-50 rounded-2xl border border-dashed border-surface-200">
                    <IconSportCourt class="w-8 h-8 text-surface-300 mb-2" />
                    <p class="text-xs font-bold m-0">Sin datos para hoy</p>
                  </div>
                </div>
              </template>
            </template>
          </div>

          <!-- Columna derecha: Resumen del día -->
          <div class="lg:col-span-1">
            <div class="h-full rounded-2xl border border-surface-200 bg-surface-50/60 p-5">
              <p class="text-[11px] font-bold uppercase tracking-wider text-surface-500 m-0">Resumen del día</p>
              <!-- TODO: la comparativa semanal requiere un campo nuevo en BIController (ej. asistencia_semanal). -->
              <template v-if="resumenDia">
                <div class="mt-4 space-y-4">
                  <div>
                    <div class="flex items-center justify-between mb-1.5">
                      <span class="text-xs font-semibold text-surface-600">Hora pico</span>
                      <span class="text-sm font-bold text-surface-900 tabular-nums">{{ resumenDia.horaPico }}</span>
                    </div>
                    <div class="flex items-center justify-between mb-1.5">
                      <span class="text-xs font-semibold text-surface-600">Reservas en hora pico</span>
                      <span class="text-sm font-bold text-surface-900 tabular-nums">{{ resumenDia.pico }}</span>
                    </div>
                    <div class="w-full bg-surface-200 rounded-full h-1.5 overflow-hidden">
                      <div class="h-full bg-primary-600 rounded-full transition-all duration-500" :style="{ width: resumenDia.picoPct + '%' }"></div>
                    </div>
                  </div>
                  <div class="border-t border-surface-200 pt-4 flex items-center justify-between">
                    <span class="text-xs font-semibold text-surface-600">Reservas totales</span>
                    <span class="text-lg font-bold text-surface-900 tabular-nums">{{ resumenDia.total }}</span>
                  </div>
                </div>
              </template>
              <p v-else class="text-xs font-semibold text-surface-500 mt-4">Sin datos para hoy</p>
            </div>
          </div>
        </div>
      </section>

      <!-- 4) SESIONES DE HOY -->
      <section class="bg-white border border-surface-200 rounded-2xl shadow-sm p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
          <div class="flex items-center gap-3 flex-wrap">
            <h2 class="text-base font-bold text-surface-900 m-0">Sesiones de Hoy</h2>
            <span
              class="inline-flex items-center px-2.5 py-1 rounded-md border text-[10px] font-bold uppercase tracking-wider"
              :class="sesionesEnVivo ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-surface-100 text-surface-600 border-surface-200'"
            >
              <template v-if="sesionesEnVivo">En vivo · {{ sesionesMostradas.length }} de {{ clasesDeHoy.length }}</template>
              <template v-else>{{ clasesDeHoy.length }} programadas</template>
            </span>
          </div>
          <p class="text-xs font-medium text-surface-600 m-0">Monitoreo directo de cupos, docentes y desarrollo de actividades.</p>
        </div>

        <div v-if="showSesionesSkeleton" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-[76px] rounded-xl bg-surface-100 border border-surface-200 animate-pulse"></div>
        </div>

        <div v-else-if="clasesDeHoy.length === 0" class="flex flex-col items-center justify-center py-12 text-surface-500 bg-surface-50 rounded-xl border border-dashed border-surface-200">
          <IconCalendar class="w-10 h-10 text-surface-300 mb-2" />
          <p class="text-sm font-bold m-0">No hay sesiones programadas para hoy</p>
        </div>

        <template v-else>
          <div class="space-y-3">
            <div
              v-for="sesion in sesionesMostradas"
              :key="sesion.id_sesion"
              class="flex flex-col sm:flex-row sm:items-center gap-4 p-3.5 rounded-xl bg-white border border-surface-200 hover:border-surface-300 transition-colors"
              :class="SESION_ESTADO_UI[sesion._estadoVisual]?.row"
            >
              <!-- Bloque de hora -->
              <div class="flex sm:flex-col items-center sm:items-center gap-2 sm:gap-0 shrink-0 bg-surface-50 border border-surface-200 rounded-xl px-3 py-2 sm:w-20 text-center">
                <span class="text-lg font-bold text-surface-900 tabular-nums leading-none">{{ formatHora(sesion.hora_inicio) }}</span>
                <span class="text-[10px] font-semibold text-surface-500 tabular-nums sm:mt-1">{{ formatHora(sesion.hora_fin) }}</span>
              </div>

              <!-- Detalle -->
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <DisciplineIcon
                    v-if="sesion.disciplina"
                    :name="sesion.disciplina"
                    class="w-4 h-4 fill-current shrink-0"
                    :class="getDisciplineColorClasses(sesion.disciplina).icon"
                  />
                  <span class="font-bold text-sm text-surface-900 truncate" :title="sesion.disciplina">{{ sesion.disciplina || 'Actividad' }}</span>
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-md border text-[9px] font-bold uppercase tracking-wider"
                    :class="SESION_ESTADO_UI[sesion._estadoVisual]?.chip"
                  >
                    {{ SESION_ESTADO_UI[sesion._estadoVisual]?.label }}
                  </span>
                </div>
                <p class="text-xs font-medium text-surface-600 m-0 mt-1 truncate">
                  <template v-if="sesion.espacio">{{ sesion.espacio }}</template>
                  <template v-if="sesion.espacio && sesionInstructor(sesion)"> · </template>
                  <template v-if="sesionInstructor(sesion)">{{ sesionInstructor(sesion) }}</template>
                </p>
              </div>

              <!-- Cupos + botón -->
              <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                <div class="text-left sm:text-right">
                  <p class="text-xs font-bold text-surface-800 m-0 tabular-nums">{{ sesionCupos(sesion) }}</p>
                  <p v-if="sesionPlazas(sesion)" class="text-[10px] font-bold uppercase tracking-wider text-surface-500 m-0">{{ sesionPlazas(sesion) }}</p>
                </div>
                <RouterLink
                  to="/admin/activities"
                  class="inline-flex items-center min-h-[44px] px-3.5 rounded-xl text-xs font-bold border transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                  :class="sesion._estadoVisual === 'EN_CURSO' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-white text-surface-700 border-surface-200 hover:bg-surface-50'"
                >
                  {{ SESION_ESTADO_UI[sesion._estadoVisual]?.btn }}
                </RouterLink>
              </div>
            </div>
          </div>

          <div class="mt-4 text-right">
            <RouterLink to="/admin/activities" class="text-xs font-bold text-primary-600 hover:text-primary-700 transition-colors">
              Ver todas →
            </RouterLink>
          </div>
        </template>
      </section>

      <!-- 5) ACCIONES DIRECTAS -->
      <section class="space-y-3">
        <p class="text-[11px] font-bold uppercase tracking-wider text-surface-500 m-0">Acciones directas de dirección</p>
        <DashboardQuickActions />
      </section>

      <!-- 6) MÓDULOS COMPLEMENTARIOS -->
      <section class="space-y-4">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <IconGrid class="w-4 h-4 text-surface-500" />
            <h2 class="text-sm font-bold text-surface-900 m-0">Módulos complementarios del club</h2>
          </div>
          <span v-if="lastReadTime" class="text-[11px] font-semibold text-surface-500">Corte {{ lastReadTime }} hrs</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <!-- Top Espacios -->
          <div class="bg-white border border-surface-200 rounded-2xl shadow-sm p-5 flex flex-col">
            <div class="flex items-center justify-between gap-2 mb-4">
              <div class="flex items-center gap-2 min-w-0">
                <span class="w-8 h-8 rounded-lg bg-primary-50 border border-primary-100 text-primary-600 flex items-center justify-center shrink-0">
                  <IconSportCourt class="w-4 h-4" />
                </span>
                <h3 class="text-sm font-bold text-surface-900 m-0 truncate">Top Espacios Demandados</h3>
              </div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-surface-500 shrink-0">Hoy</span>
            </div>

            <div v-if="showPanelSkeleton" class="space-y-3">
              <div v-for="i in 3" :key="i" class="h-9 rounded-lg bg-surface-100 animate-pulse"></div>
            </div>
            <div v-else-if="topEspacios.length === 0" class="flex-1 flex items-center justify-center py-6 text-surface-500 text-xs font-semibold">
              Sin datos para hoy
            </div>
            <div v-else class="space-y-3 flex-1">
              <div v-for="(esp, i) in topEspacios" :key="i">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-xs font-semibold text-surface-700 truncate" :title="esp.label">{{ esp.label }}</span>
                  <span class="text-xs font-bold text-surface-900 tabular-nums">{{ esp.pct }}%</span>
                </div>
                <div class="w-full bg-surface-100 rounded-full h-1.5 overflow-hidden" :title="`${esp.count} reservas`">
                  <div class="h-full bg-primary-600 rounded-full" :style="{ width: esp.pct + '%' }"></div>
                </div>
              </div>
            </div>

            <RouterLink to="/admin/spaces/list" class="mt-4 text-xs font-bold text-primary-600 hover:text-primary-700 transition-colors">
              Ver espacios →
            </RouterLink>
          </div>

          <!-- Ludoteca -->
          <div class="bg-white border border-surface-200 rounded-2xl shadow-sm p-5 flex flex-col">
            <div class="flex items-center justify-between gap-2 mb-4">
              <div class="flex items-center gap-2 min-w-0">
                <span class="w-8 h-8 rounded-lg bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                  <IconBaby class="w-4 h-4" />
                </span>
                <h3 class="text-sm font-bold text-surface-900 m-0 truncate">Ludoteca Britania Kids</h3>
              </div>
              <span
                v-if="ludoteca"
                class="inline-flex items-center px-2 py-0.5 rounded-md border text-[10px] font-bold uppercase tracking-wider shrink-0"
                :class="ludoteca.alertas_tiempo === 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
              >
                {{ ludoteca.alertas_tiempo === 0 ? 'Normal' : 'Atención' }}
              </span>
            </div>

            <div v-if="showPanelSkeleton" class="space-y-3">
              <div class="h-16 rounded-xl bg-surface-100 animate-pulse"></div>
              <div class="h-16 rounded-xl bg-surface-100 animate-pulse"></div>
            </div>
            <div v-else-if="!ludoteca" class="flex-1 flex items-center justify-center py-6 text-surface-500 text-xs font-semibold">
              Sin datos disponibles
            </div>
            <div v-else class="space-y-3 flex-1">
              <div class="rounded-xl border border-surface-200 bg-surface-50/60 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-surface-500 m-0">Activos</p>
                <p class="text-sm font-bold text-surface-900 m-0 mt-0.5">{{ ludoteca.ninos_activos }} niños · {{ ludoteca.ninos_entregados }} entregados</p>
              </div>
              <div class="rounded-xl border border-surface-200 bg-surface-50/60 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-surface-500 m-0">Cuota día</p>
                <p class="text-sm font-bold text-surface-900 m-0 mt-0.5">{{ formatMXN(ludoteca.ingresos_hoy) }} · {{ ludoteca.alertas_tiempo }} con retraso</p>
              </div>
            </div>

            <RouterLink to="/admin/ludoteca" class="mt-4 text-xs font-bold text-primary-600 hover:text-primary-700 transition-colors">
              Ver ludoteca →
            </RouterLink>
          </div>

          <!-- Torneos -->
          <div class="bg-white border border-surface-200 rounded-2xl shadow-sm p-5 flex flex-col">
            <div class="flex items-center gap-2 mb-4 min-w-0">
              <span class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                <IconTrophy class="w-4 h-4" />
              </span>
              <h3 class="text-sm font-bold text-surface-900 m-0 truncate">Torneos en Curso</h3>
            </div>

            <div v-if="torneoStore.loading" class="flex-1 flex items-center justify-center py-6">
              <LoadingSpinner size="sm" />
            </div>
            <div v-else-if="!torneoPrincipal" class="flex-1 flex items-center justify-center py-6 text-surface-500 text-xs font-semibold">
              Sin torneos en curso
            </div>
            <div v-else class="flex-1">
              <div class="flex items-center gap-2 flex-wrap mb-2">
                <span v-if="torneoEstado" class="inline-flex items-center px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold uppercase tracking-wider">
                  {{ torneoEstado }}
                </span>
                <span v-if="torneoPrincipal.disciplina?.nombre_disciplina" class="text-[11px] font-semibold text-surface-500">
                  {{ torneoPrincipal.disciplina.nombre_disciplina }}
                </span>
              </div>
              <h4 class="text-sm font-bold text-surface-900 m-0 leading-tight">{{ torneoPrincipal.nombre_torneo }}</h4>
              <p v-if="torneoPrincipal.inscritos_actual != null" class="text-xs font-medium text-surface-600 m-0 mt-1">
                {{ torneoPrincipal.inscritos_actual }}<template v-if="torneoPrincipal.cupo_maximo">/{{ torneoPrincipal.cupo_maximo }}</template> inscritos
              </p>
            </div>

            <RouterLink :to="torneoLink" class="mt-4 text-xs font-bold text-primary-600 hover:text-primary-700 transition-colors">
              Ver cuadro de llaves →
            </RouterLink>
          </div>
        </div>
      </section>

      <!-- 7) RESERVAS PRÓXIMAS (Hoy) -->
      <section class="bg-white border border-surface-200 rounded-2xl shadow-sm p-5 sm:p-6">
        <div class="mb-4">
          <h2 class="text-base font-bold text-surface-900 m-0">Reservas Próximas (Hoy)</h2>
          <p class="text-xs font-medium text-surface-600 m-0 mt-0.5">Listado de reservas On Demand del día.</p>
        </div>

        <div class="max-h-[420px] overflow-y-auto pr-1 space-y-2">
          <div v-if="reservacionAdminStore.loading.reservaciones" class="flex flex-col items-center justify-center py-12 text-surface-500">
            <LoadingSpinner class="w-8 h-8 mb-2" />
            <span class="text-xs font-bold">Cargando reservas…</span>
          </div>

          <div v-else-if="reservasProximasHoy.length === 0" class="flex flex-col items-center justify-center text-surface-500 py-12">
            <IconCalendar class="w-10 h-10 text-surface-300 mb-2" />
            <p class="text-xs font-bold m-0">No hay reservas próximas hoy</p>
          </div>

          <div v-else class="space-y-2">
            <div v-for="reserva in reservasProximasHoy" :key="reserva.id_reserva"
                 class="flex items-center justify-between p-3.5 bg-white hover:bg-surface-50 transition-colors rounded-xl border border-surface-200 gap-3">
              <div class="flex items-center gap-3.5 min-w-0 flex-1">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 border"
                     :class="getDisciplineColorClasses(reserva.disciplina?.nombre_disciplina || reserva.nombre_disciplina).bg">
                  <DisciplineIcon :name="reserva.disciplina?.nombre_disciplina || reserva.nombre_disciplina"
                                  class="w-5 h-5 fill-current"
                                  :class="getDisciplineColorClasses(reserva.disciplina?.nombre_disciplina || reserva.nombre_disciplina).icon" />
                </div>
                <div class="min-w-0 flex flex-col gap-1 flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-bold text-surface-900 text-sm truncate" :title="reserva.nombre_titular">
                      {{ reserva.nombre_titular }}
                    </span>
                    <span v-if="reserva.modalidad" class="px-1.5 py-0.5 rounded border text-[9px] uppercase font-bold tracking-wide"
                          :class="reserva.modalidad === 'ACOMPANANTES' ? 'bg-primary-50 text-primary-700 border-primary-200' : 'bg-surface-50 text-surface-700 border-surface-200'">
                      {{ reserva.modalidad === 'ACOMPANANTES' ? 'Acompañantes' : 'Individual' }}
                    </span>
                  </div>

                  <div class="flex items-center flex-wrap gap-x-3 gap-y-1 mt-0.5">
                    <div class="flex items-center gap-1.5 text-xs text-surface-600 font-semibold bg-surface-50 px-2 py-0.5 rounded-md border border-surface-100">
                      <span>{{ reserva.espacio_fisico?.nombre_espacio || reserva.nombre_espacio || 'N/A' }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-bold text-surface-600">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      <span>{{ formatHora(reserva.hora_inicio) }} – {{ formatHora(reserva.hora_fin) }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-surface-600">
                      <span class="w-1.5 h-1.5 rounded-full bg-surface-300"></span>
                      <span class="capitalize">{{ (reserva.disciplina?.nombre_disciplina || reserva.nombre_disciplina || 'Multi-Deporte').toLowerCase() }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex items-center shrink-0">
                <span class="inline-flex px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider border shrink-0"
                      :class="ESTATUS_COLORS[reserva.estatus_operativo] || 'bg-surface-50 text-surface-600 border-surface-200'">
                  {{ formatEstatus(reserva.estatus_operativo) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </main>
</template>
