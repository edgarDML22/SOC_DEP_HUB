<script setup>
import { ref, computed } from 'vue'
import { useTournamentStore } from '@/stores/tournamentStore'
import { useAlerts } from '@/composables/useAlerts'
import AppModal from '@/components/ui/modal/AppModal.vue'
import AppConfirmModal from '@/components/ui/modal/AppConfirmModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppField from '@/components/ui/form/AppField.vue'
import AppTextarea from '@/components/ui/form/AppTextarea.vue'
import { IconTrophy, IconWarning, IconShield } from '@/components/icons'

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
      desc: 'Habilita el pre-registro público y permite a los participantes e instructores inscribirse al torneo.'
    }
  } else if (status === 'EN_INSCRIPCION') {
    return {
      value: 'PROGRAMADO',
      label: 'Confirmar y Generar Bracket',
      desc: 'Cierra el registro de participantes y genera automáticamente la estructura de encuentros (bracket) del torneo.'
    }
  } else if (status === 'PROGRAMADO') {
    return {
      value: 'EN_CURSO',
      label: 'Iniciar Torneo',
      desc: 'Marca el inicio oficial de la competencia. Los partidos podrán comenzar a jugarse y registrar resultados.'
    }
  } else if (status === 'EN_CURSO') {
    return {
      value: 'FINALIZADO',
      label: 'Finalizar Torneo',
      desc: 'Concluye el torneo y fija los resultados del bracket. Esta acción es irreversible.'
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
  errorMsg.value = ''
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
  } finally {
    loading.value = false
  }
}

const isCancelling = computed(() => transitionTargetStatus.value === 'CANCELADO')
const confirmMessage = computed(() => isCancelling.value
  ? 'Esta acción es definitiva e irreversible. Todos los partidos y registros asociados al torneo se cancelarán.'
  : `¿Seguro que quieres avanzar el torneo a "${STEPS.find(s => s.value === transitionTargetStatus.value)?.label ?? transitionTargetStatus.value}"?`)
const currentLabel = computed(() => STEPS.find(s => s.value === currentStatus.value)?.label ?? (currentStatus.value === 'CANCELADO' ? 'Cancelado' : currentStatus.value))
</script>

