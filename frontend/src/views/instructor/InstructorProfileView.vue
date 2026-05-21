<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useInstructorStore } from '@/stores/profiles/instructorStore';

// Nuevos iconos
import {
  IconArrowLeft, IconEnvelope, IconPhone, IconBriefcase, IconClock,
  IconHistory, IconSupport, IconLock, IconLogout, IconUser
} from '@/components/icons';

const router = useRouter();
const profileStore = useInstructorStore();

onMounted(() => {
  profileStore.fetchProfile();
});

const handleLogout = () => {
  profileStore.logout();
};
</script>

<template>
  <main class="w-full bg-surface-50 min-h-screen font-sans p-4 md:p-6 lg:p-8 pb-24 lg:pb-8 flex justify-center">
    
    <div class="w-full max-w-5xl flex flex-col gap-6">

      <!-- Header Volver -->
      <div class="mb-2">
        <button @click="router.back()" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors mb-6 focus:outline-none w-fit group">
            <IconArrowLeft class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" /> Volver
        </button>
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-5 border-b border-surface-200 pb-5">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-surface-900 m-0 tracking-tight" >Mi Perfil</h1>
                <p class="text-sm md:text-base font-medium text-surface-500 m-0 mt-2">Gestión de Información y Seguridad</p>
            </div>
            
            <button @click="handleLogout" class="px-6 py-2.5 w-full md:w-auto bg-red-50 border border-red-200 text-red-700 hover:bg-red-100 hover:border-red-300 font-semibold rounded-xl shadow-sm transition-all flex items-center justify-center gap-2 active:scale-95">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Cerrar Sesión
            </button>
        </div>
      </div>

      <!-- Grid Layout para Desktop -->
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6 items-start">

        <!-- Lado Izquierdo (MÁS ANCHO) -->
        <div class="flex flex-col gap-6 order-2 lg:order-1">
          
          <!-- TARJETA 1: DATOS PERSONALES Y PROFESIONALES -->
          <div class="bg-white rounded-3xl border border-surface-200 p-6 sm:p-8 shadow-sm">
            <h3 class="text-xl font-bold text-surface-900 m-0 tracking-tight border-b border-surface-100 pb-5 mb-6">Datos del Instructor</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
              
              <div class="flex items-start gap-4 p-3 bg-surface-50/50 rounded-2xl border border-surface-100">
                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-primary-600 shrink-0 shadow-sm border border-surface-100">
                  <IconEnvelope class="w-5 h-5" />
                </div>
                <div class="grow min-w-0 flex flex-col justify-center h-12">
                  <label class="block font-medium text-[11px] text-surface-500 uppercase tracking-widest">Correo Electrónico</label>
                  <input type="text" :value="profileStore.email || 'No disponible'" readonly disabled 
                         class="w-full bg-transparent text-sm md:text-base font-semibold text-surface-900 focus:outline-none truncate" />
                </div>
              </div>

              <div class="flex items-start gap-4 p-3 bg-surface-50/50 rounded-2xl border border-surface-100">
                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-primary-600 shrink-0 shadow-sm border border-surface-100">
                  <IconPhone class="w-5 h-5" />
                </div>
                <div class="grow min-w-0 flex flex-col justify-center h-12">
                  <label class="block font-medium text-[11px] text-surface-500 uppercase tracking-widest">Teléfono</label>
                  <input type="text" :value="profileStore.phone || 'No registrado'" readonly disabled 
                         class="w-full bg-transparent text-sm md:text-base font-semibold text-surface-900 focus:outline-none truncate" />
                </div>
              </div>

              <div class="flex items-start gap-4 p-3 bg-surface-50/50 rounded-2xl border border-surface-100">
                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-primary-600 shrink-0 shadow-sm border border-surface-100">
                  <IconClock class="w-5 h-5" />
                </div>
                <div class="grow min-w-0 flex flex-col justify-center h-12">
                  <label class="block font-medium text-[11px] text-surface-500 uppercase tracking-widest">Contratación</label>
                  <input type="text" :value="profileStore.hireDate || 'Pendiente'" readonly disabled 
                         class="w-full bg-transparent text-sm md:text-base font-semibold text-surface-900 focus:outline-none truncate" />
                </div>
              </div>

            </div>
          </div>


        </div>

        <!-- Lado Derecho (WIDGETS) -->
        <div class="flex flex-col gap-6 order-1 lg:order-2">

          <!-- HERO CARD: RESUMEN Y AVATAR -->
          <div class="bg-linear-to-br from-primary-800 to-primary-600 rounded-3xl p-6 shadow-lg relative overflow-hidden flex flex-col items-center text-center">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="w-24 h-24 bg-white/20 backdrop-blur-md text-white rounded-full flex items-center justify-center text-3xl font-bold shadow-sm border-2 border-white/30 mb-4 z-10">
              {{ profileStore.userInitials }}
            </div>
            
            <h2 class="text-xl font-bold text-white mb-2 z-10">{{ profileStore.fullName || 'Cargando...' }}</h2>

            <div class="flex flex-col gap-2 w-full z-10 mt-3">
              <div class="bg-white/10 rounded-xl p-3.5 border border-white/10 flex items-center justify-between backdrop-blur-sm">
                <span class="text-primary-100 text-xs font-medium uppercase tracking-wider">Puesto</span>
                <span class="px-3 py-1 bg-white text-primary-800 rounded-full text-[10px] font-bold uppercase shadow-sm">{{ profileStore.role || 'INSTRUCTOR' }}</span>
              </div>
              <div class="bg-white/10 rounded-xl p-3.5 border border-white/10 flex items-center justify-between backdrop-blur-sm">
                <span class="text-primary-100 text-xs font-medium uppercase tracking-wider">Especialidad</span>
                <span class="text-white text-sm font-bold truncate max-w-[140px]">{{ profileStore.discipline || 'No disponible' }}</span>
              </div>
            </div>
          </div>
          
          <!-- TARJETA: SEGURIDAD (WIDGET) -->
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

          <!-- MENU DE ACCIONES -->
          <div class="bg-white rounded-3xl border border-surface-200 shadow-sm overflow-hidden flex flex-col">
            <button class="w-full flex items-center justify-between p-5 bg-white hover:bg-surface-50 transition-colors border-b border-surface-100 group">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-primary-600 shrink-0 shadow-sm border border-surface-100 group-hover:bg-primary-50 group-hover:border-primary-200 transition-all">
                  <IconHistory class="w-5 h-5" />
                </div>
                <span class="font-semibold text-surface-900">Historial de sesiones</span>
              </div>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-surface-400 group-hover:text-primary-600 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </button>
            
            <button @click="profileStore.getSupportLink()" class="w-full flex items-center justify-between p-5 bg-white hover:bg-surface-50 transition-colors group">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-primary-600 shrink-0 shadow-sm border border-surface-100 group-hover:bg-primary-50 group-hover:border-primary-200 transition-all">
                  <IconSupport class="w-5 h-5" />
                </div>
                <span class="font-semibold text-surface-900">Soporte y Ayuda</span>
              </div>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-surface-400 group-hover:text-primary-600 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </button>
          </div>

        </div>

      </div>
    </div>
  </main>
</template>
