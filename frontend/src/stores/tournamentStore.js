import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/services/api";

export const useTournamentStore = defineStore("tournament", () => {
    // ── STATE ───────────────────────────────────────────────────────
    const torneos = ref([]);
    const torneoActivo = ref(null);
    const bracket = ref({});
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({ page: 1, total: 0, perPage: 15 });
    const filtros = ref({
        estatus: "",
        disciplina: "",
        categoria: "",
        tipo_acceso: "",
    });

    // ── ACTIONS ─────────────────────────────────────────────────────

    /**
     * Obtiene la lista de torneos con filtros
     */
    const fetchTorneos = async (nuevosFiltros = {}) => {
        loading.value = true;
        error.value = null;

        // Actualizar filtros si se pasan nuevos
        if (Object.keys(nuevosFiltros).length > 0) {
            filtros.value = { ...filtros.value, ...nuevosFiltros };
        }

        try {
            const params = {
                ...filtros.value,
                page: pagination.value.page,
                per_page: pagination.value.perPage,
            };
            const response = await api.get("/torneos", { params });

            // Ajustar según la estructura de respuesta del backend (Laravel Paginator o Array)
            const res = response.data.data ?? response.data;
            
            if (res && typeof res === 'object' && Array.isArray(res.data)) {
                // Es un paginador
                torneos.value = res.data.map(t => ({
                    ...t,
                    id_torneo: t.id_torneo || t.id,
                    id: t.id || t.id_torneo
                }));
                pagination.value.total = res.total || 0;
            } else {
                // Es un array directo o algo más
                const data = Array.isArray(res) ? res : [];
                torneos.value = data.map(t => ({
                    ...t,
                    id_torneo: t.id_torneo || t.id,
                    id: t.id || t.id_torneo
                }));
                if (response.data.meta) {
                    pagination.value.total = response.data.meta.total;
                }
            }
        } catch (err) {
            console.error("Error fetching torneos:", err);
            error.value = err.response?.data?.message || "Error al cargar los torneos.";
        } finally {
            loading.value = false;
        }
    };

    /**
     * Obtiene un torneo por ID
     */
    const fetchTorneoById = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/torneos/${id}`);
            const data = response.data.data ?? response.data;
            torneoActivo.value = {
                ...data,
                id_torneo: data.id_torneo || data.id,
                id: data.id || data.id_torneo
            };
        } catch (err) {
            console.error(`Error fetching torneo ${id}:`, err);
            error.value = err.response?.data?.message || "Error al cargar los detalles del torneo.";
        } finally {
            loading.value = false;
        }
    };

    /**
     * Obtiene el bracket de un torneo y lo agrupa por fase
     */
    const fetchBracket = async (id) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/torneos/${id}/bracket`);
            const data = response.data.data ?? response.data;

            // Agrupar por fase si viene como lista plana
            if (Array.isArray(data)) {
                bracket.value = data.reduce((acc, match) => {
                    const fase = match.fase || "Fase desconocida";
                    if (!acc[fase]) acc[fase] = [];
                    acc[fase].push(match);
                    return acc;
                }, {});
            } else {
                bracket.value = data;
            }
        } catch (err) {
            console.error(`Error fetching bracket for torneo ${id}:`, err);
            error.value = err.response?.data?.message || "Error al cargar el bracket.";
        } finally {
            loading.value = false;
        }
    };

    /**
     * Crea un nuevo torneo
     */
    const crearTorneo = async (payload) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post("/torneos", payload);
            const nuevoTorneo = response.data.data ?? response.data;
            torneos.value.unshift(nuevoTorneo);
            return nuevoTorneo;
        } catch (err) {
            console.error("Error creating torneo:", err);
            error.value = err.response?.data?.message || "Error al crear el torneo.";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Transiciona el estatus de un torneo con mutación optimista
     */
    const transicionarEstatus = async (id, nuevoEstatus, motivo = null) => {
        error.value = null;

        // Buscar el torneo para guardar estado previo en caso de rollback
        const index = torneos.value.findIndex(t => t.id_torneo === id);
        let previousStatus = null;
        let isActivo = false;

        if (torneoActivo.value && (torneoActivo.value.id_torneo === id || torneoActivo.value.id === id)) {
            previousStatus = torneoActivo.value.estado || torneoActivo.value.estatus_torneo;
            // Mutación optimista en el activo
            torneoActivo.value.estado = nuevoEstatus;
            torneoActivo.value.estatus_torneo = nuevoEstatus;
            isActivo = true;
        }

        if (index !== -1) {
            if (!previousStatus) previousStatus = torneos.value[index].estado || torneos.value[index].estatus_torneo;
            // Mutación optimista en la lista
            torneos.value[index].estado = nuevoEstatus;
            torneos.value[index].estatus_torneo = nuevoEstatus;
        }

        try {
            const response = await api.patch(`/torneos/${id}/status`, {
                estatus: nuevoEstatus,
                motivo
            });

            // Actualizar con la respuesta real del servidor si es necesario
            const data = response.data.data ?? response.data;
            if (isActivo) {
                torneoActivo.value = { ...torneoActivo.value, ...data };
            }
            if (index !== -1) {
                torneos.value[index] = { ...torneos.value[index], ...data };
            }
        } catch (err) {
            console.error(`Error transitioning status for torneo ${id}:`, err);
            error.value = err.response?.data?.message || "Error al cambiar el estatus del torneo.";

            // Rollback en caso de error
            if (isActivo) {
                torneoActivo.value.estado = previousStatus;
                torneoActivo.value.estatus_torneo = previousStatus;
            }
            if (index !== -1) {
                torneos.value[index].estado = previousStatus;
                torneos.value[index].estatus_torneo = previousStatus;
            }

            throw err;
        }
    };

    return {
        torneos,
        torneoActivo,
        bracket,
        loading,
        error,
        pagination,
        filtros,
        fetchTorneos,
        fetchTorneoById,
        fetchBracket,
        crearTorneo,
        transicionarEstatus,
    };
});
