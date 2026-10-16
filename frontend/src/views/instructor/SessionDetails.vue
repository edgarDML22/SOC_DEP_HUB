<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';
import {
  IconArrowLeft,
  IconClock,
  IconCalendar,
  IconGrid,
  IconUser,
  IconQr,
  IconAlertCircle,
  IconWarning,
  IconStart,
} from '@/components/icons';

const route = useRoute();
const router = useRouter();
const sessionId = route.params.id;

const sessionData = ref(null);
const rawHistoryState = ref(null);
const isLoading = ref(true);
const hasError = ref(false);
const activeFilter = ref('todos'); // 'todos' | 'asistieron' | 'no_shows'
const isHistorical = ref(true);

const fetchSessionDetail = async () => {
  isLoading.value = true;
  hasError.value = false;

  // Intentamos obtener el desglose histórico desde la API oficial de Gabo
  try {
    const response = await api.get(`/instructor/historial-sesiones/${sessionId}`);
    if (response.data && response.data.success) {
      sessionData.value = response.data.data;
      isHistorical.value = true;
    } else if (response.data?.data) {
      sessionData.value = response.data.data;
      isHistorical.value = true;
    }
  } catch (error) {
    console.warn('No se pudo cargar desde historial-sesiones API:', error);

    // Fallback: Si viene desde la agenda activa vía history.state
    if (history.state && history.state.sessionData) {
      try {
        const parsed = typeof history.state.sessionData === 'string'
          ? JSON.parse(history.state.sessionData)
          : history.state.sessionData;
        rawHistoryState.value = parsed;
        isHistorical.value = false;
        sessionData.value = {
          id_sesion: parsed.id || sessionId,
          disciplina: parsed.tipo || parsed.disciplina || 'Clase Deportiva',
          espacio: parsed.espacio || 'Cancha asignada',
          fecha_sesion: parsed.fecha || parsed.diaSemana || null,
          horario: parsed.horaInicio && parsed.horaFin ? `${parsed.horaInicio} - ${parsed.horaFin}` : (parsed.horario || ''),
          cupo_maximo: parsed.capacidadMaxima || parsed.cupo_maximo || 0,
          status: parsed.status || 'Programada',
          statusType: parsed.statusType || 'info',
          resumen: {
            inscritos: parsed.capacidadMaxima || 0,
            asistieron: 0,
            no_shows: 0,
          },
          asistentes: [],
        };
      } catch (e) {
        hasError.value = 'No se encontró la información de la sesión. Regresa e inténtalo de nuevo.';
      }
    } else {
      hasError.value =
        error.response?.data?.message ||
        'No fue posible consultar la información de la sesión histórica. Es probable que no exista o no pertenezca a tu cuenta.';
    }
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchSessionDetail();
});

const goBack = () => {
  if (window.history.length > 1) {
    router.back();
  } else {
    router.push('/instructor/sessions');
  }
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

const getInitials = (nombre) => {
  if (!nombre) return 'U';
  return nombre
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0].toUpperCase())
    .join('');
};

const formatMetodoDisplay = (metodo) => {
  const m = String(metodo || '').toUpperCase();
  if (m.includes('QR')) return 'QR Scanner';
  if (m.includes('MANUAL')) return 'Manual';
  if (m.includes('SISTEMA')) return 'Sistema';
  return metodo || 'QR Scanner';
};

// Resumen estadístico
const resumenStats = computed(() => {
  const data = sessionData.value;
  if (!data) return { inscritos: 0, asistieron: 0, no_shows: 0, porcentaje: 0 };

  const inscritos = data.resumen?.inscritos ?? (data.asistentes ? data.asistentes.length : 0);
  const asistieron = data.resumen?.asistieron ?? (data.asistentes ? data.asistentes.filter(a => a.estatus_asistencia === 'ASISTIO').length : 0);
  const no_shows = data.resumen?.no_shows ?? (data.asistentes ? data.asistentes.filter(a => a.estatus_asistencia === 'NO_SHOW').length : 0);
  const porcentaje = inscritos > 0 ? Math.round((asistieron / inscritos) * 100) : 0;

  return { inscritos, asistieron, no_shows, porcentaje };
});

const asistentesFiltrados = computed(() => {
  const lista = sessionData.value?.asistentes || [];
  if (activeFilter.value === 'asistieron') {
    return lista.filter((a) => a.estatus_asistencia === 'ASISTIO');
  }
  if (activeFilter.value === 'no_shows') {
    return lista.filter((a) => a.estatus_asistencia === 'NO_SHOW' || a.estatus_asistencia === 'CANCELO');
  }
  return lista;
});

