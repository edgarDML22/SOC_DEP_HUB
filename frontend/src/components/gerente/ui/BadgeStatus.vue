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
  // Socios — estatus_cuenta
  AL_CORRIENTE:        { label: 'Al Corriente',      classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  MOROSO:              { label: 'Moroso',             classes: 'bg-amber-50  text-amber-700  border-amber-200'  },
  SUSPENDIDO:          { label: 'Suspendido',         classes: 'bg-red-50    text-red-700    border-red-200'    },
  PENALIZADO:          { label: 'Penalizado',         classes: 'bg-orange-50 text-orange-700 border-orange-200' },

  // Socios — estatus_penalizacion
  SIN_PENALIZACION:    { label: 'Sin Penalización',       classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  PENALIZADO_AMBOS:    { label: 'Penalizado Ambos',        classes: 'bg-purple-50 text-purple-700 border-purple-200' },
  PENALIZADO_RESERVA:  { label: 'Penalizado Reservas',     classes: 'bg-red-50    text-red-700    border-red-200'    },
  PENALIZADO_LUDOTECA: { label: 'Penalizado Ludoteca',     classes: 'bg-amber-50  text-amber-700  border-amber-200'  },

  // Socios — tipo_socio
  ACCIONISTA:          { label: 'Accionista',              classes: 'bg-indigo-50  text-indigo-700  border-indigo-200'  },
  RENTISTA:            { label: 'Rentista',                classes: 'bg-teal-50    text-teal-700    border-teal-200'    },

  // Socios — modalidad_plan
  INDIVIDUAL:          { label: 'Individual',              classes: 'bg-orange-50  text-orange-700  border-orange-200'  },
  FAMILIAR:            { label: 'Familiar',                classes: 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200' },

  // Socios — genero
  M:                   { label: 'Masculino',          classes: 'bg-sky-50    text-sky-700    border-sky-200'    },
  F:                   { label: 'Femenino',           classes: 'bg-rose-50   text-rose-700   border-rose-200'   },
  O:                   { label: 'Otro',               classes: 'bg-slate-50  text-slate-700  border-slate-200'  },
  OTRO:                { label: 'Otro',               classes: 'bg-slate-50  text-slate-700  border-slate-200'  },

  // Instructores / Usuarios
  ACTIVO:              { label: 'Activo',             classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  INACTIVO:            { label: 'Inactivo',           classes: 'bg-slate-100  text-slate-500  border-slate-200'  },
  BAJA_TEMPORAL:       { label: 'Baja Temporal',      classes: 'bg-orange-50 text-orange-700 border-orange-200' },
  PAUSA:               { label: 'En Pausa',           classes: 'bg-amber-50  text-amber-700  border-amber-200'  },
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

  // Ludoteca
  COMPLETADA_A_TIEMPO:    { label: 'A Tiempo',             classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  COMPLETADA_CON_RETRASO: { label: 'Con Retraso',          classes: 'bg-amber-50  text-amber-700  border-amber-200'  },
  FORZADO_POR_SISTEMA:    { label: 'Forzado por Sistema',  classes: 'bg-red-50    text-red-700    border-red-200'    },

  // Roles Administrativos
  GERENTE:                { label: 'Gerente',              classes: 'bg-indigo-50  text-indigo-700  border-indigo-200' },
  SUBGERENTE:             { label: 'Subgerente',           classes: 'bg-violet-50  text-violet-700  border-violet-200' },
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
