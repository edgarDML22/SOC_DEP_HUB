<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import CalendarioPublicadas from './CalendarioPublicadas.vue'
import { useAlerts } from '@/composables/useAlerts'
import { usePlantillasStore } from '@/stores/programacion/plantillasStore'
import api from '@/services/api'

const { toastSuccess, toastError } = useAlerts()
const plantillasStore = usePlantillasStore()

// ─── Estado principal ────────────────────────────────────────────────────────
const sesiones   = ref([])
const isLoading  = ref(false)
const loadError  = ref('')

const activeView      = ref('tabla')
const searchFilter    = ref('')
const selectedEstatus = ref('TODOS')

// ─── Carga de datos reales ───────────────────────────────────────────────────
async function fetchSesiones() {
  isLoading.value = true
  loadError.value = ''
  try {
    const { data } = await api.get('/programacion/sesiones-activas')
    sesiones.value = data.data ?? []
  } catch (e) {
    loadError.value = e?.response?.data?.message ?? 'No se pudieron cargar las sesiones publicadas.'
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchSesiones)

// Cuando se retiran sesiones desde PlantillasGestion, refrescar automáticamente
watch(() => plantillasStore.sesionesRetiradas, (val) => {
  if (val > 0) fetchSesiones()
})

// ─── Filtros de búsqueda ────────────────────────────────────────────────────
const sesionesFiltradas = computed(() => {
  return sesiones.value.filter(s => {
    const term = searchFilter.value.toLowerCase().trim()
    const matchesSearch = !term ||
      String(s.id_sesion).includes(term) ||
      (s.disciplina ?? '').toLowerCase().includes(term) ||
      (s.instructor  ?? '').toLowerCase().includes(term) ||
      (s.espacio     ?? '').toLowerCase().includes(term)
    const matchesEstatus = selectedEstatus.value === 'TODOS' || s.estatus_sesion === selectedEstatus.value
    return matchesSearch && matchesEstatus
  })
})

const hayFiltrosActivos = computed(() =>
  searchFilter.value.trim() !== '' || selectedEstatus.value !== 'TODOS'
)

function limpiarFiltros() {
  searchFilter.value    = ''
  selectedEstatus.value = 'TODOS'
}

// ─── Modal de gestión de estatus ────────────────────────────────────────────
const showModal          = ref(false)
const sesionSeleccionada = ref(null)
const tempEstatus        = ref('DISPONIBLE')
const isSaving           = ref(false)

function abrirDetalle(sesion) {
  sesionSeleccionada.value = { ...sesion }
  tempEstatus.value        = sesion.estatus_sesion
  showModal.value          = true
}

function cerrarModal() {
  showModal.value          = false
  sesionSeleccionada.value = null
}

async function guardarCambios() {
  if (!sesionSeleccionada.value) return
  isSaving.value = true
  try {
    await api.patch(`/programacion/sesiones-activas/${sesionSeleccionada.value.id_sesion}`, {
      estatus_sesion: tempEstatus.value,
    })
    const original = sesiones.value.find(s => s.id_sesion === sesionSeleccionada.value.id_sesion)
    if (original) original.estatus_sesion = tempEstatus.value
    toastSuccess(`Sesión #${sesionSeleccionada.value.id_sesion} actualizada a ${tempEstatus.value}.`)
    cerrarModal()
  } catch (e) {
    toastError(e?.response?.data?.message ?? 'Error al actualizar el estatus.')
  } finally {
    isSaving.value = false
  }
}

// ─── Helpers ────────────────────────────────────────────────────────────────
const DIAS_LABEL = {
  LUNES: 'Lunes', MARTES: 'Martes', MIERCOLES: 'Miércoles',
  JUEVES: 'Jueves', VIERNES: 'Viernes', SABADO: 'Sábado', DOMINGO: 'Domingo',
}

function formatDate(d) {
  if (!d) return '—'
  const [y, m, day] = d.split('-')
  return `${day}/${m}/${y}`
}

defineExpose({ fetchSesiones })
</script>

<template>
  <div class="flex flex-col h-full bg-slate-50 font-sans min-h-0 relative">

    <!-- ── Header (solo cuando hay sesiones o hay error/carga) ── -->
    <div
      v-if="isLoading || loadError || sesiones.length > 0"
      class="px-6 py-4 bg-white border-b border-slate-200 shrink-0 shadow-sm"
    >
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-black text-slate-800 tracking-tight">Monitoreo de Sesiones Semanales</h2>
          <p class="text-xs text-slate-500 font-medium">Visualiza las sesiones publicadas y gestiona cancelaciones.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <!-- Búsqueda -->
          <div class="relative w-48 md:w-60">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              v-model="searchFilter"
              type="text"
              placeholder="Buscar sesión, instructor..."
              class="w-full pl-9 pr-4 py-2 text-xs font-bold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all shadow-inner"
            />
          </div>

          <!-- Filtro de Estatus -->
          <select
            v-model="selectedEstatus"
            class="px-3 py-2 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer shadow-sm transition-all"
          >
            <option value="TODOS">Todos los Estatus</option>
            <option value="DISPONIBLE">Disponible</option>
            <option value="CANCELADA">Cancelada</option>
          </select>

          <!-- Botón recargar -->
          <button
            @click="fetchSesiones"
            :disabled="isLoading"
            class="p-2 text-slate-500 hover:text-blue-600 bg-white border border-slate-200 hover:border-blue-300 rounded-xl transition-all focus:outline-none disabled:opacity-50"
            title="Recargar sesiones"
          >
            <svg class="w-4 h-4" :class="isLoading ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>

          <!-- Switcher Tabla / Calendario -->
          <div class="flex p-0.5 bg-slate-100 border border-slate-200 rounded-xl shadow-inner">
            <button
              @click="activeView = 'tabla'"
              class="py-1.5 px-3 rounded-lg text-xs font-extrabold flex items-center gap-1.5 transition-all"
              :class="activeView === 'tabla' ? 'bg-white text-slate-800 shadow-sm border border-slate-200' : 'text-slate-500 hover:text-slate-800'"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
              </svg>
              Tabla
            </button>
            <button
              @click="activeView = 'calendario'"
              class="py-1.5 px-3 rounded-lg text-xs font-extrabold flex items-center gap-1.5 transition-all"
              :class="activeView === 'calendario' ? 'bg-white text-slate-800 shadow-sm border border-slate-200' : 'text-slate-500 hover:text-slate-800'"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75" />
              </svg>
              Calendario
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Contenido ── -->
    <div class="flex-1 overflow-hidden p-6 min-h-0">

      <!-- ── SKELETON de carga ── -->
      <div v-if="isLoading" class="h-full flex flex-col gap-3">
        <div class="h-12 bg-slate-200 rounded-2xl animate-pulse" />
        <div v-for="i in 5" :key="i" class="h-14 bg-white border border-slate-200 rounded-2xl animate-pulse" :style="`opacity:${1 - i * 0.12}`" />
      </div>

      <!-- ── ERROR de red ── -->
      <div v-else-if="loadError" class="h-full flex items-center justify-center">
        <div class="text-center max-w-sm">
          <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
          </div>
          <p class="text-sm font-extrabold text-slate-800 mb-1">Error al cargar sesiones</p>
          <p class="text-xs text-slate-500 mb-4">{{ loadError }}</p>
          <button
            @click="fetchSesiones"
            class="px-4 py-2 text-xs font-extrabold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors"
          >
            Reintentar
          </button>
        </div>
      </div>

      <!-- ── EMPTY STATE: aún no hay ninguna programación publicada ── -->
      <div v-else-if="sesiones.length === 0" class="h-full flex items-center justify-center">
        <div class="w-full max-w-lg">

          <!-- Tarjeta principal -->
          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">

            <!-- Banda superior decorativa -->
            <div class="h-1.5 w-full bg-gradient-to-r from-violet-600 via-purple-500 to-purple-400" />

            <div class="p-8 text-center">
              <!-- Icono ilustrado -->
              <div class="relative mx-auto mb-6 w-20 h-20">
                <div class="absolute inset-0 rounded-2xl bg-slate-50 border border-slate-200 rotate-6" />
                <div class="absolute inset-0 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center">
                  <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                  </svg>
                </div>
              </div>

              <h3 class="text-xl font-black text-slate-800 mb-2 tracking-tight">Sin programación publicada</h3>
              <p class="text-sm text-slate-500 leading-relaxed max-w-sm mx-auto">
                Aún no se han generado sesiones para esta semana. Publica una plantilla desde
                <span class="font-bold text-slate-700">Gestión de Plantillas</span> para verlas aquí.
              </p>
            </div>

            <!-- Separador -->
            <div class="border-t border-slate-100 mx-6" />

            <!-- Pasos en fila -->
            <div class="p-6">
              <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest mb-4 text-center">Cómo publicar una programación</p>
              <div class="grid grid-cols-4 gap-3">
                <div
                  v-for="(paso, i) in [
                    { icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', label: 'Abre Gestión de Plantillas' },
                    { icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', label: 'Activa una plantilla con bloques' },
                    { icon: 'M5 13l4 4L19 7', label: 'Haz clic en Publicar y elige semana' },
                    { icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z', label: 'Las sesiones aparecen aquí' },
                  ]"
                  :key="i"
                  class="flex flex-col items-center text-center gap-2"
                >
                  <div class="relative">
                    <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center">
                      <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="paso.icon" />
                      </svg>
                    </div>
                    <span class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-indigo-500 text-white text-[9px] font-black flex items-center justify-center">{{ i + 1 }}</span>
                  </div>
                  <p class="text-[10px] font-semibold text-slate-500 leading-tight">{{ paso.label }}</p>
                </div>
              </div>
            </div>

          </div>

          <!-- Enlace para recargar -->
          <div class="mt-4 text-center">
            <button
              @click="fetchSesiones"
              class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Verificar nuevamente
            </button>
          </div>
        </div>
      </div>

      <!-- ── EMPTY STATE con filtros: hay sesiones pero los filtros no muestran nada ── -->
      <template v-else-if="sesionesFiltradas.length === 0 && hayFiltrosActivos">
        <div
          v-show="activeView === 'tabla'"
          class="h-full flex flex-col bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden"
        >
          <div class="flex-1 flex items-center justify-center flex-col gap-3 py-16">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <p class="text-sm font-extrabold text-slate-600">Sin resultados para los filtros actuales</p>
            <p class="text-xs text-slate-400">Prueba cambiando el estatus o el término de búsqueda.</p>
            <button
              @click="limpiarFiltros"
              class="mt-1 px-4 py-2 text-xs font-extrabold text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-colors"
            >
              Limpiar filtros
            </button>
          </div>
        </div>
      </template>

      <!-- ── CONTENIDO NORMAL: hay sesiones y (opcionalmente) filtros activos ── -->
      <template v-else-if="sesiones.length > 0">

        <!-- VISTA TABLA -->
        <div
          v-show="activeView === 'tabla'"
          class="h-full overflow-hidden flex flex-col bg-white rounded-3xl border border-slate-200 shadow-sm"
        >
          <div class="flex-1 overflow-auto">
            <table class="w-full border-collapse text-left min-w-[1000px]">
              <thead>
                <tr class="bg-slate-50/70 border-b border-slate-200 text-slate-400 text-[10px] uppercase font-black tracking-wider sticky top-0 z-10">
                  <th class="py-4 px-6">ID Sesión</th>
                  <th class="py-4 px-4 text-center">Estatus</th>
                  <th class="py-4 px-4">Fecha</th>
                  <th class="py-4 px-4">Disciplina</th>
                  <th class="py-4 px-4">Instructor</th>
                  <th class="py-4 px-4">Espacio</th>
                  <th class="py-4 px-4 text-center">Día</th>
                  <th class="py-4 px-4 text-center">Hora inicio</th>
                  <th class="py-4 px-4 text-center">Hora fin</th>
                  <th class="py-4 px-4 text-center">Inscritos</th>
                  <th class="py-4 px-6 text-right">Acción</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
                <tr
                  v-for="s in sesionesFiltradas"
                  :key="s.id_sesion"
                  class="hover:bg-slate-50/50 transition-colors"
                  :class="s.estatus_sesion === 'CANCELADA' ? 'bg-red-50/20' : ''"
                >
                  <td class="py-4 px-6 font-mono font-bold text-slate-400">#{{ s.id_sesion }}</td>
                  <td class="py-4 px-4 text-center">
                    <span
                      class="inline-flex px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border shadow-sm"
                      :class="s.estatus_sesion === 'CANCELADA'
                        ? 'bg-red-50 text-red-700 border-red-200'
                        : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                    >
                      {{ s.estatus_sesion }}
                    </span>
                  </td>
                  <td class="py-4 px-4 text-slate-500 font-medium tabular-nums">{{ formatDate(s.fecha_sesion) }}</td>
                  <td class="py-4 px-4 text-slate-800 font-extrabold">{{ s.disciplina ?? '—' }}</td>
                  <td class="py-4 px-4 text-slate-600 font-bold">{{ s.instructor ?? '—' }}</td>
                  <td class="py-4 px-4 text-slate-500 font-medium">{{ s.espacio ?? '—' }}</td>
                  <td class="py-4 px-4 text-center">
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md text-[10px] font-bold">
                      {{ DIAS_LABEL[s.dia_semana] ?? s.dia_semana }}
                    </span>
                  </td>
                  <td class="py-4 px-4 text-center tabular-nums">{{ (s.hora_inicio ?? '').slice(0,5) }}</td>
                  <td class="py-4 px-4 text-center tabular-nums">{{ (s.hora_fin ?? '').slice(0,5) }}</td>
                  <td class="py-4 px-4 text-center font-bold">
                    <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-700 font-extrabold shadow-inner tabular-nums">
                      {{ s.cantidad_inscritos ?? 0 }}
                    </span>
                  </td>
                  <td class="py-4 px-6 text-right">
                    <button
                      @click="abrirDetalle(s)"
                      class="inline-flex items-center gap-1 px-3 py-1.5 text-[11px] font-extrabold text-slate-600 hover:text-blue-600 hover:bg-blue-50 border border-slate-200 hover:border-blue-200 rounded-xl transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                      </svg>
                      Gestionar
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Footer -->
          <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 font-bold shrink-0">
            <span>Mostrando {{ sesionesFiltradas.length }} de {{ sesiones.length }} sesiones</span>
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
              Datos en vivo
            </span>
          </div>
        </div>

        <!-- VISTA CALENDARIO -->
        <div v-show="activeView === 'calendario'" class="h-full bg-white rounded-3xl border border-slate-200 shadow-sm p-4">
          <CalendarioPublicadas
            :sesiones="sesionesFiltradas"
            @select-sesion="abrirDetalle"
          />
        </div>

      </template>
    </div>

    <!-- ══════════ MODAL DE DETALLE & OPERACIÓN (Teleport) ══════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div
          v-if="showModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="cerrarModal"
        >
          <!-- Contenido del Modal -->
          <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden transition-all duration-300 transform scale-100">

            <!-- Encabezado dinámico según estatus temporal -->
            <div
              class="px-6 py-5 text-white relative transition-colors duration-300"
              :class="tempEstatus === 'CANCELADA'
                ? 'bg-gradient-to-br from-rose-500 to-red-700'
                : 'bg-gradient-to-br from-blue-600 to-indigo-700'"
            >
              <button
                @click="cerrarModal"
                class="absolute top-4 right-4 text-white/80 hover:text-white bg-black/10 hover:bg-black/20 p-1.5 rounded-full transition-colors focus:outline-none"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>

              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center shadow-inner">
                  <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.246.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                </div>
                <div>
                  <p class="text-white/70 text-[9px] uppercase font-black tracking-widest leading-none">Sesión Publicada</p>
                  <h3 class="text-white text-lg font-black mt-1">{{ sesionSeleccionada?.disciplina }}</h3>
                </div>
              </div>
            </div>

            <!-- Cuerpo del Modal -->
            <div class="p-6 space-y-5">

              <!-- Información de la sesión -->
              <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-xs">
                <div>
                  <span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block">ID Sesión</span>
                  <span class="text-slate-800 font-mono font-bold">#{{ sesionSeleccionada?.id_sesion }}</span>
                </div>
                <div>
                  <span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block">Categoría</span>
                  <span class="text-slate-800 font-bold">{{ sesionSeleccionada?.categoria }}</span>
                </div>
                <div class="col-span-2 border-t border-slate-200/50 pt-2.5">
                  <span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block">Fecha & Horario</span>
                  <span class="text-slate-800 font-bold">
                    {{ DIAS_LABEL[sesionSeleccionada?.dia_semana] ?? sesionSeleccionada?.dia_semana }}
                    {{ formatDate(sesionSeleccionada?.fecha_sesion) }} ·
                    {{ (sesionSeleccionada?.hora_inicio ?? '').slice(0,5) }}–{{ (sesionSeleccionada?.hora_fin ?? '').slice(0,5) }}
                  </span>
                </div>
                <div class="col-span-2 border-t border-slate-200/50 pt-2.5">
                  <span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block">Instructor & Espacio</span>
                  <span class="text-slate-700 font-bold block">Prof: {{ sesionSeleccionada?.instructor }}</span>
                  <span class="text-slate-500 font-medium block">Sala: {{ sesionSeleccionada?.espacio }}</span>
                </div>
              </div>

              <!-- Cantidad de Inscritos (READ ONLY) -->
              <div>
                <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-2">Cantidad de Inscritos (Lectura)</label>
                <div class="flex items-center gap-3 px-4 py-3 bg-blue-50 border border-blue-100 text-blue-700 rounded-2xl">
                  <div class="w-8 h-8 rounded-xl bg-blue-500/10 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                  </div>
                  <div>
                    <span class="text-xs font-black block">Alumnos registrados</span>
                    <span class="text-[10px] opacity-90">Hay <strong class="text-sm font-black">{{ sesionSeleccionada?.cantidad_inscritos ?? 0 }}</strong> usuarios inscritos a esta sesión actualmente.</span>
                  </div>
                </div>
              </div>

              <!-- Estatus (UPDATE SEGMENTED CONTROL) -->
              <div>
                <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-2">Estatus de la Actividad</label>
                <div class="flex p-1 bg-slate-100 rounded-2xl border border-slate-200 gap-1.5">
                  <button
                    type="button"
                    @click="tempEstatus = 'DISPONIBLE'"
                    class="flex-1 py-3 text-xs font-black rounded-xl border transition-all flex items-center justify-center gap-1.5 focus:outline-none"
                    :class="tempEstatus === 'DISPONIBLE'
                      ? 'bg-emerald-600 border-emerald-500 text-white shadow-sm font-extrabold'
                      : 'border-transparent text-slate-500 hover:bg-white/60 hover:text-slate-700 font-bold'"
                  >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    DISPONIBLE
                  </button>

                  <button
                    type="button"
                    @click="tempEstatus = 'CANCELADA'"
                    class="flex-1 py-3 text-xs font-black rounded-xl border transition-all flex items-center justify-center gap-1.5 focus:outline-none"
                    :class="tempEstatus === 'CANCELADA'
                      ? 'bg-red-600 border-red-500 text-white shadow-sm font-extrabold'
                      : 'border-transparent text-slate-500 hover:bg-white/60 hover:text-slate-700 font-bold'"
                  >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    CANCELADA
                  </button>
                </div>
              </div>

            </div>

            <!-- Footer del Modal -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
              <button
                type="button"
                @click="cerrarModal"
                :disabled="isSaving"
                class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-200/50 rounded-xl transition-all disabled:opacity-50"
              >
                Cerrar
              </button>

              <button
                type="button"
                @click="guardarCambios"
                :disabled="isSaving"
                class="px-5 py-2.5 text-xs font-black text-white bg-slate-800 hover:bg-slate-900 active:scale-95 rounded-xl transition-all shadow-sm flex items-center gap-1.5 disabled:opacity-50"
              >
                <svg v-if="isSaving" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <span>Guardar cambios</span>
              </button>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<style scoped>
.overflow-auto::-webkit-scrollbar { width: 6px; height: 6px; }
.overflow-auto::-webkit-scrollbar-track { background: transparent; }
.overflow-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.overflow-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
