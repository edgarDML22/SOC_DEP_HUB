<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

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

const toggle = () => { isOpen.value = !isOpen.value }
const close  = () => { isOpen.value = false }

const handleAction = (item) => {
  if (item.disabled) return
  close()
  item.action?.()
}

const onClickOutside = (e) => {
  if (menuRef.value && !menuRef.value.contains(e.target)) close()
}

onMounted(()  => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="menuRef" class="relative inline-block">

    <!-- Trigger: botón ⋮ -->
    <button
      @click.stop="toggle"
      class="w-9 h-9 rounded-xl flex items-center justify-center transition-all
             bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700
             focus:outline-none focus:ring-2 focus:ring-blue-500/40"
      :class="{ 'bg-slate-200 text-slate-700': isOpen }"
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
        class="absolute z-50 top-full mt-2 w-52 bg-white rounded-2xl shadow-2xl shadow-slate-900/15
               border border-slate-100 overflow-hidden py-1"
        :class="align === 'left' ? 'left-0' : 'right-0'"
      >
        <template v-for="(item, idx) in items" :key="idx">
          <!-- Separador -->
          <div v-if="item.separator" class="my-1 border-t border-slate-100" />

          <!-- Ítem de acción -->
          <button
            v-else
            @click="handleAction(item)"
            :disabled="item.disabled"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-medium
                   transition-colors text-left
                   disabled:opacity-40 disabled:cursor-not-allowed"
            :class="item.destructive
              ? 'text-red-600 hover:bg-red-50'
              : 'text-slate-700 hover:bg-slate-50'"
            type="button"
          >
            <!-- Icono opcional (HTML/SVG raw o slot) -->
            <span v-if="item.icon" class="w-4 h-4 shrink-0 flex items-center justify-center" v-html="item.icon" />
            <span v-else class="w-4 h-4 shrink-0 rounded bg-slate-100" />
            <span class="truncate">{{ item.label }}</span>
          </button>
        </template>
      </div>
    </Transition>
  </div>
</template>
