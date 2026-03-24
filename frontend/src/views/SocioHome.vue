<template>
  <div class="layout-wrapper">

    <!-- NAVBAR -->
   <nav class="top-navbar">
  <div class="navbar-left">
    <img src="../assets/Logo.jpeg" alt="SOC-DEP HUB" class="brand-logo" />
    <span class="brand-name">SOC-DEP HUB</span>
  </div>
  
  <div class="navbar-center">
    <a href="#" class="nav-link active" @click.prevent="handleClick('Inicio')">
      <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
        </path>
      </svg>
      Inicio
    </a>

    <a href="#" class="nav-link" @click.prevent="handleClick('Reservar')">
      <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
        </path>
      </svg>
      Reservar
    </a>

    <a href="#" class="nav-link" @click.prevent="handleClick('Torneos')">
      <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
        </path>
      </svg>
      Torneos
    </a>

    <a href="#" class="nav-link" @click.prevent="handleClick('Invitados')">
      <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
        </path>
      </svg>
      Invitados
    </a>

    <a href="#" class="nav-link" @click.prevent="handleClick('Historial')">
      <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
        </path>
      </svg>
      Historial
    </a>

    <a href="#" class="nav-link " @click.prevent="handleClick('Perfil')">
      <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
        </path>
      </svg>
      Perfil
    </a>
  </div>

  <div class="navbar-right">
    
    <!-- 🔥 TU LÓGICA ORIGINAL -->
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

    <!-- AVATAR FUNCIONAL -->
    <div class="avatar-wrapper">

  <div class="nav-avatar" @click="toggleMenu">
    {{ userInitials }}
  </div>

  <div v-if="menuOpen" class="dropdown">
    <button>Perfil</button>
    <button>Configuración</button>
    <button @click="getSupportLink">Ayuda</button>
    <hr />
    <button class="logout" @click="logout">Cerrar sesión</button>
  </div>

</div>

    <!-- DROPDOWN FUNCIONAL -->
    <div v-if="menuOpen" class="dropdown">
      <button>Perfil</button>
      <button>Configuración</button>
      <button @click="getSupportLink">Ayuda</button>
      <hr />
      <button class="logout" @click="logout">Cerrar sesión</button>
    </div>

  </div>
</nav>

    <!-- CONTENIDO -->
    <main class="main-content">
      <div class="container">

        <!-- 🔥 FIX AQUÍ -->
        <h2>Hola, {{ user.name }}</h2>

        <p class="subtitle">Bienvenido de vuelta al Club Deportivo</p>

        <!-- RESERVA -->
        <div class="card card-blue">
          <div class="card-header">
            <span>Próxima reserva</span>
            <span class="status inactive">Inactivo</span>
          </div>

          <div class="torneos-body">
            <p class="empty">No hay reservas disponibles</p>
          </div>

          <div class="card-actions">
            <button class="btn-gray" @click="handleClick('detalle')">Ver detalle</button>
            <button class="btn-blue" @click="handleClick('qr')">Presentar Pase QR</button>
          </div>
        </div>

        <!-- ACCIONES -->
        <h3 class="section-title">Acciones rápidas</h3>

        <div class="actions">
          <div class="action-card" @click="handleClick('reservar')">Reservar espacio</div>
          <div class="action-card" @click="handleClick('torneos')">Ver torneos activos</div>
          <div class="action-card" @click="handleClick('mis reservas')">Mis reservas</div>
          <div class="action-card" @click="handleClick('historial')">Historial</div>
        </div>

        <!-- TORNEOS -->
        <div class="card torneos">
          <div class="torneos-header">
            <h3>Torneos activos</h3>
            <button class="btn-link" @click="handleClick('ver torneos')">
              Ver todos →
            </button>
          </div>

          <div class="torneos-body">
            <p class="empty">NO HAY TORNEOS ACTIVOS</p>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import api from '../services/api.js' 

const router = useRouter()

const user = ref({ name: 'Usuario' })
const profile = ref(null)
const menuOpen = ref(false)
const showNotifications = ref(false)
const notifications = ref(2)

/* 🔥 INICIALES */
const userInitials = computed(() =>
  user.value.name ? user.value.name[0].toUpperCase() : 'U'
)
const getSupportLink = async () => {
  try {
    // 👇 ESTA LÍNEA ES CLAVE
    await api.get('/sanctum/csrf-cookie')

    const response = await api.get('/api/v1/system/support-link')

    console.log(response.data)

    const url = response.data?.data?.support_url

    if (url) {
      window.open(url, '_blank')
    }
  } catch (error) {
    console.error("Hubo un error al obtener el link:", error)

    if (error.response) {
      console.error('STATUS:', error.response.status)
      console.error('DATA:', error.response.data)
    }

    alert("No se pudo cargar el formulario de soporte.")
  }
}


