<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTournamentStore } from '@/stores/tournamentStore'
import { useAlerts } from '@/composables/useAlerts'
import TournamentStatusModal from '@/components/tournaments/TournamentStatusModal.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import BracketView from '@/components/tournaments/BracketView.vue'

const activeTab = ref('detalles')

const route = useRoute()
const router = useRouter()
const { toastSuccess, toastError } = useAlerts()
const store = useTournamentStore()

const torneoId = route.params.id
const torneo = computed(() => store.torneoActivo || {})

const isLoading = ref(true)
const errorMsg = ref('')

onMounted(async () => {
  if (torneoId) {
    try {
      await store.fetchTorneoById(torneoId)
    } catch (err) {
      console.error("Error al cargar torneo:", err)
      errorMsg.value = "Hubo un problema al cargar los detalles de este torneo."
    } finally {
      isLoading.value = false
    }
  }
})

const showStatusModal = ref(false)

const isFinalState = computed(() => {
  const status = torneo.value.estado || torneo.value.estatus_torneo
  return ['FINALIZADO', 'CANCELADO'].includes(status)
})

const handleStatusUpdated = async () => {
  if (torneoId) {
    try {
      await store.fetchTorneoById(torneoId)
    } catch (err) {
      console.error("Error al recargar torneo:", err)
    }
  }
}

const estadoLabel = computed(() => {
  const map = {
    EN_PLANIFICACION: 'En planificación',
    EN_INSCRIPCION: 'En inscripción',
    PROGRAMADO: 'Programado',
    EN_CURSO: 'En curso',
    FINALIZADO: 'Finalizado',
    CANCELADO: 'Cancelado'
  }
  const estado = torneo.value.estado || torneo.value.estatus_torneo
  return map[estado] ?? estado
})

const estadoClasses = computed(() => {
  const map = {
    EN_PLANIFICACION: 'bg-surface-100 text-surface-600 border-surface-200',
    EN_INSCRIPCION: 'bg-purple-50 text-purple-700 border-purple-200',
    PROGRAMADO: 'bg-blue-50 text-blue-700 border-blue-200',
    EN_CURSO: 'bg-amber-50 text-amber-700 border-amber-200',
    FINALIZADO: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    CANCELADO: 'bg-red-50 text-red-600 border-red-200'
  }
  const estado = torneo.value.estado || torneo.value.estatus_torneo
  return map[estado] ?? 'bg-surface-100 text-surface-600 border-surface-200'
})

const formatFecha = (f) => {
  if (!f) return '—'
  return new Date(f + 'T12:00:00').toLocaleDateString('es-MX', {
    day: 'numeric', month: 'long', year: 'numeric'
  })
}

