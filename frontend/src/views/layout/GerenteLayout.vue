<script setup>
import GerenteSideBar from '@/components/gerente/GerenteSideBar.vue'
import GerenteTopBar from '@/components/gerente/GerenteTopBar.vue'
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

    <main class="flex-1 flex flex-col min-h-0">
      <GerenteTopBar class="shrink-0" />
      <!-- overflow-y-auto here lets normal pages scroll; views that want full-height
           declare h-full on their root element and control their own overflow -->
      <div class="flex-1 overflow-y-auto min-h-0">
        <router-view v-slot="{ Component }">
          <keep-alive>
            <component :is="Component" />
          </keep-alive>
        </router-view>
      </div>
    </main>
  </div>
</template>