<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useProfileStore } from '@/stores/profiles/socioStore'
import { useNotificacionesStore } from '@/stores/profiles/notificacionesStore'
import { IconHome, IconCalendar, IconTrophy, IconGuests, IconClock, IconUser, IconBell, IconQr } from '@/components/icons';

const profileStore       = useProfileStore()
const notifStore         = useNotificacionesStore()
const router             = useRouter()

const menuOpen           = ref(false)
const showNotifications  = ref(false)
const notifSeleccionada  = ref(null)   // notificación abierta en el modal de detalle

const profileDropdown       = ref(null)
const notifDropdownDesktop  = ref(null)
const notifDropdownMobile   = ref(null)

const toggleMenu = () => {
  menuOpen.value = !menuOpen.value
  if (menuOpen.value) showNotifications.value = false
}

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value
  if (showNotifications.value) menuOpen.value = false
  // La campana se detiene reactivamente cuando tieneNoLeidas === false,
  // lo que ocurre al marcar individualmente o con "marcar todas leídas".
}

const handleClickOutside = (event) => {
  if (profileDropdown.value && !profileDropdown.value.contains(event.target)) {
    menuOpen.value = false
  }
  const clickDesktop = notifDropdownDesktop.value?.contains(event.target)
  const clickMobile  = notifDropdownMobile.value?.contains(event.target)
  if (!clickDesktop && !clickMobile) {
    showNotifications.value = false
  }
}

// Abre el modal de detalle y marca la notificación como leída
const abrirDetalle = async (notif) => {
  notifSeleccionada.value  = notif
  showNotifications.value = false
  if (!notif.leida) {
    await notifStore.marcarLeida(notif.id)
  }
}

const cerrarDetalle = () => {
  notifSeleccionada.value = null
}

// Helpers de presentación
const labelServicio = (estatus) => {
  const map = {
    PENALIZADO_RESERVA:  'Reservaciones',
    PENALIZADO_LUDOTECA: 'Ludoteca',
    PENALIZADO_AMBOS:    'Reservaciones y Ludoteca',
  }
  return map[estatus] ?? estatus
}

