<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAdminFamilyStore } from '@/stores/admin/adminFamilyStore'
import { useSocioStore } from '@/stores/admin/socioStore'
import { useAlerts } from '@/composables/useAlerts'

import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import AppModal from '@/components/ui/modal/AppModal.vue'
import AppConfirmModal from '@/components/ui/modal/AppConfirmModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppField from '@/components/ui/form/AppField.vue'
import AppInput from '@/components/ui/form/AppInput.vue'
import AppSelect from '@/components/ui/form/AppSelect.vue'
import AppDatePicker from '@/components/ui/form/AppDatePicker.vue'
import QrCodeDisplay from '@/components/ui/qr/QrCodeDisplay.vue'
import QrCodeModal from '@/components/ui/qr/QrCodeModal.vue'
import { IconUser, IconMail, IconQr, IconTrash } from '@/components/icons'
import IconUserPlus from '@/components/icons/IconUserPlus.vue'

const route = useRoute()
const router = useRouter()
const familyStore = useAdminFamilyStore()
const socioStore = useSocioStore()
const { toastInfo, toastSuccess, toastError, extractApiErrorMessage } = useAlerts()

const socioId = route.params.id

const { miembros, loading, totalMiembros } = storeToRefs(familyStore)
const { currentSocio } = storeToRefs(socioStore)


onMounted(() => {

  const cachedSocio = socioStore.getSocioById(socioId)
  if (cachedSocio) {
    currentSocio.value = cachedSocio
  }


  if (!currentSocio.value || currentSocio.value.id_socio !== Number(socioId)) {
    socioStore.fetchSocioDetails(socioId).then(details => {
      if (details) {
        currentSocio.value = details
      }
    })
  }

  familyStore.fetchMiembros(socioId)
})

const goBack = () => {
  router.push('/admin/socios')
}


const showModal = ref(false)
const step = ref(1)
const qrGeneratedData = ref(null)

const showQrModal = ref(false)
const qrModalData = ref(null)

const initialForm = {
  id_miembro: null,
  nombre: '',
  parentesco: '',
  fecha_nacimiento: '',
  genero: '',
  correo: ''
}
const formData = ref({ ...initialForm })
const formErrors = ref({})

const PARENTESCO_OPTIONS = [
  { label: 'Cónyuge', value: 'CONYUGE' },
  { label: 'Hijo/a', value: 'HIJO/A' },
  { label: 'Otro', value: 'OTRO' }
]
const GENERO_OPTIONS = [
  { label: 'Masculino', value: 'M' },
  { label: 'Femenino', value: 'F' },
  { label: 'Otro', value: 'O' }
]
// Fecha local (toISOString daría la fecha en UTC: "mañana" por la noche en México)
const hoyIso = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Mexico_City' }).format(new Date())

const validateForm = () => {
  const f = formData.value
  const errors = {}
  if (!f.nombre?.trim()) errors.nombre = 'Escribe el nombre completo.'
  if (!f.parentesco) errors.parentesco = 'Selecciona el parentesco.'
  if (!f.genero) errors.genero = 'Selecciona el género.'
  if (!f.fecha_nacimiento) errors.fecha_nacimiento = 'Selecciona la fecha de nacimiento.'
  if (f.correo && !/^\S+@\S+\.\S+$/.test(f.correo)) errors.correo = 'Ingresa un correo válido.'
  formErrors.value = errors
  return Object.keys(errors).length === 0
}

// Al corregir un campo marcado, su error desaparece (no se marcan campos nuevos mientras escribe)
watch(formData, () => {
  const prev = formErrors.value
  if (!Object.keys(prev).length) return
  validateForm()
  formErrors.value = Object.fromEntries(Object.entries(formErrors.value).filter(([key]) => key in prev))
}, { deep: true })


const formEdadCalculada = computed(() => {
  if (!formData.value.fecha_nacimiento) return '-'
  const birthDate = new Date(formData.value.fecha_nacimiento)
  const today = new Date()
  let age = today.getFullYear() - birthDate.getFullYear()
  const m = today.getMonth() - birthDate.getMonth()
  if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
    age--
  }
  return age
})

const canAddMore = computed(() => totalMiembros.value < 6)


const formatParentesco = (parentesco, genero) => {
  if (!parentesco) return '-'
  const normalized = parentesco.toUpperCase()

  if (normalized === 'HIJO/A' || normalized === 'HIJO' || normalized === 'HIJA') {
    if (genero === 'M') return 'Hijo'
    if (genero === 'F') return 'Hija'
    return 'Hijo/a'
  }

  if (normalized === 'CONYUGE' || normalized === 'CÓNYUGE') {
    return 'Cónyuge'
  }

  if (normalized === 'OTRO') {
    return 'Otro'
  }

  return parentesco
}

