<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  data: {
    type: Array,
    required: true,
    default: () => []
  },
  filename: {
    type: String,
    default: 'export'
  },
  columns: {
    type: Array,
    required: true,
    default: () => []
  }
})

const exportToCsv = () => {
  if (!props.data || !props.data.length) {
    console.warn('No hay datos para exportar')
    return
  }

  // Extraer las cabeceras a partir del label o field de columns
  const headers = props.columns.map(col => {
    if (typeof col === 'string') return col
    return col.label || col.field || ''
  })

  // Extraer las filas según los campos definidos en columns
  const rows = props.data.map(row => {
    return props.columns.map(col => {
      const field = typeof col === 'string' ? col : col.field
      let val = row[field]

      if (val === null || val === undefined) val = ''

      const stringVal = String(val)
      // Escapar comas o comillas dobles
      if (stringVal.includes(',') || stringVal.includes('"') || stringVal.includes('\n')) {
        return `"${stringVal.replace(/"/g, '""')}"`
      }
      return stringVal
    })
  })

  // Ensamblar el CSV final
  const csvString = [
    headers.join(','),
    ...rows.map(r => r.join(','))
  ].join('\n')

  // Crear el blob con BOM (\uFEFF) para que Excel reconozca el UTF-8
  const blob = new Blob(["\uFEFF" + csvString], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)

  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', `${props.filename}.csv`)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)

  // Liberar el objeto URL
  setTimeout(() => URL.revokeObjectURL(url), 100)
}
</script>

<template>
  <button @click="exportToCsv"
    class="flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-emerald-600/20 active:scale-95 focus:outline-none"
    title="Exportar a CSV" type="button">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 6c0-1.1.9-2 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Z" />
      <path d="M4 12h16" />
      <path d="M12 4v16" />
    </svg>
    <slot>Exportar CSV</slot>
  </button>
</template>
