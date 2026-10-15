<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';
import {
  IconArrowLeft,
  IconCalendar,
  IconClock,
  IconTarget,
  IconAlertCircle,
  IconWarning,
  IconChevronDown,
} from '@/components/icons';

const router = useRouter();

const activeTab = ref('asistencias');
const selectedDateRange = ref('current_month');
const historyItems = ref([]);
const isLoading = ref(true);
const hasError = ref(false);
const totalAsistencias = ref(0);
const totalNoShows = ref(0);

const isDateDropdownOpen = ref(false);
const dateDropdownRef = ref(null);

const datePillOptions = [
  { key: 'current_month', label: 'Este Mes', hint: 'Mes en curso' },
  { key: 'last_month', label: 'Mes Pasado', hint: 'Mes anterior' },
  { key: 'last_3_months', label: 'Últimos 3 Meses', hint: 'Últimos 90 días' },
];

const currentRangeLabel = computed(() => {
  const match = datePillOptions.find((p) => p.key === selectedDateRange.value);
  return match ? match.label : 'Periodo';
});

const selectDateRange = (key) => {
  selectedDateRange.value = key;
  isDateDropdownOpen.value = false;
};

const handleClickOutside = (event) => {
  if (dateDropdownRef.value && !dateDropdownRef.value.contains(event.target)) {
    isDateDropdownOpen.value = false;
  }
};

const attendancePercentage = computed(() => {
  const total = totalAsistencias.value + totalNoShows.value;
  if (total === 0) return '--';
  return Math.round((totalAsistencias.value / total) * 100);
});

const healthStatusLabel = computed(() => {
  const pct = attendancePercentage.value;
  if (pct === '--') return 'Sin registros';
  const num = Number(pct);
  if (num >= 85) return 'Excelente ritmo';
  if (num >= 70) return 'Cumplimiento regular';
  return 'En riesgo';
});

const attendanceBadgeClasses = computed(() => {
  const pct = attendancePercentage.value;
  if (pct === '--') {
    return 'bg-white/10 text-white/80 border-white/15';
  }
  const numeric = Number(pct);
  if (numeric >= 85) {
    return 'bg-emerald-500/20 text-emerald-200 border-emerald-400/30';
  }
  if (numeric >= 70) {
    return 'bg-amber-500/20 text-amber-200 border-amber-400/30';
  }
  return 'bg-rose-500/20 text-rose-200 border-rose-400/30';
});

// Calcula rangos de fecha en formato YYYY-MM-DD para los filtros del backend
const calculateDateRange = (rangeKey) => {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();

  const toYMD = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  };

  if (rangeKey === 'last_month') {
    const start = new Date(year, month - 1, 1);
    const end = new Date(year, month, 0);
    return {
      fecha_desde: toYMD(start),
      fecha_hasta: toYMD(end),
    };
  }

  if (rangeKey === 'last_3_months') {
    const start = new Date(year, month - 2, 1);
    const end = new Date(year, month + 1, 0);
    return {
      fecha_desde: toYMD(start),
      fecha_hasta: toYMD(end),
    };
  }

  const start = new Date(year, month, 1);
  const end = new Date(year, month + 1, 0);
  return {
    fecha_desde: toYMD(start),
    fecha_hasta: toYMD(end),
  };
};

const formatFecha = (iso) => {
  if (!iso) return '—';
  const cleanIso = String(iso).split('T')[0];
  const [y, m, d] = cleanIso.split('-');
  const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
  const monthIdx = parseInt(m, 10) - 1;
  return `${parseInt(d, 10)} ${meses[monthIdx] || ''} ${y}`;
};

const formatHora = (hora) => {
  if (!hora) return '—';
  return String(hora).substring(0, 5);
};

// Clasificación de estatus de asistencia según las respuestas del servidor
const extractStatus = (itemOrStatus) => {
  if (!itemOrStatus) return '';
  if (typeof itemOrStatus === 'object') {
    return String(itemOrStatus.estatus_inscripcion || itemOrStatus.status || '').toUpperCase().trim();
  }
  return String(itemOrStatus).toUpperCase().trim();
};

