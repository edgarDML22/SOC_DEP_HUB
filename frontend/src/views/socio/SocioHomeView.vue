<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useProfileStore } from '@/stores/profiles/socioStore'
import { useAgendaStore } from '@/stores/agendaStore'
import { useSocioTorneoStore } from '@/stores/socioTorneoStore'
import api from '@/services/api'
import QrCredentialModal from '@/components/socio/QrCredentialModal.vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'

import { IconCalendar, IconTrophy, IconGuests, IconClock, IconBaby } from '@/components/icons';

const profileStore = useProfileStore();
const agendaStore = useAgendaStore();
const torneoStore = useSocioTorneoStore();
const router = useRouter();

onMounted(async () => {
  agendaStore.fetchSocioAgenda();
  await torneoStore.fetchDisponibles();
});

const qrPayload    = ref('');
const isQrModalOpen = ref(false);
const qrIsLoading  = ref(false);
const errorQr      = ref('');

const showDetailsModal = ref(false);
const selectedActivity = ref(null);

const openDetails = () => {
  selectedActivity.value = agendaStore.proximaActividad;
  showDetailsModal.value = true;
};

const closeDetails = () => {
  showDetailsModal.value = false;
};

const formatHora = (hora) => {
  if (!hora) return '—';
  return hora.substring(0, 5);
};

