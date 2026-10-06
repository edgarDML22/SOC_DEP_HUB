<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import { useAlerts } from '@/composables/useAlerts'

const router = useRouter()
const route = useRoute()
const { toastSuccess, toastError } = useAlerts()

// --- Variables de Estado ---
const torneos = ref([])
const preRegistros = ref([])
const loadingTorneos = ref(false)
const loadingRegistros = ref(false)
const actionInProgress = ref(null) // ID del registro en procesamiento

// --- Filtros ---
const selectedTorneoId = ref('')
const selectedStatus = ref('PENDIENTE')
const searchQuery = ref('')

// --- Paginación ---
const currentPage = ref(1)
const lastPage = ref(1)
const totalRecords = ref(0)

// --- Estado de Modales ---
const showConfirmModal = ref(false)
const showRejectModal = ref(false)
const activeRecord = ref(null)
const rejectReason = ref('')

const showDocModal = ref(false)
const currentDocUrl = ref('')
const currentDocTitle = ref('')

// --- UI y Acordeón ---
const expandedRecordId = ref(null)
const toggleExpand = (id) => {
  if (expandedRecordId.value === id) {
    expandedRecordId.value = null
  } else {
    expandedRecordId.value = id
  }
}

const showDropdown = ref(false)
const selectTorneo = (id) => {
  selectedTorneoId.value = id
  showDropdown.value = false
}
const closeDropdown = (e) => {
  if (!e.target.closest('.custom-select-wrapper')) {
    showDropdown.value = false
  }
}

// --- Propiedades Computadas ---
const filteredTorneos = computed(() => {
  // Solo torneos en estatus de inscripción
  return torneos.value.filter(t => t.estado === 'EN_INSCRIPCION' || t.estatus_torneo === 'EN_INSCRIPCION')
})

const currentTorneoName = computed(() => {
  const torneo = torneos.value.find(t => t.id_torneo === selectedTorneoId.value || t.id === selectedTorneoId.value)
  return torneo ? torneo.nombre_torneo : 'Selecciona un Torneo'
})

// --- Peticiones API ---

// Cargar torneos disponibles para el filtro principal
const fetchTorneos = async () => {
  loadingTorneos.value = true
  try {
    const response = await api.get('/torneos')
    const resData = response.data.data ?? response.data
    if (Array.isArray(resData)) {
      torneos.value = resData
    } else if (resData && Array.isArray(resData.data)) {
      torneos.value = resData.data
    }

    // Seleccionar torneo desde URL si existe, si no, seleccionar el primero activo
    const queryTorneoId = route.query.torneo_id
    if (queryTorneoId) {
      const parsedId = Number(queryTorneoId) || queryTorneoId
      selectedTorneoId.value = parsedId
    } else if (filteredTorneos.value.length > 0) {
      selectedTorneoId.value = filteredTorneos.value[0].id_torneo || filteredTorneos.value[0].id
    }
  } catch (err) {
    console.error('Error fetching tournaments:', err)
    toastError('No se pudo cargar la lista de torneos.')
  } finally {
    loadingTorneos.value = false
  }
}

// Obtener pre-registros del torneo y estado seleccionados
const fetchPreRegistros = async () => {
  if (!selectedTorneoId.value) return
  loadingRegistros.value = true
  try {
    const response = await api.get(`/torneos/${selectedTorneoId.value}/pre-registros`, {
      params: {
        estatus: selectedStatus.value,
        page: currentPage.value
      }
    })

    const res = response.data.data ?? response.data

    if (res && typeof res === 'object' && Array.isArray(res.data)) {
      preRegistros.value = res.data
      currentPage.value = res.current_page || 1
      lastPage.value = res.last_page || 1
      totalRecords.value = res.total || 0
    } else {
      preRegistros.value = Array.isArray(res) ? res : []
      currentPage.value = 1
      lastPage.value = 1
      totalRecords.value = preRegistros.value.length
    }
  } catch (err) {
    console.error('Error fetching pre-registrations:', err)
    toastError('Error al cargar la bandeja de pre-registros.')
  } finally {
    loadingRegistros.value = false
  }
}

