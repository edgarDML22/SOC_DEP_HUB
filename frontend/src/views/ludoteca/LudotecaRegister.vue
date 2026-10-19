<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminLudotecaStore } from '@/stores/ludoteca/adminLudotecaStore'
import { storeToRefs } from 'pinia'
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import SearchInput from '@/components/gerente/ui/SearchInput.vue'
import FilterContainer from '@/components/gerente/ui/FilterContainer.vue'
import FilterSelect from '@/components/gerente/ui/FilterSelect.vue'
import AppDatePicker from '@/components/ui/form/AppDatePicker.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import ExportCsvButton from '@/components/gerente/ui/ExportCsvButton.vue'
import { IconFilter, IconChevronDown, IconCalendar, IconStar, IconHourglass, IconAlertCircle } from '@/components/icons'
import { useformat } from '@/utils/formatters'

const router = useRouter()
const store = useAdminLudotecaStore()
const { formatText, dateFormat } = useformat();

const { record, loading, listFilters } = storeToRefs(store)
const { fetchRecord } = store

// ── FILTROS ────────────────────────────────────────────────────
const OPT_CALIFICACION = [
    { label: 'Todas', value: null },
    { label: '1 Estrella', value: 1 },
    { label: '2 Estrellas', value: 2 },
    { label: '3 Estrellas', value: 3 },
    { label: '4 Estrellas', value: 4 },
    { label: '5 Estrellas', value: 5 },
]

const OPT_ESTATUS = [
    { label: 'Todos', value: null },
    { label: 'A tiempo', value: 'COMPLETADA_A_TIEMPO' },
    { label: 'Con retraso', value: 'COMPLETADA_CON_RETRASO' },
    { label: 'Forzado por sistema', value: 'FORZADO_POR_SISTEMA' },
]

const OPT_TIEMPO = [
    { label: 'Cualquier tiempo', value: null },
    { label: 'Menos de 15 min', value: 'lt15' },
    { label: '15 - 30 min', value: 'lt30' },
    { label: '31 - 60 min', value: 'lt60' },
    { label: '61 - 90 min', value: 'lt90' },
    { label: 'Más de 90 min', value: 'gt90' },
]

const isFiltering = ref(false)

// ── Cargar Datos ───────────────────────────────────────────────
const loadData = async (silent = false) => {
    if (silent) isFiltering.value = true
    const params = {}
    const f = listFilters.value

    if (f.fecha_inicio) {
        params.fecha_inicio = f.fecha_inicio
    }
    if (f.fecha_fin) {
        params.fecha_fin = f.fecha_fin
    }

    await fetchRecord(params, true)
    isFiltering.value = false
}

onMounted(async () => {
    await fetchRecord({}, true)
})

// Las fechas gatillan una nueva petición al servidor
watch(
    [() => listFilters.value.fecha_inicio, () => listFilters.value.fecha_fin],
    () => {
        loadData(true)
    }
)

const filteredRecord = computed(() => {
    let r = record.value
    const f = listFilters.value

    if (f.search) {
        const q = f.search.toLowerCase()
        r = r.filter(i =>
            i.nombre_menor?.toLowerCase().includes(q) ||
            i.nombre_titular?.toLowerCase().includes(q) ||
            String(i.numero_accion ?? '').includes(q)
        )
    }

    if (f.calificacion) {
        r = r.filter(i => i.calificacion_servicio === f.calificacion)
    }

    if (f.estatus) {
        r = r.filter(i => i.estatus_final === f.estatus)
    }

    if (f.tiempo) {
        r = r.filter(i => {
            const t = i.tiempo_total_minutos
            if (f.tiempo === 'lt15') return t < 15
            if (f.tiempo === 'lt30') return t >= 15 && t <= 30
            if (f.tiempo === 'lt60') return t >= 31 && t <= 60
            if (f.tiempo === 'lt90') return t >= 61 && t <= 90
            if (f.tiempo === 'gt90') return t > 90
            return true
        })
    }

    // Ordenar por fecha más cercana a hoy (descendente)
    r.sort((a, b) => {
        const timeA = a.hora_ingreso ? new Date(a.hora_ingreso.replace(' ', 'T')).getTime() : 0
        const timeB = b.hora_ingreso ? new Date(b.hora_ingreso.replace(' ', 'T')).getTime() : 0
        return timeB - timeA
    })

    return r
})

