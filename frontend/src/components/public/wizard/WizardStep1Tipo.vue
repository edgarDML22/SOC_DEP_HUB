<template>
  <div class="animate-fade-in">
    <h2 class="text-xl md:text-2xl font-bold text-white mb-2 text-center">Selecciona el tipo de registro</h2>
    <p class="text-slate-400 text-center mb-8 text-sm md:text-base">¿Participarás de forma individual o con un compañero?</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
      <!-- Individual Card -->
      <div 
        class="group relative bg-white/5 backdrop-blur-md border rounded-2xl p-6 text-center cursor-pointer transition-all duration-300 hover:-translate-y-1"
        :class="store.tipo === 'INDIVIDUAL' ? 'border-primary-600 bg-primary-600/10 shadow-lg shadow-primary-600/20' : 'border-white/10 hover:border-primary-600/40 hover:bg-primary-600/5'"
        @click="selectTipo('INDIVIDUAL')"
      >
        <div class="text-4xl text-primary-600 mb-4">
          <i class="fas fa-user"></i>
        </div>
        <h3 class="text-lg font-bold text-white mb-1">Individual</h3>
        <p class="text-slate-400 text-sm">Registro para un solo jugador.</p>
        <div 
          class="absolute top-4 right-4 text-primary-600 text-lg transition-opacity duration-300"
          :class="store.tipo === 'INDIVIDUAL' ? 'opacity-100' : 'opacity-0'"
        >
          <i class="fas fa-check-circle"></i>
        </div>
      </div>

      <!-- Equipo Card -->
      <div 
        class="group relative bg-white/5 backdrop-blur-md border rounded-2xl p-6 text-center cursor-pointer transition-all duration-300 hover:-translate-y-1"
        :class="store.tipo === 'EQUIPO' ? 'border-primary-600 bg-primary-600/10 shadow-lg shadow-primary-600/20' : 'border-white/10 hover:border-primary-600/40 hover:bg-primary-600/5'"
        @click="selectTipo('EQUIPO')"
      >
        <div class="text-4xl text-primary-600 mb-4">
          <i class="fas fa-users"></i>
        </div>
        <h3 class="text-lg font-bold text-white mb-1">En Pareja</h3>
        <p class="text-slate-400 text-sm">Registro para un equipo de dos jugadores.</p>
        <div 
          class="absolute top-4 right-4 text-primary-600 text-lg transition-opacity duration-300"
          :class="store.tipo === 'EQUIPO' ? 'opacity-100' : 'opacity-0'"
        >
          <i class="fas fa-check-circle"></i>
        </div>
      </div>
    </div>

    <!-- Nombre de Equipo -->
    <div v-if="store.tipo === 'EQUIPO'" class="max-w-[480px] mx-auto mb-8 bg-white/[0.02] border border-white/5 rounded-2xl p-6 animate-fade-in">
      <div class="flex flex-col gap-2 text-left">
        <label for="nombre_equipo" class="font-bold text-slate-300 text-sm">Nombre del Equipo</label>
        <input 
          type="text" 
          id="nombre_equipo" 
          v-model="store.nombre_equipo" 
          placeholder="Ej. Los Guerreros del Padel" 
          required
          class="bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-primary-600 focus:bg-white/[0.08] transition-all duration-300 text-sm font-medium"
        />
        <span class="text-xs text-slate-500 mt-1 block">Ingresa el nombre con el que se identificará a tu pareja en el torneo.</span>
      </div>
    </div>

    <!-- Acciones -->
    <div class="flex justify-end border-t border-white/5 pt-6">
      <button 
        class="bg-primary-600 text-white rounded-xl py-3 px-8 font-bold shadow-lg shadow-primary-600/25 transition-all duration-300 hover:scale-[1.02] hover:bg-primary-700 hover:shadow-primary-700/40 flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
        :disabled="!isTipoValido"
        @click="nextStep"
      >
        Continuar
        <i class="fas fa-arrow-right"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { usePreRegisterStore } from "@/stores/preRegisterStore";

const store = usePreRegisterStore();

const selectTipo = (tipo) => {
  store.setTipo(tipo);
};

const isTipoValido = computed(() => {
  if (!store.tipo) return false;
  if (store.tipo === "EQUIPO") {
    return store.nombre_equipo && store.nombre_equipo.trim().length > 0;
  }
  return true;
});

const nextStep = () => {
  if (isTipoValido.value) {
    store.setPaso(2);
  }
};
</script>
