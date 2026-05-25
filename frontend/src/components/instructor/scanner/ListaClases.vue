<script setup>
import { useScannerStore } from '@/stores/profiles/scannerStore'

const store = useScannerStore()

const ESTATUS_BADGE = {
    'EN CURSO':   { bg: 'bg-green-100',   text: 'text-green-700',   dot: 'bg-green-500' },
    'PROGRAMADA': { bg: 'bg-primary-50',  text: 'text-primary-700', dot: 'bg-primary-500' },
    'FINALIZADA': { bg: 'bg-surface-100', text: 'text-surface-500', dot: 'bg-surface-400' },
}

function badgeClase(estatus) {
    return ESTATUS_BADGE[estatus] ?? { bg: 'bg-surface-100', text: 'text-surface-500', dot: 'bg-surface-400' }
}

function pct(inscritos, maximo) {
    if (!maximo) return 0
    return Math.min(100, Math.round((inscritos / maximo) * 100))
}
</script>

<template>
  <div class="space-y-3">

    <p class="text-xs font-bold text-surface-500 uppercase tracking-widest">
      Selecciona la sesión
    </p>

    <div class="space-y-2">
      <button
        v-for="s in store.sesionesHoy"
        :key="s.id_sesion"
        @click="store.seleccionarSesion(s.id_sesion)"
        class="w-full text-left bg-white border rounded-2xl p-4 transition-all duration-150 focus:outline-none group hover:border-primary-400 hover:shadow-md hover:shadow-primary-600/5 active:scale-[0.98]"
        :class="store.sesionActivaId === s.id_sesion
          ? 'border-primary-400 ring-2 ring-primary-100 shadow-sm'
          : 'border-surface-200 shadow-sm'"
      >
        <div class="flex items-start justify-between gap-3">
          <!-- Izquierda: disciplina + espacio -->
          <div class="flex items-center gap-3 flex-1 min-w-0">
            <!-- Icono de actividad -->
            <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 group-hover:bg-primary-100 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div class="min-w-0">
              <p class="font-bold text-surface-900 text-sm leading-tight truncate">{{ s.disciplina }}</p>
              <p class="text-xs text-surface-500 mt-0.5 truncate flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ s.espacio }}
              </p>
            </div>
          </div>

          <!-- Derecha: badge estatus + horario -->
          <div class="flex flex-col items-end gap-1.5 shrink-0">
            <span
              class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
              :class="[badgeClase(s.estatus_sesion).bg, badgeClase(s.estatus_sesion).text]"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="badgeClase(s.estatus_sesion).dot" />
              {{ s.estatus_sesion }}
            </span>
            <span class="text-[11px] font-semibold text-surface-500 tabular-nums">
              {{ s.hora_inicio }} – {{ s.hora_fin }}
            </span>
          </div>
        </div>

        <!-- Barra de aforo -->
        <div class="mt-3 flex items-center gap-2">
          <div class="flex-1 h-1.5 bg-surface-100 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="pct(s.cantidad_inscritos, s.cupo_maximo) >= 90 ? 'bg-red-400' : 'bg-primary-400'"
              :style="{ width: pct(s.cantidad_inscritos, s.cupo_maximo) + '%' }"
            />
          </div>
          <span class="text-[11px] font-semibold text-surface-400 tabular-nums shrink-0">
            {{ s.cantidad_inscritos }}/{{ s.cupo_maximo }}
          </span>
        </div>
      </button>
    </div>

    <!-- Estado vacío (no debería ocurrir si MenuCategorias habilita bien) -->
    <div v-if="store.sesionesHoy.length === 0" class="text-center py-10">
      <p class="text-sm font-semibold text-surface-400">Sin sesiones activas hoy</p>
    </div>

  </div>
</template>
