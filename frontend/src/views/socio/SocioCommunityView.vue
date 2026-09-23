<script setup>
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
const route = useRoute()

const items = ref([
  { route: '/socio/community/guests-list', label: 'Invitados', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' },
  { route: '/socio/community/family-members-list', label: 'Familiares', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' },
  { route: '/socio/community/friends-list', label: 'Amigos', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>' }
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
      <!-- Botón Volver -->
      <button @click="$router.push('/socio/home')" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 focus:outline-none w-fit group">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Volver
      </button>
      
      <!-- Nivel 1 (Pills - Mayor Jerarquía) -->
      <div class="flex p-1.5 bg-surface-100 rounded-2xl w-full max-w-2xl mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200">
          <router-link 
             v-for="tab in items"
             :key="tab.route"
             :to="tab.route"
             class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
             :class="activeTab === tab.route ? 'bg-primary-600 text-white font-extrabold rounded-xl shadow-md transform scale-[1.02]' : 'text-surface-500 font-bold hover:bg-white/60 hover:text-surface-700 rounded-xl'"
          >
             <span v-html="tab.icon" class="flex-shrink-0"></span>
             {{ tab.label }}
          </router-link>
      </div>
    </div>

    <!-- Router View (Los hijos tienen sus propios márgenes) -->
    <div class="w-full">
        <router-view v-slot="{ Component, route }">
          <Transition name="tab-fade" mode="out-in">
            <component :is="Component" :key="route.path" />
          </Transition>
        </router-view>
    </div>

  </div>
</template>