const extractDate = (dateTimeStr) => {
    if (!dateTimeStr) return ''
    return dateTimeStr.includes('T') ? dateTimeStr.split('T')[0] : (dateTimeStr.includes(' ') ? dateTimeStr.split(' ')[0] : dateTimeStr)
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
    if (!timeString) return '—'
    const timePart = timeString.includes('T') ? timeString.split('T')[1] : (timeString.includes(' ') ? timeString.split(' ')[1] : timeString)
    const parts = timePart.split(':')
    if (parts.length < 2) return timePart
    return `${parts[0]}:${parts[1]}`
}

const currentPage = ref(1)
const itemsPerPage = ref(15)

const lastPage = computed(() => {
    return Math.max(1, Math.ceil(filteredRecord.value.length / itemsPerPage.value))
})

const paginatedRecord = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return filteredRecord.value.slice(start, end)
})

watch(filteredRecord, () => {
    currentPage.value = 1
})

const OPT_ESTATUS_UI = computed(() => OPT_ESTATUS.map(o => ({ ...o, label: formatText(o.label) })))
const activeFiltersCount = computed(() =>
    ['fecha_inicio', 'fecha_fin', 'calificacion', 'estatus', 'tiempo'].filter(k => listFilters.value[k]).length
)

const hasActiveFilters = computed(() =>
    listFilters.value.search || listFilters.value.fecha_inicio || listFilters.value.fecha_fin ||
    listFilters.value.calificacion || listFilters.value.estatus || listFilters.value.tiempo
)

const clearFilters = () => {
    listFilters.value.search = ''
    listFilters.value.fecha_inicio = ''
    listFilters.value.fecha_fin = ''
    listFilters.value.calificacion = null
    listFilters.value.estatus = null
    listFilters.value.tiempo = null
}

