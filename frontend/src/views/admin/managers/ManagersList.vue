<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import {
  AdminPageHeader,
  BadgeStatus,
  ActionMenu,
  SearchInput,
  FilterContainer,
  FilterSelect
} from '@/components/gerente/ui'
import { IconUser, IconMail, IconBriefcase, IconLock, IconShield, IconAlertCircle, IconEdit } from '@/components/icons'
import IconUserPlus from '@/components/icons/IconUserPlus.vue'
import AppModal from '@/components/ui/modal/AppModal.vue'
import AppConfirmModal from '@/components/ui/modal/AppConfirmModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppField from '@/components/ui/form/AppField.vue'
import AppInput from '@/components/ui/form/AppInput.vue'
import AppSelect from '@/components/ui/form/AppSelect.vue'
import { useAlerts } from '@/composables/useAlerts'
import { useAdminStore } from '@/stores/profiles/adminStore'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'

const adminStore = useAdminStore()
const { toastSuccess } = useAlerts()
const { managers, isLoading, listFilters } = storeToRefs(adminStore)

// Inicializar si por alguna razón está null (fallback de seguridad)
if (!listFilters.value) {
  listFilters.value = { search: '', rol: null, status: null }
}

// Auth info
const currentUser = computed(() => {
  try {
    return JSON.parse(localStorage.getItem('user_data')) || {}
  } catch {
    return {}
  }
})
const isGerente = computed(() => currentUser.value.rol === 'gerente')

// State
const isCreateModalOpen = ref(false)
const isEditModalOpen = ref(false)

const createForm = ref({
  nombre_completo: '',
  correo_electronico: '',
  cargo: '',
  password: '',
  rol: 'subgerente'
})

const editForm = ref({
  id: null,
  nombre_empleado: '',
  email: '',
  cargo: ''
})

const OPT_ROL = [
  { label: 'Todos', value: null },
  { label: 'Gerente', value: 'gerente' },
  { label: 'Subgerente', value: 'subgerente' },
]

const OPT_ESTATUS = [
  { label: 'Todos', value: null },
  { label: 'Activo', value: '1' },
  { label: 'Inactivo', value: '0' },
]

// Filters
const filteredManagers = computed(() => {
  let r = managers.value || []
  const f = listFilters.value

  if (f.search) {
    const q = f.search.toLowerCase()
    r = r.filter(m =>
      m.name?.toLowerCase().includes(q) ||
      m.email?.toLowerCase().includes(q) ||
      m.cargo?.toLowerCase().includes(q)
    )
  }

  if (f.rol) {
    r = r.filter(m => m.rol === f.rol)
  }

  if (f.status !== null) {
    const isActive = f.status === '1'
    r = r.filter(m => m.activo === isActive)
  }

  return [...r].sort((a, b) => (a.name || '').localeCompare(b.name || ''))
})

const hasActiveFilters = computed(() =>
  listFilters.value.search || listFilters.value.rol || listFilters.value.status !== null
)

const clearFilters = () => {
  listFilters.value.search = ''
  listFilters.value.rol = null
  listFilters.value.status = null
}

// Avatar helper (copy from SociosList)
const AVATAR_GRADIENTS = [
  'from-primary-400 to-primary-600',
  'from-emerald-400 to-emerald-600',
  'from-purple-400 to-purple-600',
  'from-orange-400 to-orange-600',
  'from-rose-400 to-rose-600',
  'from-cyan-400 to-cyan-600',
]
const avatarGradient = (name = '') => {
  const idx = (name.charCodeAt(0) ?? 0) % AVATAR_GRADIENTS.length
  return AVATAR_GRADIENTS[idx]
}
const initials = (name = '') => {
  const parts = name.trim().split(' ').filter(Boolean)
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return (parts[0]?.[0] ?? '?').toUpperCase()
}

// Actions
const OPT_ROL_FORM = [
  { label: 'Gerente', value: 'gerente' },
  { label: 'Subgerente', value: 'subgerente' },
]
const createErrors = ref({})
const editErrors = ref({})
const EMAIL_RE = /^\S+@\S+\.\S+$/

