<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import api from '@/services/api'
import { useAlerts } from '@/composables/useAlerts'
import AdminPageHeader from '@/components/gerente/ui/AdminPageHeader.vue'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'

import { useTournamentStore } from '@/stores/tournamentStore'

const router = useRouter()
const { toastSuccess, toastError } = useAlerts()
const store = useTournamentStore()

const EMPTY_FORM = () => ({
  nombre_torneo: '',
  nombre_categoria: '',
  nombre_disciplina: '',
  fecha_inicio: null,
  fecha_fin: null,
  tipo_acceso: null,
  formato_competencia: null,
  cupo_maximo: 8,
  descripcion: ''
})

const form = ref(EMPTY_FORM())
const loading = ref(false)
const formError = ref('')

const ACCESO_OPTS = [
  { label: 'Interno', value: 'INTERNO' },
  { label: 'Abierto', value: 'ABIERTO' }
]

const FORMATO_OPTS = [
  { label: 'Eliminación directa', value: 'ELIMINACION_DIRECTA' },
  { label: 'Fase de grupos', value: 'FASE_GRUPOS' }
]

const toDateStr = (d) => {
  if (!d) return ''
  return new Intl.DateTimeFormat('en-CA').format(d)
}

const submit = async () => {
  formError.value = ''

  if (!form.value.fecha_inicio || !form.value.fecha_fin) {
    formError.value = 'Las fechas de inicio y fin son requeridas.'
    return
  }

  if (!form.value.tipo_acceso || !form.value.formato_competencia) {
    formError.value = 'Selecciona tipo de acceso y formato de competencia.'
    return
  }

  loading.value = true

  try {
    const payload = {
      ...form.value,
      tipo_acceso: form.value.tipo_acceso?.value ?? form.value.tipo_acceso,
      formato_competencia: form.value.formato_competencia?.value ?? form.value.formato_competencia,
      fecha_inicio: toDateStr(form.value.fecha_inicio),
      fecha_fin: toDateStr(form.value.fecha_fin)
    }

    await store.crearTorneo(payload)

    toastSuccess('Torneo creado correctamente')
    setTimeout(() => router.push('/admin/tournaments'), 1200)

  } catch (err) {
    const status = err.response?.status
    if (status === 422) {
      formError.value = store.error || 'Error de validación: revisa los campos.'
    } else if (status === 409) {
      formError.value = 'Ya existe un torneo con ese nombre en esa fecha.'
    } else {
      formError.value = store.error || 'Ocurrió un error al crear el torneo.'
      toastError('Error al crear torneo')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <div class="max-w-2xl mx-auto px-4 py-8">

      <AdminPageHeader
        title="Crear Torneo"
        subtitle="Configura los parámetros del nuevo torneo"
        back-route="/admin/tournaments"
      />

      <!-- Form card -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mt-6">

        <!-- Card header accent -->
        <div class="h-1 bg-linear-to-r from-blue-500 to-indigo-600"></div>

        <div class="p-6 sm:p-8">

          <!-- Error banner -->
          <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
          >
            <div
              v-if="formError"
              class="mb-6 flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3"
            >
              <svg class="mt-0.5 size-4 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
              </svg>
              <p class="text-sm text-red-700">{{ formError }}</p>
            </div>
          </Transition>

          <form @submit.prevent="submit" class="space-y-5">

            <!-- Nombre del torneo -->
            <div class="flex flex-col gap-1.5">
              <label class="text-sm font-medium text-slate-700">Nombre del torneo <span class="text-red-400">*</span></label>
              <input
                v-model="form.nombre_torneo"
                required
                placeholder="Ej: Copa Verano 2025"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
              />
            </div>

            <!-- Categoría / Disciplina -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-slate-700">Categoría <span class="text-red-400">*</span></label>
                <input
                  v-model="form.nombre_categoria"
                  required
                  placeholder="Ej: Juvenil"
                  class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
                />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-slate-700">Disciplina <span class="text-red-400">*</span></label>
                <input
                  v-model="form.nombre_disciplina"
                  required
                  placeholder="Ej: Tenis"
                  class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
                />
              </div>
            </div>

            <!-- Fechas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-slate-700">Fecha de inicio <span class="text-red-400">*</span></label>
                <DatePicker
                  v-model="form.fecha_inicio"
                  dateFormat="dd/mm/yy"
                  placeholder="dd/mm/aaaa"
                  showIcon
                  fluid
                  class="w-full"
                />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-slate-700">Fecha de fin <span class="text-red-400">*</span></label>
                <DatePicker
                  v-model="form.fecha_fin"
                  dateFormat="dd/mm/yy"
                  placeholder="dd/mm/aaaa"
                  showIcon
                  fluid
                  class="w-full"
                />
              </div>
            </div>

            <!-- Tipo de acceso / Formato -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-slate-700">Tipo de acceso <span class="text-red-400">*</span></label>
                <Select
                  v-model="form.tipo_acceso"
                  :options="ACCESO_OPTS"
                  optionLabel="label"
                  placeholder="Selecciona"
                  class="w-full"
                />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-slate-700">Formato <span class="text-red-400">*</span></label>
                <Select
                  v-model="form.formato_competencia"
                  :options="FORMATO_OPTS"
                  optionLabel="label"
                  placeholder="Selecciona"
                  class="w-full"
                />
              </div>
            </div>

            <!-- Cupo máximo -->
            <div class="flex flex-col gap-1.5">
              <label class="text-sm font-medium text-slate-700">Cupo máximo <span class="text-red-400">*</span></label>
              <div class="flex items-center gap-3">
                <button
                  type="button"
                  @click="form.cupo_maximo = Math.max(1, form.cupo_maximo - 1)"
                  class="flex size-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 transition"
                >
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                </button>
                <span class="w-14 text-center text-lg font-semibold text-slate-800">{{ form.cupo_maximo }}</span>
                <button
                  type="button"
                  @click="form.cupo_maximo++"
                  class="flex size-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 transition"
                >
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                </button>
                <span class="text-xs text-slate-400">participantes</span>
              </div>
            </div>

            <!-- Descripción -->
            <div class="flex flex-col gap-1.5">
              <label class="text-sm font-medium text-slate-700">Descripción</label>
              <textarea
                v-model="form.descripcion"
                rows="3"
                placeholder="Información adicional sobre el torneo..."
                class="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition"
              />
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
              <CancelButton @click="router.push('/admin/tournaments')" />
              <ConfirmButton
                label="Crear torneo"
                :loading="loading"
                type="submit"
              >
                <template #icon>
                  <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                  </svg>
                </template>
              </ConfirmButton>
            </div>

          </form>
        </div>
      </div>

    </div>
  </div>
</template>
