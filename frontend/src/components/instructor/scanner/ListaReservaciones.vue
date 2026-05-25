<script setup>
import { useScannerStore } from '@/stores/profiles/scannerStore'

const store = useScannerStore()
</script>

<template>
  <div class="space-y-3">

    <p class="text-xs font-bold text-surface-500 uppercase tracking-widest">
      Reservaciones activas hoy
    </p>

    <div class="space-y-2">
      <div
        v-for="r in store.reservacionesHoy"
        :key="r.id_reserva"
        class="bg-white border border-surface-200 rounded-2xl p-4 shadow-sm"
      >
        <div class="flex items-start gap-3">
          <!-- Icono -->
          <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
            </svg>
          </div>

          <!-- Datos -->
          <div class="flex-1 min-w-0">
            <p class="font-bold text-surface-900 text-sm leading-tight truncate">{{ r.socio_nombre }}</p>
            <p class="text-xs text-surface-500 mt-0.5 truncate">{{ r.espacio }}</p>

            <div class="flex flex-wrap items-center gap-2 mt-2">
              <!-- Horario -->
              <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-surface-500 bg-surface-50 border border-surface-200 px-2 py-0.5 rounded-lg tabular-nums">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                {{ r.hora_inicio }} – {{ r.hora_fin }}
              </span>

              <!-- Acompañantes -->
              <span
                v-if="r.num_acompanantes > 0"
                class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-700 bg-primary-50 border border-primary-100 px-2 py-0.5 rounded-lg"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2m8-10a4 4 0 100-8 4 4 0 000 8zm6 4v2m4-2v2m-2-4a2 2 0 100-4 2 2 0 000 4z"/>
                </svg>
                +{{ r.num_acompanantes }} acompañante{{ r.num_acompanantes > 1 ? 's' : '' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Botón de acción: escanear acceso para esta reserva -->
        <button
          @click="store.seleccionarReserva(r.id_reserva)"
          class="mt-3 w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold transition-all active:scale-[0.98] focus:outline-none shadow-sm shadow-primary-600/20"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
          </svg>
          Registrar acceso
        </button>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-if="store.reservacionesHoy.length === 0" class="text-center py-10">
      <p class="text-sm font-semibold text-surface-400">No hay reservaciones pendientes</p>
    </div>

  </div>
</template>
