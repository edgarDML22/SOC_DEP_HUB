<script setup>
import { onMounted, computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminGuestStore } from '@/stores/admin/adminGuestStore';
import { useSocioStore } from '@/stores/admin/socioStore';
import { storeToRefs } from 'pinia';
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue';
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue';
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue';
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue';
import { IconUser, IconMail, IconPhone, IconQr, IconSearch } from '@/components/icons';
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue';
import CancelButton from '@/components/gerente/ui/CancelButton.vue';
import SearchInput from '@/components/gerente/ui/SearchInput.vue';
import { useformat } from '@/utils/formatters';

const { dateFormat } = useformat();

const route = useRoute();
const router = useRouter();
const guestStore = useAdminGuestStore();
const socioStore = useSocioStore();

const { guests, activePasses, isLoading, loading, error } = storeToRefs(guestStore);
const { currentSocio } = storeToRefs(socioStore);

const socioId = route.params.id;

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
  if (res.success) {
    showRestoreModal.value = false;
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

const handleGuestSubmit = async () => {
  if (!validateForm()) return;

  const payload = { ...guestForm.value };

  if (modalMode.value === 'create') {
    const res = await guestStore.createGuest(socioId, payload);
    if (res.success) {
      guestQrData.value = res.data.qr_code_image || res.data.qr_code || null;
      modalStep.value = 2;
    }
  } else {
    const res = await guestStore.updateGuest(socioId, selectedGuestId.value, payload);
    if (res.success) {
      showGuestModal.value = false;
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

const qrUrl = computed(() => {
  if (!selectedGuestForQr.value?.codigo_qr) return null;
  return `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${selectedGuestForQr.value.codigo_qr}`;
});

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
  if (res.success) {
    showToggleModal.value = false;
  } else {
    toggleError.value = res.error || 'Error al cambiar el estatus del pase.';
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
  if (res.success) {
    showDeleteModal.value = false;
  }
};

</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">

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

        <div class="flex items-center gap-4">
          <!-- Total Invitados -->
          <div class="flex items-center gap-2.5 px-4 py-2.5 bg-white border border-surface-200 rounded-2xl shadow-sm">
            <span class="text-[11px] font-black uppercase tracking-wider text-surface-400">Total Invitados</span>
            <div class="px-3 py-1 bg-surface-100 text-surface-900 rounded-xl text-sm font-black">
              {{ guests.length }}
            </div>
          </div>

          <!-- Pases Activos -->
          <div class="flex items-center gap-2.5 px-4 py-2.5 bg-white border border-surface-200 rounded-2xl shadow-sm">
            <span class="text-[11px] font-black uppercase tracking-wider text-surface-400">Pases Activos</span>
            <div class="px-3 py-1 rounded-xl text-sm font-black transition-colors"
              :class="activePasses >= 5 ? 'bg-red-100 text-red-700' : 'bg-primary-100 text-primary-700'">
              {{ activePasses }} / 5
            </div>
          </div>

          <!-- Botón Registrar (Se oculta si llega al límite) -->
          <Transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-x-4" enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 translate-x-4">
            <button v-if="activePasses < 5"
              class="flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-2xl font-bold text-sm shadow-lg shadow-primary-600/20 transition-all hover:-translate-y-0.5 active:translate-y-0"
              @click="openCreateModal">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M12 5v14M5 12h14" />
              </svg>
              Registrar Invitado
            </button>
          </Transition>
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

      <!-- BARRA DE BÚSQUEDA -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-4">
        <SearchInput v-model="search" placeholder="Buscar por nombre, correo o teléfono…" />
      </div>

      <!-- TABLA DE INVITADOS -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-visible min-h-96">

        <!-- Estado: Cargando -->
        <div v-if="isLoading && !guests.length" class="p-12 flex flex-col items-center justify-center gap-4">
          <LoadingSpinner size="lg" />
          <p class="text-sm font-bold text-surface-400 animate-pulse">Obteniendo invitados...</p>
        </div>

        <!-- Estado: Error -->
        <div v-else-if="error" class="p-12 text-center">
          <div class="w-16 h-16 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h3 class="text-lg font-black text-surface-900">Error al cargar</h3>
          <p class="text-surface-500 mt-1">{{ error }}</p>
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

        <!-- Tabla -->
        <div v-else class="overflow-x-auto">
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
      </div>
    </div>
  </main>

  <!-- MODAL: CREAR/EDITAR INVITADO -->
  <Teleport to="body">
    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showGuestModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
        @click.self="showGuestModal = false">
        <div class="bg-white w-full max-w-md rounded-[2rem] shadow-2xl flex flex-col overflow-hidden">

          <!-- Cabecera -->
          <div class="flex items-center justify-between px-8 py-6 bg-white border-b border-surface-100">
            <div>
              <h2 class="text-xl font-black text-surface-900 leading-tight">
                {{ modalStep === 2 ? 'Pase de Invitado' :
                  (modalMode === 'create' ? 'Registrar Invitado' : 'Editar Invitado') }}
              </h2>
              <p class="text-xs font-bold text-surface-500 mt-1 uppercase tracking-wider">
                {{ modalStep === 2 ? 'Registro exitoso' : 'Completa la información del invitado' }}
              </p>
            </div>
            <button @click="showGuestModal = false"
              class="w-10 h-10 rounded-xl bg-surface-100 hover:bg-surface-200 flex items-center justify-center text-surface-500 transition-colors">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 6L6 18M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Cuerpo -->
          <div class="overflow-y-auto p-7 bg-surface-50/30">
            <!-- STEP 1: FORM -->
            <div v-if="modalStep === 1" class="space-y-5">
              <!-- Nombre -->
              <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Nombre
                  Completo</label>
                <div class="relative">
                  <IconUser class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" />
                  <input v-model="guestForm.nombre_invitado" type="text" maxlength="255" placeholder="Ej. Juan Pérez"
                    class="w-full pl-11 pr-4 py-3 bg-white border rounded-2xl text-sm font-semibold text-surface-900 placeholder:text-surface-400 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all shadow-sm"
                    :class="formErrors.nombre_invitado ? 'border-red-300' : 'border-surface-200'" />
                </div>
                <p v-if="formErrors.nombre_invitado" class="text-[10px] font-bold text-red-500 px-1">{{
                  formErrors.nombre_invitado }}</p>
              </div>

              <!-- Correo -->
              <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Correo
                  (Opcional)</label>
                <div class="relative">
                  <IconMail class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" />
                  <input v-model="guestForm.correo" type="email" placeholder="ejemplo@correo.com"
                    class="w-full pl-11 pr-4 py-3 bg-white border rounded-2xl text-sm font-semibold text-surface-900 placeholder:text-surface-400 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all shadow-sm"
                    :class="formErrors.correo ? 'border-red-300' : 'border-surface-200'" />
                </div>
                <p v-if="formErrors.correo" class="text-[10px] font-bold text-red-500 px-1">{{ formErrors.correo }}</p>
              </div>

              <!-- Teléfono -->
              <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Teléfono
                  (Opcional)</label>
                <div class="relative">
                  <IconPhone class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" />
                  <input v-model="guestForm.telefono" type="tel" placeholder="10 dígitos"
                    class="w-full pl-11 pr-4 py-3 bg-white border rounded-2xl text-sm font-semibold text-surface-900 placeholder:text-surface-400 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all shadow-sm"
                    :class="formErrors.telefono ? 'border-red-300' : 'border-surface-200'" />
                </div>
                <p v-if="formErrors.telefono" class="text-[10px] font-bold text-red-500 px-1">{{ formErrors.telefono }}
                </p>
              </div>
            </div>

            <!-- STEP 2: QR -->
            <div v-else class="flex flex-col items-center py-4">
              <div
                class="w-56 h-56 bg-surface-50 rounded-[2.5rem] p-6 border border-surface-100 shadow-inner flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 bg-linear-to-br from-primary-500/5 to-transparent"></div>
                <img v-if="guestQrData" :src="guestQrData" alt="QR Code" class="w-full h-full relative z-10" />
                <div v-else class="flex flex-col items-center gap-2 text-surface-300 relative z-10">
                  <IconQr class="w-12 h-12" />
                  <span class="text-[10px] font-black uppercase tracking-widest">Generando QR...</span>
                </div>
              </div>
              <div class="mt-8 text-center space-y-2">
                <p class="text-base font-black text-surface-900">{{ guestForm.nombre_invitado }}</p>
                <p class="text-sm text-surface-500 font-medium">El pase ha sido generado y está listo para ser
                  utilizado.
                </p>
              </div>
            </div>
          </div>

          <!-- Pie del modal -->
          <div class="flex items-center justify-end gap-3 px-7 py-4 border-t border-surface-100 bg-white">
            <template v-if="modalStep === 1">
              <CancelButton label="Cancelar" @click="showGuestModal = false" />
              <ConfirmButton :label="modalMode === 'create' ? 'Registrar' : 'Guardar Cambios'"
                :loading="loading.create || loading.update" @click="handleGuestSubmit" />
            </template>
            <template v-else>
              <ConfirmButton label="Finalizar" @click="showGuestModal = false" />
            </template>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- MODAL: MOSTRAR QR -->
  <Teleport to="body">
    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showQrModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
        @click.self="showQrModal = false">
        <div class="bg-white w-full max-w-xs rounded-[2rem] shadow-2xl flex flex-col overflow-hidden">
          <div class="px-6 py-5 border-b border-surface-100 flex items-center justify-between">
            <h3 class="text-lg font-black text-surface-900">Pase QR</h3>
            <button @click="showQrModal = false" class="text-surface-400 hover:text-surface-600 transition-colors">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 6L6 18M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="p-8 flex flex-col items-center gap-6">
            <div
              class="w-full aspect-square bg-surface-50 rounded-3xl p-4 border border-surface-100 shadow-inner flex items-center justify-center">
              <img v-if="qrUrl" :src="qrUrl" alt="QR Code" class="w-full h-full" />
              <div v-else class="text-surface-300 flex flex-col items-center gap-2">
                <IconQr class="w-10 h-10" />
                <span class="text-[10px] font-black uppercase tracking-widest">Sin código</span>
              </div>
            </div>
            <div class="text-center">
              <p class="font-black text-surface-900 leading-tight">{{ selectedGuestForQr?.nombre_invitado }}</p>
              <p class="text-xs text-surface-400 mt-1 font-medium">Escanea este código en recepción</p>
            </div>
          </div>
          <div class="px-6 py-4 bg-surface-50 border-t border-surface-100 flex justify-center">
            <ConfirmButton label="Cerrar" @click="showQrModal = false" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- MODAL: TOGGLE DAILY PASS -->
  <Teleport to="body">
    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showToggleModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
        @click.self="showToggleModal = false">
        <div class="bg-white w-full max-w-sm rounded-[2rem] shadow-2xl flex flex-col overflow-hidden">
          <div class="p-8">
            <div class="w-16 h-16 bg-primary-50 text-primary-600 rounded-3xl flex items-center justify-center mb-6">
              <IconQr class="w-8 h-8" />
            </div>
            <h3 class="text-xl font-black text-surface-900 leading-tight">Gestionar Daily Pass</h3>
            <p class="text-surface-500 mt-3 leading-relaxed">
              El pase de <span class="font-bold text-surface-900">{{ selectedGuestForToggle?.nombre_invitado }}</span>
              está actualmente
              <span class="font-bold uppercase"
                :class="selectedGuestForToggle?.estatus_acceso === 'ACTIVO' ? 'text-primary-600' : 'text-surface-400'">
                {{ selectedGuestForToggle?.estatus_acceso || 'INACTIVO' }}
              </span>.
            </p>
            <p class="text-surface-500 mt-2">
              ¿Deseas {{ selectedGuestForToggle?.estatus_acceso === 'ACTIVO' ? 'desactivar' : 'activar' }} su acceso
              para
              hoy?
            </p>

            <!-- Error Inline -->
            <Transition enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
              <div v-if="toggleError"
                class="mt-4 p-4 bg-red-50 border border-red-100 rounded-2xl flex gap-3 items-start animate-shake">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                  stroke-width="2.5">
                  <circle cx="12" cy="12" r="10" />
                  <path d="M12 8v4M12 16h.01" />
                </svg>
                <p class="text-xs font-bold text-red-700 leading-tight">{{ toggleError }}</p>
              </div>
            </Transition>
          </div>

          <div class="px-8 py-6 bg-surface-50 border-t border-surface-100 flex items-center justify-end gap-3">
            <CancelButton label="Cerrar" @click="showToggleModal = false" />
            <ConfirmButton
              :label="selectedGuestForToggle?.estatus_acceso === 'ACTIVO' ? 'Desactivar Pase' : 'Activar Pase'"
              :loading="loading.toggle" @click="handleTogglePass" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
  <!-- MODAL: REACTIVAR INVITADO -->
  <Teleport to="body">
    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showRestoreModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
        @click.self="showRestoreModal = false">
        <div class="bg-white w-full max-w-sm rounded-[2rem] shadow-2xl flex flex-col overflow-hidden">
          <div class="p-8">
            <div class="w-16 h-16 bg-primary-50 text-primary-600 rounded-3xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
              </svg>
            </div>
            <h3 class="text-xl font-black text-surface-900 leading-tight">¿Reactivar invitado?</h3>
            <p class="text-surface-500 mt-3 leading-relaxed">
              Estás a punto de reactivar a <span class="font-bold text-surface-900">{{ selectedGuestForRestore?.nombre_invitado }}</span>.
              El invitado volverá a estar disponible en la lista activa de este socio.
            </p>
          </div>

          <div class="px-8 py-6 bg-surface-50 border-t border-surface-100 flex items-center justify-end gap-3">
            <CancelButton label="Cancelar" @click="showRestoreModal = false" />
            <ConfirmButton label="Sí, reactivar" :loading="loading.update" @click="handleRestoreGuest" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- MODAL: ELIMINAR INVITADO -->
  <Teleport to="body">
    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showDeleteModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
        @click.self="showDeleteModal = false">
        <div class="bg-white w-full max-w-sm rounded-[2rem] shadow-2xl flex flex-col overflow-hidden">
          <div class="p-8">
            <div class="w-16 h-16 bg-red-50 text-red-600 rounded-3xl flex items-center justify-center mb-6">
              <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6" />
              </svg>
            </div>
            <h3 class="text-xl font-black text-surface-900 leading-tight">¿Eliminar invitado?</h3>
            <p class="text-surface-500 mt-3 leading-relaxed">
              Estás a punto de eliminar a <span class="font-bold text-surface-900">{{ selectedGuestForDelete?.nombre_invitado }}</span>.
              Esta acción desactivará su pase actual y lo dará de baja de la lista de invitados de este socio.
            </p>
            <p class="text-surface-400 text-xs mt-4 font-medium italic">
              * El registro podrá ser visualizado posteriormente por administración.
            </p>
          </div>

          <div class="px-8 py-6 bg-surface-50 border-t border-surface-100 flex items-center justify-end gap-3">
            <CancelButton label="Cancelar" @click="showDeleteModal = false" />
            <button
              class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-bold text-sm transition-all shadow-lg shadow-red-600/20 flex items-center gap-2"
              :disabled="loading.delete"
              @click="handleDeleteGuest">
              <LoadingSpinner v-if="loading.delete" size="xs" color="white" />
              {{ loading.delete ? 'Eliminando...' : 'Sí, eliminar' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
