<script setup>
import { ref, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useDisciplinesStore } from '@/stores/admin/disciplines'
import { useAlerts } from '@/composables/useAlerts'

import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'

const props = defineProps({
    modelValue: Boolean,
    discipline: Object
})
const emit = defineEmits(['update:modelValue', 'status-updated'])

const disciplinesStore = useDisciplinesStore()
const { toastInfo } = useAlerts()

const isSaving = ref(false)
const conflicts = ref(null)

const nextStatus = computed(() => {
    return props.discipline?.estatus === 'ACTIVO' ? 'PAUSA' : 'ACTIVO'
})

const closeModal = () => {
    emit('update:modelValue', false)
    conflicts.value = null
}

const handleConfirm = async () => {
    isSaving.value = true
    conflicts.value = null
    
    const statusToSend = nextStatus.value === 'PAUSA' ? 'PAUSA' : 'ACTIVO'

    const res = await disciplinesStore.changeDisciplineStatus(props.discipline.id_disciplina, statusToSend)
    isSaving.value = false

    if (res.success) {
        toastInfo('Estatus actualizado', `La disciplina ahora está en ${statusToSend === 'PAUSA' ? 'Pausa' : 'Activo'}.`, 'success')
        emit('status-updated')
        closeModal()
    } else if (res.conflictos) {
        conflicts.value = res.conflictos
    } else {
        toastInfo('Error', res.error || 'No se pudo cambiar el estatus.', 'error')
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
    <Teleport to="body">
        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="modelValue"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
                @click.self="closeModal">
                <Transition enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0">
                    <div v-if="modelValue"
                        class="bg-white w-full max-w-md rounded-4xl shadow-2xl p-8 text-center overflow-hidden">
                        
                        <!-- Si hay conflictos -->
                        <div v-if="conflicts" class="text-left space-y-4">
                            <div class="flex items-center gap-3 mb-4 text-red-600">
                                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-surface-900">No se puede pausar</h3>
                                    <p class="text-xs text-surface-500">Existen dependencias activas</p>
                                </div>
                            </div>
                            
                            <div class="bg-surface-50 rounded-2xl p-4 border border-surface-200 text-sm max-h-[40vh] overflow-y-auto">
                                <ul class="space-y-3">
                                    <li v-for="conf in formattedConflicts" :key="conf.title">
                                        <strong class="text-surface-700 block mb-1 font-bold">{{ conf.title }}</strong>
                                        <ul class="list-disc pl-5 text-surface-500 text-xs space-y-1">
                                            <li v-for="item in conf.items" :key="item">{{ item }}</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>

                            <div class="pt-4 flex justify-end">
                                <CancelButton label="Entendido" @click="closeModal" class="w-full" />
                            </div>
                        </div>

                        <!-- Estado Normal Confirmación -->
                        <div v-else>
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5"
                                :class="nextStatus === 'PAUSA' ? 'bg-amber-50 text-amber-500' : 'bg-green-50 text-green-500'">
                                <svg v-if="nextStatus === 'PAUSA'" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 9v4M12 17h.01" />
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                </svg>
                                <svg v-else class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            
                            <h3 class="text-xl font-black text-surface-900 mb-2 leading-tight">¿Cambiar estatus?</h3>
                            <p class="text-sm text-surface-500 mb-6 leading-relaxed font-medium">
                                Estás a punto de cambiar la disciplina 
                                <span class="font-black text-surface-800">{{ discipline?.nombre_disciplina }}</span> 
                                de <BadgeStatus :status="discipline?.estatus" size="sm" class="inline-flex align-middle mx-1 shadow-xs" /> 
                                a <BadgeStatus :status="nextStatus" size="sm" class="inline-flex align-middle mx-1 shadow-xs" />.
                            </p>

                            <div class="flex gap-3">
                                <CancelButton @click="closeModal" class="flex-1" />
                                <ConfirmButton
                                    :label="nextStatus === 'PAUSA' ? 'Pausar' : 'Activar'"
                                    :loading="isSaving"
                                    @click="handleConfirm"
                                    class="flex-1"
                                    :class="nextStatus === 'PAUSA' ? 'bg-amber-500! hover:bg-amber-600!' : 'bg-green-600! hover:bg-green-700!'"
                                />
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
