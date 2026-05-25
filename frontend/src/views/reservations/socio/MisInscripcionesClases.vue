<script setup>
import { ref, computed, onMounted } from 'vue'
import { useActividadesStore } from '@/stores/actividadesStore'
import { useFamilyStore } from '@/stores/community/familyStore'
import { useGuestStore } from '@/stores/community/guestStore'
import { useAlerts } from '@/composables/useAlerts'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'

const store = useActividadesStore()
const familyStore = useFamilyStore()
const guestStore = useGuestStore()
const { showLoading, closeLoading, successModal } = useAlerts()

const selectedFamiliarId = ref(null)
const selectedInvitadoId = ref(null)

onMounted(async () => {
  if (!familyStore.miembrosFamiliares || familyStore.miembrosFamiliares.length === 0) {
    await familyStore.fetchMiembrosFamiliares().catch(() => {})
  }
  if (!guestStore.invitados || guestStore.invitados.length === 0) {
    await guestStore.fetchInvitados(true).catch(() => {})
  }
})

const cancelandoId = ref(null)
const toast = ref({ show: false, ok: true, message: '' })

// Modal de detalles
const showDetailsModal = ref(false)
const selectedInscripcion = ref(null)

// Filtros por estatus de inscripción
const activeStatusFilter = ref('TODAS')

