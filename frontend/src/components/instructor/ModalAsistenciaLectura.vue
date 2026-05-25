<script setup>
import { computed } from 'vue';

const props = defineProps({
  sesion: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['close']);

const esCerrada = computed(() => props.sesion.tipo === 'CLASE_CERRADA');

const inscritos   = computed(() => props.sesion.listas?.inscritos   ?? []);
const asistencia  = computed(() => props.sesion.listas?.asistencia  ?? []);
const noShow      = computed(() => props.sesion.listas?.falta       ?? []);
const contadores  = computed(() => props.sesion.contadores ?? { inscritos: 0, asistencia: 0, no_show: 0 });

const porcentajeAsistencia = computed(() => {
  const total = contadores.value.inscritos;
  if (!total) return 0;
  return Math.round((contadores.value.asistencia / total) * 100);
});

const formatHora = (h) => h ? h.substring(0, 5) : '—';
</script>

<template>
  <Transition name="fade">
    <div class="fixed inset-0 z-100 flex items-end sm:items-center justify-center p-0 sm:p-4">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-surface-900/60 backdrop-blur-sm" @click="emit('close')"></div>

      <!-- Panel -->
      <div class="relative bg-white w-full sm:max-w-lg rounded-t-4xl sm:rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/20 animate-scale-in max-h-[95dvh] flex flex-col">

        <!-- Header coloreado según tipo de clase -->
        <div
          class="p-6 sm:p-7 text-white flex justify-between items-start shrink-0"
          :class="esCerrada ? 'bg-linear-to-br from-violet-700 to-purple-800' : 'bg-linear-to-br from-teal-500 to-emerald-700'"
        >
          <div class="flex-1 min-w-0 pr-4">
            <div class="flex items-center gap-2 mb-2 flex-wrap">
              <span class="px-2.5 py-0.5 rounded-full border border-white/30 text-[10px] font-bold uppercase tracking-wider bg-white/15">
                {{ esCerrada ? 'CLASE CERRADA' : 'CLASE ABIERTA' }}
              </span>
              <span class="px-2.5 py-0.5 rounded-full border border-white/30 text-[10px] font-bold uppercase tracking-wider bg-white/15">
                {{ sesion.estatus }}
              </span>
            </div>
            <h3 class="text-xl sm:text-2xl font-bold tracking-tight leading-tight truncate">{{ sesion.titulo }}</h3>
            <p class="text-white/80 text-sm font-medium mt-1">
              {{ sesion.fecha }} • {{ formatHora(sesion.hora_inicio) }}–{{ formatHora(sesion.hora_fin) }}
              <template v-if="sesion.espacio"> • {{ sesion.espacio }}</template>
            </p>
          </div>
          <button
            @click="emit('close')"
            class="w-9 h-9 bg-white/10 hover:bg-white/25 rounded-full flex items-center justify-center transition-colors focus:outline-none shrink-0"
            aria-label="Cerrar"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Barra de progreso de asistencia -->
        <div class="px-6 pt-4 pb-2 shrink-0 bg-white border-b border-surface-100">
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest">Asistencia</span>
            <span class="text-sm font-bold" :class="porcentajeAsistencia >= 70 ? 'text-emerald-600' : 'text-amber-600'">
              {{ porcentajeAsistencia }}%
            </span>
          </div>
          <div class="w-full h-2 bg-surface-100 rounded-full overflow-hidden">
            <div
              class="h-2 rounded-full transition-all duration-500"
              :class="porcentajeAsistencia >= 70 ? 'bg-emerald-500' : 'bg-amber-400'"
              :style="{ width: porcentajeAsistencia + '%' }"
            ></div>
          </div>
          <!-- Resumen de contadores -->
          <div class="flex items-center gap-4 mt-3">
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-surface-400 inline-block"></span>
              <span class="text-xs font-semibold text-surface-600">{{ contadores.inscritos }} inscritos</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
              <span class="text-xs font-semibold text-emerald-700">{{ contadores.asistencia }} asistencia</span>
            </div>
            <div v-if="esCerrada" class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span>
              <span class="text-xs font-semibold text-red-600">{{ contadores.falta }} no show</span>
            </div>
          </div>
        </div>

        <!-- Listas de usuarios (scrolleable) -->
        <div class="overflow-y-auto scrollbar-thin flex-1 p-6 sm:p-7 space-y-6">

          <!-- ── INSCRITOS ────────────────────────────────────────────────── -->
          <section>
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-xs font-extrabold text-surface-500 uppercase tracking-widest">Inscritos</h4>
              <span class="text-xs font-bold text-surface-400 bg-surface-100 px-2 py-0.5 rounded-full">{{ inscritos.length }}</span>
            </div>
            <div v-if="inscritos.length === 0" class="text-center py-6 text-surface-400 text-sm font-medium bg-surface-50 rounded-2xl border border-surface-100">
              Sin inscritos confirmados
            </div>
            <ul v-else class="space-y-2">
              <li
                v-for="u in inscritos"
                :key="u.id_inscripcion"
                class="flex items-center gap-3 p-3 bg-surface-50 rounded-xl border border-surface-100"
              >
                <div class="w-8 h-8 rounded-full bg-surface-300 text-white flex items-center justify-center text-xs font-extrabold uppercase shrink-0">
                  {{ u.nombre?.charAt(0) ?? '?' }}
                </div>
                <span class="text-sm font-semibold text-surface-800 truncate flex-1">{{ u.nombre }}</span>
                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-surface-200 text-surface-600">
                  Inscrito
                </span>
              </li>
            </ul>
          </section>

          <!-- ── ASISTENCIA ──────────────────────────────────────────────── -->
          <section>
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-xs font-extrabold text-emerald-600 uppercase tracking-widest">Asistencia</h4>
              <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">{{ asistencia.length }}</span>
            </div>
            <div v-if="asistencia.length === 0" class="text-center py-6 text-surface-400 text-sm font-medium bg-surface-50 rounded-2xl border border-surface-100">
              Ningún registro de asistencia aún
            </div>
            <ul v-else class="space-y-2">
              <li
                v-for="u in asistencia"
                :key="u.id_inscripcion"
                class="flex items-center gap-3 p-3 bg-emerald-50 rounded-xl border border-emerald-100"
              >
                <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-extrabold uppercase shrink-0">
                  {{ u.nombre?.charAt(0) ?? '?' }}
                </div>
                <span class="text-sm font-semibold text-surface-800 truncate flex-1">{{ u.nombre }}</span>
                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200">
                  Asistió
                </span>
              </li>
            </ul>
          </section>

          <!-- ── NO SHOW (solo Clase Cerrada) ──────────────────────────── -->
          <section v-if="esCerrada">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-xs font-extrabold text-red-500 uppercase tracking-widest">No Show</h4>
              <span class="text-xs font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded-full border border-red-100">{{ noShow.length }}</span>
            </div>
            <div v-if="noShow.length === 0" class="text-center py-6 text-surface-400 text-sm font-medium bg-surface-50 rounded-2xl border border-surface-100">
              Sin no shows registrados
            </div>
            <ul v-else class="space-y-2">
              <li
                v-for="u in noShow"
                :key="u.id_inscripcion"
                class="flex items-center gap-3 p-3 bg-red-50 rounded-xl border border-red-100"
              >
                <div class="w-8 h-8 rounded-full bg-red-400 text-white flex items-center justify-center text-xs font-extrabold uppercase shrink-0">
                  {{ u.nombre?.charAt(0) ?? '?' }}
                </div>
                <span class="text-sm font-semibold text-surface-800 truncate flex-1">{{ u.nombre }}</span>
                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-red-100 text-red-600 border border-red-200">
                  No Show
                </span>
              </li>
            </ul>
          </section>

        </div>

        <!-- Footer — solo lectura, sin acciones de guardado -->
        <div class="px-6 pb-6 pt-4 shrink-0 border-t border-surface-100 bg-white">
          <p class="text-[11px] text-center text-surface-400 font-medium mb-3">Vista de solo lectura — los cambios se aplican desde el módulo de gestión</p>
          <button
            @click="emit('close')"
            class="w-full py-3 rounded-xl font-bold text-sm transition-all active:scale-95 border"
            :class="esCerrada
              ? 'bg-violet-50 text-violet-700 border-violet-200 hover:bg-violet-100'
              : 'bg-teal-50 text-teal-700 border-teal-200 hover:bg-teal-100'"
          >
            Cerrar
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar { width: 4px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

.fade-enter-active,
.fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

@keyframes scale-in {
  from { opacity: 0; transform: scale(0.96) translateY(12px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-scale-in { animation: scale-in 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
</style>
