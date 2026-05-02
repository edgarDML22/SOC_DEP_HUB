<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  hora_limite: {
    type: String,
    required: true
  }
});

const minutosRestantes = ref(0);
const segundosRestantes = ref(0);
let intervalId = null;

const calcularTiempo = () => {
  if (!props.hora_limite) return;
  
  const limite = new Date(props.hora_limite).getTime();
  const ahora = Date.now();
  const diffMs = limite - ahora;

  // Calculamos los minutos exactos según el ticket
  minutosRestantes.value = Math.floor(diffMs / 60000);
  
  // Extraemos los segundos para que el reloj se vea animado
  const absDiff = Math.abs(diffMs);
  segundosRestantes.value = Math.floor((absDiff % 60000) / 1000);
};

onMounted(() => {
  calcularTiempo();
  // Se ejecuta cada 1 segundo (1000ms)
  intervalId = setInterval(calcularTiempo, 1000);
});

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId);
});

// Formateamos para que se vea estilo reloj (ej. "15:05")
const tiempoMostrar = computed(() => {
  const min = Math.abs(minutosRestantes.value);
  const sec = segundosRestantes.value.toString().padStart(2, '0');
  return `${min}:${sec}`;
});

// Reglas de estado de tu ticket
const isNormal = computed(() => minutosRestantes.value > 30);
const isWarning = computed(() => minutosRestantes.value <= 30 && minutosRestantes.value > 10);
const isDanger = computed(() => minutosRestantes.value <= 10 && minutosRestantes.value >= 0);
const isCritical = computed(() => minutosRestantes.value < 0);
</script>

<template>
  <div class="w-full">
    <!-- ESTADO CRÍTICO (Menos de 0 min) -->
    <div v-if="isCritical" class="bg-red-50 border border-red-200 rounded-xl p-3 flex items-start gap-3 w-full shadow-sm animate-pulse">
      <div class="mt-0.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
      </div>
      <div>
        <p class="text-red-700 font-bold text-[13px] m-0 leading-tight">Excedido por {{ tiempoMostrar }} min</p>
        <p class="text-red-600 font-medium text-[11px] m-0 mt-0.5 leading-snug">Sanción posible. Recoge a tu menor inmediatamente.</p>
      </div>
    </div>

    <!-- ESTADOS NORMAL, WARNING, DANGER -->
    <div v-else class="flex items-center justify-between p-3 rounded-xl border transition-colors bg-white shadow-sm"
         :class="{
           'border-surface-200': isNormal,
           'border-orange-300 bg-orange-50': isWarning,
           'border-red-300 bg-red-50': isDanger
         }">
      
      <span class="text-[11px] font-semibold uppercase tracking-wider text-surface-500"
            :class="{'text-orange-700': isWarning, 'text-red-700': isDanger}">
        Tiempo Restante:
      </span>

      <div class="flex items-center gap-1.5 font-bold text-base tabular-nums"
           :class="{
             'text-surface-700': isNormal,
             'text-orange-600': isWarning,
             'text-red-600': isDanger
           }">
        <!-- Ícono de alerta que aparece solo en modo Danger (<= 10 min) -->
        <svg v-if="isDanger" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        {{ tiempoMostrar }}
      </div>
    </div>
  </div>
</template>