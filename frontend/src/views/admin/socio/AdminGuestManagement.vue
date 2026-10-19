<script setup>
import { onMounted, computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminGuestStore } from '@/stores/admin/adminGuestStore';
import { useSocioStore } from '@/stores/admin/socioStore';
import { storeToRefs } from 'pinia';
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue';
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue';
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import { IconUser, IconMail, IconPhone, IconQr, IconTrash } from '@/components/icons';
import IconUserPlus from '@/components/icons/IconUserPlus.vue';
import SearchInput from '@/components/gerente/ui/SearchInput.vue';
import AppModal from '@/components/ui/modal/AppModal.vue';
import AppConfirmModal from '@/components/ui/modal/AppConfirmModal.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppField from '@/components/ui/form/AppField.vue';
import AppInput from '@/components/ui/form/AppInput.vue';
import QrCodeDisplay from '@/components/ui/qr/QrCodeDisplay.vue';
import QrCodeModal from '@/components/ui/qr/QrCodeModal.vue';
import { useAlerts } from '@/composables/useAlerts';
import { useformat } from '@/utils/formatters';

const { dateFormat } = useformat();
const { toastSuccess, toastError } = useAlerts();

const route = useRoute();
const router = useRouter();
const guestStore = useAdminGuestStore();
const socioStore = useSocioStore();

const { guests, activePasses, isLoading, loading, error } = storeToRefs(guestStore);
const { currentSocio } = storeToRefs(socioStore);

const socioId = route.params.id;

// El store reutiliza `error` también al crear/editar: la lista solo se reemplaza por
// el estado de error cuando de verdad no hay datos que mostrar.
const loadError = computed(() => (!guests.value.length ? error.value : null));

// AVATAR HELPERS
const AVATAR_GRADIENTS = [
  'from-primary-400 to-primary-600',
  'from-emerald-400 to-emerald-600',
  'from-purple-400 to-purple-600',
  'from-orange-400 to-orange-600',
  'from-rose-400 to-rose-600',
  'from-cyan-400 to-cyan-600',
];

const avatarGradient = (name = '') => {
  const idx = (name.charCodeAt(0) ?? 0) % AVATAR_GRADIENTS.length;
  return AVATAR_GRADIENTS[idx];
};

const initials = (name = '') => {
  const parts = name.trim().split(' ').filter(Boolean);
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
  return (parts[0]?.[0] ?? '?').toUpperCase();
};

// SEARCH & SORTED GUESTS
const search = ref('');

const filteredGuests = computed(() => {
  let r = guests.value;

  if (search.value) {
    const q = search.value.toLowerCase();
    r = r.filter(g =>
      g.nombre_invitado?.toLowerCase().includes(q) ||
      g.correo?.toLowerCase().includes(q) ||
      g.telefono?.includes(q)
    );
  }

  return [...r].sort((a, b) => {
    // Primero alfabético por nombre
    const nameA = a.nombre_invitado || '';
    const nameB = b.nombre_invitado || '';
    const nameCompare = nameA.localeCompare(nameB);

    if (nameCompare !== 0) return nameCompare;

    // Luego por estatus del pase (estatus_acceso)
    const statusA = a.estatus_acceso || '';
    const statusB = b.estatus_acceso || '';
    return statusA.localeCompare(statusB);
  });
});

