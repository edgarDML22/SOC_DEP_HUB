<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useProfileStore } from '@/stores/profileStore';
import { 
  IconEdit, IconUser, IconIdCard, IconCreditCard, 
  IconShield, IconLock, IconCalendar, IconMail, IconGender 
} from '@/components/icons';

const profileStore = useProfileStore();

// Estados para controlar la edición
const isEditing = ref(false);
const isSaving = ref(false);

// Estado local SOLO para los campos que sí se pueden editar
const formData = reactive({
  fecha_nacimiento: '',
  genero: ''
});

// Sincronizar los datos del store con el formulario local
watch(() => profileStore.profileData, (newData) => {
  if (newData) {
    formData.fecha_nacimiento = profileStore.fechaNacimiento;
    formData.genero = profileStore.genero;
  }
}, { immediate: true });

onMounted(() => {
  profileStore.fetchProfile();
});

const toggleEdit = () => {
  isEditing.value = !isEditing.value;
  // Si se cancela la edición, revertimos los cambios locales
  if (!isEditing.value) {
    formData.fecha_nacimiento = profileStore.fechaNacimiento;
    formData.genero = profileStore.genero;
  }
};

const handleSave = async () => {
  isSaving.value = true;
  const success = await profileStore.updateProfile(formData);
  isSaving.value = false;
  
  if (success) {
    isEditing.value = false;
  } else {
    alert("No se pudieron guardar los cambios. Intenta de nuevo.");
  }
};
</script>

<template>
  <main class="main-content">
    <div class="profile-container">

      <div class="page-header">
        <h1 class="page-title">Mi Perfil</h1>
        <p class="page-subtitle">Gestiona tu Información Personal</p>
      </div>

      <div v-if="profileStore.profileData?.estatus_cuenta === 'MOROSO'" class="alert-banner">
        ⚠️ Atención: El estatus de esta cuenta es <strong>{{ profileStore.statusAccount }}</strong>.
      </div>

      <div class="profile-content">
        <div class="profile-card summary-card">
          <div class="summary-left">
            <div class="avatar-large">{{ profileStore.userInitials }}</div>
            <div class="summary-text">
              <h2>{{ profileStore.fullName }}</h2>
              <div class="badges-container">
                <span class="badge" :class="profileStore.statusBadgeClass">{{ profileStore.statusAccount }}</span>
                <span class="badge badge-gray">{{ profileStore.typeSocio }}</span>
                <span class="badge badge-gray" v-if="profileStore.modalidadPlan !== 'N/A'">{{ profileStore.modalidadPlan }}</span>
              </div>
            </div>
          </div>

          <div class="action-buttons">
            <button v-if="!isEditing" @click="toggleEdit" class="edit-btn">
              <IconEdit />
              Editar
            </button>
            <div v-else class="edit-actions">
              <button @click="toggleEdit" class="btn-cancel" :disabled="isSaving">Cancelar</button>
              <button @click="handleSave" class="btn-save" :disabled="isSaving">
                {{ isSaving ? 'Guardando...' : 'Guardar' }}
              </button>
            </div>
          </div>
        </div>

        <div class="profile-card details-card">
          <h3 class="card-title">Información personal</h3>

          <div class="form-container">
            
            <div class="form-group-with-icon">
              <div class="icon-box"><IconUser /></div>
              <div class="input-wrapper">
                <label for="nombre">Nombre Completo</label>
                <input id="nombre" type="text" :value="profileStore.fullName" readonly disabled />
              </div>
            </div>

            <div class="form-group-with-icon">
              <div class="icon-box"><IconMail /></div>
              <div class="input-wrapper">
                <label for="correo">Correo Electrónico</label>
                <input id="correo" type="text" :value="profileStore.correoElectronico" readonly disabled />
              </div>
            </div>

            <div class="form-group-with-icon">
              <div class="icon-box"><IconCalendar /></div>
              <div class="input-wrapper">
                <label for="fecha_nac">Fecha de Nacimiento</label>
                <input v-if="isEditing" id="fecha_nac" type="date" v-model="formData.fecha_nacimiento" class="editable-input" />
                <input v-else id="fecha_nac" type="text" :value="profileStore.fechaNacimiento" readonly disabled />
              </div>
            </div>

            <div class="form-group-with-icon">
              <div class="icon-box"><IconGender /></div>
              <div class="input-wrapper">
                <label for="genero">Género</label>
                <select v-if="isEditing" id="genero" v-model="formData.genero" class="editable-input select-input">
                  <option value="M">Masculino</option>
                  <option value="F">Femenino</option>
                  <option value="OTRO">Otro</option>
                </select>
                <input v-else id="genero" type="text" :value="profileStore.genero === 'M' ? 'Masculino' : profileStore.genero === 'F' ? 'Femenino' : 'Otro'" readonly disabled />
              </div>
            </div>

            <div class="form-group-with-icon">
              <div class="icon-box"><IconIdCard /></div>
              <div class="input-wrapper">
                <label for="num_accion">Número de Acción</label>
                <input id="num_accion" type="text" :value="profileStore.actionNumber" readonly disabled />
              </div>
            </div>

            <div class="form-group-with-icon">
              <div class="icon-box"><IconCreditCard /></div>
              <div class="input-wrapper">
                <label for="tipo_socio">Rol / Tipo de Socio</label>
                <input id="tipo_socio" type="text" :value="profileStore.typeSocio" readonly disabled />
              </div>
            </div>

            <div class="form-group-with-icon">
              <div class="icon-box"><IconShield /></div>
              <div class="input-wrapper">
                <label for="estatus">Estatus de Cuenta</label>
                <input id="estatus" type="text" :value="profileStore.statusAccount" readonly disabled />
              </div>
            </div>
          </div>
        </div>

        <div class="profile-card security-card">
          <div class="card-header-icon">
            <IconLock />
            <h3 class="card-title no-margin">Seguridad</h3>
          </div>

          <div class="security-row">
            <div class="security-info">
              <h4>Contraseña</h4>
              <p>{{ profileStore.passwordUpdateText }}</p>
            </div>
            <button @click="$router.push('/forgot-password')" class="action-btn">Cambiar</button>
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
  background-color: #1d4ed8; /* Azul fijo para que no desaparezca */
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
  color: #111827;
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

