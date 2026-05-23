<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { useWizardStore } from '@/stores/programacion/wizardStore'

const props = defineProps({
  // Sesión local resaltada (por compatibilidad con el wizard al hacer click en una tarjeta del borrador)
  sesionResaltadaIndex: { type: Number, default: null },
  // Formulario de sesión en progreso para preview en tiempo real
  sesionPreview: { type: Object, default: null },
})

const emit = defineEmits(['click-slot'])

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

// ─── Bloques de vista previa (preview en tiempo real desde el formulario) ───
const bloquesPreview = computed(() => {
  const f = props.sesionPreview
  if (!f || !f.hora_inicio || !f.hora_fin || !f.dias || f.dias.length === 0) return []

  const disc = store.disciplinas.find(d => d.id_disciplina === f.id_disciplina)
  const inst = store.instructores.find(i => i.id_instructor === f.id_instructor)
  const esp  = store.espacios.find(e => e.id_espacio === f.id_espacio)

  return f.dias.map((dia, idx) => ({
    _origen: 'preview',
    _srcIdx: idx,
    dia_semana: dia,
    hora_inicio: f.hora_inicio,
    hora_fin: f.hora_fin,
    requiere_inscripcion: f.requiere_inscripcion,
    id_espacio:    f.id_espacio    ?? null,
    id_instructor: f.id_instructor ?? null,
    _disciplina_nombre: disc?.nombre_disciplina ?? '',
    _instructor_nombre: inst?.nombre_completo ?? '',
    _espacio_nombre: esp?.nombre_espacio ?? '',
  }))
})

// Combina bloques guardados/borradores con los de vista previa
const todosLosBloques = computed(() => [
  ...store.sesionesVisibles,
  ...bloquesPreview.value,
])

