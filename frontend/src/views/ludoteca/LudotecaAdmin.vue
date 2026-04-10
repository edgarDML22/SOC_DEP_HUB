<script setup>
import { ref } from "vue";
import { useRoute } from "vue-router";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';

const route = useRoute()


const items = ref([
  { route: { name: 'admin-ludoteca-list' }, label: 'Lista ludoteca Gerencia' }
])
</script>

<template>
  <div class="layout-wrapper">
    <div class="layout-container">
        
  <!--     
      <div class="tabs-card">
        <Tabs :value="route.name" class="custom-tabs">
          <TabList>
            <Tab 
              v-for="tab in items" 
              :key="tab.label" 
              :value="tab.route.name"
            >
              <router-link 
                :to="tab.route"
                class="tab-link"
                active-class="is-active"
              >
              </router-link>
            </Tab>
          </TabList>
        </Tabs>
      </div> -->

      <!-- CONTENIDO -->
      <div class="content-area">
        <!-- 🔥 OBLIGATORIO -->
        <router-view />
      </div>  

    </div>
  </div>
</template>

<style scoped>
.layout-wrapper {
    min-height: 100vh;
    background-color: var(--p-surface-50);
    font-family: var(--p-font-family);
    padding: 1.5rem;
}

@media (min-width: 768px) {
    .layout-wrapper {
        padding: 2.5rem;
    }
}

.layout-container {
    max-width: 80rem;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.tabs-card {
    background-color: #ffffff;
    border-radius: 1rem;
    padding: 0.5rem 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--p-surface-200);
    overflow-x: auto;
}

.tab-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--p-surface-600);
    font-weight: 600;
    font-size: 1rem;
    padding: 0.75rem 1.25rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.tab-link:hover:not(.is-active) {
    background-color: var(--p-surface-100);
    color: var(--p-surface-900);
}

.tab-link.is-active {
    color: var(--p-primary-600);
    background-color: var(--p-primary-50);
}

:deep(.p-tablist-tab-list) {
    border: none !important; 
    background: transparent !important;
}

:deep(.p-tab) {
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin-right: 0.5rem !important;
}

.content-area {
    width: 100%;
}
</style>