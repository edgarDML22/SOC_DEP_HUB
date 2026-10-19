<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useReservacionAdminStore } from '@/stores/admin/reservationAdminStore'
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import ExportCsvButton from '@/components/gerente/ui/ExportCsvButton.vue'
import SearchInput from '@/components/gerente/ui/SearchInput.vue'
import FilterContainer from '@/components/gerente/ui/FilterContainer.vue'
import FilterSelect from '@/components/gerente/ui/FilterSelect.vue'
import AppDatePicker from '@/components/ui/form/AppDatePicker.vue'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'
import { IconFilter, IconChevronDown, IconCalendar } from '@/components/icons'

const store = useReservacionAdminStore()

const filters = ref({
    espacio_id: '',
    search: '',
    disciplina_id: '',
    fecha_inicio: '',
    fecha_fin: '',
    modalidad: ''
})

const isModalOpen = ref(false)
const selectedAcompanantes = ref([])

onMounted(() => {
    store.fetchFiltersMeta()
    store.fetchReservaciones(filters.value)
})

const OPT_MODALIDAD = [
    { label: 'Todas', value: '' },
    { label: 'Individual', value: 'INDIVIDUAL' },
    { label: 'Acompañantes', value: 'ACOMPANANTES' }
]
const espacioOpts = computed(() => [
    { label: 'Todos', value: '' },
    ...(store.filtersMeta.espacios || []).map(e => ({ label: e.nombre, value: e.id }))
])
const disciplinaOpts = computed(() => [
    { label: 'Todas', value: '' },
    ...(store.filtersMeta.disciplinas || []).map(d => ({ label: d.nombre, value: d.id }))
])
const activeFiltersCount = computed(() =>
    ['espacio_id', 'disciplina_id', 'modalidad', 'fecha_inicio', 'fecha_fin'].filter(k => filters.value[k]).length
)

const hasActiveFilters = computed(() => 
    filters.value.search || 
    filters.value.espacio_id || 
    filters.value.disciplina_id || 
    filters.value.fecha_inicio || 
    filters.value.fecha_fin ||
    filters.value.modalidad
)

const clearFilters = () => {
    filters.value = {
        espacio_id: '',
        search: '',
        disciplina_id: '',
        fecha_inicio: '',
        fecha_fin: '',
        modalidad: ''
    }
}

const statusOrder = {
    'ACTIVA': 1,
    'PENDIENTE': 2,
    'COMPLETADA': 3,
    'CANCELADA': 4,
    'NO_SHOW': 5
}

const sortedReservaciones = computed(() => {
    let result = [...store.reservaciones]

    if (filters.value.search) {
        const query = filters.value.search.toLowerCase()
        result = result.filter(r => 
            r.nombre_titular?.toLowerCase().includes(query) || 
            r.numero_accion?.toString().includes(query)
        )
    }

    if (filters.value.espacio_id) {
        result = result.filter(r => r.id_espacio == filters.value.espacio_id)
    }

    if (filters.value.disciplina_id) {
        result = result.filter(r => r.id_disciplina == filters.value.disciplina_id)
    }

    if (filters.value.modalidad) {
        result = result.filter(r => r.modalidad?.toUpperCase() === filters.value.modalidad.toUpperCase())
    }

    if (filters.value.fecha_inicio) {
        result = result.filter(r => r.fecha_reserva >= filters.value.fecha_inicio)
    }

    if (filters.value.fecha_fin) {
        result = result.filter(r => r.fecha_reserva <= filters.value.fecha_fin)
    }

    return result.sort((a, b) => {
        // 1. Mayor prioridad: fecha más reciente primero (descendente)
        const dateA = a.fecha_reserva || ''
        const dateB = b.fecha_reserva || ''
        if (dateA !== dateB) {
            return dateB.localeCompare(dateA)
        }

        // 2. Misma fecha: hora de inicio más reciente primero (descendente)
        const timeA = a.hora_inicio || ''
        const timeB = b.hora_inicio || ''
        if (timeA !== timeB) {
            return timeB.localeCompare(timeA)
        }

        // 3. Menor prioridad: por orden de estatus
        const orderA = statusOrder[a.estatus_operativo] || 99
        const orderB = statusOrder[b.estatus_operativo] || 99
        return orderA - orderB
    })
})

