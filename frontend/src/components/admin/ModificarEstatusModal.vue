<script setup>
import { ref, watch } from 'vue'
import { useSpacesStore } from '@/stores/admin/spaces'
import { useAlerts } from '@/composables/useAlerts'
import AppModal from '@/components/ui/modal/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import StatusOptionGroup from '@/components/ui/status/StatusOptionGroup.vue'
import { IconLayers, IconWarning, IconLock, IconShield } from '@/components/icons'

const props = defineProps({
  show: Boolean,
  space: Object
})
const emit = defineEmits(['close', 'updated'])

const spacesStore = useSpacesStore()
const { toastSuccess } = useAlerts()

const STATUS_OPTIONS = [
  { value: 'ACTIVO', label: 'Activo', description: 'Disponible para reservas y clases programadas.', icon: IconShield, tone: 'emerald' },
  { value: 'MANTENIMIENTO', label: 'Mantenimiento', description: 'Fuera de servicio temporalmente. Permite editar su información.', icon: IconWarning, tone: 'amber' },
  { value: 'DESHABILITADO', label: 'Deshabilitado', description: 'Fuera de uso. No acepta reservas ni clases.', icon: IconLock, tone: 'rose' },
]
const STATUS_LABEL = { ACTIVO: 'Activo', MANTENIMIENTO: 'Mantenimiento', DESHABILITADO: 'Deshabilitado' }

const estatusSeleccionado = ref('')
const isLoading = ref(false)
const conflictos = ref(null)
const errorMensaje = ref('')

watch(() => props.show, (val) => {
  if (val && props.space) {
    estatusSeleccionado.value = props.space.estatus
    conflictos.value = null
    errorMensaje.value = ''
  }
})

const handleUpdate = async () => {
  if (estatusSeleccionado.value === props.space.estatus) {
    emit('close')
    return
  }

  isLoading.value = true
  conflictos.value = null
  errorMensaje.value = ''

  const res = await spacesStore.updateSpaceStatus(props.space.id_espacio, estatusSeleccionado.value)
  isLoading.value = false

  if (res.success) {
    toastSuccess(`${props.space.nombre_espacio} ahora está en ${STATUS_LABEL[estatusSeleccionado.value] || estatusSeleccionado.value}`)
    emit('updated', estatusSeleccionado.value)
    emit('close')
  } else if (res.conflictos) {
    conflictos.value = res.conflictos
  } else {
    errorMensaje.value = res.error || 'Ocurrió un error al cambiar el estatus.'
  }
}
</script>

<template>
  <AppModal
    :model-value="show"
    title="Modificar estatus"
    :subtitle="space?.nombre_espacio"
    :icon="IconLayers"
    :persistent="isLoading"
    @update:model-value="(v) => !v && emit('close')"
  >
    <div class="flex flex-col gap-4">
      <!-- Conflictos que impiden el cambio -->
      <div v-if="conflictos?.length" role="alert"
        class="rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-500/30">
        <p class="m-0 flex items-center gap-2 text-sm font-bold text-amber-800 dark:text-amber-200">
          <IconWarning class="h-4 w-4 shrink-0" />
          No se puede cambiar el estatus
        </p>
        <p class="m-0 mt-1 text-xs font-semibold text-amber-700 dark:text-amber-300">Resuelve primero estos conflictos:</p>
        <ul class="m-0 mt-2 list-disc space-y-1 pl-5 text-xs font-medium text-amber-800 dark:text-amber-200">
          <li v-for="(conflicto, idx) in conflictos" :key="idx">{{ conflicto }}</li>
        </ul>
      </div>

      <div v-if="errorMensaje" role="alert"
        class="flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" /><path d="M12 8v4M12 16h.01" /></svg>
        {{ errorMensaje }}
      </div>

      <StatusOptionGroup v-model="estatusSeleccionado" :options="STATUS_OPTIONS" :current="space?.estatus" label="Estatus del espacio" />
    </div>

    <template #footer>
      <AppButton variant="secondary" :disabled="isLoading" @click="emit('close')">Cancelar</AppButton>
      <AppButton :tone="estatusSeleccionado === 'DESHABILITADO' ? 'rose' : 'blue'" :loading="isLoading"
        :disabled="estatusSeleccionado === space?.estatus" @click="handleUpdate">
        Guardar estatus
      </AppButton>
    </template>
  </AppModal>
</template>
