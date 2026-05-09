<script setup>
import { ref, onMounted, computed } from 'vue'
import { 
  AdminPageHeader, 
  BadgeStatus, 
  ActionMenu, 
  SearchInput,
  LoadingSpinner 
} from '@/components/gerente/ui'
import { useAdminStore } from '@/stores/profiles/adminStore'

const store = useAdminStore()

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
const searchQuery = ref('')
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

// Filters
const filteredManagers = computed(() => {
  if (!searchQuery.value) return store.managers
  const q = searchQuery.value.toLowerCase()
  return store.managers.filter(m => 
    m.name?.toLowerCase().includes(q) || 
    m.email?.toLowerCase().includes(q) ||
    m.cargo?.toLowerCase().includes(q)
  )
})

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
  const success = await store.crearManager(createForm.value)
  if (success) isCreateModalOpen.value = false
}

const handleUpdate = async () => {
  const success = await store.actualizarManager(editForm.value.id, editForm.value)
  if (success) isEditModalOpen.value = false
}

const handleToggleStatus = async (manager) => {
  await store.toggleActivo(manager.id, !manager.activo)
}

const buildMenuItems = (manager) => {
  const items = [
    { 
      label: 'Editar', 
      icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
      action: () => openEditModal(manager) 
    }
  ]

  if (isGerente.value) {
    items.push({
      label: manager.activo ? 'Deshabilitar' : 'Habilitar',
      icon: manager.activo 
        ? '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>'
        : '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
      action: () => handleToggleStatus(manager),
      destructive: manager.activo
    })
  }

  return items
}

onMounted(() => {
  store.fetchManagers()
})
</script>

