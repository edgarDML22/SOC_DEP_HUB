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

// FILTROS LOCALES (Mismo estilo y estética que Reservaciones Manage.vue)
const filters = [
  { id: 'TODAS', label: 'Todas', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>' },
  { id: 'CONFIRMADA', label: 'Confirmadas', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' },
  { id: 'PENDIENTE', label: 'Pendientes', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' },
  { id: 'CANCELADA', label: 'Canceladas', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' },
  { id: 'ESPERA', label: 'En espera', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 2h14M5 22h14M19 2v4a7 7 0 0 1-7 7 7 7 0 0 1-7-7V2M19 22v-4a7 7 0 0 0-7-7 7 7 0 0 0-7 7v4"/></svg>' },
  { id: 'NO_SHOW', label: 'No Shows', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>' }
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

function calcularMinutosRestantes(fecha, hora) {
  if (!fecha || !hora) return 9999
  const ahoraStr = new Date().toLocaleString("en-US", { timeZone: "America/Mexico_City" });
  const ahoraMexico = new Date(ahoraStr);
  const fechaHoraSesion = new Date(`${fecha}T${hora}`);
  return (fechaHoraSesion - ahoraMexico) / 60000;
}

// Inicia el flujo de cancelación con SweetAlert2
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
      customClass: {
          popup: 'swal-border-radius',
          confirmButton: 'btn-delete-confirm',
          cancelButton: 'btn-cancel',
      }
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

const estatusBadge = {
  CONFIRMADA: { bg: 'bg-emerald-55/70 border border-emerald-200', text: 'text-emerald-800', label: 'Confirmada' },
  PENDIENTE:  { bg: 'bg-blue-55/70 border border-blue-200',     text: 'text-blue-800',    label: 'Pendiente' },
  CANCELADA:  { bg: 'bg-slate-100 border border-slate-200',         text: 'text-slate-500',    label: 'Cancelada' },
  NO_SHOW:    { bg: 'bg-red-55/70 border border-red-200',       text: 'text-red-800',     label: 'No Show (Penalizado)' },
  FALTA:      { bg: 'bg-red-55/70 border border-red-200',       text: 'text-red-800',     label: 'No Show (Penalizado)' },
  LISTA:      { bg: 'bg-blue-100/70 border border-blue-200',    text: 'text-blue-800',    label: 'En Lista' },
  ESPERA:     { bg: 'bg-amber-55/70 border border-amber-200',    text: 'text-amber-800',   label: 'Lista de espera' },
}

function getBadge(estatus) {
  return estatusBadge[estatus] ?? { bg: 'bg-slate-100 border border-slate-200', text: 'text-slate-550', label: estatus }
}

const cancelables = ['CONFIRMADA', 'PENDIENTE', 'LISTA', 'ESPERA']

const inscripcionesVisibles = computed(() => {
  const ahoraStr = new Date().toLocaleString("en-US", { timeZone: "America/Mexico_City" })
  const ahoraMexico = new Date(ahoraStr)

  // 1. Filtrar las que ya pasaron
  const filtradas = store.misInscripciones.filter(i => {
    if (!i.fecha_sesion) return true
    const horaFinRef = i.hora_fin || i.hora_inicio || '23:59:59'
    const fechaHoraSesion = new Date(`${i.fecha_sesion}T${horaFinRef}`)
    return fechaHoraSesion >= ahoraMexico
  })

  // 2. Ordenar: las no canceladas primero (de más próxima a más lejana), canceladas al fondo
  return [...filtradas].sort((a, b) => {
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

const inscripcionesFiltradas = computed(() => {
  // Solo se muestran las inscripciones del socio titular (Yo)
  let resultado = inscripcionesVisibles.value.filter(i => i.tipo_usuario === 'socio_titular')

  // Filtrar por estatus seleccionado
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
      <div v-else-if="store.errorInscripciones" class="bg-red-50 border border-red-200 rounded-3xl p-6 flex items-center gap-4 shadow-sm">
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <p class="text-sm font-bold text-red-800">{{ store.errorInscripciones }}</p>
      </div>

      <!-- Filtros por Estatus de Inscripción -->
      <div v-if="!store.loadingInscripciones && !store.errorInscripciones" class="flex flex-col gap-3 mb-6 pt-2">
        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">Filtrar por Estado</label>
        
        <!-- FILTROS PILL (scroll horizontal) — Mismo estilo que Reservaciones Manage.vue -->
        <div class="flex gap-2 overflow-x-auto scrollbar-none pb-1 -mx-1 px-1 justify-start md:justify-center">
          <button
            v-for="filter in filters"
            :key="filter.id"
            @click="activeStatusFilter = filter.id"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap transition-all focus:outline-none shrink-0 border cursor-pointer"
            :class="activeStatusFilter === filter.id
              ? 'bg-primary-600 text-white border-primary-600 shadow-md shadow-primary-200'
              : 'bg-white text-surface-600 border-surface-200 hover:border-primary-300 hover:text-primary-700 hover:bg-primary-50'"
          >
            <span v-html="filter.icon" class="[&>svg]:w-3.5 [&>svg]:h-3.5 shrink-0"></span>
            {{ filter.label }}
          </button>
        </div>
      </div>
 
      <!-- Empty state general -->
      <div
        v-if="!store.loadingInscripciones && !store.errorInscripciones && store.misInscripciones.length === 0"
        class="flex flex-col items-center py-20 bg-white border border-slate-200 rounded-3xl shadow-sm animate-scale-in"
      >
        <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center text-blue-600 mb-4 border border-blue-100">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
            <line x1="16" x2="16" y1="2" y2="6"/>
            <line x1="8" x2="8" y1="2" y2="6"/>
            <line x1="3" x2="21" y1="10" y2="10"/>
            <path d="m9 16 2 2 4-4"/>
          </svg>
        </div>
        <p class="text-slate-900 font-extrabold text-xl">Sin inscripciones registradas</p>
        <p class="text-slate-500 font-medium text-sm text-center max-w-xs mt-1.5 leading-relaxed">
          Aquí verás el historial de todas tus clases y actividades inscritas.
        </p>
      </div>
 
      <!-- Empty state filtrado (Si hay inscripciones, pero no coinciden con la pestaña o buscador) -->
      <div
        v-else-if="!store.loadingInscripciones && !store.errorInscripciones && inscripcionesFiltradas.length === 0"
        class="flex flex-col items-center py-20 bg-white border border-slate-200 rounded-[32px] shadow-sm animate-scale-in"
      >
        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 mb-4 border border-slate-200">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
        </div>
        <p class="text-slate-900 font-extrabold text-xl">Sin coincidencias</p>
        <p class="text-slate-500 font-medium text-sm text-center max-w-xs mt-1.5 leading-relaxed">
          No encontramos inscripciones registradas con este estado de filtrado.
        </p>
      </div>

      <div v-else-if="!store.loadingInscripciones && !store.errorInscripciones" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="inscripcion in inscripcionesFiltradas"
          :key="inscripcion.id_inscripcion"
          class="p-1 bg-slate-100/70 border border-slate-200/50 rounded-[28px] shadow-sm hover:shadow-xl hover:border-blue-200/60 transition-all duration-300 ease-[cubic-bezier(0.32,0.72,0,1)] hover:-translate-y-1 group/card flex flex-col h-full relative overflow-hidden"
        >
          <!-- Franja lateral de color según estatus, redondeada en sus extremos -->
          <div
            class="absolute left-1 top-4 bottom-4 w-1.5 rounded-full shrink-0 z-10"
            :class="{
              'bg-emerald-500': inscripcion.estatus_inscripcion === 'CONFIRMADA',
              'bg-blue-400':    inscripcion.estatus_inscripcion === 'PENDIENTE',
              'bg-slate-200':   inscripcion.estatus_inscripcion === 'CANCELADA',
              'bg-red-500':     inscripcion.estatus_inscripcion === 'NO_SHOW' || inscripcion.estatus_inscripcion === 'FALTA',
              'bg-blue-500':    inscripcion.estatus_inscripcion === 'LISTA',
              'bg-amber-400':   inscripcion.estatus_inscripcion === 'ESPERA',
            }"
          />

          <div class="bg-white rounded-[24px] overflow-hidden flex flex-col flex-1 h-full pl-3.5 pr-5 py-5 md:py-6 gap-4">
            
            <!-- Contenido -->
            <div class="flex flex-col gap-4 h-full justify-between">
              
              <!-- Info Principal -->
              <div class="space-y-3.5 min-w-0">
                <div class="flex items-start justify-between gap-4 w-full">
                  <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <div class="w-8.5 h-8.5 rounded-xl bg-blue-50/60 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100/30">
                      <DisciplineIcon :name="inscripcion.disciplina" class="w-5 h-5 fill-current" />
                    </div>
                    <h3 class="text-base md:text-lg font-bold text-slate-800 m-0 truncate">
                      {{ inscripcion.nombre_actividad }}
                    </h3>
                  </div>
                  
                  <!-- Estatus Badge -->
                  <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[9px] font-extrabold border tracking-wider uppercase shrink-0"
                    :class="[getBadge(inscripcion.estatus_inscripcion).bg, getBadge(inscripcion.estatus_inscripcion).text]"
                  >
                    <span v-if="inscripcion.estatus_inscripcion === 'CONFIRMADA'" class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                    <span v-else-if="['PENDIENTE', 'LISTA'].includes(inscripcion.estatus_inscripcion)" class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse shrink-0"></span>
                    <span v-else-if="inscripcion.estatus_inscripcion === 'ESPERA'" class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse shrink-0"></span>
                    {{ getBadge(inscripcion.estatus_inscripcion).label }}
                  </span>
                </div>

                <!-- Badges de tipo de clase y participante -->
                <div class="flex items-center gap-1.5 flex-wrap">
                  <!-- Badge Abierta/Cerrada -->
                  <span 
                    class="text-[9px] font-black uppercase px-2 py-0.5 rounded border"
                    :class="inscripcion.tipo_clase === 'Cerrada'
                      ? 'bg-violet-50 border-violet-200 text-violet-850'
                      : 'bg-teal-50 border-teal-200 text-teal-850'"
                  >
                    {{ inscripcion.tipo_clase }}
                  </span>

                  <!-- Badge Participante si no es el socio principal -->
                  <span 
                    v-if="inscripcion.tipo_usuario === 'miembro_familiar' && inscripcion.familiar"
                    class="text-[9px] font-black uppercase px-2 py-0.5 rounded border bg-blue-50 border-blue-200 text-blue-800"
                  >
                    Familiar: {{ inscripcion.familiar.nombre }}
                  </span>
                  <span 
                    v-else-if="inscripcion.tipo_usuario === 'invitado' && inscripcion.invitado"
                    class="text-[9px] font-black uppercase px-2 py-0.5 rounded border bg-indigo-50 border-indigo-200 text-indigo-800"
                  >
                    Invitado: {{ inscripcion.invitado.nombre }}
                  </span>
                </div>

                <!-- Info con cajas de iconos premium -->
                <div class="flex flex-col gap-2 min-w-0 pt-1.5">
                  <!-- Fecha -->
                  <div class="flex items-center gap-3 text-slate-700 min-w-0">
                    <div class="w-7.5 h-7.5 rounded-lg bg-blue-50/60 border border-blue-100/30 text-blue-600 flex items-center justify-center shrink-0">
                      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                    <div class="flex flex-col min-w-0">
                      <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider leading-none mb-0.5">Fecha</span>
                      <span class="text-xs font-bold text-slate-800 truncate">{{ formatFecha(inscripcion.fecha_sesion) }}</span>
                    </div>
                  </div>

                  <!-- Hora -->
                  <div class="flex items-center gap-3 text-slate-700 min-w-0">
                    <div class="w-7.5 h-7.5 rounded-lg bg-blue-50/60 border border-blue-100/30 text-blue-600 flex items-center justify-center shrink-0">
                      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                      </svg>
                    </div>
                    <div class="flex flex-col min-w-0">
                      <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider leading-none mb-0.5">Horario</span>
                      <span class="text-xs font-bold text-slate-800 truncate tabular-nums">{{ inscripcion.hora_inicio }} – {{ inscripcion.hora_fin }}</span>
                    </div>
                  </div>

                  <!-- Espacio -->
                  <div v-if="inscripcion.espacio" class="flex items-center gap-3 text-slate-700 min-w-0">
                    <div class="w-7.5 h-7.5 rounded-lg bg-blue-50/60 border border-blue-100/30 text-blue-600 flex items-center justify-center shrink-0">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                        <circle cx="12" cy="10" r="3" />
                      </svg>
                    </div>
                    <div class="flex flex-col min-w-0">
                      <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider leading-none mb-0.5">Espacio</span>
                      <span class="text-xs font-semibold text-slate-650 truncate">{{ inscripcion.espacio }}</span>
                    </div>
                  </div>

                  <!-- Instructor -->
                  <div v-if="inscripcion.instructor" class="flex items-center gap-3 text-slate-700 min-w-0">
                    <div class="w-7.5 h-7.5 rounded-lg bg-blue-50/60 border border-blue-100/30 text-blue-600 flex items-center justify-center shrink-0">
                      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    </div>
                    <div class="flex flex-col min-w-0">
                      <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider leading-none mb-0.5">Instructor</span>
                      <span class="text-xs font-semibold text-slate-655 truncate">{{ inscripcion.instructor }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Estatus & Acciones en el pie de tarjeta -->
              <div class="flex items-center justify-end gap-2 shrink-0 border-t border-slate-100 pt-3">
                <!-- Botón X: Cancelar inscripción -->
                <button
                  v-if="cancelables.includes(inscripcion.estatus_inscripcion)"
                  :id="`btn-cancelar-inscripcion-${inscripcion.id_inscripcion}`"
                  @click="selectedSesionCancelacion = inscripcion"
                  class="w-9 h-9 bg-red-50 hover:bg-red-550 text-red-500 hover:text-white rounded-xl flex items-center justify-center transition-all shadow-sm focus:outline-none shrink-0 cursor-pointer border-none"
                  title="Cancelar cupo"
                >
                  <svg class="w-4.5 h-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>

                <!-- Botón Ver Inscritos (Lectura de Participantes) -->
                <button
                  @click="selectedSesionVerInscritos = inscripcion"
                  class="w-9 h-9 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm focus:outline-none cursor-pointer border-none"
                  title="Ver personas inscritas"
                >
                  <svg xmlns="http://www.w3.org/2000/svg"
                      class="w-4.5 h-4.5" viewBox="0 0 24 24"
                      fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                      stroke-linejoin="round">
                      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="11" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                  </svg>
                </button>

                <!-- Botón Ver Detalles (Copiado de Reservaciones) -->
                <button
                  @click="openDetails(inscripcion)"
                  class="w-9 h-9 bg-slate-50 text-slate-600 rounded-xl flex items-center justify-center hover:bg-slate-600 hover:text-white transition-all shadow-sm focus:outline-none cursor-pointer border-none"
                  title="Ver detalles de la clase"
                >
                  <svg xmlns="http://www.w3.org/2000/svg"
                      class="w-4 h-4" viewBox="0 0 24 24"
                      fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                      stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                      <path d="M14 2v6h6" />
                      <path d="M16 13H8" />
                      <path d="M16 17H8" />
                      <path d="M10 9H8" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>


    <!-- MODAL DE DETALLES -->
    <Transition name="fade">
      <div v-if="showDetailsModal && selectedInscripcion" class="fixed inset-0 z-100 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-surface-900/60 backdrop-blur-sm" @click="closeDetails"></div>

        <div
          class="relative bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/20 animate-scale-in"
        >
          <!-- Header Modal -->
          <div class="bg-primary-600 p-6 text-white flex justify-between items-center">
            <div>
              <h3 class="text-xl font-bold tracking-tight">Detalles de la Clase</h3>
              <p class="text-primary-100 text-xs font-medium opacity-80">{{ formatFecha(selectedInscripcion.fecha_sesion) }}</p>
            </div>
            <button @click="closeDetails"
              class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors focus:outline-none"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Body Modal -->
          <div class="p-8 flex flex-col gap-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Actividad -->
              <div class="flex flex-col gap-1">
                <span class="text-[11px] font-extrabold text-surface-900 uppercase tracking-widest">Actividad</span>
                <div class="flex items-center gap-2 text-surface-900 font-medium">
                  <div class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                  </div>
                  {{ selectedInscripcion.nombre_actividad }}
                </div>
              </div>
              <!-- Horario -->
              <div class="flex flex-col gap-1">
                <span class="text-[11px] font-extrabold text-surface-900 uppercase tracking-widest">Horario</span>
                <div class="flex items-center gap-2 text-surface-900 font-medium">
                  <div class="w-8 h-8 bg-green-50 text-green-600 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  {{ selectedInscripcion.hora_inicio }} - {{ selectedInscripcion.hora_fin }}
                </div>
              </div>
              <!-- Espacio -->
              <div class="flex flex-col gap-1 md:col-span-2">
                <span class="text-[11px] font-extrabold text-surface-900 uppercase tracking-widest">Espacio</span>
                <div class="flex items-center gap-2 text-surface-900 font-medium">
                  <div class="w-8 h-8 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                      <circle cx="12" cy="10" r="3" />
                    </svg>
                  </div>
                  {{ selectedInscripcion.espacio || 'Por asignar' }}
                </div>
              </div>
              <!-- Instructor -->
              <div class="flex flex-col gap-1 md:col-span-2" v-if="selectedInscripcion.instructor">
                <span class="text-[11px] font-extrabold text-surface-900 uppercase tracking-widest">Instructor</span>
                <div class="flex items-center gap-2 text-surface-900 font-medium">
                  <div class="w-8 h-8 bg-orange-50 text-orange-600 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  {{ selectedInscripcion.instructor }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Modal de Cancelación Selectiva (Lista Dinámica de Inscritos) -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="selectedSesionCancelacion" class="fixed inset-0 z-100 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-surface-900/60 backdrop-blur-sm" @click="selectedSesionCancelacion = null"></div>

          <div
            class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/20 animate-scale-in"
          >
            <!-- Header Modal -->
            <div class="bg-red-600 p-6 text-white flex justify-between items-center">
              <div>
                <h3 class="text-xl font-bold tracking-tight">Gestionar Inscripciones</h3>
                <p class="text-red-100 text-xs font-medium opacity-80">Selecciona a quién deseas cancelar</p>
              </div>
              <button @click="selectedSesionCancelacion = null"
                class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors focus:outline-none cursor-pointer border-none"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body Modal -->
            <div class="p-6 flex flex-col gap-5 text-slate-800">
              <!-- Detalle de la sesión -->
              <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-1">
                <p class="text-sm font-extrabold text-slate-800 leading-tight">
                  {{ selectedSesionCancelacion.nombre_actividad }}
                </p>
                <p class="text-xs text-slate-500 font-semibold">
                  {{ formatFecha(selectedSesionCancelacion.fecha_sesion) }} · {{ selectedSesionCancelacion.hora_inicio }} – {{ selectedSesionCancelacion.hora_fin }}
                </p>
              </div>

              <!-- Lista Dinámica de Inscritos Activos en la Sesión -->
              <div class="space-y-2.5 max-h-[240px] overflow-y-auto pr-1">
                <div v-if="enrolleesForCancel.length === 0" class="text-center py-6 text-slate-450 font-medium text-xs">
                  No hay inscripciones activas para cancelar.
                </div>
                <div v-else
                     v-for="acomp in enrolleesForCancel"
                     :key="acomp.id_inscripcion"
                     class="flex items-center justify-between p-3.5 bg-white rounded-2xl border border-slate-200 hover:border-slate-350 transition-all shadow-sm group"
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <!-- Avatar inicial -->
                    <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold uppercase shrink-0">
                      {{ acomp.tipo_usuario === 'socio_titular' ? 'Yo' : (acomp.tipo_usuario === 'miembro_familiar' ? (acomp.familiar?.nombre?.charAt(0) || 'F') : (acomp.invitado?.nombre?.charAt(0) || 'I')) }}
                    </div>
                    <div class="min-w-0">
                      <span class="block font-bold text-slate-800 text-xs truncate leading-tight">
                        {{ acomp.tipo_usuario === 'socio_titular' ? 'Socio Titular (Mí Mismo)' : (acomp.tipo_usuario === 'miembro_familiar' ? acomp.familiar?.nombre : acomp.invitado?.nombre) }}
                      </span>
                      <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] font-bold border tracking-wide uppercase mt-1"
                            :class="{
                              'bg-green-50 text-green-700 border-green-200': acomp.tipo_usuario === 'socio_titular',
                              'bg-purple-50 text-purple-700 border-purple-200': acomp.tipo_usuario === 'miembro_familiar',
                              'bg-orange-50 text-orange-700 border-orange-200': acomp.tipo_usuario === 'invitado'
                            }">
                        <span v-if="acomp.tipo_usuario === 'socio_titular'" class="w-1 h-1 bg-green-550 rounded-full animate-pulse shrink-0"></span>
                        {{ acomp.tipo_usuario === 'socio_titular' ? 'Titular' : (acomp.tipo_usuario === 'miembro_familiar' ? 'Familiar' : 'Invitado') }}
                      </span>
                    </div>
                  </div>

                  <!-- Botón de papelera rojo interactivo -->
                  <div class="flex items-center gap-2 shrink-0">
                    <button
                      @click.stop="handleCancelarClick(acomp)"
                      :disabled="cancelandoId === acomp.id_inscripcion"
                      class="w-9 h-9 bg-red-50 hover:bg-red-550 text-red-500 hover:text-white rounded-xl flex items-center justify-center transition-all focus:outline-none active:scale-90 border-none cursor-pointer"
                      title="Cancelar inscripción"
                    >
                      <svg v-if="cancelandoId === acomp.id_inscripcion" class="animate-spin w-4.5 h-4.5 text-red-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                      </svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Footer Modal -->
              <div class="border-t border-slate-100 pt-4 flex justify-end">
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

    <!-- Modal de Ver Inscritos (Lista Dinámica sin Eliminar) -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="selectedSesionVerInscritos" class="fixed inset-0 z-100 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-surface-900/60 backdrop-blur-sm" @click="selectedSesionVerInscritos = null"></div>

          <div
            class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/20 animate-scale-in"
          >
            <!-- Header Modal -->
            <div class="bg-blue-600 p-6 text-white flex justify-between items-center">
              <div>
                <h3 class="text-xl font-bold tracking-tight">Participantes Inscritos</h3>
                <p class="text-blue-100 text-xs font-medium opacity-80">Lista de personas registradas en la clase</p>
              </div>
              <button @click="selectedSesionVerInscritos = null"
                class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors focus:outline-none cursor-pointer border-none"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body Modal -->
            <div class="p-6 flex flex-col gap-5 text-slate-800">
              <!-- Detalle de la sesión -->
              <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-1">
                <p class="text-sm font-extrabold text-slate-800 leading-tight">
                  {{ selectedSesionVerInscritos.nombre_actividad }}
                </p>
                <p class="text-xs text-slate-500 font-semibold">
                  {{ formatFecha(selectedSesionVerInscritos.fecha_sesion) }} · {{ selectedSesionVerInscritos.hora_inicio }} – {{ selectedSesionVerInscritos.hora_fin }}
                </p>
              </div>

              <!-- Lista Dinámica de Inscritos Activos en la Sesión (Solo Lectura) -->
              <div class="space-y-2.5 max-h-[240px] overflow-y-auto pr-1">
                <div v-if="enrolleesForView.length === 0" class="text-center py-6 text-slate-450 font-medium text-xs">
                  No hay inscripciones registradas.
                </div>
                <div v-else
                     v-for="acomp in enrolleesForView"
                     :key="acomp.id_inscripcion"
                     class="flex items-center gap-3 p-3.5 bg-white rounded-2xl border border-slate-200 hover:border-slate-350 transition-all shadow-sm"
                >
                  <!-- Avatar inicial -->
                  <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold uppercase shrink-0">
                    {{ acomp.tipo_usuario === 'socio_titular' ? 'Yo' : (acomp.tipo_usuario === 'miembro_familiar' ? (acomp.familiar?.nombre?.charAt(0) || 'F') : (acomp.invitado?.nombre?.charAt(0) || 'I')) }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <span class="block font-bold text-slate-800 text-xs truncate leading-tight">
                      {{ acomp.tipo_usuario === 'socio_titular' ? 'Socio Titular (Mí Mismo)' : (acomp.tipo_usuario === 'miembro_familiar' ? acomp.familiar?.nombre : acomp.invitado?.nombre) }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] font-bold border tracking-wide uppercase mt-1"
                          :class="{
                            'bg-green-50 text-green-700 border-green-200': acomp.tipo_usuario === 'socio_titular',
                            'bg-purple-50 text-purple-700 border-purple-200': acomp.tipo_usuario === 'miembro_familiar',
                            'bg-orange-50 text-orange-700 border-orange-200': acomp.tipo_usuario === 'invitado'
                          }">
                      <span v-if="acomp.tipo_usuario === 'socio_titular'" class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                      {{ acomp.tipo_usuario === 'socio_titular' ? 'Titular' : (acomp.tipo_usuario === 'miembro_familiar' ? 'Familiar' : 'Invitado') }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Footer Modal -->
              <div class="border-t border-slate-100 pt-4 flex justify-end">
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
.scrollbar-none::-webkit-scrollbar {
    display: none;
}

.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
@keyframes scale-in {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}
.animate-scale-in {
  animation: scale-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
