<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useFamilyStore } from '@/stores/community/familyStore';
import { useFriendStore } from '@/stores/community/friendStore';
import { useGuestStore } from '@/stores/community/guestStore';
import { useReservationStore } from '@/stores/reservationStore';
import { storeToRefs } from 'pinia';

const familyStore = useFamilyStore();
const friendStore = useFriendStore();
const guestStore = useGuestStore();
const reservationStore = useReservationStore();

const { reservaPayload, capacidadMaximaEspacio, acompanantesSeleccionados } = storeToRefs(reservationStore);

const activeTab = ref('familiares');
const dataLoaded = ref(false);

onMounted(async () => {
    try {
        await Promise.all([
            familyStore.fetchMiembrosFamiliares(),
            friendStore.fetchFriends(),
            guestStore.fetchInvitados(true)
        ]);
    } catch (e) {
        console.error('Error cargando datos para Step 4:', e);
    } finally {
        dataLoaded.value = true;
    }
});

const limit = computed(() => capacidadMaximaEspacio.value || 4);
const remaining = computed(() => limit.value - 1 - acompanantesSeleccionados.value.length);

// Solo mostrar amigos con estado ACEPTADA
const amigosDisponibles = computed(() => {
    return (friendStore.friends || []).filter(f => f.estado?.toUpperCase() === 'ACEPTADA');
});

// Solo mostrar invitados con pase ACTIVO
const invitadosDisponibles = computed(() => {
    return (guestStore.invitados || []).filter(i => i.estatus_acceso?.toUpperCase() === 'ACTIVO');
});

const isSelected = (id, tipo) => {
    return acompanantesSeleccionados.value.some(a => a.id === id && a.tipo === tipo);
};

const handleToggle = (id, tipo, nombre) => {
    if (!isSelected(id, tipo) && remaining.value <= 0) return;
    
    // Solo hace el toggle local (Sin POST)
    reservationStore.toggleAcompanante({
        id: id,
        tipo: tipo,
        nombre: nombre,
    });
};

const removeAcompanante = (acomp) => {
    // Solo hace el toggle local (Sin POST)
    reservationStore.toggleAcompanante(acomp);
};

// --- AQUI ESTA LA MAGIA ---
const nextStep = async () => {
    // LLama a la API explícitamente y si todo sale bien, avanza de paso internamente
    await reservationStore.confirmarAcompanantes();
};

const skipToConfirm = async () => {
    // Limpiar acompañantes y avanzar directo
    acompanantesSeleccionados.value.splice(0);
    // Hacemos el POST de un array vacío
    await reservationStore.confirmarAcompanantes();
};
</script>

