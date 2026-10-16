<script setup>
import { computed } from 'vue';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';
import {
  IconCalendar,
  IconClock,
  IconGrid,
  IconUser,
  IconWarning,
} from '@/components/icons';

const props = defineProps({
  session: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['view-attendance', 'click']);

const esCancelada = computed(() => {
  const estatus = String(props.session.estatus_sesion || '').toUpperCase();
  return estatus.includes('CANCELADA');
});

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

const asistenciaRatio = computed(() => {
  const confirmadas = Number(props.session.asistencias_confirmadas ?? 0);
  const inscritos = Number(props.session.cantidad_inscritos ?? 0);
  return { confirmadas, inscritos };
});

const porcentajeAsistencia = computed(() => {
  const { confirmadas, inscritos } = asistenciaRatio.value;
  if (!inscritos || inscritos <= 0) return 0;
  return Math.min(100, Math.round((confirmadas / inscritos) * 100));
});

const handleActionClick = () => {
  emit('view-attendance', props.session);
};
</script>

<template>
  <article
    class="bg-white dark:bg-surface-100 rounded-3xl border border-surface-200/90 dark:border-surface-200/70 p-5 flex flex-col justify-between gap-4 shadow-2xs hover:shadow-md hover:border-primary-300 dark:hover:border-primary-700 transition-all group relative overflow-hidden text-surface-800 dark:text-surface-100"
  >
    <!-- Borde lateral de acento semántico (Verde = Completada, Ámbar/Rojo = Cancelada) -->
    <div
      class="absolute left-0 top-0 bottom-0 w-1.5 transition-colors"
      :class="esCancelada ? 'bg-amber-500' : 'bg-emerald-500'"
    ></div>

    <!-- Header de la tarjeta -->
    <div class="flex items-start justify-between gap-3 pl-1">
      <div class="flex items-start gap-3.5 min-w-0">
        <div
          class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-2xs group-hover:scale-105 transition-transform border"
          :class="esCancelada
            ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-200/70 dark:border-amber-900/50 text-amber-700 dark:text-amber-300'
            : 'bg-primary-50 dark:bg-primary-950/50 border-primary-100 dark:border-primary-900/40 text-primary-700 dark:text-primary-300'"
        >
          <DisciplineIcon
            :name="session.disciplina || 'Actividad'"
            class="w-6 h-6 shrink-0"
          />
        </div>
        <div class="min-w-0">
          <h3 class="text-sm sm:text-base font-bold text-surface-900 dark:text-surface-100 truncate m-0 group-hover:text-primary-700 dark:group-hover:text-primary-400 transition-colors">
            {{ session.disciplina || 'Clase Deportiva' }}
          </h3>
          <p class="text-xs font-medium text-surface-500 dark:text-surface-400 truncate m-0 mt-0.5 flex items-center gap-1.5">
            <IconGrid class="w-3.5 h-3.5 shrink-0 text-surface-400" />
            <span>{{ session.espacio || 'Cancha o Espacio por asignar' }}</span>
          </p>
        </div>
      </div>

      <!-- Badge de Estado -->
      <span
        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] sm:text-xs font-black uppercase shrink-0 border shadow-2xs"
        :class="esCancelada
          ? 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800'
          : 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800'"
      >
        <span
          class="w-1.5 h-1.5 rounded-full shrink-0"
          :class="esCancelada ? 'bg-amber-500' : 'bg-emerald-500'"
        ></span>
        {{ esCancelada ? 'Cancelada' : 'Completada' }}
      </span>
    </div>

    <!-- Fechas y Horario -->
    <div class="pl-1 flex flex-wrap items-center gap-2 text-xs font-medium text-surface-600 dark:text-surface-400">
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-surface-50 dark:bg-surface-200/50 text-surface-800 dark:text-surface-200 font-semibold border border-surface-200/60 dark:border-surface-300/60">
        <IconCalendar class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400 shrink-0" />
        {{ formatFecha(session.fecha_sesion) }}
      </span>
      <span v-if="session.hora_inicio" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-surface-50 dark:bg-surface-200/50 text-surface-600 dark:text-surface-300 border border-surface-200/60 dark:border-surface-300/60">
        <IconClock class="w-3.5 h-3.5 text-surface-400 shrink-0" />
        {{ formatHora(session.hora_inicio) }}<span v-if="session.hora_fin"> - {{ formatHora(session.hora_fin) }}</span>
      </span>
      <span v-if="session.cupo_maximo" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-surface-100 dark:bg-surface-200 text-surface-600 dark:text-surface-300">
        Cupo max: {{ session.cupo_maximo }}
      </span>
    </div>

    <!-- Sección de Métricas / Motivo de Cancelación -->
    <div class="pl-1 pt-1">
      <!-- Si es COMPLETADA: Barra visual de asistencia y ratio -->
      <div v-if="!esCancelada" class="space-y-2 bg-surface-50/70 dark:bg-surface-200/30 p-3 rounded-2xl border border-surface-200/60 dark:border-surface-200/40">
        <div class="flex items-center justify-between text-xs">
          <div class="flex items-center gap-1.5 font-bold text-surface-700 dark:text-surface-200">
            <IconUser class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
            <span>Asistencia de alumnos</span>
          </div>
          <span class="font-extrabold text-surface-900 dark:text-white">
            {{ asistenciaRatio.confirmadas }} / {{ asistenciaRatio.inscritos }} asistieron
            <span class="text-[10px] text-surface-500 font-normal">({{ porcentajeAsistencia }}%)</span>
          </span>
        </div>

        <!-- Barra de progreso -->
        <div class="w-full bg-surface-200 dark:bg-surface-300 rounded-full h-2 overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-500 ease-out"
            :class="porcentajeAsistencia >= 80 ? 'bg-emerald-500' : (porcentajeAsistencia >= 50 ? 'bg-primary-500' : 'bg-amber-500')"
            :style="{ width: `${porcentajeAsistencia}%` }"
          ></div>
        </div>
      </div>

      <!-- Si es CANCELADA: Recuadro semántico con motivo de suspensión -->
      <div
        v-else
        class="bg-amber-50/90 dark:bg-amber-950/30 border border-amber-200/90 dark:border-amber-800/60 rounded-2xl p-3 flex items-start gap-2.5"
      >
        <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-700 dark:text-amber-300 shrink-0">
          <IconWarning class="w-4 h-4" />
        </div>
        <div class="flex-1 min-w-0">
          <span class="block text-[11px] font-extrabold text-amber-900 dark:text-amber-200 uppercase tracking-wider">
            Motivo de cancelación
          </span>
          <p class="text-xs text-amber-800 dark:text-amber-300 font-medium m-0 mt-0.5 leading-snug break-words">
            {{ session.motivo_cancelacion || 'Sesión suspendida por el club (clima, torneo o mantenimiento).' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Botón de Acción -->
    <div class="pt-3 border-t border-surface-100 dark:border-surface-200/80 flex items-center justify-between gap-3 pl-1">
      <div class="text-[11px] font-bold text-surface-400 uppercase tracking-wider">
        ID #{{ session.id_sesion }}
      </div>

      <button
        type="button"
        @click="handleActionClick"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all shadow-xs active:scale-95 cursor-pointer"
        :class="esCancelada
          ? 'bg-surface-100 dark:bg-surface-200 text-surface-700 dark:text-surface-200 hover:bg-surface-200 dark:hover:bg-surface-300 border border-surface-200 dark:border-surface-300'
          : 'bg-primary-600 hover:bg-primary-700 text-white shadow-primary-700/20 hover:-translate-y-0.5'"
      >
        <span>Ver Lista de Asistencia</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  </article>
</template>

