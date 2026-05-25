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
const contadores  = computed(() => props.sesion.contadores ?? { inscritos: 0, asistencia: 0, falta: 0 });

const porcentajeAsistencia = computed(() => {
  const total = contadores.value.inscritos;
  if (!total) return 0;
  return Math.round((contadores.value.asistencia / total) * 100);
});

const formatHora = (h) => h ? h.substring(0, 5) : '—';

// Acento semántico del header según tipo (unificado con el estándar global de modales)
const headerAccent = computed(() =>
  esCerrada.value ? 'border-violet-500' : 'border-teal-500'
);
const badgeClass = computed(() =>
  esCerrada.value
    ? 'bg-violet-100 text-violet-800 border-violet-200'
    : 'bg-teal-100 text-teal-800 border-teal-200'
);
const closeBtnClass = computed(() =>
  esCerrada.value
    ? 'bg-violet-50 text-violet-700 border-violet-200 hover:bg-violet-100'
    : 'bg-teal-50 text-teal-700 border-teal-200 hover:bg-teal-100'
);
</script>

<template>
  <Transition name="fade">
    <div class="fixed inset-0 z-100 flex items-end sm:items-center justify-center p-0 sm:p-4">
      <!-- Overlay -->
      <div class="absolute inset-0 bg-surface-900/60 backdrop-blur-sm" @click="emit('close')"></div>

      <!-- Panel -->
      <div class="relative bg-white w-full sm:max-w-lg rounded-t-4xl sm:rounded-[2.5rem] shadow-2xl overflow-hidden border border-surface-100 animate-scale-in max-h-[95dvh] flex flex-col">

        <!-- Header unificado: fondo blanco, acento lateral azul de marca, sin gradiente masivo -->
        <div
          class="px-6 sm:px-7 pt-6 pb-5 shrink-0 border-b border-surface-100 border-l-4"
          :class="headerAccent"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <!-- Badges de tipo y estado -->
              <div class="flex items-center gap-2 mb-3 flex-wrap">
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full border" :class="badgeClass">
                  {{ esCerrada ? 'CLASE CERRADA' : 'CLASE ABIERTA' }}
                </span>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full border border-surface-200 bg-surface-100 text-surface-600">
                  {{ sesion.estatus }}
                </span>
              </div>
              <!-- Título -->
              <h3 class="text-xl sm:text-2xl font-bold text-surface-900 tracking-tight leading-tight truncate">
                {{ sesion.titulo }}
              </h3>
              <!-- Meta -->
              <p class="text-surface-500 text-sm font-medium mt-1.5">
                {{ sesion.fecha }} • {{ formatHora(sesion.hora_inicio) }}–{{ formatHora(sesion.hora_fin) }}
                <template v-if="sesion.espacio"> • {{ sesion.espacio }}</template>
              </p>
            </div>
            <!-- Botón cerrar -->
            <button
              @click="emit('close')"
              class="w-9 h-9 bg-surface-100 hover:bg-surface-200 rounded-full flex items-center justify-center transition-colors focus:outline-none shrink-0"
              aria-label="Cerrar"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-surface-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Barra de progreso de asistencia -->
        <div class="px-6 pt-4 pb-3 shrink-0 bg-white border-b border-surface-100">
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest">Asistencia</span>
            <span class="text-sm font-bold" :class="porcentajeAsistencia >= 70 ? 'text-emerald-600' : 'text-amber-500'">
              {{ porcentajeAsistencia }}%
            </span>
          </div>
          <div class="w-full h-1.5 bg-surface-100 rounded-full overflow-hidden">
            <div
              class="h-1.5 rounded-full transition-all duration-500"
              :class="porcentajeAsistencia >= 70 ? 'bg-emerald-500' : 'bg-amber-400'"
              :style="{ width: porcentajeAsistencia + '%' }"
            ></div>
          </div>
          <!-- Chips de conteo semánticos -->
          <div class="flex items-center gap-2 mt-3 flex-wrap">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-surface-100 text-surface-600">
              <span class="w-2 h-2 rounded-full bg-surface-400 inline-block"></span>
              {{ contadores.inscritos }} inscritos
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
              <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
              {{ contadores.asistencia }} asistencia
            </span>
            <span v-if="esCerrada" class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-red-50 text-red-600 border border-red-100">
              <span class="w-2 h-2 rounded-full bg-red-400 inline-block"></span>
              {{ contadores.falta }} no show
            </span>
          </div>
        </div>

        <!-- Listas de usuarios (scrolleable) -->
        <div class="overflow-y-auto scrollbar-thin flex-1 px-6 sm:px-7 py-5 space-y-6">

          <!-- ── INSCRITOS ────────────────────────────────────────────────── -->
          <section>
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-[11px] font-extrabold text-surface-500 uppercase tracking-widest">Inscritos</h4>
              <span class="text-[11px] font-bold text-surface-500 bg-surface-100 px-2.5 py-0.5 rounded-full">{{ inscritos.length }}</span>
            </div>
            <div v-if="inscritos.length === 0" class="text-center py-6 text-surface-400 text-sm font-medium bg-surface-50 rounded-2xl border border-dashed border-surface-200">
              Sin inscritos confirmados
            </div>
            <ul v-else class="space-y-2">
              <li
                v-for="u in inscritos"
                :key="u.id_inscripcion"
                class="flex items-center gap-3 px-4 py-3 bg-white rounded-xl border border-surface-100 hover:border-surface-200 transition-colors"
              >
                <!-- Avatar inicial -->
                <div class="w-8 h-8 rounded-full bg-surface-200 text-surface-600 flex items-center justify-center text-xs font-extrabold uppercase shrink-0 select-none">
                  {{ u.nombre?.charAt(0) ?? '?' }}
                </div>
                <span class="text-sm font-semibold text-surface-800 truncate flex-1">{{ u.nombre }}</span>
                <span class="text-[10px] font-bold uppercase tracking-wide px-2.5 py-0.5 rounded-full bg-surface-100 text-surface-500 border border-surface-200 shrink-0">
                  Inscrito
                </span>
              </li>
            </ul>
          </section>

          <!-- ── ASISTENCIA ──────────────────────────────────────────────── -->
          <section>
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-[11px] font-extrabold text-emerald-600 uppercase tracking-widest">Asistencia</h4>
              <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-100">{{ asistencia.length }}</span>
            </div>
            <div v-if="asistencia.length === 0" class="text-center py-6 text-surface-400 text-sm font-medium bg-surface-50 rounded-2xl border border-dashed border-surface-200">
              Ningún registro de asistencia aún
            </div>
            <ul v-else class="space-y-2">
              <li
                v-for="u in asistencia"
                :key="u.id_inscripcion"
                class="flex items-center gap-3 px-4 py-3 bg-emerald-50/60 rounded-xl border border-emerald-100 hover:border-emerald-200 transition-colors"
              >
                <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-extrabold uppercase shrink-0 select-none">
                  {{ u.nombre?.charAt(0) ?? '?' }}
                </div>
                <span class="text-sm font-semibold text-surface-800 truncate flex-1">{{ u.nombre }}</span>
                <span class="text-[10px] font-bold uppercase tracking-wide px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 shrink-0">
                  Asistió
                </span>
              </li>
            </ul>
          </section>

          <!-- ── NO SHOW (solo Clase Cerrada) ──────────────────────────── -->
          <section v-if="esCerrada">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-[11px] font-extrabold text-red-500 uppercase tracking-widest">No Show</h4>
              <span class="text-[11px] font-bold text-red-500 bg-red-50 px-2.5 py-0.5 rounded-full border border-red-100">{{ noShow.length }}</span>
            </div>
            <div v-if="noShow.length === 0" class="text-center py-6 text-surface-400 text-sm font-medium bg-surface-50 rounded-2xl border border-dashed border-surface-200">
              Sin no shows registrados
            </div>
            <ul v-else class="space-y-2">
              <li
                v-for="u in noShow"
                :key="u.id_inscripcion"
                class="flex items-center gap-3 px-4 py-3 bg-red-50/60 rounded-xl border border-red-100 hover:border-red-200 transition-colors"
              >
                <div class="w-8 h-8 rounded-full bg-red-400 text-white flex items-center justify-center text-xs font-extrabold uppercase shrink-0 select-none">
                  {{ u.nombre?.charAt(0) ?? '?' }}
                </div>
                <span class="text-sm font-semibold text-surface-800 truncate flex-1">{{ u.nombre }}</span>
                <span class="text-[10px] font-bold uppercase tracking-wide px-2.5 py-0.5 rounded-full bg-red-100 text-red-600 border border-red-200 shrink-0">
                  No Show
                </span>
              </li>
            </ul>
          </section>

        </div>

        <!-- Footer -->
        <div class="px-6 pb-6 pt-4 shrink-0 border-t border-surface-100 bg-white">
          <p class="text-[11px] text-center text-surface-400 font-medium mb-3">
            Vista de solo lectura — los cambios se aplican desde el módulo de gestión
          </p>
          <button
            @click="emit('close')"
            class="w-full py-3 rounded-xl font-bold text-sm transition-all active:scale-95 border"
            :class="closeBtnClass"
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
