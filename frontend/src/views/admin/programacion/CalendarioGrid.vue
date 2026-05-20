<script setup>
import { ref, computed } from 'vue'
import { useWizardStore } from '@/stores/programacion/wizardStore'

const props = defineProps({
  sesionResaltadaIndex: { type: Number, default: null },
})

const store = useWizardStore()

const DIAS = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO']
const DIAS_LABEL = {
  LUNES: 'Lun', MARTES: 'Mar', MIERCOLES: 'Mié',
  JUEVES: 'Jue', VIERNES: 'Vie', SABADO: 'Sáb', DOMINGO: 'Dom',
}
const DIAS_NUMERO = {
  LUNES: 1, MARTES: 2, MIERCOLES: 3,
  JUEVES: 4, VIERNES: 5, SABADO: 6, DOMINGO: 7,
}

const HORA_INICIO  = 6
const HORA_FIN     = 22
const TOTAL_HORAS  = HORA_FIN - HORA_INICIO   // 16
const ROW_PX       = 60                        // px per hour

const horas = Array.from({ length: TOTAL_HORAS }, (_, i) => {
  const h = HORA_INICIO + i
  return `${String(h).padStart(2, '0')}:00`
})

// ─── Space tabs ──────────────────────────────────────────────────────────────
const tabActiva = ref(null)

const espacioActivo = computed(() => {
  const list = store.espaciosEnDraft
  if (!list.length) return null
  if (tabActiva.value === null) return list[0]
  return list.find(e => e.id_espacio === tabActiva.value) ?? list[0]
})

function setTab(id) { tabActiva.value = id }

// ─── Time helpers ────────────────────────────────────────────────────────────
function toMinutes(t) {
  const [h, m] = t.split(':').map(Number)
  return h * 60 + m
}

function posicionPx(act) {
  const startMin = toMinutes(act.hora_inicio) - HORA_INICIO * 60
  const durMin   = toMinutes(act.hora_fin) - toMinutes(act.hora_inicio)
  return {
    top:    `${(startMin / 60) * ROW_PX}px`,
    height: `${Math.max((durMin / 60) * ROW_PX, 28)}px`,
  }
}

// ─── Data per day (shows ALL spaces when no tab selected yet) ─────────────
function actividadesDeDia(dia) {
  const esp = espacioActivo.value
  if (!esp) return []
  return store.draft.actividades
    .map((a, i) => ({ ...a, _srcIdx: i }))
    .filter(a => a.id_espacio === esp.id_espacio && a.dia_semana === dia)
}

function borradoresDeDia(dia) {
  const esp = espacioActivo.value
  if (!esp) return []
  return store.borradorLocal
    .map((a, i) => ({ ...a, _srcIdx: i }))
    .filter(a => a.id_espacio === esp.id_espacio && a.dia_semana === dia)
}

// ─── Color tokens ────────────────────────────────────────────────────────────
function colorPersistida(act) {
  return act.requiere_inscripcion
    ? { bg: '#eef2ff', border: '#4361EE', text: '#3730a3', sub: '#6366f1' }
    : { bg: '#f0fdfa', border: '#00A896', text: '#0f766e', sub: '#0d9488' }
}

function colorLocal(act, highlighted) {
  if (highlighted) {
    return act.requiere_inscripcion
      ? { bg: '#e0e7ff', border: '#4361EE', text: '#312e81', sub: '#4338ca', dashed: false }
      : { bg: '#ccfbf1', border: '#00A896', text: '#134e4a', sub: '#0f766e', dashed: false }
  }
  return act.requiere_inscripcion
    ? { bg: 'rgba(238,242,255,0.7)', border: '#818cf8', text: '#4338ca', sub: '#6366f1', dashed: true }
    : { bg: 'rgba(240,253,250,0.7)', border: '#2dd4bf', text: '#0f766e', sub: '#14b8a6', dashed: true }
}

// ─── Current-time indicator ───────────────────────────────────────────────
const now = new Date()
const currentHourDecimal = now.getHours() + now.getMinutes() / 60
const currentTimeTopPx = computed(() => {
  const offsetFromStart = currentHourDecimal - HORA_INICIO
  if (offsetFromStart < 0 || offsetFromStart > TOTAL_HORAS) return null
  return offsetFromStart * ROW_PX
})
const todayDia = DIAS[now.getDay() === 0 ? 6 : now.getDay() - 1]

const hayActividades = computed(() => store.tieneActividades || store.tieneBorradorLocal)
</script>

