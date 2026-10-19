<script setup>
import { computed } from 'vue';
import { IconChevronRight } from '@/components/icons';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';
import { getDisciplineColorClasses } from '@/utils/disciplineColors';

// sesion._estadoVisual: EN_CURSO | PROXIMA | CONFIRMADA | FINALIZADA (calculado en el dashboard)
const props = defineProps({
  sesion: { type: Object, required: true },
});

const ESTADO_UI = {
  EN_CURSO: {
    label: 'En curso',
    btn: 'Ver lista',
    time: 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-[0_10px_20px_-10px_rgba(5,150,105,0.7)]',
    chip: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
    row: 'ring-emerald-200 bg-emerald-50/40 dark:ring-emerald-500/30 dark:bg-emerald-500/5',
    action: 'bg-emerald-600 text-white hover:bg-emerald-700',
  },
  PROXIMA: {
    label: 'Próxima',
    btn: 'Gestionar',
    time: 'bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-[0_10px_20px_-10px_rgba(29,78,216,0.7)]',
    chip: 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300',
    row: 'ring-primary-200 dark:ring-primary-500/30',
    action: 'bg-primary-600 text-white hover:bg-primary-700',
  },
  CONFIRMADA: {
    label: 'Confirmada',
    btn: 'Gestionar',
    time: 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300',
    chip: 'bg-surface-200 text-surface-700',
    row: 'ring-surface-200/70',
    action: 'bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300',
  },
  FINALIZADA: {
    label: 'Finalizada',
    btn: 'Gestionar',
    time: 'bg-surface-200 text-surface-800',
    chip: 'bg-surface-200 text-surface-700',
    row: 'ring-surface-200/70',
    action: 'bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300',
  },
};

const ui = computed(() => ESTADO_UI[props.sesion._estadoVisual] || ESTADO_UI.CONFIRMADA);
const colors = computed(() => getDisciplineColorClasses(props.sesion.disciplina));

const formatHora = (h) => (h ? String(h).slice(0, 5) : '--:--');

const instructor = computed(() => {
  const s = props.sesion;
  return (typeof s.instructor === 'object' ? s.instructor?.nombre : s.instructor) || '';
});

const inscritos = computed(() => (typeof props.sesion.cantidad_inscritos === 'number' ? props.sesion.cantidad_inscritos : 0));

const cupos = computed(() => {
  const s = props.sesion;
  if (s.cupo_maximo) return `${inscritos.value} / ${s.cupo_maximo} cupos`;
  return `${s.cantidad_inscritos ?? 0} inscritos`;
});

const ocupacion = computed(() => {
  const max = props.sesion.cupo_maximo;
  if (!max) return null;
  const pct = Math.min(100, Math.round((inscritos.value / max) * 100));
  if (inscritos.value >= max) return { pct: 100, label: 'Grupo cerrado', bar: 'bg-gradient-to-r from-rose-500 to-pink-400' };
  return {
    pct,
    label: `${pct}% lleno`,
    bar: pct >= 80 ? 'bg-gradient-to-r from-amber-400 to-orange-400' : 'bg-gradient-to-r from-emerald-500 to-teal-400',
  };
});
</script>

<template>
  <div class="flex items-stretch gap-3 rounded-2xl bg-white p-3 ring-1 transition-shadow hover:shadow-[0_12px_24px_-18px_rgba(15,23,42,0.4)] sm:gap-4 sm:p-3.5" :class="ui.row">
    <!-- Horario -->
    <div class="flex w-[4.5rem] shrink-0 flex-col items-center justify-center rounded-xl px-2 py-2.5 text-center sm:w-20" :class="ui.time">
      <span class="text-lg font-extrabold leading-none tabular-nums">{{ formatHora(sesion.hora_inicio) }}</span>
      <span class="mt-1 text-[11px] font-semibold tabular-nums opacity-80">{{ formatHora(sesion.hora_fin) }}</span>
    </div>

    <!-- Detalle -->
    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border" :class="colors.bg">
          <DisciplineIcon v-if="sesion.disciplina" :name="sesion.disciplina" class="h-3.5 w-3.5 fill-current" :class="colors.icon" />
        </span>
        <span class="truncate text-sm font-bold text-surface-900" :title="sesion.disciplina">{{ sesion.disciplina || 'Actividad' }}</span>
        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider" :class="ui.chip">
          <span v-if="sesion._estadoVisual === 'EN_CURSO'" class="h-1.5 w-1.5 animate-pulse rounded-full bg-current motion-reduce:animate-none"></span>
          {{ ui.label }}
        </span>
      </div>

      <p class="m-0 mt-1 truncate text-xs font-medium text-surface-600">
        <template v-if="sesion.espacio">{{ sesion.espacio }}</template>
        <template v-if="sesion.espacio && instructor"> · </template>
        <template v-if="instructor">{{ instructor }}</template>
      </p>

      <!-- Ocupación -->
      <!-- En móvil: texto arriba y barra a todo el ancho; desde sm comparten renglón -->
      <div class="mt-2 flex flex-col-reverse gap-1.5 sm:flex-row sm:items-center sm:gap-2.5">
        <div v-if="ocupacion" class="h-1.5 w-full overflow-hidden rounded-full bg-surface-200 sm:w-auto sm:min-w-0 sm:flex-1" :title="cupos">
          <div class="h-full rounded-full transition-all duration-500" :class="ocupacion.bar" :style="{ width: ocupacion.pct + '%' }"></div>
        </div>
        <span class="shrink-0 text-xs font-bold tabular-nums text-surface-800">
          {{ cupos }}<template v-if="ocupacion"><span class="hidden font-semibold text-surface-600 sm:inline"> · {{ ocupacion.label }}</span></template>
        </span>
      </div>
    </div>

    <!-- Acción -->
    <RouterLink
      to="/admin/activities"
      class="inline-flex shrink-0 items-center justify-center self-center rounded-xl text-xs font-bold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 max-sm:h-10 max-sm:w-10 sm:min-h-[40px] sm:px-4"
      :class="ui.action"
      :aria-label="`${ui.btn}: ${sesion.disciplina || 'actividad'}`"
    >
      <span class="hidden sm:inline">{{ ui.btn }}</span>
      <IconChevronRight class="h-4 w-4 sm:hidden" />
    </RouterLink>
  </div>
</template>