const getIniciales = (nombre) => {
  if (!nombre) return '?'
  const partes = nombre.trim().split(/\s+/)
  return (partes[0][0] + (partes[1]?.[0] || '')).toUpperCase()
}


const openCreateModal = () => {
  if (!canAddMore.value) {
    toastInfo('Límite alcanzado', 'Un socio puede tener un máximo de 6 familiares.', 'warning')
    return
  }
  formData.value = { ...initialForm }
  formErrors.value = {}
  step.value = 1
  showModal.value = true
}

const closeFormModal = () => {
  showModal.value = false
  setTimeout(() => {
    formData.value = { ...initialForm }
    step.value = 1
  }, 300)
}

const submitForm = async () => {
  if (!validateForm()) return
  try {
    const payload = {
      nombre_completo: formData.value.nombre,
      parentesco: formData.value.parentesco,
      fecha_nacimiento: formData.value.fecha_nacimiento,
      genero: formData.value.genero,
      correo: formData.value.correo || null,
      edad: formEdadCalculada.value
    }

    const nuevo = await familyStore.crearMiembro(socioId, payload)
    toastSuccess('Familiar registrado correctamente')

    qrGeneratedData.value = {
      nombre: nuevo.nombre_completo,
      parentesco: formatParentesco(nuevo.parentesco, nuevo.genero),
      qr: nuevo.codigoQrActivo?.codigo_qr
    }
    step.value = 2
  } catch (error) {
    toastError('No se pudo registrar al familiar')
  }
}

// ── ELIMINAR FAMILIAR ──
// Nota: el endpoint DELETE /admin/socios/:id/familiares/:miembro tiene un bug pendiente en el back.
// Si falla, el modal se queda abierto y muestra el motivo que devuelve el servidor.
const showDeleteModal = ref(false)
const miembroAEliminar = ref(null)
const deleteError = ref('')

const deleteMiembro = (miembro) => {
  miembroAEliminar.value = miembro
  deleteError.value = ''
  showDeleteModal.value = true
}

const confirmDeleteMiembro = async () => {
  if (!miembroAEliminar.value) return
  deleteError.value = ''
  try {
    await familyStore.eliminarMiembro(socioId, miembroAEliminar.value.id_miembro)
    showDeleteModal.value = false
    toastSuccess(`${miembroAEliminar.value.nombre_completo} fue eliminado de la familia`)
  } catch (error) {
    deleteError.value = extractApiErrorMessage(error)
    toastError('No se pudo eliminar al familiar')
  }
}

const openQrModal = (miembro) => {
  qrModalData.value = {
    nombre: miembro.nombre_completo,
    parentesco: formatParentesco(miembro.parentesco, miembro.genero),
    qr: miembro.codigoQrActivo?.codigo_qr || 'SIN_QR'
  }
  showQrModal.value = true
}


