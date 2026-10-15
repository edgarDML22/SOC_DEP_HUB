<script setup>
import { ref, computed, onMounted } from 'vue'
import { IconBell } from '@/components/icons'
import { useAdminStore } from '@/stores/profiles/adminStore'
import AdminProfileModal from '@/components/gerente/ui/AdminProfileModal.vue'
import DarkModeToggle from '@/components/ui/DarkModeToggle.vue'

const profileStore = useAdminStore()

const menuOpen = ref(false)
const showNotifications = ref(false)
const showProfileModal = ref(false)

const notificationsList = ref([])
const activeCount = computed(() => notificationsList.value.length)

const emit = defineEmits(['toggle-sidebar', 'toggle-mobile-sidebar'])

const handleToggleSidebar = () => {
  emit('toggle-sidebar')
  emit('toggle-mobile-sidebar')
}

const toggleMenu = () => {
  menuOpen.value = !menuOpen.value
  if (menuOpen.value) {
    showNotifications.value = false
  }
}

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value
  if (showNotifications.value) {
    menuOpen.value = false
  }
}

const closeMenu = () => {
  menuOpen.value = false
}

const closeNotifications = () => {
  showNotifications.value = false
}

const emit = defineEmits(['toggle-sidebar'])

const toggleMenu = () => menuOpen.value = !menuOpen.value
const toggleNotifications = () => showNotifications.value = !showNotifications.value
const closeMenu = () => menuOpen.value = false
const markAllAsRead = () => {
  notificationsList.value = []
}

onMounted(() => {
  if (!profileStore.profileData) {
    profileStore.fetchProfile()
  }
})
</script>

