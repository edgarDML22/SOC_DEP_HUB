<script setup>
import { ref, computed } from 'vue'
import CalendarioPublicadas from './CalendarioPublicadas.vue'
import { IconCalendar, IconGrid } from '@/components/icons'

// ─── Estado local y Mock Data (Monitoreo de Sesiones de esta Semana) ──────
// Esta data representa la estructura requerida. El backend poblará este listado.
const mockSesiones = ref([
  {
    id_sesion: 101,
    estatus: 'DISPONIBLE',
    fecha_real: '2026-05-25',
    disciplina: 'Fútbol',
    espacio: 'Cancha Principal',
    instructor: 'Carlos Gómez',
    dia_semana: 'LUNES',
    hora_inicio: '08:00',
    hora_fin: '09:30',
    inscritos: 14,
    categoria: 'Deportes de Equipo',
    requiere_inscripcion: false
  },
  {
    id_sesion: 102,
    estatus: 'DISPONIBLE',
    fecha_real: '2026-05-26',
    disciplina: 'Spinning',
    espacio: 'Salón de Ciclismo',
    instructor: 'Laura Martínez',
    dia_semana: 'MARTES',
    hora_inicio: '10:00',
    hora_fin: '11:00',
    inscritos: 8,
    categoria: 'Cardio',
    requiere_inscripcion: true
  },
  {
    id_sesion: 103,
    estatus: 'CANCELADA',
    fecha_real: '2026-05-27',
    disciplina: 'Yoga',
    espacio: 'Sala A',
    instructor: 'Ana Belén',
    dia_semana: 'MIERCOLES',
    hora_inicio: '07:00',
    hora_fin: '08:30',
    inscritos: 20,
    categoria: 'Mente y Cuerpo',
    requiere_inscripcion: false
  },
  {
    id_sesion: 104,
    estatus: 'DISPONIBLE',
    fecha_real: '2026-05-28',
    disciplina: 'Boxeo',
    espacio: 'Zona de Ring',
    instructor: 'Julio César',
    dia_semana: 'JUEVES',
    hora_inicio: '18:00',
    hora_fin: '19:30',
    inscritos: 5,
    categoria: 'Deportes de Contacto',
    requiere_inscripcion: true
  },
  {
    id_sesion: 105,
    estatus: 'DISPONIBLE',
    fecha_real: '2026-05-29',
    disciplina: 'Crossfit',
    espacio: 'Box de Crossfit',
    instructor: 'Roberto Díaz',
    dia_semana: 'VIERNES',
    hora_inicio: '09:00',
    hora_fin: '10:30',
    inscritos: 18,
    categoria: 'Fuerza',
    requiere_inscripcion: false
  },
  {
    id_sesion: 106,
    estatus: 'DISPONIBLE',
    fecha_real: '2026-05-30',
    disciplina: 'Zumba',
    espacio: 'Salón de Baile',
    instructor: 'María Rodríguez',
    dia_semana: 'SABADO',
    hora_inicio: '11:00',
    hora_fin: '12:30',
    inscritos: 25,
    categoria: 'Baile',
    requiere_inscripcion: false
  }
])

const activeView = ref('tabla') // 'tabla' | 'calendario'
const searchFilter = ref('')
const selectedEstatus = ref('TODOS')

// ─── Filtros de búsqueda ──────────────────────────────────────────────────
const sesionesFiltradas = computed(() => {
  return mockSesiones.value.filter(s => {
    const term = searchFilter.value.toLowerCase().trim()
    const matchesSearch = !term ||
      String(s.id_sesion).includes(term) ||
      s.disciplina.toLowerCase().includes(term) ||
      s.instructor.toLowerCase().includes(term) ||
      s.espacio.toLowerCase().includes(term) ||
      s.categoria.toLowerCase().includes(term)
    
    const matchesEstatus = selectedEstatus.value === 'TODOS' || s.estatus === selectedEstatus.value
    
    return matchesSearch && matchesEstatus
  })
})

