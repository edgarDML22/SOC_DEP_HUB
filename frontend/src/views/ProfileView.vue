<template>
  <div class="layout-wrapper">
    
    <nav class="top-navbar">
      <div class="navbar-left">
        <img src="../assets/LogoSocDep.jpg" alt="SOC-DEP HUB" class="brand-logo" />
        <span class="brand-name">SOC-DEP HUB</span>
      </div>
      
      <div class="navbar-center">
        <router-link to="/socio/home" class="nav-link">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
          Inicio
        </router-link>

        <a href="#" class="nav-link">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
          Reservar
        </a>
        <a href="#" class="nav-link">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
          Torneos
        </a>
        <a href="#" class="nav-link">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          Invitados
        </a>
        <a href="#" class="nav-link">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          Historial
        </a>
        <a href="#" class="nav-link active">
          <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
          Perfil
        </a>
      </div>

      <div class="navbar-right">
        <button class="notification-btn">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
          <span class="notification-badge">2</span>
        </button>
        <div class="nav-avatar">{{ userInitials }}</div>
      </div>
    </nav>

    <main class="main-content">
      <div class="profile-container">
        
        <div class="page-header">
          <h1 class="page-title">Mi perfil</h1>
          <p class="page-subtitle">Gestiona tu informacion personal</p>
        </div>

        <div v-if="isAccountInactive" class="alert-banner">
          ⚠️ Atención: El estatus de esta cuenta es <strong>{{ profileData.estatus_cuenta }}</strong>.
        </div>

        <div v-if="profileData" class="profile-content">
          
          <div class="profile-card summary-card">
            <div class="summary-left">
              <div class="avatar-large">{{ userInitials }}</div>
              <div class="summary-text">
                <h2>{{ profileData.nombre_completo }}</h2>
                <div class="badges-container">
                  <span class="badge" :class="statusBadgeClass">{{ profileData.estatus_cuenta }}</span>
                  <span class="badge badge-gray" v-if="profileData.tipo_socio">{{ profileData.tipo_socio }}</span>
                </div>
              </div>
            </div>
            <button class="edit-btn">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
              Editar
            </button>
          </div>

          <div class="profile-card details-card">
            <h3 class="card-title">Informacion personal</h3>
            
            <div class="form-container">
              
              <div class="form-group-with-icon">
                <div class="icon-box">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <div class="input-wrapper">
                  <label for="nombre">Nombre Completo</label>
                  <input 
                    id="nombre" 
                    type="text" 
                    :value="profileData.nombre_completo" 
                    readonly 
                    disabled 
                  />
                </div>
              </div>

              <div class="form-group-with-icon">
                <div class="icon-box">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                </div>
                <div class="input-wrapper">
                  <label for="num_accion">Número de Acción</label>
                  <input 
                    id="num_accion" 
                    type="text" 
                    :value="profileData.num_accion || 'N/A'" 
                    readonly 
                    disabled 
                  />
                </div>
              </div>

              <div class="form-group-with-icon">
                <div class="icon-box">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
                <div class="input-wrapper">
                  <label for="tipo_socio">Rol / Tipo de Socio</label>
                  <input 
                    id="tipo_socio" 
                    type="text" 
                    :value="profileData.tipo_socio || 'N/A'" 
                    readonly 
                    disabled 
                  />
                </div>
              </div>

              <div class="form-group-with-icon">
                <div class="icon-box">
                  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <div class="input-wrapper">
                  <label for="estatus">Estatus de Cuenta</label>
                  <input 
                    id="estatus" 
                    type="text" 
                    :value="profileData.estatus_cuenta" 
                    readonly 
                    disabled 
                  />
                </div>
              </div>

            </div>
          </div>

          <div class="profile-card security-card">
            <div class="card-header-icon">
              <svg fill="none" stroke="#3b82f6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
              <h3 class="card-title no-margin">Seguridad</h3>
            </div>
            
            <div class="security-row">
              <div class="security-info">
                <h4>Contraseña</h4>
                <p>{{ passwordUpdateText }}</p>
              </div>
              <button class="action-btn">Cambiar</button>
            </div>
          </div>

        </div>
        
        <div v-else class="loading">
          Cargando información del perfil...
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api.js'; // Ajusta la ruta a tu archivo api.js si es necesario

const profileData = ref(null);

// Lógica de inactividad
const isAccountInactive = computed(() => {
  if (!profileData.value) return false;
  const status = profileData.value.estatus_cuenta?.toUpperCase();
  return status === 'INACTIVO' || status === 'SUSPENDIDO';
});

// Calcula las iniciales para los avatares
const userInitials = computed(() => {
  if (!profileData.value?.nombre_completo) return '';
  const names = profileData.value.nombre_completo.split(' ');
  if (names.length >= 2) {
    return `${names[0][0]}${names[1][0]}`.toUpperCase();
  }
  return names[0][0].toUpperCase();
});

// Da color al badge dinámicamente
const statusBadgeClass = computed(() => {
  const status = profileData.value?.estatus_cuenta?.toUpperCase();
  if (status === 'ACTIVO') return 'badge-green';
  if (status === 'SUSPENDIDO' || status === 'INACTIVO') return 'badge-red';
  return 'badge-gray';
});

