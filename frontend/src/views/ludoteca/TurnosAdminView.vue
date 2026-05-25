<script setup>
import { ref, computed, onMounted, reactive, watch } from "vue";
import { useAdminLudotecaStore } from "@/stores/ludoteca/adminLudotecaStore";
import CollapsibleSection from '@/components/gerente/ui/CollapsibleSection.vue';
import { useAlerts } from '@/composables/useAlerts';

// Componentes reutilizados de Gestión de Sesiones
import CalendarioGridLudoteca from './CalendarioGridLudoteca.vue';
import SelectHora from '@/components/admin/programacion/SelectHora.vue';
import SelectInstructor from '@/components/admin/programacion/SelectInstructor.vue';

// Store
const store = useAdminLudotecaStore();
const { toastSuccess, toastError, confirmDelete } = useAlerts();

// ─── Constantes de día / hora ─────────────────────────────────────────────
const DIAS_SEMANA = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO']
const DIAS_SHORT  = { LUNES: 'L', MARTES: 'M', MIERCOLES: 'X', JUEVES: 'J', VIERNES: 'V', SABADO: 'S', DOMINGO: 'D' }
const DIAS_LABEL  = { LUNES: 'Lun', MARTES: 'Mar', MIERCOLES: 'Mié', JUEVES: 'Jue', VIERNES: 'Vie', SABADO: 'Sáb', DOMINGO: 'Dom' }
const DIAS_INDEX  = { LUNES: 1, MARTES: 2, MIERCOLES: 3, JUEVES: 4, VIERNES: 5, SABADO: 6, DOMINGO: 0 }

const HORAS = Array.from({ length: 17 }, (_, i) => {
  const h = 6 + i
  return `${String(h).padStart(2, '0')}:00`
})

// ─── Sidebar and Modal State ──────────────────────────────────────────────
const panelVisible = ref(true);
const seccionAsignar = ref(true);
const seccionTurnosActivos = ref(true);
const showDetailModal = ref(false);
const selectedTurno = ref(null);

// ─── Avatar helpers (copiados de SelectInstructor) ────────────────────────
const AVATAR_GRADIENTS = [
  'from-blue-500 to-indigo-600',
  'from-violet-500 to-purple-600',
  'from-emerald-500 to-teal-600',
  'from-rose-500 to-pink-600',
  'from-amber-500 to-orange-600',
  'from-cyan-500 to-sky-600',
]
function getInitials(nombre) {
  if (!nombre) return '?'
  return nombre.trim().split(/\s+/).slice(0, 2).map(p => p[0].toUpperCase()).join('')
}
function avatarGradient(nombre) {
  if (!nombre) return AVATAR_GRADIENTS[0]
  const idx = nombre.charCodeAt(0) % AVATAR_GRADIENTS.length
  return AVATAR_GRADIENTS[idx]
}

// ─── Visibilidad de instructores en el calendario ─────────────────────────
const instructoresSeleccionados = reactive({}) // nombre_instructor → boolean
const gruposExpandidos = reactive({}) // nombre_instructor → boolean

function toggleVisibilidadInstructor(nombre) {
  if (instructoresSeleccionados[nombre]) {
    delete instructoresSeleccionados[nombre] // Lo vuelve a ocultar
  } else {
    instructoresSeleccionados[nombre] = true // Lo muestra en el calendario
  }
}

function hayFiltrosActivos() {
  return Object.keys(instructoresSeleccionados).length > 0
}

function instructorVisible(nombre) {
  // Por defecto, ninguno se muestra en el calendario (Default: None)
  return !!instructoresSeleccionados[nombre]
}

function grupoActivo(nombre) {
  // Azul cuando está seleccionado (visible), Gris cuando no (oculto)
  return instructorVisible(nombre)
}

function toggleGrupoInstructor(nombre) {
  gruposExpandidos[nombre] = !gruposExpandidos[nombre]
}

function grupoEstaExpandido(nombre) {
  return !!gruposExpandidos[nombre]
}

function limpiarSeleccionOjitos() {
  // Al limpiar, ocultamos todos de nuevo (Default: None)
  Object.keys(instructoresSeleccionados).forEach(k => delete instructoresSeleccionados[k])
}

// ─── Turnos visibles en el calendario (filtrados por ojito) ───────────────
const turnosVisibles = computed(() => {
  return store.turnosAsignados.filter(t => instructorVisible(t.instructor))
})

