<script setup>
import { computed } from 'vue';

const props = defineProps({
  estatus: {
    type: String,
    required: true,
    default: 'RECEPCION' 
  }
});

// Mapeamos el estatus de la API al paso actual (1, 2 o 3)
const pasoActual = computed(() => {
  // Si no hay estatus o no es ACTIVA, asumimos que apenas lo van a registrar (Recepción)
  if (!props.estatus || props.estatus !== 'ACTIVA' && props.estatus !== 'FINALIZADA') return 1;
  // Si está en la ludoteca
  if (props.estatus === 'ACTIVA') return 2;
  // Si ya lo recogieron (asumo que se llama FINALIZADA o ENTREGADO)
  return 3;
});

// Configuración visual de los 3 pasos
const pasos = [
  { id: 1, label: 'En Recepción', baseColor: 'bg-surface-300' },
  { id: 2, label: 'En Ludoteca', baseColor: 'bg-green-500' },
  { id: 3, label: 'Entregado', baseColor: 'bg-primary-600' }
];

const isActive = (pasoId) => pasoActual.value === pasoId;
const isPast = (pasoId) => pasoActual.value > pasoId;
</script>

<template>
  <div class="w-full py-4">
    <div class="relative flex items-center justify-between w-full">
      
      <!-- Línea conectora de fondo (Gris) -->
      <div class="absolute top-1/2 left-0 w-full h-1.5 bg-surface-200 -translate-y-1/2 rounded-full z-0"></div>
      
      <!-- Línea conectora de progreso (Color dinámico) -->
      <div 
        class="absolute top-1/2 left-0 h-1.5 -translate-y-1/2 rounded-full z-0 transition-all duration-500 ease-in-out"
        :class="pasoActual === 1 ? 'w-0' : pasoActual === 2 ? 'w-1/2 bg-green-400' : 'w-full bg-primary-500'"
      ></div>

      <!-- Los 3 Puntos (Bolitas) -->
      <div v-for="paso in pasos" :key="paso.id" class="relative z-10 flex flex-col items-center gap-2 group">
        
        <!-- Contenedor del Círculo -->
        <div class="relative flex items-center justify-center w-8 h-8 rounded-full transition-all duration-300 bg-white"
             :class="[
               isActive(paso.id) ? `border-4 border-white shadow-md ${paso.baseColor}` : 
               isPast(paso.id) ? `${paso.baseColor} border-2 border-white shadow-sm` : 
               'bg-surface-200 border-2 border-white'
             ]">
            
            <!-- Animación Pulse solo para el paso activo -->
            <div v-if="isActive(paso.id)" 
                 class="absolute w-full h-full rounded-full animate-ping opacity-40"
                 :class="paso.baseColor">
            </div>

            <!-- Icono de palomita si ya pasó ese estado -->
            <svg v-if="isPast(paso.id)" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            
            <!-- Círculo interior pequeño si está activo -->
            <div v-if="isActive(paso.id)" class="w-2.5 h-2.5 bg-white rounded-full"></div>
        </div>
        
        <!-- Texto debajo de la bolita -->
        <span class="absolute top-10 text-[10px] md:text-[11px] font-bold text-center whitespace-nowrap transition-colors"
              :class="isActive(paso.id) ? 'text-surface-900' : isPast(paso.id) ? 'text-surface-600' : 'text-surface-400'">
          {{ paso.label }}
        </span>
      </div>
      
    </div>
  </div>
</template>