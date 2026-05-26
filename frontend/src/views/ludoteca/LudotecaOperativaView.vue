<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useLudotecaOperativaStore } from '@/stores/ludoteca/ludotecaOperativaStore';
import { useInstructorStore } from '@/stores/profiles/instructorStore';
import { useAlerts } from '@/composables/useAlerts';
import BloqueoTurno from '@/components/instructor/BloqueoTurno.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';

const { actionToast, showLoading, closeLoading, successModal, errorModal, confirmWarning } = useAlerts();

const searchQuery = ref('');
const activeTab = ref('activos'); 
 
// Estado para modales
const modalIngresoInfo = ref({ visible: false, idEstancia: null, correo: '' });
const modalSalidaInfo = ref({ visible: false, idEstancia: null, tipoUsuario: 'SOCIO_TITULAR', correo: '' });
const modalIncidenciaInfo = ref({ visible: false, idEstancia: null, correo: '' });

const store = useLudotecaOperativaStore();
const instructorStore = useInstructorStore();
let timer = null;

watch(() => instructorStore.idInstructor, (newId) => {
    if (newId) {
        store.fetchEstancias();
    }
});

const onVisibilityChange = () => {
  if (!document.hidden && store.isTurnoActivo) {
    store.fetchEstancias();
  }
};

onMounted(() => {
  if (instructorStore.idInstructor) {
      store.fetchEstancias();
  }

  timer = setInterval(() => {
    if (store.isTurnoActivo && !document.hidden) {
        store.fetchEstancias();
    }
  }, 60000);

  document.addEventListener('visibilitychange', onVisibilityChange);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
  document.removeEventListener('visibilitychange', onVisibilityChange);
});

// Helpers para la UI
const abrirModalIncidencia = (id) => {
    modalIncidenciaInfo.value = { visible: true, idEstancia: id, correo: '' };
};

const confirmarIncidencia = async () => {
    modalIncidenciaInfo.value.visible = false;
    const res = await store.cambiarEstatusEstancia(modalIncidenciaInfo.value.idEstancia, 'INACTIVO', {
        correo_receptor: modalIncidenciaInfo.value.correo
    });
    if (res?.success) {
        actionToast('El menor fue marcado como inactivo.', 'success');
    } else {
        await errorModal('Error al registrar', res?.message || 'No se pudo registrar la incidencia.');
    }
};

const abrirModalSalida = (id) => {
    modalSalidaInfo.value = { visible: true, idEstancia: id, tipoUsuario: 'SOCIO_TITULAR', correo: '' };
};

const confirmarSalida = async () => {
    modalSalidaInfo.value.visible = false;
    const res = await store.cambiarEstatusEstancia(modalSalidaInfo.value.idEstancia, 'ENTREGADO', {
        tipo_usuario: modalSalidaInfo.value.tipoUsuario,
        correo_receptor: modalSalidaInfo.value.correo
    });
    if (res?.success) {
        actionToast('El menor fue entregado correctamente.', 'success');
    } else {
        await errorModal('Error al registrar salida', res?.message || 'No se pudo registrar la salida.');
    }
};

const abrirModalIngreso = (id) => {
    modalIngresoInfo.value = { visible: true, idEstancia: id, correo: '' };
};

