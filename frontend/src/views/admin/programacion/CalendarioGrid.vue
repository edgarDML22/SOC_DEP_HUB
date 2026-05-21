<script setup>
import { computed } from 'vue'
import { useWizardStore } from '@/stores/programacion/wizardStore'

const props = defineProps({
  // Sesión local resaltada (por compatibilidad con el wizard al hacer click en una tarjeta del borrador)
  sesionResaltadaIndex: { type: Number, default: null },
})

const store = useWizardStore()

// ─── Constantes de layout ─────────────────────────────────────────────────
const DIAS = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO']
const DIAS_LABEL = {
  LUNES: 'Lun', MARTES: 'Mar', MIERCOLES: 'Mié',
  JUEVES: 'Jue', VIERNES: 'Vie', SABADO: 'Sáb', DOMINGO: 'Dom',
}

const HORA_INICIO = 6                       // 06:00
const HORA_FIN    = 22                      // 22:00
const TOTAL_HORAS = HORA_FIN - HORA_INICIO  // 16
const SLOT_MIN    = 30                       // 30-minute slots
const SLOTS_POR_HORA = 60 / SLOT_MIN         // 2
const TOTAL_SLOTS = TOTAL_HORAS * SLOTS_POR_HORA // 32
const SLOT_PX     = 30                       // altura visual de cada slot (= 60px por hora)

const horas = Array.from({ length: TOTAL_HORAS }, (_, i) => {
  const h = HORA_INICIO + i
  return `${String(h).padStart(2, '0')}:00`
})

// ─── Time helpers ────────────────────────────────────────────────────────
function toMinutes(t) {
  const [h, m] = String(t).split(':').map(Number)
  return h * 60 + m
}

function clampSlot(s) {
  return Math.max(0, Math.min(TOTAL_SLOTS, s))
}

function sesionASlots(s) {
  const startMin = toMinutes(s.hora_inicio) - HORA_INICIO * 60
  const endMin   = toMinutes(s.hora_fin)    - HORA_INICIO * 60
  const startSlot = clampSlot(Math.floor(startMin / SLOT_MIN))
  const endSlot   = clampSlot(Math.ceil(endMin   / SLOT_MIN))
  return { startSlot, endSlot: Math.max(endSlot, startSlot + 1) }
}

// ─── Estado inicial vacío: sin filtro de disciplina activo, no renderizar nada ───
// Evita la saturación visual masiva. El usuario activa el filtro desde el header
// o desde el ojo de la sección de borradores para ver las sesiones.
const calendarVisibleSinFiltro = computed(() => store.filtros.id_disciplina !== null)

// ─── Bloques filtrados desde el store ────────────────────────────────────
// Cada bloque trae { ...sesion, _origen: 'draft'|'borrador', _srcIdx }
const bloques = computed(() => calendarVisibleSinFiltro.value ? store.sesionesVisibles : [])

// ─── Layout de lanes por día (manejo de solapamientos) ───────────────────
// Para cada día calculamos columnas-lane para que los bloques solapados se
// repartan el ancho disponible (estilo Google Calendar simplificado).
function layoutDia(dia) {
  const items = bloques.value
    .filter(b => b.dia_semana === dia)
    .map(b => {
      const { startSlot, endSlot } = sesionASlots(b)
      return { ...b, _startSlot: startSlot, _endSlot: endSlot }
    })
    .sort((a, b) => a._startSlot - b._startSlot || b._endSlot - a._endSlot)

  // Asigna lane (columna interna) usando greedy
  const lanes = [] // cada lane es un array de items que terminan antes del nuevo
  for (const it of items) {
    let asignado = false
    for (let i = 0; i < lanes.length; i++) {
      const last = lanes[i][lanes[i].length - 1]
      if (last._endSlot <= it._startSlot) {
        lanes[i].push(it)
        it._lane = i
        asignado = true
        break
      }
    }
    if (!asignado) {
      it._lane = lanes.length
      lanes.push([it])
    }
  }

  const totalLanes = Math.max(1, lanes.length)
  return items.map(it => ({ ...it, _totalLanes: totalLanes }))
}

