import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import { definePreset } from '@primevue/themes';

import App from './App.vue'
import router from './router'
import './assets/css/global.css'


const TemaSocDep = definePreset(Aura, {
    semantic: {
        // 1. COLORES PRIMARIOS (Tus azules de SocioProfile y Navbar)
        primary: {
            50: '#eff6ff',
            100: '#dbeafe',
            500: '#3b82f6',
            600: '#2563eb',
            700: '#1d4ed8', // Azul base    
            800: '#1e40af', // Azul hover
            900: '#1e3a8a'
        },
        // 2. COLORES DE SUPERFICIE (Fondos y bordes de SocioHome y Perfil)
        surface: {
            50: '#f9fafb',  // Fondos de inputs
            100: '#f3f4f6', // Fondo de IconBoxes
            200: '#e5e7eb', // Bordes de tarjetas/inputs
            300: '#d1d5db',
            400: '#9ca3af',
            500: '#6b7280', // Color de subtítulos
            600: '#4b5563',
            700: '#374151',
            800: '#1f2937',
            900: '#111827', // Color de títulos principales
            950: '#030712'
        },
        // 3. TIPOGRAFÍA Y BORDES GLOBALES
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
        borderRadius: {
            medium: '8px' // El radio que usas en casi todo
        }
    },
    components: {
        tabs: {
            tab: { 
                padding: '12px 16px',
                fontWeight: '600'
            },
            tablist: {
                background: 'transparent',
                borderColor: '{surface.200}'
            }
        },
        card: {
            borderRadius: '12px', 
            shadow: '0 1px 3px 0 rgba(0, 0, 0, 0.1)'
        }
    }
});


const app = createApp(App)
const pinia = createPinia()
app.use(pinia)
app.use(router)

app.use(PrimeVue, {
    theme: {
        preset: TemaSocDep,
        options: {
            darkModeSelector: '.app-dark',
            cssLayer: false // Permite que tu CSS manual conviva fácilmente con PrimeVue
        }
    }
});

app.mount('#app')