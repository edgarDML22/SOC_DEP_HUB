<script setup>
import { computed } from 'vue';

/**
 * Estado vacío ilustrado para el Hub de Torneos del socio.
 *
 * Cada variante trae su propia ilustración SVG y copy por defecto, y expone un
 * slot `actions` para montar los CTAs funcionales de cada caso.
 *
 * - `disponible`: catálogo sin torneos abiertos.
 * - `historial`  : el socio todavía no registra participaciones (con medalla animada).
 * - `busqueda`   : la búsqueda activa no arroja resultados.
 */
const props = defineProps({
  variant: {
    type: String,
    default: 'disponible',
    validator: (value) => ['disponible', 'historial', 'busqueda'].includes(value),
  },
  title: { type: String, default: '' },
  message: { type: String, default: '' },
});

const COPY = {
  disponible: {
    title: 'No hay torneos disponibles',
    message:
      'Actualmente no hay torneos abiertos en fase de inscripción. Explora otras actividades del club o vuelve a verificar más tarde.',
  },
  historial: {
    title: 'Aún no tienes historial',
    message:
      'Tu primer torneo está más cerca de lo que crees. Inscríbete en uno disponible y empieza a construir tu palmarés.',
  },
  busqueda: {
    title: 'Sin resultados',
    message: 'No encontramos torneos que coincidan con tu búsqueda. Prueba con otro término o revisa el catálogo completo.',
  },
};

const content = computed(() => ({
  title: props.title || COPY[props.variant].title,
  message: props.message || COPY[props.variant].message,
}));
</script>

