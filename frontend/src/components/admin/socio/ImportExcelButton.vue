<script setup>
import { ref, computed } from 'vue'
import api from '@/services/api'

const emit = defineEmits(['import-complete'])

// ── Estado ──────────────────────────────────────────────
const fileInput = ref(null)
const selectedFile = ref(null)
const previewData = ref(null)
const showPreviewModal = ref(false)
const showResultsModal = ref(false)
const isUploading = ref(false)
const importResults = ref(null)
const parseError = ref('')

// ── Seleccionar archivo ─────────────────────────────────
const openFilePicker = () => {
  fileInput.value?.click()
}

const onFileSelected = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  // Validar tipo
  const validTypes = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
    'application/vnd.ms-excel', // .xls
    'text/csv',
    'application/csv',
  ]
  const validExts = ['.xlsx', '.xls', '.csv']
  const ext = '.' + file.name.split('.').pop().toLowerCase()

  if (!validExts.includes(ext) && !validTypes.includes(file.type)) {
    parseError.value = 'Solo se aceptan archivos .xlsx, .xls o .csv'
    return
  }

  if (file.size > 10 * 1024 * 1024) {
    parseError.value = 'El archivo no debe exceder 10 MB.'
    return
  }

  selectedFile.value = file
  parseError.value = ''

  // Preview: leer primeras filas
  await parsePreview(file)
  showPreviewModal.value = true
}

const parsePreview = async (file) => {
  const ext = file.name.split('.').pop().toLowerCase()

  if (ext === 'csv') {
    const text = await file.text()
    const lines = text.split(/\r?\n/).filter(l => l.trim())
    const headers = lines[0].split(',').map(h => h.trim().replace(/^"|"$/g, ''))
    const rows = lines.slice(1, 6).map(line => {
      return line.split(',').map(cell => cell.trim().replace(/^"|"$/g, ''))
    })
    previewData.value = { headers, rows, totalRows: lines.length - 1 }
  } else {
    // Para Excel, solo mostrar info del archivo
    previewData.value = {
      headers: ['(Vista previa no disponible para archivos Excel)'],
      rows: [],
      totalRows: '?',
      isExcel: true,
    }
  }
}

