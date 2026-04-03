<script setup>
import GerenteSidebar from '@/components/gerente/GerenteSideBar.vue'
import GerenteTopbar from '@/components/gerente/GerenteTopBar.vue'
import { useProfileStore } from '@/stores/profileStore'
import { onMounted } from 'vue';

const profileStore = useProfileStore()
onMounted (() =>{
  profileStore.fetchProfile()
})
</script>

<template>
  <div class="layout">

    <!-- SIDEBAR -->
    <GerenteSidebar />

    <!-- CONTENIDO -->
    <div class="main">

      <!-- TOPBAR -->
      <GerenteTopbar />

      <!-- VISTAS DINÁMICAS -->
      <div class="content">
        <router-view />
      </div>

    </div>

  </div>
</template>

<style scoped>
.layout {
  display: flex;
}

/* Contenedor principal (lado derecho) */
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f9fafb;
  min-height: 100vh;
}

/* Aquí vive tu contenido (tablas, torneos, etc) */
.content {
  padding: 20px;
}
</style>