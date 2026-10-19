<script setup>
import { IconCalendar, IconHistory, IconChevronRight } from '@/components/icons';
import IconPlus from '@/components/icons/IconPlus.vue';
import IconUserPlus from '@/components/icons/IconUserPlus.vue';
import { toneOf } from '@/components/ui/tones';

defineProps({
  actions: {
    type: Array,
    default: () => [
      { label: 'Crear Reserva', to: '/admin/reservations', icon: IconPlus, tone: 'blue' },
      { label: 'Nuevo Socio', to: '/admin/socios/socios-list', icon: IconUserPlus, tone: 'emerald' },
      { label: 'Publicar Horario', to: '/admin/activities', icon: IconCalendar, tone: 'amber' },
      { label: 'Auditoría Operativa', to: '/admin/reports', icon: IconHistory, tone: 'violet' },
    ],
  },
});
</script>

<template>
  <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    <RouterLink
      v-for="action in actions"
      :key="action.to"
      :to="action.to"
      class="group flex min-h-[64px] items-center gap-3 rounded-2xl bg-white p-3 shadow-[0_12px_24px_-20px_rgba(15,23,42,0.4)] ring-1 ring-surface-200/70 transition-all duration-200 hover:-translate-y-0.5 hover:ring-primary-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 sm:p-4"
    >
      <span
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white transition-transform duration-200 group-hover:scale-105"
        :class="[toneOf(action.tone).solid, toneOf(action.tone).shadow]"
      >
        <component :is="action.icon" class="h-5 w-5" />
      </span>
      <span class="min-w-0 flex-1 text-xs font-bold leading-tight text-surface-800 sm:text-sm">{{ action.label }}</span>
      <IconChevronRight class="hidden h-4 w-4 shrink-0 text-surface-500 transition-transform group-hover:translate-x-0.5 group-hover:text-primary-500 sm:block" />
    </RouterLink>
  </div>
</template>
