<template>
  <div class="animate-fade-in">
    <h2 class="text-xl md:text-2xl font-bold text-white mb-2 text-center">Documentación</h2>
    <p class="text-slate-400 text-center mb-8 text-sm md:text-base">Sube tus documentos en formato PDF para validar tu registro.</p>

    <div class="flex flex-col gap-6">
      <!-- Documentos Capitán -->
      <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-primary-500 mb-4">
          {{ store.tipo === 'EQUIPO' ? 'Documentos del Capitán' : 'Tus Documentos' }}
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <!-- INE -->
          <div class="flex flex-col gap-2 text-left">
            <label class="font-bold text-slate-400 text-xs uppercase tracking-wider">INE (PDF)</label>
            <div 
              class="relative h-[110px] border-2 border-dashed rounded-xl flex items-center justify-center transition-all duration-300 cursor-pointer overflow-hidden p-4"
              :class="store.archivos.ine ? 'border-green-500 bg-green-500/5 hover:bg-green-500/10' : 'border-white/10 bg-white/5 hover:border-primary-600 hover:bg-primary-600/5'"
            >
              <input type="file" @change="handleFile($event, 'ine')" accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-20" />
              <div class="flex flex-col items-center gap-2 text-center pointer-events-none z-10">
                <i class="fas text-2xl" :class="[store.archivos.ine ? 'fa-file-pdf text-green-400' : 'fa-cloud-upload-alt text-slate-400']"></i>
                <span class="text-[11px] font-medium leading-tight max-w-[140px] truncate" :class="store.archivos.ine ? 'text-green-400 font-semibold' : 'text-slate-500'">
                  {{ store.archivos.ine ? store.archivos.ine.name : 'Seleccionar archivo' }}
                </span>
              </div>
            </div>
          </div>

          <!-- CURP -->
          <div class="flex flex-col gap-2 text-left">
            <label class="font-bold text-slate-400 text-xs uppercase tracking-wider">CURP (PDF)</label>
            <div 
              class="relative h-[110px] border-2 border-dashed rounded-xl flex items-center justify-center transition-all duration-300 cursor-pointer overflow-hidden p-4"
              :class="store.archivos.curp ? 'border-green-500 bg-green-500/5 hover:bg-green-500/10' : 'border-white/10 bg-white/5 hover:border-primary-600 hover:bg-primary-600/5'"
            >
              <input type="file" @change="handleFile($event, 'curp')" accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-20" />
              <div class="flex flex-col items-center gap-2 text-center pointer-events-none z-10">
                <i class="fas text-2xl" :class="[store.archivos.curp ? 'fa-file-pdf text-green-400' : 'fa-cloud-upload-alt text-slate-400']"></i>
                <span class="text-[11px] font-medium leading-tight max-w-[140px] truncate" :class="store.archivos.curp ? 'text-green-400 font-semibold' : 'text-slate-500'">
                  {{ store.archivos.curp ? store.archivos.curp.name : 'Seleccionar archivo' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Carta Responsiva -->
          <div class="flex flex-col gap-2 text-left">
            <label class="font-bold text-slate-400 text-xs uppercase tracking-wider">Carta Responsiva (PDF)</label>
            <div 
              class="relative h-[110px] border-2 border-dashed rounded-xl flex items-center justify-center transition-all duration-300 cursor-pointer overflow-hidden p-4"
              :class="store.archivos.carta ? 'border-green-500 bg-green-500/5 hover:bg-green-500/10' : 'border-white/10 bg-white/5 hover:border-primary-600 hover:bg-primary-600/5'"
            >
              <input type="file" @change="handleFile($event, 'carta')" accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-20" />
              <div class="flex flex-col items-center gap-2 text-center pointer-events-none z-10">
                <i class="fas text-2xl" :class="[store.archivos.carta ? 'fa-file-pdf text-green-400' : 'fa-cloud-upload-alt text-slate-400']"></i>
                <span class="text-[11px] font-medium leading-tight max-w-[140px] truncate" :class="store.archivos.carta ? 'text-green-400 font-semibold' : 'text-slate-500'">
                  {{ store.archivos.carta ? store.archivos.carta.name : 'Seleccionar archivo' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Documentos Compañero (si aplica) -->
      <div v-if="store.tipo === 'EQUIPO'" class="bg-white/[0.02] border border-white/5 rounded-2xl p-6">
        <h3 class="text-base font-semibold text-primary-500 mb-4">Documentos del Compañero</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <!-- Compañero INE -->
          <div class="flex flex-col gap-2 text-left">
            <label class="font-bold text-slate-400 text-xs uppercase tracking-wider">INE (PDF)</label>
            <div 
              class="relative h-[110px] border-2 border-dashed rounded-xl flex items-center justify-center transition-all duration-300 cursor-pointer overflow-hidden p-4"
              :class="store.archivos.companero_ine ? 'border-green-500 bg-green-500/5 hover:bg-green-500/10' : 'border-white/10 bg-white/5 hover:border-primary-600 hover:bg-primary-600/5'"
            >
              <input type="file" @change="handleFile($event, 'companero_ine')" accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-20" />
              <div class="flex flex-col items-center gap-2 text-center pointer-events-none z-10">
                <i class="fas text-2xl" :class="[store.archivos.companero_ine ? 'fa-file-pdf text-green-400' : 'fa-cloud-upload-alt text-slate-400']"></i>
                <span class="text-[11px] font-medium leading-tight max-w-[140px] truncate" :class="store.archivos.companero_ine ? 'text-green-400 font-semibold' : 'text-slate-500'">
                  {{ store.archivos.companero_ine ? store.archivos.companero_ine.name : 'Seleccionar archivo' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Compañero CURP -->
          <div class="flex flex-col gap-2 text-left">
            <label class="font-bold text-slate-400 text-xs uppercase tracking-wider">CURP (PDF)</label>
            <div 
              class="relative h-[110px] border-2 border-dashed rounded-xl flex items-center justify-center transition-all duration-300 cursor-pointer overflow-hidden p-4"
              :class="store.archivos.companero_curp ? 'border-green-500 bg-green-500/5 hover:bg-green-500/10' : 'border-white/10 bg-white/5 hover:border-primary-600 hover:bg-primary-600/5'"
            >
              <input type="file" @change="handleFile($event, 'companero_curp')" accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-20" />
              <div class="flex flex-col items-center gap-2 text-center pointer-events-none z-10">
                <i class="fas text-2xl" :class="[store.archivos.companero_curp ? 'fa-file-pdf text-green-400' : 'fa-cloud-upload-alt text-slate-400']"></i>
                <span class="text-[11px] font-medium leading-tight max-w-[140px] truncate" :class="store.archivos.companero_curp ? 'text-green-400 font-semibold' : 'text-slate-500'">
                  {{ store.archivos.companero_curp ? store.archivos.companero_curp.name : 'Seleccionar archivo' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Compañero Carta Responsiva -->
          <div class="flex flex-col gap-2 text-left">
            <label class="font-bold text-slate-400 text-xs uppercase tracking-wider">Carta Responsiva (PDF)</label>
            <div 
              class="relative h-[110px] border-2 border-dashed rounded-xl flex items-center justify-center transition-all duration-300 cursor-pointer overflow-hidden p-4"
              :class="store.archivos.companero_carta ? 'border-green-500 bg-green-500/5 hover:bg-green-500/10' : 'border-white/10 bg-white/5 hover:border-primary-600 hover:bg-primary-600/5'"
            >
              <input type="file" @change="handleFile($event, 'companero_carta')" accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-20" />
              <div class="flex flex-col items-center gap-2 text-center pointer-events-none z-10">
                <i class="fas text-2xl" :class="[store.archivos.companero_carta ? 'fa-file-pdf text-green-400' : 'fa-cloud-upload-alt text-slate-400']"></i>
                <span class="text-[11px] font-medium leading-tight max-w-[140px] truncate" :class="store.archivos.companero_carta ? 'text-green-400 font-semibold' : 'text-slate-500'">
                  {{ store.archivos.companero_carta ? store.archivos.companero_carta.name : 'Seleccionar archivo' }}
                </span>
              </div>
            </div>
          </div>
        </div>
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
        type="button" 
        class="bg-primary-600 text-white rounded-xl py-3 px-8 font-bold shadow-lg shadow-primary-600/25 transition-all duration-300 hover:scale-[1.02] hover:bg-primary-700 hover:shadow-primary-700/40 flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100" 
        :disabled="!allFilesSelected" 
        @click="nextStep"
      >
        Siguiente
        <i class="fas fa-arrow-right"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { usePreRegisterStore } from "@/stores/preRegisterStore";

const store = usePreRegisterStore();

const handleFile = (event, key) => {
  const file = event.target.files[0];
  if (file && file.type === "application/pdf") {
    store.setArchivo(key, file);
  } else {
    alert("Por favor selecciona un archivo PDF válido.");
    event.target.value = null;
  }
};

const allFilesSelected = computed(() => {
  const capitanFiles = store.archivos.ine && store.archivos.curp && store.archivos.carta;
  if (store.tipo === "INDIVIDUAL") return capitanFiles;
  
  const companeroFiles = store.archivos.companero_ine && store.archivos.companero_curp && store.archivos.companero_carta;
  return capitanFiles && companeroFiles;
});

const prevStep = () => {
  store.setPaso(store.tipo === "EQUIPO" ? 3 : 2);
};

const nextStep = () => {
  if (allFilesSelected.value) {
    store.setPaso(5);
  }
};
</script>
