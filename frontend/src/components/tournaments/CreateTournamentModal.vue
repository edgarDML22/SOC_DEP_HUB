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
import { IconTrophy, IconWarning } from '@/components/icons'
import IconPlus from '@/components/icons/IconPlus.vue'

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

const form = ref(EMPTY_FORM())
const loading = ref(false)
const formError = ref('')
const submitted = ref(false)
const fechaInicioError = ref('')
const fechaFinError = ref('')

const ACCESO_OPTS = [
  { label: 'Interno', value: 'INTERNO' },
  { label: 'Abierto', value: 'ABIERTO' }
]

const FORMATO_OPTS = [
  { label: 'Eliminación directa', value: 'ELIMINACION_DIRECTA' },
  { label: 'Fase de grupos', value: 'FASE_GRUPOS' }
]

const GENERO_OPTS = [
  { label: 'Masculino (M)', value: 'M' },
  { label: 'Femenino (F)', value: 'F' },
  { label: 'Mixto', value: 'MIXTO' }
]

const MODALIDAD_OPTS = [
  { label: 'Individual', value: 'INDIVIDUAL' },
  { label: 'Parejas', value: 'PAREJAS' },
  { label: 'Equipo', value: 'EQUIPO' }
]

const CATEGORIA_OPTS = computed(() => {
  return store.categoriasTorneo
    .filter(c => ['Infantil', 'Juvenil', 'Adulto'].includes(c.nombre_categoria))
    .map(c => ({
      label: c.nombre_categoria,
      value: c
    }))
})

watch(() => form.value.nombre_disciplina, () => {
  form.value.nombre_categoria = null
})

watch(() => form.value.nombre_categoria, (newCat) => {
  if (newCat?.genero_requerido) {
    const rawVal = String(newCat.genero_requerido).toUpperCase();
    const matchedGender = GENERO_OPTS.find(g => g.value && String(g.value).toUpperCase() === rawVal);
    if (matchedGender) {
      form.value.genero_requerido = matchedGender.value;
    }
  }
})

const DISCIPLINA_OPTS = computed(() => {
  return disciplinesStore.disciplines.map(d => ({
    label: d.nombre_disciplina,
    value: d.nombre_disciplina
  }))
})

const normalizeDate = (val) => {
  if (!val) return null
  if (val instanceof Date) {
    const d = new Date(val.getTime())
    d.setHours(0, 0, 0, 0)
    return d
  }
  if (typeof val === 'string') {
    const parts = val.split(/[-/]/)
    if (parts.length === 3) {
      if (parts[0].length === 4) {
        return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 0, 0, 0, 0)
      } else if (parts[2].length === 4) {
        return new Date(Number(parts[2]), Number(parts[1]) - 1, Number(parts[0]), 0, 0, 0, 0)
      }
    }
    const d = new Date(val)
    if (!isNaN(d.getTime())) {
      d.setHours(0, 0, 0, 0)
      return d
    }
  }
  return null
}

const minFechaInicio = computed(() => {
  const d = new Date()
  d.setHours(0, 0, 0, 0)
  d.setDate(d.getDate() + 7)
  return d
})

const minFechaFin = computed(() => {
  if (!form.value.fecha_inicio) return null
  const d = normalizeDate(form.value.fecha_inicio)
  if (!d) return null
  d.setDate(d.getDate() + 15)
  return d
})

watch(() => form.value.fecha_inicio, (newInicio) => {
  fechaInicioError.value = ''
  if (!newInicio) {
    form.value.fecha_fin = ''
    fechaFinError.value = ''
    return
  }
  if (form.value.fecha_fin) {
    const inicio = normalizeDate(newInicio)
    const fin = normalizeDate(form.value.fecha_fin)
    if (inicio && fin) {
      const minFin = new Date(inicio)
      minFin.setDate(minFin.getDate() + 15)
      if (fin.getTime() < minFin.getTime()) {
        form.value.fecha_fin = ''
        fechaFinError.value = ''
      }
    }
  }
})

watch(() => form.value.fecha_fin, () => {
  fechaFinError.value = ''
})

