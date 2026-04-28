<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useAdminLudotecaStore } from "@/stores/ludoteca/adminLudotecaStore";

import Card     from "primevue/card";
import Select   from "primevue/select";
import DatePicker from "primevue/datepicker";
import Button   from "primevue/button";
import Tag      from "primevue/tag";
import Message  from "primevue/message";
import IconBaby from "@/components/icons/IconBaby.vue";

// Store
const store = useAdminLudotecaStore();

// Filtro de la lista de turnos (hoy / semana)
const filtroTurnos = ref("hoy");

const turnosFiltrados = computed(() => {
    const hoy = new Date().toISOString().split("T")[0];
    if (filtroTurnos.value === "hoy") {
        return store.turnosAsignados.filter(t => t.fecha === hoy);
    }
    return store.turnosAsignados;
});

// Formulario
const form = ref({
    instructor: null,
    fecha:      null,
    horaInicio: null,
    horaFin:    null,
});

const conflictoMsg  = ref(null);
const submitSuccess = ref(false);

const horasInvalidas = computed(() => {
    if (!form.value.horaInicio || !form.value.horaFin) return false;
    return form.value.horaFin <= form.value.horaInicio;
});

const formValido = computed(() =>
    form.value.instructor &&
    form.value.fecha &&
    form.value.horaInicio &&
    form.value.horaFin &&
    !horasInvalidas.value
);