// ─── Layout de lanes por día (manejo de solapamientos) ───────────────────
// Para cada día calculamos columnas-lane para que los bloques solapados se
// repartan el ancho disponible (estilo Google Calendar simplificado).
function layoutDia(dia) {
  const items = todosLosBloques.value
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

// ─── Tokens visuales — paleta Google Calendar exacta ─────────────────────
//
// CONFIRMADAS (origen 'confirmada' — BD actividades_plantilla):
//   Abierta  → bg-emerald-600 sólido, texto blanco
//   Cerrada  → bg-red-600 sólido, texto blanco
//
// DRAFT persistido (origen 'draft' — payload JSON):
//   Abierta  → bg-emerald-50, text-emerald-700, borde punteado border-emerald-400
//   Cerrada  → bg-red-50, text-red-700, borde punteado border-red-400
//
// BORRADOR LOCAL (origen 'borrador' — en memoria):
//   Mismo estilo que draft (punteado) — visualmente idéntico a draft pendiente
//
// CONFLICTO vs CONFIRMADA → bg-amber-500 sólido, texto blanco (causa severa)
// CONFLICTO vs BORRADOR   → bg-amber-50 punteado, text-amber-700, border-amber-400 dashed
// PREVIEW   → fondo primary suave, borde punteado primary
//
// conflictoTipo: 'confirmada' | 'borrador' | null
function clasesBloque(b, conflictoTipo) {
  if (conflictoTipo === 'confirmada') {
    // Ámbar degradado suave — mismo tono que las tarjetas del panel lateral
    return {
      bg: '',
      borderColor: 'border-amber-600',
      borderStyle: 'border border-amber-600 border-l-4 border-l-amber-700',
      text: 'text-white font-extrabold',
      sub:  'text-amber-100 font-semibold',
      stripeBg: 'linear-gradient(to bottom right, #fbbf24, #d97706)',
    }
  }

  if (conflictoTipo === 'borrador') {
    // Ámbar claro punteado — la causa es un draft o borrador local
    return {
      bg: 'bg-amber-50',
      borderColor: 'border-amber-400',
      borderStyle: 'border border-dashed border-amber-400 border-l-4 border-l-amber-500',
      text: 'text-amber-700 font-extrabold',
      sub:  'text-amber-600 font-semibold',
      stripeBg: 'repeating-linear-gradient(135deg, rgba(245,158,11,0.08) 0 8px, rgba(245,158,11,0.18) 8px 16px)',
    }
  }

  if (b._origen === 'preview') {
    // Siempre azul primary — la bolita verde/roja dentro del bloque indica el tipo
    return {
      bg: 'bg-primary-50',
      borderColor: 'border-primary-400',
      borderStyle: 'border border-dashed border-l-4 border-l-primary-500',
      text: 'text-primary-700 font-extrabold',
      sub:  'text-primary-500 font-semibold',
      stripeBg: null,
    }
  }

  const cerrada     = !!b.requiere_inscripcion
  const esConfirmada = b._origen === 'confirmada'

  // Confirmadas: gradiente metálico — mismo tono que los headers del modal de detalle
  if (esConfirmada) {
    return cerrada
      ? {
          bg: 'bg-gradient-to-br from-rose-500 to-red-700',
          borderColor: 'border-red-700',
          borderStyle: 'border border-red-700 border-l-4 border-l-red-800',
          text: 'text-white font-extrabold',
          sub:  'text-rose-100 font-semibold',
          stripeBg: null,
        }
      : {
          bg: 'bg-gradient-to-br from-emerald-500 to-teal-700',
          borderColor: 'border-teal-700',
          borderStyle: 'border border-teal-700 border-l-4 border-l-teal-800',
          text: 'text-white font-extrabold',
          sub:  'text-emerald-100 font-semibold',
          stripeBg: null,
        }
  }

  // Draft persistido o borrador local: fondo muy claro, borde punteado
  return cerrada
    ? {
        bg: 'bg-red-50',
        borderColor: 'border-red-400',
        borderStyle: 'border border-dashed border-red-400 border-l-4 border-l-red-500',
        text: 'text-red-700 font-extrabold',
        sub:  'text-red-600 font-medium',
        stripeBg: null,
      }
    : {
        bg: 'bg-emerald-50',
        borderColor: 'border-emerald-400',
        borderStyle: 'border border-dashed border-emerald-400 border-l-4 border-l-emerald-500',
        text: 'text-emerald-700 font-extrabold',
        sub:  'text-emerald-600 font-medium',
        stripeBg: null,
      }
}

function overlaps(aS, aE, bS, bE) { return aS < bE && bS < aE }

// Retorna el tipo de conflicto del bloque contra cualquier preview activo.
// El preview es un borrador en progreso → tipo 'borrador'.
function tipoConflictoConPreview(b) {
  if (!bloquesPreview.value.length) return null
  const bS = toMinutes(b.hora_inicio)
  const bE = toMinutes(b.hora_fin)
  for (const p of bloquesPreview.value) {
    if (p.dia_semana !== b.dia_semana) continue
    if (!overlaps(bS, bE, toMinutes(p.hora_inicio), toMinutes(p.hora_fin))) continue
    if (p.id_espacio    && p.id_espacio    === b.id_espacio)    return 'borrador'
    if (p.id_instructor && p.id_instructor === b.id_instructor) return 'borrador'
  }
  return null
}

// Retorna 'confirmada' | 'borrador' | null según el tipo de conflicto del bloque.
// - Para bloques preview: detecta si choca contra una confirmada o un borrador/draft existente.
// - Para bloques existentes: consulta el Map sesionesEnConflicto del store.
function tipoConflicto(b) {
  if (b._origen === 'preview') {
    const cols = store.detectarColisionEnEdicion(b, null, null)
    if (!cols.length) return null
    const tieneConfirmada = cols.some(c => c.sesionInfractora?.origen === 'confirmada')
    return tieneConfirmada ? 'confirmada' : 'borrador'
  }
  // Conflicto vs preview activo (siempre tipo 'borrador') o conflicto entre sesiones existentes
  const vsPreview = tipoConflictoConPreview(b)
  const vsExistente = store.sesionesEnConflicto.get(`${b._origen}-${b._srcIdx}`) ?? null
  // Prevalece 'confirmada' si cualquiera de los dos es confirmada
  if (vsPreview === 'confirmada' || vsExistente === 'confirmada') return 'confirmada'
  if (vsPreview === 'borrador'   || vsExistente === 'borrador')   return 'borrador'
  return null
}

// ─── Click → abre modal (vía store) ───────────────────────────────────────
// Omitir para bloques de preview en tiempo real
function abrirDetalle(b) {
  if (b._origen === 'preview') return
  store.abrirDetalleSesion(b._origen, b._srcIdx)
}

// ─── Click en celda vacía del Calendario ──────────────────────────────────
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

// ─── Indicador de hora actual ─────────────────────────────────────────────
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
  const currentDay = current.getDay() // 0 = Sunday, 1 = Monday, etc.
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

// ─── Estado vacío ─────────────────────────────────────────────────────────
const sinResultados = computed(() =>
  bloquesPreview.value.length === 0 && todosLosBloques.value.length === 0
)
const hayDataEnSistema = computed(() =>
  store.tieneActividades || store.tieneBorradorLocal || store.actividadesConfirmadas.length > 0
)

// Razón del estado vacío (para el mensaje)
const motivoVacio = computed(() => {
  if (store.filtros.id_disciplina === null) return 'sin-filtro'
  if (!hayDataEnSistema.value)              return 'sin-data'
  return 'filtro-sin-resultados'
})
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
              :title="`Programar sesión el ${DIAS_LABEL[dia]} a las ${getHourTimeLabel(hIdx)}`"
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

          <!-- LÍNEA DE HORA ACTUAL (REMOVIDA) -->

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
                width: `calc(${100 / b._totalLanes}% - 6px)`,
                marginLeft: `calc(${(100 / b._totalLanes) * b._lane}% + 3px)`,
                ...(clasesBloque(b, tipoConflicto(b)).stripeBg ? { backgroundImage: clasesBloque(b, tipoConflicto(b)).stripeBg } : {}),
              }"
              :class="[
                'relative z-10 rounded-xl overflow-hidden text-left transition-all duration-150',
                b._origen === 'preview'
                  ? 'cursor-default focus:outline-none'
                  : 'hover:shadow-md hover:-translate-y-px focus:outline-none focus:ring-2 focus:ring-primary-500/40 active:scale-[0.98]',
                'my-0.5',
                clasesBloque(b, tipoConflicto(b)).bg,
                clasesBloque(b, tipoConflicto(b)).borderStyle,
                clasesBloque(b, tipoConflicto(b)).borderColor,
                props.sesionResaltadaIndex !== null && b._origen === 'borrador' && b._srcIdx === props.sesionResaltadaIndex
                  ? 'ring-2 ring-primary-500/50 shadow-md -translate-y-px'
                  : '',
              ]"
            >
              <div class="pl-2.5 pr-2 py-1.5 h-full flex flex-col justify-start overflow-hidden">
                <p
                  :class="['text-[11px] font-bold truncate leading-tight flex items-center gap-1.5', clasesBloque(b, tipoConflicto(b)).text]"
                >
                  <span v-if="b._origen === 'preview'" :class="['w-1.5 h-1.5 rounded-full shrink-0', b.requiere_inscripcion ? 'bg-red-500' : 'bg-emerald-500']" />
                  {{ b._disciplina_nombre || 'Nueva Sesión' }}
                </p>
                <p
                  v-if="(b._endSlot - b._startSlot) >= 2"
                  :class="['text-[10px] font-medium truncate leading-tight mt-0.5 tabular-nums', clasesBloque(b, tipoConflicto(b)).sub]"
                >{{ String(b.hora_inicio).slice(0,5) }}–{{ String(b.hora_fin).slice(0,5) }}</p>
                <p
                  v-if="(b._endSlot - b._startSlot) >= 3"
                  :class="['text-[10px] font-medium truncate leading-tight mt-0.5', b._instructor_nombre ? clasesBloque(b, tipoConflicto(b)).sub : 'text-slate-400 italic']"
                >{{ b._instructor_nombre || 'Sin instructor' }}</p>
                <p
                  v-if="(b._endSlot - b._startSlot) >= 4"
                  :class="['text-[10px] font-medium truncate leading-tight mt-0.5', b._espacio_nombre ? clasesBloque(b, tipoConflicto(b)).sub : 'text-slate-400 italic']"
                >{{ b._espacio_nombre || 'Sin espacio' }}</p>
              </div>
            </button>
          </template>

          <!-- ── Empty state overlay ── -->
          <div
            v-if="sinResultados"
            class="absolute inset-0 flex items-center justify-center pointer-events-none z-30"
            style="grid-column: 1 / -1; grid-row: 1 / -1;"
          >
            <!-- Sin filtro de disciplina activo -->
            <div v-if="motivoVacio === 'sin-filtro'" class="bg-white border border-slate-200/80 shadow-xl rounded-2xl p-8 max-w-sm text-center select-none pointer-events-auto mx-4 transition-all duration-200">
              <div class="w-14 h-14 rounded-2xl bg-primary-50 border border-primary-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <h3 class="text-sm font-bold text-slate-800 mb-1.5">Selecciona una disciplina</h3>
              <p class="text-xs text-slate-500 leading-relaxed">
                Usa el filtro de <span class="font-semibold text-slate-700">Disciplina</span> en el encabezado o haz clic en el ícono de ojo en la barra de borradores para ver las sesiones.
              </p>
            </div>

            <!-- Sin datos con filtro activo -->
            <div v-else-if="motivoVacio === 'sin-data'" class="bg-white border border-slate-200/80 shadow-xl rounded-2xl p-8 max-w-sm text-center select-none pointer-events-auto mx-4 transition-all duration-200">
              <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75" />
                </svg>
              </div>
              <h3 class="text-sm font-bold text-slate-800 mb-1.5">Sin sesiones programadas</h3>
              <p class="text-xs text-slate-500 leading-relaxed">
                No hay sesiones creadas para esta disciplina. Abre el panel lateral y programa tu primera actividad.
              </p>
            </div>

            <!-- Con datos pero filtro sin resultados -->
            <div v-else class="bg-white border border-slate-200/80 shadow-xl rounded-2xl p-8 max-w-sm text-center select-none pointer-events-auto mx-4 transition-all duration-200">
              <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
              </div>
              <h3 class="text-sm font-bold text-slate-800 mb-1.5">Sin coincidencias</h3>
              <p class="text-xs text-slate-500 leading-relaxed">
                No se encontraron sesiones que coincidan con los filtros seleccionados. Intenta ajustar o limpiar los filtros en la barra superior.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════ LEYENDA ══════════ -->
    <div class="flex gap-3 flex-wrap shrink-0 px-1">
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border-l-[3px] border-l-teal-800 border border-teal-700 inline-block"
          style="background: linear-gradient(to bottom right, #10b981, #0f766e);" />
        Confirmada · Abierta
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border-l-[3px] border-l-red-800 border border-red-700 inline-block"
          style="background: linear-gradient(to bottom right, #f43f5e, #b91c1c);" />
        Confirmada · Cerrada
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border border-dashed border-l-[3px] border-emerald-400 border-l-emerald-500 bg-emerald-50 inline-block" />
        Borrador · Abierta
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border border-dashed border-l-[3px] border-red-400 border-l-red-500 bg-red-50 inline-block" />
        Borrador · Cerrada
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border-l-[3px] border-l-amber-700 border border-amber-600 inline-block"
          style="background: linear-gradient(to bottom right, #fbbf24, #d97706);" />
        Conflicto · vs Confirmada
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span
          class="w-3.5 h-3.5 rounded-sm border border-dashed border-l-[3px] border-l-amber-500 border-amber-400 bg-amber-50 inline-block"
          style="background-image: repeating-linear-gradient(135deg, rgba(245,158,11,0.25) 0 3px, transparent 3px 6px);"
        />
        Conflicto · vs Borrador
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-500">
        <span class="w-3.5 h-3.5 rounded-sm border border-dashed border-l-[3px] border-primary-400 bg-primary-50/40 inline-block" />
        Vista previa
      </span>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar for the calendar grid container to make it match Google Calendar */
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
