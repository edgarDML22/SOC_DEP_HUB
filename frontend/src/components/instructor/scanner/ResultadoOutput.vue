<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useScannerStore } from '@/stores/profiles/scannerStore'

const store  = useScannerStore()
const router = useRouter()

const ultimoResultado = computed(() => store.resultados[0] ?? null)
const exito    = computed(() => ultimoResultado.value?.success === true)
const mismatch = computed(() => ultimoResultado.value?.qr_mismatch === true)

// Casos específicos del flujo de Mis Clases (sesiones)
const esSesion          = computed(() => ultimoResultado.value?.tipo_actividad === 'sesion')
const exitoSesion       = computed(() => esSesion.value && exito.value)
const erroresParciales  = computed(() => ultimoResultado.value?.errores_parciales ?? null)
const tieneErrorParcial = computed(() => Array.isArray(erroresParciales.value) && erroresParciales.value.length > 0)

const LABEL_CATEGORIA = {
  CLASES:        'Mis Clases',
  RESERVACIONES: 'Reservaciones',
  TORNEO:        'Encuentros Torneo',
}
const labelCategoria = computed(() => LABEL_CATEGORIA[store.categoriaActiva] ?? '')

async function handleListo() {
  store.resetHub()
  await store.refreshDatosMenu()
  await router.push('/instructor/qr')
}

function continuarEscaneando() {
  store.irAtras()
}
</script>

