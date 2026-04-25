<template>
  <!-- Usamos w-full para ocupar toda la pantalla y flex-col para organizar el navbar y la vista -->
  <div class="min-h-screen bg-surface-50 font-sans text-surface-900 flex flex-col w-full">
      <SocioNavbar /> 
      <Toast position="bottom-right" />
      
      <!-- Este main es el que asegura que el perfil tome todo el espacio sobrante -->
      <main class="flex-1 w-full flex flex-col">
          <router-view />
      </main>
  </div>
</template>

<script setup>
import SocioNavbar from '@/components/socio/SocioNavBar.vue';
import { useProfileStore } from '@/stores/profiles/socioStore'
import { useFamilyStore } from '@/stores/community/familyStore'
import { useGuestStore } from '@/stores/community/guestStore'
import Toast from 'primevue/toast';
import { onMounted } from 'vue';

const profileStore = useProfileStore();
const familyStore = useFamilyStore();
const guestStore = useGuestStore();

onMounted (() =>{
    profileStore.fetchProfile()
    familyStore.fetchMiembrosFamiliares()
    guestStore.fetchInvitados()
})
</script>