const formatFecha = (iso) => {
  if (!iso) return null
  const d = new Date(iso)
  if (isNaN(d.getTime())) return null
  return d.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const tituloNotif = (notif) => {
  if (notif.data?.tipo === 'SANCION_ASIGNADA')     return 'Penalización asignada'
  if (notif.data?.tipo === 'SANCION_LEVANTADA')    return 'Penalización levantada'
  if (notif.data?.tipo === 'SOLICITUD_ACEPTADA')   return '¡Solicitud aceptada!'
  if (notif.data?.tipo === 'SOLICITUD_RECHAZADA')  return 'Solicitud rechazada'
  if (notif.data?.tipo === 'SOLICITUD_ENVIADA')    return 'Nueva solicitud de amistad'
  return 'Notificación'
}

const subtituloNotif = (notif) => {
  const nombre = notif.data?.nombre_remitente ?? 'Un socio'
  if (notif.data?.tipo === 'SANCION_ASIGNADA')     return labelServicio(notif.data?.estatus_penalizacion)
  if (notif.data?.tipo === 'SANCION_LEVANTADA')    return 'Todos tus servicios están activos'
  if (notif.data?.tipo === 'SOLICITUD_ACEPTADA')   return `El socio ${nombre} ha aceptado tu solicitud.`
  if (notif.data?.tipo === 'SOLICITUD_RECHAZADA')  return `El socio ${nombre} ha rechazado tu solicitud.`
  if (notif.data?.tipo === 'SOLICITUD_ENVIADA')    return `Has recibido una solicitud de amistad de ${nombre}.`
  return ''
}

// Helper: ¿es una notificación de amistad?
const esTipoAmistad = (tipo) =>
  ['SOLICITUD_ENVIADA', 'SOLICITUD_ACEPTADA', 'SOLICITUD_RECHAZADA'].includes(tipo)

const iconoNotif = (tipo) => {
  if (tipo === 'SANCION_ASIGNADA') {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
      <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
      <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
    </svg>`
  }
  if (tipo === 'SANCION_LEVANTADA') {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
      <polyline points="22 4 12 14.01 9 11.01"/>
    </svg>`
  }
  // Íconos de amistad
  if (tipo === 'SOLICITUD_ACEPTADA') {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
      <path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </svg>`
  }
  if (tipo === 'SOLICITUD_RECHAZADA') {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
      <line x1="17" y1="11" x2="23" y2="11"/>
    </svg>`
  }
  if (tipo === 'SOLICITUD_ENVIADA') {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
      <path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>
    </svg>`
  }
  return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
  </svg>`
}

onMounted(async () => {
  document.addEventListener('click', handleClickOutside)
  await notifStore.fetchNotificaciones()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="font-sans">
    <div class="hidden md:block h-[90px] w-full"></div>
    <div class="md:hidden h-[68px] w-full"></div>

    <!-- ── DESKTOP ── -->
    <nav class="hidden md:flex w-full fixed top-0 z-100 px-4 pt-4 pb-4 backdrop-blur-sm pointer-events-none justify-center">
      <div class="pointer-events-auto w-full max-w-5xl rounded-2xl bg-white/80 backdrop-blur-md shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-surface-200/50 px-6 py-2.5 flex items-center justify-between transition-all">

        <router-link to="/socio/home" class="flex items-center gap-3 shrink-0 group">
          <div class="p-1 bg-white rounded-xl shadow-sm border border-surface-100 group-hover:scale-105 transition-transform">
            <img src="../../assets/LogoSocDep.jpg" alt="SOC-DEP HUB" class="h-8 w-8 object-cover rounded-lg" />
          </div>
          <span class="font-bold text-lg tracking-tight text-surface-900 group-hover:text-primary-600 transition-colors">SOC-DEP</span>
        </router-link>

        <div class="flex items-center gap-1 lg:gap-2 justify-center flex-1 mx-4">
          <router-link to="/socio/home" class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
            <IconHome class="w-[18px] h-[18px] group-[.router-link-active]:text-white" /> Inicio
          </router-link>
          <router-link to="/socio/agenda"
            class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
            <IconCalendar class="w-[18px] h-[18px] group-[.router-link-active]:text-white" /> Agenda
          </router-link>
          <router-link to="/socio/tournaments" class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
            <IconTrophy class="w-[18px] h-[18px] group-[.router-link-active]:text-white" /> Torneos
          </router-link>
          <router-link to="/socio/community" class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
            <IconGuests class="w-[18px] h-[18px] group-[.router-link-active]:text-white" /> Amigos
          </router-link>
          <router-link to="/socio/qr"
            class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
            <IconQr class="w-[18px] h-[18px] group-[.router-link-active]:text-white" /> Código QR
          </router-link>
        </div>

        <div class="flex items-center gap-3 shrink-0">
          <!-- ── Campanita Desktop ── -->
          <div class="relative" ref="notifDropdownDesktop">
            <button
              class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 hover:bg-surface-100 hover:text-surface-900 active:scale-95 transition-all"
              @click="toggleNotifications"
            >
              <IconBell class="w-5 h-5" :class="notifStore.tieneNoLeidas ? 'text-primary-600 bell-ring' : ''" />
              <span v-if="notifStore.tieneNoLeidas"
                class="absolute top-2 right-2.5 bg-primary-600 h-2 w-2 rounded-full border border-white ring-[1.5px] ring-white"/>
            </button>

            <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100">
              <div v-if="showNotifications"
                class="absolute top-14 right-0 w-80 bg-white rounded-2xl border border-surface-100 shadow-[0_15px_50px_rgba(0,0,0,0.1)] z-50 overflow-hidden">
                <div class="flex items-center justify-between px-5 pt-5 pb-3">
                  <h4 class="font-bold text-base text-surface-900">Notificaciones</h4>
                  <button v-if="notifStore.tieneNoLeidas"
                    @click="notifStore.marcarTodasLeidas"
                    class="text-[11px] font-bold text-primary-600 hover:text-primary-800 transition-colors">
                    Marcar todas leídas
                  </button>
                </div>
                <div class="h-px bg-surface-100 mx-5"/>

                <div v-if="notifStore.isLoading" class="flex justify-center py-8">
                  <div class="w-6 h-6 rounded-full border-2 border-surface-200 border-t-primary-500 animate-spin"/>
                </div>
                <div v-else-if="notifStore.notificaciones.length === 0"
                  class="py-8 text-center text-sm font-medium text-surface-400">
                  Sin notificaciones nuevas
                </div>
                <ul v-else class="max-h-72 overflow-y-auto divide-y divide-surface-50">
                  <li v-for="n in notifStore.notificaciones" :key="n.id"
                    @click="abrirDetalle(n)"
                    class="flex items-start gap-3 px-5 py-3.5 cursor-pointer transition-colors"
                    :class="n.leida ? 'hover:bg-surface-50' : 'bg-primary-50/60 hover:bg-primary-50'"
                  >
                    <div class="mt-0.5 w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                      :class="n.leida ? 'bg-surface-100 text-surface-400' : 'bg-primary-100 text-primary-600'">
                      <span v-html="iconoNotif(n.data?.tipo)"/>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-bold truncate"
                        :class="n.leida ? 'text-surface-900' : 'text-primary-600'">{{ tituloNotif(n) }}</p>
                      <p class="text-xs truncate mt-0.5"
                        :class="n.leida ? 'text-surface-500' : 'text-primary-500'">{{ subtituloNotif(n) }}</p>
                      <p class="text-[10px] text-surface-400 mt-1">{{ new Date(n.creada_en).toLocaleDateString('es-MX') }}</p>
                    </div>
                    <span v-if="!n.leida" class="mt-2 w-2 h-2 rounded-full bg-primary-500 shrink-0"/>
                  </li>
                </ul>
              </div>
            </Transition>
          </div>

          <!-- Avatar / dropdown perfil -->
          <div class="relative" ref="profileDropdown">
            <button class="w-10 h-10 bg-primary-600 outline-2 outline-offset-2 outline-transparent hover:outline-primary-200 text-white rounded-xl shadow-inner flex items-center justify-center text-sm font-bold hover:scale-105 active:scale-95 transition-all" @click="toggleMenu">
              {{ profileStore.userInitials }}
            </button>
            <div v-if="menuOpen" class="absolute top-14 right-0 w-64 bg-white rounded-2xl border border-surface-100 shadow-[0_15px_50px_rgba(0,0,0,0.1)] p-3 z-50 flex flex-col gap-1 transition-all">
              <div class="px-4 py-3 bg-surface-50 rounded-xl mb-2 border border-surface-100">
                <span class="block text-xs font-medium text-surface-500 uppercase tracking-wider mb-1">Mi Cuenta</span>
                <strong class="block text-sm font-bold text-surface-900">{{ profileStore.userInitials }} (Socio)</strong>
              </div>
              <router-link to="/socio/profile" class="px-4 py-2.5 text-sm font-medium text-surface-600 hover:bg-surface-50 hover:text-primary-700 rounded-lg transition-colors flex items-center gap-3">
                <IconUser class="w-[18px] h-[18px]" /> Mi Perfil
              </router-link>
              <router-link to="/socio/history" class="px-4 py-2.5 text-sm font-medium text-surface-600 hover:bg-surface-50 hover:text-primary-700 rounded-lg transition-colors flex items-center gap-3">
                <IconClock class="w-[18px] h-[18px]" /> Historial
              </router-link>
              <button @click="profileStore.getSupportLink" class="px-4 py-2.5 text-sm font-medium text-surface-600 hover:bg-surface-50 hover:text-primary-700 rounded-lg transition-colors text-left flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px] opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Ayuda
              </button>
              <div class="h-px bg-surface-100 w-full my-1 rounded-full"/>
              <button class="px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors text-left flex items-center gap-3" @click="profileStore.logout">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Cerrar Sesión
              </button>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- ── MOBILE ── -->
    <div class="md:hidden fixed top-0 left-0 w-full px-5 py-3 bg-white/90 backdrop-blur-xl border-b border-surface-200 z-110 flex items-center justify-between shadow-sm">
      <div class="flex items-center gap-3">
        <img src="../../assets/LogoSocDep.jpg" class="w-9 h-9 rounded-lg shadow-sm border border-surface-100 object-cover" />
        <span class="font-bold text-lg text-surface-900 tracking-tight">SOC-DEP</span>
      </div>

      <!-- ── Campanita Mobile ── -->
      <div class="relative" ref="notifDropdownMobile">
        <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 active:scale-95 hover:bg-surface-100 transition-all relative" @click="toggleNotifications">
          <IconBell class="w-5 h-5" :class="notifStore.tieneNoLeidas ? 'text-primary-600 bell-ring' : ''" />
          <span v-if="notifStore.tieneNoLeidas"
            class="absolute top-2 right-2.5 bg-primary-600 h-2 w-2 rounded-full border border-white ring-[1.5px] ring-white"/>
        </button>

        <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100">
          <div v-if="showNotifications"
            class="absolute top-12 right-0 w-80 bg-white rounded-2xl border border-surface-200 shadow-2xl z-60 overflow-hidden">
            <div class="flex items-center justify-between px-5 pt-5 pb-3">
              <h4 class="font-bold text-base text-surface-900">Notificaciones</h4>
              <button v-if="notifStore.tieneNoLeidas"
                @click="notifStore.marcarTodasLeidas"
                class="text-[11px] font-bold text-primary-600 hover:text-primary-800 transition-colors">
                Marcar todas leídas
              </button>
            </div>
            <div class="h-px bg-surface-100 mx-5"/>

            <div v-if="notifStore.isLoading" class="flex justify-center py-8">
              <div class="w-6 h-6 rounded-full border-2 border-surface-200 border-t-primary-500 animate-spin"/>
            </div>
            <div v-else-if="notifStore.notificaciones.length === 0"
              class="py-8 text-center text-sm font-medium text-surface-400">
              Sin notificaciones nuevas
            </div>
            <ul v-else class="max-h-72 overflow-y-auto divide-y divide-surface-50">
              <li v-for="n in notifStore.notificaciones" :key="n.id"
                @click="abrirDetalle(n)"
                class="flex items-start gap-3 px-5 py-3.5 cursor-pointer transition-colors"
                :class="n.leida ? 'hover:bg-surface-50' : 'bg-primary-50/60 hover:bg-primary-50'"
              >
                <div class="mt-0.5 w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                  :class="n.leida ? 'bg-surface-100 text-surface-400' : 'bg-primary-100 text-primary-600'">
                  <span v-html="iconoNotif(n.data?.tipo)"/>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold truncate"
                    :class="n.leida ? 'text-surface-900' : 'text-primary-600'">{{ tituloNotif(n) }}</p>
                  <p class="text-xs truncate mt-0.5"
                    :class="n.leida ? 'text-surface-500' : 'text-primary-500'">{{ subtituloNotif(n) }}</p>
                  <p class="text-[10px] text-surface-400 mt-1">{{ new Date(n.creada_en).toLocaleDateString('es-MX') }}</p>
                </div>
                <span v-if="!n.leida" class="mt-2 w-2 h-2 rounded-full bg-primary-500 shrink-0"/>
              </li>
            </ul>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="md:hidden fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-surface-200 z-100 px-2 pt-2 pb-[max(env(safe-area-inset-bottom),0.5rem)] shadow-[0_-10px_20px_rgba(0,0,0,0.03)] selection:bg-transparent">
      <div class="flex items-center justify-between h-[64px] pb-1 gap-1">
        <router-link to="/socio/home" class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative active:scale-95 transition-all">
          <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300"/>
          <div class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none"/>
          <IconHome class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
          <span class="text-[12px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Inicio</span>
        </router-link>

        <router-link to="/socio/agenda" class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative active:scale-95 transition-all">
          <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300"/>
          <div class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none"/>
          <IconCalendar class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
          <span class="text-[12px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Agenda</span>
        </router-link>

        <router-link to="/socio/qr" class="flex flex-col items-center justify-center group w-[20%] relative active:scale-90 transition-all -mt-6">
          <div class="w-14 h-14 rounded-full bg-linear-to-br from-primary-800 to-primary-600 shadow-[0_6px_24px_rgba(37,99,235,0.45)] flex items-center justify-center ring-4 ring-white">
            <IconQr class="w-7 h-7 text-white drop-shadow-sm" />
          </div>
          <span class="text-[11px] font-bold text-primary-700 mt-1 tracking-wide">Código QR</span>
        </router-link>

        <router-link to="/socio/community" class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative active:scale-95 transition-all">
          <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300"/>
          <div class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none"/>
          <IconGuests class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
          <span class="text-[12px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Comunidad</span>
        </router-link>

        <router-link to="/socio/profile" class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative active:scale-95 transition-all">
          <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300"/>
          <div class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none"/>
          <IconUser class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
          <span class="text-[12px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Perfil</span>
        </router-link>
      </div>
    </nav>

    <!-- ══════════════════════════════════════════
         MODAL DE DETALLE DE NOTIFICACIÓN
    ══════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div v-if="notifSeleccionada"
          class="fixed inset-0 z-200 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="cerrarDetalle"
        >
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
          >
            <div v-if="notifSeleccionada"
              class="bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl overflow-hidden"
            >
              <!-- Cabecera coloreada — roja para sanción, verde para liberación -->
              <div
                class="px-7 py-6 text-white relative overflow-hidden"
                :class="esTipoAmistad(notifSeleccionada.data?.tipo)
                  ? 'bg-linear-to-br from-blue-500 to-indigo-600'
                  : notifSeleccionada.data?.tipo === 'SANCION_LEVANTADA'
                    ? 'bg-linear-to-br from-green-500 to-emerald-500'
                    : 'bg-linear-to-br from-red-500 to-orange-500'"
              >
                <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"/>
                <div class="relative z-10 flex items-start gap-4">
                  <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
                    <!-- Ícono amistad -->
                    <svg v-if="esTipoAmistad(notifSeleccionada.data?.tipo)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                      <path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <!-- Ícono sanción -->
                    <svg v-else-if="notifSeleccionada.data?.tipo !== 'SANCION_LEVANTADA'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                      <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                      <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <!-- Ícono liberación -->
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                      <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                  </div>
                  <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-white/70 mb-0.5">
                      {{ esTipoAmistad(notifSeleccionada.data?.tipo)
                        ? 'Comunidad · Amigos'
                        : notifSeleccionada.data?.tipo === 'SANCION_LEVANTADA'
                          ? 'Penalización Levantada'
                          : 'Penalización Asignada' }}
                    </p>
                    <h3 class="text-lg font-black leading-tight">
                      {{ esTipoAmistad(notifSeleccionada.data?.tipo)
                        ? tituloNotif(notifSeleccionada)
                        : notifSeleccionada.data?.tipo === 'SANCION_LEVANTADA'
                          ? 'Cuenta sin restricciones'
                          : labelServicio(notifSeleccionada.data?.estatus_penalizacion) }}
                    </h3>
                  </div>
                </div>
              </div>

              <!-- Cuerpo — variante amistad -->
              <div v-if="esTipoAmistad(notifSeleccionada.data?.tipo)" class="px-7 py-6 space-y-4">
                <p class="text-sm font-semibold text-surface-700 leading-relaxed">
                  {{ subtituloNotif(notifSeleccionada) }}
                </p>
                <div class="flex items-start gap-3 bg-blue-50 rounded-2xl border border-blue-100 p-4">
                  <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                  </svg>
                  <p class="text-xs font-semibold text-blue-800 leading-relaxed">
                    Puedes gestionar tus amigos y solicitudes desde la sección Comunidad.
                  </p>
                </div>
              </div>

              <!-- Cuerpo — variante sanción -->
              <div v-else-if="notifSeleccionada.data?.tipo !== 'SANCION_LEVANTADA'" class="px-7 py-6 space-y-4">
                <p class="text-sm font-semibold text-surface-700 leading-relaxed">
                  La administración del club ha registrado una penalización en tu cuenta para el/los siguiente(s) servicio(s):
                  <span class="font-black text-surface-900"> {{ labelServicio(notifSeleccionada.data?.estatus_penalizacion) }}</span>.
                </p>

                <!-- Fechas de liberación -->
                <div class="bg-surface-50 rounded-2xl border border-surface-200 p-4 space-y-3">
                  <p class="text-[10px] font-black uppercase tracking-widest text-surface-500">Fecha de Liberación</p>

                  <div v-if="notifSeleccionada.data?.fecha_fin_reserva" class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"/>
                    <div>
                      <p class="text-[10px] font-bold text-surface-500 uppercase tracking-wide">Reservaciones</p>
                      <p class="text-sm font-black text-surface-900">{{ formatFecha(notifSeleccionada.data.fecha_fin_reserva) }}</p>
                    </div>
                  </div>

                  <div v-if="notifSeleccionada.data?.fecha_fin_ludoteca" class="flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"/>
                    <div>
                      <p class="text-[10px] font-bold text-surface-500 uppercase tracking-wide">Ludoteca</p>
                      <p class="text-sm font-black text-surface-900">{{ formatFecha(notifSeleccionada.data.fecha_fin_ludoteca) }}</p>
                    </div>
                  </div>
                </div>

                <div class="flex items-start gap-3 bg-primary-50 rounded-2xl border border-primary-100 p-4">
                  <svg class="w-5 h-5 text-primary-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                  </svg>
                  <p class="text-xs font-semibold text-primary-800 leading-relaxed">
                    Te invitamos a acudir con la gerencia del club para mayor información sobre esta restricción.
                  </p>
                </div>
              </div>

              <!-- Cuerpo — variante liberación -->
              <div v-else class="px-7 py-6 space-y-4">
                <p class="text-sm font-semibold text-surface-700 leading-relaxed">
                  Nos complace informarte que la penalización en tu cuenta ha sido <span class="font-black text-surface-900">levantada</span>.
                  Todos tus servicios están nuevamente disponibles.
                </p>
                <div class="flex items-start gap-3 bg-green-50 rounded-2xl border border-green-100 p-4">
                  <svg class="w-5 h-5 text-green-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                  </svg>
                  <p class="text-xs font-semibold text-green-800 leading-relaxed">
                    {{ notifSeleccionada.data?.motivo === 'automatico'
                      ? 'El período de penalización concluyó de forma automática.'
                      : 'Un administrador del club levantó la restricción en tu cuenta.' }}
                  </p>
                </div>
              </div>

              <!-- Pie -->
              <div class="px-7 py-4 border-t border-surface-100 flex justify-end">
                <button @click="cerrarDetalle"
                  class="px-6 py-2.5 rounded-xl bg-surface-900 text-white text-sm font-black hover:bg-surface-800 active:scale-95 transition-all">
                  Entendido
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<style scoped>
/*
  Swing suave de campana: 3 balanceos rápidos, luego pausa larga.
  Ciclo total 5 s — activo los primeros 0.9 s, quieto el resto.
  transform-origin arriba-centro para simular el pivote de la campana.
*/
.bell-ring {
  transform-origin: top center;
  animation: bell-swing 5s ease-in-out infinite;
}

@keyframes bell-swing {
  0%        { transform: rotate(0deg);   }
  5%        { transform: rotate(18deg);  }
  10%       { transform: rotate(-15deg); }
  15%       { transform: rotate(12deg);  }
  20%       { transform: rotate(-8deg);  }
  25%       { transform: rotate(4deg);   }
  30%, 100% { transform: rotate(0deg);   }
}
</style>
