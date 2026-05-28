<script setup>
defineProps({
    participantes: { type: Array, required: true },
    emptyTitulo:   { type: String, default: 'Sin participantes' },
    emptyMensaje:  { type: String, default: '' },
})

const TIPO_LABEL = {
    socio_titular:    'Socio',
    miembro_familiar: 'Familiar',
    invitado:         'Invitado',
}

const TIPO_STYLE = {
    socio_titular:    'bg-primary-50 text-primary-700',
    miembro_familiar: 'bg-violet-50 text-violet-700',
    invitado:         'bg-amber-50 text-amber-700',
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
        class="flex items-center gap-3 px-4 py-3 rounded-2xl border bg-white transition-all duration-200"
        :class="p.asistencia
          ? 'border-green-100 bg-green-50/40'
          : 'border-surface-100'"
      >
        <!-- Indicador de estado -->
        <div
          class="shrink-0 w-9 h-9 rounded-xl flex items-center justify-center"
          :class="p.asistencia ? 'bg-green-100' : 'bg-surface-100'"
        >
          <svg v-if="p.asistencia"
            xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span v-else class="w-2 h-2 rounded-full bg-surface-300" />
        </div>

        <!-- Nombre + código -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <p class="text-sm font-bold text-surface-900 truncate leading-tight">{{ p.nombre }}</p>
            <span
              v-if="TIPO_LABEL[p.tipo_usuario]"
              class="shrink-0 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-md"
              :class="TIPO_STYLE[p.tipo_usuario] ?? 'bg-surface-100 text-surface-500'"
            >
              {{ TIPO_LABEL[p.tipo_usuario] }}
            </span>
          </div>
          <p
            class="text-[11px] font-mono font-semibold tracking-widest mt-0.5"
            :class="p.asistencia ? 'text-green-600' : 'text-surface-400'"
          >
            {{ p.codigo_qr ?? '—' }}
          </p>
        </div>

        <!-- Chip de estado -->
        <span
          class="shrink-0 text-[10px] font-bold px-2.5 py-1 rounded-full"
          :class="p.asistencia
            ? 'bg-green-100 text-green-700'
            : 'bg-surface-100 text-surface-500'"
        >
          {{ p.asistencia ? 'Confirmado' : 'Pendiente' }}
        </span>
      </li>
    </ul>
  </div>
</template>
