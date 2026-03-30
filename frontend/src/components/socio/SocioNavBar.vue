<script setup>
import { ref } from 'vue'
import { useProfileStore } from '@/stores/profileStore'

const profileStore = useProfileStore();

const menuOpen = ref(false)
const notifications = ref(2)


const toggleMenu = () => {
    menuOpen.value = !menuOpen.value
}

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value
}
</script>

<template>
    <nav class="top-navbar">
        <div class="navbar-left">
            <img src="../../assets/LogoSocDep.jpg" alt="SOC-DEP HUB" class="brand-logo" />
            <span class="brand-name">SOC-DEP HUB</span>
        </div>

        <div class="navbar-center">
            <router-link to="/socio/home" class="nav-link">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                    </path>
                </svg>
                Inicio
            </router-link>

            <router-link to="/socio/reservations" class="nav-link">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                Reservas
            </router-link>

            <router-link to="/socio/tournaments" class="nav-link">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                    </path>
                </svg>
                Torneos
            </router-link>

            <router-link to="/socio/guests" class="nav-link">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
                Invitados
            </router-link>

            <router-link to="/socio/history" class="nav-link">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Historial
            </router-link>


            <router-link to="/socio/profile" class="nav-link">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Perfil
            </router-link>
        </div>


        <div class="navbar-right">

            <div class="notification-wrapper">
                <button class="notification-btn" @click="toggleNotifications">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                        </path>
                    </svg>

                    <span class="notification-badge">{{ notifications }}</span>
                </button>

                <div v-if="showNotifications" class="notification-dropdown">
                    <p class="empty">No hay notificaciones</p>
                </div>
            </div>

            <div class="avatar-wrapper">
                <div class="nav-avatar" @click="toggleMenu">
                    {{ profileStore.userInitials }}
                </div>

            </div>

            <div v-if="menuOpen" class="dropdown">
                <router-link to="/socio/profile">Perfil</router-link>
                <router-link to="/socio/configuration">Configuración</router-link>
                <button @click="profileStore.getSupportLink">Ayuda</button>
                <hr />
                <button class="logout" @click="profileStore.logout">Cerrar sesión</button>
            </div>

        </div>
    </nav>

</template>

<style scoped>
.notification-wrapper {
    position: relative;
}

.notification-btn {
    cursor: pointer;
    position: relative;
    background: none;
    border: none;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-btn svg {
    width: 20px;
    height: 20px;
}

.notification-btn:hover {
    color: #111827;
}

.notification-badge {
    position: absolute;
    top: -3px;
    right: -5px;
    background: #ef4444;
    color: white;
    font-size: 9px;
    border-radius: 999px;
    height: 15px;
    width: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    border: 2px solid white;
}

.notification-dropdown {
    position: absolute;
    top: 45px;
    right: 0;
    width: 220px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    padding: 12px;
}

.nav-avatar {
    width: 34px;
    height: 34px;
    background: #2563eb;
    color: white;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
}

.top-navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #ffffff;
    padding: 0 2rem;
    min-height: 70px;
    border-bottom: 1px solid #e5e7eb;
    flex-wrap: wrap;
    /* Permite que los elementos bajen en pantallas chicas */
}

.navbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Ajustes del Logo */
.brand-logo {
    height: 36px;
    width: 36px;
    object-fit: cover;
    border-radius: 8px;
    display: block;
}

.brand-name {
    font-weight: 700;
    font-size: 1.2rem;
    letter-spacing: -0.5px;
    color: #111827;
}

.navbar-center {
    display: flex;
    gap: 1.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.2s;
}

.nav-link:hover {
    background-color: #f3f4f6;
    color: #111827;
}

.nav-link.router-link-active {
    background-color: #e0e7ff;
    color: #1d4ed8;
}

.nav-link .icon {
    width: 18px;
    height: 18px;
}

.navbar-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.notification-btn {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-btn svg {
    width: 24px;
    height: 24px;
}

.notification-badge {
    position: absolute;
    top: -2px;
    right: -4px;
    background-color: #ef4444;
    color: white;
    font-size: 10px;
    font-weight: bold;
    height: 16px;
    width: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
}

.nav-avatar {
    width: 36px;
    height: 36px;
    background-color: #1d4ed8;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
}

.dropdown {
    position: absolute;
    top: 60px;
    right: 20px;
    width: 200px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.dropdown button,
.dropdown a {
    padding: 12px 14px;
    border: none;
    background: white;
    cursor: pointer;
    color: #111827;
    text-decoration: none;
    text-align: center;
    font-size: 14px;
    font-family: inherit;
}


.dropdown button:hover,
.dropdown a:hover {
    background: #f3f4f6;
}

.dropdown a.router-link-exact-active {
    pointer-events: none;
    color: #9ca3af;
    background: transparent;
}

.dropdown .logout {
    color: #ef4444;
    font-weight: bold;
}
</style>