const currentPage = ref(1)
const itemsPerPage = ref(15)

const lastPage = computed(() => {
    return Math.max(1, Math.ceil(sortedReservaciones.value.length / itemsPerPage.value))
})

const paginatedReservaciones = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return sortedReservaciones.value.slice(start, end)
})

watch(sortedReservaciones, () => {
    currentPage.value = 1
})

const selectedReserva = ref(null)

const openAcompanantesModal = (reserva) => {
    selectedReserva.value = reserva
    try {
        const acompanantesData = reserva.acompanantes_draft
        if (typeof acompanantesData === 'string') {
            selectedAcompanantes.value = JSON.parse(acompanantesData || '[]')
        } else {
            selectedAcompanantes.value = acompanantesData || []
        }
        isModalOpen.value = true
    } catch (e) {
        console.error('Error processing acompanantes data:', e)
        selectedAcompanantes.value = []
    }
}

const closeAcompanantesModal = () => {
    isModalOpen.value = false
    selectedAcompanantes.value = []
    selectedReserva.value = null
}

const buildMenuItems = (reserva) => [
    {
        label: 'Ver acompañantes',
        icon: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
               </svg>`,
        action: () => openAcompanantesModal(reserva),
    }
]

const getBadgeColor = (tipo) => {
    switch (tipo?.toLowerCase()) {
        case 'socio_amigo': return 'bg-blue-100 text-blue-800 border-blue-200'
        case 'invitado': return 'bg-amber-100 text-amber-800 border-amber-200'
        case 'familiar': return 'bg-emerald-100 text-emerald-800 border-emerald-200'
        default: return 'bg-slate-100 text-slate-800 border-slate-200'
    }
}

const getStatusColor = (status) => {
    switch (status?.toUpperCase()) {
        case 'ACTIVA': return 'bg-emerald-50 text-emerald-700 border-emerald-200'
        case 'PENDIENTE': return 'bg-amber-50 text-amber-700 border-amber-200'
        case 'COMPLETADA': return 'bg-primary-50 text-primary-700 border-primary-200'
        case 'CANCELADA': return 'bg-red-50 text-red-700 border-red-200'
        case 'NO_SHOW': return 'bg-purple-50 text-purple-700 border-purple-200'
        default: return 'bg-slate-100 text-slate-500 border-slate-200'
    }
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    const parts = dateString.split('-')
    if (parts.length !== 3) return dateString
    return `${parts[2]}-${parts[1]}-${parts[0]}`
}

const formatNiceDate = (dateString) => {
    if (!dateString) return '—'
    const parts = dateString.split('-')
    if (parts.length !== 3) return dateString
    const year = parts[0]
    const monthIndex = parseInt(parts[1], 10) - 1
    const day = parseInt(parts[2], 10)
    const months = [
        'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
        'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
    ]
    const monthName = months[monthIndex] || parts[1]
    return `${day} ${monthName}, ${year}`
}

const formatTimeHM = (timeString) => {
    if (!timeString) return ''
    const parts = timeString.split(':')
    if (parts.length < 2) return timeString
    return `${parts[0]}:${parts[1]}`
}


const getModalidadLabel = (modalidad) => {
    const labels = {
        'ACOMPANANTES': 'ACOMPAÑANTES',
        'INDIVIDUAL': 'INDIVIDUAL'
    }
    return labels[modalidad?.toUpperCase()] || String(modalidad || '').toUpperCase()
}

const getStatusLabel = (status) => {
    const labels = {
        'ACTIVA': 'ACTIVA',
        'PENDIENTE': 'PENDIENTE',
        'COMPLETADA': 'COMPLETADA',
        'CANCELADA': 'CANCELADA',
        'NO_SHOW': 'NO SHOW',
    }
    return labels[status?.toUpperCase()] || (status ? String(status).replace('_', ' ').toUpperCase() : '')
}

const dataToExport = computed(() => {
    return sortedReservaciones.value.map(r => ({
        ...r,
        fecha_format: formatDate(r.fecha_reserva),
        modalidad_format: getModalidadLabel(r.modalidad),
        estatus_format: getStatusLabel(r.estatus_operativo),
        horario: `${r.hora_inicio} - ${r.hora_fin}`
    }))
})

const exportColumns = [
    { label: 'Titular', field: 'nombre_titular' },
    { label: 'Acción', field: 'numero_accion' },
    { label: 'Espacio', field: 'nombre_espacio' },
    { label: 'Disciplina', field: 'nombre_disciplina' },
    { label: 'Modalidad', field: 'modalidad_format' },
    { label: 'Fecha', field: 'fecha_reserva' },
    { label: 'Horario', field: 'horario' },
    { label: 'Estatus', field: 'estatus_format' },
]
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- CABECERA -->
        <AdminPageHeader 
            title="Historial de Registros"
            subtitle="Consulta el registro histórico de reservaciones de espacios, horarios y asistencias."
        />

        <!-- Top Bar: Filters & Actions -->
        <FilterContainer :has-active-filters="!!hasActiveFilters" :active-count="activeFiltersCount" @clear="clearFilters">
            <template #search>
                <SearchInput v-model="filters.search" placeholder="Buscar por nombre o número de acción..." />
            </template>

            <FilterSelect label="Espacio" v-model="filters.espacio_id" :options="espacioOpts"><template #icon><IconFilter /></template></FilterSelect>
            <FilterSelect label="Disciplina" v-model="filters.disciplina_id" :options="disciplinaOpts"><template #icon><IconFilter /></template></FilterSelect>
            <FilterSelect label="Modalidad" v-model="filters.modalidad" :options="OPT_MODALIDAD"><template #icon><IconFilter /></template></FilterSelect>

            <div class="flex flex-col gap-1.5">
                <span class="px-1 text-[10px] font-black uppercase tracking-widest text-surface-400">Desde</span>
                <AppDatePicker v-model="filters.fecha_inicio" :max-date="filters.fecha_fin || null" />
            </div>
            <div class="flex flex-col gap-1.5">
                <span class="px-1 text-[10px] font-black uppercase tracking-widest text-surface-400">Hasta</span>
                <AppDatePicker v-model="filters.fecha_fin" :min-date="filters.fecha_inicio || null" />
            </div>

            <template #actions>
                <ExportCsvButton
                    :data="dataToExport"
                    :columns="exportColumns"
                    filename="reservaciones_export"
                    class="w-full sm:w-auto"
                />
            </template>
        </FilterContainer>

        <!-- Data Table -->
        <div class="rounded-2xl border border-surface-200 shadow-sm bg-white overflow-hidden min-h-96 flex flex-col">
            <div class="overflow-x-auto flex-1">
            
            <!-- SKELETON -->
            <TableSkeleton v-if="store.loading.reservaciones" :rows="6" :columns="5" :has-avatar="false" />

            <table v-else class="w-full text-sm text-left text-slate-600">
                <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left font-extrabold">Titular / Acción</th>
                        <th scope="col" class="px-6 py-4 text-left font-extrabold">Espacio</th>
                        <th scope="col" class="px-6 py-4 text-left font-extrabold">Disciplina</th>
                        <th scope="col" class="px-6 py-4 text-left font-extrabold">Fecha / Hora</th>
                        <th scope="col" class="px-6 py-4 text-left font-extrabold">Modalidad</th>
                        <th scope="col" class="px-6 py-4 text-left font-extrabold">Estatus</th>
                        <th scope="col" class="px-6 py-4 text-right font-extrabold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="paginatedReservaciones.length === 0" class="bg-white border-b border-surface-100">
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500 font-medium">
                            No se encontraron reservaciones con los filtros actuales.
                        </td>
                    </tr>
                    <tr v-else v-for="reserva in paginatedReservaciones" :key="reserva.id_reserva" class="bg-white border-b border-surface-100 hover:bg-surface-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800 text-sm leading-snug">{{ reserva.nombre_titular }}</div>
                            <div class="mt-1 flex items-center">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-primary-50 text-primary-700 border border-primary-100 text-[10px] font-bold tracking-tight">
                                    Acción #{{ reserva.numero_accion ?? '—' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800">{{ reserva.nombre_espacio }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-primary-50 text-primary-700 border border-primary-100 text-[10px] font-bold">
                                <DisciplineIcon :name="reserva.disciplina?.nombre_disciplina || reserva.nombre_disciplina" class="w-3.5 h-3.5 shrink-0" />
                                {{ reserva.disciplina?.nombre_disciplina || reserva.nombre_disciplina }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800">
                                <svg class="w-3.5 h-3.5 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ formatNiceDate(reserva.fecha_reserva) }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-primary-50 text-primary-700 border border-primary-100 text-[11px] font-semibold tabular-nums tracking-tight">
                                    <svg class="w-3 h-3 text-primary-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ formatTimeHM(reserva.hora_inicio) }} - {{ formatTimeHM(reserva.hora_fin) }} hrs
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-[11px] font-bold tracking-wide rounded-md border uppercase" 
                                  :class="reserva.modalidad?.toUpperCase() === 'ACOMPANANTES' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-orange-50 text-orange-700 border-orange-200'">
                                {{ getModalidadLabel(reserva.modalidad) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 text-[11px] font-bold tracking-wide rounded-md border uppercase" :class="getStatusColor(reserva.estatus_operativo)">
                                {{ getStatusLabel(reserva.estatus_operativo) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <ActionMenu :items="buildMenuItems(reserva)" align="right" />
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>

            <!-- Paginación -->
            <div v-if="!store.loading.reservaciones && sortedReservaciones.length > 0" class="px-6 py-3 bg-slate-900 border-t border-slate-700 flex items-center justify-between text-xs text-slate-400 font-bold shrink-0 mt-auto">
                <span class="tabular-nums text-slate-300">
                    {{ sortedReservaciones.length }} {{ sortedReservaciones.length === 1 ? 'registro' : 'registros' }}
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

        <!-- Teleport Modal para Acompañantes -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="isModalOpen" class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click="closeAcompanantesModal">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]" @click.stop>
                        
                        <!-- Header del Modal -->
                        <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-white shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-50 rounded-xl">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-black text-slate-800">
                                    {{ selectedReserva?.modalidad === 'INDIVIDUAL' ? 'Información de Reserva' : 'Detalle de Acompañantes' }}
                                </h3>
                            </div>
                            <button @click="closeAcompanantesModal" class="text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 p-2 rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Contenido del Modal -->
                        <div class="p-5 overflow-y-auto bg-slate-50/50 flex-1">
                            <div v-if="selectedReserva?.modalidad === 'INDIVIDUAL'" class="flex flex-col items-center justify-center py-10 text-center">
                                <div class="p-3 bg-amber-50 rounded-2xl mb-4 text-amber-500">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-base font-black text-slate-800">Modalidad Individual</h4>
                                <p class="text-slate-500 font-medium mt-2 text-sm max-w-xs">Esta reservación fue realizada bajo la modalidad individual, por lo que no cuenta con acompañantes.</p>
                            </div>
                            
                            <div v-else-if="selectedAcompanantes.length === 0" class="flex flex-col items-center justify-center py-10 text-center">
                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <p class="text-slate-500 font-medium">No hay acompañantes registrados<br>para esta reserva.</p>
                            </div>
                            
                            <div v-else class="space-y-3">
                                <div v-for="(acomp, index) in selectedAcompanantes" :key="index" 
                                    class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800 text-base">{{ acomp.nombre || 'Sin nombre' }}</span>
                                            
                                            <!-- Renderizar propiedades extras dinámicamente si las hay -->
                                            <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-500 mt-2" v-if="Object.keys(acomp).filter(k => k.toLowerCase() !== 'nombre' && k.toLowerCase() !== 'tipo' && k.toLowerCase() !== 'id').length > 0">
                                                <div v-for="key in Object.keys(acomp).filter(k => k.toLowerCase() !== 'nombre' && k.toLowerCase() !== 'tipo' && k.toLowerCase() !== 'id')" :key="key" class="flex items-center gap-1">
                                                    <span class="font-semibold text-slate-600 capitalize">{{ key.replace(/_/g, ' ') }}:</span>
                                                    <span>{{ acomp[key] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="shrink-0">
                                            <span class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md border whitespace-nowrap" :class="getBadgeColor(acomp.tipo)">
                                                {{ acomp.tipo ? acomp.tipo.replace('_', ' ') : 'DESCONOCIDO' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer del Modal -->
                        <div class="p-5 border-t border-slate-100 bg-white flex justify-end shrink-0">
                            <button @click="closeAcompanantesModal" class="px-6 py-2.5 text-sm font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-500">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}
.modal-enter-active > div,
.modal-leave-active > div {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    opacity: 0;
    transform: scale(0.95) translateY(10px);
}
</style>
