   <script setup>
import { useAdminStore } from '@/stores/profiles/adminStore'
import { IconUser, IconBriefcase, IconShield } from '@/components/icons'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue'])

const profileStore = useAdminStore()

const closeModal = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="modelValue" class="fixed inset-0 z-100 flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm" @click.self="closeModal">
        
        <Transition
          enter-active-class="transition-all duration-300 ease-out delay-100"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 translate-y-4"
        >
          <div v-if="modelValue" class="bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col relative border border-surface-200">
            <!-- Header Background -->
            <div class="h-32 bg-linear-to-br from-slate-800 to-slate-950 relative">
              <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
              <button @click="closeModal" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors backdrop-blur-md">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M18 6L6 18M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Avatar -->
            <div class="px-8 pb-8 relative text-center -mt-16">
              <div class="inline-flex items-center justify-center w-32 h-32 rounded-3xl bg-white p-2 shadow-xl mb-4 relative z-10 border border-surface-100">
                <div class="w-full h-full rounded-2xl bg-linear-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center text-5xl font-black shadow-inner">
                  {{ profileStore.userInitials }}
                </div>
              </div>

              <!-- Info -->
              <div class="space-y-1 mb-6">
                <h2 class="text-2xl font-black text-surface-900 tracking-tight">{{ profileStore.fullName || 'Cargando...' }}</h2>
                <div class="inline-flex items-center justify-center gap-2 text-primary-600 font-bold text-sm bg-primary-50 px-3 py-1 rounded-full border border-primary-100">
                  <IconShield class="w-4 h-4" />
                  <span>{{ profileStore.role === 'SUBGERENTE' ? 'Subgerente' : 'Gerente' }}</span>
                </div>
              </div>

              <!-- Details Grid -->
              <div class="grid grid-cols-1 gap-3 text-left">
                <div class="flex items-center gap-3 p-3.5 rounded-2xl bg-surface-50 border border-surface-100 hover:border-surface-200 transition-colors">
                  <div class="w-10 h-10 rounded-xl bg-white shadow-sm border border-surface-200 flex items-center justify-center text-surface-400 shrink-0">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                      <polyline points="22,6 12,13 2,6"/>
                    </svg>
                  </div>
                  <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-widest text-surface-400">Correo Electrónico</p>
                    <p class="text-sm font-bold text-surface-900 truncate">{{ profileStore.email || 'No disponible' }}</p>
                  </div>
                </div>

                <div class="flex items-center gap-3 p-3.5 rounded-2xl bg-surface-50 border border-surface-100 hover:border-surface-200 transition-colors">
                  <div class="w-10 h-10 rounded-xl bg-white shadow-sm border border-surface-200 flex items-center justify-center text-surface-400 shrink-0">
                    <IconBriefcase class="w-5 h-5" />
                  </div>
                  <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-widest text-surface-400">Cargo</p>
                    <p class="text-sm font-bold text-surface-900 truncate">{{ profileStore.position || 'Administración' }}</p>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
