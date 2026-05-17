import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/services/api";

export const useAdminLudotecaStore = defineStore("adminLudoteca", () => {

    const viewActive = ref("dashboard"); // 'dashboard' o 'turnos'
    const mainTab = ref("register"); // 'register' o 'stats'

    const statsCache = ref({
        hoy: null,
        semana: null,
        mes: null
    });

    const activeRequests = {};

    const isLoaded = ref({
        instructores: false,
        turnos: false,
        record: false,
        sociosConMenores: false
    });

    const instructoresHabilitados = ref([]);

    const turnosAsignados = ref([]);
    const record = ref([]);
    const sociosConMenores = ref([]);

    const loading = ref({
        stats: false,
        instructores: false,
        turnos: false,
        submit: false,
        record: false,
        sociosConMenores: false,
    });

    const error = ref(null);

    const fetchStats = async (rango = 'hoy', silent = false, force = false) => {
        if (!force && statsCache.value[rango]) {
            return statsCache.value[rango];
        }

        if (!force && activeRequests[rango]) {
            if (!silent) loading.value.stats = true;
            await activeRequests[rango];
            if (!silent) loading.value.stats = false;
            return statsCache.value[rango];
        }

        if (!silent) loading.value.stats = true;
        
        activeRequests[rango] = api.get(`/ludoteca/admin/stats?rango=${rango}`);
        
        try {
            const res = await activeRequests[rango];
            if (res.data.success) {
                statsCache.value[rango] = res.data.data;
                return statsCache.value[rango];
            }
        } catch (err) {
            console.error("[adminLudotecaStore] Error al cargar stats:", err);
            return null;
        } finally {
            delete activeRequests[rango];
            if (!silent) loading.value.stats = false;
        }
    };

    const fetchInstructores = async (force = false) => {
        // Cache: Si ya tenemos la lista de instructores y no se pide forzar, evitamos la petición
        if (!force && isLoaded.value.instructores) {
            return;
        }

        loading.value.instructores = true;
        try {
            const res = await api.get("/ludoteca/admin/instructores");
            if (res.data.success) {
                instructoresHabilitados.value = res.data.data;
                isLoaded.value.instructores = true;
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
    const fetchTurnos = async (force = false) => {
        // Cache: Si ya tenemos turnos y no se pide forzar, evitamos la petición
        if (!force && isLoaded.value.turnos) {
            return;
        }

        loading.value.turnos = true;
        try {
            const res = await api.get("/ludoteca/admin/turnos");
            if (res.data.success) {
                turnosAsignados.value = res.data.data;
                isLoaded.value.turnos = true;
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
            // Al crear uno nuevo, forzamos la actualización de la lista
            await fetchTurnos(true);
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

    const fetchRecord = async (params = {}, force = false) => {
        if (!force && isLoaded.value.record && Object.keys(params).length === 0) {
            return;
        }
        loading.value.record = true;
        try {
            const res = await api.get("/ludoteca/admin/historial", { params });
            if (res.data.success) {
                record.value = res.data.data;
                isLoaded.value.record = true;
            }
        } catch (err) {
            console.error("[adminLudotecaStore] Error al cargar record:", err);
            record.value = [];
        } finally {
            loading.value.record = false;
        }
    };

    const fetchSociosConMenores = async (force = false) => {
        if (!force && isLoaded.value.sociosConMenores) {
            return;
        }
        loading.value.sociosConMenores = true;
        try {
            const res = await api.get("/ludoteca/admin/socios-con-menores");
            if (res.data.success) {
                sociosConMenores.value = res.data.data;
                isLoaded.value.sociosConMenores = true;
            }
        } catch (err) {
            console.error("[adminLudotecaStore] Error al cargar socios con menores:", err);
        } finally {
            loading.value.sociosConMenores = false;
        }
    };

    const clearCache = () => {
        statsCache.value = { hoy: null, semana: null, mes: null };
        isLoaded.value.instructores = false;
        isLoaded.value.turnos = false;
        isLoaded.value.record = false;
        isLoaded.value.sociosConMenores = false;
        instructoresHabilitados.value = [];
        turnosAsignados.value = [];
        record.value = [];
        sociosConMenores.value = [];
    };

    return {
        // State
        viewActive,
        mainTab,
        statsCache,
        instructoresHabilitados,
        turnosAsignados,
        record,
        sociosConMenores,
        loading,
        error,
        // Actions
        fetchStats,
        fetchInstructores,
        fetchTurnos,
        fetchRecord,
        fetchSociosConMenores,
        crearTurno,
        clearCache
    };
});