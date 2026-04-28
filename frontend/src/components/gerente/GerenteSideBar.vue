<script setup>
import { ref } from 'vue'
import {
  IconHome,
  IconCalendar,
  IconTrophy,
  IconUser,
  IconClock,
  IconGuests
} from '@/components/icons'

const isOpen = ref(true)
const openMenu = ref(null)

const toggleSidebar = () => {
  isOpen.value = !isOpen.value
}

const toggleMenu = (menu) => {
  openMenu.value = openMenu.value === menu ? null : menu
}
</script>

<template>
  <aside :class="[
    'h-screen bg-slate-950 text-slate-300 transition-all duration-300 flex flex-col',
    isOpen ? 'w-64' : 'w-20'
  ]">
    <!-- HEADER -->
    <div class="flex items-center justify-between px-4 py-4">
      <button @click="toggleSidebar" class="text-xs text-slate-400 hover:text-white">
        <img src="@/assets/LogoSocDep.jpg" class="w-8 h-8 rounded-md" />
      </button>
      <div class="flex items-center gap-2">
        <span v-if="isOpen" class="text-sm font-bold text-white">SOC-DEP HUB</span>
      </div>


    </div>

    <!-- MENU -->
    <nav class="flex flex-col gap-2 px-2">

      <!-- ITEM NORMAL -->
      <router-link to="/admin/dashboard" class="sidebar-menu-item">
        <IconHome class="sidebar-icon" />
        <span v-if="isOpen">Dashboard</span>
      </router-link>

      <!-- SUBMENU -->
      <div class="flex flex-col">
        <button @click="toggleMenu('reservas')" class="sidebar-menu-item w-full">
          <IconCalendar class="sidebar-icon" />
          <span v-if="isOpen">Reservas</span>
        </button>

        <div :class="[
          'grid transition-all duration-300 ease-in-out ml-8 overflow-hidden',
          openMenu === 'reservas' && isOpen ? 'grid-rows-[1fr] opacity-100 mt-1' : 'grid-rows-[0fr] opacity-0'
        ]">
          <div class="min-h-0 flex flex-col gap-1">
            <router-link to="/admin/reservations" class="sidebar-submenu-item">
              On-Demand
            </router-link>
            <router-link to="/admin/ludoteca/ludoteca-list" class="sidebar-submenu-item">
              Ludoteca
            </router-link>
          </div>
        </div>
      </div>

      <router-link to="/admin/tournaments" class="sidebar-menu-item">
        <IconTrophy class="sidebar-icon" />
        <span v-if="isOpen">Torneos</span>
      </router-link>

      <div class="flex flex-col">
        <button @click="toggleMenu('users')" class="sidebar-menu-item w-full">
          <IconUser class="sidebar-icon" />
          <span v-if="isOpen">Usuarios</span>
        </button>

        <div :class="[
          'grid transition-all duration-300 ease-in-out ml-8 overflow-hidden',
          openMenu === 'users' && isOpen ? 'grid-rows-[1fr] opacity-100 mt-1' : 'grid-rows-[0fr] opacity-0'
        ]">
          <div class="min-h-0 flex flex-col gap-1">
            <router-link to="/admin/gerentes" class="sidebar-submenu-item">
              Gerentes
            </router-link>
            <router-link to="/admin/instructores" class="sidebar-submenu-item">
              Instructores
            </router-link>
            <router-link to="/admin/socios" class="sidebar-submenu-item">
              Socios
            </router-link>
          </div>
        </div>
      </div>

      <router-link to="/admin/reports" class="sidebar-menu-item">
        <IconGuests class="sidebar-icon" />
        <span v-if="isOpen">Reportes</span>
      </router-link>

    </nav>
  </aside>
</template>