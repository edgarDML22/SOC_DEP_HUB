<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useProfileStore } from '@/stores/profiles/socioStore'
import { useNotificacionesStore } from '@/stores/profiles/notificacionesStore'
import { IconHome, IconCalendar, IconTrophy, IconGuests, IconClock, IconUser, IconBell, IconQr } from '@/components/icons';
import DarkModeToggle from '@/components/ui/DarkModeToggle.vue'

const profileStore       = useProfileStore()
const notifStore         = useNotificacionesStore()
const router             = useRouter()

const virtualMorosoLeido = ref(false)

const notificacionesList = computed(() => {
  const list = [...notifStore.notificaciones];
  if (profileStore.profileData?.estatus_cuenta === 'MOROSO') {
    list.unshift({
      id: 'virtual-moroso',
      leida: virtualMorosoLeido.value,
      creada_en: new Date().toISOString(),
      data: {
        tipo: 'CUENTA_MOROSA'
      }
    });
  }
  return list;
});

const tieneNoLeidas = computed(() => {
  const morosoNoLeido = (profileStore.profileData?.estatus_cuenta === 'MOROSO' && !virtualMorosoLeido.value);
  return notifStore.tieneNoLeidas || morosoNoLeido;
});

const marcarTodasLeidas = async () => {
  virtualMorosoLeido.value = true;
  await notifStore.marcarTodasLeidas();
};

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
  if (notif.id === 'virtual-moroso') {
    virtualMorosoLeido.value = true
  } else if (!notif.leida) {
    await notifStore.marcarLeida(notif.id)
  }
}

const cerrarDetalle = () => {
  notifSeleccionada.value = null
}

