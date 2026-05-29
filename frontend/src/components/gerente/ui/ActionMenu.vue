<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'

/**
 * ActionMenu — dropdown de acciones para cards del panel admin.
 *
 * Props:
 *   items: Array<{ label, icon?, action, destructive?, disabled? }>
 *   align: 'left' | 'right'  (default: 'right')
 *
 * Uso:
 *   <ActionMenu :items="menuItems" />
 *   donde menuItems = [
 *     { label: 'Ver perfil', icon: '...svg...', action: () => router.push(...) },
 *     { label: 'Eliminar', action: () => handleDelete(), destructive: true },
 *   ]
 */
const props = defineProps({
  items: { type: Array, required: true },
  align: { type: String, default: 'right' },
})

const isOpen = ref(false)
const menuRef = ref(null)
const loadingIdx = ref(-1)

const toggle = () => { isOpen.value = !isOpen.value }
const close  = () => { isOpen.value = false }

const handleAction = async (item, idx) => {
  if (item.disabled || loadingIdx.value !== -1) return
  
  if (item.action) {
    const result = item.action()
    if (result instanceof Promise) {
      loadingIdx.value = idx
      try {
        // Aseguramos que el spinner sea visible al menos 400ms para mejor UX
        await Promise.all([result, new Promise(resolve => setTimeout(resolve, 400))])
      } finally {
        loadingIdx.value = -1
        close()
      }
    } else {
      close()
    }
  } else {
    close()
  }
}

const onClickOutside = (e) => {
  if (menuRef.value && !menuRef.value.contains(e.target)) close()
}

watch(isOpen, (val) => {
  if (!menuRef.value) return
  
  // 1. Elevate row z-index and set position relative
  const row = menuRef.value.closest('tr') || menuRef.value.parentElement
  if (row) {
    if (val) {
      row.style.zIndex = '50'
      row.style.position = 'relative'
    } else {
      row.style.zIndex = ''
      row.style.position = ''
    }
  }

  // 2. Adjust overflow of ancestors to prevent clipping the absolute dropdown
  let parent = menuRef.value.parentElement
  while (parent && parent !== document.body) {
    const style = window.getComputedStyle(parent)
    const hasOverflow = 
      style.overflow === 'hidden' || style.overflow === 'auto' || style.overflow === 'scroll' ||
      style.overflowX === 'hidden' || style.overflowX === 'auto' || style.overflowX === 'scroll' ||
      style.overflowY === 'hidden' || style.overflowY === 'auto' || style.overflowY === 'scroll'
      
    if (hasOverflow) {
      if (val) {
        if (parent.dataset.origOverflow === undefined) {
          parent.dataset.origOverflow = parent.style.overflow || ''
          parent.dataset.origOverflowX = parent.style.overflowX || ''
          parent.dataset.origOverflowY = parent.style.overflowY || ''
        }
        parent.style.setProperty('overflow', 'visible', 'important')
        parent.style.setProperty('overflow-x', 'visible', 'important')
        parent.style.setProperty('overflow-y', 'visible', 'important')
      } else {
        if (parent.dataset.origOverflow !== undefined) {
          parent.style.overflow = parent.dataset.origOverflow
          parent.style.overflowX = parent.dataset.origOverflowX
          parent.style.overflowY = parent.dataset.origOverflowY
          delete parent.dataset.origOverflow
          delete parent.dataset.origOverflowX
          delete parent.dataset.origOverflowY
        }
      }
    }
    parent = parent.parentElement
  }
})

onMounted(()  => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="menuRef" class="relative inline-block">

    <!-- Trigger: botón ⋮ -->
    <button
      @click.stop="toggle"
      class="w-9 h-9 rounded-xl flex items-center justify-center transition-all
             bg-surface-100 hover:bg-surface-200 text-surface-500 hover:text-surface-700
             focus:outline-none focus:ring-2 focus:ring-primary-500/40"
      :class="{ 'bg-surface-200 text-surface-700': isOpen }"
      aria-label="Más acciones"
      type="button"
    >
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
        <circle cx="12" cy="5"  r="1.5"/>
        <circle cx="12" cy="12" r="1.5"/>
        <circle cx="12" cy="19" r="1.5"/>
      </svg>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 scale-95 translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 translate-y-1"
    >
      <div
        v-if="isOpen"
        class="absolute z-50 top-full mt-2 w-52 bg-white rounded-2xl shadow-2xl shadow-surface-900/15
               border border-surface-100 overflow-hidden py-1"
        :class="align === 'left' ? 'left-0' : 'right-0'"
      >
        <template v-for="(item, idx) in items">
          <!-- Separador -->
          <div v-if="item.separator" :key="'sep-' + idx" class="my-1 border-t border-surface-100" />

          <!-- Ítem de acción -->
          <button
            v-else
            :key="'btn-' + idx"
            @click="handleAction(item, idx)"
            :disabled="item.disabled || loadingIdx === idx"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-medium
                   transition-colors text-left
                   disabled:opacity-40 disabled:cursor-not-allowed"
            :class="item.customClass
              ? item.customClass
              : item.destructive
                ? 'text-red-600 hover:bg-red-50'
                : 'text-surface-700 hover:bg-surface-50'"
            type="button"
          >
            <!-- Icono opcional (HTML/SVG raw o slot) -->
            <span v-if="loadingIdx === idx" class="w-4 h-4 shrink-0 flex items-center justify-center">
              <LoadingSpinner size="sm" :color="item.destructive ? 'danger' : 'surface'" />
            </span>
            <span v-else-if="item.icon" class="w-4 h-4 shrink-0 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full" v-html="item.icon" />
            <span v-else class="w-4 h-4 shrink-0 rounded bg-surface-100" />
            <span class="whitespace-nowrap pr-2">{{ item.label }}</span>
          </button>
        </template>
      </div>
    </Transition>
  </div>
</template>
