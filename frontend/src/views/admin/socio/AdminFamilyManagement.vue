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
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import { IconChevronDown, IconUser, IconMail, IconCalendar, IconGuests } from '@/components/icons'

const route = useRoute()
const router = useRouter()
const familyStore = useAdminFamilyStore()
const socioStore = useSocioStore()
const { actionToast, toastInfo, confirmDelete } = useAlerts()

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


const openCreateModal = () => {
  if (!canAddMore.value) {
    toastInfo('Límite alcanzado', 'Un socio puede tener un máximo de 6 familiares.', 'error')
    return
  }
  formData.value = { ...initialForm }
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
    toastInfo('Creado', 'Familiar registrado correctamente.', 'success')

    qrGeneratedData.value = {
      nombre: nuevo.nombre_completo,
      parentesco: formatParentesco(nuevo.parentesco, nuevo.genero),
      qr: nuevo.codigoQrActivo?.codigo_qr
    }
    step.value = 2
  } catch (error) {
    toastInfo('Error', 'Hubo un error al procesar la solicitud.', 'error')
  }
}

const deleteMiembro = async (miembro) => {
  const result = await confirmDelete(
    'Eliminar Familiar',
    `¿Estás seguro de eliminar a ${miembro.nombre_completo}? Esta acción no se puede deshacer.`,
    'Sí, Eliminar',
    async () => {
      await familyStore.eliminarMiembro(socioId, miembro.id_miembro)
    }
  )
  
  if (result.isConfirmed) {
    toastInfo('Eliminado', 'Miembro familiar eliminado.', 'success')
  }
}
const copiarImagenAlPortapapeles = async (url) => {
  try {
    toastInfo('Procesando', 'Preparando imagen...', 'info')
    const response = await fetch(url)
    const blob = await response.blob()
    await navigator.clipboard.write([
      new ClipboardItem({ [blob.type]: blob })
    ])
    toastInfo('¡Listo!', 'Imagen del QR copiada al portapapeles', 'success')
  } catch (err) {
    toastInfo('Error', 'Usa clic derecho para copiar la imagen.', 'error')
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
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-red-500"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>`,
    action: () => deleteMiembro(miembro),
  },
]
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto space-y-8">
      

      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <button @click="goBack"
              class="w-11 h-11 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 hover:text-primary-600 transition-all shadow-sm group shrink-0">
              <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none"
                  stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
          </button>
          <AdminPageHeader 
            title="Gestión de Familiares" 
            :subtitle="`Socio Titular: ${currentSocio?.nombre_completo || 'Cargando...'}`"
          />
        </div>
        
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-xl shadow-sm border border-surface-200">
            <span class="text-sm font-semibold text-surface-500">Ocupación</span>
            <span class="text-sm font-black" :class="totalMiembros >= 6 ? 'text-red-500' : 'text-primary-600'">
              {{ totalMiembros }} / 6
            </span>
          </div>
          <button 
            @click="openCreateModal"
            :disabled="!canAddMore"
            class="px-5 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-xl shadow-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            + Añadir Familiar
          </button>
        </div>
      </div>


      <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-visible">
        <div v-if="loading.fetch" class="p-12 flex justify-center">
          <LoadingSpinner />
        </div>
        
        <div v-else-if="miembros.length === 0" class="p-16 flex flex-col items-center justify-center text-center">
          <h3 class="text-base font-black text-surface-900">Sin Familiares</h3>
          <p class="text-sm text-surface-500 mt-1 max-w-xs">No se han registrado miembros familiares para este socio.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm text-left text-slate-600">
            <thead>
              <tr class="bg-surface-50 border-b border-surface-200">
                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-widest text-slate-900 rounded-tl-2xl">Nombre</th>
                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-widest text-slate-900">Correo</th>
                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-widest text-slate-900">Género</th>
                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-widest text-slate-900">Parentesco</th>
                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black uppercase tracking-widest text-slate-900">Edad</th>
                <th scope="col" class="px-6 py-4 text-right text-[11px] font-black uppercase tracking-widest text-slate-900 rounded-tr-2xl">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
              <tr v-for="miembro in miembros" :key="miembro.id_miembro" class="bg-white border-b border-surface-100 hover:bg-surface-50/50 transition-colors group">
                <td class="px-6 py-4 font-semibold text-surface-900">{{ miembro.nombre_completo }}</td>
                <td class="px-6 py-4 font-semibold text-surface-900">{{ miembro.correo || 'N/A' }}</td>
                <td class="px-6 py-4"><BadgeStatus :status="miembro.genero" /></td>
                <td class="px-6 py-4 font-semibold text-surface-900">{{ formatParentesco(miembro.parentesco, miembro.genero) }}</td>
                <td class="px-6 py-4 font-semibold text-surface-900">{{ miembro.edad }} años</td>
                <td class="px-6 py-4 text-right">
                  <ActionMenu :items="buildMenuItems(miembro)" align="right" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>


    <Teleport to="body">
      <Transition 
        enter-active-class="transition-all duration-300 ease-out" 
        enter-from-class="opacity-0"
        enter-to-class="opacity-100" 
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" 
        leave-to-class="opacity-0"
      >
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm" @click.self="closeFormModal">
          <div class="bg-white w-full max-w-lg rounded-4xl shadow-2xl flex flex-col overflow-hidden">
            
            <div class="px-7 py-5 border-b border-surface-100 flex justify-between items-center">
              <div>
                <h2 class="text-lg font-black text-surface-900 leading-tight">
                  {{ step === 2 ? 'Familiar Registrado' : 'Registrar Familiar' }}
                </h2>
              </div>
              <button v-if="step !== 2" @click="closeFormModal" class="w-8 h-8 rounded-full bg-surface-100 hover:bg-surface-200 flex items-center justify-center">
                <svg class="w-4 h-4 text-surface-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
              </button>
            </div>


            <div v-if="step === 1" class="p-7 space-y-5 bg-surface-50/30">
              <div class="space-y-1.5">
                <label class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                  Nombre Completo <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                  <IconUser class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" />
                  <input v-model="formData.nombre" type="text"
                    class="w-full pl-11 pr-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-semibold text-surface-900 placeholder:text-surface-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all shadow-sm"
                    placeholder="Ej. Juan Pérez">
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <label class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                    Parentesco <span class="text-red-400">*</span>
                  </label>
                  <div class="relative">
                    <IconGuests class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" />
                    <select v-model="formData.parentesco"
                      class="w-full pl-11 pr-8 py-3 bg-white border border-surface-200 rounded-xl text-sm font-semibold text-surface-900 appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all cursor-pointer shadow-sm">
                      <option value="" disabled>Seleccionar</option>
                      <option value="CONYUGE">Cónyuge</option>
                      <option value="HIJO/A">Hijo/a</option>
                      <option value="OTRO">Otro</option>
                    </select>
                    <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                  </div>
                </div>
                <div class="space-y-1.5">
                  <label class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                    Género <span class="text-red-400">*</span>
                  </label>
                  <div class="relative">
                    <IconUser class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" />
                    <select v-model="formData.genero"
                      class="w-full pl-11 pr-8 py-3 bg-white border border-surface-200 rounded-xl text-sm font-semibold text-surface-900 appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all cursor-pointer shadow-sm">
                      <option value="" disabled>Seleccionar</option>
                      <option value="M">Masculino</option>
                      <option value="F">Femenino</option>
                      <option value="O">Otro</option>
                    </select>
                    <IconChevronDown class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <label class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                    Fecha de Nacimiento <span class="text-red-400">*</span>
                  </label>
                  <div class="relative">
                    <IconCalendar class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" />
                    <input v-model="formData.fecha_nacimiento" type="date"
                      class="w-full pl-11 pr-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-semibold text-surface-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all shadow-sm">
                  </div>
                </div>
                <div class="space-y-1.5">
                  <label class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                    Edad
                  </label>
                  <input type="text" :value="formEdadCalculada" readonly
                    class="w-full px-4 py-3 bg-surface-100 border border-surface-200 rounded-xl text-sm font-bold text-surface-500 cursor-not-allowed">
                </div>
              </div>

              <div class="space-y-1.5">
                <label class="block text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">
                  Correo (Opcional)
                </label>
                <div class="relative">
                  <IconMail class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-300" />
                  <input v-model="formData.correo" type="email"
                    class="w-full pl-11 pr-4 py-3 bg-white border border-surface-200 rounded-xl text-sm font-semibold text-surface-900 placeholder:text-surface-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition-all shadow-sm"
                    placeholder="correo@ejemplo.com">
                </div>
              </div>

              <div class="pt-4 flex justify-end gap-3 border-t border-surface-100 mt-4">
                <CancelButton label="Cancelar" @click="closeFormModal" />
                <ConfirmButton label="Registrar" :loading="loading.create" @click="submitForm" />
              </div>
            </div>


            <div v-else-if="step === 2" class="p-8 flex flex-col items-center text-center">
              <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
              </div>
              <h3 class="text-xl font-black text-surface-900 mb-1">Registro Exitoso</h3>
              <p class="text-sm text-surface-500 mb-6">El familiar ha sido agregado y su código de acceso está listo.</p>
              
              <div class="p-4 bg-white border border-surface-200 rounded-2xl shadow-sm mb-6">
                <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${qrGeneratedData?.qr}`" alt="QR Code" class="w-36 h-36 object-contain">
              </div>
              <div class="flex flex-col gap-3 w-full">
                <button @click="copiarImagenAlPortapapeles(`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${qrGeneratedData?.qr}`)"
                  class="w-full rounded-xl px-4 py-3 font-bold transition-all flex items-center justify-center gap-2 active:scale-95 bg-linear-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white shadow-md shadow-primary-200" style="background-size: 200% auto;">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                    <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                  </svg>
                  Copiar Código QR
                </button>
                <ConfirmButton label="Finalizar" @click="closeFormModal" class="w-full" />
              </div>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>


    <Teleport to="body">
      <Transition 
        enter-active-class="transition-all duration-300 ease-out" 
        enter-from-class="opacity-0"
        enter-to-class="opacity-100" 
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" 
        leave-to-class="opacity-0"
      >
        <div v-if="showQrModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm" @click.self="showQrModal = false">
          <div class="bg-white w-full max-w-sm rounded-4xl shadow-2xl flex flex-col overflow-hidden text-center p-8">
            <h2 class="text-xl font-black text-surface-900 leading-tight mb-1">Código de Acceso</h2>
            <p class="text-sm text-surface-500 font-medium mb-6">{{ qrModalData?.nombre }} - {{ qrModalData?.parentesco }}</p>
            
            <div class="mx-auto p-4 bg-white border border-surface-200 rounded-2xl shadow-sm mb-6">
              <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${qrModalData?.qr}`" alt="QR Code" class="w-40 h-40 object-contain mx-auto">
            </div>
            <div class="flex flex-col gap-3 w-full">
              <button @click="copiarImagenAlPortapapeles(`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${qrModalData?.qr}`)"
                class="w-full rounded-xl px-4 py-3 font-bold transition-all flex items-center justify-center gap-2 active:scale-95 bg-linear-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white shadow-md shadow-primary-200" style="background-size: 200% auto;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                  <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                </svg>
                Copiar Código QR
              </button>
              <CancelButton label="Cerrar" @click="showQrModal = false" class="w-full" />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </main>
</template>
