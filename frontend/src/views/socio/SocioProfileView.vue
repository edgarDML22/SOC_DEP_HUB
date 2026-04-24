<script setup>

import { ref, reactive, onMounted, watch } from 'vue';

import { useProfileStore } from '@/stores/profiles/socioStore';

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
  <main class="w-full flex-1 p-4 sm:p-8 flex justify-center">
    <!-- Reducimos el ancho máximo para que no se estire tanto (max-w-3xl) y se vea mejor proporcionado -->
    <div class="w-full max-w-3xl">

      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-surface-900 mb-1 " >Mi Perfil</h1>
        <p class="text-sm text-surface-500 m-0">Gestiona tu Información Personal</p>
      </div>

      <!-- Alerta -->
      <div v-if="profileStore.profileData?.estatus_cuenta === 'MOROSO'" 
           class="bg-red-50 text-red-800 p-4 border border-red-400 rounded-medium mb-6 text-sm">
        ⚠️ Atención: El estatus de esta cuenta es <strong class="font-bold">{{ profileStore.statusAccount }}</strong>.
      </div>

      <!-- Contenedor principal de tarjetas -->
      <div class="flex flex-col gap-6">
        
        <!-- TARJETA 1: RESUMEN -->
        <div class="card p-8 sm:p-10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
          <div class="flex items-center gap-6">
            <!-- Avatar -->
            <div class="w-16 h-16 bg-primary-700 text-white rounded-full flex items-center justify-center text-xl font-semibold shrink-0">
              {{ profileStore.userInitials }}
            </div>
            
            <!-- Info y Badges -->
            <div>
              <h2 class="text-lg font-semibold text-surface-900 mb-2">{{ profileStore.fullName }}</h2>
              <div class="flex flex-wrap gap-2">
                <span class="badge" :class="profileStore.statusBadgeClass">{{ profileStore.statusAccount }}</span>
                <span class="badge badge-gray">{{ profileStore.typeSocio }}</span>
                <span class="badge badge-gray" v-if="profileStore.modalidadPlan !== 'N/A'">{{ profileStore.modalidadPlan }}</span>
                <span class="badge badge-green" v-if="!profileStore.profileData?.contador_no_shows || profileStore.profileData?.contador_no_shows === 0">
                  Cuenta al corriente (0 faltas)
                </span>
                <span class="badge bg-yellow-100 text-yellow-800" v-else>
                  {{ profileStore.profileData?.contador_no_shows }} Falta(s) registradas
                </span>
              </div>
            </div>
          </div>
          
          <!-- Botones de Acción -->
          <div class="w-full sm:w-auto mt-4 sm:mt-0">
            <button v-if="!isEditing" @click="toggleEdit" class="action-btn w-full sm:w-auto flex justify-center items-center gap-2">
              <IconEdit class="w-4 h-4" />
              Editar
            </button>
            <div v-else class="flex gap-3 w-full sm:w-auto">
              <button @click="toggleEdit" class="btn-cancel flex-1 sm:flex-none flex justify-center" :disabled="isSaving">Cancelar</button>
              <button @click="handleSave" class="btn-primary flex-1 sm:flex-none flex justify-center" :disabled="isSaving">
                {{ isSaving ? 'Guardando...' : 'Guardar' }}
              </button>
            </div>
          </div>
        </div>

        <!-- TARJETA 2: DETALLES -->
        <div class="card p-8 sm:p-10">
          <h3 class="text-base font-semibold text-surface-900 mb-6">Información personal</h3>

          <!-- Formulario -->
          <div class="flex flex-col gap-8">
            
            <!-- Grupo: Nombre Completo -->
            <div class="flex items-center gap-5">
              <div class="w-12 h-12 bg-surface-50 rounded-xl flex items-center justify-center text-surface-500 shrink-0">
                <IconUser class="w-5 h-5" />
              </div>
              <div class="grow">
                <label for="nombre" class="block font-medium text-[13px] text-surface-500 mb-1.5">Nombre Completo</label>
                <input id="nombre" type="text" :value="profileStore.fullName" readonly disabled 
                       class="w-full px-4 py-3.5 border border-surface-200 rounded-medium text-[15px] font-medium bg-surface-50 text-surface-900 cursor-not-allowed" />
              </div>
            </div>

            <!-- Grupo: Correo -->
            <div class="flex items-center gap-5">
              <div class="w-12 h-12 bg-surface-50 rounded-xl flex items-center justify-center text-surface-500 shrink-0">
                <IconMail class="w-5 h-5" />
              </div>
              <div class="grow">
                <label for="correo" class="block font-medium text-[13px] text-surface-500 mb-1.5">Correo Electrónico</label>
                <input id="correo" type="text" :value="profileStore.correoElectronico" readonly disabled 
                       class="w-full px-4 py-3.5 border border-surface-200 rounded-medium text-[15px] font-medium bg-surface-50 text-surface-900 cursor-not-allowed" />
              </div>
            </div>

            <!-- Grupo: Fecha Nacimiento -->
            <div class="flex items-center gap-5">
              <div class="w-12 h-12 bg-surface-50 rounded-xl flex items-center justify-center text-surface-500 shrink-0">
                <IconCalendar class="w-5 h-5" />
              </div>
              <div class="grow">
                <label for="fecha_nac" class="block font-medium text-[13px] text-surface-500 mb-1.5">Fecha de Nacimiento</label>
                <input v-if="isEditing" id="fecha_nac" type="date" v-model="formData.fecha_nacimiento" 
                       class="w-full px-4 py-3.5 border border-primary-700 rounded-medium text-[15px] font-medium bg-white text-surface-900 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-shadow" />
                <input v-else id="fecha_nac" type="text" :value="profileStore.fechaNacimiento" readonly disabled 
                       class="w-full px-4 py-3.5 border border-surface-200 rounded-medium text-[15px] font-medium bg-surface-50 text-surface-900 cursor-not-allowed" />
              </div>
            </div>

            <!-- Grupo: Género -->
            <div class="flex items-center gap-5">
              <div class="w-12 h-12 bg-surface-50 rounded-xl flex items-center justify-center text-surface-500 shrink-0">
                <IconGender class="w-5 h-5" />
              </div>
              <div class="grow">
                <label for="genero" class="block font-medium text-[13px] text-surface-500 mb-1.5">Género</label>
                <select v-if="isEditing" id="genero" v-model="formData.genero" 
                        class="w-full px-4 py-3.5 border border-primary-700 rounded-medium text-[15px] font-medium bg-white text-surface-900 focus:outline-none focus:ring-2 focus:ring-blue-200 transition-shadow">
                  <option value="M">Masculino</option>
                  <option value="F">Femenino</option>
                  <option value="OTRO">Otro</option>
                </select>
                <input v-else id="genero" type="text" :value="profileStore.genero === 'M' ? 'Masculino' : profileStore.genero === 'F' ? 'Femenino' : 'Otro'" readonly disabled 
                       class="w-full px-4 py-3.5 border border-surface-200 rounded-medium text-[15px] font-medium bg-surface-50 text-surface-900 cursor-not-allowed" />
              </div>
            </div>

            <!-- Grupo: Número de Acción -->
            <div class="flex items-center gap-5">
              <div class="w-12 h-12 bg-surface-50 rounded-xl flex items-center justify-center text-surface-500 shrink-0">
                <IconIdCard class="w-5 h-5" />
              </div>
              <div class="grow">
                <label for="num_accion" class="block font-medium text-[13px] text-surface-500 mb-1.5">Número de Acción</label>
                <input id="num_accion" type="text" :value="profileStore.actionNumber" readonly disabled 
                       class="w-full px-4 py-3.5 border border-surface-200 rounded-medium text-[15px] font-medium bg-surface-50 text-surface-900 cursor-not-allowed" />
              </div>
            </div>

            <!-- Grupo: Rol -->
            <div class="flex items-center gap-5">
              <div class="w-12 h-12 bg-surface-50 rounded-xl flex items-center justify-center text-surface-500 shrink-0">
                <IconCreditCard class="w-5 h-5" />
              </div>
              <div class="grow">
                <label for="tipo_socio" class="block font-medium text-[13px] text-surface-500 mb-1.5">Rol / Tipo de Socio</label>
                <input id="tipo_socio" type="text" :value="profileStore.typeSocio" readonly disabled 
                       class="w-full px-4 py-3.5 border border-surface-200 rounded-medium text-[15px] font-medium bg-surface-50 text-surface-900 cursor-not-allowed" />
              </div>
            </div>

            <!-- Grupo: Estatus -->
            <div class="flex items-center gap-5">
              <div class="w-12 h-12 bg-surface-50 rounded-xl flex items-center justify-center text-surface-500 shrink-0">
                <IconShield class="w-5 h-5" />
              </div>
              <div class="grow">
                <label for="estatus" class="block font-medium text-[13px] text-surface-500 mb-1.5">Estatus de Cuenta</label>
                <input id="estatus" type="text" :value="profileStore.statusAccount" readonly disabled 
                       class="w-full px-4 py-3.5 border border-surface-200 rounded-medium text-[15px] font-medium bg-surface-50 text-surface-900 cursor-not-allowed" />
              </div>
            </div>

          </div>
        </div>

        <!-- TARJETA 3: SEGURIDAD -->
        <div class="card p-8 sm:p-10 flex flex-col gap-6">
          <div class="flex items-center gap-3 border-b border-surface-200 pb-4">
            <IconLock class="w-5 h-5 text-surface-500" />
            <h3 class="text-base font-semibold text-surface-900 m-0">Seguridad</h3>
          </div>

          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 sm:gap-0">
            <div>
              <h4 class="text-sm font-semibold text-surface-900 mb-1.5">Contraseña</h4>
              <p class="text-xs text-surface-500 m-0">{{ profileStore.passwordUpdateText }}</p>
            </div>
            <button @click="$router.push('/forgot-password')" class="action-btn w-full sm:w-auto flex justify-center">Cambiar</button>
          </div>
        </div>

        <!-- TARJETA 4: LUDOTECA -->
        <div class="card p-8 sm:p-10 flex flex-col gap-6">
          <div class="flex items-center gap-3 border-b border-surface-200 pb-4">
            <IconLock class="w-5 h-5 text-surface-500" />
            <h3 class="text-base font-semibold text-surface-900 m-0">Ludoteca</h3>
          </div>

          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 sm:gap-0">
            <div>
              <p class="text-sm text-surface-500 m-0">Gestión de tus hijos</p>
            </div>
            <button @click="$router.push('socio-ludoteca')" class="btn-primary w-full sm:w-auto flex justify-center">Ver</button>
          </div>
        </div>

      </div>
    </div>
  </main>
</template>
