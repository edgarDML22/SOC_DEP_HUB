<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useSocioStore } from '@/stores/admin/socioStore'
import { storeToRefs } from 'pinia'
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import SearchInput from '@/components/gerente/ui/SearchInput.vue'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import ExportCsvButton from '@/components/gerente/ui/ExportCsvButton.vue'
import PenalizacionModal from '@/components/admin/socio/PenalizacionModal.vue'
import { IconFilter, IconChevronDown, IconAlertCircle, IconWarning, IconHistory, IconLayers } from '@/components/icons'
import EstatusCuentaModal from '@/components/admin/socio/EstatusCuentaModal.vue'
import api from '@/services/api'
import BaseChart from '@/components/admin/BaseChart.vue'

const router = useRouter()
const socioStore = useSocioStore()

const { socios, isLoading, error: errorMsg, listFilters } = storeToRefs(socioStore)
const { fetchSocios, fetchSocioDetails } = socioStore

const OPT_TIPO = [
  { label: 'Todos los tipos', value: null },
  { label: 'Accionista', value: 'ACCIONISTA' },
  { label: 'Rentista', value: 'RENTISTA' },
]
const OPT_MODALIDAD = [
  { label: 'Todas las modalidades', value: null },
  { label: 'Individual', value: 'INDIVIDUAL' },
  { label: 'Familiar', value: 'FAMILIAR' },
]
const OPT_GENERO = [
  { label: 'Todos los géneros', value: null },
  { label: 'Masculino', value: 'M' },
  { label: 'Femenino', value: 'F' },
]
const OPT_ESTATUS_CUENTA = [
  { label: 'Todos los estatus', value: null },
  { label: 'Al Corriente', value: 'AL_CORRIENTE' },
  { label: 'Moroso', value: 'MOROSO' },
  { label: 'Suspendido', value: 'SUSPENDIDO' },
]
const OPT_ESTATUS_PENALIZACION = [
  { label: 'Todos', value: null },
  { label: 'Sin Penalización', value: 'SIN_PENALIZACION' },
  { label: 'Penalización Reservas', value: 'PENALIZADO_RESERVA' },
  { label: 'Penalización Ludoteca', value: 'PENALIZADO_LUDOTECA' },
  { label: 'Penalización Ambos', value: 'PENALIZADO_AMBOS' },
]

const filteredSocios = computed(() => {
  let r = socios.value
  const f = listFilters.value

  if (f.search) {
    const q = f.search.toLowerCase()
    r = r.filter(s =>
      s.nombre_completo?.toLowerCase().includes(q) ||
      String(s.numero_accion ?? '').includes(q)
    )
  }
  if (f.tipo) r = r.filter(s => s.tipo_socio === f.tipo)
  if (f.modalidad) r = r.filter(s => s.modalidad_plan === f.modalidad)
  if (f.genero) r = r.filter(s => s.genero === f.genero)
  if (f.estatus) r = r.filter(s => s.estatus_cuenta === f.estatus)
  if (f.penalizacion) r = r.filter(s => (s.estatus_penalizacion ?? 'SIN_PENALIZACION') === f.penalizacion)

  // Ordenar alfabéticamente por nombre
  return [...r].sort((a, b) => (a.nombre_completo || '').localeCompare(b.nombre_completo || ''))
})

const currentPage = ref(1)
const itemsPerPage = ref(15)

const lastPage = computed(() => {
  return Math.max(1, Math.ceil(filteredSocios.value.length / itemsPerPage.value))
})

const paginatedSocios = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredSocios.value.slice(start, end)
})

watch(filteredSocios, () => {
  currentPage.value = 1
})

const hasActiveFilters = computed(() =>
  listFilters.value.search || listFilters.value.tipo || listFilters.value.modalidad || listFilters.value.genero ||
  listFilters.value.estatus || listFilters.value.penalizacion
)

