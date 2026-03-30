<script setup>
import { useProfileStore } from '@/stores/profileStore';
const profileStore = useProfileStore();
</script>

<template>
  <main class="main-content">
    <div class="profile-container">

      <div class="page-header">
        <h1 class="page-title">Mi Perfil</h1>
        <p class="page-subtitle">Gestiona tu Informacion Personal</p>
      </div>

      <div v-if="profileStore.isAccountInactive" class="alert-banner">
        ⚠️ Atención: El estatus de esta cuenta es <strong>{{profileStore.statusAccount}}</strong>.
      </div>

      <div class="profile-content">
        <div class="profile-card summary-card">
          <div class="summary-left">
            <div class="avatar-large">{{profileStore.userInitials }}</div>
            <div class="summary-text">
              <h2>{{ profileStore.fullName }}</h2>
              <div class="badges-container">
                <span class="badge" :class="profileStore.statusBadgeClass">{{ profileStore.statusAccount}}</span>
                <span class="badge badge-gray" v-if="profileStore.statusAccount">{{ profileStore.typeSocio }}</span>
              </div>
            </div>
          </div>
          <button class="edit-btn">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
              </path>
            </svg>
            Editar
          </button>
        </div>

        <div class="profile-card details-card">
          <h3 class="card-title">Informacion personal</h3>

          <div class="form-container">

            <div class="form-group-with-icon">
              <div class="icon-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <div class="input-wrapper">
                <label for="nombre">Nombre Completo</label>
                <input id="nombre" type="text" :value="profileStore.fullName" readonly disabled />
              </div>
            </div>

            <div class="form-group-with-icon">
              <div class="icon-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2">
                  </path>
                </svg>
              </div>
              <div class="input-wrapper">
                <label for="num_accion">Número de Acción</label>
                <input id="num_accion" type="text" :value="profileStore.actionNumber || 'N/A'" readonly disabled />
              </div>
            </div>

            <div class="form-group-with-icon">
              <div class="icon-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
              </div>
              <div class="input-wrapper">
                <label for="tipo_socio">Rol / Tipo de Socio</label>
                <input id="tipo_socio" type="text" :value="profileStore.typeSocio || 'N/A'" readonly disabled />
              </div>
            </div>

            <div class="form-group-with-icon">
              <div class="icon-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                  </path>
                </svg>
              </div>
              <div class="input-wrapper">
                <label for="estatus">Estatus de Cuenta</label>
                <input id="estatus" type="text" :value="profileStore.statusAccount" readonly disabled />
              </div>
            </div>

          </div>
        </div>

        <div class="profile-card security-card">
          <div class="card-header-icon">
            <svg fill="none" stroke="#3b82f6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
              </path>
            </svg>
            <h3 class="card-title no-margin">Seguridad</h3>
          </div>

          <div class="security-row">
            <div class="security-info">
              <h4>Contraseña</h4>
              <p>{{ profileStore.passwordUpdateText }}</p>
            </div>
            <!-- Cambiar a un router-link este boton -->
            <button class="action-btn">Cambiar</button>
          </div>
        </div>

      </div>


    </div>
  </main>
</template>



<style scoped>
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

.badge-green {
  background-color: #dcfce7;
  color: #166534;
}

.badge-gray {
  background-color: #f3f4f6;
  color: #374151;
}

.badge-red {
  background-color: #fee2e2;
  color: #991b1b;
}

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
</style>