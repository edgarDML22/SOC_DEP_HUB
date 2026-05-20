<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePlantillasStore } from '@/stores/programacion/plantillasStore'
import { useAlerts } from '@/composables/useAlerts'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import { IconEdit, IconTrash, IconWarning } from '@/components/icons'

const store = usePlantillasStore()
const { toastSuccess, toastError } = useAlerts()

onMounted(() => {
  if (store.plantillas.length === 0) store.fetchPlantillas()
})

// ─── Helpers ──────────────────────────────────────────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  const [y, m, day] = d.split('-')
  return `${day}/${m}/${y}`
}

// ─── Modal: Creación ──────────────────────────────────────────────────────────
const showCreateModal  = ref(false)
const createForm       = ref({ nombre_plantilla: '', fecha_inicio: '', fecha_fin: '' })
const createErrors     = ref({})
const createSuccess    = ref(false)

function openCreate() {
  createForm.value   = { nombre_plantilla: '', fecha_inicio: '', fecha_fin: '' }
  createErrors.value = {}
  createSuccess.value = false
  showCreateModal.value = true
}

function closeCreate() {
  showCreateModal.value = false
  createSuccess.value   = false
}

function validateCreateForm() {
  const errors = {}
  if (!createForm.value.nombre_plantilla.trim())
    errors.nombre_plantilla = 'El nombre es obligatorio.'
  if (createForm.value.fecha_inicio && createForm.value.fecha_fin &&
      createForm.value.fecha_fin < createForm.value.fecha_inicio)
    errors.fecha_fin = 'La fecha de fin debe ser mayor o igual a la de inicio.'
  return errors
}

const createNameEmpty = computed(() => !createForm.value.nombre_plantilla.trim())

async function submitCreate() {
  createErrors.value = validateCreateForm()
  if (Object.keys(createErrors.value).length) return
  try {
    await store.createPlantilla(createForm.value)
    createSuccess.value = true
    setTimeout(() => {
      closeCreate()
      toastSuccess('Plantilla creada correctamente.')
    }, 900)
  } catch (e) {
    const serverErrors = e?.response?.data?.errors
    if (serverErrors) {
      createErrors.value = {
        nombre_plantilla: serverErrors.nombre_plantilla?.[0],
        fecha_inicio:     serverErrors.fecha_inicio?.[0],
        fecha_fin:        serverErrors.fecha_fin?.[0],
      }
    } else {
      toastError(e?.response?.data?.message ?? 'Error al crear la plantilla.')
    }
  }
}

// ─── Modal: Edición ───────────────────────────────────────────────────────────
const showEditModal  = ref(false)
const editTarget     = ref(null)
const editForm       = ref({ nombre_plantilla: '', fecha_inicio: '', fecha_fin: '', estatus_plantilla: '' })
const editErrors     = ref({})
const saveSuccess    = ref(false)

function openEdit(p) {
  editTarget.value = p
  editForm.value   = {
    nombre_plantilla:  p.nombre_plantilla,
    fecha_inicio:      p.fecha_inicio ?? '',
    fecha_fin:         p.fecha_fin    ?? '',
    estatus_plantilla: p.estatus_plantilla,
  }
  editErrors.value  = {}
  saveSuccess.value = false
  showEditModal.value = true
}

function closeEdit() {
  showEditModal.value = false
  editTarget.value    = null
  saveSuccess.value   = false
}

// plantilla activa distinta a la que se está editando
const otherActivePlantilla = computed(() =>
  store.plantillas.find(
    p => p.estatus_plantilla === 'ACTIVO' && p.id_plantilla !== editTarget.value?.id_plantilla
  ) ?? null
)

// intento de pasar de INACTIVO → ACTIVO cuando ya existe otra activa
const blockActivation = computed(() =>
  editForm.value.estatus_plantilla === 'ACTIVO' &&
  editTarget.value?.estatus_plantilla !== 'ACTIVO' &&
  otherActivePlantilla.value !== null
)

