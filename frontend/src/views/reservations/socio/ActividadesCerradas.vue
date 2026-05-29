<script setup>
import { computed, ref } from 'vue'
import { useActividadesStore } from '@/stores/actividadesStore'
import { useFamilyStore } from '@/stores/community/familyStore'
import { useGuestStore } from '@/stores/community/guestStore'
import { useAlerts } from '@/composables/useAlerts'

const store = useActividadesStore()
const familyStore = useFamilyStore()
const guestStore = useGuestStore()
const { successModal, errorModal, showLoading, closeLoading } = useAlerts()

const selectedSesionInscripcion = ref(null)

// Selección en el modal — lógica sin cambios
const inscripcionTipo = ref('titular') // 'titular' | 'familiar' | 'invitado'
const seleccionados = ref([])

function toggleSeleccion(id, tipo, nombre) {
  const index = seleccionados.value.findIndex(p => p.id === id && p.tipo === tipo)
  if (index !== -1) {
    seleccionados.value.splice(index, 1)
  } else {
    seleccionados.value.push({ id, tipo, nombre })
  }
}

function isSelected(id, tipo) {
  return seleccionados.value.some(p => p.id === id && p.tipo === tipo)
}

function removeFromCart(id, tipo) {
  const index = seleccionados.value.findIndex(p => p.id === id && p.tipo === tipo)
  if (index !== -1) seleccionados.value.splice(index, 1)
}

// Helpers de detección — sin cambios
function esSocioInscrito(sesionId) {
  return store.misInscripciones.some(i =>
    i.id_sesion === sesionId &&
    i.tipo_usuario === 'socio_titular' &&
    !i.invitado &&
    i.estatus_inscripcion === 'CONFIRMADA'
  )
}

function esFamiliarInscrito(sesionId, familiarId) {
  return store.misInscripciones.some(i =>
    i.id_sesion === sesionId &&
    i.tipo_usuario === 'miembro_familiar' &&
    i.familiar?.id === familiarId &&
    i.estatus_inscripcion === 'CONFIRMADA'
  )
}

function esInvitadoInscrito(sesionId, paseId) {
  return store.misInscripciones.some(i =>
    i.id_sesion === sesionId &&
    i.tipo_usuario === 'invitado' &&
    i.invitado?.id_pase === paseId &&
    i.estatus_inscripcion === 'CONFIRMADA'
  )
}

function isSesionCompletamenteInscrita(sesionId) {
  if (!esSocioInscrito(sesionId)) return false
  for (const f of familyStore.miembrosFamiliares) {
    if (!esFamiliarInscrito(sesionId, f.id_miembro)) return false
  }
  for (const g of guestStore.invitados.filter(g => g.estatus_acceso === 'ACTIVO')) {
    if (!esInvitadoInscrito(sesionId, g.id_pase)) return false
  }
  return true
}

const inscritosIds = computed(() =>
  new Set(store.misInscripciones.map(i => i.id_sesion).filter(Boolean))
)

const isConfirmarDisabled = computed(() => seleccionados.value.length === 0)

function abrirModalInscripcion(sesion) {
  selectedSesionInscripcion.value = sesion
  seleccionados.value = []
  if (!esSocioInscrito(sesion.id_sesion)) {
    inscripcionTipo.value = 'titular'
  } else if (familyStore.miembrosFamiliares.some(f => !esFamiliarInscrito(sesion.id_sesion, f.id_miembro))) {
    inscripcionTipo.value = 'familiar'
  } else {
    inscripcionTipo.value = 'invitado'
  }
}

