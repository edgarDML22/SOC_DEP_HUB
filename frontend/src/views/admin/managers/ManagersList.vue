<script setup>
import { ref, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import {
  AdminPageHeader,
  BadgeStatus,
  ActionMenu,
  SearchInput,
  LoadingSpinner,
  CancelButton
} from '@/components/gerente/ui'
import { IconFilter, IconChevronDown } from '@/components/icons'
import { useAdminStore } from '@/stores/profiles/adminStore'

const adminStore = useAdminStore()
const { managers, isLoading, listFilters } = storeToRefs(adminStore)

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
  { label: 'Todos los roles', value: null },
  { label: 'Gerente', value: 'gerente' },
  { label: 'Subgerente', value: 'subgerente' },
]

const OPT_ESTATUS = [
  { label: 'Todos los estatus', value: null },
  { label: 'Activo', value: '1' },
  { label: 'Inactivo', value: '0' },
]

// Filters
const filteredManagers = computed(() => {
  let r = managers.value
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
const openCreateModal = () => {
  createForm.value = {
    nombre_completo: '',
    correo_electronico: '',
    cargo: '',
    password: '',
    rol: 'subgerente'
  }
  isCreateModalOpen.value = true
}

const openEditModal = (manager) => {
  editForm.value = {
    id: manager.id,
    nombre_empleado: manager.name,
    email: manager.email,
    cargo: manager.cargo
  }
  isEditModalOpen.value = true
}

const handleCreate = async () => {
  const success = await adminStore.crearManager(createForm.value)
  if (success) isCreateModalOpen.value = false
}

const handleUpdate = async () => {
  const success = await adminStore.actualizarManager(editForm.value.id, editForm.value)
  if (success) isEditModalOpen.value = false
}

const handleToggleStatus = async (manager) => {
  await adminStore.toggleActivo(manager.id, !manager.activo)
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
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans animate-fade-in">
    <div class="max-w-7xl mx-auto space-y-8">

      <!-- CABECERA -->
      <AdminPageHeader title="Gestión de Gerentes" subtitle="Administra los accesos de nivel gerencial y subgerencial.">
        <div class="flex items-center gap-3">
          <span class="text-sm font-bold text-surface-500 hidden sm:inline-block">
            {{ filteredManagers.length }}
            <span class="font-medium text-surface-400">de {{ managers.length }} gerentes</span>
          </span>

          <button @click="openCreateModal" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold 
                   flex items-center gap-2 transition-all active:scale-95 shadow-lg shadow-blue-600/20">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden sm:inline">Nuevo Gerente</span>
            <span class="sm:hidden">Nuevo</span>
          </button>
        </div>
      </AdminPageHeader>

      <!-- BARRA DE FILTROS -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5 space-y-4">
        <SearchInput v-model="listFilters.search" placeholder="Buscar por nombre, correo o cargo..." />

        <div class="grid grid-cols-2 gap-3 max-w-2xl">
          <!-- Filtro Rol -->
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Rol de Acceso</label>
            <div class="relative">
              <IconFilter
                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="listFilters.rol"
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_ROL" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown
                class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            </div>
          </div>

          <!-- Filtro Estatus -->
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estatus</label>
            <div class="relative">
              <IconFilter
                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
              <select v-model="listFilters.status"
                class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-400 transition-all cursor-pointer">
                <option v-for="opt in OPT_ESTATUS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <IconChevronDown
                class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
            </div>
          </div>
        </div>

        <Transition enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1">
          <div v-if="hasActiveFilters" class="flex justify-end border-t border-slate-50 pt-3">
            <button @click="clearFilters"
              class="text-xs font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1.5 transition-colors">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 6L6 18M6 6l12 12" />
              </svg>
              Limpiar filtros
            </button>
          </div>
        </Transition>
      </div>

      <!-- TABLA -->
      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden min-h-96">

        <!-- Estado: cargando -->
        <div v-if="isLoading.fetch" class="p-8 space-y-3">
          <div v-for="n in 4" :key="n" class="flex items-center gap-4 animate-pulse py-4 border-b border-surface-50">
            <div class="w-10 h-10 rounded-xl bg-surface-200 shrink-0" />
            <div class="flex-1 space-y-2">
              <div class="h-3.5 bg-surface-200 rounded-lg w-48" />
              <div class="h-3 bg-surface-100 rounded-lg w-28" />
            </div>
            <div class="h-3 w-32 bg-surface-100 rounded-lg hidden sm:block" />
            <div class="h-5 w-24 bg-surface-100 rounded-full" />
            <div class="h-5 w-12 bg-surface-100 rounded-full" />
          </div>
        </div>

        <!-- Estado: vacío -->
        <div v-else-if="filteredManagers.length === 0"
          class="p-20 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 rounded-2xl bg-surface-100 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <h3 class="text-base font-black text-surface-900 tracking-tight">Sin resultados</h3>
          <p class="text-sm text-surface-500 mt-1 max-w-xs">No se encontraron gerentes con los criterios de búsqueda
            actuales.</p>
          <button @click="clearFilters" class="mt-4 text-sm font-bold text-blue-600 hover:underline">
            Limpiar filtros
          </button>
        </div>

        <!-- Tabla con datos -->
        <table v-else class="w-full text-sm">
          <thead>
            <tr class="bg-surface-50 border-b border-surface-200">
              <th class="px-8 py-4 text-left text-xs font-black uppercase tracking-widest text-surface-700">Gerente</th>
              <th
                class="px-6 py-4 text-left text-xs font-black uppercase tracking-widest text-surface-700 hidden md:table-cell">
                Cargo</th>
              <th class="px-6 py-4 text-left text-xs font-black uppercase tracking-widest text-surface-700">Rol</th>
              <th class="px-6 py-4 text-center text-xs font-black uppercase tracking-widest text-surface-700">Estatus
              </th>
              <th class="px-6 py-4 text-right text-xs font-black uppercase tracking-widest text-surface-700">Acciones
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-surface-50">
            <tr v-for="manager in filteredManagers" :key="manager.id"
              class="hover:bg-surface-50/50 transition-colors group">
              <td class="px-8 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-linear-to-br flex items-center justify-center
                             text-white font-black text-xs shrink-0 shadow-sm" :class="avatarGradient(manager.name)">
                    {{ initials(manager.name) }}
                  </div>
                  <div class="flex flex-col min-w-0">
                    <span class="font-bold text-surface-900 tracking-tight truncate">{{ manager.name }}</span>
                    <span class="text-xs text-surface-400 truncate">{{ manager.email }}</span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm font-semibold text-surface-500 hidden md:table-cell">
                {{ manager.cargo }}
              </td>
              <td class="px-6 py-4">
                <BadgeStatus :status="manager.rol" size="sm" />
              </td>
              <td class="px-6 py-4">
                <div class="flex justify-center">
                  <button v-if="isGerente" @click="handleToggleStatus(manager)"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
                    :class="manager.activo ? 'bg-emerald-500 shadow-sm shadow-emerald-500/20' : 'bg-slate-200'"
                    :disabled="isLoading.toggle">
                    <span
                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                      :class="manager.activo ? 'translate-x-5' : 'translate-x-0'" />
                  </button>
                  <div v-else>
                    <div class="w-2.5 h-2.5 rounded-full"
                      :class="manager.activo ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-slate-300'" />
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
    </div>

    <!-- Modals -->
    <Teleport to="body">
      <!-- Create Modal -->
      <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <div v-if="isCreateModalOpen"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="isCreateModalOpen = false">
          <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden animate-scale-in">
            <div class="p-8 pb-4 flex justify-between items-center border-b border-slate-50">
              <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Nuevo Gerente</h3>
                <p class="text-sm text-slate-400 font-medium mt-1.5">Registra un nuevo usuario con acceso
                  administrativo.</p>
              </div>
              <button @click="isCreateModalOpen = false"
                class="p-2.5 hover:bg-slate-100 rounded-xl text-slate-400 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="handleCreate" class="p-8 space-y-5">
              <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Nombre
                  Completo</label>
                <input v-model="createForm.nombre_completo" type="text" required
                  class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none font-semibold text-slate-700"
                  placeholder="Ej. Juan Pérez" />
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Correo
                    Electrónico</label>
                  <input v-model="createForm.correo_electronico" type="email" required
                    class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none font-semibold text-slate-700"
                    placeholder="juan@ejemplo.com" />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Cargo</label>
                  <input v-model="createForm.cargo" type="text" required
                    class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none font-semibold text-slate-700"
                    placeholder="Ej. Dir. Comercial" />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Contraseña</label>
                  <input v-model="createForm.password" type="password" required minlength="8"
                    class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none font-semibold text-slate-700"
                    placeholder="••••••••" />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Rol de
                    Acceso</label>
                  <div class="relative">
                    <select v-model="createForm.rol"
                      class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none appearance-none cursor-pointer font-semibold text-slate-700">
                      <option value="gerente">Gerente</option>
                      <option value="subgerente">Subgerente</option>
                    </select>
                    <IconChevronDown
                      class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                  </div>
                </div>
              </div>

              <div class="pt-4 flex gap-3">
                <CancelButton label="Cancelar" @click="isCreateModalOpen = false" class="flex-1" />
                <button type="submit" :disabled="isLoading.create" class="flex-[2] bg-blue-600 hover:bg-blue-700 text-white px-6 py-3.5 rounded-2xl font-bold 
                         transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50">
                  <LoadingSpinner v-if="isLoading.create" size="sm" color="white" />
                  {{ isLoading.create ? 'Guardando...' : 'Crear Gerente' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>

      <!-- Edit Modal -->
      <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100"
        leave-to-class="opacity-0">
        <div v-if="isEditModalOpen"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="isEditModalOpen = false">
          <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden animate-scale-in">
            <div class="p-8 pb-4 flex justify-between items-center border-b border-slate-50">
              <div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Editar Gerente</h3>
                <p class="text-sm text-slate-400 font-medium mt-1.5">Actualiza la información del usuario
                  administrativo.</p>
              </div>
              <button @click="isEditModalOpen = false"
                class="p-2.5 hover:bg-slate-100 rounded-xl text-slate-400 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="handleUpdate" class="p-8 space-y-5">
              <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Nombre
                  Completo</label>
                <input v-model="editForm.nombre_empleado" type="text" required
                  class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none font-semibold text-slate-700"
                  placeholder="Ej. Juan Pérez" />
              </div>

              <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Correo
                  Electrónico</label>
                <input v-model="editForm.email" type="email" required
                  class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none font-semibold text-slate-700"
                  placeholder="juan@ejemplo.com" />
              </div>

              <div class="space-y-1.5">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Cargo</label>
                <input v-model="editForm.cargo" type="text" required
                  class="w-full px-4 py-3.5 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none font-semibold text-slate-700"
                  placeholder="Ej. Dir. Comercial" />
              </div>

              <div class="pt-4 flex gap-3">
                <CancelButton label="Cancelar" @click="isEditModalOpen = false" class="flex-1" />
                <button type="submit" :disabled="isLoading.update" class="flex-[2] bg-blue-600 hover:bg-blue-700 text-white px-6 py-3.5 rounded-2xl font-bold 
                         transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50">
                  <LoadingSpinner v-if="isLoading.update" size="sm" color="white" />
                  {{ isLoading.update ? 'Actualizando...' : 'Guardar Cambios' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
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