async function submitEdit() {
  editErrors.value = {}
  if (!editForm.value.nombre_plantilla.trim()) {
    editErrors.value.nombre_plantilla = 'El nombre es obligatorio.'
    return
  }
  if (blockActivation.value) return
  try {
    await store.updatePlantilla(editTarget.value.id_plantilla, editForm.value)
    saveSuccess.value = true
    setTimeout(closeEdit, 900)
  } catch (e) {
    toastError(e?.response?.data?.message ?? 'Error al actualizar la plantilla.')
  }
}

// ─── Modal: Eliminación ───────────────────────────────────────────────────────
const showDeleteModal     = ref(false)
const deleteTarget        = ref(null)
const deleteConfirmInput  = ref('')

function openDelete(p) {
  deleteTarget.value       = p
  deleteConfirmInput.value = ''
  showDeleteModal.value    = true
}

function closeDelete() {
  showDeleteModal.value = false
  deleteTarget.value    = null
}

const deleteConfirmed = computed(() => deleteConfirmInput.value === 'ELIMINAR')
const isActiveTarget  = computed(() => deleteTarget.value?.estatus_plantilla === 'ACTIVO')

async function submitDelete() {
  if (!deleteConfirmed.value || isActiveTarget.value) return
  try {
    const result = await store.deletePlantilla(deleteTarget.value.id_plantilla)
    toastSuccess(result?.message ?? 'Plantilla eliminada.')
    closeDelete()
  } catch (e) {
    toastError(e?.response?.data?.message ?? 'Error al eliminar la plantilla.')
    closeDelete()
  }
}

defineExpose({ openCreate })
</script>