// ── AVATAR ────────────────────────────────────────────────────
const AVATAR_GRADIENTS = [
    'from-blue-400 to-blue-600',
    'from-indigo-400 to-indigo-600',
    'from-violet-400 to-violet-600',
    'from-fuchsia-400 to-fuchsia-600',
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

// ── ACCIONES ───────────────────────────────────────────────────
const buildMenuItems = (item) => [
    {
        label: 'Ver detalles',
        icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                 <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                 <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
               </svg>`,
        action: () => router.push(`/admin/ludoteca/record/${item.id_historial}`),
    }
]

</script>

<template>
    <div class="space-y-8 animate-fade-in">

        <!-- CABECERA -->
        <AdminPageHeader title="Historial de Registros"
            subtitle="Consulta el registro histórico de ingresos, egresos y calificaciones de la ludoteca.">
            <ExportCsvButton :data="filteredRecord" filename="ludoteca-registros" :columns="[
                { label: 'ID', field: 'id_historial' },
                { label: 'Número Acción', field: 'numero_accion' },
                { label: 'Nombre Menor', field: 'nombre_menor' },
                { label: 'Nombre Titular', field: 'nombre_titular' },
                { label: 'Hora de Ingreso', field: 'hora_ingreso' },
                { label: 'Hora de Egreso', field: 'hora_egreso' },
                { label: 'Tiempo Total (minutos)', field: 'tiempo_total_minutos' },
                { label: 'Estatus Final', field: 'estatus_final' },
                { label: 'Calificación Servicio', field: 'calificacion_servicio' },
                { label: 'Observaciones', field: 'observaciones' }
            ]" />
        </AdminPageHeader>

        <!-- BARRA DE FILTROS -->
        <FilterContainer :has-active-filters="!!hasActiveFilters" :active-count="activeFiltersCount" @clear="clearFilters">
            <template #search>
                <SearchInput v-model="listFilters.search" placeholder="Buscar por menor, titular o acción…" />
            </template>

            <div class="flex flex-col gap-1.5">
                <span class="px-1 text-[10px] font-black uppercase tracking-widest text-surface-400">Desde</span>
                <AppDatePicker v-model="listFilters.fecha_inicio" :max-date="listFilters.fecha_fin || null" />
            </div>
            <div class="flex flex-col gap-1.5">
                <span class="px-1 text-[10px] font-black uppercase tracking-widest text-surface-400">Hasta</span>
                <AppDatePicker v-model="listFilters.fecha_fin" :min-date="listFilters.fecha_inicio || null" />
            </div>
            <FilterSelect label="Calificación" v-model="listFilters.calificacion" :options="OPT_CALIFICACION"><template #icon><IconStar /></template></FilterSelect>
            <FilterSelect label="Estatus final" v-model="listFilters.estatus" :options="OPT_ESTATUS_UI"><template #icon><IconAlertCircle /></template></FilterSelect>
            <FilterSelect label="Tiempo de estancia" v-model="listFilters.tiempo" :options="OPT_TIEMPO"><template #icon><IconHourglass /></template></FilterSelect>
        </FilterContainer>

        <!-- TABLA -->
        <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden min-h-[400px] relative flex flex-col">
            
            <div class="flex-1 overflow-x-auto flex flex-col">

            <!-- SKELETON -->
            <TableSkeleton v-if="loading.record && !isFiltering" :rows="8" :columns="6" :has-avatar="true" />

            <!-- Empty State -->
            <div v-if="!loading.record && filteredRecord.length === 0"
                class="p-20 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 rounded-2xl bg-surface-50 flex items-center justify-center mb-4 text-surface-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <h3 class="text-base font-black text-surface-900">Sin registros</h3>
                <p class="text-sm text-surface-500 mt-1 max-w-xs">No se encontraron registros históricos con los
                    criterios seleccionados.</p>
            </div>

            <!-- Table -->
            <div v-else class="flex-1">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="bg-slate-900 text-white text-[11px] uppercase font-bold tracking-widest sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left font-extrabold">Menor</th>
                            <th scope="col" class="px-6 py-4 text-left font-extrabold">Titular / Acción</th>
                            <th scope="col" class="px-6 py-4 text-left font-extrabold">Ingreso / Egreso</th>
                            <th scope="col" class="px-6 py-4 text-left font-extrabold">Fecha / Tiempo</th>
                            <th scope="col" class="px-6 py-4 text-center font-extrabold">Calif.</th>
                            <th scope="col" class="px-6 py-4 text-center font-extrabold">Estatus Final</th>
                            <th scope="col" class="px-6 py-4 text-right font-extrabold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100">
                        <tr v-for="item in paginatedRecord" :key="item.id_historial"
                            class="bg-white border-b border-surface-100 hover:bg-surface-50/50 transition-colors group cursor-pointer"
                            @click="router.push(`/admin/ludoteca/record/${item.id_historial}`)">

                            <!-- Menor -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-linear-to-br flex items-center justify-center text-white font-black text-xs shrink-0 shadow-sm"
                                        :class="avatarGradient(item.nombre_menor)">
                                        {{ initials(item.nombre_menor) }}
                                    </div>
                                    <span class="font-medium text-surface-900">{{ item.nombre_menor }}</span>
                                </div>
                            </td>

                            <!-- Titular / Acción -->
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800 text-sm leading-snug">{{ item.nombre_titular }}</div>
                                <div class="mt-1 flex items-center">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-primary-50 text-primary-700 border border-primary-100 text-[10px] font-bold tracking-tight">
                                        Acción #{{ item.numero_accion ?? '—' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Ingreso / Egreso -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                        <span class="text-xs font-bold text-black">{{ formatTimeHM(item.hora_ingreso) }} hrs</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                                        <span class="text-xs font-bold text-black">{{ formatTimeHM(item.hora_egreso) }} hrs</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Fecha / Tiempo -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800">
                                    <svg class="w-3.5 h-3.5 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>{{ formatNiceDate(extractDate(item.hora_ingreso)) }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-primary-50 text-primary-700 border border-primary-100 text-[11px] font-semibold tabular-nums tracking-tight">
                                        <svg class="w-3 h-3 text-primary-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ item.tiempo_total_minutos }} min
                                    </span>
                                </div>
                            </td>

                            <!-- Calificación -->
                            <td class="px-6 py-4 text-center">
                                <div v-if="item.calificacion_servicio" class="flex items-center justify-center gap-0.5">
                                    <span class="text-xs font-medium text-amber-500">{{ item.calificacion_servicio
                                    }}</span>
                                    <IconStar class="w-3 h-3 text-amber-500 fill-amber-500" />
                                </div>
                                <span v-else class="text-surface-300 font-medium text-[10px]">N/A</span>
                            </td>

                            <!-- Estatus Final -->
                            <td class="px-6 py-4 text-center" @click.stop>
                                <BadgeStatus :status="item.estatus_final" />
                            </td>

                            <!-- Acciones -->
                            <td class="px-6 py-4 text-right" @click.stop>
                                <ActionMenu :items="buildMenuItems(item)" align="right" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>

            <!-- Paginación -->
            <div v-if="!loading.record && filteredRecord.length > 0" class="px-6 py-3 bg-slate-900 border-t border-slate-700 flex items-center justify-between text-xs text-slate-400 font-bold shrink-0 mt-auto">
                <span class="tabular-nums text-slate-300">
                    {{ filteredRecord.length }} {{ filteredRecord.length === 1 ? 'registro' : 'registros' }}
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
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>