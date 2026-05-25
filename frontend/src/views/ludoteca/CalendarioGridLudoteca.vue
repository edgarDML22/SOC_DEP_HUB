<script setup>
import { ref, computed, onUnmounted } from 'vue'

const props = defineProps({
  // Turnos visibles en el calendario (ya filtrados por visibilidad de instructor)
  turnos: { type: Array, default: () => [] },
  // Formulario de turno en progreso para preview en tiempo real
  turnoPreview: { type: Object, default: null },
})

const emit = defineEmits(['click-slot', 'click-turno'])

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
  return h * 60 + (m || 0)
}

function clampSlot(s) {
  return Math.max(0, Math.min(TOTAL_SLOTS, s))
}

function turnoASlots(t) {
  const horaIni = t.hora_inicio || ''
  const horaFin = t.hora_fin || ''
  const startMin = toMinutes(horaIni) - HORA_INICIO * 60
  const endMin   = toMinutes(horaFin) - HORA_INICIO * 60
  const startSlot = clampSlot(Math.floor(startMin / SLOT_MIN))
  const endSlot   = clampSlot(Math.ceil(endMin / SLOT_MIN))
  return { startSlot, endSlot: Math.max(endSlot, startSlot + 1) }
}

// ─── Mapear fecha → día de la semana ─────────────────────────────────────
function fechaADia(fecha) {
  if (!fecha) return null
  const d = new Date(fecha + 'T12:00:00')
  const day = d.getDay() // 0=Dom, 1=Lun ...
  return DIAS[day === 0 ? 6 : day - 1]
}

// ─── Bloques de vista previa (preview en tiempo real desde el formulario) ───
const bloquesPreview = computed(() => {
  const f = props.turnoPreview
  if (!f || !f.hora_inicio || !f.hora_fin || !f.dia) return []

  return [{
    _origen: 'preview',
    _srcIdx: 0,
    dia_semana: f.dia,
    hora_inicio: f.hora_inicio,
    hora_fin: f.hora_fin,
    _instructor_nombre: f._instructor_nombre || '',
  }]
})

// Combina turnos reales con los de vista previa
const todosLosBloques = computed(() => {
  // Map turnos to include dia_semana from fecha
  const mapped = props.turnos.map((t, i) => ({
    ...t,
    _origen: 'turno',
    _srcIdx: i,
    dia_semana: fechaADia(t.fecha),
    _instructor_nombre: t.instructor || '',
  }))
  return [...mapped, ...bloquesPreview.value]
})

