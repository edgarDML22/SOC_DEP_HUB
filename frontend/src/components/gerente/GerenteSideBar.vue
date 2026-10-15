<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  IconHome,
  IconCalendar,
  IconUser,
  IconGuests,
  IconShield,
  IconClock,
  IconBaby,
  IconTrophy,
  IconTarget,
  IconBriefcase,
  IconLayers,
  IconGrid
} from '@/components/icons'
import { useAdminLudotecaStore } from '@/stores/ludoteca/adminLudotecaStore'
import { useAdminStore } from '@/stores/profiles/adminStore'

const props = defineProps({
  isMobileOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:isMobileOpen', 'close'])

const ludotecaStore = useAdminLudotecaStore()
const adminStore = useAdminStore()
const route = useRoute()
const isOpen = ref(true)
const openSection = ref(null)

const userName = computed(() => adminStore.fullName || 'Administrador')
const userEmail = computed(() => adminStore.email || 'admin@socdephub.com')
const userInitials = computed(() => {
  if (adminStore.userInitials && adminStore.userInitials !== '?') {
    return adminStore.userInitials
  }
  if (adminStore.fullName) {
    const parts = adminStore.fullName.trim().split(/\s+/)
    if (parts.length >= 2) return `${parts[0][0]}${parts[1][0]}`.toUpperCase()
    if (parts.length === 1 && parts[0].length > 0) return parts[0][0].toUpperCase()
  }
  return 'AD'
})
const userAvatar = computed(() => {
  return adminStore.profileData?.foto_perfil || 
         adminStore.profileData?.avatar || 
         adminStore.profileData?.foto || 
         adminStore.profileData?.profile_photo_url || 
         null
})

onMounted(() => {
  if (!adminStore.profileData) {
    adminStore.fetchProfile()
  }
})

const toggleSidebar = () => {
  isOpen.value = !isOpen.value
  if (!isOpen.value) openSection.value = null
}

const toggleSection = (key) => {
  openSection.value = openSection.value === key ? null : key
}

const closeMobileSidebar = () => {
  emit('update:isMobileOpen', false)
  emit('close')
}

watch(
  () => route.path,
  () => {
    if (props.isMobileOpen) {
      closeMobileSidebar()
    }
  }
)

const setLudotecaView = (view, forceStats = true) => {
  if (forceStats) {
    ludotecaStore.mainTab = 'stats'
  } else {
    ludotecaStore.mainTab = 'register'
  }
  ludotecaStore.viewActive = view
  if (props.isMobileOpen) {
    closeMobileSidebar()
  }
}

// Verifica si la ruta activa coincide con alguno de los paths de la sección
const isRouteUnder = (paths) => paths.some(p => route.path.startsWith(p))

// Geometría y curvas SVG del menú ramificado
const PAD = 6
const ROW_HEIGHT = 40
const INDENT = 44
const TRUNK = 22
const RADIUS = 10
const r = Math.min(RADIUS, ROW_HEIGHT / 2 - 2)
const endX = INDENT - 8

// Sub-rama anidada para opciones de segundo nivel (ej. Asignar Turnos)
const SUB_TRUNK = INDENT + 8
const SUB_INDENT = INDENT + 28
const subEndX = SUB_INDENT - 8

const getTreeHeight = (count) => PAD * 2 + count * ROW_HEIGHT
const rowY = (k) => PAD + k * ROW_HEIGHT + ROW_HEIGHT / 2
const trunkPath = (count) => `M ${TRUNK} 0 V ${rowY(count - 1) - r}`

const branchPath = (k, child) => {
  if (child?.isSubChild) {
    const startY = rowY(k - 1) + 14
    return `M ${SUB_TRUNK} ${startY} V ${rowY(k) - r} A ${r} ${r} 0 0 0 ${SUB_TRUNK + r} ${rowY(k)} H ${subEndX}`
  }
  return `M ${TRUNK} ${rowY(k) - r} A ${r} ${r} 0 0 0 ${TRUNK + r} ${rowY(k)} H ${endX}`
}

const reachPath = (k, child) => {
  if (child?.isSubChild) {
    const startY = rowY(k - 1) + 14
    return `M ${TRUNK} 0 V ${rowY(k - 1) - r} A ${r} ${r} 0 0 0 ${TRUNK + r} ${rowY(k - 1)} H ${endX} M ${SUB_TRUNK} ${startY} V ${rowY(k) - r} A ${r} ${r} 0 0 0 ${SUB_TRUNK + r} ${rowY(k)} H ${subEndX}`
  }
  return `M ${TRUNK} 0 V ${rowY(k) - r} A ${r} ${r} 0 0 0 ${TRUNK + r} ${rowY(k)} H ${endX}`
}

const reachLength = (k, child) => {
  if (child?.isSubChild) {
    const startY = rowY(k - 1) + 14
    const seg1 = (rowY(k - 1) - r) + (Math.PI * r) / 2 + (endX - TRUNK - r)
    const seg2 = ((rowY(k) - r) - startY) + (Math.PI * r) / 2 + (subEndX - SUB_TRUNK - r)
    return seg1 + seg2
  }
  return (rowY(k) - r) + (Math.PI * r) / 2 + (endX - TRUNK - r)
}

const isSectionOpen = (section) => openSection.value === section.key

// 'Asignar Turnos' solo se muestra cuando la ruta activa corresponde al módulo de Ludoteca
const isLudotecaContextActive = computed(() => {
  return route.path.startsWith('/admin/ludoteca')
})

const getVisibleChildren = (section) => {
  if (!section?.children) return []
  return section.children.filter(child => !child.isSubChild || isLudotecaContextActive.value)
}

const isChildActive = (child) => {
  if (child.to === '/admin/ludoteca') {
    return route.path === '/admin/ludoteca' || (route.path.startsWith('/admin/ludoteca') && route.path !== '/admin/ludoteca/turnos')
  }
  return route.path.startsWith(child.to)
}

const hoveredChildKey = ref(null)

const setHoveredChild = (sectionKey, childIndex) => {
  hoveredChildKey.value = `${sectionKey}-${childIndex}`
}

const clearHoveredChild = () => {
  hoveredChildKey.value = null
}

const isChildHovered = (sectionKey, childIndex) => {
  return hoveredChildKey.value === `${sectionKey}-${childIndex}`
}

const getChildComponentType = () => 'router-link'

const handleChildClick = (child) => {
  if (child.label === 'Ludoteca') {
    setLudotecaView('dashboard', false)
  }
  if (props.isMobileOpen) {
    closeMobileSidebar()
  }
}

const sections = [
  {
    key: 'operacion',
    label: 'Operación',
    icon: IconCalendar,
    paths: ['/admin/reservations', '/admin/ludoteca', '/admin/tournaments', '/admin/activities'],
    children: [
      { label: 'Reservas On-Demand', to: '/admin/reservations', icon: IconClock },
      { label: 'Ludoteca', to: '/admin/ludoteca', icon: IconBaby },
      { label: 'Asignar Turnos', to: '/admin/ludoteca/turnos', icon: IconCalendar, isSubChild: true, parentKey: 'ludoteca' },
      { label: 'Torneos', to: '/admin/tournaments', icon: IconTrophy },
      { label: 'Actividades', to: '/admin/activities', icon: IconTarget }
    ],
  },
  {
    key: 'usuarios',
    label: 'Usuarios',
    icon: IconUser,
    paths: ['/admin/socios', '/admin/instructors', '/admin/managers'],
    children: [
      { label: 'Socios', to: '/admin/socios', icon: IconGuests },
      { label: 'Instructores', to: '/admin/instructors', icon: IconBriefcase },
      { label: 'Gerentes', to: '/admin/managers', icon: IconShield }
    ],
  },
  {
    key: 'club',
    label: 'Config. del Club',
    icon: IconShield,
    paths: ['/admin/spaces', '/admin/disciplines'],
    children: [
      { label: 'Espacios', to: '/admin/spaces/list', icon: IconLayers },
      { label: 'Disciplinas', to: '/admin/disciplines/list', icon: IconGrid },
    ],
  },
]
</script>

<template>
  <!-- Sidebar de escritorio -->
  <aside
    class="hidden lg:flex h-full bg-slate-950 text-slate-200 flex-col font-sans
           transition-all duration-300 ease-in-out shrink-0 z-30"
    :class="isOpen ? 'w-64' : 'w-[72px]'"
  >
    <div class="flex items-center gap-3 px-4 py-5 border-b border-slate-800/60 h-[76px]">
      <button
        @click="toggleSidebar"
        class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0
               text-slate-200 hover:bg-slate-800/80 hover:text-white transition-colors cursor-pointer"
        aria-label="Alternar menú"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 -translate-x-2"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 -translate-x-2"
      >
        <div v-if="isOpen" class="flex items-center gap-2 overflow-hidden">
          <img src="@/assets/LogoSocDep.jpg" class="w-8 h-8 rounded-lg object-cover shrink-0" alt="SOC-DEP" />
          <span class="text-sm font-black text-white tracking-tight whitespace-nowrap">
            SOC-DEP HUB
          </span>
        </div>
      </Transition>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-2 flex flex-col gap-0.5">
      <router-link
        to="/admin/dashboard"
        class="flex items-center py-2.5 rounded-xl text-sm font-medium
               transition-all duration-200 group"
        :class="[
          isOpen ? 'gap-3 px-3 justify-start' : 'justify-center',
          route.path === '/admin/dashboard'
            ? 'bg-slate-800/80 text-white font-semibold shadow-xs'
            : 'text-slate-100 hover:bg-slate-800/50 hover:text-white'
        ]"
        :title="!isOpen ? 'Dashboard' : undefined"
      >
        <IconHome class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" />
        <span v-if="isOpen" class="truncate">Dashboard</span>
      </router-link>

      <router-link
        to="/admin/reports"
        class="flex items-center py-2.5 rounded-xl text-sm font-medium
               transition-all duration-200 group"
        :class="[
          isOpen ? 'gap-3 px-3 justify-start' : 'justify-center',
          route.path.startsWith('/admin/reports')
            ? 'bg-slate-800/80 text-white font-semibold shadow-xs'
            : 'text-slate-100 hover:bg-slate-800/50 hover:text-white'
        ]"
        :title="!isOpen ? 'Reportes' : undefined"
      >
        <IconGuests class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" />
        <span v-if="isOpen" class="truncate">Reportes</span>
      </router-link>

      <div class="my-2 border-t border-slate-800/60" />

      <div v-for="section in sections" :key="section.key" class="flex flex-col">
        <button
          @click="toggleSection(section.key)"
          class="flex items-center py-2.5 rounded-xl text-sm font-medium w-full
                 transition-all duration-200 group cursor-pointer relative"
          :class="[
            isOpen ? 'gap-3 px-3 justify-start' : 'justify-center',
            isRouteUnder(section.paths)
              ? 'bg-slate-800/80 text-white font-semibold shadow-xs'
              : openSection === section.key
                ? 'bg-slate-800/60 text-white'
                : 'text-slate-100 hover:bg-slate-800/50 hover:text-white',
          ]"
          :title="!isOpen ? section.label : undefined"
          type="button"
        >
          <span
            v-if="isOpen && isRouteUnder(section.paths)"
            class="absolute left-1 w-0.5 h-4 rounded-full transition-all duration-300"
            style="background-color: #0667e2;"
          />

          <component
            :is="section.icon"
            class="w-5 h-5 shrink-0 transition-all duration-300 group-hover:scale-110"
            :class="isRouteUnder(section.paths) ? 'text-white' : openSection === section.key ? 'text-white' : 'text-slate-100 group-hover:text-white'"
          />

          <span v-if="isOpen" class="flex-1 text-left truncate">{{ section.label }}</span>

          <svg
            v-if="isOpen"
            class="w-3.5 h-3.5 shrink-0 transition-transform duration-300"
            :class="[
              openSection === section.key ? 'rotate-180' : '',
              isRouteUnder(section.paths) ? 'text-white' : openSection === section.key ? 'text-white' : 'text-slate-200 group-hover:text-white'
            ]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
          >
            <path d="M6 9l6 6 6-6" />
          </svg>
        </button>

        <div 
          v-if="isOpen"
          class="grid transition-[grid-template-rows] duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]"
          :class="isSectionOpen(section) ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
        >
          <div class="min-h-0 overflow-hidden">
            <div 
              class="relative box-border py-[6px] transition-[height] duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]" 
              :style="{ height: `${getTreeHeight(getVisibleChildren(section).length)}px` }"
            >
              <svg 
                class="absolute top-0 left-0 overflow-visible pointer-events-none transition-opacity duration-250"
                :class="isSectionOpen(section) ? 'opacity-100 delay-75' : 'opacity-0'"
                :width="SUB_INDENT" 
                :height="getTreeHeight(getVisibleChildren(section).length)" 
                aria-hidden="true"
              >
                <path 
                  class="fill-none stroke-slate-600" 
                  stroke-width="1.5" 
                  stroke-linecap="round" 
                  stroke-linejoin="round"
                  :d="trunkPath(getVisibleChildren(section).length)" 
                />
                <path 
                  v-for="(child, k) in getVisibleChildren(section)" 
                  :key="'branch-desk-' + (child.to || k)"
                  class="fill-none stroke-slate-600" 
                  stroke-width="1.5" 
                  stroke-linecap="round" 
                  stroke-linejoin="round"
                  :d="branchPath(k, child)" 
                />
                <path 
                  v-for="(child, k) in getVisibleChildren(section)" 
                  :key="'reach-hover-desk-' + (child.to || k)"
                  class="fill-none stroke-white transition-[stroke-dashoffset] duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]" 
                  stroke-width="1.65" 
                  stroke-linecap="round" 
                  stroke-linejoin="round"
                  :d="reachPath(k, child)"
                  :style="{
                    strokeDasharray: reachLength(k, child),
                    strokeDashoffset: (isChildHovered(section.key || section.id || section.label, k) && !isChildActive(child)) ? 0 : reachLength(k, child)
                  }"
                />
                <path 
                  v-for="(child, k) in getVisibleChildren(section)" 
                  :key="'reach-desk-' + (child.to || k)"
                  class="fill-none transition-[stroke-dashoffset] duration-400 ease-[cubic-bezier(0.23,1,0.32,1)]" 
                  stroke="#0667e2"
                  stroke-width="2" 
                  stroke-linecap="round" 
                  stroke-linejoin="round"
                  :d="reachPath(k, child)"
                  :style="{
                    stroke: '#0667e2',
                    strokeDasharray: reachLength(k, child),
                    strokeDashoffset: isChildActive(child) ? 0 : reachLength(k, child)
                  }"
                />
              </svg>

              <component
                v-for="(child, k) in getVisibleChildren(section)"
                :key="child.to || child.label || k"
                :is="getChildComponentType(child)"
                :to="child.to"
                @click="handleChildClick(child)"
                @mouseenter="setHoveredChild(section.key || section.id || section.label, k)"
                @mouseleave="clearHoveredChild()"
                @focus="setHoveredChild(section.key || section.id || section.label, k)"
                @blur="clearHoveredChild()"
                :tabindex="isSectionOpen(section) ? 0 : -1"
                class="flex items-center gap-2.5 w-full h-[40px] pr-3 text-left text-sm transition-all duration-200 select-none group rounded-lg"
                :style="{ paddingLeft: `${child.isSubChild ? SUB_INDENT : INDENT}px`, ...(isChildActive(child) ? { color: '#0667e2' } : {}) }"
                :class="isChildActive(child) ? 'text-[#0667e2] font-semibold' : 'text-slate-200 hover:text-white font-medium'"
              >
                <span 
                  class="inline-flex shrink-0 transition-colors duration-200 relative z-10"
                  :style="isChildActive(child) ? { color: '#0667e2' } : {}"
                  :class="isChildActive(child) ? 'text-[#0667e2]' : 'text-slate-200 group-hover:text-white'"
                >
                  <component :is="child.icon" class="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" :class="isChildActive(child) ? 'scale-110' : ''" />
                </span>
                <span class="truncate">{{ child.label || child.name }}</span>
              </component>
            </div>
          </div>
        </div>

      </div>

    </nav>

    <div class="px-2 py-4 border-t border-slate-800/60 mt-auto overflow-hidden">
      <div v-if="isOpen" class="px-2 flex flex-col gap-1 transition-all duration-300">
        <div class="flex items-center gap-2 text-slate-500 text-[10px] font-bold tracking-widest uppercase">
          <span class="text-[#0667e2] text-xs">©</span>
          <span>2026 SOCDEPHUB</span>
        </div>
        <p class="text-slate-600 text-[9px] leading-tight pl-5">
          Todos Los Derechos Reservados.
        </p>
      </div>
      <div
        v-else
        class="flex justify-center text-slate-500 font-bold text-lg hover:text-[#0667e2] transition-colors cursor-default"
        title="© 2026 SOCDEPHUB"
      >
        ©
      </div>
    </div>
  </aside>

  <!-- Drawer móvil -->
  <Transition
    enter-active-class="transition-opacity duration-300 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-opacity duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div 
      v-if="isMobileOpen" 
      @click="closeMobileSidebar" 
      class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden" 
      aria-hidden="true"
    />
  </Transition>

  <aside 
    :class="[
      'fixed top-0 bottom-0 left-0 z-50 w-[80vw] max-w-2xl bg-slate-900 text-white transform transition-transform duration-300 ease-in-out lg:hidden flex flex-col shadow-2xl font-sans',
      isMobileOpen ? 'translate-x-0' : '-translate-x-full'
    ]"
    aria-label="Menú de navegación móvil"
  >
    <div class="flex items-center justify-between px-5 sm:px-8 py-5 border-b border-slate-800/60 h-20 sm:h-22 shrink-0">
      <div class="flex items-center gap-3 overflow-hidden">
        <img src="@/assets/LogoSocDep.jpg" class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl object-cover shrink-0 shadow-sm" alt="SOC-DEP" />
        <span class="text-base sm:text-xl font-black text-white tracking-tight whitespace-nowrap">
          SOC-DEP HUB
        </span>
      </div>

      <button
        type="button"
        @click="closeMobileSidebar"
        class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl flex items-center justify-center text-slate-200 hover:bg-slate-800/80 hover:text-white transition-colors cursor-pointer"
        aria-label="Cerrar menú"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="px-6 sm:px-8 pt-6 pb-3 flex flex-col items-start text-left shrink-0">
      <img
        v-if="userAvatar"
        :src="userAvatar"
        alt="Perfil"
        class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover shadow-md ring-2 ring-white/10 mb-4"
      />
      <div
        v-else
        class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-2xl sm:text-3xl font-bold shadow-md mb-4"
        style="color: #0667e2;"
      >
        {{ userInitials }}
      </div>

      <h3 class="text-xl sm:text-2xl font-bold text-white tracking-tight leading-snug line-clamp-1 w-full">
        {{ userName }}
      </h3>

      <p class="text-sm sm:text-base font-normal text-slate-200 mt-1 truncate w-full">
        {{ userEmail }}
      </p>

      <div class="w-full h-px bg-slate-800 mt-6"></div>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-3 sm:px-5 flex flex-col gap-1.5">
      <router-link
        to="/admin/dashboard"
        class="flex items-center gap-3.5 px-4 py-3 sm:py-3.5 rounded-xl text-base sm:text-lg font-medium transition-all duration-200 group justify-start"
        :class="route.path === '/admin/dashboard'
          ? 'bg-slate-800/80 text-white font-semibold shadow-xs'
          : 'text-slate-100 hover:bg-slate-800/50 hover:text-white'"
        @click="closeMobileSidebar"
      >
        <IconHome class="w-5 h-5 sm:w-6 sm:h-6 shrink-0 transition-transform group-hover:scale-110" />
        <span class="truncate">Dashboard</span>
      </router-link>

      <router-link
        to="/admin/reports"
        class="flex items-center gap-3.5 px-4 py-3 sm:py-3.5 rounded-xl text-base sm:text-lg font-medium transition-all duration-200 group justify-start"
        :class="route.path.startsWith('/admin/reports')
          ? 'bg-slate-800/80 text-white font-semibold shadow-xs'
          : 'text-slate-100 hover:bg-slate-800/50 hover:text-white'"
        @click="closeMobileSidebar"
      >
        <IconGuests class="w-5 h-5 sm:w-6 sm:h-6 shrink-0 transition-transform group-hover:scale-110" />
        <span class="truncate">Reportes</span>
      </router-link>

      <div class="my-2 border-t border-slate-800/60" />

      <div v-for="section in sections" :key="'mob-' + section.key" class="flex flex-col">
        <button
          @click="toggleSection(section.key)"
          class="flex items-center gap-3.5 px-3 py-3 sm:py-3.5 rounded-xl text-base sm:text-lg font-medium w-full transition-all duration-200 group justify-start cursor-pointer text-left relative"
          :class="[
            isRouteUnder(section.paths)
              ? 'bg-slate-800/80 text-white font-semibold shadow-xs'
              : openSection === section.key
                ? 'bg-slate-800/60 text-white'
                : 'text-slate-100 hover:bg-slate-800/50 hover:text-white',
          ]"
          type="button"
        >
          <span
            v-if="isRouteUnder(section.paths)"
            class="absolute left-1 w-0.5 h-5 rounded-full transition-all duration-300"
            style="background-color: #0667e2;"
          />

          <component
            :is="section.icon"
            class="w-5 h-5 sm:w-6 sm:h-6 shrink-0 transition-all duration-300 group-hover:scale-110"
            :class="isRouteUnder(section.paths) ? 'text-white' : openSection === section.key ? 'text-white' : 'text-slate-100 group-hover:text-white'"
          />

          <span class="flex-1 truncate">{{ section.label }}</span>

          <svg
            class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 transition-transform duration-300"
            :class="[
              openSection === section.key ? 'rotate-180' : '',
              isRouteUnder(section.paths) ? 'text-white' : openSection === section.key ? 'text-white' : 'text-slate-200 group-hover:text-white'
            ]"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
          >
            <path d="M6 9l6 6 6-6" />
          </svg>
        </button>

        <div 
          class="grid transition-[grid-template-rows] duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]"
          :class="isSectionOpen(section) ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
        >
          <div class="min-h-0 overflow-hidden">
            <div 
              class="relative box-border py-[6px] transition-[height] duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]" 
              :style="{ height: `${getTreeHeight(getVisibleChildren(section).length)}px` }"
            >
              <svg 
                class="absolute top-0 left-0 overflow-visible pointer-events-none transition-opacity duration-250"
                :class="isSectionOpen(section) ? 'opacity-100 delay-75' : 'opacity-0'"
                :width="SUB_INDENT" 
                :height="getTreeHeight(getVisibleChildren(section).length)" 
                aria-hidden="true"
              >
                <path 
                  class="fill-none stroke-slate-600" 
                  stroke-width="1.5" 
                  stroke-linecap="round" 
                  stroke-linejoin="round"
                  :d="trunkPath(getVisibleChildren(section).length)" 
                />
                <path 
                  v-for="(child, k) in getVisibleChildren(section)" 
                  :key="'branch-mob-' + (child.to || k)"
                  class="fill-none stroke-slate-600" 
                  stroke-width="1.5" 
                  stroke-linecap="round" 
                  stroke-linejoin="round"
                  :d="branchPath(k, child)" 
                />
                <path 
                  v-for="(child, k) in getVisibleChildren(section)" 
                  :key="'reach-hover-mob-' + (child.to || k)"
                  class="fill-none stroke-white transition-[stroke-dashoffset] duration-300 ease-[cubic-bezier(0.23,1,0.32,1)]" 
                  stroke-width="1.65" 
                  stroke-linecap="round" 
                  stroke-linejoin="round"
                  :d="reachPath(k, child)"
                  :style="{
                    strokeDasharray: reachLength(k, child),
                    strokeDashoffset: (isChildHovered(section.key || section.id || section.label, k) && !isChildActive(child)) ? 0 : reachLength(k, child)
                  }"
                />
                <path 
                  v-for="(child, k) in getVisibleChildren(section)" 
                  :key="'reach-mob-' + (child.to || k)"
                  class="fill-none transition-[stroke-dashoffset] duration-400 ease-[cubic-bezier(0.23,1,0.32,1)]" 
                  stroke="#0667e2"
                  stroke-width="2" 
                  stroke-linecap="round" 
                  stroke-linejoin="round"
                  :d="reachPath(k, child)"
                  :style="{
                    stroke: '#0667e2',
                    strokeDasharray: reachLength(k, child),
                    strokeDashoffset: isChildActive(child) ? 0 : reachLength(k, child)
                  }"
                />
              </svg>

              <component
                v-for="(child, k) in getVisibleChildren(section)"
                :key="child.to || child.label || k"
                :is="getChildComponentType(child)"
                :to="child.to"
                @click="handleChildClick(child)"
                @mouseenter="setHoveredChild(section.key || section.id || section.label, k)"
                @mouseleave="clearHoveredChild()"
                @focus="setHoveredChild(section.key || section.id || section.label, k)"
                @blur="clearHoveredChild()"
                :tabindex="isSectionOpen(section) ? 0 : -1"
                class="flex items-center gap-3 w-full h-[40px] pr-3 text-left text-sm sm:text-base transition-all duration-200 select-none group rounded-lg"
                :style="{ paddingLeft: `${child.isSubChild ? SUB_INDENT : INDENT}px`, ...(isChildActive(child) ? { color: '#0667e2' } : {}) }"
                :class="isChildActive(child) ? 'text-[#0667e2] font-semibold' : 'text-slate-200 hover:text-white font-medium'"
              >
                <span 
                  class="inline-flex shrink-0 transition-colors duration-200 relative z-10"
                  :style="isChildActive(child) ? { color: '#0667e2' } : {}"
                  :class="isChildActive(child) ? 'text-[#0667e2]' : 'text-slate-200 group-hover:text-white'"
                >
                  <component :is="child.icon" class="w-4.5 h-4.5 sm:w-5 sm:h-5 shrink-0 transition-transform group-hover:scale-110" :class="isChildActive(child) ? 'scale-110' : ''" />
                </span>
                <span class="truncate">{{ child.label || child.name }}</span>
              </component>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <div class="px-6 sm:px-8 py-5 border-t border-slate-800/60 mt-auto overflow-hidden shrink-0">
      <div class="flex flex-col gap-1">
        <div class="flex items-center gap-2 text-slate-500 text-xs sm:text-sm font-bold tracking-widest uppercase">
          <span class="text-[#0667e2] text-sm">©</span>
          <span>2026 SOCDEPHUB</span>
        </div>
        <p class="text-slate-600 text-[10px] sm:text-xs leading-tight pl-5">
          Todos Los Derechos Reservados.
        </p>
      </div>
    </div>
  </aside>
</template>
