<script setup>
import { ref, computed } from "vue";
import api from "@/services/api";

const props = defineProps({
  match: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["close", "updated"]);

const score1 = ref(props.match.resultado_comp1 ?? 0);
const score2 = ref(props.match.resultado_comp2 ?? 0);
const isSubmitting = ref(false);
const errorMsg = ref("");

const comp1Name = computed(() => {
  if (props.match.es_bye) return "BYE";
  return props.match.competidor1?.nombre_completo
    || `Participante #${props.match.competidor1?.id_interno ?? 1}`;
});

const comp2Name = computed(() => {
  if (props.match.es_bye) return "BYE";
  return props.match.competidor2?.nombre_completo
    || `Participante #${props.match.competidor2?.id_interno ?? 2}`;
});

const handleSubmit = async () => {
  errorMsg.value = "";

  // Validaciones
  if (score1.value < 0 || score2.value < 0) {
    errorMsg.value = "Los resultados deben ser enteros mayores o iguales a 0.";
    return;
  }

  if (score1.value === score2.value) {
    errorMsg.value = "Los resultados no pueden ser iguales. Debe haber un ganador.";
    return;
  }

  isSubmitting.value = true;
  try {
    const res = await api.patch(`/encuentros/${props.match.id_encuentro}/resultado`, {
      resultado_comp1: Number(score1.value),
      resultado_comp2: Number(score2.value),
    });

    if (res.data.estatus_encuentro) {
      emit("updated", {
        resultado_comp1: Number(score1.value),
        resultado_comp2: Number(score2.value)
      });
      emit("close");
    }
  } catch (err) {
    console.error("Error al reportar resultado:", err);
    errorMsg.value = err.response?.data?.message || "Ocurrió un error al enviar el resultado.";
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-surface-200 max-w-md w-full overflow-hidden animate-scale-in">
      <!-- Cabecera -->
      <div class="px-6 py-5 border-b border-surface-100 flex items-center justify-between">
        <h3 class="text-lg font-black text-surface-900">Reportar Resultado</h3>
        <button @click="emit('close')" class="text-surface-400 hover:text-surface-600 transition-colors">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <div v-if="errorMsg" class="p-4 bg-red-50 border border-red-100 text-red-600 rounded-xl text-sm font-bold">
          {{ errorMsg }}
        </div>

        <div class="grid grid-cols-2 gap-6 items-center">
          <!-- Competidor 1 -->
          <div class="space-y-2">
            <label class="block text-xs font-black uppercase tracking-widest text-surface-500 truncate" :title="comp1Name">
              {{ comp1Name }}
            </label>
            <input
              type="number"
              v-model.number="score1"
              min="0"
              class="w-full px-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-center font-bold text-lg text-surface-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
              required
            />
          </div>

          <!-- Competidor 2 -->
          <div class="space-y-2">
            <label class="block text-xs font-black uppercase tracking-widest text-surface-500 truncate" :title="comp2Name">
              {{ comp2Name }}
            </label>
            <input
              type="number"
              v-model.number="score2"
              min="0"
              class="w-full px-4 py-3 bg-surface-50 border border-surface-200 rounded-xl text-center font-bold text-lg text-surface-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
              required
            />
          </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-100">
          <button
            type="button"
            @click="emit('close')"
            class="px-5 py-2.5 bg-white border border-surface-200 text-surface-700 font-bold rounded-xl text-sm hover:bg-surface-50 transition-colors"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-5 py-2.5 bg-primary-600 text-white font-bold rounded-xl text-sm hover:bg-primary-700 transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2"
          >
            <span v-if="isSubmitting" class="w-4 h-4 border-2 border-white/35 border-t-white rounded-full animate-spin"></span>
            Enviar Marcador
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