watch(notifSeleccionada, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

// Ensures overflow is reset if the component is destroyed while the modal is open
onUnmounted(() => {
  document.body.style.overflow = ''
  document.removeEventListener('click', handleClickOutside)
})

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
  // Date-only strings (YYYY-MM-DD) are UTC midnight — add T12:00 to keep local date correct
  const normalized = /^\d{4}-\d{2}-\d{2}$/.test(iso) ? `${iso}T12:00:00` : iso
  const d = new Date(normalized)
  if (isNaN(d.getTime())) return null
  return d.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const tituloNotif = (notif) => {
  if (notif.data?.tipo === 'CUENTA_MOROSA')               return 'Adeudo Pendiente'
  if (notif.data?.tipo === 'SANCION_ASIGNADA')            return 'Penalización asignada'
  if (notif.data?.tipo === 'SANCION_LEVANTADA')           return 'Penalización levantada'
  if (notif.data?.tipo === 'SOLICITUD_ACEPTADA')          return '¡Solicitud aceptada!'
  if (notif.data?.tipo === 'SOLICITUD_RECHAZADA')         return 'Solicitud rechazada'
  if (notif.data?.tipo === 'SOLICITUD_ENVIADA')           return 'Nueva solicitud de amistad'
  if (notif.data?.tipo === 'SESION_CANCELADA')            return 'Sesión cancelada'
  if (notif.data?.tipo === 'SESION_CANCELADA_INSTRUCTOR') return 'Sesión cancelada'
  if (notif.data?.tipo === 'INVITACION_EQUIPO')    return 'Invitación a torneo'
  return 'Notificación'
}

const subtituloNotif = (notif) => {
  if (notif.data?.tipo === 'CUENTA_MOROSA')        return 'Tu cuenta tiene estatus de MOROSO'
  const nombre = notif.data?.nombre_remitente ?? 'Un socio'
  if (notif.data?.tipo === 'SANCION_ASIGNADA')     return labelServicio(notif.data?.estatus_penalizacion)
  if (notif.data?.tipo === 'SANCION_LEVANTADA')    return 'Todos tus servicios están activos'
  if (notif.data?.tipo === 'SOLICITUD_ACEPTADA')   return `El socio ${nombre} ha aceptado tu solicitud.`
  if (notif.data?.tipo === 'SOLICITUD_RECHAZADA')  return `El socio ${nombre} ha rechazado tu solicitud.`
  if (notif.data?.tipo === 'SOLICITUD_ENVIADA')    return `Has recibido una solicitud de amistad de ${nombre}.`
  if (notif.data?.tipo === 'SESION_CANCELADA') {
    const d = notif.data
    const hora = d.hora_inicio ? d.hora_inicio.slice(0, 5) : ''
    return [d.disciplina, hora].filter(Boolean).join(' · ')
  }
  if (notif.data?.tipo === 'SESION_CANCELADA_INSTRUCTOR') {
    const d = notif.data
    const hora = d.hora_inicio ? d.hora_inicio.slice(0, 5) : ''
    return [d.disciplina, hora].filter(Boolean).join(' · ')
  }
  if (notif.data?.tipo === 'INVITACION_EQUIPO') {
    const capitan = notif.data?.nombre_capitan ?? 'Un socio'
    return `${capitan} te ha invitado a jugar en su equipo.`
  }
  return ''
}

// Helper: ¿es una notificación de amistad?
const esTipoAmistad = (tipo) =>
  ['SOLICITUD_ENVIADA', 'SOLICITUD_ACEPTADA', 'SOLICITUD_RECHAZADA'].includes(tipo)

// Helper: ¿es notificación de torneo?
const esTipoTorneo = (tipo) =>
  ['INVITACION_EQUIPO'].includes(tipo)

const iconoNotif = (tipo) => {
  if (tipo === 'CUENTA_MOROSA') {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-red-500">
      <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
      <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
    </svg>`
  }
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
  if (tipo === 'SESION_CANCELADA' || tipo === 'SESION_CANCELADA_INSTRUCTOR') {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
      <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
      <line x1="9" y1="14" x2="15" y2="20"/><line x1="15" y1="14" x2="9" y2="20"/>
    </svg>`
  }
  if (tipo === 'INVITACION_EQUIPO') {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
      <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
    </svg>`
  }
  return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
  </svg>`
}

const esTipoSesionCancelada = (tipo) =>
  ['SESION_CANCELADA', 'SESION_CANCELADA_INSTRUCTOR'].includes(tipo)

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

</script>

<template>
  <div class="font-sans">
    <div class="hidden md:block h-[90px] w-full"></div>
    <div class="md:hidden h-[68px] w-full"></div>

    <!-- ── DESKTOP ── -->
    <nav class="hidden md:flex w-full fixed top-0 z-100 px-4 pt-4 pb-4 backdrop-blur-sm pointer-events-none justify-center">
      <div class="pointer-events-auto w-full max-w-5xl rounded-2xl bg-white/80 dark:bg-surface-100/80 backdrop-blur-md shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-surface-200/50 px-6 py-2.5 flex items-center justify-between transition-all">

        <router-link to="/socio/home" class="flex items-center gap-3 shrink-0 group">
          <div class="p-1 bg-white rounded-xl shadow-sm border border-surface-100 group-hover:scale-105 transition-transform">
            <img src="../../assets/LogoSocDep.png" alt="SOC-DEP HUB" class="h-8 w-8 object-cover rounded-lg" />
          </div>
          <span class="font-bold text-lg tracking-tight text-surface-900 group-hover:text-primary-600 transition-colors">Soc-Dep Hub</span>
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
          <!-- BOTÓN DE MODO OSCURO (DESKTOP) -->
          <DarkModeToggle />

          <!-- ── Campanita Desktop ── -->
          <div class="relative" ref="notifDropdownDesktop">
            <button
              class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 hover:bg-surface-100 hover:text-surface-900 active:scale-95 transition-all"
              @click="toggleNotifications"
            >
              <IconBell class="w-5 h-5" :class="tieneNoLeidas ? 'text-primary-600 bell-ring' : ''" />
              <span v-if="tieneNoLeidas"
                class="absolute top-2 right-2.5 bg-primary-600 h-2 w-2 rounded-full border border-white ring-[1.5px] ring-white"/>
            </button>

            <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100">
              <div v-if="showNotifications"
                class="absolute top-14 right-0 w-80 bg-white dark:bg-surface-100 rounded-2xl border border-surface-100 shadow-[0_15px_50px_rgba(0,0,0,0.1)] z-50 overflow-hidden">
                <div class="flex items-center justify-between px-5 pt-5 pb-3">
                  <h4 class="font-bold text-base text-surface-900">Notificaciones</h4>
                  <button v-if="tieneNoLeidas"
                    @click="marcarTodasLeidas"
                    class="text-[11px] font-bold text-primary-600 hover:text-primary-800 transition-colors">
                    Marcar todas leídas
                  </button>
                </div>
                <div class="h-px bg-surface-100 mx-5"/>

                <div v-if="notifStore.isLoading" class="flex justify-center py-8">
                  <div class="w-6 h-6 rounded-full border-2 border-surface-200 border-t-primary-500 animate-spin"/>
                </div>
                <div v-else-if="notificacionesList.length === 0"
                  class="py-8 text-center text-sm font-medium text-surface-400">
                  Sin notificaciones nuevas
                </div>
                <ul v-else class="max-h-72 overflow-y-auto divide-y divide-surface-50">
                  <li v-for="n in notificacionesList" :key="n.id"
                    @click="abrirDetalle(n)"
                    class="flex items-start gap-3 px-5 py-3.5 cursor-pointer transition-colors"
                    :class="n.id === 'virtual-moroso' && !n.leida
                      ? 'bg-red-50/60 hover:bg-red-50'
                      : n.leida
                        ? 'hover:bg-surface-50'
                        : 'bg-primary-50/60 hover:bg-primary-50'"
                  >
                    <div class="mt-0.5 w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                      :class="n.id === 'virtual-moroso' && !n.leida
                        ? 'bg-red-100 text-red-600'
                        : n.leida
                          ? 'bg-surface-100 text-surface-400'
                          : 'bg-primary-100 text-primary-600'">
                      <span v-html="iconoNotif(n.data?.tipo)"/>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-bold truncate"
                        :class="n.id === 'virtual-moroso' && !n.leida
                          ? 'text-red-600'
                          : n.leida
                            ? 'text-surface-900'
                            : 'text-primary-600'">{{ tituloNotif(n) }}</p>
                      <p class="text-xs truncate mt-0.5"
                        :class="n.id === 'virtual-moroso' && !n.leida
                          ? 'text-red-500'
                          : n.leida
                            ? 'text-surface-500'
                            : 'text-primary-500'">{{ subtituloNotif(n) }}</p>
                      <p class="text-[10px] text-surface-400 mt-1">{{ new Date(n.creada_en).toLocaleDateString('es-MX') }}</p>
                    </div>
                    <span v-if="!n.leida" class="mt-2 w-2 h-2 rounded-full bg-primary-500 shrink-0"
                      :class="n.id === 'virtual-moroso' ? 'bg-red-500' : 'bg-primary-500'"/>
                  </li>
                </ul>
              </div>
            </Transition>
          </div>

          <!-- Avatar / dropdown perfil -->
          <div class="relative" ref="profileDropdown">
            <button class="w-10 h-10 overflow-hidden bg-primary-600 outline-2 outline-offset-2 outline-transparent hover:outline-primary-200 text-white rounded-xl shadow-inner flex items-center justify-center text-sm font-bold hover:scale-105 active:scale-95 transition-all" @click="toggleMenu">
              <img v-if="profileStore.fotoPerfil" :src="profileStore.fotoPerfil" alt="Foto" class="w-full h-full object-cover" />
              <span v-else>{{ profileStore.userInitials }}</span>
            </button>
            <div v-if="menuOpen" class="absolute top-14 right-0 w-64 bg-white dark:bg-surface-100 rounded-2xl border border-surface-100 shadow-[0_15px_50px_rgba(0,0,0,0.1)] p-3 z-50 flex flex-col gap-1 transition-all">
              <div class="px-4 py-3 bg-surface-50 rounded-xl mb-2 border border-surface-100 flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg overflow-hidden bg-primary-600 flex items-center justify-center shrink-0 text-white text-xs font-bold shadow-sm">
                  <img v-if="profileStore.fotoPerfil" :src="profileStore.fotoPerfil" alt="Foto" class="w-full h-full object-cover" />
                  <span v-else>{{ profileStore.userInitials }}</span>
                </div>
                <div class="min-w-0">
                  <span class="block text-[10px] font-medium text-surface-500 uppercase tracking-wider">Mi Cuenta</span>
                  <strong class="block text-xs font-bold text-surface-900 truncate">{{ profileStore.fullName }}</strong>
                </div>
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
    <div class="md:hidden fixed top-0 left-0 w-full px-5 py-3 bg-white/90 dark:bg-surface-100/90 backdrop-blur-xl border-b border-surface-200 z-110 flex items-center justify-between shadow-sm">
      <div class="flex items-center gap-3">
        <img src="../../assets/LogoSocDep.png" class="w-9 h-9 rounded-lg shadow-sm border border-surface-100 object-cover" />
        <span class="font-bold text-lg text-surface-900 tracking-tight">Soc-Dep Hub</span>
      </div>

      <!-- Contenedor del Toggle + Notificaciones en Móvil -->
      <div class="flex items-center gap-3">
        <!-- BOTÓN DE MODO OSCURO (MOBILE) -->
        <DarkModeToggle />

        <!-- ── Campanita Mobile ── -->
        <div class="relative" ref="notifDropdownMobile">
        <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 active:scale-95 hover:bg-surface-100 transition-all relative" @click="toggleNotifications">
          <IconBell class="w-5 h-5" :class="tieneNoLeidas ? 'text-primary-600 bell-ring' : ''" />
          <span v-if="tieneNoLeidas"
            class="absolute top-2 right-2.5 bg-primary-600 h-2 w-2 rounded-full border border-white ring-[1.5px] ring-white"/>
        </button>

        <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100">
          <div v-if="showNotifications"
            class="absolute top-12 right-0 w-80 bg-white dark:bg-surface-100 rounded-2xl border border-surface-200 shadow-2xl z-60 overflow-hidden">
            <div class="flex items-center justify-between px-5 pt-5 pb-3">
              <h4 class="font-bold text-base text-surface-900">Notificaciones</h4>
              <button v-if="tieneNoLeidas"
                @click="marcarTodasLeidas"
                class="text-[11px] font-bold text-primary-600 hover:text-primary-800 transition-colors">
                Marcar todas leídas
              </button>
            </div>
            <div class="h-px bg-surface-100 mx-5"/>

            <div v-if="notifStore.isLoading" class="flex justify-center py-8">
              <div class="w-6 h-6 rounded-full border-2 border-surface-200 border-t-primary-500 animate-spin"/>
            </div>
            <div v-else-if="notificacionesList.length === 0"
              class="py-8 text-center text-sm font-medium text-surface-400">
              Sin notificaciones nuevas
            </div>
            <ul v-else class="max-h-72 overflow-y-auto divide-y divide-surface-50">
              <li v-for="n in notificacionesList" :key="n.id"
                @click="abrirDetalle(n)"
                class="flex items-start gap-3 px-5 py-3.5 cursor-pointer transition-colors"
                :class="n.id === 'virtual-moroso' && !n.leida
                  ? 'bg-red-50/60 hover:bg-red-50'
                  : n.leida
                    ? 'hover:bg-surface-50'
                    : 'bg-primary-50/60 hover:bg-primary-50'"
              >
                <div class="mt-0.5 w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                  :class="n.id === 'virtual-moroso' && !n.leida
                    ? 'bg-red-100 text-red-600'
                    : n.leida
                      ? 'bg-surface-100 text-surface-400'
                      : 'bg-primary-100 text-primary-600'">
                  <span v-html="iconoNotif(n.data?.tipo)"/>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold truncate"
                    :class="n.id === 'virtual-moroso' && !n.leida
                      ? 'text-red-600'
                      : n.leida
                        ? 'text-surface-900'
                        : 'text-primary-600'">{{ tituloNotif(n) }}</p>
                  <p class="text-xs truncate mt-0.5"
                    :class="n.id === 'virtual-moroso' && !n.leida
                      ? 'text-red-500'
                      : n.leida
                        ? 'text-surface-500'
                        : 'text-primary-500'">{{ subtituloNotif(n) }}</p>
                  <p class="text-[10px] text-surface-400 mt-1">{{ new Date(n.creada_en).toLocaleDateString('es-MX') }}</p>
                </div>
                <span v-if="!n.leida" class="mt-2 w-2 h-2 rounded-full bg-primary-500 shrink-0"
                  :class="n.id === 'virtual-moroso' ? 'bg-red-500' : 'bg-primary-500'"/>
              </li>
            </ul>
          </div>
        </Transition>
      </div>
    </div>
  </div>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="md:hidden fixed bottom-0 left-0 w-full bg-white/90 dark:bg-surface-100/90 backdrop-blur-xl border-t border-surface-200 z-100 px-2 pt-2 pb-[max(env(safe-area-inset-bottom),0.5rem)] shadow-[0_-10px_20px_rgba(0,0,0,0.03)] selection:bg-transparent">
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
          class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="cerrarDetalle"
        >
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
          >
            <div v-if="notifSeleccionada"
              class="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden"
            >
              <!-- Cabecera coloreada — roja para sanción, verde para liberación, violeta para sesión -->
              <div
                class="px-7 py-6 text-white relative overflow-hidden"
                :class="esTipoSesionCancelada(notifSeleccionada.data?.tipo)
                  ? 'bg-linear-to-br from-violet-600 to-purple-800'
                  : esTipoAmistad(notifSeleccionada.data?.tipo)
                    ? 'bg-linear-to-br from-blue-500 to-indigo-600'
                    : esTipoTorneo(notifSeleccionada.data?.tipo)
                    ? 'bg-linear-to-br from-purple-500 to-fuchsia-600'
                  : notifSeleccionada.data?.tipo === 'SANCION_LEVANTADA'
                      ? 'bg-linear-to-br from-green-500 to-emerald-500'
                      : 'bg-linear-to-br from-red-500 to-orange-500'"
              >
                <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"/>
                <div class="relative z-10 flex items-start gap-4">
                  <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
                    <!-- Ícono sesión cancelada -->
                    <svg v-if="esTipoSesionCancelada(notifSeleccionada.data?.tipo)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                      <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                      <line x1="9" y1="14" x2="15" y2="20"/><line x1="15" y1="14" x2="9" y2="20"/>
                    </svg>
                    <!-- Ícono amistad -->
                    <svg v-else-if="esTipoAmistad(notifSeleccionada.data?.tipo)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                      <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                      <path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <!-- Ícono torneo -->
                    <svg v-else-if="esTipoTorneo(notifSeleccionada.data?.tipo)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                      <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
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
                      {{ esTipoSesionCancelada(notifSeleccionada.data?.tipo)
                        ? 'Programación · Sesiones'
                        : esTipoAmistad(notifSeleccionada.data?.tipo)
                          ? 'Comunidad · Amigos'
                          : esTipoTorneo(notifSeleccionada.data?.tipo)
                          ? 'Torneos'
                          : notifSeleccionada.data?.tipo === 'SANCION_LEVANTADA'
                              ? 'Penalización Levantada'
                              : notifSeleccionada.data?.tipo === 'CUENTA_MOROSA'
                                ? 'Estado de Cuenta'
                                : 'Penalización Asignada' }}
                    </p>
                    <h3 class="text-lg font-black leading-tight">
                      {{ esTipoSesionCancelada(notifSeleccionada.data?.tipo)
                        ? notifSeleccionada.data?.disciplina ?? 'Sesión cancelada'
                        : (esTipoAmistad(notifSeleccionada.data?.tipo) || esTipoTorneo(notifSeleccionada.data?.tipo))
                          ? tituloNotif(notifSeleccionada)
                          : notifSeleccionada.data?.tipo === 'SANCION_LEVANTADA'
                            ? 'Cuenta sin restricciones'
                            : notifSeleccionada.data?.tipo === 'CUENTA_MOROSA'
                              ? 'Adeudo Pendiente'
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

              <!-- Cuerpo — variante torneo -->
              <div v-else-if="esTipoTorneo(notifSeleccionada.data?.tipo)" class="px-7 py-6 space-y-4">
                <p class="text-sm font-semibold text-surface-700 leading-relaxed">
                  {{ subtituloNotif(notifSeleccionada) }}
                </p>
                <div class="flex items-start gap-3 bg-purple-50 rounded-2xl border border-purple-100 p-4">
                  <svg class="w-5 h-5 text-purple-500 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
                  </svg>
                  <p class="text-xs font-semibold text-purple-800 leading-relaxed">
                    Dirígete a la sección de Torneos (pestaña Historial) para aceptar o rechazar la invitación a este equipo.
                  </p>
                </div>
              </div>

              <!-- Cuerpo — variante morosidad -->
              <div v-else-if="notifSeleccionada.data?.tipo === 'CUENTA_MOROSA'" class="px-7 py-6 space-y-4">
                <p class="text-sm font-semibold text-surface-700 leading-relaxed">
                  Tu cuenta presenta adeudos pendientes. Para regularizar tu estatus y evitar limitaciones en tus servicios, por favor acude a las oficinas de Administración del club.
                </p>
                <div class="flex items-start gap-3 bg-red-50 rounded-2xl border border-red-100 p-4">
                  <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                  </svg>
                  <p class="text-xs font-semibold text-red-800 leading-relaxed">
                    Atención: Los socios con estatus MOROSO no pueden reservar nuevos espacios o clases.
                  </p>
                </div>
              </div>

              <!-- Cuerpo — variante sesión cancelada (socio e instructor) -->
              <div v-else-if="esTipoSesionCancelada(notifSeleccionada.data?.tipo)" class="px-7 py-6 space-y-4">
                <!-- Mensaje intro -->
                <p class="text-sm font-semibold text-surface-600 leading-relaxed">
                  {{ notifSeleccionada.data?.tipo === 'SESION_CANCELADA_INSTRUCTOR'
                    ? 'La administración ha cancelado la siguiente sesión que tenías programada. Por favor, no te presentes al club.'
                    : 'La administración ha cancelado la siguiente sesión. Pedimos una sincera disculpa por los inconvenientes.' }}
                </p>

                <!-- Tarjeta de detalles -->
                <div class="bg-violet-50 rounded-2xl border border-violet-100 overflow-hidden">
                  <div class="px-4 py-2.5 bg-violet-100/60 border-b border-violet-100">
                    <p class="text-[10px] font-black uppercase tracking-widest text-violet-600">Detalle de la sesión</p>
                  </div>
                  <div class="divide-y divide-violet-100/70">

                    <!-- Disciplina -->
                    <div class="flex items-center gap-3 px-4 py-3">
                      <div class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                        </svg>
                      </div>
                      <div class="min-w-0">
                        <p class="text-[10px] font-bold text-violet-500 uppercase tracking-wide">Disciplina</p>
                        <p class="text-sm font-black text-surface-900 truncate">{{ notifSeleccionada.data?.disciplina ?? '—' }}</p>
                      </div>
                    </div>

                    <!-- Instructor (solo para socios) -->
                    <div v-if="notifSeleccionada.data?.instructor" class="flex items-center gap-3 px-4 py-3">
                      <div class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                      </div>
                      <div class="min-w-0">
                        <p class="text-[10px] font-bold text-violet-500 uppercase tracking-wide">Instructor</p>
                        <p class="text-sm font-black text-surface-900 truncate">{{ notifSeleccionada.data.instructor }}</p>
                      </div>
                    </div>

                    <!-- Espacio -->
                    <div class="flex items-center gap-3 px-4 py-3">
                      <div class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                        </svg>
                      </div>
                      <div class="min-w-0">
                        <p class="text-[10px] font-bold text-violet-500 uppercase tracking-wide">Espacio</p>
                        <p class="text-sm font-black text-surface-900 truncate">{{ notifSeleccionada.data?.espacio ?? '—' }}</p>
                      </div>
                    </div>

                    <!-- Fecha -->
                    <div class="flex items-center gap-3 px-4 py-3">
                      <div class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5"/>
                        </svg>
                      </div>
                      <div class="min-w-0">
                        <p class="text-[10px] font-bold text-violet-500 uppercase tracking-wide">Fecha</p>
                        <p class="text-sm font-black text-surface-900">{{ formatFecha(notifSeleccionada.data?.fecha_sesion) ?? '—' }}</p>
                      </div>
                    </div>

                    <!-- Horario -->
                    <div class="flex items-center gap-3 px-4 py-3">
                      <div class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
                        </svg>
                      </div>
                      <div class="min-w-0">
                        <p class="text-[10px] font-bold text-violet-500 uppercase tracking-wide">Horario</p>
                        <p class="text-sm font-black text-surface-900">
                          {{ notifSeleccionada.data?.hora_inicio ? notifSeleccionada.data.hora_inicio.slice(0,5) : '—' }}
                          –
                          {{ notifSeleccionada.data?.hora_fin ? notifSeleccionada.data.hora_fin.slice(0,5) : '—' }}
                        </p>
                      </div>
                    </div>

                  </div>
                </div>

                <!-- Nota informativa -->
                <div class="flex items-start gap-3 bg-violet-50 rounded-2xl border border-violet-200 p-4">
                  <svg class="w-4 h-4 text-violet-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                  </svg>
                  <p class="text-xs font-semibold text-violet-800 leading-relaxed">
                    {{ notifSeleccionada.data?.tipo === 'SESION_CANCELADA_INSTRUCTOR'
                      ? 'Si tienes dudas, comunícate directamente con la gerencia del club.'
                      : 'Si tienes alguna duda, acude a las oficinas de Administración del club. Agradecemos tu comprensión.' }}
                  </p>
                </div>
              </div>

              <!-- Cuerpo — variante sanción -->
              <div v-else-if="notifSeleccionada.data?.tipo !== 'SANCION_LEVANTADA' && notifSeleccionada.data?.tipo !== 'CUENTA_MOROSA' && !esTipoTorneo(notifSeleccionada.data?.tipo)" class="px-7 py-6 space-y-4">
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
              <div class="px-7 py-4 border-t border-surface-100 flex justify-end gap-3">
                <button v-if="esTipoTorneo(notifSeleccionada.data?.tipo)"
                  @click="router.push('/socio/tournaments'); cerrarDetalle();"
                  class="px-6 py-2.5 rounded-xl bg-purple-600 text-white text-sm font-black hover:bg-purple-700 active:scale-95 transition-all">
                  Ir a Torneos
                </button>
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
