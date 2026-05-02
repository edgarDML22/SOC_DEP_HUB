<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useAlerts } from '@/composables/useAlerts'
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'

const route = useRoute()
const router = useRouter()
const { toastSuccess, toastError } = useAlerts()

const torneo = ref({
  nombre_torneo: route.query.nombre_torneo ?? '',
  fecha_inicio: route.query.fecha_inicio ?? '',
  categoria: route.query.categoria ?? '',
  disciplina: route.query.disciplina ?? '',
  estado: route.query.estado ?? 'EN_PLANIFICACION',
  tipo_acceso: route.query.tipo_acceso ?? '',
  formato_competencia: route.query.formato_competencia ?? '',
  cupo_maximo: route.query.cupo_maximo ?? '',
  descripcion: route.query.descripcion ?? ''
})

const confirming = ref(false)
const formError = ref('')
const showConfirmModal = ref(false)

const estadoLabel = computed(() => {
  const map = {
    EN_PLANIFICACION: 'En planificación',
    PROGRAMADO: 'Programado',
    EN_CURSO: 'En curso',
    FINALIZADO: 'Finalizado',
    CANCELADO: 'Cancelado'
  }
  return map[torneo.value.estado] ?? torneo.value.estado
})

const estadoClasses = computed(() => {
  const map = {
    EN_PLANIFICACION: 'bg-slate-100 text-slate-600',
    PROGRAMADO: 'bg-blue-100 text-blue-700',
    EN_CURSO: 'bg-amber-100 text-amber-700',
    FINALIZADO: 'bg-purple-100 text-purple-700',
    CANCELADO: 'bg-red-100 text-red-600'
  }
  return map[torneo.value.estado] ?? 'bg-slate-100 text-slate-600'
})

const accesoLabel = computed(() => {
  return torneo.value.tipo_acceso === 'INTERNO' ? 'Interno' : 'Abierto'
})

const formatoLabel = computed(() => {
  return torneo.value.formato_competencia === 'ELIMINACION_DIRECTA'
    ? 'Eliminación directa'
    : 'Fase de grupos'
})

const formatFecha = (f) => {
  if (!f) return '—'
  return new Date(f + 'T12:00:00').toLocaleDateString('es-MX', {
    day: 'numeric', month: 'long', year: 'numeric'
  })
}

const canConfirm = computed(() =>
  torneo.value.estado === 'EN_PLANIFICACION'
)

