<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '@/services/api';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';
import InstructorSessionCard from '@/components/instructor/history/InstructorSessionCard.vue';
import SessionAttendanceListModal from '@/components/instructor/history/SessionAttendanceListModal.vue';
import {
  IconArrowLeft,
  IconCalendar,
  IconClock,
  IconTarget,
  IconAlertCircle,
  IconWarning,
  IconChevronDown,
  IconInbox,
  IconUser,
} from '@/components/icons';

const router = useRouter();
const route = useRoute();

// Switch principal: 'historial' vs 'agenda'
const mainTab = ref('historial');

// Estados del Historial de Clases
const filtroEstatus = ref('todas'); // 'todas' | 'completadas' | 'canceladas'
const selectedDateRange = ref('current_month'); // 'current_month' | 'last_month' | 'last_3_months'
const historialItems = ref([]);
const isLoadingHistorial = ref(true);
const hasHistorialError = ref(false);

const metaStats = ref({
  total_sesiones_completadas: 0,
  total_sesiones_canceladas: 0,
  total_alumnos_atendidos: 0,
  current_page: 1,
  last_page: 1,
  total: 0,
});

// Dropdown de Periodo (patrón US-15)
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

// Calcula rangos de fecha en formato YYYY-MM-DD para la API
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

// Modal de detalle nominal de asistencia
const selectedSessionForModal = ref(null);
const isAttendanceModalOpen = ref(false);

const openAttendanceModal = (session) => {
  selectedSessionForModal.value = session;
  isAttendanceModalOpen.value = true;
};

const closeAttendanceModal = () => {
  selectedSessionForModal.value = null;
  isAttendanceModalOpen.value = false;
};

// Carga de historial de sesiones
const fetchHistorial = async (page = 1) => {
  isLoadingHistorial.value = true;
  hasHistorialError.value = false;

  const { fecha_desde, fecha_hasta } = calculateDateRange(selectedDateRange.value);

  try {
    const response = await api.get('/instructor/historial-sesiones', {
      params: {
        filtro_estatus: filtroEstatus.value,
        fecha_desde,
        fecha_hasta,
        page,
      },
    });

    if (response.data && response.data.success) {
      historialItems.value = response.data.data || [];
      if (response.data.meta) {
        metaStats.value = {
          total_sesiones_completadas: response.data.meta.total_sesiones_completadas ?? 0,
          total_sesiones_canceladas: response.data.meta.total_sesiones_canceladas ?? 0,
          total_alumnos_atendidos: response.data.meta.total_alumnos_atendidos ?? 0,
          current_page: response.data.meta.current_page ?? 1,
          last_page: response.data.meta.last_page ?? 1,
          total: response.data.meta.total ?? response.data.data.length,
        };
      }
    } else {
      historialItems.value = response.data?.data || response.data || [];
    }
  } catch (error) {
    console.error('Error al sincronizar historial de sesiones del instructor:', error);
    hasHistorialError.value =
      error.response?.data?.message ||
      'No se pudo conectar con el servidor para consultar tu historial. Revisa tu conexión o intenta de nuevo.';
  } finally {
    isLoadingHistorial.value = false;
  }
};

// -------------------------------------------------------------
// Estados de la Agenda Semanal Activa (Preservación retrocompatible)
// -------------------------------------------------------------
const agendaSemanalData = ref([]);
const isLoadingAgenda = ref(false);
const errorAgendaMsg = ref('');

const ordenDias = {
  'LUNES': 1, 'MARTES': 2, 'MIÉRCOLES': 3, 'JUEVES': 4, 'VIERNES': 5, 'SÁBADO': 6, 'DOMINGO': 7
};

