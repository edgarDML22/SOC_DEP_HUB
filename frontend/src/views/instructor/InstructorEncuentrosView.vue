<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/services/api';
import { useAlerts } from '@/composables/useAlerts';
import ReportResultModal from '@/components/tournaments/ReportResultModal.vue';

const { toastError } = useAlerts();

const encuentros     = ref([]);
const isLoading      = ref(true);
const errorMsg       = ref('');
const selectedMatch  = ref(null);
const showModal      = ref(false);

onMounted(async () => {
  try {
    const response = await api.get('/instructor/encuentros-torneo');
    encuentros.value = response.data.data ?? [];
  } catch (err) {
    console.error('Error cargando encuentros de torneo:', err);
    errorMsg.value = 'No fue posible cargar tus encuentros de hoy.';
  } finally {
    isLoading.value = false;
  }
});

// ── Helpers ───────────────────────────────────────────────
const getCompName = (comp) => {
  if (!comp) return 'Por definir';
  // Use accessor that resolves external names
  return comp.nombre_completo ?? `Participante #${comp.id_interno ?? comp.id_participante_torneo}`;
};

const FASE_LABELS = {
  '16VOS':        'Dieciseisavos',
  '8VOS':         'Octavos de Final',
  'CUARTOS':      'Cuartos de Final',
  'SEMIFINALES':  'Semifinales',
  'TERCER_LUGAR': 'Tercer Lugar',
  'FINAL':        'Final',
};
const getFaseLabel = (fase) => FASE_LABELS[fase] ?? fase;

const formatHora = (dt) => {
  if (!dt) return '—';
  return new Date(dt).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
};

const STATUS_LABELS = {
  PENDIENTE:                         'Pendiente',
  EN_CURSO:                          'En curso',
  RESULTADO_PENDIENTE_VALIDACION:    'Pendiente de validación',
  FINALIZADO:                        'Finalizado',
  BYE:                               'Bye',
  CANCELADO:                         'Cancelado',
};
const getStatusLabel  = (s) => STATUS_LABELS[s] ?? s;

const STATUS_CLASSES = {
  PENDIENTE:                         'bg-surface-200 text-surface-700 border-surface-300',
  EN_CURSO:                          'bg-blue-100 text-blue-800 border-blue-300',
  RESULTADO_PENDIENTE_VALIDACION:    'bg-amber-100 text-amber-800 border-amber-300',
  FINALIZADO:                        'bg-emerald-100 text-emerald-800 border-emerald-300',
  BYE:                               'bg-purple-100 text-purple-800 border-purple-300',
  CANCELADO:                         'bg-red-100 text-red-800 border-red-300',
};
const getStatusClasses = (s) => STATUS_CLASSES[s] ?? STATUS_CLASSES.PENDIENTE;

const ACCENT_CLASSES = {
  PENDIENTE:                         'bg-surface-300',
  EN_CURSO:                          'bg-blue-500',
  RESULTADO_PENDIENTE_VALIDACION:    'bg-amber-400',
  FINALIZADO:                        'bg-emerald-500',
  BYE:                               'bg-purple-400',
  CANCELADO:                         'bg-red-500',
};
const getAccentClass = (s) => ACCENT_CLASSES[s] ?? 'bg-surface-200';

// ── Modal handlers ────────────────────────────────────────
const openModal = (encuentro) => {
  selectedMatch.value = encuentro;
  showModal.value     = true;
};

const handleUpdated = (scores) => {
  // Optimistic update: mark status locally without refetch
  const idx = encuentros.value.findIndex(
    (e) => e.id_encuentro === selectedMatch.value?.id_encuentro
  );
  if (idx !== -1) {
    encuentros.value[idx].estatus_encuentro = 'RESULTADO_PENDIENTE_VALIDACION';
    if (scores) {
      encuentros.value[idx].resultado_comp1 = scores.resultado_comp1;
      encuentros.value[idx].resultado_comp2 = scores.resultado_comp2;
    }
  }
  showModal.value    = false;
  selectedMatch.value = null;
};