// ── Confirmar importación ───────────────────────────────
const confirmImport = async () => {
  if (!selectedFile.value) return

  isUploading.value = true
  showPreviewModal.value = false

  try {
    const formData = new FormData()
    formData.append('archivo', selectedFile.value)

    const response = await api.post('/socios/import-excel', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    importResults.value = response.data
    showResultsModal.value = true
    emit('import-complete')
  } catch (error) {
    const msg = error.response?.data?.message || 'Error inesperado al importar.'
    importResults.value = {
      success: false,
      resumen: null,
      errores: [{ hoja: '-', fila: 0, campo: '-', valor: '-', mensaje: msg }],
      actualizaciones: [],
    }
    showResultsModal.value = true
  } finally {
    isUploading.value = false
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
  }
}

// ── Cancelar ────────────────────────────────────────────
const cancelImport = () => {
  showPreviewModal.value = false
  selectedFile.value = null
  previewData.value = null
  parseError.value = ''
  if (fileInput.value) fileInput.value.value = ''
}

const closeResults = () => {
  showResultsModal.value = false
  importResults.value = null
}

// ── Computed helpers ────────────────────────────────────
const totalActualizados = computed(() => {
  if (!importResults.value?.resumen) return 0
  const r = importResults.value.resumen
  return (r.socios?.actualizados || 0) + (r.familiares?.actualizados || 0)
})

const totalErrores = computed(() => {
  if (!importResults.value?.resumen) return 0
  const r = importResults.value.resumen
  return (r.socios?.errores || 0) + (r.familiares?.errores || 0)
})

const totalSinCambios = computed(() => {
  if (!importResults.value?.resumen) return 0
  const r = importResults.value.resumen
  return (r.socios?.sin_cambios || 0) + (r.familiares?.sin_cambios || 0)
})
</script>

<template>
  <!-- Botón de Importar -->
  <button
    @click="openFilePicker"
    :disabled="isUploading"
    class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl transition-all duration-200 shadow-md shadow-blue-600/20 active:scale-95 focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed"
    title="Importar desde Excel/CSV"
    type="button"
    id="btn-import-excel"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
      <polyline points="17 8 12 3 7 8" />
      <line x1="12" y1="3" x2="12" y2="15" />
    </svg>
    <template v-if="isUploading">
      <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
      </svg>
      Importando…
    </template>
    <template v-else>
      <slot>Importar Excel</slot>
    </template>
  </button>

  <!-- Input oculto -->
  <input
    ref="fileInput"
    type="file"
    accept=".xlsx,.xls,.csv"
    class="hidden"
    @change="onFileSelected"
  />

  <!-- Error de parseo -->
  <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
    leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
    <div v-if="parseError" class="fixed bottom-6 right-6 bg-red-600 text-white text-sm font-bold px-5 py-3 rounded-xl shadow-lg z-[9999] flex items-center gap-2">
      <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
      </svg>
      {{ parseError }}
      <button @click="parseError = ''" class="ml-2 hover:text-red-200 transition-colors">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
  </Transition>

  <!-- ═══ MODAL: PREVIEW & CONFIRMACIÓN ═══ -->
  <Teleport to="body">
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showPreviewModal" class="fixed inset-0 z-[9998] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="cancelImport"></div>

        <!-- Modal -->
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden z-10">
          <!-- Header -->
          <div class="p-6 pb-4 border-b border-surface-200">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                  <line x1="16" y1="13" x2="8" y2="13"/>
                  <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-black text-surface-900">Confirmar Importación</h3>
                <p class="text-xs font-medium text-surface-500">{{ selectedFile?.name }} — {{ previewData?.totalRows || '?' }} filas detectadas</p>
              </div>
            </div>
          </div>

          <!-- Body -->
          <div class="p-6 overflow-y-auto flex-1 space-y-4">
            <!-- Advertencia -->
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex gap-3">
              <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
              </svg>
              <div>
                <p class="text-sm font-bold text-amber-800">Importación y Actualización Atómica (UPSERT)</p>
                <p class="text-xs text-amber-700 mt-1">Los socios nuevos se insertarán (generando automáticamente sus cuentas de acceso) y los existentes se actualizarán. Si se detecta <strong>un solo error</strong> en cualquier fila, se abortará toda la operación y no se modificará nada.</p>
              </div>
            </div>

            <!-- Preview de CSV -->
            <div v-if="previewData && !previewData.isExcel" class="overflow-x-auto">
              <p class="text-xs font-black text-surface-400 uppercase tracking-widest mb-2">Vista previa (primeras 5 filas)</p>
              <table class="w-full text-xs border-collapse">
                <thead>
                  <tr>
                    <th v-for="(h, i) in previewData.headers" :key="i"
                      class="text-left px-3 py-2 bg-surface-100 border-b border-surface-200 font-bold text-surface-700 whitespace-nowrap">
                      {{ h }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, ri) in previewData.rows" :key="ri" class="border-b border-surface-100 hover:bg-surface-50">
                    <td v-for="(cell, ci) in row" :key="ci" class="px-3 py-2 text-surface-600 whitespace-nowrap max-w-[200px] truncate">
                      {{ cell || '—' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Info para Excel -->
            <div v-else-if="previewData?.isExcel" class="text-center py-6">
              <div class="w-14 h-14 rounded-2xl bg-emerald-100 flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                </svg>
              </div>
              <p class="text-sm font-bold text-surface-700">Archivo Excel seleccionado</p>
              <p class="text-xs text-surface-500 mt-1">El archivo será procesado en el servidor de forma extremadamente optimizada.</p>
            </div>

            <!-- Formato esperado -->
            <div class="bg-surface-50 rounded-2xl p-4 border border-surface-200">
              <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest mb-2">Formato esperado del archivo (Hoja Única)</p>
              <div class="space-y-2">
                <div>
                  <p class="text-xs font-bold text-surface-700">Columnas Requeridas (en cualquier orden):</p>
                  <p class="text-[11px] text-surface-500 font-mono leading-relaxed bg-surface-100 p-2.5 rounded-xl border border-surface-200 mt-1">
                    numero_accion | rol (titular / miembro) | nombre_completo | tipo_accion | estatus_accion | genero | fecha_nacimiento | parentesco | email
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="p-6 pt-4 border-t border-surface-200 flex justify-end gap-3">
            <button @click="cancelImport"
              class="px-5 py-2.5 text-sm font-bold text-surface-600 bg-surface-100 hover:bg-surface-200 rounded-xl transition-all">
              Cancelar
            </button>
            <button @click="confirmImport"
              class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md shadow-blue-600/20 active:scale-95">
              Confirmar Importación
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- ═══ MODAL: RESULTADOS ═══ -->
  <Teleport to="body">
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
      enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="showResultsModal" class="fixed inset-0 z-[9998] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeResults"></div>

        <!-- Modal -->
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden z-10">
          <!-- Header -->
          <div class="p-6 pb-4 border-b border-surface-200">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                :class="importResults?.success ? 'bg-emerald-100' : 'bg-red-100'">
                <svg v-if="importResults?.success" class="w-5 h-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
                <svg v-else class="w-5 h-5 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-black text-surface-900">
                  {{ importResults?.success ? 'Importación Completada' : 'Error en la Importación' }}
                </h3>
              </div>
            </div>
          </div>

          <!-- Body -->
          <div class="p-6 overflow-y-auto flex-1 space-y-5">

            <!-- Resumen con métricas -->
            <div v-if="importResults?.resumen" class="grid grid-cols-3 gap-3">
              <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-center">
                <p class="text-2xl font-black text-emerald-700">{{ totalActualizados }}</p>
                <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mt-1">Actualizados</p>
              </div>
              <div class="bg-surface-50 border border-surface-200 rounded-2xl p-4 text-center">
                <p class="text-2xl font-black text-surface-600">{{ totalSinCambios }}</p>
                <p class="text-[10px] font-bold text-surface-500 uppercase tracking-wider mt-1">Sin Cambios</p>
              </div>
              <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-center">
                <p class="text-2xl font-black text-red-600">{{ totalErrores }}</p>
                <p class="text-[10px] font-bold text-red-500 uppercase tracking-wider mt-1">Errores</p>
              </div>
            </div>

            <!-- Desglose por hoja -->
            <div v-if="importResults?.resumen" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div v-if="importResults.resumen.socios?.total > 0" class="bg-surface-50 rounded-2xl p-4 border border-surface-200">
                <p class="text-xs font-black text-surface-700 mb-2">Socios Titulares</p>
                <div class="space-y-1 text-xs text-surface-600">
                  <p>Total filas: <span class="font-bold text-surface-900">{{ importResults.resumen.socios.total }}</span></p>
                  <p>Actualizados: <span class="font-bold text-emerald-700">{{ importResults.resumen.socios.actualizados }}</span></p>
                  <p>Sin cambios: <span class="font-bold text-surface-500">{{ importResults.resumen.socios.sin_cambios }}</span></p>
                  <p>Errores: <span class="font-bold text-red-600">{{ importResults.resumen.socios.errores }}</span></p>
                </div>
              </div>
              <div v-if="importResults.resumen.familiares?.total > 0" class="bg-surface-50 rounded-2xl p-4 border border-surface-200">
                <p class="text-xs font-black text-surface-700 mb-2">Miembros Familiares</p>
                <div class="space-y-1 text-xs text-surface-600">
                  <p>Total filas: <span class="font-bold text-surface-900">{{ importResults.resumen.familiares.total }}</span></p>
                  <p>Actualizados: <span class="font-bold text-emerald-700">{{ importResults.resumen.familiares.actualizados }}</span></p>
                  <p>Sin cambios: <span class="font-bold text-surface-500">{{ importResults.resumen.familiares.sin_cambios }}</span></p>
                  <p>Errores: <span class="font-bold text-red-600">{{ importResults.resumen.familiares.errores }}</span></p>
                </div>
              </div>
            </div>

            <!-- Lista de actualizaciones -->
            <div v-if="importResults?.actualizaciones?.length" class="space-y-2">
              <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Registros Actualizados</p>
              <div class="max-h-[200px] overflow-y-auto bg-emerald-50 rounded-2xl border border-emerald-200 p-3 space-y-2">
                <div v-for="(act, i) in importResults.actualizaciones" :key="i"
                  class="flex items-start gap-2 text-xs">
                  <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                  <div>
                    <span class="font-bold text-emerald-800">
                      <span class="text-emerald-500 font-mono text-[10px]">[{{ act.hoja }}]</span>
                      {{ act.nombre || act.numero_accion }}
                    </span>
                    <span class="text-emerald-600"> — {{ act.campos_actualizados.join(', ') }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Lista de errores -->
            <div v-if="importResults?.errores?.length" class="space-y-2">
              <p class="text-[10px] font-black text-surface-400 uppercase tracking-widest">Errores Encontrados</p>
              <div class="max-h-[200px] overflow-y-auto bg-red-50 rounded-2xl border border-red-200 p-3 space-y-2">
                <div v-for="(err, i) in importResults.errores" :key="i"
                  class="flex items-start gap-2 text-xs">
                  <svg class="w-3.5 h-3.5 text-red-500 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                  </svg>
                  <div>
                    <span class="font-bold text-red-700">
                      <span class="text-red-400 font-mono text-[10px]">[{{ err.hoja }}] Fila {{ err.fila }}</span>
                      {{ err.campo }}
                    </span>
                    <span v-if="err.valor && err.valor !== '-'" class="text-red-500"> = "{{ err.valor }}"</span>
                    <p class="text-red-600 mt-0.5">{{ err.mensaje }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="p-6 pt-4 border-t border-surface-200 flex justify-end">
            <button @click="closeResults"
              class="px-6 py-2.5 text-sm font-bold text-white bg-surface-900 hover:bg-surface-800 rounded-xl transition-all shadow-md active:scale-95">
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
