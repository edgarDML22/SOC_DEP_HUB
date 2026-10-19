<script setup>
/**
 * QrCodeDisplay — QR de acceso homogéneo con acciones de Copiar y Descargar.
 * Se genera en el navegador (qrcode.vue), así copiar no depende de servicios externos.
 * Usado por QrCodeModal y por los pasos de "registro exitoso" de familiares e invitados.
 */
import { computed, ref } from 'vue';
import QrcodeVue from 'qrcode.vue';
import { useAlerts } from '@/composables/useAlerts';
import AppButton from '@/components/ui/AppButton.vue';
import { IconQr } from '@/components/icons';

const props = defineProps({
  value: { type: String, default: '' },
  name: { type: String, default: '' },
  caption: { type: String, default: '' },
});

const { toastSuccess, toastError, toastInfo } = useAlerts();
const qrWrap = ref(null);

const hasCode = computed(() => !!props.value && props.value !== 'SIN_QR');
const fileName = computed(() => {
  const slug = (props.name || 'acceso')
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .replace(/[^a-zA-Z0-9]+/g, '-').replace(/^-|-$/g, '').toLowerCase();
  return `qr-${slug || 'acceso'}.png`;
});

const getCanvas = () => qrWrap.value?.querySelector('canvas');

const download = () => {
  const canvas = getCanvas();
  if (!canvas) return;
  const link = document.createElement('a');
  link.href = canvas.toDataURL('image/png');
  link.download = fileName.value;
  link.click();
};

const copy = async () => {
  const canvas = getCanvas();
  if (!canvas) return;
  try {
    if (!navigator.clipboard?.write || typeof ClipboardItem === 'undefined') throw new Error('unsupported');
    // La promesa dentro de ClipboardItem es la forma compatible con Safari (iOS)
    await navigator.clipboard.write([
      new ClipboardItem({ 'image/png': new Promise((resolve) => canvas.toBlob(resolve, 'image/png')) }),
    ]);
    toastSuccess('Código QR copiado al portapapeles');
  } catch {
    // Navegadores sin soporte para copiar imágenes: se descarga como respaldo
    try {
      download();
      toastInfo('QR descargado', 'Tu navegador no permite copiar imágenes; se descargó el código QR.', 'info');
    } catch {
      toastError('No se pudo copiar el código QR');
    }
  }
};
</script>

<template>
  <div class="flex flex-col items-center text-center">
    <!-- QR sobre fondo blanco (también en modo oscuro, para que siempre sea escaneable) -->
    <div class="qr-container relative rounded-3xl bg-white p-3 shadow-[0_18px_36px_-20px_rgba(29,78,216,0.55)] ring-1 ring-primary-100">
      <div class="pointer-events-none absolute -inset-px rounded-3xl bg-gradient-to-br from-primary-500/15 via-transparent to-sky-400/15" aria-hidden="true"></div>
      <div v-if="hasCode" ref="qrWrap" class="qr-box relative h-52 w-52 sm:h-56 sm:w-56">
        <QrcodeVue :value="value" :size="512" :margin="2" level="M" render-as="canvas" />
      </div>
      <div v-else class="relative flex h-52 w-52 flex-col items-center justify-center gap-2 text-surface-400 sm:h-56 sm:w-56">
        <IconQr class="h-10 w-10" />
        <span class="text-xs font-bold uppercase tracking-wider">Sin código activo</span>
      </div>
    </div>

    <p v-if="name" class="m-0 mt-4 text-base font-bold leading-tight text-surface-900">{{ name }}</p>
    <span
      v-if="caption"
      class="mt-1.5 inline-flex rounded-full bg-primary-50 px-3 py-1 text-xs font-bold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
    >
      {{ caption }}
    </span>
    <p class="m-0 mt-3 max-w-[260px] text-xs font-medium text-surface-600">
      Muestra este código en la entrada del club para registrar el acceso.
    </p>

    <div v-if="hasCode" class="mt-5 grid w-full grid-cols-2 gap-2.5">
      <AppButton @click="copy">
        <template #icon>
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="13" height="13" x="9" y="9" rx="2" /><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
          </svg>
        </template>
        Copiar
      </AppButton>
      <AppButton variant="soft" @click="download">
        <template #icon>
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
          </svg>
        </template>
        Descargar
      </AppButton>
    </div>
  </div>
</template>

<style scoped>
/* El canvas se genera a 512px (copia nítida) y se muestra al tamaño del contenedor */
.qr-box :deep(canvas) {
  display: block;
  width: 100% !important;
  height: 100% !important;
  border-radius: 0.75rem;
}
</style>
