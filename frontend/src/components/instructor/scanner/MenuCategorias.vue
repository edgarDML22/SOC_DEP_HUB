<script setup>
import { computed, onMounted } from 'vue'
import { useScannerStore } from '@/stores/profiles/scannerStore'
import { IconCalendar } from '@/components/icons'
import CategoriaCard from './CategoriaCard.vue'

const store = useScannerStore()

// Carga las 3 listas en paralelo al montar
onMounted(() => store.fetchDatosMenu())

const cargando = computed(() =>
    store.sesionesLoading || store.reservacionesLoading || store.encuentrosLoading
)

const reservasActivas = computed(() =>
    store.reservacionesHoy.filter(r => r.estatus_operativo === 'ACTIVA')
)
</script>

<template>
  <div class="space-y-3">

    <!-- Skeleton -->
    <template v-if="cargando">
      <div v-for="i in 3" :key="i" class="h-[82px] rounded-2xl bg-surface-100 animate-pulse" />
    </template>

    <template v-else>

      <!-- MIS CLASES -->
      <CategoriaCard
        titulo="Mis Clases"
        :subtitulo="store.haySesionesHoy
          ? 'Pase de lista de tus sesiones de hoy'
          : 'No tienes sesiones activas en este momento'"
        :deshabilitado="!store.haySesionesHoy"
        @click="store.seleccionarCategoria('CLASES')"
      >
        <template #icon>
          <IconCalendar class="w-6 h-6" />
        </template>
      </CategoriaCard>

      <!-- RESERVACIONES -->
      <CategoriaCard
        titulo="Reservaciones"
        :subtitulo="store.hayReservaciones
          ? 'Valida el acceso de socios con reserva activa'
          : 'No hay reservaciones pendientes para hoy'"
        :deshabilitado="!store.hayReservaciones"
        @click="store.seleccionarCategoria('RESERVACIONES')"
      >
        <template #icon>
          <!-- Ticket / key outline -->
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
          </svg>
        </template>
      </CategoriaCard>

      <!-- ENCUENTROS TORNEO -->
      <CategoriaCard
        titulo="Encuentros Torneo"
        :subtitulo="store.hayEncuentros
          ? 'Registra participantes en tus encuentros de hoy'
          : 'No tienes encuentros asignados hoy'"
        :deshabilitado="!store.hayEncuentros"
        @click="store.seleccionarCategoria('TORNEO')"
      >
        <template #icon>
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
          </svg>
        </template>
      </CategoriaCard>

    </template>

    <!-- Pills de contexto tras cargar -->
    <template v-if="!cargando">
      <div class="flex flex-wrap gap-2 pt-1">
        <span
          v-if="store.haySesionesHoy"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary-50 border border-primary-100 text-xs font-semibold text-primary-700"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse"></span>
          {{ store.sesionesHoy.length }} {{ store.sesionesHoy.length === 1 ? 'clase' : 'clases' }} hoy
        </span>
        <span
          v-if="store.hayReservaciones"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary-50 border border-primary-100 text-xs font-semibold text-primary-700"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse"></span>
          {{ reservasActivas.length }} {{ reservasActivas.length === 1 ? 'reserva' : 'reservas' }} pendientes
        </span>
        <span
          v-if="store.hayEncuentros"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary-50 border border-primary-100 text-xs font-semibold text-primary-700"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse"></span>
          {{ store.encuentrosHoy.length }} {{ store.encuentrosHoy.length === 1 ? 'encuentro' : 'encuentros' }} hoy
        </span>
      </div>
    </template>

  </div>
</template>
