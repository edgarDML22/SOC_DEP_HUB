<script setup>
import GerenteNavBar from '@/components/gerente/GerenteNavBar.vue'
import { useProfileStore } from '@/stores/profiles/socioStore'
import { onMounted } from 'vue';

const profileStore = useProfileStore()
onMounted (() =>{
  profileStore.fetchProfile()
})
</script>

<template>
  <div class="min-h-screen bg-surface-50">
    <GerenteNavBar />
    
    <!-- CONTENIDO -->
    <div class="w-full mx-auto pb-20 md:pb-0">
      <router-view />
    </div>
  </div>
</template>