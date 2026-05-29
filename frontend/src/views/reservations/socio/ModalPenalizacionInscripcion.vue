<script setup>
/**
 * ModalPenalizacionInscripcion.vue
 *
 * Modal de advertencia glassmorphism oscuro que se muestra cuando el socio
 * cancela una inscripción con menos de 2 horas de antelación en una
 * actividad cerrada. Informa la consecuencia (NO_SHOW) antes de confirmar.
 *
 * Props:
 *   show        — boolean   — controla visibilidad
 *   inscripcion — object    — datos de la inscripción a cancelar
 *
 * Emits:
 *   confirm     — el usuario acepta cancelar (se registrará NO_SHOW)
 *   cancel      — el usuario decide mantener la inscripción
 */
const props = defineProps({
  show:        { type: Boolean, default: false },
  inscripcion: { type: Object,  default: null  },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="show"
        class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
        @click.self="emit('cancel')"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md" />

        <!-- Panel premium blanco de alta legibilidad -->
        <div
          class="relative w-full max-w-md rounded-3xl border border-red-200 bg-white shadow-2xl p-7 flex flex-col gap-5"
          style="z-index: 100000;"
        >
          <!-- Icono de advertencia -->
          <div class="flex items-center justify-center">
            <div class="w-16 h-16 rounded-full bg-red-50 border border-red-100 flex items-center justify-center">
              <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
            </div>
          </div>

          <!-- Título -->
          <div class="text-center space-y-1">
            <h3 class="text-xl font-black text-slate-900 tracking-tight">
              Cancelación tardía
            </h3>
            <p class="text-sm text-slate-500 font-semibold">
              Faltan menos de 2 horas para la sesión
            </p>
          </div>

          <!-- Detalle de la actividad -->
          <div v-if="inscripcion" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 space-y-1">
            <p class="text-sm font-extrabold text-slate-800">{{ inscripcion.nombre_actividad }}</p>
            <p class="text-xs text-slate-500 font-semibold">
              {{ inscripcion.fecha_sesion }} · {{ inscripcion.hora_inicio }} – {{ inscripcion.hora_fin }}
            </p>
          </div>

          <!-- Mensaje de consecuencia -->
          <div class="rounded-2xl border border-red-100 bg-red-50 px-4 py-3 space-y-1">
            <p class="text-sm font-extrabold text-red-700">
              ⚠️ Se registrará un No Show en la cuenta del Socio.
            </p>
            <p class="text-xs text-red-600/90 font-medium leading-relaxed">
              Acumular No Shows puede generar penalizaciones según las políticas del club.
            </p>
          </div>

          <!-- Acciones -->
          <div class="flex flex-col sm:flex-row gap-3 pt-1">
            <!-- Mantener inscripción -->
            <button
              id="btn-modal-penalizacion-mantener"
              @click="emit('cancel')"
              class="flex-1 px-4 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm font-bold text-slate-700
                     hover:bg-slate-100 transition-all duration-200 active:scale-95 cursor-pointer text-center"
            >
              Mantener inscripción
            </button>
            <!-- Cancelar de todas formas -->
            <button
              id="btn-modal-penalizacion-confirmar"
              @click="emit('confirm')"
              class="flex-1 px-4 py-3 rounded-2xl bg-red-600 hover:bg-red-500 text-sm font-extrabold text-white
                     transition-all duration-200 active:scale-95 shadow-lg shadow-red-200 cursor-pointer text-center"
            >
              Cancelar de todas formas
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}
.modal-fade-enter-active .relative,
.modal-fade-leave-active .relative {
  transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-from .relative {
  transform: scale(0.92) translateY(12px);
  opacity: 0;
}
</style>