const layoutPorDia = computed(() => {
  const out = {}
  for (const dia of DIAS) out[dia] = layoutDia(dia)
  return out
})

// ─── Tokens visuales (paleta unificada) ───────────────────────────────────
// Confirmadas (draft persistido): fondo sólido emerald/red, border-l grueso
// Borrador local: fondo tenue, border-dashed
function clasesBloque(b, conflicto) {
  if (conflicto) {
    return {
      bg: 'bg-amber-50',
      borderColor: 'border-amber-400',
      borderStyle: 'border-l-4 border-y border-r',
      text: 'text-amber-800',
      sub:  'text-amber-700',
      stripeBg: 'repeating-linear-gradient(135deg, rgba(245,158,11,0.15) 0 6px, transparent 6px 12px)',
    }
  }
  const cerrada = !!b.requiere_inscripcion
  const esBorrador = b._origen === 'borrador'

  if (esBorrador) {
    return cerrada
      ? {
          bg: 'bg-red-50/40',
          borderColor: 'border-red-400',
          borderStyle: 'border border-dashed border-l-[3px]',
          text: 'text-red-700',
          sub:  'text-red-600',
          stripeBg: null,
        }
      : {
          bg: 'bg-emerald-50/40',
          borderColor: 'border-emerald-400',
          borderStyle: 'border border-dashed border-l-[3px]',
          text: 'text-emerald-700',
          sub:  'text-emerald-600',
          stripeBg: null,
        }
  }
  return cerrada
    ? {
        bg: 'bg-red-50',
        borderColor: 'border-red-300',
        borderStyle: 'border border-l-[3px]',
        text: 'text-red-700',
        sub:  'text-red-600',
        stripeBg: null,
      }
    : {
        bg: 'bg-emerald-50',
        borderColor: 'border-emerald-300',
        borderStyle: 'border border-l-[3px]',
        text: 'text-emerald-700',
        sub:  'text-emerald-600',
        stripeBg: null,
      }
}

function tieneConflicto(b) {
  return store.detectarColisionEnEdicion(b, b._origen, b._srcIdx).length > 0
}

// ─── Click → abre modal (vía store) ───────────────────────────────────────
function abrirDetalle(b) {
  store.abrirDetalleSesion(b._origen, b._srcIdx)
}

// ─── Indicador de hora actual ─────────────────────────────────────────────
const now = new Date()
const currentMinutes = now.getHours() * 60 + now.getMinutes()
const currentTimeTopPx = computed(() => {
  const offsetMin = currentMinutes - HORA_INICIO * 60
  if (offsetMin < 0 || offsetMin > TOTAL_HORAS * 60) return null
  return (offsetMin / SLOT_MIN) * SLOT_PX
})
const todayDia = DIAS[now.getDay() === 0 ? 6 : now.getDay() - 1]

// ─── Estado vacío ─────────────────────────────────────────────────────────
const sinResultados   = computed(() => bloques.value.length === 0)
const hayDataEnSistema = computed(() => store.tieneActividades || store.tieneBorradorLocal)

// Razón del estado vacío (para el mensaje)
const motivoVacio = computed(() => {
  if (!calendarVisibleSinFiltro.value) return 'sin-filtro'
  if (!hayDataEnSistema.value)          return 'sin-data'
  return 'filtro-sin-resultados'
})
</script>