const sesionesAgrupadas = computed(() => {
  const grupos = {};
  agendaSemanalData.value.forEach(sesion => {
    if (!grupos[sesion.diaSemana]) {
      grupos[sesion.diaSemana] = [];
    }
    grupos[sesion.diaSemana].push(sesion);
  });

  const diasOrdenados = Object.keys(grupos).sort((a, b) => (ordenDias[a] || 99) - (ordenDias[b] || 99));

  const resultado = {};
  diasOrdenados.forEach(dia => {
    resultado[dia] = grupos[dia].sort((a, b) => (a.horaInicio || '').localeCompare(b.horaInicio || ''));
  });

  return resultado;
});

const haySesionesAgenda = computed(() => agendaSemanalData.value.length > 0);

const fetchAgendaSemanal = async () => {
  if (agendaSemanalData.value.length > 0) return;
  isLoadingAgenda.value = true;
  errorAgendaMsg.value = '';

  try {
    const response = await api.get('/instructor/sessions');
    if (response.data && response.data.success) {
      agendaSemanalData.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Error cargando la agenda semanal:', error);
    errorAgendaMsg.value = 'Hubo un problema al cargar tu agenda semanal activa.';
  } finally {
    isLoadingAgenda.value = false;
  }
};

const handleAgendaSessionClick = (sesion) => {
  router.push({
    path: `/instructor/sessions/${sesion.id}`,
    state: { sessionData: JSON.stringify(sesion) }
  });
};

// Observadores de cambios en filtros
watch([filtroEstatus, selectedDateRange], () => {
  fetchHistorial(1);
});

watch(mainTab, (newTab) => {
  if (newTab === 'agenda') {
    fetchAgendaSemanal();
  } else {
    fetchHistorial(1);
  }
});

// Soporte táctil swipe en móviles para alternar chips de filtro
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
    const options = ['todas', 'completadas', 'canceladas'];
    const currentIdx = options.indexOf(filtroEstatus.value);
    if (diffX < 0 && currentIdx < options.length - 1) {
      filtroEstatus.value = options[currentIdx + 1];
    } else if (diffX > 0 && currentIdx > 0) {
      filtroEstatus.value = options[currentIdx - 1];
    }
  }
};

