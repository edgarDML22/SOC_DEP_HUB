<template>
    <div class="layout-wrapper">
        <SocioNavbar /> 
        <router-view />
    </div>
</template>

<script setup>
import SocioNavbar from '@/components/socio/SocioNavBar.vue';
import { useProfileStore } from '@/stores/profiles/socioStore'
import { onMounted } from 'vue';

const profileStore = useProfileStore();

onMounted (() =>{
  profileStore.fetchProfile()
})
</script>

<style scoped>
.layout-wrapper {
    min-height: 100vh;
    background-color: var(--p-surface-50);
    font-family: var(--p-font-family);
    color: var(--p-surface-900);
}
</style>