const buildMenuItems = (miembro) => [
  {
    label: 'Mostrar Código QR',
    icon: `<svg fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21,2H15a1,1,0,0,0-1,1V9a1,1,0,0,0,1,1h1v2h2V10h2v2h2V3A1,1,0,0,0,21,2ZM18,8H16V4h4V8ZM3,10H9a1,1,0,0,0,1-1V3A1,1,0,0,0,9,2H3A1,1,0,0,0,2,3V9A1,1,0,0,0,3,10ZM4,4H8V8H4ZM5,16v2H3V16ZM3,20H5v2H3Zm4-2v2H5V18Zm0-2H5V14H7V12H9v4ZM5,12v2H3V12Zm9,3v1H13V14H11v4h3v3a1,1,0,0,0,1,1h6a1,1,0,0,0,1-1V15a1,1,0,0,0-1-1H16V12H14Zm6,1v4H16V16ZM9,18h2v2h1v2H7V20H9ZM13,6H11V4h2ZM11,8h2v4H11ZM5,5H7V7H5ZM17,5h2V7H17Zm2,14H17V17h2Z"></path></svg>`,
    action: () => openQrModal(miembro),
  },
  {
    label: 'Eliminar',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/></svg>`,
    destructive: true,
    action: () => deleteMiembro(miembro),
  },
]
</script>

<template>
  <main class="min-h-screen bg-surface-50 px-4 py-5 pb-16 font-sans sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">

      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
          <button @click="goBack" aria-label="Volver a socios"
            class="group flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-surface-200 bg-white text-surface-600 shadow-sm transition-all hover:bg-surface-50 hover:text-primary-600">
            <svg class="h-5 w-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor"
              viewBox="0 0 24 24" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
          </button>
          <AdminPageHeader title="Gestión de Familiares"
            :subtitle="`Socio Titular: ${currentSocio?.nombre_completo || 'Cargando...'}`" />
        </div>

        <div class="grid grid-cols-[auto_1fr] items-center gap-3 sm:flex">
          <div class="flex items-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2.5 shadow-sm">
            <span class="text-sm font-semibold text-surface-600">Ocupación</span>
            <span class="text-sm font-black tabular-nums" :class="totalMiembros >= 6 ? 'text-rose-600' : 'text-primary-600'">
              {{ totalMiembros }} / 6
            </span>
          </div>
          <AppButton :disabled="!canAddMore" @click="openCreateModal">
            <template #icon><IconUserPlus class="h-4 w-4" /></template>
            Añadir Familiar
          </AppButton>
        </div>
      </div>


      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-visible">
        <div v-if="loading.fetch" class="p-12 flex justify-center">
          <LoadingSpinner />
        </div>

        <div v-else-if="miembros.length === 0" class="p-16 flex flex-col items-center justify-center text-center">
          <h3 class="text-base font-black text-surface-900">Sin Familiares</h3>
          <p class="text-sm text-surface-500 mt-1 max-w-xs">No se han registrado miembros familiares para este socio.
          </p>
        </div>

        <template v-else>

          <!-- VISTA TABLA (pantalla grande) -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left text-slate-600">
              <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
                <tr>
                  <th scope="col" class="px-6 py-4 text-left font-extrabold rounded-tl-2xl">Nombre</th>
                  <th scope="col" class="px-6 py-4 text-left font-extrabold">Correo</th>
                  <th scope="col" class="px-6 py-4 text-left font-extrabold">Género</th>
                  <th scope="col" class="px-6 py-4 text-left font-extrabold">Parentesco</th>
                  <th scope="col" class="px-6 py-4 text-left font-extrabold">Edad</th>
                  <th scope="col" class="px-6 py-4 text-right font-extrabold rounded-tr-2xl">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-surface-100">
                <tr v-for="miembro in miembros" :key="miembro.id_miembro"
                  class="bg-white border-b border-surface-100 hover:bg-surface-50/50 transition-colors group">
                  <td class="px-6 py-4 font-semibold text-surface-900">{{ miembro.nombre_completo }}</td>
                  <td class="px-6 py-4 font-semibold text-surface-900">{{ miembro.correo || 'N/A' }}</td>
                  <td class="px-6 py-4">
                    <BadgeStatus :status="miembro.genero" />
                  </td>
                  <td class="px-6 py-4 font-semibold text-surface-900">{{ formatParentesco(miembro.parentesco,
                    miembro.genero) }}</td>
                  <td class="px-6 py-4 font-semibold text-surface-900">{{ miembro.edad }} años</td>
                  <td class="px-6 py-4 text-right">
                    <ActionMenu :items="buildMenuItems(miembro)" align="right" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- VISTA TARJETAS (pantalla chica) -->
          <div class="md:hidden p-4 space-y-4">
            <div v-for="miembro in miembros" :key="miembro.id_miembro"
              class="bg-white border border-surface-200 rounded-2xl shadow-sm">
              <!-- Encabezado: avatar, nombre, correo y menú -->
              <div class="flex items-center gap-3 px-4 py-3">
                <div
                  class="w-10 h-10 shrink-0 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-black">
                  {{ getIniciales(miembro.nombre_completo) }}
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-bold text-surface-900 truncate">{{ miembro.nombre_completo }}</p>
                  <p class="text-xs font-medium text-surface-500 truncate">{{ miembro.correo || 'N/A' }}</p>
                </div>
                <ActionMenu :items="buildMenuItems(miembro)" align="right" />
              </div>

              <!-- Etiquetas: parentesco y género -->
              <div class="flex items-center gap-2 px-4 py-3 border-t border-surface-100">
                <span
                  class="px-2.5 py-1 rounded-full bg-surface-100 text-surface-600 text-[10px] font-black uppercase tracking-wider">
                  {{ formatParentesco(miembro.parentesco, miembro.genero) }}
                </span>
                <BadgeStatus :status="miembro.genero" />
              </div>

              <!-- Datos alineados -->
              <div class="px-4 py-3 border-t border-surface-100 space-y-2">
                <div class="flex items-center justify-between text-sm">
                  <span class="font-semibold text-surface-500">Edad</span>
                  <span class="font-bold text-surface-900">{{ miembro.edad }} años</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="font-semibold text-surface-500">Correo</span>
                  <span class="font-bold text-surface-900 truncate ml-4">{{ miembro.correo || 'N/A' }}</span>
                </div>
              </div>
            </div>
          </div>

        </template>
      </div>

    </div>


    <!-- MODAL: REGISTRAR FAMILIAR (paso 1: formulario · paso 2: registro exitoso + QR) -->
    <AppModal
      v-model="showModal"
      :title="step === 2 ? 'Familiar registrado' : 'Registrar familiar'"
      :subtitle="step === 2 ? 'Su código de acceso ya está listo' : `Miembro de la familia de ${currentSocio?.nombre_completo || 'este socio'}`"
      :icon="step === 2 ? IconQr : IconUserPlus"
      :tone="step === 2 ? 'emerald' : 'blue'"
      :persistent="loading.create"
      @close="closeFormModal"
    >
      <form v-if="step === 1" id="family-form" class="grid grid-cols-1 gap-4 pt-1 sm:grid-cols-2" novalidate @submit.prevent="submitForm">
        <AppField label="Nombre completo" for="fam-nombre" required :error="formErrors.nombre" class="sm:col-span-2">
          <AppInput id="fam-nombre" v-model="formData.nombre" :icon="IconUser" placeholder="Ej. Juan Pérez" autocomplete="off" :invalid="!!formErrors.nombre" />
        </AppField>

        <AppField label="Parentesco" required :error="formErrors.parentesco">
          <AppSelect v-model="formData.parentesco" :options="PARENTESCO_OPTIONS" placeholder="Seleccionar" :invalid="!!formErrors.parentesco" />
        </AppField>

        <AppField label="Género" required :error="formErrors.genero">
          <AppSelect v-model="formData.genero" :options="GENERO_OPTIONS" placeholder="Seleccionar" :invalid="!!formErrors.genero" />
        </AppField>

        <AppField label="Fecha de nacimiento" required :error="formErrors.fecha_nacimiento">
          <AppDatePicker v-model="formData.fecha_nacimiento" :max-date="hoyIso" :invalid="!!formErrors.fecha_nacimiento" />
        </AppField>

        <AppField label="Edad" hint="Se calcula con la fecha de nacimiento">
          <AppInput :model-value="formEdadCalculada === '-' ? '—' : `${formEdadCalculada} años`" readonly tabindex="-1" />
        </AppField>

        <AppField label="Correo" for="fam-correo" optional :error="formErrors.correo" class="sm:col-span-2">
          <AppInput id="fam-correo" v-model="formData.correo" type="email" :icon="IconMail" placeholder="correo@ejemplo.com" autocomplete="off" :invalid="!!formErrors.correo" />
        </AppField>
      </form>

      <div v-else class="pt-2">
        <QrCodeDisplay :value="qrGeneratedData?.qr" :name="qrGeneratedData?.nombre" :caption="qrGeneratedData?.parentesco" />
      </div>

      <template #footer>
        <template v-if="step === 1">
          <AppButton variant="secondary" :disabled="loading.create" @click="closeFormModal">Cancelar</AppButton>
          <AppButton type="submit" form="family-form" :loading="loading.create">Registrar familiar</AppButton>
        </template>
        <AppButton v-else tone="emerald" @click="closeFormModal">Finalizar</AppButton>
      </template>
    </AppModal>

    <!-- MODAL: ELIMINAR FAMILIAR -->
    <AppConfirmModal
      v-model="showDeleteModal"
      title="¿Eliminar familiar?"
      :icon="IconTrash"
      tone="rose"
      confirm-label="Sí, eliminar"
      :loading="loading.delete"
      :error="deleteError"
      @confirm="confirmDeleteMiembro"
    >
      Se eliminará a <strong class="text-surface-900">{{ miembroAEliminar?.nombre_completo }}</strong>
      ({{ formatParentesco(miembroAEliminar?.parentesco, miembroAEliminar?.genero) }}) de la familia de este socio
      y su código de acceso dejará de funcionar.
      <span class="mt-3 block text-xs font-medium text-surface-500">Esta acción no se puede deshacer.</span>
    </AppConfirmModal>

    <!-- MODAL: CÓDIGO QR -->
    <QrCodeModal
      v-model="showQrModal"
      :value="qrModalData?.qr"
      :name="qrModalData?.nombre"
      :caption="qrModalData?.parentesco"
    />

  </main>
</template>
