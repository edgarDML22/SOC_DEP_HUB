<script setup>
import { ref } from 'vue'
import { useProfileStore } from '@/stores/profiles/socioStore'
import { IconHome, IconCalendar, IconTrophy, IconGuests, IconClock, IconUser, IconBell, IconQr } from '@/components/icons';

const profileStore = useProfileStore();

const menuOpen = ref(false)
const notifications = ref(2)
const showNotifications = ref(false)

const toggleMenu = () => {
    menuOpen.value = !menuOpen.value
}

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value
}
</script>

<template>
    <!-- Navbar principal con altura y bordes -->
    <nav class="bg-white min-h-[70px] border-b border-surface-200 w-full">
        <!-- Contenedor interno que abarca todo el ancho pero con padding generoso (px-8 a px-16) -->
        <div class="w-full px-8 lg:px-12 xl:px-16 py-4 flex flex-wrap lg:flex-nowrap justify-between items-center">
        
        <!-- Izquierda: Logo y Marca -->
        <div class="flex items-center gap-4 order-1">
            <img src="../../assets/LogoSocDep.jpg" alt="SOC-DEP HUB" class="h-10 w-10 object-cover rounded-medium block" />
            <span class="font-bold text-[1.2rem] tracking-tight text-surface-900">SOC-DEP HUB</span>
        </div>

        <!-- Derecha: Notificaciones y Perfil -->
        <div class="flex items-center gap-5 order-2 lg:order-3 ml-auto lg:ml-0 relative">
            
            <!-- Notificaciones -->
            <div class="relative">
                <button class="relative bg-transparent border-none text-surface-500 cursor-pointer flex items-center justify-center hover:text-surface-900 transition-colors" @click="toggleNotifications">
                    <IconBell class="w-6 h-6" />
                    <span class="absolute top-[-2px] right-[-4px] bg-red-500 text-white text-[10px] font-bold h-4 w-4 rounded-full flex items-center justify-center border-2 border-white">
                        {{ notifications }}
                    </span>
                </button>

                <!-- Dropdown Notificaciones -->
                <div v-if="showNotifications" class="absolute top-11 right-0 w-56 bg-white rounded-xl border border-surface-200 shadow-lg p-3 z-50">
                    <p class="text-sm text-surface-500 text-center m-0">No hay notificaciones</p>
                </div>
            </div>

            <!-- Avatar y Menú Perfil -->
            <div class="relative">
                <div class="w-10 h-10 bg-primary-700 text-white rounded-full flex items-center justify-center cursor-pointer text-sm font-semibold hover:bg-primary-800 transition-colors" @click="toggleMenu">
                    {{ profileStore.userInitials }}
                </div>

                <!-- Dropdown Perfil -->
                <div v-if="menuOpen" class="absolute top-14 right-0 w-48 bg-white rounded-xl border border-surface-200 shadow-lg flex flex-col overflow-hidden z-50">
                    <router-link to="/socio/profile" class="px-4 py-3 text-sm text-center text-surface-900 hover:bg-surface-100 transition-colors">Perfil</router-link>
                    <router-link to="/socio/configuration" class="px-4 py-3 text-sm text-center text-surface-900 hover:bg-surface-100 transition-colors">Configuración</router-link>
                    <button @click="profileStore.getSupportLink" class="px-4 py-3 text-sm text-center text-surface-900 bg-transparent border-none cursor-pointer hover:bg-surface-100 transition-colors">Ayuda</button>
                    <hr class="m-0 border-t border-surface-200" />
                    <button class="logout px-4 py-3 text-sm text-center text-red-500 font-bold bg-transparent border-none cursor-pointer hover:bg-red-50 transition-colors" @click="profileStore.logout">Cerrar sesión</button>
                </div>
            </div>

        </div>

        <!-- Centro: Links de Navegación -->
        <div class="flex gap-2 lg:gap-8 order-3 lg:order-2 w-full lg:w-auto mt-4 lg:mt-0 pt-3 lg:pt-0 border-t lg:border-t-0 border-surface-200 justify-start lg:justify-center overflow-x-auto lg:overflow-visible pb-2 lg:pb-0 scrollbar-thin">
            
            <router-link to="/socio/home" class="flex items-center gap-1.5 no-underline text-surface-500 text-sm font-medium px-3 py-2 rounded-lg transition-colors hover:bg-surface-100 hover:text-surface-900 whitespace-nowrap shrink-0 [&.router-link-active]:bg-blue-50 [&.router-link-active]:text-primary-700">
                <IconHome class="w-[18px] h-[18px]" /> Inicio
            </router-link>

            <router-link to="/socio/reservations" 
                class="flex items-center gap-1.5 no-underline text-surface-500 text-sm font-medium px-3 py-2 rounded-lg transition-colors hover:bg-surface-100 hover:text-surface-900 whitespace-nowrap shrink-0 [&.router-link-active]:bg-blue-50 [&.router-link-active]:text-primary-700"
                :class="{ 'pointer-events-none opacity-40 grayscale cursor-not-allowed': profileStore.isAccountInactive }">
                <IconCalendar class="w-[18px] h-[18px]"/> Reservas
            </router-link>

            <router-link to="/socio/tournaments" class="flex items-center gap-1.5 no-underline text-surface-500 text-sm font-medium px-3 py-2 rounded-lg transition-colors hover:bg-surface-100 hover:text-surface-900 whitespace-nowrap shrink-0 [&.router-link-active]:bg-blue-50 [&.router-link-active]:text-primary-700">
                <IconTrophy class="w-[18px] h-[18px]" /> Torneos
            </router-link>

            <router-link to="/socio/community" class="flex items-center gap-1.5 no-underline text-surface-500 text-sm font-medium px-3 py-2 rounded-lg transition-colors hover:bg-surface-100 hover:text-surface-900 whitespace-nowrap shrink-0 [&.router-link-active]:bg-blue-50 [&.router-link-active]:text-primary-700">
                <IconGuests class="w-[18px] h-[18px]" /> Comunidad
            </router-link>

            <router-link to="/socio/history" class="flex items-center gap-1.5 no-underline text-surface-500 text-sm font-medium px-3 py-2 rounded-lg transition-colors hover:bg-surface-100 hover:text-surface-900 whitespace-nowrap shrink-0 [&.router-link-active]:bg-blue-50 [&.router-link-active]:text-primary-700">
                <IconClock class="w-[18px] h-[18px]" /> Historial
            </router-link>

           <router-link to="/socio/qr" 
                class="flex items-center gap-1.5 no-underline text-surface-500 text-sm font-medium px-3 py-2 rounded-lg transition-colors hover:bg-surface-100 hover:text-surface-900 whitespace-nowrap shrink-0 [&.router-link-active]:bg-blue-50 [&.router-link-active]:text-primary-700"
                :class="{ 'pointer-events-none opacity-40 grayscale cursor-not-allowed': profileStore.isAccountInactive }">
                <IconQr class="w-[18px] h-[18px]"/> QR
            </router-link>

            <router-link to="/socio/profile" class="flex items-center gap-1.5 no-underline text-surface-500 text-sm font-medium px-3 py-2 rounded-lg transition-colors hover:bg-surface-100 hover:text-surface-900 whitespace-nowrap shrink-0 [&.router-link-active]:bg-blue-50 [&.router-link-active]:text-primary-700">
                <IconUser class="w-[18px] h-[18px]" /> Perfil
            </router-link>

        </div>
        </div>
    </nav>
</template>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
    height: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 10px;
}
</style>