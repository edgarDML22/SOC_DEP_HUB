<template>
    <div class="navbar-right">
        <div class="user-initials" @click="toggleMenu">
            <span>CM</span>
        </div>

        <div v-if="isMenuOpen" class="dropdown-menu">
            <div class="dropdown-header">
                <strong>Carlos Mendoza</strong>
                <span>Socio Titular</span>
            </div>
            <hr />
            <router-link to="/socio/perfil" class="dropdown-item">Mi Perfil</router-link>
            <a href="#" class="dropdown-item" @click.prevent="handleLogout">Cerrar Sesión</a>
        </div>
    </div>

    <div class="navbar-right">
            <button class="notification-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                    </path>
                </svg>
                <span class="notification-badge">2</span>
            </button>
            <div class="nav-avatar">{{ userInitials }}</div>
        </div>

        <div class="navbar-right">
            <div class="user-initials" @click="toggleMenu">
                <span>{{ initials }}</span>
            </div>

            <div v-if="isMenuOpen" class="dropdown-menu">
                <div class="dropdown-header">
                    <strong>Carlos Mendoza</strong>
                    <span>Socio Titular</span>
                </div>
                <hr />
                <router-link to="/socio/perfil" class="dropdown-item">Mi Perfil</router-link>
                <a href="#" class="dropdown-item" @click.prevent="handleLogout">Cerrar Sesión</a>
            </div>
        </div>


</template>

<script setup   >
    defineProps({
        initials : String,
        menuOpen : Boolean,
        showNotifications : Boolean,
        notifications : Number
    });

    defineEmits(['toggleMenu', 'toggleNotifications']);


</script>

<style scoped>
/* DROPDOWN MENU */

</style>
