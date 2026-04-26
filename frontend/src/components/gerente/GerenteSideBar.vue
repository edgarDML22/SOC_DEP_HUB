<script setup>
import {
  IconHome,
  IconCalendar,
  IconTrophy,
  IconUser,
  IconClock,
  IconGuests
} from '@/components/icons'
</script>

<template>
  <aside class="sidebar">

    <div class="title-container">
      <img src="@/assets/LogoSocDep.jpg" alt="Logo" class="sidebar-logo" />
      <span class="title">Menu Principal</span>
    </div>

    <nav class="menu">

      <router-link to="/admin/dashboard" class="item">
        <IconHome class="icon" />
        <span>Dashboard</span>
      </router-link>

      <router-link to="/admin/reservations" class="item">
        <IconCalendar class="icon" />
        <span>Reservas On-Demand</span>
      </router-link>

      <router-link to="/admin/tournaments" class="item">
        <IconTrophy class="icon" />
        <span>Torneos</span>
      </router-link>

      <router-link to="/admin/spaces" class="item">
        <IconHome class="icon" />
        <span>Espacios</span>
      </router-link>

      <router-link to="/admin/socios" class="item">
        <IconUser class="icon" />
        <span>Socios</span>
      </router-link>

      <router-link to="/admin/instructors" class="item">
        <IconUser class="icon" />
        <span>Instructores</span>
      </router-link>

      <router-link to="/admin/ludoteca/ludoteca-list" class="item">
        <IconClock class="icon" />
        <span>Ludoteca</span>
      </router-link>

      <router-link to="/admin/reports" class="item">
        <IconGuests class="icon" />
        <span>Reportes</span>
      </router-link>

    </nav>

  </aside>
</template>

<style scoped>
.sidebar {
  width: 250px;
  height: 100vh;
  background: linear-gradient(180deg, #0f172a, #020617);
  color: white;
  padding: 20px 15px;
}

.title-container {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}

.sidebar-logo {
  width: 35px;
  height: 35px;
  border-radius: 6px;
  object-fit: cover;
}

.title {
  font-size: 12px;
  color: #94a3b8;
}

.menu {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 8px;
  text-decoration: none;
  color: #cbd5f5;
  transition: 0.2s;
}

.item:hover {
  background: #1e293b;
}

/* 🔥 ACTIVO (como en tu imagen) */
.item.router-link-active {
  background: #1e293b;
  color: white;
}

.icon {
  width: 18px;
  height: 18px;
}
</style>
