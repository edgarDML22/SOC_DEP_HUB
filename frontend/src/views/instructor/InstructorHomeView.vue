<script setup>
import { markRaw, onMounted, computed } from 'vue';
import { useInstructorStore } from '@/stores/profiles/instructorStore';
import { useAgendaStore } from '@/stores/agendaStore';
import { storeToRefs } from 'pinia';
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue';

import {
  IconCalendar,
  IconClock,
  IconHourglass,
  IconUser,
  IconBaby,
  IconTrophy,
} from '@/components/icons';

const profileStore = useInstructorStore();
const agendaStore = useAgendaStore();
const { proximaActividadInstructor, loadingInstructor, itemsInstructor } = storeToRefs(agendaStore);

// Sesiones de hoy derivadas directamente de mi-agenda (itemsInstructor del store)
const todaySessions = computed(() => {
  const todayStr = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD
  return itemsInstructor.value.filter(i => i.fecha === todayStr);
});

const stats = computed(() => {
  const items = itemsInstructor.value;
  const hoy = todaySessions.value;
  const now = new Date();
  const currentHour = now.getHours();
  const currentMin  = now.getMinutes();

  // Próximas en las siguientes 2 horas (de toda la agenda, no solo hoy)
  const proximas = items.filter(i => {
    if (!i.hora_inicio) return false;
    const [h, m] = i.hora_inicio.split(':').map(Number);
    const diffMin = (h * 60 + m) - (currentHour * 60 + currentMin);
    return diffMin >= 0 && diffMin <= 120;
  }).length;

  // Total inscritos: suma de contadores.inscritos de sesiones (torneos no tienen contadores)
  const totalInscritos = items.reduce((sum, i) => sum + (i.contadores?.inscritos ?? 0), 0);

  // Pendientes: sesiones/encuentros con estatus que no sea FINALIZADA/CANCELADA (ya filtrado en backend, pero son todos los que no han ocurrido aún)
  const pendientes = items.filter(i => {
    const estatus = (i.estatus ?? '').toUpperCase();
    return estatus !== 'FINALIZADA' && estatus !== 'CANCELADA' && estatus !== 'FINALIZADO' && estatus !== 'CANCELADO';
  }).length;

  return [
    { id: 1, value: hoy.length,       label: 'Sesiones hoy',   icon: markRaw(IconCalendar),  iconBg: 'bg-primary-50', iconColor: 'text-primary-600', hoverBg: 'group-hover:bg-primary-600' },
    { id: 2, value: proximas,          label: 'Próx. 2 horas',  icon: markRaw(IconClock),     iconBg: 'bg-primary-50', iconColor: 'text-primary-600', hoverBg: 'group-hover:bg-primary-600' },
    { id: 3, value: pendientes,        label: 'Pendientes',      icon: markRaw(IconHourglass), iconBg: 'bg-primary-50', iconColor: 'text-primary-600', hoverBg: 'group-hover:bg-primary-600' },
    { id: 4, value: totalInscritos,    label: 'Total Inscritos', icon: markRaw(IconUser),      iconBg: 'bg-primary-50', iconColor: 'text-primary-600', hoverBg: 'group-hover:bg-primary-600' },
  ];
});

const isLoading = computed(() => loadingInstructor.value);

// Turno de ludoteca activo solo si hora_fin > now
const turnoLudotecaActivo = computed(() => {
  const turno = profileStore.turnoLudotecaHoy
  if (!turno?.hora_fin) return false
  const now = new Date()
  const [h, m] = turno.hora_fin.split(':').map(Number)
  const finMs  = h * 60 + m
  const nowMs  = now.getHours() * 60 + now.getMinutes()
  return finMs > nowMs
})

