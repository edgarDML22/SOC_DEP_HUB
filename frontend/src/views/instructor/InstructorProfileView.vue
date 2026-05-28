<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useInstructorStore } from '@/stores/profiles/instructorStore';

// Nuevos iconos
import {
  IconArrowLeft, IconEnvelope, IconPhone, IconBriefcase, IconClock,
  IconHistory, IconSupport, IconLock, IconLogout, IconUser
} from '@/components/icons';

const router = useRouter();
const profileStore = useInstructorStore();

const fileInput = ref(null);
const isUploading = ref(false);

const customAlert = reactive({
  show: false,
  title: '',
  message: '',
  type: 'error'
});

const fotoError = ref(false);

const showCustomAlert = (title, message, type = 'error') => {
  customAlert.title = title;
  customAlert.message = message;
  customAlert.type = type;
  customAlert.show = true;
};

const triggerFileSelect = () => {
  if (fileInput.value) {
    fileInput.value.click();
  }
};

const showCropModal = ref(false);
const rawImageSrc = ref('');
const cropZoom = ref(1);
const cropPosition = reactive({ x: 0, y: 0 });
const isDraggingCrop = ref(false);
const dragStart = reactive({ x: 0, y: 0 });
const selectedFile = ref(null);
const imgRef = ref(null);
const imageDimensions = reactive({ w: 0, h: 0 });

const onImageLoaded = (e) => {
  const img = e.target;
  const aspect = img.naturalWidth / img.naturalHeight;
  if (aspect > 1) {
    imageDimensions.h = 300;
    imageDimensions.w = 300 * aspect;
  } else {
    imageDimensions.w = 300;
    imageDimensions.h = 300 / aspect;
  }
  cropPosition.x = 0;
  cropPosition.y = 0;
};

const startDrag = (e) => {
  isDraggingCrop.value = true;
  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  const clientY = e.touches ? e.touches[0].clientY : e.clientY;
  dragStart.x = clientX - cropPosition.x;
  dragStart.y = clientY - cropPosition.y;

  if (e.touches) {
    window.addEventListener('touchmove', handleDrag, { passive: false });
    window.addEventListener('touchend', stopDrag);
  } else {
    window.addEventListener('mousemove', handleDrag);
    window.addEventListener('mouseup', stopDrag);
  }
};

const handleDrag = (e) => {
  if (!isDraggingCrop.value) return;
  if (e.cancelable) e.preventDefault();
  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  const clientY = e.touches ? e.touches[0].clientY : e.clientY;
  cropPosition.x = clientX - dragStart.x;
  cropPosition.y = clientY - dragStart.y;
};

const stopDrag = () => {
  isDraggingCrop.value = false;
  window.removeEventListener('mousemove', handleDrag);
  window.removeEventListener('mouseup', stopDrag);
  window.removeEventListener('touchmove', handleDrag);
  window.removeEventListener('touchend', stopDrag);
};

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  if (!file.type.startsWith('image/')) {
    showCustomAlert('Archivo no válido', 'Por favor selecciona un archivo de imagen válido.', 'error');
    return;
  }

  if (file.size > 5 * 1024 * 1024) {
    showCustomAlert('Tamaño excedido', 'La imagen de perfil no debe superar los 5MB para recortarla.', 'error');
    return;
  }

  selectedFile.value = file;

  const reader = new FileReader();
  reader.onload = (e) => {
    rawImageSrc.value = e.target.result;
    cropZoom.value = 1;
    cropPosition.x = 0;
    cropPosition.y = 0;
    showCropModal.value = true;
  };
  reader.readAsDataURL(file);
};

