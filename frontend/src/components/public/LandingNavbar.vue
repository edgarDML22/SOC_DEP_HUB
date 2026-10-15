<template>
  <header
    class="sticky top-0 z-50 backdrop-blur-md bg-[#0e1c3d]/95 border-b border-blue-400/20"
    role="banner"
  >
    <div class="max-w-7xl mx-auto px-6 md:px-12 h-14 md:h-16 flex items-center justify-between">
      <!-- Logo -->
      <RouterLink
        to="/"
        class="flex items-center gap-2.5 group focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 rounded-lg px-1 py-1"
        aria-label="Ir a inicio - Britania Club Deportivo"
      >
        <div class="relative flex items-center justify-center w-9 h-9 rounded-lg bg-[#131f38] border border-blue-400/40 shadow-[0_0_10px_rgba(59,130,246,0.25)] overflow-hidden">
          <span class="absolute inset-0 bg-gradient-to-tr from-blue-600/25 to-transparent" aria-hidden="true" />
          <img
            :src="logoSrc"
            alt="Logo Britania Club Deportivo"
            class="relative w-full h-full object-contain p-1"
            loading="eager"
            decoding="async"
            width="36"
            height="36"
          />
        </div>
        <span class="flex items-baseline text-lg md:text-xl font-bold tracking-tight leading-none">
          <span class="text-white">Britania</span>
          <span class="ml-1 text-[#e61c24]">Club</span>
        </span>
      </RouterLink>

      <!-- Desktop Navigation -->
      <nav class="hidden md:flex items-center gap-6" aria-label="Navegación principal">
        <a
          v-for="link in navLinks"
          :key="link.id"
          :href="link.href"
          class="relative text-xs font-semibold uppercase tracking-[0.05em] text-slate-300 hover:text-white transition-colors px-1 py-1.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60 rounded-md"
          :class="{ 'text-white': activeSection === link.id }"
          @click.prevent="handleNavClick(link)"
        >
          {{ link.label }}
          <span
            v-if="activeSection === link.id"
            class="absolute inset-x-0 -bottom-1 h-0.5 bg-blue-500 rounded-full shadow-[0_2px_8px_rgba(59,130,246,0.5)]"
          />
        </a>
      </nav>

      <!-- Desktop Actions -->
      <div class="hidden md:flex items-center gap-2.5">
        <RouterLink
          to="/torneos"
          class="inline-flex items-center gap-2 px-3.5 py-1.5 text-[11px] font-bold uppercase leading-tight tracking-[0.05em] text-blue-100 bg-blue-900/40 border border-blue-400/40 rounded-lg shadow-[0_0_15px_rgba(37,99,235,0.15)] transition-all duration-200 hover:border-blue-400 hover:bg-blue-800/50 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
          @click="emit('navigate', { to: '/torneos', type: 'button' })"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-blue-400 shadow-[0_0_6px_#60a5fa]" aria-hidden="true" />
          <span>Pre-Registro<br />Torneos</span>
        </RouterLink>
        <RouterLink
          to="/login"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 text-[11px] font-bold uppercase tracking-[0.05em] text-white bg-[#e61c24] rounded-lg shadow-[0_4px_20px_rgba(230,28,36,0.4)] transition-all duration-200 hover:bg-[#e61c24]/90 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e61c24]/60"
          @click="emit('navigate', { to: '/login', type: 'button' })"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="13"
            height="13"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <rect x="3" y="11" width="18" height="11" rx="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
          </svg>
          Iniciar Sesión
        </RouterLink>
      </div>

      <!-- Mobile Menu Button -->
      <button
        ref="menuButton"
        type="button"
        class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg border border-blue-400/30 bg-[#131f38] text-white transition-colors hover:border-blue-400/60 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
        :aria-expanded="isMenuOpen"
        aria-controls="mobile-menu"
        :aria-label="isMenuOpen ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'"
        @click="toggleMenu"
      >
        <span class="sr-only">{{ isMenuOpen ? 'Cerrar menú' : 'Abrir menú' }}</span>
        <svg
          v-if="!isMenuOpen"
          xmlns="http://www.w3.org/2000/svg"
          width="20"
          height="20"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <line x1="3" y1="6" x2="21" y2="6" />
          <line x1="3" y1="12" x2="21" y2="12" />
          <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          width="20"
          height="20"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <line x1="18" y1="6" x2="6" y2="18" />
          <line x1="6" y1="6" x2="18" y2="18" />
        </svg>
      </button>
    </div>

    <!-- Mobile Menu Panel -->
    <div
      v-if="isMenuOpen"
      id="mobile-menu"
      class="md:hidden absolute inset-x-0 top-full z-40 bg-[#0e1c3d]/98 backdrop-blur-xl border-b border-blue-400/20 shadow-lg transition-transform duration-300 ease-out"
      role="dialog"
      aria-modal="true"
      aria-label="Menú de navegación móvil"
    >
      <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col gap-6">
        <!-- Mobile Navigation Links -->
        <nav class="flex flex-col gap-2" aria-label="Navegación móvil">
          <a
            v-for="link in navLinks"
            :key="link.id + '-mobile'"
            :href="link.href"
            class="flex items-center justify-between px-3 py-3 text-base font-bold uppercase tracking-wide text-white/90 hover:text-white hover:bg-white/5 rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
            @click.prevent="handleMobileNavClick(link)"
          >
            {{ link.label }}
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <polyline points="9 18 15 12 9 6" />
            </svg>
          </a>
        </nav>

        <!-- Mobile Actions -->
        <div class="flex flex-col gap-3 pt-4 border-t border-blue-400/15">
          <RouterLink
            to="/torneos"
            class="inline-flex items-center justify-center gap-2 min-h-[44px] px-6 py-3 text-sm font-bold uppercase tracking-wide text-blue-100 bg-blue-900/40 border border-blue-400/40 rounded-lg transition-all duration-200 hover:bg-blue-800/50 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/60"
            @click="handleMobileButtonClick('/torneos')"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-blue-400 shadow-[0_0_6px_#60a5fa]" aria-hidden="true" />
            Pre-Registro Torneos
          </RouterLink>
          <RouterLink
            to="/login"
            class="inline-flex items-center justify-center min-h-[44px] px-6 py-3 text-sm font-bold uppercase tracking-wide text-white bg-[#e61c24] rounded-lg shadow-[0_4px_20px_rgba(230,28,36,0.4)] transition-all duration-200 hover:bg-[#e61c24]/90 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e61c24]/60"
            @click="handleMobileButtonClick('/login')"
          >
            Iniciar Sesión
          </RouterLink>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute } from 'vue-router'
import logoSrc from '@/assets/LogoSocDep.png'

interface NavLink {
  id: string
  label: string
  href: string
}

interface NavClickPayload {
  to?: string
  type?: string
}

const emit = defineEmits<{
  'menu-open': []
  'menu-close': []
  'navigate': [payload: NavClickPayload]
}>()

const route = useRoute()

const navLinks: NavLink[] = [
  { id: 'nosotros', label: 'Nosotros', href: '#nosotros' },
  { id: 'deportes', label: 'Deportes', href: '#deportes' },
  { id: 'beneficios', label: 'Beneficios', href: '#beneficios' },
  { id: 'torneos', label: 'Torneos', href: '#torneos' },
  { id: 'galeria', label: 'Galería', href: '#galeria' },
  { id: 'contacto', label: 'Contacto', href: '#contacto' }
]

const isMenuOpen = ref(false)
const activeSection = ref<string>('')
const menuButton = ref<HTMLButtonElement | null>(null)
const observer = ref<IntersectionObserver | null>(null)

const openMenu = () => {
  if (isMenuOpen.value) return
  isMenuOpen.value = true
  emit('menu-open')
  document.body.style.overflow = 'hidden'
}

const closeMenu = () => {
  if (!isMenuOpen.value) return
  isMenuOpen.value = false
  emit('menu-close')
  document.body.style.overflow = ''
}

const toggleMenu = () => {
  isMenuOpen.value ? closeMenu() : openMenu()
}

const handleNavClick = (link: NavLink) => {
  activeSection.value = link.id
  emit('navigate', { to: link.href, type: 'anchor' })
}

const handleMobileNavClick = (link: NavLink) => {
  activeSection.value = link.id
  emit('navigate', { to: link.href, type: 'anchor' })
  closeMenu()
}

const handleMobileButtonClick = (to: string) => {
  emit('navigate', { to, type: 'button' })
  closeMenu()
}

const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && isMenuOpen.value) {
    closeMenu()
    menuButton.value?.focus()
  }
}

const setupIntersectionObserver = () => {
  if (observer.value) {
    observer.value.disconnect()
  }

  const sections = navLinks
    .map((link) => document.getElementById(link.id))
    .filter((el): el is HTMLElement => el !== null)

  if (sections.length === 0) {
    observer.value = null
    return
  }

  const rootMargin = '-40% 0px -55% 0px'

  observer.value = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const id = entry.target.id
          if (id) {
            activeSection.value = id
          }
        }
      })
    },
    { root: null, rootMargin, threshold: [0, 0.1, 0.25, 0.5] }
  )

  sections.forEach((section) => {
    observer.value?.observe(section)
  })
}

watch(
  () => route.fullPath,
  () => {
    closeMenu()
  }
)

onMounted(() => {
  document.addEventListener('keydown', handleKeyDown)
  requestAnimationFrame(() => {
    setupIntersectionObserver()
  })
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 768 && isMenuOpen.value) {
      closeMenu()
    }
  })
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeyDown)
  observer.value?.disconnect()
  observer.value = null
  document.body.style.overflow = ''
})
</script>



