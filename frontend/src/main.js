import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import { definePreset } from '@primevue/themes';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import '@/assets/css/main.css'

import App from './App.vue'
import router from './router'

const TemaSocDep = definePreset(Aura, {
    semantic: {
        // Escala azul completa: PrimeVue usa 200/300/400 (p. ej. primary.400 en modo oscuro);
        // si faltan, cae a su paleta por defecto (esmeralda) y los focos salen verdes.
        primary: {
            50: '#eff6ff',
            100: '#dbeafe',
            200: '#bfdbfe',
            300: '#93c5fd',
            400: '#60a5fa',
            500: '#3b82f6',
            600: '#2563eb',
            700: '#1d4ed8', //Color principal
            800: '#1e40af', // Color hover
            900: '#1e3a8a',
            950: '#172554'
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
        },
        // Campos de formulario (Select, DatePicker, MultiSelect, InputText…) con el mismo
        // look que AppInput: 44px de alto, esquinas de 12px y anillo de foco suave.
        formField: {
            paddingX: '0.875rem',
            paddingY: '0.625rem',
            borderRadius: '0.75rem',
            focusRing: { width: '0', style: 'none', color: 'transparent', offset: '0', shadow: '0 0 0 4px var(--app-focus-ring)' }
        },
        list: {
            padding: '0.375rem',
            gap: '2px',
            option: { padding: '0.625rem 0.75rem', borderRadius: '0.625rem' }
        },
        overlay: {
            select: { borderRadius: '0.875rem', shadow: '0 20px 40px -16px rgba(15, 23, 42, 0.3)' },
            popover: { borderRadius: '1rem', padding: '0.875rem', shadow: '0 24px 48px -18px rgba(15, 23, 42, 0.35)' }
        },
        colorScheme: {
            light: {
                formField: {
                    borderColor: '{surface.200}',
                    hoverBorderColor: '{surface.300}',
                    focusBorderColor: '{primary.500}',
                    color: '{surface.900}',
                    placeholderColor: '{surface.400}',
                    invalidPlaceholderColor: '{surface.400}',
                    iconColor: '{surface.500}',
                    shadow: 'none'
                },
                list: {
                    option: {
                        selectedBackground: '{primary.50}',
                        selectedColor: '{primary.700}',
                        selectedFocusBackground: '{primary.100}',
                        selectedFocusColor: '{primary.800}'
                    }
                }
            },
            // El modo oscuro del proyecto invierte la escala surface (--color-surface-*),
            // así que aquí se usan sus variables directamente.
            dark: {
                formField: {
                    background: 'var(--color-surface-100)',
                    disabledBackground: 'var(--color-surface-200)',
                    borderColor: 'var(--color-surface-200)',
                    hoverBorderColor: 'var(--color-surface-300)',
                    focusBorderColor: '{primary.400}',
                    color: 'var(--color-surface-900)',
                    placeholderColor: 'var(--color-surface-500)',
                    invalidPlaceholderColor: 'var(--color-surface-500)',
                    iconColor: 'var(--color-surface-600)',
                    shadow: 'none'
                },
                overlay: {
                    select: { background: 'var(--color-surface-100)', borderColor: 'var(--color-surface-200)', color: 'var(--color-surface-900)' },
                    popover: { background: 'var(--color-surface-100)', borderColor: 'var(--color-surface-200)', color: 'var(--color-surface-900)' }
                },
                list: {
                    option: {
                        focusBackground: 'var(--color-surface-200)',
                        color: 'var(--color-surface-800)',
                        focusColor: 'var(--color-surface-900)',
                        selectedBackground: 'rgba(59, 130, 246, 0.16)',
                        selectedColor: '{primary.300}',
                        selectedFocusBackground: 'rgba(59, 130, 246, 0.24)',
                        selectedFocusColor: '{primary.200}'
                    }
                }
            }
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
        },
        // Calendario: celdas más grandes (fáciles de tocar) y esquinas redondeadas
        datepicker: {
            header: { padding: '0 0 0.75rem 0' },
            title: { gap: '0.375rem', fontWeight: '700' },
            date: { width: '2.5rem', height: '2.5rem', borderRadius: '0.75rem' },
            weekDay: { padding: '0.375rem', fontWeight: '700' },
            colorScheme: {
                light: { today: { background: '{primary.50}', color: '{primary.700}' } },
                dark: { today: { background: 'rgba(59, 130, 246, 0.18)', color: '{primary.300}' } }
            }
        }
    }
});

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(ToastService)
app.directive('tooltip', Tooltip)
// Textos de PrimeVue en español (se combinan con los valores por defecto)
const localeEs = {
    dayNames: ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'],
    dayNamesShort: ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'],
    dayNamesMin: ['D', 'L', 'M', 'X', 'J', 'V', 'S'],
    monthNames: ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'],
    monthNamesShort: ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'],
    firstDayOfWeek: 1,
    today: 'Hoy',
    clear: 'Limpiar',
    weekHeader: 'Sem',
    dateFormat: 'dd/mm/yy',
    am: 'a. m.',
    pm: 'p. m.',
    chooseYear: 'Elegir año',
    chooseMonth: 'Elegir mes',
    chooseDate: 'Elegir fecha',
    prevYear: 'Año anterior',
    nextYear: 'Año siguiente',
    prevMonth: 'Mes anterior',
    nextMonth: 'Mes siguiente',
    emptyMessage: 'Sin opciones disponibles',
    emptyFilterMessage: 'Sin resultados',
    emptySearchMessage: 'Sin resultados',
    emptySelectionMessage: 'Sin selección',
    selectionMessage: '{0} seleccionados',
    searchMessage: '{0} resultados'
}

app.use(PrimeVue, {
    locale: localeEs,
    theme: {
        preset: TemaSocDep,
        options: {
            darkModeSelector: '.app-dark',
            // Al meter a PrimeVue en esta capa, tu <style scoped> siempre ganará sin usar !important
            cssLayer: {
                name: 'primevue',
                // PrimeVue declara este orden antes que la hoja de Tailwind, así que define la
                // prioridad de todas las capas: el reset de Tailwind (base) queda por debajo de
                // PrimeVue (si no, le borra los bordes a Select/DatePicker) y las utilidades
                // de Tailwind por encima (para poder ajustar componentes con clases).
                order: 'theme, base, primevue, components, utilities',
                options: {
                    mode: 'any'
                }
            }
        }
    }
});

app.mount('#app')