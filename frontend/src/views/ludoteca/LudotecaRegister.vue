<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminLudotecaStore } from '@/stores/ludoteca/adminLudotecaStore'
import { storeToRefs } from 'pinia'
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import BadgeStatus from '@/components/gerente/ui/BadgeStatus.vue'
import ActionMenu from '@/components/gerente/ui/ActionMenu.vue'
import SearchInput from '@/components/gerente/ui/SearchInput.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import TableSkeleton from '@/components/gerente/ui/TableSkeleton.vue'
import ExportCsvButton from '@/components/gerente/ui/ExportCsvButton.vue'
import { IconFilter, IconChevronDown, IconCalendar, IconStar, IconUser, IconHourglass, IconAlertCircle } from '@/components/icons'
import { useformat } from '@/utils/formatters'

// FullCalendar o similar no es necesario aquí, solo una tabla
import DatePicker from "primevue/datepicker";

const router = useRouter()
const store = useAdminLudotecaStore()
const { formatText, dateFormat } = useformat();

const { record, sociosConMenores, loading } = storeToRefs(store)
const { fetchRecord, fetchSociosConMenores } = store

// ── FILTROS ────────────────────────────────────────────────────
const search = ref('')
const dateRange = ref(null) // [start, end]
const filterSocio = ref(null)
const filterCalificacion = ref(null)
const filterEstatus = ref(null)
const filterTiempo = ref(null)

const OPT_CALIFICACION = [
    { label: 'Todas', value: null },
    { label: '1 Estrella', value: 1 },
    { label: '2 Estrellas', value: 2 },
    { label: '3 Estrellas', value: 3 },
    { label: '4 Estrellas', value: 4 },
    { label: '5 Estrellas', value: 5 },
]

const OPT_ESTATUS = [
    { label: 'Todos los estatus', value: null },
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

    if (dateRange.value && dateRange.value[0] && dateRange.value[1]) {
        // Formatear fechas YYYY-MM-DD
        const formatDate = (d) => d.toISOString().split('T')[0]
        params.fecha_inicio = formatDate(dateRange.value[0])
        params.fecha_fin = formatDate(dateRange.value[1])
    }

    await fetchRecord(params, true)
    isFiltering.value = false
}

onMounted(async () => {
    await Promise.all([
        fetchRecord({}, true),
        fetchSociosConMenores()
    ])
})

// Solo el rango de fechas gatilla una nueva petición al servidor
watch(dateRange, () => {
    loadData(true)
})

const filteredRecord = computed(() => {
    let r = record.value

    if (search.value) {
        const q = search.value.toLowerCase()
        r = r.filter(i =>
            i.nombre_menor?.toLowerCase().includes(q) ||
            i.nombre_titular?.toLowerCase().includes(q) ||
            String(i.numero_accion ?? '').includes(q)
        )
    }

    if (filterSocio.value) {
        r = r.filter(i => i.id_socio === filterSocio.value)
    }

    if (filterCalificacion.value) {
        r = r.filter(i => i.calificacion_servicio === filterCalificacion.value)
    }

    if (filterEstatus.value) {
        r = r.filter(i => i.estatus_final === filterEstatus.value)
    }

    if (filterTiempo.value) {
        r = r.filter(i => {
            const t = i.tiempo_total_minutos
            if (filterTiempo.value === 'lt15') return t < 15
            if (filterTiempo.value === 'lt30') return t >= 15 && t <= 30
            if (filterTiempo.value === 'lt60') return t >= 31 && t <= 60
            if (filterTiempo.value === 'lt90') return t >= 61 && t <= 90
            if (filterTiempo.value === 'gt90') return t > 90
            return true
        })
    }

    return r
})

const hasActiveFilters = computed(() =>
    search.value || dateRange.value || filterSocio.value ||
    filterCalificacion.value || filterEstatus.value || filterTiempo.value
)

