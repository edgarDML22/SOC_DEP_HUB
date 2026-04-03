<script setup>
import { ref } from "vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';

const items = ref([
    { route: '/socio/reservations/manage', label: 'Gestión', icon: 'pi pi-clock' },
    { route: '/socio/reservations/on-demand', label: 'On Demand', icon: 'pi pi-clock' },
    { route: '/socio/reservations/active-sessions', label: 'Actividades Programdas', icon: 'pi pi-clock' }
]);
</script>


<template>
  <div class="layout-wrapper">
    <div class="card p-4">
        <Tabs :value="$route.path">
            <TabList>
                <Tab v-for="tab in items" :key="tab.label" :value="tab.route">
                    <router-link v-if="tab.route" v-slot="{ href, navigate }" :to="tab.route" custom>
                        <a v-ripple :href="href" @click="navigate" class="flex items-center gap-2 text-inherit" style="text-decoration: none; color: inherit;">
                            <i :class="tab.icon" />
                            <span>{{ tab.label }}</span>
                        </a>
                    </router-link>
                </Tab>
            </TabList>
        </Tabs>
    </div>

    <div class="contenido-hijo p-4">
        <router-view />
    </div>
  </div>
</template>


<style scoped>
.layout-wrapper {
    min-height: 100vh;
    background-color: #f8f9fa;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    color: #111827;
}

</style>