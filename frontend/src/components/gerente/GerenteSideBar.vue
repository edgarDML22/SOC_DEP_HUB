<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import {
  IconHome,
  IconCalendar,
  IconUser,
  IconGuests,
  IconShield,
} from '@/components/icons'

const route  = useRoute()
const isOpen = ref(true)
const openSection = ref(null)

const toggleSidebar = () => {
  isOpen.value = !isOpen.value
  // Al colapsar ocultamos todos los submenús abiertos
  if (!isOpen.value) openSection.value = null
}

const toggleSection = (key) => {
  openSection.value = openSection.value === key ? null : key
}

// Detección de sección activa para resaltar el botón padre aunque esté colapsado
const isRouteUnder = (paths) => paths.some(p => route.path.startsWith(p))

const sections = [
  {
    key: 'operacion',
    label: 'Operación',
    icon: IconCalendar,
    paths: ['/admin/reservations', '/admin/ludoteca', '/admin/tournaments'],
    children: [
      { label: 'Reservas On-Demand',  to: '/admin/reservations' },
      { label: 'Ludoteca',            to: '/admin/ludoteca/ludoteca-list' },
      { label: 'Torneos',             to: '/admin/tournaments' },
    ],
  },
  {
    key: 'usuarios',
    label: 'Usuarios',
    icon: IconUser,
    paths: ['/admin/socios', '/admin/instructors'],
    children: [
      { label: 'Socios',       to: '/admin/socios' },
      { label: 'Instructores', to: '/admin/instructors' },
    ],
  },
  {
    key: 'club',
    label: 'Config. del Club',
    icon: IconShield,
    paths: ['/admin/spaces', '/admin/disciplines'],
    children: [
      { label: 'Espacios',     to: '/admin/spaces/list' },
      { label: 'Disciplinas',  to: '/admin/disciplines/list' },
    ],
  },
]
</script>

