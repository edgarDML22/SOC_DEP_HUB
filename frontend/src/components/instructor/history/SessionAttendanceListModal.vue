<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import api from '@/services/api';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';
import {
  IconCalendar,
  IconClock,
  IconGrid,
  IconUser,
  IconQr,
  IconAlertCircle,
  IconWarning,
} from '@/components/icons';

const props = defineProps({
  sessionId: {
    type: [Number, String],
    required: true,
  },
  initialData: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const isLoading = ref(true);
const hasError = ref(false);
const sessionDetail = ref(null);
const activeFilter = ref('todos'); // 'todos' | 'asistieron' | 'no_shows'

const fetchSessionDetail = async () => {
  isLoading.value = true;
  hasError.value = false;

  try {
    const response = await api.get(`/instructor/historial-sesiones/${props.sessionId}`);
    if (response.data && response.data.success) {
      sessionDetail.value = response.data.data;
    } else {
      sessionDetail.value = response.data?.data || response.data;
    }
  } catch (error) {
    console.error('Error al cargar detalle de sesión histórica:', error);
    hasError.value =
      error.response?.data?.message ||
      'No se pudo obtener el desglose de asistencia para esta sesión. Intenta de nuevo.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  document.body.style.overflow = 'hidden';
  fetchSessionDetail();
});

onUnmounted(() => {
  document.body.style.overflow = '';
});

// Resumen de asistencia
const resumenStats = computed(() => {
  const data = sessionDetail.value;
  if (!data) return { inscritos: 0, asistieron: 0, no_shows: 0, porcentaje: 0 };

  const inscritos = data.resumen?.inscritos ?? (data.asistentes ? data.asistentes.length : 0);
  const asistieron = data.resumen?.asistieron ?? (data.asistentes ? data.asistentes.filter(a => a.estatus_asistencia === 'ASISTIO').length : 0);
  const no_shows = data.resumen?.no_shows ?? (data.asistentes ? data.asistentes.filter(a => a.estatus_asistencia === 'NO_SHOW').length : 0);
  const porcentaje = inscritos > 0 ? Math.round((asistieron / inscritos) * 100) : 0;

  return { inscritos, asistieron, no_shows, porcentaje };
});

const asistentesFiltrados = computed(() => {
  const lista = sessionDetail.value?.asistentes || [];
  if (activeFilter.value === 'asistieron') {
    return lista.filter((a) => a.estatus_asistencia === 'ASISTIO');
  }
  if (activeFilter.value === 'no_shows') {
    return lista.filter((a) => a.estatus_asistencia === 'NO_SHOW' || a.estatus_asistencia === 'CANCELO');
  }
  return lista;
});

const formatFecha = (iso) => {
  if (!iso) return '—';
  const cleanIso = String(iso).split('T')[0];
  const [y, m, d] = cleanIso.split('-');
  const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
  const monthIdx = parseInt(m, 10) - 1;
  return `${parseInt(d, 10)} ${meses[monthIdx] || ''} ${y}`;
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
  if (m.includes('MANUAL')) return 'Registro Manual';
  if (m.includes('SISTEMA')) return 'Sistema';
  return metodo || 'QR Scanner';
};
</script>

<template>
  <Transition name="fade">
    <div class="fixed inset-0 z-200 flex items-end sm:items-center justify-center p-0 sm:p-4">
      <!-- Backdrop -->
      <div
        class="absolute inset-0 bg-surface-900/60 dark:bg-black/80 backdrop-blur-sm transition-opacity"
        @click="emit('close')"
      ></div>

      <!-- Panel Modal -->
      <div
        class="relative bg-white dark:bg-surface-100 w-full sm:max-w-2xl rounded-t-3xl sm:rounded-[2.5rem] shadow-2xl overflow-hidden border border-surface-200 dark:border-surface-200/80 animate-scale-in max-h-[92dvh] flex flex-col z-10"
      >
        <!-- Header del Modal -->
        <div class="px-6 sm:px-8 pt-6 pb-4 border-b border-surface-100 dark:border-surface-200/70 bg-surface-50/70 dark:bg-surface-200/20 shrink-0">
          <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3.5 min-w-0">
              <div class="w-12 h-12 rounded-2xl bg-primary-50 dark:bg-primary-950/50 border border-primary-100 dark:border-primary-900/40 flex items-center justify-center text-primary-700 dark:text-primary-300 shrink-0 shadow-2xs">
                <DisciplineIcon
                  :name="sessionDetail?.disciplina || initialData?.disciplina || 'Actividad'"
                  class="w-6 h-6 shrink-0"
                />
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Sesión Concluida y Auditada
                  </span>
                  <span class="text-[11px] font-bold text-surface-400">
                    #{{ sessionId }}
                  </span>
                </div>
                <h2 class="text-lg sm:text-xl font-extrabold text-surface-900 dark:text-surface-100 truncate m-0">
                  {{ sessionDetail?.disciplina || initialData?.disciplina || 'Detalle de Asistencia' }}
                </h2>
              </div>
            </div>

            <!-- Botón cerrar -->
            <button
              type="button"
              @click="emit('close')"
              class="w-9 h-9 bg-white dark:bg-surface-200 hover:bg-surface-100 dark:hover:bg-surface-300 rounded-full flex items-center justify-center text-surface-500 dark:text-surface-300 border border-surface-200 dark:border-surface-300 transition-colors focus:outline-none shrink-0 cursor-pointer shadow-2xs active:scale-95"
              aria-label="Cerrar modal"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Metadatos de Fecha y Espacio -->
          <div class="flex flex-wrap items-center gap-2.5 mt-3 pt-3 border-t border-surface-200/60 dark:border-surface-200/40 text-xs font-semibold text-surface-600 dark:text-surface-300">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white dark:bg-surface-200 border border-surface-200 dark:border-surface-300">
              <IconCalendar class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400 shrink-0" />
              {{ formatFecha(sessionDetail?.fecha_sesion || initialData?.fecha_sesion) }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white dark:bg-surface-200 border border-surface-200 dark:border-surface-300">
              <IconClock class="w-3.5 h-3.5 text-surface-400 shrink-0" />
              {{ sessionDetail?.horario || `${initialData?.hora_inicio || ''} - ${initialData?.hora_fin || ''}` }}
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white dark:bg-surface-200 border border-surface-200 dark:border-surface-300">
              <IconGrid class="w-3.5 h-3.5 text-surface-400 shrink-0" />
              {{ sessionDetail?.espacio || initialData?.espacio || 'Instalaciones del Club' }}
            </span>
          </div>
        </div>

        <!-- Cuerpo del Modal con Scroll -->
        <div class="p-5 sm:p-7 overflow-y-auto space-y-6 flex-1 min-h-[250px]">
          <!-- Estado de Carga -->
          <div v-if="isLoading" class="flex flex-col items-center justify-center py-12 gap-3">
            <div class="w-10 h-10 rounded-full border-3 border-surface-200 border-t-primary-600 animate-spin"></div>
            <p class="text-xs sm:text-sm font-bold text-surface-500">Cargando lista nominal de asistencia...</p>
          </div>

          <!-- Estado de Error -->
          <div
            v-else-if="hasError"
            class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 rounded-3xl p-6 text-center space-y-3"
          >
            <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center text-rose-600 dark:text-rose-400 mx-auto">
              <IconAlertCircle class="w-6 h-6" />
            </div>
            <h3 class="text-sm font-bold text-rose-900 dark:text-rose-200 m-0">No se pudo cargar la asistencia</h3>
            <p class="text-xs text-rose-700 dark:text-rose-300 font-medium m-0">{{ hasError }}</p>
            <button
              type="button"
              @click="fetchSessionDetail"
              class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition-all shadow-xs cursor-pointer active:scale-95"
            >
              Reintentar
            </button>
          </div>

          <!-- Contenido Principal -->
          <template v-else>
            <!-- Tarjeta de Resumen y Aforo -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
              <div class="bg-surface-50 dark:bg-surface-200/40 border border-surface-200/70 dark:border-surface-200/50 rounded-2xl p-3 text-center">
                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-surface-500 dark:text-surface-400">Inscritos</span>
                <span class="text-xl sm:text-2xl font-black text-surface-900 dark:text-surface-100 mt-0.5 block">
                  {{ resumenStats.inscritos }}
                </span>
              </div>

              <div class="bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/70 dark:border-emerald-800/50 rounded-2xl p-3 text-center">
                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Asistieron</span>
                <span class="text-xl sm:text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-0.5 block">
                  {{ resumenStats.asistieron }}
                </span>
              </div>

              <div class="bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-800/50 rounded-2xl p-3 text-center">
                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300">No-Shows</span>
                <span class="text-xl sm:text-2xl font-black text-amber-700 dark:text-amber-400 mt-0.5 block">
                  {{ resumenStats.no_shows }}
                </span>
              </div>

              <div class="bg-primary-50/70 dark:bg-primary-950/30 border border-primary-200/70 dark:border-primary-800/50 rounded-2xl p-3 text-center">
                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-primary-800 dark:text-primary-300">Puntualidad</span>
                <span class="text-xl sm:text-2xl font-black text-primary-700 dark:text-primary-400 mt-0.5 block">
                  {{ resumenStats.porcentaje }}%
                </span>
              </div>
            </div>

            <!-- Tabs de Filtro de Asistentes -->
            <div class="flex items-center gap-2 border-b border-surface-200 dark:border-surface-200/70 pb-3">
              <button
                type="button"
                @click="activeFilter = 'todos'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer active:scale-95"
                :class="activeFilter === 'todos'
                  ? 'bg-primary-600 text-white shadow-xs'
                  : 'bg-surface-100 dark:bg-surface-200 text-surface-600 dark:text-surface-300 hover:bg-surface-200'"
              >
                <span>Todos</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold" :class="activeFilter === 'todos' ? 'bg-white/20' : 'bg-surface-200 dark:bg-surface-300'">
                  {{ sessionDetail?.asistentes?.length || 0 }}
                </span>
              </button>

              <button
                type="button"
                @click="activeFilter = 'asistieron'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer active:scale-95"
                :class="activeFilter === 'asistieron'
                  ? 'bg-emerald-600 text-white shadow-xs'
                  : 'bg-surface-100 dark:bg-surface-200 text-surface-600 dark:text-surface-300 hover:bg-surface-200'"
              >
                <span>Asistieron</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold" :class="activeFilter === 'asistieron' ? 'bg-white/20' : 'bg-surface-200 dark:bg-surface-300'">
                  {{ resumenStats.asistieron }}
                </span>
              </button>

              <button
                type="button"
                @click="activeFilter = 'no_shows'"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer active:scale-95"
                :class="activeFilter === 'no_shows'
                  ? 'bg-amber-600 text-white shadow-xs'
                  : 'bg-surface-100 dark:bg-surface-200 text-surface-600 dark:text-surface-300 hover:bg-surface-200'"
              >
                <span>Faltaron</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold" :class="activeFilter === 'no_shows' ? 'bg-white/20' : 'bg-surface-200 dark:bg-surface-300'">
                  {{ resumenStats.no_shows }}
                </span>
              </button>
            </div>

            <!-- Lista Nominal de Asistentes -->
            <div v-if="asistentesFiltrados.length === 0" class="py-8 text-center text-surface-400">
              <IconUser class="w-10 h-10 mx-auto mb-2 opacity-50" />
              <p class="text-sm font-bold text-surface-700 dark:text-surface-200 m-0">
                {{ activeFilter === 'todos' ? 'No hubo alumnos inscritos en esta clase' : 'No hay registros en esta categoría' }}
              </p>
              <p class="text-xs text-surface-500 font-medium m-0 mt-1">
                La sesión concluyó sin registros que coincidan con el filtro seleccionado.
              </p>
            </div>

            <div v-else class="space-y-3">
              <article
                v-for="(alumno, idx) in asistentesFiltrados"
                :key="`alumno-${idx}-${alumno.nombre}`"
                class="bg-white dark:bg-surface-100 border border-surface-200/90 dark:border-surface-200/70 rounded-2xl p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs hover:border-primary-300 dark:hover:border-primary-700 transition-all"
              >
                <!-- Info Alumno -->
                <div class="flex items-center gap-3 min-w-0">
                  <div
                    class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-xs shrink-0 border"
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

                <!-- Badges de Asistencia y Método -->
                <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
                  <!-- Método de Registro si asistió -->
                  <span
                    v-if="alumno.estatus_asistencia === 'ASISTIO'"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase bg-surface-100 text-surface-600 border border-surface-200 dark:bg-surface-200 dark:text-surface-300 dark:border-surface-300"
                  >
                    <IconQr class="w-3 h-3 text-primary-600 dark:text-primary-400" />
                    <span>{{ formatMetodoDisplay(alumno.metodo) }}</span>
                    <span v-if="alumno.hora_registro" class="text-surface-400 lowercase font-medium">({{ alumno.hora_registro.substring(0, 5) }})</span>
                  </span>

                  <!-- Estatus Asistencia -->
                  <span
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase border shadow-2xs"
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
              </article>
            </div>
          </template>
        </div>

        <!-- Footer del Modal -->
        <div class="px-6 py-4 bg-surface-50 dark:bg-surface-200/30 border-t border-surface-100 dark:border-surface-200/70 flex items-center justify-between gap-3 shrink-0">
          <p class="text-[11px] text-surface-500 font-medium m-0 truncate">
            Auditoría de pase de lista cerrada y respaldada.
          </p>
          <button
            type="button"
            @click="emit('close')"
            class="px-5 py-2.5 bg-surface-900 hover:bg-black dark:bg-surface-100 dark:hover:bg-surface-200 text-white dark:text-surface-900 rounded-xl text-xs font-bold transition-all cursor-pointer shadow-xs active:scale-95 shrink-0"
          >
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

