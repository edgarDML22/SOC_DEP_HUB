<script setup>
import { ref, onMounted } from 'vue'
import { IconBell } from '@/components/icons'
import { useAdminStore } from '@/stores/profiles/adminStore'

const profileStore = useAdminStore()

const menuOpen = ref(false)
const showNotifications = ref(false)
const notifications = ref(3)

const toggleMenu = () => menuOpen.value = !menuOpen.value
const toggleNotifications = () => showNotifications.value = !showNotifications.value
const closeMenu = () => menuOpen.value = false

onMounted(() => {
  if (!profileStore.profileData) {
    profileStore.fetchProfile()
  }
})
</script>

<template>
  <header class="topbar">
    <!-- LEFT -->
    <div class="left">
      <input type="text" placeholder="Buscar..." class="search" />
    </div>

    <!-- RIGHT -->
    <div class="right">
      <span class="role">Gerente</span>
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
      <div class="avatar-container relative">
        <div class="nav-avatar" @click="toggleMenu">
          {{ profileStore.userInitials }}
        </div>

        <!-- OVERLAY INVISIBLE PARA CERRAR -->
        <div v-if="menuOpen" class="fixed inset-0 z-10" @click="closeMenu"></div>

        <!-- DROPDOWN REDISEÑADO -->
        <Transition enter-active-class="transition ease-out duration-100"
          enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95">
          <div v-if="menuOpen" class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl bg-slate-900 
                      shadow-2xl ring-1 ring-black ring-opacity-5 focus:outline-none z-20 
                      overflow-hidden border border-slate-800">
            <div class="py-1">
              <!-- INFORMACIÓN PERSONAL -->
              <router-link to="/admin/profile"
                class="flex items-center px-4 py-3 text-sm text-slate-300 hover:bg-slate-800 hover:text-white transition-colors"
                @click="closeMenu">
                <svg class="mr-3 h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Información Personal
              </router-link>

              <!-- SOPORTE TÉCNICO -->
              <button @click="profileStore.getSupportLink(); closeMenu()"
                class="flex w-full items-center px-4 py-3 text-sm text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
                <svg class="mr-3 h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Soporte Técnico
              </button>

              <div class="border-t border-slate-800 my-1"></div>

              <!-- CERRAR SESIÓN -->
              <button @click="profileStore.logout(); closeMenu()"
                class="flex w-full items-center px-4 py-3 text-sm text-red-400 hover:bg-slate-800 hover:text-red-300 transition-colors">
                <svg class="mr-3 h-5 w-5 text-red-500/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Cerrar Sesión
              </button>
            </div>
          </div>
        </Transition>
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
  position: relative;
  z-index: 40;
}

/* SEARCH */
.search {
  border: 1px solid #d1d5db;
  padding: 6px 10px;
  border-radius: 6px;
  outline: none;
  transition: border-color 0.2s;
}

.search:focus {
  border-color: #2563eb;
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
  color: #374151;
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
  padding: 8px;
  border-radius: 8px;
  transition: background-color 0.2s;
}

.notification-btn:hover {
  background-color: #f3f4f6;
}

.notification-btn svg {
  width: 22px;
  height: 22px;
}

.notification-badge {
  position: absolute;
  top: 4px;
  right: 4px;
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
  top: 50px;
  right: 0;
  width: 260px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
  padding: 16px;
  z-index: 50;
}

/* 👤 AVATAR */
.nav-avatar {
  width: 38px;
  height: 38px;
  background-color: #2563eb;
  color: white;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  transition: transform 0.2s, box-shadow 0.2s;
  border: 2px solid transparent;
}

.nav-avatar:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
  border-color: #93c5fd;
}
</style>
