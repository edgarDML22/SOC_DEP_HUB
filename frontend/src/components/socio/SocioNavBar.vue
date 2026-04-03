<script setup>
import { ref } from 'vue'
import { useProfileStore } from '@/stores/profileStore'
import { IconHome, IconCalendar, IconTrophy, IconGuests, IconClock, IconUser, IconBell, IconQr} from '@/components/icons';

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
                <IconHome class="icon"/>
                Inicio
            </router-link>

            <router-link to="/socio/reservations" class="nav-link" 
                :class="{ 'disabled-link': profileStore.isAccountInactive }">
                <IconCalendar class="icon"/>
                Reservas
            </router-link>

            <router-link to="/socio/tournaments" class="nav-link">
                <IconTrophy class="icon"/>
                Torneos
            </router-link>

            <router-link to="/socio/guests" class="nav-link">
                <IconGuests class="icon"/>
                Invitados
            </router-link>

            <router-link to="/socio/history" class="nav-link">
                <IconClock class="icon"/>
                Historial
            </router-link>

           <router-link 
                to="/socio/qr" 
                class="nav-link qr-link"
                :class="{ 'disabled-link': profileStore.isAccountInactive }"
            >
                <IconQr class="icon"/>
                QR
            </router-link>

            <router-link to="/socio/profile" class="nav-link">
                <IconUser class="icon"/>
                Perfil
            </router-link>

        </div>

        <div class="navbar-right">

            <div class="notification-wrapper">
                <button class="notification-btn" @click="toggleNotifications">
                    <IconBell/>

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

@media (min-width: 1050px) {
    .qr-link {
        pointer-events: none; 
        opacity: 0.4; 
        filter: grayscale(100%);
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
        border-top: 1px solid #e5e7eb; 
        justify-content: flex-start;
        overflow-x: auto; /* Activa el scroll horizontal */
        
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
        padding-bottom: 8px; 
    }
   
    .navbar-center::-webkit-scrollbar {
        height: 6px; /* Grosor de la barra horizontal */
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

.disabled-link {
    pointer-events: none; 
    opacity: 0.4; 
    filter: grayscale(100%); 
    cursor: not-allowed; 
}
</style>