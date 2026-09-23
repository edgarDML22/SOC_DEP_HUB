<script setup>
import { ref, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: true },
  title:      { type: String, required: true },
  hint:       { type: String, default: '' },
  badge:      { type: [String, Number], default: null },
  badgeTone:  { type: String, default: 'slate' }, // slate | primary | emerald | red | amber
  dotClass:   { type: String, default: '' }, // e.g. 'bg-emerald-500' or 'bg-red-500'
  dense:      { type: Boolean, default: false },
  iconTone:   { type: String, default: 'slate' }, // slate | primary | emerald | amber | violet
})
const emit = defineEmits(['update:modelValue'])

const isOpen = ref(props.modelValue)
watch(() => props.modelValue, async v => {
  if (v === isOpen.value) return
  isOpen.value = v
  await nextTick()
  if (!bodyRef.value) {
    maxH.value = v ? 'none' : '0px'
    return
  }
  if (v) {
    maxH.value = bodyRef.value.scrollHeight + 'px'
    setTimeout(() => { if (isOpen.value) maxH.value = 'none' }, 220)
  } else {
    maxH.value = bodyRef.value.scrollHeight + 'px'
    requestAnimationFrame(() => { maxH.value = '0px' })
  }
})

const bodyRef = ref(null)
const maxH = ref(props.modelValue ? 'none' : '0px')

async function toggle() {
  isOpen.value = !isOpen.value
  emit('update:modelValue', isOpen.value)
  await nextTick()
  if (!bodyRef.value) return
  if (isOpen.value) {
    maxH.value = bodyRef.value.scrollHeight + 'px'
    setTimeout(() => { if (isOpen.value) maxH.value = 'none' }, 220)
  } else {
    maxH.value = bodyRef.value.scrollHeight + 'px'
    requestAnimationFrame(() => { maxH.value = '0px' })
  }
}

const badgeClass = {
  slate:   'bg-slate-100 text-slate-500',
  primary: 'bg-primary-50 text-primary-700',
  emerald: 'bg-emerald-50 text-emerald-700',
  red:     'bg-red-50 text-red-700',
  amber:   'bg-amber-50 text-amber-700',
}

const iconToneClass = {
  slate:   'bg-slate-50 border-slate-100 text-slate-400',
  primary: 'bg-primary-50 border-primary-100 text-primary-600',
  emerald: 'bg-emerald-50 border-emerald-100 text-emerald-600',
  amber:   'bg-amber-50 border-amber-100 text-amber-600',
  violet:  'bg-violet-50 border-violet-100 text-violet-600',
}
</script>

<template>
  <section class="border-b border-slate-100 last:border-b-0">
    <button
      type="button"
      @click="toggle"
      class="group w-full flex items-center gap-3.5 text-left transition-all duration-200 hover:bg-slate-50/70 focus:outline-none focus:bg-slate-50/70"
      :class="[
        dense ? 'px-5 py-3' : 'px-5 py-4',
        isOpen ? 'bg-slate-50/30' : ''
      ]"
    >
      <!-- Contenedor del chevron izquierdo estilizado y dinámico -->
      <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 border border-slate-200/60 bg-slate-50/50 shadow-sm transition-all duration-200 group-hover:bg-white group-hover:border-slate-300">
        <svg
          class="w-3 h-3 text-slate-400 transition-transform duration-200"
          :class="isOpen ? 'rotate-90 text-primary-500' : 'group-hover:text-slate-600'"
          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
      </div>

      <div class="flex-1 min-w-0 flex items-center gap-2">
        <span v-if="dotClass" :class="['w-2 h-2 rounded-full shrink-0 ring-4 ring-slate-100/50 shadow-sm', dotClass]" />
        <div class="min-w-0">
          <p class="text-sm font-extrabold text-slate-800 leading-tight truncate">{{ title }}</p>
          <p v-if="hint" class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5 truncate">
            {{ hint }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <!-- Badge -->
        <span
          v-if="badge !== null && badge !== ''"
          :class="['text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full shrink-0', badgeClass[badgeTone] ?? badgeClass.slate]"
        >{{ badge }}</span>

        <!-- Slot para ícono decorativo de sección con diseño premium de tarjeta -->
        <div
          v-if="$slots.icon"
          class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 border transition-all duration-200 shadow-sm group-hover:scale-105"
          :class="iconToneClass[iconTone] ?? iconToneClass.slate"
        >
          <slot name="icon" />
        </div>

        <!-- Slot para acciones inline en el header (ej. botón Limpiar) -->
        <span v-if="$slots.actions" @click.stop class="ml-1 shrink-0">
          <slot name="actions" />
        </span>
      </div>
    </button>

    <div
      ref="bodyRef"
      :style="{ maxHeight: maxH, overflow: maxH === 'none' ? 'visible' : 'hidden' }"
      class="transition-[max-height] duration-200 ease-out"
    >
      <div :class="dense ? 'px-5 pb-4' : 'px-5 pb-5'">
        <slot />
      </div>
    </div>
  </section>
</template>

