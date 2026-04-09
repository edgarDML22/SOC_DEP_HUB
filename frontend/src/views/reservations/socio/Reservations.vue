<script setup>
import { ref } from "vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';

const items = ref([
    { route: '/socio/reservations/manage', label: 'Gestión', icon: 'pi pi-calendar-plus' },
    { route: '/socio/reservations/on-demand', label: 'On Demand', icon: 'pi pi-bolt' },
    { route: '/socio/reservations/active-sessions', label: 'Actividades Programadas', icon: 'pi pi-list' }
]);
</script>

<template>
  <div class="layout-wrapper">
    <div class="layout-container">
        
        <div class="tabs-card">
            <Tabs :value="$route.path" class="custom-tabs">
                <TabList>
                    <Tab v-for="tab in items" :key="tab.label" :value="tab.route">
                        <router-link v-if="tab.route" v-slot="{ href, navigate, isActive }" :to="tab.route" custom>
                            <a v-ripple :href="href" @click="navigate" class="tab-link" :class="{ 'is-active': isActive }">
                                <i :class="tab.icon" class="tab-icon" />
                                <span>{{ tab.label }}</span>
                            </a>
                        </router-link>
                    </Tab>
                </TabList>
            </Tabs>
        </div>

        <div class="content-area">
            <router-view v-slot="{ Component }">
                <keep-alive>
                    <component :is="Component" />
                </keep-alive>
            </router-view>
        </div>

    </div>
  </div>
</template>

<style scoped>
/* =========================================
   1. CONTENEDORES PRINCIPALES
========================================= */
.layout-wrapper {
    min-height: 100vh;
    background-color: var(--p-surface-50); /* Fondo gris súper clarito global */
    font-family: var(--p-font-family);
    padding: 1.5rem;
}

@media (min-width: 768px) {
    .layout-wrapper {
        padding: 2.5rem;
    }
}

.layout-container {
    max-width: 80rem; /* Equivale a max-w-7xl */
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem; /* Separación entre las pestañas y el contenido */
}

/* =========================================
   2. ESTILOS DE LA TARJETA DE PESTAÑAS
========================================= */
.tabs-card {
    background-color: #ffffff;
    border-radius: 1rem;
    padding: 0.5rem 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--p-surface-200);
    overflow-x: auto; /* Para que en móviles se pueda hacer scroll horizontal si hay muchas pestañas */
}

.tab-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: var(--p-surface-600);
    font-weight: 600;
    font-size: 1rem;
    padding: 0.75rem 1.25rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.tab-icon {
    font-size: 1.1rem;
    transition: transform 0.3s ease;
}

/* Estado normal hover */
.tab-link:hover:not(.is-active) {
    background-color: var(--p-surface-100);
    color: var(--p-surface-900);
}

/* Estado Activo */
.tab-link.is-active {
    color: var(--p-primary-600);
    background-color: var(--p-primary-50);
}

.tab-link.is-active .tab-icon {
    transform: scale(1.1); /* Efecto sutil al estar activo */
}

/* =========================================
   3. REESCRITURA DE PRIMEVUE (Force Clean)
========================================= */
/* Obligamos a PrimeVue a quitar sus bordes y fondos predeterminados para usar los nuestros */
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

:deep(.p-tab-active) {
    border: none !important;
}

/* =========================================
   4. ÁREA DE CONTENIDO
========================================= */
.content-area {
    width: 100%;
    /* Si necesitas que el router-view tenga fondo blanco por defecto, 
       descomenta las siguientes líneas, pero es mejor que cada componente dicte su diseño */
    /* background-color: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    border: 1px solid var(--p-surface-200); */
}
</style>