// ─── Modal de Operaciones y Estatus ───────────────────────────────────────
const showModal = ref(false)
const sesionSeleccionada = ref(null)
const tempEstatus = ref('DISPONIBLE')
const isSaving = ref(false)
const showToast = ref(false)
const toastMessage = ref('')

function abrirDetalle(sesion) {
  sesionSeleccionada.value = { ...sesion }
  tempEstatus.value = sesion.estatus
  showModal.value = true
}

function cerrarModal() {
  showModal.value = false
  sesionSeleccionada.value = null
}

// Lógica de guardado en el Frontend (simulando API)
async function guardarCambios() {
  if (!sesionSeleccionada.value) return
  isSaving.value = true

  // Simulamos delay de respuesta de red
  await new Promise(resolve => setTimeout(resolve, 600))

  try {
    // 💡 NOTA PARA EL COMPAÑERO BACKEND:
    // Aquí es donde realizarás la petición HTTP PATCH al backend para actualizar el estatus.
    // Ejemplo:
    // const response = await api.patch(`/programacion/sesiones-publicadas/${sesionSeleccionada.value.id_sesion}`, {
    //   estatus: tempEstatus.value
    // })
    
    // Actualización del estado local
    const original = mockSesiones.value.find(s => s.id_sesion === sesionSeleccionada.value.id_sesion)
    if (original) {
      original.estatus = tempEstatus.value
    }

    triggerToast(`Estatus de la sesión #${sesionSeleccionada.value.id_sesion} actualizado a ${tempEstatus.value}`)
    cerrarModal()
  } catch (error) {
    console.error('Error actualizando estatus:', error)
  } finally {
    isSaving.value = false
  }
}

function triggerToast(msg) {
  toastMessage.value = msg
  showToast.value = true
  setTimeout(() => {
    showToast.value = false
  }, 4000)
}

// Formatear nombres de días a formato legible
const DIAS_LABEL = {
  LUNES: 'Lunes', MARTES: 'Martes', MIERCOLES: 'Miércoles',
  JUEVES: 'Jueves', VIERNES: 'Viernes', SABADO: 'Sábado', DOMINGO: 'Domingo',
}
</script>