const loadProfile = async () => {
  try {
    const token = localStorage.getItem('auth_token')

    if (!token) {
      console.warn('No hay token')
      return
    }

    console.log('TOKEN:', token)

    

const response = await api.get('/api/v1/profile', {
  headers: {
    Authorization: `Bearer ${token}`
  }
})

    
    console.log('RESPONSE:', response.data)

    profile.value = response.data.data
    user.value.name = profile.value?.nombre_completo || 'Usuario'

  } catch (error) {
    console.error('ERROR COMPLETO:', error)

    if (error.response) {
      console.error('STATUS:', error.response.status)
      console.error('DATA:', error.response.data)
    }
  }
}
/* TOGGLES */
const toggleMenu = () => {
  menuOpen.value = !menuOpen.value
}

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value
}

/* BOTONES */
const handleClick = (action) => {
  console.log('Click en:', action)
}

/* LOGOUT */
const logout = () => {
  localStorage.clear()
  router.push('/login')
}

/* MOUNT */
onMounted(() => {
  loadProfile()
})
</script>
<style scoped>

/* TODO TU CSS EXACTO (AJUSTADO BIEN NAVBAR) */
.layout-wrapper {
  min-height: 100vh;
  background-color: #f8f9fa;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  color: #111827;
}

/* NAVBAR */
.top-navbar { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  height: 64px; /* 👈 clave */
  padding: 0 32px; /* 👈 más aire */
  background: #ffffff; 
  border-bottom: 1px solid #e5e7eb; 
}

/* LEFT */
.navbar-left { 
  display: flex; 
  align-items: center; 
  gap: 10px; 
}

.brand-logo { 
  height: 34px; 
  width: 34px; 
  border-radius: 8px; 
  object-fit: cover; 
}

.brand-name {
  font-weight: 600; /* 👈 menos pesado */
  font-size: 15px;
  color: #111827;
}

/* CENTER */
.navbar-center { 
  display: flex; 
  align-items: center;
  gap: 24px; /* 👈 spacing real */
}

.nav-link { 
  display: flex;
  align-items: center;
  gap: 6px;
  text-decoration: none; 
  font-size: 13.5px; /* 👈 más fino */
  color: #6b7280; 
  padding: 6px 10px; 
  border-radius: 6px; 
  transition: all 0.2s ease;
  font-weight: 500;
}

.nav-link:hover { 
  background: #f3f4f6; 
  color: #111827; 
}

.nav-link.active { 
  background: #e0e7ff; 
  color: #2563eb; 
}

/* ICONOS */
.icon {
  width: 16px; /* 👈 importante */
  height: 16px;
}

/* RIGHT */
.navbar-right { 
  display: flex; 
  align-items: center; 
  gap: 16px; 
}

/* NOTIFICACIONES */
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

/* DROPDOWN */
.notification-dropdown { 
  position: absolute; 
  top: 45px; 
  right: 0; 
  width: 220px; 
  background: white; 
  border-radius: 12px; 
  border: 1px solid #e5e7eb; 
  box-shadow: 0 10px 25px rgba(0,0,0,0.08); 
  padding: 12px; 
}

/* AVATAR */
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

/* DROPDOWN MENU */
.dropdown { 
  position: absolute; 
  top: 60px; 
  right: 20px; 
  width: 200px; 
  background: white; 
  border-radius: 12px; 
  border: 1px solid #e5e7eb; 
  box-shadow: 0 12px 25px rgba(0,0,0,0.08); 
  overflow: hidden; 
  display: flex; 
  flex-direction: column; 
}

.dropdown button { 
  padding: 12px 14px; 
  border: none; 
  background: white; 
  cursor: pointer; 
}

.dropdown button:hover { 
  background: #f3f4f6; 
}

.logout { 
  color: #ef4444; 
}

/* RESTO (SIN CAMBIOS) */
.container { max-width: 752px; margin: auto; padding: 20px; }
.subtitle { color: #6b7280; margin-bottom: 16px; }
.card { background: white; border-radius: 16px; padding: 18px; border: 1px solid #e5e7eb; margin-bottom: 24px; }
.card-blue { background: #dbeafe; border: 1px solid #bfdbfe; min-height: 299px; display: flex; flex-direction: column; justify-content: space-between; }
.card-header { display: flex; justify-content: space-between; align-items: center; }
.status.inactive { background: #e5e7eb; color: #6b7280; padding: 4px 10px; border-radius: 999px; font-size: 12px; }
.card-actions { display: flex; gap: 10px; }
.btn-gray { background: #e5e7eb; border-radius: 8px; padding: 6px 12px; }
.btn-blue { background: #2563eb; color: white; border-radius: 8px; padding: 6px 12px; }
.actions { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.action-card { height: 160px; border-radius: 16px; border: 1px solid #e5e7eb; background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.torneos-header { display: flex; justify-content: space-between; align-items: center; }
.btn-link { background: none; border: none; color: #2563eb; cursor: pointer; }
.torneos-body { min-height: 120px; display: flex; align-items: center; justify-content: center; }
.empty { color: #6b7280; text-align: center; }

</style>