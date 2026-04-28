<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useLudotecaOperativaStore } from '@/stores/ludoteca/ludotecaOperativaStore';
import { useInstructorStore } from '@/stores/profiles/instructorStore';
import BloqueoTurno from '@/components/instructor/BloqueoTurno.vue';

const searchQuery = ref('');
const activeTab = ref('activos'); 
 
// Estado para modales
const modalIngresoInfo = ref({ visible: false, idEstancia: null, tipoUsuario: 'SOCIO_TITULAR', correo: '' });
const modalSalidaInfo = ref({ visible: false, idEstancia: null, tipoUsuario: 'SOCIO_TITULAR', correo: '' });

const store = useLudotecaOperativaStore();
const instructorStore = useInstructorStore();
let timer = null;

watch(() => instructorStore.idInstructor, (newId) => {
    if (newId) {
        store.fetchEstancias();
    }
});

onMounted(() => {
  if (instructorStore.idInstructor) {
      store.fetchEstancias();
  }

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
    store.cambiarEstatusEstancia(id, 'INACTIVO');
};

const abrirModalSalida = (id) => {
    modalSalidaInfo.value = { visible: true, idEstancia: id, tipoUsuario: 'SOCIO_TITULAR', correo: '' };
};

const confirmarSalida = () => {
    store.cambiarEstatusEstancia(modalSalidaInfo.value.idEstancia, 'ENTREGADO', {
        tipo_usuario: modalSalidaInfo.value.tipoUsuario,
        correo_receptor: modalSalidaInfo.value.correo
    });
    modalSalidaInfo.value.visible = false;
};

const abrirModalIngreso = (id) => {
    modalIngresoInfo.value = { visible: true, idEstancia: id, tipoUsuario: 'SOCIO_TITULAR', correo: '' };
};

const confirmarIngreso = () => {
    store.registrarIngreso(modalIngresoInfo.value.idEstancia, {
        tipo_usuario: modalIngresoInfo.value.tipoUsuario,
        correo: modalIngresoInfo.value.correo
    });
    modalIngresoInfo.value.visible = false;
};

const inactivosFiltrados = computed(() => {
    if (searchQuery.value.length < 2) return [];
    const query = searchQuery.value.toLowerCase();
    return store.estanciasInactivas.filter(nino => 
        nino.nombre_nino?.toLowerCase().includes(query) || 
        nino.nombre_tutor?.toLowerCase().includes(query)
    );
});

const formatTime = (timeString) => {
    if (!timeString) return 'Sin registrar';
    
    try {
        const date = new Date(timeString);
        if (isNaN(date.getTime())) {
            const today = new Date().toISOString().split('T')[0];
            const normalizedDate = new Date(`${today}T${timeString}`);
            if (!isNaN(normalizedDate.getTime())) {
                return new Intl.DateTimeFormat('es-MX', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                    timeZone: 'America/Mexico_City'
                }).format(normalizedDate);
            }
            return timeString;
        }
        return new Intl.DateTimeFormat('es-MX', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
            timeZone: 'America/Mexico_City'
        }).format(date);
    } catch (e) {
        return timeString;
    }
};
</script>

