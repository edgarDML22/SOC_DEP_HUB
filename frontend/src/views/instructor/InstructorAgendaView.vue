<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAgendaStore } from '@/stores/agendaStore';
import { storeToRefs } from 'pinia';
import ModalAsistenciaLectura from '@/components/instructor/ModalAsistenciaLectura.vue';

const router = useRouter();  // kept for back-navigation
const agendaStore = useAgendaStore();
const { itemsInstructor, loadingInstructor, errorInstructor } = storeToRefs(agendaStore);

onMounted(() => {
  agendaStore.fetchInstructorAgenda({ force: true });
});

// ── Tabs ─────────────────────────────────────────────────────────────────────
const activeTab = ref('hoy');

const tabs = [
  {
    key: 'hoy',
    label: 'Hoy',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
  },
  {
    key: 'proximas',
    label: 'Próximas',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>`,
  },
];

// ── Helpers de fecha ─────────────────────────────────────────────────────────
const hoyStr = computed(() => new Date().toLocaleDateString('en-CA'));

const formatHora = (h) => h ? h.substring(0, 5) : '—';

const formatFecha = (iso) => {
  if (!iso) return '—';
  const [y, m, d] = iso.split('-');
  const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  return `${d} ${meses[parseInt(m) - 1]} ${y}`;
};

const formatFechaLarga = (iso) => {
  if (!iso) return '—';
  return new Date(iso + 'T12:00:00').toLocaleDateString('es-MX', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  });
};

// ── Filtrado por tab ─────────────────────────────────────────────────────────
const itemsHoy = computed(() =>
  itemsInstructor.value
    .filter(i => i.fecha === hoyStr.value)
    .slice()
    .sort((a, b) => (a.hora_inicio ?? '').localeCompare(b.hora_inicio ?? ''))
);

const agrupadosProximas = computed(() => {
  const grupos = {};
  itemsInstructor.value.forEach(i => {
    if (!grupos[i.fecha]) grupos[i.fecha] = [];
    grupos[i.fecha].push(i);
  });
  return Object.keys(grupos).sort().map(fecha => ({ fecha, items: grupos[fecha] }));
});

// ── Configuración visual por tipo ────────────────────────────────────────────
const configTipo = {
  CLASE_CERRADA: {
    badgeBg:      'bg-violet-100 text-violet-800 border-violet-200',
    badge:        'CLASE CERRADA',
    btnBg:        'bg-violet-50 text-violet-700 hover:bg-violet-600 hover:text-white border-violet-200 hover:border-violet-600',
    accentBorder: 'border-l-violet-500',
    iconColor:    'text-violet-500',
  },
  CLASE_ABIERTA: {
    badgeBg:      'bg-teal-100 text-teal-800 border-teal-200',
    badge:        'CLASE ABIERTA',
    btnBg:        'bg-teal-50 text-teal-700 hover:bg-teal-600 hover:text-white border-teal-200 hover:border-teal-600',
    accentBorder: 'border-l-teal-500',
    iconColor:    'text-teal-500',
  },
  TORNEO: {
    badgeBg:      'bg-amber-100 text-amber-800 border-amber-200',
    badge:        'TORNEO',
    btnBg:        'bg-amber-50 text-amber-700 hover:bg-amber-600 hover:text-white border-amber-200 hover:border-amber-600',
    accentBorder: 'border-l-amber-500',
    iconColor:    'text-amber-500',
  },
  TURNO_LUDOTECA: {
    badgeBg:      'bg-primary-100 text-primary-800 border-primary-200',
    badge:        'LUDOTECA',
    btnBg:        'bg-primary-50 text-primary-700 hover:bg-primary-600 hover:text-white border-primary-200 hover:border-primary-600',
    accentBorder: 'border-l-primary-500',
    iconColor:    'text-primary-500',
  },
};

const getCfg = (tipo) => configTipo[tipo] ?? configTipo.CLASE_ABIERTA;

// ── Modal de asistencia ───────────────────────────────────────────────────────
const showModalAsistencia = ref(false);
const sesionSeleccionada = ref(null);

const abrirAsistencia = (item) => {
  sesionSeleccionada.value = item;
  showModalAsistencia.value = true;
};

const cerrarAsistencia = () => {
  showModalAsistencia.value = false;
  setTimeout(() => { sesionSeleccionada.value = null; }, 300);
};

</script>

<template>
  <div class="w-full font-sans bg-surface-50 min-h-screen">

    <!-- Encabezado -->
    <div class="max-w-7xl mx-auto px-4 md:px-6 lg:px-8 pt-4 md:pt-6 lg:pt-8 bg-surface-50">
      <button
        @click="router.push('/instructor/home')"
        class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 focus:outline-none w-fit group"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m15 18-6-6 6-6"/>
        </svg>
        Volver
      </button>

      <h2 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 mb-5 tracking-tight">Mi Agenda</h2>

      <!-- Segmented Control -->
      <div class="flex p-1.5 bg-surface-100 rounded-2xl w-full max-w-2xl mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-6">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
          :class="activeTab === tab.key
            ? 'bg-primary-600 text-white font-extrabold rounded-xl shadow-md transform scale-[1.02]'
            : 'text-surface-500 font-bold hover:bg-white/60 hover:text-surface-700 rounded-xl'"
        >
          <span v-html="tab.icon" class="shrink-0"></span>
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Contenido principal -->
    <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8">
      <div class="max-w-7xl mx-auto">

        <!-- Loading -->
        <div v-if="loadingInstructor" class="flex flex-col items-center py-20">
          <div class="w-12 h-12 rounded-full border-4 border-slate-200 border-t-blue-600 animate-spin mb-4" />
          <p class="text-slate-500 font-semibold text-sm">Cargando agenda...</p>
        </div>

        <!-- Error -->
        <div v-else-if="errorInstructor" class="p-6 bg-red-50 border border-red-200 rounded-2xl text-center text-red-700 text-sm font-medium">
          {{ errorInstructor }}
        </div>

        <!-- ── CONTENIDO DE TABS CON TRANSICIÓN ── -->
        <Transition v-else name="tab-fade" mode="out-in">
          <div :key="activeTab">

        <!-- ── TAB HOY ──────────────────────────────────────────────────────── -->
        <div v-if="activeTab === 'hoy'" class="space-y-4">
          <div v-if="itemsHoy.length === 0" class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm">
            <p class="text-surface-900 font-bold text-lg">Día Libre</p>
            <p class="text-surface-500 font-medium text-sm text-center max-w-xs mt-1">
              No tienes sesiones ni encuentros para hoy.
            </p>
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="item in itemsHoy"
              :key="item.id_sesion ?? item.id_encuentro ?? item.titulo + item.hora_inicio"
              class="bg-white rounded-2xl border border-surface-100 border-l-4 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex flex-col group"
              :class="getCfg(item.tipo).accentBorder"
            >
              <!-- Card Header — blanco con título + badge, sin gradiente -->
              <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                <h3 class="font-bold text-surface-900 text-sm leading-snug line-clamp-2 flex-1">
                  {{ item.titulo }}
                </h3>
                <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full border shrink-0" :class="getCfg(item.tipo).badgeBg">
                  {{ getCfg(item.tipo).badge }}
                </span>
              </div>

              <!-- Divider -->
              <div class="mx-5 border-t border-surface-100"></div>

              <!-- Body -->
              <div class="px-5 py-4 flex-1 space-y-2.5">
                <!-- Fecha -->
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 shrink-0" :class="getCfg(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span class="text-sm font-semibold text-surface-700">{{ formatFecha(item.fecha) }}</span>
                </div>

                <!-- Horario -->
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 shrink-0" :class="getCfg(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                  </svg>
                  <span class="text-sm font-semibold tabular-nums text-surface-700">
                    {{ formatHora(item.hora_inicio) }} – {{ formatHora(item.hora_fin) }}
                  </span>
                </div>

                <!-- Espacio -->
                <div v-if="item.espacio" class="flex items-center gap-2">
                  <svg class="w-4 h-4 shrink-0" :class="getCfg(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span class="text-sm font-medium text-surface-500 truncate">{{ item.espacio }}</span>
                </div>

                <!-- Contadores (solo clases) — chips semánticos en línea -->
                <div v-if="item.contadores" class="flex items-center gap-2 pt-1 flex-wrap">
                  <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-surface-100 text-surface-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-surface-400 inline-block"></span>
                    {{ item.contadores.inscritos }} inscritos
                  </span>
                  <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                    {{ item.contadores.asistencia }} asistencia
                  </span>
                  <span v-if="item.tipo === 'CLASE_CERRADA'" class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span>
                    {{ item.contadores.falta }} no show
                  </span>
                </div>

                <!-- Fase torneo -->
                <div v-if="item.tipo === 'TORNEO' && item.fase" class="flex items-center gap-2">
                  <svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                  </svg>
                  <span class="text-sm font-medium text-surface-500">{{ item.fase }}</span>
                </div>
              </div>

              <!-- Footer -->
              <div class="px-4 pb-4 mt-auto border-t border-surface-50 pt-3">
                <router-link
                  v-if="item.tipo === 'TURNO_LUDOTECA'"
                  to="/instructor/ludoteca"
                  class="w-full flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-xs font-bold border transition-all"
                  :class="getCfg(item.tipo).btnBg"
                >
                  <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M9 11.75c-.69 0-1.25.56-1.25 1.25s.56 1.25 1.25 1.25 1.25-.56 1.25-1.25-.56-1.25-1.25-1.25zm6 0c-.69 0-1.25.56-1.25 1.25s.56 1.25 1.25 1.25 1.25-.56 1.25-1.25-.56-1.25-1.25-1.25zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8 0-.29.02-.58.05-.86 2.36-1.05 4.23-2.98 5.21-5.37C11.07 8.33 14.05 10 17.42 10c.78 0 1.53-.09 2.25-.26.21.71.33 1.47.33 2.26 0 4.41-3.59 8-8 8z"/></svg>
                  Ir al Tablero
                </router-link>
                <button
                  v-else
                  @click="item.tipo !== 'TORNEO' ? abrirAsistencia(item) : null"
                  class="w-full flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-xs font-bold border transition-all"
                  :class="getCfg(item.tipo).btnBg"
                >
                  <template v-if="item.tipo === 'TORNEO'">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    Encuentro Torneo
                  </template>
                  <template v-else>
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Lista de Asistencia
                  </template>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ── TAB PRÓXIMAS ────────────────────────────────────────────────── -->
        <div v-else class="space-y-8">
          <div v-if="agrupadosProximas.length === 0" class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm">
            <p class="text-surface-900 font-bold text-lg">Agenda Vacía</p>
            <p class="text-surface-500 font-medium text-sm text-center max-w-xs mt-1">
              No tienes actividades próximas.
            </p>
          </div>

          <div v-else v-for="grupo in agrupadosProximas" :key="grupo.fecha" class="space-y-3">
            <h3 class="text-sm md:text-base font-extrabold capitalize tracking-wide pl-3 border-l-4 border-l-primary-500 text-primary-700">
              {{ formatFechaLarga(grupo.fecha) }}
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              <div
                v-for="item in grupo.items"
                :key="item.id_sesion ?? item.id_encuentro ?? item.titulo + item.hora_inicio"
                class="bg-white rounded-2xl border border-surface-100 border-l-4 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex flex-col group"
                :class="getCfg(item.tipo).accentBorder"
              >
                <!-- Header: título + badge, fondo blanco -->
                <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                  <h3 class="font-bold text-surface-900 text-sm leading-snug line-clamp-2 flex-1">
                    {{ item.titulo }}
                  </h3>
                  <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full border shrink-0" :class="getCfg(item.tipo).badgeBg">
                    {{ getCfg(item.tipo).badge }}
                  </span>
                </div>

                <div class="mx-5 border-t border-surface-100"></div>

                <!-- Body -->
                <div class="px-5 py-4 flex-1 space-y-2.5">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" :class="getCfg(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-semibold text-surface-700">{{ formatFecha(item.fecha) }}</span>
                  </div>

                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" :class="getCfg(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span class="text-sm font-semibold tabular-nums text-surface-700">
                      {{ formatHora(item.hora_inicio) }} – {{ formatHora(item.hora_fin) }}
                    </span>
                  </div>

                  <div v-if="item.espacio" class="flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" :class="getCfg(item.tipo).iconColor" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-sm font-medium text-surface-500 truncate">{{ item.espacio }}</span>
                  </div>

                  <div v-if="item.contadores" class="flex items-center gap-2 pt-1 flex-wrap">
                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-surface-100 text-surface-600">
                      <span class="w-1.5 h-1.5 rounded-full bg-surface-400 inline-block"></span>
                      {{ item.contadores.inscritos }} inscritos
                    </span>
                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                      {{ item.contadores.asistencia }} asistencia
                    </span>
                    <span v-if="item.tipo === 'CLASE_CERRADA'" class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-100">
                      <span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span>
                      {{ item.contadores.falta }} no show
                    </span>
                  </div>

                  <div v-if="item.tipo === 'TORNEO' && item.fase" class="flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    <span class="text-sm font-medium text-surface-500">{{ item.fase }}</span>
                  </div>
                </div>

                <!-- Footer: solo botón de acción (sin QR — vista de solo lectura) -->
                <div v-if="item.tipo !== 'TURNO_LUDOTECA'" class="px-4 pb-4 mt-auto border-t border-surface-50 pt-3">
                  <button
                    @click="item.tipo !== 'TORNEO' ? abrirAsistencia(item) : null"
                    class="w-full flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-xs font-bold border transition-all"
                    :class="getCfg(item.tipo).btnBg"
                  >
                    <template v-if="item.tipo === 'TORNEO'">
                      <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                      Encuentro Torneo
                    </template>
                    <template v-else>
                      <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                      Lista de Asistencia
                    </template>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

          </div>
        </Transition>

      </div>
    </div>

    <!-- Modal de Lista de Asistencia (Lectura) -->
    <ModalAsistenciaLectura
      v-if="showModalAsistencia && sesionSeleccionada"
      :sesion="sesionSeleccionada"
      @close="cerrarAsistencia"
    />
  </div>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar { width: 4px; height: 4px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>