const formatFechaLarga = (iso) => {
  if (!iso) return '—';
  return new Date(iso + 'T12:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

const formatFecha = (iso) => {
  if (!iso) return '—';
  const [y, m, d] = iso.split('-');
  const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  return `${d} ${meses[parseInt(m) - 1]} ${y}`;
};

const getActivityLabel = (tipo) => {
  const configs = {
    'RESERVA': 'Reserva de Espacio',
    'CLASE_ABIERTA': 'Clase Abierta',
    'CLASE_CERRADA': 'Clase Cerrada',
    'TORNEO': 'Encuentro Torneo'
  };
  return configs[tipo] || tipo;
};

const handleClick = async (action) => {
  if (action === 'qr') {
    if (profileStore.isAccountInactive) return;

    try {
      errorQr.value    = '';
      qrIsLoading.value = true;
      const response = await api.get('/profile/qr-data');
      if (response.data.success) {
        qrPayload.value    = response.data.data.qr_payload;
        isQrModalOpen.value = true;
      }
    } catch (error) {
      console.error('Error al generar QR:', error);
      if (error.response?.status === 403) {
        errorQr.value = error.response.data?.message ?? 'Tu cuenta no puede generar el código QR en este momento.';
      } else {
        errorQr.value = 'No se pudo generar el código QR. Intenta de nuevo más tarde.';
      }
    } finally {
      qrIsLoading.value = false;
    }
  } else if (action === 'ver torneos') {
    router.push('/socio/tournaments');
  }
};

const torneosActivosProximos = computed(() => {
  return [...torneoStore.disponibles].slice(0, 3);
});

const getCupoPercentage = (torneo) => {
  if (!torneo.cupo_maximo) return 0;
  return Math.min(100, Math.round((torneo.inscritos_actual / torneo.cupo_maximo) * 100));
};

const getProgressBarColor = (pct) => {
  if (pct >= 100) return 'bg-rose-500';
  if (pct >= 75) return 'bg-amber-500';
  return 'bg-emerald-500';
};
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans">
    <div class="max-w-5xl mx-auto p-4 md:p-8 space-y-6 md:space-y-8 pb-24 md:pb-8">

      <!-- Alertas Globales -->
      <div v-if="profileStore.hasPenalty"
        class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl flex items-start gap-3 shadow-sm animate-fade-in">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-red-500 mt-0.5" fill="none"
          viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
          <h4 class="font-bold text-base">Atención</h4>
          <p class="text-sm mt-1 font-medium">Tu cuenta tiene una penalización activa: <span class="font-bold uppercase">{{ profileStore.statusPenalizacion }}</span>. Algunos servicios del club están temporalmente restringidos.</p>
        </div>
      </div>

      <div v-if="errorQr"
        class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl flex items-center gap-3 shadow-sm animate-pulse">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
            clip-rule="evenodd" />
        </svg>
        <span class="text-sm font-medium">{{ errorQr }}</span>
      </div>

      <!-- SECCIÓN 1: BIENVENIDA -->
      <div class="flex flex-col gap-1 md:gap-1.5 pt-2">
        <h2 class="text-2xl md:text-3xl font-bold text-surface-900 tracking-tight m-0 drop-shadow-sm">
          Hola, {{ profileStore.fullName || 'Socio' }}
        </h2>
        <p class="text-surface-500 font-medium text-sm md:text-base m-0">Bienvenido de vuelta al Club</p>
      </div>

      <!-- SECCIÓN 2: PRÓXIMA RESERVA (Highlight) -->
      <div
        class="bg-linear-to-br from-primary-800 to-primary-600 text-white rounded-3xl md:rounded-[2.5rem] p-6 md:p-8 relative overflow-hidden shadow-xl shadow-primary-700/20 flex flex-col md:flex-row md:items-center justify-between gap-6 transition-all hover:shadow-2xl hover:shadow-primary-700/30">
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex-1">
          <div v-if="agendaStore.proximaActividad">
            <div class="flex items-center gap-3 mb-4">
              <span class="bg-white/20 backdrop-blur-md text-white border border-white/30 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider shadow-sm">
                {{ agendaStore.proximaActividad.tipo.replace('_', ' ') }}
              </span>
              <span class="bg-white/20 backdrop-blur-md text-white border border-white/30 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider shadow-sm">
                Próxima actividad
              </span>
            </div>
            <div class="flex items-center gap-3 mb-2 text-white">
              <IconTrophy v-if="agendaStore.proximaActividad.tipo.toLowerCase().includes('torneo')" class="w-7 h-7 md:w-9 md:h-9 shrink-0 drop-shadow-md" />
              <DisciplineIcon v-else :name="agendaStore.proximaActividad.disciplina || agendaStore.proximaActividad.titulo" class="w-7 h-7 md:w-9 md:h-9 shrink-0 drop-shadow-md text-white fill-white" />
              <h3 class="text-2xl md:text-3xl font-bold tracking-tight line-clamp-1 m-0">{{ agendaStore.proximaActividad.titulo }}</h3>
            </div>
            <p class="text-primary-100 font-medium text-sm md:text-base opacity-90 max-w-sm leading-relaxed">
               {{ agendaStore.proximaActividad.fecha }} • {{ agendaStore.proximaActividad.hora_inicio }}{{ agendaStore.proximaActividad.hora_fin ? ' - ' + agendaStore.proximaActividad.hora_fin : '' }}
               <br />
               {{ agendaStore.proximaActividad.espacio }}
            </p>
          </div>
          <div v-else>
            <div class="flex items-center gap-3 mb-4">
              <span class="bg-white/20 backdrop-blur-md text-white border border-white/30 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider shadow-sm">
                Próxima Reserva
              </span>
            </div>
            <h3 class="text-2xl md:text-3xl font-bold mb-2 tracking-tight">Cero reservas activas</h3>
            <p class="text-primary-100 font-medium text-sm md:text-base opacity-90 max-w-sm leading-relaxed">
              Elige el espacio que necesites y reserva tu horario.
            </p>
          </div>
        </div>

        <div class="relative z-10 shrink-0 flex flex-col sm:flex-row gap-3 w-full md:w-auto">
          <!-- Caso 1: Hay próxima actividad activa (Abre Modal de Detalles) -->
          <button
            v-if="agendaStore.proximaActividad"
            @click="openDetails"
            class="w-full sm:w-auto bg-white hover:bg-surface-100 text-primary-800 rounded-xl px-8 py-3.5 font-extrabold transition-all active:scale-95 shadow-lg shadow-black/10 text-center flex items-center justify-center gap-2 hover:-translate-y-0.5 border border-white/90 cursor-pointer"
          >
            Ver detalles
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
              <polyline points="14 2 14 8 20 8" />
              <line x1="16" y1="13" x2="8" y2="13" />
              <line x1="16" y1="17" x2="8" y2="17" />
              <line x1="10" y1="9" x2="8" y2="9" />
            </svg>
          </button>

          <!-- Caso 2: No hay próxima actividad y las reservaciones están permitidas -->
          <router-link
            v-else-if="!profileStore.isAccountInactive && !profileStore.isReservationsBlocked"
            to="/socio/reservations"
            class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-8 py-3.5 font-bold transition-all active:scale-95 shadow-lg shadow-black/20 text-center border border-primary-500 flex items-center justify-center gap-2 hover:-translate-y-0.5"
          >
            Reservar ahora
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
            </svg>
          </router-link>

          <!-- Caso 3: No hay próxima actividad y las reservaciones están bloqueadas -->
          <div
            v-else-if="!profileStore.isAccountInactive && profileStore.isReservationsBlocked"
            class="w-full sm:w-auto bg-white/10 text-white/50 rounded-xl px-8 py-3.5 font-bold border border-white/20 flex flex-col items-center justify-center gap-1 cursor-not-allowed"
          >
            <span class="flex items-center gap-2">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
              </svg>
              Reservaciones bloqueadas
            </span>
            <span v-if="profileStore.fechaLiberacionReserva" class="text-[11px] font-medium opacity-70">
              Hasta el {{ profileStore.fechaLiberacionReserva }}
            </span>
          </div>
        </div>
      </div>

      <!-- SECCIÓN 3: ACCIONES RÁPIDAS -->
      <div>
        <h3 class="text-xl md:text-2xl font-bold text-surface-900 mb-5 tracking-tight">Acciones rápidas</h3>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 md:gap-5">

          <!-- ── ACTIVIDADES PROGRAMADAS (sin restricción) ── -->
          <router-link to="/socio/classes"
            class="group bg-white rounded-3xl border border-surface-200 p-5 md:p-6 aspect-square flex flex-col items-center justify-center text-center shadow-sm hover:shadow-xl hover:border-primary-200 hover:-translate-y-1.5 active:scale-95 active:translate-y-0 transition-all duration-300 ease-out">
            <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-colors mb-4 mt-2">
              <IconClock class="w-7 h-7 md:w-8 md:h-8" />
            </div>
            <span class="font-medium text-surface-900 text-sm md:text-base group-hover:text-primary-700 transition-colors leading-tight">Actividades<br>Programadas</span>
          </router-link>

          <!-- ── RESERVACIONES ── -->
          <div class="flex flex-col gap-1.5">
            <!-- Tarjeta activa -->
            <router-link
              v-if="!profileStore.isAccountInactive && !profileStore.isReservationsBlocked"
              to="/socio/reservations"
              class="group bg-white rounded-3xl border border-surface-200 p-5 md:p-6 aspect-square flex flex-col items-center justify-center text-center shadow-sm hover:shadow-xl hover:border-primary-200 hover:-translate-y-1.5 active:scale-95 active:translate-y-0 transition-all duration-300 ease-out"
            >
              <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-colors mb-4 mt-2">
                <IconCalendar class="w-7 h-7 md:w-8 md:h-8" />
              </div>
              <span class="font-medium text-surface-900 text-sm md:text-base group-hover:text-primary-700 transition-colors leading-tight">Reservaciones</span>
            </router-link>
            <!-- Tarjeta deshabilitada -->
            <div
              v-else
              class="bg-surface-100 rounded-3xl border border-surface-200 p-5 md:p-6 aspect-square flex flex-col items-center justify-center text-center cursor-not-allowed opacity-60 select-none"
            >
              <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-surface-200 text-surface-400 flex items-center justify-center mb-4 mt-2">
                <IconCalendar class="w-7 h-7 md:w-8 md:h-8" />
              </div>
              <span class="font-medium text-surface-500 text-sm md:text-base leading-tight">Reservaciones</span>
            </div>
            <!-- Mensaje de liberación -->
            <p v-if="profileStore.isReservationsBlocked && profileStore.fechaLiberacionReserva"
              class="text-center text-[11px] font-semibold text-amber-600 leading-tight px-1">
              Penalizado hasta el<br>{{ profileStore.fechaLiberacionReserva }}
            </p>
          </div>

          <!-- ── TORNEOS (sin restricción) ── -->
          <router-link to="/socio/tournaments"
            class="group bg-white rounded-3xl border border-surface-200 p-5 md:p-6 aspect-square flex flex-col items-center justify-center text-center shadow-sm hover:shadow-xl hover:border-primary-200 hover:-translate-y-1.5 active:scale-95 active:translate-y-0 transition-all duration-300 ease-out">
            <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-colors mb-4 mt-2">
              <IconTrophy class="w-7 h-7 md:w-8 md:h-8" />
            </div>
            <span class="font-medium text-surface-900 text-sm md:text-base group-hover:text-primary-700 transition-colors leading-tight">Consultar<br>Torneos</span>
          </router-link>

          <!-- ── COMUNIDAD (sin restricción) ── -->
          <router-link to="/socio/community"
            class="group bg-white rounded-3xl border border-surface-200 p-5 md:p-6 aspect-square flex flex-col items-center justify-center text-center shadow-sm hover:shadow-xl hover:border-primary-200 hover:-translate-y-1.5 active:scale-95 active:translate-y-0 transition-all duration-300 ease-out">
            <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-colors mb-4 mt-2">
              <IconGuests class="w-7 h-7 md:w-8 md:h-8" />
            </div>
            <span class="font-medium text-surface-900 text-sm md:text-base group-hover:text-primary-700 transition-colors leading-tight">Gestionar<br>Comunidad</span>
          </router-link>

          <!-- ── ACCESO LUDOTECA ── -->
          <div class="flex flex-col gap-1.5">
            <!-- Tarjeta activa -->
            <router-link
              v-if="!profileStore.isAccountInactive && !profileStore.isLudotecaBlocked"
              to="/socio/socio-ludoteca/ludoteca-list"
              class="group bg-white rounded-3xl border border-surface-200 p-5 md:p-6 aspect-square flex flex-col items-center justify-center text-center shadow-sm hover:shadow-xl hover:border-primary-200 hover:-translate-y-1.5 active:scale-95 active:translate-y-0 transition-all duration-300 ease-out"
            >
              <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-colors mb-4 mt-2">
                <IconBaby class="w-7 h-7 md:w-8 md:h-8" />
              </div>
              <span class="font-medium text-surface-900 text-sm md:text-base group-hover:text-primary-700 transition-colors leading-tight">Acceso<br>Ludoteca</span>
            </router-link>
            <!-- Tarjeta deshabilitada -->
            <div
              v-else
              class="bg-surface-100 rounded-3xl border border-surface-200 p-5 md:p-6 aspect-square flex flex-col items-center justify-center text-center cursor-not-allowed opacity-60 select-none"
            >
              <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-surface-200 text-surface-400 flex items-center justify-center mb-4 mt-2">
                <IconBaby class="w-7 h-7 md:w-8 md:h-8" />
              </div>
              <span class="font-medium text-surface-500 text-sm md:text-base leading-tight">Acceso<br>Ludoteca</span>
            </div>
            <!-- Mensaje de liberación -->
            <p v-if="profileStore.isLudotecaBlocked && profileStore.fechaLiberacionLudoteca"
              class="text-center text-[11px] font-semibold text-amber-600 leading-tight px-1">
              Penalizado hasta el<br>{{ profileStore.fechaLiberacionLudoteca }}
            </p>
          </div>

          <!-- ── HISTORIAL (sin restricción) ── -->
          <router-link to="/socio/history"
            class="group bg-white rounded-3xl border border-surface-200 p-5 md:p-6 aspect-square flex flex-col items-center justify-center text-center shadow-sm hover:shadow-xl hover:border-primary-200 hover:-translate-y-1.5 active:scale-95 active:translate-y-0 transition-all duration-300 ease-out">
            <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-colors mb-4 mt-2">
              <IconClock class="w-7 h-7 md:w-8 md:h-8" />
            </div>
            <span class="font-medium text-surface-900 text-sm md:text-base group-hover:text-primary-700 transition-colors leading-tight">Consultar<br>Historial</span>
          </router-link>

        </div>
      </div>

      <!-- SECCIÓN 4: TORNEOS ACTIVOS -->
      <div class="bg-white rounded-3xl border border-surface-200 p-6 md:p-8 shadow-sm">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl md:text-2xl font-bold text-surface-900 tracking-tight">Torneos activos</h3>
          <button
            class="text-primary-600 font-semibold text-sm hover:text-primary-700 hover:underline transition-all hidden md:block"
            @click="handleClick('ver torneos')">
            Ver todos &rarr;
          </button>
        </div>

        <!-- Rejilla de Torneos -->
        <div v-if="torneosActivosProximos.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <router-link
            v-for="torneo in torneosActivosProximos"
            :key="torneo.id_torneo"
            to="/socio/tournaments"
            class="group bg-white rounded-3xl border border-surface-200 p-5 shadow-sm hover:shadow-xl hover:border-primary-200 hover:-translate-y-1.5 active:scale-98 transition-all duration-300 flex flex-col justify-between text-left cursor-pointer relative overflow-hidden"
          >
            <!-- Top Accent Line -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-primary-600"></div>

            <div class="space-y-4">
              <!-- Header & Access Badge -->
              <div class="flex justify-between items-start pt-1">
                <div class="flex items-center gap-2 min-w-0">
                  <div class="w-8 h-8 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center border border-primary-100/40 shrink-0">
                    <DisciplineIcon :name="torneo.disciplina?.nombre_disciplina" class="w-4.5 h-4.5 fill-current text-primary-600" />
                  </div>
                  <div class="flex flex-col min-w-0">
                    <span class="text-[10px] font-extrabold text-primary-600 uppercase tracking-widest leading-none truncate">
                      {{ torneo.disciplina?.nombre_disciplina || 'Multi-Deporte' }}
                    </span>
                    <span class="text-[9px] font-bold text-surface-400 mt-1 leading-none">
                      Rama: {{ torneo.categoria?.genero_requerido === 'M' ? 'Varonil' : (torneo.categoria?.genero_requerido === 'F' ? 'Femenil' : 'Mixto') }}
                    </span>
                  </div>
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0">
                  <span class="bg-primary-50/70 border border-primary-100 text-primary-700 text-[9px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                    {{ torneo.tipo_acceso }}
                  </span>
                  <span
                    class="text-[9px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider border shadow-xs"
                    :class="torneo.estatus_torneo === 'EN_INSCRIPCION' 
                      ? 'bg-blue-50 text-blue-700 border-blue-150'
                      : (torneo.estatus_torneo === 'EN_CURSO'
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-150'
                        : (torneo.estatus_torneo === 'FINALIZADO'
                          ? 'bg-surface-100 text-surface-600 border-surface-200'
                          : 'bg-amber-50 text-amber-700 border-amber-150'))"
                  >
                    {{ torneo.estatus_torneo === 'EN_CURSO' ? 'En Curso' : (torneo.estatus_torneo === 'EN_INSCRIPCION' ? 'Abierto' : (torneo.estatus_torneo === 'FINALIZADO' ? 'Finalizado' : 'Programado')) }}
                  </span>
                </div>
              </div>

              <!-- Title & Category -->
              <div>
                <h4 class="text-sm font-extrabold text-surface-900 leading-snug tracking-tight line-clamp-1 m-0">
                  {{ torneo.nombre_torneo }}
                </h4>
                <p class="text-[11px] font-bold text-surface-500 mt-1 line-clamp-1 m-0">
                  Cat: <span class="text-surface-700 font-extrabold">{{ torneo.categoria?.nombre_categoria }}</span>
                  <span class="text-surface-400 font-medium"> • {{ torneo.categoria?.edad_minima }}-{{ torneo.categoria?.edad_maxima }} años</span>
                </p>
              </div>

              <!-- Date Block -->
              <div class="flex items-center gap-1.5 text-[11px] font-bold text-surface-500">
                <IconCalendar class="w-4 h-4 text-surface-400 shrink-0" />
                <span>Inicia: <span class="text-surface-800 font-extrabold">{{ formatFecha(torneo.fecha_inicio) }}</span></span>
              </div>

              <!-- Progress Bar / Cupos -->
              <div class="space-y-1 pt-1 mt-auto">
                <div class="flex justify-between text-[10px] font-bold text-surface-500">
                  <span>Cupos ocupados</span>
                  <span class="text-surface-850 font-extrabold">{{ torneo.inscritos_actual }} / {{ torneo.cupo_maximo }}</span>
                </div>
                <div class="w-full bg-surface-100 rounded-full h-1.5 overflow-hidden shadow-inner">
                  <div
                    :class="getProgressBarColor(getCupoPercentage(torneo))"
                    class="h-full rounded-full transition-all duration-500"
                    :style="{ width: getCupoPercentage(torneo) + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- Action Status at the Bottom -->
            <div class="mt-4 pt-3 border-t border-surface-100 flex items-center justify-between">
              <span v-if="torneo.estatus_torneo === 'FINALIZADO'" class="text-surface-500 font-extrabold text-[11px] flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Torneo Concluido
              </span>
              <span v-else-if="torneo.ya_inscrito" class="text-emerald-600 font-extrabold text-[11px] flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Ya Inscrito
              </span>
              <span v-else-if="torneo.inscritos_actual >= torneo.cupo_maximo" class="text-rose-500 font-extrabold text-[11px]">
                Cupo Lleno
              </span>
              <span v-else class="text-primary-600 group-hover:text-primary-700 font-extrabold text-[11px] flex items-center gap-1">
                Inscribirme &rarr;
              </span>
            </div>
          </router-link>
        </div>

        <!-- Contenedor Punteado (Placeholder) -->
        <div v-else class="h-32 flex items-center justify-center rounded-2xl border border-dashed border-surface-300 bg-surface-50 transition-colors hover:bg-surface-100">
          <p class="text-surface-500 font-medium text-xs md:text-sm uppercase tracking-widest text-center px-4">No hay torneos activos en este momento</p>
        </div>

        <button
          class="w-full mt-4 bg-surface-50 hover:bg-surface-100 text-surface-700 border border-surface-200 rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 md:hidden"
          @click="handleClick('ver torneos')">
          Ver todos
        </button>
      </div>

    </div>

    <!-- Modal QR -->
    <QrCredentialModal v-if="isQrModalOpen" :payloadText="qrPayload" :isLoading="qrIsLoading"
      @close="isQrModalOpen = false" />

    <!-- MODAL DE DETALLES DE PRÓXIMA ACTIVIDAD -->
    <Transition name="fade">
      <div v-if="showDetailsModal && selectedActivity" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 text-slate-800">
        <div class="absolute inset-0 bg-surface-900/60 backdrop-blur-sm" @click="closeDetails"></div>
        <div class="relative bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/20 animate-scale-in max-h-full flex flex-col">
          
          <!-- Header Modal -->
          <div class="bg-blue-600 p-6 sm:p-8 text-white flex justify-between items-start shrink-0">
            <div>
              <div class="flex items-center gap-2 mb-2">
                <span class="px-2 py-0.5 rounded border border-white/30 text-[10px] font-bold uppercase tracking-wider bg-white/10 backdrop-blur-sm">
                  {{ getActivityLabel(selectedActivity.tipo) }}
                </span>
                <span class="px-2 py-0.5 rounded border border-white/30 text-[10px] font-bold uppercase tracking-wider bg-white/10 backdrop-blur-sm">
                  {{ selectedActivity.estatus }}
                </span>
              </div>
              <h3 class="text-xl sm:text-2xl font-bold tracking-tight leading-tight">{{ selectedActivity.titulo }}</h3>
              <p class="text-blue-100 text-sm font-medium opacity-90 mt-1 capitalize">{{ formatFechaLarga(selectedActivity.fecha) }}</p>
            </div>
            <button @click="closeDetails" class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors focus:outline-none shrink-0 cursor-pointer">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Body Modal -->
          <div class="p-6 sm:p-8 flex flex-col gap-6 overflow-y-auto overflow-x-hidden scrollbar-thin">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <!-- Horario -->
              <div class="flex flex-col gap-1.5 text-left">
                <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest">Horario</span>
                <div class="flex items-center gap-3 text-surface-900 font-bold">
                  <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center shrink-0 border border-blue-100/40">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  </div>
                  <span class="truncate">{{ formatHora(selectedActivity.hora_inicio) }}{{ selectedActivity.hora_fin ? ' - ' + formatHora(selectedActivity.hora_fin) : '' }}</span>
                </div>
              </div>

              <!-- Espacio -->
              <div class="flex flex-col gap-1.5 text-left">
                <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest">Espacio</span>
                <div class="flex items-center gap-3 text-surface-900 font-bold">
                  <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center shrink-0 border border-purple-100/40">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                  </div>
                  <span class="truncate">{{ selectedActivity.espacio || 'Por asignar' }}</span>
                </div>
              </div>
            </div>

            <!-- Información condicional -->
            
            <!-- Instructor / Árbitro (Solo para Clases o Torneos) -->
            <div v-if="selectedActivity.tipo !== 'RESERVA' && selectedActivity.instructor" class="flex flex-col gap-1.5 border-t border-surface-100 pt-6 text-left">
              <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest">Instructor / Responsable</span>
              <div class="flex items-center gap-3 text-surface-900 font-bold">
                <div class="w-10 h-10 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center shrink-0 border border-orange-100/40">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <span>{{ selectedActivity.instructor }}</span>
              </div>
            </div>

            <!-- Fase de Torneo -->
            <div v-if="selectedActivity.tipo === 'TORNEO' && selectedActivity.fase" class="flex flex-col gap-1.5 border-t border-surface-100 pt-6 text-left">
              <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest">Fase del Torneo</span>
              <div class="flex items-center gap-3 text-surface-900 font-bold">
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center shrink-0 border border-emerald-100/40">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
                <span>{{ selectedActivity.fase }}</span>
              </div>
            </div>

            <!-- Acompañantes (Solo para Reservas) -->
            <div v-if="selectedActivity.tipo === 'RESERVA'" class="border-t border-surface-100 pt-6 text-left">
              <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest mb-3 block">Acompañantes</span>
              
              <div v-if="!selectedActivity.acompanantes || selectedActivity.acompanantes.length === 0" class="p-4 bg-surface-50 border border-surface-200 rounded-2xl text-center text-slate-500 text-sm font-medium">
                Reserva individual (Sin acompañantes)
              </div>
              <div v-else class="flex flex-col gap-2 max-h-40 overflow-y-auto pr-2 scrollbar-thin">
                <div v-for="(acomp, idx) in selectedActivity.acompanantes" :key="idx" class="flex items-center justify-between p-3.5 bg-white rounded-xl border border-surface-200 shadow-sm">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-extrabold uppercase shrink-0 shadow-sm">
                      {{ acomp.nombre?.charAt(0) || '?' }}
                    </div>
                    <span class="text-sm font-bold text-surface-900 truncate leading-snug">{{ acomp.nombre }}</span>
                  </div>
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border tracking-wide uppercase bg-blue-50 text-blue-700 border-blue-200">
                    {{ acomp.tipo }}
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </Transition>
  </main>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
    height: 4px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
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
