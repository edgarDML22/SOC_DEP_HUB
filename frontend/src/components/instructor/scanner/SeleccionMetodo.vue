<script setup>
import { computed } from 'vue'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'

const store = useScannerStore()

const LABEL_CATEGORIA = {
    CLASES:        'Mis Clases',
    RESERVACIONES: 'Reservaciones',
    TORNEO:        'Encuentros Torneo',
}
const labelCategoria = computed(() => LABEL_CATEGORIA[store.categoriaActiva] ?? '')

// Nombre de disciplina para el ícono dinámico
const disciplinaNombre = computed(() => {
    if (store.esCategoriaClases) {
        return store.sesionesHoy.find(x => x.id_sesion === store.sesionActivaId)?.disciplina ?? ''
    }
    if (store.esCategoriaReservas) {
        return store.reservaActiva?.disciplina ?? ''
    }
    if (store.esCategoriaTorneo) {
        return store.encuentroActivo?.disciplina ?? ''
    }
    return ''
})

// Línea principal del banner (disciplina · horario)
const contextoLinea1 = computed(() => {
    if (store.esCategoriaClases) {
        const s = store.sesionesHoy.find(x => x.id_sesion === store.sesionActivaId)
        return s?.disciplina ?? ''
    }
    if (store.esCategoriaReservas) {
        const r = store.reservaActiva
        return r?.socio_nombre ?? ''
    }
    if (store.esCategoriaTorneo) {
        const e = store.encuentroActivo
        return e ? `${e.competidor_1} vs ${e.competidor_2}` : ''
    }
    return ''
})

// Línea secundaria (espacio · horario)
const contextoLinea2 = computed(() => {
    if (store.esCategoriaClases) {
        const s = store.sesionesHoy.find(x => x.id_sesion === store.sesionActivaId)
        return s ? `${s.espacio} · ${s.hora_inicio}–${s.hora_fin}` : ''
    }
    if (store.esCategoriaReservas) {
        const r = store.reservaActiva
        return r ? `${r.espacio} · ${r.hora_inicio}–${r.hora_fin}` : ''
    }
    if (store.esCategoriaTorneo) {
        const e = store.encuentroActivo
        return e ? `${e.espacio ?? ''} · ${e.hora_inicio ?? ''}` : ''
    }
    return ''
})
</script>

<template>
  <div class="space-y-6">

    <!-- ── Banner premium de sesión/reserva seleccionada ─────────────────── -->
    <div
      v-if="contextoLinea1"
      class="flex items-center gap-3.5 px-4 py-3.5 bg-linear-to-r from-primary-600 to-primary-700 rounded-2xl shadow-md shadow-primary-600/20"
    >
      <!-- Ícono dinámico de disciplina -->
      <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
        <DisciplineIcon :name="disciplinaNombre" class="w-6 h-6 text-white" />
      </div>

      <!-- Texto -->
      <div class="min-w-0 flex-1">
        <p class="text-[10px] font-bold text-primary-200 uppercase tracking-widest leading-none mb-0.5">
          {{ labelCategoria }}
        </p>
        <p class="text-sm font-bold text-white truncate leading-tight">{{ contextoLinea1 }}</p>
        <p v-if="contextoLinea2" class="text-xs text-primary-200 truncate leading-tight mt-0.5">
          {{ contextoLinea2 }}
        </p>
      </div>

      <!-- Indicador activo -->
      <div class="w-2 h-2 rounded-full bg-white/80 animate-pulse shrink-0" />
    </div>

    <!-- ── Tarjetas de método ─────────────────────────────────────────────── -->
    <div>
      <p class="text-sm font-semibold text-slate-900 mb-3">Método de Ingreso</p>

      <div class="grid grid-cols-2 gap-3">

        <!-- Cámara -->
        <button
          @click="store.seleccionarMetodo('CAMARA')"
          class="flex flex-col items-center gap-3 p-5 rounded-2xl bg-white shadow-md shadow-slate-200/80 hover:shadow-lg hover:shadow-primary-600/10 border border-slate-200 hover:border-primary-300 active:scale-[0.97] transition-all duration-200 focus:outline-none group cursor-pointer"
        >
          <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div class="text-center">
            <p class="font-bold text-sm text-slate-800">Cámara</p>
            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Escaneo automático</p>
          </div>
        </button>

        <!-- Manual -->
        <button
          @click="store.seleccionarMetodo('MANUAL')"
          class="flex flex-col items-center gap-3 p-5 rounded-2xl bg-white shadow-md shadow-slate-200/80 hover:shadow-lg hover:shadow-primary-600/10 border border-slate-200 hover:border-primary-300 active:scale-[0.97] transition-all duration-200 focus:outline-none group cursor-pointer"
        >
          <div class="w-12 h-12 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
          <div class="text-center">
            <p class="font-bold text-sm text-slate-800">Manual</p>
            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Ingresa el código</p>
          </div>
        </button>

      </div>
    </div>

  </div>
</template>
