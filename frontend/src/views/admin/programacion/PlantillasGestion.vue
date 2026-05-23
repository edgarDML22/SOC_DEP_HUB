<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePlantillasStore } from '@/stores/programacion/plantillasStore'
import { useAlerts } from '@/composables/useAlerts'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import { IconEdit, IconTrash, IconWarning } from '@/components/icons'

const store = usePlantillasStore()
const { toastSuccess, toastError } = useAlerts()

// ─── Modal: Publicar ─────────────────────────────────────────────────────────
const showPublishModal   = ref(false)
const publishTarget      = ref(null)
const publishSuccess     = ref(false)
const publishError       = ref('')
const publishConfirmInput = ref('')

// Formatea una fecha local como 'YYYY-MM-DD' sin conversión UTC
function toLocalDateString(d) {
  const y  = d.getFullYear()
  const m  = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${dd}`
}

// Devuelve el próximo lunes estrictamente mayor que hoy (nunca hoy mismo).
// Usa hora local para evitar que toISOString() adelante/atrase un día por UTC.
function getNextMonday(offsetWeeks = 0) {
  const d   = new Date()
  const day = d.getDay()                      // 0 dom … 6 sab; 1 = lunes
  // Si hoy es lunes (1) → próximo lunes en 7 días; resto → días que faltan hasta el lunes
  const daysUntilMonday = day === 1 ? 7 : (8 - day) % 7
  d.setDate(d.getDate() + daysUntilMonday + offsetWeeks * 7)
  return toLocalDateString(d)
}

// Las dos únicas opciones posibles
const opcionSemana1 = computed(() => getNextMonday(0))
const opcionSemana2 = computed(() => getNextMonday(1))

// Selección múltiple: el usuario puede elegir una o las dos
const semanasSeleccionadas = ref([])          // array de 'YYYY-MM-DD'

function toggleSemana(fecha) {
  const idx = semanasSeleccionadas.value.indexOf(fecha)
  if (idx === -1) semanasSeleccionadas.value.push(fecha)
  else            semanasSeleccionadas.value.splice(idx, 1)
}

function openPublish(p) {
  publishTarget.value       = p
  semanasSeleccionadas.value = []
  publishSuccess.value      = false
  publishError.value        = ''
  publishConfirmInput.value = ''
  showPublishModal.value    = true
}

function closePublish() {
  showPublishModal.value    = false
  publishTarget.value       = null
  publishError.value        = ''
  publishConfirmInput.value = ''
  semanasSeleccionadas.value = []
}

const publishConfirmed = computed(() => publishConfirmInput.value === 'CONFIRMAR')

// Al menos una semana seleccionada + texto de confirmación correcto
const publishReady = computed(() =>
  semanasSeleccionadas.value.length > 0 && publishConfirmed.value
)

// Llama al backend una vez por semana seleccionada (en orden cronológico)
async function submitPublish() {
  if (!publishReady.value) return
  publishError.value = ''

  const semanas = [...semanasSeleccionadas.value].sort()
  let totalCreadas = 0
  const errores = []

  for (let i = 0; i < semanas.length; i++) {
    const semana = semanas[i]
    const esUltima = i === semanas.length - 1
    try {
      const result = await store.publicarPlantilla(publishTarget.value.id_plantilla, semana, esUltima)
      totalCreadas += result?.data?.sesiones_creadas ?? 0
    } catch (e) {
      const meta = e?.response?.data?.meta
      if (e?.response?.status === 409 && meta) {
        errores.push(
          `Semana ${formatDate(meta.semana_inicio)}–${formatDate(meta.semana_fin)}: ` +
          `ya existen ${meta.sesiones_existentes} sesiones publicadas.`
        )
      } else {
        errores.push(e?.response?.data?.message ?? `Error al publicar la semana ${formatDate(semana)}.`)
      }
    }
  } // fin for

  if (errores.length === 0) {
    publishSuccess.value = true
    setTimeout(() => {
      closePublish()
      toastSuccess(`${totalCreadas} sesiones publicadas correctamente.`)
    }, 1000)
  } else if (totalCreadas > 0) {
    // Publicación parcial: algunas semanas OK, otras con conflicto
    publishError.value = errores.join(' | ')
    toastSuccess(`${totalCreadas} sesiones publicadas (con advertencias).`)
  } else {
    publishError.value = errores.join(' | ')
  }
}

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
const editForm       = ref({ nombre_plantilla: '', fecha_inicio: '', fecha_fin: '', estatus_plantilla: false })
const editErrors     = ref({})
const saveSuccess    = ref(false)

function openEdit(p) {
  editTarget.value = p
  editForm.value   = {
    nombre_plantilla:  p.nombre_plantilla,
    fecha_inicio:      p.fecha_inicio ?? '',   // solo lectura en UI
    fecha_fin:         p.fecha_fin    ?? '',   // solo lectura en UI
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
    p => p.estatus_plantilla === true && p.id_plantilla !== editTarget.value?.id_plantilla
  ) ?? null
)

// intento de pasar de INACTIVO → ACTIVO cuando ya existe otra activa
const blockActivation = computed(() =>
  editForm.value.estatus_plantilla === true &&
  editTarget.value?.estatus_plantilla !== true &&
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
    const { fecha_inicio, fecha_fin, ...payload } = editForm.value
    await store.updatePlantilla(editTarget.value.id_plantilla, payload)
    saveSuccess.value = true
    setTimeout(closeEdit, 900)
  } catch (e) {
    toastError(e?.response?.data?.message ?? 'Error al actualizar la plantilla.')
  }
}

// ─── Modal: Despublicación ────────────────────────────────────────────────────
const showUnpublishModal    = ref(false)
const unpublishTarget       = ref(null)
const unpublishConfirmInput = ref('')
const unpublishSuccess      = ref(false)
const unpublishError        = ref('')

function openUnpublish(p) {
  unpublishTarget.value       = p
  unpublishConfirmInput.value = ''
  unpublishSuccess.value      = false
  unpublishError.value        = ''
  showUnpublishModal.value    = true
}

function closeUnpublish() {
  showUnpublishModal.value    = false
  unpublishTarget.value       = null
  unpublishConfirmInput.value = ''
  unpublishError.value        = ''
}

const unpublishConfirmed = computed(() => unpublishConfirmInput.value === 'RETIRAR')

async function submitUnpublish() {
  if (!unpublishConfirmed.value) return
  unpublishError.value = ''
  try {
    const result = await store.despublicarPlantilla(unpublishTarget.value.id_plantilla)
    unpublishSuccess.value = true
    const eliminadas = result?.data?.sesiones_eliminadas ?? 0
    setTimeout(() => {
      closeUnpublish()
      toastSuccess(`Programación retirada. ${eliminadas} sesión(es) eliminada(s).`)
    }, 1000)
  } catch (e) {
    const meta = e?.response?.data?.meta
    if (e?.response?.status === 422 && meta) {
      unpublishError.value = e.response.data.meta.motivo
    } else {
      unpublishError.value = e?.response?.data?.message ?? 'Error al despublicar la programación.'
    }
  }
}

// ─── Modal: Eliminación ───────────────────────────────────────────────────────
const showDeleteModal     = ref(false)
const deleteTarget        = ref(null)
const deleteConfirmInput  = ref('')

// Modal de error bloqueante (reemplaza el toast cuando el backend devuelve 422)
const showDeleteErrorModal = ref(false)
const deleteErrorTitle     = ref('')
const deleteErrorMessage   = ref('')

function openDelete(p) {
  deleteTarget.value       = p
  deleteConfirmInput.value = ''
  showDeleteModal.value    = true
}

function closeDelete() {
  showDeleteModal.value = false
  deleteTarget.value    = null
}

function closeDeleteError() {
  showDeleteErrorModal.value = false
  deleteErrorTitle.value     = ''
  deleteErrorMessage.value   = ''
}

const deleteConfirmed = computed(() => deleteConfirmInput.value === 'ELIMINAR')
const isActiveTarget  = computed(() => deleteTarget.value?.estatus_plantilla === true)

function getMenuItems(p) {
  const items = []

  items.push({
    label: 'Editar',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>',
    action: () => openEdit(p)
  })

  if (!p.publicada) {
    const canPublish = p.estatus_plantilla && (p.total_actividades ?? 0) > 0
    items.push({
      label: 'Publicar',
      icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
      action: () => openPublish(p),
      disabled: !canPublish
    })
  } else {
    items.push({
      label: 'Retirar',
      icon: '<svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>',
      action: () => openUnpublish(p)
    })
    
    items.push({
      label: 'Exportar PDF',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
      action: async () => {
        try {
          await store.exportarPdf(p.id_plantilla)
          toastSuccess('PDF generado exitosamente')
        } catch (e) {
          toastError('Error al generar el PDF')
        }
      },
      customClass: 'text-purple-600 hover:bg-purple-50'
    })
  }

  items.push({ separator: true })

  items.push({
    label: 'Eliminar',
    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
    action: () => openDelete(p),
    destructive: true,
    disabled: p.estatus_plantilla
  })

  return items
}

async function submitDelete() {
  if (!deleteConfirmed.value || isActiveTarget.value) return
  try {
    const result = await store.deletePlantilla(deleteTarget.value.id_plantilla)
    toastSuccess(result?.message ?? 'Plantilla eliminada.')
    closeDelete()
  } catch (e) {
    const status  = e?.response?.status
    const message = e?.response?.data?.message ?? 'Ocurrió un error al intentar eliminar la plantilla.'
    closeDelete()
    if (status === 422) {
      deleteErrorTitle.value     = 'No se puede eliminar esta plantilla'
      deleteErrorMessage.value   = message
      showDeleteErrorModal.value = true
    } else {
      toastError(message)
    }
  }
}

defineExpose({ openCreate })
</script>

<template>
  <div class="flex flex-col gap-6">

    <!-- ── Tabla de Plantillas ─────────────────────────────────────────────── -->
    <div class="overflow-x-auto rounded-2xl border border-surface-200 shadow-sm bg-white">

      <TableSkeleton v-if="store.isLoading" :rows="4" :columns="5" :has-avatar="false" />

      <table v-else class="w-full text-sm text-left">
        <thead class="bg-surface-50 border-b border-surface-200">
          <tr>
            <th class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-widest text-slate-900">Nombre de Plantilla</th>
            <th class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-widest text-slate-900">Fecha Inicio</th>
            <th class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-widest text-slate-900">Fecha Fin</th>
            <th class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-widest text-slate-900">Actividades</th>
            <th class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-widest text-slate-900">Estatus</th>
            <th class="px-6 py-4 text-right text-[11px] font-black uppercase tracking-widest text-slate-900">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-surface-100">
          <tr v-if="store.plantillas.length === 0" class="bg-white">
            <td colspan="6" class="px-6 py-14 text-center text-slate-400 font-medium">
              No hay plantillas registradas aún.
            </td>
          </tr>
          <tr
            v-else
            v-for="(p, idx) in store.plantillas"
            :key="p.id_plantilla"
            class="bg-white border-b border-surface-100 hover:bg-surface-50/50 transition-colors animate-row-in"
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
                :class="p.estatus_plantilla
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : 'bg-slate-100 text-slate-500 border-slate-200'"
              >
                {{ p.estatus_plantilla ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <!-- Indicador "Publicada" -->
                <span
                  v-if="p.publicada"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg cursor-default"
                  title="Esta programación ya fue publicada"
                >
                  <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                  </svg>
                  Publicada
                </span>
                
                <ActionMenu :items="getMenuItems(p)" align="right" />
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

              <!-- Fechas (solo lectura — se asignan automáticamente al publicar) -->
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Fecha inicio</label>
                  <div class="w-full border border-slate-100 rounded-xl px-4 py-2.5 bg-slate-50 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-semibold text-slate-400">{{ formatDate(editForm.fecha_inicio) }}</span>
                  </div>
                </div>
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Fecha fin</label>
                  <div class="w-full border border-slate-100 rounded-xl px-4 py-2.5 bg-slate-50 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-semibold text-slate-400">{{ formatDate(editForm.fecha_fin) }}</span>
                  </div>
                </div>
              </div>

              <!-- Estatus -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">Estatus</label>
                <div class="flex p-1 bg-slate-100 rounded-xl border border-slate-200 gap-1">
                  <!-- Botón ACTIVO — deshabilitado si hay otra plantilla activa -->
                  <button
                    type="button"
                    @click="!blockActivation && (editForm.estatus_plantilla = true)"
                    :class="[
                      'flex-1 py-2 px-3 text-xs font-extrabold rounded-lg transition-all',
                      editForm.estatus_plantilla === true
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
                    @click="editForm.estatus_plantilla = false"
                    :class="[
                      'flex-1 py-2 px-3 text-xs font-extrabold rounded-lg transition-all',
                      editForm.estatus_plantilla === false
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
    <!-- MODAL: PUBLICAR PROGRAMACIÓN (estilo GitHub)                          -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showPublishModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="closePublish"
        >
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col relative">

            <!-- Overlay de éxito -->
            <Transition
              enter-active-class="transition-opacity duration-150 ease-out"
              enter-from-class="opacity-0"
              enter-to-class="opacity-100"
              leave-active-class="transition-opacity duration-150 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <div v-if="publishSuccess" class="absolute inset-0 z-10 bg-white/90 backdrop-blur-[2px] rounded-2xl flex flex-col items-center justify-center gap-4">
                <div class="w-16 h-16 rounded-full bg-emerald-600 flex items-center justify-center shadow-lg animate-scale-in">
                  <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                </div>
                <p class="text-base font-black text-slate-800">Programación publicada</p>
              </div>
            </Transition>

            <!-- Header verde -->
            <div class="flex items-center justify-between p-6 border-b border-emerald-100 bg-emerald-50">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 rounded-xl">
                  <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <p class="text-[10px] uppercase font-black tracking-widest text-emerald-500">Publicar programación</p>
                  <h3 class="text-lg font-black text-emerald-900 leading-tight">Confirmar publicación</h3>
                </div>
              </div>
              <button @click="closePublish" class="text-slate-400 hover:text-slate-600 bg-white/70 hover:bg-white p-2 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5">

              <!-- Info de la plantilla -->
              <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-lg shrink-0">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <p class="text-xs text-slate-500 font-semibold">Plantilla a publicar</p>
                  <p class="text-sm font-extrabold text-slate-800 truncate">{{ publishTarget?.nombre_plantilla }}</p>
                  <p class="text-xs text-slate-500 mt-0.5">
                    <span class="font-bold text-blue-600">{{ publishTarget?.total_actividades ?? 0 }}</span>
                    sesiones por semana seleccionada
                  </p>
                </div>
              </div>

              <!-- Selección de semanas -->
              <div class="space-y-3">
                <div>
                  <p class="text-sm font-bold text-slate-700">Selecciona una o ambas semanas</p>
                  <p class="text-xs text-slate-400 mt-0.5">Las sesiones se generarán para cada semana que elijas.</p>
                </div>

                <div class="space-y-2">
                  <!-- Opción semana 1 -->
                  <button
                    type="button"
                    @click="toggleSemana(opcionSemana1)"
                    class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-xl border-2 transition-all text-left focus:outline-none"
                    :class="semanasSeleccionadas.includes(opcionSemana1)
                      ? 'border-emerald-400 bg-emerald-50/70 shadow-sm'
                      : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80'"
                  >
                    <!-- Checkbox visual -->
                    <div
                      class="w-4.5 h-4.5 rounded border-2 flex items-center justify-center shrink-0 transition-all mt-px"
                      :class="semanasSeleccionadas.includes(opcionSemana1)
                        ? 'border-emerald-500 bg-emerald-500'
                        : 'border-slate-300 bg-white'"
                    >
                      <svg v-if="semanasSeleccionadas.includes(opcionSemana1)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-[11px] font-semibold tracking-wide"
                        :class="semanasSeleccionadas.includes(opcionSemana1) ? 'text-emerald-600' : 'text-slate-400'"
                      >Próxima semana</p>
                      <p class="text-sm font-bold leading-snug mt-0.5"
                        :class="semanasSeleccionadas.includes(opcionSemana1) ? 'text-emerald-900' : 'text-slate-700'"
                      >
                        {{ formatDate(opcionSemana1) }}
                        <span class="font-normal mx-1.5" :class="semanasSeleccionadas.includes(opcionSemana1) ? 'text-emerald-400' : 'text-slate-300'">—</span>
                        {{ formatDate(toLocalDateString(new Date(new Date(opcionSemana1 + 'T12:00:00').getTime() + 6 * 86400000))) }}
                      </p>
                    </div>
                    <div v-if="semanasSeleccionadas.includes(opcionSemana1)" class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0" />
                  </button>

                  <!-- Opción semana 2 -->
                  <button
                    type="button"
                    @click="toggleSemana(opcionSemana2)"
                    class="w-full flex items-center gap-3.5 px-4 py-3.5 rounded-xl border-2 transition-all text-left focus:outline-none"
                    :class="semanasSeleccionadas.includes(opcionSemana2)
                      ? 'border-emerald-400 bg-emerald-50/70 shadow-sm'
                      : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80'"
                  >
                    <div
                      class="w-4.5 h-4.5 rounded border-2 flex items-center justify-center shrink-0 transition-all mt-px"
                      :class="semanasSeleccionadas.includes(opcionSemana2)
                        ? 'border-emerald-500 bg-emerald-500'
                        : 'border-slate-300 bg-white'"
                    >
                      <svg v-if="semanasSeleccionadas.includes(opcionSemana2)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-[11px] font-semibold tracking-wide"
                        :class="semanasSeleccionadas.includes(opcionSemana2) ? 'text-emerald-600' : 'text-slate-400'"
                      >Semana siguiente</p>
                      <p class="text-sm font-bold leading-snug mt-0.5"
                        :class="semanasSeleccionadas.includes(opcionSemana2) ? 'text-emerald-900' : 'text-slate-700'"
                      >
                        {{ formatDate(opcionSemana2) }}
                        <span class="font-normal mx-1.5" :class="semanasSeleccionadas.includes(opcionSemana2) ? 'text-emerald-400' : 'text-slate-300'">—</span>
                        {{ formatDate(toLocalDateString(new Date(new Date(opcionSemana2 + 'T12:00:00').getTime() + 6 * 86400000))) }}
                      </p>
                    </div>
                    <div v-if="semanasSeleccionadas.includes(opcionSemana2)" class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0" />
                  </button>
                </div>
              </div>

              <!-- Confirmación estilo GitHub — aparece solo cuando hay semana(s) seleccionadas -->
              <Transition name="fade-down">
                <div v-if="semanasSeleccionadas.length > 0" class="space-y-2.5">
                  <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3">
                    <p class="text-xs font-bold text-emerald-800">
                      Para confirmar, escribe
                      <span class="font-black tracking-widest text-emerald-900">CONFIRMAR</span>
                      en el campo de abajo:
                    </p>
                  </div>
                  <input
                    v-model="publishConfirmInput"
                    type="text"
                    placeholder="CONFIRMAR"
                    autocomplete="off"
                    class="w-full border-2 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-800 tracking-wider placeholder:tracking-normal placeholder:font-normal focus:outline-none focus:ring-2 transition-colors"
                    :class="publishConfirmed
                      ? 'border-emerald-400 bg-emerald-50 focus:ring-emerald-500/40'
                      : 'border-slate-200 focus:ring-emerald-500/40 focus:border-emerald-500'"
                  />
                </div>
              </Transition>

              <!-- Error 409 u otro error de servidor -->
              <Transition name="fade-down">
                <div v-if="publishError" class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl p-4">
                  <IconWarning class="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
                  <p class="text-xs font-bold text-red-800 leading-relaxed">{{ publishError }}</p>
                </div>
              </Transition>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-slate-100 bg-white flex justify-end gap-3">
              <button
                @click="closePublish"
                class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="submitPublish"
                :disabled="!publishReady || store.isSaving"
                class="px-6 py-2.5 text-sm font-extrabold text-white rounded-xl shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                :class="publishReady && !store.isSaving ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-emerald-600'"
              >
                {{ store.isSaving ? 'Publicando...' : 'Confirmar publicación' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: RETIRAR PROGRAMACIÓN                                            -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showUnpublishModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="closeUnpublish"
        >
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col relative">

            <!-- Overlay de éxito -->
            <Transition
              enter-active-class="transition-opacity duration-150 ease-out"
              enter-from-class="opacity-0"
              enter-to-class="opacity-100"
              leave-active-class="transition-opacity duration-150 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <div v-if="unpublishSuccess" class="absolute inset-0 z-10 bg-white/90 backdrop-blur-[2px] rounded-2xl flex flex-col items-center justify-center gap-4">
                <div class="w-16 h-16 rounded-full bg-orange-500 flex items-center justify-center shadow-lg animate-scale-in">
                  <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                </div>
                <p class="text-base font-black text-slate-800">Programación retirada</p>
              </div>
            </Transition>

            <!-- Header naranja -->
            <div class="flex items-center justify-between p-6 border-b border-orange-100 bg-orange-50">
              <div class="flex items-center gap-3">
                <div class="p-2.5 bg-orange-100 rounded-xl">
                  <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div>
                  <p class="text-[10px] uppercase font-black tracking-widest text-orange-400">Programación activa</p>
                  <h3 class="text-lg font-black text-orange-900 leading-tight">Retirar sesiones publicadas</h3>
                </div>
              </div>
              <button @click="closeUnpublish" class="text-slate-400 hover:text-slate-600 bg-white/70 hover:bg-white p-2 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5">

              <!-- Info de la plantilla -->
              <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center gap-3">
                <div class="p-2.5 bg-orange-100 rounded-lg shrink-0">
                  <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 012 10z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="min-w-0">
                  <p class="text-xs text-slate-500 font-semibold">Plantilla seleccionada</p>
                  <p class="text-base font-extrabold text-slate-800 truncate">{{ unpublishTarget?.nombre_plantilla }}</p>
                  <p class="text-xs text-slate-400 mt-0.5">
                    Semana publicada: <span class="font-bold text-slate-600">{{ formatDate(unpublishTarget?.fecha_inicio) }} — {{ formatDate(unpublishTarget?.fecha_fin) }}</span>
                  </p>
                </div>
              </div>

              <!-- Reglas del retiro -->
              <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-3">
                <p class="text-xs font-extrabold text-slate-400 uppercase tracking-widest">¿Qué ocurre al retirar?</p>
                <div class="space-y-3">
                  <div class="flex items-start gap-3">
                    <div class="mt-0.5 w-7 h-7 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
                      <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-600 leading-snug">Las sesiones programadas se eliminan permanentemente de la agenda.</p>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="mt-0.5 w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                      <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-600 leading-snug">Los bloques de horario permanecen intactos — puedes volver a publicar cuando quieras.</p>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="mt-0.5 w-7 h-7 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                      <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-600 leading-snug">Si hay inscripciones activas o sesiones completadas, el retiro será bloqueado.</p>
                  </div>
                </div>
              </div>

              <!-- Confirmación estilo GitHub -->
              <div class="space-y-3">
                <div class="bg-orange-50 border border-orange-200 rounded-xl px-4 py-3">
                  <p class="text-sm font-semibold text-orange-800">
                    Para confirmar, escribe <span class="font-black tracking-widest text-orange-900">RETIRAR</span> en el campo de abajo:
                  </p>
                </div>
                <input
                  v-model="unpublishConfirmInput"
                  type="text"
                  placeholder="RETIRAR"
                  autocomplete="off"
                  class="w-full border-2 rounded-xl px-4 py-3 text-sm font-bold text-slate-800 tracking-wider placeholder:tracking-normal placeholder:font-normal focus:outline-none focus:ring-2 transition-colors"
                  :class="unpublishConfirmed
                    ? 'border-orange-400 bg-orange-50 focus:ring-orange-500/40'
                    : 'border-slate-200 focus:ring-orange-500/40 focus:border-orange-500'"
                />
              </div>

              <!-- Error del servidor -->
              <Transition name="fade-down">
                <div v-if="unpublishError" class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl p-4">
                  <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                  </svg>
                  <div>
                    <p class="text-sm font-extrabold text-red-800">Retiro bloqueado</p>
                    <p class="text-sm font-medium text-red-700 mt-0.5 leading-relaxed">{{ unpublishError }}</p>
                  </div>
                </div>
              </Transition>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-slate-100 bg-white flex justify-end gap-3">
              <button
                @click="closeUnpublish"
                class="px-5 py-2.5 text-sm font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="submitUnpublish"
                :disabled="!unpublishConfirmed || store.isSaving"
                class="px-6 py-2.5 text-sm font-extrabold text-white bg-orange-500 hover:bg-orange-600 rounded-xl shadow-sm transition-all disabled:opacity-40 disabled:cursor-not-allowed"
              >
                {{ store.isSaving ? 'Retirando...' : 'Retirar sesiones' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- MODAL: ERROR DE ELIMINACIÓN BLOQUEADA                                 -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showDeleteErrorModal"
          class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm"
          @click.self="closeDeleteError"
        >
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden flex flex-col">

            <!-- Header rojo -->
            <div class="flex items-center gap-3 p-6 border-b border-red-100 bg-red-50">
              <div class="p-2.5 bg-red-100 rounded-xl shrink-0">
                <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <p class="text-[10px] uppercase font-black tracking-widest text-red-400">Acción bloqueada</p>
                <h3 class="text-base font-black text-red-800 leading-tight">{{ deleteErrorTitle }}</h3>
              </div>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
              <p class="text-sm font-semibold text-slate-700 leading-relaxed">{{ deleteErrorMessage }}</p>

              <div class="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                <p class="text-xs font-semibold text-red-700 leading-relaxed">
                  Usa la acción <strong>"Retirar"</strong> para eliminar las sesiones activas vinculadas y después intenta eliminar nuevamente.
                </p>
              </div>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-slate-100 bg-white flex justify-end">
              <button
                @click="closeDeleteError"
                class="px-6 py-2.5 text-sm font-extrabold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm transition-all active:scale-95"
              >
                Aceptar
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