const goBack = () => {
  router.push('/admin/tournaments')
}
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-20 font-sans">
    <div class="max-w-4xl mx-auto space-y-6">

      <!-- Navegación Superior -->
      <header class="flex items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <button @click="goBack"
            class="w-11 h-11 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 hover:text-primary-600 transition-all shadow-sm group">
            <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor"
              viewBox="0 0 24 24" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
          </button>
          <h1 class="text-2xl font-black text-surface-900 tracking-tight">Detalles del Torneo</h1>
        </div>

        <ConfirmButton v-if="!isFinalState" label="Gestionar Estado" @click="showStatusModal = true"
          class="bg-primary-600! hover:bg-primary-700!" />
      </header>

      <!-- Errores -->
      <section v-if="errorMsg" class="text-center p-12 bg-red-50 rounded-2xl border border-red-100 animate-scale-in">
        <p class="text-red-600 font-bold">{{ errorMsg }}</p>
        <button @click="goBack"
          class="mt-4 px-6 py-2.5 bg-white border border-red-200 text-red-600 rounded-xl font-bold hover:bg-red-50 transition-colors shadow-sm">
          Volver al listado
        </button>
      </section>

      <!-- Cargando -->
      <section v-if="isLoading" class="flex flex-col items-center justify-center p-20">
        <LoadingSpinner />
        <p class="text-sm font-extrabold uppercase tracking-widest text-surface-400 mt-4">Cargando detalles...</p>
      </section>

      <!-- Contenido Detallado -->
      <Transition enter-active-class="transition-all duration-500 ease-out"
        enter-from-class="opacity-0 translate-y-4 scale-[0.98]" enter-to-class="opacity-100 translate-y-0 scale-100">
        <section v-if="torneo && !isLoading">
          <article
            class="bg-white rounded-2xl shadow-xl shadow-surface-200/40 border border-surface-200 overflow-hidden relative">

            <!-- Accent Bar -->
            <div class="absolute top-0 left-0 right-0 h-1" :class="{
              'bg-surface-300': (torneo.estado || torneo.estatus_torneo) === 'EN_PLANIFICACION',
              'bg-purple-500': (torneo.estado || torneo.estatus_torneo) === 'EN_INSCRIPCION',
              'bg-blue-500': (torneo.estado || torneo.estatus_torneo) === 'PROGRAMADO',
              'bg-amber-500': (torneo.estado || torneo.estatus_torneo) === 'EN_CURSO',
              'bg-emerald-500': (torneo.estado || torneo.estatus_torneo) === 'FINALIZADO',
              'bg-red-500': (torneo.estado || torneo.estatus_torneo) === 'CANCELADO'
            }"></div>

            <!-- Cabecera Héroe -->
            <div class="flex items-center justify-between px-7 py-6 border-b border-surface-100">
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-sm shrink-0"
                  :class="estadoClasses">
                  <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-xl font-black text-surface-900 leading-tight">{{ torneo.nombre_torneo }}</h2>
                  <p class="text-xs font-extrabold text-surface-500 mt-1 uppercase tracking-widest">
                    {{ torneo.disciplina }} · {{ torneo.categoria }}
                    <span class="text-surface-300 mx-1">|</span>
                    <span class="font-mono text-surface-600 bg-surface-100 px-2 py-0.5 rounded-md normal-case tracking-normal">ID: {{ torneo.id_torneo }}</span>
                  </p>
                </div>
              </div>
              <span class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border"
                :class="estadoClasses">
                {{ estadoLabel }}
              </span>
            </div>

            <!-- Tabs de Navegación -->
            <div class="flex border-b border-surface-200 bg-surface-50/50">
              <button 
                @click="activeTab = 'detalles'"
                :class="activeTab === 'detalles' ? 'border-primary-600 text-primary-600 font-black' : 'border-transparent text-surface-500 hover:text-surface-700 hover:border-surface-300 font-bold'"
                class="py-3 px-6 border-b-2 text-sm transition-all focus:outline-none"
              >
                Detalles
              </button>
              <button 
                @click="activeTab = 'bracket'"
                :class="activeTab === 'bracket' ? 'border-primary-600 text-primary-600 font-black' : 'border-transparent text-surface-500 hover:text-surface-700 hover:border-surface-300 font-bold'"
                class="py-3 px-6 border-b-2 text-sm transition-all focus:outline-none"
              >
                Bracket
              </button>
            </div>

            <!-- Cuerpo de Información -->
            <div v-if="activeTab === 'detalles'" class="bg-surface-50/40 p-7">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <!-- Columna Izquierda: Detalles -->
                <div class="space-y-6">
                  <p class="text-[10px] font-black uppercase tracking-widest text-surface-500">Configuración General</p>

                  <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm">
                      <span class="block text-[10px] font-black uppercase tracking-widest text-surface-500 mb-1.5">Fecha Inicio</span>
                      <span class="text-sm font-bold text-surface-900">{{ formatFecha(torneo.fecha_inicio) }}</span>
                    </div>
                    <div class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm">
                      <span class="block text-[10px] font-black uppercase tracking-widest text-surface-500 mb-1.5">Fecha Fin</span>
                      <span class="text-sm font-bold text-surface-900">{{ formatFecha(torneo.fecha_fin) }}</span>
                    </div>
                    <div class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm">
                      <span class="block text-[10px] font-black uppercase tracking-widest text-surface-500 mb-1.5">Modalidad</span>
                      <span class="text-sm font-bold text-surface-900">{{ torneo.modalidad || '—' }}</span>
                    </div>
                    <div class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm">
                      <span class="block text-[10px] font-black uppercase tracking-widest text-surface-500 mb-1.5">Formato</span>
                      <span class="text-sm font-bold text-surface-900">
                        {{ torneo.formato_competencia === 'ELIMINACION_DIRECTA' ? 'Eliminación directa' : 'Fase de grupos' }}
                      </span>
                    </div>
                  </div>

                  <div v-if="torneo.descripcion" class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm">
                    <span class="block text-[10px] font-black uppercase tracking-widest text-surface-500 mb-1.5">Descripción</span>
                    <p class="text-sm text-surface-600 leading-relaxed font-medium">{{ torneo.descripcion }}</p>
                  </div>
                </div>

                <!-- Columna Derecha: Estado y Próximos Pasos -->
                <div class="space-y-6">
                  <p class="text-[10px] font-black uppercase tracking-widest text-surface-500">Restricciones y Cupos</p>

                  <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm">
                      <span class="block text-[10px] font-black uppercase tracking-widest text-surface-500 mb-1.5">Tipo Acceso</span>
                      <span class="text-sm font-bold text-surface-900">{{ torneo.tipo_acceso === 'INTERNO' ? 'Interno' : 'Abierto' }}</span>
                    </div>
                    <div class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm">
                      <span class="block text-[10px] font-black uppercase tracking-widest text-surface-500 mb-1.5">Género Requerido</span>
                      <span class="text-sm font-bold text-surface-900">
                        {{
                          torneo.genero === 'M' || torneo.genero === 'Masculino' || torneo.genero === 'Varonil' || torneo.genero === 'VARONIL'
                            ? 'Varonil'
                            : (torneo.genero === 'F' || torneo.genero === 'Femenino' || torneo.genero === 'Femenil' || torneo.genero === 'FEMENIL'
                              ? 'Femenil'
                              : (torneo.genero === 'MIXTO' || torneo.genero === 'Mixto'
                                ? 'Mixto'
                                : torneo.genero || 'Cualquiera'))
                        }}
                      </span>
                    </div>
                    <div class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm">
                      <span class="block text-[10px] font-black uppercase tracking-widest text-surface-500 mb-1.5">Cupo Mínimo</span>
                      <span class="text-sm font-bold text-surface-900">{{ torneo.cupo_minimo || 'Sin mínimo' }}</span>
                    </div>
                    <div class="bg-white rounded-2xl p-5 border border-surface-200 shadow-sm">
                      <span class="block text-[10px] font-black uppercase tracking-widest text-surface-500 mb-1.5">Cupo Máximo</span>
                      <span class="text-sm font-bold text-surface-900">{{ torneo.cupo_maximo || 'Sin límite' }}</span>
                    </div>

                    <!-- Motivo de Cancelación (Solo si aplica) -->
                    <div v-if="(torneo.estado || torneo.estatus_torneo) === 'CANCELADO' && torneo.motivo_cancelacion" 
                         class="bg-red-50 rounded-2xl p-5 border border-red-200 shadow-sm col-span-2">
                      <span class="block text-[10px] font-black uppercase tracking-widest text-red-500 mb-1.5">Motivo de Cancelación</span>
                      <p class="text-sm font-bold text-red-900">{{ torneo.motivo_cancelacion }}</p>
                    </div>
                  </div>

                  <p class="text-[10px] font-black uppercase tracking-widest text-surface-500 pt-2">Estado y Seguimiento</p>

                  <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden">
                    <div class="p-6">
                      <div class="flex items-center gap-4 mb-4">
                        <div
                          class="w-12 h-12 rounded-xl flex items-center justify-center bg-primary-50 text-primary-600">
                          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" />
                          </svg>
                        </div>
                        <div>
                          <p class="text-sm font-black text-surface-900">Estado Actual: {{ estadoLabel }}</p>
                          <p class="text-xs text-surface-500 font-medium">Ciclo de vida del torneo</p>
                        </div>
                      </div>

                      <div v-if="canConfirm" class="mt-4 p-4 rounded-xl bg-purple-50 border border-purple-100">
                        <p class="text-xs text-purple-700 font-bold leading-relaxed">
                          ⚠️ Este torneo se encuentra en fase de planificación. Al confirmarlo, se habilitarán las
                          inscripciones públicas o internas según la configuración.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- Cuerpo del Bracket -->
            <div v-else-if="activeTab === 'bracket'" class="p-7">
              <BracketView :idTorneo="Number(torneoId)" />
            </div>

            <!-- Pie de Acciones -->
            <div class="flex items-center justify-end gap-3 px-7 py-5 border-t border-surface-100 bg-white">
              <button @click="goBack" class="px-6 py-2.5 rounded-xl border border-surface-200 bg-white
                       text-sm font-bold text-surface-700 hover:bg-surface-50 transition-colors">
                Volver al listado
              </button>
            </div>
          </article>
        </section>
      </Transition>

    </div>

    <TournamentStatusModal 
      v-if="showStatusModal" 
      :torneo="torneo"
      @close="showStatusModal = false" 
      @updated="handleStatusUpdated" 
    />

  </main>
</template>