async function handleConfirmarInscripcion() {
  if (!selectedSesionInscripcion.value) return
  if (seleccionados.value.length === 0) return

  const id_sesion = selectedSesionInscripcion.value.id_sesion
  const participantesAInscribir = [...seleccionados.value]
  selectedSesionInscripcion.value = null
  showLoading('Inscribiendo participantes...')

  let exitos = 0
  let errores = []

  for (const p of participantesAInscribir) {
    const opts = {}
    if (p.tipo === 'familiar') opts.id_miembro_familiar = p.id
    if (p.tipo === 'invitado') opts.id_pase_invitado = p.id
    const res = await store.inscribirse(id_sesion, opts)
    if (res.ok) { exitos++ } else { errores.push(`${p.nombre}: ${res.message}`) }
  }

  closeLoading()

  if (errores.length === 0) {
    await successModal('Inscripciones Completadas', `Se han inscrito con éxito ${exitos} participante(s).`)
  } else if (exitos > 0) {
    const Swal = (await import('sweetalert2')).default;
    await Swal.fire({
      title: 'Inscripción Parcial',
      html: `Inscritos con éxito: <strong>${exitos}</strong>.<br><br>Errores:<br>${errores.map(e => `· ${e}`).join('<br>')}`,
      icon: 'warning', confirmButtonText: 'Entendido', buttonsStyling: false,
      background: 'var(--p-surface-50)', color: 'var(--p-surface-900)',
      customClass: { popup: 'swal-border-radius', confirmButton: 'btn-primary' }
    })
  } else {
    await errorModal('No se pudo completar la inscripción', `Los siguientes participantes tienen un conflicto de horario con otra clase en su agenda:<br><br>${errores.map(e => `· ${e}`).join('<br>')}`)
  }
}

function formatFecha(f) {
  if (!f) return ''
  const [y, m, d] = f.split('-')
  const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']
  return `${d} ${meses[parseInt(m) - 1]} ${y}`
}

const tabLabels = { titular: 'Yo', familiar: 'Familiar', invitado: 'Invitado' }

const ESTATUS_SESION_MAP = {
  DISPONIBLE: { class: 'bg-emerald-50 text-emerald-700 border-emerald-200', label: 'Disponible' },
  LLENO: { class: 'bg-orange-50 text-orange-700 border-orange-200', label: 'Cupo Lleno' },
  EN_CURSO: { class: 'bg-amber-50 text-amber-700 border-amber-200', label: 'En Curso' },
  FINALIZADA: { class: 'bg-blue-50 text-blue-700 border-blue-200', label: 'Finalizada' },
  CANCELADA: { class: 'bg-red-50 text-red-700 border-red-200', label: 'Cancelada' },
  CANCELADA_POR_TORNEO: { class: 'bg-red-50 text-red-700 border-red-200', label: 'Cancelada por Torneo' },
}

function getEstatusSesionBadge(estatus) {
  return ESTATUS_SESION_MAP[estatus] ?? { class: 'bg-slate-50 text-slate-600 border-slate-200', label: estatus || 'Programada' }
}
</script>