<template>
  <div class="flex flex-col h-full gap-2.5">

    <!-- ── Space tabs ── -->
    <div v-if="store.espaciosEnDraft.length > 0" class="flex gap-1.5 flex-wrap shrink-0">
      <button
        v-for="esp in store.espaciosEnDraft"
        :key="esp.id_espacio"
        @click="setTab(esp.id_espacio)"
        :class="[
          'px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all duration-150',
          espacioActivo?.id_espacio === esp.id_espacio
            ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
            : 'bg-white text-slate-500 border-slate-200 hover:border-blue-300 hover:text-blue-600'
        ]"
      >{{ esp.nombre_espacio }}</button>
    </div>

    <!-- ── Empty state ── -->
    <div
      v-if="!hayActividades"
      class="flex-1 flex items-center justify-center rounded-2xl border-2 border-dashed border-slate-100 bg-white/50"
    >
      <div class="text-center select-none px-6">
        <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
          </svg>
        </div>
        <p class="text-sm font-bold text-slate-400">Sin sesiones aún</p>
        <p class="text-xs text-slate-300 mt-1 max-w-[180px] mx-auto leading-relaxed">
          Completa el formulario y presiona <strong class="text-slate-400">+ Agregar Sesión</strong>
        </p>
      </div>
    </div>

    <!-- ── Calendar grid ── -->
    <div v-else class="flex-1 min-h-0 overflow-auto rounded-2xl border border-slate-200/80 bg-white shadow-sm">
      <div class="flex flex-col" :style="{ minWidth: '680px' }">

        <!-- Day headers — sticky -->
        <div class="flex sticky top-0 z-20 bg-white/95 backdrop-blur-sm border-b border-slate-100">
          <!-- Time gutter header -->
          <div class="w-14 shrink-0" />
          <!-- Day columns -->
          <div
            v-for="dia in DIAS"
            :key="dia"
            class="flex-1 py-3 text-center border-l border-slate-100 first:border-l-0"
          >
            <div
              :class="[
                'inline-flex flex-col items-center gap-0.5',
              ]"
            >
              <span
                :class="[
                  'text-[10px] font-black uppercase tracking-widest',
                  dia === todayDia ? 'text-blue-500' : 'text-slate-400'
                ]"
              >{{ DIAS_LABEL[dia] }}</span>
              <span
                v-if="dia === todayDia"
                class="w-1 h-1 rounded-full bg-blue-400"
              />
            </div>
          </div>
        </div>

        <!-- Time rows + event grid -->
        <div class="flex relative" :style="{ height: (TOTAL_HORAS * ROW_PX) + 'px' }">

          <!-- Time gutter -->
          <div class="w-14 shrink-0 relative">
            <div
              v-for="(hora, i) in horas"
              :key="hora"
              class="absolute w-full flex items-start justify-end pr-2.5"
              :style="{ top: (i * ROW_PX) + 'px', height: ROW_PX + 'px' }"
            >
              <span class="text-[9px] font-bold text-slate-300 leading-none -translate-y-px tracking-wide">{{ hora }}</span>
            </div>
          </div>

          <!-- Day columns -->
          <div class="flex flex-1">
            <div
              v-for="(dia, diaIdx) in DIAS"
              :key="dia"
              :class="[
                'flex-1 relative border-l border-slate-100',
                dia === todayDia ? 'bg-blue-50/20' : '',
              ]"
            >
              <!-- Hour lines -->
              <div
                v-for="(hora, i) in horas"
                :key="`h-${hora}`"
                class="absolute left-0 right-0"
                :style="{ top: (i * ROW_PX) + 'px' }"
              >
                <div :class="['border-t w-full', i === 0 ? 'border-slate-200' : 'border-slate-100']" />
              </div>

              <!-- Half-hour tick lines -->
              <div
                v-for="(hora, i) in horas"
                :key="`half-${hora}`"
                class="absolute left-3 right-0 border-t border-dashed border-slate-100/80"
                :style="{ top: (i * ROW_PX + ROW_PX / 2) + 'px' }"
              />

              <!-- Current time indicator -->
              <template v-if="currentTimeTopPx !== null && dia === todayDia">
                <div
                  class="absolute left-0 right-0 z-10 flex items-center pointer-events-none"
                  :style="{ top: currentTimeTopPx + 'px' }"
                >
                  <div class="w-1.5 h-1.5 rounded-full bg-blue-500 -ml-0.5 shrink-0 shadow-sm" />
                  <div class="flex-1 h-px bg-blue-400" />
                </div>
              </template>

              <!-- Persisted sessions -->
              <div
                v-for="act in actividadesDeDia(dia)"
                :key="`p-${act._srcIdx}`"
                :style="{
                  ...posicionPx(act),
                  position: 'absolute',
                  left: '3px',
                  right: '3px',
                  backgroundColor: colorPersistida(act).bg,
                  borderLeftColor: colorPersistida(act).border,
                }"
                class="rounded-lg border-l-[3px] overflow-hidden shadow-sm hover:shadow-md transition-shadow cursor-default"
              >
                <div
                  class="h-[3px] w-full opacity-60"
                  :style="{ backgroundColor: colorPersistida(act).border }"
                />
                <div class="px-2 py-1 h-[calc(100%-3px)] flex flex-col justify-start overflow-hidden">
                  <p
                    class="text-[11px] font-extrabold truncate leading-tight"
                    :style="{ color: colorPersistida(act).text }"
                  >{{ act._disciplina_nombre }}</p>
                  <p
                    class="text-[9px] font-semibold truncate leading-tight mt-px"
                    :style="{ color: colorPersistida(act).sub }"
                  >{{ act.hora_inicio.slice(0,5) }}–{{ act.hora_fin.slice(0,5) }}</p>
                  <p
                    class="text-[9px] truncate leading-tight opacity-75"
                    :style="{ color: colorPersistida(act).sub }"
                  >{{ act._instructor_nombre }}</p>
                </div>
              </div>

              <!-- Local draft sessions -->
              <div
                v-for="act in borradoresDeDia(dia)"
                :key="`l-${act._srcIdx}`"
                :style="{
                  ...posicionPx(act),
                  position: 'absolute',
                  left: '3px',
                  right: '3px',
                  backgroundColor: colorLocal(act, props.sesionResaltadaIndex === act._srcIdx).bg,
                  borderLeftColor: colorLocal(act, props.sesionResaltadaIndex === act._srcIdx).border,
                  borderStyle: colorLocal(act, props.sesionResaltadaIndex === act._srcIdx).dashed ? 'dashed' : 'solid',
                  ...(props.sesionResaltadaIndex === act._srcIdx ? {
                    boxShadow: `0 0 0 2px ${colorLocal(act, true).border}40`,
                  } : {}),
                }"
                class="rounded-lg border-l-[3px] overflow-hidden transition-all duration-200 cursor-pointer"
              >
                <!-- Dashed top stripe for draft state -->
                <div
                  v-if="props.sesionResaltadaIndex !== act._srcIdx"
                  class="h-0.5 w-full"
                  :style="{
                    background: `repeating-linear-gradient(90deg, ${colorLocal(act, false).border} 0, ${colorLocal(act, false).border} 4px, transparent 4px, transparent 8px)`
                  }"
                />
                <div class="px-2 py-1 flex flex-col justify-start overflow-hidden"
                  :style="{ height: props.sesionResaltadaIndex !== act._srcIdx ? 'calc(100% - 2px)' : '100%' }"
                >
                  <p
                    class="text-[11px] font-extrabold truncate leading-tight"
                    :style="{ color: colorLocal(act, props.sesionResaltadaIndex === act._srcIdx).text }"
                  >{{ act._disciplina_nombre }}</p>
                  <p
                    class="text-[9px] font-semibold truncate leading-tight mt-px"
                    :style="{ color: colorLocal(act, props.sesionResaltadaIndex === act._srcIdx).sub }"
                  >{{ act.hora_inicio.slice(0,5) }}–{{ act.hora_fin.slice(0,5) }}</p>
                  <p
                    class="text-[9px] truncate leading-tight opacity-75"
                    :style="{ color: colorLocal(act, props.sesionResaltadaIndex === act._srcIdx).sub }"
                  >{{ act._instructor_nombre }}</p>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Legend ── -->
    <div class="flex gap-4 flex-wrap shrink-0">
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-400">
        <span class="w-3.5 h-3.5 rounded-sm inline-block" style="background:#f0fdfa; border-left: 3px solid #00A896;" />
        Abierta · guardada
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-400">
        <span class="w-3.5 h-3.5 rounded-sm inline-block" style="background:#eef2ff; border-left: 3px solid #4361EE;" />
        Inscripción · guardada
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-400">
        <span class="w-3.5 h-3.5 rounded-sm inline-block" style="background:rgba(240,253,250,0.7); border-left: 3px dashed #2dd4bf;" />
        Borrador · abierta
      </span>
      <span class="flex items-center gap-1.5 text-[10px] font-semibold text-slate-400">
        <span class="w-3.5 h-3.5 rounded-sm inline-block" style="background:rgba(238,242,255,0.7); border-left: 3px dashed #818cf8;" />
        Borrador · inscripción
      </span>
    </div>
  </div>
</template>
