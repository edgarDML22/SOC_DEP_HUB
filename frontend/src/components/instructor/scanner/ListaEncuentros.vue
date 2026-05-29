<script setup>
import { useScannerStore } from '@/stores/profiles/scannerStore'

const store = useScannerStore()

const ESTATUS_BADGE = {
    'PROGRAMADO':  { bg: 'bg-primary-50',  text: 'text-primary-700',  dot: 'bg-primary-500' },
    'EN CURSO':    { bg: 'bg-green-100',   text: 'text-green-700',    dot: 'bg-green-500' },
    'FINALIZADO':  { bg: 'bg-surface-100', text: 'text-surface-500',  dot: 'bg-surface-400' },
    'BYE':         { bg: 'bg-amber-50',    text: 'text-amber-700',    dot: 'bg-amber-400' },
}

function badge(estatus) {
    return ESTATUS_BADGE[estatus] ?? { bg: 'bg-surface-100', text: 'text-surface-500', dot: 'bg-surface-400' }
}

function faseLegible(fase) {
    if (!fase) return '—'
    return fase
        .replace(/_/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase())
}
</script>

<template>
  <div class="space-y-3">

    <p class="text-xs font-bold text-surface-500 uppercase tracking-widest">
      Tus encuentros de hoy
    </p>

    <div class="space-y-2">
      <div
        v-for="e in store.encuentrosHoy"
        :key="e.id_encuentro"
        class="bg-white border border-surface-200 rounded-2xl p-4 shadow-sm"
      >
        <!-- Cabecera: torneo + fase + estatus -->
        <div class="flex items-start justify-between gap-2 mb-3">
          <div class="flex items-center gap-2 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9H4.5a2.5 2.5 0 010-5H6m12 0h1.5a2.5 2.5 0 010 5H18M6 9v6a6 6 0 0012 0V9M6 9H3m15 0h3M12 21v-3m0 0a6 6 0 01-6-6M12 18a6 6 0 006-6" />
              </svg>
            </div>
            <div class="min-w-0">
              <p class="font-bold text-surface-900 text-xs leading-tight truncate">{{ e.torneo }}</p>
              <p class="text-[10px] text-primary-600 font-semibold mt-0.5">{{ faseLegible(e.fase_bracket) }}</p>
            </div>
          </div>
          <span
            class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0"
            :class="[badge(e.estatus_encuentro).bg, badge(e.estatus_encuentro).text]"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="badge(e.estatus_encuentro).dot" />
            {{ e.estatus_encuentro }}
          </span>
        </div>

        <!-- Competidores con "VS" central -->
        <div class="flex items-center gap-2 mb-3">
          <div class="flex-1 bg-surface-50 border border-surface-200 rounded-xl px-3 py-2 text-center">
            <p class="text-xs font-bold text-surface-900 leading-tight truncate">{{ e.competidor_1 }}</p>
          </div>
          <span class="text-[11px] font-black text-surface-400 shrink-0">VS</span>
          <div class="flex-1 bg-surface-50 border border-surface-200 rounded-xl px-3 py-2 text-center">
            <p class="text-xs font-bold text-surface-900 leading-tight truncate">{{ e.competidor_2 }}</p>
          </div>
        </div>

        <!-- Metadata: espacio + horario -->
        <div class="flex flex-wrap gap-2 mb-3">
          <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-surface-500 bg-surface-50 border border-surface-100 px-2 py-0.5 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ e.espacio }}
          </span>
          <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-surface-500 bg-surface-50 border border-surface-100 px-2 py-0.5 rounded-lg tabular-nums">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            {{ e.hora_inicio }}{{ e.hora_fin ? ` – ${e.hora_fin}` : '' }}
          </span>
        </div>

        <!-- Botón de acción -->
        <button
          @click="store.seleccionarEncuentro(e.id_encuentro)"
          class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold transition-all active:scale-[0.98] focus:outline-none shadow-sm shadow-primary-600/20"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
          </svg>
          Registrar acceso
        </button>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-if="store.encuentrosHoy.length === 0" class="text-center py-10">
      <p class="text-sm font-semibold text-surface-400">No tienes encuentros asignados hoy</p>
    </div>

  </div>
</template>