const confirmarIngreso = async () => {
    modalIngresoInfo.value.visible = false;
    const res = await store.registrarIngreso(modalIngresoInfo.value.idEstancia, {
        correo: modalIngresoInfo.value.correo
    });
    if (res?.success) {
        actionToast('El menor fue activado en la ludoteca correctamente.', 'success');
    } else {
        await errorModal('Error al activar', res?.message || 'No se pudo activar el ingreso.');
    }
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
        
        // Si la fecha no es válida (por ejemplo si solo viene la hora "HH:mm:ss")
        if (isNaN(date.getTime())) {
            // Intentamos parsear asumiendo que es una hora del día de hoy
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
  <main class="w-full bg-surface-50 min-h-screen font-sans pb-24 md:pb-8">
    <div class="max-w-5xl mx-auto p-4 md:p-8 space-y-5">

      <!-- Encabezado -->
      <div class="flex flex-col gap-1 pt-2">
        <button @click="$router.push('/instructor/home')" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-3 focus:outline-none w-fit group">
          <svg class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
          Volver
        </button>
        <h2 class="text-2xl md:text-3xl font-bold text-surface-900 tracking-tight m-0">Tablero Operativo</h2>
        <p class="text-surface-500 font-medium text-sm m-0">Ludoteca</p>
      </div>

      <!-- Error global -->
      <div v-if="store.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3.5 rounded-2xl flex items-center gap-3">
        <svg class="h-4 w-4 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span class="text-sm font-medium">{{ store.error }}</span>
      </div>

      <!-- Bloqueo de turno -->
      <BloqueoTurno v-if="!store.isTurnoActivo && !store.loading" @retry="store.fetchEstancias" />

      <!-- Tablero principal -->
      <div v-else-if="store.isTurnoActivo" class="animate-fade-in space-y-5">

        <!-- Segmented control -->
        <div class="flex p-1.5 bg-surface-100 rounded-2xl shadow-inner border border-surface-200">

          <!-- Activos -->
          <button
            @click="activeTab = 'activos'"
            class="flex-1 flex items-center justify-center gap-2 py-3 px-2 text-sm font-bold rounded-xl transition-all focus:outline-none"
            :class="activeTab === 'activos'
              ? 'bg-primary-600 text-white shadow-md scale-[1.02]'
              : 'text-surface-500 hover:bg-white/60 hover:text-surface-700'"
          >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Activos</span>
            <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full leading-none"
              :class="activeTab === 'activos' ? 'bg-white/25 text-white' : 'bg-primary-100 text-primary-700'">
              {{ store.estanciasActivas.length }}
            </span>
          </button>

          <!-- Inactivos -->
          <button
            @click="activeTab = 'inactivos'"
            class="flex-1 flex items-center justify-center gap-2 py-3 px-2 text-sm font-bold rounded-xl transition-all focus:outline-none"
            :class="activeTab === 'inactivos'
              ? 'bg-primary-600 text-white shadow-md scale-[1.02]'
              : 'text-surface-500 hover:bg-white/60 hover:text-surface-700'"
          >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span>Inactivos</span>
          </button>

          <!-- Entregados -->
          <button
            @click="activeTab = 'entregados'"
            class="flex-1 flex items-center justify-center gap-2 py-3 px-2 text-sm font-bold rounded-xl transition-all focus:outline-none"
            :class="activeTab === 'entregados'
              ? 'bg-primary-600 text-white shadow-md scale-[1.02]'
              : 'text-surface-500 hover:bg-white/60 hover:text-surface-700'"
          >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Entregados</span>
            <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full leading-none"
              :class="activeTab === 'entregados' ? 'bg-white/25 text-white' : 'bg-primary-100 text-primary-700'">
              {{ store.estanciasEntregadas.length }}
            </span>
          </button>

        </div>

        <!-- ── ACTIVOS ───────────────────────────────────────────── -->
        <div v-show="activeTab === 'activos'">
          <div v-if="store.estanciasActivas.length === 0"
               class="h-36 flex flex-col items-center justify-center rounded-2xl border border-dashed border-surface-200 bg-white gap-2">
            <svg class="w-8 h-8 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
            </svg>
            <p class="text-surface-500 font-medium text-sm">No hay menores activos en este momento.</p>
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div
              v-for="nino in store.estanciasActivas"
              :key="nino.id_registro"
              class="bg-white rounded-2xl border border-surface-100 border-l-4 border-l-primary-500 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col"
            >
              <div class="px-5 pt-5 pb-3 flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center font-extrabold text-sm shrink-0 select-none">
                  {{ nino.nombre_nino?.charAt(0) ?? '?' }}
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="font-bold text-surface-900 text-base leading-tight truncate">{{ nino.nombre_nino || 'Menor sin nombre' }}</h4>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <svg class="w-3.5 h-3.5 text-surface-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <p class="text-surface-500 text-xs font-medium truncate">{{ nino.nombre_tutor || 'Tutor no especificado' }}</p>
                  </div>
                </div>
                <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-primary-50 text-primary-700 border border-primary-200 shrink-0">
                  <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse inline-block"></span>
                  Activo
                </span>
              </div>

              <div class="mx-5 border-t border-surface-100"></div>

              <div class="px-4 pb-4 pt-3 flex gap-2">
                <button
                  @click="abrirModalIncidencia(nino.id_registro)"
                  class="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-xs font-bold border border-surface-200 bg-white text-surface-600 hover:bg-surface-100 hover:border-surface-300 transition-all active:scale-95"
                >
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                  </svg>
                  Incidencia
                </button>
                <button
                  @click="abrirModalSalida(nino.id_registro)"
                  class="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-xs font-bold bg-primary-600 hover:bg-primary-700 text-white border border-primary-500 shadow-sm transition-all active:scale-95"
                >
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                  </svg>
                  Marcar Salida
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ── INACTIVOS ─────────────────────────────────────────── -->
        <div v-show="activeTab === 'inactivos'" class="space-y-4">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
              <svg class="h-4 w-4 text-surface-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
              </svg>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar menor o tutor..."
              class="w-full pl-10 pr-4 py-3 bg-white border border-surface-200 rounded-xl focus:ring-2 focus:ring-primary-400 focus:border-primary-400 outline-none text-surface-900 text-sm placeholder:text-surface-400 transition-all"
            />
          </div>

          <div v-if="searchQuery.length < 2"
               class="h-36 flex flex-col items-center justify-center rounded-2xl border border-dashed border-surface-200 bg-white gap-2">
            <svg class="w-7 h-7 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
            </svg>
            <p class="text-surface-400 font-medium text-sm">Escribe al menos 2 caracteres para buscar.</p>
          </div>
          <div v-else-if="inactivosFiltrados.length === 0"
               class="h-36 flex items-center justify-center rounded-2xl border border-dashed border-surface-200 bg-white">
            <p class="text-surface-400 font-medium text-sm">Sin resultados para "{{ searchQuery }}".</p>
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div
              v-for="nino in inactivosFiltrados"
              :key="nino.id_registro"
              class="bg-white rounded-2xl border border-surface-100 border-l-4 border-l-primary-500 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col"
            >
              <div class="px-5 pt-5 pb-3 flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center font-extrabold text-sm shrink-0 select-none">
                  {{ nino.nombre_nino?.charAt(0) ?? '?' }}
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="font-bold text-surface-900 text-base leading-tight truncate">{{ nino.nombre_nino }}</h4>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <svg class="w-3.5 h-3.5 text-surface-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <p class="text-surface-500 text-xs font-medium truncate">{{ nino.nombre_tutor }}</p>
                  </div>
                </div>
                <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-surface-100 text-surface-600 border border-surface-200 shrink-0">
                  <span class="w-1.5 h-1.5 rounded-full bg-surface-400 inline-block"></span>
                  Inactivo
                </span>
              </div>

              <div class="mx-5 border-t border-surface-100"></div>

              <div class="px-4 pb-4 pt-3">
                <button
                  @click="abrirModalIngreso(nino.id_registro)"
                  class="w-full flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-xs font-bold bg-primary-600 hover:bg-primary-700 text-white border border-primary-500 shadow-sm transition-all active:scale-95"
                >
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                  </svg>
                  Registrar Ingreso
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ── ENTREGADOS ─────────────────────────────────────────── -->
        <div v-show="activeTab === 'entregados'">
          <div v-if="store.estanciasEntregadas.length === 0"
               class="h-36 flex flex-col items-center justify-center rounded-2xl border border-dashed border-surface-200 bg-white gap-2">
            <svg class="w-8 h-8 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-surface-500 font-medium text-sm">No se han entregado menores hoy.</p>
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div
              v-for="nino in store.estanciasEntregadas"
              :key="nino.id_registro"
              class="bg-white rounded-2xl border border-surface-100 border-l-4 border-l-primary-500 shadow-sm opacity-80 flex flex-col"
            >
              <div class="px-5 pt-5 pb-3 flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center font-extrabold text-sm shrink-0 select-none">
                  {{ nino.nombre_nino?.charAt(0) ?? '?' }}
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="font-bold text-surface-900 text-base leading-tight truncate">{{ nino.nombre_nino }}</h4>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <svg class="w-3.5 h-3.5 text-surface-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <p class="text-surface-500 text-xs font-medium truncate">{{ nino.nombre_tutor }}</p>
                  </div>
                </div>
                <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-primary-100 text-primary-700 border border-primary-200 shrink-0">
                  <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                  Entregado
                </span>
              </div>

              <div class="mx-5 border-t border-surface-100"></div>

              <div class="px-5 py-3 flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-primary-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-xs font-semibold text-surface-500">Salida: <span class="text-primary-600 font-bold">{{ formatTime(nino.hora_salida) }}</span></span>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Cargando -->
      <div v-if="store.loading && !store.isTurnoActivo" class="flex justify-center p-12">
        <LoadingSpinner />
      </div>

    </div>

    <!-- Modal Ingreso -->
    <div v-if="modalIngresoInfo.visible" class="fixed inset-0 z-50 flex items-center justify-center bg-surface-900/50 backdrop-blur-sm p-4" @click.self="modalIngresoInfo.visible = false">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl animate-fade-in overflow-y-auto" style="max-height: 85svh;">
        <div class="p-6">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-surface-900">Registrar Ingreso</h3>
          </div>
          <div>
            <label class="block text-xs font-semibold text-surface-500 uppercase tracking-wider mb-1.5">Correo del socio titular</label>
            <input v-model="modalIngresoInfo.correo" type="email" placeholder="ejemplo@correo.com"
              class="w-full bg-surface-50 border border-surface-200 rounded-xl px-4 py-3 outline-none text-sm focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition-all" />
          </div>
          <div class="flex gap-3 mt-6">
            <button @click="modalIngresoInfo.visible = false" class="flex-1 py-3 rounded-xl font-bold text-sm border border-surface-200 bg-white text-surface-700 hover:bg-surface-50 transition-all active:scale-95">Cancelar</button>
            <button @click="confirmarIngreso" class="flex-1 py-3 rounded-xl font-bold text-sm bg-primary-600 hover:bg-primary-700 text-white shadow-sm transition-all active:scale-95">Confirmar Entrada</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Salida -->
    <div v-if="modalSalidaInfo.visible" class="fixed inset-0 z-50 flex items-center justify-center bg-surface-900/50 backdrop-blur-sm p-4" @click.self="modalSalidaInfo.visible = false">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl animate-fade-in overflow-y-auto" style="max-height: 85svh;">
        <div class="p-6">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-surface-900">Registrar Salida</h3>
          </div>
          <div>
            <label class="block text-xs font-semibold text-surface-500 uppercase tracking-wider mb-1.5">Correo del socio titular</label>
            <input v-model="modalSalidaInfo.correo" type="email" placeholder="ejemplo@correo.com"
              class="w-full bg-surface-50 border border-surface-200 rounded-xl px-4 py-3 outline-none text-sm focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition-all" />
          </div>
          <div class="flex gap-3 mt-6">
            <button @click="modalSalidaInfo.visible = false" class="flex-1 py-3 rounded-xl font-bold text-sm border border-surface-200 bg-white text-surface-700 hover:bg-surface-50 transition-all active:scale-95">Cancelar</button>
            <button @click="confirmarSalida" class="flex-1 py-3 rounded-xl font-bold text-sm bg-primary-600 hover:bg-primary-700 text-white shadow-sm transition-all active:scale-95">Confirmar Salida</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Incidencia -->
    <div v-if="modalIncidenciaInfo.visible" class="fixed inset-0 z-50 flex items-center justify-center bg-surface-900/50 backdrop-blur-sm p-4" @click.self="modalIncidenciaInfo.visible = false">
      <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl animate-fade-in overflow-y-auto" style="max-height: 85svh;">
        <div class="p-6">
          <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-surface-900">Registrar Incidencia</h3>
          </div>
          <div>
            <label class="block text-xs font-semibold text-surface-500 uppercase tracking-wider mb-1.5">Correo del socio titular</label>
            <input v-model="modalIncidenciaInfo.correo" type="email" placeholder="ejemplo@correo.com"
              class="w-full bg-surface-50 border border-surface-200 rounded-xl px-4 py-3 outline-none text-sm focus:ring-2 focus:ring-red-400 focus:border-red-400 transition-all"
              @keyup.enter="confirmarIncidencia" />
          </div>
          <div class="flex gap-3 mt-6">
            <button @click="modalIncidenciaInfo.visible = false" class="flex-1 py-3 rounded-xl font-bold text-sm border border-surface-200 bg-white text-surface-700 hover:bg-surface-50 transition-all active:scale-95">Cancelar</button>
            <button @click="confirmarIncidencia" class="flex-1 py-3 rounded-xl font-bold text-sm bg-red-600 hover:bg-red-700 text-white shadow-sm transition-all active:scale-95">Confirmar Incidencia</button>
          </div>
        </div>
      </div>
    </div>

  </main>
</template>