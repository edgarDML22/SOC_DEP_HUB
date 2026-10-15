<template>
  <div class="min-h-screen bg-[#0b1326] overflow-x-hidden">
    <LandingNavbar @navigate="handleNavigate" />
    <main id="main-content">
      <!-- Hero Slider (primera sección visible) -->
      <LandingHero />

      <!-- Sobre el Club -->
      <LandingAbout />

      <!-- Deportes / Instalaciones -->
      <LandingCarousel />

      <!-- Beneficios Exclusivos -->
      <LandingBenefits />

      <!-- Torneos destacados + pre-registro -->
      <LandingTournaments />

      <!-- Galería -->
      <LandingGallery />

      <!-- Contacto / Concierge -->
      <LandingContact />
    </main>
    <LandingFooter />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import LandingNavbar from '@/components/public/LandingNavbar.vue'
import LandingHero from '@/components/public/LandingHero.vue'
import LandingAbout from '@/components/public/landing/LandingAbout.vue'
import LandingCarousel from '@/components/public/landing/LandingCarousel.vue'
import LandingBenefits from '@/components/public/landing/LandingBenefits.vue'
import LandingTournaments from '@/components/public/landing/LandingTournaments.vue'
import LandingGallery from '@/components/public/landing/LandingGallery.vue'
import LandingContact from '@/components/public/landing/LandingContact.vue'
import LandingFooter from '@/components/public/LandingFooter.vue'

interface NavClickPayload {
  to?: string
  type?: string
}

const route = useRoute()

const scrollToHash = (hash: string) => {
  if (!hash) return
  const elementId = hash.replace('#', '')
  if (!elementId) return
  const element = document.getElementById(elementId)
  if (element) {
    window.setTimeout(() => {
      element.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }, 50)
  }
}

const handleNavigate = (payload: NavClickPayload) => {
  if (payload?.type === 'anchor' && payload.to?.startsWith('#')) {
    scrollToHash(payload.to)
  }
}

onMounted(() => {
  if (route.hash) {
    scrollToHash(route.hash)
  }
})
</script>

<style scoped>
section[id] {
  scroll-margin-top: 5rem;
}
</style>
