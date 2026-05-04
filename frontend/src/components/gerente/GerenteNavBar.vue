<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { IconHome, IconCalendar, IconClock, IconUser, IconBell, IconBaby, IconTrophy, IconGuests } from '@/components/icons';
import { useAdminStore } from '@/stores/profiles/adminStore'

import { useAlerts } from '@/composables/useAlerts'

const router = useRouter()
const route = useRoute()
const profileStore = useAdminStore()
const { showLoading, closeLoading } = useAlerts()

const isMoreActive = computed(() => {
    return ['/admin/tournaments', '/admin/spaces', '/admin/reports'].some(path => route.path.includes(path))
})

const menuOpen = ref(false)
const moreMenuOpen = ref(false)
const notifications = ref(2)
const showNotifications = ref(false)

const profileDropdown = ref(null)
const moreDropdown = ref(null)
const notifDropdownDesktop = ref(null)
const notifDropdownMobile = ref(null)

const toggleMenu = () => {
    menuOpen.value = !menuOpen.value
    if (menuOpen.value) { showNotifications.value = false; moreMenuOpen.value = false; }
}

const toggleMoreMenu = () => {
    moreMenuOpen.value = !moreMenuOpen.value
    if (moreMenuOpen.value) { showNotifications.value = false; menuOpen.value = false; }
}

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value
    if (showNotifications.value) { menuOpen.value = false; moreMenuOpen.value = false; }
}

