<script setup>
import { ref, computed, onMounted } from 'vue'
import {
    IconHistory,
    IconLayers
} from '@/components/icons'
import { useReservacionAdminStore } from '@/stores/admin/reservationAdminStore'

// Sub-componentes
import ReservationRegisters from '@/views/admin/reservations/ReservationRegisters.vue'
import ReservationStats from '@/views/admin/reservations/ReservationStats.vue'

const store = useReservacionAdminStore()

onMounted(() => {
    // Prefetch silent para tener los datos listos al cambiar de tab
    store.fetchStats('hoy', true)
    store.fetchStats('semana', true)
    store.fetchStats('mes', true)
})

const activeTab = ref('registros')
const isChangingTab = ref(false)

const tabs = [
    { name: 'registros', label: 'Registros', icon: IconHistory },
    { name: 'stats', label: 'Estadísticas', icon: IconLayers },
]

const components = {
    registros: ReservationRegisters,
    stats: ReservationStats
}

const selectTab = (tabName) => {
    if (activeTab.value === tabName) return
    isChangingTab.value = true
    activeTab.value = tabName
    setTimeout(() => {
        isChangingTab.value = false
    }, 250)
}

const activeComponent = computed(() => components[activeTab.value])
</script>

<template>
    <div class="p-6 font-sans">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-black text-surface-900 tracking-tight mb-8">Administración de Reservas</h1>

            <!-- Segmented Control (Pills) -->
            <div
                class="flex p-1.5 bg-surface-100/50 rounded-2xl w-full mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-8">
                <button v-for="tab in tabs" :key="tab.name" @click="selectTab(tab.name)"
                    class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all duration-200 ease-out whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none active:scale-[0.99] border-none cursor-pointer"
                    :class="activeTab === tab.name
                        ? 'bg-surface-900 text-white font-black rounded-xl shadow-md transform scale-[1.02]'
                        : 'text-surface-500 font-bold hover:bg-white hover:text-surface-700 rounded-xl'">
                    <component :is="tab.icon" class="w-5 h-5 shrink-0" />
                    {{ tab.label }}
                </button>
            </div>

            <!-- Área de Contenido -->
            <div class="bg-white rounded-[2.2rem] border border-surface-200/80 shadow-[0_12px_30px_-10px_rgba(0,0,0,0.03)] p-6 md:p-8 min-h-[400px] relative overflow-hidden">
                <!-- Local loading overlay -->
                <Transition
                    enter-active-class="transition-opacity duration-150"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition-opacity duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="isChangingTab" class="absolute inset-0 bg-white/70 backdrop-blur-[1px] z-20 flex items-center justify-center">
                        <div class="w-8 h-8 border-4 border-slate-200 border-t-slate-900 rounded-full animate-spin"></div>
                    </div>
                </Transition>

                <transition name="fade" mode="out-in">
                    <keep-alive>
                        <component :is="activeComponent" />
                    </keep-alive>
                </transition>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>