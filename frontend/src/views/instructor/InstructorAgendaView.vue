<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAgendaStore } from '@/stores/agendaStore';
import { storeToRefs } from 'pinia';
import ModalAsistenciaLectura from '@/components/instructor/ModalAsistenciaLectura.vue';

const router = useRouter();
const agendaStore = useAgendaStore();
const { itemsInstructor, loadingInstructor, errorInstructor } = storeToRefs(agendaStore);

onMounted(() => {
  agendaStore.fetchInstructorAgenda();
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
  itemsInstructor.value.filter(i => i.fecha === hoyStr.value)
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
    gradient: 'from-violet-600 to-purple-700',
    badgeBg:  'bg-violet-100 text-violet-800 border-violet-300',
    badge:    'CLASE CERRADA',
    btnBg:    'bg-violet-50 text-violet-700 hover:bg-violet-600 hover:text-white border-violet-200 hover:border-violet-600',
    accentBg: 'bg-violet-600',
  },
  CLASE_ABIERTA: {
    gradient: 'from-teal-500 to-emerald-600',
    badgeBg:  'bg-teal-100 text-teal-800 border-teal-300',
    badge:    'CLASE ABIERTA',
    btnBg:    'bg-teal-50 text-teal-700 hover:bg-teal-600 hover:text-white border-teal-200 hover:border-teal-600',
    accentBg: 'bg-teal-600',
  },
  TORNEO: {
    gradient: 'from-amber-500 to-orange-600',
    badgeBg:  'bg-amber-100 text-amber-800 border-amber-300',
    badge:    'TORNEO',
    btnBg:    'bg-amber-50 text-amber-700 hover:bg-amber-600 hover:text-white border-amber-200 hover:border-amber-600',
    accentBg: 'bg-amber-500',
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

// ── QR: redirige al módulo de sesiones existente ──────────────────────────────
const abrirQR = (item) => {
  if (item.id_sesion) router.push(`/instructor/sessions/${item.id_sesion}`);
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
        <div v-if="loadingInstructor" class="flex justify-center py-16">
          <div class="w-10 h-10 rounded-full border-3 border-surface-200 border-t-primary-500 animate-spin"/>
        </div>

        <!-- Error -->
        <div v-else-if="errorInstructor" class="p-6 bg-red-50 border border-red-200 rounded-2xl text-center text-red-700 text-sm font-medium">
          {{ errorInstructor }}
        </div>

        <!-- ── TAB HOY ──────────────────────────────────────────────────────── -->
        <div v-else-if="activeTab === 'hoy'" class="space-y-4">
          <div v-if="itemsHoy.length === 0" class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm">
            <p class="text-surface-900 font-bold text-lg">Día Libre</p>
            <p class="text-surface-500 font-medium text-sm text-center max-w-xs mt-1">
              No tienes sesiones ni encuentros para hoy.
            </p>
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="item in itemsHoy"
              :key="item.id_sesion ?? item.id_encuentro ?? item.titulo + item.hora_inicio"
              class="bg-white rounded-3xl border border-surface-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden flex flex-col group"
            >
              <!-- Card Header coloreado según tipo -->
              <div class="px-5 py-3.5 flex items-center justify-between bg-linear-to-r" :class="getCfg(item.tipo).gradient">
                <span class="text-white font-bold text-sm tracking-wide line-clamp-1 pr-2">
                  {{ item.titulo }}
                </span>
                <span class="bg-white/20 text-white text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full shrink-0">
                  {{ getCfg(item.tipo).badge }}
                </span>
              </div>

              <!-- Body -->
              <div class="px-5 py-4 flex-1 space-y-3">
                <!-- Fecha + badge tipo clase -->
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2 text-surface-600">
                    <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-semibold">{{ formatFecha(item.fecha) }}</span>
                  </div>
                  <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-lg border" :class="getCfg(item.tipo).badgeBg">
                    {{ getCfg(item.tipo).badge }}
                  </span>
                </div>

                <!-- Horario -->
                <div class="flex items-center gap-2 text-surface-600">
                  <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                  </svg>
                  <span class="text-sm font-semibold tabular-nums">
                    {{ formatHora(item.hora_inicio) }} – {{ formatHora(item.hora_fin) }}
                  </span>
                </div>

                <!-- Espacio -->
                <div v-if="item.espacio" class="flex items-center gap-2 text-surface-600">
                  <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span class="text-sm font-medium text-surface-500 truncate">{{ item.espacio }}</span>
                </div>

                <!-- Contadores (solo clases) -->
                <div v-if="item.contadores" class="flex items-center gap-3 pt-1">
                  <span class="text-xs font-semibold text-surface-500">
                    <span class="font-bold text-surface-700">{{ item.contadores.inscritos }}</span> inscritos
                  </span>
                  <span class="text-xs font-semibold text-emerald-600">
                    <span class="font-bold">{{ item.contadores.asistencia }}</span> asistencia
                  </span>
                  <span v-if="item.tipo === 'CLASE_CERRADA'" class="text-xs font-semibold text-red-500">
                    <span class="font-bold">{{ item.contadores.falta }}</span> no show
                  </span>
                </div>

                <!-- Fase torneo -->
                <div v-if="item.tipo === 'TORNEO' && item.fase" class="flex items-center gap-2 text-surface-600">
                  <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                  </svg>
                  <span class="text-sm font-medium text-surface-500">{{ item.fase }}</span>
                </div>
              </div>

              <!-- Footer con botones -->
              <div class="px-5 pb-5 mt-auto flex gap-2">
                <!-- Botón QR (solo clases) -->
                <button
                  v-if="item.tipo !== 'TORNEO'"
                  @click="abrirQR(item)"
                  :title="'Escanear QR — ' + item.titulo"
                  class="w-10 h-10 shrink-0 rounded-xl border border-surface-200 bg-surface-50 hover:bg-surface-100 text-surface-500 hover:text-surface-700 flex items-center justify-center transition-all"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
                    <path d="M14 14h.01M14 17h.01M17 14h.01M17 17h.01M20 14h.01M20 17h.01M20 20h.01M17 20h.01M14 20h.01"/>
                  </svg>
                </button>

                <!-- Botón principal -->
                <button
                  @click="item.tipo !== 'TORNEO' ? abrirAsistencia(item) : null"
                  class="flex-1 flex items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-bold border transition-all"
                  :class="getCfg(item.tipo).btnBg"
                >
                  <template v-if="item.tipo === 'TORNEO'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    Encuentro Torneo
                  </template>
                  <template v-else>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Ver Lista de Asistencia
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

          <div v-else v-for="grupo in agrupadosProximas" :key="grupo.fecha" class="space-y-4">
            <h3 class="text-sm md:text-base font-extrabold text-primary-900 normal-case tracking-wide pl-2 border-l-4 border-primary-500">
              {{ formatFechaLarga(grupo.fecha) }}
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="item in grupo.items"
                :key="item.id_sesion ?? item.id_encuentro ?? item.titulo + item.hora_inicio"
                class="bg-white rounded-3xl border border-surface-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden flex flex-col group"
              >
                <!-- Header coloreado -->
                <div class="px-5 py-3.5 flex items-center justify-between bg-linear-to-r" :class="getCfg(item.tipo).gradient">
                  <span class="text-white font-bold text-sm tracking-wide line-clamp-1 pr-2">
                    {{ item.titulo }}
                  </span>
                  <span class="bg-white/20 text-white text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full shrink-0">
                    {{ getCfg(item.tipo).badge }}
                  </span>
                </div>

                <!-- Body -->
                <div class="px-5 py-4 flex-1 space-y-3">
                  <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-surface-600">
                      <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span class="text-sm font-semibold">{{ formatFecha(item.fecha) }}</span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-lg border" :class="getCfg(item.tipo).badgeBg">
                      {{ getCfg(item.tipo).badge }}
                    </span>
                  </div>

                  <div class="flex items-center gap-2 text-surface-600">
                    <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span class="text-sm font-semibold tabular-nums">
                      {{ formatHora(item.hora_inicio) }} – {{ formatHora(item.hora_fin) }}
                    </span>
                  </div>

                  <div v-if="item.espacio" class="flex items-center gap-2 text-surface-600">
                    <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-sm font-medium text-surface-500 truncate">{{ item.espacio }}</span>
                  </div>

                  <div v-if="item.contadores" class="flex items-center gap-3 pt-1">
                    <span class="text-xs font-semibold text-surface-500">
                      <span class="font-bold text-surface-700">{{ item.contadores.inscritos }}</span> inscritos
                    </span>
                    <span class="text-xs font-semibold text-emerald-600">
                      <span class="font-bold">{{ item.contadores.asistencia }}</span> asistencia
                    </span>
                    <span v-if="item.tipo === 'CLASE_CERRADA'" class="text-xs font-semibold text-red-500">
                      <span class="font-bold">{{ item.contadores.falta }}</span> no show
                    </span>
                  </div>

                  <div v-if="item.tipo === 'TORNEO' && item.fase" class="flex items-center gap-2 text-surface-600">
                    <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    <span class="text-sm font-medium text-surface-500">{{ item.fase }}</span>
                  </div>
                </div>

                <!-- Footer -->
                <div class="px-5 pb-5 mt-auto flex gap-2">
                  <button
                    v-if="item.tipo !== 'TORNEO'"
                    @click="abrirQR(item)"
                    :title="'Escanear QR'"
                    class="w-10 h-10 shrink-0 rounded-xl border border-surface-200 bg-surface-50 hover:bg-surface-100 text-surface-500 hover:text-surface-700 flex items-center justify-center transition-all"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
                      <path d="M14 14h.01M14 17h.01M17 14h.01M17 17h.01M20 14h.01M20 17h.01M20 20h.01M17 20h.01M14 20h.01"/>
                    </svg>
                  </button>
                  <button
                    @click="item.tipo !== 'TORNEO' ? abrirAsistencia(item) : null"
                    class="flex-1 flex items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-bold border transition-all"
                    :class="getCfg(item.tipo).btnBg"
                  >
                    <template v-if="item.tipo === 'TORNEO'">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                      Encuentro Torneo
                    </template>
                    <template v-else>
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                      Ver Lista de Asistencia
                    </template>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

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