<template>
  <div class="p-6 max-w-7xl mx-auto space-y-6 animate-fade-in">
    <!-- Header -->
    <AdminPageHeader 
      title="Gestión de Gerentes" 
      subtitle="Administra los accesos de nivel gerencial y subgerencial."
    >
      <button 
        @click="openCreateModal"
        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold 
               flex items-center gap-2 transition-all active:scale-95 shadow-lg shadow-blue-600/20"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
        </svg>
        Nuevo Gerente
      </button>
    </AdminPageHeader>

    <!-- Content Card -->
    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden">
      <!-- Search -->
      <div class="p-6 border-b border-slate-50 flex items-center justify-between gap-4 flex-wrap">
        <SearchInput v-model="searchQuery" placeholder="Buscar por nombre, correo o cargo..." class="max-w-md w-full" />
        
        <div class="text-sm font-medium text-slate-400">
          Mostrando {{ filteredManagers.length }} gerentes
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/50 text-slate-400 text-[11px] uppercase tracking-widest font-bold">
              <th class="px-8 py-4">Nombre</th>
              <th class="px-6 py-4">Correo</th>
              <th class="px-6 py-4">Cargo</th>
              <th class="px-6 py-4">Rol</th>
              <th class="px-6 py-4 text-center">Estatus</th>
              <th class="px-6 py-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-if="store.isLoading.fetch" class="animate-pulse">
              <td colspan="6" class="py-20 text-center">
                <LoadingSpinner size="lg" />
                <p class="mt-4 text-slate-400 font-medium">Cargando gerentes...</p>
              </td>
            </tr>

            <tr v-else-if="filteredManagers.length === 0">
              <td colspan="6" class="py-20 text-center text-slate-400 font-medium">
                No se encontraron gerentes que coincidan con la búsqueda.
              </td>
            </tr>

            <tr 
              v-for="manager in filteredManagers" 
              :key="manager.id"
              class="hover:bg-slate-50/80 transition-colors group"
            >
              <td class="px-8 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                    {{ manager.name?.substring(0, 2).toUpperCase() }}
                  </div>
                  <span class="font-bold text-slate-700 tracking-tight">{{ manager.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 text-sm font-medium text-slate-500">{{ manager.email }}</td>
              <td class="px-6 py-4 text-sm font-medium text-slate-500">{{ manager.cargo }}</td>
              <td class="px-6 py-4">
                <BadgeStatus :status="manager.rol" size="md" />
              </td>
              <td class="px-6 py-4">
                <div class="flex justify-center">
                  <button 
                    v-if="isGerente"
                    @click="handleToggleStatus(manager)"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
                    :class="manager.activo ? 'bg-emerald-500' : 'bg-slate-200'"
                    :disabled="store.isLoading.toggle"
                  >
                    <span 
                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                      :class="manager.activo ? 'translate-x-5' : 'translate-x-0'"
                    />
                  </button>
                  <div v-else>
                    <div 
                      class="w-2.5 h-2.5 rounded-full"
                      :class="manager.activo ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-slate-300'"
                    />
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-right">
                <ActionMenu :items="buildMenuItems(manager)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modals -->
    <Teleport to="body">
      <!-- Create Modal -->
      <Transition 
        enter-active-class="transition duration-300 ease-out" 
        enter-from-class="opacity-0" 
        enter-to-class="opacity-100" 
        leave-active-class="transition duration-200 ease-in" 
        leave-from-class="opacity-100" 
        leave-to-class="opacity-0"
      >
        <div v-if="isCreateModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div 
            class="bg-white w-full max-w-lg rounded-[2rem] shadow-2xl overflow-hidden animate-scale-in"
            @click.stop
          >
            <div class="p-8 pb-4 flex justify-between items-center border-b border-slate-50">
              <h3 class="text-2xl font-black text-slate-900 tracking-tight">Nuevo Gerente</h3>
              <button @click="isCreateModalOpen = false" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 transition-colors">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <form @submit.prevent="handleCreate" class="p-8 space-y-5">
              <div class="space-y-1.5">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Nombre Completo</label>
                <input 
                  v-model="createForm.nombre_completo" 
                  type="text" required
                  class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none"
                  placeholder="Ej. Juan Pérez"
                />
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <label class="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Correo Electrónico</label>
                  <input 
                    v-model="createForm.correo_electronico" 
                    type="email" required
                    class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none"
                    placeholder="juan@ejemplo.com"
                  />
                </div>
                <div class="space-y-1.5">
                  <label class="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Cargo</label>
                  <input 
                    v-model="createForm.cargo" 
                    type="text" required
                    class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none"
                    placeholder="Ej. Dir. Comercial"
                  />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <label class="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Contraseña</label>
                  <input 
                    v-model="createForm.password" 
                    type="password" required minlength="8"
                    class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none"
                    placeholder="••••••••"
                  />
                </div>
                <div class="space-y-1.5">
                  <label class="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Rol de Acceso</label>
                  <select 
                    v-model="createForm.rol"
                    class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none appearance-none cursor-pointer"
                  >
                    <option value="gerente">Gerente</option>
                    <option value="subgerente">Subgerente</option>
                  </select>
                </div>
              </div>

              <div class="pt-4 flex gap-3">
                <button 
                  type="button" @click="isCreateModalOpen = false"
                  class="flex-1 px-6 py-3.5 rounded-2xl font-bold text-slate-500 hover:bg-slate-100 transition-colors"
                >
                  Cancelar
                </button>
                <button 
                  type="submit"
                  :disabled="store.isLoading.create"
                  class="flex-[2] bg-blue-600 hover:bg-blue-700 text-white px-6 py-3.5 rounded-2xl font-bold 
                         transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50"
                >
                  <LoadingSpinner v-if="store.isLoading.create" size="sm" color="white" />
                  {{ store.isLoading.create ? 'Guardando...' : 'Crear Gerente' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>

      <!-- Edit Modal -->
      <Transition 
        enter-active-class="transition duration-300 ease-out" 
        enter-from-class="opacity-0" 
        enter-to-class="opacity-100" 
        leave-active-class="transition duration-200 ease-in" 
        leave-from-class="opacity-100" 
        leave-to-class="opacity-0"
      >
        <div v-if="isEditModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div 
            class="bg-white w-full max-w-lg rounded-[2rem] shadow-2xl overflow-hidden animate-scale-in"
            @click.stop
          >
            <div class="p-8 pb-4 flex justify-between items-center border-b border-slate-50">
              <h3 class="text-2xl font-black text-slate-900 tracking-tight">Editar Gerente</h3>
              <button @click="isEditModalOpen = false" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 transition-colors">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <form @submit.prevent="handleUpdate" class="p-8 space-y-5">
              <div class="space-y-1.5">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Nombre Completo</label>
                <input 
                  v-model="editForm.nombre_empleado" 
                  type="text" required
                  class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none"
                  placeholder="Ej. Juan Pérez"
                />
              </div>

              <div class="space-y-1.5">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Correo Electrónico</label>
                <input 
                  v-model="editForm.email" 
                  type="email" required
                  class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none"
                  placeholder="juan@ejemplo.com"
                />
              </div>

              <div class="space-y-1.5">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-400 ml-1">Cargo</label>
                <input 
                  v-model="editForm.cargo" 
                  type="text" required
                  class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 focus:border-blue-500 focus:bg-white transition-all outline-none"
                  placeholder="Ej. Dir. Comercial"
                />
              </div>

              <div class="pt-4 flex gap-3">
                <button 
                  type="button" @click="isEditModalOpen = false"
                  class="flex-1 px-6 py-3.5 rounded-2xl font-bold text-slate-500 hover:bg-slate-100 transition-colors"
                >
                  Cancelar
                </button>
                <button 
                  type="submit"
                  :disabled="store.isLoading.update"
                  class="flex-[2] bg-blue-600 hover:bg-blue-700 text-white px-6 py-3.5 rounded-2xl font-bold 
                         transition-all active:scale-95 flex items-center justify-center gap-2 disabled:opacity-50"
                >
                  <LoadingSpinner v-if="store.isLoading.update" size="sm" color="white" />
                  {{ store.isLoading.update ? 'Actualizando...' : 'Guardar Cambios' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-out;
}
.animate-scale-in {
  animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}
</style>
