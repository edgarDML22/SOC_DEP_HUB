<script setup>
import { ref, computed } from 'vue'
import { useTournamentStore } from '@/stores/tournamentStore'
import { useAlerts } from '@/composables/useAlerts'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'

const props = defineProps({
  torneo: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const store = useTournamentStore()
const { toastSuccess, toastError } = useAlerts()

const loading = ref(false)
const errorMsg = ref('')
const showCancelForm = ref(false)
const motivoCancelacion = ref('')

const currentStatus = computed(() => {
  return props.torneo?.estado ?? props.torneo?.estatus_torneo ?? 'EN_PLANIFICACION'
})

const STEPS = [
  { value: 'EN_PLANIFICACION', label: 'Planificación', desc: 'Configuración inicial del torneo' },
  { value: 'EN_INSCRIPCION', label: 'Inscripción', desc: 'Registro abierto para competidores' },
  { value: 'PROGRAMADO', label: 'Programado', desc: 'Bracket generado y horarios listos' },
  { value: 'EN_CURSO', label: 'En Curso', desc: 'Partidos jugándose actualmente' },
  { value: 'FINALIZADO', label: 'Finalizado', desc: 'Torneo concluido con ganadores' }
]

const currentStepIndex = computed(() => {
  return STEPS.findIndex(s => s.value === currentStatus.value)
})

const nextTransition = computed(() => {
  const status = currentStatus.value
  if (status === 'EN_PLANIFICACION') {
    return {
      value: 'EN_INSCRIPCION',
      label: 'Abrir Inscripción',
      desc: 'Habilita el pre-registro público y permite a los participantes e instructores inscribirse al torneo.',
      color: 'from-purple-500 to-indigo-600'
    }
  } else if (status === 'EN_INSCRIPCION') {
    return {
      value: 'PROGRAMADO',
      label: 'Confirmar y Generar Bracket',
      desc: 'Cierra el registro de participantes y genera automáticamente la estructura de encuentros (bracket) del torneo.',
      color: 'from-blue-500 to-indigo-600'
    }
  } else if (status === 'PROGRAMADO') {
    return {
      value: 'EN_CURSO',
      label: 'Iniciar Torneo',
      desc: 'Marca el inicio oficial de la competencia. Los partidos podrán comenzar a jugarse y registrar resultados.',
      color: 'from-amber-500 to-orange-600'
    }
  } else if (status === 'EN_CURSO') {
    return {
      value: 'FINALIZADO',
      label: 'Finalizar Torneo',
      desc: 'Concluye el torneo y fija los resultados del bracket. Esta acción es irreversible.',
      color: 'from-emerald-500 to-teal-600'
    }
  }
  return null
})

const isFinalState = computed(() => {
  return ['FINALIZADO', 'CANCELADO'].includes(currentStatus.value)
})

const showConfirmModal = ref(false)
const transitionTargetStatus = ref(null)

const executeTransition = (targetStatus) => {
  transitionTargetStatus.value = targetStatus
  showConfirmModal.value = true
}

const executeTransitionConfirmed = async () => {
  errorMsg.value = ''
  loading.value = true

  try {
    const id = props.torneo?.id_torneo ?? props.torneo?.id
    await store.transicionarEstatus(id, transitionTargetStatus.value, transitionTargetStatus.value === 'CANCELADO' ? motivoCancelacion.value : null)

    toastSuccess(transitionTargetStatus.value === 'CANCELADO' ? 'Torneo cancelado correctamente' : 'Estado de torneo actualizado')
    emit('updated')
    emit('close')
    showConfirmModal.value = false
  } catch (err) {
    console.error("Error transitioning status:", err)
    errorMsg.value = store.error || err.response?.data?.message || 'Ocurrió un error al cambiar el estado del torneo.'
    toastError('Error al actualizar estado')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm" @click.self="emit('close')">
        
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto border border-surface-200 flex flex-col relative">
          <!-- Card header accent -->
          <div class="h-1.5 w-full bg-linear-to-r from-blue-500 to-indigo-600 shrink-0"></div>

          <div class="p-6 sm:p-8 flex-1 flex flex-col min-h-0">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
              <div>
                <h2 class="text-2xl font-black text-surface-900">Ciclo de Vida del Torneo</h2>
                <p class="text-sm text-surface-500 font-medium mt-1">Monitorea y cambia el estado del torneo "{{ torneo.nombre_torneo }}"</p>
              </div>
              <button @click="emit('close')" type="button" class="p-2 text-surface-400 hover:text-surface-700 hover:bg-surface-100 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
              
              <!-- Left Column: Stepper Timeline (5 columns) -->
              <div class="md:col-span-5 bg-surface-50 p-6 rounded-2xl border border-surface-100 space-y-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-surface-400 mb-2 px-1">Línea de Tiempo</h3>
                
                <div class="relative flex flex-col gap-6 pl-2">
                  <!-- Stepper vertical connector line -->
                  <div class="absolute left-[17px] top-4 bottom-4 w-0.5 bg-surface-200">
                    <!-- Colored active connector -->
                    <div class="h-full bg-primary-500 origin-top transition-transform duration-500" 
                         :style="{ transform: `scaleY(${currentStepIndex >= 0 ? currentStepIndex / (STEPS.length - 1) : 0})` }">
                    </div>
                  </div>
                  
                  <!-- Stepper Steps -->
                  <div v-for="(step, idx) in STEPS" :key="step.value" class="relative flex gap-4 items-start group">
                    <!-- Node Icon -->
                    <div class="relative z-10 flex size-9 items-center justify-center rounded-full border-2 transition-all duration-300 shrink-0"
                         :class="{
                           'bg-primary-500 border-primary-500 text-white shadow-lg shadow-primary-500/30 scale-110': idx === currentStepIndex && currentStatus !== 'CANCELADO',
                           'bg-emerald-50 border-emerald-500 text-emerald-600': idx < currentStepIndex && currentStatus !== 'CANCELADO',
                           'bg-white border-surface-300 text-surface-400': idx > currentStepIndex || currentStatus === 'CANCELADO',
                           'bg-red-50 border-red-500 text-red-600': idx === currentStepIndex && currentStatus === 'CANCELADO'
                         }">
                         
                         <!-- Completed Checkmark -->
                         <svg v-if="idx < currentStepIndex && currentStatus !== 'CANCELADO'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                         </svg>
                         <!-- Cancelled Mark -->
                         <svg v-else-if="idx === currentStepIndex && currentStatus === 'CANCELADO'" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                         </svg>
                         <!-- Active Pulsing Dot -->
                         <div v-else-if="idx === currentStepIndex" class="size-2.5 rounded-full bg-white animate-pulse"></div>
                         <!-- Pending Index -->
                         <span v-else class="text-xs font-bold">{{ idx + 1 }}</span>
                    </div>
                    
                    <!-- Text Labels -->
                    <div class="flex-1 pt-0.5">
                      <h4 class="text-sm font-bold transition-colors"
                          :class="{
                            'text-primary-600': idx === currentStepIndex && currentStatus !== 'CANCELADO',
                            'text-emerald-700': idx < currentStepIndex && currentStatus !== 'CANCELADO',
                            'text-surface-700': idx > currentStepIndex,
                            'text-red-700': idx === currentStepIndex && currentStatus === 'CANCELADO',
                            'text-surface-400 line-through': idx > currentStepIndex && currentStatus === 'CANCELADO'
                          }">
                        {{ step.label }}
                      </h4>
                      <p class="text-[11px] text-surface-400 font-medium mt-0.5 leading-snug">{{ step.desc }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right Column: Details, Warnings & Action Area (7 columns) -->
              <div class="md:col-span-7 space-y-6">
                <!-- Error Banner -->
                <div v-if="errorMsg" class="flex items-start gap-3 rounded-2xl bg-red-50 border border-red-200 px-5 py-4">
                  <svg class="mt-0.5 size-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                  </svg>
                  <p class="text-sm text-red-800 font-medium">{{ errorMsg }}</p>
                </div>

                <!-- Active State Card -->
                <div class="bg-surface-50 p-6 rounded-2xl border border-surface-200 shadow-sm relative overflow-hidden flex items-start gap-4">
                  <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                       :class="{
                         'bg-amber-50 text-amber-600': currentStatus === 'EN_PLANIFICACION',
                         'bg-purple-50 text-purple-600': currentStatus === 'EN_INSCRIPCION',
                         'bg-blue-50 text-blue-600': currentStatus === 'PROGRAMADO',
                         'bg-orange-50 text-orange-600': currentStatus === 'EN_CURSO',
                         'bg-emerald-50 text-emerald-600': currentStatus === 'FINALIZADO',
                         'bg-red-50 text-red-600': currentStatus === 'CANCELADO'
                       }">
                       <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                       </svg>
                  </div>
                  <div class="flex-1">
                    <span class="text-[10px] font-black uppercase tracking-widest text-surface-400">Estado Actual</span>
                    <h4 class="text-lg font-black text-surface-900 mt-0.5">
                      {{ STEPS.find(s => s.value === currentStatus)?.label ?? (currentStatus === 'CANCELADO' ? 'Cancelado' : currentStatus) }}
                    </h4>
                    <p class="text-xs text-surface-500 font-medium mt-1 leading-snug">
                      {{ props.torneo?.descripcion || 'Parámetros básicos del torneo cargados en el sistema.' }}
                    </p>
                  </div>
                </div>

                <!-- Cancelled Reason Banner -->
                <div v-if="currentStatus === 'CANCELADO'" class="bg-red-50 p-6 rounded-2xl border border-red-200 space-y-2">
                  <h5 class="text-xs font-black uppercase tracking-wider text-red-600">Motivo de Cancelación</h5>
                  <p class="text-sm font-bold text-red-900 leading-relaxed italic">
                    "{{ props.torneo?.motivo_cancelacion || 'No especificado.' }}"
                  </p>
                </div>

                <!-- Next Transition Card -->
                <div v-if="nextTransition" class="bg-gradient-to-br p-6 rounded-3xl text-white shadow-xl relative overflow-hidden space-y-4"
                     :class="nextTransition.color">
                  <!-- Glow background circles -->
                  <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                  <div class="absolute -left-10 -top-10 w-40 h-40 bg-white/5 rounded-full blur-xl pointer-events-none"></div>
                  
                  <div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-white/70">Próxima Etapa</span>
                    <h4 class="text-xl font-black mt-0.5">{{ nextTransition.label }}</h4>
                    <p class="text-xs text-white/90 font-medium mt-1 leading-relaxed">{{ nextTransition.desc }}</p>
                  </div>

                  <div class="pt-2 flex justify-end">
                    <button @click="executeTransition(nextTransition.value)"
                            :disabled="loading"
                            class="px-6 py-3 rounded-2xl bg-surface-950 text-white font-black hover:bg-surface-900 transition-colors shadow-lg flex items-center gap-2 text-xs tracking-wide border border-white/10">
                      <LoadingSpinner v-if="loading" class="size-4 text-white" />
                      <span v-else>Confirmar Transición</span>
                    </button>
                  </div>
                </div>

                <!-- Cancellation Section (Only if not in final state) -->
                <div v-if="!isFinalState" class="border border-surface-200 rounded-2xl overflow-hidden transition-all duration-300 bg-white"
                     :class="showCancelForm ? 'bg-red-50/10 border-red-200' : 'hover:bg-surface-50/50'">
                  <!-- Accordion Toggle Header -->
                  <button @click="showCancelForm = !showCancelForm"
                          class="w-full px-6 py-4 flex items-center justify-between font-bold text-sm text-surface-700 transition-colors"
                          :class="{ 'text-red-700 border-b border-red-100': showCancelForm }">
                    <span class="flex items-center gap-2">
                      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636" />
                      </svg>
                      Cancelar Torneo
                    </span>
                    <svg class="w-5 h-5 transition-transform duration-300" 
                         :class="{ 'rotate-180 text-red-500': showCancelForm }"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>

                  <!-- Expandable cancellation form -->
                  <div v-if="showCancelForm" class="p-6 space-y-4">
                    <p class="text-xs text-red-600 font-semibold flex items-start gap-1.5">
                      <svg class="w-4 h-4 shrink-0 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                      </svg>
                      La cancelación es definitiva e irreversible. Por favor, describe detalladamente la justificación de la cancelación del torneo.
                    </p>
                    
                    <div class="space-y-1.5">
                      <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                        Motivo de Cancelación (Mín. 20 caracteres)
                      </label>
                      <textarea v-model="motivoCancelacion" rows="3"
                                class="w-full p-4 bg-surface-50 border border-surface-200 rounded-2xl text-sm outline-none transition-all resize-none font-semibold text-surface-700 focus:ring-2 focus:ring-red-500/20 focus:border-red-500"
                                placeholder="Ej: El torneo no cuenta con suficientes participantes registrados para cumplir con el cupo mínimo de competencia..."></textarea>
                      <div class="flex justify-end">
                        <span class="text-[10px] font-bold"
                              :class="motivoCancelacion.length < 20 ? 'text-red-400' : 'text-emerald-500'">
                          {{ motivoCancelacion.length }} / 20 caracteres
                        </span>
                      </div>
                    </div>

                    <div class="flex justify-end pt-2">
                      <ConfirmButton label="Confirmar Cancelación"
                                     :loading="loading"
                                     :disabled="motivoCancelacion.length < 20 || loading"
                                     @click="executeTransition('CANCELADO')"
                                     class="bg-red-600! hover:bg-red-700!" />
                    </div>
                  </div>
                </div>
                
                <!-- Completed Final State Banner -->
                <div v-if="currentStatus === 'FINALIZADO'" class="bg-emerald-50/50 p-6 rounded-2xl border border-emerald-200 flex items-start gap-4">
                  <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                  </div>
                  <div>
                    <h4 class="text-sm font-black text-emerald-950">¡Torneo Finalizado!</h4>
                    <p class="text-xs text-emerald-700 font-bold mt-1 leading-snug">
                      Este torneo concluyó exitosamente. Se ha registrado toda la competencia y los resultados del bracket están fijos.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100 mt-6 shrink-0">
              <CancelButton label="Cerrar" @click="emit('close')" type="button" />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- SUB-MODAL DE CONFIRMACIÓN DE TRANSICIÓN/CANCELACIÓN -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="showConfirmModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-surface-900/80 backdrop-blur-xs" @click.self="showConfirmModal = false">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 border border-surface-200 text-center space-y-6">
          <!-- Header Icon -->
          <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center"
               :class="transitionTargetStatus === 'CANCELADO' ? 'bg-red-50 text-red-600' : 'bg-primary-50 text-primary-600'">
            <svg v-if="transitionTargetStatus === 'CANCELADO'" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <svg v-else class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>

          <!-- Content -->
          <div class="space-y-2">
            <h3 class="text-xl font-black text-surface-900">
              {{ transitionTargetStatus === 'CANCELADO' ? '¿Cancelar Torneo?' : '¿Confirmar Transición?' }}
            </h3>
            <p class="text-sm text-surface-500 font-semibold leading-relaxed">
              {{ transitionTargetStatus === 'CANCELADO' 
                ? 'Esta acción es definitiva e irreversible. Todos los partidos y registros asociados al torneo se cancelarán.' 
                : `¿Estás seguro de que deseas avanzar el estado del torneo a "${STEPS.find(s => s.value === transitionTargetStatus)?.label ?? transitionTargetStatus}"?`
              }}
            </p>
          </div>

          <!-- Buttons -->
          <div class="flex gap-3">
            <CancelButton label="No, volver" @click="showConfirmModal = false" class="flex-1" />
            <ConfirmButton 
              :label="transitionTargetStatus === 'CANCELADO' ? 'Sí, cancelar' : 'Sí, avanzar'" 
              :loading="loading" 
              @click="executeTransitionConfirmed" 
              class="flex-1"
              :class="transitionTargetStatus === 'CANCELADO' ? 'bg-red-600! hover:bg-red-700!' : 'bg-primary-600! hover:bg-primary-700!'" 
            />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
