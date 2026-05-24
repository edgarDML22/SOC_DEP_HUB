<script setup>
import { computed, ref } from 'vue'
import { useActividadesStore } from '@/stores/actividadesStore'

const store = useActividadesStore()

// Toast local
const toast = ref({ show: false, ok: true, message: '' })
const inscribiendoId = ref(null)

function showToast(ok, message) {
  toast.value = { show: true, ok, message }
  setTimeout(() => { toast.value.show = false }, 3500)
}

// IDs de sesiones en las que el usuario ya está inscrito
const inscritosIds = computed(() =>
  new Set(store.misInscripciones.map(i => i.id_sesion).filter(Boolean))
)

async function handleInscribirse(sesion) {
  inscribiendoId.value = sesion.id_sesion
  const result = await store.inscribirse(sesion.id_sesion)
  showToast(result.ok, result.message)
  inscribiendoId.value = null
}

function formatFecha(f) {
  if (!f) return ''
  const [y, m, d] = f.split('-')
  const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']
  return `${d} ${meses[parseInt(m) - 1]} ${y}`
}
</script>

<template>
  <div class="w-full px-4 md:px-6 lg:px-8 pb-24 md:pb-8">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Toast -->
      <Transition name="slide-toast">
        <div
          v-if="toast.show"
          class="fixed top-5 right-5 z-50 flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl text-sm font-semibold transition-all"
          :class="toast.ok
            ? 'bg-emerald-600 text-white'
            : 'bg-red-600 text-white'"
        >
          <svg v-if="toast.ok" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <svg v-else class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
          {{ toast.message }}
        </div>
      </Transition>

      <!-- Filtros -->
      <div class="flex flex-col sm:flex-row gap-3">
        <!-- Filtro Disciplina -->
        <div class="relative flex-1">
          <label class="block text-xs font-bold text-surface-500 uppercase tracking-wider mb-1.5">Disciplina</label>
          <select
            v-model="store.filtroDisciplinaId"
            id="filtro-disciplina-abierta"
            class="w-full bg-white border border-surface-200 rounded-xl px-4 py-2.5 text-sm font-medium text-surface-800 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition-all appearance-none cursor-pointer"
          >
            <option :value="null">Todas las disciplinas</option>
            <option
              v-for="d in store.disciplinasDisponibles"
              :key="d.id"
              :value="d.id"
            >{{ d.nombre }}</option>
          </select>
          <svg class="pointer-events-none absolute right-3 bottom-3 w-4 h-4 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </div>

        <!-- Filtro Hora -->
        <div class="relative flex-1">
          <label class="block text-xs font-bold text-surface-500 uppercase tracking-wider mb-1.5">Hora</label>
          <select
            v-model="store.filtroHora"
            id="filtro-hora-abierta"
            class="w-full bg-white border border-surface-200 rounded-xl px-4 py-2.5 text-sm font-medium text-surface-800 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-primary-400 transition-all appearance-none cursor-pointer"
          >
            <option value="">Cualquier hora</option>
            <option v-for="h in store.horasDisponibles" :key="h" :value="h">{{ h }}</option>
          </select>
          <svg class="pointer-events-none absolute right-3 bottom-3 w-4 h-4 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </div>

        <!-- Limpiar filtros -->
        <div class="flex items-end">
          <button
            v-if="store.filtroDisciplinaId || store.filtroHora"
            @click="store.resetFiltros()"
            class="px-4 py-2.5 rounded-xl text-sm font-semibold text-surface-500 hover:text-red-600 hover:bg-red-50 border border-surface-200 transition-all"
          >
            Limpiar
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="store.loading" class="flex flex-col items-center py-20">
        <div class="w-10 h-10 rounded-full border-2 border-surface-200 border-t-primary-600 animate-spin mb-4" />
        <p class="text-surface-500 font-medium text-sm">Cargando actividades...</p>
      </div>

      <!-- Error -->
      <div v-else-if="store.error" class="bg-red-50 border border-red-200 rounded-2xl p-6 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <p class="text-sm font-medium text-red-700">{{ store.error }}</p>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="store.sesionesTipoCerrada.length === 0"
        class="flex flex-col items-center py-20 bg-white rounded-3xl border border-surface-100 shadow-sm"
      >
        <div class="w-16 h-16 bg-primary-50 rounded-full flex items-center justify-center text-primary-400 mb-4">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
          </svg>
        </div>
        <p class="text-surface-900 font-bold text-lg">Sin actividades cerradas</p>
        <p class="text-surface-500 font-medium text-sm text-center max-w-xs mt-1">
          {{ store.filtroDisciplinaId || store.filtroHora
            ? 'No hay resultados con los filtros actuales. Intenta ajustarlos.'
            : 'No hay actividades cerradas programadas para los próximos días.' }}
        </p>
        <button
          v-if="store.filtroDisciplinaId || store.filtroHora"
          @click="store.resetFiltros()"
          class="mt-4 px-4 py-2 rounded-xl text-sm font-semibold text-primary-600 hover:bg-primary-50 border border-primary-200 transition-all"
        >
          Limpiar filtros
        </button>
      </div>

      <!-- Grid de sesiones -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="sesion in store.sesionesTipoCerrada"
          :key="sesion.id_sesion"
          class="bg-white rounded-3xl border border-surface-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden flex flex-col"
        >
          <!-- Header de la card — badge cambia según el tipo -->
          <div 
            class="px-5 py-3.5 flex items-center justify-between bg-gradient-to-r from-blue-500 to-blue-700"
          >
            <span class="text-white font-bold text-sm tracking-wide">
              {{ sesion.nombre_actividad }}
            </span>
            <span class="bg-white/20 text-white text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full">
              {{ sesion.requiere_inscripcion ? 'Cerrada' : 'Abierta' }}
            </span>
          </div>

          <!-- Body -->
          <div class="px-5 py-4 flex-1 space-y-3">
            <!-- Fecha y hora -->
            <div class="flex items-center gap-2 text-surface-600">
              <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span class="text-sm font-semibold">{{ formatFecha(sesion.fecha_sesion) }}</span>
            </div>

            <div class="flex items-center gap-2 text-surface-600">
              <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
              </svg>
              <span class="text-sm font-semibold tabular-nums">
                {{ sesion.hora_inicio }} – {{ sesion.hora_fin }}
              </span>
            </div>

            <!-- Instructor -->
            <div v-if="sesion.instructor" class="flex items-center gap-2 text-surface-600">
              <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span class="text-sm font-medium text-surface-500 truncate">{{ sesion.instructor }}</span>
            </div>

            <!-- Espacio -->
            <div v-if="sesion.espacio" class="flex items-center gap-2 text-surface-600">
              <svg class="w-4 h-4 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span class="text-sm font-medium text-surface-500 truncate">{{ sesion.espacio }}</span>
            </div>

            <!-- Inscritos -->
            <div class="flex items-center gap-2">
              <svg 
                class="w-4 h-4 shrink-0 text-blue-500" 
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span class="text-xs font-semibold text-surface-500">
                {{ sesion.cantidad_inscritos }} inscritos 
                <span v-if="sesion.requiere_inscripcion && sesion.cupo_maximo">· Cupo: {{ sesion.disponible }} disponibles</span>
                <span v-else>· Sin límite de cupo</span>
              </span>
            </div>
          </div>

          <!-- Footer – botón inscribirse -->
          <div class="px-5 pb-5">
            <!-- Ya inscrito -->
            <div
              v-if="sesion._inscrito || inscritosIds.has(sesion.id_sesion)"
              class="w-full flex items-center justify-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-2.5 text-sm font-bold"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              Inscrito
            </div>

            <!-- Botón inscribirse -->
            <button
              v-else
              :id="`btn-inscribir-${sesion.id_sesion}`"
              @click="handleInscribirse(sesion)"
              :disabled="inscribiendoId === sesion.id_sesion || store.loadingAccion || (sesion.requiere_inscripcion && sesion.es_cupo_lleno)"
              class="w-full flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl px-4 py-2.5 text-sm font-bold transition-all active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed shadow-sm"
            >
              <svg v-if="inscribiendoId === sesion.id_sesion" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
              {{ inscribiendoId === sesion.id_sesion ? 'Inscribiendo...' : (sesion.requiere_inscripcion && sesion.es_cupo_lleno ? 'Cupo lleno' : 'Inscribirse') }}
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.slide-toast-enter-active,
.slide-toast-leave-active {
  transition: all 0.3s ease;
}
.slide-toast-enter-from,
.slide-toast-leave-to {
  opacity: 0;
  transform: translateX(1.5rem);
}
</style>
