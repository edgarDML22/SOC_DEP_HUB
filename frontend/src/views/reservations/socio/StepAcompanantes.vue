<script setup>
import { ref, onMounted, computed } from 'vue';
import { useFamilyStore } from '@/stores/community/familyStore';
import { useFriendStore } from '@/stores/community/friendStore';
import { useGuestStore } from '@/stores/community/guestStore';
import { useReservationStore } from '@/stores/reservationStore';
import { storeToRefs } from 'pinia';

const familyStore = useFamilyStore();
const friendStore = useFriendStore();
const guestStore = useGuestStore();
const reservationStore = useReservationStore();

const { reservaPayload, espaciosPorDisciplina } = storeToRefs(reservationStore);

const activeTab = ref('familiares');

onMounted(async () => {
    familyStore.fetchMiembrosFamiliares();
    friendStore.fetchFriends();
    guestStore.fetchInvitados();
});

const limit = computed(() => espaciosPorDisciplina.value[0]?.capacidad_maxima || 1);
const remaining = computed(() => limit.value - 1 - reservaPayload.value.acompanantes.length);

const isSelected = (id, tipo) => {
    return reservaPayload.value.acompanantes.some(a => a.id === id && a.tipo === tipo);
};

const handleToggle = (item, tipo, nombre) => {
    if (!isSelected(item.id, tipo) && remaining.value <= 0) return;
    
    reservationStore.toggleAcompanante({
        id: item.id,
        tipo: tipo,
        nombre: nombre,
        ...item
    });
};

const nextStep = () => {
    reservationStore.intentarCambioPaso("5");
};
</script>

