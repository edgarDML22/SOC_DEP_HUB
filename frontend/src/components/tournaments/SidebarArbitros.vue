<script setup>
/**
 * SidebarArbitros — sidebar lateral que muestra los árbitros del pool de un torneo
 * con badges de disponibilidad (LIBRE / OCUPADO).
 *
 * Props:
 *   torneo       — objeto del torneo seleccionado
 *   arbitrosPool — { disponibles: [], ocupados: [] }
 *   loading      — boolean
 *   rangoActivo  — { inicio, fin } rango horario seleccionado (para refrescar disponibilidad)
 *
 * Emits:
 *   refresh-availability — solicita refrescar la disponibilidad con un rango horario
 */
import { computed } from 'vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'

const props = defineProps({
    torneo: { type: Object, default: null },
    arbitrosPool: { type: Object, default: () => ({ disponibles: [], ocupados: [] }) },
    loading: { type: Boolean, default: false },
    rangoActivo: { type: Object, default: null },
})

defineEmits(['refresh-availability'])

const todosLosArbitros = computed(() => {
    const disponibles = (props.arbitrosPool?.disponibles || []).map(a => ({
        ...a,
        ocupado: false,
    }))
    const ocupados = (props.arbitrosPool?.ocupados || []).map(a => ({
        ...a,
        ocupado: true,
    }))
    return [...disponibles, ...ocupados]
})

const hayArbitros = computed(() => todosLosArbitros.value.length > 0)
const hayRango = computed(() => props.rangoActivo?.inicio && props.rangoActivo?.fin)

const getInitial = (nombre) => {
    if (!nombre) return '?'
    return nombre.charAt(0).toUpperCase()
}

const getInitialBg = (nombre) => {
    const colors = [
        'bg-blue-100 text-blue-700',
        'bg-purple-100 text-purple-700',
        'bg-pink-100 text-pink-700',
        'bg-amber-100 text-amber-700',
        'bg-emerald-100 text-emerald-700',
        'bg-cyan-100 text-cyan-700',
    ]
    const idx = nombre ? nombre.charCodeAt(0) % colors.length : 0
    return colors[idx]
}
</script>

<template>
    <aside class="bg-white rounded-2xl border border-surface-200 shadow-sm overflow-hidden flex flex-col h-full">
        <!-- Header -->
        <div class="px-5 py-4 border-b border-surface-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-surface-900 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-surface-900 leading-tight">Árbitros</h3>
                    <p class="text-[10px] font-bold text-surface-400 uppercase tracking-widest">Pool del torneo</p>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <!-- Sin torneo seleccionado -->
            <div v-if="!torneo"
                class="flex flex-col items-center justify-center text-center py-12 px-4">
                <div class="w-14 h-14 rounded-2xl bg-surface-50 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-surface-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="text-xs font-bold text-surface-500">Selecciona un torneo</p>
                <p class="text-[11px] text-surface-400 mt-1">Haz clic en un evento del calendario para ver los
                    árbitros disponibles.</p>
            </div>

            <!-- Loading -->
            <div v-else-if="loading" class="py-12 flex flex-col items-center gap-3">
                <LoadingSpinner size="md" />
                <p class="text-xs font-bold text-surface-400 animate-pulse">Cargando árbitros...</p>
            </div>

            <!-- Sin rango horario: mostrar todos los árbitros del torneo -->
            <template v-if="!hayRango && hayArbitros">
                <TransitionGroup name="list" tag="div" class="space-y-1.5">
                    <div v-for="arbitro in todosLosArbitros" :key="arbitro.id_instructor"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200"
                        :class="arbitro.ocupado
                            ? 'bg-red-50/50 border border-red-100'
                            : 'bg-blue-50/50 border border-blue-100 hover:bg-blue-50'">
                        <!-- Avatar -->
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black shrink-0"
                            :class="getInitialBg(arbitro.nombre)">
                            {{ getInitial(arbitro.nombre) }}
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-surface-800 truncate">{{ arbitro.nombre }}</p>
                        </div>

                        <!-- Badge: Designado (sin horario) -->
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider shrink-0 bg-blue-100 text-blue-700">
                            Designado
                        </span>
                    </div>
                </TransitionGroup>
            </template>

            <!-- Con rango horario: mostrar carga o árbitros con disponibilidad -->
            <template v-else-if="hayRango">
                <!-- Cargando disponibilidad -->
                <div v-if="loading" class="py-12 flex flex-col items-center gap-3">
                    <LoadingSpinner size="md" />
                    <p class="text-xs font-bold text-surface-400 animate-pulse">Verificando disponibilidad...</p>
                </div>

                <!-- Árbitros con estado LIBRE/OCUPADO -->
                <TransitionGroup v-else name="list" tag="div" class="space-y-1.5">
                    <div v-for="arbitro in todosLosArbitros" :key="arbitro.id_instructor"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200"
                        :class="arbitro.ocupado
                            ? 'bg-red-50/50 border border-red-100'
                            : 'bg-emerald-50/50 border border-emerald-100 hover:bg-emerald-50'">
                        <!-- Avatar -->
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black shrink-0"
                            :class="getInitialBg(arbitro.nombre)">
                            {{ getInitial(arbitro.nombre) }}
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-surface-800 truncate">{{ arbitro.nombre }}</p>
                            <p v-if="arbitro.ocupado && arbitro.motivo" class="text-[10px] font-medium text-red-500 mt-0.5 leading-tight truncate" :title="arbitro.motivo">
                                {{ arbitro.motivo }}
                            </p>
                        </div>

                        <!-- Badge -->
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider shrink-0"
                            :class="arbitro.ocupado
                                ? 'bg-red-100 text-red-700'
                                : 'bg-emerald-100 text-emerald-700'">
                            {{ arbitro.ocupado ? 'Ocupado' : 'Libre' }}
                        </span>
                    </div>
                </TransitionGroup>
            </template>

            <!-- Sin pool de árbitros -->
            <div v-else-if="!hayArbitros && !loading"
                class="flex flex-col items-center justify-center text-center py-8 px-4">
                <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <p class="text-xs font-bold text-surface-600">Sin árbitros asignados</p>
                <p class="text-[11px] text-surface-400 mt-1">Este torneo no tiene un pool de árbitros configurado.</p>
            </div>
        </div>

        <!-- Footer stats -->
        <div v-if="torneo && hayArbitros && hayRango"
            class="px-5 py-3 border-t border-surface-100 bg-surface-50 flex items-center justify-between">
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="text-[10px] font-bold text-surface-500">{{ arbitrosPool?.disponibles?.length || 0 }}
                    disponibles</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <span class="text-[10px] font-bold text-surface-500">{{ arbitrosPool?.ocupados?.length || 0 }}
                    ocupados</span>
            </div>
        </div>
    </aside>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: translateX(10px);
}
</style>
