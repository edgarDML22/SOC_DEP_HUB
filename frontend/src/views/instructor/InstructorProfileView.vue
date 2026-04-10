<script setup>
import { ref, onMounted } from 'vue';
import { useInstructorStore } from '@/stores/profiles/instructorStore';
import InstructorNavBar from '@/components/instructor/InstructorNavBar.vue';

// Nuevos iconos
import {
  IconEnvelope, IconPhone, IconBriefcase, IconClock,
  IconHistory, IconSupport, IconLock, IconLogout
} from '@/components/icons';

const profileStore = useInstructorStore();

onMounted(() => {
  profileStore.fetchProfile();
});

const passwordData = ref({
  current: '',
  new: '',
  confirm: ''
});

const handlePasswordUpdate = async () => {
  if (passwordData.value.new !== passwordData.value.confirm) {
    alert("Las contraseñas nuevas no coinciden");
    return;
  }

  // TODO: Implementar la llamada real a la API para cambiar la contraseña
  console.log("Actualizar contraseña", passwordData.value);
  alert("Contraseña actualizada exitosamente (simulación)");

  passwordData.value = { current: '', new: '', confirm: '' };
};

const handleLogout = () => {
  profileStore.logout();
};
</script>

<template>
  <InstructorNavBar />
  <main class="profile-page">

    <header class="card profile-header">
      <div class="avatar">
        <span v-if="profileStore.isLoading">...</span>
        <span v-else>{{ profileStore.userInitials }}</span>
      </div>
      <h1 class="profile-name">{{ profileStore.fullName || 'Cargando...' }}</h1>
      <h2 class="profile-specialty">{{ profileStore.role }}</h2>
      <p class="profile-speciality">{{ profileStore.discipline || 'No disponible' }}</p>
    </header>

    <section class="card">
      <article class="info-item">
        <div class="icon-placeholder">
          <IconEnvelope />
        </div>
        <div class="info-content">
          <span class="info-label">Email</span>
          <span class="info-value">{{ profileStore.email || 'No disponible' }}</span>
        </div>
      </article>

      <article class="info-item">
        <div class="icon-placeholder">
          <IconPhone />
        </div>
        <div class="info-content">
          <span class="info-label">Teléfono</span>
          <span class="info-value">{{ profileStore.phone || 'No registrado' }}</span>
        </div>
      </article>

      <article class="info-item">
        <div class="icon-placeholder">
          <IconBriefcase />
        </div>
        <div class="info-content">
          <span class="info-label">Rol</span>
          <span class="info-value">{{ profileStore.role }}</span>
        </div>
      </article>

      <article class="info-item border-none">
        <div class="icon-placeholder">
          <IconClock />
        </div>
        <div class="info-content">
          <span class="info-label">Contratación</span>
          <span class="info-value">{{ profileStore.hireDate || 'Pendiente' }}</span>
        </div>
      </article>

    </section>

    <section class="card action-menu">
      <button class="action-item">
        <div class="action-left">
          <div class="icon-placeholder">
            <IconHistory />
          </div>
          <span class="action-label">Historial de sesiones</span>
        </div>
        <span class="arrow">></span>
      </button>

      <button class="action-item border-none">
        <div class="action-left">
          <div class="icon-placeholder">
            <IconSupport />
          </div>
          <span class="action-label">Soporte</span>
        </div>
        <span class="arrow">></span>
      </button>
    </section>

    <section class="card password-section">
      <div class="section-header">
        <div class="icon-placeholder">
          <IconLock />
        </div>
        <h2>Cambiar contraseña</h2>
      </div>
      <p class="section-description">
        Actualiza tu contraseña para mantener tu cuenta segura.
      </p>

      <form @submit.prevent="handlePasswordUpdate" class="password-form">
        <div class="form-group">
          <label for="currentPassword">Contraseña actual</label>
          <input type="password" id="currentPassword" v-model="passwordData.current"
            placeholder="Ingresa tu contraseña actual" class="input-field" required />
        </div>

        <div class="form-group">
          <label for="newPassword">Nueva contraseña</label>
          <input type="password" id="newPassword" v-model="passwordData.new" placeholder="Mínimo 8 caracteres"
            class="input-field" minlength="8" required />
        </div>

        <div class="form-group">
          <label for="confirmPassword">Confirmar nueva contraseña</label>
          <input type="password" id="confirmPassword" v-model="passwordData.confirm"
            placeholder="Repite la nueva contraseña" class="input-field" minlength="8" required />
        </div>

        <button type="submit" class="btn-primary">Actualizar contraseña</button>
      </form>
    </section>

    <button @click="handleLogout" class="btn-logout">
      <span class="icon-placeholder red-text">
        <IconLogout />
      </span>
      Cerrar sesión
    </button>

  </main>
