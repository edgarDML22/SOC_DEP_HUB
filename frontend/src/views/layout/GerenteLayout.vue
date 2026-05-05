<script setup>
import GerenteSideBar from '@/components/gerente/GerenteSideBar.vue'
import { useAdminStore } from '@/stores/profiles/adminStore'
import { onMounted } from 'vue'

const profileStore = useAdminStore()
onMounted(() => {
  profileStore.fetchProfile()
})
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-surface-50">
    <GerenteSideBar />

    <main class="flex-1 overflow-y-auto">
      <router-view />
    </main>
  </div>
</template>