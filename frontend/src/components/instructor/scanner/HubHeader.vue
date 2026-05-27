<script setup>
import { computed } from 'vue'
import { IconArrowLeft, IconQr } from '@/components/icons'

const props = defineProps({
    paso:          { type: String,  required: true },
    ocultarVolver: { type: Boolean, default: false },
    categoria:     { type: String,  default: null },
})

const LABEL_CATEGORIA = {
    CLASES:        'Mis Clases',
    RESERVACIONES: 'Reservaciones',
    TORNEO:        'Encuentros Torneo',
}
const labelCategoria = computed(() =>
    props.paso === 'SELECCION_METODO' ? null : (LABEL_CATEGORIA[props.categoria] ?? null)
)
const emit = defineEmits(['volver'])

const TITULOS = {
    MENU:               'Registro de Asistencia',
    LIST_CLASES:        'Mis Clases',
    LIST_RESERVACIONES: 'Reservaciones',
    LIST_TORNEO:        'Encuentros Torneo',
    SELECCION_METODO:   'Método de Ingreso',
    ESCANER_ACTIVO:     'Escanear Código',
    PASE_LISTA:         'Pase de Lista',
    OUTPUT:             'Resultado',
}

const SUBTITULOS = {
    MENU:               'Selecciona la categoría para continuar',
    LIST_CLASES:        'Elige la sesión a la que deseas pasar lista',
    LIST_RESERVACIONES: '',
    LIST_TORNEO:        'Selecciona el encuentro a registrar',
    SELECCION_METODO:   'Elige cómo deseas ingresar el código QR',
    ESCANER_ACTIVO:     'Apunta la cámara o escribe el código del socio',
    PASE_LISTA:         'Confirma la asistencia cuando hayas terminado',
    OUTPUT:             'Registro procesado',
}

const titulo    = computed(() => TITULOS[props.paso]    ?? TITULOS.MENU)
const subtitulo = computed(() => SUBTITULOS[props.paso] ?? '')

const mostrarVolver = computed(() => !props.ocultarVolver)

const labelVolver = computed(() => props.paso === 'MENU' ? 'Inicio' : 'Volver')
</script>

<template>
  <div class="mb-6">
    <div class="flex items-center justify-between mb-4">
      <button
        v-if="mostrarVolver"
        @click="emit('volver')"
        class="flex items-center gap-1.5 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors group focus:outline-none"
      >
        <IconArrowLeft class="w-4 h-4 group-hover:-translate-x-1 transition-transform shrink-0" />
        {{ labelVolver }}
      </button>
      <div v-else />

      <!-- Badge de categoría activa -->
      <Transition
        enter-active-class="transition-all duration-200"
        enter-from-class="opacity-0 translate-x-2"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition-all duration-150"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 translate-x-2"
      >
        <span
          v-if="labelCategoria"
          class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-primary-700 bg-white border border-primary-200 shadow-sm px-3 py-1.5 rounded-full tracking-wide"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-primary-500 shrink-0"></span>
          {{ labelCategoria }}
        </span>
      </Transition>
    </div>

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
