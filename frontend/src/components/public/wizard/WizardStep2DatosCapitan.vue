<template>
  <div class="animate-fade-in">
    <h2 class="text-xl md:text-2xl font-bold text-white mb-2 text-center">
      {{ store.tipo === 'EQUIPO' ? 'Datos del Capitán' : 'Datos Personales' }}
    </h2>
    <p class="text-slate-400 text-center mb-8 text-sm md:text-base">Ingresa la información del participante principal.</p>

    <form @submit.prevent="nextStep" class="max-w-[600px] mx-auto">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
        <!-- Nombre -->
        <div class="flex flex-col gap-2 text-left">
          <label for="nombre" class="font-bold text-slate-300 text-sm">Nombre(s)</label>
          <input 
            type="text" 
            id="nombre" 
            v-model="formData.nombre" 
            placeholder="Ej. Juan" 
            required
            class="bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-primary-600 focus:bg-white/[0.08] transition-all duration-300 text-sm font-medium w-full"
          />
        </div>

        <!-- Apellido -->
        <div class="flex flex-col gap-2 text-left">
          <label for="apellido" class="font-bold text-slate-300 text-sm">Apellido(s)</label>
          <input 
            type="text" 
            id="apellido" 
            v-model="formData.apellido" 
            placeholder="Ej. Pérez" 
            required
            class="bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-primary-600 focus:bg-white/[0.08] transition-all duration-300 text-sm font-medium w-full"
          />
        </div>

        <!-- Correo -->
        <div class="flex flex-col gap-2 text-left">
          <label for="email" class="font-bold text-slate-300 text-sm">Correo Electrónico</label>
          <input 
            type="email" 
            id="email" 
            v-model="formData.email" 
            placeholder="juan.perez@ejemplo.com" 
            required
            class="bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-primary-600 focus:bg-white/[0.08] transition-all duration-300 text-sm font-medium w-full"
          />
        </div>

        <!-- Teléfono -->
        <div class="flex flex-col gap-2 text-left">
          <label for="telefono" class="font-bold text-slate-300 text-sm">Teléfono de Contacto</label>
          <input 
            type="tel" 
            id="telefono" 
            v-model="formData.telefono" 
            placeholder="10 dígitos" 
            required
            class="bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-primary-600 focus:bg-white/[0.08] transition-all duration-300 text-sm font-medium w-full"
          />
        </div>

        <!-- Fecha de Nacimiento -->
        <div class="flex flex-col gap-2 text-left">
          <label for="fecha_nacimiento" class="font-bold text-slate-300 text-sm">Fecha de Nacimiento</label>
          <input 
            type="date" 
            id="fecha_nacimiento" 
            v-model="formData.fecha_nacimiento" 
            required
            class="bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-primary-600 focus:bg-white/[0.08] transition-all duration-300 text-sm font-medium w-full"
          />
        </div>

        <!-- Género -->
        <div class="flex flex-col gap-2 text-left">
          <label for="genero" class="font-bold text-slate-300 text-sm">Género</label>
          <select 
            id="genero" 
            v-model="formData.genero" 
            required
            class="bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-primary-600 focus:bg-white/[0.08] transition-all duration-300 text-sm font-medium w-full cursor-pointer"
          >
            <option value="" disabled class="bg-slate-800 text-white">Selecciona una opción</option>
            <option value="M" class="bg-slate-800 text-white">Masculino</option>
            <option value="F" class="bg-slate-800 text-white">Femenino</option>
            <option value="X" class="bg-slate-800 text-white">No Binario / Otro</option>
          </select>
        </div>

        <!-- Ranking Declarado (Full Width) -->
        <div class="flex flex-col gap-2 text-left sm:col-span-2">
          <label for="ranking" class="font-bold text-slate-300 text-sm">Ranking Declarado (0 - 500)</label>
          <input 
            type="number" 
            id="ranking" 
            v-model.number="formData.ranking_declarado" 
            min="0" 
            max="500" 
            placeholder="Ej. 250" 
            required
            class="bg-white/5 border border-white/10 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:outline-none focus:border-primary-600 focus:bg-white/[0.08] transition-all duration-300 text-sm font-medium w-full"
          />
          <span class="text-xs text-slate-500 mt-1 block">Ingresa un valor entre 0 y 500 basado en tu nivel actual.</span>
          <p v-if="rankingError" class="text-xs text-red-500 mt-1 block font-semibold">{{ rankingError }}</p>
        </div>
      </div>

      <!-- Acciones -->
      <div class="flex justify-between border-t border-white/5 pt-6 mt-6">
        <button 
          type="button" 
          class="border border-white/10 hover:border-primary-600 hover:bg-primary-600/5 text-white rounded-xl py-3 px-8 font-bold transition-all duration-300 flex items-center gap-2 cursor-pointer" 
          @click="prevStep"
        >
          <i class="fas fa-arrow-left"></i>
          Atrás
        </button>
        <button 
          type="submit" 
          class="bg-primary-600 text-white rounded-xl py-3 px-8 font-bold shadow-lg shadow-primary-600/25 transition-all duration-300 hover:scale-[1.02] hover:bg-primary-700 hover:shadow-primary-700/40 flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100" 
          :disabled="!isFormValid"
        >
          Siguiente
          <i class="fas fa-arrow-right"></i>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, reactive } from "vue";
import { usePreRegisterStore } from "@/stores/preRegisterStore";

const store = usePreRegisterStore();

const formData = reactive({
  nombre: store.datosCapitan.nombre,
  apellido: store.datosCapitan.apellido,
  email: store.datosCapitan.email,
  telefono: store.datosCapitan.telefono,
  ranking_declarado: store.datosCapitan.ranking_declarado,
  fecha_nacimiento: store.datosCapitan.fecha_nacimiento,
  genero: store.datosCapitan.genero,
});

const rankingError = computed(() => {
  if (formData.ranking_declarado !== null) {
    if (formData.ranking_declarado < 0 || formData.ranking_declarado > 500) {
      return "Ingresa un valor entre 0 y 500";
    }
  }
  return "";
});

const isFormValid = computed(() => {
  return (
    formData.nombre &&
    formData.apellido &&
    formData.email &&
    formData.telefono &&
    formData.fecha_nacimiento &&
    formData.genero &&
    formData.ranking_declarado !== null &&
    !rankingError.value
  );
});

const prevStep = () => {
  store.setDatos("capitan", formData);
  store.setPaso(1);
};

const nextStep = () => {
  if (isFormValid.value) {
    store.setDatos("capitan", formData);
    store.setPaso(store.tipo === "EQUIPO" ? 3 : 4);
  }
};
</script>
