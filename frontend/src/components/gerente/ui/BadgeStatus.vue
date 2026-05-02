<script setup>
/**
 * BadgeStatus — insignia de estado unificada para todo el panel admin.
 *
 * Props:
 *   status : string — clave del estado (ej. 'AL_CORRIENTE', 'ACTIVO', 'PROGRAMADO'...)
 *   size   : 'sm' | 'md'  (default 'sm')
 *
 * El componente mapea automáticamente el estado a paleta de colores.
 * Para estados desconocidos devuelve slate (neutral).
 */
const props = defineProps({
  status: { type: String, required: true },
  size:   { type: String, default: 'sm' },
})

const STATUS_MAP = {
  // Socios
  AL_CORRIENTE:        { label: 'Al Corriente',      classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  MOROSO:              { label: 'Moroso',             classes: 'bg-amber-50  text-amber-700  border-amber-200'  },
  SUSPENDIDO:          { label: 'Suspendido',         classes: 'bg-red-50    text-red-700    border-red-200'    },
  PENALIZADO:          { label: 'Penalizado',         classes: 'bg-orange-50 text-orange-700 border-orange-200' },
  PENALIZADO_AMBOS:    { label: 'Pen. Ambos',         classes: 'bg-orange-50 text-orange-700 border-orange-200' },
  PENALIZADO_RESERVA:  { label: 'Pen. Reservas',      classes: 'bg-orange-50 text-orange-700 border-orange-200' },
  PENALIZADO_LUDOTECA: { label: 'Pen. Ludoteca',      classes: 'bg-orange-50 text-orange-700 border-orange-200' },

  // Instructores / Usuarios
  ACTIVO:              { label: 'Activo',             classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  INACTIVO:            { label: 'Inactivo',           classes: 'bg-slate-100  text-slate-500  border-slate-200'  },
  HABILITADO:          { label: 'Habilitado',         classes: 'bg-blue-50   text-blue-700   border-blue-200'   },
  DESHABILITADO:       { label: 'Deshabilitado',      classes: 'bg-red-50    text-red-700    border-red-200'    },

  // Torneos
  PROGRAMADO:          { label: 'Programado',         classes: 'bg-blue-50   text-blue-700   border-blue-200'   },
  EN_CURSO:            { label: 'En Curso',           classes: 'bg-purple-50 text-purple-700 border-purple-200' },
  FINALIZADO:          { label: 'Finalizado',         classes: 'bg-slate-100 text-slate-500  border-slate-200'  },
  CANCELADO:           { label: 'Cancelado',          classes: 'bg-red-50    text-red-700    border-red-200'    },

  // Invitados / Pases
  ACTIVO_PASE:         { label: 'Pase Activo',        classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  USADO:               { label: 'Usado',              classes: 'bg-slate-100  text-slate-500  border-slate-200'  },
  EXPIRADO:            { label: 'Expirado',           classes: 'bg-red-50    text-red-700    border-red-200'    },
}

const resolved = (status) => {
  const key = status?.toUpperCase?.() ?? ''
  return STATUS_MAP[key] ?? { label: key || '—', classes: 'bg-slate-100 text-slate-500 border-slate-200' }
}

const sizeClasses = {
  sm: 'text-[10px] tracking-wider px-2.5 py-0.5',
  md: 'text-xs          tracking-wide  px-3    py-1',
}
</script>

<template>
  <span
    class="inline-flex items-center font-bold rounded-full border uppercase shrink-0 whitespace-nowrap"
    :class="[resolved(status).classes, sizeClasses[size] ?? sizeClasses.sm]"
  >
    {{ resolved(status).label }}
  </span>
</template>
