<script setup>
import { ref, computed, onUnmounted } from 'vue'

const props = defineProps({
  // Sesiones publicadas para esta semana
  sesiones: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits(['select-sesion'])

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

// ─── Layout de lanes por día (manejo de solapamientos) ───────────────────
function layoutDia(dia) {
  const items = props.sesiones
    .filter(b => b.dia_semana === dia)
    .map(b => {
      const { startSlot, endSlot } = sesionASlots(b)
      return { ...b, _startSlot: startSlot, _endSlot: endSlot }
    })
    .sort((a, b) => a._startSlot - b._startSlot || b._endSlot - a._endSlot)

  // Asigna lane (columna interna) usando greedy
  const lanes = []
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

// Estilo visual según estatus de sesión
function clasesBloque(b) {
  if (b.estatus === 'CANCELADA') {
    return {
      bg: 'bg-slate-100 border-slate-200 text-slate-400 opacity-70 line-through',
      borderStyle: 'border border-slate-200 border-l-4 border-l-slate-400',
      text: 'text-slate-400 font-extrabold',
      sub: 'text-slate-400 font-medium',
    }
  }

  const cerrada = !!b.requiere_inscripcion

  return cerrada
    ? {
        bg: 'bg-gradient-to-br from-rose-500 to-red-700',
        borderColor: 'border-red-700',
        borderStyle: 'border border-red-700 border-l-4 border-l-red-800',
        text: 'text-white font-extrabold',
        sub: 'text-rose-100 font-semibold',
      }
    : {
        bg: 'bg-gradient-to-br from-emerald-500 to-teal-700',
        borderColor: 'border-teal-700',
        borderStyle: 'border border-teal-700 border-l-4 border-l-teal-800',
        text: 'text-white font-extrabold',
        sub: 'text-emerald-100 font-semibold',
      }
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
const todayIdx = computed(() => DIAS.indexOf(todayDia))

const timezoneLabel = computed(() => {
  const offsetMinutes = new Date().getTimezoneOffset()
  const offsetHours = -offsetMinutes / 60
  const sign = offsetHours >= 0 ? '+' : ''
  return `GMT${sign}${offsetHours}`
})

const weekDays = computed(() => {
  const current = new Date()
  const currentDay = current.getDay()
  const distanceToMonday = currentDay === 0 ? 6 : currentDay - 1
  
  const monday = new Date(current)
  monday.setDate(current.getDate() - distanceToMonday)
  
  return DIAS.map((dia, index) => {
    const d = new Date(monday)
    d.setDate(monday.getDate() + index)
    return {
      dia,
      fecha: d.getDate(),
    }
  })
})

function formatGutterHour(h) {
  const [hStr] = h.split(':')
  const num = parseInt(hStr, 10)
  if (num === 12) return '12 PM'
  if (num === 0) return '12 AM'
  return num < 12 ? `${num} AM` : `${num - 12} PM`
}
</script>

<template>
  <div class="flex flex-col h-full gap-3 min-h-0">
    <!-- ══════════ CALENDAR GRID ══════════ -->
    <div class="flex-1 min-h-0 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-sm calendar-scrollbar">
      <div class="min-w-[820px] pb-4">

        <!-- ── Day headers (sticky) ── -->
        <div
          class="sticky top-0 z-20 bg-white/95 backdrop-blur-sm border-b border-slate-200 grid"
          :style="{ gridTemplateColumns: '64px repeat(7, minmax(0, 1fr))' }"
        >
          <!-- Top-left: Timezone label -->
          <div class="h-20 flex flex-col items-end justify-end pb-2 pr-3 border-r border-slate-100">
            <span class="text-[10px] font-semibold text-slate-600 tracking-wider">
              {{ timezoneLabel }}
            </span>
          </div>
          <!-- Day names & dates -->
          <div
            v-for="dayObj in weekDays"
            :key="dayObj.dia"
            class="h-20 border-l border-slate-100 flex flex-col items-center justify-center gap-1"
          >
            <span
              :class="[
                'text-[11px] font-bold uppercase tracking-wider',
                dayObj.dia === todayDia ? 'text-primary-600' : 'text-slate-700'
              ]"
            >{{ DIAS_LABEL[dayObj.dia] }}</span>
            <div
              :class="[
                'w-9 h-9 rounded-full flex items-center justify-center text-[15px] transition-colors duration-150',
                dayObj.dia === todayDia
                  ? 'bg-primary-600 text-white font-bold shadow-sm'
                  : 'text-slate-800 font-semibold hover:bg-slate-100'
              ]"
            >
              {{ dayObj.fecha }}
            </div>
          </div>
        </div>

        <!-- ── Body grid (gutter + 7 day columns) ── -->
        <div
          class="relative grid"
          :style="{
            gridTemplateColumns: '64px repeat(7, minmax(0, 1fr))',
            gridTemplateRows: `repeat(${TOTAL_SLOTS}, ${SLOT_PX}px)`,
          }"
        >
          <!-- GUTTER de horas -->
          <div
            v-for="(hora, i) in horas"
            :key="'g-' + hora"
            class="pr-3 flex items-start justify-end relative"
            :style="{ gridColumn: 1, gridRow: `${i * SLOTS_POR_HORA + 1} / span ${SLOTS_POR_HORA}` }"
          >
            <span
              :class="[
                'text-[10px] font-semibold text-slate-700 leading-none tracking-wide',
                i > 0 ? '-translate-y-1.5' : 'translate-y-1'
              ]"
            >
              {{ formatGutterHour(hora) }}
            </span>
            <span
              v-if="i === horas.length - 1"
              class="absolute bottom-0 right-3 translate-y-1 text-[10px] font-semibold text-slate-700 leading-none tracking-wide"
            >
              {{ formatGutterHour(`${String(HORA_FIN).padStart(2, '0')}:00`) }}
            </span>
          </div>

          <!-- COLUMNAS DE DÍA — fondo (líneas de hora) -->
          <template v-for="(dia, diaIdx) in DIAS" :key="'col-' + dia">
            <div
              v-for="hIdx in TOTAL_HORAS"
              :key="`bg-${dia}-${hIdx}`"
              :class="[
                'w-full h-full border-l border-l-slate-100 border-t border-t-slate-100 text-left align-top select-none',
                hIdx === TOTAL_HORAS ? 'border-b border-b-slate-100' : '',
                dia === todayDia ? 'bg-slate-50/50' : '',
              ]"
              :style="{
                gridColumn: diaIdx + 2,
                gridRow: `${(hIdx - 1) * SLOTS_POR_HORA + 1} / span ${SLOTS_POR_HORA}`,
              }"
            >
              <!-- Faint dashed inner line to represent 30-minute slot visually -->
              <div class="h-1/2 border-b border-b-slate-100/30 border-dashed pointer-events-none" />
            </div>
          </template>

          <!-- LÍNEA DE HORA ACTUAL -->
          <template v-if="currentTimeTopPx !== null && todayIdx !== -1">
            <div
              class="pointer-events-none z-20 absolute left-0 right-0 flex items-center"
              :style="{
                gridColumn: todayIdx + 2,
                top: currentTimeTopPx + 'px',
                height: '1px'
              }"
            >
              <div class="w-2 h-2 rounded-full bg-red-500 -ml-1 shadow-sm shrink-0 z-30" />
              <div class="flex-1 h-0.5 bg-red-500" />
            </div>
          </template>

          <!-- BLOQUES DE SESIÓN -->
          <template v-for="(dia, diaIdx) in DIAS" :key="'sess-' + dia">
            <button
              v-for="b in layoutPorDia[dia]"
              :key="b.id"
              type="button"
              @click="emit('select-sesion', b)"
              :style="{
                gridColumn: diaIdx + 2,
                gridRow: `${b._startSlot + 1} / ${b._endSlot + 1}`,
                width: `calc(${100 / b._totalLanes}% - 6px)`,
                marginLeft: `calc(${(100 / b._totalLanes) * b._lane}% + 3px)`,
              }"
              :class="[
                'relative z-10 rounded-xl overflow-hidden text-left transition-all duration-150 my-0.5',
                'hover:shadow-md hover:-translate-y-px focus:outline-none focus:ring-2 focus:ring-blue-500/40 active:scale-[0.98]',
                clasesBloque(b).bg,
                clasesBloque(b).borderStyle,
              ]"
            >
              <div class="pl-2.5 pr-2 py-2 h-full flex flex-col justify-between overflow-hidden">
                <div class="min-w-0">
                  <div class="flex items-start justify-between gap-1">
                    <p :class="['text-[11px] font-bold truncate leading-tight', clasesBloque(b).text]">
                      {{ b.disciplina }}
                    </p>
                    <!-- Small cancel indicator -->
                    <span
                      v-if="b.estatus === 'CANCELADA'"
                      class="px-1 py-0.2 rounded text-[7px] font-black bg-red-100 text-red-700 uppercase tracking-wide shrink-0"
                    >
                      Canc.
                    </span>
                  </div>
                  <p :class="['text-[9px] font-medium truncate leading-tight mt-0.5 tabular-nums', clasesBloque(b).sub]">
                    {{ String(b.hora_inicio).slice(0,5) }}–{{ String(b.hora_fin).slice(0,5) }}
                  </p>
                  <p :class="['text-[9px] font-semibold truncate leading-tight mt-0.5', clasesBloque(b).sub]">
                    {{ b.instructor }}
                  </p>
                </div>
                <div class="flex items-center justify-between text-[9px] font-bold border-t border-white/10 pt-1 mt-1 shrink-0">
                  <span :class="['truncate', clasesBloque(b).sub]">{{ b.espacio }}</span>
                  <span :class="['shrink-0 flex items-center gap-0.5 px-1 rounded-md bg-black/10 text-[9px]', clasesBloque(b).text]">
                    👥 {{ b.inscritos }}
                  </span>
                </div>
              </div>
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- ══════════ LEYENDA ══════════ -->
    <div class="flex gap-4 flex-wrap shrink-0 px-2 text-[10px]">
      <span class="flex items-center gap-1.5 font-bold text-slate-500">
        <span class="w-3 h-3 rounded border border-teal-700 bg-gradient-to-br from-emerald-500 to-teal-700" />
        Sesión Disponible (Abierta)
      </span>
      <span class="flex items-center gap-1.5 font-bold text-slate-500">
        <span class="w-3 h-3 rounded border border-red-700 bg-gradient-to-br from-rose-500 to-red-700" />
        Sesión Disponible (Inscripción Requerida)
      </span>
      <span class="flex items-center gap-1.5 font-bold text-slate-500">
        <span class="w-3 h-3 rounded border border-slate-300 bg-slate-100" />
        Sesión Cancelada
      </span>
    </div>
  </div>
</template>

<style scoped>
.calendar-scrollbar::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
.calendar-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.calendar-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.calendar-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
