<script setup>
import { ref, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useDisciplinesStore } from '@/stores/admin/disciplines'
import { useAlerts } from '@/composables/useAlerts'

import AppModal from '@/components/ui/modal/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import StatusOptionGroup from '@/components/ui/status/StatusOptionGroup.vue'
import { IconLayers, IconWarning, IconShield } from '@/components/icons'

const props = defineProps({
    modelValue: Boolean,
    discipline: Object
})
const emit = defineEmits(['update:modelValue', 'status-updated'])

const disciplinesStore = useDisciplinesStore()
const { toastInfo } = useAlerts()

const isSaving = ref(false)
const conflicts = ref(null)

const STATUS_OPTIONS = [
    { value: 'ACTIVO', label: 'Activo', description: 'Disponible para reservas, clases y torneos.', icon: IconShield, tone: 'emerald' },
    { value: 'PAUSA', label: 'En pausa', description: 'Detenida temporalmente. Requiere no tener actividad vinculada.', icon: IconWarning, tone: 'amber' },
]
const STATUS_LABEL = { ACTIVO: 'Activo', PAUSA: 'En pausa' }

const selectedStatus = ref('')
const errorMsg = ref('')

watch(() => props.modelValue, (open) => {
    if (open && props.discipline) {
        selectedStatus.value = props.discipline.estatus
        conflicts.value = null
        errorMsg.value = ''
    }
})

const closeModal = () => {
    emit('update:modelValue', false)
    conflicts.value = null
}

const handleConfirm = async () => {
    isSaving.value = true
    conflicts.value = null
    
    errorMsg.value = ''
    if (selectedStatus.value === props.discipline.estatus) {
        closeModal()
        return
    }
    const statusToSend = selectedStatus.value

    const res = await disciplinesStore.changeDisciplineStatus(props.discipline.id_disciplina, statusToSend)
    isSaving.value = false

    if (res.success) {
        toastInfo('Estatus actualizado', `${props.discipline.nombre_disciplina} ahora está en ${STATUS_LABEL[statusToSend]}.`, 'success')
        emit('status-updated')
        closeModal()
    } else if (res.conflictos) {
        conflicts.value = res.conflictos
    } else {
        errorMsg.value = res.error || 'No se pudo cambiar el estatus.'
    }
}

const formattedConflicts = computed(() => {
    if (!conflicts.value) return []
    const list = []
    const c = conflicts.value
    
    if (c.reservaciones_activas > 0) {
        list.push({
            title: 'Reservaciones Activas',
            items: [`Tiene ${c.reservaciones_activas} reservaciones activas vinculadas.`]
        })
    }
    if (c.sesiones_activas > 0) {
        list.push({
            title: 'Sesiones Activas',
            items: [`Tiene ${c.sesiones_activas} sesiones de clase activas actualmente.`]
        })
    }
    if (c.actividades_programadas > 0) {
        list.push({
            title: 'Actividades de Plantilla',
            items: [`Tiene ${c.actividades_programadas} actividades configuradas en la plantilla.`]
        })
    }
    if (c.torneos_activos && c.torneos_activos.length > 0) {
        list.push({
            title: 'Torneos Activos',
            items: c.torneos_activos.map(t => `Torneo: "${t}"`)
        })
    }
    return list
})
</script>

<template>
    <AppModal
        :model-value="modelValue"
        title="Cambiar estatus"
        :subtitle="discipline?.nombre_disciplina"
        :icon="IconLayers"
        :persistent="isSaving"
        @update:model-value="(v) => !v && closeModal()"
    >
        <div class="flex flex-col gap-4">
            <!-- Dependencias activas que impiden el cambio -->
            <div v-if="conflicts" role="alert"
                class="rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:ring-amber-500/30">
                <p class="m-0 flex items-center gap-2 text-sm font-bold text-amber-800 dark:text-amber-200">
                    <IconWarning class="h-4 w-4 shrink-0" />
                    No se puede pausar: existen dependencias activas
                </p>
                <ul class="m-0 mt-2 flex max-h-[30vh] list-none flex-col gap-2.5 overflow-y-auto p-0">
                    <li v-for="conf in formattedConflicts" :key="conf.title">
                        <strong class="block text-xs font-bold text-amber-900 dark:text-amber-100">{{ conf.title }}</strong>
                        <ul class="m-0 mt-0.5 list-disc space-y-0.5 pl-5 text-xs font-medium text-amber-800 dark:text-amber-200">
                            <li v-for="item in conf.items" :key="item">{{ item }}</li>
                        </ul>
                    </li>
                </ul>
            </div>

            <div v-if="errorMsg" role="alert"
                class="flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
                <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
                {{ errorMsg }}
            </div>

            <StatusOptionGroup v-model="selectedStatus" :options="STATUS_OPTIONS" :current="discipline?.estatus" label="Estatus de la disciplina" />
        </div>

        <template #footer>
            <AppButton variant="secondary" :disabled="isSaving" @click="closeModal">Cancelar</AppButton>
            <AppButton :tone="selectedStatus === 'PAUSA' ? 'amber' : 'blue'" :loading="isSaving"
                :disabled="selectedStatus === discipline?.estatus" @click="handleConfirm">
                Guardar estatus
            </AppButton>
        </template>
    </AppModal>
</template>