<template>
  <div class="flex flex-col h-full gap-3 min-h-0">

    <!-- ══════════ CALENDAR GRID ══════════ -->
    <div class="flex-1 min-h-0 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="min-w-[820px]">

        <!-- ── Day headers (sticky) ── -->
        <div
          class="sticky top-0 z-20 bg-white/95 backdrop-blur-sm border-b border-slate-100 grid"
          :style="{ gridTemplateColumns: '56px repeat(7, minmax(0, 1fr))' }"
        >
          <div class="h-12" />
          <div
            v-for="dia in DIAS"
            :key="dia"
            class="h-12 border-l border-slate-100 flex flex-col items-center justify-center"
          >
            <span
              :class="[
                'text-[10px] font-black uppercase tracking-widest',
                dia === todayDia ? 'text-primary-600' : 'text-slate-400'
              ]"
            >{{ DIAS_LABEL[dia] }}</span>
            <span
              v-if="dia === todayDia"
              class="mt-0.5 w-1 h-1 rounded-full bg-primary-500"
            />
          </div>
        </div>

        <!-- ── Body grid (gutter + 7 day columns) ── -->
        <div
          class="relative grid"
          :style="{
            gridTemplateColumns: '56px repeat(7, minmax(0, 1fr))',
            gridTemplateRows: `repeat(${TOTAL_SLOTS}, ${SLOT_PX}px)`,
          }"
        >
          <!-- GUTTER de horas -->
          <div
            v-for="(hora, i) in horas"
            :key="'g-' + hora"
            class="border-t border-slate-100 pr-2.5 flex items-start justify-end"
            :style="{ gridColumn: 1, gridRow: `${i * SLOTS_POR_HORA + 1} / span ${SLOTS_POR_HORA}` }"
          >
            <span class="text-[9px] font-bold text-slate-300 leading-none -translate-y-1 tracking-wide">{{ hora }}</span>
          </div>

          <!-- COLUMNAS DE DÍA — fondo (líneas de hora y media hora) -->
          <template v-for="(dia, diaIdx) in DIAS" :key="'col-' + dia">
            <div
              v-for="slot in TOTAL_SLOTS"
              :key="`bg-${dia}-${slot}`"
              :class="[
                'border-l',
                slot % 2 === 1 ? 'border-t border-t-slate-100' : 'border-t border-t-dashed border-t-slate-100/60',
                dia === todayDia ? 'bg-primary-50/15' : '',
                'border-l-slate-100',
              ]"
              :style="{ gridColumn: diaIdx + 2, gridRow: slot }"
            />
          </template>

          <!-- LÍNEA DE HORA ACTUAL -->
          <template v-if="currentTimeTopPx !== null">
            <div
              class="pointer-events-none z-10 absolute left-14 right-0 flex items-center"
              :style="{ top: currentTimeTopPx + 'px' }"
            >
              <div class="w-1.5 h-1.5 rounded-full bg-primary-500 -ml-1 shadow-sm shrink-0" />
              <div class="flex-1 h-px bg-primary-400" />
            </div>
          </template>

          <!-- BLOQUES DE SESIÓN -->
          <template v-for="(dia, diaIdx) in DIAS" :key="'sess-' + dia">
            <button
              v-for="b in layoutPorDia[dia]"
              :key="`${b._origen}-${b._srcIdx}-${dia}`"
              type="button"
              @click="abrirDetalle(b)"
              :style="{
                gridColumn: diaIdx + 2,
                gridRow: `${b._startSlot + 1} / ${b._endSlot + 1}`,
                width: `calc(${100 / b._totalLanes}% - 4px)`,
                marginLeft: `calc(${(100 / b._totalLanes) * b._lane}% + 2px)`,
                ...(tieneConflicto(b) ? { backgroundImage: clasesBloque(b, true).stripeBg } : {}),
              }"
              :class="[
                'relative z-10 rounded-lg overflow-hidden text-left transition-all duration-150',
                'hover:shadow-md hover:-translate-y-px focus:outline-none focus:ring-2 focus:ring-primary-500/40',
                'my-px',
                clasesBloque(b, tieneConflicto(b)).bg,
                clasesBloque(b, tieneConflicto(b)).borderStyle,
                clasesBloque(b, tieneConflicto(b)).borderColor,
                props.sesionResaltadaIndex !== null && b._origen === 'borrador' && b._srcIdx === props.sesionResaltadaIndex
                  ? 'ring-2 ring-primary-500/50 shadow-md -translate-y-px'
                  : '',
              ]"
            >
              <div class="px-2 py-1.5 h-full flex flex-col justify-start overflow-hidden">
                <p
                  :class="['text-xs font-extrabold truncate leading-tight', clasesBloque(b, tieneConflicto(b)).text]"
                >{{ b._disciplina_nombre || '—' }}</p>
                <p
                  v-if="(b._endSlot - b._startSlot) >= 2"
                  :class="['text-[11px] font-semibold truncate leading-tight mt-0.5 tabular-nums', clasesBloque(b, tieneConflicto(b)).sub]"
                >{{ String(b.hora_inicio).slice(0,5) }}–{{ String(b.hora_fin).slice(0,5) }}</p>
                <p
                  v-if="(b._endSlot - b._startSlot) >= 3"
                  :class="['text-[11px] truncate leading-tight opacity-80 mt-0.5', clasesBloque(b, tieneConflicto(b)).sub]"
                >{{ b._instructor_nombre }}</p>
                <p
                  v-if="(b._endSlot - b._startSlot) >= 4"
                  :class="['text-[10px] truncate leading-tight opacity-60 mt-0.5', clasesBloque(b, tieneConflicto(b)).sub]"
                >{{ b._espacio_nombre }}</p>
              </div>
            </button>
          </template>

          <!-- ── Empty state overlay ── -->
          <div
            v-if="sinResultados"
            class="absolute inset-0 flex items-center justify-center pointer-events-none"
          >
            <!-- Sin filtro activo: mensaje principal de bienvenida -->
            <div v-if="motivoVacio === 'sin-filtro'" class="text-center select-none px-8 max-w-sm">
              <div class="w-16 h-16 rounded-3xl bg-primary-50 border border-primary-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-primary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <p class="text-sm font-extrabold text-slate-600 mb-1">Selecciona una disciplina para visualizar</p>
              <p class="text-xs text-slate-400 leading-relaxed">
                Usa el filtro de Disciplina en el header, o el ícono de ojo <span class="font-bold">👁</span> en la sección de borradores para activar la vista.
              </p>
            </div>

            <!-- Sin datos en el sistema -->
            <div v-else-if="motivoVacio === 'sin-data'" class="text-center select-none px-6 max-w-sm bg-white/60 backdrop-blur-sm rounded-2xl py-6">
              <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75" />
                </svg>
              </div>
              <p class="text-sm font-bold text-slate-500">Sin sesiones aún</p>
              <p class="text-xs text-slate-400 mt-1 leading-relaxed">Abre el panel y agrega tu primera sesión.</p>
            </div>

            <!-- Con datos pero filtro sin resultados -->
            <div v-else class="text-center select-none px-6 max-w-sm bg-white/60 backdrop-blur-sm rounded-2xl py-6">
              <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
              </div>
              <p class="text-sm font-bold text-slate-500">Sin resultados para este filtro</p>
              <p class="text-xs text-slate-400 mt-1 leading-relaxed">Prueba con otra disciplina o ajusta los filtros del header.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════ LEYENDA ══════════ -->
    <div class="flex gap-3 flex-wrap shrink-0 px-1">
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border border-l-[3px] border-emerald-300 border-l-emerald-500 bg-emerald-50 inline-block" />
        Confirmada · Abierta
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border border-l-[3px] border-red-300 border-l-red-500 bg-red-50 inline-block" />
        Confirmada · Cerrada
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border border-dashed border-l-[3px] border-emerald-400 bg-emerald-50/40 inline-block" />
        Borrador · Abierta
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border border-dashed border-l-[3px] border-red-400 bg-red-50/40 inline-block" />
        Borrador · Cerrada
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span
          class="w-3.5 h-3.5 rounded-sm border border-l-4 border-amber-400 bg-amber-50 inline-block"
          style="background-image: repeating-linear-gradient(135deg, rgba(245,158,11,0.25) 0 3px, transparent 3px 6px);"
        />
        Conflicto
      </span>
    </div>
  </div>
</template>
