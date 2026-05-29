import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/services/api";

export const useSocioTorneoStore = defineStore("socioTorneo", () => {
  // --- STATE ---
  const disponibles = ref([]);
  const historial = ref([]);
  const loading = ref(false);
  const error = ref(null);

  // --- ACTIONS ---

  /**
   * Carga los torneos disponibles en estatus EN_INSCRIPCION.
   */
  const fetchDisponibles = async () => {
    loading.value = true;
    error.value = null;
    try {
      const res = await api.get("/socio/torneos/disponibles");
      disponibles.value = res.data.data || [];
    } catch (err) {
      console.error("Error al cargar torneos disponibles:", err);
      error.value = err.response?.data?.message || "No se pudieron cargar los torneos disponibles.";
    } finally {
      loading.value = false;
    }
  };

  /**
   * Carga los últimos 10 torneos del socio.
   */
  const fetchHistorial = async () => {
    loading.value = true;
    error.value = null;
    try {
      const res = await api.get("/socio/torneos/historial");
      historial.value = res.data.data || [];
    } catch (err) {
      console.error("Error al cargar historial de torneos:", err);
      error.value = err.response?.data?.message || "No se pudo cargar el historial de torneos.";
    } finally {
      loading.value = false;
    }
  };

  /**
   * Inscribe a un participante (Titular / Familiar) en un torneo.
   */
  const inscribir = async (id_torneo, payload) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await api.post(`/torneos/${id_torneo}/inscripciones`, payload);
      
      // Actualizar localmente el estado ya_inscrito e inscritos_actual del torneo
      const torneo = disponibles.value.find(t => t.id_torneo === id_torneo);
      if (torneo) {
        torneo.ya_inscrito = true;
        torneo.inscritos_actual += 1;
      }
      return { success: true, message: res.data.message || "Inscripción confirmada con éxito!" };
    } catch (err) {
      console.error("Error al inscribir en torneo:", err);
      const motivo = err.response?.data?.motivo || err.response?.data?.message || "Error al realizar la inscripción.";
      return { success: false, error: motivo };
    } finally {
      loading.value = false;
    }
  };

  /**
   * Crea un equipo para el torneo con el compañero seleccionado
   */
  const crearEquipo = async (id_torneo, payload) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await api.post(`/torneos/${id_torneo}/equipos`, payload);
      return res.data;
    } catch (err) {
      console.error("Error al crear equipo:", err);
      error.value = err.response?.data?.message || "Error enviando invitación";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Responde a una invitación de equipo (ACEPTADA / RECHAZADA)
   */
  const responderInvitacion = async (id_torneo, id_equipo, payload) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await api.patch(`/torneos/${id_torneo}/equipos/${id_equipo}/responder`, payload);
      return res.data;
    } catch (err) {
      console.error("Error al responder invitación:", err);
      error.value = err.response?.data?.message || "Error respondiendo a la invitación";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Reasigna compañero de un equipo
   */
  const reasignarCompanero = async (payload) => {
    loading.value = true;
    error.value = null;
    try {
      const res = await api.patch(`/equipos/reasignar`, payload);
      return res.data;
    } catch (err) {
      console.error("Error al reasignar compañero:", err);
      error.value = err.response?.data?.message || "Error reasignando compañero";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    disponibles,
    historial,
    loading,
    error,
    fetchDisponibles,
    fetchHistorial,
    inscribir,
    crearEquipo,
    responderInvitacion,
    reasignarCompanero
  };
});