<template>
  <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Filtros — v-model bindings sin cambios -->
      <div class="p-5 rounded-2xl border border-slate-200 bg-white shadow-sm grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="relative">
          <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
            <span class="inline-flex items-center gap-1.5">
              <svg class="w-3 h-3 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" /></svg>
              Disciplina
            </span>
          </label>
          <select
            v-model="store.filtroDisciplinaId"
            id="filtro-disciplina-cerrada"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-400 focus:border-violet-400 transition-all appearance-none cursor-pointer"
          >
            <option :value="null">Todas las disciplinas</option>
            <option v-for="d in store.disciplinasDisponibles" :key="d.id" :value="d.id">{{ d.nombre }}</option>
          </select>
          <svg class="pointer-events-none absolute right-3 bottom-3 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </div>

        <div class="relative">
          <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
            <span class="inline-flex items-center gap-1.5">
              <svg class="w-3 h-3 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
              Instructor
            </span>
          </label>
          <select
            v-model="store.filtroInstructorId"
            id="filtro-instructor-cerrada"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-400 focus:border-violet-400 transition-all appearance-none cursor-pointer"
          >
            <option :value="null">Todos los instructores</option>
            <option v-for="i in store.instructoresDisponibles" :key="i.id" :value="i.id">{{ i.nombre }}</option>
          </select>
          <svg class="pointer-events-none absolute right-3 bottom-3 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </div>

        <div class="relative">
          <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
            <span class="inline-flex items-center gap-1.5">
              <svg class="w-3 h-3 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75" /></svg>
              Día de la Semana
            </span>
          </label>
          <select
            v-model="store.filtroDiaSemana"
            id="filtro-dia-cerrada"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-400 focus:border-violet-400 transition-all appearance-none cursor-pointer"
          >
            <option :value="null">Cualquier día</option>
            <option v-for="d in store.diasDisponibles" :key="d" :value="d">{{ d }}</option>
          </select>
          <svg class="pointer-events-none absolute right-3 bottom-3 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </div>

        <div class="flex gap-2 items-end">
          <div class="relative flex-1">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
              <span class="inline-flex items-center gap-1.5">
                <svg class="w-3 h-3 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Hora
              </span>
            </label>
            <select
              v-model="store.filtroHora"
              id="filtro-hora-cerrada"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-400 focus:border-violet-400 transition-all appearance-none cursor-pointer"
            >
              <option value="">Cualquier hora</option>
              <option v-for="h in store.horasDisponibles" :key="h" :value="h">{{ h }}</option>
            </select>
            <svg class="pointer-events-none absolute right-3 bottom-3 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
          </div>
          <!-- v-if / @click store.resetFiltros() sin cambios -->
          <button
            v-if="store.hayFiltrosActivos"
            @click="store.resetFiltros()"
            class="px-4 py-2.5 rounded-xl text-sm font-bold text-red-600 hover:text-white hover:bg-red-500 border border-red-200 hover:border-red-500 transition-all h-[42px] cursor-pointer bg-red-50"
          >
            Limpiar
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="store.loading" class="flex flex-col items-center py-20">
        <div class="w-12 h-12 rounded-full border-4 border-slate-200 border-t-violet-500 animate-spin mb-4" />
        <p class="text-slate-400 font-semibold text-sm">Cargando actividades cerradas...</p>
      </div>

      <!-- Error -->
      <div v-else-if="store.error" class="bg-red-50 border border-red-200 rounded-2xl p-5 flex items-center gap-4 shadow-sm">
        <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <p class="text-sm font-bold text-red-800">{{ store.error }}</p>
      </div>

      <!-- Empty state — store.sesionesTipoCerrada.length sin cambios -->
      <div
        v-else-if="store.sesionesTipoCerrada.length === 0"
        class="flex flex-col items-center py-20 bg-white border border-slate-200 rounded-3xl shadow-sm"
      >
        <div class="w-14 h-14 bg-violet-50 rounded-full flex items-center justify-center text-violet-500 mb-4 border border-violet-100">
          <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
          </svg>
        </div>
        <p class="text-slate-800 font-extrabold text-lg">Sin actividades cerradas</p>
        <p class="text-slate-400 font-medium text-sm text-center max-w-xs mt-1.5 leading-relaxed">
          {{ store.hayFiltrosActivos ? 'No hay resultados con los filtros actuales.' : 'No hay actividades cerradas programadas.' }}
        </p>
        <button
          v-if="store.hayFiltrosActivos"
          @click="store.resetFiltros()"
          class="mt-5 px-5 py-2 rounded-xl text-sm font-bold text-white bg-violet-600 hover:bg-violet-500 transition-all cursor-pointer shadow-sm"
        >
          Limpiar filtros
        </button>
      </div>

      <!-- Grid de sesiones — store.sesionesTipoCerrada / :key sesion.id_sesion sin cambios -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div
          v-for="sesion in store.sesionesTipoCerrada"
          :key="sesion.id_sesion"
          class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden flex flex-col border-l-4 border-l-violet-500"
        >
          <!-- Header blanco limpio -->
          <div class="px-5 pt-4 pb-3 flex items-start justify-between gap-3">
            <h3 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2 flex-1" :title="sesion.nombre_actividad">
              {{ sesion.nombre_actividad }}
            </h3>
            <div class="flex flex-col items-end gap-1.5 shrink-0">
              <!-- esSocioInscrito() reactivo sin cambios -->
              <span v-if="esSocioInscrito(sesion.id_sesion)" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-bold uppercase tracking-wider shrink-0">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                Ya inscrito
              </span>
              <!-- Estatus de la Sesión -->
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold border tracking-wider uppercase shrink-0"
                :class="getEstatusSesionBadge(sesion.estatus_sesion).class">
                {{ getEstatusSesionBadge(sesion.estatus_sesion).label }}
              </span>
            </div>
          </div>

          <div class="mx-5 border-t border-slate-100"></div>

          <!-- Body -->
          <div class="px-5 py-3.5 flex-1 flex flex-col justify-between gap-2.5">
            <div class="space-y-2">
              <div class="flex items-center gap-2 text-slate-600">
                <div class="w-6 h-6 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500 shrink-0">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <span class="text-sm font-semibold">{{ formatFecha(sesion.fecha_sesion) }}</span>
              </div>
              <div class="flex items-center gap-2 text-slate-600">
                <div class="w-6 h-6 rounded-lg bg-violet-50 flex items-center justify-center text-violet-500 shrink-0">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                  </svg>
                </div>
                <span class="text-sm font-semibold tabular-nums">{{ sesion.hora_inicio }} – {{ sesion.hora_fin }}</span>
              </div>
              <div v-if="sesion.instructor?.nombre" class="flex items-center gap-2 text-slate-600">
                <div class="w-6 h-6 rounded-lg bg-orange-50 flex items-center justify-center text-orange-400 shrink-0">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <span class="text-sm font-medium text-slate-500 truncate" :title="sesion.instructor.nombre">{{ sesion.instructor.nombre }}</span>
              </div>
              <div v-if="sesion.espacio" class="flex items-center gap-2 text-slate-600">
                <div class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 shrink-0">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="3" />
                  </svg>
                </div>
                <span class="text-sm font-medium text-slate-500 truncate" :title="sesion.espacio">{{ sesion.espacio }}</span>
              </div>
              <!-- Cupo — sesion.es_cupo_lleno / sesion.disponible sin cambios -->
              <div class="flex items-center gap-2 text-slate-600">
                <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0"
                     :class="sesion.es_cupo_lleno ? 'bg-rose-50 text-rose-500' : 'bg-slate-50 text-slate-400'">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </div>
                <span class="text-sm font-semibold text-slate-500">
                  {{ sesion.cantidad_inscritos }} inscritos
                  <span
                    class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider border"
                    :class="sesion.es_cupo_lleno
                      ? 'bg-rose-50 border-rose-200 text-rose-700'
                      : 'bg-violet-50 border-violet-200 text-violet-700'"
                  >
                    {{ sesion.es_cupo_lleno ? 'Cupo Lleno' : `${sesion.disponible} disponibles` }}
                  </span>
                </span>
              </div>
            </div>

            <!-- Footer -->
            <div class="pt-2">
              <div
                v-if="isSesionCompletamenteInscrita(sesion.id_sesion)"
                class="w-full flex items-center justify-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-2.5 text-xs font-bold"
              >
                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Inscrito
              </div>
              <div
                v-else-if="sesion.estatus_sesion === 'CANCELADA' || sesion.estatus_sesion === 'CANCELADA_POR_TORNEO'"
                class="w-full flex items-center justify-center gap-2 bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-2.5 text-xs font-bold cursor-not-allowed"
              >
                Clase Cancelada
              </div>
              <div
                v-else-if="sesion.estatus_sesion === 'FINALIZADA'"
                class="w-full flex items-center justify-center gap-2 bg-blue-50 border border-blue-200 text-blue-600 rounded-xl px-4 py-2.5 text-xs font-bold cursor-not-allowed"
              >
                Clase Finalizada
              </div>
              <div
                v-else-if="sesion.estatus_sesion === 'EN_CURSO'"
                class="w-full flex items-center justify-center gap-2 bg-amber-50 border border-amber-200 text-amber-600 rounded-xl px-4 py-2.5 text-xs font-bold cursor-not-allowed"
              >
                Clase en Curso
              </div>
              <!-- Cupo lleno state diferenciado -->
              <div
                v-else-if="sesion.es_cupo_lleno || sesion.estatus_sesion === 'LLENO'"
                class="w-full flex items-center justify-center gap-2 bg-rose-50 border border-rose-200 text-rose-600 rounded-xl px-4 py-2.5 text-xs font-bold cursor-not-allowed"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                Cupo agotado
              </div>
              <button
                v-else
                :id="`btn-inscribir-${sesion.id_sesion}`"
                @click="abrirModalInscripcion(sesion)"
                :disabled="store.loadingAccion"
                class="w-full flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-500 text-white rounded-xl px-4 py-2.5 text-xs font-bold transition-all active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed border border-violet-700 focus:outline-none cursor-pointer"
              >
                <svg v-if="store.loadingAccion" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Inscribirse
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal de Inscripción — selectedSesionInscripcion / inscripcionTipo sin cambios -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="selectedSesionInscripcion" class="fixed inset-0 z-99999 flex items-end sm:items-center justify-center p-0 sm:p-4">
          <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="selectedSesionInscripcion = null" />

          <div class="relative w-full sm:max-w-md sm:rounded-3xl rounded-t-3xl border border-slate-200 bg-white shadow-2xl overflow-hidden flex flex-col text-slate-800 max-h-[92vh]">

            <!-- Header azul de marca -->
            <div class="bg-blue-600 px-6 pt-6 pb-5 flex items-center gap-4 shrink-0">
              <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-white shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-base font-extrabold text-white leading-none">Opciones de Inscripción</h3>
                <span class="text-xs text-blue-100 font-medium">Selecciona quién asistirá a la sesión</span>
              </div>
              <button @click="selectedSesionInscripcion = null" class="w-8 h-8 bg-white/15 hover:bg-white/25 rounded-full flex items-center justify-center transition-colors focus:outline-none shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <div class="flex flex-col gap-4 p-6 overflow-y-auto">

              <!-- Info sesión -->
              <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-1">
                <p class="text-sm font-bold text-slate-900 leading-tight">{{ selectedSesionInscripcion.nombre_actividad }}</p>
                <p class="text-xs text-slate-400 font-semibold">
                  {{ selectedSesionInscripcion.fecha_sesion }} · {{ selectedSesionInscripcion.hora_inicio }} – {{ selectedSesionInscripcion.hora_fin }}
                </p>
              </div>

              <!-- Segmented control estilo StepAcompanantes — inscripcionTipo / @click sin cambios -->
              <div class="flex p-1.5 bg-slate-100 rounded-2xl border border-slate-200 shadow-inner">
                <button
                  @click="inscripcionTipo = 'titular'"
                  class="flex-1 py-2.5 px-3 text-sm font-bold text-center rounded-xl transition-all cursor-pointer focus:outline-none"
                  :class="inscripcionTipo === 'titular'
                    ? 'bg-blue-600 text-white font-extrabold shadow-md scale-[1.02]'
                    : 'text-slate-500 font-bold hover:bg-white/60 hover:text-slate-700'"
                >Mí Mismo</button>
                <button
                  @click="inscripcionTipo = 'familiar'"
                  class="flex-1 py-2.5 px-3 text-sm font-bold text-center rounded-xl transition-all cursor-pointer focus:outline-none"
                  :class="inscripcionTipo === 'familiar'
                    ? 'bg-blue-600 text-white font-extrabold shadow-md scale-[1.02]'
                    : 'text-slate-500 font-bold hover:bg-white/60 hover:text-slate-700'"
                >Familiar</button>
                <button
                  @click="inscripcionTipo = 'invitado'"
                  class="flex-1 py-2.5 px-3 text-sm font-bold text-center rounded-xl transition-all cursor-pointer focus:outline-none"
                  :class="inscripcionTipo === 'invitado'
                    ? 'bg-blue-600 text-white font-extrabold shadow-md scale-[1.02]'
                    : 'text-slate-500 font-bold hover:bg-white/60 hover:text-slate-700'"
                >Invitado</button>
              </div>

              <!-- Listas de selección — toda la lógica sin cambios -->
              <div class="min-h-20 flex flex-col justify-center gap-3">

                <!-- Titular -->
                <div v-if="inscripcionTipo === 'titular'" class="space-y-2">
                  <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Seleccionar Socio Titular</label>
                  <div
                    @click="!esSocioInscrito(selectedSesionInscripcion.id_sesion) && toggleSeleccion('titular', 'titular', 'Mí Mismo')"
                    class="flex items-center justify-between p-3.5 bg-white rounded-2xl border-2 transition-all group"
                    :class="[
                      esSocioInscrito(selectedSesionInscripcion.id_sesion)
                        ? 'border-slate-100 bg-slate-50/70 opacity-60 cursor-not-allowed'
                        : isSelected('titular', 'titular')
                          ? 'border-blue-500 bg-blue-50/30 cursor-pointer shadow-sm'
                          : 'border-slate-200 hover:border-slate-300 cursor-pointer'
                    ]"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold uppercase shrink-0"
                           :class="isSelected('titular', 'titular') && !esSocioInscrito(selectedSesionInscripcion.id_sesion) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'">
                        Yo
                      </div>
                      <div>
                        <div class="font-bold text-slate-800 text-xs">Socio Titular (Mí Mismo)</div>
                        <div class="text-[10px] text-slate-400 font-medium mt-0.5">Inscribirte a ti mismo</div>
                      </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                      <span v-if="esSocioInscrito(selectedSesionInscripcion.id_sesion)" class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">
                        Ya inscrito
                      </span>
                      <div v-else class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-colors shrink-0"
                           :class="isSelected('titular', 'titular') ? 'bg-blue-600 border-blue-600' : 'border-slate-300'">
                        <svg v-if="isSelected('titular', 'titular')" class="h-2.5 w-2.5 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Familiar -->
                <div v-else-if="inscripcionTipo === 'familiar'" class="space-y-2">
                  <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Seleccionar Familiar</label>
                  <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    <div v-if="familyStore.miembrosFamiliares.length === 0" class="text-center py-5 text-slate-400 font-medium text-xs border-2 border-dashed border-slate-200 rounded-2xl">
                      No tienes miembros familiares registrados.
                    </div>
                    <div v-else
                         v-for="familiar in familyStore.miembrosFamiliares"
                         :key="familiar.id_miembro"
                         @click="!esFamiliarInscrito(selectedSesionInscripcion.id_sesion, familiar.id_miembro) && toggleSeleccion(familiar.id_miembro, 'familiar', familiar.nombre_completo)"
                         class="flex items-center justify-between p-3 rounded-2xl border-2 transition-all group"
                         :class="[
                           esFamiliarInscrito(selectedSesionInscripcion.id_sesion, familiar.id_miembro)
                             ? 'border-slate-100 bg-slate-50/70 opacity-60 cursor-not-allowed'
                             : isSelected(familiar.id_miembro, 'familiar')
                               ? 'border-blue-500 bg-blue-50/30 cursor-pointer shadow-sm'
                               : 'border-slate-200 hover:border-slate-300 cursor-pointer'
                         ]"
                    >
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold uppercase shrink-0"
                             :class="isSelected(familiar.id_miembro, 'familiar') && !esFamiliarInscrito(selectedSesionInscripcion.id_sesion, familiar.id_miembro) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'">
                          {{ familiar.nombre_completo?.charAt(0) || 'F' }}
                        </div>
                        <div>
                          <div class="font-bold text-slate-800 text-xs">{{ familiar.nombre_completo }}</div>
                          <div class="text-[10px] text-slate-400 font-medium mt-0.5">{{ familiar.parentesco || 'Familiar' }}</div>
                        </div>
                      </div>
                      <div class="flex items-center gap-2 shrink-0">
                        <span v-if="esFamiliarInscrito(selectedSesionInscripcion.id_sesion, familiar.id_miembro)" class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">
                          Ya inscrito
                        </span>
                        <div v-else class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-colors shrink-0"
                             :class="isSelected(familiar.id_miembro, 'familiar') ? 'bg-blue-600 border-blue-600' : 'border-slate-300'">
                          <svg v-if="isSelected(familiar.id_miembro, 'familiar')" class="h-2.5 w-2.5 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Invitado -->
                <div v-else-if="inscripcionTipo === 'invitado'" class="space-y-2">
                  <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Seleccionar Invitado (Con pase activo)</label>
                  <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    <div v-if="guestStore.invitados.filter(g => g.estatus_acceso === 'ACTIVO').length === 0" class="text-center py-5 text-slate-400 font-semibold text-xs border border-dashed border-slate-200 rounded-2xl leading-relaxed">
                      No tienes invitados con pase activo hoy.
                    </div>
                    <div v-else
                         v-for="g in guestStore.invitados.filter(g => g.estatus_acceso === 'ACTIVO')"
                         :key="g.id"
                         @click="!esInvitadoInscrito(selectedSesionInscripcion.id_sesion, g.id_pase) && toggleSeleccion(g.id_pase, 'invitado', g.nombre)"
                         class="flex items-center justify-between p-3 rounded-2xl border-2 transition-all group"
                         :class="[
                           esInvitadoInscrito(selectedSesionInscripcion.id_sesion, g.id_pase)
                             ? 'border-slate-100 bg-slate-50/70 opacity-60 cursor-not-allowed'
                             : isSelected(g.id_pase, 'invitado')
                               ? 'border-blue-500 bg-blue-50/30 cursor-pointer shadow-sm'
                               : 'border-slate-200 hover:border-slate-300 cursor-pointer'
                         ]"
                    >
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold uppercase shrink-0"
                             :class="isSelected(g.id_pase, 'invitado') && !esInvitadoInscrito(selectedSesionInscripcion.id_sesion, g.id_pase) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'">
                          {{ g.nombre?.charAt(0) || 'I' }}
                        </div>
                        <div>
                          <div class="font-bold text-slate-800 text-xs">{{ g.nombre }}</div>
                          <div class="text-[10px] text-slate-400 font-medium mt-0.5">Pase Activo</div>
                        </div>
                      </div>
                      <div class="flex items-center gap-2 shrink-0">
                        <span v-if="esInvitadoInscrito(selectedSesionInscripcion.id_sesion, g.id_pase)" class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">
                          Ya inscrito
                        </span>
                        <div v-else class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-colors shrink-0"
                             :class="isSelected(g.id_pase, 'invitado') ? 'bg-blue-600 border-blue-600' : 'border-slate-300'">
                          <svg v-if="isSelected(g.id_pase, 'invitado')" class="h-2.5 w-2.5 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Carrito live — seleccionados reactivo, sin cambios en lógica -->
              <Transition name="cart-fade">
                <div v-if="seleccionados.length > 0" class="bg-white border-2 border-slate-900 rounded-2xl p-4">
                  <div class="flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-slate-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    </svg>
                    <span class="text-xs font-black text-slate-800 uppercase tracking-widest">A inscribir</span>
                    <span class="ml-auto bg-blue-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full">{{ seleccionados.length }}</span>
                  </div>
                  <div class="space-y-2">
                    <div
                      v-for="p in seleccionados"
                      :key="`${p.tipo}-${p.id}`"
                      class="flex items-center gap-2.5"
                    >
                      <div class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-black uppercase shrink-0">
                        {{ p.nombre?.charAt(0) || '?' }}
                      </div>
                      <div class="flex-1 min-w-0">
                        <span class="block text-xs font-bold text-slate-800 truncate">{{ p.nombre }}</span>
                        <span class="text-[9px] font-black uppercase tracking-wider"
                              :class="{
                                'text-emerald-600': p.tipo === 'titular',
                                'text-purple-600': p.tipo === 'familiar',
                                'text-orange-500': p.tipo === 'invitado'
                              }">
                          {{ tabLabels[p.tipo] || p.tipo }}
                        </span>
                      </div>
                      <button
                        @click="removeFromCart(p.id, p.tipo)"
                        class="w-6 h-6 rounded-lg bg-red-50 hover:bg-red-500 text-red-400 hover:text-white flex items-center justify-center transition-all shrink-0 border border-red-100 hover:border-red-500 cursor-pointer"
                        title="Quitar"
                      >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </Transition>

            </div>

            <!-- Botones — handleConfirmarInscripcion / isConfirmarDisabled sin cambios -->
            <div class="flex gap-3 border-t border-slate-100 px-6 py-4 shrink-0 bg-white">
              <button
                @click="selectedSesionInscripcion = null"
                class="flex-1 px-4 py-2.5 rounded-2xl border border-slate-200 bg-slate-50 text-sm font-bold text-slate-700 hover:bg-slate-100 transition-all active:scale-95 cursor-pointer"
              >
                Cancelar
              </button>
              <button
                @click="handleConfirmarInscripcion"
                :disabled="isConfirmarDisabled"
                class="flex-1 px-4 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-sm font-black text-white transition-all active:scale-95 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer flex items-center justify-center gap-1.5 border-none"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Confirmar
              </button>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-active .relative, .modal-fade-leave-active .relative {
  transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-fade-enter-from .relative { transform: scale(0.93) translateY(10px); opacity: 0; }

.cart-fade-enter-active, .cart-fade-leave-active { transition: all 0.2s ease; }
.cart-fade-enter-from, .cart-fade-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