const validateCreate = () => {
  const f = createForm.value
  const e = {}
  if (!f.nombre_completo.trim()) e.nombre_completo = 'Escribe el nombre completo.'
  if (!f.correo_electronico.trim()) e.correo_electronico = 'Escribe el correo.'
  else if (!EMAIL_RE.test(f.correo_electronico)) e.correo_electronico = 'Ingresa un correo válido.'
  if (!f.cargo.trim()) e.cargo = 'Escribe el cargo.'
  if (!f.password) e.password = 'Escribe una contraseña.'
  else if (f.password.length < 8) e.password = 'Debe tener al menos 8 caracteres.'
  createErrors.value = e
  return Object.keys(e).length === 0
}

const validateEdit = () => {
  const f = editForm.value
  const e = {}
  if (!f.nombre_empleado?.trim()) e.nombre_empleado = 'Escribe el nombre completo.'
  if (!f.email?.trim()) e.email = 'Escribe el correo.'
  else if (!EMAIL_RE.test(f.email)) e.email = 'Ingresa un correo válido.'
  if (!f.cargo?.trim()) e.cargo = 'Escribe el cargo.'
  if (f.password && f.password.length < 8) e.password = 'Debe tener al menos 8 caracteres.'
  editErrors.value = e
  return Object.keys(e).length === 0
}

// Al corregir un campo marcado, su error desaparece
const revalidate = (errorsRef, validate) => () => {
  const prev = errorsRef.value
  if (!Object.keys(prev).length) return
  validate()
  errorsRef.value = Object.fromEntries(Object.entries(errorsRef.value).filter(([key]) => key in prev))
}
watch(createForm, revalidate(createErrors, validateCreate), { deep: true })
watch(editForm, revalidate(editErrors, validateEdit), { deep: true })

const openCreateModal = () => {
  createForm.value = {
    nombre_completo: '',
    correo_electronico: '',
    cargo: '',
    password: '',
    rol: 'subgerente'
  }
  createErrors.value = {}
  isCreateModalOpen.value = true
}

const openEditModal = (manager) => {
  editForm.value = {
    id: manager.id,
    nombre_empleado: manager.name,
    email: manager.email,
    cargo: manager.cargo,
    password: ''
  }
  editErrors.value = {}
  isEditModalOpen.value = true
}

const handleCreate = async () => {
  if (!validateCreate()) return
  const success = await adminStore.crearManager(createForm.value)
  if (success) isCreateModalOpen.value = false
}

const handleUpdate = async () => {
  if (!validateEdit()) return
  const payload = { ...editForm.value }
  if (!payload.password) delete payload.password
  const success = await adminStore.actualizarManager(editForm.value.id, payload)
  if (success) isEditModalOpen.value = false
}

// ── Habilitar / deshabilitar: siempre con confirmación y alerta de resultado ──
const showToggleModal = ref(false)
const managerToToggle = ref(null)

const handleToggleStatus = (manager) => {
  managerToToggle.value = manager
  showToggleModal.value = true
}

const confirmToggleStatus = async () => {
  const manager = managerToToggle.value
  if (!manager) return
  const enabling = !manager.activo
  const ok = await adminStore.toggleActivo(manager.id, enabling)
  if (ok) {
    showToggleModal.value = false
    toastSuccess(`${manager.name} fue ${enabling ? 'habilitado' : 'deshabilitado'}`)
  }
}

const buildMenuItems = (manager) => {
  const items = [
    {
      label: 'Editar',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
      action: () => openEditModal(manager)
    }
  ]

  if (isGerente.value) {
    items.push({
      label: manager.activo ? 'Deshabilitar' : 'Habilitar',
      icon: manager.activo
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
      action: () => handleToggleStatus(manager),
      destructive: manager.activo
    })
  }

  return items
}

onMounted(() => {
  adminStore.fetchManagers()
})
</script>

