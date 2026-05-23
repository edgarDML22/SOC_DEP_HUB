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
</script>

<template>
  <section class="border-b-2 border-slate-100 last:border-b-0">
    <button
      type="button"
      @click="toggle"
      class="w-full flex items-center gap-3 text-left transition-colors hover:bg-primary-50/40 focus:outline-none focus:bg-primary-50/40"
      :class="dense ? 'px-5 py-3' : 'px-5 py-4'"
    >
      <!-- Flecha en azul primario -->
      <svg
        class="w-3.5 h-3.5 text-primary-500 shrink-0 transition-transform duration-200"
        :class="isOpen ? 'rotate-90' : ''"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
      </svg>

      <div class="flex-1 min-w-0 flex items-center gap-2">
        <span v-if="dotClass" :class="['w-2 h-2 rounded-full shrink-0', dotClass]" />
        <div class="min-w-0">
          <p class="text-sm font-extrabold text-slate-800 leading-tight truncate">{{ title }}</p>
          <p v-if="hint" class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5 truncate">
            {{ hint }}
          </p>
        </div>
      </div>

      <span
        v-if="badge !== null && badge !== ''"
        :class="['text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full shrink-0', badgeClass[badgeTone] ?? badgeClass.slate]"
      >{{ badge }}</span>

      <!-- Slot para ícono decorativo de sección -->
      <span v-if="$slots.icon" class="text-primary-400 shrink-0">
        <slot name="icon" />
      </span>

      <!-- Slot para acciones inline en el header (ej. botón Limpiar) -->
      <span v-if="$slots.actions" @click.stop>
        <slot name="actions" />
      </span>
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
