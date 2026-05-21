<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import DisciplineIcon from '@/components/icons/disciplines/DisciplineIcon.vue'

const props = defineProps({
  modelValue:    { type: [Number, null], default: null },
  opciones:      { type: Array,   required: true }, // [{ id_disciplina, nombre_disciplina }]
  placeholder:   { type: String,  default: 'Seleccionar disciplina...' },
  emptyMessage:  { type: String,  default: 'Sin disciplinas para esta combinación' },
  error:         { type: String,  default: '' },
  label:         { type: String,  default: 'Disciplina' },
  showLabel:     { type: Boolean, default: true },
  size:          { type: String,  default: 'md' }, // md | sm
  allowClear:    { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const highlightId = ref(null)
const rootRef = ref(null)
const uid = `disc-${Math.random().toString(36).slice(2, 9)}`

const seleccionado = computed(() =>
  props.opciones.find(d => d.id_disciplina === props.modelValue) ?? null
)

function toggle() {
  open.value = !open.value
  if (open.value) { keyBuffer.value = ''; highlightId.value = props.modelValue }
}
function close() { open.value = false }

function seleccionar(id) {
  emit('update:modelValue', id)
  highlightId.value = null
  keyBuffer.value = ''
  close()
}

// ─── Búsqueda por teclado ────────────────────────────────────────────────
const keyBuffer = ref('')
let keyTimer = null

function onKey(e) {
  if (!open.value) return
  if (e.key === 'Escape') { close(); return }
  if (e.key === 'Enter') {
    e.preventDefault()
    if (highlightId.value !== null) seleccionar(highlightId.value)
    else close()
    return
  }
  if (e.key.length !== 1) return

  clearTimeout(keyTimer)
  keyBuffer.value += e.key.toLowerCase()
  keyTimer = setTimeout(() => { keyBuffer.value = '' }, 800)

  const match = props.opciones.find(d =>
    d.nombre_disciplina.toLowerCase().startsWith(keyBuffer.value)
  )
  if (match) {
    highlightId.value = match.id_disciplina
    document.getElementById(`${uid}-opt-${match.id_disciplina}`)?.scrollIntoView({ block: 'nearest' })
  }
}

function onClickOutside(e) {
  if (!rootRef.value) return
  if (!rootRef.value.contains(e.target)) close()
}

onMounted(() => {
  document.addEventListener('click', onClickOutside)
  document.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  document.removeEventListener('keydown', onKey)
})

const padCls = computed(() => props.size === 'sm' ? 'px-3 py-2' : 'px-4 py-3')
</script>

<template>
  <div ref="rootRef" class="relative">
    <div v-if="showLabel" class="flex items-center justify-between mb-1.5">
      <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600">{{ label }}</label>
      <slot name="label-action" />
    </div>

    <button
      type="button"
      @click.stop="toggle"
      :class="[
        'w-full flex items-center gap-3 rounded-xl border text-sm font-semibold transition-all duration-150 text-left focus:outline-none',
        padCls,
        error
          ? 'border-red-300 bg-red-50'
          : open
            ? 'border-primary-400 bg-white shadow-md ring-2 ring-primary-500/20'
            : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white'
      ]"
    >
      <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 bg-primary-50 text-primary-600">
        <DisciplineIcon :name="seleccionado?.nombre_disciplina ?? ''" class="w-5 h-5" />
      </span>
      <span :class="['flex-1 truncate', seleccionado ? 'text-slate-800' : 'text-slate-400']">
        {{ seleccionado?.nombre_disciplina ?? placeholder }}
      </span>
      <button
        v-if="seleccionado && allowClear"
        type="button"
        @click.stop="seleccionar(null)"
        class="w-5 h-5 rounded-full bg-slate-200 hover:bg-red-100 hover:text-red-500 flex items-center justify-center text-slate-500 shrink-0 transition-colors focus:outline-none"
      >
        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      <svg
        class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-150"
        :class="open ? 'rotate-180' : ''"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
      </svg>
    </button>

    <div
      v-if="open"
      class="absolute top-full left-0 right-0 mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl z-40 overflow-hidden"
    >
      <div class="max-h-52 overflow-y-auto py-1">
        <button
          v-if="allowClear"
          type="button"
          @click.stop="seleccionar(null)"
          :class="[
            'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors text-left focus:outline-none',
            modelValue === null ? 'bg-slate-100 text-slate-600' : 'text-slate-400 hover:bg-slate-50'
          ]"
        >
          <span class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </span>
          <span class="font-semibold italic">Sin disciplina</span>
        </button>
        <div v-if="allowClear" class="border-t border-slate-100 my-1" />

        <div v-if="opciones.length === 0" class="px-4 py-4 text-xs text-slate-400 text-center font-medium">
          {{ emptyMessage }}
        </div>

        <button
          v-for="d in opciones"
          :key="d.id_disciplina"
          :id="`${uid}-opt-${d.id_disciplina}`"
          type="button"
          @click.stop="seleccionar(d.id_disciplina)"
          :class="[
            'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors text-left focus:outline-none',
            modelValue === d.id_disciplina
              ? 'bg-primary-50 text-primary-700'
              : highlightId === d.id_disciplina
                ? 'bg-primary-100/70 text-primary-600'
                : 'text-slate-700 hover:bg-slate-100'
          ]"
        >
          <span class="w-7 h-7 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
            <DisciplineIcon :name="d.nombre_disciplina" class="w-4 h-4" />
          </span>
          <span class="font-semibold truncate">{{ d.nombre_disciplina }}</span>
          <svg
            v-if="modelValue === d.id_disciplina"
            class="w-4 h-4 text-primary-600 ml-auto shrink-0"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </button>
      </div>
    </div>

    <p v-if="error" class="text-red-500 text-xs mt-1 font-medium flex items-center gap-1">
      <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
      {{ error }}
    </p>
  </div>
</template>