const isNoShowStatus = (itemOrStatus) => {
  const s = extractStatus(itemOrStatus);
  return ['NO_SHOW', 'FALTA', 'INASISTENCIA', 'NO_ASISTIO', 'PENALIZADA'].includes(s);
};

const isAsistenciaStatus = (itemOrStatus) => {
  const s = extractStatus(itemOrStatus);
  return ['ASISTIDA', 'ASISTIO', 'COMPLETADA', 'PRESENTE', 'CONFIRMADA'].includes(s);
};

// Sincroniza el historial según la pestaña activa y el filtro de fechas
const fetchHistory = async () => {
  isLoading.value = true;
  hasError.value = false;

  const { fecha_desde, fecha_hasta } = calculateDateRange(selectedDateRange.value);

  try {
    const response = await api.get('/actividades/mis-inscripciones', {
      params: {
        tab: activeTab.value,
        fecha_desde,
        fecha_hasta,
      },
    });

    const rawData = response.data?.data || response.data || [];
    const meta = response.data?.meta || response.data?.resumen || {};
    const items = Array.isArray(rawData) ? rawData : [];

    // Validamos que los registros correspondan al rango seleccionado
    const inDateRange = (item) => {
      const rawDate = item.fecha_sesion || item.fecha_transaccion;
      if (!rawDate) return false;
      const f = String(rawDate).split(/[ T]/)[0];
      if (!fecha_desde || !fecha_hasta) return true;
      return f >= fecha_desde && f <= fecha_hasta;
    };

    const scopedItems = items.filter(inDateRange);

    const hasApiTotals =
      typeof meta.total_asistencias === 'number' ||
      typeof meta.asistencias === 'number';

    if (hasApiTotals) {
      totalAsistencias.value = meta.total_asistencias ?? meta.asistencias ?? 0;
      totalNoShows.value = meta.total_no_shows ?? meta.no_shows ?? 0;
    } else {
      totalAsistencias.value = scopedItems.filter(isAsistenciaStatus).length;
      totalNoShows.value = scopedItems.filter(isNoShowStatus).length;
    }

    if (activeTab.value === 'asistencias') {
      historyItems.value = scopedItems.filter(isAsistenciaStatus);
    } else {
      historyItems.value = scopedItems.filter(isNoShowStatus);
    }
  } catch (error) {
    console.error('Error al sincronizar historial de actividades:', error);
    hasError.value =
      error.response?.data?.message ||
      'No se pudo conectar con el servidor para consultar tu historial. Revisa tu conexión a internet o intenta de nuevo.';
  } finally {
    isLoading.value = false;
  }
};

watch([activeTab, selectedDateRange], () => {
  fetchHistory();
});

