import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/services/api";
import { handleApiError } from "@/composables/useAlerts";

export const useTournamentStore = defineStore("tournament", () => {
    // ── STATE ───────────────────────────────────────────────────────
    const torneos = ref([]);
    const torneoActivo = ref(null);
    const bracket = ref({});
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({ page: 1, total: 0, perPage: 15 });
    const filtros = ref({
        search: '',
        estatus: null,
        disciplina: null,
        categoria: null,
        tipo_acceso: null,
    });

    const categoriasTorneo = ref([]);

    /**
     * Normaliza un torneo del backend (que usa `id_torneo`) exponiendo también `id`.
     * Se usa en todas las acciones para que las vistas puedan leer `id_torneo` sin riesgo.
     */
    const normalizeTorneo = (t) => {
        if (!t || typeof t !== 'object') return t;
        const id = t.id_torneo ?? t.id;
        return { ...t, id_torneo: id, id };
    };

    /**
     * Compara ids tolerando la diferencia entre string (route params) y number (API).
     */
    const isSameTorneo = (torneo, id) => {
        if (!torneo || id === null || id === undefined) return false;
        return String(torneo.id_torneo ?? torneo.id) === String(id);
    };

    /**
     * Lee el estado de un torneo independientemente del nombre de la columna
     * (`estado` en las respuestas transformadas, `estatus_torneo` en el modelo).
     */
    const getEstado = (torneo) => torneo?.estado || torneo?.estatus_torneo || null;

    // ── ACTIONS ─────────────────────────────────────────────────────

    const fetchCategoriasTorneo = async () => {
        if (categoriasTorneo.value.length > 0) return;
        try {
            const res = await api.get("/torneos/categorias");
            if (res.data.success) {
                categoriasTorneo.value = res.data.data;
            }
        } catch (err) {
            console.error("Error fetching categorias torneo:", err);
            error.value = handleApiError(err);
        }
    };

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
            // El backend espera `nombre_disciplina` / `nombre_categoria`.
            const params = {
                estatus: filtros.value.estatus,
                tipo_acceso: filtros.value.tipo_acceso,
                nombre_disciplina: filtros.value.disciplina,
                nombre_categoria: filtros.value.categoria,
                search: filtros.value.search || undefined,
                page: pagination.value.page,
                per_page: pagination.value.perPage,
            };
            const response = await api.get("/torneos", { params });

            // Ajustar según la estructura de respuesta del backend (Laravel Paginator o Array)
            const res = response.data.data ?? response.data;

            if (res && typeof res === 'object' && !Array.isArray(res) && Array.isArray(res.data)) {
                // Es un paginador
                torneos.value = res.data.map(normalizeTorneo);
                pagination.value.total = res.total ?? 0;
            } else {
                // Es un array directo
                const data = Array.isArray(res) ? res : [];
                torneos.value = data.map(normalizeTorneo);
                pagination.value.total = response.data?.meta?.total ?? data.length;
            }
        } catch (err) {
            console.error("Error fetching torneos:", err);
            // El backend responde 404 cuando no hay torneos: es un estado vacío válido, no un fallo.
            if (err.response?.status === 404) {
                torneos.value = [];
                pagination.value.total = 0;
            } else {
                error.value = handleApiError(err);
            }
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
            torneoActivo.value = normalizeTorneo(data);
            return torneoActivo.value;
        } catch (err) {
            console.error(`Error fetching torneo ${id}:`, err);
            error.value = handleApiError(err);
            throw err;
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
            return bracket.value;
        } catch (err) {
            console.error(`Error fetching bracket for torneo ${id}:`, err);
            error.value = handleApiError(err);
            throw err;
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
            const nuevoTorneo = normalizeTorneo(response.data.data ?? response.data);
            // El backend no devuelve el torneo completo tras crearlo, así que no lo
            // agregamos a la lista: la vista debe recargar para tener todos los campos.
            return nuevoTorneo;
        } catch (err) {
            console.error("Error creating torneo:", err);
            error.value = handleApiError(err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Actualiza un torneo existente
     */
    const actualizarTorneo = async (id, payload) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.put(`/torneos/${id}`, payload);
            const torneoActualizado = response.data.data ?? response.data;

            // Mutar en la lista
            const index = torneos.value.findIndex(t => t.id_torneo === id || t.id === id);
            if (index !== -1) {
                torneos.value[index] = { ...torneos.value[index], ...torneoActualizado };
            }

            // Mutar en el activo si es el mismo
            if (torneoActivo.value && (torneoActivo.value.id_torneo === id || torneoActivo.value.id === id)) {
                torneoActivo.value = { ...torneoActivo.value, ...torneoActualizado };
            }

            return torneoActualizado;
        } catch (err) {
            console.error(`Error updating torneo ${id}:`, err);
            error.value = err.response?.data?.message || "Error al actualizar el torneo.";
            throw err;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Cambia / transiciona el estado de un torneo con mutación optimista y rollback
     */
    const cambiarEstadoTorneo = async (id, nuevoEstatus, motivo = null) => {
        error.value = null;

        // Buscar el torneo para guardar estado previo en caso de rollback
        const index = torneos.value.findIndex(t => isSameTorneo(t, id));
        let previousStatus = null;
        let previousMotivo = null;
        let isActivo = false;

        if (isSameTorneo(torneoActivo.value, id)) {
            previousStatus = getEstado(torneoActivo.value);
            previousMotivo = torneoActivo.value.motivo_cancelacion ?? null;
            // Mutación optimista en el activo
            torneoActivo.value.estado = nuevoEstatus;
            torneoActivo.value.estatus_torneo = nuevoEstatus;
            if (nuevoEstatus === 'CANCELADO' && motivo) {
                torneoActivo.value.motivo_cancelacion = motivo;
            }
            isActivo = true;
        }

        if (index !== -1) {
            if (previousStatus === null) {
                previousStatus = getEstado(torneos.value[index]);
            }
            if (previousMotivo === null) {
                previousMotivo = torneos.value[index].motivo_cancelacion ?? null;
            }
            // Mutación optimista en la lista
            torneos.value[index].estado = nuevoEstatus;
            torneos.value[index].estatus_torneo = nuevoEstatus;
            if (nuevoEstatus === 'CANCELADO' && motivo) {
                torneos.value[index].motivo_cancelacion = motivo;
            }
        }

        try {
            const response = await api.patch(`/torneos/${id}/status`, {
                nuevo_estatus: nuevoEstatus,
                motivo_cancelacion: motivo
            });

            // Actualizar con la respuesta real del servidor si es necesario
            const data = response.data.data ?? response.data;
            if (isActivo) {
                torneoActivo.value = { ...torneoActivo.value, ...data };
            }
            if (index !== -1) {
                torneos.value[index] = { ...torneos.value[index], ...data };
            }
            return data;
        } catch (err) {
            console.error(`Error transitioning status for torneo ${id}:`, err);
            error.value = handleApiError(err);

            // Rollback en caso de error
            if (isActivo) {
                torneoActivo.value.estado = previousStatus;
                torneoActivo.value.estatus_torneo = previousStatus;
                if (previousMotivo === null) delete torneoActivo.value.motivo_cancelacion;
                else torneoActivo.value.motivo_cancelacion = previousMotivo;
            }
            if (index !== -1) {
                torneos.value[index].estado = previousStatus;
                torneos.value[index].estatus_torneo = previousStatus;
                if (previousMotivo === null) delete torneos.value[index].motivo_cancelacion;
                else torneos.value[index].motivo_cancelacion = previousMotivo;
            }

            throw err;
        }
    };

    // Alias para mantener compatibilidad con código existente
    const transicionarEstatus = cambiarEstadoTorneo;

    return {
        torneos,
        torneoActivo,
        bracket,
        loading,
        error,
        pagination,
        filtros,
        categoriasTorneo,
        getEstado,
        fetchTorneos,
        fetchTorneoById,
        fetchBracket,
        fetchCategoriasTorneo,
        crearTorneo,
        actualizarTorneo,
        cambiarEstadoTorneo,
        transicionarEstatus,
    };
});