const cropAndUpload = () => {
  const imgElement = new Image();
  imgElement.onload = () => {
    const canvas = document.createElement('canvas');
    canvas.width = 400;
    canvas.height = 400;
    const ctx = canvas.getContext('2d');

    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';

    // Dibujar fondo difuminado (estilo cover) tomando los colores de la imagen original
    ctx.filter = 'blur(15px)';
    const imgRatio = imgElement.naturalWidth / imgElement.naturalHeight;
    let bgW = 400, bgH = 400;
    if (imgRatio > 1) {
      bgW = 400 * imgRatio;
    } else {
      bgH = 400 / imgRatio;
    }
    ctx.drawImage(imgElement, (400 - bgW) / 2, (400 - bgH) / 2, bgW, bgH);
    ctx.filter = 'none'; // Restaurar para que la foto principal no salga borrosa

    const ratio = 400 / 300;

    const drawW = imageDimensions.w * cropZoom.value * ratio;
    const drawH = imageDimensions.h * cropZoom.value * ratio;

    const centerX = 200 + cropPosition.x * ratio;
    const centerY = 200 + cropPosition.y * ratio;

    const drawX = centerX - drawW / 2;
    const drawY = centerY - drawH / 2;

    ctx.drawImage(imgElement, drawX, drawY, drawW, drawH);

    canvas.toBlob(async (blob) => {
      if (!blob) {
        showCustomAlert('Error', 'No se pudo procesar la imagen de corte.', 'error');
        return;
      }

      showCropModal.value = false;
      isUploading.value = true;

      const croppedFile = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
      const res = await profileStore.uploadPhoto(croppedFile);
      isUploading.value = false;

      if (!res || !res.success) {
        showCustomAlert('Error al subir', res.error || 'No se pudo subir la foto de perfil. Intenta de nuevo.', 'error');
      } else {
        showCustomAlert('¡Excelente!', 'Tu foto de perfil ha sido recortada y actualizada correctamente.', 'success');
      }
    }, 'image/jpeg', 0.95);
  };
  imgElement.src = rawImageSrc.value;
};

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
            
            <!-- Contenedor de Foto de Perfil con Cargador -->
            <div class="relative group cursor-pointer w-24 h-24 mb-4 z-10" @click="triggerFileSelect">
              <input type="file" ref="fileInput" accept="image/*" @change="handleFileChange" class="hidden" />
              
              <!-- Imagen de perfil -->
              <div class="w-full h-full rounded-full overflow-hidden border-2 border-white/40 shadow-md bg-white/10 flex items-center justify-center transition-all duration-300 group-hover:border-white group-hover:scale-105 relative">
                <div class="absolute inset-0 flex items-center justify-center text-3xl font-bold text-white uppercase select-none z-0">
                  {{ profileStore.userInitials }}
                </div>
                <img 
                  v-if="profileStore.fotoPerfil" 
                  :src="profileStore.fotoPerfil" 
                  @error="fotoError = true; $event.target.style.display = 'none'" 
                  @load="fotoError = false; $event.target.style.display = 'block'"
                  alt="Perfil" 
                  class="absolute inset-0 w-full h-full object-cover z-10 bg-primary-600" 
                />
              </div>

              <!-- Overlay interactivo para "Cambiar Foto" -->
              <div class="absolute inset-0 bg-black/60 rounded-full flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white mb-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                  <circle cx="12" cy="13" r="4"/>
                </svg>
                <span class="text-[9px] font-bold text-white uppercase tracking-wider">Cambiar</span>
              </div>

              <!-- Spinner de carga -->
              <div v-if="isUploading" class="absolute inset-0 bg-black/75 rounded-full flex items-center justify-center z-20">
                <div class="w-6 h-6 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
              </div>
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

              <!-- Botón Agregar/Cambiar Foto -->
              <button 
                @click="triggerFileSelect"
                class="mt-2 w-full py-2.5 px-4 bg-white hover:bg-primary-50 text-primary-800 font-bold rounded-xl shadow-xs transition-all active:scale-[0.98] flex items-center justify-center gap-2 text-xs border border-transparent cursor-pointer"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 text-primary-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                  <circle cx="12" cy="13" r="4"/>
                </svg>
                <span>{{ (profileStore.fotoPerfil && !fotoError) ? 'Cambiar foto de perfil' : 'Agregar foto de perfil' }}</span>
              </button>
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
    <!-- ══════════════════════════════════════════
         MODAL DE CORTE DE IMAGEN (WhatsApp Style)
    ══════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div v-if="showCropModal"
          class="fixed inset-0 z-[200] flex flex-col items-center justify-between p-6 bg-black font-sans"
        >
          <!-- Encabezado -->
          <div class="w-full flex items-center justify-between py-4 text-white max-w-[340px] shrink-0">
            <h3 class="text-base font-bold text-center tracking-wide grow">Mover y redimensionar</h3>
            <button @click="showCropModal = false" class="text-white hover:text-gray-300 transition-colors p-1.5 focus:outline-none cursor-pointer">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 6L6 18M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Contenedor del Visor de Recorte -->
          <div class="relative w-[300px] h-[300px] bg-neutral-950 overflow-hidden rounded-lg flex items-center justify-center select-none touch-none border border-white/10 shadow-lg">
            <!-- Imagen cargada -->
            <img 
              :src="rawImageSrc" 
              ref="imgRef"
              @load="onImageLoaded"
              :style="{
                width: `${imageDimensions.w}px`,
                height: `${imageDimensions.h}px`,
                transform: `translate(${cropPosition.x}px, ${cropPosition.y}px) scale(${cropZoom})`,
                cursor: isDraggingCrop ? 'grabbing' : 'grab'
              }" 
              class="max-w-none max-h-none absolute origin-center select-none touch-none pointer-events-auto"
              @mousedown="startDrag"
              @touchstart="startDrag"
            />

            <!-- Máscara de círculo (WhatsApp Crop Style) -->
            <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
              <svg class="w-full h-full text-black/60 fill-current" viewBox="0 0 300 300">
                <defs>
                  <mask id="cropMask">
                    <!-- Rectángulo blanco de fondo completo -->
                    <rect x="0" y="0" width="300" height="300" fill="white" />
                    <!-- Perforación circular negra en el centro (diámetro 240px, radio 120px) -->
                    <circle cx="150" cy="150" r="120" fill="black" />
                  </mask>
                </defs>
                <rect x="0" y="0" width="300" height="300" mask="url(#cropMask)" />
                <!-- Línea de guía blanca discontinua de recorte -->
                <circle cx="150" cy="150" r="120" stroke="white" stroke-width="2" stroke-dasharray="4 4" fill="none" class="opacity-75" />
              </svg>
            </div>
          </div>

          <!-- Controles de Zoom -->
          <div class="flex items-center gap-4 w-full max-w-[300px] text-white shrink-0">
            <!-- Icono Zoom Menos -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            <input 
              type="range" 
              v-model.number="cropZoom" 
              min="1" 
              max="3" 
              step="0.05" 
              class="grow accent-white h-1 bg-white/20 rounded-lg appearance-none cursor-pointer"
            />
            <!-- Icono Zoom Mas -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <line x1="12" y1="5" x2="12" y2="19" />
              <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
          </div>

          <!-- Botones de Acción (Estilo WhatsApp) -->
          <div class="flex justify-between w-full max-w-[300px] gap-4 mb-6 shrink-0">
            <button 
              type="button" 
              @click="showCropModal = false"
              class="flex-1 py-3 border border-white/20 rounded-full text-sm font-bold text-white hover:bg-white/10 active:scale-95 transition-all cursor-pointer"
            >
              Cancelar
            </button>
            <button 
              type="button" 
              @click="cropAndUpload"
              class="flex-1 py-3 bg-white text-black rounded-full text-sm font-black hover:bg-slate-100 active:scale-95 transition-all cursor-pointer shadow-md"
            >
              Seleccionar
            </button>
          </div>

        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════
         MODAL DE ALERTA PREMIUM PERSONALIZADO
    ══════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div v-if="customAlert.show"
          class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="customAlert.show = false"
        >
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
          >
            <div v-if="customAlert.show"
              class="bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl overflow-hidden border border-surface-100 font-sans"
            >
              <!-- Cabecera coloreada y estilizada según tipo -->
              <div
                class="px-7 py-7 text-white relative overflow-hidden flex flex-col items-center text-center"
                :class="customAlert.type === 'success'
                  ? 'bg-linear-to-br from-emerald-500 to-green-600'
                  : 'bg-linear-to-br from-rose-500 to-red-600'"
              >
                <!-- Círculos decorativos traslúcidos -->
                <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"/>
                <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"/>
                
                <div class="relative z-10 flex flex-col items-center">
                  <!-- Contenedor del Icono -->
                  <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center shrink-0 shadow-inner mb-3">
                    <!-- Icono Éxito -->
                    <svg v-if="customAlert.type === 'success'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-7 h-7">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <!-- Icono Error/Alerta -->
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-7 h-7">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376C1.83 19.13 4.021 21 6.5 21h11c2.479 0 4.67-1.87 3.303-4.874L13.793 4.293c-1.367-3.056-5.218-3.056-6.585 0L1.197 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                  </div>
                  
                  <h3 class="text-xl font-black leading-tight tracking-tight mt-1">{{ customAlert.title }}</h3>
                </div>
              </div>

              <!-- Cuerpo de la Alerta -->
              <div class="px-8 py-7 flex flex-col items-center bg-surface-50/20">
                <p class="text-sm font-semibold text-surface-600 text-center leading-relaxed mb-6 select-none">
                  {{ customAlert.message }}
                </p>

                <!-- Botón de Acción -->
                <button
                  type="button"
                  @click="customAlert.show = false"
                  class="w-full py-3.5 px-6 font-black rounded-2xl shadow-xs text-sm active:scale-[0.98] transition-all cursor-pointer text-center"
                  :class="customAlert.type === 'success'
                    ? 'bg-emerald-600 hover:bg-emerald-700 text-white'
                    : 'bg-red-600 hover:bg-red-700 text-white'"
                >
                  Entendido
                </button>
              </div>

            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
</template>
