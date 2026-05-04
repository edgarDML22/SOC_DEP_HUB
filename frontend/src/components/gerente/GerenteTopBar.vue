<script setup>
import { ref } from 'vue'
import { IconBell } from '@/components/icons'
import { useAdminStore } from '@/stores/profiles/adminStore'

const profileStore = useAdminStore()

const menuOpen = ref(false)
const showNotifications = ref(false)
const notifications = ref(3)

const toggleMenu = () => menuOpen.value = !menuOpen.value
const toggleNotifications = () => showNotifications.value = !showNotifications.value
</script>

<template>

  <header class="topbar">

    <!-- LEFT -->
    <div class="left">
      <input type="text" placeholder="Buscar..." class="search" />
    </div>

    <!-- RIGHT -->
    <div class="right">

      <!-- 🔥 GERENTE PILL -->
      <span class="role">Gerente</span>

      <!-- 🔔 NOTIFICACIONES -->
      <div class="notification-wrapper">
        <button class="notification-btn" @click="toggleNotifications">
          <IconBell />
          <span class="notification-badge">{{ notifications }}</span>
        </button>

        <div v-if="showNotifications" class="notification-dropdown">
          <p>No hay notificaciones</p>
        </div>
      </div>

      <!-- 👤 AVATAR -->
      <div class="avatar-wrapper">
        <div class="nav-avatar" @click="toggleMenu">
          {{ profileStore.userInitials }}
        </div>
      </div>

      <!-- DROPDOWN -->
      <div v-if="menuOpen" class="dropdown">
        <router-link to="/admin/profile">Perfil</router-link>
        <button @click="profileStore.logout">Cerrar sesión</button>
      </div>

    </div>

  </header>
</template>

<style scoped>
.topbar {
  height: 70px;
  background: white;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  padding: 0 20px;
  align-items: center;
}

/* SEARCH */
.search {
  border: 1px solid #d1d5db;
  padding: 6px 10px;
  border-radius: 6px;
}

/* RIGHT */
.right {
  display: flex;
  align-items: center;
  gap: 15px;
}

/* 🔥 GERENTE BADGE */
.role {
  background: #f3f4f6;
  padding: 6px 12px;
  border-radius: 999px;
  font-weight: 600;
  font-size: 13px;
}

/* 🔔 NOTIFICATIONS */
.notification-wrapper {
  position: relative;
}

.notification-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  position: relative;
}

.notification-btn svg {
  width: 22px;
  height: 22px;
}

.notification-badge {
  position: absolute;
  top: -4px;
  right: -6px;
  background: #ef4444;
  color: white;
  font-size: 10px;
  border-radius: 999px;
  height: 16px;
  width: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  border: 2px solid white;
}

.notification-dropdown {
  position: absolute;
  top: 40px;
  right: 0;
  width: 220px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
  padding: 12px;
}

/* 👤 AVATAR */
.nav-avatar {
  width: 36px;
  height: 36px;
  background-color: #2563eb;
  color: white;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
}

/* DROPDOWN */
.dropdown {
  position: absolute;
  top: 60px;
  right: 20px;
  width: 180px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 12px 25px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.dropdown a,
.dropdown button {
  padding: 12px;
  border: none;
  background: white;
  cursor: pointer;
  text-align: center;
}

.dropdown button:hover,
.dropdown a:hover {
  background: #f3f4f6;
}
</style>