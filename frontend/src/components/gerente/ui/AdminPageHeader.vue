<script setup>
/**
 * AdminPageHeader — cabecera estándar para todas las vistas del panel admin.
 *
 * Slots:
 *   default  — acciones del lado derecho (botones, filtros rápidos, etc.)
 *   subtitle — sobrescribe el subtítulo si necesitas algo más complejo que texto plano
 *
 * Props:
 *   title    : string (requerido)
 *   subtitle : string (opcional)
 *   backRoute: string — nombre de ruta Vue Router para mostrar botón "Volver"
 */
defineProps({
  title:     { type: String, required: true },
  subtitle:  { type: String, default: '' },
  backRoute: { type: String, default: '' },
})
</script>

<template>
  <div class="flex items-start justify-between gap-4 flex-wrap">
    <div class="flex items-center gap-3">
      <!-- Botón volver opcional -->
      <router-link
        v-if="backRoute"
        :to="{ name: backRoute }"
        class="w-9 h-9 rounded-xl bg-white border border-slate-200 shadow-sm
               flex items-center justify-center text-slate-500
               hover:bg-slate-50 hover:text-slate-800 transition-all shrink-0"
        aria-label="Volver"
      >
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
      </router-link>

      <div>
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400 m-0 leading-none mb-1">
          Administración
        </p>
        <h1 class="text-2xl lg:text-3xl font-black text-slate-900 m-0 tracking-tight leading-tight">
          {{ title }}
        </h1>
        <slot name="subtitle">
          <p v-if="subtitle" class="text-sm text-slate-500 font-medium m-0 mt-1">{{ subtitle }}</p>
        </slot>
      </div>
    </div>

    <!-- Slot para botones/acciones del lado derecho -->
    <div v-if="$slots.default" class="flex items-center gap-3 shrink-0 flex-wrap">
      <slot />
    </div>
  </div>
</template>
