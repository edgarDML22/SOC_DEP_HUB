<script setup>
import { computed } from 'vue'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'

const store = useScannerStore()

// Ventana temporal: habilitar el botón si faltan <= 20 min para hora_inicio
// o si ya transcurrió (hora_inicio <= now <= hora_fin)
function dentroDeVentana(r) {
    const now = new Date()
    const [hI, mI] = (r.hora_inicio ?? '00:00').split(':').map(Number)
    const [hF, mF] = (r.hora_fin    ?? '23:59').split(':').map(Number)

    const inicioMs = hI * 60 + mI
    const finMs    = hF * 60 + mF
    const nowMs    = now.getHours() * 60 + now.getMinutes()

    const minutosParaInicio = inicioMs - nowMs

    // Dentro de ventana: ya empezó o faltan ≤ 20 minutos
    return minutosParaInicio <= 20 && nowMs <= finMs
}

function puedeRegistrar(r) {
    return r.estatus_operativo === 'ACTIVA' && dentroDeVentana(r)
}

function tooltipBloqueado(r) {
    if (r.estatus_operativo !== 'ACTIVA') return 'Reservación no activa'
    const [hI, mI] = (r.hora_inicio ?? '00:00').split(':').map(Number)
    const nowMs    = new Date().getHours() * 60 + new Date().getMinutes()
    const inicioMs = hI * 60 + mI
    const minRestantes = inicioMs - nowMs
    if (minRestantes > 20) return `Disponible en ${minRestantes - 20} min`
    return 'Fuera del horario'
}

const reservacionesConVentana = computed(() =>
    store.reservacionesHoy.map(r => ({
        ...r,
        _puedeRegistrar: puedeRegistrar(r),
        _tooltip: puedeRegistrar(r) ? '' : tooltipBloqueado(r),
    }))
)
</script>

<template>
  <div class="space-y-3">

    <p class="text-sm font-semibold text-slate-700 mb-3">Reservaciones activas hoy</p>

    <div class="space-y-2.5">
      <div
        v-for="r in reservacionesConVentana"
        :key="r.id_reserva"
        class="relative bg-white border border-surface-200 rounded-2xl shadow-sm overflow-hidden"
      >
        <!-- Barra lateral siempre azul -->
        <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-2xl bg-primary-600" />

        <div class="pl-5 pr-4 pt-4 pb-3">
          <div class="flex items-start gap-3">

            <!-- Ícono dinámico de disciplina -->
            <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
              <DisciplineIcon :name="r.disciplina" class="w-5 h-5" />
            </div>

            <!-- Datos principales -->
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-surface-900 text-sm leading-tight">{{ r.socio_nombre }}</p>
              <p class="text-xs font-medium text-surface-500 mt-0.5 truncate">{{ r.espacio }}</p>

              <div class="flex flex-wrap items-center gap-2 mt-2.5">
                <!-- Horario en azul institucional -->
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-700 bg-primary-50 border border-primary-100 px-2.5 py-1 rounded-lg tabular-nums">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                  </svg>
                  {{ r.hora_inicio }} – {{ r.hora_fin }}
                </span>

                <!-- Disciplina si existe y no tiene DisciplineIcon reconocido -->
                <span v-if="r.disciplina && r.disciplina !== '—'"
                  class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-700 bg-primary-50 border border-primary-100 px-2.5 py-1 rounded-lg"
                >
                  {{ r.disciplina }}
                </span>

                <!-- Acompañantes -->
                <span
                  v-if="r.num_acompanantes > 0"
                  class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-700 bg-primary-50 border border-primary-100 px-2.5 py-1 rounded-lg"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m8-10a4 4 0 100-8 4 4 0 000 8zm6 4v2m4-2v2m-2-4a2 2 0 100-4 2 2 0 000 4z"/>
                  </svg>
                  +{{ r.num_acompanantes }} acompañante{{ r.num_acompanantes > 1 ? 's' : '' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Botón de acción con validación temporal -->
          <div class="mt-3">
            <button
              @click="r._puedeRegistrar && store.seleccionarReserva(r.id_reserva)"
              :disabled="!r._puedeRegistrar"
              :title="r._tooltip"
              class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold transition-all focus:outline-none"
              :class="r._puedeRegistrar
                ? 'bg-primary-600 hover:bg-primary-700 text-white shadow-sm shadow-primary-600/20 active:scale-[0.98]'
                : 'bg-surface-100 text-surface-300 cursor-not-allowed'"
            >
              <template v-if="r._puedeRegistrar">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
                Registrar acceso
              </template>
              <template v-else>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ r._tooltip }}
              </template>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-if="store.reservacionesHoy.length === 0" class="text-center py-10">
      <p class="text-sm font-semibold text-surface-400">No hay reservaciones pendientes</p>
    </div>

  </div>
</template>
