import { ref, onMounted, onBeforeUnmount } from 'vue'

/**
 * Oculta una barra al hacer scroll hacia abajo y la muestra al subir.
 * Escucha el scroll de un contenedor (no de window) y solo actúa por debajo de `maxWidth`.
 *
 * @param {import('vue').Ref<HTMLElement|null>} containerRef contenedor con overflow-y-auto
 * @param {{ maxWidth?: number, threshold?: number, topOffset?: number, lockMs?: number }} options
 */
export function useHideOnScroll(containerRef, options = {}) {
  const {
    maxWidth = 1024, // a partir de aquí (lg) la barra siempre se muestra
    threshold = 8, // desplazamiento mínimo para cambiar de estado
    topOffset = 64, // cerca del inicio siempre visible
    lockMs = 350, // duración de la animación: se ignora el scroll mientras ocurre
  } = options

  const hidden = ref(false)
  let lastY = 0
  let lockedUntil = 0
  let ticking = false

  const isActive = () => window.innerWidth < maxWidth

  const setHidden = (value) => {
    if (hidden.value === value) return
    hidden.value = value
    // Al ocultar/mostrar cambia el alto del contenedor y el navegador puede reajustar
    // scrollTop (sobre todo al final de la página); sin este candado la barra parpadea.
    lockedUntil = performance.now() + lockMs
  }

  const evaluate = () => {
    ticking = false
    const el = containerRef.value
    if (!el) return

    const y = el.scrollTop
    if (!isActive()) {
      setHidden(false)
      lastY = y
      return
    }
    if (performance.now() < lockedUntil) {
      lastY = y
      return
    }

    const delta = y - lastY
    if (y <= topOffset) setHidden(false)
    else if (delta > threshold) setHidden(true)
    else if (delta < -threshold) setHidden(false)
    else return // movimiento pequeño: se acumula hasta superar el umbral

    lastY = y
  }

  const onScroll = () => {
    if (ticking) return
    ticking = true
    requestAnimationFrame(evaluate)
  }

  const onResize = () => {
    if (!isActive()) setHidden(false)
  }

  /** Muestra la barra y reinicia la referencia (p. ej. al cambiar de ruta). */
  const reset = () => {
    hidden.value = false
    lastY = containerRef.value?.scrollTop ?? 0
  }

  onMounted(() => {
    containerRef.value?.addEventListener('scroll', onScroll, { passive: true })
    window.addEventListener('resize', onResize)
  })

  onBeforeUnmount(() => {
    containerRef.value?.removeEventListener('scroll', onScroll)
    window.removeEventListener('resize', onResize)
  })

  return { hidden, reset }
}
