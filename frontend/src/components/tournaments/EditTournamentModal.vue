<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useAlerts } from '@/composables/useAlerts'
import AppModal from '@/components/ui/modal/AppModal.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppField from '@/components/ui/form/AppField.vue'
import AppInput from '@/components/ui/form/AppInput.vue'
import AppSelect from '@/components/ui/form/AppSelect.vue'
import AppDatePicker from '@/components/ui/form/AppDatePicker.vue'
import AppTextarea from '@/components/ui/form/AppTextarea.vue'
import { IconEdit, IconWarning } from '@/components/icons'

import { useTournamentStore } from '@/stores/tournamentStore'
import { useDisciplinesStore } from '@/stores/admin/disciplines'

const props = defineProps({
  torneo: {
    type: Object,
    required: true
  },
  open: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close', 'updated'])

const { toastSuccess, toastError } = useAlerts()
const store = useTournamentStore()
const disciplinesStore = useDisciplinesStore()

const loading = ref(false)
const formError = ref('')
const submitted = ref(false)

const form = ref({
  nombre_torneo: '',
  nombre_categoria: null,
  nombre_disciplina: null,
  fecha_inicio: '',
  fecha_fin: '',
  tipo_acceso: null,
  formato_competencia: null,
  cupo_minimo: 2,
  cupo_maximo: 8,
  genero_requerido: null,
  modalidad: null,
  descripcion: ''
})

const rawCategoria = ref(null)
const rawDisciplina = ref(null)

const matchSelects = () => {
  if (rawCategoria.value && store.categoriasTorneo.length) {
    const matched = CATEGORIA_OPTS.value.find(c => c.label === rawCategoria.value || c.value.nombre_categoria === rawCategoria.value)
    if (matched) form.value.nombre_categoria = matched.value
  }
  if (rawDisciplina.value && disciplinesStore.disciplines.length) {
    const matched = DISCIPLINA_OPTS.value.find(d => d.label === rawDisciplina.value)
    if (matched) form.value.nombre_disciplina = matched.value
  }
}

watch(() => store.categoriasTorneo, matchSelects, { deep: true })
watch(() => disciplinesStore.disciplines, matchSelects, { deep: true })

onMounted(async () => {
  disciplinesStore.fetchDisciplines()
  store.fetchCategoriasTorneo()
  
  if (props.torneo && props.torneo.id_torneo) {
    loading.value = true
    try {
      await store.fetchTorneoById(props.torneo.id_torneo)
      const fullTorneo = store.torneoActivo
      
      if (fullTorneo) {
        form.value.nombre_torneo = fullTorneo.nombre_torneo || ''
        rawCategoria.value = fullTorneo.categoria || fullTorneo.nombre_categoria
        rawDisciplina.value = fullTorneo.disciplina || fullTorneo.nombre_disciplina
        form.value.fecha_inicio = fullTorneo.fecha_inicio ? String(fullTorneo.fecha_inicio).slice(0, 10) : ''
        form.value.fecha_fin = fullTorneo.fecha_fin ? String(fullTorneo.fecha_fin).slice(0, 10) : ''
        form.value.tipo_acceso = fullTorneo.tipo_acceso
        form.value.formato_competencia = fullTorneo.formato_competencia
        form.value.cupo_minimo = fullTorneo.cupo_minimo || 2
        form.value.cupo_maximo = fullTorneo.cupo_maximo || 8
        form.value.genero_requerido = fullTorneo.genero || fullTorneo.genero_requerido
        form.value.modalidad = fullTorneo.modalidad
        form.value.descripcion = fullTorneo.descripcion || ''
        
        matchSelects()
      }
    } catch (e) {
      console.error(e)
    } finally {
      loading.value = false
    }
  }
})

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
  { label: 'Masculino (Varonil)', value: 'VARONIL' },
  { label: 'Femenino (Femenil)', value: 'FEMENIL' },
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

const DISCIPLINA_OPTS = computed(() => {
  return disciplinesStore.disciplines.map(d => ({
    label: d.nombre_disciplina,
    value: d.nombre_disciplina
  }))
})

const toDateStr = (d) => {
  if (!d) return ''
  if (typeof d === 'string') return d // In case it's already a string
  return new Intl.DateTimeFormat('en-CA').format(d)
}

const getVal = (field) => {
  if (field === null || field === undefined) return null
  if (typeof field === 'object') return field.nombre_categoria ?? field.value ?? field
  return field
}

const submit = async () => {
  submitted.value = true
  formError.value = ''

  if (!form.value.nombre_torneo.trim()) {
    formError.value = 'Escribe el nombre del torneo.'
    return
  }

  if (!form.value.fecha_inicio || !form.value.fecha_fin) {
    formError.value = 'Las fechas de inicio y fin son requeridas.'
    return
  }

  if (!form.value.tipo_acceso || !form.value.formato_competencia || !form.value.nombre_categoria || !form.value.nombre_disciplina || !form.value.modalidad) {
    formError.value = 'Completa todos los campos obligatorios.'
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

    await store.actualizarTorneo(props.torneo.id_torneo, payload)

    toastSuccess('Torneo actualizado correctamente')
    emit('updated')
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
    } else {
      formError.value = store.error || 'Ocurrió un error al actualizar el torneo.'
      toastError('Error al actualizar torneo')
    }
  } finally {
    loading.value = false
  }
}

const stepCupo = (field, delta) => {
  const next = form.value[field] + delta
  if (field === 'cupo_minimo') form.value.cupo_minimo = Math.max(2, next)
  else form.value.cupo_maximo = Math.max(form.value.cupo_minimo + 1, next)
}
</script>

<template>
  <AppModal
    :model-value="open"
    title="Editar torneo"
    :subtitle="torneo?.nombre_torneo"
    :icon="IconEdit"
    size="lg"
    :persistent="loading"
    @update:model-value="(v) => !v && emit('close')"
  >
    <form id="edit-tournament-form" class="flex flex-col gap-4" novalidate @submit.prevent="submit">
      <div v-if="formError" role="alert"
        class="flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
        <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
        {{ formError }}
      </div>

      <AppField label="Nombre del torneo" for="torneo-edit-nombre" required>
        <AppInput id="torneo-edit-nombre" v-model="form.nombre_torneo" placeholder="Ej: Copa Verano 2026"
          :invalid="submitted && !form.nombre_torneo.trim()" />
      </AppField>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <AppField label="Categoría" required>
          <AppSelect v-model="form.nombre_categoria" :options="CATEGORIA_OPTS" placeholder="Selecciona" :invalid="submitted && !form.nombre_categoria" />
        </AppField>
        <AppField label="Disciplina" required>
          <AppSelect v-model="form.nombre_disciplina" :options="DISCIPLINA_OPTS" placeholder="Selecciona" :invalid="submitted && !form.nombre_disciplina" />
        </AppField>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <AppField label="Fecha de inicio" required>
          <AppDatePicker v-model="form.fecha_inicio" :invalid="submitted && !form.fecha_inicio" />
        </AppField>
        <AppField label="Fecha de fin" required>
          <AppDatePicker v-model="form.fecha_fin" :min-date="form.fecha_inicio || null" :invalid="submitted && !form.fecha_fin" />
        </AppField>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <AppField label="Tipo de acceso" required>
          <AppSelect v-model="form.tipo_acceso" :options="ACCESO_OPTS" placeholder="Selecciona" :invalid="submitted && !form.tipo_acceso" />
        </AppField>
        <AppField label="Formato" required>
          <AppSelect v-model="form.formato_competencia" :options="FORMATO_OPTS" placeholder="Selecciona" :invalid="submitted && !form.formato_competencia" />
        </AppField>
        <AppField label="Modalidad" required>
          <AppSelect v-model="form.modalidad" :options="MODALIDAD_OPTS" placeholder="Selecciona" :invalid="submitted && !form.modalidad" />
        </AppField>
        <AppField label="Género">
          <AppSelect v-model="form.genero_requerido" :options="GENERO_OPTS" placeholder="Cualquiera" />
        </AppField>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <AppField label="Cupo mínimo" required hint="Mínimo 2 participantes.">
          <div class="flex items-center gap-3">
            <button type="button" aria-label="Disminuir cupo mínimo" class="cupo-btn" @click="stepCupo('cupo_minimo', -1)">−</button>
            <span class="w-12 text-center text-lg font-bold tabular-nums text-surface-900">{{ form.cupo_minimo }}</span>
            <button type="button" aria-label="Aumentar cupo mínimo" class="cupo-btn" @click="stepCupo('cupo_minimo', 1)">+</button>
          </div>
        </AppField>
        <AppField label="Cupo máximo" required>
          <div class="flex items-center gap-3">
            <button type="button" aria-label="Disminuir cupo máximo" class="cupo-btn" @click="stepCupo('cupo_maximo', -1)">−</button>
            <span class="w-12 text-center text-lg font-bold tabular-nums text-surface-900">{{ form.cupo_maximo }}</span>
            <button type="button" aria-label="Aumentar cupo máximo" class="cupo-btn" @click="stepCupo('cupo_maximo', 1)">+</button>
          </div>
        </AppField>
      </div>

      <AppField label="Descripción" for="torneo-edit-descripcion" optional>
        <AppTextarea id="torneo-edit-descripcion" v-model="form.descripcion" rows="3" placeholder="Información adicional sobre el torneo…" />
      </AppField>
    </form>

    <template #footer>
      <AppButton variant="secondary" :disabled="loading" @click="emit('close')">Cancelar</AppButton>
      <AppButton type="submit" form="edit-tournament-form" :loading="loading">Guardar cambios</AppButton>
    </template>
  </AppModal>
</template>

<style scoped>
.cupo-btn {
  display: flex;
  height: 2.5rem;
  width: 2.5rem;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  border-radius: 0.75rem;
  border: 1px solid var(--color-surface-200);
  background: var(--color-surface-50);
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1;
  color: var(--color-surface-700);
  transition: background-color 0.15s;
}
.cupo-btn:hover {
  background: var(--color-surface-100);
}
</style>
