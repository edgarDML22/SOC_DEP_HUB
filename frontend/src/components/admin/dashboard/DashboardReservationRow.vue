<script setup>
import { computed } from 'vue';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';
import { getDisciplineColorClasses } from '@/utils/disciplineColors';

const props = defineProps({
  reserva: { type: Object, required: true },
});

const ESTATUS_UI = {
  DISPONIBLE: { label: 'Disponible', cls: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' },
  EN_CURSO: { label: 'En Curso', cls: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' },
  FINALIZADA: { label: 'Finalizada', cls: 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' },
  CANCELADA: { label: 'Cancelada', cls: 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' },
  ACTIVA: { label: 'Activa', cls: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' },
  PENDIENTE: { label: 'Pendiente', cls: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' },
  COMPLETADA: { label: 'Completada', cls: 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' },
  NO_SHOW: { label: 'No Show', cls: 'bg-surface-200 text-surface-700' },
};

const disciplina = computed(() => props.reserva.disciplina?.nombre_disciplina || props.reserva.nombre_disciplina);
const colors = computed(() => getDisciplineColorClasses(disciplina.value));
const estatus = computed(() => ESTATUS_UI[props.reserva.estatus_operativo] || { label: props.reserva.estatus_operativo, cls: 'bg-surface-100 text-surface-600' });
const espacio = computed(() => props.reserva.espacio_fisico?.nombre_espacio || props.reserva.nombre_espacio || 'N/A');

const formatHora = (h) => (h ? String(h).slice(0, 5) : '--:--');
</script>

<template>
  <div class="flex items-start gap-3 rounded-2xl bg-white p-3 ring-1 ring-surface-200/70 transition-shadow hover:shadow-[0_12px_24px_-18px_rgba(15,23,42,0.4)] sm:items-center sm:gap-3.5 sm:p-3.5">
    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border" :class="colors.bg">
      <DisciplineIcon :name="disciplina" class="h-5 w-5 fill-current" :class="colors.icon" />
    </div>

    <div class="min-w-0 flex-1">
      <div class="flex items-start justify-between gap-2">
        <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
          <span class="truncate text-sm font-bold text-surface-900" :title="reserva.nombre_titular">{{ reserva.nombre_titular }}</span>
          <span
            v-if="reserva.modalidad"
            class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
            :class="reserva.modalidad === 'ACOMPANANTES' ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300' : 'bg-surface-100 text-surface-600'"
          >
            {{ reserva.modalidad === 'ACOMPANANTES' ? 'Acompañantes' : 'Individual' }}
          </span>
        </div>
        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider" :class="estatus.cls">
          {{ estatus.label }}
        </span>
      </div>

      <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-surface-600">
        <span class="truncate">{{ espacio }}</span>
        <span class="inline-flex items-center gap-1 font-bold text-surface-700">
          <svg class="h-3.5 w-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          {{ formatHora(reserva.hora_inicio) }} – {{ formatHora(reserva.hora_fin) }}
        </span>
        <span class="capitalize">{{ (disciplina || 'Multi-Deporte').toLowerCase() }}</span>
      </div>
    </div>
  </div>
</template>