// Acciones operativas para sesiones activas (si aplica)
const handleRegistrarAsistencia = () => {
  router.push({ path: '/instructor/scanner', query: { sesion: sessionId } });
};

const handleEmpezarSesion = () => {
  if (sessionData.value) {
    sessionData.value.status = 'En curso';
    sessionData.value.statusType = 'info';
  }
  alert('¡Sesión en Curso iniciada!');
};
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans p-4 md:p-6 lg:p-8 pb-28 md:pb-12 flex justify-center selection:bg-primary-500/20">
    <div class="w-full max-w-4xl flex flex-col gap-6">

      <!-- Botón Volver -->
      <div>
        <button
          @click="goBack"
          class="inline-flex items-center gap-2 text-surface-500 hover:text-primary-700 font-bold text-xs sm:text-sm transition-all focus:outline-none group cursor-pointer active:scale-95"
        >
          <div class="w-7 h-7 rounded-xl bg-white dark:bg-surface-100 border border-surface-200 dark:border-surface-200/80 flex items-center justify-center shadow-2xs group-hover:bg-primary-50 group-hover:border-primary-200 dark:group-hover:bg-primary-950/40 transition-all">
            <IconArrowLeft class="w-4 h-4 shrink-0 group-hover:-translate-x-0.5 transition-transform text-surface-600 dark:text-surface-300 group-hover:text-primary-600" />
          </div>
          <span>Volver al Historial</span>
        </button>
      </div>

      <!-- Estado de Carga -->
      <section v-if="isLoading" class="flex flex-col items-center justify-center py-24 gap-3">
        <div class="w-12 h-12 rounded-full border-4 border-surface-200 border-t-primary-600 animate-spin"></div>
        <p class="text-sm font-bold text-surface-500 dark:text-surface-400">Cargando detalle de la sesión...</p>
      </section>

      <!-- Estado de Error -->
      <section
        v-else-if="hasError"
        class="bg-white dark:bg-surface-100 rounded-3xl border border-rose-200 dark:border-rose-900/60 p-8 md:p-12 text-center shadow-xs max-w-lg mx-auto flex flex-col items-center gap-4 my-8"
      >
        <div class="w-14 h-14 rounded-2xl bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900/50">
          <IconAlertCircle class="w-7 h-7" />
        </div>
        <div class="space-y-1.5">
          <h3 class="text-base sm:text-lg font-bold text-surface-900 dark:text-surface-100 m-0">
            No se pudo consultar la sesión
          </h3>
          <p class="text-xs sm:text-sm text-surface-500 dark:text-surface-400 font-medium m-0 max-w-sm leading-relaxed">
            {{ typeof hasError === 'string' ? hasError : 'Ocurrió un error al contactar al servidor.' }}
          </p>
        </div>
        <div class="flex items-center gap-3 mt-2">
          <button
            @click="fetchSessionDetail"
            class="px-5 py-2.5 bg-primary-700 hover:bg-primary-800 text-white font-bold rounded-xl text-xs sm:text-sm transition-all shadow-sm active:scale-95 cursor-pointer"
          >
            Reintentar
          </button>
          <button
            @click="goBack"
            class="px-5 py-2.5 bg-surface-100 hover:bg-surface-200 text-surface-700 dark:bg-surface-200 dark:text-surface-200 font-bold rounded-xl text-xs sm:text-sm transition-all active:scale-95 cursor-pointer"
          >
            Volver a Sesiones
          </button>
        </div>
      </section>

      <!-- Contenido de Sesión -->
      <div v-else-if="sessionData" class="flex flex-col gap-6">

        <!-- Banner / Hero Card de la Sesión -->
        <section
          class="relative overflow-hidden rounded-3xl md:rounded-[2.5rem] bg-linear-to-br from-primary-900 via-primary-800 to-primary-700 text-white p-6 sm:p-8 shadow-xl shadow-primary-900/15 border border-white/10"
        >
          <div class="absolute -top-24 -right-24 w-72 h-72 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute -bottom-16 -left-16 w-56 h-56 bg-primary-400/20 rounded-full blur-2xl pointer-events-none"></div>

          <div class="relative z-10 flex flex-col gap-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="space-y-1.5 max-w-xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-[10px] md:text-xs font-bold tracking-wider uppercase">
                  <span class="w-1.5 h-1.5 rounded-full" :class="isHistorical ? 'bg-emerald-400' : 'bg-primary-300 animate-pulse'"></span>
                  {{ isHistorical ? 'Sesión Histórica Concluida' : 'Sesión Activa Programada' }}
                  <span class="opacity-75">• #{{ sessionData.id_sesion }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight m-0 text-white drop-shadow-xs">
                  {{ sessionData.disciplina }}
                </h1>
                <p class="text-primary-100 text-xs sm:text-sm font-medium m-0 leading-relaxed opacity-95">
                  Registro oficial y pase de lista auditado de la sesión impartida por el instructor.
                </p>
              </div>

              <!-- Badge de cumplimiento / aforo -->
              <div
                v-if="isHistorical"
                class="inline-flex items-center gap-3 px-4 py-2.5 rounded-2xl border border-emerald-400/30 bg-emerald-500/20 text-emerald-200 backdrop-blur-md self-start sm:self-center shrink-0 shadow-sm"
              >
                <div class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center shrink-0 shadow-inner">
                  <IconUser class="w-4 h-4 text-white" />
                </div>
                <div class="flex flex-col">
                  <span class="text-xs uppercase tracking-wider font-extrabold text-white/90">Asistencia</span>
                  <span class="text-lg sm:text-xl font-black leading-tight text-white">
                    {{ resumenStats.porcentaje }}% <span class="text-[11px] font-medium opacity-80">({{ resumenStats.asistieron }} / {{ resumenStats.inscritos }})</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Chips de información: Fecha, Horario, Cancha -->
            <div class="flex flex-wrap items-center gap-2 pt-2 text-xs font-semibold">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 text-white">
                <IconCalendar class="w-4 h-4 text-primary-200" />
                {{ formatFecha(sessionData.fecha_sesion) }}
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 text-white">
                <IconClock class="w-4 h-4 text-primary-200" />
                {{ sessionData.horario }}
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 text-white">
                <IconGrid class="w-4 h-4 text-primary-200" />
                {{ sessionData.espacio }}
              </span>
            </div>
          </div>
        </section>

        <!-- Tarjetas de Métricas de Aforo (4 Cards) -->
        <section class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div class="bg-white dark:bg-surface-100 rounded-2xl border border-surface-200/80 dark:border-surface-200/60 p-4 shadow-2xs">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-surface-500 dark:text-surface-400 block">Total Inscritos</span>
            <div class="flex items-baseline gap-2 mt-1">
              <span class="text-2xl sm:text-3xl font-black text-surface-900 dark:text-surface-100 leading-none">{{ resumenStats.inscritos }}</span>
              <span class="text-[11px] font-semibold text-surface-500">alumnos</span>
            </div>
          </div>

          <div class="bg-white dark:bg-surface-100 rounded-2xl border border-emerald-200/80 dark:border-emerald-900/60 p-4 shadow-2xs">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 block">Asistieron</span>
            <div class="flex items-baseline gap-2 mt-1">
              <span class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 leading-none">{{ resumenStats.asistieron }}</span>
              <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">confirmados</span>
            </div>
          </div>

          <div
            class="bg-white dark:bg-surface-100 rounded-2xl border p-4 shadow-2xs"
            :class="resumenStats.no_shows > 0 ? 'border-amber-200/90 dark:border-amber-900/60 bg-amber-50/20' : 'border-surface-200/80 dark:border-surface-200/60'"
          >
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider block" :class="resumenStats.no_shows > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-surface-500'">
              No-Shows
            </span>
            <div class="flex items-baseline gap-2 mt-1">
              <span class="text-2xl sm:text-3xl font-black leading-none" :class="resumenStats.no_shows > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-surface-900 dark:text-surface-100'">
                {{ resumenStats.no_shows }}
              </span>
              <span class="text-[11px] font-semibold text-amber-600 dark:text-amber-400">faltas</span>
            </div>
          </div>

          <div class="bg-white dark:bg-surface-100 rounded-2xl border border-primary-200/80 dark:border-primary-900/60 p-4 shadow-2xs">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-primary-700 dark:text-primary-400 block">Efectividad</span>
            <div class="flex items-baseline gap-2 mt-1">
              <span class="text-2xl sm:text-3xl font-black text-primary-600 dark:text-primary-400 leading-none">{{ resumenStats.porcentaje }}%</span>
              <span class="text-[11px] font-semibold text-primary-600 dark:text-primary-400">de asistencia</span>
            </div>
          </div>
        </section>

        <!-- SECCIÓN HISTÓRICA: Pase de Lista Auditado Nominal -->
        <section v-if="isHistorical" class="space-y-4">
          <!-- Filtros de Asistentes -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-surface-200 dark:border-surface-200/70 pb-3">
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="activeFilter = 'todos'"
                class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 cursor-pointer active:scale-95 border"
                :class="activeFilter === 'todos'
                  ? 'bg-primary-600 text-white border-primary-600 shadow-xs'
                  : 'bg-white dark:bg-surface-100 text-surface-600 dark:text-surface-300 border-surface-200 dark:border-surface-300 hover:bg-surface-50'"
              >
                <span>Todos</span>
                <span class="min-w-5 px-1.5 py-0.2 rounded-full text-[10px] font-black" :class="activeFilter === 'todos' ? 'bg-white/20' : 'bg-surface-100 text-surface-700 dark:bg-surface-200 dark:text-surface-300'">
                  {{ sessionData.asistentes?.length || 0 }}
                </span>
              </button>

              <button
                type="button"
                @click="activeFilter = 'asistieron'"
                class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 cursor-pointer active:scale-95 border"
                :class="activeFilter === 'asistieron'
                  ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs'
                  : 'bg-white dark:bg-surface-100 text-surface-600 dark:text-surface-300 border-surface-200 dark:border-surface-300 hover:bg-surface-50'"
              >
                <span>Asistieron</span>
                <span class="min-w-5 px-1.5 py-0.2 rounded-full text-[10px] font-black" :class="activeFilter === 'asistieron' ? 'bg-white/20' : 'bg-surface-100 text-surface-700 dark:bg-surface-200 dark:text-surface-300'">
                  {{ resumenStats.asistieron }}
                </span>
              </button>

              <button
                type="button"
                @click="activeFilter = 'no_shows'"
                class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 cursor-pointer active:scale-95 border"
                :class="activeFilter === 'no_shows'
                  ? 'bg-amber-600 text-white border-amber-600 shadow-xs'
                  : 'bg-white dark:bg-surface-100 text-surface-600 dark:text-surface-300 border-surface-200 dark:border-surface-300 hover:bg-surface-50'"
              >
                <span>Faltaron (No-Shows)</span>
                <span class="min-w-5 px-1.5 py-0.2 rounded-full text-[10px] font-black" :class="activeFilter === 'no_shows' ? 'bg-white/20' : 'bg-surface-100 text-surface-700 dark:bg-surface-200 dark:text-surface-300'">
                  {{ resumenStats.no_shows }}
                </span>
              </button>
            </div>

            <!-- Aviso de Solo Lectura -->
            <div class="inline-flex items-center gap-2 text-xs font-semibold text-surface-500 dark:text-surface-400">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              <span>Modo Auditoría • Solo Lectura</span>
            </div>
          </div>

          <!-- Estado Vacío si no hay alumnos -->
          <div
            v-if="asistentesFiltrados.length === 0"
            class="bg-white dark:bg-surface-100 rounded-3xl border border-surface-200/80 p-8 md:p-12 text-center shadow-xs flex flex-col items-center gap-3"
          >
            <div class="w-14 h-14 rounded-2xl bg-surface-100 dark:bg-surface-200 flex items-center justify-center text-surface-400">
              <IconUser class="w-7 h-7" />
            </div>
            <h3 class="text-base sm:text-lg font-bold text-surface-900 dark:text-surface-100 m-0">
              {{ activeFilter === 'todos' ? 'No hubo alumnos inscritos en esta clase' : 'Sin alumnos en este filtro' }}
            </h3>
            <p class="text-xs sm:text-sm text-surface-500 font-medium m-0 max-w-sm">
              La sesión concluyó sin registros coincidentes para la categoría seleccionada.
            </p>
          </div>

          <!-- Grid de Participantes -->
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <article
              v-for="(alumno, idx) in asistentesFiltrados"
              :key="`det-alumno-${idx}-${alumno.nombre}`"
              class="bg-white dark:bg-surface-100 rounded-2xl border border-surface-200/90 dark:border-surface-200/70 p-4 flex flex-col justify-between gap-3 shadow-2xs hover:border-primary-300 dark:hover:border-primary-700 transition-all relative overflow-hidden"
            >
              <div
                class="absolute left-0 top-0 bottom-0 w-1 transition-colors"
                :class="alumno.estatus_asistencia === 'ASISTIO' ? 'bg-emerald-500' : 'bg-amber-500'"
              ></div>

              <div class="flex items-start justify-between gap-3 pl-1.5">
                <div class="flex items-start gap-3 min-w-0">
                  <div
                    class="w-11 h-11 rounded-xl flex items-center justify-center font-black text-xs shrink-0 border"
                    :class="alumno.estatus_asistencia === 'ASISTIO'
                      ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800'
                      : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800'"
                  >
                    <img
                      v-if="alumno.foto_perfil"
                      :src="alumno.foto_perfil"
                      :alt="alumno.nombre"
                      class="w-full h-full object-cover rounded-xl"
                    />
                    <span v-else>{{ getInitials(alumno.nombre) }}</span>
                  </div>

                  <div class="min-w-0">
                    <h4 class="text-sm font-bold text-surface-900 dark:text-surface-100 truncate m-0">
                      {{ alumno.nombre }}
                    </h4>
                    <p class="text-xs font-medium text-surface-500 dark:text-surface-400 m-0 mt-0.5 truncate">
                      <span v-if="alumno.numero_accion" class="font-bold text-surface-700 dark:text-surface-300">Acción #{{ alumno.numero_accion }} • </span>
                      <span>{{ alumno.tipo }}</span>
                      <span v-if="alumno.parentesco" class="text-primary-600 dark:text-primary-400 font-semibold"> ({{ alumno.parentesco }})</span>
                      <span v-if="alumno.socio_anfitrion" class="opacity-90"> • Anfitrión: {{ alumno.socio_anfitrion }}</span>
                    </p>
                  </div>
                </div>

                <!-- Badge Asistencia -->
                <span
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase shrink-0 border shadow-2xs"
                  :class="alumno.estatus_asistencia === 'ASISTIO'
                    ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800'
                    : 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800'"
                >
                  <span
                    class="w-1.5 h-1.5 rounded-full shrink-0"
                    :class="alumno.estatus_asistencia === 'ASISTIO' ? 'bg-emerald-500' : 'bg-amber-500'"
                  ></span>
                  {{ alumno.estatus_asistencia === 'ASISTIO' ? 'Asistió' : (alumno.estatus_asistencia === 'CANCELO' ? 'Canceló' : 'No-Show') }}
                </span>
              </div>

              <!-- Registro de Método y Hora -->
              <div class="pt-2 border-t border-surface-100 dark:border-surface-200/80 flex items-center justify-between text-[11px] font-semibold text-surface-600 dark:text-surface-400 pl-1.5">
                <div class="flex items-center gap-1.5">
                  <span v-if="alumno.estatus_asistencia === 'ASISTIO'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-50 dark:bg-surface-200 border border-surface-200 dark:border-surface-300 text-surface-700 dark:text-surface-200">
                    <IconQr class="w-3 h-3 text-primary-600 dark:text-primary-400" />
                    <span>{{ formatMetodoDisplay(alumno.metodo) }}</span>
                  </span>
                  <span v-else class="text-surface-400 font-medium">
                    Inasistencia registrada
                  </span>
                </div>

                <div v-if="alumno.hora_registro" class="flex items-center gap-1 text-surface-500">
                  <IconClock class="w-3 h-3 text-surface-400" />
                  <span>{{ alumno.hora_registro.substring(0, 5) }} hrs</span>
                </div>
              </div>
            </article>
          </div>
        </section>

        <!-- SECCIÓN ACTIVA: Panel de Acciones Operativas (Fallback si la sesión aún no ha concluido) -->
        <section v-else class="bg-white dark:bg-surface-100 rounded-3xl border border-surface-200 p-6 md:p-8 shadow-sm flex flex-col gap-4">
          <h3 class="text-xs font-bold text-surface-400 uppercase tracking-widest mb-1 m-0 border-b border-surface-100 pb-3">
            Acciones Operativas
          </h3>

          <button
            type="button"
            @click="handleRegistrarAsistencia"
            class="w-full bg-primary-50 hover:bg-primary-100 text-primary-700 border border-primary-200 rounded-xl px-4 py-3.5 font-bold transition-all active:scale-95 flex items-center justify-center gap-3 cursor-pointer"
          >
            <IconQr class="w-5 h-5 shrink-0 text-primary-600" />
            Escanear Pases QR
          </button>

          <button
            type="button"
            @click="handleEmpezarSesion"
            class="w-full text-white rounded-xl px-4 py-3.5 font-bold transition-all flex items-center justify-center gap-3 shadow-sm border cursor-pointer"
            :class="sessionData.status === 'En curso' ? 'bg-surface-300 border-surface-300 text-surface-600 cursor-not-allowed shadow-none' : 'bg-primary-600 hover:bg-primary-700 border-primary-500 active:scale-95 shadow-primary-600/20'"
            :disabled="sessionData.status === 'En curso'"
          >
            <IconStart class="w-5 h-5 shrink-0" />
            {{ sessionData.status === 'En curso' ? 'Sesión en Curso' : 'Empezar Clase' }}
          </button>
        </section>

      </div>
    </div>
  </main>
</template>