const proximaEnCurso = computed(() => {
  const act = proximaActividadInstructor.value
  if (!act?.fecha || !act?.hora_inicio || !act?.hora_fin) return false
  const hoy = new Date().toLocaleDateString('en-CA')
  if (act.fecha !== hoy) return false
  const now = new Date()
  const nowMin = now.getHours() * 60 + now.getMinutes()
  const [hi, mi] = act.hora_inicio.split(':').map(Number)
  const [hf, mf] = act.hora_fin.split(':').map(Number)
  return nowMin >= hi * 60 + mi && nowMin < hf * 60 + mf
})

const proximaDistancia = computed(() => {
  if (proximaEnCurso.value) return 'EN CURSO'
  const fecha = proximaActividadInstructor.value?.fecha
  if (!fecha) return null
  const hoy     = new Date().toLocaleDateString('en-CA')
  if (fecha === hoy) return 'HOY'
  const msDay   = 86_400_000
  const diff    = Math.round((new Date(fecha + 'T12:00:00') - new Date(hoy + 'T12:00:00')) / msDay)
  if (diff === 1) return 'MAÑANA'
  if (diff > 1)   return `${diff} DÍAS`
  return null
})

const proximaBadgeLabel = computed(() => {
  const tipo = proximaActividadInstructor.value?.tipo ?? '';
  if (tipo === 'TORNEO') return 'Encuentro Torneo';
  if (tipo === 'CLASE_CERRADA') return 'Clase Cerrada';
  if (tipo === 'CLASE_ABIERTA') return 'Clase Abierta';
  return 'Próxima Actividad';
});

onMounted(async () => {
  await profileStore.fetchProfile();
  agendaStore.fetchInstructorAgenda();
});

