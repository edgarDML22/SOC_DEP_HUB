<script setup>
import { computed, ref } from 'vue';
import { useReservationStore } from '@/stores/reservationStore';
import { storeToRefs } from 'pinia';
import { useRouter } from 'vue-router';
import { useProfileStore } from '@/stores/profiles/socioStore'; 

const router = useRouter();
const profileStore = useProfileStore();
const reservationStore = useReservationStore();
const { reservaPayload, cargando, errorNavegacion, acompanantesSeleccionados } = storeToRefs(reservationStore);

const isConfirming = ref(false);

const dateToday = computed(() => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date().toLocaleDateString('es-ES', options);
});

const handleConfirm = async () => {
    if (isConfirming.value) return;
    isConfirming.value = true;
    
    const result = await reservationStore.confirmarReserva();
    isConfirming.value = false;
    
    if (result.success) {
        // Redirigir al inicio de socio donde podrá ver su reserva activa
        reservationStore.resetearReserva();
        router.push('/socio/home');
    } else {
        if (result.status === 409) {
            // El horario o cancha ya no está disponible
            reservationStore.resetearReserva();
            reservationStore.errorNavegacion = result.error;
            reservationStore.pasoActual = "1";
        }
    }
};

const handleCancel = async () => {
    await reservationStore.descartarBorrador();
    router.push('/socio/home');
};

const formatTime = (timeStr) => {
    if (!timeStr) return '';
    return timeStr.substring(0, 5);
};
</script>

<template>
  <div class="w-full flex flex-col items-center animate-fade-in">
      <div v-if="errorNavegacion" class="w-full max-w-2xl bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl font-semibold text-sm shadow-sm mb-6 flex items-center gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
          <span>{{ errorNavegacion }}</span>
      </div>

      <!-- TICKET -->
      <div class="w-full max-w-2xl bg-white rounded-[2.5rem] shadow-[0_15px_40px_-15px_rgba(0,0,0,0.1)] border border-surface-200 overflow-hidden relative mb-8">
          <!-- Decoración superior (Ticket cut) -->
          <div class="absolute top-0 left-0 w-full h-3 bg-gradient-to-r from-primary-600 via-primary-500 to-primary-700"></div>
          
          <div class="p-8 md:p-10 border-b-2 border-dashed border-surface-200 relative">
              <!-- Círculos de corte estilo ticket -->
              <div class="absolute -bottom-4 -left-4 w-8 h-8 bg-surface-50 rounded-full border border-surface-200 border-r-transparent border-b-transparent rotate-45"></div>
              <div class="absolute -bottom-4 -right-4 w-8 h-8 bg-surface-50 rounded-full border border-surface-200 border-l-transparent border-b-transparent -rotate-45"></div>

              <div class="text-center mb-6">
                  <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-50 text-primary-600 rounded-full mb-4">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  </div>
                  <h3 class="text-2xl font-bold text-surface-900 tracking-tight">Resumen de Reserva</h3>
                  <p class="text-surface-500 font-medium mt-1 capitalize">{{ dateToday }}</p>
              </div>

              <div class="bg-surface-50 rounded-2xl p-6 grid grid-cols-2 gap-y-6 gap-x-4">
                  <div class="col-span-2 sm:col-span-1">
                      <p class="text-[11px] text-surface-500 font-semibold uppercase tracking-wider mb-1">Deporte</p>
                      <p class="text-base font-bold text-surface-900">{{ reservaPayload.disciplinaSeleccionada }}</p>
                  </div>
                  <div class="col-span-2 sm:col-span-1">
                      <p class="text-[11px] text-surface-500 font-semibold uppercase tracking-wider mb-1">Cancha</p>
                      <p class="text-base font-bold text-surface-900">{{ reservaPayload.espacioSeleccionado }}</p>
                  </div>
                  <div class="col-span-2">
                      <p class="text-[11px] text-surface-500 font-semibold uppercase tracking-wider mb-1">Horario</p>
                      <div class="flex items-center gap-3">
                          <span class="text-lg font-bold text-primary-700">{{ formatTime(reservaPayload.hora_inicio) }}</span>
                          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-surface-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                          <span class="text-lg font-bold text-primary-700">{{ formatTime(reservaPayload.hora_fin) }}</span>
                      </div>
                  </div>
              </div>
          </div>

          <div class="p-6 md:p-8 bg-white">
              <p class="text-[11px] text-surface-500 font-semibold uppercase tracking-wider mb-4">Acompañantes ({{ acompanantesSeleccionados.length }})</p>
              
              <div v-if="acompanantesSeleccionados.length > 0" class="space-y-2">
                  <div v-for="acompanante in acompanantesSeleccionados" :key="acompanante.id + acompanante.tipo" class="flex items-center justify-between px-4 py-3 rounded-xl border border-surface-100 bg-surface-50/50">
                      <div class="flex items-center gap-3 min-w-0">
                          <div class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0">
                              {{ acompanante.nombre?.charAt(0) || '?' }}
                          </div>
                          <span class="font-bold text-surface-900 text-sm truncate">{{ acompanante.nombre }}</span>
                      </div>
                      <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border shrink-0"
                            :class="{
                              'bg-green-50 text-green-700 border-green-200': acompanante.tipo === 'Amigo',
                              'bg-purple-50 text-purple-700 border-purple-200': acompanante.tipo === 'Familiar',
                              'bg-orange-50 text-orange-700 border-orange-200': acompanante.tipo === 'Invitado'
                            }">
                          {{ acompanante.tipo === 'Familiar' ? 'FAMILIAR' : acompanante.tipo === 'Amigo' ? 'AMIGO' : 'INVITADO' }}
                      </span>
                  </div>
              </div>
              <div v-else class="text-center p-5 border-2 border-dashed border-surface-100 rounded-xl bg-surface-50/50">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-surface-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                  <p class="text-surface-400 font-medium text-sm">No has elegido a ningún acompañante</p>
              </div>
          </div>
      </div>

      <div class="w-full max-w-2xl flex flex-col sm:flex-row gap-4">
          <button @click="handleCancel" :disabled="isConfirming || cargando" class="flex-1 px-8 py-4 bg-white border-2 border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300 font-bold rounded-xl transition-all focus:outline-none flex justify-center items-center active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
              Cancelar
          </button>
          
          <button @click="handleConfirm" :disabled="isConfirming || cargando" class="flex-[2] px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl shadow-[0_8px_20px_-6px_rgba(37,99,235,0.4)] hover:shadow-[0_12px_25px_-6px_rgba(37,99,235,0.5)] transition-all flex justify-center items-center gap-3 active:scale-95 border-none disabled:opacity-70 disabled:cursor-not-allowed">
              <template v-if="isConfirming || cargando">
                  <div class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  Procesando...
              </template>
              <template v-else>
                  Confirmar Reserva <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
              </template>
          </button>
      </div>
  </div>
</template>
