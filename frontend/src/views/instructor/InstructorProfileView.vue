<script setup>
import { ref, onMounted } from 'vue';
import { useInstructorStore } from '@/stores/profiles/instructorStore';

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
  <main class="home-instructor !gap-4">

    <header class="featured-card p-6 flex flex-col items-center text-center">
      <div class="w-20 h-20 bg-primary-600 text-white rounded-full flex items-center justify-center text-3xl font-black mb-4 shadow-lg shadow-primary-600/30">
        <span v-if="profileStore.isLoading">...</span>
        <span v-else>{{ profileStore.userInitials }}</span>
      </div>
      <h1 class="text-xl font-black text-surface-900 mb-1">{{ profileStore.fullName || 'Cargando...' }}</h1>
      <h2 class="text-xs font-bold text-surface-500 uppercase tracking-wider mb-2">{{ profileStore.role }}</h2>
      <p class="inline-flex items-center gap-2 bg-surface-100 text-surface-600 px-3 py-1.5 rounded-lg text-[10px] font-black border border-surface-200">
        {{ profileStore.discipline || 'No disponible' }}
      </p>
    </header>

    <section class="bg-white border border-surface-200 rounded-2xl p-6 flex flex-col gap-6 shadow-sm">
      <article class="flex items-start gap-4">
        <div class="w-10 h-10 bg-surface-50 rounded-xl flex items-center justify-center text-surface-400 shrink-0">
          <IconEnvelope class="w-5 h-5" />
        </div>
        <div class="flex flex-col">
          <span class="text-[10px] font-bold text-surface-400 uppercase tracking-tight">Email</span>
          <span class="text-sm font-bold text-surface-900">{{ profileStore.email || 'No disponible' }}</span>
        </div>
      </article>

      <article class="flex items-start gap-4">
        <div class="w-10 h-10 bg-surface-50 rounded-xl flex items-center justify-center text-surface-400 shrink-0">
          <IconPhone class="w-5 h-5" />
        </div>
        <div class="flex flex-col">
          <span class="text-[10px] font-bold text-surface-400 uppercase tracking-tight">Teléfono</span>
          <span class="text-sm font-bold text-surface-900">{{ profileStore.phone || 'No registrado' }}</span>
        </div>
      </article>

      <article class="flex items-start gap-4">
        <div class="w-10 h-10 bg-surface-50 rounded-xl flex items-center justify-center text-surface-400 shrink-0">
          <IconBriefcase class="w-5 h-5" />
        </div>
        <div class="flex flex-col">
          <span class="text-[10px] font-bold text-surface-400 uppercase tracking-tight">Rol Administrativo</span>
          <span class="text-sm font-bold text-surface-900">{{ profileStore.role }}</span>
        </div>
      </article>

      <article class="flex items-start gap-4">
        <div class="w-10 h-10 bg-surface-50 rounded-xl flex items-center justify-center text-surface-400 shrink-0">
          <IconClock class="w-5 h-5" />
        </div>
        <div class="flex flex-col">
          <span class="text-[10px] font-bold text-surface-400 uppercase tracking-tight">Fecha de Contratación</span>
          <span class="text-sm font-bold text-surface-900">{{ profileStore.hireDate || 'Pendiente' }}</span>
        </div>
      </article>
    </section>

    <section class="bg-white border border-surface-200 rounded-2xl overflow-hidden shadow-sm">
      <button class="w-full flex items-center justify-between p-5 border-b border-surface-100 hover:bg-surface-50 transition-colors">
        <div class="flex items-center gap-4">
          <div class="w-8 h-8 bg-surface-50 rounded-lg flex items-center justify-center text-surface-400">
            <IconHistory class="w-4 h-4" />
          </div>
          <span class="text-sm font-bold text-surface-900">Historial de sesiones</span>
        </div>
        <span class="text-surface-300 font-black">›</span>
      </button>

      <button class="w-full flex items-center justify-between p-5 hover:bg-surface-50 transition-colors">
        <div class="flex items-center gap-4">
          <div class="w-8 h-8 bg-surface-50 rounded-lg flex items-center justify-center text-surface-400">
            <IconSupport class="w-4 h-4" />
          </div>
          <span class="text-sm font-bold text-surface-900">Soporte y Ayuda</span>
        </div>
        <span class="text-surface-300 font-black">›</span>
      </button>
    </section>

    <section class="bg-white border border-surface-200 rounded-2xl p-6 flex flex-col gap-6 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center text-primary-600">
          <IconLock class="w-4 h-4" />
        </div>
        <h2 class="text-sm font-black text-surface-900 uppercase tracking-tight">Seguridad de la Cuenta</h2>
      </div>

      <form @submit.prevent="handlePasswordUpdate" class="flex flex-col gap-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-bold text-surface-500 uppercase px-1">Contraseña actual</label>
          <input type="password" v-model="passwordData.current" placeholder="••••••••" class="w-full p-4 bg-surface-50 border border-surface-200 rounded-xl text-sm outline-none focus:border-primary-500 transition-all" required />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-bold text-surface-500 uppercase px-1">Nueva contraseña</label>
          <input type="password" v-model="passwordData.new" placeholder="Mínimo 8 caracteres" class="w-full p-4 bg-surface-50 border border-surface-200 rounded-xl text-sm outline-none focus:border-primary-500 transition-all" minlength="8" required />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-[10px] font-bold text-surface-500 uppercase px-1">Confirmar nueva</label>
          <input type="password" v-model="passwordData.confirm" placeholder="Repite la contraseña" class="w-full p-4 bg-surface-50 border border-surface-200 rounded-xl text-sm outline-none focus:border-primary-500 transition-all" minlength="8" required />
        </div>

        <button type="submit" class="w-full bg-primary-600 text-white p-4 rounded-xl font-black text-sm shadow-lg shadow-primary-600/20 active:scale-[0.98] transition-all">
          Actualizar Contraseña
        </button>
      </form>
    </section>

    <button @click="handleLogout" class="w-full bg-white border border-red-200 text-red-600 p-4 rounded-2xl font-black text-sm flex items-center justify-center gap-3 hover:bg-red-50 transition-all mb-4">
      <IconLogout class="w-5 h-5" />
      Cerrar sesión
    </button>

  </main>
</template>

<style scoped>
/* No styles needed, using global system */
</style>
