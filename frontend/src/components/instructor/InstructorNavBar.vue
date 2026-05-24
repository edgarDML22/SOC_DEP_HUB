<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useInstructorStore } from '@/stores/profiles/instructorStore'
import { useNotificacionesStore } from '@/stores/profiles/notificacionesStore'
import { IconHome, IconCalendar, IconClock, IconUser, IconBell, IconBaby, IconQr } from '@/components/icons';

const profileStore = useInstructorStore();
const notifStore = useNotificacionesStore();

const menuOpen = ref(false)
const showNotifications = ref(false)

const profileDropdown = ref(null)
const notifDropdownDesktop = ref(null)
const notifDropdownMobile = ref(null)

const toggleMenu = () => {
    menuOpen.value = !menuOpen.value
    if (menuOpen.value) showNotifications.value = false;
}

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value
    if (showNotifications.value) menuOpen.value = false;
}

const handleClickOutside = (event) => {
    if (profileDropdown.value && !profileDropdown.value.contains(event.target)) {
        menuOpen.value = false;
    }
    
    const clickedDesktopNotif = notifDropdownDesktop.value && notifDropdownDesktop.value.contains(event.target);
    const clickedMobileNotif = notifDropdownMobile.value && notifDropdownMobile.value.contains(event.target);
    
    if (!clickedDesktopNotif && !clickedMobileNotif) {
        showNotifications.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="font-sans">
    <!-- ESPACIADORES PARA EVITAR SUPERPOSICIÓN -->
    <div class="hidden md:block h-[90px] w-full"></div>
    <div class="md:hidden h-[68px] w-full"></div>

    <!-- ========================================= -->
    <!-- DESKTOP: FLOATING ISLAND NAVIGATION       -->
    <!-- ========================================= -->
    <nav class="hidden md:flex w-full fixed top-0 z-100 px-4 pt-4 pb-4 backdrop-blur-sm pointer-events-none justify-center">
      <div class="pointer-events-auto w-full max-w-5xl rounded-2xl bg-white/80 backdrop-blur-md shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-surface-200/50 px-6 py-2.5 flex items-center justify-between transition-all">
        
        <!-- Izquierda: Logo -->
        <router-link to="/instructor/home" class="flex items-center gap-3 shrink-0 group">
          <div class="p-1 bg-white rounded-xl shadow-sm border border-surface-100 group-hover:scale-105 transition-transform">
              <img src="../../assets/LogoSocDep.jpg" alt="SOC-DEP HUB" class="h-8 w-8 object-cover rounded-lg" />
          </div>
          <span class="font-bold text-lg tracking-tight text-surface-900 group-hover:text-primary-600 transition-colors">SOC-DEP</span>
        </router-link>

        <!-- Centro: Enlaces (Isla de navegación) con Active State Notorio -->
        <div class="flex items-center gap-1 lg:gap-2 justify-center flex-1 mx-4">
            <router-link to="/instructor/home" class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
                <IconHome class="w-[18px] h-[18px] group-[.router-link-active]:text-white" /> Inicio
            </router-link>

            <router-link to="/instructor/agenda" class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
                <IconCalendar class="w-[18px] h-[18px] group-[.router-link-active]:text-white"/> Agenda
            </router-link>

            <router-link to="/instructor/qr" class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
                <IconQr class="w-[18px] h-[18px] group-[.router-link-active]:text-white"/> Escanear QR
            </router-link>

            <router-link to="/instructor/encuentros" class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
                <svg class="w-[18px] h-[18px] group-[.router-link-active]:text-white animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497" />
                </svg>
                Arbitraje
            </router-link>

            <!-- Pestaña de Ludoteca Protegida -->
            <component
                :is="profileStore.tieneTurnoLudotecaHoy ? 'router-link' : 'span'"
                to="/instructor/ludoteca"
                class="group flex items-center gap-2 font-medium px-4 py-2 rounded-xl transition-all [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold"
                :class="profileStore.tieneTurnoLudotecaHoy 
                    ? 'text-surface-500 hover:bg-surface-100 hover:text-surface-900 active:scale-95 cursor-pointer' 
                    : 'text-gray-300 cursor-not-allowed select-none opacity-40'"
            >
                <IconBaby class="w-[18px] h-[18px]" :class="profileStore.tieneTurnoLudotecaHoy ? 'group-[.router-link-active]:text-white' : ''" /> Ludoteca
            </component>
        </div>

        <!-- Derecha: Perfil & Notificaciones -->
        <div class="flex items-center gap-3 shrink-0">
            <!-- Notificaciones -->
            <div class="relative" ref="notifDropdownDesktop">
                <button class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 hover:bg-surface-100 hover:text-surface-900 active:scale-95 transition-all" @click="toggleNotifications">
                    <IconBell class="w-5 h-5" :class="notifStore.tieneNoLeidas ? 'text-primary-600 bell-ring' : ''" />
                    <span v-if="notifStore.tieneNoLeidas" class="absolute top-2 right-2.5 bg-primary-600 h-2 w-2 rounded-full border border-surface-50 ring-[1.5px] ring-white"></span>
                </button>

                <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100">
                    <div v-if="showNotifications" class="absolute top-14 right-0 w-80 bg-white rounded-2xl border border-surface-100 shadow-[0_15px_50px_rgba(0,0,0,0.1)] p-5 z-50 overflow-hidden">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-bold text-base text-surface-900">Notificaciones</h4>
                            <button v-if="notifStore.tieneNoLeidas" @click="notifStore.marcarTodasLeidas" class="text-[11px] font-bold text-primary-600 hover:text-primary-800 transition-colors">Marcar todas leídas</button>
                        </div>
                        <div class="h-px w-full bg-surface-100 mb-4"></div>
                        
                        <div v-if="notifStore.isLoading" class="flex justify-center py-8">
                            <div class="w-6 h-6 rounded-full border-2 border-surface-200 border-t-primary-500 animate-spin"/>
                        </div>
                        <div v-else-if="notifStore.notificaciones.length === 0" class="py-8 text-center text-sm font-medium text-surface-400">
                            Sin notificaciones nuevas
                        </div>
                        <ul v-else class="max-h-72 overflow-y-auto divide-y divide-surface-50 -mx-5 px-5">
                            <li v-for="n in notifStore.notificaciones" :key="n.id" class="flex items-start gap-3 py-3.5">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold truncate" :class="n.leida ? 'text-surface-900' : 'text-primary-600'">{{ n.data?.titulo || 'Notificación' }}</p>
                                    <p class="text-xs text-surface-500 truncate mt-0.5">{{ n.data?.mensaje || 'Nueva actualización' }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </Transition>
            </div>

            <!-- Avatar -->
            <div class="relative" ref="profileDropdown">
                <button class="w-10 h-10 bg-primary-600 outline-2 outline-offset-2 outline-transparent hover:outline-primary-200 text-white rounded-xl shadow-inner flex items-center justify-center text-sm font-bold hover:scale-105 active:scale-95 transition-all" @click="toggleMenu">
                    <span v-if="profileStore.isLoading">...</span>
                    <span v-else>{{ profileStore.userInitials }}</span>
                </button>

                <div v-if="menuOpen" class="absolute top-14 right-0 w-64 bg-white rounded-2xl border border-surface-100 shadow-[0_15px_50px_rgba(0,0,0,0.1)] p-3 z-50 flex flex-col gap-1 transition-all">
                    <div class="px-4 py-3 bg-surface-50 rounded-xl mb-2 border border-surface-100">
                        <span class="block text-xs font-medium text-surface-500 uppercase tracking-wider mb-1">Mi Cuenta</span>
                        <strong class="block text-sm font-bold text-surface-900">{{ profileStore.userInitials }} (Instructor)</strong>
                    </div>
                    
                    <router-link to="/instructor/profile" class="px-4 py-2.5 text-sm font-medium text-surface-600 hover:bg-surface-50 hover:text-primary-700 rounded-lg transition-colors flex items-center gap-3">
                        <IconUser class="w-[18px] h-[18px]" /> Mi Perfil
                    </router-link>
                    
                    <router-link to="/instructor/encuentros" class="px-4 py-2.5 text-sm font-medium text-surface-600 hover:bg-surface-50 hover:text-primary-700 rounded-lg transition-colors flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497" />
                        </svg>
                        Mis Encuentros (Árbitro)
                    </router-link>
                    
                    <button @click="profileStore.getSupportLink" class="px-4 py-2.5 text-sm font-medium text-surface-600 hover:bg-surface-50 hover:text-primary-700 rounded-lg transition-colors text-left flex items-center gap-3 w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px] opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        Ayuda
                    </button>
                    
                    <div class="h-px bg-surface-100 w-full my-1 rounded-full"></div>
                    
                    <button class="px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors text-left flex items-center gap-3 w-full" @click="profileStore.logout(); menuOpen = false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Cerrar Sesión
                    </button>
                </div>
            </div>
        </div>

      </div>
    </nav>


    <!-- ========================================= -->
    <!-- MOBILE: TOP NAVBAR & BOTTOM NAVIGATION    -->
    <!-- ========================================= -->
    
    <!-- Top Bar (Mobile) - Logo & Notifications -->
    <div class="md:hidden fixed top-0 left-0 w-full px-5 py-3 bg-white/90 backdrop-blur-xl border-b border-surface-200 z-110 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
            <img src="../../assets/LogoSocDep.jpg" class="w-9 h-9 rounded-lg shadow-sm border border-surface-100 object-cover" />
            <span class="font-bold text-lg text-surface-900 tracking-tight">SOC-DEP</span>
        </div>
        
        <div class="relative" ref="notifDropdownMobile">
            <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 active:scale-95 hover:bg-surface-100 transition-all relative" @click="toggleNotifications">
                <IconBell class="w-5 h-5" :class="notifStore.tieneNoLeidas ? 'text-primary-600 bell-ring' : ''" />
                <span v-if="notifStore.tieneNoLeidas" class="absolute top-2 right-2.5 bg-primary-600 h-2 w-2 rounded-full border border-surface-50 ring-[1.5px] ring-white"></span>
            </button>

            <!-- Notificaciones Dropdown (Móvil) -->
            <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1 scale-95" enter-to-class="opacity-100 translate-y-0 scale-100">
                <div v-if="showNotifications" class="absolute top-12 right-0 w-80 bg-white rounded-2xl border border-surface-200 shadow-2xl p-5 z-60 overflow-hidden">
                   <div class="flex items-center justify-between mb-2">
                       <h4 class="font-bold text-base text-surface-900">Notificaciones</h4>
                       <button v-if="notifStore.tieneNoLeidas" @click="notifStore.marcarTodasLeidas" class="text-[11px] font-bold text-primary-600 hover:text-primary-800 transition-colors">Marcar todas leídas</button>
                   </div>
                   <div class="h-px w-full bg-surface-100 mb-4"></div>
                   
                   <div v-if="notifStore.isLoading" class="flex justify-center py-8">
                       <div class="w-6 h-6 rounded-full border-2 border-surface-200 border-t-primary-500 animate-spin"/>
                   </div>
                   <div v-else-if="notifStore.notificaciones.length === 0" class="py-8 text-center text-sm font-medium text-surface-400">
                       Sin notificaciones nuevas
                   </div>
                   <ul v-else class="max-h-72 overflow-y-auto divide-y divide-surface-50 -mx-5 px-5">
                       <li v-for="n in notifStore.notificaciones" :key="n.id" class="flex items-start gap-3 py-3.5">
                           <div class="flex-1 min-w-0">
                               <p class="text-sm font-bold truncate" :class="n.leida ? 'text-surface-900' : 'text-primary-600'">{{ n.data?.titulo || 'Notificación' }}</p>
                               <p class="text-xs text-surface-500 truncate mt-0.5">{{ n.data?.mensaje || 'Nueva actualización' }}</p>
                           </div>
                       </li>
                   </ul>
                </div>
            </Transition>
        </div>
    </div>

    <!-- Bottom Navigation Bar (Mobile) -->
    <nav class="md:hidden fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-surface-200 z-100 px-2 pt-2 pb-[max(env(safe-area-inset-bottom),0.5rem)] shadow-[0_-10px_20px_rgba(0,0,0,0.03)] selection:bg-transparent">
        <div class="flex items-center justify-around h-[64px] pb-1 gap-1">
            
            <router-link to="/instructor/home" class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative active:scale-95 transition-all">
                <!-- Efecto Lámpara / Indicador Superior -->
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300"></div>
                <div class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none"></div>

                <IconHome class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
                <span class="text-[10px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Inicio</span>
            </router-link>

            <router-link to="/instructor/agenda" class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative active:scale-95 transition-all">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300"></div>
                <div class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none"></div>

                <IconCalendar class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
                <span class="text-[10px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Agenda</span>
            </router-link>

            <router-link to="/instructor/qr" class="flex flex-col items-center justify-center group w-[20%] relative active:scale-90 transition-all -mt-6">
                <div class="w-14 h-14 rounded-full bg-linear-to-br from-primary-800 to-primary-600 shadow-[0_6px_24px_rgba(37,99,235,0.45)] flex items-center justify-center ring-4 ring-white">
                    <IconQr class="w-7 h-7 text-white drop-shadow-sm" />
                </div>
                <span class="text-[11px] font-bold text-primary-700 mt-1 tracking-wide">Escanear</span>
            </router-link>

            <!-- Pestaña de Ludoteca Protegida Móvil -->
            <component
                :is="profileStore.tieneTurnoLudotecaHoy ? 'router-link' : 'span'"
                to="/instructor/ludoteca"
                class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative transition-all"
                :class="profileStore.tieneTurnoLudotecaHoy ? 'active:scale-95 cursor-pointer' : 'cursor-not-allowed select-none opacity-40'"
            >
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300"></div>
                <div class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none"></div>

                <IconBaby class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
                <span class="text-[10px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Ludoteca</span>
            </component>

            <router-link to="/instructor/profile" class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative active:scale-95 transition-all">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300"></div>
                <div class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none"></div>

                <IconUser class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
                <span class="text-[10px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Perfil</span>
            </router-link>

        </div>
    </nav>
  </div>
</template>

<style scoped>
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