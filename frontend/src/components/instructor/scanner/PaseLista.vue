<script setup>
import { ref, computed } from 'vue'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import ContadorAforo from './ContadorAforo.vue'
import ModalConfirmarAsistencia from './ModalConfirmarAsistencia.vue'

const store = useScannerStore()
const mostrarModal = ref(false)

// ── Modo cerrado (requiere_inscripcion = true) ────────────────────────────────
const pendientes = computed(() =>
    store.listaInscritos.filter(i => i.estatus_asistencia !== 'PRESENTE')
)
const presentes = computed(() =>
    store.listaInscritos.filter(i => i.estatus_asistencia === 'PRESENTE')
)

// Ordenar: primero presentes, luego pendientes/falta
const listaOrdenada = computed(() => [
    ...presentes.value,
    ...pendientes.value,
])

// ── Modo abierto (requiere_inscripcion = false) ───────────────────────────────
const registradosAbiertos = computed(() =>
    store.resultados.filter(r => r.success)
)

// ── Contexto de sesión ────────────────────────────────────────────────────────
const sesion = computed(() => store.sesionActiva)

function seguirEscaneando() {
    store.irAtras()   // PASE_LISTA → ESCANER_ACTIVO
}
</script>

<template>
  <div class="space-y-4">

    <!-- ── Chip de sesión activa ──────────────────────────────────────────── -->
    <div
      v-if="sesion"
      class="flex items-center gap-3 px-4 py-3 bg-primary-50 border border-primary-100 rounded-2xl"
    >
      <div class="w-2 h-2 rounded-full bg-primary-500 shrink-0 animate-pulse" />
      <div class="min-w-0 flex-1">
        <p class="text-[10px] font-bold text-primary-500 uppercase tracking-widest">Pase de lista activo</p>
        <p class="text-sm font-bold text-primary-800 truncate leading-tight mt-0.5">
          {{ sesion.disciplina }} · {{ sesion.hora_inicio }}–{{ sesion.hora_fin }}
        </p>
      </div>
      <!-- Badge requiere / libre -->
      <span class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full"
        :class="store.esSesionCerrada
          ? 'bg-primary-100 text-primary-700'
          : 'bg-surface-100 text-surface-500'"
      >
        {{ store.esSesionCerrada ? 'Clase cerrada' : 'Clase abierta' }}
      </span>
    </div>

    <!-- ── Cargando lista (solo modo cerrado) ─────────────────────────────── -->
    <div v-if="store.listaLoading" class="space-y-2">
      <div v-for="i in 4" :key="i" class="h-14 rounded-2xl bg-surface-100 animate-pulse" />
    </div>

    <template v-else>

      <!-- ══════════════════════════════════════════════════════════════════ -->
      <!--  MODO CERRADO (requiere_inscripcion = true)                       -->
      <!-- ══════════════════════════════════════════════════════════════════ -->
      <template v-if="store.esSesionCerrada">

        <!-- Resumen de progreso -->
        <div class="flex items-center justify-between px-1">
          <p class="text-xs font-bold text-surface-500 uppercase tracking-widest">
            Lista de inscritos
          </p>
          <span class="text-xs font-bold tabular-nums"
            :class="presentes.length === store.listaInscritos.length && store.listaInscritos.length > 0
              ? 'text-green-600' : 'text-surface-500'"
          >
            {{ presentes.length }}/{{ store.listaInscritos.length }}
          </span>
        </div>

        <!-- Lista vacía -->
        <div v-if="store.listaInscritos.length === 0" class="text-center py-8">
          <p class="text-sm font-semibold text-surface-400">Sin inscritos en esta sesión</p>
        </div>

        <!-- Filas de inscritos -->
        <ul class="space-y-2">
          <li
            v-for="inscrito in listaOrdenada"
            :key="inscrito.id_inscripcion"
            class="flex items-center gap-3 px-4 py-3 rounded-2xl border bg-white transition-all duration-200"
            :class="{
              'border-green-100 bg-green-50/40':  inscrito.estatus_asistencia === 'PRESENTE',
              'border-red-100 bg-red-50/40':      inscrito.estatus_asistencia === 'FALTA',
              'border-surface-100':               !inscrito.estatus_asistencia,
            }"
          >
            <!-- Ícono de estado -->
            <div class="shrink-0 w-8 h-8 rounded-xl flex items-center justify-center"
              :class="{
                'bg-green-100': inscrito.estatus_asistencia === 'PRESENTE',
                'bg-red-100':   inscrito.estatus_asistencia === 'FALTA',
                'bg-surface-100': !inscrito.estatus_asistencia,
              }"
            >
              <!-- Check verde -->
              <svg v-if="inscrito.estatus_asistencia === 'PRESENTE'"
                xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <!-- X roja -->
              <svg v-else-if="inscrito.estatus_asistencia === 'FALTA'"
                xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <!-- Punto gris pendiente -->
              <span v-else class="w-2 h-2 rounded-full bg-surface-300" />
            </div>

            <!-- Nombre y código -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold text-surface-900 truncate leading-tight">{{ inscrito.nombre }}</p>
              <p class="text-[11px] font-mono font-semibold tracking-widest mt-0.5"
                :class="{
                  'text-green-600': inscrito.estatus_asistencia === 'PRESENTE',
                  'text-red-400':   inscrito.estatus_asistencia === 'FALTA',
                  'text-surface-400': !inscrito.estatus_asistencia,
                }"
              >{{ inscrito.codigo_qr ?? '—' }}</p>
            </div>

            <!-- Badge de estado -->
            <span class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full"
              :class="{
                'bg-green-100 text-green-700':   inscrito.estatus_asistencia === 'PRESENTE',
                'bg-red-100 text-red-600':        inscrito.estatus_asistencia === 'FALTA',
                'bg-surface-100 text-surface-500': !inscrito.estatus_asistencia,
              }"
            >
              {{
                inscrito.estatus_asistencia === 'PRESENTE' ? 'Asistió' :
                inscrito.estatus_asistencia === 'FALTA'    ? 'Falta'   : 'Pendiente'
              }}
            </span>
          </li>
        </ul>

      </template>

      <!-- ══════════════════════════════════════════════════════════════════ -->
      <!--  MODO ABIERTO (requiere_inscripcion = false)                      -->
      <!-- ══════════════════════════════════════════════════════════════════ -->
      <template v-else>

        <!-- Contador de aforo prominente -->
        <ContadorAforo
          :actual="registradosAbiertos.length"
          :maximo="sesion?.cupo_maximo ?? 0"
        />

        <!-- Lista dinámica de escaneados -->
        <div v-if="registradosAbiertos.length > 0">
          <p class="text-xs font-bold text-surface-500 uppercase tracking-widest mb-2 px-1">
            Registrados ({{ registradosAbiertos.length }})
          </p>
          <ul class="space-y-2 max-h-56 overflow-y-auto">
            <li
              v-for="(r, i) in registradosAbiertos"
              :key="i"
              class="flex items-center gap-3 px-4 py-3 rounded-2xl border border-green-100 bg-green-50/40"
            >
              <div class="w-8 h-8 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-surface-900 truncate">{{ r.data?.nombre ?? '—' }}</p>
                <p class="text-[11px] font-mono font-semibold tracking-widest text-green-600 mt-0.5">
                  {{ r.data?.codigo_qr ?? '—' }}
                </p>
              </div>
              <span class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700">
                Asistió
              </span>
            </li>
          </ul>
        </div>

        <div v-else class="text-center py-6">
          <p class="text-sm font-semibold text-surface-400">Aún no hay asistentes registrados</p>
        </div>

      </template>

    </template>

    <!-- ── Acciones ──────────────────────────────────────────────────────── -->
    <div class="pt-2 space-y-3">

      <!-- Botón seguir escaneando -->
      <button
        v-if="!store.aforoLleno && !store.listaConfirmada"
        @click="seguirEscaneando"
        class="w-full py-3.5 rounded-2xl font-bold text-sm border border-primary-200 bg-primary-50 text-primary-700 hover:bg-primary-100 active:scale-[0.98] transition-all duration-150 focus:outline-none"
      >
        Seguir escaneando
      </button>

      <!-- Botón Confirmar Asistencia -->
      <button
        v-if="!store.listaConfirmada"
        @click="mostrarModal = true"
        :disabled="store.loading"
        class="w-full py-4 rounded-2xl font-bold text-sm transition-all duration-150 focus:outline-none"
        :class="store.loading
          ? 'bg-surface-100 text-surface-300 cursor-not-allowed'
          : 'bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-600/20 active:scale-[0.98]'"
      >
        <span v-if="store.loading" class="flex items-center justify-center gap-2">
          <span class="w-4 h-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
          Confirmando…
        </span>
        <span v-else>Confirmar Asistencia</span>
      </button>

      <!-- Estado: ya confirmada -->
      <div
        v-if="store.listaConfirmada"
        class="flex items-center justify-center gap-2 py-3 rounded-2xl bg-green-50 border border-green-100"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        <p class="text-sm font-bold text-green-700">Lista confirmada</p>
      </div>
    </div>

    <!-- Modal de confirmación -->
    <ModalConfirmarAsistencia
      v-if="mostrarModal"
      :total-presentes="store.esSesionCerrada ? presentes.length : registradosAbiertos.length"
      :total-inscritos="store.esSesionCerrada ? store.listaInscritos.length : null"
      @cancelar="mostrarModal = false"
      @confirmar="mostrarModal = false; store.confirmarAsistencia()"
    />

  </div>
</template>