// --- Observadores ---
watch([selectedTorneoId, selectedStatus], () => {
  currentPage.value = 1
  expandedRecordId.value = null // reset accordion state when changing filters
  fetchPreRegistros()
})

watch(currentPage, () => {
  expandedRecordId.value = null
  fetchPreRegistros()
})

// --- Funciones de Apoyo ---
const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('es-MX', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatText = (text) => {
  if (!text) return ''
  return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase().replace(/_/g, ' ')
}

const getGenderLabel = (g) => {
  if (!g) return 'No especificado'
  const lower = g.toLowerCase()
  if (lower === 'f' || lower === 'femenino') return 'Femenino'
  if (lower === 'm' || lower === 'masculino') return 'Masculino'
  return g.charAt(0).toUpperCase() + g.slice(1).toLowerCase()
}

// Obtener y abrir el archivo PDF dentro del modal visor
const openDocument = async (path, docLabel) => {
  if (!path) return
  try {
    const response = await api.get('/pre-registros/documento', {
      params: { path },
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    currentDocUrl.value = URL.createObjectURL(blob)
    currentDocTitle.value = docLabel
    showDocModal.value = true
  } catch (err) {
    console.error(`Error loading document ${docLabel}:`, err)
    toastError(`No se pudo abrir el documento: ${docLabel}`)
  }
}

const closeDocument = () => {
  showDocModal.value = false
  if (currentDocUrl.value) {
    URL.revokeObjectURL(currentDocUrl.value)
    currentDocUrl.value = ''
  }
  currentDocTitle.value = ''
}

// --- Acciones Principales ---

const initiateApprove = (record) => {
  activeRecord.value = record
  showConfirmModal.value = true
}

const confirmApprove = async () => {
  if (!activeRecord.value) return
  const recordId = activeRecord.value.id
  showConfirmModal.value = false
  actionInProgress.value = recordId

  try {
    const response = await api.patch(`/torneos/${selectedTorneoId.value}/pre-registros/${recordId}/aprobar`)
    toastSuccess(response.data.message || 'El pre-registro ha sido aprobado.')
    
    // Actualización visual inmediata en la lista local
    const recordIndex = preRegistros.value.findIndex(r => r.id === recordId)
    if (recordIndex !== -1) {
      preRegistros.value[recordIndex].estatus = 'APROBADO'
      // Si estamos en la pestaña de pendientes, quitamos la tarjeta de la vista
      if (selectedStatus.value === 'PENDIENTE') {
        preRegistros.value.splice(recordIndex, 1)
        totalRecords.value = Math.max(0, totalRecords.value - 1)
      }
    }
  } catch (err) {
    console.error('Error approving pre-registration:', err)
    toastError(err.response?.data?.message || 'Error al aprobar el pre-registro.')
  } finally {
    actionInProgress.value = null
    activeRecord.value = null
  }
}

const initiateReject = (record) => {
  activeRecord.value = record
  rejectReason.value = ''
  showRejectModal.value = true
}

const confirmReject = async () => {
  if (!activeRecord.value || rejectReason.value.length < 20) return
  const recordId = activeRecord.value.id
  showRejectModal.value = false
  actionInProgress.value = recordId

  try {
    const response = await api.patch(`/torneos/${selectedTorneoId.value}/pre-registros/${recordId}/rechazar`, {
      motivo_rechazo: rejectReason.value
    })
    toastSuccess(response.data.message || 'El pre-registro ha sido rechazado.')

    // Actualización visual inmediata en la lista local
    const recordIndex = preRegistros.value.findIndex(r => r.id === recordId)
    if (recordIndex !== -1) {
      preRegistros.value[recordIndex].estatus = 'RECHAZADO'
      if (selectedStatus.value === 'PENDIENTE') {
        preRegistros.value.splice(recordIndex, 1)
        totalRecords.value = Math.max(0, totalRecords.value - 1)
      }
    }
  } catch (err) {
    console.error('Error rejecting pre-registration:', err)
    toastError(err.response?.data?.message || 'Error al rechazar el pre-registro.')
  } finally {
    actionInProgress.value = null
    activeRecord.value = null
    rejectReason.value = ''
  }
}

// Filtrar pre-registros por término de búsqueda (nombre o correo)
const searchedPreRegistros = computed(() => {
  if (!searchQuery.value) return preRegistros.value
  const query = searchQuery.value.toLowerCase().trim()
  return preRegistros.value.filter(record => {
    if (record.tipo === 'INDIVIDUAL') {
      const name = record.datos_participante?.nombre_completo?.toLowerCase() || ''
      const email = record.datos_participante?.correo?.toLowerCase() || ''
      return name.includes(query) || email.includes(query)
    } else {
      const teamName = record.datos_participante?.nombre_equipo?.toLowerCase() || ''
      const membersMatch = record.datos_participante?.integrantes?.some(member => {
        const mName = member.nombre_completo?.toLowerCase() || ''
        const mEmail = member.correo?.toLowerCase() || ''
        return mName.includes(query) || mEmail.includes(query)
      })
      return teamName.includes(query) || membersMatch
    }
  })
})

onMounted(() => {
  document.addEventListener('click', closeDropdown)
  fetchTorneos()
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown)
})
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-20 font-sans text-surface-800">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Header Section -->
      <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-4">
        <div class="flex items-center gap-4">
          <button @click="router.push('/admin/tournaments')"
            class="w-10 h-10 bg-white border border-surface-200 rounded-full flex items-center justify-center text-surface-500 hover:bg-surface-100 hover:text-surface-700 transition-all shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
          </button>
          <div>
            <h1 class="text-2xl font-bold text-surface-900 tracking-tight">Bandeja de Pre-registros</h1>
            <p class="text-sm text-surface-500 mt-1">Revisa las solicitudes, inspecciona PDFs y gestiona aprobaciones.</p>
          </div>
        </div>

        <div class="bg-white px-5 py-3 border border-surface-200 rounded-2xl shadow-sm text-right shrink-0">
          <span class="text-xs font-semibold uppercase tracking-wider text-surface-400 block mb-0.5">Torneo Seleccionado</span>
          <span class="text-sm font-bold text-primary-600 block">{{ currentTorneoName }}</span>
        </div>
      </header>

      <!-- Filter Panel -->
      <section class="bg-white rounded-3xl border border-surface-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          
          <!-- Tournament Select (Mejorado) -->
          <div class="flex flex-col gap-2 custom-select-wrapper relative">
            <label class="text-xs font-semibold uppercase tracking-wider text-surface-500">Torneo en Inscripción</label>
            <button @click="showDropdown = !showDropdown" 
              class="w-full bg-surface-50 border border-surface-200 rounded-xl py-3 px-4 text-sm font-medium flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all text-left">
              <span v-if="selectedTorneoId" class="truncate">{{ currentTorneoName }}</span>
              <span v-else class="text-surface-400">Seleccionar torneo...</span>
              <i class="fas fa-chevron-down text-surface-400 transition-transform" :class="{'rotate-180': showDropdown}"></i>
            </button>
            
            <Transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
              <div v-if="showDropdown" class="absolute z-20 w-full top-[100%] mt-2 bg-white border border-surface-100 rounded-2xl shadow-xl max-h-60 overflow-y-auto py-2">
                <div v-if="filteredTorneos.length === 0" class="px-4 py-3 text-sm text-surface-500">No hay torneos en inscripción</div>
                <div v-for="t in filteredTorneos" :key="t.id_torneo || t.id" 
                     @click="selectTorneo(t.id_torneo || t.id)"
                     class="px-4 py-3 text-sm cursor-pointer hover:bg-surface-50 flex items-center justify-between transition-colors"
                     :class="{'bg-primary-50 text-primary-700 font-bold': (t.id_torneo || t.id) === selectedTorneoId}">
                  <span>{{ t.nombre_torneo }} <span class="text-xs text-surface-400 font-normal ml-1">({{ formatText(t.disciplina) }})</span></span>
                  <i v-if="(t.id_torneo || t.id) === selectedTorneoId" class="fas fa-check text-primary-600"></i>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Status Select (Segmented Buttons) -->
          <div class="flex flex-col gap-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-surface-500">Estado de Solicitud</label>
            <div class="flex bg-surface-100/70 p-1 rounded-xl h-[46px] w-full gap-1 border border-surface-200/50">
              <button 
                @click="selectedStatus = 'PENDIENTE'"
                :class="selectedStatus === 'PENDIENTE' ? 'bg-amber-500 text-white shadow-sm border-amber-600' : 'text-surface-600 hover:text-surface-800 hover:bg-surface-200/50 border-transparent'"
                class="flex-1 flex items-center justify-center gap-2 rounded-lg text-xs font-semibold transition-all border cursor-pointer">
                Pendientes
                <span v-if="selectedStatus === 'PENDIENTE' && totalRecords > 0" class="bg-white/20 text-white py-0.5 px-2 rounded-full text-[10px]">{{ totalRecords }}</span>
              </button>
              <button 
                @click="selectedStatus = 'APROBADO'"
                :class="selectedStatus === 'APROBADO' ? 'bg-emerald-500 text-white shadow-sm border-emerald-600' : 'text-surface-600 hover:text-surface-800 hover:bg-surface-200/50 border-transparent'"
                class="flex-1 flex items-center justify-center rounded-lg text-xs font-semibold transition-all border cursor-pointer">
                Aprobados
              </button>
              <button 
                @click="selectedStatus = 'RECHAZADO'"
                :class="selectedStatus === 'RECHAZADO' ? 'bg-red-500 text-white shadow-sm border-red-600' : 'text-surface-600 hover:text-surface-800 hover:bg-surface-200/50 border-transparent'"
                class="flex-1 flex items-center justify-center rounded-lg text-xs font-semibold transition-all border cursor-pointer">
                Rechazados
              </button>
            </div>
          </div>

          <!-- Search Bar -->
          <div class="flex flex-col gap-2">
            <label for="search-input" class="text-xs font-semibold uppercase tracking-wider text-surface-500">Buscar Participante</label>
            <div class="relative flex items-center">
              <i class="fas fa-search absolute left-4 text-surface-400 pointer-events-none"></i>
              <input id="search-input" type="text" v-model="searchQuery" placeholder="Nombre o correo..."
                class="w-full bg-surface-50 border border-surface-200 rounded-xl py-3 pl-10 pr-4 text-sm font-medium placeholder-surface-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all" />
            </div>
          </div>

        </div>
      </section>

      <!-- Main Loader / Error / Empty States -->
      <section v-if="loadingRegistros" class="flex flex-col items-center justify-center p-20 bg-white rounded-3xl border border-surface-200 shadow-sm">
        <i class="fas fa-circle-notch fa-spin text-4xl text-primary-500 mb-4"></i>
        <p class="text-sm font-semibold text-surface-500">Cargando solicitudes...</p>
      </section>

      <section v-else-if="!selectedTorneoId" class="text-center p-16 bg-white rounded-3xl border border-surface-200 shadow-sm">
        <div class="w-16 h-16 mx-auto bg-amber-50 rounded-full flex items-center justify-center mb-4">
          <i class="fas fa-inbox text-2xl text-amber-500"></i>
        </div>
        <h3 class="text-lg font-bold text-surface-900 mb-1">Ningún torneo seleccionado</h3>
        <p class="text-surface-500 text-sm">Selecciona o habilita un torneo en fase de inscripción.</p>
      </section>

      <section v-else-if="searchedPreRegistros.length === 0" class="flex flex-col items-center justify-center p-16 bg-white rounded-3xl border border-surface-200 shadow-sm text-center">
        <div class="w-16 h-16 rounded-full bg-surface-50 flex items-center justify-center text-surface-400 mb-4 border border-surface-100">
          <i class="fas fa-folder-open text-2xl"></i>
        </div>
        <h3 class="text-lg font-bold text-surface-900 mb-1">Bandeja vacía</h3>
        <p class="text-sm text-surface-500 max-w-sm">No se encontraron pre-registros con el estado seleccionado.</p>
      </section>

      <!-- Pre-registrations Cards Grid (Acordeón) -->
      <section v-else class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 items-start">
          <div v-for="record in searchedPreRegistros" :key="record.id"
            class="bg-white rounded-3xl border shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden flex flex-col relative group cursor-pointer"
            :class="[
              actionInProgress === record.id ? 'opacity-70 pointer-events-none' : '',
              expandedRecordId === record.id ? 'border-primary-300 ring-2 ring-primary-500/20' : 'border-surface-200'
            ]"
            @click="toggleExpand(record.id)">
            
            <!-- Loading Indicator Overlay per card -->
            <div v-if="actionInProgress === record.id" class="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-10">
              <i class="fas fa-circle-notch fa-spin text-3xl text-primary-500"></i>
            </div>

            <!-- Card Header (Always visible) -->
            <div class="p-5 flex items-start gap-3 relative">
              <!-- Avatar -->
              <div class="shrink-0 mt-1">
                <img v-if="record.tipo === 'INDIVIDUAL' && record.datos_participante?.foto_perfil" :src="record.datos_participante.foto_perfil" class="w-10 h-10 rounded-full object-cover shadow-sm border border-surface-200" />
                <div v-else-if="record.tipo === 'INDIVIDUAL'" class="w-10 h-10 rounded-full bg-surface-100 text-surface-600 flex items-center justify-center text-sm font-bold border border-surface-200">
                  {{ record.datos_participante?.nombre_completo?.charAt(0).toUpperCase() }}
                </div>
                <div v-else class="w-10 h-10 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold border border-purple-100">
                  <i class="fas fa-users"></i>
                </div>
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0 pr-8">
                <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider"
                    :class="record.tipo === 'INDIVIDUAL' ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600'">
                    {{ record.tipo }}
                  </span>
                  <!-- Status Badge -->
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider border"
                    :class="{
                      'bg-amber-50 text-amber-600 border-amber-200/50': record.estatus === 'PENDIENTE',
                      'bg-emerald-50 text-emerald-600 border-emerald-200/50': record.estatus === 'APROBADO',
                      'bg-red-50 text-red-600 border-red-200/50': record.estatus === 'RECHAZADO'
                    }">
                    {{ record.estatus === 'PENDIENTE' ? 'Pendiente' : (record.estatus === 'APROBADO' ? 'Aprobado' : 'Rechazado') }}
                  </span>
                </div>
                
                <h3 class="text-sm font-bold text-surface-900 truncate">
                  {{ record.tipo === 'INDIVIDUAL' ? record.datos_participante?.nombre_completo : record.datos_participante?.nombre_equipo }}
                </h3>
                <span v-if="record.tipo === 'INDIVIDUAL'" class="block text-[10px] text-surface-500 truncate">{{ record.datos_participante?.correo }}</span>
                <span v-else class="block text-[10px] text-surface-500 truncate">{{ record.datos_participante?.integrantes?.length || 0 }} Integrantes</span>
                
                <p class="text-[9px] text-surface-400 mt-1.5 flex items-center gap-1 font-medium">
                  <i class="far fa-clock"></i> {{ formatDate(record.created_at) }}
                </p>
              </div>

              <!-- Arrow -->
              <div class="absolute right-5 bottom-5 text-surface-800 transition-transform duration-300 flex items-center justify-center"
                   :class="{'rotate-180 text-primary-600': expandedRecordId === record.id}">
                <svg class="w-5 h-5 drop-shadow-sm" fill="currentColor" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path d="M78.466,35.559L50.15,63.633L22.078,35.317c-0.777-0.785-2.044-0.789-2.828-0.012s-0.789,2.044-0.012,2.827L48.432,67.58 c0.365,0.368,0.835,0.563,1.312,0.589c0.139,0.008,0.278-0.001,0.415-0.021c0.054,0.008,0.106,0.021,0.16,0.022 c0.544,0.029,1.099-0.162,1.515-0.576l29.447-29.196c0.785-0.777,0.79-2.043,0.012-2.828S79.249,34.781,78.466,35.559z"></path> </g> </g></svg>
              </div>
            </div>

            <!-- Card Body (Expandable) -->
            <div v-show="expandedRecordId === record.id" class="px-5 pb-5 pt-3 flex-grow flex flex-col gap-5 border-t border-surface-100 bg-surface-50/30" @click.stop>
              
              <!-- INDIVIDUAL DETAILS -->
              <div v-if="record.tipo === 'INDIVIDUAL'" class="space-y-4">
                <!-- Stats/Details -->
                <div class="grid grid-cols-2 gap-3 pb-3 border-b border-surface-200">
                  <div>
                    <span class="block text-[9px] font-bold uppercase tracking-wider text-surface-400 mb-0.5">Género</span>
                    <span class="text-xs font-medium text-surface-800">{{ getGenderLabel(record.datos_participante?.genero) }}</span>
                  </div>
                  <div>
                    <span class="block text-[9px] font-bold uppercase tracking-wider text-surface-400 mb-0.5">Ranking</span>
                    <span class="text-xs font-medium text-surface-800"># {{ record.datos_participante?.ranking_declarado }}</span>
                  </div>
                </div>

                <!-- Documents -->
                <div>
                  <span class="block text-[9px] font-bold uppercase tracking-wider text-surface-400 mb-2">Documentos Adjuntos</span>
                  <div class="flex flex-col gap-2">
                    <button v-if="record.urls_documentos?.ine_pdf" @click="openDocument(record.urls_documentos.ine_pdf, 'INE')"
                      class="flex items-center gap-2 px-3 py-2 bg-white border border-surface-200 hover:border-surface-300 hover:bg-surface-50 rounded-lg text-xs font-semibold text-surface-700 transition-all w-full text-left">
                      <i class="fas fa-file-pdf text-red-500 w-4 text-center"></i> INE
                    </button>
                    <button v-if="record.urls_documentos?.curp_pdf" @click="openDocument(record.urls_documentos.curp_pdf, 'CURP')"
                      class="flex items-center gap-2 px-3 py-2 bg-white border border-surface-200 hover:border-surface-300 hover:bg-surface-50 rounded-lg text-xs font-semibold text-surface-700 transition-all w-full text-left">
                      <i class="fas fa-file-pdf text-red-500 w-4 text-center"></i> CURP
                    </button>
                    <button v-if="record.urls_documentos?.responsiva_pdf" @click="openDocument(record.urls_documentos.responsiva_pdf, 'Responsiva')"
                      class="flex items-center gap-2 px-3 py-2 bg-white border border-surface-200 hover:border-surface-300 hover:bg-surface-50 rounded-lg text-xs font-semibold text-surface-700 transition-all w-full text-left">
                      <i class="fas fa-file-pdf text-red-500 w-4 text-center"></i> Carta Resp.
                    </button>
                  </div>
                </div>
              </div>

              <!-- EQUIPO DETAILS -->
              <div v-else class="space-y-4">
                <span class="block text-[9px] font-bold uppercase tracking-wider text-surface-400">Integrantes del Equipo</span>
                
                <div v-for="(member, idx) in record.datos_participante?.integrantes" :key="idx" class="border-b border-surface-200 last:border-0 pb-3 last:pb-0">
                  <div class="flex items-start gap-3 mb-2.5">
                    <img v-if="member.foto_perfil" :src="member.foto_perfil" class="w-8 h-8 rounded-full object-cover border border-surface-200 shadow-sm shrink-0" />
                    <div v-else class="w-8 h-8 rounded-full bg-white text-surface-500 flex items-center justify-center text-xs font-bold border border-surface-200 shrink-0">
                      {{ idx + 1 }}
                    </div>
                    <div class="min-w-0 flex-1">
                      <span class="block text-xs font-semibold text-surface-800 truncate">{{ member.nombre_completo }}</span>
                      <span class="block text-[9px] text-surface-500 truncate">{{ member.correo }}</span>
                      <div class="flex gap-2 mt-1 text-[9px] text-surface-600 font-medium">
                        <span>Gen: {{ getGenderLabel(member.genero) }}</span>
                        <span>Rank: # {{ member.ranking_declarado }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Member Documents -->
                  <div class="flex flex-wrap gap-1.5 pl-11">
                    <button v-if="record.urls_documentos?.[member.correo]?.ine_pdf" @click="openDocument(record.urls_documentos[member.correo].ine_pdf, `INE - ${member.nombre_completo}`)"
                      class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white border border-surface-200 hover:bg-surface-50 rounded-md text-[9px] font-medium text-surface-700 transition-colors flex-1 text-center justify-center">
                      <i class="fas fa-file-pdf text-red-500"></i> INE
                    </button>
                    <button v-if="record.urls_documentos?.[member.correo]?.curp_pdf" @click="openDocument(record.urls_documentos[member.correo].curp_pdf, `CURP - ${member.nombre_completo}`)"
                      class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white border border-surface-200 hover:bg-surface-50 rounded-md text-[9px] font-medium text-surface-700 transition-colors flex-1 text-center justify-center">
                      <i class="fas fa-file-pdf text-red-500"></i> CURP
                    </button>
                    <button v-if="record.urls_documentos?.[member.correo]?.responsiva_pdf" @click="openDocument(record.urls_documentos[member.correo].responsiva_pdf, `Responsiva - ${member.nombre_completo}`)"
                      class="flex items-center gap-1.5 px-2.5 py-1.5 bg-white border border-surface-200 hover:bg-surface-50 rounded-md text-[9px] font-medium text-surface-700 transition-colors flex-1 text-center justify-center">
                      <i class="fas fa-file-pdf text-red-500"></i> Resp.
                    </button>
                  </div>
                </div>
              </div>

              <!-- Rejection Reason if rejected -->
              <div v-if="record.estatus === 'RECHAZADO' && record.motivo_rechazo" class="p-3 bg-red-50 rounded-2xl border border-red-100">
                <span class="block text-[9px] font-bold uppercase tracking-wider text-red-600 mb-1">Motivo del Rechazo</span>
                <p class="text-xs text-red-800 font-medium">{{ record.motivo_rechazo }}</p>
              </div>

              <!-- Card Actions footer (only if status is PENDIENTE) -->
              <div v-if="record.estatus === 'PENDIENTE'" class="pt-2 flex flex-col gap-2 mt-auto">
                <button @click="initiateApprove(record)" :disabled="actionInProgress !== null"
                  class="w-full py-2.5 px-4 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 shadow-sm shadow-emerald-500/20 disabled:opacity-50">
                  <i class="fas fa-check"></i> Aprobar
                </button>
                <button @click="initiateReject(record)" :disabled="actionInProgress !== null"
                  class="w-full py-2.5 px-4 bg-red-100 hover:bg-red-200 text-red-600 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                  <i class="fas fa-times"></i> Rechazar
                </button>
              </div>
            </div>

          </div>
        </div>

        <!-- Pagination Controls -->
        <footer v-if="lastPage > 1" class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-8">
          <span class="text-sm font-medium text-surface-500">
            Mostrando {{ preRegistros.length }} de {{ totalRecords }} solicitudes (Pág. {{ currentPage }} de {{ lastPage }})
          </span>
          
          <div class="flex items-center gap-2">
            <button @click="currentPage--" :disabled="currentPage === 1"
              class="w-10 h-10 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 transition-all disabled:opacity-50 shadow-sm">
              <i class="fas fa-chevron-left text-sm"></i>
            </button>
            <button @click="currentPage++" :disabled="currentPage === lastPage"
              class="w-10 h-10 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 transition-all disabled:opacity-50 shadow-sm">
              <i class="fas fa-chevron-right text-sm"></i>
            </button>
          </div>
        </footer>
      </section>

    </div>

    <!-- ── MODALS ── -->

    <!-- Confirmation Dialog: Approval -->
    <Transition enter-active-class="transition-opacity duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showConfirmModal" class="fixed inset-0 bg-surface-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-md w-full p-8 space-y-6">
          
          <div class="w-14 h-14 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center text-2xl mb-2">
            <i class="fas fa-check"></i>
          </div>

          <div>
            <h3 class="text-xl font-bold text-surface-900 mb-2">Aprobar solicitud</h3>
            <p class="text-surface-500 text-sm leading-relaxed">
              ¿Estás seguro de que deseas aprobar el pre-registro de 
              <strong class="text-surface-900 font-semibold">{{ activeRecord?.tipo === 'INDIVIDUAL' ? activeRecord?.datos_participante?.nombre_completo : activeRecord?.datos_participante?.nombre_equipo }}</strong>?
            </p>
          </div>

          <div class="p-4 bg-emerald-50/50 rounded-2xl border border-emerald-100/50 text-sm text-emerald-800 flex gap-3">
            <i class="fas fa-info-circle text-emerald-500 mt-0.5"></i>
            <span class="leading-relaxed">Al confirmar, el participante quedará inscrito y recibirá su código QR de acceso automáticamente.</span>
          </div>

          <div class="flex gap-3 pt-2">
            <button @click="showConfirmModal = false" class="flex-1 py-3 rounded-xl bg-surface-100 text-surface-700 font-semibold hover:bg-surface-200 transition-colors">
              Cancelar
            </button>
            <button @click="confirmApprove" class="flex-1 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-semibold transition-colors shadow-sm shadow-emerald-500/20">
              Sí, Aprobar
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Modal Form: Rejection -->
    <Transition enter-active-class="transition-opacity duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showRejectModal" class="fixed inset-0 bg-surface-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-lg w-full p-8 space-y-6">
          
          <div class="w-14 h-14 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center text-2xl mb-2">
            <i class="fas fa-times"></i>
          </div>

          <div>
            <h3 class="text-xl font-bold text-surface-900 mb-2">Rechazar solicitud</h3>
            <p class="text-surface-500 text-sm leading-relaxed">
              Explica detalladamente la razón de rechazo para 
              <strong class="text-surface-900 font-semibold">{{ activeRecord?.tipo === 'INDIVIDUAL' ? activeRecord?.datos_participante?.nombre_completo : activeRecord?.datos_participante?.nombre_equipo }}</strong>.
            </p>
          </div>

          <!-- Reject Form Input -->
          <div class="space-y-2">
            <label for="reject-textarea" class="text-xs font-semibold text-surface-700">Motivo del rechazo</label>
            <textarea id="reject-textarea" v-model="rejectReason" rows="4" placeholder="Escribe el motivo del rechazo del documento..."
              class="w-full bg-surface-50 border border-surface-200 rounded-2xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all resize-none"></textarea>
            
            <div class="flex justify-between items-center text-xs mt-1" :class="rejectReason.length < 20 ? 'text-red-500' : 'text-surface-400'">
              <span v-if="rejectReason.length < 20">Mínimo 20 caracteres</span>
              <span v-else>Formato correcto</span>
              <span>{{ rejectReason.length }} / min 20</span>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button @click="showRejectModal = false" class="flex-1 py-3 rounded-xl bg-surface-100 text-surface-700 font-semibold hover:bg-surface-200 transition-colors">
              Cancelar
            </button>
            <button @click="confirmReject" :disabled="rejectReason.length < 20"
              class="flex-1 py-3 rounded-xl bg-red-500 hover:bg-red-600 text-white font-semibold transition-colors disabled:opacity-50 shadow-sm shadow-red-500/20">
              Confirmar Rechazo
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Modal Document Viewer -->
    <Transition enter-active-class="transition-opacity duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showDocModal" class="fixed inset-0 bg-surface-900/60 backdrop-blur-md flex items-center justify-center p-4 md:p-8 z-[60]">
        <div class="bg-white rounded-3xl shadow-2xl max-w-5xl w-full flex flex-col overflow-hidden">
          
          <!-- Header -->
          <div class="flex items-center justify-between p-5 border-b border-surface-100 bg-white shrink-0">
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 bg-surface-100 text-surface-600 rounded-full flex items-center justify-center">
                <i class="fas fa-file-pdf"></i>
              </div>
              <div>
                <h3 class="text-base font-bold text-surface-900">{{ currentDocTitle }}</h3>
                <p class="text-xs text-surface-500">Visor de Documento</p>
              </div>
            </div>
            <button @click="closeDocument" class="w-10 h-10 rounded-full bg-surface-100 text-surface-500 hover:text-surface-900 hover:bg-surface-200 transition-colors flex items-center justify-center">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <!-- Document Frame -->
          <div class="w-full h-[75vh] bg-surface-100">
            <iframe :src="currentDocUrl" class="w-full h-full border-none"></iframe>
          </div>
        </div>
      </div>
    </Transition>

  </main>
</template>