// ─── Layout de lanes por día (manejo de solapamientos) ───────────────────
function layoutDia(dia) {
  const items = todosLosBloques.value
    .filter(b => b.dia_semana === dia)
    .map(b => {
      const { startSlot, endSlot } = turnoASlots(b)
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

// ─── Tokens visuales ─────────────────────────────────────────────────────
function clasesBloque(b) {
  if (b._origen === 'preview') {
    return {
      bg: 'bg-primary-50',
      borderStyle: 'border border-dashed border-l-4 border-l-primary-500 border-primary-400',
      text: 'text-primary-700 font-extrabold',
      sub: 'text-primary-500 font-semibold',
    }
  }
  // Turno activo — gradiente azul→indigo (mismos colores que el FullCalendar anterior)
  return {
    bg: 'bg-linear-to-br from-blue-500 to-indigo-700',
    borderStyle: 'border border-blue-700 border-l-4 border-l-blue-800',
    text: 'text-white font-extrabold',
    sub: 'text-blue-100 font-semibold',
  }
}

// ─── Click → abre modal (vía emit) ───────────────────────────────────────
function abrirDetalle(b) {
  if (b._origen === 'preview') return
  emit('click-turno', b)
}

// ─── Arrastre y Selección de Rango de Celdas (Estilo Google Calendar) ──────
const isDragging = ref(false)
const dragStartDia = ref(null)
const dragStartHourIdx = ref(null)
const dragCurrentHourIdx = ref(null)

function iniciarArrastre(dia, hIdx) {
  isDragging.value = true
  dragStartDia.value = dia
  dragStartHourIdx.value = hIdx
  dragCurrentHourIdx.value = hIdx
  window.addEventListener('mouseup', finalizarArrastre)
}

function actualizarArrastre(dia, hIdx) {
  if (!isDragging.value) return
  if (dia === dragStartDia.value) {
    dragCurrentHourIdx.value = hIdx
  }
}

function finalizarArrastre() {
  if (!isDragging.value) return

  const minIdx = Math.min(dragStartHourIdx.value, dragCurrentHourIdx.value)
  const maxIdx = Math.max(dragStartHourIdx.value, dragCurrentHourIdx.value)

  const startHour = HORA_INICIO + (minIdx - 1)
  const endHour = HORA_INICIO + maxIdx
  const formatHour = (h) => `${String(h).padStart(2, '0')}:00`

  emit('click-slot', {
    dia: dragStartDia.value,
    horaInicio: formatHour(startHour),
    horaFin: formatHour(endHour),
  })

  isDragging.value = false
  dragStartDia.value = null
  dragStartHourIdx.value = null
  dragCurrentHourIdx.value = null
  window.removeEventListener('mouseup', finalizarArrastre)
}

function estaEnRangoSeleccionado(dia, hIdx) {
  if (!isDragging.value) return false
  if (dia !== dragStartDia.value) return false
  const minIdx = Math.min(dragStartHourIdx.value, dragCurrentHourIdx.value)
  const maxIdx = Math.max(dragStartHourIdx.value, dragCurrentHourIdx.value)
  return hIdx >= minIdx && hIdx <= maxIdx
}

onUnmounted(() => {
  window.removeEventListener('mouseup', finalizarArrastre)
})

function getHourTimeLabel(hIdx) {
  const hour = HORA_INICIO + (hIdx - 1)
  return `${String(hour).padStart(2, '0')}:00`
}

// ─── Indicador de día actual ─────────────────────────────────────────────
const now = new Date()
const todayDia = DIAS[now.getDay() === 0 ? 6 : now.getDay() - 1]

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
          <div class="h-14 flex items-center justify-end pr-3 border-r border-slate-100">
            <span class="text-[10px] font-semibold text-slate-600 tracking-wider">
              {{ timezoneLabel }}
            </span>
          </div>
          <!-- Day names -->
          <div
            v-for="dayObj in weekDays"
            :key="dayObj.dia"
            class="h-14 border-l border-slate-100 flex items-center justify-center"
          >
            <span
              :class="[
                'text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-md transition-all duration-150',
                dayObj.dia === todayDia ? 'bg-primary-50 text-primary-600 font-extrabold shadow-sm' : 'text-slate-700'
              ]"
            >{{ DIAS_LABEL[dayObj.dia] }}</span>
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
            <!-- Render the final hour (10 PM / 22:00) at the bottom border of the last hour slot -->
            <span
              v-if="i === horas.length - 1"
              class="absolute bottom-0 right-3 translate-y-1 text-[10px] font-semibold text-slate-700 leading-none tracking-wide"
            >
              {{ formatGutterHour(`${String(HORA_FIN).padStart(2, '0')}:00`) }}
            </span>
          </div>

          <!-- COLUMNAS DE DÍA — fondo (líneas de hora) -->
          <template v-for="(dia, diaIdx) in DIAS" :key="'col-' + dia">
            <button
              v-for="hIdx in TOTAL_HORAS"
              :key="`bg-${dia}-${hIdx}`"
              type="button"
              @mousedown.prevent="iniciarArrastre(dia, hIdx)"
              @mouseenter="actualizarArrastre(dia, hIdx)"
              :class="[
                'w-full h-full border-l border-l-slate-100 border-t border-t-slate-100 text-left align-top transition-all duration-100 cursor-pointer focus:outline-none select-none group',
                hIdx === TOTAL_HORAS ? 'border-b border-b-slate-100' : '',
                estaEnRangoSeleccionado(dia, hIdx)
                  ? 'bg-primary-100/70 border-primary-200'
                  : (dia === todayDia ? 'bg-primary-50/10 hover:bg-primary-100/30' : 'hover:bg-slate-50'),
              ]"
              :style="{
                gridColumn: diaIdx + 2,
                gridRow: `${(hIdx - 1) * SLOTS_POR_HORA + 1} / span ${SLOTS_POR_HORA}`,
              }"
              :title="`Programar turno el ${DIAS_LABEL[dia]} a las ${getHourTimeLabel(hIdx)}`"
            >
              <!-- Faint dashed inner line to represent 30-minute slot visually -->
              <div
                :class="[
                  'h-1/2 border-b border-b-slate-100/30 border-dashed pointer-events-none group-hover:border-b-transparent transition-colors duration-100',
                  estaEnRangoSeleccionado(dia, hIdx) ? 'border-b-transparent' : ''
                ]"
              />
            </button>
          </template>

          <!-- BLOQUES DE TURNO -->
          <template v-for="(dia, diaIdx) in DIAS" :key="'sess-' + dia">
            <button
              v-for="b in layoutPorDia[dia]"
              :key="`${b._origen}-${b._srcIdx}-${dia}`"
              type="button"
              @click="abrirDetalle(b)"
              :style="{
                gridColumn: diaIdx + 2,
                gridRow: `${b._startSlot + 1} / ${b._endSlot + 1}`,
                width: `calc(${100 / b._totalLanes}% - 6px)`,
                marginLeft: `calc(${(100 / b._totalLanes) * b._lane}% + 3px)`,
              }"
              :class="[
                'relative z-10 rounded-xl overflow-hidden text-left transition-all duration-150',
                b._origen === 'preview'
                  ? 'cursor-default focus:outline-none'
                  : 'hover:shadow-md hover:-translate-y-px focus:outline-none focus:ring-2 focus:ring-primary-500/40 active:scale-[0.98]',
                'my-0.5',
                clasesBloque(b).bg,
                clasesBloque(b).borderStyle,
              ]"
            >
              <div class="pl-2.5 pr-2 py-1.5 h-full flex flex-col justify-start overflow-hidden">
                <p
                  :class="['text-[11px] font-bold truncate leading-tight flex items-center gap-1.5', clasesBloque(b).text]"
                >
                  <span v-if="b._origen === 'preview'" class="w-1.5 h-1.5 rounded-full shrink-0 bg-primary-400" />
                  {{ b._instructor_nombre || 'Nuevo Turno' }}
                </p>
                <p
                  v-if="(b._endSlot - b._startSlot) >= 2"
                  :class="['text-[10px] font-medium truncate leading-tight mt-0.5 tabular-nums', clasesBloque(b).sub]"
                >{{ String(b.hora_inicio).slice(0,5) }}–{{ String(b.hora_fin).slice(0,5) }}</p>
                <p
                  v-if="(b._endSlot - b._startSlot) >= 3"
                  :class="['text-[10px] font-medium truncate leading-tight mt-0.5', clasesBloque(b).sub]"
                >Instructor</p>
              </div>
            </button>
          </template>
        </div>
      </div>
    </div>

    <!-- ══════════ LEYENDA ══════════ -->
    <div class="flex gap-3 flex-wrap shrink-0 px-1">
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border-l-[3px] border-l-blue-800 border border-blue-700 inline-block"
          style="background: linear-gradient(to bottom right, #3b82f6, #4338ca);" />
        Turno activo
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border border-dashed border-l-[3px] border-primary-400 bg-primary-50/40 inline-block" />
        Vista previa
      </span>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar for the calendar grid container to match Google Calendar */
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