<template>
  <div class="flex flex-col w-full animate-fade-in">
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4 bg-surface-50 border border-surface-200 p-5 rounded-[2rem] shadow-sm">
          <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-white rounded-xl shadow-sm border border-surface-100 flex items-center justify-center text-primary-600 shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
              </div>
              <div>
                  <div class="font-bold text-surface-900 text-lg leading-tight">¿Con quién juegas?</div>
                  <div class="text-xs text-surface-500 font-medium uppercase tracking-wider mt-1">Opcional. Selecciona tus acompañantes</div>
              </div>
          </div>
          <div class="flex flex-col items-end shrink-0">
              <div class="text-sm font-bold text-surface-700">Lugares disponibles</div>
              <div class="text-2xl font-extrabold" :class="remaining > 0 ? 'text-green-600' : 'text-red-600'">
                  {{ remaining }} <span class="text-sm text-surface-500 font-medium">de {{ limit - 1 }}</span>
              </div>
          </div>
      </div>

      <!-- TABS -->
      <div class="flex p-1 bg-surface-100 rounded-2xl w-full mb-6">
          <button @click="activeTab = 'familiares'" :class="activeTab === 'familiares' ? 'bg-white shadow text-primary-700' : 'text-surface-600 hover:bg-surface-200/50'" class="flex-1 py-3 px-2 rounded-xl text-sm font-bold transition-all focus:outline-none">Familiares</button>
          <button @click="activeTab = 'amigos'" :class="activeTab === 'amigos' ? 'bg-white shadow text-primary-700' : 'text-surface-600 hover:bg-surface-200/50'" class="flex-1 py-3 px-2 rounded-xl text-sm font-bold transition-all focus:outline-none">Amigos</button>
          <button @click="activeTab = 'invitados'" :class="activeTab === 'invitados' ? 'bg-white shadow text-primary-700' : 'text-surface-600 hover:bg-surface-200/50'" class="flex-1 py-3 px-2 rounded-xl text-sm font-bold transition-all focus:outline-none">Invitados</button>
      </div>

      <div class="bg-white rounded-3xl border border-surface-200 p-2 md:p-4 mb-8 min-h-[300px]">
          <!-- Familiares -->
          <div v-if="activeTab === 'familiares'" class="space-y-2">
              <div v-if="familyStore.loading" class="text-center py-10 text-surface-500 font-medium">Cargando familiares...</div>
              <div v-else-if="!familyStore.miembrosFamiliares.length" class="text-center py-10 text-surface-500 font-medium border-2 border-dashed border-surface-100 rounded-2xl">No tienes familiares registrados.</div>
              <div v-else v-for="familiar in familyStore.miembrosFamiliares" :key="familiar.id" 
                   @click="handleToggle(familiar, 'Familiar', familiar.nombre_familiar)"
                   class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer group"
                   :class="[isSelected(familiar.id, 'Familiar') ? 'border-primary-500 bg-primary-50/50' : 'border-surface-100 hover:border-primary-200 hover:bg-surface-50', remaining <= 0 && !isSelected(familiar.id, 'Familiar') ? 'opacity-50 cursor-not-allowed hidden-hover' : '']">
                  <div class="flex items-center gap-4">
                      <div class="w-10 h-10 rounded-full flex items-center justify-center text-surface-600 font-bold uppercase shrink-0"
                           :class="isSelected(familiar.id, 'Familiar') ? 'bg-primary-600 text-white' : 'bg-surface-200'">
                           {{ familiar.nombre_familiar?.charAt(0) || 'F' }}
                      </div>
                      <div>
                          <div class="font-bold text-surface-900">{{ familiar.nombre_familiar }}</div>
                          <div class="text-xs text-surface-500 font-medium uppercase mt-0.5">{{ familiar.parentesco || 'Familiar' }}</div>
                      </div>
                  </div>
                  <div class="w-6 h-6 rounded-md border-2 flex items-center justify-center transition-colors shrink-0"
                       :class="isSelected(familiar.id, 'Familiar') ? 'bg-primary-600 border-primary-600 text-white' : 'border-surface-300 group-hover:border-primary-400'">
                       <svg v-if="isSelected(familiar.id, 'Familiar')" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </div>
              </div>
          </div>

          <!-- Amigos -->
          <div v-if="activeTab === 'amigos'" class="space-y-2">
              <div v-if="friendStore.loading" class="text-center py-10 text-surface-500 font-medium">Cargando amigos...</div>
              <div v-else-if="!friendStore.friends.length" class="text-center py-10 text-surface-500 font-medium border-2 border-dashed border-surface-100 rounded-2xl">No tienes amigos registrados.</div>
              <div v-else v-for="amigo in friendStore.friends" :key="amigo.id" 
                   @click="handleToggle(amigo, 'Amigo', amigo.nombre_completo || amigo.name)"
                   class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer group"
                   :class="[isSelected(amigo.id, 'Amigo') ? 'border-primary-500 bg-primary-50/50' : 'border-surface-100 hover:border-primary-200 hover:bg-surface-50', remaining <= 0 && !isSelected(amigo.id, 'Amigo') ? 'opacity-50 cursor-not-allowed hidden-hover' : '']">
                  <div class="flex items-center gap-4">
                      <div class="w-10 h-10 rounded-full flex items-center justify-center text-surface-600 font-bold uppercase shrink-0"
                           :class="isSelected(amigo.id, 'Amigo') ? 'bg-primary-600 text-white' : 'bg-surface-200'">
                           {{ (amigo.nombre_completo || amigo.name || 'A').charAt(0) }}
                      </div>
                      <div>
                          <div class="font-bold text-surface-900">{{ amigo.nombre_completo || amigo.name }}</div>
                          <div class="text-xs text-surface-500 font-medium uppercase mt-0.5">Amigo</div>
                      </div>
                  </div>
                  <div class="w-6 h-6 rounded-md border-2 flex items-center justify-center transition-colors shrink-0"
                       :class="isSelected(amigo.id, 'Amigo') ? 'bg-primary-600 border-primary-600 text-white' : 'border-surface-300 group-hover:border-primary-400'">
                       <svg v-if="isSelected(amigo.id, 'Amigo')" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </div>
              </div>
          </div>

          <!-- Invitados -->
          <div v-if="activeTab === 'invitados'" class="space-y-2">
              <div v-if="guestStore.loading" class="text-center py-10 text-surface-500 font-medium">Cargando invitados...</div>
              <div v-else-if="!guestStore.invitados.length" class="text-center py-10 text-surface-500 font-medium border-2 border-dashed border-surface-100 rounded-2xl">No tienes invitados registrados.</div>
              <div v-else v-for="invitado in guestStore.invitados" :key="invitado.id" 
                   @click="handleToggle(invitado, 'Invitado', invitado.nombre_completo || invitado.nombres)"
                   class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer group"
                   :class="[isSelected(invitado.id, 'Invitado') ? 'border-primary-500 bg-primary-50/50' : 'border-surface-100 hover:border-primary-200 hover:bg-surface-50', remaining <= 0 && !isSelected(invitado.id, 'Invitado') ? 'opacity-50 cursor-not-allowed hidden-hover' : '']">
                  <div class="flex items-center gap-4">
                      <div class="w-10 h-10 rounded-full flex items-center justify-center text-surface-600 font-bold uppercase shrink-0"
                           :class="isSelected(invitado.id, 'Invitado') ? 'bg-primary-600 text-white' : 'bg-surface-200'">
                           {{ (invitado.nombre_completo || invitado.nombres || 'I').charAt(0) }}
                      </div>
                      <div>
                          <div class="font-bold text-surface-900">{{ invitado.nombre_completo || invitado.nombres }}</div>
                          <div class="text-xs text-surface-500 font-medium uppercase mt-0.5">Invitado</div>
                      </div>
                  </div>
                  <div class="w-6 h-6 rounded-md border-2 flex items-center justify-center transition-colors shrink-0"
                       :class="isSelected(invitado.id, 'Invitado') ? 'bg-primary-600 border-primary-600 text-white' : 'border-surface-300 group-hover:border-primary-400'">
                       <svg v-if="isSelected(invitado.id, 'Invitado')" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </div>
              </div>
          </div>
      </div>

      <div class="flex justify-end mt-4">
          <button @click="nextStep" class="w-full md:w-auto px-10 py-4 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl shadow-[0_8px_20px_-6px_rgba(37,99,235,0.4)] hover:shadow-[0_12px_25px_-6px_rgba(37,99,235,0.5)] transition-all flex justify-center items-center gap-3 border-none text-lg active:scale-95">
              Continuar a Confirmación <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>
      </div>
  </div>
</template>
