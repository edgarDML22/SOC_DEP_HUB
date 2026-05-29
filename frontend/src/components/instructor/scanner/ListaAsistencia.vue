<script setup>
import { ref } from 'vue'

defineProps({
    participantes: { type: Array, required: true },
    emptyTitulo:   { type: String, default: 'Sin participantes' },
    emptyMensaje:  { type: String, default: '' },
})

const failedImages = ref(new Set())

const TIPO_LABEL = {
    socio_titular:    'Socio',
    miembro_familiar: 'Familiar',
    invitado:         'Invitado',
}

const TIPO_STYLE = {
    socio_titular:    'bg-primary-50 text-primary-700',
    miembro_familiar: 'bg-yellow-100 text-yellow-800',
    invitado:         'bg-surface-100 text-surface-700',
}

// ---- Variantes visuales por estado_asistencia ---------------------------
// PENDIENTE        → gris neutral
// NUEVO_CONFIRMADO → verde brillante (escaneado en esta sesión del Hub)
// YA_REGISTRADO    → azul (asistencia previa, no es escaneo reciente)
const ITEM_STYLE = {
    PENDIENTE:        'border-surface-100',
    NUEVO_CONFIRMADO: 'border-green-100 bg-green-50/40',
    YA_REGISTRADO:    'border-blue-100 bg-blue-50/40',
}

const ICON_BG_STYLE = {
    PENDIENTE:        'bg-surface-100',
    NUEVO_CONFIRMADO: 'bg-green-100',
    YA_REGISTRADO:    'bg-blue-100',
}

const CHIP_STYLE = {
    PENDIENTE:        'bg-surface-100 text-surface-500',
    NUEVO_CONFIRMADO: 'bg-green-100 text-green-700',
    YA_REGISTRADO:    'bg-blue-100 text-blue-700',
}

const CHIP_LABEL = {
    PENDIENTE:        'Pendiente',
    NUEVO_CONFIRMADO: 'Confirmado',
    YA_REGISTRADO:    'Previo',
}
</script>

<template>
  <div>
    <!-- Estado vacío -->
    <div v-if="participantes.length === 0" class="text-center py-10 px-4">
      <div class="w-12 h-12 mx-auto rounded-2xl bg-surface-100 flex items-center justify-center mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-surface-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
      </div>
      <p class="text-sm font-bold text-surface-600">{{ emptyTitulo }}</p>
      <p v-if="emptyMensaje" class="text-xs text-surface-400 mt-1">{{ emptyMensaje }}</p>
    </div>

    <!-- Filas -->
    <ul v-else class="space-y-2">
      <li
        v-for="(p, i) in participantes"
        :key="p.codigo_qr ?? `${p.tipo_usuario}-${p.id_usuario}-${i}`"
        class="flex items-center gap-3.5 px-4 py-3 rounded-2xl border bg-white transition-all duration-200 hover:shadow-sm"
        :class="ITEM_STYLE[p.estado_asistencia] ?? 'border-surface-200/60 hover:border-primary-200'"
      >
        <!-- Avatar + Status Badge -->
        <div class="relative shrink-0">
          <div class="w-10 h-10 rounded-full overflow-hidden border border-surface-200 bg-surface-50 shrink-0 shadow-sm">
            <img 
              v-if="p.tipo_usuario === 'socio_titular' && p.foto_perfil && !failedImages.has(p.id_usuario)" 
              :src="p.foto_perfil" 
              class="w-full h-full object-cover" 
              alt="Foto" 
              @error="failedImages.add(p.id_usuario)"
            />
            <div v-else class="w-full h-full flex items-center justify-center bg-primary-50 text-primary-700 font-bold text-sm">
              {{ p.nombre.charAt(0).toUpperCase() }}
            </div>
          </div>
          
          <!-- Status Badge -->
          <div 
            class="absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full border-2 border-white flex items-center justify-center shadow-sm"
            :class="ICON_BG_STYLE[p.estado_asistencia] ?? 'bg-surface-200'"
          >
            <svg
              v-if="p.estado_asistencia === 'NUEVO_CONFIRMADO'"
              xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-green-700"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <svg
              v-else-if="p.estado_asistencia === 'YA_REGISTRADO'"
              xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-blue-700"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span v-else class="w-1.5 h-1.5 rounded-full bg-surface-400" />
          </div>
        </div>

        <!-- Nombre + código -->
        <div class="flex-1 min-w-0">
          <div class="flex flex-col gap-0.5">
            <p class="text-sm font-bold text-surface-900 truncate leading-tight">{{ p.nombre }}</p>
            <div class="flex items-center gap-1.5 mt-0.5">
              <span
                v-if="TIPO_LABEL[p.tipo_usuario]"
                class="shrink-0 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-md"
                :class="TIPO_STYLE[p.tipo_usuario] ?? 'bg-surface-100 text-surface-500'"
              >
                {{ TIPO_LABEL[p.tipo_usuario] }}
              </span>
            </div>
          </div>
        </div>

        <!-- Chip de estado -->
        <span
          class="shrink-0 text-[10px] font-bold px-2.5 py-1 rounded-full"
          :class="CHIP_STYLE[p.estado_asistencia] ?? 'bg-surface-100 text-surface-500'"
        >
          {{ CHIP_LABEL[p.estado_asistencia] ?? 'Pendiente' }}
        </span>
      </li>
    </ul>
  </div>
</template>
