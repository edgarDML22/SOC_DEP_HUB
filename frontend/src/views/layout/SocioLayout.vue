<template>
  <!-- Usamos w-full para ocupar toda la pantalla y flex-col para organizar el navbar y la vista -->
  <div class="min-h-screen bg-surface-50 font-sans text-surface-900 flex flex-col w-full">
      <SocioNavbar /> 
      <Toast position="bottom-right" />
      
      <!-- Este main es el que asegura que el perfil tome todo el espacio sobrante -->
      <main class="flex-1 w-full flex flex-col">
          <router-view v-slot="{ Component, route }">
            <Transition name="mobile-fade" mode="out-in">
              <component :is="Component" :key="route.path" />
            </Transition>
          </router-view>
      </main>
  </div>
</template>

<script setup>
import SocioNavbar from '@/components/socio/SocioNavBar.vue';
import { useProfileStore } from '@/stores/profiles/socioStore'
import { useBootstrapStore } from '@/stores/profiles/bootstrapStore'
import Toast from 'primevue/toast';
import { onMounted } from 'vue';

const profileStore = useProfileStore();
const bootstrapStore = useBootstrapStore();

onMounted(async () => {
    // Request 1: perfil + QR — bloqueante, navbar y guardas de ruta dependen de esto
    await profileStore.fetchProfile();
    // Request 2: notificaciones + familiares + invitados — no bloquea la UI principal
    bootstrapStore.fetchSocioData();
})
</script>