/* BOTONES Y ESTADOS DE EDICIÓN */
.edit-actions {
  display: flex;
  gap: 10px;
}

.edit-btn, .btn-cancel, .btn-save, .action-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 8px; /* Borde fijo para que regresen a ser redondeados */
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.edit-btn, .btn-cancel, .action-btn {
  background-color: #ffffff;
  border: 1px solid #d1d5db;
  color: #374151;
}

.btn-save {
  background-color: #1d4ed8;
  border: 1px solid #1d4ed8;
  color: white;
}

.btn-save:hover { opacity: 0.9; }
.btn-save:disabled { opacity: 0.6; cursor: not-allowed; }
.edit-btn:hover, .btn-cancel:hover, .action-btn:hover { background-color: #f9fafb; }

.edit-btn svg { width: 16px; height: 16px; }

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

.icon-box svg { width: 20px; height: 20px; }

.input-wrapper { flex-grow: 1; }

.input-wrapper label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.4rem;
  font-size: 13px;
  color: #6b7280;
}

/* Estilo unificado para inputs (Bloqueados y Editables) */
.input-wrapper input, .select-input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px; /* Borde redondeado fijo */
  font-size: 15px;
  font-weight: 500;
  box-sizing: border-box;
}

/* Estilo para inputs bloqueados (readonly) */
input:disabled {
  background-color: #f9fafb;
  color: #111827;
  cursor: not-allowed;
}

/* Estilo para campos editables activos */
.editable-input {
  background-color: #ffffff;
  color: #111827;
  border-color: #1d4ed8 !important;
}

.editable-input:focus {
  outline: none;
  box-shadow: 0 0 0 2px #bfdbfe;
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
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 16px;
}

.card-header-icon svg { width: 20px; height: 20px; }

.no-margin { margin: 0 !important; }

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

/* =========================================
   DISEÑO RESPONSIVO (MÓVILES)
   ========================================= */
@media (max-width: 1049px) {
 
  .main-content {
    padding: 1rem; 
  }

  
  .summary-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 20px;
  }

  
  .badges-container {
    flex-wrap: wrap;
  }

  
  .action-buttons, .edit-actions {
    width: 100%;
  }

  
  .edit-btn {
    width: 100%;
    justify-content: center;
  }

  
  .btn-cancel, .btn-save {
    flex: 1;
    justify-content: center;
  }

  
  .security-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }

  .security-row .action-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>