// Calcula el tiempo transcurrido desde la última actualización de contraseña
const passwordUpdateText = computed(() => {
  if (!profileData.value?.fecha_actualizacion_password) {
    return 'Cargando información...';
  }

  // Convertimos la fecha que manda Laravel a formato JavaScript
  const updateDate = new Date(profileData.value.fecha_actualizacion_password);
  const today = new Date();
  
  // Calculamos la diferencia en milisegundos y la pasamos a días
  const diffTime = Math.abs(today - updateDate);
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays === 0) return 'Última actualización hoy';
  if (diffDays === 1) return 'Última actualización hace 1 día';
  
  return `Última actualización hace ${diffDays} días`;
});

// Petición al backend usando Axios
const fetchProfile = async () => {
  try {
    const response = await api.get('/api/v1/profile', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
      }
    });

    if (response.data.success) {
      profileData.value = response.data.data;
    } else {
      console.error("Error desde el servidor:", response.data.message);
    }
  } catch (error) {
    console.error("Error de conexión al obtener el perfil:", error);
  }
};

onMounted(() => {
  fetchProfile();
});
</script>

<style scoped>
/* Tipografía global y Layout de Figma */
.layout-wrapper {
  min-height: 100vh;
  background-color: #f8f9fa;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  color: #111827;
}

/* NAVBAR STYLES */
.top-navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #ffffff;
  padding: 0 2rem;
  min-height: 70px;
  border-bottom: 1px solid #e5e7eb;
  flex-wrap: wrap; /* Permite que los elementos bajen en pantallas chicas */
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

.nav-link.active {
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

/* MAIN CONTENT */
.main-content {
  padding: 2rem;
}

.profile-container {
  max-width: 900px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: #111827;
}

.page-subtitle {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

/* CARDS GENERAL */
.profile-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 20px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* TARJETA 1: RESUMEN */
.summary-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.avatar-large {
  width: 64px;
  height: 64px;
  background-color: #1d4ed8;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 600;
}

.summary-text h2 {
  margin: 0 0 8px 0;
  font-size: 18px;
  font-weight: 600;
}

.badges-container {
  display: flex;
  gap: 8px;
}

.badge {
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 500;
  display: inline-block;
}

.badge-green { background-color: #dcfce7; color: #166534; }
.badge-gray { background-color: #f3f4f6; color: #374151; }
.badge-red { background-color: #fee2e2; color: #991b1b; }

.edit-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background-color: #ffffff;
  border: 1px solid #d1d5db;
  color: #374151;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.edit-btn:hover {
  background-color: #f9fafb;
}

.edit-btn svg {
  width: 16px;
  height: 16px;
}

/* TARJETA 2: DETALLES CON ICONOS */
.card-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 20px 0;
  color: #111827;
}

.form-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-group-with-icon {
  display: flex;
  align-items: center;
  gap: 16px;
}

/* Estilo del cuadro gris del icono estilo Figma */
.icon-box {
  width: 44px;
  height: 44px;
  background-color: #f3f4f6;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  flex-shrink: 0; 
}

.icon-box svg {
  width: 20px;
  height: 20px;
}

/* Contenedor del label e input para que llenen el espacio sobrante */
.input-wrapper {
  flex-grow: 1;
}

.input-wrapper label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.4rem;
  font-size: 13px;
  color: #6b7280;
}

.input-wrapper input[type="text"] {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background-color: #f9fafb;
  color: #111827;
  font-size: 15px;
  font-weight: 500;
  cursor: not-allowed;
  box-sizing: border-box;
}

.alert-banner {
  background-color: #fef2f2;
  color: #991b1b;
  padding: 12px 16px;
  border: 1px solid #f87171;
  border-radius: 8px;
  margin-bottom: 24px;
  font-size: 14px;
}

.loading {
  text-align: center;
  padding: 40px;
  color: #6b7280;
}

/* TARJETA 3: SEGURIDAD */
.security-card {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.card-header-icon {
  display: flex;
  align-items: center;
  gap: 10px;
  border-bottom: 1px solid #f3f4f6;
  padding-bottom: 16px;
}

.card-header-icon svg {
  width: 20px;
  height: 20px;
}

.no-margin {
  margin: 0 !important;
}

.security-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.security-info h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
  color: #111827;
}

.security-info p {
  margin: 0;
  font-size: 12px;
  color: #6b7280;
}

.action-btn {
  background-color: #ffffff;
  border: 1px solid #d1d5db;
  color: #374151;
  padding: 6px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.action-btn:hover {
  background-color: #f9fafb;
}

/* =========================================
   DISEÑO RESPONSIVO (Móviles y Tablets)
   ========================================= */
@media (max-width: 850px) {
  .top-navbar {
    padding: 10px 1rem;
  }

  /* Los enlaces de navegación se van al segundo "piso" */
  .navbar-center {
    order: 3; /* Los manda hasta abajo */
    width: 100%; /* Toman todo el ancho disponible */
    margin-top: 12px;
    justify-content: flex-start;
    overflow-x: auto; /* Permite deslizar con el dedo/mouse a los lados */
    padding-bottom: 8px; 
  }

  /* Evita que los botones se apachurren y los mantiene en una línea */
  .nav-link {
    white-space: nowrap;
    flex-shrink: 0;
  }

  /* Estilo sutil a la barrita de scroll en móviles */
  .navbar-center::-webkit-scrollbar {
    height: 4px;
  }
  .navbar-center::-webkit-scrollbar-thumb {
    background-color: #d1d5db;
    border-radius: 4px;
  }

 
  .main-content {
    padding: 1rem; 
  }
}
</style>