<template>
  <div
    class="flex flex-col items-center py-14 md:py-20 bg-white rounded-3xl border border-surface-100 shadow-sm text-center px-5"
  >
    <!-- ── Ilustración: no hay torneos abiertos ── -->
    <svg
      v-if="variant === 'disponible'"
      viewBox="0 0 220 160"
      fill="none"
      class="w-full max-w-[220px] h-auto mx-auto mb-2"
      aria-hidden="true"
    >
      <circle cx="110" cy="78" r="62" fill="#eff6ff" />
      <circle cx="110" cy="78" r="44" fill="#dbeafe" opacity=".5" />

      <!-- Calendario -->
      <rect x="70" y="48" width="80" height="74" rx="12" fill="#fff" stroke="#bfdbfe" stroke-width="2" />
      <path d="M70 66h80" stroke="#bfdbfe" stroke-width="2" />
      <rect x="86" y="38" width="8" height="18" rx="4" fill="#93c5fd" />
      <rect x="126" y="38" width="8" height="18" rx="4" fill="#93c5fd" />

      <!-- Espacios vacíos (días sin torneo) -->
      <rect x="82" y="78" width="20" height="7" rx="3.5" fill="#dbeafe" />
      <rect x="110" y="78" width="20" height="7" rx="3.5" fill="#e0e7ff" />
      <rect x="82" y="94" width="20" height="7" rx="3.5" fill="#e0e7ff" />
      <rect x="110" y="94" width="20" height="7" rx="3.5" fill="#dbeafe" />
      <rect x="82" y="110" width="20" height="7" rx="3.5" fill="#dbeafe" />

      <!-- Copa en desvanecido -->
      <g opacity=".45">
        <path
          d="M148 30c6 4 9 8 9 8s-3 4-9 8"
          stroke="#93c5fd"
          stroke-width="2.5"
          stroke-linecap="round"
        />
      </g>
    </svg>

    <!-- ── Ilustración: sin historial (medalla animada) ── -->
    <svg
      v-else-if="variant === 'historial'"
      viewBox="0 0 220 160"
      fill="none"
      class="w-full max-w-[220px] h-auto mx-auto mb-2"
      aria-hidden="true"
    >
      <circle cx="110" cy="80" r="62" fill="#fffbeb" />
      <circle cx="110" cy="80" r="44" fill="#fef3c7" opacity=".55" />

      <!-- Chispas de fondo -->
      <circle class="spark spark-a" cx="52" cy="46" r="3.5" fill="#fcd34d" />
      <circle class="spark spark-b" cx="172" cy="58" r="2.5" fill="#fbbf24" />
      <circle class="spark spark-c" cx="164" cy="120" r="3" fill="#fcd34d" />

      <g class="medal">
        <!-- Cintas -->
        <path d="M96 42L78 82h20l10-40z" fill="#fbbf24" />
        <path d="M124 42l18 40h-20l-10-40z" fill="#f59e0b" />

        <!-- Disco -->
        <circle cx="110" cy="100" r="32" fill="#f59e0b" />
        <circle cx="110" cy="100" r="25" fill="#fcd34d" />
        <circle cx="110" cy="100" r="19" fill="#fef3c7" />

        <!-- Estrella central -->
        <path
          d="M110 88.5l3.9 7.9 8.7 1.3-6.3 6.1 1.5 8.7-7.8-4.1-7.8 4.1 1.5-8.7-6.3-6.1 8.7-1.3z"
          fill="#b45309"
        />
      </g>
    </svg>

    <!-- ── Ilustración: búsqueda sin resultados ── -->
    <svg
      v-else
      viewBox="0 0 220 160"
      fill="none"
      class="w-full max-w-[220px] h-auto mx-auto mb-2"
      aria-hidden="true"
    >
      <circle cx="110" cy="78" r="62" fill="#f8f9fa" />
      <circle cx="110" cy="78" r="44" fill="#f3f4f6" opacity=".6" />

      <!-- Tarjeta vacía -->
      <rect x="62" y="44" width="96" height="72" rx="12" fill="#fff" stroke="#e5e7eb" stroke-width="2" />
      <rect x="76" y="60" width="48" height="7" rx="3.5" fill="#e5e7eb" />
      <rect x="76" y="76" width="64" height="7" rx="3.5" fill="#f3f4f6" />
      <rect x="76" y="92" width="36" height="7" rx="3.5" fill="#f3f4f6" />

      <!-- Lupa -->
      <g class="lupa">
        <circle cx="146" cy="96" r="24" fill="#fff" stroke="#2563eb" stroke-width="4" />
        <path d="M164 114l14 14" stroke="#2563eb" stroke-width="6" stroke-linecap="round" />
      </g>
    </svg>

    <h3 class="text-lg md:text-xl font-extrabold text-surface-900 tracking-tight mt-2">
      {{ content.title }}
    </h3>
    <p class="text-surface-500 font-semibold text-sm max-w-sm mt-1.5 leading-relaxed">
      {{ content.message }}
    </p>

    <div v-if="$slots.actions" class="mt-6 flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
      <slot name="actions" />
    </div>
  </div>
</template>

<style scoped>
/* La medalla flota suavemente para invitar a participar */
@keyframes medalFloat {
  0%,
  100% {
    transform: translate3d(0, 0, 0);
  }
  50% {
    transform: translate3d(0, -6px, 0);
  }
}

.medal {
  transform-box: fill-box;
  transform-origin: center;
  animation: medalFloat 3.2s cubic-bezier(0.45, 0, 0.55, 1) infinite;
}

@keyframes sparkFade {
  0%,
  100% {
    opacity: 0.25;
    transform: scale(0.8);
  }
  50% {
    opacity: 1;
    transform: scale(1.15);
  }
}

.spark {
  transform-box: fill-box;
  transform-origin: center;
  animation: sparkFade 3.2s ease-in-out infinite;
}
.spark-b {
  animation-delay: 0.5s;
}
.spark-c {
  animation-delay: 1s;
}

@keyframes lupaSweep {
  0%,
  100% {
    transform: translate3d(0, 0, 0);
  }
  50% {
    transform: translate3d(-7px, -7px, 0);
  }
}

.lupa {
  transform-box: fill-box;
  transform-origin: center;
  animation: lupaSweep 3.6s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
  .medal,
  .spark,
  .lupa {
    animation: none;
  }
}
</style>
