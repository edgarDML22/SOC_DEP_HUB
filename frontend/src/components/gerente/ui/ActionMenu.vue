<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'

/**
 * ActionMenu — dropdown flotante de acciones para tablas y cards del panel admin.
 *
 * Props:
 *   items: Array<{ label, icon?, action, destructive?, disabled?, separator?, customClass? }>
 *   align: 'left' | 'right'  (default: 'right')
 *   disabled: Boolean (default: false)
 *
 * Uso:
 *   <ActionMenu :items="menuItems" align="right" />
 */
const props = defineProps({
  items: { type: Array, required: true },
  align: { type: String, default: 'right' },
  disabled: { type: Boolean, default: false },
})

const isOpen = ref(false)
const triggerRef = ref(null)
const dropdownRef = ref(null)
const loadingIdx = ref(-1)
const placement = ref('bottom') // 'bottom' | 'top'
const dropdownStyle = ref({})
// Momento en que se abrió: los scrolls de los primeros instantes los provoca el reacomodo del
// layout (p. ej. la topbar móvil que se oculta al llegar al final), no el usuario.
let openedAt = 0

const calculatePosition = () => {
  if (!triggerRef.value) return
  const rect = triggerRef.value.getBoundingClientRect()
  const menuWidth = 208 // w-52 = 13rem = 208px

  // Estimación inicial de altura según ítems
  let estimatedHeight = 16
  props.items.forEach(item => {
    estimatedHeight += item.separator ? 9 : 42
  })
  if (estimatedHeight < 80) estimatedHeight = 80

  const spaceBelow = window.innerHeight - rect.bottom
  const spaceAbove = rect.top

  let top = 0
  // Si no cabe abajo y hay más espacio arriba, abrir HACIA ARRIBA
  if (spaceBelow < estimatedHeight + 16 && spaceAbove > spaceBelow) {
    placement.value = 'top'
    top = rect.top - estimatedHeight - 6
    if (top < 8) top = 8
  } else {
    placement.value = 'bottom'
    top = rect.bottom + 6
    if (top + estimatedHeight > window.innerHeight - 8) {
      top = Math.max(8, window.innerHeight - estimatedHeight - 8)
    }
  }

  // Alineación horizontal
  let left = props.align === 'left' ? rect.left : rect.right - menuWidth

  // Limitar dentro del viewport
  const margin = 8
  if (left + menuWidth > window.innerWidth - margin) {
    left = window.innerWidth - menuWidth - margin
  }
  if (left < margin) {
    left = margin
  }

  dropdownStyle.value = {
    top: `${Math.round(top)}px`,
    left: `${Math.round(left)}px`,
    minWidth: `${menuWidth}px`,
    maxWidth: `calc(100vw - ${margin * 2}px)`,
  }
}

const toggle = async () => {
  if (props.disabled) return
  if (isOpen.value) {
    close()
    return
  }

  calculatePosition()
  isOpen.value = true
  openedAt = performance.now()

  await nextTick()
  if (dropdownRef.value && triggerRef.value) {
    const rect = triggerRef.value.getBoundingClientRect()
    const realHeight = dropdownRef.value.offsetHeight
    const spaceBelow = window.innerHeight - rect.bottom
    const spaceAbove = rect.top

    let top = 0
    if (spaceBelow < realHeight + 16 && spaceAbove > spaceBelow) {
      placement.value = 'top'
      top = rect.top - realHeight - 6
      if (top < 8) top = 8
    } else {
      placement.value = 'bottom'
      top = rect.bottom + 6
      if (top + realHeight > window.innerHeight - 8) {
        top = Math.max(8, window.innerHeight - realHeight - 8)
      }
    }
    // Ancho real (el menú crece con textos largos): se recalcula la posición horizontal
    const realWidth = dropdownRef.value.offsetWidth
    const margin = 8
    let left = props.align === 'left' ? rect.left : rect.right - realWidth
    left = Math.min(Math.max(left, margin), window.innerWidth - realWidth - margin)
    dropdownStyle.value = {
      ...dropdownStyle.value,
      top: `${Math.round(top)}px`,
      left: `${Math.round(left)}px`,
    }
  }
}

const close = () => {
  isOpen.value = false
}

const handleAction = async (item, idx) => {
  if (item.disabled || loadingIdx.value !== -1) return

  if (item.action) {
    const result = item.action()
    if (result instanceof Promise) {
      loadingIdx.value = idx
      try {
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
  if (!isOpen.value) return
  if (triggerRef.value && triggerRef.value.contains(e.target)) return
  if (dropdownRef.value && dropdownRef.value.contains(e.target)) return
  close()
}

const onScroll = () => {
  if (!isOpen.value) return
  // Recién abierto: se reubica junto al botón en lugar de cerrarse
  if (performance.now() - openedAt < 400) {
    calculatePosition()
    return
  }
  close()
}

const onResize = () => {
  if (isOpen.value) close()
}

const onKeyDown = (e) => {
  if (e.key === 'Escape' && isOpen.value) close()
}

onMounted(() => {
  document.addEventListener('mousedown', onClickOutside)
  window.addEventListener('scroll', onScroll, true) // capture: true detecta scroll en cualquier contenedor
  window.addEventListener('resize', onResize)
  window.addEventListener('keydown', onKeyDown)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', onClickOutside)
  window.removeEventListener('scroll', onScroll, true)
  window.removeEventListener('resize', onResize)
  window.removeEventListener('keydown', onKeyDown)
})
</script>

<template>
  <div class="relative inline-block">

    <!-- Trigger: botón ⋮ -->
    <button
      ref="triggerRef"
      @click.stop="toggle"
      :disabled="disabled"
      class="w-9 h-9 rounded-xl flex items-center justify-center transition-all
             bg-surface-100 hover:bg-surface-200 text-surface-500 hover:text-surface-700
             focus:outline-none focus:ring-2 focus:ring-primary-500/40 cursor-pointer
             disabled:opacity-40 disabled:cursor-not-allowed"
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

    <!-- Dropdown Teleportado al body para no ser cortado por tablas ni alterar el overflow del layout -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-150 ease-out"
        :enter-from-class="placement === 'top' ? 'opacity-0 scale-95 translate-y-1' : 'opacity-0 scale-95 -translate-y-1'"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition-all duration-100 ease-in"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95"
      >
        <div
          v-if="isOpen"
          ref="dropdownRef"
          :style="dropdownStyle"
          class="fixed z-[9999] bg-white rounded-2xl shadow-2xl shadow-surface-900/20
                 border border-surface-200 overflow-hidden py-1 select-none"
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
                     transition-colors text-left cursor-pointer
                     disabled:opacity-40 disabled:cursor-not-allowed"
              :class="item.customClass
                ? item.customClass
                : item.destructive
                  ? 'text-red-600 hover:bg-red-50'
                  : 'text-surface-700 hover:bg-surface-50'"
              type="button"
            >
              <!-- Icono opcional -->
              <span v-if="loadingIdx === idx" class="w-4 h-4 shrink-0 flex items-center justify-center">
                <LoadingSpinner size="sm" :color="item.destructive ? 'danger' : 'surface'" />
              </span>
              <span v-else-if="item.icon" class="w-4 h-4 shrink-0 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full" v-html="item.icon" />
              <span v-else class="w-4 h-4 shrink-0 rounded bg-surface-100" />
              <span class="min-w-0 pr-2">{{ item.label }}</span>
            </button>
          </template>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