<template>
  <main class="min-h-screen bg-surface-50 px-4 py-5 pb-16 font-sans animate-fade-in sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-5 sm:space-y-6">

      <!-- CABECERA -->
      <AdminPageHeader title="Gestión de Gerentes" subtitle="Administra los accesos de nivel gerencial y subgerencial." />

      <!-- Acción principal a la izquierda y el conteo justo a su derecha -->
      <div class="flex items-center gap-3 sm:gap-4">
        <AppButton class="shrink-0" @click="openCreateModal">
          <template #icon><IconUserPlus class="h-4 w-4" /></template>
          Nuevo Gerente
        </AppButton>
        <span class="min-w-0 whitespace-nowrap text-sm font-semibold text-surface-600">
          <span class="font-bold tabular-nums text-surface-900">{{ filteredManagers.length }}</span>
          de <span class="tabular-nums">{{ managers.length }}</span><span class="hidden sm:inline"> gerentes</span>
        </span>
      </div>

      <!-- BARRA DE FILTROS -->
      <FilterContainer :has-active-filters="hasActiveFilters" :active-count="[listFilters?.rol, listFilters?.status].filter(Boolean).length" @clear="clearFilters">
        <template #search>
          <SearchInput v-if="listFilters" v-model="listFilters.search"
            placeholder="Buscar por nombre, correo o cargo..." />
        </template>

        <FilterSelect label="Rol de Acceso" v-model="listFilters.rol" :options="OPT_ROL">
          <template #icon>
            <IconShield />
          </template>
        </FilterSelect>

        <FilterSelect label="Estatus" v-model="listFilters.status" :options="OPT_ESTATUS">
          <template #icon>
            <IconAlertCircle />
          </template>
        </FilterSelect>
      </FilterContainer>

      <!-- TABLA -->
      <div
        class="bg-white rounded-[2.2rem] border border-surface-200/80 shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] overflow-hidden min-h-96">

        <!-- Estado: cargando -->
        <TableSkeleton v-if="isLoading.fetch" :rows="4" :columns="5" :has-avatar="true" />

        <!-- Estado: vacío -->
        <div v-else-if="filteredManagers.length === 0"
          class="p-20 flex flex-col items-center justify-center text-center">
          <div
            class="w-16 h-16 rounded-[1.5rem] bg-surface-100 flex items-center justify-center mb-4 border border-surface-200/40">
            <svg class="w-8 h-8 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <h3 class="text-base font-black text-surface-900 tracking-tight">Sin resultados</h3>
          <p class="text-sm text-surface-500 mt-1 max-w-xs font-medium">No se encontraron gerentes con los criterios de
            búsqueda actuales.</p>
          <button @click="clearFilters"
            class="mt-4 text-sm font-black text-primary-700 hover:text-primary-800 transition-colors">
            Limpiar filtros
          </button>
        </div>

        <!-- Tabla con datos -->
        <template v-else>
          <!-- TABLA (desktop) -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
                <tr>
                  <th class="px-8 py-4.5 text-left font-extrabold">Gerente</th>
                  <th class="px-6 py-4.5 text-left font-extrabold hidden md:table-cell">Cargo</th>
                  <th class="px-6 py-4.5 text-left font-extrabold">Rol</th>
                  <th class="px-6 py-4.5 text-center font-extrabold">Estatus</th>
                  <th class="px-6 py-4.5 text-right font-extrabold">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-surface-100">
                <tr v-for="manager in filteredManagers" :key="manager.id"
                  class="bg-white border-b border-surface-100 hover:bg-surface-50/60 transition-colors group">
                  <td class="px-8 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-xl bg-gradient-to-br flex items-center justify-center
                         text-white font-black text-xs shrink-0 shadow-xs" :class="avatarGradient(manager.name)">
                        {{ initials(manager.name) }}
                      </div>
                      <div class="flex flex-col min-w-0">
                        <span class="font-bold text-surface-900 tracking-tight truncate">{{ manager.name }}</span>
                        <span class="text-[11px] font-mono tracking-tight text-surface-400 truncate mt-0.5">{{
                          manager.email
                        }}</span>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-xs font-bold text-surface-500 hidden md:table-cell">
                    {{ manager.cargo }}
                  </td>
                  <td class="px-6 py-4">
                    <BadgeStatus :status="manager.rol" size="sm" />
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex justify-center">
                      <button v-if="isGerente" @click="handleToggleStatus(manager)" role="switch" :aria-checked="manager.activo"
                        :aria-label="`${manager.activo ? 'Deshabilitar' : 'Habilitar'} a ${manager.name}`"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2"
                        :class="manager.activo ? 'bg-emerald-500 shadow-sm shadow-emerald-500/20' : 'bg-slate-200'"
                        :disabled="isLoading.toggle">
                        <span
                          class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-350 ease-out"
                          :class="manager.activo ? 'translate-x-5' : 'translate-x-0'" />
                      </button>
                      <div v-else>
                        <div class="w-2.5 h-2.5 rounded-full animate-pulse"
                          :class="manager.activo ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-slate-350'" />
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <ActionMenu :items="buildMenuItems(manager)" align="right" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- TARJETAS (móvil) -->
          <div class="md:hidden flex flex-col gap-3.5 p-4">
            <div v-for="manager in filteredManagers" :key="manager.id"
              class="bg-white border border-surface-200 rounded-2xl shadow-sm">

              <!-- Cabecera: avatar, nombre, rol y acciones -->
              <div class="flex items-center gap-3 px-4 py-3">
                <div
                  class="w-10 h-10 rounded-xl bg-gradient-to-br flex items-center justify-center text-white font-black text-xs shrink-0 shadow-xs"
                  :class="avatarGradient(manager.name)">
                  {{ initials(manager.name) }}
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-bold text-surface-900 truncate leading-tight">{{ manager.name }}</p>
                  <div class="mt-1">
                    <BadgeStatus :status="manager.rol" size="sm" />
                  </div>
                </div>
                <ActionMenu :items="buildMenuItems(manager)" align="right" />
              </div>

              <!-- Cuerpo: cargo, correo y estatus -->
              <div class="px-4 py-3 border-t border-surface-100 space-y-2.5">
                <div class="flex items-center justify-between gap-4 text-sm">
                  <span class="font-semibold text-surface-500">Cargo</span>
                  <span class="font-bold text-surface-900 text-right">{{ manager.cargo }}</span>
                </div>

                <div class="flex items-center justify-between gap-4 text-sm">
                  <span class="font-semibold text-surface-500">Correo</span>
                  <a :href="`mailto:${manager.email}`"
                    class="font-semibold text-primary-600 hover:underline truncate min-w-0">
                    {{ manager.email }}
                  </a>
                </div>

                <div class="flex items-center justify-between gap-4 text-sm">
                  <span class="font-semibold text-surface-500">Estatus</span>
                  <div class="flex items-center gap-2">
                    <span class="font-bold" :class="manager.activo ? 'text-emerald-600' : 'text-surface-400'">
                      {{ manager.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                    <button v-if="isGerente" @click="handleToggleStatus(manager)" :disabled="isLoading.toggle" role="switch" :aria-checked="manager.activo"
                      :aria-label="`${manager.activo ? 'Deshabilitar' : 'Habilitar'} a ${manager.name}`"
                      class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2"
                      :class="manager.activo ? 'bg-emerald-500' : 'bg-slate-200'">
                      <span
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-300 ease-out"
                        :class="manager.activo ? 'translate-x-5' : 'translate-x-0'" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </template>
      </div>
    </div>

    <!-- MODAL: NUEVO GERENTE -->
    <AppModal v-model="isCreateModalOpen" title="Nuevo gerente" subtitle="Registra un nuevo usuario administrativo"
      :icon="IconUserPlus" :persistent="isLoading.create">
      <form id="manager-create-form" class="grid grid-cols-1 gap-4 sm:grid-cols-2" novalidate @submit.prevent="handleCreate">
        <AppField label="Nombre completo" for="mgr-nombre" required :error="createErrors.nombre_completo" class="sm:col-span-2">
          <AppInput id="mgr-nombre" v-model="createForm.nombre_completo" :icon="IconUser" placeholder="Ej. Juan Pérez García" autocomplete="off" :invalid="!!createErrors.nombre_completo" />
        </AppField>
        <AppField label="Correo electrónico" for="mgr-correo" required :error="createErrors.correo_electronico">
          <AppInput id="mgr-correo" v-model="createForm.correo_electronico" type="email" :icon="IconMail" placeholder="juan@ejemplo.com" autocomplete="off" :invalid="!!createErrors.correo_electronico" />
        </AppField>
        <AppField label="Cargo" for="mgr-cargo" required :error="createErrors.cargo">
          <AppInput id="mgr-cargo" v-model="createForm.cargo" :icon="IconBriefcase" placeholder="Ej. Dir. Comercial" autocomplete="off" :invalid="!!createErrors.cargo" />
        </AppField>
        <AppField label="Contraseña" for="mgr-password" required hint="Mínimo 8 caracteres." :error="createErrors.password">
          <AppInput id="mgr-password" v-model="createForm.password" type="password" :icon="IconLock" placeholder="••••••••" autocomplete="new-password" :invalid="!!createErrors.password" />
        </AppField>
        <AppField label="Rol de acceso" required>
          <AppSelect v-model="createForm.rol" :options="OPT_ROL_FORM" />
        </AppField>
      </form>
      <template #footer>
        <AppButton variant="secondary" :disabled="isLoading.create" @click="isCreateModalOpen = false">Cancelar</AppButton>
        <AppButton type="submit" form="manager-create-form" :loading="isLoading.create">Crear gerente</AppButton>
      </template>
    </AppModal>

    <!-- MODAL: EDITAR GERENTE -->
    <AppModal v-model="isEditModalOpen" title="Editar gerente" :subtitle="editForm.nombre_empleado"
      :icon="IconEdit" :persistent="isLoading.update">
      <form id="manager-edit-form" class="grid grid-cols-1 gap-4 sm:grid-cols-2" novalidate @submit.prevent="handleUpdate">
        <AppField label="Nombre completo" for="mgr-edit-nombre" required :error="editErrors.nombre_empleado" class="sm:col-span-2">
          <AppInput id="mgr-edit-nombre" v-model="editForm.nombre_empleado" :icon="IconUser" autocomplete="off" :invalid="!!editErrors.nombre_empleado" />
        </AppField>
        <AppField label="Correo electrónico" for="mgr-edit-correo" required :error="editErrors.email">
          <AppInput id="mgr-edit-correo" v-model="editForm.email" type="email" :icon="IconMail" autocomplete="off" :invalid="!!editErrors.email" />
        </AppField>
        <AppField label="Cargo" for="mgr-edit-cargo" required :error="editErrors.cargo">
          <AppInput id="mgr-edit-cargo" v-model="editForm.cargo" :icon="IconBriefcase" autocomplete="off" :invalid="!!editErrors.cargo" />
        </AppField>
        <AppField label="Nueva contraseña" for="mgr-edit-password" optional hint="Déjala en blanco para no cambiarla." :error="editErrors.password" class="sm:col-span-2">
          <AppInput id="mgr-edit-password" v-model="editForm.password" type="password" :icon="IconLock" placeholder="••••••••" autocomplete="new-password" :invalid="!!editErrors.password" />
        </AppField>
      </form>
      <template #footer>
        <AppButton variant="secondary" :disabled="isLoading.update" @click="isEditModalOpen = false">Cancelar</AppButton>
        <AppButton type="submit" form="manager-edit-form" :loading="isLoading.update">Guardar cambios</AppButton>
      </template>
    </AppModal>

    <!-- MODAL: HABILITAR / DESHABILITAR GERENTE -->
    <AppConfirmModal
      v-model="showToggleModal"
      :title="managerToToggle?.activo ? '¿Deshabilitar gerente?' : '¿Habilitar gerente?'"
      :icon="IconShield"
      :tone="managerToToggle?.activo ? 'rose' : 'emerald'"
      :confirm-label="managerToToggle?.activo ? 'Sí, deshabilitar' : 'Sí, habilitar'"
      :loading="isLoading.toggle"
      @confirm="confirmToggleStatus"
    >
      <template v-if="managerToToggle?.activo">
        <strong class="text-surface-900">{{ managerToToggle?.name }}</strong> perderá el acceso al panel de
        administración hasta que vuelvas a habilitarlo.
      </template>
      <template v-else>
        <strong class="text-surface-900">{{ managerToToggle?.name }}</strong> recuperará el acceso al panel de
        administración con su rol de <strong class="text-surface-900">{{ managerToToggle?.rol }}</strong>.
      </template>
    </AppConfirmModal>
  </main>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}

.animate-scale-in {
  animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }

  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
