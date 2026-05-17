<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api'
import { useAlerts } from '@/composables/useAlerts'

const router = useRouter()
const route = useRoute()
const { toastSuccess, toastError } = useAlerts()

// --- State Variables ---
const torneos = ref([])
const preRegistros = ref([])
const loadingTorneos = ref(false)
const loadingRegistros = ref(false)
const actionInProgress = ref(null) // Stores record ID currently being processed

// --- Filters ---
const selectedTorneoId = ref('')
const selectedStatus = ref('PENDIENTE')
const searchQuery = ref('')

// --- Pagination ---
const currentPage = ref(1)
const lastPage = ref(1)
const totalRecords = ref(0)

// --- Modals State ---
const showConfirmModal = ref(false)
const showRejectModal = ref(false)
const activeRecord = ref(null)
const rejectReason = ref('')

// --- Computed Properties ---
const filteredTorneos = computed(() => {
  // Only tournaments in status EN_INSCRIPCION
  return torneos.value.filter(t => t.estado === 'EN_INSCRIPCION' || t.estatus_torneo === 'EN_INSCRIPCION')
})

const currentTorneoName = computed(() => {
  const torneo = torneos.value.find(t => t.id_torneo === selectedTorneoId.value || t.id === selectedTorneoId.value)
  return torneo ? torneo.nombre_torneo : 'Selecciona un Torneo'
})

// --- API Calls ---

// Fetch all tournaments for the dropdown filter
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

    // Set selected tournament based on query param if available, otherwise use first active
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

// Fetch pre-registrations for selected tournament and status
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

// --- Watchers to refetch automatically ---
watch([selectedTorneoId, selectedStatus], () => {
  currentPage.value = 1
  fetchPreRegistros()
})

watch(currentPage, () => {
  fetchPreRegistros()
})

// --- Helpers ---
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

