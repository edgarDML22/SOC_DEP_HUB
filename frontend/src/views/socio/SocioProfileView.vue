<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useProfileStore } from '@/stores/profiles/socioStore';
import PanelPenalizaciones from '@/components/socio/PanelPenalizaciones.vue';
import {
  IconArrowLeft, IconEdit, IconUser, IconIdCard, IconCreditCard,
  IconShield, IconLock, IconCalendar, IconMail, IconGender
} from '@/components/icons';

const router = useRouter();
const profileStore = useProfileStore();

const isEditing = ref(false);
const isSaving = ref(false);

const formData = reactive({
  fecha_nacimiento: '',
  genero: ''
});

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

const logout = () => {
  profileStore.logout();
};

const formatearFecha = (fecha) => {
  if (!fecha) return 'N/A';
  const str = String(fecha);
  const datePart = str.split(/[ T]/)[0];
  const d = new Date(`${datePart}T00:00:00`);
  if (isNaN(d.getTime())) return fecha;
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans p-4 md:p-6 lg:p-8 pb-24 lg:pb-8 flex justify-center">
    
    <div class="w-full max-w-5xl flex flex-col gap-6">

      <div class="mb-2">
        <button @click="router.back()" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-6 focus:outline-none w-fit group">
            <IconArrowLeft class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" /> Volver
        </button>
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-5 border-b border-surface-200 pb-5">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight" >Mi Perfil</h1>
                <p class="text-sm md:text-base font-medium text-surface-500 m-0 mt-2">Gestiona tu Información Personal</p>
            </div>
            
            <button @click="logout" class="px-6 py-2.5 w-full md:w-auto bg-red-50 border border-red-200 text-red-700 hover:bg-red-100 hover:border-red-300 font-semibold rounded-xl shadow-sm transition-all flex items-center justify-center gap-2 active:scale-95">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Cerrar Sesión
            </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6 items-start">

        <div class="flex flex-col gap-6 order-2 lg:order-1">
          
          <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm h-full">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-gray-100 pb-4 mb-4 gap-4">
              <h3 class="text-lg font-bold text-gray-900 m-0 tracking-tight">Datos del Socio</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-7">
              

              <div class="flex items-start gap-4 p-3 bg-gray-50/50 rounded-xl border border-gray-100">
                <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center text-primary-600 shrink-0 shadow-sm border border-gray-100">
                  <IconMail class="w-5 h-5" />
                </div>
                <div class="grow min-w-0 flex flex-col justify-center h-12">
                  <label class="block font-medium text-[11px] text-gray-500 uppercase tracking-widest">Correo Electrónico</label>
                  <input type="text" :value="profileStore.correoElectronico" readonly disabled 
                         class="w-full bg-transparent text-sm md:text-base font-semibold text-gray-900 focus:outline-none truncate" />
                </div>
              </div>

              <div class="flex items-start gap-4 p-3 bg-gray-50/50 rounded-xl border border-gray-100">
                <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shrink-0 shadow-sm border border-gray-100 text-primary-600">
                  <IconCalendar class="w-5 h-5" />
                </div>
                <div class="grow min-w-0 flex flex-col justify-center h-12">
                  <label class="block font-medium text-[11px] text-gray-500 uppercase tracking-widest">Nacimiento</label>
                  <input type="text" :value="formatearFecha(profileStore.fechaNacimiento)" readonly disabled 
                         class="w-full bg-transparent text-sm md:text-base font-semibold text-gray-900 focus:outline-none truncate" />
                </div>
              </div>

              <div class="flex items-start gap-4 p-3 bg-gray-50/50 rounded-xl border border-gray-100">
                <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center shrink-0 shadow-sm border border-gray-100 text-primary-600">
                  <IconGender class="w-5 h-5" />
                </div>
                <div class="grow min-w-0 flex flex-col justify-center h-12">
                  <label class="block font-medium text-[11px] text-gray-500 uppercase tracking-widest">Género</label>
                  <input type="text" :value="profileStore.genero === 'M' ? 'Masculino' : profileStore.genero === 'F' ? 'Femenino' : 'Otro'" readonly disabled 
                         class="w-full bg-transparent text-sm md:text-base font-semibold text-gray-900 focus:outline-none" />
                </div>
              </div>
              
              <div class="flex items-start gap-4 p-3 bg-gray-50/50 rounded-xl border border-gray-100">
                <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center text-primary-600 shrink-0 shadow-sm border border-gray-100">
                  <IconIdCard class="w-5 h-5" />
                </div>
                <div class="grow min-w-0 flex flex-col justify-center h-12">
                  <label class="block font-medium text-[11px] text-gray-500 uppercase tracking-widest">Número de Acción</label>
                  <input type="text" :value="profileStore.actionNumber || 'N/A'" readonly disabled 
                         class="w-full bg-transparent text-sm md:text-base font-bold text-gray-900 focus:outline-none truncate" />
                </div>
              </div>

            </div>

          </div>
          
        </div>

        <div class="flex flex-col gap-6 order-1 lg:order-2">

          <div class="bg-linear-to-br from-primary-800 to-primary-600 rounded-3xl p-6 shadow-lg relative overflow-hidden flex flex-col items-center text-center">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="w-24 h-24 bg-white/20 backdrop-blur-md text-white rounded-full flex items-center justify-center text-3xl font-bold shadow-sm border-2 border-white/30 mb-4 z-10">
              {{ profileStore.userInitials }}
            </div>
            
            <h2 class="text-xl font-bold text-white mb-4 z-10">{{ profileStore.fullName }}</h2>

            <div class="flex flex-col gap-2 w-full z-10">
              <div class="bg-white/10 rounded-xl p-3.5 border border-white/10 flex items-center justify-between backdrop-blur-sm">
                <span class="text-primary-100 text-xs font-medium uppercase tracking-wider">Estatus</span>
                <span class="px-3 py-1 bg-white text-primary-800 rounded-full text-[10px] font-bold uppercase shadow-sm">{{ profileStore.statusAccount }}</span>
              </div>
              <div class="bg-white/10 rounded-xl p-3.5 border border-white/10 flex items-center justify-between backdrop-blur-sm">
                <span class="text-primary-100 text-xs font-medium uppercase tracking-wider">Miembro</span>
                <span class="text-white text-sm font-bold truncate max-w-[120px]">{{ profileStore.typeSocio }}</span>
              </div>
              <div class="bg-white/10 rounded-xl p-3.5 border border-white/10 flex items-center justify-between backdrop-blur-sm" v-if="profileStore.modalidadPlan !== 'N/A'">
                <span class="text-primary-100 text-xs font-medium uppercase tracking-wider">Plan Activo</span>
                <span class="text-white text-sm font-bold truncate max-w-[120px]">{{ profileStore.modalidadPlan }}</span>
              </div>
            </div>
          </div>
          
          <!-- Inserción del nuevo panel unificado -->
          <PanelPenalizaciones />

          <div class="bg-white rounded-3xl border border-surface-200 p-5 flex flex-col gap-4 shadow-sm group hover:border-primary-200 transition-colors">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-primary-600 shrink-0 shadow-sm border border-surface-100 group-hover:bg-primary-50 group-hover:border-primary-200 transition-all">
                  <IconLock class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-base font-bold text-surface-900 m-0 leading-tight">Seguridad</h3>
                <p class="text-[11px] font-medium text-surface-500 m-0 mt-0.5 uppercase tracking-wider">Contraseña y Acceso</p>
              </div>
            </div>
            
            <button @click="$router.push('/forgot-password')" class="w-full bg-surface-50 hover:bg-surface-100 text-surface-700 border border-surface-200 rounded-xl px-4 py-2.5 font-semibold transition-all active:scale-95 flex items-center justify-center mt-1 text-sm shadow-sm group-hover:shadow">
              Cambiar Contraseña
            </button>
          </div>

        </div>

      </div>
    </div>
  </main>
</template>