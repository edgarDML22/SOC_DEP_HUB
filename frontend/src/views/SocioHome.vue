<template>
  <div class="layout-wrapper">

    <!-- NAVBAR -->
    <nav class="top-navbar">
      <div class="navbar-left">
        <img src="/logo.jpeg" class="brand-logo" />
        <span class="brand-name">SOC-DEP HUB</span>
      </div>

      <div class="navbar-center">
        <a href="#" class="nav-link active" @click.prevent="handleClick('Inicio')">Inicio</a>
        <a href="#" class="nav-link" @click.prevent="handleClick('Reservar')">Reservar</a>
        <a href="#" class="nav-link" @click.prevent="handleClick('Torneos')">Torneos</a>
        <a href="#" class="nav-link" @click.prevent="handleClick('Invitados')">Invitados</a>
        <a href="#" class="nav-link" @click.prevent="handleClick('Historial')">Historial</a>
        <a href="#" class="nav-link" @click.prevent="handleClick('Perfil')">Perfil</a>
      </div>

      <div class="navbar-right">

        <!-- CAMPANA -->
        <div class="notification-wrapper">
          <div class="notification-btn" @click="toggleNotifications">
            🔔
            <span class="notification-badge">{{ notifications }}</span>
          </div>

          <div v-if="showNotifications" class="notification-dropdown">
            <p class="empty">No hay notificaciones</p>
          </div>
        </div>

        <!-- AVATAR -->
        <div class="nav-avatar" @click="toggleMenu">
          {{ userInitials }}
        </div>

        <!-- DROPDOWN -->
        <div v-if="menuOpen" class="dropdown">
          <button>Perfil</button>
          <button>Configuración</button>
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

const loadProfile = async () => {
  try {
    const token = localStorage.getItem('auth_token')

    if (!token) {
      console.warn('No hay token')
      return
    }

    console.log('TOKEN:', token)

    const response = await axios({
      method: 'get',
      url: 'http://localhost:8000/api/v1/profile',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
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

/* TODO TU CSS EXACTO (NO MODIFICADO) */
.layout-wrapper { background: #f5f7fb; font-family: 'Inter', sans-serif; }
.top-navbar { display: flex; justify-content: space-between; align-items: center; height: 70px; padding: 0 2rem; background: white; border-bottom: 1px solid #e5e7eb; }
.navbar-left { display: flex; align-items: center; gap: 10px; }
.brand-logo { height: 36px; width: 36px; border-radius: 8px; object-fit: cover; }
.navbar-center { display: flex; gap: 14px; }
.nav-link { text-decoration: none; font-size: 14px; color: #6b7280; padding: 6px 10px; border-radius: 8px; }
.nav-link.active { background: #e0edff; color: #2563eb; }
.navbar-right { display: flex; align-items: center; gap: 12px; }
.notification-wrapper { position: relative; }
.notification-btn { cursor: pointer; position: relative; }
.notification-badge { position: absolute; top: -5px; right: -6px; background: red; color: white; font-size: 10px; border-radius: 50%; padding: 2px 5px; }
.notification-dropdown { position: absolute; top: 45px; right: 0; width: 220px; background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 10px 25px rgba(0,0,0,0.08); padding: 12px; }
.nav-avatar { width: 36px; height: 36px; background: #2563eb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.dropdown { position: absolute; top: 60px; right: 20px; width: 200px; background: white; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 12px 25px rgba(0,0,0,0.08); overflow: hidden; display: flex; flex-direction: column; }
.dropdown button { padding: 12px 14px; border: none; background: white; cursor: pointer; }
.dropdown button:hover { background: #f3f4f6; }
.logout { color: #ef4444; }
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