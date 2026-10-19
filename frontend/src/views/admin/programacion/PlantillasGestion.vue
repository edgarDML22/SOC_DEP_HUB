<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePlantillasStore } from '@/stores/programacion/plantillasStore'
import { useAlerts } from '@/composables/useAlerts'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import { IconEdit, IconTrash, IconWarning, IconTrophy, IconCalendar, IconLock, IconShield } from '@/components/icons'
import api from '@/services/api'
import AppModal from '@/components/ui/modal/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppField from '@/components/ui/form/AppField.vue'
import AppInput from '@/components/ui/form/AppInput.vue'
import StatusOptionGroup from '@/components/ui/status/StatusOptionGroup.vue'
import AppDatePicker from '@/components/ui/form/AppDatePicker.vue'

const store = usePlantillasStore()
const { toastSuccess, toastError } = useAlerts()

// ─── Modal: Publicar ─────────────────────────────────────────────────────────
const showPublishModal   = ref(false)
const publishTarget      = ref(null)
const publishError       = ref('')
const publishConfirmInput = ref('')

// Formatea una fecha local como 'YYYY-MM-DD' sin conversión UTC
function toLocalDateString(d) {
  const y  = d.getFullYear()
  const m  = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${dd}`
}

// Devuelve el lunes de la semana actual (hoy o el lunes anterior más cercano)
function getBaseMonday() {
  const d   = new Date()
  const day = d.getDay() // 0 dom ... 6 sab; 1 lunes
  const diffToMonday = day === 0 ? 6 : day - 1
  d.setDate(d.getDate() - diffToMonday)
  return d
}

// Devuelve el lunes con desplazamiento de semanas respecto a la base
function getMonday(offsetWeeks = 0) {
  const d = getBaseMonday()
  d.setDate(d.getDate() + offsetWeeks * 7)
  return toLocalDateString(d)
}

// Opciones de semanas disponibles:
// Si hoy es domingo (día 0): la semana entrante que empieza mañana es getMonday(1).
// Si hoy es lunes a sábado: getMonday(0) permite publicación inmediata/de emergencia para la semana en curso.
const hoyEsDomingo = computed(() => new Date().getDay() === 0)

const opcionSemana1 = computed(() => hoyEsDomingo.value ? getMonday(1) : getMonday(0))
const opcionSemana2 = computed(() => hoyEsDomingo.value ? getMonday(2) : getMonday(1))
const opcionSemana3 = computed(() => hoyEsDomingo.value ? getMonday(3) : getMonday(2))

const labelSemana1 = computed(() => hoyEsDomingo.value ? 'Semana entrante' : 'Semana en curso')
const labelSemana2 = computed(() => 'Semana siguiente')
const labelSemana3 = computed(() => 'En dos semanas')

// Semanas ocupadas por otras publicaciones
const semanasOcupadas = ref([])
const cargandoSemanasOcupadas = ref(false)

function getOcupacionSemana(fechaLunes) {
  return semanasOcupadas.value.find(s => s.semana_inicio === fechaLunes) ?? null
}

function esSemanaBloqueada(fechaLunes) {
  const ocupacion = getOcupacionSemana(fechaLunes)
  if (!ocupacion) return false
  // Si está ocupada por otra plantilla, bloquea. Si está ocupada por esta misma plantilla ya publicada, también bloquea para no solapar.
  return true
}

const opcionesSemanas = computed(() => [
  {
    fecha: opcionSemana1.value,
    label: labelSemana1.value,
  },
  {
    fecha: opcionSemana2.value,
    label: labelSemana2.value,
  },
  {
    fecha: opcionSemana3.value,
    label: labelSemana3.value,
  },
])

const todasLasSemanasOcupadas = computed(() =>
  opcionesSemanas.value.length > 0 && opcionesSemanas.value.every(op => esSemanaBloqueada(op.fecha))
)

// Selección múltiple de semanas
const semanasSeleccionadas = ref([])          // array de 'YYYY-MM-DD'

// Opciones avanzadas: días a omitir (feriados / festivos) y filtro de pasadas
const DIA_META = {
  LUNES:     { key: 'LUNES',     short: 'Lun', full: 'Lunes',     color: 'sky' },
  MARTES:    { key: 'MARTES',    short: 'Mar', full: 'Martes',    color: 'blue' },
  MIERCOLES: { key: 'MIERCOLES', short: 'Mié', full: 'Miércoles', color: 'indigo' },
  JUEVES:    { key: 'JUEVES',    short: 'Jue', full: 'Jueves',    color: 'violet' },
  VIERNES:   { key: 'VIERNES',   short: 'Vie', full: 'Viernes',   color: 'purple' },
  SABADO:    { key: 'SABADO',    short: 'Sáb', full: 'Sábado',    color: 'amber' },
  DOMINGO:   { key: 'DOMINGO',   short: 'Dom', full: 'Domingo',   color: 'rose' },
}
const diasSemanaOpciones = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO']
const omitirDias = ref([])
const omitirSesionesPasadas = ref(true)

function toggleDiaOmitido(dia) {
  const idx = omitirDias.value.indexOf(dia)
  if (idx === -1) omitirDias.value.push(dia)
  else omitirDias.value.splice(idx, 1)
}

function toggleSemana(fecha) {
  if (esSemanaBloqueada(fecha)) return
  const idx = semanasSeleccionadas.value.indexOf(fecha)
  if (idx === -1) semanasSeleccionadas.value.push(fecha)
  else            semanasSeleccionadas.value.splice(idx, 1)
}

const actividadesTarget = ref([])
const cargandoDetallePlantilla = ref(false)

// Cuenta las actividades base por día de la semana
const actividadesPorDia = computed(() => {
  const map = {}
  diasSemanaOpciones.forEach(d => { map[d] = 0 })
  for (const act of actividadesTarget.value) {
    if (map[act.dia_semana] !== undefined) {
      map[act.dia_semana]++
    }
  }
  return map
})

// Actividades efectivas por semana restando los días excluidos
const actividadesEfectivasSemana = computed(() => {
  if (!publishTarget.value) return 0
  if (actividadesTarget.value.length === 0) {
    return publishTarget.value.total_actividades ?? 0
  }
  let count = 0
  for (const act of actividadesTarget.value) {
    if (!omitirDias.value.includes(act.dia_semana)) {
      count++
    }
  }
  return count
})

// No permitir publicar si todos los 7 días de la semana están excluidos
const todosLosDiasExcluidos = computed(() =>
  diasSemanaOpciones.every(d => omitirDias.value.includes(d))
)

async function openPublish(p) {
  publishTarget.value       = p
  actividadesTarget.value   = []
  semanasSeleccionadas.value = []
  omitirDias.value          = []
  omitirSesionesPasadas.value = true
  publishError.value        = ''
  publishConfirmInput.value = ''
  showPublishModal.value    = true

  // Consultar en paralelo las semanas ya ocupadas y el detalle de actividades de la plantilla
  try {
    cargandoSemanasOcupadas.value = true
    cargandoDetallePlantilla.value = true

    const [resOcupadas, resDetalle] = await Promise.all([
      api.get('/programacion/plantillas/semanas-ocupadas'),
      api.get(`/programacion/plantillas/${p.id_plantilla}`)
    ])

    if (resOcupadas.data?.data) {
      semanasOcupadas.value = resOcupadas.data.data
    }
    if (resDetalle.data?.data?.actividades) {
      actividadesTarget.value = resDetalle.data.data.actividades
    }

    // Preseleccionar la primera semana disponible que NO esté bloqueada
    const opcionesDisponibles = [opcionSemana1.value, opcionSemana2.value, opcionSemana3.value]
    const primeraLibre = opcionesDisponibles.find(op => !esSemanaBloqueada(op))
    if (primeraLibre) {
      semanasSeleccionadas.value = [primeraLibre]
    }
  } catch (err) {
    console.error('Error cargando datos para publicar plantilla:', err)
  } finally {
    cargandoSemanasOcupadas.value = false
    cargandoDetallePlantilla.value = false
  }
}

function closePublish() {
  showPublishModal.value    = false
  publishTarget.value       = null
  actividadesTarget.value   = []
  publishError.value        = ''
  publishConfirmInput.value = ''
  semanasSeleccionadas.value = []
  omitirDias.value          = []
}

const publishConfirmed = computed(() => publishConfirmInput.value === 'CONFIRMAR')

// Al menos una semana seleccionada + al menos una actividad efectiva (no todas excluidas) + texto de confirmación correcto
const publishReady = computed(() =>
  semanasSeleccionadas.value.length > 0 &&
  actividadesEfectivasSemana.value > 0 &&
  publishConfirmed.value
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
      const opciones = {
        omitir_dias: omitirDias.value,
        omitir_sesiones_pasadas: omitirSesionesPasadas.value,
      }
      const result = await store.publicarPlantilla(publishTarget.value.id_plantilla, semana, esUltima, opciones)
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
    closePublish()
    toastSuccess(`${totalCreadas} sesiones publicadas correctamente.`)
  } else if (totalCreadas > 0) {
    publishError.value = errores.join(' | ')
    closePublish()
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

function openCreate() {
  createForm.value   = { nombre_plantilla: '', fecha_inicio: '', fecha_fin: '' }
  createErrors.value = {}
  showCreateModal.value = true
}

function closeCreate() {
  showCreateModal.value = false
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
    closeCreate()
    toastSuccess('Plantilla creada correctamente.')
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

function openEdit(p) {
  editTarget.value = p
  editForm.value   = {
    nombre_plantilla:  p.nombre_plantilla,
    fecha_inicio:      p.fecha_inicio ?? '',   // solo lectura en UI
    fecha_fin:         p.fecha_fin    ?? '',   // solo lectura en UI
    estatus_plantilla: p.estatus_plantilla,
  }
  editErrors.value  = {}
  showEditModal.value = true
}

function closeEdit() {
  showEditModal.value = false
  editTarget.value    = null
}

const PLANTILLA_STATUS_OPTIONS = [
  { value: 'ACTIVO', label: 'Activo', description: 'Es la programación vigente del club. Solo puede haber una.', icon: IconShield, tone: 'emerald' },
  { value: 'INACTIVO', label: 'Inactivo', description: 'Queda guardada pero no está en uso.', icon: IconLock, tone: 'blue' },
]
const editStatus = computed({
  get: () => (editForm.value.estatus_plantilla ? 'ACTIVO' : 'INACTIVO'),
  set: (v) => { editForm.value.estatus_plantilla = v === 'ACTIVO' },
})

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
    closeEdit()
    toastSuccess('Plantilla actualizada correctamente.')
  } catch (e) {
    toastError(e?.response?.data?.message ?? 'Error al actualizar la plantilla.')
  }
}

// ─── Modal: Despublicación ────────────────────────────────────────────────────
const showUnpublishModal    = ref(false)
const unpublishTarget       = ref(null)
const unpublishConfirmInput = ref('')
const unpublishError        = ref('')

function openUnpublish(p) {
  unpublishTarget.value       = p
  unpublishConfirmInput.value = ''
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
    const eliminadas = result?.data?.sesiones_eliminadas ?? 0
    closeUnpublish()
    toastSuccess(`Programación retirada. ${eliminadas} sesión(es) eliminada(s).`)
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
    const canPublish = (p.total_actividades ?? 0) > 0
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
  }

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
    disabled: (p.total_actividades ?? 0) === 0
  })

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
        <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
          <tr>
            <th class="px-6 py-4 text-left font-extrabold rounded-tl-2xl">Nombre de Plantilla</th>
            <th class="px-6 py-4 text-left font-extrabold">Fecha Inicio</th>
            <th class="px-6 py-4 text-left font-extrabold">Fecha Fin</th>
            <th class="px-6 py-4 text-left font-extrabold">Actividades</th>
            <th class="px-6 py-4 text-left font-extrabold">Estatus</th>
            <th class="px-6 py-4 text-right font-extrabold rounded-tr-2xl">Acciones</th>
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

    <!-- MODAL: CREACIÓN -->
    <AppModal
      v-model="showCreateModal"
      title="Nueva plantilla"
      subtitle="Programación de actividades"
      :icon="IconCalendar"
      :persistent="store.isSaving"
      @close="closeCreate"
    >
      <form id="create-plantilla-form" class="flex flex-col gap-4" novalidate @submit.prevent="submitCreate">
        <AppField label="Nombre de plantilla" for="plantilla-nombre" required :error="createErrors.nombre_plantilla">
          <AppInput id="plantilla-nombre" v-model="createForm.nombre_plantilla" placeholder="Ej. Programación Base 2026-II"
            :invalid="!!createErrors.nombre_plantilla" />
        </AppField>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <AppField label="Fecha de inicio" optional :error="createErrors.fecha_inicio">
            <AppDatePicker v-model="createForm.fecha_inicio" :invalid="!!createErrors.fecha_inicio" />
          </AppField>
          <AppField label="Fecha de fin" optional :error="createErrors.fecha_fin">
            <AppDatePicker v-model="createForm.fecha_fin" :min-date="createForm.fecha_inicio || null" :invalid="!!createErrors.fecha_fin" />
          </AppField>
        </div>

        <div class="flex items-start gap-2.5 rounded-2xl bg-primary-50 p-3.5 text-sm font-medium text-primary-800 ring-1 ring-primary-100 dark:bg-primary-500/10 dark:text-primary-200 dark:ring-primary-500/30">
          <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
          <p class="m-0">
            La plantilla se creará en estado <strong>INACTIVO</strong>. Podrás activarla desde el panel de edición una vez configurados sus bloques.
          </p>
        </div>
      </form>

      <template #footer>
        <AppButton variant="secondary" :disabled="store.isSaving" @click="closeCreate">Cancelar</AppButton>
        <AppButton type="submit" form="create-plantilla-form" :loading="store.isSaving" :disabled="createNameEmpty">
          Crear plantilla
        </AppButton>
      </template>
    </AppModal>

    <!-- MODAL: EDICIÓN -->
    <AppModal
      :model-value="showEditModal"
      title="Editar plantilla"
      :subtitle="editTarget?.nombre_plantilla"
      :icon="IconEdit"
      :persistent="store.isSaving"
      @update:model-value="(v) => !v && closeEdit()"
    >
      <form id="edit-plantilla-form" class="flex flex-col gap-4" novalidate @submit.prevent="submitEdit">
        <AppField label="Nombre de plantilla" for="plantilla-edit-nombre" required :error="editErrors.nombre_plantilla">
          <AppInput id="plantilla-edit-nombre" v-model="editForm.nombre_plantilla" placeholder="Ej. Programación Base 2026"
            :invalid="!!editErrors.nombre_plantilla" />
        </AppField>

        <!-- Fechas de solo lectura: se asignan automáticamente al publicar -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <AppField label="Fecha de inicio" hint="Se asigna al publicar.">
            <AppInput :model-value="formatDate(editForm.fecha_inicio)" :icon="IconCalendar" readonly />
          </AppField>
          <AppField label="Fecha de fin" hint="Se asigna al publicar.">
            <AppInput :model-value="formatDate(editForm.fecha_fin)" :icon="IconCalendar" readonly />
          </AppField>
        </div>

        <div class="flex flex-col gap-3">
          <StatusOptionGroup v-model="editStatus" :options="PLANTILLA_STATUS_OPTIONS"
            :current="editTarget?.estatus_plantilla ? 'ACTIVO' : 'INACTIVO'" label="Estatus de la plantilla" />

          <div v-if="blockActivation" role="alert"
            class="flex items-start gap-2.5 rounded-2xl bg-amber-50 p-3.5 text-sm font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30">
            <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
            <p class="m-0">
              Solo puede haber una programación activa. Desactiva
              <strong>"{{ otherActivePlantilla?.nombre_plantilla }}"</strong> antes de activar esta plantilla.
            </p>
          </div>
        </div>
      </form>

      <template #footer>
        <AppButton variant="secondary" :disabled="store.isSaving" @click="closeEdit">Cancelar</AppButton>
        <AppButton type="submit" form="edit-plantilla-form" :loading="store.isSaving" :disabled="blockActivation">
          Guardar cambios
        </AppButton>
      </template>
    </AppModal>

    <!-- MODAL: PUBLICAR PROGRAMACIÓN -->
    <AppModal
      :model-value="showPublishModal"
      title="Publicar programación"
      :subtitle="publishTarget?.nombre_plantilla"
      :icon="IconCalendar"
      tone="emerald"
      size="lg"
      :persistent="store.isSaving"
      @update:model-value="(v) => !v && closePublish()"
    >
      <form id="publish-form" class="flex flex-col gap-5" novalidate @submit.prevent="submitPublish">
        <p class="m-0 text-sm font-medium text-surface-600">
          Genera las sesiones de actividades según los horarios de la plantilla.
          <span class="font-bold text-surface-900 tabular-nums">{{ actividadesEfectivasSemana }}</span> actividades base por semana<span v-if="omitirDias.length > 0 && publishTarget?.total_actividades"> (de {{ publishTarget?.total_actividades }})</span>.
        </p>

        <!-- Semanas -->
        <section class="flex flex-col gap-2.5" aria-label="Semanas a programar">
          <div class="flex items-center justify-between gap-2">
            <h3 class="m-0 text-[13px] font-semibold text-surface-800">Semanas a programar</h3>
            <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold tabular-nums text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
              {{ semanasSeleccionadas.length }} elegida(s)
            </span>
          </div>

          <template v-for="opcion in opcionesSemanas" :key="opcion.fecha">
            <!-- Ocupada por otra plantilla -->
            <div v-if="esSemanaBloqueada(opcion.fecha)"
              class="flex cursor-not-allowed items-center gap-3 rounded-xl border-2 border-surface-200 bg-surface-100 px-3.5 py-3 opacity-80">
              <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border-2 border-surface-300 text-surface-400" aria-hidden="true">
                <IconLock class="h-3 w-3" />
              </span>
              <span class="min-w-0">
                <span class="block text-sm font-bold text-surface-600">{{ opcion.label }}</span>
                <span class="block text-xs font-medium tabular-nums text-surface-500">
                  {{ formatDate(opcion.fecha) }} → {{ formatDate(toLocalDateString(new Date(new Date(opcion.fecha + 'T12:00:00').getTime() + 6 * 86400000))) }}
                </span>
                <span class="mt-0.5 block text-xs font-semibold text-surface-500">
                  Ocupada por {{ getOcupacionSemana(opcion.fecha)?.nombre_plantilla || 'otra programación' }}
                </span>
              </span>
            </div>

            <!-- Disponible -->
            <button v-else type="button" role="checkbox" :aria-checked="semanasSeleccionadas.includes(opcion.fecha)"
              class="flex w-full cursor-pointer items-center gap-3 rounded-xl border-2 bg-white px-3.5 py-3 text-left transition-all duration-150 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-100"
              :class="semanasSeleccionadas.includes(opcion.fecha)
                ? 'border-emerald-500 bg-emerald-50/70 ring-4 ring-emerald-100 dark:bg-emerald-500/10 dark:ring-emerald-500/15'
                : 'border-surface-200 hover:border-surface-300 hover:bg-surface-50'"
              @click="toggleSemana(opcion.fecha)">
              <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border-2 transition-colors"
                :class="semanasSeleccionadas.includes(opcion.fecha) ? 'border-transparent bg-emerald-600 text-white' : 'border-surface-300'"
                aria-hidden="true">
                <svg v-if="semanasSeleccionadas.includes(opcion.fecha)" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
              </span>
              <span class="min-w-0">
                <span class="block text-sm font-bold text-surface-900">{{ opcion.label }}</span>
                <span class="block text-xs font-medium tabular-nums text-surface-600">
                  {{ formatDate(opcion.fecha) }} → {{ formatDate(toLocalDateString(new Date(new Date(opcion.fecha + 'T12:00:00').getTime() + 6 * 86400000))) }}
                </span>
              </span>
            </button>
          </template>
        </section>

        <!-- Días especiales -->
        <section class="flex flex-col gap-2.5" aria-label="Días especiales o feriados">
          <div class="flex items-center justify-between gap-2">
            <h3 class="m-0 text-[13px] font-semibold text-surface-800">
              Días especiales o feriados <span class="font-medium text-surface-500">(opcional)</span>
            </h3>
            <span v-if="omitirDias.length > 0" class="rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
              {{ omitirDias.length }} excluido(s)
            </span>
          </div>
          <p class="m-0 text-xs font-medium text-surface-500">Toca un día para omitirlo de la programación.</p>
          <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
            <button v-for="dia in diasSemanaOpciones" :key="dia" type="button"
              :aria-pressed="omitirDias.includes(dia)"
              class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 px-1 py-2 text-center transition-all focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary-100"
              :class="omitirDias.includes(dia)
                ? 'border-rose-400 bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
                : 'border-surface-200 bg-white text-surface-800 hover:border-surface-300 hover:bg-surface-50'"
              @click="toggleDiaOmitido(dia)">
              <span class="text-xs font-bold">{{ DIA_META[dia].short }}</span>
              <span class="mt-0.5 text-[10px] font-semibold" :class="omitirDias.includes(dia) ? '' : 'text-surface-500'">
                {{ omitirDias.includes(dia) ? 'Excluido' : (actividadesPorDia[dia] ? `${actividadesPorDia[dia]} act.` : 'Activo') }}
              </span>
            </button>
          </div>
        </section>

        <!-- Filtrar horas transcurridas -->
        <label class="flex cursor-pointer select-none items-start gap-3 rounded-2xl bg-surface-50 p-3.5 ring-1 ring-surface-200">
          <input v-model="omitirSesionesPasadas" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded accent-emerald-600" />
          <span class="text-sm">
            <span class="block font-bold text-surface-900">Filtrar horas ya transcurridas</span>
            <span class="mt-0.5 block text-xs font-medium text-surface-600">
              Si publicas durante la semana en curso, se omiten las clases que ya concluyeron hoy.
            </span>
          </span>
        </label>

        <!-- Avisos -->
        <div v-if="todasLasSemanasOcupadas" role="alert"
          class="flex items-start gap-2.5 rounded-2xl bg-surface-100 p-3.5 text-sm font-semibold text-surface-700 ring-1 ring-surface-200">
          <IconLock class="mt-0.5 h-4 w-4 shrink-0" />
          Todas las semanas próximas ya tienen una programación publicada. Retira alguna para poder publicar esta.
        </div>
        <div v-if="actividadesEfectivasSemana === 0" role="alert"
          class="flex items-start gap-2.5 rounded-2xl bg-amber-50 p-3.5 text-sm font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30">
          <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
          No puedes publicar: se excluyeron todas las actividades base. Desmarca al menos un día.
        </div>
        <div v-else-if="omitirDias.length > 0"
          class="flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-800 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/30">
          <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
          <span>No se generarán sesiones para: <strong>{{ omitirDias.map(d => DIA_META[d].full).join(', ') }}</strong>.</span>
        </div>

        <!-- Confirmación -->
        <AppField for="publish-confirm" :error="publishError">
          <p class="m-0 mb-1.5 text-[13px] font-semibold text-surface-800">
            Para confirmar, escribe <span class="font-black tracking-widest text-emerald-700 dark:text-emerald-300">CONFIRMAR</span>
          </p>
          <AppInput id="publish-confirm" v-model="publishConfirmInput" placeholder="CONFIRMAR" autocomplete="off" spellcheck="false"
            :invalid="!!publishError" />
        </AppField>
      </form>

      <template #footer>
        <AppButton variant="secondary" :disabled="store.isSaving" @click="closePublish">Cancelar</AppButton>
        <AppButton type="submit" form="publish-form" tone="emerald" :loading="store.isSaving" :disabled="!publishReady">
          Confirmar publicación
        </AppButton>
      </template>
    </AppModal>

    <!-- MODAL: RETIRAR PROGRAMACIÓN -->
    <AppModal
      :model-value="showUnpublishModal"
      title="Retirar sesiones publicadas"
      :subtitle="unpublishTarget?.nombre_plantilla"
      :icon="IconWarning"
      tone="amber"
      :persistent="store.isSaving"
      @update:model-value="(v) => !v && closeUnpublish()"
    >
      <form id="unpublish-form" class="flex flex-col gap-4" novalidate @submit.prevent="submitUnpublish">
        <p class="m-0 rounded-2xl bg-surface-50 px-4 py-3 text-sm font-medium text-surface-600 ring-1 ring-surface-200">
          Semana publicada:
          <strong class="text-surface-900">{{ formatDate(unpublishTarget?.fecha_inicio) }} — {{ formatDate(unpublishTarget?.fecha_fin) }}</strong>
        </p>

        <div class="flex flex-col gap-2.5 rounded-2xl bg-surface-50 p-4 ring-1 ring-surface-200">
          <p class="m-0 text-xs font-bold uppercase tracking-widest text-surface-500">¿Qué ocurre al retirar?</p>
          <ul class="m-0 flex list-none flex-col gap-2.5 p-0 text-sm font-medium text-surface-700">
            <li class="flex items-start gap-2.5">
              <IconTrash class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
              Las sesiones programadas se eliminan permanentemente de la agenda.
            </li>
            <li class="flex items-start gap-2.5">
              <IconShield class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
              Los bloques de horario permanecen intactos: puedes volver a publicar cuando quieras.
            </li>
            <li class="flex items-start gap-2.5">
              <IconWarning class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
              Si hay inscripciones activas o sesiones completadas, el retiro será bloqueado.
            </li>
          </ul>
        </div>

        <AppField for="unpublish-confirm" :error="unpublishError">
          <template #default>
            <p class="m-0 mb-1.5 text-[13px] font-semibold text-surface-800">
              Para confirmar, escribe <span class="font-black tracking-widest text-amber-700 dark:text-amber-300">RETIRAR</span>
            </p>
            <AppInput id="unpublish-confirm" v-model="unpublishConfirmInput" placeholder="RETIRAR" autocomplete="off"
              :invalid="!!unpublishError" />
          </template>
        </AppField>
      </form>

      <template #footer>
        <AppButton variant="secondary" :disabled="store.isSaving" @click="closeUnpublish">Cancelar</AppButton>
        <AppButton type="submit" form="unpublish-form" tone="amber" :loading="store.isSaving" :disabled="!unpublishConfirmed">
          Retirar sesiones
        </AppButton>
      </template>
    </AppModal>

    <!-- MODAL: ELIMINACIÓN BLOQUEADA -->
    <AppModal
      :model-value="showDeleteErrorModal"
      :title="deleteErrorTitle"
      subtitle="Acción bloqueada"
      :icon="IconWarning"
      tone="rose"
      size="sm"
      @update:model-value="(v) => !v && closeDeleteError()"
    >
      <div class="flex flex-col gap-3">
        <p class="m-0 text-[15px] leading-relaxed text-surface-700">{{ deleteErrorMessage }}</p>
        <p class="m-0 flex items-start gap-2 rounded-2xl bg-amber-50 p-3.5 text-sm font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30">
          <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
          <span>Usa la acción <strong>"Retirar"</strong> para eliminar las sesiones activas vinculadas y después intenta eliminar nuevamente.</span>
        </p>
      </div>
      <template #footer>
        <AppButton variant="secondary" @click="closeDeleteError">Entendido</AppButton>
      </template>
    </AppModal>

    <!-- MODAL: ELIMINAR PLANTILLA -->
    <AppModal
      :model-value="showDeleteModal"
      title="Eliminar plantilla"
      :subtitle="deleteTarget?.nombre_plantilla"
      :icon="IconTrash"
      tone="rose"
      size="sm"
      :persistent="store.isDeleting"
      @update:model-value="(v) => !v && closeDelete()"
    >
      <!-- Plantilla activa: no se puede eliminar -->
      <div v-if="isActiveTarget" role="alert"
        class="flex items-start gap-2.5 rounded-2xl bg-amber-50 p-3.5 text-sm font-semibold text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30">
        <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
        <p class="m-0">
          La plantilla está en producción. No puedes eliminar una programación <strong>ACTIVA</strong>:
          desactívala desde el panel de edición y luego podrás eliminarla.
        </p>
      </div>

      <form v-else id="delete-plantilla-form" class="flex flex-col gap-4" novalidate @submit.prevent="submitDelete">
        <p class="m-0 text-[15px] leading-relaxed text-surface-700">
          Vas a eliminar <strong class="text-surface-900">"{{ deleteTarget?.nombre_plantilla }}"</strong>
          y todas sus actividades asociadas. Esta acción no se puede deshacer.
        </p>
        <AppField for="delete-confirm">
          <p class="m-0 mb-1.5 text-[13px] font-semibold text-surface-800">
            Para confirmar, escribe <span class="font-black tracking-widest text-rose-600 dark:text-rose-400">ELIMINAR</span>
          </p>
          <AppInput id="delete-confirm" v-model="deleteConfirmInput" placeholder="ELIMINAR" autocomplete="off" />
        </AppField>
      </form>

      <template #footer>
        <AppButton variant="secondary" :disabled="store.isDeleting" @click="closeDelete">
          {{ isActiveTarget ? 'Cerrar' : 'Cancelar' }}
        </AppButton>
        <AppButton v-if="!isActiveTarget" type="submit" form="delete-plantilla-form" tone="rose"
          :loading="store.isDeleting" :disabled="!deleteConfirmed">
          Eliminar plantilla
        </AppButton>
      </template>
    </AppModal>

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