onMounted(() => {
  // Si en el query viene tab=agenda, abrimos agenda
  if (route.query.tab === 'agenda') {
    mainTab.value = 'agenda';
    fetchAgendaSemanal();
  } else {
    fetchHistorial(1);
  }
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans p-4 md:p-6 lg:p-8 pb-28 md:pb-12 flex justify-center selection:bg-primary-500/20">
    <div class="w-full max-w-5xl flex flex-col gap-6">

      <!-- Botón Volver al Inicio y Switch Principal de Modos -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <button
          @click="router.push('/instructor/home')"
          class="inline-flex items-center gap-2 text-surface-500 hover:text-primary-700 font-bold text-xs sm:text-sm transition-all focus:outline-none group cursor-pointer active:scale-95"
        >
          <div class="w-7 h-7 rounded-xl bg-white dark:bg-surface-100 border border-surface-200 dark:border-surface-200/80 flex items-center justify-center shadow-2xs group-hover:bg-primary-50 group-hover:border-primary-200 dark:group-hover:bg-primary-950/40 transition-all">
            <IconArrowLeft class="w-4 h-4 shrink-0 group-hover:-translate-x-0.5 transition-transform text-surface-600 dark:text-surface-300 group-hover:text-primary-600" />
          </div>
          <span>Volver al Inicio</span>
        </button>

        <!-- Switch Pestañas: Historial de Clases vs Agenda Semanal -->
        <div class="bg-surface-200/80 dark:bg-surface-200/40 p-1 rounded-2xl flex items-center gap-1 border border-surface-200/60 dark:border-surface-300/40 self-start sm:self-auto shadow-2xs">
          <button
            type="button"
            @click="mainTab = 'historial'"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer flex items-center gap-2"
            :class="mainTab === 'historial'
              ? 'bg-white dark:bg-surface-100 text-surface-900 dark:text-surface-100 shadow-sm'
              : 'text-surface-600 dark:text-surface-400 hover:text-surface-900'"
          >
            <IconCalendar class="w-4 h-4 text-primary-600 dark:text-primary-400" />
            <span>Historial de Clases</span>
          </button>

          <button
            type="button"
            @click="mainTab = 'agenda'"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer flex items-center gap-2"
            :class="mainTab === 'agenda'
              ? 'bg-white dark:bg-surface-100 text-surface-900 dark:text-surface-100 shadow-sm'
              : 'text-surface-600 dark:text-surface-400 hover:text-surface-900'"
          >
            <IconClock class="w-4 h-4 text-surface-500" />
            <span>Agenda Semanal</span>
          </button>
        </div>
      </div>

      <!-- ========================================================= -->
      <!-- PESTAÑA 1: HISTORIAL DE CLASES IMPARTIDAS                  -->
      <!-- ========================================================= -->
      <template v-if="mainTab === 'historial'">

        <!-- Hero Card con Gradiente Premium y Métricas Rápidas (Reutilizando patrón US-15) -->
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
                  SOCDEP HUB • PANEL DEL INSTRUCTOR
                </div>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight m-0 text-white drop-shadow-xs">
                  Historial de Clases Impartidas
                </h1>
                <p class="text-primary-100 text-xs sm:text-sm font-medium m-0 leading-relaxed opacity-95">
                  Consulta el registro clasificado de tus sesiones concluidas y canceladas con métricas de aforo y pase de lista auditado.
                </p>
              </div>

              <!-- Indicador de total histórico -->
              <div
                class="inline-flex items-center gap-3 px-4 py-2.5 rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md self-start sm:self-center shrink-0 shadow-sm"
              >
                <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center shrink-0 shadow-inner">
                  <IconTarget class="w-4 h-4 text-white" />
                </div>
                <div class="flex flex-col">
                  <span class="text-xs uppercase tracking-wider font-extrabold text-white/90">Efectividad</span>
                  <span class="text-lg sm:text-xl font-black leading-tight text-white">
                    {{ metaStats.total_sesiones_completadas + metaStats.total_sesiones_canceladas > 0
                      ? Math.round((metaStats.total_sesiones_completadas / (metaStats.total_sesiones_completadas + metaStats.total_sesiones_canceladas)) * 100)
                      : 100 }}%
                    <span class="text-[11px] font-medium opacity-80">(Completadas)</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- 3 Tarjetas de Métricas Rápidas devueltas por el endpoint de Gabo -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
              <!-- Card 1: Total Sesiones Completadas -->
              <div class="bg-white/10 hover:bg-white/15 backdrop-blur-md rounded-2xl p-4 border border-white/15 transition-all">
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-emerald-200 block">
                  Sesiones Completadas
                </span>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl sm:text-3xl font-black text-white leading-none">
                    {{ metaStats.total_sesiones_completadas }}
                  </span>
                  <span class="text-[11px] font-semibold text-emerald-300">impartidas con éxito</span>
                </div>
              </div>

              <!-- Card 2: Alumnos Totales Atendidos -->
              <div class="bg-white/10 hover:bg-white/15 backdrop-blur-md rounded-2xl p-4 border border-white/15 transition-all">
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-primary-200 block">
                  Alumnos Atendidos
                </span>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl sm:text-3xl font-black text-white leading-none">
                    {{ metaStats.total_alumnos_atendidos }}
                  </span>
                  <span class="text-[11px] font-semibold text-primary-200">asistencias confirmadas</span>
                </div>
              </div>

              <!-- Card 3: Sesiones Canceladas -->
              <div
                class="backdrop-blur-md rounded-2xl p-4 border transition-all"
                :class="metaStats.total_sesiones_canceladas > 0 ? 'bg-amber-500/20 border-amber-300/30' : 'bg-white/10 border-white/15'"
              >
                <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider block" :class="metaStats.total_sesiones_canceladas > 0 ? 'text-amber-200' : 'text-primary-200'">
                  Sesiones Canceladas
                </span>
                <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl sm:text-3xl font-black text-white leading-none">
                    {{ metaStats.total_sesiones_canceladas }}
                  </span>
                  <span class="text-[11px] font-semibold" :class="metaStats.total_sesiones_canceladas > 0 ? 'text-amber-300' : 'text-white/60'">
                    por lluvia, torneo o aviso
                  </span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Barra de Navegación: Chips de Filtro de Estatus + Selector de Periodo -->
        <section aria-label="Navegación de historial y filtro" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-surface-200/80 dark:border-surface-200/60 pb-3">
          
          <!-- Chips de Estatus: Todas, Completadas, Canceladas -->
          <div
            role="tablist"
            aria-label="Filtro de estatus de sesión histórica"
            class="flex items-center gap-2 w-full sm:w-auto"
          >
            <!-- Chip: Todas -->
            <button
              id="tab-todas"
              role="tab"
              :aria-selected="filtroEstatus === 'todas'"
              @click="filtroEstatus = 'todas'"
              class="flex-1 sm:flex-initial py-2.5 px-4 sm:px-5 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 focus:outline-none cursor-pointer active:scale-98 border shadow-xs"
              :class="filtroEstatus === 'todas'
                ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-700/20'
                : 'bg-white dark:bg-surface-100 text-surface-600 dark:text-surface-300 border-surface-200 dark:border-surface-300 hover:bg-surface-50 dark:hover:bg-surface-200'"
            >
              <span>Todas</span>
              <span
                class="min-w-5 px-2 py-0.2 rounded-full text-[11px] font-black transition-colors"
                :class="filtroEstatus === 'todas'
                  ? 'bg-white/25 text-white'
                  : 'bg-surface-100 text-surface-700 dark:bg-surface-200 dark:text-surface-300'"
              >
                {{ metaStats.total_sesiones_completadas + metaStats.total_sesiones_canceladas }}
              </span>
            </button>

            <!-- Chip: Completadas (Verde) -->
            <button
              id="tab-completadas"
              role="tab"
              :aria-selected="filtroEstatus === 'completadas'"
              @click="filtroEstatus = 'completadas'"
              class="flex-1 sm:flex-initial py-2.5 px-4 sm:px-5 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 focus:outline-none cursor-pointer active:scale-98 border shadow-xs"
              :class="filtroEstatus === 'completadas'
                ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-700/20'
                : 'bg-white dark:bg-surface-100 text-surface-600 dark:text-surface-300 border-surface-200 dark:border-surface-300 hover:bg-surface-50 dark:hover:bg-surface-200'"
            >
              <span>Completadas</span>
              <span
                class="min-w-5 px-2 py-0.2 rounded-full text-[11px] font-black transition-colors"
                :class="filtroEstatus === 'completadas'
                  ? 'bg-white/25 text-white'
                  : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'"
              >
                {{ metaStats.total_sesiones_completadas }}
              </span>
            </button>

            <!-- Chip: Canceladas (Ámbar/Rojo) -->
            <button
              id="tab-canceladas"
              role="tab"
              :aria-selected="filtroEstatus === 'canceladas'"
              @click="filtroEstatus = 'canceladas'"
              class="flex-1 sm:flex-initial py-2.5 px-4 sm:px-5 rounded-2xl text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 focus:outline-none cursor-pointer active:scale-98 border shadow-xs"
              :class="filtroEstatus === 'canceladas'
                ? 'bg-amber-600 text-white border-amber-600 shadow-md shadow-amber-600/20'
                : 'bg-white dark:bg-surface-100 text-surface-600 dark:text-surface-300 border-surface-200 dark:border-surface-300 hover:bg-surface-50 dark:hover:bg-surface-200'"
            >
              <span>Canceladas</span>
              <span
                class="min-w-5 px-2 py-0.2 rounded-full text-[11px] font-black transition-colors flex items-center justify-center gap-1"
                :class="filtroEstatus === 'canceladas'
                  ? 'bg-white/25 text-white'
                  : 'bg-amber-100 text-amber-900 border border-amber-300 dark:bg-amber-950/70 dark:text-amber-300'"
              >
                {{ metaStats.total_sesiones_canceladas }}
              </span>
            </button>
          </div>

          <!-- Selector de Periodo con Dropdown Accesible (Patrón US-15) -->
          <div class="relative w-full sm:w-auto" ref="dateDropdownRef">
            <button
              @click="isDateDropdownOpen = !isDateDropdownOpen"
              class="w-full sm:w-64 inline-flex items-center justify-between gap-3 px-4 py-2.5 rounded-2xl bg-white dark:bg-surface-100 border border-surface-200 dark:border-surface-300 text-xs sm:text-sm font-bold text-surface-700 dark:text-surface-200 shadow-xs hover:border-primary-300 hover:text-primary-700 dark:hover:text-primary-400 transition-all cursor-pointer active:scale-98"
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

            <!-- Dropdown Menú -->
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

        <!-- Contenedor Principal de Lista de Tarjetas / Estados -->
        <section
          id="panel-instructor-history"
          @touchstart="handleTouchStart"
          @touchend="handleTouchEnd"
          class="touch-pan-y min-h-[320px] flex flex-col"
        >
          <!-- Skeletons de Carga (Idénticos al patrón US-15) -->
          <div
            v-if="isLoadingHistorial"
            class="grid grid-cols-1 md:grid-cols-2 gap-4"
            aria-busy="true"
            aria-label="Cargando historial de clases del instructor"
          >
            <div
              v-for="index in 4"
              :key="`instructor-skeleton-${index}`"
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

              <div class="h-8 bg-surface-100 dark:bg-surface-200/60 rounded-xl"></div>

              <div class="pt-3 border-t border-surface-100 dark:border-surface-200/80 flex items-center justify-between gap-3">
                <div class="h-3 w-28 bg-surface-100 dark:bg-surface-200 rounded-md"></div>
                <div class="h-7 w-28 bg-surface-200 dark:bg-surface-300 rounded-xl"></div>
              </div>
            </div>
          </div>

          <!-- Estado de Error -->
          <div
            v-else-if="hasHistorialError"
            class="bg-white dark:bg-surface-100 rounded-3xl border border-rose-200 dark:border-rose-900/60 p-8 md:p-12 text-center shadow-xs max-w-lg mx-auto flex flex-col items-center gap-4 my-auto"
          >
            <div class="w-14 h-14 rounded-2xl bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/50">
              <IconAlertCircle class="w-7 h-7" />
            </div>
            <div class="space-y-1.5">
              <h3 class="text-base sm:text-lg font-bold text-surface-900 dark:text-surface-100 m-0">
                No pudimos cargar tu historial de clases
              </h3>
              <p class="text-xs sm:text-sm text-surface-500 dark:text-surface-400 font-medium m-0 max-w-sm leading-relaxed">
                {{ typeof hasHistorialError === 'string' ? hasHistorialError : 'Ocurrió un error al contactar al servidor del club.' }}
              </p>
            </div>
            <button
              type="button"
              @click="fetchHistorial(1)"
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

          <!-- Estado Vacío (Ilustración / Mensaje de Estado Vacío) -->
          <div
            v-else-if="historialItems.length === 0"
            class="bg-white dark:bg-surface-100 rounded-3xl border border-surface-200/80 dark:border-surface-200/60 p-8 md:p-14 text-center shadow-xs flex flex-col items-center gap-5 max-w-xl mx-auto my-auto w-full transition-all"
          >
            <div class="w-16 h-16 rounded-3xl flex items-center justify-center border shadow-xs bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 border-primary-100 dark:border-primary-900/50">
              <IconInbox class="w-8 h-8" />
            </div>

            <div class="space-y-1.5">
              <h3 class="text-base sm:text-lg md:text-xl font-bold text-surface-900 dark:text-surface-100 m-0">
                No se encontraron clases impartidas en este período
              </h3>
              <p class="text-xs sm:text-sm text-surface-500 dark:text-surface-400 font-medium m-0 max-w-md leading-relaxed mx-auto">
                No hay sesiones concluidas o canceladas registradas en el rango de fechas seleccionado ({{ currentRangeLabel }}). Puedes seleccionar otro periodo o revisar la agenda activa.
              </p>
            </div>

            <button
              type="button"
              @click="mainTab = 'agenda'"
              class="px-6 py-3 bg-primary-700 hover:bg-primary-800 text-white font-bold rounded-2xl text-xs sm:text-sm transition-all shadow-md shadow-primary-700/20 active:scale-95 inline-flex items-center gap-2 cursor-pointer no-underline hover:-translate-y-0.5"
            >
              <IconCalendar class="w-4 h-4" />
              <span>Ver agenda semanal activa</span>
            </button>
          </div>

          <!-- Grid de Tarjetas de Sesión (InstructorSessionCard.vue) -->
          <div v-else class="flex flex-col gap-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <InstructorSessionCard
                v-for="session in historialItems"
                :key="session.id_sesion"
                :session="session"
                @view-attendance="openAttendanceModal"
              />
            </div>

            <!-- Paginación si hay múltiples páginas -->
            <div
              v-if="metaStats.last_page > 1"
              class="flex items-center justify-between pt-4 border-t border-surface-200/80 dark:border-surface-200/60"
            >
              <span class="text-xs text-surface-500 font-medium">
                Página {{ metaStats.current_page }} de {{ metaStats.last_page }} (Total: {{ metaStats.total }})
              </span>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  :disabled="metaStats.current_page <= 1"
                  @click="fetchHistorial(metaStats.current_page - 1)"
                  class="px-3 py-1.5 rounded-xl border border-surface-200 bg-white dark:bg-surface-100 text-xs font-bold disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                >
                  Anterior
                </button>
                <button
                  type="button"
                  :disabled="metaStats.current_page >= metaStats.last_page"
                  @click="fetchHistorial(metaStats.current_page + 1)"
                  class="px-3 py-1.5 rounded-xl border border-surface-200 bg-white dark:bg-surface-100 text-xs font-bold disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                >
                  Siguiente
                </button>
              </div>
            </div>
          </div>

        </section>

      </template>

      <!-- ========================================================= -->
      <!-- PESTAÑA 2: AGENDA SEMANAL ACTIVA (Preservación del flujo)  -->
      <!-- ========================================================= -->
      <template v-else>
        <div class="flex flex-col gap-6 animate-fade-in">

          <div class="flex flex-col md:flex-row md:items-end justify-between gap-3 border-b border-surface-200 pb-4">
            <div>
              <p class="text-xs md:text-sm font-bold text-surface-400 m-0 tracking-widest uppercase mb-1">Agenda Semanal Activa</p>
              <h2 class="text-2xl md:text-3xl font-extrabold text-surface-900 dark:text-surface-100 m-0 tracking-tight">Sesiones Programadas</h2>
            </div>
            <p class="text-xs text-surface-500 font-medium m-0">Sesiones y turnos disponibles para esta semana.</p>
          </div>

          <!-- Estado de Carga Agenda -->
          <section v-if="isLoadingAgenda" class="flex flex-col items-center py-20">
            <div class="w-12 h-12 rounded-full border-4 border-surface-200 border-t-primary-600 animate-spin mb-4" />
            <p class="text-surface-500 font-semibold text-sm">Cargando agenda semanal...</p>
          </section>

          <!-- Estado de Error Agenda -->
          <section v-else-if="errorAgendaMsg" class="flex flex-col items-center justify-center p-12 bg-red-50 rounded-3xl border border-red-200">
            <p class="text-red-600 font-bold mb-3">{{ errorAgendaMsg }}</p>
            <button
              @click="fetchAgendaSemanal"
              class="px-4 py-2 bg-primary-600 text-white font-bold rounded-xl text-xs"
            >
              Reintentar
            </button>
          </section>

          <!-- Mapeo por Día -->
          <div v-else-if="haySesionesAgenda" class="flex flex-col gap-6">
            <section v-for="(sesiones, dia) in sesionesAgrupadas" :key="dia" class="flex flex-col gap-3">
              <header class="flex items-center gap-2 px-2">
                <IconCalendar class="w-5 h-5 text-primary-600" />
                <h3 class="text-sm font-bold text-surface-600 dark:text-surface-300 tracking-wider uppercase m-0">{{ dia }}</h3>
              </header>

              <div class="bg-white dark:bg-surface-100 rounded-3xl border border-surface-200 dark:border-surface-200/80 p-3 sm:p-5 shadow-xs flex flex-col gap-3">
                <div
                  v-for="session in sesiones"
                  :key="session.id"
                  class="group bg-white dark:bg-surface-100 border border-surface-200 dark:border-surface-200/80 rounded-2xl p-3 md:p-4 flex flex-row items-center justify-between gap-4 cursor-pointer hover:border-primary-300 hover:shadow-md hover:-translate-y-0.5 transition-all active:scale-95"
                  @click="handleAgendaSessionClick(session)"
                >
                  <div class="flex items-center gap-4 w-full min-w-0">
                    <!-- Horario -->
                    <div class="bg-surface-100 dark:bg-surface-200/60 rounded-xl px-3 py-2 flex flex-col items-center justify-center min-w-[70px] shrink-0 border border-surface-200 dark:border-surface-300 group-hover:bg-primary-50 group-hover:border-primary-100 transition-colors">
                      <span class="font-bold text-surface-900 dark:text-surface-100 group-hover:text-primary-900 text-sm md:text-base leading-none">{{ session.horaInicio }}</span>
                      <span class="text-[10px] font-medium text-surface-500 group-hover:text-primary-600 mt-1 uppercase">{{ session.horaFin }}</span>
                    </div>

                    <!-- Detalles -->
                    <div class="flex flex-col grow min-w-0">
                      <h4 class="font-bold text-surface-900 dark:text-surface-100 text-sm md:text-base m-0 mb-0.5 truncate">{{ session.espacio }}</h4>
                      <p class="text-xs font-medium text-surface-500 dark:text-surface-400 m-0 truncate">Capacidad: {{ session.capacidadMaxima }} px.</p>
                    </div>
                  </div>

                  <!-- Tipo y Flecha -->
                  <div class="flex items-center gap-3 shrink-0">
                    <span
                      class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                      :class="{
                        'bg-primary-100 text-primary-800': session.statusType === 'info',
                        'bg-green-100 text-green-800': session.statusType === 'success',
                        'bg-surface-100 text-surface-800': !session.statusType || (session.statusType !== 'info' && session.statusType !== 'success')
                      }"
                    >
                      {{ session.tipo }}
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-surface-400 group-hover:text-primary-600 group-hover:translate-x-1 transition-all" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <!-- Estado de Vacío Agenda -->
          <section v-else class="h-64 flex flex-col items-center justify-center rounded-3xl border border-dashed border-surface-300 bg-white dark:bg-surface-100 shadow-sm mt-4">
            <div class="w-16 h-16 bg-surface-100 text-surface-400 rounded-full flex items-center justify-center mb-4">
              <IconInbox class="w-8 h-8" />
            </div>
            <h3 class="text-lg font-bold text-surface-900 dark:text-surface-100 mb-1">Sin agenda esta semana</h3>
            <p class="text-surface-500 font-medium text-sm text-center px-4">No tienes sesiones programadas para los próximos días.</p>
          </section>

        </div>
      </template>

    </div>

    <!-- Modal de Desglose Nominal de Asistencia -->
    <SessionAttendanceListModal
      v-if="isAttendanceModalOpen && selectedSessionForModal"
      :session-id="selectedSessionForModal.id_sesion"
      :initial-data="selectedSessionForModal"
      @close="closeAttendanceModal"
    />
  </main>
</template>

<style scoped>
.touch-pan-y {
  touch-action: pan-y;
}
</style>
