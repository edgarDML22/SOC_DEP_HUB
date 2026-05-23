<script setup>
import { ref } from 'vue'
import { useActividadesStore } from '@/stores/actividadesStore'

const store = useActividadesStore()

const cancelandoId = ref(null)
const toast = ref({ show: false, ok: true, message: '' })

function showToast(ok, message) {
  toast.value = { show: true, ok, message }
  setTimeout(() => { toast.value.show = false }, 3500)
}

async function handleCancelar(inscripcion) {
  cancelandoId.value = inscripcion.id_inscripcion
  const result = await store.cancelarInscripcion(inscripcion.id_inscripcion)
  showToast(result.ok, result.message)
  cancelandoId.value = null
}

function formatFecha(f) {
  if (!f) return '—'
  const [y, m, d] = f.split('-')
  const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']
  return `${d} ${meses[parseInt(m) - 1]} ${y}`
}

const estatusBadge = {
  CONFIRMADA: { bg: 'bg-emerald-100', text: 'text-emerald-700', label: 'Confirmada' },
  PENDIENTE:  { bg: 'bg-amber-100',   text: 'text-amber-700',   label: 'Pendiente' },
  CANCELADA:  { bg: 'bg-surface-100', text: 'text-surface-500', label: 'Cancelada' },
  LISTA:      { bg: 'bg-blue-100',    text: 'text-blue-700',    label: 'Lista' },
  ESPERA:     { bg: 'bg-orange-100',  text: 'text-orange-700',  label: 'Lista de espera' },
}

function getBadge(estatus) {
  return estatusBadge[estatus] ?? { bg: 'bg-surface-100', text: 'text-surface-500', label: estatus }
}

const cancelables = ['CONFIRMADA', 'PENDIENTE', 'LISTA', 'ESPERA']
</script>

<template>
  <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8">
    <div class="max-w-7xl mx-auto">

      <!-- Toast -->
      <Transition name="slide-toast">
        <div
          v-if="toast.show"
          class="fixed top-5 right-5 z-50 flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl text-sm font-semibold"
          :class="toast.ok ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'"
        >
          <svg v-if="toast.ok" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <svg v-else class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
          {{ toast.message }}
        </div>
      </Transition>

      <!-- Loading -->
      <div v-if="store.loadingInscripciones" class="flex flex-col items-center py-20">
        <div class="w-10 h-10 rounded-full border-2 border-surface-200 border-t-primary-600 animate-spin mb-4" />
        <p class="text-surface-500 font-medium text-sm">Cargando tus inscripciones...</p>
      </div>

      <!-- Error -->
      <div v-else-if="store.errorInscripciones" class="bg-red-50 border border-red-200 rounded-2xl p-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <p class="text-sm font-medium text-red-700">{{ store.errorInscripciones }}</p>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="store.misInscripciones.length === 0"
        class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm"
      >
        <div class="w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center text-primary-400 mb-4">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
            <line x1="16" x2="16" y1="2" y2="6"/>
            <line x1="8" x2="8" y1="2" y2="6"/>
            <line x1="3" x2="21" y1="10" y2="10"/>
            <path d="m9 16 2 2 4-4"/>
          </svg>
        </div>
        <p class="text-surface-900 font-bold text-lg">Mis Inscripciones</p>
        <p class="text-surface-500 font-medium text-sm text-center max-w-xs mt-1">
          Aquí verás el historial de todas tus clases y actividades inscritas.
        </p>
      </div>

      <!-- Lista de inscripciones -->
      <div v-else class="space-y-3">
        <div
          v-for="inscripcion in store.misInscripciones"
          :key="inscripcion.id_inscripcion"
          class="bg-white rounded-2xl border border-surface-100 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden"
        >
          <div class="flex flex-col sm:flex-row sm:items-center gap-0">

            <!-- Franja lateral de color según estatus -->
            <div
              class="w-full sm:w-1.5 h-1.5 sm:h-auto sm:self-stretch rounded-t-2xl sm:rounded-t-none sm:rounded-l-2xl shrink-0"
              :class="{
                'bg-emerald-500': inscripcion.estatus_inscripcion === 'CONFIRMADA',
                'bg-amber-400':   inscripcion.estatus_inscripcion === 'PENDIENTE',
                'bg-surface-300': inscripcion.estatus_inscripcion === 'CANCELADA',
                'bg-blue-500':    inscripcion.estatus_inscripcion === 'LISTA',
                'bg-orange-400':  inscripcion.estatus_inscripcion === 'ESPERA',
              }"
            />

            <!-- Contenido -->
            <div class="flex flex-1 flex-col sm:flex-row sm:items-center gap-4 px-5 py-4">

              <!-- Info principal -->
              <div class="flex-1 min-w-0 space-y-1.5">
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="text-base font-bold text-surface-900 truncate">
                    {{ inscripcion.nombre_actividad }}
                  </h3>
                  <!-- Badge tipo -->
                  <span class="text-[10px] font-black uppercase tracking-wide px-2 py-0.5 rounded-full"
                    :class="inscripcion.requiere_inscripcion
                      ? 'bg-red-100 text-red-600'
                      : 'bg-emerald-100 text-emerald-700'">
                    {{ inscripcion.tipo_clase }}
                  </span>
                </div>

                <!-- Fecha + Hora -->
                <div class="flex items-center gap-4 text-surface-500 flex-wrap">
                  <span class="flex items-center gap-1.5 text-sm font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ formatFecha(inscripcion.fecha_sesion) }}
                  </span>
                  <span class="flex items-center gap-1.5 text-sm font-medium tabular-nums">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    {{ inscripcion.hora_inicio }} – {{ inscripcion.hora_fin }}
                  </span>
                </div>

                <!-- Instructor + Espacio -->
                <div class="flex items-center gap-4 text-surface-400 flex-wrap">
                  <span v-if="inscripcion.instructor" class="flex items-center gap-1.5 text-xs font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ inscripcion.instructor }}
                  </span>
                  <span v-if="inscripcion.espacio" class="flex items-center gap-1.5 text-xs font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    </svg>
                    {{ inscripcion.espacio }}
                  </span>
                </div>
              </div>

              <!-- Badge de estatus + acción -->
              <div class="flex sm:flex-col items-center sm:items-end gap-3 shrink-0">
                <!-- Badge estatus -->
                <span
                  class="text-xs font-bold px-3 py-1 rounded-full"
                  :class="[getBadge(inscripcion.estatus_inscripcion).bg, getBadge(inscripcion.estatus_inscripcion).text]"
                >
                  {{ getBadge(inscripcion.estatus_inscripcion).label }}
                </span>

                <!-- Botón cancelar (solo si es cancelable) -->
                <button
                  v-if="cancelables.includes(inscripcion.estatus_inscripcion)"
                  :id="`btn-cancelar-inscripcion-${inscripcion.id_inscripcion}`"
                  @click="handleCancelar(inscripcion)"
                  :disabled="cancelandoId === inscripcion.id_inscripcion"
                  class="text-xs font-semibold text-surface-400 hover:text-red-500 transition-colors flex items-center gap-1 disabled:opacity-50"
                >
                  <svg v-if="cancelandoId === inscripcion.id_inscripcion" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                  </svg>
                  <svg v-else class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  {{ cancelandoId === inscripcion.id_inscripcion ? 'Cancelando...' : 'Cancelar' }}
                </button>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.slide-toast-enter-active,
.slide-toast-leave-active {
  transition: all 0.3s ease;
}
.slide-toast-enter-from,
.slide-toast-leave-to {
  opacity: 0;
  transform: translateX(1.5rem);
}
</style>