onMounted(() => {
  fetchHistory();
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

// Soporte de deslizamiento táctil horizontal en móviles
let touchStartX = 0;
let touchStartY = 0;

const handleTouchStart = (event) => {
  if (!event.changedTouches || event.changedTouches.length === 0) return;
  touchStartX = event.changedTouches[0].clientX;
  touchStartY = event.changedTouches[0].clientY;
};

const handleTouchEnd = (event) => {
  if (!event.changedTouches || event.changedTouches.length === 0) return;
  const touchEndX = event.changedTouches[0].clientX;
  const touchEndY = event.changedTouches[0].clientY;

  const diffX = touchEndX - touchStartX;
  const diffY = touchEndY - touchStartY;

  if (Math.abs(diffX) >= 50 && Math.abs(diffX) > Math.abs(diffY) * 1.25) {
    if (diffX < 0 && activeTab.value === 'asistencias') {
      activeTab.value = 'no_shows';
    } else if (diffX > 0 && activeTab.value === 'no_shows') {
      activeTab.value = 'asistencias';
    }
  }
};
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans p-4 md:p-6 lg:p-8 pb-28 md:pb-12 flex justify-center selection:bg-primary-500/20">
    <div class="w-full max-w-5xl flex flex-col gap-6">

      <div>
        <button
          @click="router.push('/socio/home')"
          class="inline-flex items-center gap-2 text-surface-500 hover:text-primary-700 font-bold text-xs sm:text-sm transition-all focus:outline-none group cursor-pointer active:scale-95"
        >
          <div class="w-7 h-7 rounded-xl bg-white dark:bg-surface-100 border border-surface-200 dark:border-surface-200/80 flex items-center justify-center shadow-2xs group-hover:bg-primary-50 group-hover:border-primary-200 dark:group-hover:bg-primary-950/40 transition-all">
            <IconArrowLeft class="w-4 h-4 shrink-0 group-hover:-translate-x-0.5 transition-transform text-surface-600 dark:text-surface-300 group-hover:text-primary-600" />
          </div>
          <span>Volver al Inicio</span>
        </button>
      </div>

      <section
        class="relative overflow-hidden rounded-3xl md:rounded-[2.5rem] bg-linear-to-br from-primary-900 via-primary-800 to-primary-700 text-white p-6 sm:p-8 shadow-xl shadow-primary-900/15 border border-white/10"
      >
        <div class="absolute -top-24 -right-24 w-72 h-72 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-56 h-56 bg-primary-400/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col gap-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1.5 max-w-xl">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-[10px] md:text-xs font-bold tracking-wider uppercase">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                SOCDEP HUB • HISTORIAL DEPORTIVO
              </div>
              <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight m-0 text-white drop-shadow-xs">
                Historial de Actividades
              </h1>
              <p class="text-primary-100 text-xs sm:text-sm font-medium m-0 leading-relaxed opacity-95">
                Consulta tu registro de participación, asistencias y cumplimiento en las clases y canchas del club.
              </p>
            </div>

            <div
              :class="attendanceBadgeClasses"
              class="inline-flex items-center gap-3 px-4 py-2.5 rounded-2xl border backdrop-blur-md self-start sm:self-center shrink-0 shadow-sm transition-all"
            >
              <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center shrink-0 shadow-inner">
                <IconTarget class="w-4 h-4 text-white" />
              </div>
              <div class="flex flex-col">
                <span class="text-xs uppercase tracking-wider font-extrabold text-white/90">Cumplimiento</span>
                <span class="text-lg sm:text-xl font-black leading-tight text-white">
                  {{ attendancePercentage }}% <span class="text-[11px] font-medium opacity-80">({{ healthStatusLabel }})</span>
                </span>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2">
            <div class="bg-white/10 hover:bg-white/15 backdrop-blur-md rounded-2xl p-3.5 border border-white/15 transition-all">
              <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-primary-200 block">Asistencias</span>
              <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-black text-white leading-none">{{ totalAsistencias }}</span>
                <span class="text-[11px] font-semibold text-emerald-300">cumplidas</span>
              </div>
            </div>

            <div
              class="backdrop-blur-md rounded-2xl p-3.5 border transition-all"
              :class="totalNoShows > 0 ? 'bg-amber-500/20 border-amber-300/30' : 'bg-white/10 border-white/15'"
            >
              <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider block" :class="totalNoShows > 0 ? 'text-amber-200' : 'text-primary-200'">
                No-Shows
              </span>
              <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-black text-white leading-none">{{ totalNoShows }}</span>
                <span class="text-[11px] font-semibold" :class="totalNoShows > 0 ? 'text-amber-300' : 'text-white/60'">
                  {{ totalNoShows === 1 ? 'inasistencia' : 'inasistencias' }}
                </span>
              </div>
            </div>

            <div class="col-span-2 sm:col-span-1 bg-white/10 hover:bg-white/15 backdrop-blur-md rounded-2xl p-3.5 border border-white/15 transition-all">
              <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-primary-200 block">Total Registros</span>
              <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl sm:text-3xl font-black text-white leading-none">
                  {{ totalAsistencias + totalNoShows }}
                </span>
                <span class="text-[11px] font-semibold text-primary-200">en el periodo</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section aria-label="Navegación de historial y filtro" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-surface-200/80 dark:border-surface-200/60 pb-3">
        
        <div
          role="tablist"
          aria-label="Tipo de registro de historial"
          class="flex items-center gap-2.5 w-full sm:w-auto"
        >
          <button
            id="tab-asistencias"
            role="tab"
            :aria-selected="activeTab === 'asistencias'"
            aria-controls="panel-history-items"
            :tabindex="activeTab === 'asistencias' ? 0 : -1"
            @click="activeTab = 'asistencias'"
            @keydown.right.prevent="activeTab = 'no_shows'"
            class="flex-1 sm:flex-initial py-3 px-4 sm:px-6 rounded-2xl text-sm font-bold transition-all flex items-center justify-center gap-2.5 focus:outline-none cursor-pointer active:scale-98 border shadow-xs"
            :class="activeTab === 'asistencias'
              ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-700/20'
              : 'bg-white dark:bg-surface-100 text-surface-600 dark:text-surface-300 border-surface-200 dark:border-surface-300 hover:bg-surface-50 dark:hover:bg-surface-200'"
          >
            <span>Asistencias</span>
            <span
              class="min-w-6 px-2.5 py-0.5 rounded-full text-xs font-black transition-colors"
              :class="activeTab === 'asistencias'
                ? 'bg-white/25 text-white'
                : 'bg-surface-100 text-surface-700 dark:bg-surface-200 dark:text-surface-300'"
            >
              {{ totalAsistencias }}
            </span>
          </button>

          <button
            id="tab-no-shows"
            role="tab"
            :aria-selected="activeTab === 'no_shows'"
            aria-controls="panel-history-items"
            :tabindex="activeTab === 'no_shows' ? 0 : -1"
            @click="activeTab = 'no_shows'"
            @keydown.left.prevent="activeTab = 'asistencias'"
            class="flex-1 sm:flex-initial py-3 px-4 sm:px-6 rounded-2xl text-sm font-bold transition-all flex items-center justify-center gap-2 sm:gap-2.5 focus:outline-none cursor-pointer active:scale-98 border shadow-xs"
            :class="activeTab === 'no_shows'
              ? 'bg-amber-600 text-white border-amber-600 shadow-md shadow-amber-600/20'
              : 'bg-white dark:bg-surface-100 text-surface-600 dark:text-surface-300 border-surface-200 dark:border-surface-300 hover:bg-surface-50 dark:hover:bg-surface-200'"
          >
            <span>Inasistencias</span>
            <span class="hidden md:inline text-xs opacity-80 font-normal">(No-Shows)</span>
            <span
              class="min-w-6 px-2.5 py-0.5 rounded-full text-xs font-black shrink-0 transition-colors flex items-center justify-center gap-1"
              :class="activeTab === 'no_shows'
                ? 'bg-white/25 text-white'
                : totalNoShows > 0
                  ? 'bg-amber-100 text-amber-900 border border-amber-300 dark:bg-amber-950/70 dark:text-amber-300'
                  : 'bg-surface-100 text-surface-700 dark:bg-surface-200 dark:text-surface-300'"
            >
              <span v-if="totalNoShows > 0 && activeTab !== 'no_shows'" class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse shrink-0"></span>
              {{ totalNoShows }}
            </span>
          </button>
        </div>

        <div class="relative w-full sm:w-auto" ref="dateDropdownRef">
          <button
            @click="isDateDropdownOpen = !isDateDropdownOpen"
            class="w-full sm:w-64 inline-flex items-center justify-between gap-3 px-4 py-3 rounded-2xl bg-white dark:bg-surface-100 border border-surface-200 dark:border-surface-300 text-sm font-bold text-surface-700 dark:text-surface-200 shadow-xs hover:border-primary-300 hover:text-primary-700 dark:hover:text-primary-400 transition-all cursor-pointer active:scale-98"
            aria-haspopup="true"
            :aria-expanded="isDateDropdownOpen"
          >
            <div class="inline-flex items-center gap-2.5 truncate">
              <div class="w-6 h-6 rounded-lg bg-primary-50 dark:bg-primary-950/60 border border-primary-200/60 dark:border-primary-800/60 flex items-center justify-center shrink-0">
                <IconCalendar class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400 shrink-0" />
              </div>
              <span class="truncate">{{ currentRangeLabel }}</span>
            </div>
            <IconChevronDown
              class="w-4 h-4 text-surface-400 shrink-0 transition-transform duration-200"
              :class="isDateDropdownOpen ? 'rotate-180 text-primary-600' : ''"
            />
          </button>

          <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
          >
            <div
              v-if="isDateDropdownOpen"
              class="absolute left-0 right-0 sm:left-auto sm:right-0 top-full mt-2 w-full sm:w-64 bg-white dark:bg-surface-100 rounded-2xl border border-surface-200 dark:border-surface-300 shadow-xl p-2 z-50 space-y-1 backdrop-blur-xl"
            >
              <div class="px-3 py-1.5 text-[10px] font-extrabold text-surface-400 uppercase tracking-wider">
                Seleccionar Periodo
              </div>
              <button
                v-for="pill in datePillOptions"
                :key="pill.key"
                @click="selectDateRange(pill.key)"
                class="w-full flex items-center justify-between p-3 rounded-xl text-left transition-all cursor-pointer group"
                :class="selectedDateRange === pill.key
                  ? 'bg-primary-50 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 font-bold'
                  : 'hover:bg-surface-50 dark:hover:bg-surface-200 text-surface-700 dark:text-surface-200'"
              >
                <div>
                  <span class="block text-sm font-bold">{{ pill.label }}</span>
                  <span class="block text-[11px] font-medium text-surface-400 mt-0.5">{{ pill.hint }}</span>
                </div>
                <div v-if="selectedDateRange === pill.key" class="w-5 h-5 rounded-full bg-primary-600 text-white flex items-center justify-center shrink-0">
                  <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
              </button>
            </div>
          </transition>
        </div>

      </section>

      <!-- Alerta informativa cuando se seleccionan inasistencias -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div
          v-if="activeTab === 'no_shows'"
          class="bg-amber-50/90 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-3xl p-4 sm:p-5 flex items-start gap-4 shadow-xs"
        >
          <div class="w-10 h-10 rounded-2xl bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-700 dark:text-amber-300 shrink-0 shadow-inner">
            <IconWarning class="w-5 h-5" />
          </div>
          <div class="flex-1 min-w-0">
            <h2 class="text-xs sm:text-sm font-bold text-amber-900 dark:text-amber-200 m-0 leading-tight">
              Aviso sobre cumplimiento y penalizaciones de asistencia
            </h2>
            <p class="text-xs text-amber-800/90 dark:text-amber-300/90 font-medium m-0 mt-1 leading-relaxed">
              Acumular inasistencias sin previo aviso (No-Shows) puede restringir temporalmente tu facultad de reservar actividades y canchas del club. Recuerda cancelar con al menos 2 horas de anticipación si no puedes acudir.
            </p>
          </div>
        </div>
      </transition>

      <section
        id="panel-history-items"
        role="tabpanel"
        :aria-labelledby="activeTab === 'asistencias' ? 'tab-asistencias' : 'tab-no-shows'"
        @touchstart="handleTouchStart"
        @touchend="handleTouchEnd"
        class="touch-pan-y min-h-[320px] flex flex-col"
      >
        <div
          v-if="isLoading"
          class="grid grid-cols-1 md:grid-cols-2 gap-4"
          aria-busy="true"
          aria-label="Cargando historial de actividades"
        >
          <div
            v-for="index in 4"
            :key="`history-skeleton-${index}`"
            class="bg-white dark:bg-surface-100 rounded-3xl border border-surface-200 dark:border-surface-200/80 p-5 space-y-4 animate-pulse shadow-xs"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-surface-200 dark:bg-surface-300 shrink-0"></div>
                <div class="space-y-2">
                  <div class="h-4 w-32 bg-surface-200 dark:bg-surface-300 rounded-md"></div>
                  <div class="h-3 w-20 bg-surface-100 dark:bg-surface-200 rounded-md"></div>
                </div>
              </div>
              <div class="h-6 w-20 bg-surface-200 dark:bg-surface-300 rounded-full"></div>
            </div>

            <div class="pt-3 border-t border-surface-100 dark:border-surface-200/80 flex items-center justify-between gap-3">
              <div class="h-3 w-36 bg-surface-100 dark:bg-surface-200 rounded-md"></div>
              <div class="h-3 w-20 bg-surface-100 dark:bg-surface-200 rounded-md"></div>
            </div>
          </div>
        </div>

        <div
          v-else-if="hasError"
          class="bg-white dark:bg-surface-100 rounded-3xl border border-rose-200 dark:border-rose-900/60 p-8 md:p-12 text-center shadow-xs max-w-lg mx-auto flex flex-col items-center gap-4 my-auto"
        >
          <div class="w-14 h-14 rounded-2xl bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/50">
            <IconAlertCircle class="w-7 h-7" />
          </div>
          <div class="space-y-1.5">
            <h3 class="text-base sm:text-lg font-bold text-surface-900 dark:text-surface-100 m-0">
              No pudimos cargar tu historial
            </h3>
            <p class="text-xs sm:text-sm text-surface-500 dark:text-surface-400 font-medium m-0 max-w-sm leading-relaxed">
              {{ typeof hasError === 'string' ? hasError : 'Ocurrió un error al contactar al servidor del club.' }}
            </p>
          </div>
          <button
            @click="fetchHistory"
            class="px-5 py-2.5 bg-primary-700 hover:bg-primary-800 text-white font-bold rounded-xl text-xs sm:text-sm transition-all shadow-sm active:scale-95 flex items-center gap-2 cursor-pointer mt-1"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
              <path d="M3 3v5h5" />
              <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
              <path d="M16 16h5v5" />
            </svg>
            <span>Reintentar carga</span>
          </button>
        </div>

        <div
          v-else-if="historyItems.length === 0"
          class="bg-white dark:bg-surface-100 rounded-3xl border border-surface-200/80 dark:border-surface-200/60 p-8 md:p-14 text-center shadow-xs flex flex-col items-center gap-5 max-w-xl mx-auto my-auto w-full transition-all"
        >
          <div
            class="w-16 h-16 rounded-3xl flex items-center justify-center border shadow-xs"
            :class="activeTab === 'asistencias'
              ? 'bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 border-primary-100 dark:border-primary-900/50'
              : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/50'"
          >
            <IconCalendar v-if="activeTab === 'asistencias'" class="w-8 h-8" />
            <svg v-else class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
          </div>

          <div class="space-y-1.5">
            <h3 class="text-base sm:text-lg md:text-xl font-bold text-surface-900 dark:text-surface-100 m-0">
              {{ activeTab === 'asistencias'
                ? 'No tienes asistencias registradas en este periodo'
                : '¡Excelente! No tienes inasistencias registradas en este periodo' }}
            </h3>
            <p class="text-xs sm:text-sm text-surface-500 dark:text-surface-400 font-medium m-0 max-w-md leading-relaxed mx-auto">
              {{ activeTab === 'asistencias'
                ? 'Explora la oferta de clases abiertas, cerradas y canchas disponibles del club para mantenerte activo.'
                : 'Continúas con un historial intachable. Sigue cumpliendo con tus horarios reservados para conservar todos tus beneficios.' }}
            </p>
          </div>

          <router-link
            to="/socio/agenda"
            class="px-6 py-3 bg-primary-700 hover:bg-primary-800 text-white font-bold rounded-2xl text-xs sm:text-sm transition-all shadow-md shadow-primary-700/20 active:scale-95 inline-flex items-center gap-2 cursor-pointer no-underline hover:-translate-y-0.5"
          >
            <IconCalendar class="w-4 h-4" />
            <span>Explorar agenda de clases</span>
          </router-link>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <article
            v-for="item in historyItems"
            :key="item.id_inscripcion || item.id_sesion"
            class="bg-white dark:bg-surface-100 rounded-3xl border border-surface-200/90 dark:border-surface-200/70 p-5 flex flex-col justify-between gap-4 shadow-2xs hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all group relative overflow-hidden"
          >
            <div
              class="absolute left-0 top-0 bottom-0 w-1.5 transition-colors"
              :class="isNoShowStatus(item) ? 'bg-amber-500' : 'bg-emerald-500'"
            ></div>

            <div class="flex items-start justify-between gap-3 pl-1">
              <div class="flex items-start gap-3.5 min-w-0">
                <div class="w-12 h-12 rounded-2xl bg-primary-50 dark:bg-primary-950/50 border border-primary-100 dark:border-primary-900/40 flex items-center justify-center text-primary-700 dark:text-primary-300 shrink-0 shadow-2xs group-hover:scale-105 transition-transform">
                  <DisciplineIcon
                    :name="item.disciplina || item.nombre_actividad || 'Actividad'"
                    class="w-6 h-6 shrink-0"
                  />
                </div>
                <div class="min-w-0">
                  <h3 class="text-sm sm:text-base font-bold text-surface-900 dark:text-surface-100 truncate m-0 group-hover:text-primary-700 dark:group-hover:text-primary-400 transition-colors">
                    {{ item.nombre_actividad || item.disciplina || 'Actividad Deportiva' }}
                  </h3>
                  <p class="text-xs font-medium text-surface-500 dark:text-surface-400 truncate m-0 mt-0.5">
                    {{ item.espacio || 'Instalaciones del Club' }}
                    <span v-if="item.instructor" class="opacity-90"> • Prof. {{ item.instructor }}</span>
                  </p>
                </div>
              </div>

              <span
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] sm:text-xs font-black uppercase shrink-0 border shadow-2xs"
                :class="isNoShowStatus(item)
                  ? 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800'
                  : 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800'"
              >
                <span
                  class="w-1.5 h-1.5 rounded-full shrink-0"
                  :class="isNoShowStatus(item) ? 'bg-amber-500' : 'bg-emerald-500'"
                ></span>
                {{ isNoShowStatus(item) ? 'No-Show' : 'Asistió' }}
              </span>
            </div>

            <div class="pt-3 border-t border-surface-100 dark:border-surface-200/80 flex flex-wrap items-center justify-between gap-2.5 text-xs font-medium text-surface-600 dark:text-surface-400 pl-1">
              <div class="flex items-center gap-2.5 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-surface-50 dark:bg-surface-200/50 text-surface-800 dark:text-surface-200 font-semibold border border-surface-200/60 dark:border-surface-300/60">
                  <IconCalendar class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400 shrink-0" />
                  {{ formatFecha(item.fecha_sesion || item.fecha_transaccion) }}
                </span>
                <span v-if="item.hora_inicio" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-surface-50 dark:bg-surface-200/50 text-surface-600 dark:text-surface-300 border border-surface-200/60 dark:border-surface-300/60">
                  <IconClock class="w-3.5 h-3.5 text-surface-400 shrink-0" />
                  {{ formatHora(item.hora_inicio) }}<span v-if="item.hora_fin"> - {{ formatHora(item.hora_fin) }}</span>
                </span>
              </div>

              <div class="flex items-center gap-1.5">
                <span
                  v-if="item.familiar"
                  class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200 dark:bg-violet-950/40 dark:text-violet-300 dark:border-violet-800 shadow-2xs"
                >
                  Familiar: {{ item.familiar.nombre }}
                </span>
                <span
                  v-else-if="item.invitado"
                  class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800 shadow-2xs"
                >
                  Invitado: {{ item.invitado.nombre }}
                </span>
                <span
                  v-if="item.tipo_clase"
                  class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-surface-100 text-surface-600 border border-surface-200 dark:bg-surface-200 dark:text-surface-300 dark:border-surface-300"
                >
                  {{ item.tipo_clase }}
                </span>
              </div>
            </div>
          </article>
        </div>
      </section>

    </div>
  </main>
</template>

<style scoped>
.touch-pan-y {
  touch-action: pan-y;
}
</style>