<template>
  <aside
    class="h-screen sticky top-0 z-50 bg-slate-950 text-slate-400 flex flex-col
           transition-all duration-300 ease-in-out shrink-0"
    :class="isOpen ? 'w-64' : 'w-[72px]'"
  >
    <!-- ── HEADER ─────────────────────────────────────── -->
    <div class="flex items-center gap-3 px-4 py-5 border-b border-slate-800/60">
      <button
        @click="toggleSidebar"
        class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0
               hover:bg-slate-800 transition-colors"
        aria-label="Colapsar menú"
      >
        <img src="@/assets/LogoSocDep.jpg" class="w-7 h-7 rounded-lg object-cover" alt="SOC-DEP" />
      </button>

      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 -translate-x-2"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 -translate-x-2"
      >
        <span v-if="isOpen" class="text-sm font-black text-white tracking-tight whitespace-nowrap overflow-hidden">
          SOC-DEP HUB
        </span>
      </Transition>
    </div>

    <!-- ── NAVEGACIÓN ──────────────────────────────────── -->
    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-2 flex flex-col gap-0.5">

      <!-- Dashboard (ítem directo) -->
      <router-link
        to="/admin/dashboard"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
               transition-all duration-200 group"
        :class="route.path === '/admin/dashboard'
          ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30'
          : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-100'"
        :title="!isOpen ? 'Dashboard' : undefined"
      >
        <IconHome class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" />
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-x-1"
          enter-to-class="opacity-100 translate-x-0"
          leave-active-class="transition-none"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <span v-if="isOpen" class="truncate">Dashboard</span>
        </Transition>
      </router-link>

      <!-- Sección "Reportes" (ítem directo) -->
      <router-link
        to="/admin/reports"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
               transition-all duration-200 group"
        :class="route.path.startsWith('/admin/reports')
          ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30'
          : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-100'"
        :title="!isOpen ? 'Reportes' : undefined"
      >
        <IconGuests class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" />
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-x-1"
          enter-to-class="opacity-100 translate-x-0"
          leave-active-class="transition-none"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <span v-if="isOpen" class="truncate">Reportes</span>
        </Transition>
      </router-link>

      <!-- ── DIVIDER + SECCIONES CON SUBMENÚ ──────────── -->
      <div class="my-2 border-t border-slate-800/60" />

      <template v-for="section in sections" :key="section.key">
        <div class="flex flex-col">

          <!-- Botón padre de sección -->
          <button
            @click="toggleSection(section.key)"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium w-full
                   transition-all duration-200 group"
            :class="[
              isRouteUnder(section.paths)
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30 font-bold'
                : openSection === section.key
                  ? 'bg-slate-800 text-slate-100'
                  : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-100',
            ]"
            :title="!isOpen ? section.label : undefined"
            type="button"
          >
            <component
              :is="section.icon"
              class="w-5 h-5 shrink-0 transition-all duration-300 group-hover:scale-110"
              :class="isRouteUnder(section.paths) ? 'text-white' : 'text-slate-400 group-hover:text-slate-100'"
            />

            <Transition
              enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 -translate-x-1"
              enter-to-class="opacity-100 translate-x-0"
              leave-active-class="transition-none"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <span v-if="isOpen" class="flex-1 text-left truncate">{{ section.label }}</span>
            </Transition>

            <!-- Chevron animado -->
            <Transition
              enter-active-class="transition-all duration-200"
              enter-from-class="opacity-0"
              enter-to-class="opacity-100"
              leave-active-class="transition-none"
            >
              <svg
                v-if="isOpen"
                class="w-3.5 h-3.5 shrink-0 transition-transform duration-300"
                :class="[
                  openSection === section.key ? 'rotate-180' : '',
                  isRouteUnder(section.paths) ? 'text-white' : openSection === section.key ? 'text-blue-400' : 'text-slate-600'
                ]"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
              >
                <path d="M6 9l6 6 6-6"/>
              </svg>
            </Transition>
          </button>

          <!-- Submenú con animación grid-rows trick -->
          <div
            class="grid transition-all duration-300 ease-in-out overflow-hidden"
            :class="openSection === section.key && isOpen
              ? 'grid-rows-[1fr] opacity-100 mt-0.5'
              : 'grid-rows-[0fr] opacity-0'"
          >
            <div class="min-h-0 flex flex-col gap-0.5 pl-3 pb-0.5">
              <!-- Línea decorativa vertical -->
              <div class="relative">
                <div class="absolute left-2 top-1 bottom-1 w-px bg-slate-700/60 rounded-full" />

                <router-link
                  v-for="child in section.children"
                  :key="child.to"
                  :to="child.to"
                  class="flex items-center gap-2.5 pl-6 pr-3 py-2 rounded-lg text-sm
                         transition-all duration-150 ml-0"
                  :class="route.path.startsWith(child.to)
                    ? 'text-white font-bold bg-blue-600 shadow-md shadow-blue-600/20'
                    : 'text-slate-500 hover:text-slate-200 hover:bg-slate-800/40 font-medium'"
                >
                  <span
                    class="w-1.5 h-1.5 rounded-full shrink-0 transition-all"
                    :class="route.path.startsWith(child.to)
                      ? 'bg-white scale-125'
                      : 'bg-slate-700'"
                  />
                  {{ child.label }}
                </router-link>
              </div>
            </div>
          </div>

        </div>
      </template>

    </nav>

    <!-- ── FOOTER ──────────────────────────────────────── -->
    <div class="px-2 pb-4 border-t border-slate-800/60 pt-3">
      <button
        @click="toggleSidebar"
        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-medium
               text-slate-500 hover:bg-slate-800/70 hover:text-slate-300 transition-all"
        :title="!isOpen ? (isOpen ? 'Colapsar' : 'Expandir') : undefined"
        type="button"
      >
        <svg
          class="w-5 h-5 shrink-0 transition-transform duration-300"
          :class="isOpen ? 'rotate-0' : 'rotate-180'"
          viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        >
          <path d="M11 19l-7-7 7-7M19 12H4" />
        </svg>
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-x-1"
          enter-to-class="opacity-100 translate-x-0"
          leave-active-class="transition-none"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <span v-if="isOpen" class="text-xs">Colapsar menú</span>
        </Transition>
      </button>
    </div>
  </aside>
</template>