<template>
  <div class="space-y-4">

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- ÉXITO — SESIÓN (Pase de lista de Mis Clases)                   -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <template v-if="exitoSesion">

      <div class="bg-white rounded-2xl shadow-sm border border-surface-100 overflow-hidden">

        <!-- Franja decorativa superior verde -->
        <div class="h-1.5 w-full" style="background-color: #059669;" />

        <div class="px-6 pt-8 pb-7 flex flex-col items-center text-center gap-5">

          <!-- Icono check -->
          <div class="w-20 h-20 rounded-full flex items-center justify-center shadow-lg"
               style="background-color: #059669;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>

          <!-- Textos -->
          <div class="space-y-1.5">
            <p class="text-2xl font-bold text-surface-900 tracking-tight">¡Asistencia Confirmada!</p>
            <p class="text-sm text-surface-500 font-medium leading-relaxed">
              La lista fue enviada correctamente.
            </p>
          </div>

          <!-- Total de participantes registrados -->
          <div class="w-full px-5 py-4 rounded-xl bg-green-50 border border-green-100 flex items-center justify-between">
            <div class="text-left">
              <p class="text-xs font-bold text-green-700 uppercase tracking-widest mb-1">
                Participantes registrados
              </p>
              <p class="text-[11px] font-semibold text-green-800 leading-snug">
                Asistencia registrada en el sistema
              </p>
            </div>
            <p class="text-3xl font-bold text-green-700 tabular-nums">
              {{ ultimoResultado?.data?.total_confirmados ?? 0 }}
            </p>
          </div>

          <!-- Contexto de sesión -->
          <div v-if="store.sesionActiva"
               class="w-full px-4 py-3.5 rounded-xl border border-blue-100 text-left"
               style="background-color: #EFF6FF;">
            <div class="flex items-center gap-1.5 mb-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-400" fill="none"
                   viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest">Sesión</p>
            </div>
            <p class="text-base font-bold text-blue-900 leading-tight">
              {{ store.sesionActiva.disciplina }}
            </p>
            <div class="flex items-center gap-1 mt-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-blue-400" fill="none"
                   viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-xs font-semibold text-blue-600 tabular-nums">
                {{ store.sesionActiva.hora_inicio }}–{{ store.sesionActiva.hora_fin }}
              </p>
            </div>
          </div>

        </div>
      </div>

      <button
        @click="handleListo"
        class="w-full py-4 rounded-2xl font-bold text-sm bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-600/20 active:scale-[0.98] transition-all focus:outline-none"
      >
        Listo
      </button>

    </template>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- ERROR PARCIAL — SESIÓN (algunos QR fallaron)                   -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <template v-else-if="esSesion && tieneErrorParcial">

      <div class="bg-white rounded-2xl shadow-sm border border-surface-100 overflow-hidden">

        <!-- Franja amarilla -->
        <div class="h-1.5 w-full" style="background-color: #F59E0B;" />

        <div class="px-6 pt-8 pb-7 flex flex-col items-center text-center gap-5">

          <!-- Icono advertencia -->
          <div class="w-20 h-20 rounded-full flex items-center justify-center shadow-lg"
               style="background-color: #F59E0B;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
          </div>

          <!-- Textos -->
          <div class="space-y-1.5">
            <p class="text-2xl font-bold text-surface-900 tracking-tight">Registro parcial</p>
            <p class="text-sm text-surface-500 font-medium leading-relaxed">
              {{ ultimoResultado?.total_registrados ?? 0 }} participante(s) registrados correctamente.
              Los siguientes códigos no pudieron procesarse:
            </p>
          </div>

          <!-- Chips de QR fallidos -->
          <div class="w-full flex flex-wrap gap-2 justify-center">
            <span
              v-for="qr in erroresParciales"
              :key="qr"
              class="bg-red-50 text-red-700 border border-red-200 rounded-full px-3 py-1.5 text-[11px] font-mono font-bold tracking-widest"
            >
              {{ qr }}
            </span>
          </div>

        </div>
      </div>

      <button
        @click="handleListo"
        class="w-full py-4 rounded-2xl font-bold text-sm bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-600/20 active:scale-[0.98] transition-all focus:outline-none"
      >
        Entendido
      </button>

    </template>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- ÉXITO — Reservaciones (flujo original)                         -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <template v-else-if="exito">

      <!-- Tarjeta central blanca -->
      <div class="bg-white rounded-2xl shadow-sm border border-surface-100 overflow-hidden">

        <!-- Franja decorativa superior -->
        <div class="h-1.5 w-full" style="background-color: #059669;" />

        <div class="px-6 pt-8 pb-7 flex flex-col items-center text-center gap-5">

          <!-- Icono check -->
          <div class="w-20 h-20 rounded-full flex items-center justify-center shadow-lg"
               style="background-color: #059669;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>

          <!-- Textos -->
          <div class="space-y-1.5">
            <p class="text-2xl font-bold text-surface-900 tracking-tight">¡Bienvenido!</p>
            <p class="text-sm text-surface-500 font-medium leading-relaxed">
              Asistencia confirmada de forma exitosa.
            </p>
          </div>

          <!-- Datos del socio -->
          <div v-if="ultimoResultado?.data?.nombre" class="w-full space-y-2.5">
            <!-- Nombre -->
            <div class="w-full px-4 py-3 rounded-xl bg-surface-50 border border-surface-100 text-left">
              <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-0.5">Socio</p>
              <p class="text-sm font-bold text-surface-900 leading-tight">
                {{ ultimoResultado.data.nombre }}
              </p>
            </div>
            <!-- Código QR + N.° acción -->
            <div class="flex gap-2">
              <div v-if="store.codigoEscaneado"
                   class="flex-1 px-4 py-3 rounded-xl bg-surface-50 border border-surface-100 text-left">
                <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-0.5">Código QR</p>
                <p class="text-sm font-bold text-surface-900 leading-tight">
                  {{ store.codigoEscaneado }}
                </p>
              </div>
              <div v-if="ultimoResultado?.data?.numero_accion"
                   class="flex-1 px-4 py-3 rounded-xl bg-surface-50 border border-surface-100 text-left">
                <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-0.5">N.° Acción</p>
                <p class="text-sm font-bold text-surface-900">
                  {{ ultimoResultado.data.numero_accion }}
                </p>
              </div>
            </div>
          </div>

          <!-- Fallback: solo código si no hay datos de socio -->
          <div v-else-if="store.codigoEscaneado"
               class="w-full px-4 py-3 rounded-xl bg-surface-50 border border-surface-100 text-left">
            <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-0.5">Código QR</p>
            <p class="text-sm font-bold text-surface-900 font-mono tracking-widest">
              {{ store.codigoEscaneado }}
            </p>
          </div>

        </div>
      </div>

      <!-- Botón Listo -->
      <button
        @click="handleListo"
        class="w-full py-4 rounded-2xl font-bold text-sm bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-600/20 active:scale-[0.98] transition-all focus:outline-none"
      >
        Listo
      </button>

    </template>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- QR MISMATCH                                                    -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <template v-else-if="mismatch">

      <div class="bg-white rounded-2xl shadow-sm border border-surface-100 overflow-hidden">

        <!-- Franja decorativa superior roja -->
        <div class="h-1.5 w-full" style="background-color: #DC2626;" />

        <div class="px-6 pt-8 pb-7 flex flex-col items-center text-center gap-5">

          <!-- Icono advertencia -->
          <div class="w-20 h-20 rounded-full flex items-center justify-center shadow-lg"
               style="background-color: #DC2626;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
          </div>

          <!-- Textos -->
          <div class="space-y-2">
            <p class="text-2xl font-bold text-surface-900 tracking-tight">Código no reconocido</p>
            <p class="text-sm text-surface-500 font-medium leading-relaxed">
              El código QR escaneado no corresponde al socio titular asociado a esta reservación.
              Verifique que el socio esté usando su propio código.
            </p>
          </div>

          <!-- Código escaneado -->
          <div v-if="store.codigoEscaneado"
               class="w-full px-4 py-3 rounded-xl border border-surface-100 text-left"
               style="background-color: #F1F5F9;">
            <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-0.5">Código escaneado</p>
            <p class="text-sm font-bold text-surface-900 font-mono tracking-widest">
              {{ store.codigoEscaneado }}
            </p>
          </div>

        </div>
      </div>

      <!-- Botón Escanear otro código -->
      <button
        @click="continuarEscaneando"
        class="w-full py-4 rounded-2xl font-bold text-sm bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-600/20 active:scale-[0.98] transition-all focus:outline-none"
      >
        Escanear otro código
      </button>

    </template>

    <!-- ══════════════════════════════════════════════════════════════ -->
    <!-- ERROR GENÉRICO                                                 -->
    <!-- ══════════════════════════════════════════════════════════════ -->
    <template v-else>

      <div class="bg-white rounded-2xl shadow-sm border border-surface-100 overflow-hidden">

        <div class="h-1.5 w-full bg-linear-to-r from-red-300 to-red-500" />

        <div class="px-6 pt-8 pb-7 flex flex-col items-center text-center gap-5">

          <div class="w-20 h-20 rounded-full bg-red-50 border border-red-100 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-red-500" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>

          <div class="space-y-1.5">
            <p class="text-2xl font-bold text-surface-900 tracking-tight">No se pudo registrar</p>
            <p class="text-sm text-surface-500 font-medium leading-relaxed">
              {{ ultimoResultado?.message ?? store.error ?? 'Ocurrió un error inesperado. Intente de nuevo.' }}
            </p>
          </div>

          <div v-if="store.codigoEscaneado"
               class="w-full px-4 py-3 rounded-xl border border-surface-100 text-left"
               style="background-color: #F1F5F9;">
            <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest mb-0.5">Código escaneado</p>
            <p class="text-base font-mono font-black tracking-widest text-surface-900">
              {{ store.codigoEscaneado }}
            </p>
          </div>

        </div>
      </div>

      <!-- Etiqueta de contexto -->
      <div class="flex items-center justify-center">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-surface-100 text-surface-500 text-xs font-semibold">
          <span class="w-1.5 h-1.5 rounded-full bg-surface-400"></span>
          {{ labelCategoria }}
        </span>
      </div>

      <button
        @click="continuarEscaneando"
        class="w-full py-4 rounded-2xl font-bold text-sm bg-primary-600 hover:bg-primary-700 text-white shadow-md shadow-primary-600/20 active:scale-[0.98] transition-all focus:outline-none"
      >
        Escanear otro código
      </button>

    </template>

  </div>
</template>