<template>
  <div class="flex flex-col gap-6">

    <!-- ── Tabla de Plantillas ─────────────────────────────────────────────── -->
    <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm bg-white">

      <TableSkeleton v-if="store.isLoading" :rows="4" :columns="5" :has-avatar="false" />

      <table v-else class="w-full text-sm text-left text-slate-600">
        <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="px-6 py-4 font-extrabold tracking-wider">Nombre de Plantilla</th>
            <th class="px-6 py-4 font-extrabold tracking-wider">Fecha Inicio</th>
            <th class="px-6 py-4 font-extrabold tracking-wider">Fecha Fin</th>
            <th class="px-6 py-4 font-extrabold tracking-wider">Actividades</th>
            <th class="px-6 py-4 font-extrabold tracking-wider">Estatus</th>
            <th class="px-6 py-4 font-extrabold tracking-wider text-right">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.plantillas.length === 0" class="bg-white">
            <td colspan="6" class="px-6 py-14 text-center text-slate-400 font-medium">
              No hay plantillas registradas aún.
            </td>
          </tr>
          <tr
            v-else
            v-for="(p, idx) in store.plantillas"
            :key="p.id_plantilla"
            class="bg-white border-b border-slate-100 hover:bg-slate-50 transition-colors animate-row-in"
            :style="{ animationDelay: `${idx * 40}ms` }"
          >
            <td class="px-6 py-4">
              <div class="font-bold text-slate-800">{{ p.nombre_plantilla }}</div>
            </td>
            <td class="px-6 py-4 font-semibold text-slate-700 whitespace-nowrap">{{ formatDate(p.fecha_inicio) }}</td>
            <td class="px-6 py-4 font-semibold text-slate-700 whitespace-nowrap">{{ formatDate(p.fecha_fin) }}</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-blue-50 text-blue-700 text-sm font-extrabold border border-blue-100">
                {{ p.total_actividades ?? 0 }}
              </span>
            </td>
            <td class="px-6 py-4">
              <span
                class="inline-flex items-center font-bold rounded-full border uppercase whitespace-nowrap text-xs tracking-wide px-3 py-1"
                :class="p.estatus_plantilla === 'ACTIVO'
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : 'bg-slate-100 text-slate-500 border-slate-200'"
              >
                {{ p.estatus_plantilla === 'ACTIVO' ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <!-- Editar -->
                <button
                  @click="openEdit(p)"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 active:scale-95"
                >
                  <IconEdit class="w-3.5 h-3.5" />
                  Editar
                </button>

                <!-- Eliminar (siempre visible; el modal muestra el bloqueo si está ACTIVO) -->
                <button
                  @click="openDelete(p)"
                  :class="p.estatus_plantilla === 'ACTIVO'
                    ? 'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-400 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors border border-slate-200 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1'
                    : 'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 active:scale-95'"
                  :title="p.estatus_plantilla === 'ACTIVO' ? 'Plantilla en producción – ver detalles' : 'Eliminar plantilla'"
                >
                  <IconTrash class="w-3.5 h-3.5" />
                  Eliminar
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: CREACIÓN                                                       -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showCreateModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="closeCreate"
        >
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col relative">

            <!-- Overlay de éxito -->
            <Transition
              enter-active-class="transition-opacity duration-150 ease-out"
              enter-from-class="opacity-0"
              enter-to-class="opacity-100"
              leave-active-class="transition-opacity duration-200 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <div v-if="createSuccess" class="absolute inset-0 z-10 bg-white/90 backdrop-blur-[2px] rounded-2xl flex flex-col items-center justify-center gap-4">
                <div class="w-16 h-16 rounded-full bg-emerald-600 flex items-center justify-center shadow-lg animate-scale-in">
                  <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                </div>
                <p class="text-base font-black text-slate-800">Plantilla creada</p>
              </div>
            </Transition>

            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-50 rounded-xl">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                  </svg>
                </div>
                <div>
                  <p class="text-[10px] uppercase font-black tracking-widest text-slate-400">Programación</p>
                  <h3 class="text-lg font-black text-slate-800 leading-tight">Nueva Plantilla</h3>
                </div>
              </div>
              <button
                @click="closeCreate"
                class="text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 p-2 rounded-xl transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4 bg-slate-50/50">
              <!-- Nombre -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Nombre de plantilla</label>
                <input
                  v-model="createForm.nombre_plantilla"
                  type="text"
                  placeholder="Ej. Programación Base 2026-II"
                  class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-colors"
                  :class="createErrors.nombre_plantilla ? 'border-red-400' : ''"
                />
                <p v-if="createErrors.nombre_plantilla" class="text-red-500 text-xs mt-1 font-medium">{{ createErrors.nombre_plantilla }}</p>
              </div>

              <!-- Fechas -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Fecha inicio</label>
                  <input
                    v-model="createForm.fecha_inicio"
                    type="date"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-colors"
                    :class="createErrors.fecha_inicio ? 'border-red-400' : ''"
                  />
                  <p v-if="createErrors.fecha_inicio" class="text-red-500 text-xs mt-1 font-medium">{{ createErrors.fecha_inicio }}</p>
                </div>
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Fecha fin</label>
                  <input
                    v-model="createForm.fecha_fin"
                    type="date"
                    :min="createForm.fecha_inicio || undefined"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-colors"
                    :class="createErrors.fecha_fin ? 'border-red-400' : ''"
                  />
                  <p v-if="createErrors.fecha_fin" class="text-red-500 text-xs mt-1 font-medium">{{ createErrors.fecha_fin }}</p>
                </div>
              </div>

              <!-- Info estado inicial -->
              <div class="flex items-start gap-2 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2.5">
                <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs font-semibold text-blue-700">
                  La plantilla se creará en estado <strong>INACTIVO</strong>. Podrás activarla desde el panel de edición una vez hayas configurado sus bloques.
                </p>
              </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-slate-100 bg-white flex justify-end gap-3">
              <button
                @click="closeCreate"
                class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="submitCreate"
                :disabled="createNameEmpty || store.isSaving"
                :class="createNameEmpty || store.isSaving
                  ? 'px-6 py-2.5 text-sm font-extrabold text-slate-400 bg-slate-100 rounded-xl border border-slate-200 cursor-not-allowed transition-all'
                  : 'px-6 py-2.5 text-sm font-extrabold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-all'"
              >
                {{ store.isSaving ? 'Creando...' : 'Crear plantilla' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: EDICIÓN                                                        -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showEditModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="closeEdit"
        >
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col relative">

            <!-- Overlay de éxito (animate-scale-in idéntico a ManageDisciplinesModal) -->
            <Transition
              enter-active-class="transition-opacity duration-150 ease-out"
              enter-from-class="opacity-0"
              enter-to-class="opacity-100"
              leave-active-class="transition-opacity duration-200 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <div v-if="saveSuccess" class="absolute inset-0 z-10 bg-white/90 backdrop-blur-[2px] rounded-2xl flex flex-col items-center justify-center gap-4">
                <div class="w-16 h-16 rounded-full bg-emerald-600 flex items-center justify-center shadow-lg animate-scale-in">
                  <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                </div>
                <p class="text-base font-black text-slate-800">Plantilla actualizada</p>
              </div>
            </Transition>

            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-50 rounded-xl">
                  <IconEdit class="w-5 h-5 text-blue-600" />
                </div>
                <div>
                  <p class="text-[10px] uppercase font-black tracking-widest text-slate-400">Plantilla</p>
                  <h3 class="text-lg font-black text-slate-800 leading-tight">Editar plantilla</h3>
                </div>
              </div>
              <button
                @click="closeEdit"
                class="text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 p-2 rounded-xl transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4 bg-slate-50/50">
              <!-- Nombre -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Nombre de plantilla</label>
                <input
                  v-model="editForm.nombre_plantilla"
                  type="text"
                  placeholder="Ej. Programación Base 2026"
                  class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-colors"
                />
                <p v-if="editErrors.nombre_plantilla" class="text-red-500 text-xs mt-1 font-medium">{{ editErrors.nombre_plantilla }}</p>
              </div>

              <!-- Fechas -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Fecha inicio</label>
                  <input
                    v-model="editForm.fecha_inicio"
                    type="date"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Fecha fin</label>
                  <input
                    v-model="editForm.fecha_fin"
                    type="date"
                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition-colors"
                  />
                </div>
              </div>

              <!-- Estatus -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Estatus</label>
                <div class="flex p-1 bg-slate-100 rounded-xl border border-slate-200 gap-1">
                  <!-- Botón ACTIVO — deshabilitado si hay otra plantilla activa -->
                  <button
                    type="button"
                    @click="!blockActivation && (editForm.estatus_plantilla = 'ACTIVO')"
                    :class="[
                      'flex-1 py-2 px-3 text-xs font-extrabold rounded-lg transition-all',
                      editForm.estatus_plantilla === 'ACTIVO'
                        ? 'bg-emerald-600 text-white shadow-sm'
                        : blockActivation
                          ? 'text-slate-300 cursor-not-allowed'
                          : 'text-slate-500 hover:bg-white/60'
                    ]"
                  >
                    Activo
                  </button>
                  <!-- Botón INACTIVO -->
                  <button
                    type="button"
                    @click="editForm.estatus_plantilla = 'INACTIVO'"
                    :class="[
                      'flex-1 py-2 px-3 text-xs font-extrabold rounded-lg transition-all',
                      editForm.estatus_plantilla === 'INACTIVO'
                        ? 'bg-slate-600 text-white shadow-sm'
                        : 'text-slate-500 hover:bg-white/60'
                    ]"
                  >
                    Inactivo
                  </button>
                </div>

                <!-- Bloqueo: ya existe otra plantilla activa -->
                <Transition name="fade-down">
                  <div v-if="blockActivation" class="mt-2 flex items-start gap-2 bg-red-50 border border-red-200 rounded-xl px-3 py-2.5">
                    <IconWarning class="w-4 h-4 text-red-500 shrink-0 mt-0.5" />
                    <p class="text-xs font-semibold text-red-700">
                      Solo puede haber una programación activa. Debes desactivar
                      <strong>"{{ otherActivePlantilla?.nombre_plantilla }}"</strong>
                      antes de activar esta plantilla.
                    </p>
                  </div>
                </Transition>
              </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-slate-100 bg-white flex justify-end gap-3">
              <button
                @click="closeEdit"
                class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="submitEdit"
                :disabled="store.isSaving || blockActivation"
                :class="store.isSaving || blockActivation
                  ? 'px-6 py-2.5 text-sm font-extrabold text-slate-400 bg-slate-100 rounded-xl border border-slate-200 cursor-not-allowed transition-all'
                  : 'px-6 py-2.5 text-sm font-extrabold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-all'"
              >
                {{ store.isSaving ? 'Guardando...' : 'Guardar cambios' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: ELIMINACIÓN PELIGROSA (estilo GitHub)                          -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showDeleteModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="closeDelete"
        >
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">

            <!-- Header rojo -->
            <div class="flex items-center justify-between p-6 border-b border-red-100 bg-red-50">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 rounded-xl">
                  <IconTrash class="w-5 h-5 text-red-600" />
                </div>
                <div>
                  <p class="text-[10px] uppercase font-black tracking-widest text-red-400">Zona de peligro</p>
                  <h3 class="text-lg font-black text-red-800 leading-tight">Eliminar plantilla</h3>
                </div>
              </div>
              <button
                @click="closeDelete"
                class="text-slate-400 hover:text-slate-600 bg-white/70 hover:bg-white p-2 rounded-xl transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">

              <!-- Banner de bloqueo: plantilla ACTIVO -->
              <Transition name="fade-down">
                <div v-if="isActiveTarget" class="flex items-start gap-3 bg-amber-50 border border-amber-300 rounded-xl p-4">
                  <IconWarning class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-extrabold text-amber-800">Acción denegada: La plantilla está en producción</p>
                    <p class="text-xs text-amber-700 mt-1">
                      No es posible eliminar una programación que se encuentra actualmente <strong>ACTIVA</strong>. Primero desactívala desde el panel de edición y luego podrás eliminarla.
                    </p>
                  </div>
                </div>
              </Transition>

              <template v-if="!isActiveTarget">
                <p class="text-sm text-slate-700">
                  Estás a punto de eliminar la plantilla
                  <strong class="text-slate-900">"{{ deleteTarget?.nombre_plantilla }}"</strong>
                  y todas sus actividades asociadas.
                </p>

                <div class="bg-red-50 border border-red-200 rounded-xl p-3">
                  <p class="text-xs font-bold text-red-700">
                    Para confirmar, escribe <span class="font-black tracking-widest">ELIMINAR</span> en el campo de abajo:
                  </p>
                </div>

                <input
                  v-model="deleteConfirmInput"
                  type="text"
                  placeholder="ELIMINAR"
                  class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 tracking-wider placeholder:tracking-normal placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-red-500/40 focus:border-red-500 transition-colors"
                  :class="deleteConfirmed ? 'border-red-400 bg-red-50' : ''"
                />
              </template>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-slate-100 bg-white flex justify-end gap-3">
              <button
                @click="closeDelete"
                class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors"
              >
                {{ isActiveTarget ? 'Cerrar' : 'Cancelar' }}
              </button>
              <button
                v-if="!isActiveTarget"
                @click="submitDelete"
                :disabled="!deleteConfirmed || store.isDeleting"
                class="px-6 py-2.5 text-sm font-extrabold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed"
              >
                {{ store.isDeleting ? 'Eliminando...' : 'Confirmar destrucción' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
/* ── Check de éxito (idéntico a ManageDisciplinesModal) ── */
.animate-scale-in {
  animation: scaleIn 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}
@keyframes scaleIn {
  from { transform: scale(0); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}

/* ── Animación de filas de tabla (idéntica a ManageDisciplinesModal) ── */
.animate-row-in {
  animation: rowIn 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}
@keyframes rowIn {
  from { opacity: 0; transform: translateY(6px) scale(0.98); }
  to   { opacity: 1; transform: translateY(0)   scale(1);    }
}

/* ── Modales ── */
.modal-enter-active,
.modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-active > div,
.modal-leave-active > div { transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }
.modal-enter-from > div,
.modal-leave-to > div { opacity: 0; transform: scale(0.96) translateY(8px); }

/* ── Advertencia de activación ── */
.fade-down-enter-active,
.fade-down-leave-active { transition: all 0.2s ease; }
.fade-down-enter-from,
.fade-down-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
