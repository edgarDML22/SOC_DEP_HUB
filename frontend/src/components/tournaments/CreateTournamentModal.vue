<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import { useAlerts } from '@/composables/useAlerts'
import LoadingSpinner from '@/components/gerente/ui/LoadingSpinner.vue'
import ConfirmButton from '@/components/gerente/ui/ConfirmButton.vue'
import CancelButton from '@/components/gerente/ui/CancelButton.vue'

import { useTournamentStore } from '@/stores/tournamentStore'
import { useDisciplinesStore } from '@/stores/admin/disciplines'

const emit = defineEmits(['close', 'created'])

const { toastSuccess, toastError } = useAlerts()
const store = useTournamentStore()
const disciplinesStore = useDisciplinesStore()

onMounted(() => {
  disciplinesStore.fetchDisciplines()
  store.fetchCategoriasTorneo()
})

const EMPTY_FORM = () => ({
  nombre_torneo: '',
  nombre_categoria: null,
  nombre_disciplina: null,
  fecha_inicio: null,
  fecha_fin: null,
  tipo_acceso: null,
  formato_competencia: null,
  cupo_minimo: 2,
  cupo_maximo: 8,
  genero_requerido: null,
  modalidad: null,
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

const GENERO_OPTS = [
  { label: 'Cualquiera', value: null },
  { label: 'Masculino (M)', value: 'M' },
  { label: 'Femenino (F)', value: 'F' },
  { label: 'Mixto', value: 'MIXTO' }
]

const MODALIDAD_OPTS = [
  { label: 'Individual', value: 'INDIVIDUAL' },
  { label: 'Parejas', value: 'PAREJAS' },
  { label: 'Mixto', value: 'MIXTO' }
]

const CATEGORIA_OPTS = computed(() => {
  return store.categoriasTorneo.map(c => ({
    label: c.nombre_categoria,
    value: c
  }))
})

watch(() => form.value.nombre_categoria, (newCat) => {
  if (newCat && newCat.value && newCat.value.genero_requerido) {
    const rawVal = String(newCat.value.genero_requerido).toUpperCase();
    const matchedGender = GENERO_OPTS.find(g => g.value && String(g.value).toUpperCase() === rawVal);
    if (matchedGender) {
      form.value.genero_requerido = matchedGender;
    }
  }
})

const DISCIPLINA_OPTS = computed(() => {
  return disciplinesStore.disciplines.map(d => ({
    label: d.nombre_disciplina,
    value: d.nombre_disciplina
  }))
})

const toDateStr = (d) => {
  if (!d) return ''
  return new Intl.DateTimeFormat('en-CA').format(d)
}

const getVal = (field) => {
  if (field === null || field === undefined) return null
  if (typeof field === 'object' && 'value' in field) {
    if (typeof field.value === 'object' && field.value !== null) {
      return field.value.nombre_categoria ?? field.value
    }
    return field.value
  }
  return field
}

const submit = async () => {
  formError.value = ''

  if (!form.value.fecha_inicio || !form.value.fecha_fin) {
    formError.value = 'Las fechas de inicio y fin son requeridas.'
    return
  }

  if (!form.value.tipo_acceso || !form.value.formato_competencia || !form.value.nombre_categoria || !form.value.nombre_disciplina || !form.value.modalidad) {
    formError.value = 'Por favor completa todos los campos obligatorios.'
    return
  }

  if (form.value.cupo_minimo < 2) {
    formError.value = 'El cupo mínimo debe ser al menos 2 participantes.'
    return
  }

  if (form.value.cupo_minimo >= form.value.cupo_maximo) {
    formError.value = 'El cupo mínimo debe ser estrictamente menor al cupo máximo.'
    return
  }

  loading.value = true

  try {
    const payload = {
      ...form.value,
      nombre_categoria: getVal(form.value.nombre_categoria),
      nombre_disciplina: getVal(form.value.nombre_disciplina),
      tipo_acceso: getVal(form.value.tipo_acceso),
      formato_competencia: getVal(form.value.formato_competencia),
      genero_requerido: getVal(form.value.genero_requerido),
      modalidad: getVal(form.value.modalidad),
      fecha_inicio: toDateStr(form.value.fecha_inicio),
      fecha_fin: toDateStr(form.value.fecha_fin)
    }

    await store.crearTorneo(payload)

    toastSuccess('Torneo creado correctamente')
    emit('created')
    emit('close')

  } catch (err) {
    const status = err.response?.status
    if (status === 422) {
      if (err.response?.data?.errors) {
        const errorList = Object.values(err.response.data.errors).flat()
        formError.value = errorList.join(' ')
      } else {
        formError.value = store.error || 'Error de validación: revisa los campos.'
      }
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
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-surface-900/60 backdrop-blur-sm" @click.self="emit('close')">
        
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-surface-200 flex flex-col relative">
          <!-- Card header accent -->
          <div class="h-1.5 w-full bg-linear-to-r from-blue-500 to-indigo-600 shrink-0"></div>

          <div class="p-6 sm:p-8 flex-1">
            <div class="flex justify-between items-center mb-6">
              <div>
                <h2 class="text-2xl font-black text-surface-900">Crear Torneo</h2>
                <p class="text-sm text-surface-500 font-medium mt-1">Configura los parámetros del nuevo torneo</p>
              </div>
              <button @click="emit('close')" type="button" class="p-2 text-surface-400 hover:text-surface-700 hover:bg-surface-100 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Error banner -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-2"
            >
              <div v-if="formError" class="mb-6 flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
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
                  <Select
                    v-model="form.nombre_categoria"
                    :options="CATEGORIA_OPTS"
                    optionLabel="label"
                    placeholder="Selecciona Categoría"
                    class="w-full"
                  />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-sm font-medium text-slate-700">Disciplina <span class="text-red-400">*</span></label>
                  <Select
                    v-model="form.nombre_disciplina"
                    :options="DISCIPLINA_OPTS"
                    optionLabel="label"
                    placeholder="Selecciona Disciplina"
                    class="w-full"
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

              <!-- Tipo de acceso / Formato / Modalidad / Género -->
              <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
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
                <div class="flex flex-col gap-1.5">
                  <label class="text-sm font-medium text-slate-700">Modalidad <span class="text-red-400">*</span></label>
                  <Select
                    v-model="form.modalidad"
                    :options="MODALIDAD_OPTS"
                    optionLabel="label"
                    placeholder="Selecciona"
                    class="w-full"
                  />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-sm font-medium text-slate-700">Género <span class="text-red-400">*</span></label>
                  <Select
                    v-model="form.genero_requerido"
                    :options="GENERO_OPTS"
                    optionLabel="label"
                    placeholder="Cualquiera"
                    class="w-full"
                  />
                </div>
              </div>

              <!-- Cupos -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                  <label class="text-sm font-medium text-slate-700">Cupo mínimo <span class="text-red-400">*</span></label>
                  <div class="flex items-center gap-3">
                    <button
                      type="button"
                      @click="form.cupo_minimo = Math.max(2, form.cupo_minimo - 1)"
                      class="flex size-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 transition"
                    >
                      <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                    </button>
                    <span class="w-14 text-center text-lg font-semibold text-slate-800">{{ form.cupo_minimo }}</span>
                    <button
                      type="button"
                      @click="form.cupo_minimo++"
                      class="flex size-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 transition"
                    >
                      <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    </button>
                    <span class="text-xs text-slate-400">mín. 2</span>
                  </div>
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="text-sm font-medium text-slate-700">Cupo máximo <span class="text-red-400">*</span></label>
                  <div class="flex items-center gap-3">
                    <button
                      type="button"
                      @click="form.cupo_maximo = Math.max(form.cupo_minimo + 1, form.cupo_maximo - 1)"
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
                  </div>
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
                ></textarea>
              </div>

              <!-- Actions -->
              <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 mt-2">
                <CancelButton @click="emit('close')" type="button" />
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
    </Transition>
  </Teleport>
</template>
