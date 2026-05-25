<script setup>
import { computed } from 'vue'
import { IconArrowLeft, IconQr } from '@/components/icons'

const props = defineProps({
    paso: { type: String, required: true },
})
const emit = defineEmits(['volver'])

const TITULOS = {
    MENU:               'Registro de Asistencia',
    LIST_CLASES:        'Mis Clases',
    LIST_RESERVACIONES: 'Reservaciones',
    LIST_TORNEO:        'Encuentros Torneo',
    SELECCION_METODO:   'Método de Ingreso',
    ESCANER_ACTIVO:     'Escanear Código',
    OUTPUT:             'Resultado',
}

const SUBTITULOS = {
    MENU:               'Selecciona la categoría para continuar',
    LIST_CLASES:        'Elige la sesión a la que deseas pasar lista',
    LIST_RESERVACIONES: 'Selecciona la reserva para validar el acceso',
    LIST_TORNEO:        'Selecciona el encuentro a registrar',
    SELECCION_METODO:   'Elige cómo deseas ingresar el código QR',
    ESCANER_ACTIVO:     'Apunta la cámara o escribe el código del socio',
    OUTPUT:             'Registro procesado',
}

const titulo    = computed(() => TITULOS[props.paso]   ?? TITULOS.MENU)
const subtitulo = computed(() => SUBTITULOS[props.paso] ?? '')
const mostrarVolver = computed(() => props.paso !== 'MENU')
</script>

<template>
  <div class="mb-6">
    <button
      v-if="mostrarVolver"
      @click="emit('volver')"
      class="flex items-center gap-1.5 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 group focus:outline-none"
    >
      <IconArrowLeft class="w-4 h-4 group-hover:-translate-x-1 transition-transform shrink-0" />
      Volver
    </button>

    <div class="flex items-center gap-4">
      <div class="w-12 h-12 bg-primary-50 text-primary-600 rounded-2xl flex items-center justify-center shrink-0">
        <IconQr class="w-6 h-6" />
      </div>
      <div>
        <h1 class="text-xl md:text-2xl font-bold text-surface-900 tracking-tight leading-tight">{{ titulo }}</h1>
        <p class="text-sm text-surface-500 font-medium mt-0.5">{{ subtitulo }}</p>
      </div>
    </div>
  </div>
</template>