// FILTROS LOCALES — sin cambios de datos
const filters = [
  { id: 'TODAS',     label: 'Todas',      icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>' },
  { id: 'CONFIRMADA',label: 'Confirmadas',icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' },
  { id: 'PENDIENTE', label: 'Pendientes', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' },
  { id: 'CANCELADA', label: 'Canceladas', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' },
  { id: 'ESPERA',    label: 'En espera',  icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 2h14M5 22h14M19 2v4a7 7 0 0 1-7 7 7 7 0 0 1-7-7V2M19 22v-4a7 7 0 0 0-7-7 7 7 0 0 0-7 7v4"/></svg>' },
  { id: 'NO_SHOW',   label: 'No Shows',   icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>' }
];

// Modal de ver inscritos y cancelación selectiva
const selectedSesionCancelacion = ref(null)
const selectedSesionVerInscritos = ref(null)

const enrolleesForCancel = computed(() => {
  if (!selectedSesionCancelacion.value) return []
  const targetSesionId = selectedSesionCancelacion.value.id_sesion
  return store.misInscripciones.filter(i =>
    i.id_sesion === targetSesionId &&
    cancelables.includes(i.estatus_inscripcion)
  )
})

const enrolleesForView = computed(() => {
  if (!selectedSesionVerInscritos.value) return []
  const targetSesionId = selectedSesionVerInscritos.value.id_sesion
  return store.misInscripciones.filter(i =>
    i.id_sesion === targetSesionId &&
    ['CONFIRMADA', 'PENDIENTE', 'LISTA', 'ESPERA'].includes(i.estatus_inscripcion)
  )
})

function showToast(ok, message) {
  toast.value = { show: true, ok, message }
  setTimeout(() => { toast.value.show = false }, 3500)
}

function getAhoraMexico() {
  const formatter = new Intl.DateTimeFormat('en-US', {
    timeZone: 'America/Mexico_City',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false
  })
  const parts = formatter.formatToParts(new Date())
  const pv = {}
  parts.forEach(p => { pv[p.type] = p.value })
  return new Date(`${pv.year}-${pv.month}-${pv.day}T${pv.hour}:${pv.minute}:${pv.second}`)
}

function calcularMinutosRestantes(fecha, hora) {
  if (!fecha || !hora) return 9999
  const ahoraMexico = getAhoraMexico();
  const fechaHoraSesion = new Date(`${fecha}T${hora}`);
  return (fechaHoraSesion - ahoraMexico) / 60000;
}

// Flujo de cancelación con SweetAlert2 — sin cambios en lógica
async function handleCancelarClick(inscripcion) {
  const esCerrada = inscripcion.tipo_clase === 'Cerrada' || inscripcion.requiere_inscripcion
  const esInvitado = inscripcion.tipo_usuario === 'invitado'
  const minutos = calcularMinutosRestantes(inscripcion.fecha_sesion, inscripcion.hora_inicio)
  const aplicaPenalizacion = esCerrada && !esInvitado && minutos < 120

  const titulo = aplicaPenalizacion ? 'Cancelación con Penalización' : 'Cancelar Inscripción';
  const mensajeHtml = aplicaPenalizacion
    ? `¡Atención! Faltan menos de 2 horas (o la clase ya inició) para tu clase de <strong>${inscripcion.nombre_actividad}</strong>. Si cancelas ahora, se registrará un <span style="color:#dc2626;font-weight:700;">NO SHOW</span> en tu cuenta. ¿Deseas continuar?`
    : `¿Estás seguro de que deseas cancelar tu cupo para la clase de <strong>${inscripcion.nombre_actividad}</strong>? El cupo quedará libre para otros socios.`;

  const Swal = (await import('sweetalert2')).default;
  const result = await Swal.fire({
      title: titulo,
      html: mensajeHtml,
      showCancelButton: true,
      confirmButtonText: 'Sí, Cancelar',
      cancelButtonText: 'Regresar',
      buttonsStyling: false,
      background: 'var(--p-surface-50)',
      color: 'var(--p-surface-900)',
      customClass: { popup: 'swal-border-radius', confirmButton: 'btn-delete-confirm', cancelButton: 'btn-cancel' }
  });

  if (result.isConfirmed) {
      showLoading('Cancelando cupo...');
      cancelandoId.value = inscripcion.id_inscripcion
      const res = await store.cancelarInscripcion(inscripcion.id_inscripcion)
      closeLoading();
      cancelandoId.value = null

      if (res.ok) {
          if (selectedSesionCancelacion.value && enrolleesForCancel.value.length === 0) {
              selectedSesionCancelacion.value = null
          }
          if (res.penalizacion) {
              await Swal.fire({
                  title: 'No Show registrado',
                  html: 'Tu cupo fue cancelada tardíamente.<br>Se registró un <span style="color:#dc2626;font-weight:700;">NO SHOW</span> en tu cuenta.',
                  icon: 'warning',
                  confirmButtonText: 'Entendido',
                  buttonsStyling: false,
                  background: 'var(--p-surface-50)',
                  color: 'var(--p-surface-900)',
                  customClass: { popup: 'swal-border-radius', confirmButton: 'btn-primary' }
              });
          } else {
              await successModal('Cupo cancelado', 'Tu inscripción ha sido cancelada correctamente.');
          }
      } else {
          await Swal.fire({
              title: 'Error',
              text: res.message || 'No se pudo cancelar el cupo.',
              icon: 'error',
              confirmButtonText: 'Entendido',
              buttonsStyling: false,
              background: 'var(--p-surface-50)',
              color: 'var(--p-surface-900)',
              customClass: { popup: 'swal-border-radius', confirmButton: 'btn-primary' }
          });
      }
  }
}

function openDetails(inscripcion) {
  selectedInscripcion.value = inscripcion
  showDetailsModal.value = true
}

function closeDetails() {
  showDetailsModal.value = false
  selectedInscripcion.value = null
}

function formatFecha(f) {
  if (!f) return '—'
  const [y, m, d] = f.split('-')
  const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']
  return `${d} ${meses[parseInt(m) - 1]} ${y}`
}

// estatusBadge — sin cambios en datos
const estatusBadge = {
  CONFIRMADA: { bg: 'bg-emerald-50 border border-emerald-200', text: 'text-emerald-700', label: 'Confirmada' },
  PENDIENTE:  { bg: 'bg-blue-50 border border-blue-200',       text: 'text-blue-700',    label: 'Pendiente' },
  CANCELADA:  { bg: 'bg-slate-100 border border-slate-200',    text: 'text-slate-500',   label: 'Cancelada' },
  NO_SHOW:    { bg: 'bg-red-50 border border-red-200',         text: 'text-red-700',     label: 'No Show' },
  FALTA:      { bg: 'bg-red-50 border border-red-200',         text: 'text-red-700',     label: 'No Show' },
  LISTA:      { bg: 'bg-blue-50 border border-blue-200',       text: 'text-blue-700',    label: 'En Lista' },
  ESPERA:     { bg: 'bg-amber-50 border border-amber-200',     text: 'text-amber-700',   label: 'Lista de espera' },
}

function getBadge(estatus) {
  return estatusBadge[estatus] ?? { bg: 'bg-slate-100 border border-slate-200', text: 'text-slate-600', label: estatus }
}

const cancelables = ['CONFIRMADA', 'PENDIENTE', 'LISTA', 'ESPERA']

// inscripcionesVisibles — sin cambios en lógica
const inscripcionesVisibles = computed(() => {
  const ahoraMexico = getAhoraMexico()

  const filtradas = store.misInscripciones.filter(i => {
    if (!i.fecha_sesion) return true
    const horaFinRef = i.hora_fin || i.hora_inicio || '23:59:59'
    const fechaHoraSesion = new Date(`${i.fecha_sesion}T${horaFinRef}`)
    return fechaHoraSesion >= ahoraMexico
  })

  // 2. Mapear cada inscripción a su estado efectivo del grupo para esa sesión.
  // Si el socio se canceló a sí mismo pero tiene familiares o invitados activos en esa misma sesión,
  // la tarjeta adopta el estatus del participante activo de mayor rango (CONFIRMADA > PENDIENTE > ESPERA/LISTA).
  const conEstatusEfectivo = filtradas.map(i => {
    const todasDeSesion = store.misInscripciones.filter(m => m.id_sesion === i.id_sesion)
    const activas = todasDeSesion.filter(m => ['CONFIRMADA', 'PENDIENTE', 'LISTA', 'ESPERA'].includes(m.estatus_inscripcion))

    let estatusEfectivo = i.estatus_inscripcion
    if (activas.length > 0) {
      const tieneConfirmada = activas.some(m => m.estatus_inscripcion === 'CONFIRMADA')
      const tienePendiente = activas.some(m => m.estatus_inscripcion === 'PENDIENTE')

      if (tieneConfirmada) {
        estatusEfectivo = 'CONFIRMADA'
      } else if (tienePendiente) {
        estatusEfectivo = 'PENDIENTE'
      } else {
        estatusEfectivo = activas[0].estatus_inscripcion
      }
    }

    return {
      ...i,
      estatus_inscripcion: estatusEfectivo
    }
  })

  // 3. Ordenar: las no canceladas primero (de más próxima a más lejana), canceladas al fondo
  return [...conEstatusEfectivo].sort((a, b) => {
    const aEsCancelada = ['CANCELADA', 'NO_SHOW', 'FALTA'].includes(a.estatus_inscripcion)
    const bEsCancelada = ['CANCELADA', 'NO_SHOW', 'FALTA'].includes(b.estatus_inscripcion)
    if (aEsCancelada && !bEsCancelada) return 1
    if (!aEsCancelada && bEsCancelada) return -1
    const dateA = a.fecha_sesion || '9999-12-31'
    const dateB = b.fecha_sesion || '9999-12-31'
    const timeA = a.hora_inicio || '00:00:00'
    const timeB = b.hora_inicio || '00:00:00'
    return dateA.localeCompare(dateB) || timeA.localeCompare(timeB)
  })
})

// inscripcionesFiltradas — sin cambios en lógica
const inscripcionesFiltradas = computed(() => {
  let resultado = inscripcionesVisibles.value.filter(i => i.tipo_usuario === 'socio_titular')
  if (activeStatusFilter.value !== 'TODAS') {
    if (activeStatusFilter.value === 'ESPERA') {
      resultado = resultado.filter(i => ['ESPERA', 'LISTA'].includes(i.estatus_inscripcion))
    } else if (activeStatusFilter.value === 'NO_SHOW') {
      resultado = resultado.filter(i => ['NO_SHOW', 'FALTA'].includes(i.estatus_inscripcion))
    } else {
      resultado = resultado.filter(i => i.estatus_inscripcion === activeStatusFilter.value)
    }
  }
  return resultado
})

// Acento lateral por estatus — mismo patrón que SocioAgendaView
const cardAccentByStatus = {
  CONFIRMADA: 'border-l-emerald-500',
  PENDIENTE:  'border-l-blue-500',
  CANCELADA:  'border-l-slate-300',
  NO_SHOW:    'border-l-red-500',
  FALTA:      'border-l-red-500',
  LISTA:      'border-l-blue-400',
  ESPERA:     'border-l-amber-400',
}

function getCardAccent(estatus) {
  return cardAccentByStatus[estatus] || 'border-l-slate-300'
}
</script>

<template>
  <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8">
    <div class="max-w-7xl mx-auto">

      <!-- Loading -->
      <div v-if="store.loadingInscripciones" class="flex flex-col items-center py-20">
        <div class="w-12 h-12 rounded-full border-4 border-slate-200 border-t-blue-600 animate-spin mb-4" />
        <p class="text-slate-500 font-semibold text-sm">Cargando tus inscripciones...</p>
      </div>

      <!-- Error -->
      <div v-else-if="store.errorInscripciones" class="bg-red-50 border border-red-200 rounded-2xl p-5 flex items-center gap-4 shadow-sm">
        <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <p class="text-sm font-bold text-red-800">{{ store.errorInscripciones }}</p>
      </div>

      <!-- Filtros por Estatus — activeStatusFilter binding sin cambios -->
      <div v-if="!store.loadingInscripciones && !store.errorInscripciones" class="flex flex-col gap-3 mb-6 pt-2">
        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">Filtrar por Estado</label>
        <div class="flex gap-2 overflow-x-auto scrollbar-none pb-1 -mx-1 px-1 justify-start md:justify-center">
          <button
            v-for="filter in filters"
            :key="filter.id"
            @click="activeStatusFilter = filter.id"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-all focus:outline-none shrink-0 border cursor-pointer"
            :class="activeStatusFilter === filter.id
              ? 'bg-primary-600 text-white border-primary-600 shadow-sm'
              : 'bg-white text-slate-600 border-slate-200 hover:border-primary-300 hover:text-primary-700 hover:bg-primary-50'"
          >
            <span v-html="filter.icon" class="[&>svg]:w-3.5 [&>svg]:h-3.5 shrink-0"></span>
            {{ filter.label }}
          </button>
        </div>
      </div>

      <!-- Empty state general -->
      <div
        v-if="!store.loadingInscripciones && !store.errorInscripciones && store.misInscripciones.length === 0"
        class="flex flex-col items-center py-20 bg-white border border-slate-200 rounded-3xl shadow-sm"
      >
        <div class="w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 mb-4 border border-slate-200">
          <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
            <line x1="16" x2="16" y1="2" y2="6"/>
            <line x1="8" x2="8" y1="2" y2="6"/>
            <line x1="3" x2="21" y1="10" y2="10"/>
            <path d="m9 16 2 2 4-4"/>
          </svg>
        </div>
        <p class="text-slate-800 font-extrabold text-lg">Sin inscripciones registradas</p>
        <p class="text-slate-400 font-medium text-sm text-center max-w-xs mt-1.5 leading-relaxed">
          Aquí verás el historial de todas tus clases y actividades inscritas.
        </p>
      </div>

      <!-- Empty state filtrado -->
      <div
        v-else-if="!store.loadingInscripciones && !store.errorInscripciones && inscripcionesFiltradas.length === 0"
        class="flex flex-col items-center py-20 bg-white border border-slate-200 rounded-3xl shadow-sm"
      >
        <div class="w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 mb-4 border border-slate-200">
          <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </div>
        <p class="text-slate-800 font-extrabold text-lg">Sin coincidencias</p>
        <p class="text-slate-400 font-medium text-sm text-center max-w-xs mt-1.5 leading-relaxed">
          No encontramos inscripciones con este estado.
        </p>
      </div>

      <!-- Lista de inscripciones — v-for binding sin cambios -->
      <div v-else-if="!store.loadingInscripciones && !store.errorInscripciones" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="inscripcion in inscripcionesFiltradas"
          :key="inscripcion.id_inscripcion"
          class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col overflow-hidden border-l-4"
          :class="getCardAccent(inscripcion.estatus_inscripcion)"
        >
          <!-- Card Header: limpio blanco — título + icono disciplina -->
          <div class="px-5 pt-4 pb-3 flex items-start gap-3">
            <!-- DisciplineIcon — :name binding sin cambios -->
            <div class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center shrink-0 text-slate-500 mt-0.5">
              <DisciplineIcon :name="inscripcion.disciplina" class="w-5 h-5 fill-current" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2">
                {{ inscripcion.nombre_actividad }}
              </h3>
              <!-- Badges tipo clase + participante -->
              <div class="flex items-center gap-1.5 flex-wrap mt-1.5">
                <!-- :class ternario sin cambios -->
                <span
                  class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md border"
                  :class="inscripcion.tipo_clase === 'Cerrada'
                    ? 'bg-violet-50 border-violet-200 text-violet-700'
                    : 'bg-teal-50 border-teal-200 text-teal-700'"
                >
                  {{ inscripcion.tipo_clase }}
                </span>
                <!-- v-if participante — condiciones sin cambios -->
                <span
                  v-if="inscripcion.tipo_usuario === 'miembro_familiar' && inscripcion.familiar"
                  class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md border bg-purple-50 border-purple-200 text-purple-700"
                >
                  Familiar: {{ inscripcion.familiar.nombre }}
                </span>
                <span
                  v-else-if="inscripcion.tipo_usuario === 'invitado' && inscripcion.invitado"
                  class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md border bg-orange-50 border-orange-200 text-orange-700"
                >
                  Invitado: {{ inscripcion.invitado.nombre }}
                </span>
              </div>
            </div>
            <!-- Estatus Badge — getBadge() sin cambios -->
            <span
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold border tracking-wide uppercase shrink-0 mt-0.5"
              :class="[getBadge(inscripcion.estatus_inscripcion).bg, getBadge(inscripcion.estatus_inscripcion).text]"
            >
              <span v-if="inscripcion.estatus_inscripcion === 'CONFIRMADA'" class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
              <span v-else-if="['PENDIENTE','LISTA'].includes(inscripcion.estatus_inscripcion)" class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse shrink-0"></span>
              <span v-else-if="inscripcion.estatus_inscripcion === 'ESPERA'" class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse shrink-0"></span>
              {{ getBadge(inscripcion.estatus_inscripcion).label }}
            </span>
          </div>

          <div class="mx-5 border-t border-slate-100"></div>

          <!-- Body: info con iconos de colores variados, no todos azules -->
          <div class="px-5 py-3.5 flex flex-col gap-2">
            <!-- Fecha -->
            <div class="flex items-center gap-2.5 text-slate-600">
              <div class="w-6 h-6 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500 shrink-0">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <span class="text-xs font-semibold">{{ formatFecha(inscripcion.fecha_sesion) }}</span>
            </div>
            <!-- Hora -->
            <div class="flex items-center gap-2.5 text-slate-600">
              <div class="w-6 h-6 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 shrink-0">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
              </div>
              <span class="text-xs font-semibold tabular-nums">{{ inscripcion.hora_inicio }} – {{ inscripcion.hora_fin }}</span>
            </div>
            <!-- Espacio — v-if sin cambios -->
            <div v-if="inscripcion.espacio" class="flex items-center gap-2.5 text-slate-600">
              <div class="w-6 h-6 rounded-lg bg-violet-50 flex items-center justify-center text-violet-500 shrink-0">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="3" />
                </svg>
              </div>
              <span class="text-xs font-semibold truncate">{{ inscripcion.espacio }}</span>
            </div>
            <!-- Instructor — v-if sin cambios -->
            <div v-if="inscripcion.instructor" class="flex items-center gap-2.5 text-slate-600">
              <div class="w-6 h-6 rounded-lg bg-orange-50 flex items-center justify-center text-orange-400 shrink-0">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <span class="text-xs font-semibold truncate">{{ inscripcion.instructor }}</span>
            </div>
          </div>

          <!-- Acciones — todos los @click / :disabled sin cambios -->
          <div class="px-5 pb-4 pt-2 mt-auto flex items-center justify-end gap-2 border-t border-slate-100">
            <!-- Cancelar inscripción — v-if cancelables sin cambios -->
            <button
              v-if="cancelables.includes(inscripcion.estatus_inscripcion)"
              :id="`btn-cancelar-inscripcion-${inscripcion.id_inscripcion}`"
              @click="selectedSesionCancelacion = inscripcion"
              class="w-8 h-8 bg-red-50 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-500 hover:text-white transition-all focus:outline-none shrink-0 cursor-pointer border border-red-100 hover:border-red-500"
              title="Cancelar cupo"
            >
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            <!-- Ver inscritos — @click sin cambios -->
            <button
              @click="selectedSesionVerInscritos = inscripcion"
              class="w-8 h-8 bg-slate-50 text-slate-500 rounded-xl flex items-center justify-center hover:bg-slate-600 hover:text-white transition-all focus:outline-none cursor-pointer border border-slate-200 hover:border-slate-600"
              title="Ver personas inscritas"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="11" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </button>

            <!-- Ver detalles — openDetails(inscripcion) sin cambios -->
            <button
              @click="openDetails(inscripcion)"
              class="w-8 h-8 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all focus:outline-none cursor-pointer border border-blue-100 hover:border-blue-600"
              title="Ver detalles"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <path d="M14 2v6h6" /><path d="M16 13H8" /><path d="M16 17H8" /><path d="M10 9H8" />
              </svg>
            </button>
          </div>
        </div>
      </div>


    <!-- MODAL DE DETALLES — showDetailsModal / selectedInscripcion / closeDetails sin cambios -->
    <Transition name="fade">
      <div v-if="showDetailsModal && selectedInscripcion" class="fixed inset-0 z-100 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="closeDetails"></div>
        <div class="relative bg-white w-full sm:max-w-lg sm:rounded-3xl rounded-t-3xl shadow-2xl overflow-hidden border border-slate-100 animate-scale-in">
          <!-- Header azul de marca -->
          <div class="bg-blue-600 px-6 pt-6 pb-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-white shrink-0">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-base font-extrabold text-white leading-none">Detalles de la Clase</h3>
              <p class="text-blue-100 text-xs font-medium mt-0.5">{{ formatFecha(selectedInscripcion.fecha_sesion) }}</p>
            </div>
            <button @click="closeDetails"
              class="w-8 h-8 bg-white/15 hover:bg-white/25 rounded-full flex items-center justify-center transition-colors focus:outline-none shrink-0"
            >
              <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <!-- Body Modal -->
          <div class="p-6 flex flex-col gap-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div class="flex flex-col gap-1.5">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Actividad</span>
                <div class="flex items-center gap-2.5 text-slate-900 font-semibold text-sm">
                  <div class="w-8 h-8 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center border border-blue-100 shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                  </div>
                  {{ selectedInscripcion.nombre_actividad }}
                </div>
              </div>
              <div class="flex flex-col gap-1.5">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Horario</span>
                <div class="flex items-center gap-2.5 text-slate-900 font-semibold text-sm">
                  <div class="w-8 h-8 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center border border-emerald-100 shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  </div>
                  {{ selectedInscripcion.hora_inicio }} - {{ selectedInscripcion.hora_fin }}
                </div>
              </div>
              <div class="flex flex-col gap-1.5 sm:col-span-2">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Espacio</span>
                <div class="flex items-center gap-2.5 text-slate-900 font-semibold text-sm">
                  <div class="w-8 h-8 bg-violet-50 text-violet-600 rounded-xl flex items-center justify-center border border-violet-100 shrink-0">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="3" /></svg>
                  </div>
                  {{ selectedInscripcion.espacio || 'Por asignar' }}
                </div>
              </div>
              <!-- v-if instructor sin cambios -->
              <div class="flex flex-col gap-1.5 sm:col-span-2" v-if="selectedInscripcion.instructor">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Instructor</span>
                <div class="flex items-center gap-2.5 text-slate-900 font-semibold text-sm">
                  <div class="w-8 h-8 bg-orange-50 text-orange-500 rounded-xl flex items-center justify-center border border-orange-100 shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                  </div>
                  {{ selectedInscripcion.instructor }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Modal de Cancelación Selectiva — selectedSesionCancelacion / enrolleesForCancel sin cambios -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="selectedSesionCancelacion" class="fixed inset-0 z-100 flex items-end sm:items-center justify-center p-0 sm:p-4">
          <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="selectedSesionCancelacion = null"></div>
          <div class="relative bg-white w-full sm:max-w-md sm:rounded-3xl rounded-t-3xl shadow-2xl overflow-hidden border border-slate-100 animate-scale-in">
            <!-- Header azul de marca -->
            <div class="bg-blue-600 px-6 pt-6 pb-5 flex items-center gap-4">
              <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-white shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-base font-extrabold text-white leading-none">Gestionar Inscripciones</h3>
                <p class="text-blue-100 text-xs font-medium mt-0.5">Selecciona a quién deseas cancelar</p>
              </div>
              <button @click="selectedSesionCancelacion = null"
                class="w-8 h-8 bg-white/15 hover:bg-white/25 rounded-full flex items-center justify-center transition-colors focus:outline-none shrink-0 cursor-pointer"
              >
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="p-6 flex flex-col gap-4 text-slate-800">
              <!-- Info sesión -->
              <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-1">
                <p class="text-sm font-bold text-slate-900 leading-tight">{{ selectedSesionCancelacion.nombre_actividad }}</p>
                <p class="text-xs text-slate-400 font-semibold">
                  {{ formatFecha(selectedSesionCancelacion.fecha_sesion) }} · {{ selectedSesionCancelacion.hora_inicio }} – {{ selectedSesionCancelacion.hora_fin }}
                </p>
              </div>
              <!-- Lista inscritos activos — enrolleesForCancel / acomp binding sin cambios -->
              <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                <div v-if="enrolleesForCancel.length === 0" class="text-center py-6 text-slate-400 font-medium text-xs">
                  No hay inscripciones activas para cancelar.
                </div>
                <div v-else
                     v-for="acomp in enrolleesForCancel"
                     :key="acomp.id_inscripcion"
                     class="flex items-center justify-between p-3 bg-white rounded-xl border border-slate-200 hover:border-slate-300 transition-all shadow-sm"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold uppercase shrink-0">
                      {{ acomp.tipo_usuario === 'socio_titular' ? 'Yo' : (acomp.tipo_usuario === 'miembro_familiar' ? (acomp.familiar?.nombre?.charAt(0) || 'F') : (acomp.invitado?.nombre?.charAt(0) || 'I')) }}
                    </div>
                    <div class="min-w-0">
                      <span class="block font-bold text-slate-800 text-xs truncate leading-tight">
                        {{ acomp.tipo_usuario === 'socio_titular' ? 'Socio Titular (Mí Mismo)' : (acomp.tipo_usuario === 'miembro_familiar' ? acomp.familiar?.nombre : acomp.invitado?.nombre) }}
                      </span>
                      <!-- :class ternario por tipo de usuario — sin cambios -->
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold border tracking-wide uppercase mt-1"
                            :class="{
                              'bg-emerald-50 text-emerald-700 border-emerald-200': acomp.tipo_usuario === 'socio_titular',
                              'bg-purple-50 text-purple-700 border-purple-200': acomp.tipo_usuario === 'miembro_familiar',
                              'bg-orange-50 text-orange-700 border-orange-200': acomp.tipo_usuario === 'invitado'
                            }">
                        {{ acomp.tipo_usuario === 'socio_titular' ? 'Titular' : (acomp.tipo_usuario === 'miembro_familiar' ? 'Familiar' : 'Invitado') }}
                      </span>
                    </div>
                  </div>
                  <!-- Botón papelera — @click.stop / :disabled / cancelandoId sin cambios -->
                  <button
                    @click.stop="handleCancelarClick(acomp)"
                    :disabled="cancelandoId === acomp.id_inscripcion"
                    class="w-8 h-8 bg-red-50 hover:bg-red-500 text-red-500 hover:text-white rounded-xl flex items-center justify-center transition-all focus:outline-none active:scale-90 cursor-pointer border border-red-100 hover:border-red-500"
                  >
                    <svg v-if="cancelandoId === acomp.id_inscripcion" class="animate-spin w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/>
                    </svg>
                  </button>
                </div>
              </div>
              <div class="border-t border-slate-100 pt-3">
                <button
                  @click="selectedSesionCancelacion = null"
                  class="w-full py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm font-bold text-slate-700 hover:bg-slate-100 transition-all duration-200 active:scale-95 cursor-pointer text-center"
                >
                  Cerrar
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Modal Ver Inscritos — selectedSesionVerInscritos / enrolleesForView sin cambios -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="selectedSesionVerInscritos" class="fixed inset-0 z-100 flex items-end sm:items-center justify-center p-0 sm:p-4">
          <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="selectedSesionVerInscritos = null"></div>
          <div class="relative bg-white w-full sm:max-w-md sm:rounded-3xl rounded-t-3xl shadow-2xl overflow-hidden border border-slate-100 animate-scale-in">
            <!-- Header azul de marca -->
            <div class="bg-blue-600 px-6 pt-6 pb-5 flex items-center gap-4">
              <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-white shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="text-base font-extrabold text-white leading-none">Participantes Inscritos</h3>
                <p class="text-blue-100 text-xs font-medium mt-0.5">Lista de personas registradas en la clase</p>
              </div>
              <button @click="selectedSesionVerInscritos = null"
                class="w-8 h-8 bg-white/15 hover:bg-white/25 rounded-full flex items-center justify-center transition-colors focus:outline-none shrink-0 cursor-pointer"
              >
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="p-6 flex flex-col gap-4">
              <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-1">
                <p class="text-sm font-bold text-slate-900 leading-tight">{{ selectedSesionVerInscritos.nombre_actividad }}</p>
                <p class="text-xs text-slate-400 font-semibold">
                  {{ formatFecha(selectedSesionVerInscritos.fecha_sesion) }} · {{ selectedSesionVerInscritos.hora_inicio }} – {{ selectedSesionVerInscritos.hora_fin }}
                </p>
              </div>
              <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                <div v-if="enrolleesForView.length === 0" class="text-center py-6 text-slate-400 font-medium text-xs">
                  No hay inscripciones registradas.
                </div>
                <div v-else
                     v-for="acomp in enrolleesForView"
                     :key="acomp.id_inscripcion"
                     class="flex items-center gap-3 p-4 bg-white rounded-2xl border-2 shadow-sm"
                     :class="{
                       'border-emerald-100': acomp.tipo_usuario === 'socio_titular',
                       'border-purple-100': acomp.tipo_usuario === 'miembro_familiar',
                       'border-orange-100': acomp.tipo_usuario === 'invitado'
                     }"
                >
                  <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-black uppercase shrink-0"
                       :class="{
                         'bg-emerald-600 text-white': acomp.tipo_usuario === 'socio_titular',
                         'bg-purple-500 text-white': acomp.tipo_usuario === 'miembro_familiar',
                         'bg-orange-500 text-white': acomp.tipo_usuario === 'invitado'
                       }">
                    {{ acomp.tipo_usuario === 'socio_titular' ? 'Yo' : (acomp.tipo_usuario === 'miembro_familiar' ? (acomp.familiar?.nombre?.charAt(0) || 'F') : (acomp.invitado?.nombre?.charAt(0) || 'I')) }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <span class="block font-bold text-slate-900 text-sm truncate leading-tight">
                      {{ acomp.tipo_usuario === 'socio_titular' ? 'Socio Titular (Mí Mismo)' : (acomp.tipo_usuario === 'miembro_familiar' ? acomp.familiar?.nombre : acomp.invitado?.nombre) }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold border tracking-wide uppercase mt-1"
                          :class="{
                            'bg-emerald-50 text-emerald-700 border-emerald-200': acomp.tipo_usuario === 'socio_titular',
                            'bg-purple-50 text-purple-700 border-purple-200': acomp.tipo_usuario === 'miembro_familiar',
                            'bg-orange-50 text-orange-700 border-orange-200': acomp.tipo_usuario === 'invitado'
                          }">
                      <span v-if="acomp.tipo_usuario === 'socio_titular'" class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                      {{ acomp.tipo_usuario === 'socio_titular' ? 'Titular' : (acomp.tipo_usuario === 'miembro_familiar' ? 'Familiar' : 'Invitado') }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="border-t border-slate-100 pt-3">
                <button
                  @click="selectedSesionVerInscritos = null"
                  class="w-full py-3 rounded-2xl border border-slate-200 bg-slate-50 text-sm font-bold text-slate-700 hover:bg-slate-100 transition-all duration-200 active:scale-95 cursor-pointer text-center"
                >
                  Cerrar
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</div>
</template>

<style scoped>
.scrollbar-none::-webkit-scrollbar { display: none; }
.scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@keyframes scale-in {
  from { opacity: 0; transform: scale(0.97) translateY(8px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-scale-in { animation: scale-in 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
