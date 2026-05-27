<script setup>
import { computed } from 'vue'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'

const store = useScannerStore()

// Chips semánticos con colores institucionales
const ESTATUS_CHIP = {
    DISPONIBLE: { bg: 'bg-emerald-100', text: 'text-emerald-800', border: 'border border-emerald-300', dot: 'bg-emerald-600', label: 'Disponible' },
    EN_CURSO:   { bg: 'bg-amber-100',   text: 'text-amber-800',   border: 'border border-amber-300',   dot: 'bg-amber-600',   label: 'En curso'   },
    FINALIZADA: { bg: 'bg-blue-100',    text: 'text-blue-800',    border: 'border border-blue-300',    dot: 'bg-blue-600',    label: 'Finalizada' },
    CANCELADA:  { bg: 'bg-red-100',     text: 'text-red-800',     border: 'border border-red-300',     dot: 'bg-red-600',     label: 'Cancelada'  },
    LLENO:      { bg: 'bg-orange-100',  text: 'text-orange-800',  border: 'border border-orange-300',  dot: 'bg-orange-600',  label: 'Lleno'      },
}

function chip(estatus) {
    return ESTATUS_CHIP[estatus] ?? { bg: 'bg-surface-100', text: 'text-surface-600', border: 'border border-surface-300', dot: 'bg-surface-500', label: estatus ?? '—' }
}

function pct(inscritos, maximo) {
    if (!maximo) return 0
    return Math.min(100, Math.round((inscritos / maximo) * 100))
}

const sesionSeleccionada = computed(() =>
    store.sesionesHoy.find(s => s.id_sesion === store.sesionActivaId) ?? null
)

// Lista preliminar de inscritos visible al seleccionar sesión cerrada
const mostrarListaPreliminar = computed(() =>
    sesionSeleccionada.value?.requiere_inscripcion === true
    && store.listaInscritos.length > 0
)

async function seleccionar(sesion) {
    // Solo avanzar si la sesión está EN_CURSO
    if (sesion.estatus_sesion !== 'EN_CURSO') return
    await store.seleccionarSesion(sesion.id_sesion)
}
</script>