const clearFilters = () => {
  listFilters.value.search = ''
  listFilters.value.tipo = listFilters.value.modalidad = listFilters.value.genero =
    listFilters.value.estatus = listFilters.value.penalizacion = null
}

// ── AVATAR ────────────────────────────────────────────────────
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

// ── ACCIONES DE TABLA ─────────────────────────────────────────
const buildMenuItems = (socio) => [
  {
    label: 'Ver perfil completo',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
             <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
           </svg>`,
    action: () => {
      if (typeof socioStore.setCurrentSocio === 'function') {
        socioStore.setCurrentSocio(socio);
      } else {
        socioStore.currentSocio = socio;
      }
      router.push({ path: `/admin/socios/${socio.id_socio}` });
    },
  },
  {
    label: 'Gestionar penalizaciones',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M12 9v4M12 17h.01"/>
             <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
           </svg>`,
    action: () => openPenalty(socio),
  },
  ...(socio.modalidad_plan === 'FAMILIAR' ? [{
    label: 'Gestionar Miembros Familiares',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M17 21v-2a4 4 0 0 0-3-3.87M9 21v-2a4 4 0 0 0-3-3.87"/>
             <circle cx="9" cy="7" r="4"/><circle cx="17" cy="7" r="4"/>
           </svg>`,
    action: () => { router.push(`/admin/socios/${socio.id_socio}/familiares`) },
  }] : []),
  {
    label: 'Invitados',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
             <circle cx="9" cy="7" r="4"/>
             <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
           </svg>`,
    action: () => { router.push({ name: 'admin-socio-invitados', params: { id: socio.id_socio } }) },
  },
  {
    label: socio.estatus_cuenta === 'SUSPENDIDO' ? 'Reactivar cuenta' : 'Suspender cuenta',
    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
             <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
           </svg>`,
    action: () => openEstatus(socio),
  },
]

// ── MODALES ───────────────────────────────────────────────────
const showPenaltyModal = ref(false)
const showFamilyModal = ref(false)
const showEstatusModal = ref(false)
const selectedSocio = ref(null)
const isModalLoading = ref(false)

const openPenalty = (socio) => {
  selectedSocio.value = socio
  showPenaltyModal.value = true
}

const openFamily = async (socio) => {
  selectedSocio.value = socio
  showFamilyModal.value = true

  // Si ya tenemos la info en caché, no mostramos el spinner global ni bloqueamos
  const cached = socioStore.getSocioById(socio.id_socio)
  if (!cached?.miembros_familiares) {
    isModalLoading.value = true
  }

  try {
    // Silent fetch: no dispara el isLoading global del store
    await fetchSocioDetails(socio.id_socio, true, true)
    selectedSocio.value = socioStore.getSocioById(socio.id_socio) ?? socio
  } finally {
    isModalLoading.value = false
  }
}


const openEstatus = (socio) => {
  selectedSocio.value = socio
  showEstatusModal.value = true
}

// ── SOCIOS BI & ANALYTICS (Pestañas) ──────────────────────────
const activeTab = ref('register')
const isDemographicsLoading = ref(true)
const demographicsData = ref(null)

const fetchDemographics = async () => {
  try {
    isDemographicsLoading.value = true
    const res = await api.get('/admin/bi/socios')
    if (res.data && res.data.success) {
      demographicsData.value = res.data.data
    }
  } catch (error) {
    console.error("Error cargando demografía de socios:", error)
  } finally {
    isDemographicsLoading.value = false
  }
}

watch(activeTab, (newTab) => {
  if (newTab === 'stats' && !demographicsData.value) {
    fetchDemographics()
  }
})

// Gráfica A: Segmentación (Dona)
const chartTipoMembresia = computed(() => {
  if (!demographicsData.value?.tipo_membresia) return null
  return {
    labels: demographicsData.value.tipo_membresia.labels,
    datasets: [{
      data: demographicsData.value.tipo_membresia.data,
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        const colorsMap = [
          ['#60a5fa', '#2563eb'], // Accionista: Blue/Indigo gradient
          ['#34d399', '#059669']  // Rentista: Emerald/Teal gradient
        ];
        const pair = colorsMap[context.dataIndex % colorsMap.length];
        if (!chartArea) return pair[0];
        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        gradient.addColorStop(0, pair[1]);
        gradient.addColorStop(1, pair[0]);
        return gradient;
      },
      borderWidth: 2,
      borderColor: '#ffffff',
      hoverOffset: 12
    }]
  }
})

// Gráfica B: Planes (Doughnut/Pie)
const chartModalidadPlan = computed(() => {
  if (!demographicsData.value?.modalidad_plan) return null
  return {
    labels: demographicsData.value.modalidad_plan.labels,
    datasets: [{
      data: demographicsData.value.modalidad_plan.data,
      backgroundColor: (context) => {
        const { ctx, chartArea } = context.chart;
        const colorsMap = [
          ['#c084fc', '#7c3aed'], // Individual: Purple/Violet gradient
          ['#f472b6', '#db2777']  // Familiar: Pink/Rose gradient
        ];
        const pair = colorsMap[context.dataIndex % colorsMap.length];
        if (!chartArea) return pair[0];
        const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
        gradient.addColorStop(0, pair[1]);
        gradient.addColorStop(1, pair[0]);
        return gradient;
      },
      borderWidth: 2,
      borderColor: '#ffffff',
      hoverOffset: 12
    }]
  }
})

// Gráfica C: Demografía (Barras Agrupadas)
const chartDemografia = computed(() => {
  if (!demographicsData.value?.demografia) return null
  const rangos = ['Menores 18', '18-29', '30-49', '50-64', '65+']
  
  const dataM = rangos.map(r => {
    const item = demographicsData.value.demografia.find(d => d.rango_edad === r && d.genero === 'M')
    return item ? parseInt(item.total) : 0
  })
  
  const dataF = rangos.map(r => {
    const item = demographicsData.value.demografia.find(d => d.rango_edad === r && d.genero === 'F')
    return item ? parseInt(item.total) : 0
  })

  return {
    labels: rangos,
    datasets: [
      {
        label: 'Masculino',
        data: dataM,
        backgroundColor: (context) => {
          const { ctx, chartArea } = context.chart;
          if (!chartArea) return '#2563eb';
          const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
          gradient.addColorStop(0, '#2563eb');
          gradient.addColorStop(1, '#60a5fa');
          return gradient;
        },
        borderColor: '#1d4ed8',
        borderWidth: 1,
        borderRadius: 6
      },
      {
        label: 'Femenino',
        data: dataF,
        backgroundColor: (context) => {
          const { ctx, chartArea } = context.chart;
          if (!chartArea) return '#e11d48';
          const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
          gradient.addColorStop(0, '#e11d48');
          gradient.addColorStop(1, '#fda4af');
          return gradient;
        },
        borderColor: '#be123c',
        borderWidth: 1,
        borderRadius: 6
      }
    ]
  }
})

const optionsDemografia = {
  plugins: { legend: { display: true } },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { stepSize: 1 }
    }
  }
}

// Opciones de Gráficas circulares que desactivan el click del legend para evitar filtrado y ocultan los ejes X/Y
const chartOptionsPie = {
  plugins: {
    legend: {
      position: 'bottom',
      onClick: () => {} // Al establecer una función vacía desactivamos el filtrado por defecto de ChartJS
    }
  },
  scales: {
    x: { display: false },
    y: { display: false }
  }
}

const totalMembresias = computed(() => {
  return demographicsData.value?.tipo_membresia?.data?.reduce((a, b) => a + Number(b), 0) || 0;
});

const individualMembresias = computed(() => {
  const labels = demographicsData.value?.tipo_membresia?.labels || [];
  const data = demographicsData.value?.tipo_membresia?.data || [];
  const colors = ['#2563eb', '#10b981']; 
  return labels.map((label, idx) => ({
    label: label === 'ACCIONISTA' ? 'Accionista' : (label === 'RENTISTA' ? 'Rentista' : label),
    value: data[idx] || 0,
    color: colors[idx % colors.length]
  })).filter(item => item.value > 0);
});

const totalPlanes = computed(() => {
  return demographicsData.value?.modalidad_plan?.data?.reduce((a, b) => a + Number(b), 0) || 0;
});

const individualPlanes = computed(() => {
  const labels = demographicsData.value?.modalidad_plan?.labels || [];
  const data = demographicsData.value?.modalidad_plan?.data || [];
  const colors = ['#7c3aed', '#ec4899'];
  return labels.map((label, idx) => ({
    label: label === 'INDIVIDUAL' ? 'Individual' : (label === 'FAMILIAR' ? 'Familiar' : label),
    value: data[idx] || 0,
    color: colors[idx % colors.length]
  })).filter(item => item.value > 0);
});

// ── INIT ──────────────────────────────────────────────────────
onMounted(fetchSocios)
</script>

<template>
  <main class="min-h-screen bg-surface-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-7xl mx-auto">

      <!-- CABECERA -->
      <AdminPageHeader title="Socios Titulares"
        subtitle="Gestión de membresías, penalizaciones e información de cuenta."
        class="mb-8"
      >
        <span class="text-sm font-bold text-surface-500">
          {{ filteredSocios.length }}
          <span class="font-medium text-surface-400">de {{ socios.length }} socios</span>
        </span>
      </AdminPageHeader>

      <!-- Segmented Control (Pills) -->
      <div class="flex p-1.5 bg-surface-100/50 rounded-2xl w-full mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-8">
        <button @click="activeTab = 'register'"
          class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all duration-200 ease-out whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none active:scale-[0.99] border-none cursor-pointer font-sans"
          :class="activeTab === 'register'
            ? 'bg-surface-900 text-white font-black rounded-xl shadow-md transform scale-[1.02]'
            : 'text-surface-500 font-bold hover:bg-white hover:text-surface-700 rounded-xl'">
          <component :is="IconHistory" class="w-5 h-5 shrink-0" />
          Registros
        </button>
        <button @click="activeTab = 'stats'"
          class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all duration-200 ease-out whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none active:scale-[0.99] border-none cursor-pointer font-sans"
          :class="activeTab === 'stats'
            ? 'bg-surface-900 text-white font-black rounded-xl shadow-md transform scale-[1.02]'
            : 'text-surface-500 font-bold hover:bg-white hover:text-surface-700 rounded-xl'">
          <component :is="IconLayers" class="w-5 h-5 shrink-0" />
          Estadísticas
        </button>
      </div>

      <!-- Área de Contenido -->
      <div class="bg-white rounded-[2.2rem] border border-surface-200/80 shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 md:p-8 min-h-[400px] relative overflow-hidden">
        <transition name="fade" mode="out-in">
          
          <!-- PESTAÑA: ESTADÍSTICAS DEMOGRÁFICAS -->
          <div v-if="activeTab === 'stats'" key="stats" class="space-y-8">
            <div class="py-4">
              <div v-if="isDemographicsLoading" class="flex flex-col items-center justify-center py-12">
                <LoadingSpinner />
                <span class="text-xs font-bold text-surface-400 mt-2">Cargando datos demográficos…</span>
              </div>
              
              <div v-else-if="demographicsData" class="space-y-8">
                <!-- Fila 1: Segmentación & Planes -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                  <!-- Gráfico A: Segmentación de Membresías -->
                  <div class="bg-white border border-surface-200 rounded-[2.5rem] p-8 flex flex-col justify-between hover:shadow-md transition-all duration-300 shadow-sm min-h-[360px]">
                    <div>
                      <h4 class="text-xs font-black text-surface-900 mb-4 uppercase tracking-wider">Accionistas vs Rentistas</h4>
                      <div class="h-[220px] flex items-center justify-center">
                        <BaseChart v-if="chartTipoMembresia" type="doughnut" :data="chartTipoMembresia" :options="chartOptionsPie" />
                      </div>
                    </div>
                    <!-- Desglose de Totales -->
                    <div class="mt-4 border-t border-surface-200 pt-4" v-if="individualMembresias.length">
                      <div class="flex items-center justify-between mb-2">
                        <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Desglose</span>
                        <span class="text-xs font-bold text-surface-500 bg-white px-3 py-1 rounded-full border border-surface-200 shadow-2xs">
                          Total: <span class="text-surface-900 font-black">{{ totalMembresias }}</span>
                        </span>
                      </div>
                      <div class="flex gap-4">
                        <div v-for="(val, idx) in individualMembresias" :key="idx" class="flex items-center gap-1.5 text-xs font-bold text-surface-600">
                          <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: val.color }"></span>
                          <span>{{ val.label }}: <span class="text-surface-900 font-black">{{ val.value }}</span></span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Gráfico B: Modalidad de Planes -->
                  <div class="bg-white border border-surface-200 rounded-[2.5rem] p-8 flex flex-col justify-between hover:shadow-md transition-all duration-300 shadow-sm min-h-[360px]">
                    <div>
                      <h4 class="text-xs font-black text-surface-900 mb-4 uppercase tracking-wider">Planes Individuales vs Familiares</h4>
                      <div class="h-[220px] flex items-center justify-center">
                        <BaseChart v-if="chartModalidadPlan" type="pie" :data="chartModalidadPlan" :options="chartOptionsPie" />
                      </div>
                    </div>
                    <!-- Desglose de Totales -->
                    <div class="mt-4 border-t border-surface-200 pt-4" v-if="individualPlanes.length">
                      <div class="flex items-center justify-between mb-2">
                        <span class="text-[9px] font-black text-surface-400 uppercase tracking-widest">Desglose</span>
                        <span class="text-xs font-bold text-surface-500 bg-white px-3 py-1 rounded-full border border-surface-200 shadow-2xs">
                          Total: <span class="text-surface-900 font-black">{{ totalPlanes }}</span>
                        </span>
                      </div>
                      <div class="flex gap-4">
                        <div v-for="(val, idx) in individualPlanes" :key="idx" class="flex items-center gap-1.5 text-xs font-bold text-surface-600">
                          <span class="w-2 h-2 rounded-full shrink-0" :style="{ backgroundColor: val.color }"></span>
                          <span>{{ val.label }}: <span class="text-surface-900 font-black">{{ val.value }}</span></span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Fila 2: Distribución por Edad y Género -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                  <div class="bg-white border border-surface-200 rounded-[2.5rem] p-8 flex flex-col hover:shadow-md transition-all duration-300 shadow-sm min-h-[360px] lg:col-span-2">
                    <h4 class="text-xs font-black text-surface-900 mb-4 uppercase tracking-wider">Distribución por Edad y Género</h4>
                    <div class="flex-1 min-h-[240px]">
                      <BaseChart v-if="chartDemografia" type="bar" :data="chartDemografia" :options="optionsDemografia" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- PESTAÑA: REGISTROS -->
          <div v-else key="register" class="flex flex-col gap-6">

            <!-- BARRA DE FILTROS -->
            <div class="bg-white p-6 rounded-3xl border border-surface-200 shadow-sm space-y-6">
              <div class="flex flex-col gap-2">
                <SearchInput v-model="listFilters.search" placeholder="Buscar por nombre o número de acción…" />
              </div>
              
              <div class="flex flex-col lg:flex-row gap-4 items-end">
                <div class="flex-1 w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                  <div class="flex flex-col gap-1.5 justify-end">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Tipo</label>
                    <div class="relative">
                      <IconFilter
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                      <select v-model="listFilters.tipo"
                        class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
                        <option v-for="opt in OPT_TIPO" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                      </select>
                      <IconChevronDown
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                  </div>
                  
                  <div class="flex flex-col gap-1.5 justify-end">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Modalidad</label>
                    <div class="relative">
                      <IconFilter
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                      <select v-model="listFilters.modalidad"
                        class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
                        <option v-for="opt in OPT_MODALIDAD" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                      </select>
                      <IconChevronDown
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                  </div>
                  
                  <div class="flex flex-col gap-1.5 justify-end">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Género</label>
                    <div class="relative">
                      <IconFilter
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                      <select v-model="listFilters.genero"
                        class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
                        <option v-for="opt in OPT_GENERO" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                      </select>
                      <IconChevronDown
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                  </div>
                  
                  <div class="flex flex-col gap-1.5 justify-end">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estatus Cuenta</label>
                    <div class="relative">
                      <IconAlertCircle
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                      <select v-model="listFilters.estatus"
                        class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
                        <option v-for="opt in OPT_ESTATUS_CUENTA" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                      </select>
                      <IconChevronDown
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                  </div>
                  
                  <div class="flex flex-col gap-1.5 justify-end">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estatus Penalización</label>
                    <div class="relative">
                      <IconWarning
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                      <select v-model="listFilters.penalizacion"
                        class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all cursor-pointer">
                        <option v-for="opt in OPT_ESTATUS_PENALIZACION" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                      </select>
                      <IconChevronDown
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                  </div>
                </div>

                <div class="shrink-0 w-full lg:w-auto flex items-center gap-4 justify-end">
                  <Transition enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 translate-x-4" enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 translate-x-4">
                    <button v-if="hasActiveFilters" @click="clearFilters"
                      class="text-xs font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1.5 transition-colors px-2 py-1">
                      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M18 6L6 18M6 6l12 12" />
                      </svg>
                      Limpiar filtros
                    </button>
                  </Transition>

                  <ExportCsvButton :data="filteredSocios" filename="socios-titulares"
                    :columns="[
                      { label: 'ID Socio', field: 'id_socio' },
                      { label: 'Número Acción', field: 'numero_accion' },
                      { label: 'Nombre Completo', field: 'nombre_completo' },
                      { label: 'Tipo', field: 'tipo_socio' },
                      { label: 'Modalidad', field: 'modalidad_plan' },
                      { label: 'Estatus', field: 'estatus_cuenta' }
                    ]"
                    class="w-full md:w-auto" />
                </div>
              </div>
            </div>

            <!-- TABLA -->
            <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden min-h-96 flex flex-col">

              <!-- Estado: cargando -->
              <TableSkeleton v-if="isLoading" :rows="6" :columns="6" :has-avatar="true" />

              <!-- Estado: error -->
              <div v-else-if="errorMsg" class="p-8 text-center text-red-700 font-semibold text-sm">
                {{ errorMsg }}
              </div>

              <!-- Estado: vacío -->
              <div v-else-if="filteredSocios.length === 0" class="p-16 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 rounded-2xl bg-surface-100 flex items-center justify-center mb-4">
                  <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                </div>
                <h3 class="text-base font-black text-surface-900">Sin resultados</h3>
                <p class="text-sm text-surface-500 mt-1 max-w-xs">No se encontraron socios con los filtros aplicados.</p>
                <button @click="clearFilters" class="mt-4 text-sm font-bold text-primary-600 hover:underline">
                  Limpiar filtros
                </button>
              </div>

              <!-- Tabla con datos -->
              <div v-else class="overflow-x-auto flex-1">
                <table class="w-full text-sm text-left text-slate-600">
                  <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
                    <tr>
                      <th scope="col" class="px-6 py-4 text-left font-extrabold rounded-tl-2xl">Socio / Acción</th>
                      <th scope="col" class="px-6 py-4 text-left font-extrabold">Tipo / Plan</th>
                      <th scope="col" class="px-6 py-4 text-left font-extrabold hidden sm:table-cell">Género</th>
                      <th scope="col" class="px-6 py-4 text-left font-extrabold">Estatus Cuenta</th>
                      <th scope="col" class="px-6 py-4 text-left font-extrabold hidden md:table-cell">Estatus Penalización</th>
                      <th scope="col" class="px-6 py-4 text-right font-extrabold rounded-tr-2xl">Acciones</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-surface-100">
                    <tr v-for="(socio, idx) in paginatedSocios" :key="socio.id_socio"
                      v-memo="[socio.estatus_cuenta, socio.estatus_penalizacion, socio.nombre_completo, socio.tipo_socio, socio.modalidad_plan, socio.genero]"
                      class="bg-white border-b border-surface-100 hover:bg-surface-50/50 transition-colors group animate-row-in"
                      :style="{ animationDelay: `${idx * 30}ms` }">
                      <!-- Nombre + avatar + Acción agrupados -->
                      <td class="px-6 py-4 first:last:rounded-bl-2xl">
                        <div class="flex items-center gap-3">
                          <div class="w-9 h-9 rounded-xl bg-linear-to-br flex items-center justify-center
                                   text-white font-black text-xs shrink-0 shadow-sm"
                            :class="avatarGradient(socio.nombre_completo)">
                            {{ initials(socio.nombre_completo) }}
                          </div>
                          <div class="flex flex-col min-w-0">
                            <span class="font-bold text-slate-800 truncate max-w-[180px]">
                              {{ socio.nombre_completo }}
                            </span>
                            <span class="text-slate-500 text-xs mt-0.5 font-semibold font-sans tracking-wide">
                              Acción: {{ socio.numero_accion }}
                            </span>
                          </div>
                        </div>
                      </td>
                      <!-- Tipo / Plan agrupados -->
                      <td class="px-6 py-4">
                        <div class="flex flex-col gap-1 items-start">
                          <BadgeStatus :status="socio.tipo_socio" />
                          <BadgeStatus :status="socio.modalidad_plan" />
                        </div>
                      </td>
                      <!-- Género -->
                      <td class="px-6 py-4 hidden sm:table-cell">
                        <BadgeStatus :status="socio.genero" />
                      </td>
                      <!-- Estatus Cuenta -->
                      <td class="px-6 py-4" @click.stop>
                        <BadgeStatus :status="socio.estatus_cuenta" />
                      </td>
                      <!-- Estatus Penalización -->
                      <td class="px-6 py-4 hidden md:table-cell" @click.stop>
                        <BadgeStatus :status="socio.estatus_penalizacion ?? 'SIN_PENALIZACION'" />
                      </td>
                      <!-- Menú acciones -->
                      <td class="px-6 py-4 text-right last:last:rounded-br-2xl" @click.stop>
                        <ActionMenu :items="buildMenuItems(socio)" align="right" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Paginación -->
              <div v-if="!isLoading && filteredSocios.length > 0" class="px-6 py-3 bg-slate-900 border-t border-slate-700 flex items-center justify-between text-xs text-slate-400 font-bold rounded-b-2xl mt-auto">
                <span class="tabular-nums text-slate-300">
                  {{ filteredSocios.length }} {{ filteredSocios.length === 1 ? 'socio' : 'socios' }}
                  <span class="text-slate-600 mx-1">·</span>
                  página <span class="text-white">{{ currentPage }}</span> de <span class="text-white">{{ lastPage }}</span>
                </span>
                <div class="flex items-center gap-2">
                  <button
                    @click="currentPage--"
                    :disabled="currentPage <= 1"
                    class="w-7 h-7 rounded-lg border border-slate-700 bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-slate-700 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                  </button>
                  <span class="tabular-nums text-white font-black">{{ currentPage }}</span>
                  <button
                    @click="currentPage++"
                    :disabled="currentPage >= lastPage"
                    class="w-7 h-7 rounded-lg border border-slate-700 bg-slate-800 flex items-center justify-center text-slate-400 hover:bg-slate-700 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

        </transition>
      </div>

    </div><!-- /max-w -->

    <!-- ══════════════════════════════════════════════════════════
         MODAL: PENALIZACIONES
    ══════════════════════════════════════════════════════════ -->
    <PenalizacionModal v-model="showPenaltyModal" :socio="selectedSocio" />

    <!-- ══════════════════════════════════════════════════════════
         MODAL: ESTATUS DE CUENTA
    ══════════════════════════════════════════════════════════ -->
    <EstatusCuentaModal v-model="showEstatusModal" :socio="selectedSocio" />

    <!-- ══════════════════════════════════════════════════════════
         MODAL: MIEMBROS FAMILIARES
    ══════════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0"
        enter-to-class="opacity-100" leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showFamilyModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm"
          @click.self="showFamilyModal = false">
          <div class="bg-white w-full max-w-md rounded-4xl shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">

            <!-- Cabecera -->
            <div class="flex items-center justify-between px-7 py-5 border-b border-surface-100">
              <div>
                <h2 class="text-lg font-black text-surface-900 leading-tight">Miembros Familiares</h2>
                <p class="text-xs text-surface-500 font-medium mt-0.5">{{ selectedSocio?.nombre_completo }}</p>
              </div>
              <button @click="showFamilyModal = false" class="w-9 h-9 rounded-xl bg-surface-100 hover:bg-surface-200
                       flex items-center justify-center text-surface-500 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M18 6L6 18M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Cuerpo -->
            <div class="overflow-y-auto p-6 bg-surface-50/30">

              <!-- Cargando -->
              <div v-if="isModalLoading" class="flex justify-center py-12">
                <LoadingSpinner />
              </div>

              <!-- Vacío -->
              <div v-else-if="!selectedSocio?.miembros_familiares?.length" class="flex flex-col items-center justify-center py-14 text-center
                       bg-white rounded-2xl border-2 border-dashed border-surface-200">
                <div class="w-14 h-14 rounded-2xl bg-surface-100 flex items-center justify-center mb-3">
                  <svg class="w-7 h-7 text-surface-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5">
                    <path d="M17 21v-2a4 4 0 0 0-3-3.87M9 21v-2a4 4 0 0 0-3-3.87" />
                    <circle cx="9" cy="7" r="4" />
                    <circle cx="17" cy="7" r="4" />
                  </svg>
                </div>
                <p class="text-sm font-bold text-surface-700">Sin miembros familiares</p>
                <p class="text-xs text-surface-400 mt-1 max-w-[200px]">
                  No hay miembros familiares registrados para este socio.
                </p>
              </div>

              <!-- Lista -->
              <div v-else class="flex flex-col gap-2.5">
                <div v-for="fam in selectedSocio.miembros_familiares" :key="fam.id_miembro" class="flex items-center gap-4 p-4 rounded-2xl bg-white border border-surface-200
                         hover:border-primary-200 hover:shadow-sm transition-all">
                  <div class="w-10 h-10 rounded-xl bg-linear-to-br from-purple-400 to-purple-600
                              text-white flex items-center justify-center font-bold text-sm shrink-0">
                    {{ fam.nombre_completo?.charAt(0) ?? '?' }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-surface-900 truncate">{{ fam.nombre_completo }}</p>
                    <p class="text-xs text-surface-500 font-medium">{{ fam.parentesco }} · {{ fam.genero }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pie -->
            <div class="px-7 py-4 border-t border-surface-100 flex justify-end">
              <CancelButton label="Cerrar" @click="showFamilyModal = false" />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>


  </main>
</template>

<style scoped>
.animate-row-in {
  animation: rowIn 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}
@keyframes rowIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
