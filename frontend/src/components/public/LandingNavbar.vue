<template>
  <header
    class="sticky top-0 z-50 border-b transition-[background-color,box-shadow,border-color] duration-300"
    :class="
      isScrolled || isMenuOpen
        ? 'border-lp-line bg-lp-paper/90 shadow-[0_8px_30px_-18px_rgba(30,64,175,0.35)] backdrop-blur-lg'
        : 'border-transparent bg-lp-paper/60 backdrop-blur-md'
    "
  >
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-12">
      <LandingLogo @click="handleLogoClick" />

      <!-- Navegación de escritorio -->
      <nav class="hidden items-center gap-1 lg:flex" aria-label="Navegación principal">
        <a
          v-for="link in navLinks"
          :key="link.id"
          :href="link.href"
          class="lp-body rounded-full px-3 py-2 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-100"
          :class="
            activeSection === link.id
              ? 'bg-primary-50 text-primary-700'
              : 'text-lp-body hover:bg-primary-50/60 hover:text-primary-700'
          "
          :aria-current="activeSection === link.id ? 'location' : undefined"
          @click.prevent="handleNavClick(link)"
        >
          {{ link.label }}
        </a>
      </nav>

      <!-- Acciones de escritorio -->
      <div class="hidden items-center gap-2 lg:flex">
        <LandingButton to="/torneos" variant="outline" size="sm" class="hidden xl:inline-flex">
          <IconTrophy class="h-4 w-4" aria-hidden="true" />
          Pre-Registro Torneos
        </LandingButton>
        <LandingButton to="/login" size="sm">
          <IconLock class="h-4 w-4" aria-hidden="true" />
          Iniciar Sesión
        </LandingButton>
      </div>

      <!-- Botón menú móvil -->
      <button
        ref="menuButton"
        type="button"
        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-primary-50 text-primary-700 ring-1 ring-primary-100 transition-colors hover:bg-primary-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-200 lg:hidden"
        :aria-expanded="isMenuOpen"
        aria-controls="mobile-menu"
        :aria-label="isMenuOpen ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'"
        @click="toggleMenu"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
          <template v-if="!isMenuOpen">
            <line x1="4" y1="7" x2="20" y2="7" />
            <line x1="4" y1="12" x2="20" y2="12" />
            <line x1="4" y1="17" x2="14" y2="17" />
          </template>
          <template v-else>
            <line x1="18" y1="6" x2="6" y2="18" />
            <line x1="6" y1="6" x2="18" y2="18" />
          </template>
        </svg>
      </button>
    </div>

    <!-- Menú móvil -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 -translate-y-2"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div v-if="isMenuOpen" id="mobile-menu" class="absolute inset-x-0 top-full lg:hidden">
        <div class="max-h-[calc(100dvh-4rem)] overflow-y-auto rounded-b-3xl border-b border-lp-line bg-lp-paper px-4 pb-6 pt-3 shadow-lp-lift sm:px-6">
          <nav class="flex flex-col gap-1" aria-label="Navegación móvil">
            <a
              v-for="link in navLinks"
              :key="link.id + '-mobile'"
              :href="link.href"
              class="lp-body flex items-center justify-between rounded-2xl px-4 py-3.5 text-base font-semibold transition-colors focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-100"
              :class="activeSection === link.id ? 'bg-primary-50 text-primary-700' : 'text-lp-ink hover:bg-primary-50/60'"
              @click.prevent="handleMobileNavClick(link)"
            >
              {{ link.label }}
              <IconChevronRight class="h-4 w-4 text-primary-400" aria-hidden="true" />
            </a>
          </nav>

          <div class="mt-4 flex flex-col gap-2.5 border-t border-lp-line pt-5">
            <LandingButton to="/torneos" variant="outline" block @click="closeMenu">
              <IconTrophy class="h-4 w-4" aria-hidden="true" />
              Pre-Registro Torneos
            </LandingButton>
            <LandingButton to="/login" block @click="closeMenu">
              <IconLock class="h-4 w-4" aria-hidden="true" />
              Iniciar Sesión
            </LandingButton>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Fondo del menú móvil: va en <body> porque el backdrop-blur del header
         atraparía a un elemento fixed dentro de él -->
    <Teleport to="body">
      <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0" leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
        <div v-if="isMenuOpen" class="fixed inset-0 z-40 bg-primary-900/25 backdrop-blur-[2px] lg:hidden" aria-hidden="true" @click="closeMenu" />
      </Transition>
    </Teleport>
  </header>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute } from 'vue-router'
import { IconTrophy, IconLock, IconChevronRight } from '@/components/icons'
import LandingLogo from '@/components/public/landing/ui/LandingLogo.vue'
import LandingButton from '@/components/public/landing/ui/LandingButton.vue'

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
const isScrolled = ref(false)
const activeSection = ref<string>('')
const menuButton = ref<HTMLButtonElement | null>(null)
let observer: IntersectionObserver | null = null

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
  closeMenu()
  handleNavClick(link)
}

// El logo apunta a "/" y la ruta no cambia estando en la landing,
// así que cerramos el menú y subimos al inicio manualmente
const handleLogoClick = () => {
  closeMenu()
  activeSection.value = ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && isMenuOpen.value) {
    closeMenu()
    menuButton.value?.focus()
  }
}

const handleScroll = () => {
  isScrolled.value = window.scrollY > 8
}

const handleResize = () => {
  if (window.innerWidth >= 1024) closeMenu()
}

const setupIntersectionObserver = () => {
  // Se observa también el hero para limpiar el enlace activo al volver arriba
  const sections = ['inicio', ...navLinks.map((link) => link.id)]
    .map((id) => document.getElementById(id))
    .filter((el): el is HTMLElement => el !== null)

  if (sections.length === 0) return

  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) activeSection.value = entry.target.id === 'inicio' ? '' : entry.target.id
      })
    },
    { rootMargin: '-40% 0px -55% 0px' }
  )
  sections.forEach((section) => observer?.observe(section))
}

watch(() => route.fullPath, closeMenu)

onMounted(() => {
  handleScroll()
  document.addEventListener('keydown', handleKeyDown)
  window.addEventListener('scroll', handleScroll, { passive: true })
  window.addEventListener('resize', handleResize)
  requestAnimationFrame(setupIntersectionObserver)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeyDown)
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('resize', handleResize)
  observer?.disconnect()
  observer = null
  document.body.style.overflow = ''
})
</script>