const toDateStr = (d) => {
    if (!d) return null;
    const y  = d.getFullYear();
    const m  = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${dd}`;
};

const toTimeStr = (d) => {
    if (!d) return null;
    const parts = new Intl.DateTimeFormat('es-MX', {
        hour:   '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
        timeZone: 'America/Mexico_City',
    }).formatToParts(d);
    const get = (type) => parts.find(p => p.type === type)?.value ?? '00';
    return `${get('hour')}:${get('minute')}:${get('second')}`;
};

const formatHora = (str) => (str ? str.slice(0, 5) : "—");

const formatFecha = (str) => {
    if (!str) return "—";
    const [y, m, d] = str.split("-");
    return `${d}/${m}/${y}`;
};

const esHoy = (fechaStr) =>
    fechaStr === new Date().toISOString().split("T")[0];

const handleSubmit = async () => {
    conflictoMsg.value  = null;
    submitSuccess.value = false;

    const result = await store.crearTurno({
        id_instructor: form.value.instructor.id_instructor,
        fecha:         toDateStr(form.value.fecha),
        hora_inicio:   toTimeStr(form.value.horaInicio),
        hora_fin:      toTimeStr(form.value.horaFin),
    });

    if (result.success) {
        submitSuccess.value = true;
        form.value.fecha      = null;
        form.value.horaInicio = null;
        form.value.horaFin    = null;
        setTimeout(() => (submitSuccess.value = false), 4000);

    } else if (result.conflicto) {
        conflictoMsg.value =
            `El instructor ${form.value.instructor.nombre_completo} ` +
            `ya tiene una actividad programada en ese horario. ` +
            `Elige otro horario o un instructor diferente.`;
    }
};

//Ciclo de vida
let statsInterval = null;

onMounted(async () => {
    await Promise.all([
        store.fetchStats(),
        store.fetchInstructores(),
        store.fetchTurnos(),
    ]);
    statsInterval = setInterval(() => store.fetchStats(), 60_000);
});

onUnmounted(() => clearInterval(statsInterval));
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans pb-24 md:pb-8">
    <div class="max-w-5xl mx-auto p-4 md:p-8 space-y-6">

      <!-- Encabezado -->
      <div class="flex items-start justify-between pt-2">
        <div class="flex flex-col gap-1">
          <h1 class="text-2xl md:text-3xl font-bold text-surface-900 tracking-tight m-0">Panel Ludoteca</h1>
          <p class="text-surface-500 font-medium text-sm md:text-base m-0">Analíticas en tiempo real y asignación de turnos</p>
        </div>
        <button
          :disabled="store.loading.stats"
          @click="store.fetchStats()"
          title="Actualizar métricas"
          class="bg-white border border-surface-200 rounded-xl p-2.5 cursor-pointer text-surface-500 transition-all hover:bg-surface-100 hover:text-surface-800 disabled:opacity-50 disabled:cursor-default flex items-center"
        >
          <svg
            :class="store.loading.stats ? 'animate-spin' : ''"
            class="w-4 h-4"
            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          >
            <path d="M23 4v6h-6M1 20v-6h6" /><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
          </svg>
        </button>
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Ocupación actual -->
        <div class="bg-white rounded-3xl p-5 flex items-center gap-4 border border-surface-100 shadow-sm hover:shadow-md transition-shadow">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-blue-50 text-blue-600">
            <IconBaby class="w-6 h-6" />
          </div>
          <div class="flex flex-col gap-0.5">
            <span class="text-xs font-semibold text-surface-500 uppercase tracking-widest">Niños dentro ahora</span>
            <span class="text-3xl font-bold text-surface-900 leading-none">
              <template v-if="store.loading.stats">—</template>
              <template v-else>{{ store.stats.ocupacion_actual }}</template>
            </span>
            <span class="text-xs font-medium text-blue-500 mt-1">de {{ store.stats.total_hoy }} registrados hoy</span>
          </div>
        </div>

        <!-- Calificación promedio -->
        <div class="bg-white rounded-3xl p-5 flex items-center gap-4 border border-surface-100 shadow-sm hover:shadow-md transition-shadow">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-amber-50 text-amber-600">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
          </div>
          <div class="flex flex-col gap-0.5">
            <span class="text-xs font-semibold text-surface-500 uppercase tracking-widest">Calificación promedio</span>
            <span class="text-3xl font-bold text-surface-900 leading-none">
              <template v-if="store.loading.stats">—</template>
              <template v-else-if="store.stats.calificacion_promedio !== null">
                {{ store.stats.calificacion_promedio }}
                <span class="text-base font-medium text-surface-400">/ 5</span>
              </template>
              <template v-else>
                <span class="text-lg text-surface-400">Sin datos</span>
              </template>
            </span>
            <span class="text-xs font-medium text-amber-500 mt-1">Encuestas de hoy</span>
          </div>
        </div>

        <!-- Incidencias del día -->
        <div class="bg-white rounded-3xl p-5 flex items-center gap-4 border border-surface-100 shadow-sm hover:shadow-md transition-shadow">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-red-50 text-red-600">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
              <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
          </div>
          <div class="flex flex-col gap-0.5">
            <span class="text-xs font-semibold text-surface-500 uppercase tracking-widest">Entregas con retraso</span>
            <span class="text-3xl font-bold text-surface-900 leading-none">
              <template v-if="store.loading.stats">—</template>
              <template v-else>{{ store.stats.incidencias_dia }}</template>
            </span>
            <span class="text-xs font-medium text-red-400 mt-1">Incidencias registradas hoy</span>
          </div>
        </div>

      </div>

      <!-- Módulo principal: Form + Lista -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">

        <!-- Columna izquierda: Formulario -->
        <div class="bg-white rounded-3xl border border-surface-200 overflow-hidden shadow-sm">
          <div class="px-6 py-5 border-b border-surface-100">
            <h2 class="text-base font-bold text-surface-900 m-0 mb-1">Asignar Turno</h2>
            <p class="text-sm text-surface-400 m-0">Programa un instructor para la ludoteca</p>
          </div>

          <div class="px-6 py-5 flex flex-col gap-4">

            <!-- Alerta éxito -->
            <Transition name="fade">
              <div v-if="submitSuccess" class="flex items-start gap-2.5 px-4 py-3.5 rounded-2xl text-sm font-medium bg-green-50 text-green-700 border border-green-200">
                <svg class="w-4 h-4 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                Turno asignado exitosamente. La lista ya está actualizada.
              </div>
            </Transition>

            <!-- Alerta conflicto -->
            <Transition name="fade">
              <div v-if="conflictoMsg" class="flex items-start gap-2.5 px-4 py-3.5 rounded-2xl text-sm font-medium bg-red-50 text-red-700 border border-red-200">
                <svg class="w-4 h-4 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ conflictoMsg }}
              </div>
            </Transition>

            <!-- Instructor -->
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-surface-600">Instructor habilitado</label>
              <Select
                v-model="form.instructor"
                :options="store.instructoresHabilitados"
                optionLabel="nombre_completo"
                placeholder="Seleccionar instructor..."
                :loading="store.loading.instructores"
                class="w-full"
                @change="conflictoMsg = null"
              />
            </div>

            <!-- Fecha -->
            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-surface-600">Fecha del turno</label>
              <DatePicker
                v-model="form.fecha"
                dateFormat="dd/mm/yy"
                :minDate="new Date()"
                placeholder="Seleccionar fecha..."
                class="w-full"
                showIcon
                :manualInput="false"
              />
            </div>

            <!-- Horas -->
            <div class="grid grid-cols-2 gap-3">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-surface-600">Hora inicio</label>
                <DatePicker
                  v-model="form.horaInicio"
                  timeOnly
                  hourFormat="24"
                  placeholder="00:00"
                  class="w-full"
                  :manualInput="false"
                  @update:modelValue="conflictoMsg = null"
                />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-surface-600">Hora fin</label>
                <DatePicker
                  v-model="form.horaFin"
                  timeOnly
                  hourFormat="24"
                  placeholder="00:00"
                  class="w-full"
                  :manualInput="false"
                  @update:modelValue="conflictoMsg = null"
                />
              </div>
            </div>

            <!-- Validación horas -->
            <Transition name="fade">
              <p v-if="horasInvalidas" class="text-xs text-red-600 flex items-center gap-1 -mt-2">
                La hora de fin debe ser posterior a la hora de inicio.
              </p>
            </Transition>

            <button
              type="button"
              :disabled="!formValido || store.loading.submit"
              @click="handleSubmit"
              class="w-full flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold text-sm text-white bg-primary-600 hover:bg-primary-700 active:scale-95 transition-all shadow-md shadow-primary-700/20 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
            >
              <svg v-if="store.loading.submit" class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
              </svg>
              <i v-else class="pi pi-check"></i>
              {{ store.loading.submit ? 'Asignando...' : 'Asignar turno' }}
            </button>

          </div>
        </div>

        <!-- Columna derecha: Lista de turnos -->
        <div class="bg-white rounded-3xl border border-surface-200 overflow-hidden shadow-sm">
          <div class="px-6 py-5 border-b border-surface-100 flex items-center justify-between flex-wrap gap-3">
            <div>
              <h2 class="text-base font-bold text-surface-900 m-0 mb-1">Turnos Programados</h2>
              <p class="text-sm text-surface-400 m-0">Cobertura de la ludoteca</p>
            </div>
            <!-- Tabs -->
            <div class="flex bg-surface-100 rounded-xl p-1 gap-1">
              <button
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all"
                :class="filtroTurnos === 'hoy'
                  ? 'bg-white text-surface-900 shadow-sm'
                  : 'text-surface-500 hover:text-surface-700'"
                @click="filtroTurnos = 'hoy'"
              >Hoy</button>
              <button
                class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all"
                :class="filtroTurnos === 'semana'
                  ? 'bg-white text-surface-900 shadow-sm'
                  : 'text-surface-500 hover:text-surface-700'"
                @click="filtroTurnos = 'semana'"
              >Esta semana</button>
            </div>
          </div>

          <!-- Loading skeleton -->
          <div v-if="store.loading.turnos" class="p-4 flex flex-col gap-3">
            <div v-for="i in 3" :key="i" class="h-12 bg-surface-100 animate-pulse rounded-2xl" />
          </div>

          <!-- Sin turnos -->
          <div v-else-if="turnosFiltrados.length === 0" class="py-12 px-6 flex flex-col items-center gap-3 text-surface-400 text-center">
            <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <p class="text-sm m-0">No hay turnos programados para este período.</p>
          </div>

          <!-- Lista de turnos -->
          <div v-else class="flex flex-col">
            <div
              v-for="turno in turnosFiltrados"
              :key="turno.id_turno"
              class="flex items-center gap-3.5 px-6 py-3.5 border-b border-surface-50 last:border-b-0 hover:bg-surface-50 transition-colors"
            >
              <!-- Avatar -->
              <div class="w-9 h-9 rounded-full bg-linear-to-br from-blue-100 to-blue-200 text-blue-700 text-sm font-bold flex items-center justify-center shrink-0 uppercase">
                {{ turno.instructor.charAt(0) }}
              </div>

              <!-- Info -->
              <div class="flex flex-col flex-1 min-w-0">
                <span class="text-sm font-semibold text-surface-800 truncate">{{ turno.instructor }}</span>
                <span class="text-xs text-surface-400">{{ formatFecha(turno.fecha) }}</span>
              </div>

              <!-- Horario + badge -->
              <div class="flex flex-col items-end gap-1 shrink-0">
                <span class="text-xs font-semibold text-surface-700 tabular-nums">
                  {{ formatHora(turno.hora_inicio) }} – {{ formatHora(turno.hora_fin) }}
                </span>
                <Tag
                  v-if="esHoy(turno.fecha)"
                  value="Hoy"
                  severity="success"
                  class="text-[0.65rem]"
                />
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </main>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s, transform 0.25s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; transform: translateY(-4px); }

:deep(.p-select),
:deep(.p-datepicker-input),
:deep(.p-inputtext) {
  border-radius: 0.75rem !important;
  font-size: 0.875rem !important;
}

:deep(.p-select),
:deep(.p-datepicker) {
  width: 100%;
}
</style>