<script setup>
import { useProfileStore } from '@/stores/profiles/socioStore';

const socioStore = useProfileStore();
</script>

<template>
  <div v-if="socioStore.profileData?.estatus_cuenta === 'MOROSO'" class="bg-red-900 text-white p-4 flex items-center justify-center gap-3 w-full z-50">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-red-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
      <line x1="12" y1="9" x2="12" y2="13"/>
      <line x1="12" y1="17" x2="12.01" y2="17"/>
    </svg>
    <span class="font-medium text-sm md:text-base">CUENTA MOROSA. Tienes pagos pendientes. Por favor, regulariza tu situación.</span>
  </div>
</template>
