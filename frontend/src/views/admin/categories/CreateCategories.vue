<script setup>
import { ref } from 'vue'
import api from '@/services/api'
import { useRouter } from 'vue-router'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import { IconTrophy, IconUser, IconGender, IconCalendar, IconAlertCircle } from '@/components/icons'

const router = useRouter()

const form = ref({
  nombre_categoria: '',
  edad_maxima: 1,
  edad_minima: 1,
  genero_requerido: '',
})

const loading = ref(false)

// BANNER
const banner = ref({
  show: false,
  message: '',
  type: '' // success | error
})

const showBanner = (msg, type = 'success') => {
  banner.value = {
    show: true,
    message: msg,
    type
  }

  setTimeout(() => {
    banner.value.show = false
  }, 4000)
}

const submit = async () => {
  loading.value = true

  try {
    const res = await api.post('categories', form.value)

    if (res.data.success) {
      showBanner('Categoría creada correctamente', 'success')
      setTimeout(() => {
        router.push('/admin/tournaments')  
      }, 1500)
    }

  } catch (err) {
    console.error(err.response?.data)
    if (err.response?.status === 422) {
      showBanner('Error de validación: revisa los campos', 'error')
    } else if (err.response?.status === 409) {
      showBanner('Ya existe una categoría con ese nombre', 'error')
    } else {
      showBanner('Error al crear categoría', 'error')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="min-h-screen bg-slate-50 p-6 lg:p-8 pb-16 font-sans">
    <div class="max-w-3xl mx-auto space-y-8">
      
      <AdminPageHeader 
        title="Crear Categoría de Torneo" 
        subtitle="Configura una nueva clasificación para participantes en competencias"
        back-route="tournaments"
      />

      <!-- BANNER -->
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-4"
      >
        <div v-if="banner.show" 
          class="flex items-center gap-3 p-4 rounded-2xl border mb-6 transition-all"
          :class="banner.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'"
        >
          <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-white/50 shadow-xs">
             <IconAlertCircle class="w-5 h-5" />
          </div>
          <p class="text-sm font-bold">{{ banner.message }}</p>
        </div>
      </Transition>

      <div class="bg-white rounded-4xl border border-slate-200 shadow-xl overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-100">
          <h2 class="text-lg font-black text-slate-900 leading-tight">Datos de la Categoría</h2>
          <p class="text-xs text-slate-500 font-medium mt-1">Define el rango de edad y género para esta clasificación.</p>
        </div>

        <form @submit.prevent="submit" class="p-8 space-y-6 bg-slate-50/20">
          
          <!-- Nombre -->
          <div class="space-y-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Nombre de la categoría</label>
            <div class="relative">
              <IconTrophy class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input v-model="form.nombre_categoria" required placeholder="Ej. Juvenil, Veteranos, U-15..."
                class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-semibold
                       text-slate-900 placeholder:text-slate-400
                       focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all shadow-sm" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Edad Minima -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Edad Mínima</label>
              <div class="relative">
                <IconUser class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input type="number" min="1" v-model="form.edad_minima" required
                  class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-semibold
                         text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all shadow-sm" />
              </div>
            </div>

            <!-- Edad Maxima -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Edad Máxima</label>
              <div class="relative">
                <IconUser class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input type="number" min="1" v-model="form.edad_maxima" required
                  class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-semibold
                         text-slate-900 focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all shadow-sm" />
              </div>
            </div>
          </div>

          <!-- Genero -->
          <div class="space-y-1.5">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Género Requerido</label>
            <div class="relative">
              <IconGender class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <select v-model="form.genero_requerido" required
                class="w-full pl-11 pr-8 py-3 bg-white border border-slate-200 rounded-xl text-sm font-semibold
                       text-slate-900 appearance-none focus:outline-none focus:ring-2 focus:ring-primary-500/30 focus:border-primary-600 transition-all shadow-sm cursor-pointer">
                <option disabled value="">Selecciona una opción</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
                <option value="MIXTO">Mixto</option>
              </select>
              <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M6 9l6 6 6-6" />
                </svg>
              </div>
            </div>
          </div>

          <!-- BOTONES -->
          <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
            <CancelButton @click="router.push('/admin/tournaments')" />
            <ConfirmButton
              label="Crear Categoría"
              :loading="loading"
              type="submit"
            />
          </div>

        </form>
      </div>
    </div>
  </main>
</template>

<style scoped>
/* Estilos adicionales removidos en favor de Tailwind */
</style>