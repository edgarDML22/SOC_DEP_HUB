<script setup>
import { ref, computed } from 'vue'
import {
    IconHistory,
    IconLayers
} from '@/components/icons'

// Sub-componentes
import LudotecaRegister from '@/views/ludoteca/LudotecaRegister.vue'
import LudotecaAdmin from '@/views/ludoteca/LudotecaAdmin.vue'

const activeTab = ref('register')

const tabs = [
    { name: 'register', label: 'Registros', icon: IconHistory },
    { name: 'stats', label: 'Estadísticas', icon: IconLayers },
]

const components = {
    register: LudotecaRegister,
    stats: LudotecaAdmin
}

const activeComponent = computed(() => components[activeTab.value])
</script>

<template>
    <div class="p-6 font-sans">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-black text-slate-800 tracking-tight mb-8">Ludoteca</h1>

            <!-- Segmented Control (Pills) -->
            <div
                class="flex p-1.5 bg-slate-100 rounded-2xl w-full mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-slate-200 mb-8">
                <button v-for="tab in tabs" :key="tab.name" @click="activeTab = tab.name"
                    class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
                    :class="activeTab === tab.name
                        ? 'bg-blue-600 text-white font-extrabold rounded-xl shadow-md transform scale-[1.02]'
                        : 'text-slate-500 font-bold hover:bg-white/60 hover:text-slate-700 rounded-xl'">
                    <component :is="tab.icon" class="w-5 h-5 shrink-0" />
                    {{ tab.label }}
                </button>
            </div>

            <!-- Área de Contenido -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 min-h-[400px]">
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