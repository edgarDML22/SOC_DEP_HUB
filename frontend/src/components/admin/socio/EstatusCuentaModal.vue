<script setup>
import { ref, computed, watch } from 'vue'
import { useSocioStore } from '@/stores/admin/socioStore'
import { useAlerts } from '@/composables/useAlerts'

const props = defineProps({
  socio: { type: Object, default: null },
  modelValue: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const socioStore = useSocioStore()
const { toastInfo } = useAlerts()

const isSaving = ref(false)

const close = () => emit('update:modelValue', false)

// Flujo: ¿es suspensión o reactivación?
const isSuspendFlow = computed(() => {
  const est = props.socio?.estatus_cuenta
  return est === 'AL_CORRIENTE' || est === 'MOROSO'
})

const targetStatus = computed(() => isSuspendFlow.value ? 'SUSPENDIDO' : 'AL_CORRIENTE')

const AVATAR_GRADIENTS = [
  'from-primary-400 to-primary-600',
  'from-emerald-400 to-emerald-600',
  'from-purple-400 to-purple-600',
  'from-orange-400 to-orange-600',
  'from-rose-400 to-rose-600',
  'from-cyan-400 to-cyan-600',
]
const avatarGradient = (name = '') => {
  const idx = (name.charCodeAt(0) ?? 0) % AVATAR_GRADIENTS.length
  return AVATAR_GRADIENTS[idx]
}
const initials = (name = '') => {
  const parts = name.trim().split(' ').filter(Boolean)
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return (parts[0]?.[0] ?? '?').toUpperCase()
}

const save = async () => {
  if (!props.socio) return
  isSaving.value = true
  try {
    const res = await socioStore.updateEstatusCuenta(props.socio.id_socio, targetStatus.value)
    if (res.success) {
      toastInfo(
        isSuspendFlow.value ? 'Suspendido' : 'Reactivado',
        isSuspendFlow.value
          ? 'La cuenta del socio ha sido suspendida.'
          : 'La cuenta del socio ha sido reactivada.',
        'success'
      )
      close()
    } else {
      toastInfo('Error', res.error, 'error')
    }
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100" leave-to-class="opacity-0"
    >
      <div v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-surface-900/60 backdrop-blur-sm"
        @click.self="close"
      >
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          enter-to-class="opacity-100 scale-100 translate-y-0"
        >
          <div v-if="modelValue"
            class="bg-white w-full max-w-md rounded-4xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
          >
            <!-- ── Cabecera ── -->
            <div class="flex items-center justify-between px-7 py-5 bg-white border-b border-surface-100">
              <div class="flex items-center gap-3">
                <div
                  class="w-10 h-10 rounded-xl bg-linear-to-br flex items-center justify-center
                         text-white font-black text-sm shadow-sm shrink-0"
                  :class="avatarGradient(socio?.nombre_completo)"
                >
                  {{ initials(socio?.nombre_completo ?? '') }}
                </div>
                <div>
                  <h2 class="text-lg font-black text-surface-900 leading-tight">Estatus de Cuenta</h2>
                  <p class="text-xs font-extrabold text-surface-500 mt-0.5 uppercase tracking-widest truncate max-w-xs">
                    {{ socio?.nombre_completo }}
                    <span class="text-surface-300 font-bold normal-case tracking-normal"> · </span>
                    <span class="font-mono text-surface-400 normal-case tracking-normal">#{{ socio?.numero_accion }}</span>
                  </p>
                </div>
              </div>
              <button @click="close"
                class="w-9 h-9 rounded-xl bg-surface-100 hover:bg-surface-200
                       flex items-center justify-center text-surface-500 transition-colors shrink-0">
                <svg class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- ── Cuerpo ── -->
            <div class="overflow-y-auto bg-surface-50/40 p-6 flex flex-col gap-5">

              <!-- Estatus actual -->
              <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5">
                <p class="text-[10px] font-black uppercase tracking-widest text-surface-500 mb-3">Estatus Actual</p>
                <div class="flex items-center gap-3">
                  <div class="w-3 h-3 rounded-full shrink-0" :class="{
                    'bg-green-500': socio?.estatus_cuenta === 'AL_CORRIENTE',
                    'bg-amber-500': socio?.estatus_cuenta === 'MOROSO',
                    'bg-red-500': socio?.estatus_cuenta === 'SUSPENDIDO',
                  }"/>
                  <span class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest" :class="{
                    'bg-green-50 text-green-700 border border-green-200': socio?.estatus_cuenta === 'AL_CORRIENTE',
                    'bg-amber-50 text-amber-700 border border-amber-200': socio?.estatus_cuenta === 'MOROSO',
                    'bg-red-50 text-red-700 border border-red-200': socio?.estatus_cuenta === 'SUSPENDIDO',
                  }">
                    {{ socio?.estatus_cuenta?.replace(/_/g, ' ') ?? 'S/E' }}
                  </span>
                </div>
              </div>

              <!-- ═══ FLUJO SUSPENDER ═══ -->
              <div v-if="isSuspendFlow"
                class="rounded-2xl border-2 border-red-200 bg-red-50 p-5 flex flex-col gap-4">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center text-red-600 shrink-0">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                      <path d="M12 9v4M12 17h.01"/>
                      <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-black text-red-800 leading-tight">¿Suspender esta cuenta?</p>
                    <p class="text-xs font-semibold text-red-600 mt-1 leading-relaxed">
                      El socio perderá el acceso a todas las funcionalidades del club, incluyendo
                      reservas, ludoteca y beneficios de membresía.
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2 px-1">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-red-400 shrink-0">
                    <path d="M9 12l2 2 4-4"/>
                    <circle cx="12" cy="12" r="10"/>
                  </svg>
                  <span class="text-xs font-semibold text-red-700">
                    La cuenta pasará de
                    <span class="font-black">{{ socio?.estatus_cuenta?.replace(/_/g, ' ') }}</span>
                    a <span class="font-black">SUSPENDIDO</span>
                  </span>
                </div>
              </div>

              <!-- ═══ FLUJO REACTIVAR ═══ -->
              <div v-else
                class="rounded-2xl border-2 border-emerald-200 bg-emerald-50 p-5 flex flex-col gap-4">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                      <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-black text-emerald-800 leading-tight">¿Reactivar esta cuenta?</p>
                    <p class="text-xs font-semibold text-emerald-600 mt-1 leading-relaxed">
                      El socio recuperará el acceso completo a todas las funcionalidades del club,
                      incluyendo reservas, ludoteca y beneficios de membresía.
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2 px-1">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-emerald-400 shrink-0">
                    <path d="M9 12l2 2 4-4"/>
                    <circle cx="12" cy="12" r="10"/>
                  </svg>
                  <span class="text-xs font-semibold text-emerald-700">
                    La cuenta pasará de
                    <span class="font-black">SUSPENDIDO</span>
                    a <span class="font-black">AL CORRIENTE</span>
                  </span>
                </div>
              </div>

            </div>

            <!-- ── Pie ── -->
            <div class="flex items-center justify-end gap-3 px-7 py-4 border-t border-surface-100 bg-white">
              <button @click="close"
                class="px-5 py-2.5 rounded-xl border border-surface-200 bg-white
                       text-sm font-bold text-surface-700 hover:bg-surface-50 transition-colors">
                Cancelar
              </button>
              <button
                @click="save"
                :disabled="isSaving"
                class="px-6 py-2.5 rounded-xl text-white text-sm font-black
                       transition-colors shadow-sm
                       disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
                :class="isSuspendFlow
                  ? 'bg-red-600 hover:bg-red-700 active:bg-red-800'
                  : 'bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800'"
              >
                <svg v-if="isSaving" class="w-4 h-4 animate-spin"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                <template v-else>
                  <!-- Icono suspender -->
                  <svg v-if="isSuspendFlow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                  </svg>
                  <!-- Icono reactivar -->
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                  </svg>
                </template>
                {{ isSaving ? 'Procesando…' : (isSuspendFlow ? 'Suspender cuenta' : 'Reactivar cuenta') }}
              </button>
            </div>

          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