// ─── Turnos agrupados por instructor ──────────────────────────────────────
const turnosPorInstructor = computed(() => {
  const map = new Map()
  for (const t of store.turnosAsignados) {
    const key = t.instructor
    if (!map.has(key)) {
      map.set(key, { nombre: key, sesiones: [] })
    }
    map.get(key).sesiones.push(t)
  }
  return [...map.values()].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'))
})

// ─── Formateo de Fechas ───────────────────────────────────────────────────
function formatFechaDia(fechaStr) {
  if (!fechaStr) return '';
  const [y, m, d] = fechaStr.split('-');
  const dateObj = new Date(y, m - 1, d);
  const formatter = new Intl.DateTimeFormat('es-MX', { weekday: 'long', timeZone: 'UTC' });
  const dia = formatter.format(dateObj);
  return dia.charAt(0).toUpperCase() + dia.slice(1);
}

function formatFechaCompleta(fechaStr) {
  if (!fechaStr) return '';
  const [y, m, d] = fechaStr.split('-');
  const dateObj = new Date(y, m - 1, d);
  const formatter = new Intl.DateTimeFormat('es-MX', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' });
  const f = formatter.format(dateObj);
  return f.charAt(0).toUpperCase() + f.slice(1);
}

// ─── Preview del formulario para CalendarioGridLudoteca ───────────────────
const turnoPreviewData = computed(() => {
  if (!form.value.dia || !form.value.hora_inicio || !form.value.hora_fin) return null
  const inst = form.value.id_instructor
    ? store.instructoresHabilitados.find(i => i.id_instructor === form.value.id_instructor)
    : null
  return {
    dia: form.value.dia,
    hora_inicio: form.value.hora_inicio,
    hora_fin: form.value.hora_fin,
    _instructor_nombre: inst?.nombre_completo || '',
  }
})

// ─── Click en celda del calendario → pre-llenar formulario ────────────────
const handleCeldaClick = ({ dia, horaInicio, horaFin }) => {
  panelVisible.value = true;
  seccionAsignar.value = true;
  form.value.dia = dia;
  form.value.hora_inicio = horaInicio;
  form.value.hora_fin = horaFin;
};

// ─── Click en turno del calendario → abrir detalle ────────────────────────
const handleCalendarTurnoClick = (bloque) => {
  const turno = store.turnosAsignados.find(t => String(t.id_turno) === String(bloque.id_turno));
  if (turno) {
    abrirDetalle(turno);
  }
};

const abrirDetalle = (turno) => {
    selectedTurno.value = turno;
    showDetailModal.value = true;
};

const handleEliminarTurno = async () => {
    if (!selectedTurno.value) return;
    
    const instructorNombre = selectedTurno.value.instructor;
    const result = await confirmDelete(
        `¿Eliminar turno de ${instructorNombre}?`,
        'Esta acción removerá el turno del calendario de ludoteca permanentemente.',
        'Sí, eliminar'
    );
    
    if (result.isConfirmed) {
        const res = await store.eliminarTurno(selectedTurno.value.id_turno);
        if (res.success) {
            toastSuccess('Turno eliminado exitosamente.');
            showDetailModal.value = false;
            selectedTurno.value = null;
        } else {
            toastError(res.message || 'No se pudo eliminar el turno.');
        }
    }
};

// ─── Formulario ───────────────────────────────────────────────────────────
const form = ref({ id_instructor: null, dia: '', hora_inicio: '', hora_fin: '' });
const conflictoMsg  = ref(null);
const submitSuccess = ref(false);

const horasInicio = computed(() =>
  form.value.hora_fin ? HORAS.filter(h => h < form.value.hora_fin) : HORAS
)
const horasFin = computed(() =>
  form.value.hora_inicio ? HORAS.filter(h => h > form.value.hora_inicio) : HORAS
)

// ─── Control de colisiones con "Mi agenda" ─────────────────────────────────
const agendaInstructorActual = ref([]); 
const colisionActividad = ref(null);

watch(() => form.value.id_instructor, async (newId) => {
  agendaInstructorActual.value = [];
  if (!newId) {
    verificarColision();
    return;
  }
  const data = await store.fetchInstructorAgenda(newId);
  if (data && data.agenda) {
    agendaInstructorActual.value = data.agenda;
  }
  verificarColision();
});

watch([() => form.value.dia, () => form.value.hora_inicio, () => form.value.hora_fin], () => {
  verificarColision();
});

function verificarColision() {
  colisionActividad.value = null;
  conflictoMsg.value = null; // Limpiar conflictos de backend si los hubiera

  if (!form.value.id_instructor || !form.value.dia || !form.value.hora_inicio || !form.value.hora_fin) {
    return;
  }

  const fechaReq = diaAFecha(form.value.dia);
  const inicioReq = form.value.hora_inicio.substring(0, 5);
  const finReq = form.value.hora_fin.substring(0, 5);

  const agendaDia = agendaInstructorActual.value.find(a => a.fecha === fechaReq);
  if (!agendaDia) return;

  for (const item of agendaDia.items) {
    const inicioItem = item.hora_inicio.substring(0, 5);
    const finItem = item.hora_fin ? item.hora_fin.substring(0, 5) : '23:59';
    
    // Si hay cruce: inicioReq < finItem && finReq > inicioItem
    if (inicioReq < finItem && finReq > inicioItem) {
      const horaItemStr = `${inicioItem} a ${finItem}`;
      colisionActividad.value = `Tiene asignado "${item.titulo}" de ${horaItemStr} en este horario.`;
      break;
    }
  }
}

const formValido = computed(() =>
    form.value.id_instructor && form.value.dia && form.value.hora_inicio && form.value.hora_fin && !colisionActividad.value
);

// ─── Día de la semana → Fecha real (para el backend) ──────────────────────
function diaAFecha(dia) {
  const current = new Date()
  const currentDay = current.getDay() // 0=Dom, 1=Lun...
  const targetDay = DIAS_INDEX[dia]
  let diff = targetDay - currentDay
  if (diff < 0 && targetDay === 0) diff += 7
  const target = new Date(current)
  target.setDate(current.getDate() + diff)
  return new Intl.DateTimeFormat('en-CA', {
    timeZone: 'America/Mexico_City',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  }).format(target)
}

const handleSubmit = async () => {
    conflictoMsg.value  = null;
    submitSuccess.value = false;

    const fecha = diaAFecha(form.value.dia);

    const result = await store.crearTurno({
        id_instructor: form.value.id_instructor,
        fecha:         fecha,
        hora_inicio:   form.value.hora_inicio + ':00',
        hora_fin:      form.value.hora_fin + ':00',
    });

    if (result.success) {
        submitSuccess.value = true;
        toastSuccess('Turno asignado con éxito.');
        form.value.dia        = '';
        form.value.hora_inicio = '';
        form.value.hora_fin    = '';
        setTimeout(() => (submitSuccess.value = false), 4000);
    } else if (result.conflicto) {
        const errorMsg = result.message || 'El instructor ya tiene una actividad programada en ese horario.';
        conflictoMsg.value = errorMsg;
        toastError(errorMsg);
    } else {
        const errorMsg = result.message || 'Error al crear el turno. Intenta de nuevo.';
        conflictoMsg.value = errorMsg;
        toastError(errorMsg);
    }
};

onMounted(async () => {
    await Promise.all([store.fetchInstructores(), store.fetchTurnos()]);
});
</script>

<template>
  <div class="h-screen flex flex-col bg-slate-50 font-sans">
    
    <!-- Header estilo Wizard -->
    <header class="px-6 py-3 border-b border-slate-200 bg-white shrink-0 shadow-sm flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center gap-4">
        
        <!-- Botón volver al dashboard -->
        <router-link
          to="/admin/ludoteca"
          class="flex items-center gap-1.5 px-3 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-800 transition-colors font-bold text-[11px] uppercase tracking-wide border border-transparent shrink-0"
          title="Regresar al Dashboard"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
          <span class="hidden sm:inline">Volver</span>
        </router-link>

        <!-- Botón de panel toggle -->
        <button
          type="button"
          @click="panelVisible = !panelVisible"
          :title="panelVisible ? 'Ocultar panel' : 'Mostrar panel de turnos'"
          :class="[
            'w-10 h-10 rounded-xl flex items-center justify-center transition-colors shadow-xs border border-solid cursor-pointer shrink-0',
            panelVisible
              ? 'bg-primary-600 border-primary-600 hover:bg-primary-700 text-white'
              : 'bg-white border-slate-200 hover:border-primary-300 hover:bg-primary-50 text-primary-600'
          ]"
        >
          <svg v-if="!panelVisible" class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
          <svg v-else class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
          </svg>
        </button>

        <div class="hidden md:block pl-2 border-l border-slate-200">
          <p class="text-[10px] uppercase font-black tracking-widest text-slate-400 leading-none mb-0.5">Ludoteca</p>
          <h1 class="text-base font-black text-slate-800 leading-none">Gestión de Turnos</h1>
        </div>
      </div>

      <!-- Contador de turnos -->
      <div v-if="store.turnosAsignados.length > 0" class="hidden sm:flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 shrink-0">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
        <span class="text-xs font-bold text-slate-600">
          {{ store.turnosAsignados.length }} turno{{ store.turnosAsignados.length !== 1 ? 's' : '' }}
        </span>
      </div>
    </header>

    <!-- Contenido Principal: Sidebar + Calendario -->
    <div class="flex-1 flex min-h-0 overflow-hidden w-full relative">
      
      <!-- SIDEBAR LATERAL (Toggleable) -->
      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="-translate-x-2 opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="-translate-x-2 opacity-0"
      >
        <aside
          v-if="panelVisible"
          class="w-[400px] shrink-0 border-r-2 border-primary-100 bg-white flex flex-col min-h-0 overflow-hidden"
        >
          <div class="flex-1 min-h-0 overflow-y-auto">

            <!-- ═══ Sección: Asignar un Turno ═══ -->
            <CollapsibleSection
              v-model="seccionAsignar"
              title="Asignar un Turno"
              hint="Formulario de registro"
              dot-class="bg-blue-500"
              icon-tone="primary"
            >
              <template #icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
              </template>

              <div class="space-y-3 mt-2">
                <!-- Instructor (SelectInstructor) -->
                <SelectInstructor
                  :model-value="form.id_instructor"
                  @update:model-value="(v) => form.id_instructor = v"
                  :opciones="store.instructoresHabilitados"
                  label="Instructor"
                  placeholder="Seleccionar instructor..."
                />

                <!-- Días de la semana (selección única) -->
                <div>
                  <div class="flex items-center justify-between mb-2.5">
                    <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600">Día de la semana</label>
                    <button
                      v-if="form.dia"
                      type="button"
                      @click="form.dia = ''"
                      class="inline-flex items-center gap-1 text-[10px] font-bold text-slate-400 hover:text-red-500 transition-colors"
                    >
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      Limpiar
                    </button>
                  </div>
                  <div class="flex gap-1.5">
                    <button
                      v-for="dia in DIAS_SEMANA"
                      :key="dia"
                      type="button"
                      @click="form.dia = (form.dia === dia ? '' : dia)"
                      :title="DIAS_LABEL[dia]"
                      :class="[
                        'flex-1 h-10 rounded-xl text-xs font-black transition-all duration-150',
                        form.dia === dia
                          ? 'bg-primary-600 text-white shadow-sm'
                          : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700'
                      ]"
                    >{{ DIAS_SHORT[dia] }}</button>
                  </div>
                </div>

                <!-- Horarios -->
                <div class="grid grid-cols-2 gap-3">
                  <SelectHora
                    :model-value="form.hora_inicio"
                    @update:model-value="(v) => { form.hora_inicio = v; if (form.hora_fin && v >= form.hora_fin) form.hora_fin = '' }"
                    :opciones="horasInicio"
                    label="Hora inicio"
                  />
                  <SelectHora
                    :model-value="form.hora_fin"
                    @update:model-value="(v) => form.hora_fin = v"
                    :opciones="horasFin"
                    label="Hora fin"
                  />
                </div>

                <!-- Conflicto -->
                <div v-if="conflictoMsg || colisionActividad" class="p-3 bg-red-50 text-red-700 rounded-xl text-xs font-bold border border-red-100 flex items-start gap-2 text-left">
                  <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                  </svg>
                  <span class="leading-tight">{{ colisionActividad || conflictoMsg }}</span>
                </div>

                <!-- Botones -->
                <div class="flex flex-col gap-2 mt-2">
                  <button
                    type="button"
                    @click="handleSubmit"
                    :disabled="!formValido || store.loading.submit"
                    :class="[
                      'w-full py-3 rounded-xl text-sm font-black transition-all duration-150 flex items-center justify-center gap-2',
                      formValido && !store.loading.submit
                        ? 'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-800 shadow-sm'
                        : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                    ]"
                  >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>
                    </svg>
                    {{ store.loading.submit ? 'Asignando...' : 'Asignar Turno' }}
                  </button>

                  <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                  >
                    <button
                      v-if="form.id_instructor || form.dia || form.hora_inicio || form.hora_fin"
                      type="button"
                      @click="form.id_instructor = null; form.dia = ''; form.hora_inicio = ''; form.hora_fin = '';"
                      class="w-full py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition-colors flex items-center justify-center gap-1.5"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      Descartar selección
                    </button>
                  </Transition>
                </div>
              </div>
            </CollapsibleSection>

            <!-- ═══ Sección: Turnos Guardados ═══ -->
            <CollapsibleSection
              v-model="seccionTurnosActivos"
              title="Turnos Guardados"
              hint="Cobertura programada"
              :badge="store.turnosAsignados.length || null"
              badge-tone="emerald"
              icon-tone="emerald"
            >
              <template #icon>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </template>
              <template #actions>
                <button
                  v-if="hayFiltrosActivos()"
                  type="button"
                  @click="limpiarSeleccionOjitos"
                  class="flex items-center gap-1 text-[10px] font-bold text-slate-400 hover:text-red-500 transition-colors"
                >
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  Limpiar
                </button>
              </template>

              <div v-if="turnosPorInstructor.length === 0" class="text-center py-6">
                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-2">
                  <svg class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75" />
                  </svg>
                </div>
                <p class="text-xs font-bold text-slate-400">Sin turnos asignados</p>
              </div>

              <div v-else class="space-y-2 mt-2">
                <!-- Grupos por instructor -->
                <div
                  v-for="grupo in turnosPorInstructor"
                  :key="grupo.nombre"
                  class="rounded-xl border overflow-hidden transition-colors"
                  :class="grupoActivo(grupo.nombre) ? 'border-primary-400' : 'border-slate-200'"
                >
                  <!-- Cabecera del grupo -->
                  <div
                    class="flex items-center gap-2 px-2.5 py-2 cursor-pointer select-none transition-colors"
                    :class="grupoActivo(grupo.nombre)
                      ? 'bg-primary-600 hover:bg-primary-700'
                      : 'bg-slate-50 hover:bg-slate-100'"
                    @click="toggleGrupoInstructor(grupo.nombre)"
                  >
                    <!-- Avatar -->
                    <div
                      :class="[
                        'w-7 h-7 rounded-lg bg-linear-to-br flex items-center justify-center text-white text-[10px] font-black shrink-0 shadow-sm',
                        avatarGradient(grupo.nombre)
                      ]"
                    >
                      {{ getInitials(grupo.nombre) }}
                    </div>

                    <span
                      class="flex-1 text-sm font-black truncate"
                      :class="grupoActivo(grupo.nombre) ? 'text-white' : 'text-slate-700'"
                    >{{ grupo.nombre }}</span>

                    <!-- Contador -->
                    <span
                      class="text-[10px] font-black px-1.5 py-0.5 rounded-full tabular-nums"
                      :class="grupoActivo(grupo.nombre) ? 'bg-primary-500 text-white' : 'bg-slate-200 text-slate-600'"
                    >
                      {{ grupo.sesiones.length }}
                    </span>

                    <!-- Ojo: visibilidad en calendario -->
                    <button
                      type="button"
                      @click.stop="toggleVisibilidadInstructor(grupo.nombre)"
                      :title="instructorVisible(grupo.nombre) ? 'Ocultar en el calendario' : 'Mostrar en el calendario'"
                      class="w-6 h-6 rounded-md flex items-center justify-center transition-colors shrink-0"
                      :class="instructorVisible(grupo.nombre)
                        ? 'text-white hover:bg-primary-500'
                        : 'text-slate-400 hover:text-slate-600 hover:bg-slate-200'"
                    >
                      <svg v-if="instructorVisible(grupo.nombre)" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                      </svg>
                    </button>

                    <!-- Chevron expand/collapse -->
                    <svg
                      class="w-3.5 h-3.5 shrink-0 transition-transform duration-150"
                      :class="[
                        grupoEstaExpandido(grupo.nombre) ? 'rotate-90' : '',
                        grupoActivo(grupo.nombre) ? 'text-white/70' : 'text-slate-400'
                      ]"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                  </div>

                  <!-- Sesiones del grupo (turnos) -->
                  <div v-if="grupoEstaExpandido(grupo.nombre)" class="px-2 pb-2 pt-1 space-y-1.5 bg-white">
                    <div
                      v-for="t in grupo.sesiones"
                      :key="t.id_turno"
                      class="flex items-center gap-2 p-2 rounded-lg border transition-all duration-150"
                      :class="grupoActivo(grupo.nombre) ? 'border-emerald-100 bg-emerald-50/40' : 'border-slate-100 bg-slate-50/40 opacity-60'"
                    >
                      <div class="flex-1 min-w-0">
                        <p class="text-[11px] font-black tabular-nums truncate"
                           :class="grupoActivo(grupo.nombre) ? 'text-emerald-700' : 'text-slate-500'">
                          {{ formatFechaDia(t.fecha) }} · {{ t.hora_inicio.substring(0,5) }}–{{ t.hora_fin.substring(0,5) }}
                        </p>
                      </div>
                      <!-- Ojito: ver detalle del turno -->
                      <button
                        type="button"
                        @click.stop="abrirDetalle(t)"
                        class="w-5 h-5 rounded-md flex items-center justify-center transition-colors shrink-0 border bg-white/80 hover:bg-emerald-100 text-slate-400 hover:text-emerald-600 border-slate-100"
                        title="Ver detalle"
                      >
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </CollapsibleSection>

          </div>
        </aside>
      </Transition>

      <!-- CALENDARIO EN REJILLA FULL AREA -->
      <main class="flex-1 min-w-0 overflow-hidden bg-white p-4">
        <CalendarioGridLudoteca
          :turnos="turnosVisibles"
          :turno-preview="turnoPreviewData"
          @click-slot="handleCeldaClick"
          @click-turno="handleCalendarTurnoClick"
        />
      </main>
    </div>

    <!-- MODAL DE DETALLE DEL TURNO -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div
          v-if="showDetailModal && selectedTurno"
          class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-6 bg-slate-900/70 backdrop-blur-xs"
          @click.self="showDetailModal = false"
        >
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-[0.97] translate-y-6"
            enter-to-class="opacity-100 scale-100 translate-y-0"
          >
            <div
              class="bg-white w-full max-w-md rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            >
              <!-- Header del Modal -->
              <div class="bg-linear-to-br from-blue-600 to-indigo-700 text-white px-6 py-5 shrink-0 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-xs flex items-center justify-center shrink-0 shadow-inner text-white font-black">
                    {{ selectedTurno.instructor.charAt(0) }}
                  </div>
                  <div>
                    <h3 class="text-base font-black text-white m-0 tracking-tight truncate leading-tight">{{ selectedTurno.instructor }}</h3>
                    <p class="text-[10px] text-blue-100 font-bold uppercase tracking-widest leading-none mt-1">Instructor de Ludoteca</p>
                  </div>
                </div>
                
                <button
                  type="button"
                  @click="showDetailModal = false"
                  class="w-7 h-7 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors border-none cursor-pointer"
                  title="Cerrar"
                >
                  <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 6L6 18M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Cuerpo del Modal -->
              <div class="p-6 space-y-4 bg-slate-50/50">
                <!-- Fecha -->
                <div class="rounded-xl bg-white border border-solid border-slate-200 p-4 shadow-xs">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Fecha del Turno</p>
                  <div class="flex items-center gap-2 text-slate-800">
                    <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75" />
                    </svg>
                    <span class="text-sm font-extrabold">{{ formatFechaCompleta(selectedTurno.fecha) }}</span>
                  </div>
                </div>

                <!-- Horario -->
                <div class="rounded-xl bg-white border border-solid border-slate-200 p-4 shadow-xs">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Horario Asignado</p>
                  <div class="flex items-center justify-between text-slate-800">
                    <div class="text-center flex-1">
                      <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Inicio</p>
                      <span class="text-xl font-black tracking-tight">{{ selectedTurno.hora_inicio.substring(0,5) }}</span>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                    </svg>
                    <div class="text-center flex-1">
                      <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">Fin</p>
                      <span class="text-xl font-black tracking-tight">{{ selectedTurno.hora_fin.substring(0,5) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer del Modal -->
              <div class="px-6 py-4 bg-white border-t border-slate-100 flex items-center justify-between shrink-0">
                <button
                  @click="showDetailModal = false"
                  class="px-4 py-2 rounded-xl border border-solid border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors cursor-pointer"
                >Cerrar</button>
                
                <button
                  @click="handleEliminarTurno"
                  class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-black transition-colors shadow-xs flex items-center gap-1.5 border-none cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                  </svg>
                  <span>Eliminar Turno</span>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
.scrollbar-thin::-webkit-scrollbar-track {
  background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