// ACTIONS
const buildMenuItems = (guest) => {
  const isDeleted = !!guest.deleted_at;

  if (isDeleted) {
    return [
      {
        label: 'Reactivar Invitado',
        icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>`,
        action: () => openRestoreModal(guest)
      }
    ];
  }

  return [
    {
      label: 'Editar Información',
      icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`,
      action: () => openEditModal(guest)
    },
    {
      label: 'Mostrar Código QR',
      icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M7 7h.01M17 7h.01M17 17h.01M7 17h.01"/></svg>`,
      action: () => openQrModal(guest)
    },
    {
      label: 'Gestionar Daily Pass',
      icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
      action: () => openToggleModal(guest)
    },
    {
      label: 'Eliminar',
      icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/></svg>`,
      destructive: true,
      action: () => openDeleteModal(guest)
    }
  ];
};

// RESTORE MODAL
const showRestoreModal = ref(false);
const selectedGuestForRestore = ref(null);

const openRestoreModal = (guest) => {
  selectedGuestForRestore.value = guest;
  showRestoreModal.value = true;
};

const handleRestoreGuest = async () => {
  if (!selectedGuestForRestore.value) return;
  const res = await guestStore.restoreGuest(socioId, selectedGuestForRestore.value.id_invitado);
  if (res?.success) {
    showRestoreModal.value = false;
    toastSuccess('Invitado reactivado');
  } else {
    toastError(res?.error || 'No se pudo reactivar al invitado');
  }
};

onMounted(async () => {
  // Fetch socio details if not already loaded or if it's a different socio
  try {
    const data = await socioStore.fetchSocioDetails(socioId);
    if (data) {
      socioStore.setCurrentSocio(data);
    }
  } catch (err) {
    console.error("Error loading socio details:", err);
  }

  // Fetch guests
  await guestStore.fetchGuests(socioId);
});

// MODAL STATE
const showGuestModal = ref(false);
const modalStep = ref(1); // 1: form, 2: QR
const modalMode = ref('create'); // 'create' | 'edit'
const selectedGuestId = ref(null);
const guestQrData = ref(null);

const guestForm = ref({
  nombre_invitado: '',
  correo: '',
  telefono: ''
});

const formErrors = ref({
  nombre_invitado: '',
  correo: '',
  telefono: ''
});

const openCreateModal = () => {
  modalMode.value = 'create';
  modalStep.value = 1;
  guestForm.value = { nombre_invitado: '', correo: '', telefono: '' };
  formErrors.value = { nombre_invitado: '', correo: '', telefono: '' };
  guestQrData.value = null;
  showGuestModal.value = true;
};

const openEditModal = (guest) => {
  modalMode.value = 'edit';
  modalStep.value = 1;
  selectedGuestId.value = guest.id_invitado;
  guestForm.value = {
    nombre_invitado: guest.nombre_invitado,
    correo: guest.correo || '',
    telefono: guest.telefono || ''
  };
  formErrors.value = { nombre_invitado: '', correo: '', telefono: '' };
  showGuestModal.value = true;
};

const validateForm = () => {
  let valid = true;
  formErrors.value = { nombre_invitado: '', correo: '', telefono: '' };

  if (!guestForm.value.nombre_invitado) {
    formErrors.value.nombre_invitado = 'El nombre es requerido.';
    valid = false;
  } else if (guestForm.value.nombre_invitado.length > 255) {
    formErrors.value.nombre_invitado = 'El nombre no puede exceder los 255 caracteres.';
    valid = false;
  }

  if (guestForm.value.correo && !/^\S+@\S+\.\S+$/.test(guestForm.value.correo)) {
    formErrors.value.correo = 'Ingresa un correo electrónico válido.';
    valid = false;
  }

  if (guestForm.value.telefono && !/^\d+$/.test(guestForm.value.telefono)) {
    formErrors.value.telefono = 'El teléfono debe contener solo números.';
    valid = false;
  }

  return valid;
};

// Al corregir un campo marcado, su error desaparece (no se marcan campos nuevos mientras escribe)
watch(guestForm, () => {
  const marked = Object.keys(formErrors.value).filter((key) => formErrors.value[key]);
  if (!marked.length) return;
  validateForm();
  for (const key of Object.keys(formErrors.value)) {
    if (!marked.includes(key)) formErrors.value[key] = '';
  }
}, { deep: true });

const handleGuestSubmit = async () => {
  if (!validateForm()) return;

  const payload = { ...guestForm.value };

  if (modalMode.value === 'create') {
    const res = await guestStore.createGuest(socioId, payload);
    if (res?.success) {
      guestQrData.value = res.data.codigo_qr || null;
      modalStep.value = 2;
      toastSuccess('Invitado registrado correctamente');
    } else {
      toastError(res?.error || 'No se pudo registrar al invitado');
    }
  } else {
    const res = await guestStore.updateGuest(socioId, selectedGuestId.value, payload);
    if (res?.success) {
      showGuestModal.value = false;
      toastSuccess('Información del invitado actualizada');
    } else {
      toastError(res?.error || 'No se pudo actualizar al invitado');
    }
  }
};

// QR MODAL
const showQrModal = ref(false);
const selectedGuestForQr = ref(null);

const openQrModal = (guest) => {
  selectedGuestForQr.value = guest;
  showQrModal.value = true;
};

// TOGGLE DAILY PASS MODAL
const showToggleModal = ref(false);
const selectedGuestForToggle = ref(null);
const toggleError = ref('');

const openToggleModal = (guest) => {
  selectedGuestForToggle.value = guest;
  toggleError.value = '';
  showToggleModal.value = true;
};

const handleTogglePass = async () => {
  const guest = selectedGuestForToggle.value;
  const isActivating = guest.estatus_acceso !== 'ACTIVO';

  if (isActivating && activePasses.value >= 5) {
    toggleError.value = 'Límite de pases activos (5/5) alcanzado. Debes desactivar otro pase antes de activar este.';
    return;
  }

  const newStatus = isActivating ? 'ACTIVO' : 'INACTIVO';
  const res = await guestStore.togglePass(socioId, guest.id_invitado, newStatus);
  if (res?.success) {
    showToggleModal.value = false;
    toastSuccess(isActivating ? 'Daily Pass activado' : 'Daily Pass desactivado');
  } else {
    toggleError.value = res?.error || 'Error al cambiar el estatus del pase.';
  }
};

// DELETE MODAL
const showDeleteModal = ref(false);
const selectedGuestForDelete = ref(null);

const openDeleteModal = (guest) => {
  selectedGuestForDelete.value = guest;
  showDeleteModal.value = true;
};

const handleDeleteGuest = async () => {
  if (!selectedGuestForDelete.value) return;
  const res = await guestStore.deleteGuest(socioId, selectedGuestForDelete.value.id_invitado);
  if (res?.success) {
    showDeleteModal.value = false;
    toastSuccess('Invitado eliminado');
  } else {
    toastError(res?.error || 'No se pudo eliminar al invitado');
  }
};

</script>

<template>
  <main class="min-h-screen bg-surface-50 px-4 py-5 pb-16 font-sans sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader title="Gestión de Invitados" backRoute="socios-list">
        <template #subtitle>
          <div class="flex items-center gap-2 mt-1">
            <p class="text-sm text-surface-500 font-medium m-0">
              Socio: <span class="text-surface-900 font-bold">{{ currentSocio?.nombre_completo || 'Cargando...'
                }}</span>
            </p>
            <span class="w-1 h-1 rounded-full bg-surface-300"></span>
            <p class="text-xs text-surface-400 font-mono">#{{ currentSocio?.numero_accion }}</p>
          </div>
        </template>

        <!-- Métricas de Pases e Invitados (Grid 2 columnas compacto en mobile, flex en desktop) -->
        <div class="grid grid-cols-2 gap-2.5 sm:flex sm:items-center sm:gap-3 w-full sm:w-auto">
          <!-- Total Invitados -->
          <div class="flex items-center justify-between sm:justify-start gap-2 px-3 py-2.5 sm:px-4 bg-white border border-surface-200 rounded-2xl shadow-xs">
            <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-surface-400">Total Invitados</span>
            <div class="px-2.5 py-0.5 sm:px-3 sm:py-1 bg-surface-100 text-surface-900 rounded-xl text-xs sm:text-sm font-black">
              {{ guests.length }}
            </div>
          </div>

          <!-- Pases Activos -->
          <div class="flex items-center justify-between sm:justify-start gap-2 px-3 py-2.5 sm:px-4 bg-white border border-surface-200 rounded-2xl shadow-xs">
            <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-surface-400">Pases Activos</span>
            <div class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-xl text-xs sm:text-sm font-black whitespace-nowrap tabular-nums transition-colors"
              :class="activePasses >= 5 ? 'bg-red-100 text-red-700' : 'bg-primary-100 text-primary-700'">
              {{ activePasses }} / 5
            </div>
          </div>
        </div>
      </AdminPageHeader>

      <!-- BANNER DE ADVERTENCIA (Límite de pases) -->
      <Transition enter-active-class="transition-all duration-500 ease-out" enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-4">
        <div v-if="activePasses >= 5"
          class="bg-red-50 border border-red-100 rounded-3xl p-5 flex items-center gap-5 shadow-sm border-l-4 border-l-red-500">
          <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center text-red-600 shrink-0">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="12" cy="12" r="10" />
              <path d="M12 8v4M12 16h.01" />
            </svg>
          </div>
          <div>
            <h4 class="font-black text-red-900 text-base leading-tight">Límite de Pases Activos Alcanzado</h4>
            <p class="text-red-700/80 text-sm font-medium mt-1">Este socio ha alcanzado el máximo de 5 pases activos
              permitidos simultáneamente. Para registrar uno nuevo o activar otro, primero debe desactivar uno de los
              pases
              actuales.</p>
          </div>
        </div>
      </Transition>

      <!-- BARRA DE BÚSQUEDA Y NUEVO INVITADO -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-3 sm:p-4">
        <div class="flex flex-col sm:flex-row gap-2 w-full items-stretch sm:items-center">
          <div class="flex-1 min-w-0">
            <SearchInput v-model="search" placeholder="Buscar por nombre, correo o teléfono…" />
          </div>

          <!-- Botón Registrar / Nuevo Invitado -->
          <Transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 sm:translate-x-4" enter-to-class="opacity-100 sm:translate-x-0"
            leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 sm:translate-x-0"
            leave-to-class="opacity-0 sm:translate-x-4">
            <AppButton v-if="activePasses < 5" class="w-full sm:w-auto" @click="openCreateModal">
              <template #icon><IconUserPlus class="h-4 w-4" /></template>
              Nuevo Invitado
            </AppButton>
          </Transition>
        </div>
      </div>

      <!-- TABLA DE INVITADOS -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-visible min-h-96">

        <!-- Estado: Cargando -->
        <div v-if="isLoading && !guests.length" class="p-12 flex flex-col items-center justify-center gap-4">
          <LoadingSpinner size="lg" />
          <p class="text-sm font-bold text-surface-400 animate-pulse">Obteniendo invitados...</p>
        </div>

        <!-- Estado: Error -->
        <div v-else-if="loadError" class="p-12 text-center">
          <div class="w-16 h-16 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h3 class="text-lg font-black text-surface-900">Error al cargar</h3>
          <p class="text-surface-500 mt-1">{{ loadError }}</p>
          <button @click="guestStore.fetchGuests(socioId)"
            class="mt-4 text-primary-600 font-bold hover:underline">Reintentar</button>
        </div>

        <!-- Estado: Vacío -->
        <div v-else-if="!guests.length" class="p-20 text-center flex flex-col items-center">
          <div
            class="w-20 h-20 bg-surface-50 rounded-3xl flex items-center justify-center mb-6 border border-surface-100">
            <IconUser class="w-10 h-10 text-surface-200" />
          </div>
          <h3 class="text-xl font-black text-surface-900">No hay invitados registrados</h3>
          <p class="text-surface-500 mt-2 max-w-sm mx-auto">Comienza registrando un invitado para este socio utilizando
            el
            botón superior.</p>
        </div>

        <!-- Contenido con datos: Tabla (Desktop) y Tarjetas (Móvil) -->
        <template v-else>
          <!-- Tabla desktop (visible solo en >= 1024px) -->
          <div class="overflow-x-auto hidden lg:block">
            <table class="w-full text-sm">
              <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
                <tr>
                  <th class="px-6 py-4 text-left font-extrabold rounded-tl-2xl">Invitado</th>
                  <th class="px-6 py-4 text-left font-extrabold">Contacto</th>
                  <th class="px-6 py-4 text-left font-extrabold">Estatus Invitado</th>
                  <th class="px-6 py-4 text-left font-extrabold">Daily Pass</th>
                  <th class="px-6 py-4 text-right font-extrabold rounded-tr-2xl">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-surface-100">
                <tr v-for="guest in filteredGuests" :key="guest.id_invitado"
                  class="bg-white border-b border-surface-100 hover:bg-surface-50/50 transition-colors group">
                  <!-- Info Invitado -->
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-4">
                      <div
                        class="w-9 h-9 rounded-xl bg-linear-to-br flex items-center justify-center text-white font-black text-xs shadow-sm shrink-0"
                        :class="avatarGradient(guest.nombre_invitado)">
                        {{ initials(guest.nombre_invitado) }}
                      </div>
                      <div>
                        <p class="font-bold text-surface-900 text-sm leading-none">{{ guest.nombre_invitado }}</p>
                        <p class="text-[10px] text-surface-400 mt-1.5 font-medium">Invitado desde {{
                          dateFormat(guest.fecha_registro) || 'N/A' }}
                        </p>
                      </div>
                    </div>
                  </td>

                  <!-- Contacto -->
                  <td class="px-6 py-4">
                    <div class="space-y-1.5">
                      <div class="flex items-center gap-2 text-surface-600">
                        <IconMail class="w-3.5 h-3.5 text-surface-300" />
                        <span class="text-xs font-medium">{{ guest.correo || '—' }}</span>
                      </div>
                      <div class="flex items-center gap-2 text-surface-600">
                        <IconPhone class="w-3.5 h-3.5 text-surface-300" />
                        <span class="text-xs font-medium">{{ guest.telefono || '—' }}</span>
                      </div>
                    </div>
                  </td>

                  <!-- Estatus Invitado -->
                  <td class="px-6 py-4">
                    <BadgeStatus :status="guest.deleted_at ? 'ELIMINADO' : 'ACTIVO'" />
                  </td>

                  <!-- Daily Pass -->
                  <td class="px-6 py-4">
                    <div class="flex flex-col gap-1">
                      <BadgeStatus :status="guest.estatus_acceso || 'INACTIVO'" />
                      <span v-if="guest.fecha_expiracion" class="text-[10px] font-bold text-surface-400 ml-1">
                        Expira: {{ dateFormat(guest.fecha_expiracion) }}
                      </span>
                    </div>
                  </td>

                  <!-- Acciones -->
                  <td class="px-6 py-4 text-right">
                    <ActionMenu :items="buildMenuItems(guest)" align="right" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- ═══ Tarjetas Móvil (< 1024px) ═══ -->
          <div class="lg:hidden flex flex-col gap-3.5 p-3 sm:p-4">
            <!-- Mensaje si la búsqueda no arroja resultados -->
            <div v-if="filteredGuests.length === 0" class="p-8 text-center text-sm font-semibold text-surface-400">
              No se encontraron invitados que coincidan con la búsqueda.
            </div>

            <!-- Datos siempre visibles: así el menú ⋮ no compite con un panel que se expande -->
            <article v-for="guest in filteredGuests" :key="'card-' + guest.id_invitado"
              class="rounded-2xl border border-surface-200/90 bg-white p-4 shadow-xs animate-row-in"
              :class="{ 'opacity-70': guest.deleted_at }">

              <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                  <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br text-xs font-black text-white shadow-xs"
                    :class="avatarGradient(guest.nombre_invitado)">
                    {{ initials(guest.nombre_invitado) }}
                  </div>
                  <div class="flex min-w-0 flex-col">
                    <span class="truncate text-sm font-bold text-surface-900">{{ guest.nombre_invitado }}</span>
                    <span class="text-xs font-medium text-surface-600">
                      Invitado desde {{ dateFormat(guest.fecha_registro) || 'N/A' }}
                    </span>
                  </div>
                </div>
                <ActionMenu :items="buildMenuItems(guest)" align="right" class="shrink-0" />
              </div>

              <div class="mt-3 grid grid-cols-1 gap-1.5 border-t border-surface-100 pt-3 text-xs text-surface-700 sm:grid-cols-2">
                <div class="flex min-w-0 items-center gap-2">
                  <IconMail class="h-3.5 w-3.5 shrink-0 text-surface-500" />
                  <span class="truncate">{{ guest.correo || 'Sin correo' }}</span>
                </div>
                <div class="flex min-w-0 items-center gap-2">
                  <IconPhone class="h-3.5 w-3.5 shrink-0 text-surface-500" />
                  <span class="truncate">{{ guest.telefono || 'Sin teléfono' }}</span>
                </div>
              </div>

              <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-surface-100 pt-3 text-xs">
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-surface-600">Estatus</span>
                  <BadgeStatus :status="guest.deleted_at ? 'ELIMINADO' : 'ACTIVO'" />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="font-semibold text-surface-600">Daily Pass</span>
                  <BadgeStatus :status="guest.estatus_acceso || 'INACTIVO'" />
                  <span v-if="guest.fecha_expiracion" class="text-[11px] font-semibold text-surface-500">
                    Exp. {{ dateFormat(guest.fecha_expiracion) }}
                  </span>
                </div>
              </div>
            </article>
          </div>
        </template>
      </div>
    </div>


  <!-- MODAL: CREAR / EDITAR INVITADO (paso 2: pase generado + QR) -->
  <AppModal
    v-model="showGuestModal"
    :title="modalStep === 2 ? 'Invitado registrado' : (modalMode === 'create' ? 'Registrar invitado' : 'Editar invitado')"
    :subtitle="modalStep === 2 ? 'Su pase de acceso ya está listo' : 'Completa la información del invitado'"
    :icon="modalStep === 2 ? IconQr : IconUserPlus"
    :tone="modalStep === 2 ? 'emerald' : 'blue'"
    :persistent="loading.create || loading.update"
  >
    <form v-if="modalStep === 1" id="guest-form" class="flex flex-col gap-4 pt-1" novalidate @submit.prevent="handleGuestSubmit">
      <AppField label="Nombre completo" for="guest-nombre" required :error="formErrors.nombre_invitado">
        <AppInput id="guest-nombre" v-model="guestForm.nombre_invitado" :icon="IconUser" maxlength="255" placeholder="Ej. Juan Pérez" autocomplete="off" :invalid="!!formErrors.nombre_invitado" />
      </AppField>
      <AppField label="Correo" for="guest-correo" optional :error="formErrors.correo">
        <AppInput id="guest-correo" v-model="guestForm.correo" type="email" :icon="IconMail" placeholder="ejemplo@correo.com" autocomplete="off" :invalid="!!formErrors.correo" />
      </AppField>
      <AppField label="Teléfono" for="guest-telefono" optional :error="formErrors.telefono">
        <AppInput id="guest-telefono" v-model="guestForm.telefono" type="tel" inputmode="numeric" :icon="IconPhone" placeholder="10 dígitos" autocomplete="off" :invalid="!!formErrors.telefono" />
      </AppField>
    </form>

    <div v-else class="pt-2">
      <QrCodeDisplay :value="guestQrData" :name="guestForm.nombre_invitado" caption="Invitado" />
    </div>

    <template #footer>
      <template v-if="modalStep === 1">
        <AppButton variant="secondary" :disabled="loading.create || loading.update" @click="showGuestModal = false">Cancelar</AppButton>
        <AppButton type="submit" form="guest-form" :loading="loading.create || loading.update">
          {{ modalMode === 'create' ? 'Registrar invitado' : 'Guardar cambios' }}
        </AppButton>
      </template>
      <AppButton v-else tone="emerald" @click="showGuestModal = false">Finalizar</AppButton>
    </template>
  </AppModal>

  <!-- MODAL: CÓDIGO QR -->
  <QrCodeModal
    v-model="showQrModal"
    :value="selectedGuestForQr?.codigo_qr"
    :name="selectedGuestForQr?.nombre_invitado"
    caption="Invitado"
  />

  <!-- MODAL: GESTIONAR DAILY PASS -->
  <AppConfirmModal
    v-model="showToggleModal"
    title="Gestionar Daily Pass"
    :icon="IconQr"
    :tone="selectedGuestForToggle?.estatus_acceso === 'ACTIVO' ? 'amber' : 'blue'"
    :confirm-label="selectedGuestForToggle?.estatus_acceso === 'ACTIVO' ? 'Desactivar pase' : 'Activar pase'"
    :loading="loading.toggle"
    :error="toggleError"
    @confirm="handleTogglePass"
  >
    El pase de <strong class="text-surface-900">{{ selectedGuestForToggle?.nombre_invitado }}</strong> está
    <BadgeStatus :status="selectedGuestForToggle?.estatus_acceso || 'INACTIVO'" class="align-middle" />.
    ¿Deseas {{ selectedGuestForToggle?.estatus_acceso === 'ACTIVO' ? 'desactivar' : 'activar' }} su acceso para hoy?
  </AppConfirmModal>

  <!-- MODAL: REACTIVAR INVITADO -->
  <AppConfirmModal
    v-model="showRestoreModal"
    title="¿Reactivar invitado?"
    :icon="IconUserPlus"
    tone="emerald"
    confirm-label="Sí, reactivar"
    :loading="loading.update"
    @confirm="handleRestoreGuest"
  >
    <strong class="text-surface-900">{{ selectedGuestForRestore?.nombre_invitado }}</strong> volverá a estar disponible
    en la lista activa de invitados de este socio.
  </AppConfirmModal>

  <!-- MODAL: ELIMINAR INVITADO -->
  <AppConfirmModal
    v-model="showDeleteModal"
    title="¿Eliminar invitado?"
    :icon="IconTrash"
    tone="rose"
    confirm-label="Sí, eliminar"
    :loading="loading.delete"
    @confirm="handleDeleteGuest"
  >
    Se desactivará el pase de <strong class="text-surface-900">{{ selectedGuestForDelete?.nombre_invitado }}</strong>
    y se dará de baja de la lista de invitados de este socio.
    <span class="mt-3 block text-xs font-medium text-surface-500">El registro seguirá visible para la administración.</span>
  </AppConfirmModal>
  </main>
</template>