<template>
  <div class="space-y-3">

    <p class="text-sm font-semibold text-slate-700 mb-3">Selecciona la sesión</p>

    <div class="space-y-2.5">
      <div
        v-for="s in store.sesionesHoy"
        :key="s.id_sesion"
        class="relative bg-white rounded-2xl shadow-sm overflow-hidden transition-all duration-150"
        :class="[
          s.estatus_sesion === 'EN_CURSO'
            ? 'hover:shadow-md hover:shadow-primary-600/8 cursor-pointer'
            : 'cursor-not-allowed',
          store.sesionActivaId === s.id_sesion
            ? 'ring-2 ring-primary-300 shadow-md shadow-primary-600/10'
            : 'border border-surface-200',
        ]"
        @click="seleccionar(s)"
      >
        <!-- Barra lateral siempre azul -->
        <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-2xl bg-primary-600" />

        <div class="pl-5 pr-4 pt-4 pb-3">

          <div class="flex items-start justify-between gap-3">
            <!-- Ícono dinámico de disciplina + datos -->
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                <DisciplineIcon :name="s.disciplina" class="w-5 h-5" />
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

            <!-- Derecha: pill tipo + chip estatus + horario -->
            <div class="flex flex-col items-end gap-1.5 shrink-0">
              <div class="flex items-center gap-1.5">
                <!-- Pill Abierta / Cerrada -->
                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                  :class="s.requiere_inscripcion
                    ? 'bg-violet-100 text-violet-800 border border-violet-300'
                    : 'bg-emerald-100 text-emerald-800 border border-emerald-300'"
                >
                  {{ s.requiere_inscripcion ? 'Cerrada' : 'Abierta' }}
                </span>
                <!-- Chip de estatus -->
                <span
                  class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full"
                  :class="[chip(s.estatus_sesion).bg, chip(s.estatus_sesion).text, chip(s.estatus_sesion).border]"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="chip(s.estatus_sesion).dot" />
                  {{ chip(s.estatus_sesion).label }}
                </span>
              </div>
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

          <!-- Botón de acción: solo habilitado si EN_CURSO -->
          <button
            @click.stop="seleccionar(s)"
            :disabled="s.estatus_sesion !== 'EN_CURSO' || store.listaLoading"
            class="mt-3 w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold transition-all focus:outline-none"
            :class="s.estatus_sesion === 'EN_CURSO'
              ? 'bg-primary-600 hover:bg-primary-700 text-white shadow-sm shadow-primary-600/20 active:scale-[0.98]'
              : 'bg-surface-200 text-surface-500 cursor-not-allowed'"
          >
            <template v-if="s.estatus_sesion === 'EN_CURSO' && store.listaLoading && store.sesionActivaId === s.id_sesion">
              <span class="w-3.5 h-3.5 rounded-full border-2 border-white/30 border-t-white animate-spin" />
              Cargando lista…
            </template>
            <template v-else-if="s.estatus_sesion === 'EN_CURSO'">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
              Iniciar pase de lista
            </template>
            <template v-else>
              {{ s.estatus_sesion === 'DISPONIBLE' ? 'Aún no iniciada' : s.estatus_sesion === 'FINALIZADA' ? 'Finalizada' : 'No disponible' }}
            </template>
          </button>
        </div>
      </div>
    </div>

    <!-- Lista preliminar de inscritos (clases cerradas) -->
    <Transition
      enter-active-class="transition-all duration-300"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
    >
      <div v-if="mostrarListaPreliminar" class="mt-2 space-y-2">
        <p class="text-xs font-semibold text-slate-700 uppercase tracking-widest px-1">
          Lista preliminar · {{ store.listaInscritos.length }} inscrito{{ store.listaInscritos.length !== 1 ? 's' : '' }}
        </p>
        <ul class="space-y-1.5">
          <li
            v-for="inscrito in store.listaInscritos"
            :key="inscrito.id_inscripcion"
            class="flex items-center gap-3 px-4 py-2.5 rounded-xl border bg-white transition-all duration-150"
            :class="{
              'border-green-100 bg-green-50/40':  inscrito.estatus_asistencia === 'PRESENTE',
              'border-red-100 bg-red-50/40':      inscrito.estatus_asistencia === 'FALTA',
              'border-surface-100':               !inscrito.estatus_asistencia,
            }"
          >
            <div class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center"
              :class="{
                'bg-green-100': inscrito.estatus_asistencia === 'PRESENTE',
                'bg-red-100':   inscrito.estatus_asistencia === 'FALTA',
                'bg-surface-100': !inscrito.estatus_asistencia,
              }"
            >
              <svg v-if="inscrito.estatus_asistencia === 'PRESENTE'"
                xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else-if="inscrito.estatus_asistencia === 'FALTA'"
                xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span v-else class="w-1.5 h-1.5 rounded-full bg-surface-300" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-surface-900 truncate leading-tight">{{ inscrito.nombre }}</p>
              <p class="text-[10px] font-mono tracking-widest mt-0.5"
                :class="{
                  'text-green-600': inscrito.estatus_asistencia === 'PRESENTE',
                  'text-red-400':   inscrito.estatus_asistencia === 'FALTA',
                  'text-surface-400': !inscrito.estatus_asistencia,
                }"
              >{{ inscrito.codigo_qr ?? '—' }}</p>
            </div>
            <span class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full"
              :class="{
                'bg-green-100 text-green-700':    inscrito.estatus_asistencia === 'PRESENTE',
                'bg-red-100 text-red-600':         inscrito.estatus_asistencia === 'FALTA',
                'bg-surface-100 text-surface-500': !inscrito.estatus_asistencia,
              }"
            >
              {{ inscrito.estatus_asistencia === 'PRESENTE' ? 'Asistió' : inscrito.estatus_asistencia === 'FALTA' ? 'Falta' : 'Pendiente' }}
            </span>
          </li>
        </ul>
      </div>
    </Transition>

    <!-- Cargando lista preliminar -->
    <div v-if="store.listaLoading" class="space-y-1.5 mt-2">
      <div v-for="i in 3" :key="i" class="h-12 rounded-xl bg-surface-100 animate-pulse" />
    </div>

    <!-- Estado vacío -->
    <div v-if="store.sesionesHoy.length === 0" class="text-center py-10">
      <p class="text-sm font-semibold text-surface-400">Sin sesiones asignadas hoy</p>
    </div>

  </div>
</template>
