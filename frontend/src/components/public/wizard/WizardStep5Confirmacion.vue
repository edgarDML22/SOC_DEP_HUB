<template>
  <div class="animate-fade-in">
    <div v-if="!store.exito">
      <h2 class="text-xl md:text-2xl font-bold text-white mb-2 text-center">Resumen de Registro</h2>
      <p class="text-slate-400 text-center mb-8 text-sm md:text-base">Verifica que tus datos sean correctos antes de finalizar.</p>

      <!-- Banner de Equipo (si aplica) -->
      <div v-if="store.tipo === 'EQUIPO'" class="bg-primary-600/10 border border-primary-600/20 rounded-2xl p-4 mb-6 flex items-center gap-3 text-base text-white animate-fade-in">
        <i class="fas fa-users text-primary-500 text-lg"></i>
        <span><strong>Equipo:</strong> {{ formatText(store.nombre_equipo) }}</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Resumen Capitán -->
        <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6 flex flex-col justify-between">
          <div>
            <h3 class="text-sm font-semibold text-primary-500 mb-4 flex items-center gap-2 border-b border-white/5 pb-2">
              <i class="fas fa-user-tag"></i> 
              {{ store.tipo === 'EQUIPO' ? 'Capitán' : 'Participante' }}
            </h3>
            <div class="flex flex-col gap-2.5 text-left text-sm text-slate-400">
              <p><strong class="text-slate-300 font-medium">Nombre:</strong> {{ formatText(store.datosCapitan.nombre) }} {{ formatText(store.datosCapitan.apellido) }}</p>
              <p><strong class="text-slate-300 font-medium">Email:</strong> <span class="break-all">{{ store.datosCapitan.email }}</span></p>
              <p><strong class="text-slate-300 font-medium">Teléfono:</strong> {{ store.datosCapitan.telefono }}</p>
              <p><strong class="text-slate-300 font-medium">F. Nacimiento:</strong> {{ formatDate(store.datosCapitan.fecha_nacimiento) }}</p>
              <p><strong class="text-slate-300 font-medium">Género:</strong> {{ getGeneroLabel(store.datosCapitan.genero) }}</p>
              <p><strong class="text-slate-300 font-medium">Ranking:</strong> {{ store.datosCapitan.ranking_declarado }}</p>
            </div>
          </div>
        </div>

        <!-- Resumen Compañero -->
        <div v-if="store.tipo === 'EQUIPO'" class="bg-white/[0.02] border border-white/5 rounded-2xl p-6 flex flex-col justify-between">
          <div>
            <h3 class="text-sm font-semibold text-primary-500 mb-4 flex items-center gap-2 border-b border-white/5 pb-2">
              <i class="fas fa-user-friends"></i> 
              Compañero
            </h3>
            <div class="flex flex-col gap-2.5 text-left text-sm text-slate-400">
              <p><strong class="text-slate-300 font-medium">Nombre:</strong> {{ formatText(store.datosCompanero.nombre) }} {{ formatText(store.datosCompanero.apellido) }}</p>
              <p><strong class="text-slate-300 font-medium">Email:</strong> <span class="break-all">{{ store.datosCompanero.email }}</span></p>
              <p><strong class="text-slate-300 font-medium">Teléfono:</strong> {{ store.datosCompanero.telefono }}</p>
              <p><strong class="text-slate-300 font-medium">F. Nacimiento:</strong> {{ formatDate(store.datosCompanero.fecha_nacimiento) }}</p>
              <p><strong class="text-slate-300 font-medium">Género:</strong> {{ getGeneroLabel(store.datosCompanero.genero) }}</p>
              <p><strong class="text-slate-300 font-medium">Ranking:</strong> {{ store.datosCompanero.ranking_declarado }}</p>
            </div>
          </div>
        </div>

        <!-- Resumen Documentos -->
        <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6 flex flex-col justify-between" :class="{ 'md:col-span-1': store.tipo === 'EQUIPO', 'md:col-span-2': store.tipo !== 'EQUIPO' }">
          <div>
            <h3 class="text-sm font-semibold text-primary-500 mb-4 flex items-center gap-2 border-b border-white/5 pb-2">
              <i class="fas fa-file-alt"></i> 
              Documentación
            </h3>
            <div class="flex flex-col gap-2 text-left">
              <ul class="flex flex-col gap-2">
                <li class="text-xs text-slate-400 flex items-center gap-2">
                  <i class="fas fa-check text-green-500"></i> INE (Capitán)
                </li>
                <li class="text-xs text-slate-400 flex items-center gap-2">
                  <i class="fas fa-check text-green-500"></i> CURP (Capitán)
                </li>
                <li class="text-xs text-slate-400 flex items-center gap-2">
                  <i class="fas fa-check text-green-500"></i> Carta Responsiva (Capitán)
                </li>
                <template v-if="store.tipo === 'EQUIPO'">
                  <li class="text-xs text-slate-400 flex items-center gap-2">
                    <i class="fas fa-check text-green-500"></i> INE (Compañero)
                  </li>
                  <li class="text-xs text-slate-400 flex items-center gap-2">
                    <i class="fas fa-check text-green-500"></i> CURP (Compañero)
                  </li>
                  <li class="text-xs text-slate-400 flex items-center gap-2">
                    <i class="fas fa-check text-green-500"></i> Carta Responsiva (Compañero)
                  </li>
                </template>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Banner de Error -->
      <p v-if="store.error" class="bg-red-500/10 border-l-4 border-red-500 text-red-500 p-4 rounded-xl mb-6 flex items-center gap-3 text-sm font-semibold">
        <i class="fas fa-exclamation-circle text-lg"></i>
        {{ store.error }}
      </p>

      <!-- Acciones -->
      <div class="flex justify-between border-t border-white/5 pt-6 mt-6">
        <button 
          type="button" 
          class="border border-white/10 hover:border-primary-600 hover:bg-primary-600/5 text-white rounded-xl py-3 px-8 font-bold transition-all duration-300 flex items-center gap-2 cursor-pointer" 
          :disabled="store.loading" 
          @click="prevStep"
        >
          <i class="fas fa-arrow-left"></i>
          Atrás
        </button>
        <button 
          type="button" 
          class="bg-primary-600 text-white rounded-xl py-3 px-8 font-bold shadow-lg shadow-primary-600/25 transition-all duration-300 hover:scale-[1.02] hover:bg-primary-700 hover:shadow-primary-700/40 flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100" 
          :disabled="store.loading" 
          @click="handleSubmit"
        >
          <template v-if="store.loading">
            <i class="fas fa-spinner fa-spin mr-1"></i>
            Procesando...
          </template>
          <template v-else>
            Finalizar Registro
            <i class="fas fa-check"></i>
          </template>
        </button>
      </div>
    </div>

    <!-- Éxito -->
    <div v-else class="text-center py-8 max-w-[500px] mx-auto animate-fade-in">
      <div class="text-6xl text-green-500 mb-6 animate-scale-in flex justify-center">
        <i class="fas fa-check-circle"></i>
      </div>
      <h2 class="text-2xl font-bold text-white mb-2">¡Registro Recibido!</h2>
      <p class="text-slate-400 mb-6 text-sm">
        Tu solicitud de pre-registro se ha procesado correctamente.
      </p>
      <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6 mb-8 flex flex-col gap-3 text-slate-400 text-sm">
        <p>Hemos enviado los detalles a:</p>
        <p class="text-lg font-extrabold text-primary-500 tracking-wider truncate">{{ store.datosCapitan.email }}</p>
        <p class="text-xs text-slate-500 mt-2 leading-relaxed">Recibirás confirmación y tu código QR en este correo una vez que sea validado.</p>
      </div>
      <button 
        class="bg-primary-600 text-white rounded-xl py-3 px-8 font-bold shadow-lg shadow-primary-600/25 transition-all duration-300 hover:scale-[1.02] hover:bg-primary-700 hover:shadow-primary-700/40 flex items-center justify-center gap-2 cursor-pointer mx-auto" 
        @click="finish"
      >
        Volver al Inicio
      </button>
    </div>
  </div>
</template>

<script setup>
import { usePreRegisterStore } from "@/stores/preRegisterStore";
import { useRoute, useRouter } from "vue-router";

const store = usePreRegisterStore();
const route = useRoute();
const router = useRouter();

const getGeneroLabel = (g) => {
  if (g === "M") return "Masculino";
  if (g === "F") return "Femenino";
  if (g === "X") return "No Binario / Otro";
  return "No especificado";
};

const formatText = (text) => {
  if (!text) return "";
  return text.trim()
             .split(/\s+/)
             .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
             .join(' ');
};

const formatDate = (dateStr) => {
  if (!dateStr) return "Por definir";
  try {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateStr).toLocaleDateString('es-MX', options);
  } catch (e) {
    return dateStr;
  }
};

const prevStep = () => {
  store.setPaso(4);
};

const handleSubmit = async () => {
  const id_torneo = route.params.id;
  try {
    await store.submitRegistro(id_torneo);
  } catch (error) {
    console.error("Error al enviar registro:", error);
  }
};

const finish = () => {
  store.resetStore();
  router.push("/");
};
</script>
