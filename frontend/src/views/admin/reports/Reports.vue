<script setup>
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2';
import api from '@/services/api';
import { useReportsBIStore } from '@/stores/admin/reportsBIStore';
import { useAdminStore } from '@/stores/profiles/adminStore';
import { generateExecutiveReportPDF } from '@/utils/pdfGenerator';
import {
  IconHistory,
  IconLayers,
  IconTrophy,
  IconTarget
} from '@/components/icons'

const route = useRoute()
const router = useRouter()

const tabs = [
  { name: 'auditoria', label: 'Auditoría', icon: IconHistory },
  { name: 'ocupation-spaces', label: 'Ocupación Espacios', icon: IconLayers },
  { name: 'tournaments-analytics', label: 'Analíticas Torneos', icon: IconTrophy },
  { name: 'academic-performance', label: 'Rendimiento Acad.', icon: IconTarget },
]

const exportExecutivePDF = async () => {
  try {
    const adminStore = useAdminStore();
    
    // Configurar e iniciar la selección del rango
    const { value: selectedRange } = await Swal.fire({
      title: 'Configurar Reporte PDF',
      html: `
        <div class="flex flex-col gap-4 text-left p-2 font-sans">
          <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest px-1">Rango Temporal de Consulta</label>
          <select id="swal-range" class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/10 transition-all cursor-pointer">
            <option value="mes" selected>Este Mes (Recomendado)</option>
            <option value="semana">Esta Semana</option>
            <option value="ambos">Ambos (Semana y Mes - Últimos 30 días)</option>
          </select>
        </div>
      `,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Siguiente',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#0f172a',
      cancelButtonColor: '#64748b',
      preConfirm: () => {
        return document.getElementById('swal-range').value;
      }
    });

    if (!selectedRange) return; // Cancelado por el usuario

    // Mostrar pantalla de carga
    Swal.fire({
      title: 'Generando Reporte General...',
      html: 'Consolidando estadísticas de todos los módulos deportivos en tiempo real.',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    // Cargar perfil del gerente/subgerente en sesión si no está cargado
    if (!adminStore.fullName) {
      await adminStore.fetchProfile();
    }
    const exporterName = adminStore.fullName || 'Subgerente de Operaciones';

    // Determinar parámetros de fecha según la selección
    const now = new Date();
    let startDate, endDate;

    if (selectedRange === 'semana') {
      const currentDay = now.getDay();
      const distanceToMonday = currentDay === 0 ? -6 : 1 - currentDay;
      const monday = new Date(now);
      monday.setDate(now.getDate() + distanceToMonday);
      
      const sunday = new Date(monday);
      sunday.setDate(monday.getDate() + 6);
      
      startDate = monday.toISOString().split('T')[0];
      endDate = sunday.toISOString().split('T')[0];
    } else if (selectedRange === 'mes') {
      startDate = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
      endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
    } else {
      // 'ambos': últimos 30 días
      const thirtyDaysAgo = new Date(now);
      thirtyDaysAgo.setDate(now.getDate() - 30);
      startDate = thirtyDaysAgo.toISOString().split('T')[0];
      endDate = now.toISOString().split('T')[0];
    }

    const selectedParams = { fecha_inicio: startDate, fecha_fin: endDate };

    // Ejecutar llamados paralelos al servidor de BI para no bloquear al usuario
    const [resDashboard, resAcademic, resSpaces, resAuditoria, resTournaments] = await Promise.all([
      api.get('/admin/bi/dashboard'),
      api.get('/admin/bi/reports/academic', { params: selectedParams }),
      api.get('/admin/bi/reports/spaces', { params: selectedParams }),
      api.get('/admin/bi/reports/auditoria', { params: selectedParams }),
      api.get('/admin/bi/reports/tournaments', { params: selectedParams })
    ]);

    const allStats = {
      ludoteca_kpis: resDashboard.data?.data?.ludoteca_kpis || {},
      top_espacios: resDashboard.data?.data?.top_espacios || {},
      reservas_hoy_hora: resDashboard.data?.data?.reservas_hoy_hora || {},
      estatus_operativo: resDashboard.data?.data?.estatus_operativo || {},
      academic: resAcademic.data?.data || {},
      spaces: resSpaces.data?.data || {},
      auditoria: resAuditoria.data?.data || {},
      tournaments: resTournaments.data?.data || {}
    };

    // Invocar el motor de generación en formato A4 vectorial
    await generateExecutiveReportPDF(allStats, exporterName);

    Swal.fire({
      icon: 'success',
      title: '¡Reporte PDF Generado!',
      text: 'El reporte general de BI se ha descargado exitosamente.',
      confirmButtonColor: '#0f172a'
    });
  } catch (error) {
    console.error('Error generating PDF report:', error);
    Swal.fire({
      icon: 'error',
      title: 'Fallo al exportar reporte',
      text: 'No se pudo contactar al servidor analítico para consolidar los datos.',
      confirmButtonColor: '#0f172a'
    });
  }
};

</script>

<template>
  <div class="p-6 font-sans">
    <div class="max-w-7xl mx-auto">
      <div class="flex items-center justify-between flex-wrap gap-4 mb-8">
        <h1 class="text-3xl font-black text-surface-900 tracking-tight m-0">Reportes y Analíticas</h1>
        <button
          @click="exportExecutivePDF"
          class="flex items-center gap-2 px-5 py-3 bg-red-600 text-white rounded-xl text-xs font-black tracking-wider uppercase hover:bg-red-700 hover:-translate-y-0.5 active:translate-y-0 transition-all cursor-pointer shadow-sm shadow-red-900/10 hover:shadow-md"
        >
          <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
          </svg>
          PDF
        </button>
      </div>

      <!-- Segmented Control (Pills) -->
      <div class="flex p-1.5 bg-surface-100/50 rounded-2xl w-full mx-auto overflow-x-auto scrollbar-thin shadow-inner border border-surface-200 mb-8">
        <button
          v-for="tab in tabs"
          :key="tab.name"
          @click="router.push({ name: tab.name })"
          class="flex-1 py-3 px-4 text-sm md:text-base text-center transition-all whitespace-nowrap flex items-center justify-center gap-2 focus:outline-none"
          :class="route.name === tab.name
            ? 'bg-surface-900 text-white font-black rounded-xl shadow-md transform scale-[1.02]'
            : 'text-surface-500 font-bold hover:bg-white hover:text-surface-700 rounded-xl'"
        >
          <component :is="tab.icon" class="w-5 h-5 shrink-0" />
          {{ tab.label }}
        </button>
      </div>

      <!-- Área de Contenido -->
      <div class="bg-white rounded-[2.5rem] border border-surface-200 shadow-sm p-6 lg:p-10 min-h-[400px]">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>