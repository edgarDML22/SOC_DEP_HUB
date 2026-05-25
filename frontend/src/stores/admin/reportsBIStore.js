import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useReportsBIStore = defineStore('reportsBI', () => {
  // --- Academic Performance Cache ---
  const academicStats = ref(null);
  const academicFilters = ref({
    rango: 'mes',
    fecha_inicio: '',
    fecha_fin: '',
    id_disciplina: '',
    id_instructor: ''
  });

  const fetchAcademicStats = async (filters, forceRefresh = false) => {
    const isSameFilters =
      academicStats.value &&
      academicFilters.value.rango === filters.rango &&
      academicFilters.value.fecha_inicio === filters.fecha_inicio &&
      academicFilters.value.fecha_fin === filters.fecha_fin &&
      academicFilters.value.id_disciplina === filters.id_disciplina &&
      academicFilters.value.id_instructor === filters.id_instructor;

    if (isSameFilters && !forceRefresh) {
      return academicStats.value;
    }

    const res = await api.get('/admin/bi/reports/academic', { params: filters });
    if (res.data && res.data.success) {
      academicStats.value = res.data.data;
      academicFilters.value = { ...filters };
      return academicStats.value;
    }
    throw new Error('Fallo al obtener estadísticas académicas');
  };

  // --- Spaces / Infrastructure Cache ---
  const spacesStats = ref(null);
  const spacesFilters = ref({
    rango: 'semana',
    fecha_inicio: '',
    fecha_fin: '',
    id_espacio: ''
  });

  const fetchSpacesStats = async (filters, forceRefresh = false) => {
    const isSameFilters =
      spacesStats.value &&
      spacesFilters.value.rango === filters.rango &&
      spacesFilters.value.fecha_inicio === filters.fecha_inicio &&
      spacesFilters.value.fecha_fin === filters.fecha_fin &&
      spacesFilters.value.id_espacio === filters.id_espacio;

    if (isSameFilters && !forceRefresh) {
      return spacesStats.value;
    }

    const res = await api.get('/admin/bi/reports/spaces', { params: filters });
    if (res.data && res.data.success) {
      spacesStats.value = res.data.data;
      spacesFilters.value = { ...filters };
      return spacesStats.value;
    }
    throw new Error('Fallo al obtener estadísticas de espacios');
  };

  // --- Auditoria Cache ---
  const auditoriaStats = ref(null);
  const auditoriaFilters = ref({
    rango: 'mes',
    fecha_inicio: '',
    fecha_fin: ''
  });

  const fetchAuditoriaStats = async (filters, forceRefresh = false) => {
    const isSameFilters =
      auditoriaStats.value &&
      auditoriaFilters.value.rango === filters.rango &&
      auditoriaFilters.value.fecha_inicio === filters.fecha_inicio &&
      auditoriaFilters.value.fecha_fin === filters.fecha_fin;

    if (isSameFilters && !forceRefresh) {
      return auditoriaStats.value;
    }

    const res = await api.get('/admin/bi/reports/auditoria', { params: filters });
    if (res.data && res.data.success) {
      auditoriaStats.value = res.data.data;
      auditoriaFilters.value = { ...filters };
      return auditoriaStats.value;
    }
    throw new Error('Fallo al obtener estadísticas de auditoría');
  };

  // --- Tournaments Cache ---
  const tournamentsStats = ref(null);
  const tournamentsFilters = ref({
    rango: 'mes',
    fecha_inicio: '',
    fecha_fin: '',
    id_torneo: '',
    id_disciplina: ''
  });

  const fetchTournamentsStats = async (filters, forceRefresh = false) => {
    const isSameFilters =
      tournamentsStats.value &&
      tournamentsFilters.value.rango === filters.rango &&
      tournamentsFilters.value.fecha_inicio === filters.fecha_inicio &&
      tournamentsFilters.value.fecha_fin === filters.fecha_fin &&
      tournamentsFilters.value.id_torneo === filters.id_torneo &&
      tournamentsFilters.value.id_disciplina === filters.id_disciplina;

    if (isSameFilters && !forceRefresh) {
      return tournamentsStats.value;
    }

    const res = await api.get('/admin/bi/reports/tournaments', { params: filters });
    if (res.data && res.data.success) {
      tournamentsStats.value = res.data.data;
      tournamentsFilters.value = { ...filters };
      return tournamentsStats.value;
    }
    throw new Error('Fallo al obtener estadísticas de torneos');
  };

  // --- Helper to clear cache on demand ---
  const clearCache = () => {
    academicStats.value = null;
    spacesStats.value = null;
    auditoriaStats.value = null;
    tournamentsStats.value = null;
  };

  return {
    academicStats,
    academicFilters,
    fetchAcademicStats,

    spacesStats,
    spacesFilters,
    fetchSpacesStats,

    auditoriaStats,
    auditoriaFilters,
    fetchAuditoriaStats,

    tournamentsStats,
    tournamentsFilters,
    fetchTournamentsStats,

    clearCache
  };
});