</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans pb-24 md:pb-8">
    <div class="max-w-5xl mx-auto p-4 md:p-8 space-y-6 md:space-y-8">

      <!-- ESTADO DE CARGA -->
      <div v-if="isLoading" class="flex justify-center items-center py-20">
        <div class="w-8 h-8 md:w-10 md:h-10 rounded-full border-2 border-surface-200 border-t-primary-500 animate-spin"/>
      </div>

      <template v-else>
        <!-- SECCIÓN 1: BIENVENIDA -->
        <div class="flex flex-col gap-1 md:gap-1.5 pt-2 animate-fade-in">
          <p class="text-surface-500 font-medium text-sm md:text-base m-0">Bienvenido de vuelta,</p>
          <div class="flex items-center gap-3">
            <h2 class="text-2xl md:text-3xl font-bold text-surface-900 tracking-tight m-0 drop-shadow-sm">
              {{ profileStore.fullName || 'Instructor' }}
            </h2>
            <span v-if="profileStore.isCuidador" class="bg-primary-100 text-primary-700 px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1.5 border border-primary-200">
              <IconBaby class="w-3.5 h-3.5" /> Ludoteca
            </span>
          </div>
        </div>

        <!-- MODO LUDOTECA — solo si hay turno asignado hoy -->
        <div v-if="turnoLudotecaActivo" class="animate-fade-in space-y-6">
          <div class="bg-linear-to-br from-primary-800 to-primary-600 text-white rounded-3xl md:rounded-[2.5rem] p-6 md:p-8 relative overflow-hidden shadow-xl shadow-primary-700/20 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="relative z-10 flex-1">
              <div class="flex items-center gap-3 mb-4">
                <span class="bg-white/20 backdrop-blur-md text-white border border-white/30 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider shadow-sm flex items-center gap-1.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span> Turno Activo
                </span>
              </div>
              <h3 class="text-2xl md:text-3xl font-bold mb-2 tracking-tight">¡Tienes turno en Ludoteca hoy!</h3>
              <p class="text-primary-100 font-medium text-sm md:text-base opacity-90 max-w-sm leading-relaxed">
                La administración te ha asignado un turno de cuidado en la Ludoteca
                <template v-if="profileStore.turnoLudotecaHoy">
                  de <span class="text-white font-bold">{{ profileStore.turnoLudotecaHoy.hora_inicio }}</span>
                  a <span class="text-white font-bold">{{ profileStore.turnoLudotecaHoy.hora_fin }}</span>.
                </template>
              </p>
            </div>
            <div class="relative z-10 shrink-0 w-full md:w-auto">
              <router-link to="/instructor/ludoteca" class="w-full sm:w-auto bg-white text-primary-700 hover:bg-primary-50 rounded-xl px-8 py-4 font-bold transition-all active:scale-95 shadow-lg flex items-center justify-center gap-3 group">
                Ir al Tablero Operativo
                <IconBaby class="w-5 h-5 group-hover:rotate-12 transition-transform" />
              </router-link>
            </div>
          </div>
          <div class="bg-white rounded-3xl border border-dashed border-surface-300 p-12 flex flex-col items-center justify-center text-center space-y-4 opacity-60">
            <div class="w-20 h-20 bg-surface-100 rounded-full flex items-center justify-center">
              <IconBaby class="w-10 h-10 text-surface-400" />
            </div>
            <div class="max-w-xs">
              <h4 class="text-surface-900 font-bold">Modo Ludoteca Activo</h4>
              <p class="text-surface-500 text-sm">Puedes acceder a todas las funciones desde la sección de Ludoteca en tu barra de navegación.</p>
            </div>
          </div>
        </div>

        <template v-else>
          <!-- SECCIÓN 2: BANNER PRÓXIMA ACTIVIDAD — visible cuando no hay turno de Ludoteca hoy -->
          <div class="bg-linear-to-br from-primary-800 to-primary-600 text-white rounded-3xl md:rounded-[2.5rem] p-6 md:p-8 relative overflow-hidden shadow-xl shadow-primary-700/20 flex flex-col md:flex-row md:items-center justify-between gap-6 transition-all hover:shadow-2xl hover:shadow-primary-700/30 animate-fade-in">
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Badge de distancia temporal -->
            <div v-if="proximaDistancia" class="absolute top-5 right-5 md:top-6 md:right-6 z-20">
              <span
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-widest border"
                :class="proximaEnCurso
                  ? 'bg-white text-primary-700 border-white'
                  : proximaDistancia === 'HOY'
                    ? 'bg-white text-primary-700 border-white'
                    : 'bg-white/15 text-white border-white/30'"
              >
                <span v-if="proximaEnCurso" class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse shrink-0"></span>
                {{ proximaDistancia }}
              </span>
            </div>

            <div class="relative z-10 flex-1">
              <div class="flex items-center gap-3 mb-4">
                <span class="bg-white/20 backdrop-blur-md text-white border border-white/30 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider shadow-sm">
                  {{ proximaEnCurso ? 'En Curso' : 'Próxima Actividad' }}
                </span>
                <span v-if="proximaActividadInstructor" class="bg-white/20 backdrop-blur-md text-white border border-white/30 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider shadow-sm">
                  {{ proximaBadgeLabel }}
                </span>
              </div>

              <!-- Con próxima actividad cargada -->
              <template v-if="proximaActividadInstructor">
                <div class="flex items-center gap-3 mb-2 text-white">
                  <!-- Torneo: trofeo; Clase: icono de disciplina por nombre -->
                  <IconTrophy
                    v-if="proximaActividadInstructor.tipo === 'TORNEO'"
                    class="w-8 h-8 shrink-0 drop-shadow-md"
                  />
                  <DisciplineIcon
                    v-else
                    :name="proximaActividadInstructor.disciplina || proximaActividadInstructor.titulo"
                    class="w-8 h-8 shrink-0 drop-shadow-md text-white fill-white"
                  />
                  <h3 class="text-2xl md:text-3xl font-bold tracking-tight line-clamp-1 m-0">
                    {{ proximaActividadInstructor.titulo }}
                  </h3>
                </div>
                <p class="text-primary-100 font-medium text-sm md:text-base opacity-90 max-w-sm leading-relaxed">
                  {{ proximaActividadInstructor.fecha }} • {{ proximaActividadInstructor.hora_inicio }}
                  <template v-if="proximaActividadInstructor.hora_fin"> – {{ proximaActividadInstructor.hora_fin }}</template>
                  <br/>{{ proximaActividadInstructor.espacio }}
                </p>
              </template>

              <!-- Sin próxima actividad o cargando -->
              <template v-else>
                <div v-if="loadingInstructor" class="flex items-center gap-3">
                  <div class="w-6 h-6 rounded-full border-2 border-white/30 border-t-white animate-spin shrink-0"></div>
                  <span class="text-primary-100 text-sm font-medium">Cargando agenda...</span>
                </div>
                <template v-else>
                  <h3 class="text-2xl md:text-3xl font-bold mb-2 tracking-tight">Sin actividad inminente</h3>
                  <p class="text-primary-100 font-medium text-sm md:text-base opacity-90 max-w-sm leading-relaxed">
                    No tienes sesiones ni encuentros próximos en los siguientes 7 días.
                  </p>
                </template>
              </template>
            </div>

            <div class="relative z-10 shrink-0 w-full md:w-auto" v-if="proximaActividadInstructor">
              <router-link
                to="/instructor/agenda"
                class="w-full sm:w-auto bg-white hover:bg-primary-50 text-primary-700 rounded-xl px-8 py-3.5 font-bold transition-all active:scale-95 shadow-lg shadow-black/20 text-center border border-white/90 flex items-center justify-center gap-2 hover:-translate-y-0.5"
              >
                Ver Agenda
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
                </svg>
              </router-link>
            </div>
          </div>

        </template>

        <!-- SECCIÓN 3: ESTADÍSTICAS RÁPIDAS — siempre visible -->
        <div class="animate-fade-in">
          <h3 class="text-xl md:text-2xl font-bold text-surface-900 mb-5 tracking-tight">Resumen de hoy</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
            <div
              v-for="stat in stats"
              :key="stat.id"
              class="group bg-white rounded-2xl border border-surface-100 p-5 md:p-6 flex flex-col items-center justify-center text-center shadow-sm hover:shadow-lg hover:border-surface-200 hover:-translate-y-1 transition-all duration-200 ease-out"
            >
              <div
                class="w-12 h-12 md:w-14 md:h-14 rounded-xl flex items-center justify-center transition-all duration-200 mb-3"
                :class="[stat.iconBg, stat.iconColor, stat.hoverBg, 'group-hover:text-white']"
              >
                <component :is="stat.icon" class="w-6 h-6 md:w-7 md:h-7" />
              </div>
              <span class="font-extrabold text-2xl md:text-3xl text-surface-900 leading-none">{{ stat.value }}</span>
              <span class="font-medium text-surface-500 text-xs mt-1.5 leading-snug">{{ stat.label }}</span>
            </div>
          </div>
        </div>

        <!-- ACCESOS RÁPIDOS: Sesiones + Arbitraje -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-in">

          <!-- Arbitraje de Torneos -->
          <router-link
            to="/instructor/encuentros"
            class="group bg-white border border-surface-100 rounded-2xl p-5 flex items-center gap-4 hover:shadow-lg hover:border-primary-200 hover:-translate-y-0.5 transition-all duration-200"
          >
            <div class="w-11 h-11 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 group-hover:bg-primary-600 group-hover:text-white transition-colors duration-200">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-bold text-surface-900 text-sm leading-tight">Arbitraje de Torneos</p>
              <p class="text-surface-500 text-xs mt-0.5 leading-snug">Reporta resultados de tus encuentros</p>
            </div>
            <svg class="w-4 h-4 text-surface-300 group-hover:text-primary-500 group-hover:translate-x-0.5 transition-all shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </router-link>

        </div>

      </template>
    </div>
  </main>
</template>
