import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import { definePreset } from '@primevue/themes';
import ToastService from 'primevue/toastservice';
import '@/assets/css/main.css'

import App from './App.vue'
import router from './router'

const TemaSocDep = definePreset(Aura, {
    semantic: {
        primary: {
            50: '#eff6ff',
            100: '#dbeafe',
            500: '#3b82f6',
            600: '#2563eb',
            700: '#1d4ed8', //Color principal
            800: '#1e40af', // Color hover
            900: '#1e3a8a'
        },
        surface: {
            50: '#f9fafb',
            100: '#f3f4f6',
            200: '#e5e7eb',
            300: '#d1d5db',
            400: '#9ca3af',
            500: '#6b7280',
            600: '#4b5563',
            700: '#374151',
            800: '#1f2937',
            900: '#111827',
            950: '#030712'
        },
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
        borderRadius: {
            medium: '8px'
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
app.use(ToastService)
app.use(PrimeVue, {
    theme: {
        preset: TemaSocDep,
        options: {
            darkModeSelector: '.app-dark',
            // Al meter a PrimeVue en esta capa, tu <style scoped> siempre ganará sin usar !important
            cssLayer: {
                name: 'primevue',
                options: {
                    mode: 'any'
                }
            }
        }
    }
});

app.mount('#app')