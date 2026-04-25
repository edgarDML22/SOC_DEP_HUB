<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useLudotecaOperativaStore } from '@/stores/ludoteca/ludotecaOperativaStore';
import BloqueoTurno from '@/components/instructor/BloqueoTurno.vue';

const activeTab = ref('activos');

const store = useLudotecaOperativaStore();
let timer = null;

onMounted(() => {
  store.fetchEstancias();

  // Validar el turno cada minuto
  timer = setInterval(() => {
    if (store.isTurnoActivo) {
        store.fetchEstancias(); 
    }
  }, 60000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});

// Helpers para la UI
const moverAInactivo = (id) => {
    store.cambiarEstatusEstancia(id, 'Inactivo');
};

const marcarSalida = (id) => {
    store.cambiarEstatusEstancia(id, 'Entregado');
};

const reingresarActivo = (id) => {
    store.cambiarEstatusEstancia(id, 'Activo');
};
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans pb-24 md:pb-8">
    <div class="max-w-5xl mx-auto p-4 md:p-8 space-y-6">
      
      <!-- Encabezado -->
      <div class="flex flex-col gap-1 md:gap-1.5 pt-2">
        <h2 class="text-2xl md:text-3xl font-bold text-surface-900 tracking-tight m-0">
          Tablero Operativo
        </h2>
        <p class="text-surface-500 font-medium text-sm md:text-base m-0">Ludoteca</p>
      </div>

      <!-- Errores Globales (Rollback / Red) -->
      <div v-if="store.error" class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl flex items-center gap-3 shadow-sm animate-pulse">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span class="text-sm font-medium">{{ store.error }}</span>
      </div>

      <!-- TIME-GATING (Bloqueo) -->
      <BloqueoTurno v-if="!store.isTurnoActivo && !store.loading" @retry="store.fetchEstancias" />

      <!-- TABLERO 3 ESTADOS -->
      <div v-else-if="store.isTurnoActivo" class="bg-white rounded-3xl border border-surface-200 p-2 md:p-6 shadow-sm overflow-hidden animate-fade-in">
        
        <!-- SISTEMA DE PESTAÑAS PERSONALIZADO -->
        <div class="flex border-b border-surface-200 mb-6">
            <button @click="activeTab = 'activos'"
                    class="flex-1 py-3 text-center font-bold text-sm md:text-base border-b-4 transition-colors rounded-tl-xl"
                    :class="activeTab === 'activos' ? 'border-green-500 text-green-600 bg-green-50/50' : 'border-transparent text-surface-500 hover:text-surface-700 hover:border-surface-300 hover:bg-surface-50'">
                Activos
            </button>
            <button @click="activeTab = 'inactivos'"
                    class="flex-1 py-3 text-center font-bold text-sm md:text-base border-b-4 transition-colors"
                    :class="activeTab === 'inactivos' ? 'border-red-500 text-red-600 bg-red-50/50' : 'border-transparent text-surface-500 hover:text-surface-700 hover:border-surface-300 hover:bg-surface-50'">
                Inactivos
            </button>
            <button @click="activeTab = 'entregados'"
                    class="flex-1 py-3 text-center font-bold text-sm md:text-base border-b-4 transition-colors rounded-tr-xl"
                    :class="activeTab === 'entregados' ? 'border-blue-500 text-blue-600 bg-blue-50/50' : 'border-transparent text-surface-500 hover:text-surface-700 hover:border-surface-300 hover:bg-surface-50'">
                Entregados
            </button>
        </div>

        <!-- CONTENIDO PESTAÑA: ACTIVOS -->
        <div v-show="activeTab === 'activos'" class="animate-fade-in">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-if="store.estanciasActivas.length === 0" class="col-span-full h-32 flex items-center justify-center rounded-2xl border border-dashed border-surface-300 bg-surface-50">
                    <p class="text-surface-500 font-medium text-sm text-center">No hay niños activos en este momento.</p>
                </div>

                <!-- Tarjetas de Niños Activos -->
                <div v-for="nino in store.estanciasActivas" :key="nino.id" class="bg-white rounded-3xl border border-green-200 p-5 shadow-sm transition-all hover:shadow-md flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-lg text-surface-900 leading-tight">{{ nino.nombre_nino || 'Niño sin nombre' }}</h4>
                        <p class="text-surface-500 text-sm mt-1">Tutor: {{ nino.nombre_tutor || 'No especificado' }}</p>
                    </div>
                    
                    <div class="flex gap-2 mt-5">
                        <button @click="moverAInactivo(nino.id)" class="bg-surface-100 hover:bg-surface-200 text-surface-700 font-semibold rounded-xl px-2 py-3 flex-1 text-center transition-all text-sm border border-surface-200 active:scale-95">
                            Incidencia
                        </button>
                        <button @click="marcarSalida(nino.id)" class="bg-green-600 hover:bg-green-700 text-white rounded-xl px-2 py-3 font-bold shadow-lg shadow-green-700/20 active:scale-95 transition-all flex-1 text-center border border-green-500 text-sm">
                            Marcar Salida
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENIDO PESTAÑA: INACTIVOS -->
        <div v-show="activeTab === 'inactivos'" class="animate-fade-in">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-if="store.estanciasInactivas.length === 0" class="col-span-full h-32 flex items-center justify-center rounded-2xl border border-dashed border-surface-300 bg-surface-50">
                    <p class="text-surface-500 font-medium text-sm text-center">No hay incidencias activas.</p>
                </div>

                <div v-for="nino in store.estanciasInactivas" :key="nino.id" class="bg-red-50/30 rounded-3xl border border-red-200 p-5 shadow-sm transition-all hover:shadow-md flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-lg text-red-900 leading-tight">{{ nino.nombre_nino }}</h4>
                        <p class="text-red-700/70 text-sm mt-1">Tutor: {{ nino.nombre_tutor }}</p>
                    </div>
                    <div class="flex gap-2 mt-5">
                        <button @click="reingresarActivo(nino.id)" class="bg-white hover:bg-red-50 text-red-700 font-semibold rounded-xl px-2 py-3 flex-1 text-center transition-all text-sm border border-red-200 active:scale-95">
                            Resolver / Reingresar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENIDO PESTAÑA: ENTREGADOS -->
        <div v-show="activeTab === 'entregados'" class="animate-fade-in">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-if="store.estanciasEntregadas.length === 0" class="col-span-full h-32 flex items-center justify-center rounded-2xl border border-dashed border-surface-300 bg-surface-50">
                    <p class="text-surface-500 font-medium text-sm text-center">No se han entregado niños hoy.</p>
                </div>

                <div v-for="nino in store.estanciasEntregadas" :key="nino.id" class="bg-surface-50 rounded-3xl border border-blue-200 p-5 shadow-sm transition-all flex flex-col justify-between opacity-90">
                    <div>
                        <h4 class="font-bold text-lg text-surface-900 leading-tight">{{ nino.nombre_nino }}</h4>
                        <p class="text-surface-500 text-sm mt-1">Tutor: {{ nino.nombre_tutor }}</p>
                    </div>
                    <div class="mt-4 flex">
                        <span class="text-xs font-semibold text-blue-700 bg-blue-100 px-3 py-1.5 rounded-lg inline-block border border-blue-200">
                            Entregado a las: {{ nino.hora_salida || 'Sin registrar' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

      </div>
      
      <!-- Cargando -->
      <div v-if="store.loading && !store.isTurnoActivo" class="flex justify-center p-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>

    </div>
  </main>
</template>