const clearFilters = () => {
    search.value = ''
    dateRange.value = null
    filterSocio.value = null
    filterCalificacion.value = null
    filterEstatus.value = null
    filterTiempo.value = null
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
            <span class="text-sm font-bold text-surface-500">
                {{ filteredRecord.length }}
                <span class="font-medium text-surface-400">registros encontrados</span>
            </span>
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
        <div class="bg-white rounded-2xl border border-surface-200 shadow-sm p-5 space-y-4">
            <div class="flex flex-col lg:flex-row gap-4">
                <div class="flex-1">
                    <SearchInput v-model="search" placeholder="Buscar por menor, titular o acción…" />
                </div>
                <div class="lg:w-72">
                    <div class="relative group">
                        <IconCalendar
                            class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 z-10" />
                        <DatePicker v-model="dateRange" selectionMode="range" :manualInput="false"
                            placeholder="Rango de fechas" class="w-full"
                            inputClass="w-full pl-10 pr-4 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 focus:ring-2 focus:ring-primary-500/40 outline-none transition-all"
                            showIcon="false" />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Socio -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Socio
                        Titular</label>
                    <div class="relative">
                        <IconUser
                            class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                        <select v-model="filterSocio"
                            class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 transition-all cursor-pointer">
                            <option :value="null">Todos los socios</option>
                            <option v-for="s in sociosConMenores" :key="s.id_socio" :value="s.id_socio">
                                {{ s.nombre_completo }} (#{{ s.numero_accion }})
                            </option>
                        </select>
                        <IconChevronDown
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                </div>

                <!-- Calificación -->
                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Calificación</label>
                    <div class="relative">
                        <IconStar
                            class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                        <select v-model="filterCalificacion"
                            class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 transition-all cursor-pointer">
                            <option v-for="opt in OPT_CALIFICACION" :key="opt.value" :value="opt.value">{{ opt.label }}
                            </option>
                        </select>
                        <IconChevronDown
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                </div>

                <!-- Estatus -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Estatus
                        Final</label>
                    <div class="relative">
                        <IconAlertCircle
                            class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                        <select v-model="filterEstatus"
                            class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 transition-all cursor-pointer">
                            <option v-for="opt in OPT_ESTATUS" :key="opt.value" :value="opt.value">{{
                                formatText(opt.label) }}
                            </option>
                        </select>
                        <IconChevronDown
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                </div>

                <!-- Tiempo -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-black uppercase tracking-widest text-surface-400 px-1">Tiempo de
                        Estancia</label>
                    <div class="relative">
                        <IconHourglass
                            class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                        <select v-model="filterTiempo"
                            class="w-full pl-10 pr-8 py-2.5 bg-surface-50 border border-surface-200 rounded-xl text-sm font-semibold text-surface-700 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/40 transition-all cursor-pointer">
                            <option v-for="opt in OPT_TIEMPO" :key="opt.value" :value="opt.value">{{ opt.label }}
                            </option>
                        </select>
                        <IconChevronDown
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-400 pointer-events-none" />
                    </div>
                </div>
            </div>

            <Transition enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in" leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1">
                <div v-if="hasActiveFilters" class="flex justify-end">
                    <button @click="clearFilters"
                        class="text-xs font-bold text-primary-600 hover:text-primary-800 flex items-center gap-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                        Limpiar filtros
                    </button>
                </div>
            </Transition>
        </div>

        <!-- TABLA -->
        <div class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden min-h-[400px] relative">

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
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-50 border-b border-surface-200">
                            <th
                                class="px-5 py-4 text-left text-[10px] font-black uppercase tracking-widest text-surface-700">
                                Menor</th>
                            <th
                                class="px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-surface-700">
                                Titular</th>
                            <th
                                class="px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-surface-700">
                                Ingreso / Egreso</th>
                            <th
                                class="px-4 py-4 text-center text-[10px] font-black uppercase tracking-widest text-surface-700">
                                Tiempo</th>
                            <th
                                class="px-4 py-4 text-center text-[10px] font-black uppercase tracking-widest text-surface-700">
                                Calif.</th>
                            <th
                                class="px-4 py-4 text-center text-[10px] font-black uppercase tracking-widest text-surface-700">
                                Estatus Final</th>
                            <th
                                class="px-4 py-4 text-right text-[10px] font-black uppercase tracking-widest text-surface-700">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100">
                        <tr v-for="item in filteredRecord" :key="item.id_historial"
                            class="hover:bg-surface-50/50 transition-colors group cursor-pointer"
                            @click="router.push(`/admin/ludoteca/record/${item.id_historial}`)">

                            <!-- Menor -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-linear-to-br flex items-center justify-center text-white font-black text-xs shrink-0 shadow-sm"
                                        :class="avatarGradient(item.nombre_menor)">
                                        {{ initials(item.nombre_menor) }}
                                    </div>
                                    <span class="font-medium text-surface-900">{{ item.nombre_menor }}</span>
                                </div>
                            </td>

                            <!-- Titular -->
                            <td class="px-4 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-surface-700">{{ item.nombre_titular
                                    }}</span>
                                    <span
                                        class="text-[10px] font-medium text-surface-400 uppercase tracking-wider">Acción
                                        #{{ item.numero_accion }}</span>
                                </div>
                            </td>

                            <!-- Ingreso / Egreso -->
                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span class="text-xs font-medium text-surface-600">{{ item.hora_ingreso
                                        }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span class="text-xs font-medium text-surface-600">{{ item.hora_egreso }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Tiempo -->
                            <td class="px-4 py-4 text-center">
                                <span
                                    class="px-2.5 py-1 rounded-lg bg-surface-100 text-surface-700 font-medium text-[10px]">
                                    {{ item.tiempo_total_minutos }} min
                                </span>
                            </td>

                            <!-- Calificación -->
                            <td class="px-4 py-4 text-center">
                                <div v-if="item.calificacion_servicio" class="flex items-center justify-center gap-0.5">
                                    <span class="text-xs font-medium text-amber-500">{{ item.calificacion_servicio
                                    }}</span>
                                    <IconStar class="w-3 h-3 text-amber-500 fill-amber-500" />
                                </div>
                                <span v-else class="text-surface-300 font-medium text-[10px]">N/A</span>
                            </td>

                            <!-- Estatus Final -->
                            <td class="px-4 py-4 text-center" @click.stop>
                                <BadgeStatus :status="item.estatus_final" />
                            </td>

                            <!-- Acciones -->
                            <td class="px-4 py-4 text-right" @click.stop>
                                <ActionMenu :items="buildMenuItems(item)" align="right" />
                            </td>
                        </tr>
                    </tbody>
                </table>
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