<script setup>
import { IconCalendar, IconHistory } from '@/components/icons';
import IconPlus from '@/components/icons/IconPlus.vue';
import IconUserPlus from '@/components/icons/IconUserPlus.vue';

defineProps({
  actions: {
    type: Array,
    default: () => [
      { label: 'Crear Reserva', to: '/admin/reservations', icon: IconPlus },
      { label: 'Nuevo Socio', to: '/admin/socios/socios-list', icon: IconUserPlus },
      { label: 'Publicar Horario', to: '/admin/activities', icon: IconCalendar },
      { label: 'Auditoría Operativa', to: '/admin/reports', icon: IconHistory },
    ],
  },
});
</script>

<template>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <RouterLink
      v-for="action in actions"
      :key="action.to"
      :to="action.to"
      class="group flex items-center gap-3 min-h-[44px] px-4 py-3 rounded-xl bg-white border border-surface-200 shadow-sm hover:border-surface-300 hover:bg-surface-50 hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 transition-all duration-200"
    >
      <span class="w-9 h-9 rounded-lg bg-surface-50 border border-surface-200 flex items-center justify-center shrink-0 text-surface-600 group-hover:text-primary-600 group-hover:border-primary-200 transition-colors">
        <component :is="action.icon" class="w-4.5 h-4.5" />
      </span>
      <span class="text-xs font-bold text-surface-800 leading-tight">{{ action.label }}</span>
    </RouterLink>
  </div>
</template>
