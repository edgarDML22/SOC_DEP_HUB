<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import IconClock from '@/components/icons/IconClock.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  opciones:   { type: Array,  required: true },
  placeholder:{ type: String, default: '--:--' },
  label:      { type: String, default: 'Hora' },
  error:      { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const open    = ref(false)
const rootRef = ref(null)

function toggle() { open.value = !open.value }
function close()  { open.value = false }

function seleccionar(hora) {
  emit('update:modelValue', hora)
  close()
}

function onClickOutside(e) {
  if (!rootRef.value) return
  if (!rootRef.value.contains(e.target)) close()
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<template>
  <div ref="rootRef" class="relative">
    <label class="block text-[10px] uppercase font-black tracking-widest text-slate-600 mb-1.5">{{ label }}</label>

    <button
      type="button"
      @click.stop="toggle"
      :class="[
        'w-full flex items-center gap-2 rounded-xl border px-3 py-3 text-sm font-bold transition-all duration-150 text-left',
        error
          ? 'border-red-300 bg-red-50'
          : open
            ? 'border-primary-400 bg-white shadow-md ring-2 ring-primary-400/20'
            : 'border-slate-200 bg-white hover:border-slate-300'
      ]"
    >
      <IconClock :class="['w-4 h-4 shrink-0', modelValue ? 'text-primary-500' : 'text-slate-400']" />
      <span :class="['flex-1 tabular-nums', modelValue ? 'text-slate-800' : 'text-slate-400']">
        {{ modelValue || placeholder }}
      </span>
      <svg
        class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-150"
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
      <div class="max-h-48 overflow-y-auto py-1">
        <button
          v-for="hora in opciones"
          :key="hora"
          type="button"
          @click.stop="seleccionar(hora)"
          :class="[
            'w-full flex items-center gap-2.5 px-4 py-2 text-sm transition-colors text-left tabular-nums',
            modelValue === hora
              ? 'bg-primary-50 text-primary-700 font-black'
              : 'text-slate-700 hover:bg-slate-50 font-semibold'
          ]"
        >
          <IconClock :class="['w-3.5 h-3.5 shrink-0', modelValue === hora ? 'text-primary-500' : 'text-slate-300']" />
          {{ hora }}
          <svg
            v-if="modelValue === hora"
            class="w-3.5 h-3.5 text-primary-500 shrink-0 ml-auto"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </button>
      </div>
    </div>

    <p v-if="error" class="text-red-500 text-[10px] mt-1 font-medium flex items-center gap-1">
      <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
      {{ error }}
    </p>
  </div>
</template>
