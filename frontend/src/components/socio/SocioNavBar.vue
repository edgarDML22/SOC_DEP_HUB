<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useProfileStore } from '@/stores/profiles/socioStore'
import { IconHome, IconCalendar, IconTrophy, IconGuests, IconClock, IconUser, IconBell, IconQr } from '@/components/icons';

const profileStore = useProfileStore();

const menuOpen = ref(false)
const notifications = ref(2)
const showNotifications = ref(false)

const profileDropdown = ref(null)
const notifDropdown = ref(null)

const toggleMenu = () => {
    menuOpen.value = !menuOpen.value;
    if (menuOpen.value) showNotifications.value = false;
}

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value;
    if (showNotifications.value) menuOpen.value = false;
}

const handleClickOutside = (event) => {
    // Si el clic no fue dentro del profile dropdown, lo cerramos
    if (profileDropdown.value && !profileDropdown.value.contains(event.target)) {
        menuOpen.value = false;
    }
    // Si el clic no fue dentro del dropdown de notificaciones, lo cerramos
    if (notifDropdown.value && !notifDropdown.value.contains(event.target)) {
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
    <!-- ESPACIADORES POUR EVITAR SUPERPOSICIÓN -->
    <div class="hidden md:block h-[90px] w-full"></div>
    <div class="md:hidden h-[68px] w-full"></div>

    <!-- ========================================= -->
    <!-- DESKTOP: FLOATING ISLAND NAVIGATION       -->
    <!-- ========================================= -->
    <nav class="hidden md:flex w-full fixed top-0 z-[100] px-4 pt-4 pb-4 backdrop-blur-sm pointer-events-none justify-center">
      <div class="pointer-events-auto w-full max-w-5xl rounded-2xl bg-white/80 backdrop-blur-md shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-surface-200/50 px-6 py-2.5 flex items-center justify-between transition-all">
        
        <!-- Izquierda: Logo -->
        <router-link to="/socio/home" class="flex items-center gap-3 shrink-0 group">
          <div class="p-1 bg-white rounded-xl shadow-sm border border-surface-100 group-hover:scale-105 transition-transform">
              <img src="../../assets/LogoSocDep.jpg" alt="SOC-DEP HUB" class="h-8 w-8 object-cover rounded-lg" />
          </div>
          <span class="font-bold text-lg tracking-tight text-surface-900 group-hover:text-primary-600 transition-colors">SOC-DEP</span>
        </router-link>

        <!-- Centro: Enlaces (Isla de navegación) con Active State Notorio -->
        <div class="flex items-center gap-1 lg:gap-2 justify-center flex-1 mx-4">
            <router-link to="/socio/home" class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
                <IconHome class="w-[18px] h-[18px] group-[.router-link-active]:text-white" /> Inicio
            </router-link>

            <router-link to="/socio/agenda" 
                class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold"
                :class="{ 'opacity-50 cursor-not-allowed': profileStore.isAccountInactive }">
                <IconCalendar class="w-[18px] h-[18px] group-[.router-link-active]:text-white"/> Agenda
            </router-link>

            <router-link to="/socio/tournaments" class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
                <IconTrophy class="w-[18px] h-[18px] group-[.router-link-active]:text-white" /> Torneos
            </router-link>

            <router-link to="/socio/community" class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold">
                <IconGuests class="w-[18px] h-[18px] group-[.router-link-active]:text-white" /> Amigos
            </router-link>

            <router-link to="/socio/qr" 
                class="group flex items-center gap-2 font-medium text-surface-500 px-4 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold"
                :class="{ 'opacity-50 cursor-not-allowed': profileStore.isAccountInactive }">
                <IconQr class="w-[18px] h-[18px] group-[.router-link-active]:text-white"/> Código QR
            </router-link>
        </div>

        <!-- Derecha: Perfil & Notificaciones -->
        <div class="flex items-center gap-3 shrink-0">
            <!-- Notificaciones -->
            <div class="relative" ref="notifDropdown">
                <button class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 hover:bg-surface-100 hover:text-surface-900 active:scale-95 transition-all" @click="toggleNotifications">
                    <IconBell class="w-5 h-5" />
                    <span v-if="notifications > 0" class="absolute top-2 right-2.5 bg-primary-600 text-white text-[10px] font-bold h-2 w-2 rounded-full border border-surface-50 ring-[1.5px] ring-white"></span>
                </button>

                <div v-if="showNotifications" class="absolute top-14 right-0 w-72 bg-white rounded-2xl border border-surface-100 shadow-[0_15px_50px_rgba(0,0,0,0.1)] p-5 z-50 transition-all">
                    <h4 class="font-bold text-lg text-surface-900 mb-2">Notificaciones</h4>
                    <div class="h-px w-full bg-surface-100 mb-4"></div>
                    <p class="text-sm font-medium text-surface-400 text-center py-6">No hay notificaciones nuevas</p>
                </div>
            </div>

            <!-- Avatar -->
            <div class="relative" ref="profileDropdown">
                <button class="w-10 h-10 bg-primary-600 outline outline-2 outline-offset-2 outline-transparent hover:outline-primary-200 text-white rounded-xl shadow-inner flex items-center justify-center text-sm font-bold hover:scale-105 active:scale-95 transition-all" @click="toggleMenu">
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
                    
                    <div class="h-px bg-surface-100 w-full my-1 rounded-full"></div>
                    
                    <button class="px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors text-left flex items-center gap-3" @click="profileStore.logout">
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
    <div class="md:hidden fixed top-0 left-0 w-full px-5 py-3 bg-white/90 backdrop-blur-xl border-b border-surface-200 z-[110] flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
            <img src="../../assets/LogoSocDep.jpg" class="w-9 h-9 rounded-lg shadow-sm border border-surface-100 object-cover" />
            <span class="font-bold text-lg text-surface-900 tracking-tight">SOC-DEP</span>
        </div>
        
        <div class="relative" ref="notifDropdown">
            <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 active:scale-95 hover:bg-surface-100 transition-all relative" @click="toggleNotifications">
                <IconBell class="w-5 h-5" />
                <span v-if="notifications > 0" class="absolute top-2 right-2.5 bg-primary-600 text-white text-[10px] font-bold h-2 w-2 rounded-full border border-surface-50 ring-[1.5px] ring-white"></span>
            </button>

            <!-- Notificaciones Dropdown (Móvil) -->
            <div v-if="showNotifications" class="absolute top-12 right-0 w-72 bg-white rounded-2xl border border-surface-200 shadow-2xl p-5 z-60 animate-fade-in">
               <h4 class="font-bold text-lg text-surface-900 mb-2">Notificaciones</h4>
               <div class="h-px w-full bg-surface-100 mb-4"></div>
               <p class="text-sm font-medium text-surface-400 text-center py-4">No hay notificaciones</p>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation Bar (Mobile) -->
    <nav class="md:hidden fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-surface-200 z-[100] px-2 pt-2 pb-[max(env(safe-area-inset-bottom),0.5rem)] shadow-[0_-10px_20px_rgba(0,0,0,0.03)] selection:bg-transparent">
        <div class="flex items-center justify-between h-[64px] pb-1 gap-1">
            
            <router-link to="/socio/home" class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative rounded-xl active:scale-95 transition-all [&.router-link-active]:bg-primary-600 [&.router-link-active]:shadow-md">
                <IconHome class="w-[22px] h-[22px] text-surface-400 group-hover:text-surface-600 group-[.router-link-active]:text-white transition-colors" />
                <span class="text-[12px] font-medium text-surface-500 group-hover:text-surface-700 group-[.router-link-active]:text-white group-[.router-link-active]:font-bold transition-colors">Inicio</span>
            </router-link>

            <router-link to="/socio/agenda" class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative rounded-xl active:scale-95 transition-all [&.router-link-active]:bg-primary-600 [&.router-link-active]:shadow-md">
                <IconCalendar class="w-[22px] h-[22px] text-surface-400 group-hover:text-surface-600 group-[.router-link-active]:text-white transition-colors" />
                <span class="text-[12px] font-medium text-surface-500 group-hover:text-surface-700 group-[.router-link-active]:text-white group-[.router-link-active]:font-bold transition-colors">Agenda</span>
            </router-link>

            <router-link to="/socio/qr" class="flex flex-col items-center justify-center group w-[20%] relative active:scale-90 transition-all -mt-6">
                <!-- Círculo con degradado premium (mismo que PRÓXIMA RESERVA) -->
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-primary-800 to-primary-600 shadow-[0_6px_24px_rgba(37,99,235,0.45)] flex items-center justify-center ring-4 ring-white">
                    <IconQr class="w-7 h-7 text-white drop-shadow-sm" />
                </div>
                <span class="text-[11px] font-bold text-primary-700 mt-1 tracking-wide"> Código QR</span>
            </router-link>

            <router-link to="/socio/community" class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative rounded-xl active:scale-95 transition-all [&.router-link-active]:bg-primary-600 [&.router-link-active]:shadow-md">
                <IconGuests class="w-[22px] h-[22px] text-surface-400 group-hover:text-surface-600 group-[.router-link-active]:text-white transition-colors" />
                <span class="text-[12px] font-medium text-surface-500 group-hover:text-surface-700 group-[.router-link-active]:text-white group-[.router-link-active]:font-bold transition-colors">Amigos</span>
            </router-link>

            <!-- Ruta explícita hacia el perfil garantizada -->
            <router-link to="/socio/profile" class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative rounded-xl active:scale-95 transition-all [&.router-link-active]:bg-primary-600 [&.router-link-active]:shadow-md">
                <IconUser class="w-[22px] h-[22px] text-surface-400 group-hover:text-surface-600 group-[.router-link-active]:text-white transition-colors" />
                <span class="text-[12px] font-medium text-surface-500 group-hover:text-surface-700 group-[.router-link-active]:text-white group-[.router-link-active]:font-bold transition-colors">Perfil</span>
            </router-link>

        </div>
    </nav>
  </div>
</template>