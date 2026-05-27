import { ref } from 'vue'

const isDark = ref(
  localStorage.getItem('theme') === 'dark' ||
  (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)
)

const updateTheme = () => {
  if (isDark.value) {
    document.documentElement.classList.add('app-dark')
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('app-dark')
    document.documentElement.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  }
}

// Ejecutar inmediatamente al cargar el módulo para evitar parpadeos
if (typeof window !== 'undefined') {
  updateTheme()
}

export const useDarkMode = () => {
  const toggle = () => {
    isDark.value = !isDark.value
    updateTheme()
  }

  return {
    isDark,
    toggle
  }
}