<template>
  <AppModal
    :model-value="true"
    title="Ciclo de vida del torneo"
    :subtitle="torneo.nombre_torneo"
    :icon="IconTrophy"
    size="lg"
    :persistent="loading"
    @update:model-value="(v) => !v && emit('close')"
  >
    <div class="flex flex-col gap-4">
      <div v-if="errorMsg && !showConfirmModal" role="alert"
        class="flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
        <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
        {{ errorMsg }}
      </div>

      <!-- Estado actual -->
      <div class="flex items-start gap-3.5 rounded-2xl bg-surface-50 p-4 ring-1 ring-surface-200">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-white"
          :class="currentStatus === 'CANCELADO' ? 'bg-rose-500' : currentStatus === 'FINALIZADO' ? 'bg-emerald-500' : 'bg-primary-600'">
          <IconTrophy class="h-5 w-5" />
        </span>
        <div class="min-w-0">
          <p class="m-0 text-[11px] font-bold uppercase tracking-widest text-surface-500">Estado actual</p>
          <p class="m-0 text-lg font-bold text-surface-900">{{ currentLabel }}</p>
          <p class="m-0 mt-1 text-sm font-medium text-surface-600">
            {{ torneo?.descripcion || 'Parámetros básicos del torneo cargados en el sistema.' }}
          </p>
        </div>
      </div>

      <!-- Línea de tiempo -->
      <ol class="m-0 flex list-none flex-col gap-0 p-0" aria-label="Línea de tiempo del torneo">
        <li v-for="(step, idx) in STEPS" :key="step.value" class="relative flex gap-3.5 pb-4 last:pb-0">
          <span v-if="idx < STEPS.length - 1" class="absolute left-[15px] top-8 h-[calc(100%-2rem)] w-0.5 rounded-full"
            :class="idx < currentStepIndex && currentStatus !== 'CANCELADO' ? 'bg-primary-600' : 'bg-surface-200'" aria-hidden="true"></span>
          <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-colors"
            :class="currentStatus === 'CANCELADO'
              ? (idx === currentStepIndex ? 'bg-rose-500 text-white' : 'bg-surface-100 text-surface-500 ring-1 ring-inset ring-surface-200')
              : idx < currentStepIndex
                ? 'bg-primary-600 text-white'
                : idx === currentStepIndex
                  ? 'bg-primary-600 text-white ring-4 ring-primary-100 dark:ring-primary-500/20'
                  : 'bg-surface-100 text-surface-500 ring-1 ring-inset ring-surface-200'">
            <svg v-if="idx < currentStepIndex && currentStatus !== 'CANCELADO'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
            <template v-else>{{ idx + 1 }}</template>
          </span>
          <div class="min-w-0 pt-0.5">
            <p class="m-0 text-sm font-bold" :class="idx === currentStepIndex ? 'text-surface-900' : 'text-surface-700'">{{ step.label }}</p>
            <p class="m-0 text-xs font-medium text-surface-500">{{ step.desc }}</p>
          </div>
        </li>
      </ol>

      <!-- Motivo de cancelación -->
      <div v-if="currentStatus === 'CANCELADO'"
        class="rounded-2xl bg-rose-50 p-4 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:ring-rose-500/30">
        <p class="m-0 text-[11px] font-bold uppercase tracking-widest text-rose-700 dark:text-rose-300">Motivo de cancelación</p>
        <p class="m-0 mt-1 text-sm font-semibold italic text-rose-900 dark:text-rose-100">"{{ torneo?.motivo_cancelacion || 'No especificado.' }}"</p>
      </div>

      <!-- Próxima etapa -->
      <div v-if="nextTransition" class="flex flex-col gap-3 rounded-2xl bg-primary-50 p-4 ring-1 ring-primary-100 dark:bg-primary-500/10 dark:ring-primary-500/30">
        <div>
          <p class="m-0 text-[11px] font-bold uppercase tracking-widest text-primary-700 dark:text-primary-300">Próxima etapa</p>
          <p class="m-0 mt-0.5 text-base font-bold text-surface-900">{{ nextTransition.label }}</p>
          <p class="m-0 mt-1 text-sm font-medium text-surface-600">{{ nextTransition.desc }}</p>
        </div>
        <AppButton class="w-full sm:w-auto sm:self-end" :disabled="loading" @click="executeTransition(nextTransition.value)">
          Confirmar transición
        </AppButton>
      </div>

      <!-- Torneo finalizado -->
      <div v-if="currentStatus === 'FINALIZADO'"
        class="flex items-start gap-2.5 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30">
        <IconShield class="mt-0.5 h-4 w-4 shrink-0" />
        <p class="m-0">Torneo finalizado. La competencia concluyó y los resultados del bracket están fijos.</p>
      </div>

      <!-- Cancelar torneo -->
      <div v-if="!isFinalState" class="overflow-hidden rounded-2xl ring-1 transition-colors"
        :class="showCancelForm ? 'ring-rose-200 dark:ring-rose-500/30' : 'ring-surface-200'">
        <button type="button" class="flex w-full cursor-pointer items-center justify-between gap-2 border-0 bg-transparent px-4 py-3.5 text-left text-sm font-bold transition-colors hover:bg-surface-50"
          :class="showCancelForm ? 'text-rose-700 dark:text-rose-300' : 'text-surface-700'"
          :aria-expanded="showCancelForm" @click="showCancelForm = !showCancelForm">
          Cancelar torneo
          <svg class="h-5 w-5 shrink-0 transition-transform duration-300" :class="showCancelForm ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </button>
        <div v-if="showCancelForm" class="flex flex-col gap-3 px-4 pb-4">
          <p class="m-0 flex items-start gap-2 text-sm font-semibold text-rose-700 dark:text-rose-300">
            <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
            La cancelación es definitiva e irreversible. Describe el motivo.
          </p>
          <AppField label="Motivo de cancelación" for="torneo-motivo" required
            :hint="`${motivoCancelacion.length} / 20 caracteres mínimo`">
            <AppTextarea id="torneo-motivo" v-model="motivoCancelacion" rows="3"
              placeholder="Ej: No hay suficientes participantes registrados para cumplir el cupo mínimo…" />
          </AppField>
          <AppButton tone="rose" class="w-full sm:w-auto sm:self-end" :disabled="motivoCancelacion.length < 20 || loading"
            @click="executeTransition('CANCELADO')">
            Cancelar torneo
          </AppButton>
        </div>
      </div>
    </div>

    <template #footer>
      <AppButton variant="secondary" :disabled="loading" @click="emit('close')">Cerrar</AppButton>
    </template>
  </AppModal>

  <!-- Confirmación de transición / cancelación -->
  <AppConfirmModal
    v-model="showConfirmModal"
    :title="isCancelling ? '¿Cancelar torneo?' : '¿Confirmar transición?'"
    :message="confirmMessage"
    :icon="IconWarning"
    :tone="isCancelling ? 'rose' : 'blue'"
    :confirm-label="isCancelling ? 'Sí, cancelar' : 'Sí, avanzar'"
    cancel-label="No, volver"
    :loading="loading"
    :error="errorMsg"
    @confirm="executeTransitionConfirmed"
  />
</template>
