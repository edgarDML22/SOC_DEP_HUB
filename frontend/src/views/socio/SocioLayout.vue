<template>
    <div class="layout-wrapper">
        <SocioNavbar /> 
        <router-view />
    </div>
</template>

<script setup>
import SocioNavbar from '@/components/socio/SocioNavBar.vue';
import { useProfileStore } from '@/stores/profileStore'
import { onMounted } from 'vue';

const profileStore = useProfileStore();

onMounted (() =>{
  profileStore.fetchProfile()
})
</script>

<style scoped>
.layout-wrapper {
    min-height: 100vh;
    background-color: #f8f9fa;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    color: #111827;
}
</style>