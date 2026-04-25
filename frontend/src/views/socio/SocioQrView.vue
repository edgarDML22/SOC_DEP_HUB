<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import QrcodeVue from 'qrcode.vue';
import { useProfileStore } from '@/stores/profiles/socioStore';
import api from '@/services/api';
import { useRouter } from 'vue-router';
import { IconArrowLeft } from '@/components/icons';

const profileStore = useProfileStore();
const router = useRouter();

const qrPayload = ref('');
const loading = ref(true);
const error = ref('');

// Intervalo para refrescar el código QR por seguridad
let refreshInterval = null;

const fetchQrData = async () => {
  try {
    loading.value = true;
    error.value = '';
    const response = await api.get('/profile/qr-data');
    if (response.data.success) {
      qrPayload.value = response.data.data.qr_payload;
    }
  } catch (err) {
    if (err.response?.status === 403) {
      error.value = err.response.data?.message ?? 'Tu cuenta no puede generar el código QR en este momento.';
    } else {
      error.value = 'No se pudo generar el código de acceso. Verifica tu conexión.';
    }
    console.error('Error al generar QR:', err);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchQrData();
  // Refresca el token cada 60 segundos por seguridad
  refreshInterval = setInterval(() => {
    fetchQrData();
  }, 60000); 
});

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval);
});
</script>

<template>
  <div class="w-full min-h-[calc(100vh-70px)] bg-surface-50 p-4 md:p-8 font-sans flex flex-col items-center justify-center pb-24 md:pb-8">
    
    <div class="w-full max-w-sm">
        
       <button @click="router.back()" class="flex items-center gap-2 text-surface-500 hover:text-primary-600 font-medium text-sm transition-colors focus:outline-none mb-6 group w-fit">
          <IconArrowLeft class="w-5 h-5 shrink-0 group-hover:-translate-x-1 transition-transform" /> Volver
       </button>

      <div class="bg-white rounded-[2rem] shadow-[0_12px_40px_rgba(0,0,0,0.06)] border border-surface-200 overflow-hidden relative">
        
        <!-- Estado Bloqueado de Cuenta -->
        <div v-if="profileStore.isAccountInactive" class="absolute inset-0 bg-white/80 backdrop-blur-md z-20 flex flex-col items-center justify-center p-8 text-center text-red-600">
           <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" /></svg>
           <h3 class="text-xl font-bold mb-2">Cuenta {{ profileStore.statusAccount }}</h3>
           <p class="font-medium text-sm text-surface-600 leading-relaxed">No puedes generar el Pase de Acceso en este momento. Por favor contacta administración.</p>
        </div>

        <div class="p-8 pb-6 flex flex-col items-center text-center">
            
            <div class="w-16 h-16 bg-primary-50 text-primary-600 rounded-full flex items-center justify-center mb-6 shadow-sm border border-primary-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><rect x="7" y="7" width="3" height="3"/><rect x="14" y="7" width="3" height="3"/><rect x="7" y="14" width="3" height="3"/><rect x="14" y="14" width="3" height="3"/></svg>
            </div>

            <h2 class="text-2xl font-extrabold text-surface-900 mb-2 tracking-tight">Pase de Acceso</h2>
            <p class="text-surface-500 font-medium text-sm mb-8 leading-relaxed">
              Muestra este código en las instalaciones para identificarte.
            </p>

            <div class="bg-white p-5 rounded-3xl border-2 border-dashed border-surface-200 w-full flex flex-col items-center justify-center min-h-[280px]">
                
                <div v-if="loading && !qrPayload" class="flex flex-col items-center gap-4 text-surface-400">
                    <div class="w-10 h-10 border-4 border-surface-200 border-t-primary-600 rounded-full animate-spin"></div>
                    <span class="font-bold text-sm tracking-widest uppercase">Generando...</span>
                </div>

                <div v-else-if="error" class="text-red-500 font-medium text-sm text-center">
                   <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto mb-2 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                   {{ error }}
                </div>

                <qrcode-vue v-else-if="qrPayload"
                  :value="qrPayload" 
                  :size="220" 
                  level="H" 
                  class="w-full h-auto max-w-[220px]"
                />
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-surface-50 border-t border-surface-100 p-5 w-full flex items-center justify-center gap-2 group cursor-pointer" @click="fetchQrData">
           <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary-500" :class="{'animate-spin text-primary-600': loading}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
           <span class="text-[11px] font-bold text-surface-500 uppercase tracking-widest group-hover:text-primary-600 transition-colors">
               Código dinámico (Actualizado)
           </span>
        </div>

      </div>
    </div>
  </div>
</template>