</template>

<style scoped>
/* Contenedor Principal (Mobile First) adaptado a tus variables */
.profile-page {
  background-color: var(--p-surface-50, #f8fafc);
  padding: 1rem;
  padding-bottom: 90px;
  /* Importante para que el NavBar inferior no cubra el boton de Cerrar sesión */
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  font-family: inherit;
  /* Utiliza la tipografía global de tu app */
}

/* Estructura Base de Tarjetas */
.card {
  background-color: #ffffff;
  border: 1px solid var(--p-surface-200, #e2e8f0);
  border-radius: 12px;
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Tarjeta 1: Header de Perfil */
.profile-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.avatar {
  width: 72px;
  height: 72px;
  background-color: var(--p-primary-600, #2563eb);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 1rem;
}

.profile-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--p-surface-900, #111827);
  margin: 0 0 0.25rem 0;
}

.profile-specialty {
  font-size: 0.85rem;
  color: var(--p-surface-500, #64748b);
  margin: 0;
}

/* Tarjeta 2: Lista de Información */
.info-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid var(--p-surface-200, #e2e8f0);
}

.info-item.border-none {
  border-bottom: none;
  padding-bottom: 0;
}

.info-item:first-child {
  padding-top: 0;
}

.icon-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--p-surface-500, #64748b);
  width: 24px;
  height: 24px;
}

.info-content {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.info-label {
  font-size: 0.75rem;
  color: var(--p-surface-500, #64748b);
}

.info-value {
  font-size: 0.9rem;
  color: var(--p-surface-900, #111827);
  font-weight: 500;
}

/* Tarjeta 3: Menú de Acciones */
.action-menu {
  padding: 0.5rem 1.25rem;
}

.action-item {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: transparent;
  border: none;
  border-bottom: 1px solid var(--p-surface-200, #e2e8f0);
  padding: 1rem 0;
  cursor: pointer;
  color: var(--p-surface-900, #111827);
}

.action-item.border-none {
  border-bottom: none;
}

.action-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.action-label {
  font-size: 0.9rem;
  font-weight: 600;
}

.arrow {
  color: var(--p-surface-500, #64748b);
  font-weight: bold;
}

/* Tarjeta 4: Formulario de Contraseña */
.section-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.section-header h2 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--p-surface-900, #111827);
  margin: 0;
}

.section-description {
  font-size: 0.85rem;
  color: var(--p-surface-500, #64748b);
  margin-bottom: 1.5rem;
  line-height: 1.4;
}

.password-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-group label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--p-surface-900, #111827);
}

.input-field {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--p-surface-200, #e2e8f0);
  border-radius: 8px;
  font-size: 0.9rem;
  background-color: transparent;
  color: var(--p-surface-900, #111827);
  outline: none;
  transition: border-color 0.2s;
  box-sizing: border-box;
  font-family: inherit;
}

.input-field:focus {
  border-color: var(--p-primary-500, #3b82f6);
}

.input-field::placeholder {
  color: #9ca3af;
}

/* Botones */
.btn-primary {
  width: 100%;
  background-color: var(--p-primary-600, #2563eb);
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.85rem;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  margin-top: 0.5rem;
  transition: background-color 0.2s;
}

.btn-primary:hover {
  background-color: var(--p-primary-700, #1d4ed8);
}

.btn-logout {
  width: 100%;
  background-color: transparent;
  color: #ef4444;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 0.85rem;
  font-size: 0.9rem;
  font-weight: 600;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  background-color: #fff;
  transition: all 0.2s;
}

.btn-logout:hover {
  background-color: #fef2f2;
}

.red-text {
  color: #ef4444;
}
</style>