const toDateStr = (d) => {
  if (!d) return ''
  if (typeof d === 'string') return d
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
  fechaInicioError.value = ''
  fechaFinError.value = ''

  let hasDateError = false

  if (!form.value.fecha_inicio) {
    fechaInicioError.value = 'La fecha de inicio es requerida.'
    hasDateError = true
  }

  if (!form.value.fecha_fin) {
    fechaFinError.value = 'La fecha de fin es requerida.'
    hasDateError = true
  }

  const inicio = normalizeDate(form.value.fecha_inicio)
  const fin = normalizeDate(form.value.fecha_fin)

  if (inicio && minFechaInicio.value && inicio.getTime() < minFechaInicio.value.getTime()) {
    fechaInicioError.value = 'La fecha de inicio debe tener al menos 7 días de anticipación respecto a hoy.'
    hasDateError = true
  }

  if (inicio && fin && minFechaFin.value && fin.getTime() < minFechaFin.value.getTime()) {
    fechaFinError.value = 'La fecha de fin debe ser al menos 15 días posterior a la fecha de inicio.'
    hasDateError = true
  }

  if (hasDateError) {
    return
  }

  if (!form.value.nombre_torneo.trim()) {
    formError.value = 'Escribe el nombre del torneo.'
    return
  }

  if (!form.value.tipo_acceso || !form.value.formato_competencia || !form.value.nombre_categoria || !form.value.nombre_disciplina || !form.value.modalidad || !form.value.genero_requerido) {
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

    await store.crearTorneo(payload)

    toastSuccess('Torneo creado correctamente')
    emit('created')
    emit('close')

  } catch (err) {
    formError.value = store.error || 'Ocurrió un error al crear el torneo.'
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
    :model-value="true"
    title="Crear torneo"
    subtitle="Configura los datos del torneo"
    :icon="IconTrophy"
    size="lg"
    :persistent="loading"
    @update:model-value="(v) => !v && emit('close')"
  >
    <form id="create-tournament-form" class="flex flex-col gap-4" novalidate @submit.prevent="submit">
      <div v-if="formError" role="alert"
        class="flex items-start gap-2.5 rounded-2xl bg-rose-50 p-3.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">
        <IconWarning class="mt-0.5 h-4 w-4 shrink-0" />
        {{ formError }}
      </div>

      <AppField label="Nombre del torneo" for="torneo-nombre" required>
        <AppInput id="torneo-nombre" v-model="form.nombre_torneo" placeholder="Ej: Copa Verano 2026"
          :invalid="submitted && !form.nombre_torneo.trim()" />
      </AppField>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <AppField label="Disciplina" required>
          <AppSelect v-model="form.nombre_disciplina" :options="DISCIPLINA_OPTS" placeholder="Selecciona disciplina"
            :invalid="submitted && !form.nombre_disciplina" />
        </AppField>
        <AppField label="Categoría" required :hint="form.nombre_disciplina ? '' : 'Elige primero la disciplina.'">
          <AppSelect v-model="form.nombre_categoria" :options="CATEGORIA_OPTS" placeholder="Selecciona categoría"
            :disabled="!form.nombre_disciplina" :invalid="submitted && !form.nombre_categoria" />
        </AppField>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <AppField label="Fecha de inicio" required :error="fechaInicioError" hint="Con al menos 7 días de anticipación.">
          <AppDatePicker v-model="form.fecha_inicio" :min-date="minFechaInicio" :invalid="!!fechaInicioError" />
        </AppField>
        <AppField label="Fecha de fin" required :error="fechaFinError" hint="Al menos 15 días después del inicio.">
          <AppDatePicker v-model="form.fecha_fin" :min-date="minFechaFin" :disabled="!form.fecha_inicio" :invalid="!!fechaFinError" />
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
        <AppField label="Género" required>
          <AppSelect v-model="form.genero_requerido" :options="GENERO_OPTS" placeholder="Selecciona" :invalid="submitted && !form.genero_requerido" />
        </AppField>
      </div>

      <!-- Cupos (solo equipos) -->
      <div v-if="form.modalidad === 'EQUIPO'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

      <AppField label="Descripción" for="torneo-descripcion" optional>
        <AppTextarea id="torneo-descripcion" v-model="form.descripcion" rows="3" placeholder="Información adicional sobre el torneo…" />
      </AppField>
    </form>

    <template #footer>
      <AppButton variant="secondary" :disabled="loading" @click="emit('close')">Cancelar</AppButton>
      <AppButton type="submit" form="create-tournament-form" :loading="loading">
        <template #icon><IconPlus class="h-4 w-4" /></template>
        Crear torneo
      </AppButton>
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
