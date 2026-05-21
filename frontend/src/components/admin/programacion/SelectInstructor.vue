<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue:   { type: [Number, null], default: null },
  opciones:     { type: Array,   required: true }, // [{ id_instructor, nombre_completo }]
  placeholder:  { type: String,  default: 'Seleccionar instructor...' },
  emptyMessage: { type: String,  default: 'Sin instructores disponibles' },
  error:        { type: String,  default: '' },
  label:        { type: String,  default: 'Instructor' },
  showLabel:    { type: Boolean, default: true },
  size:         { type: String,  default: 'md' },
  allowClear:   { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const highlightId = ref(null)
const rootRef = ref(null)
const uid = `inst-${Math.random().toString(36).slice(2, 9)}`

const seleccionado = computed(() =>
  props.opciones.find(i => i.id_instructor === props.modelValue) ?? null
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

  const match = props.opciones.find(i =>
    i.nombre_completo.toLowerCase().startsWith(keyBuffer.value)
  )
  if (match) {
    highlightId.value = match.id_instructor
    document.getElementById(`${uid}-opt-${match.id_instructor}`)?.scrollIntoView({ block: 'nearest' })
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

// ─── Avatares ────────────────────────────────────────────────────────────
const AVATAR_GRADIENTS = [
  'from-blue-500 to-indigo-600',
  'from-violet-500 to-purple-600',
  'from-emerald-500 to-teal-600',
  'from-rose-500 to-pink-600',
  'from-amber-500 to-orange-600',
  'from-cyan-500 to-sky-600',
]
function getInitials(nombre) {
  if (!nombre) return '?'
  return nombre.trim().split(/\s+/).slice(0, 2).map(p => p[0].toUpperCase()).join('')
}
function avatarGradient(nombre) {
  if (!nombre) return AVATAR_GRADIENTS[0]
  const idx = nombre.charCodeAt(0) % AVATAR_GRADIENTS.length
  return AVATAR_GRADIENTS[idx]
}

const padCls = computed(() => props.size === 'sm' ? 'px-3 py-2' : 'px-4 py-3')
</script>

<template>
  <div ref="rootRef" class="relative">
    <label v-if="showLabel" class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">
      {{ label }}
    </label>

    <button
      type="button"
      @click.stop="toggle"
      :class="[
        'w-full flex items-center gap-3 rounded-xl border text-sm font-semibold transition-all duration-150 text-left focus:outline-none',
        padCls,
        error
          ? 'border-red-300 bg-red-50'
          : open
            ? 'border-emerald-400 bg-white shadow-md ring-2 ring-emerald-400/20'
            : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white'
      ]"
    >
      <div
        v-if="seleccionado"
        :class="['w-8 h-8 rounded-full bg-linear-to-br flex items-center justify-center shrink-0 text-white text-xs font-black', avatarGradient(seleccionado.nombre_completo)]"
      >
        {{ getInitials(seleccionado.nombre_completo) }}
      </div>
      <span v-else class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
        </svg>
      </span>

      <span :class="['flex-1 truncate', seleccionado ? 'text-slate-800' : 'text-slate-400']">
        {{ seleccionado?.nombre_completo ?? placeholder }}
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
          <span class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </span>
          <span class="font-semibold italic">Sin instructor</span>
        </button>
        <div v-if="allowClear" class="border-t border-slate-100 my-1" />

        <div v-if="opciones.length === 0" class="px-4 py-4 text-xs text-slate-400 text-center font-medium">
          {{ emptyMessage }}
        </div>

        <button
          v-for="inst in opciones"
          :key="inst.id_instructor"
          :id="`${uid}-opt-${inst.id_instructor}`"
          type="button"
          @click.stop="seleccionar(inst.id_instructor)"
          :class="[
            'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors text-left focus:outline-none',
            modelValue === inst.id_instructor
              ? 'bg-emerald-50 text-emerald-700'
              : highlightId === inst.id_instructor
                ? 'bg-emerald-50/70 text-emerald-600'
                : 'text-slate-700 hover:bg-emerald-50'
          ]"
        >
          <div :class="['w-8 h-8 rounded-full bg-linear-to-br flex items-center justify-center text-white text-xs font-black shrink-0', avatarGradient(inst.nombre_completo)]">
            {{ getInitials(inst.nombre_completo) }}
          </div>
          <span class="font-semibold truncate flex-1">{{ inst.nombre_completo }}</span>
          <svg
            v-if="modelValue === inst.id_instructor"
            class="w-4 h-4 text-emerald-500 shrink-0"
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