// Stream and open the PDF file in a new tab securely (including Auth token)
const openDocument = async (path, docLabel) => {
  if (!path) return
  try {
    const response = await api.get('/pre-registros/documento', {
      params: { path },
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank')
  } catch (err) {
    console.error(`Error loading document ${docLabel}:`, err)
    toastError(`No se pudo abrir el documento: ${docLabel}`)
  }
}

// --- Action Handlers ---

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
    
    // Optimistic / instant status update in the local array
    const recordIndex = preRegistros.value.findIndex(r => r.id === recordId)
    if (recordIndex !== -1) {
      preRegistros.value[recordIndex].estatus = 'APROBADO'
      // If we are filtering by PENDIENTE, we want to remove it from the visible list smoothly
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

    // Optimistic / instant status update in the local array
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

// Filtered pre-registrations by search query
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
  fetchTorneos()
})
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-20 font-sans text-surface-800">
    <div class="max-w-6xl mx-auto space-y-6">

      <!-- Header Section -->
      <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
          <button @click="router.push('/admin/tournaments')"
            class="w-11 h-11 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-600 hover:bg-surface-50 hover:text-primary-600 transition-all shadow-sm group">
            <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor"
              viewBox="0 0 24 24" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
          </button>
          <div>
            <h1 class="text-2xl font-black text-surface-900 tracking-tight">Bandeja de Pre-registros</h1>
            <p class="text-sm text-surface-500 font-medium">Revisa las solicitudes, inspecciona PDFs y gestiona aprobaciones.</p>
          </div>
        </div>

        <div class="bg-white px-4 py-2 border border-surface-200 rounded-xl shadow-sm text-right shrink-0">
          <span class="text-[10px] font-black uppercase tracking-wider text-surface-400 block">Torneo Seleccionado</span>
          <span class="text-sm font-extrabold text-primary-600 block">{{ currentTorneoName }}</span>
        </div>
      </header>

      <!-- Filter Panel -->
      <section class="bg-white rounded-2xl border border-surface-200 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          
          <!-- Tournament Select -->
          <div class="flex flex-col gap-1.5">
            <label for="torneo-select" class="text-[10px] font-black uppercase tracking-widest text-surface-400">Torneo en Inscripción</label>
            <div class="relative">
              <select id="torneo-select" v-model="selectedTorneoId" :disabled="loadingTorneos"
                class="w-full bg-surface-50 border border-surface-200 rounded-xl py-3 px-4 text-sm font-semibold cursor-pointer focus:outline-none focus:border-primary-500 transition-colors disabled:opacity-50 appearance-none">
                <option v-if="filteredTorneos.length === 0" value="">No hay torneos en inscripción</option>
                <option v-for="t in filteredTorneos" :key="t.id_torneo || t.id" :value="t.id_torneo || t.id">
                  {{ t.nombre_torneo }} ({{ formatText(t.disciplina) }})
                </option>
              </select>
              <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-surface-400 pointer-events-none"></i>
            </div>
          </div>

          <!-- Status Select -->
          <div class="flex flex-col gap-1.5">
            <label for="status-select" class="text-[10px] font-black uppercase tracking-widest text-surface-400">Estado de Solicitud</label>
            <div class="relative">
              <select id="status-select" v-model="selectedStatus"
                class="w-full bg-surface-50 border border-surface-200 rounded-xl py-3 px-4 text-sm font-semibold cursor-pointer focus:outline-none focus:border-primary-500 transition-colors appearance-none">
                <option value="PENDIENTE">Pendientes por revisar</option>
                <option value="APROBADO">Aprobados</option>
                <option value="RECHAZADO">Rechazados</option>
              </select>
              <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-surface-400 pointer-events-none"></i>
            </div>
          </div>

          <!-- Search Bar -->
          <div class="flex flex-col gap-1.5">
            <label for="search-input" class="text-[10px] font-black uppercase tracking-widest text-surface-400">Buscar Participante</label>
            <div class="relative flex items-center">
              <i class="fas fa-search absolute left-4 text-surface-400 pointer-events-none"></i>
              <input id="search-input" type="text" v-model="searchQuery" placeholder="Nombre o correo..."
                class="w-full bg-surface-50 border border-surface-200 rounded-xl py-3 pl-11 pr-4 text-sm font-semibold placeholder-surface-400 focus:outline-none focus:border-primary-500 transition-colors" />
            </div>
          </div>

        </div>
      </section>

      <!-- Main Loader / Error / Empty States -->
      <section v-if="loadingRegistros" class="flex flex-col items-center justify-center p-20 bg-white rounded-2xl border border-surface-200 shadow-sm">
        <i class="fas fa-circle-notch fa-spin text-4xl text-primary-600 mb-4"></i>
        <p class="text-sm font-extrabold uppercase tracking-widest text-surface-400">Cargando solicitudes...</p>
      </section>

      <section v-else-if="!selectedTorneoId" class="text-center p-12 bg-amber-50 rounded-2xl border border-amber-100">
        <i class="fas fa-exclamation-triangle text-4xl text-amber-500 mb-3"></i>
        <p class="text-amber-800 font-black">No hay torneos activos en fase de inscripción.</p>
        <p class="text-amber-600 text-sm mt-1">Habilita inscripciones en la pestaña de torneos primero.</p>
      </section>

      <section v-else-if="searchedPreRegistros.length === 0" class="flex flex-col items-center justify-center p-16 bg-white rounded-2xl border border-surface-200 shadow-sm text-center">
        <div class="w-16 h-16 rounded-full bg-surface-100 flex items-center justify-center text-surface-400 mb-4">
          <i class="fas fa-folder-open text-2xl"></i>
        </div>
        <h3 class="text-lg font-bold text-surface-900 mb-1">Sin solicitudes</h3>
        <p class="text-sm text-surface-500 max-w-sm">No se encontraron pre-registros con el estado seleccionado en este torneo.</p>
      </section>

      <!-- Pre-registrations Cards Grid -->
      <section v-else class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div v-for="record in searchedPreRegistros" :key="record.id"
            class="bg-white rounded-2xl border border-surface-200 shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col justify-between relative"
            :class="{ 'opacity-70 pointer-events-none': actionInProgress === record.id }">
            
            <!-- Loading Indicator Overlay per card -->
            <div v-if="actionInProgress === record.id" class="absolute inset-0 bg-white/50 backdrop-blur-xs flex items-center justify-center z-10">
              <i class="fas fa-circle-notch fa-spin text-2xl text-primary-600"></i>
            </div>

            <!-- Card Header -->
            <div class="p-6 pb-4 border-b border-surface-100 flex justify-between items-start gap-4">
              <div>
                <span class="px-2.5 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider mb-2 inline-block shadow-sm"
                  :class="record.tipo === 'INDIVIDUAL' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-purple-50 text-purple-600 border border-purple-100'">
                  {{ record.tipo }}
                </span>
                <h3 class="text-base font-extrabold text-surface-900 leading-snug">
                  {{ record.tipo === 'INDIVIDUAL' ? record.datos_participante?.nombre_completo : record.datos_participante?.nombre_equipo }}
                </h3>
                <p class="text-[11px] text-surface-400 font-mono mt-1">Solicitado el {{ formatDate(record.created_at) }}</p>
              </div>

              <!-- Status Badge -->
              <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border"
                :class="{
                  'bg-amber-50 text-amber-600 border-amber-200': record.estatus === 'PENDIENTE',
                  'bg-emerald-50 text-emerald-600 border-emerald-200': record.estatus === 'APROBADO',
                  'bg-red-50 text-red-600 border-red-200': record.estatus === 'RECHAZADO'
                }">
                {{ record.estatus === 'PENDIENTE' ? 'Pendiente' : (record.estatus === 'APROBADO' ? 'Aprobado — QR en proceso' : 'Rechazado') }}
              </span>
            </div>

            <!-- Card Body: Participants & Documents -->
            <div class="p-6 py-4 space-y-4 bg-surface-50/30 flex-grow">
              
              <!-- INDIVIDUAL DETAILS -->
              <div v-if="record.tipo === 'INDIVIDUAL'" class="space-y-3">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold shadow-sm">
                    {{ record.datos_participante?.nombre_completo?.charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <span class="block text-xs font-extrabold text-surface-700">{{ record.datos_participante?.nombre_completo }}</span>
                    <span class="block text-[11px] text-surface-500">{{ record.datos_participante?.correo }}</span>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                  <div class="bg-white p-2 rounded-xl border border-surface-200 text-center">
                    <span class="block text-[8px] font-black uppercase tracking-wider text-surface-400">Género</span>
                    <span class="text-xs font-bold text-surface-700">{{ record.datos_participante?.genero }}</span>
                  </div>
                  <div class="bg-white p-2 rounded-xl border border-surface-200 text-center">
                    <span class="block text-[8px] font-black uppercase tracking-wider text-surface-400">Ranking</span>
                    <span class="text-xs font-bold text-surface-700">{{ record.datos_participante?.ranking_declarado }}</span>
                  </div>
                </div>

                <!-- Document Links -->
                <div class="pt-3 border-t border-surface-100">
                  <span class="block text-[9px] font-black uppercase tracking-widest text-surface-400 mb-2">Documentación Adjunta</span>
                  <div class="flex flex-wrap gap-2">
                    <button v-if="record.urls_documentos?.ine_pdf" @click="openDocument(record.urls_documentos.ine_pdf, 'INE')"
                      class="px-3 py-1.5 bg-white border border-surface-200 hover:border-primary-500 rounded-xl text-xs font-bold text-surface-700 flex items-center gap-1.5 transition-colors shadow-xs cursor-pointer">
                      <i class="fas fa-file-pdf text-red-500"></i> INE
                    </button>
                    <button v-if="record.urls_documentos?.curp_pdf" @click="openDocument(record.urls_documentos.curp_pdf, 'CURP')"
                      class="px-3 py-1.5 bg-white border border-surface-200 hover:border-primary-500 rounded-xl text-xs font-bold text-surface-700 flex items-center gap-1.5 transition-colors shadow-xs cursor-pointer">
                      <i class="fas fa-file-pdf text-red-500"></i> CURP
                    </button>
                    <button v-if="record.urls_documentos?.responsiva_pdf" @click="openDocument(record.urls_documentos.responsiva_pdf, 'Responsiva')"
                      class="px-3 py-1.5 bg-white border border-surface-200 hover:border-primary-500 rounded-xl text-xs font-bold text-surface-700 flex items-center gap-1.5 transition-colors shadow-xs cursor-pointer">
                      <i class="fas fa-file-pdf text-red-500"></i> Carta Responsiva
                    </button>
                  </div>
                </div>
              </div>

              <!-- EQUIPO DETAILS -->
              <div v-else class="space-y-4">
                <span class="block text-[9px] font-black uppercase tracking-widest text-surface-400">Integrantes del Equipo</span>
                
                <div v-for="(member, idx) in record.datos_participante?.integrantes" :key="idx" class="bg-white p-3 rounded-xl border border-surface-200 space-y-2 shadow-xs">
                  <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                      <div class="w-7 h-7 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center text-[10px] font-bold shadow-sm">
                        {{ idx + 1 }}
                      </div>
                      <div>
                        <span class="block text-xs font-extrabold text-surface-700">{{ member.nombre_completo }}</span>
                        <span class="block text-[10px] text-surface-500">{{ member.correo }}</span>
                      </div>
                    </div>
                    <span class="text-[10px] font-semibold text-surface-500">Gen: {{ member.genero }} | Rank: {{ member.ranking_declarado }}</span>
                  </div>

                  <!-- Member Documents -->
                  <div class="pt-2 border-t border-surface-100/50 flex flex-wrap gap-1.5">
                    <button v-if="record.urls_documentos?.[member.correo]?.ine_pdf" @click="openDocument(record.urls_documentos[member.correo].ine_pdf, `INE - ${member.nombre_completo}`)"
                      class="px-2.5 py-1 bg-surface-50 border border-surface-200 hover:border-primary-500 rounded-lg text-[10px] font-bold text-surface-600 flex items-center gap-1 transition-colors cursor-pointer">
                      <i class="fas fa-file-pdf text-red-500"></i> INE
                    </button>
                    <button v-if="record.urls_documentos?.[member.correo]?.curp_pdf" @click="openDocument(record.urls_documentos[member.correo].curp_pdf, `CURP - ${member.nombre_completo}`)"
                      class="px-2.5 py-1 bg-surface-50 border border-surface-200 hover:border-primary-500 rounded-lg text-[10px] font-bold text-surface-600 flex items-center gap-1 transition-colors cursor-pointer">
                      <i class="fas fa-file-pdf text-red-500"></i> CURP
                    </button>
                    <button v-if="record.urls_documentos?.[member.correo]?.responsiva_pdf" @click="openDocument(record.urls_documentos[member.correo].responsiva_pdf, `Responsiva - ${member.nombre_completo}`)"
                      class="px-2.5 py-1 bg-surface-50 border border-surface-200 hover:border-primary-500 rounded-lg text-[10px] font-bold text-surface-600 flex items-center gap-1 transition-colors cursor-pointer">
                      <i class="fas fa-file-pdf text-red-500"></i> Carta
                    </button>
                  </div>
                </div>
              </div>

              <!-- Rejection Reason if rejected -->
              <div v-if="record.estatus === 'RECHAZADO' && record.motivo_rechazo" class="p-3 bg-red-50 rounded-xl border border-red-100 mt-2">
                <span class="block text-[8px] font-black uppercase tracking-widest text-red-500 mb-1">Motivo del Rechazo</span>
                <p class="text-xs text-red-700 leading-normal font-semibold">{{ record.motivo_rechazo }}</p>
              </div>

            </div>

            <!-- Card Actions footer (only if status is PENDIENTE) -->
            <div v-if="record.estatus === 'PENDIENTE'" class="px-6 py-4 bg-surface-50/50 border-t border-surface-100 flex gap-3">
              <button @click="initiateReject(record)" :disabled="actionInProgress !== null"
                class="flex-1 py-2.5 px-4 bg-white border border-red-200 text-red-600 hover:bg-red-50 rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50">
                <i class="fas fa-times-circle"></i> Rechazar
              </button>
              <button @click="initiateApprove(record)" :disabled="actionInProgress !== null"
                class="flex-1 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors cursor-pointer flex items-center justify-center gap-1.5 shadow-sm shadow-emerald-600/10 disabled:opacity-50">
                <i class="fas fa-check-circle"></i> Aprobar
              </button>
            </div>

          </div>
        </div>

        <!-- Pagination Controls -->
        <footer v-if="lastPage > 1" class="flex justify-between items-center gap-4 pt-4">
          <span class="text-xs font-bold text-surface-500">
            Mostrando {{ preRegistros.length }} de {{ totalRecords }} solicitudes (Pág. {{ currentPage }} de {{ lastPage }})
          </span>
          
          <div class="flex items-center gap-2">
            <button @click="currentPage--" :disabled="currentPage === 1"
              class="w-10 h-10 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-650 hover:bg-surface-50 transition-colors disabled:opacity-50 shadow-xs cursor-pointer">
              <i class="fas fa-chevron-left text-xs"></i>
            </button>
            <button @click="currentPage++" :disabled="currentPage === lastPage"
              class="w-10 h-10 bg-white border border-surface-200 rounded-xl flex items-center justify-center text-surface-650 hover:bg-surface-50 transition-colors disabled:opacity-50 shadow-xs cursor-pointer">
              <i class="fas fa-chevron-right text-xs"></i>
            </button>
          </div>
        </footer>
      </section>

    </div>

    <!-- ── MODALS ── -->

    <!-- Confirmation Dialog: Approval -->
    <Transition enter-active-class="transition-opacity duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showConfirmModal" class="fixed inset-0 bg-surface-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-fade-in">
        <div class="bg-white rounded-3xl border border-surface-200 shadow-2xl max-w-md w-full overflow-hidden p-6 space-y-4">
          
          <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
            <i class="fas fa-check-circle"></i>
          </div>

          <div class="space-y-1.5">
            <h3 class="text-lg font-black text-surface-900 leading-tight">Confirmar Aprobación</h3>
            <p class="text-sm text-surface-500 font-medium leading-relaxed">
              ¿Estás seguro de que deseas aprobar el pre-registro de 
              <strong class="text-surface-900 font-extrabold">{{ activeRecord?.tipo === 'INDIVIDUAL' ? activeRecord?.datos_participante?.nombre_completo : activeRecord?.datos_participante?.nombre_equipo }}</strong>?
            </p>
          </div>

          <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-100 text-xs text-emerald-800 font-bold leading-normal flex items-start gap-2">
            <i class="fas fa-exclamation-triangle text-emerald-600 mt-0.5 shrink-0"></i>
            <span>Esta acción es irreversible. Al confirmar, el participante quedará inscrito y recibirá su código QR de acceso automáticamente.</span>
          </div>

          <div class="flex gap-3 pt-2 justify-end">
            <button @click="showConfirmModal = false" class="px-5 py-2.5 rounded-xl border border-surface-200 bg-white text-xs font-extrabold text-surface-650 hover:bg-surface-50 transition-colors cursor-pointer">
              Cancelar
            </button>
            <button @click="confirmApprove" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-extrabold text-white transition-colors cursor-pointer shadow-md shadow-emerald-600/10">
              Sí, Aprobar
            </button>
          </div>

        </div>
      </div>
    </Transition>

    <!-- Modal Form: Rejection -->
    <Transition enter-active-class="transition-opacity duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showRejectModal" class="fixed inset-0 bg-surface-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-fade-in">
        <div class="bg-white rounded-3xl border border-surface-200 shadow-2xl max-w-lg w-full overflow-hidden p-6 space-y-4">
          
          <div class="w-12 h-12 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center text-xl shadow-xs">
            <i class="fas fa-times-circle"></i>
          </div>

          <div class="space-y-1.5">
            <h3 class="text-lg font-black text-surface-900 leading-tight">Rechazar Pre-registro</h3>
            <p class="text-sm text-surface-500 font-medium leading-relaxed">
              Explica detalladamente la razón de rechazo para 
              <strong class="text-surface-900 font-extrabold">{{ activeRecord?.tipo === 'INDIVIDUAL' ? activeRecord?.datos_participante?.nombre_completo : activeRecord?.datos_participante?.nombre_equipo }}</strong>.
            </p>
          </div>

          <!-- Reject Form Input -->
          <div class="space-y-1">
            <label for="reject-textarea" class="text-[9px] font-black uppercase tracking-widest text-surface-400">Motivo del rechazo</label>
            <textarea id="reject-textarea" v-model="rejectReason" rows="4" placeholder="Escribe el motivo del rechazo del documento..."
              class="w-full bg-surface-50 border border-surface-200 rounded-2xl p-4 text-sm font-semibold focus:outline-none focus:border-red-500 transition-colors placeholder-surface-400 resize-none"></textarea>
            
            <div class="flex justify-between items-center text-xs font-semibold mt-1" :class="rejectReason.length < 20 ? 'text-red-500' : 'text-surface-400'">
              <span v-if="rejectReason.length < 20">Se requieren al menos 20 caracteres</span>
              <span v-else>Cumple con el tamaño mínimo requerido</span>
              <span>{{ rejectReason.length }} caracteres</span>
            </div>
          </div>

          <div class="flex gap-3 pt-2 justify-end">
            <button @click="showRejectModal = false" class="px-5 py-2.5 rounded-xl border border-surface-200 bg-white text-xs font-extrabold text-surface-650 hover:bg-surface-50 transition-colors cursor-pointer">
              Cancelar
            </button>
            <button @click="confirmReject" :disabled="rejectReason.length < 20"
              class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-xs font-extrabold text-white transition-colors cursor-pointer disabled:opacity-50 shadow-md shadow-red-600/10">
              Confirmar Rechazo
            </button>
          </div>

        </div>
      </div>
    </Transition>

  </main>
</template>
