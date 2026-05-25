<script setup>
import { computed } from 'vue'
import { useScannerStore } from '@/stores/profiles/scannerStore'

const store = useScannerStore()

const LABEL_CATEGORIA = {
    CLASES:        'Mis Clases',
    RESERVACIONES: 'Reservaciones',
    TORNEO:        'Encuentros Torneo',
}
const labelCategoria = computed(() => LABEL_CATEGORIA[store.categoriaActiva] ?? '')

// Contexto de lo que se seleccionó en la lista previa
const contexto = computed(() => {
    if (store.esCategoriaClases) {
        const s = store.sesionesHoy.find(x => x.id_sesion === store.sesionActivaId)
        return s ? `${s.disciplina} · ${s.hora_inicio}–${s.hora_fin}` : ''
    }
    if (store.esCategoriaReservas) {
        const r = store.reservaActiva
        return r ? `${r.socio_nombre} · ${r.hora_inicio}–${r.hora_fin}` : ''
    }
    if (store.esCategoriaTorneo) {
        const e = store.encuentroActivo
        return e ? `${e.competidor_1} vs ${e.competidor_2}` : ''
    }
    return ''
})
</script>

<template>
  <div class="space-y-6">

    <!-- Chip de contexto: qué sesión/reserva/encuentro está seleccionado -->
    <div
      v-if="contexto"
      class="flex items-center gap-2 px-4 py-3 bg-primary-50 border border-primary-200 rounded-2xl"
    >
      <div class="w-2 h-2 rounded-full bg-primary-500 shrink-0 animate-pulse" />
      <div class="min-w-0">
        <p class="text-[10px] font-bold text-primary-500 uppercase tracking-widest">{{ labelCategoria }}</p>
        <p class="text-sm font-bold text-primary-800 truncate leading-tight mt-0.5">{{ contexto }}</p>
      </div>
    </div>

    <!-- Botones de método -->
    <div>
      <p class="text-xs font-bold text-surface-500 uppercase tracking-widest mb-3">
        Método de escaneo
      </p>

      <div class="grid grid-cols-2 gap-3">
        <!-- Cámara -->
        <button
          @click="store.seleccionarMetodo('CAMARA')"
          class="flex flex-col items-center gap-3 p-5 rounded-2xl border bg-white shadow-sm hover:border-primary-400 hover:shadow-md hover:shadow-primary-600/5 active:scale-[0.97] transition-all duration-150 focus:outline-none group cursor-pointer"
        >
          <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div class="text-center">
            <p class="font-bold text-sm text-surface-900">Cámara</p>
            <p class="text-[11px] text-surface-400 mt-0.5">Escaneo automático</p>
          </div>
        </button>

        <!-- Manual -->
        <button
          @click="store.seleccionarMetodo('MANUAL')"
          class="flex flex-col items-center gap-3 p-5 rounded-2xl border bg-white shadow-sm hover:border-primary-400 hover:shadow-md hover:shadow-primary-600/5 active:scale-[0.97] transition-all duration-150 focus:outline-none group cursor-pointer"
        >
          <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
          <div class="text-center">
            <p class="font-bold text-sm text-surface-900">Manual</p>
            <p class="text-[11px] text-surface-400 mt-0.5">Ingresa el código</p>
          </div>
        </button>
      </div>
    </div>

  </div>
</template>
