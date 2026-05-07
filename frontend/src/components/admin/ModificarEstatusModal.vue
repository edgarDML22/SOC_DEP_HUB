<script setup>
import { ref, watch } from 'vue'
import { useSpacesStore } from '@/stores/admin/spaces'
import { useAlerts } from '@/composables/useAlerts'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'

const props = defineProps({
  show: Boolean,
  space: Object
})
const emit = defineEmits(['close', 'updated'])

const spacesStore = useSpacesStore()
const { toastInfo } = useAlerts()

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
    toastInfo('Estatus actualizado', `El espacio ahora está ${estatusSeleccionado.value}.`, 'success')
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
  <Teleport to="body">
    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="emit('close')">
        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 scale-95 translate-y-4" enter-to-class="opacity-100 scale-100 translate-y-0">
          <div v-if="show" class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h3 class="text-lg font-black text-slate-900">Modificar Estatus</h3>
                <p class="text-xs text-slate-500 font-medium mt-0.5">{{ space?.nombre_espacio }}</p>
              </div>
              <button @click="emit('close')" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
              </button>
            </div>

            <div class="p-6 space-y-6">
              <div v-if="errorMensaje" class="p-3 bg-red-50 text-red-700 text-sm font-semibold rounded-xl border border-red-200">
                {{ errorMensaje }}
              </div>

              <div v-if="conflictos && conflictos.length > 0" class="p-4 bg-amber-50 rounded-2xl border border-amber-200">
                <div class="flex items-center gap-2 text-amber-800 mb-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                  <span class="font-bold text-sm">No se puede cambiar el estatus</span>
                </div>
                <p class="text-xs text-amber-600 mb-2 ml-7">Resuelve los siguientes conflictos primero:</p>
                <ul class="text-xs text-amber-700 space-y-1 ml-7 list-disc">
                  <li v-for="(conflicto, idx) in conflictos" :key="idx">{{ conflicto }}</li>
                </ul>
              </div>

              <div class="space-y-3">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Seleccionar Nuevo Estatus</label>
                <div class="grid grid-cols-1 gap-2">
                  <button @click="estatusSeleccionado = 'ACTIVO'" :class="estatusSeleccionado === 'ACTIVO' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 ring-2 ring-emerald-100' : 'bg-white border-slate-200 text-slate-600 hover:border-emerald-300 hover:bg-emerald-50/50'" class="w-full flex items-center justify-between px-4 py-3 rounded-xl border-2 text-sm font-bold transition-all text-left">
                    <span class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div>Activo</span>
                    <svg v-if="estatusSeleccionado === 'ACTIVO'" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                  </button>
                  <button @click="estatusSeleccionado = 'MANTENIMIENTO'" :class="estatusSeleccionado === 'MANTENIMIENTO' ? 'bg-amber-50 border-amber-500 text-amber-700 ring-2 ring-amber-100' : 'bg-white border-slate-200 text-slate-600 hover:border-amber-300 hover:bg-amber-50/50'" class="w-full flex items-center justify-between px-4 py-3 rounded-xl border-2 text-sm font-bold transition-all text-left">
                    <span class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>Mantenimiento</span>
                    <svg v-if="estatusSeleccionado === 'MANTENIMIENTO'" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                  </button>
                  <button @click="estatusSeleccionado = 'DESHABILITADO'" :class="estatusSeleccionado === 'DESHABILITADO' ? 'bg-red-50 border-red-500 text-red-700 ring-2 ring-red-100' : 'bg-white border-slate-200 text-slate-600 hover:border-red-300 hover:bg-red-50/50'" class="w-full flex items-center justify-between px-4 py-3 rounded-xl border-2 text-sm font-bold transition-all text-left">
                    <span class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>Deshabilitado</span>
                    <svg v-if="estatusSeleccionado === 'DESHABILITADO'" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                  </button>
                </div>
              </div>
            </div>

            <div class="p-5 border-t border-slate-100 flex gap-3 bg-slate-50">
              <CancelButton @click="emit('close')" class="flex-1" />
              <ConfirmButton label="Guardar Estatus" :loading="isLoading" @click="handleUpdate" class="flex-1" />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