const confirmarTorneo = async () => {
  formError.value = ''
  confirming.value = true

  try {
    await api.post('torneos/update-status', {
      nombre_torneo: torneo.value.nombre_torneo,
      fecha_inicio: torneo.value.fecha_inicio,
      nombre_categoria: torneo.value.categoria,
      nombre_disciplina: torneo.value.disciplina
    })

    toastSuccess('Torneo confirmado correctamente')
    setTimeout(() => router.push('/admin/tournaments'), 1200)

  } catch (err) {
    formError.value = err.response?.data?.message || 'Error al confirmar el torneo.'
    toastError('No se pudo confirmar el torneo')
  } finally {
    confirming.value = false
    showConfirmModal.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <div class="max-w-2xl mx-auto px-4 py-8">

      <AdminPageHeader
        title="Detalle del Torneo"
        subtitle="Revisa la información antes de confirmar"
        back-route="/admin/tournaments"
      >
        <button
          v-if="canConfirm"
          @click="showConfirmModal = true"
          class="flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition"
        >
          <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          Confirmar torneo
        </button>
      </AdminPageHeader>

      <!-- Error banner -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div
          v-if="formError"
          class="mt-4 flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3"
        >
          <svg class="mt-0.5 size-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
          </svg>
          <p class="text-sm text-red-700">{{ formError }}</p>
        </div>
      </Transition>

      <!-- Detail card -->
      <div class="mt-6 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

        <!-- Status accent bar -->
        <div
          class="h-1"
          :class="{
            'bg-slate-300': torneo.estado === 'EN_PLANIFICACION',
            'bg-blue-500': torneo.estado === 'PROGRAMADO',
            'bg-amber-500': torneo.estado === 'EN_CURSO',
            'bg-purple-500': torneo.estado === 'FINALIZADO',
            'bg-red-400': torneo.estado === 'CANCELADO'
          }"
        ></div>

        <!-- Hero section -->
        <div class="px-6 pt-6 pb-4 flex items-start justify-between gap-4">
          <div class="flex items-start gap-4">
            <!-- Icon -->
            <div
              class="flex size-14 shrink-0 items-center justify-center rounded-2xl"
              :class="{
                'bg-slate-100': torneo.estado === 'EN_PLANIFICACION',
                'bg-blue-100': torneo.estado === 'PROGRAMADO',
                'bg-amber-100': torneo.estado === 'EN_CURSO',
                'bg-purple-100': torneo.estado === 'FINALIZADO',
                'bg-red-50': torneo.estado === 'CANCELADO'
              }"
            >
              <svg class="size-7" :class="{
                'text-slate-500': torneo.estado === 'EN_PLANIFICACION',
                'text-blue-600': torneo.estado === 'PROGRAMADO',
                'text-amber-600': torneo.estado === 'EN_CURSO',
                'text-purple-600': torneo.estado === 'FINALIZADO',
                'text-red-500': torneo.estado === 'CANCELADO'
              }" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
              </svg>
            </div>
            <div>
              <h2 class="text-xl font-bold text-slate-900">{{ torneo.nombre_torneo || 'Sin nombre' }}</h2>
              <p class="mt-0.5 text-sm text-slate-500">{{ torneo.disciplina }} · {{ torneo.categoria }}</p>
            </div>
          </div>
          <!-- Status badge -->
          <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold" :class="estadoClasses">
            {{ estadoLabel }}
          </span>
        </div>

        <div class="h-px bg-slate-100 mx-6" />

        <!-- Metadata grid -->
        <div class="grid grid-cols-2 gap-px bg-slate-100 mx-6 my-4 rounded-xl overflow-hidden">

          <div class="bg-white px-4 py-3">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Fecha inicio</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">{{ formatFecha(torneo.fecha_inicio) }}</p>
          </div>

          <div class="bg-white px-4 py-3">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Tipo de acceso</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">{{ accesoLabel || '—' }}</p>
          </div>

          <div class="bg-white px-4 py-3">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Formato</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">{{ formatoLabel || '—' }}</p>
          </div>

          <div class="bg-white px-4 py-3">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Cupo máximo</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">
              {{ torneo.cupo_maximo ? `${torneo.cupo_maximo} participantes` : '—' }}
            </p>
          </div>

        </div>

        <!-- Description -->
        <div v-if="torneo.descripcion" class="px-6 pb-6">
          <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Descripción</p>
          <p class="text-sm text-slate-600 leading-relaxed">{{ torneo.descripcion }}</p>
        </div>

        <!-- Confirm prompt when in planning -->
        <div
          v-if="canConfirm"
          class="mx-6 mb-6 rounded-xl bg-blue-50 border border-blue-100 px-4 py-3 flex items-center gap-3"
        >
          <svg class="size-5 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
          </svg>
          <p class="text-sm text-blue-700">
            Este torneo está pendiente de confirmación. Al confirmar pasará a estado <strong>Programado</strong>.
          </p>
        </div>

      </div>

    </div>
  </div>

  <!-- Confirm modal -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showConfirmModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
        @mousedown.self="showConfirmModal = false"
      >
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-2"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 translate-y-2"
        >
          <div v-if="showConfirmModal" class="w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">

            <!-- Accent -->
            <div class="h-1 bg-linear-to-r from-emerald-500 to-teal-400"></div>

            <div class="p-6">
              <!-- Icon -->
              <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-emerald-100">
                <svg class="size-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
              </div>

              <h3 class="text-center text-lg font-bold text-slate-900">Confirmar torneo</h3>
              <p class="mt-2 text-center text-sm text-slate-500">
                ¿Estás seguro de que deseas confirmar <strong>{{ torneo.nombre_torneo }}</strong>?
                El estado cambiará a <span class="font-semibold text-blue-600">Programado</span>.
              </p>

              <div class="mt-6 flex gap-3">
                <button
                  @click="showConfirmModal = false"
                  class="flex-1 rounded-xl border border-slate-200 bg-white py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 transition"
                >
                  Cancelar
                </button>
                <button
                  @click="confirmarTorneo"
                  :disabled="confirming"
                  class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-emerald-600 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60 transition"
                >
                  <svg v-if="confirming" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  {{ confirming ? 'Confirmando...' : 'Sí, confirmar' }}
                </button>
              </div>
            </div>

          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