<template>
  <div class="flex flex-col h-full bg-slate-50 font-sans min-h-0 relative">

    <!-- ── Header del Monitoreo: Filtros + Vista Switcher ── -->
    <div class="px-6 py-4 bg-white border-b border-slate-200 shrink-0 shadow-sm">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-black text-slate-800 tracking-tight">Monitoreo de Sesiones Semanales</h2>
          <p class="text-xs text-slate-500 font-medium">Visualiza las sesiones publicadas y gestiona cancelaciones.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <!-- Búsqueda -->
          <div class="relative w-48 md:w-60">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              v-model="searchFilter"
              type="text"
              placeholder="Buscar sesión, profe..."
              class="w-full pl-9 pr-4 py-2 text-xs font-bold text-slate-800 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all shadow-inner"
            />
          </div>

          <!-- Filtro de Estatus -->
          <select
            v-model="selectedEstatus"
            class="px-3 py-2 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer shadow-sm transition-all"
          >
            <option value="TODOS">Todos los Estatus</option>
            <option value="DISPONIBLE">Disponible</option>
            <option value="CANCELADA">Cancelada</option>
          </select>

          <!-- Switcher Tabla / Calendario (Segmented) -->
          <div class="flex p-0.5 bg-slate-100 border border-slate-200 rounded-xl shadow-inner">
            <button
              @click="activeView = 'tabla'"
              class="py-1.5 px-3 rounded-lg text-xs font-extrabold flex items-center gap-1.5 transition-all"
              :class="activeView === 'tabla'
                ? 'bg-white text-slate-800 shadow-sm border border-slate-200'
                : 'text-slate-500 hover:text-slate-800'"
              title="Vista de Tabla"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
              </svg>
              Tabla
            </button>
            <button
              @click="activeView = 'calendario'"
              class="py-1.5 px-3 rounded-lg text-xs font-extrabold flex items-center gap-1.5 transition-all"
              :class="activeView === 'calendario'
                ? 'bg-white text-slate-800 shadow-sm border border-slate-200'
                : 'text-slate-500 hover:text-slate-800'"
              title="Vista de Calendario"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75" />
              </svg>
              Calendario
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Contenido de la pestaña: Fills height ── -->
    <div class="flex-1 overflow-hidden p-6 min-h-0">
      
      <!-- ── VISTA TABLA (con scroll horizontal e ID únicos) ── -->
      <div
        v-show="activeView === 'tabla'"
        class="h-full overflow-hidden flex flex-col bg-white rounded-3xl border border-slate-200 shadow-sm"
      >
        <!-- Wrapper scrollable -->
        <div class="flex-1 overflow-auto">
          <table class="w-full border-collapse text-left min-w-[1000px]">
            <thead>
              <tr class="bg-slate-50/70 border-b border-slate-200 text-slate-400 text-[10px] uppercase font-black tracking-wider sticky top-0 z-10">
                <th class="py-4 px-6">ID Sesión</th>
                <th class="py-4 px-4 text-center">Estatus</th>
                <th class="py-4 px-4">Fecha Real</th>
                <th class="py-4 px-4">Categoría</th>
                <th class="py-4 px-4">Disciplina</th>
                <th class="py-4 px-4">Instructor</th>
                <th class="py-4 px-4">Espacio</th>
                <th class="py-4 px-4 text-center">Día</th>
                <th class="py-4 px-4 text-center">Hora Inicio</th>
                <th class="py-4 px-4 text-center">Hora Fin</th>
                <th class="py-4 px-4 text-center">Inscritos</th>
                <th class="py-4 px-6 text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
              <tr v-if="sesionesFiltradas.length === 0">
                <td colspan="12" class="py-12 text-center text-slate-400 font-bold">
                  No se encontraron sesiones publicadas con los filtros actuales.
                </td>
              </tr>
              <tr
                v-for="s in sesionesFiltradas"
                :key="s.id_sesion"
                class="hover:bg-slate-50/50 transition-colors"
                :class="s.estatus === 'CANCELADA' ? 'bg-red-50/10' : ''"
              >
                <!-- 1. ID sesion -->
                <td class="py-4 px-6 font-mono font-bold text-slate-400">#{{ s.id_sesion }}</td>
                <!-- 2. Estatus sesion -->
                <td class="py-4 px-4 text-center">
                  <span
                    :id="'status-badge-' + s.id_sesion"
                    class="inline-flex px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border shadow-sm"
                    :class="s.estatus === 'CANCELADA'
                      ? 'bg-red-50 text-red-700 border-red-200'
                      : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                  >
                    {{ s.estatus }}
                  </span>
                </td>
                <!-- 3. Fecha Real -->
                <td class="py-4 px-4 text-slate-500 font-medium tabular-nums">{{ s.fecha_real }}</td>
                <!-- 11. Categoria (disciplinas) -->
                <td class="py-4 px-4"><span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-bold">{{ s.categoria }}</span></td>
                <!-- 4. Disciplina -->
                <td class="py-4 px-4 text-slate-800 font-extrabold">{{ s.disciplina }}</td>
                <!-- 6. Instructor -->
                <td class="py-4 px-4 text-slate-600 font-bold">{{ s.instructor }}</td>
                <!-- 5. Espacio -->
                <td class="py-4 px-4 text-slate-500 font-medium">{{ s.espacio }}</td>
                <!-- 7. Dia de la semana -->
                <td class="py-4 px-4 text-center">
                  <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md text-[10px] font-bold">{{ DIAS_LABEL[s.dia_semana] }}</span>
                </td>
                <!-- 8. Hora Inicio -->
                <td class="py-4 px-4 text-center tabular-nums">{{ s.hora_inicio.slice(0,5) }}</td>
                <!-- 9. Hora Fin -->
                <td class="py-4 px-4 text-center tabular-nums">{{ s.hora_fin.slice(0,5) }}</td>
                <!-- 10. Cantidad Inscritos -->
                <td class="py-4 px-4 text-center font-bold">
                  <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-700 font-extrabold shadow-inner tabular-nums">
                    {{ s.inscritos }}
                  </span>
                </td>
                <!-- Acciones -->
                <td class="py-4 px-6 text-right">
                  <button
                    :id="'btn-manage-' + s.id_sesion"
                    @click="abrirDetalle(s)"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-[11px] font-extrabold text-slate-600 hover:text-blue-600 hover:bg-blue-50 border border-slate-200 hover:border-blue-200 rounded-xl transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
                    Gestionar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Footer de la tabla / Monitoreo -->
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 font-bold shrink-0">
          <span>Mostrando {{ sesionesFiltradas.length }} de {{ mockSesiones.length }} sesiones publicadas</span>
          <span class="flex items-center gap-1">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 border border-emerald-400 animate-pulse"></span>
            Monitoreo en Vivo
          </span>
        </div>
      </div>

      <!-- ── VISTA CALENDARIO (Inerte con bloques interactivos) ── -->
      <div v-show="activeView === 'calendario'" class="h-full bg-white rounded-3xl border border-slate-200 shadow-sm p-4">
        <CalendarioPublicadas
          :sesiones="sesionesFiltradas"
          @select-sesion="abrirDetalle"
        />
      </div>

    </div>

    <!-- ══════════ MODAL DE DETALLE & OPERACIÓN (Teleport) ══════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0"
      >
        <div
          v-if="showModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
          @click.self="cerrarModal"
        >
          <!-- Contenido del Modal -->
          <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden transition-all duration-300 transform scale-100">
            
            <!-- Encabezado dinámico según estatus actual -->
            <div
              class="px-6 py-5 text-white relative transition-colors duration-300"
              :class="tempEstatus === 'CANCELADA'
                ? 'bg-gradient-to-br from-rose-500 to-red-700'
                : 'bg-gradient-to-br from-blue-600 to-indigo-700'"
            >
              <button
                @click="cerrarModal"
                class="absolute top-4 right-4 text-white/80 hover:text-white bg-black/10 hover:bg-black/20 p-1.5 rounded-full transition-colors focus:outline-none"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
              
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center shadow-inner">
                  <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.246.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                </div>
                <div>
                  <p class="text-white/70 text-[9px] uppercase font-black tracking-widest leading-none">Sesión Publicada</p>
                  <h3 class="text-white text-lg font-black mt-1">{{ sesionSeleccionada?.disciplina }}</h3>
                </div>
              </div>
            </div>

            <!-- Cuerpo del Modal -->
            <div class="p-6 space-y-5">
              
              <!-- Información de la sesión -->
              <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-xs">
                <div>
                  <span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block">ID Sesión</span>
                  <span class="text-slate-800 font-mono font-bold">#{{ sesionSeleccionada?.id_sesion }}</span>
                </div>
                <div>
                  <span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block">Categoría</span>
                  <span class="text-slate-800 font-bold">{{ sesionSeleccionada?.categoria }}</span>
                </div>
                <div class="col-span-2 border-t border-slate-200/50 pt-2.5">
                  <span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block">Fecha & Horario</span>
                  <span class="text-slate-800 font-bold">
                    {{ DIAS_LABEL[sesionSeleccionada?.dia_semana] }} {{ sesionSeleccionada?.fecha_real }} · {{ sesionSeleccionada?.hora_inicio.slice(0,5) }}–{{ sesionSeleccionada?.hora_fin.slice(0,5) }}
                  </span>
                </div>
                <div class="col-span-2 border-t border-slate-200/50 pt-2.5">
                  <span class="text-slate-400 font-extrabold uppercase text-[9px] tracking-wider block">Instructor & Espacio</span>
                  <span class="text-slate-700 font-bold block">Prof: {{ sesionSeleccionada?.instructor }}</span>
                  <span class="text-slate-500 font-medium block">Sala: {{ sesionSeleccionada?.espacio }}</span>
                </div>
              </div>

              <!-- 👤 Cantidad de Inscritos (READ ONLY) -->
              <div>
                <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-2">Cantidad de Inscritos (Lectura)</label>
                <div class="flex items-center gap-3 px-4 py-3 bg-blue-50 border border-blue-100 text-blue-700 rounded-2xl">
                  <div class="w-8 h-8 rounded-xl bg-blue-500/10 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                  </div>
                  <div>
                    <span class="text-xs font-black block">Alumnos registrados</span>
                    <span class="text-[10px] opacity-90">Hay <strong class="text-sm font-black">{{ sesionSeleccionada?.inscritos }}</strong> usuarios inscritos a esta actividad actualmente.</span>
                  </div>
                </div>
              </div>

              <!-- 🛠️ Estatus (UPDATE SEGMENTED CONTROL) -->
              <div>
                <label class="block text-[10px] font-black uppercase text-slate-500 tracking-wider mb-2">Estatus de la Actividad</label>
                <div class="flex p-1 bg-slate-100 rounded-2xl border border-slate-200 gap-1.5">
                  <button
                    type="button"
                    @click="tempEstatus = 'DISPONIBLE'"
                    class="flex-1 py-3 text-xs font-black rounded-xl border transition-all flex items-center justify-center gap-1.5 focus:outline-none"
                    :class="tempEstatus === 'DISPONIBLE'
                      ? 'bg-emerald-600 border-emerald-500 text-white shadow-sm font-extrabold'
                      : 'border-transparent text-slate-500 hover:bg-white/60 hover:text-slate-700 font-bold'"
                  >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    DISPONIBLE
                  </button>
                  
                  <button
                    type="button"
                    @click="tempEstatus = 'CANCELADA'"
                    class="flex-1 py-3 text-xs font-black rounded-xl border transition-all flex items-center justify-center gap-1.5 focus:outline-none"
                    :class="tempEstatus === 'CANCELADA'
                      ? 'bg-red-600 border-red-500 text-white shadow-sm font-extrabold'
                      : 'border-transparent text-slate-500 hover:bg-white/60 hover:text-slate-700 font-bold'"
                  >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    CANCELADA
                  </button>
                </div>
              </div>

            </div>

            <!-- Footer del Modal -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50">
              <button
                type="button"
                @click="cerrarModal"
                :disabled="isSaving"
                class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-200/50 rounded-xl transition-all disabled:opacity-50"
              >
                Cerrar
              </button>
              
              <button
                type="button"
                @click="guardarCambios"
                :disabled="isSaving"
                class="px-5 py-2.5 text-xs font-black text-white bg-slate-800 hover:bg-slate-900 active:scale-95 rounded-xl transition-all shadow-sm flex items-center gap-1.5 disabled:opacity-50"
              >
                <svg v-if="isSaving" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <span>Guardar cambios</span>
              </button>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════ TOAST NOTIFICATION ══════════ -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-300 ease-out transform"
        enter-from-class="translate-y-4 opacity-0 scale-95"
        enter-to-class="translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in transform"
        leave-from-class="translate-y-0 opacity-100 scale-100"
        leave-to-class="translate-y-4 opacity-0 scale-95"
      >
        <div
          v-if="showToast"
          class="fixed bottom-6 right-6 z-[60] bg-slate-900 text-white text-xs font-bold px-4 py-3 rounded-2xl shadow-xl flex items-center gap-3 border border-slate-700/50 max-w-sm"
        >
          <div class="w-6 h-6 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <span class="flex-1 leading-snug">{{ toastMessage }}</span>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<style scoped>
/* Scrollbar personalizado para la tabla */
.overflow-auto::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
.overflow-auto::-webkit-scrollbar-track {
  background: transparent;
}
.overflow-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}
.overflow-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
