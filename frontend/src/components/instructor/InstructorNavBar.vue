<script setup>
import { ref } from 'vue'
import { useProfileStore } from '@/stores/profileStore'
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
    <nav class="top-navbar">
        <div class="navbar-left">
            <img src="../../assets/LogoSocDep.jpg" alt="SOC-DEP HUB" class="brand-logo" />
            <span class="brand-name">SOC-DEP HUB</span>
        </div>

        <div class="navbar-right">

            <div class="notification-wrapper">
                <button class="notification-btn" @click="toggleNotifications">
                    <IconBell />

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

    <nav class="bottom-navbar">
        <router-link to="/instructor/home" class="nav-item">
            <div class="icon-wrapper">
                <IconHome class="icon" />
            </div>
            <span>Inicio</span>
        </router-link>

        <router-link to="/instructor/agenda" class="nav-item">
            <div class="icon-wrapper">
                <IconCalendar class="icon" />
            </div>
            <span>Agenda</span>
        </router-link>

        <router-link to="/instructor/sessions" class="nav-item">
            <div class="icon-wrapper">
                <IconClock class="icon" />
            </div>
            <span>Sesiones</span>
        </router-link>

        <router-link to="/instructor/profile" class="nav-item">
            <div class="icon-wrapper">
                <IconUser class="icon" />
            </div>
            <span>Perfil</span>
        </router-link>
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
    color: var(--p-surface-500);
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
    border: 1px solid var(--p-surface-200);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    padding: 12px;
}



.top-navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #ffffff;
    padding: 0 2rem;
    min-height: 70px;
    border-bottom: 1px solid var(--p-surface-200);
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
    border-radius: var(--p-border-radius);
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
    color: var(--p-surface-500);
    font-size: 14px;
    font-weight: 500;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.2s;
}

.nav-link:hover {
    background-color: var(--p-surface-100);
    color: #111827;
}

.nav-link.router-link-active {
    background-color: var(--p-primary-100);
    color: var(--p-primary-700);
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
    color: var(--p-surface-500);
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
    background-color: var(--p-primary-700);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
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
    border: 1px solid var(--p-surface-200);
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
    background: var(--p-surface-100);
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

@media (min-width: 1050px) {
    .qr-link {
        display: none !important;
    }
}

/* DISEÑO PARA MÓVILES (Menos de 1050px)*/
@media (max-width: 1049px) {
    .top-navbar {
        padding: 10px 1rem;

    }

    .navbar-left {
        order: 1;
    }

    .navbar-right {
        order: 2;
        margin-left: auto;
    }

    .navbar-center {
        order: 3;
        width: 100%;
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid var(--p-surface-200);
        justify-content: flex-start;
        overflow-x: auto;

        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        padding-bottom: 8px;
    }

    .navbar-center::-webkit-scrollbar {
        height: 6px;
    }

    .navbar-center::-webkit-scrollbar-track {
        background: transparent;
    }

    .navbar-center::-webkit-scrollbar-thumb {
        background-color: #e2e8f0;
        border-radius: 10px;
    }

    .navbar-center::-webkit-scrollbar-thumb:hover {
        background-color: #cbd5e1;
    }

    .nav-link {
        white-space: nowrap;
        flex-shrink: 0;
    }
}

/* Contenedor principal fijado en la parte inferior */
.bottom-navbar {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #ffffff;
    display: flex;
    justify-content: space-around;
    /* Distribuye equitativamente */
    align-items: center;
    padding: 8px 0 12px 0;
    /* Espaciado superior e inferior */
    border-top: 1px solid var(--p-surface-200, #e5e7eb);
    /* Borde sutil arriba */
    box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.03);
    /* Sombra ligera para resaltar */
    z-index: 1000;
}

/* Estilo individual de cada botón */
.nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: var(--p-surface-500, #6b7280);
    /* Color gris inactivo */
    gap: 4px;
    /* Espacio entre icono y texto */
    min-width: 64px;
}

/* Contenedor del icono (para el fondo azul cuando está activo) */
.icon-wrapper {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    /* Bordes redondeados como en la imagen */
    transition: all 0.2s ease-in-out;
}

.icon {
    width: 22px;
    height: 22px;
}

/* Texto debajo del icono */
.nav-item span {
    font-size: 11px;
    font-weight: 500;
    font-family: inherit;
}

/* =========================================
   ESTADOS ACTIVOS (El equivalente a "Inicio" azul)
   ========================================= */
.nav-item.router-link-active {
    color: var(--p-primary-600, #2563eb);
    /* Texto azul */
}

.nav-item.router-link-active .icon-wrapper {
    background-color: var(--p-primary-100, #dbeafe);
    /* Fondo azul clarito */
    color: var(--p-primary-600, #2563eb);
    /* Icono azul */
}
</style>