<template>
    <div class="space-y-6">
      
      <!-- Errores Globales -->
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

                <div v-for="nino in store.estanciasActivas" :key="nino.id_registro" class="bg-white rounded-3xl border border-green-200 p-5 shadow-sm transition-all hover:shadow-md flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-lg text-surface-900 leading-tight">{{ nino.nombre_nino || 'Niño sin nombre' }}</h4>
                        <p class="text-surface-500 text-sm mt-1">Tutor: {{ nino.nombre_tutor || 'No especificado' }}</p>
                    </div>
                    
                    <div class="flex gap-2 mt-5">
                        <button @click="moverAInactivo(nino.id_registro)" class="bg-surface-100 hover:bg-surface-200 text-surface-700 font-semibold rounded-xl px-2 py-3 flex-1 text-center transition-all text-sm border border-surface-200 active:scale-95">
                            Incidencia
                        </button>
                        <button @click="abrirModalSalida(nino.id_registro)" class="bg-green-600 hover:bg-green-700 text-white rounded-xl px-2 py-3 font-bold shadow-lg shadow-green-700/20 active:scale-95 transition-all flex-1 text-center border border-green-500 text-sm">
                            Marcar Salida
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENIDO PESTAÑA: INACTIVOS -->
        <div v-show="activeTab === 'inactivos'" class="animate-fade-in">
            <div class="mb-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-surface-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input 
                    v-model="searchQuery" 
                    type="text" 
                    placeholder="Buscar a otros niños..." 
                    class="w-full pl-10 pr-4 py-3 bg-surface-50 border border-surface-300 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none text-surface-900"
                />
            </div>

            <div v-if="searchQuery.length < 2" class="col-span-full h-32 flex items-center justify-center rounded-2xl border border-dashed border-surface-300 bg-surface-50">
                <p class="text-surface-500 font-medium text-sm text-center">Ingresa al menos 2 caracteres.</p>
            </div>
            
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="nino in inactivosFiltrados" :key="nino.id_registro" class="bg-surface-50 rounded-3xl border border-surface-200 p-5 shadow-sm transition-all hover:shadow-md flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-lg text-surface-900 leading-tight">{{ nino.nombre_nino }}</h4>
                        <p class="text-surface-500 text-sm mt-1">Tutor: {{ nino.nombre_tutor }}</p>
                    </div>
                    <div class="flex gap-2 mt-5">
                        <button @click="abrirModalIngreso(nino.id_registro)" class="bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl px-2 py-3 flex-1 text-center transition-all text-sm shadow-md active:scale-95">
                            Ingresar
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

                <div v-for="nino in store.estanciasEntregadas" :key="nino.id_registro" class="bg-surface-50 rounded-3xl border border-blue-200 p-5 shadow-sm opacity-90">
                    <h4 class="font-bold text-lg text-surface-900 leading-tight">{{ nino.nombre_nino }}</h4>
                    <p class="text-surface-500 text-sm mt-1">Tutor: {{ nino.nombre_tutor }}</p>
                    <div class="mt-4 flex">
                        <span class="text-xs font-semibold text-blue-700 bg-blue-100 px-3 py-1.5 rounded-lg inline-block border border-blue-200">
                            Entregado: {{ formatTime(nino.hora_salida) }}
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

      <!-- Modales -->
      <Teleport to="body">
          <div v-if="modalIngresoInfo.visible" class="fixed inset-0 z-1000 flex items-center justify-center bg-surface-900/50 backdrop-blur-sm p-4">
              <div class="bg-white rounded-3xl p-6 md:p-8 w-full max-w-md shadow-2xl animate-fade-in">
                  <h3 class="text-xl font-bold text-surface-900 mb-4">Registrar Ingreso</h3>
                  <div class="space-y-4">
                      <select v-model="modalIngresoInfo.tipoUsuario" class="w-full bg-surface-50 border border-surface-300 rounded-xl px-4 py-3 outline-none">
                          <option value="SOCIO_TITULAR">Socio Titular</option>
                          <option value="MIEMBRO_FAMILIAR">Miembro Familiar</option>
                      </select>
                      <input v-model="modalIngresoInfo.correo" type="email" placeholder="Correo de quien entrega" class="w-full bg-surface-50 border border-surface-300 rounded-xl px-4 py-3 outline-none" />
                  </div>
                  <div class="flex gap-3 mt-8">
                      <button @click="modalIngresoInfo.visible = false" class="flex-1 py-3 bg-surface-100 rounded-xl font-bold">Cancelar</button>
                      <button @click="confirmarIngreso" class="flex-1 py-3 bg-primary-600 text-white rounded-xl font-bold shadow-md">Confirmar</button>
                  </div>
              </div>
          </div>

          <div v-if="modalSalidaInfo.visible" class="fixed inset-0 z-1000 flex items-center justify-center bg-surface-900/50 backdrop-blur-sm p-4">
              <div class="bg-white rounded-3xl p-6 md:p-8 w-full max-w-md shadow-2xl animate-fade-in">
                  <h3 class="text-xl font-bold text-surface-900 mb-4">Registrar Salida</h3>
                  <div class="space-y-4">
                      <select v-model="modalSalidaInfo.tipoUsuario" class="w-full bg-surface-50 border border-surface-300 rounded-xl px-4 py-3 outline-none">
                          <option value="SOCIO_TITULAR">Socio Titular</option>
                          <option value="MIEMBRO_FAMILIAR">Miembro Familiar</option>
                      </select>
                      <input v-model="modalSalidaInfo.correo" type="email" placeholder="Correo de quien recibe" class="w-full bg-surface-50 border border-surface-300 rounded-xl px-4 py-3 outline-none" />
                  </div>
                  <div class="flex gap-3 mt-8">
                      <button @click="modalSalidaInfo.visible = false" class="flex-1 py-3 bg-surface-100 rounded-xl font-bold">Cancelar</button>
                      <button @click="confirmarSalida" class="flex-1 py-3 bg-green-600 text-white rounded-xl font-bold shadow-md">Confirmar</button>
                  </div>
              </div>
          </div>
      </Teleport>

    </div>
</template>