const hayEncuentros = computed(() => encuentros.value.length > 0);
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans p-4 md:p-6 lg:p-8 pb-24 lg:pb-8 flex justify-center">
    <div class="w-full max-w-4xl flex flex-col gap-6 animate-fade-in">

      <!-- ── Encabezado ───────────────────────────────────── -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-surface-200 pb-5">
        <div>
          <button
            @click="$router.push('/instructor/home')"
            class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-4 focus:outline-none w-fit group"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m15 18-6-6 6-6"/>
            </svg>
            Volver
          </button>
          <div class="flex items-center gap-3 mb-1">
            <!-- Icon -->
            <div class="w-10 h-10 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
              </svg>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight">
              Mis Encuentros de Hoy
            </h1>
          </div>
          <p class="text-sm font-medium text-surface-500 m-0 mt-1 ml-[52px]">
            Torneos en los que eres árbitro asignado
          </p>
        </div>

        <!-- Badge contador -->
        <div v-if="!isLoading && hayEncuentros" class="ml-auto shrink-0">
          <span class="inline-flex items-center gap-2 bg-primary-50 text-primary-700 border border-primary-100 px-4 py-2 rounded-2xl text-sm font-bold">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ encuentros.length }} encuentro{{ encuentros.length !== 1 ? 's' : '' }}
          </span>
        </div>
      </div>

      <!-- ── Estado de carga ──────────────────────────────── -->
      <section v-if="isLoading" class="flex flex-col items-center justify-center p-12 mt-10">
        <div class="w-10 h-10 rounded-full border-[3px] border-surface-200 border-t-primary-500 animate-spin"/>
        <p class="text-surface-500 font-semibold mt-4 text-sm tracking-wide">
          Cargando tus encuentros de hoy...
        </p>
      </section>

      <!-- ── Error ────────────────────────────────────────── -->
      <section v-else-if="errorMsg"
        class="flex flex-col items-center justify-center p-8 bg-red-50 rounded-3xl border border-red-200 max-w-lg mx-auto mt-10 text-center shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-red-500 mb-3" fill="none"
          viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p class="text-red-700 font-bold text-lg mb-1">¡Ups! Algo salió mal</p>
        <p class="text-red-600 text-sm font-medium">{{ errorMsg }}</p>
      </section>

      <!-- ── Lista de encuentros ──────────────────────────── -->
      <div v-else-if="hayEncuentros" class="flex flex-col gap-5">
        <article
          v-for="enc in encuentros"
          :key="enc.id_encuentro"
          class="bg-white border border-surface-200 rounded-3xl shadow-xs hover:shadow-lg hover:border-surface-300 hover:-translate-y-0.5 transition-all duration-300 overflow-hidden"
        >
          <!-- Accent bar top -->
          <div class="h-1 w-full" :class="getAccentClass(enc.estatus_encuentro)"></div>

          <div class="p-5 md:p-6">
            <!-- Header: torneo chip + fase badge + status badge -->
            <div class="flex flex-wrap items-center gap-2 mb-5">
              <span class="inline-flex items-center gap-1.5 bg-primary-50 text-primary-700 border border-primary-100 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497" />
                </svg>
                {{ enc.torneo?.nombre_torneo ?? 'Torneo' }}
              </span>
              <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-surface-100 text-surface-600 border border-surface-200">
                {{ getFaseLabel(enc.fase_bracket) }}
              </span>
              <span class="ml-auto inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider border"
                :class="getStatusClasses(enc.estatus_encuentro)">
                {{ getStatusLabel(enc.estatus_encuentro) }}
              </span>
            </div>

            <!-- Competidores vs marcadores -->
            <div class="flex items-center justify-between gap-4 mb-5">
              <!-- Comp 1 -->
              <div class="flex-1 min-w-0">
                <p class="text-[10px] font-black uppercase tracking-widest text-surface-400 mb-1">Competidor 1</p>
                <p class="font-bold text-surface-900 text-base md:text-lg truncate leading-tight">
                  {{ getCompName(enc.competidor1) }}
                </p>
                <p v-if="enc.resultado_comp1 !== null && enc.resultado_comp1 !== undefined"
                   class="text-3xl font-black text-primary-600 mt-1 tabular-nums">
                  {{ enc.resultado_comp1 }}
                </p>
              </div>

              <!-- VS divider -->
              <div class="flex flex-col items-center gap-1 shrink-0 px-2">
                <div class="w-px h-6 bg-surface-200"></div>
                <span class="text-base font-black text-surface-300">VS</span>
                <div class="w-px h-6 bg-surface-200"></div>
              </div>

              <!-- Comp 2 -->
              <div class="flex-1 min-w-0 text-right">
                <p class="text-[10px] font-black uppercase tracking-widest text-surface-400 mb-1">Competidor 2</p>
                <p class="font-bold text-surface-900 text-base md:text-lg truncate leading-tight">
                  {{ getCompName(enc.competidor2) }}
                </p>
                <p v-if="enc.resultado_comp2 !== null && enc.resultado_comp2 !== undefined"
                   class="text-3xl font-black text-primary-600 mt-1 tabular-nums">
                  {{ enc.resultado_comp2 }}
                </p>
              </div>
            </div>

            <!-- Footer: horario + espacio + acción -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-surface-100">

              <!-- Metadata row -->
              <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-1.5 text-xs font-medium text-surface-500">
                  <svg class="w-3.5 h-3.5 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ formatHora(enc.fecha_hora_inicio) }} – {{ formatHora(enc.fecha_hora_fin) }}</span>
                </div>
                <div v-if="enc.id_espacio" class="flex items-center gap-1.5 text-xs font-medium text-surface-500">
                  <svg class="w-3.5 h-3.5 shrink-0 text-surface-400" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                  </svg>
                  <span>{{ enc.espacio_fisico?.nombre_espacio ?? `Espacio #${enc.id_espacio}` }}</span>
                </div>
              </div>

              <!-- CTA: condicional según estatus -->

              <!-- EN_CURSO → Botón reportar -->
              <button
                v-if="enc.estatus_encuentro === 'EN_CURSO'"
                @click="openModal(enc)"
                id="btn-reportar-resultado"
                class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-bold hover:bg-primary-700 active:scale-95 transition-all shadow-sm shadow-primary-600/20"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                </svg>
                Reportar Resultado
              </button>

              <!-- RESULTADO_PENDIENTE_VALIDACION → Badge informativo -->
              <div
                v-else-if="enc.estatus_encuentro === 'RESULTADO_PENDIENTE_VALIDACION'"
                class="flex items-center gap-2 px-4 py-2.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-xl text-sm font-bold"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Resultado enviado — pendiente de validación
              </div>

              <!-- FINALIZADO → Badge verde -->
              <div
                v-else-if="enc.estatus_encuentro === 'FINALIZADO'"
                class="flex items-center gap-2 px-4 py-2.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl text-sm font-bold"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Finalizado
              </div>

              <!-- PENDIENTE u otro → Info neutra -->
              <div
                v-else
                class="flex items-center gap-2 px-4 py-2.5 bg-surface-50 text-surface-500 border border-surface-200 rounded-xl text-sm font-medium"
              >
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Encuentro aún no iniciado
              </div>

            </div>
          </div>
        </article>
      </div>

      <!-- ── Estado vacío ─────────────────────────────────── -->
      <section v-else
        class="h-72 flex flex-col items-center justify-center rounded-3xl border border-dashed border-surface-300 bg-white shadow-xs mt-6 p-6 text-center">
        <div class="w-16 h-16 bg-amber-50 text-amber-400 rounded-full flex items-center justify-center mb-4 border border-amber-100 shadow-2xs">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497" />
          </svg>
        </div>
        <h3 class="text-lg font-bold text-surface-900 mb-1">Sin encuentros hoy</h3>
        <p class="text-surface-500 font-medium text-sm max-w-sm">
          No tienes encuentros de torneo asignados como árbitro para el día de hoy.
        </p>
      </section>

    </div>
  </main>

  <!-- ── Modal Reportar Resultado ────────────────────────── -->
  <ReportResultModal
    v-if="showModal && selectedMatch"
    :match="selectedMatch"
    @close="showModal = false; selectedMatch = null"
    @updated="handleUpdated"
  />
</template>
