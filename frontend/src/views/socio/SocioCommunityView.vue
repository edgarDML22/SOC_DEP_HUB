<script setup>
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
const route = useRoute()

const items = ref([
  { route: '/socio/community/guests-list', label: 'Invitados', icon: 'pi pi-users' },
  { route: '/socio/community/family-members-list', label: 'Familiares', icon: 'pi pi-user' },
  { route: '/socio/community/friends-list', label: 'Amigos', icon: 'pi pi-user-plus' }
])

const activeTab = computed(() => {
    if (route.path.includes('family-members')) return '/socio/community/family-members-list'
    if (route.path.includes('guests')) return '/socio/community/guests-list'
    if (route.path.includes('friends')) return '/socio/community/friends-list'
    return route.path
})
</script>

<template>
  <div class="w-full font-sans bg-surface-50 min-h-screen">
    
    <!-- Contenedor del Layout -->
    <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 pt-4 md:pt-6 lg:pt-8 bg-surface-50">
      <!-- Nivel 1 (Pills) -->
      <div class="flex p-1 bg-surface-100 rounded-xl w-full max-w-md md:mx-0 overflow-x-auto scrollbar-thin shadow-sm">
          <router-link 
             v-for="tab in items"
             :key="tab.route"
             :to="tab.route"
             class="flex-1 py-2 px-3 text-xs md:text-sm text-center transition-all whitespace-nowrap flex items-center justify-center gap-1.5 focus:outline-none"
             :class="activeTab === tab.route ? 'bg-white shadow-sm text-primary-700 font-bold rounded-lg' : 'text-surface-500 font-medium hover:bg-surface-200/50 rounded-lg'"
          >
             {{ tab.label }}
          </router-link>
      </div>
    </div>

    <!-- Router View (Los hijos tienen sus propios márgenes) -->
    <div class="w-full">
        <router-view />
    </div>

  </div>
</template>