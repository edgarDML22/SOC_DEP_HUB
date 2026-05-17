<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useProfileStore } from '@/stores/profiles/socioStore'
import api from '@/services/api'
import QrCredentialModal from '@/components/socio/QrCredentialModal.vue'

import { IconCalendar, IconTrophy, IconGuests, IconClock, IconBaby } from '@/components/icons';

const profileStore = useProfileStore();
const router = useRouter();

const qrPayload    = ref('');
const isQrModalOpen = ref(false);
const qrIsLoading  = ref(false);
const errorQr      = ref('');

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
          <div class="flex items-center gap-3 mb-4">
            <span class="bg-white/20 backdrop-blur-md text-white border border-white/30 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider shadow-sm">
              Próxima Reserva
            </span>
          </div>
          <div>
            <h3 class="text-2xl md:text-3xl font-bold mb-2 tracking-tight">Cero reservas activas</h3>
            <p class="text-primary-100 font-medium text-sm md:text-base opacity-90 max-w-sm leading-relaxed">
              Elige el espacio que necesites y reserva tu horario.
            </p>
          </div>
        </div>

        <div class="relative z-10 shrink-0 flex flex-col sm:flex-row gap-3 w-full md:w-auto">
          <!-- Botón activo -->
          <router-link
            v-if="!profileStore.isAccountInactive && !profileStore.isReservationsBlocked"
            to="/socio/reservations"
            class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-8 py-3.5 font-bold transition-all active:scale-95 shadow-lg shadow-black/20 text-center border border-primary-500 flex items-center justify-center gap-2 hover:-translate-y-0.5"
          >
            Reservar ahora
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
            </svg>
          </router-link>
          <!-- Botón deshabilitado por penalización de reservas -->
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

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-5">

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
              class="text-center text-[11px] font-semibold text-red-500 leading-tight px-1">
              Penalizado hasta el<br>{{ profileStore.fechaLiberacionReserva }}
            </p>
          </div>

          <!-- ── ACTIVIDADES PROGRAMADAS (sin restricción) ── -->
          <router-link to="/socio/classes"
            class="group bg-white rounded-3xl border border-surface-200 p-5 md:p-6 aspect-square flex flex-col items-center justify-center text-center shadow-sm hover:shadow-xl hover:border-primary-200 hover:-translate-y-1.5 active:scale-95 active:translate-y-0 transition-all duration-300 ease-out">
            <div class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-colors mb-4 mt-2">
              <IconClock class="w-7 h-7 md:w-8 md:h-8" />
            </div>
            <span class="font-medium text-surface-900 text-sm md:text-base group-hover:text-primary-700 transition-colors leading-tight">Actividades<br>Programadas</span>
          </router-link>

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
        <div class="h-32 flex items-center justify-center rounded-2xl border border-dashed border-surface-300 bg-surface-50 transition-colors hover:bg-surface-100">
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
  </main>
</template>