<template>
  <div class="flex flex-col w-full font-sans">
      
      <!-- Header con info y botón Individual -->
      <div class="flex flex-col gap-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-surface-50 border border-surface-200 p-5 rounded-2xl">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 bg-primary-50 rounded-xl flex items-center justify-center text-primary-600 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
                <div>
                    <div class="font-bold text-surface-900 text-base leading-tight">¿Con quién juegas?</div>
                    <div class="text-xs text-surface-500 font-medium mt-0.5">Opcional. Selecciona tus acompañantes</div>
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <!-- Contador de lugares -->
                <div class="flex flex-col items-center">
                    <span class="text-xs font-semibold text-surface-400">Disponibles</span>
                    <span class="text-lg font-bold" :class="remaining > 0 ? 'text-green-600' : 'text-red-600'">{{ remaining }}<span class="text-xs text-surface-400 font-medium"> / {{ limit - 1 }}</span></span>
                </div>
            </div>
        </div>

        <!-- Botón Individual -->
        <button 
          @click="skipToConfirm"
          class="w-full py-3 bg-white border-2 border-surface-200 hover:border-primary-400 text-surface-700 hover:text-primary-700 font-bold rounded-xl transition-all flex items-center justify-center gap-2 active:scale-[0.98] text-sm focus:outline-none"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
          Reservación Individual
        </button>
      </div>

      <!-- Lista dinámica de seleccionados -->
      <Transition name="fade">
        <div v-if="acompanantesSeleccionados.length > 0" class="mb-6 bg-white border-2 border-surface-900 rounded-2xl p-4 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <span class="text-[11px] font-extrabold text-surface-900 uppercase tracking-widest">Participantes elegidos</span>
            <span class="min-w-[22px] h-[22px] flex items-center justify-center text-[11px] font-bold bg-primary-600 text-white rounded-full px-1.5">{{ acompanantesSeleccionados.length }}</span>
          </div>
          <div class="flex flex-col gap-2">
            <div v-for="acomp in acompanantesSeleccionados" :key="acomp.id + acomp.tipo" 
                 class="flex items-center justify-between bg-surface-50 rounded-xl px-3.5 py-2.5 border border-surface-200">
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full bg-primary-600 text-white flex items-center justify-center text-xs font-extrabold uppercase shrink-0 shadow-sm">
                  {{ acomp.nombre?.charAt(0) || '?' }}
                </div>
                <span class="font-semibold text-surface-900 text-sm truncate leading-snug">{{ acomp.nombre }}</span>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border tracking-wide"
                      :class="{
                        'bg-green-50 text-green-700 border-green-200': acomp.tipo === 'Amigo',
                        'bg-purple-50 text-purple-700 border-purple-200': acomp.tipo === 'Familiar',
                        'bg-orange-50 text-orange-700 border-orange-200': acomp.tipo === 'Invitado'
                      }">
                  {{ acomp.tipo === 'Familiar' ? 'Familiar' : acomp.tipo === 'Amigo' ? 'Amigo' : 'Invitado' }}
                </span>
                <button @click.stop="removeAcompanante(acomp)" title="Quitar" class="w-7 h-7 bg-red-50 hover:bg-red-500 text-red-500 hover:text-white rounded-lg flex items-center justify-center transition-colors focus:outline-none active:scale-90">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- TABS (mismo estilo que Community pills) -->
      <div class="flex p-1.5 bg-surface-100 rounded-2xl w-full mb-5 shadow-inner border border-surface-200">
          <button @click="activeTab = 'familiares'" 
                  :class="activeTab === 'familiares' ? 'bg-primary-600 text-white font-extrabold shadow-md scale-[1.02]' : 'text-surface-500 font-bold hover:bg-white/60 hover:text-surface-700'" 
                  class="flex-1 py-3 px-3 rounded-xl text-sm transition-all focus:outline-none flex items-center justify-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Familiares
          </button>
          <button @click="activeTab = 'amigos'" 
                  :class="activeTab === 'amigos' ? 'bg-primary-600 text-white font-extrabold shadow-md scale-[1.02]' : 'text-surface-500 font-bold hover:bg-white/60 hover:text-surface-700'" 
                  class="flex-1 py-3 px-3 rounded-xl text-sm transition-all focus:outline-none flex items-center justify-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            Amigos
          </button>
          <button @click="activeTab = 'invitados'" 
                  :class="activeTab === 'invitados' ? 'bg-primary-600 text-white font-extrabold shadow-md scale-[1.02]' : 'text-surface-500 font-bold hover:bg-white/60 hover:text-surface-700'" 
                  class="flex-1 py-3 px-3 rounded-xl text-sm transition-all focus:outline-none flex items-center justify-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Invitados
          </button>
      </div>

      <!-- Loading state -->
      <div v-if="!dataLoaded" class="bg-white rounded-2xl border border-surface-200 p-8 text-center">
        <div class="w-8 h-8 border-3 border-surface-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-3"></div>
        <p class="text-sm text-surface-500 font-medium">Cargando listas...</p>
      </div>

      <!-- Content area -->
      <div v-else class="bg-white rounded-2xl border border-surface-200 p-3 md:p-4 mb-6 min-h-[200px]">
          <!-- Familiares -->
          <div v-if="activeTab === 'familiares'" class="space-y-2">
              <div v-if="!familyStore.miembrosFamiliares.length" class="text-center py-8 text-surface-400 font-medium text-sm border-2 border-dashed border-surface-100 rounded-xl">No tienes familiares registrados.</div>
              <div v-else v-for="familiar in familyStore.miembrosFamiliares" :key="'fam-' + familiar.id_miembro" 
                   @click="handleToggle(familiar.id_miembro, 'Familiar', familiar.nombre_completo)"
                   class="flex items-center justify-between p-3.5 rounded-xl border-2 transition-all cursor-pointer group"
                   :class="[isSelected(familiar.id_miembro, 'Familiar') ? 'border-primary-500 bg-primary-50/50' : 'border-blue-100 hover:border-primary-300 hover:bg-blue-50/30', remaining <= 0 && !isSelected(familiar.id_miembro, 'Familiar') ? 'opacity-40 cursor-not-allowed' : '']">
                  <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold uppercase shrink-0"
                           :class="isSelected(familiar.id_miembro, 'Familiar') ? 'bg-primary-600 text-white' : 'bg-blue-50 text-blue-600'">
                           {{ familiar.nombre_completo?.charAt(0) || 'F' }}
                      </div>
                      <div>
                          <div class="font-bold text-surface-900 text-sm">{{ familiar.nombre_completo }}</div>
                          <div class="text-xs text-surface-400 font-medium mt-0.5">{{ familiar.parentesco || 'Familiar' }}</div>
                      </div>
                  </div>
                  <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors shrink-0"
                       :class="isSelected(familiar.id_miembro, 'Familiar') ? 'bg-primary-600 border-primary-600 text-white' : 'border-blue-300 group-hover:border-primary-400'">
                       <svg v-if="isSelected(familiar.id_miembro, 'Familiar')" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </div>
              </div>
          </div>

          <!-- Amigos (solo ACEPTADA) -->
          <div v-if="activeTab === 'amigos'" class="space-y-2">
              <div v-if="!amigosDisponibles.length" class="text-center py-8 text-surface-400 font-medium text-sm border-2 border-dashed border-surface-100 rounded-xl">No tienes amigos aceptados disponibles.</div>
              <div v-else v-for="amigo in amigosDisponibles" :key="'ami-' + amigo.id_amigo" 
                   @click="handleToggle(amigo.id_amigo, 'Amigo', amigo.nombre_amigo)"
                   class="flex items-center justify-between p-3.5 rounded-xl border-2 transition-all cursor-pointer group"
                   :class="[isSelected(amigo.id_amigo, 'Amigo') ? 'border-primary-500 bg-primary-50/50' : 'border-blue-100 hover:border-primary-300 hover:bg-blue-50/30', remaining <= 0 && !isSelected(amigo.id_amigo, 'Amigo') ? 'opacity-40 cursor-not-allowed' : '']">
                  <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold uppercase shrink-0"
                           :class="isSelected(amigo.id_amigo, 'Amigo') ? 'bg-primary-600 text-white' : 'bg-blue-50 text-blue-600'">
                           {{ (amigo.nombre_amigo || 'A').charAt(0) }}
                      </div>
                      <div>
                          <div class="font-bold text-surface-900 text-sm">{{ amigo.nombre_amigo }}</div>
                          <div class="text-xs text-surface-400 font-medium mt-0.5">Amigo</div>
                      </div>
                  </div>
                  <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors shrink-0"
                       :class="isSelected(amigo.id_amigo, 'Amigo') ? 'bg-primary-600 border-primary-600 text-white' : 'border-blue-300 group-hover:border-primary-400'">
                       <svg v-if="isSelected(amigo.id_amigo, 'Amigo')" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </div>
              </div>
          </div>

          <!-- Invitados (solo ACTIVO) -->
          <div v-if="activeTab === 'invitados'" class="space-y-2">
              <div v-if="!invitadosDisponibles.length" class="text-center py-8 text-surface-400 font-medium text-sm border-2 border-dashed border-surface-100 rounded-xl">No tienes invitados activos disponibles.</div>
              <div v-else v-for="invitado in invitadosDisponibles" :key="'inv-' + invitado.id" 
                   @click="handleToggle(invitado.id, 'Invitado', invitado.nombre)"
                   class="flex items-center justify-between p-3.5 rounded-xl border-2 transition-all cursor-pointer group"
                   :class="[isSelected(invitado.id, 'Invitado') ? 'border-primary-500 bg-primary-50/50' : 'border-blue-100 hover:border-primary-300 hover:bg-blue-50/30', remaining <= 0 && !isSelected(invitado.id, 'Invitado') ? 'opacity-40 cursor-not-allowed' : '']">
                  <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold uppercase shrink-0"
                           :class="isSelected(invitado.id, 'Invitado') ? 'bg-primary-600 text-white' : 'bg-blue-50 text-blue-600'">
                           {{ (invitado.nombre || 'I').charAt(0) }}
                      </div>
                      <div>
                          <div class="font-bold text-surface-900 text-sm">{{ invitado.nombre }}</div>
                          <div class="text-xs text-surface-400 font-medium mt-0.5">Invitado</div>
                      </div>
                  </div>
                  <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors shrink-0"
                       :class="isSelected(invitado.id, 'Invitado') ? 'bg-primary-600 border-primary-600 text-white' : 'border-blue-300 group-hover:border-primary-400'">
                       <svg v-if="isSelected(invitado.id, 'Invitado')" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </div>
              </div>
          </div>
      </div>

      <!-- Botón Continuar -->
      <div class="flex justify-end">
          <button @click="nextStep" class="w-full md:w-auto px-8 py-3.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl shadow-md transition-all flex justify-center items-center gap-2 border-none text-base active:scale-95">
              Confirmar Acompañantes <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>
      </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-8px); }
</style>