<template>
  <header class="topbar h-16 sm:h-[70px] bg-surface-100 border-b border-surface-200 px-3 sm:px-6 flex items-center justify-between relative z-30 select-none">
    <!-- Seccion Izquierda: Boton Hamburguesa y Titulo de Modulo Activo -->
    <div class="left flex items-center gap-2 sm:gap-3 min-w-0 mr-2 sm:mr-4">
      <button
        type="button"
        class="lg:hidden flex items-center justify-center p-2 rounded-xl text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800 focus:outline-none transition-colors shrink-0 cursor-pointer"
        aria-label="Abrir menu de navegacion"
        @click="handleToggleSidebar"
  <header class="topbar">
    <div class="left flex items-center gap-3">
      <button
        type="button"
        class="lg:hidden p-2 -ml-2 rounded-xl text-surface-600 hover:text-surface-900 hover:bg-surface-200 transition-colors focus:outline-none flex items-center justify-center cursor-pointer"
        aria-label="Abrir menú"
        @click="emit('toggle-sidebar')"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
        <img
          src="@/assets/LogoSocDep.jpg"
          alt="Britania"
          class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg object-cover shrink-0 shadow-xs"
        />
      </div>
    </div>

    <!-- Seccion Derecha: Badge de Rol, Modo Oscuro, Notificaciones y Perfil -->
    <div class="right flex items-center gap-1.5 sm:gap-3 shrink-0">
      <!-- Badge de Rol Institucional -->
      <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-surface-200 text-surface-700 tracking-wide select-none">
        {{ profileStore.role === 'SUBGERENTE' ? 'Subgerente' : 'Gerente' }}
      </span>

      <!-- Alternancia instantanea de tema claro/oscuro -->
      <DarkModeToggle class="shrink-0" />

      <!-- Campana y Dropdown de Notificaciones -->
      <div class="relative shrink-0">
        <button
          type="button"
          class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-50 text-surface-600 border border-surface-200/40 hover:bg-surface-100 hover:text-surface-900 active:scale-95 transition-all shadow-[0_2px_10px_rgba(0,0,0,0.03)] cursor-pointer relative group"
          aria-label="Notificaciones"
          @click="toggleNotifications"
        >
    </div>

    <div class="right">
      <span class="role">{{ profileStore.role === 'SUBGERENTE' ? 'Subgerente' : 'Gerente' }}</span>
      
      <DarkModeToggle />

      <div class="notification-wrapper">
        <button class="notification-btn" @click="toggleNotifications">
          <IconBell class="w-5 h-5 transition-transform group-hover:scale-110" />
          <span
            v-if="activeCount > 0"
            class="absolute top-1.5 right-1.5 bg-red-500 text-white text-[10px] font-black rounded-full h-4 min-w-4 px-1 flex items-center justify-center border-2 border-surface-100 shadow-sm"
          >
            {{ activeCount }}
          </span>
        </button>

        <!-- Overlay para cerrar dropdown de notificaciones -->
        <div
          v-if="showNotifications"
          class="fixed inset-0 z-20"
          @click="closeNotifications"
        ></div>

        <!-- Dropdown responsivo de notificaciones -->
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="transform opacity-0 scale-95 translate-y-2"
          enter-to-class="transform opacity-100 scale-100 translate-y-0"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="transform opacity-100 scale-100 translate-y-0"
          leave-to-class="transform opacity-0 scale-95 translate-y-2"
        >
          <div
            v-if="showNotifications"
            class="fixed inset-x-4 top-16 sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 w-auto sm:w-96 max-w-[calc(100vw-2rem)] z-30 bg-surface-100 rounded-2xl shadow-2xl border border-surface-200 overflow-hidden font-sans"
          >
            <div class="px-4 py-3 border-b border-surface-200 bg-surface-50 flex items-center justify-between">
              <span class="font-bold text-surface-900 text-sm">Notificaciones</span>
              <button
                v-if="activeCount > 0"
                type="button"
                @click="markAllAsRead"
                class="text-xs text-primary-600 font-bold hover:underline cursor-pointer"
              >
                Marcar leidas
              </button>
            </div>

            <div v-if="activeCount > 0" class="max-h-[60vh] sm:max-h-80 overflow-y-auto divide-y divide-surface-200">
              <div
                v-for="notif in notificationsList"
                :key="notif.id"
                class="p-4 hover:bg-surface-50 transition-colors flex gap-3 text-left"
              >
                <div
                  class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                  :class="{
                    'bg-green-500/10 text-green-600': notif.tipo === 'success',
                    'bg-blue-500/10 text-blue-600': notif.tipo === 'info',
                    'bg-amber-500/10 text-amber-600': notif.tipo === 'warning'
                  }"
                >
                  <svg v-if="notif.tipo === 'success'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                  <svg v-else-if="notif.tipo === 'info'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                  </svg>
                  <svg v-else-if="notif.tipo === 'warning'" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="text-xs font-bold text-surface-900 truncate leading-tight">{{ notif.titulo }}</h4>
                  <p class="text-[11px] text-surface-500 mt-0.5 leading-normal">{{ notif.mensaje }}</p>
                  <span class="text-[10px] font-medium text-surface-400 mt-1 block">{{ notif.tiempo }}</span>
                </div>
              </div>
            </div>

            <div v-else class="p-6 text-center py-8">
              <div class="w-12 h-12 rounded-full bg-surface-200/60 flex items-center justify-center mx-auto mb-2 text-surface-400">
                <IconBell class="w-5 h-5" />
              </div>
              <p class="text-sm font-bold text-surface-700">Sin notificaciones</p>
              <p class="text-xs text-surface-400 mt-0.5">Estas al dia con todo</p>
            </div>
          </div>
        </Transition>
      </div>

      <!-- Avatar y Menu Desplegable de Perfil -->
      <div class="relative shrink-0">
        <button
          type="button"
          class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center text-xs sm:text-sm font-bold shadow-sm transition-all duration-200 hover:scale-105 hover:ring-2 hover:ring-blue-400/50 cursor-pointer select-none"
          aria-label="Menu de perfil"
          @click="toggleMenu"
        >
          {{ profileStore.userInitials }}
        </button>
      <div class="avatar-container relative">
        <div class="nav-avatar" @click="toggleMenu">
          {{ profileStore.userInitials }}
        </div>

        <div v-if="menuOpen" class="fixed inset-0 z-10" @click="closeMenu"></div>

        <Transition enter-active-class="transition ease-out duration-200"
          enter-from-class="transform opacity-0 scale-95 translate-y-1" enter-to-class="transform opacity-100 scale-100 translate-y-0"
          leave-active-class="transition ease-in duration-150" leave-from-class="transform opacity-100 scale-100 translate-y-0"
          leave-to-class="transform opacity-0 scale-95 translate-y-1">
          <div v-if="menuOpen" class="absolute right-0 mt-3 w-64 origin-top-right rounded-2xl bg-slate-950 
                      shadow-2xl ring-1 ring-black ring-opacity-5 focus:outline-none z-20 
                      overflow-hidden border border-slate-800/60 flex flex-col p-2 gap-1 font-sans">
              <button @click="showProfileModal = true; closeMenu()"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800/70 hover:text-slate-100 transition-all text-left">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Información Personal
              </button>

              <button @click="profileStore.getSupportLink(); closeMenu()"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800/70 hover:text-slate-100 transition-all text-left">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Soporte Técnico
              </button>

        <!-- Overlay para cerrar dropdown de perfil -->
        <div
          v-if="menuOpen"
          class="fixed inset-0 z-20"
          @click="closeMenu"
        ></div>

        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="transform opacity-0 scale-95 translate-y-1"
          enter-to-class="transform opacity-100 scale-100 translate-y-0"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="transform opacity-100 scale-100 translate-y-0"
          leave-to-class="transform opacity-0 scale-95 translate-y-1"
        >
          <div
            v-if="menuOpen"
            class="absolute right-0 top-full mt-2 w-64 max-w-[calc(100vw-2rem)] origin-top-right rounded-2xl bg-slate-950 shadow-2xl ring-1 ring-black/10 focus:outline-none z-30 overflow-hidden border border-slate-800/80 flex flex-col p-2 gap-1 font-sans"
          >
            <button
              type="button"
              @click="showProfileModal = true; closeMenu()"
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800/80 hover:text-white transition-all text-left cursor-pointer"
            >
              <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Informacion Personal
            </button>

            <button
              type="button"
              @click="profileStore.getSupportLink(); closeMenu()"
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800/80 hover:text-white transition-all text-left cursor-pointer"
            >
              <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              Soporte Tecnico
            </button>

            <div class="border-t border-slate-800/80 my-1"></div>

            <button
              type="button"
              @click="profileStore.logout(); closeMenu()"
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all text-left cursor-pointer"
            >
              <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Cerrar Sesion
            </button>
              <button @click="profileStore.logout(); closeMenu()"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-400/80 hover:bg-red-500/10 hover:text-red-400 transition-all text-left">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Cerrar Sesión
              </button>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Modal de Informacion Personal -->
    <AdminProfileModal v-model="showProfileModal" />
  </header>
</template>
    <AdminProfileModal v-model="showProfileModal" />
  </header>
</template>

<style scoped>
.topbar {
  height: 70px;
  background: var(--color-surface-100);
  border-bottom: 1px solid var(--color-surface-200);
  display: flex;
  justify-content: space-between;
  padding: 0 20px;
  align-items: center;
  position: relative;
  z-index: 30;
}

.right {
  display: flex;
  align-items: center;
  gap: 15px;
}

.role {
  background: var(--color-surface-200);
  padding: 6px 12px;
  border-radius: 999px;
  font-weight: 600;
  font-size: 13px;
  color: var(--color-surface-700);
}

.notification-wrapper {
  position: relative;
}

.notification-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #64748b;
  position: relative;
  padding: 10px;
  border-radius: 12px;
  transition: all 0.2s ease-in-out;
  display: flex;
  align-items: center;
  justify-content: center;
}

.notification-btn:hover {
  background-color: var(--color-surface-200);
  color: var(--color-surface-900);
}

.notification-btn:active {
  transform: scale(0.95);
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
  font-weight: 800;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
}

.notification-dropdown {
  position: absolute;
  top: 55px;
  right: 0;
  width: 320px;
  z-index: 50;
}

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