const handleClickOutside = (event) => {
    if (profileDropdown.value && !profileDropdown.value.contains(event.target)) menuOpen.value = false;
    if (moreDropdown.value && !moreDropdown.value.contains(event.target)) moreMenuOpen.value = false;

    const clickedDesktopNotif = notifDropdownDesktop.value && notifDropdownDesktop.value.contains(event.target);
    const clickedMobileNotif = notifDropdownMobile.value && notifDropdownMobile.value.contains(event.target);
    if (!clickedDesktopNotif && !clickedMobileNotif) showNotifications.value = false;
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

const logout = async () => {
    showLoading('Cerrando sesión...')
    await new Promise(r => setTimeout(r, 400)) // Pequeño delay para que la UI renderice el spinner
    await profileStore.logout()
    closeLoading()
}
</script>

<template>
    <div class="font-sans">
        <!-- ESPACIADORES PARA EVITAR SUPERPOSICIÓN -->
        <div class="hidden lg:block h-[90px] w-full"></div>
        <div class="lg:hidden h-[68px] w-full"></div>

        <!-- ========================================= -->
        <!-- DESKTOP: FLOATING ISLAND NAVIGATION       -->
        <!-- ========================================= -->
        <nav
            class="hidden lg:flex w-full fixed top-0 z-100 px-4 pt-4 pb-4 backdrop-blur-sm pointer-events-none justify-center">
            <div
                class="pointer-events-auto w-full max-w-7xl rounded-2xl bg-white/80 backdrop-blur-md shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-surface-200/50 px-6 py-2.5 flex items-center justify-between transition-all">

                <!-- Izquierda: Logo -->
                <router-link to="/admin/dashboard" class="flex items-center gap-3 shrink-0 group">
                    <div
                        class="p-1 bg-white rounded-xl shadow-sm border border-surface-100 group-hover:scale-105 transition-transform">
                        <img src="../../assets/LogoSocDep.jpg" alt="SOC-DEP HUB"
                            class="h-8 w-8 object-cover rounded-lg" />
                    </div>
                    <span
                        class="font-bold text-lg tracking-tight text-surface-900 group-hover:text-primary-600 transition-colors">Admin</span>
                </router-link>

                <!-- Centro: Enlaces -->
                <div class="flex items-center gap-1 xl:gap-2 justify-center flex-1 mx-4 overflow-x-auto no-scrollbar">
                    <router-link to="/admin/dashboard"
                        class="group flex items-center gap-2 text-sm font-medium text-surface-500 px-3 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold whitespace-nowrap">
                        <IconHome class="w-4 h-4 group-[.router-link-active]:text-white" /> Dashboard
                    </router-link>
                    <router-link to="/admin/reservations"
                        class="group flex items-center gap-2 text-sm font-medium text-surface-500 px-3 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold whitespace-nowrap">
                        <IconCalendar class="w-4 h-4 group-[.router-link-active]:text-white" /> Reservas
                    </router-link>
                    <router-link to="/admin/tournaments"
                        class="group flex items-center gap-2 text-sm font-medium text-surface-500 px-3 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold whitespace-nowrap">
                        <IconTrophy class="w-4 h-4 group-[.router-link-active]:text-white" /> Torneos
                    </router-link>
                    <router-link to="/admin/spaces"
                        class="group flex items-center gap-2 text-sm font-medium text-surface-500 px-3 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold whitespace-nowrap">
                        <IconHome class="w-4 h-4 group-[.router-link-active]:text-white" /> Espacios
                    </router-link>
                    <router-link to="/admin/instructors"
                        class="group flex items-center gap-2 text-sm font-medium text-surface-500 px-3 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold whitespace-nowrap">
                        <IconUser class="w-4 h-4 group-[.router-link-active]:text-white" /> Instructores
                    </router-link>
                    <router-link to="/admin/ludoteca/ludoteca-list"
                        class="group flex items-center gap-2 text-sm font-medium text-surface-500 px-3 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold whitespace-nowrap">
                        <IconBaby class="w-4 h-4 group-[.router-link-active]:text-white" /> Ludoteca
                    </router-link>
                    <router-link to="/admin/reports"
                        class="group flex items-center gap-2 text-sm font-medium text-surface-500 px-3 py-2 rounded-xl transition-all active:scale-95 hover:bg-surface-100 hover:text-surface-900 [&.router-link-active]:bg-primary-600 [&.router-link-active]:text-white [&.router-link-active]:shadow-md [&.router-link-active]:font-bold whitespace-nowrap">
                        <IconGuests class="w-4 h-4 group-[.router-link-active]:text-white" /> Reportes
                    </router-link>
                </div>

                <!-- Derecha: Perfil & Notificaciones -->
                <div class="flex items-center gap-3 shrink-0">
                    <!-- Notificaciones -->
                    <div class="relative" ref="notifDropdownDesktop">
                        <button
                            class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 hover:bg-surface-100 hover:text-surface-900 active:scale-95 transition-all"
                            @click="toggleNotifications">
                            <IconBell class="w-5 h-5" />
                            <span v-if="notifications > 0"
                                class="absolute top-2 right-2.5 bg-primary-600 text-white text-[10px] font-bold h-2 w-2 rounded-full border border-surface-50 ring-[1.5px] ring-white"></span>
                        </button>
                        <div v-if="showNotifications"
                            class="absolute top-14 right-0 w-72 bg-white rounded-2xl border border-surface-100 shadow-[0_15px_50px_rgba(0,0,0,0.1)] p-5 z-50 transition-all">
                            <h4 class="font-bold text-lg text-surface-900 mb-2">Notificaciones</h4>
                            <div class="h-px w-full bg-surface-100 mb-4"></div>
                            <p class="text-sm font-medium text-surface-400 text-center py-6">No hay notificaciones</p>
                        </div>
                    </div>

                    <!-- Avatar -->
                    <div class="relative" ref="profileDropdown">
                        <button
                            class="w-10 h-10 bg-primary-600 outline-2 outline-offset-2 outline-transparent hover:outline-primary-200 text-white rounded-xl shadow-inner flex items-center justify-center text-sm font-bold hover:scale-105 active:scale-95 transition-all"
                            @click="toggleMenu">
                            <span>{{ profileStore.userInitials || 'A' }}</span>
                        </button>
                        <div v-if="menuOpen"
                            class="absolute top-14 right-0 w-64 bg-white rounded-2xl border border-surface-100 shadow-[0_15px_50px_rgba(0,0,0,0.1)] p-3 z-50 flex flex-col gap-1 transition-all">
                            <div class="px-4 py-3 bg-surface-50 rounded-xl mb-2 border border-surface-100">
                                <span
                                    class="block text-xs font-medium text-surface-500 uppercase tracking-wider mb-1">Mi
                                    Cuenta</span>
                                <strong class="block text-sm font-bold text-surface-900">Gerente / Admin</strong>
                            </div>
                            <div class="h-px bg-surface-100 w-full my-1 rounded-full"></div>
                            <button
                                class="px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors text-left flex items-center gap-3 w-full"
                                @click="logout">
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

        <!-- Top Bar (Mobile) -->
        <div
            class="lg:hidden fixed top-0 left-0 w-full px-5 py-3 bg-white/90 backdrop-blur-xl border-b border-surface-200 z-110 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <img src="../../assets/LogoSocDep.jpg"
                    class="w-9 h-9 rounded-lg shadow-sm border border-surface-100 object-cover" />
                <span class="font-bold text-lg text-surface-900 tracking-tight">Admin SOC-DEP</span>
            </div>

            <div class="relative" ref="notifDropdownMobile">
                <button
                    class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 active:scale-95 hover:bg-surface-100 transition-all relative"
                    @click="toggleNotifications">
                    <IconBell class="w-5 h-5" />
                    <span v-if="notifications > 0"
                        class="absolute top-2 right-2.5 bg-primary-600 text-white text-[10px] font-bold h-2 w-2 rounded-full border border-surface-50 ring-[1.5px] ring-white"></span>
                </button>
                <div v-if="showNotifications"
                    class="absolute top-12 right-0 w-72 bg-white rounded-2xl border border-surface-200 shadow-2xl p-5 z-60 animate-fade-in">
                    <h4 class="font-bold text-lg text-surface-900 mb-2">Notificaciones</h4>
                    <div class="h-px w-full bg-surface-100 mb-4"></div>
                    <p class="text-sm font-medium text-surface-400 text-center py-4">No hay notificaciones</p>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation Bar (Mobile) -->
        <nav
            class="lg:hidden fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-surface-200 z-100 px-2 pt-2 pb-[max(env(safe-area-inset-bottom),0.5rem)] shadow-[0_-10px_20px_rgba(0,0,0,0.03)] selection:bg-transparent">
            <div class="flex items-center justify-around h-[64px] pb-1 gap-1">

                <router-link to="/admin/dashboard"
                    class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative active:scale-95 transition-all">
                    <div
                        class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300">
                    </div>
                    <div
                        class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none">
                    </div>

                    <IconHome
                        class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
                    <span
                        class="text-[10px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Inicio</span>
                </router-link>

                <router-link to="/admin/reservations"
                    class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative active:scale-95 transition-all">
                    <div
                        class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300">
                    </div>
                    <div
                        class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none">
                    </div>

                    <IconCalendar
                        class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
                    <span
                        class="text-[10px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Reservas</span>
                </router-link>

                <!-- Botón Central Dinámico: Menú Más -->
                <div class="flex flex-col items-center justify-center group w-[20%] relative transition-all"
                    :class="(moreMenuOpen || isMoreActive) ? '-mt-6 active:scale-90' : 'h-full rounded-xl active:scale-95'"
                    ref="moreDropdown">

                    <button @click="toggleMoreMenu" class="flex flex-col items-center justify-center w-full h-full"
                        :class="(moreMenuOpen || isMoreActive) ? '' : 'gap-1'">
                        <div class="flex items-center justify-center transition-all duration-300"
                            :class="(moreMenuOpen || isMoreActive) ? 'w-14 h-14 rounded-full shadow-[0_6px_24px_rgba(37,99,235,0.45)] ring-4 ring-white bg-linear-to-br from-primary-800 to-primary-600' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg" class="transition-colors duration-300"
                                :class="(moreMenuOpen || isMoreActive) ? 'w-7 h-7 text-white drop-shadow-sm' : 'w-[22px] h-[22px] text-surface-400 group-hover:text-surface-600'"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <line x1="3" y1="12" x2="21" y2="12" />
                                <line x1="3" y1="6" x2="21" y2="6" />
                                <line x1="3" y1="18" x2="21" y2="18" />
                            </svg>
                        </div>
                        <span class="transition-all duration-300"
                            :class="(moreMenuOpen || isMoreActive) ? 'text-[9px] font-bold mt-1 uppercase tracking-wide text-primary-700' : 'text-[10px] font-medium text-surface-500 group-hover:text-surface-700'">Más</span>
                    </button>

                    <div v-if="moreMenuOpen"
                        class="absolute bottom-20 left-1/2 -translate-x-1/2 w-48 bg-white rounded-2xl border border-surface-200 shadow-2xl py-2 z-60 animate-fade-in origin-bottom">
                        <router-link to="/admin/tournaments"
                            class="group px-4 py-3 flex items-center gap-3 hover:bg-surface-50 text-surface-700 font-medium text-sm transition-colors [&.router-link-active]:bg-primary-50 [&.router-link-active]:text-primary-700 [&.router-link-active]:font-bold"
                            @click="moreMenuOpen = false">
                            <IconTrophy class="w-4 h-4 text-surface-400 group-[.router-link-active]:text-primary-500" /> Torneos
                        </router-link>
                        <router-link to="/admin/spaces"
                            class="group px-4 py-3 flex items-center gap-3 hover:bg-surface-50 text-surface-700 font-medium text-sm transition-colors [&.router-link-active]:bg-primary-50 [&.router-link-active]:text-primary-700 [&.router-link-active]:font-bold"
                            @click="moreMenuOpen = false">
                            <IconHome class="w-4 h-4 text-surface-400 group-[.router-link-active]:text-primary-500" /> Espacios
                        </router-link>
                        <router-link to="/admin/reports"
                            class="group px-4 py-3 flex items-center gap-3 hover:bg-surface-50 text-surface-700 font-medium text-sm transition-colors [&.router-link-active]:bg-primary-50 [&.router-link-active]:text-primary-700 [&.router-link-active]:font-bold"
                            @click="moreMenuOpen = false">
                            <IconGuests class="w-4 h-4 text-surface-400 group-[.router-link-active]:text-primary-500" /> Reportes
                        </router-link>
                        <div class="h-px bg-surface-100 mx-3 my-1"></div>
                        <button
                            class="px-4 py-3 w-full flex items-center gap-3 hover:bg-red-50 text-red-600 font-bold text-sm transition-colors text-left"
                            @click="logout">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <polyline points="16 17 21 12 16 7" />
                                <line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
                            Cerrar Sesión
                        </button>
                    </div>
                </div>

                <router-link to="/admin/ludoteca/ludoteca-list"
                    class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative active:scale-95 transition-all">
                    <div
                        class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300">
                    </div>
                    <div
                        class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none">
                    </div>

                    <IconBaby
                        class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
                    <span
                        class="text-[10px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Ludoteca</span>
                </router-link>

                <router-link to="/admin/instructors"
                    class="flex flex-col items-center justify-center gap-1 group w-[20%] h-full relative active:scale-95 transition-all">
                    <div
                        class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-b-full bg-primary-600 opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300">
                    </div>
                    <div
                        class="absolute top-[3px] left-1/2 -translate-x-1/2 w-12 h-[35px] bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-primary-600/20 to-transparent opacity-0 group-[.router-link-active]:opacity-100 transition-all duration-300 pointer-events-none">
                    </div>

                    <IconUser
                        class="w-[22px] h-[22px] text-surface-400 group-hover:text-primary-500 group-[.router-link-active]:text-primary-600 transition-colors relative z-10" />
                    <span
                        class="text-[10px] font-medium text-surface-500 group-hover:text-primary-600 group-[.router-link-active]:text-primary-700 group-[.router-link-active]:font-bold transition-colors relative z-10">Personal</span>
                </router-link>


            </div>
        </nav>
    </div>
</template>