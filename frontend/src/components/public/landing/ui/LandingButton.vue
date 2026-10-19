<template>
  <component
    :is="tag"
    v-bind="linkAttrs"
    :class="[baseClass, sizeClasses[size], variantClasses[variant], block ? 'w-full' : '']"
  >
    <slot />
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

/**
 * Botón único de la landing. Renderiza RouterLink (to), <a> (href) o <button>,
 * para que todos los CTAs compartan forma, tamaños y estados.
 */
const props = withDefaults(
  defineProps<{
    to?: string
    href?: string
    type?: 'button' | 'submit'
    variant?: 'primary' | 'outline' | 'white' | 'glass'
    size?: 'sm' | 'md' | 'lg'
    block?: boolean
  }>(),
  { type: 'button', variant: 'primary', size: 'md', block: false }
)

const tag = computed(() => (props.to ? RouterLink : props.href ? 'a' : 'button'))

const linkAttrs = computed(() => {
  if (props.to) return { to: props.to }
  if (props.href) return { href: props.href }
  return { type: props.type }
})

const baseClass =
  'lp-body inline-flex items-center justify-center gap-2 rounded-full font-bold whitespace-nowrap transition-all duration-200 select-none active:scale-[0.97] focus:outline-none focus-visible:ring-4'

const sizeClasses = {
  sm: 'px-4 py-2 text-[13px]',
  md: 'px-6 py-3 text-sm',
  lg: 'px-7 py-3.5 text-[15px]'
}

const variantClasses = {
  primary:
    'bg-primary-700 text-white shadow-[0_12px_24px_-12px_rgba(29,78,216,0.75)] hover:-translate-y-0.5 hover:bg-primary-800 focus-visible:ring-primary-200',
  outline:
    'border border-primary-200 bg-lp-paper text-primary-700 hover:border-primary-400 hover:bg-primary-50 focus-visible:ring-primary-100',
  white:
    'bg-lp-paper text-primary-700 shadow-[0_12px_24px_-12px_rgba(15,23,42,0.45)] hover:-translate-y-0.5 hover:bg-primary-50 focus-visible:ring-white/50',
  glass:
    'border border-white/40 bg-white/10 text-white backdrop-blur-sm hover:bg-white/20 focus-visible:ring-white/40'
}
</script>
