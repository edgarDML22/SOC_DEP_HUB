import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/services/api";

export const useAdminLudotecaStore = defineStore("adminLudoteca", () => {

    // ─── State
    const stats = ref({
        kpis: {
            numero_ninos: 0,
            calificacion_promedio: null,
            total_incidencias: 0,
            tiempo_promedio_min: 0
        },
        graficas: {
            afluencia_temporal: { labels: [], data: [] },
            calificaciones: { labels: [], data: [] },
            tiempo_uso: { labels: [], data: [] }
        }
    });

    const instructoresHabilitados = ref([]);

    const turnosAsignados = ref([]);

    const loading = ref({
        stats: false,
        instructores: false,
        turnos: false,
        submit: false,
    });

    const error = ref(null);

    // ─── Actions 

    const fetchStats = async (rango = 'hoy') => {
        loading.value.stats = true;
        try {
            const res = await api.get(`/ludoteca/admin/stats?rango=${rango}`);
            if (res.data.success) {
                stats.value = res.data.data;
            }
        } catch (err) {
            console.error("[adminLudotecaStore] Error al cargar stats:", err);
        } finally {
            loading.value.stats = false;
        }
    };

    const fetchInstructores = async () => {
        loading.value.instructores = true;
        try {
            const res = await api.get("/ludoteca/admin/instructores");
            if (res.data.success) {
                instructoresHabilitados.value = res.data.data;
            }
        } catch (err) {
            console.error("[adminLudotecaStore] Error al cargar instructores:", err);
        } finally {
            loading.value.instructores = false;
        }
    };

    /**
     * Carga los turnos asignados de hoy + próximos 6 días.
     */
    const fetchTurnos = async () => {
        loading.value.turnos = true;
        try {
            const res = await api.get("/ludoteca/admin/turnos");
            if (res.data.success) {
                turnosAsignados.value = res.data.data;
            }
        } catch (err) {
            console.error("[adminLudotecaStore] Error al cargar turnos:", err);
        } finally {
            loading.value.turnos = false;
        }
    };

    const crearTurno = async (payload) => {
        loading.value.submit = true;
        error.value = null;

        try {
            await api.post("/ludoteca/admin/turnos", payload);
            await fetchTurnos();
            return { success: true };

        } catch (err) {
            if (err.response?.status === 409) {
                return {
                    success: false,
                    conflicto: true,
                    message: err.response.data.message || "Conflicto de horario detectado.",
                };
            }

            const msg = err.response?.data?.message || "Error al crear el turno. Intenta de nuevo.";
            error.value = msg;
            return { success: false, conflicto: false, message: msg };

        } finally {
            loading.value.submit = false;
        }
    };


    return {
        // State
        stats,
        instructoresHabilitados,
        turnosAsignados,
        loading,
        error,
        // Actions
        fetchStats,
        fetchInstructores,
        fetchTurnos,
        crearTurno,
    };
});