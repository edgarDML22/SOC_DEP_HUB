import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/services/api";

export const useSocioStore = defineStore("socioAdmin", () => {
    const socios = ref([]);
    const currentSocio = ref(null);
    const isLoading = ref(false);
    const error = ref(null);
    const lastFetch = ref(null);

    const setCurrentSocio = (socio) => {
        currentSocio.value = socio;
    };

    // GETTERS
    const getSocioById = (id) => {
        return socios.value.find((s) => String(s.id_socio) === String(id));
    };

    // ACTIONS
    const fetchSocios = async (force = false) => {
        if (!force && socios.value.length > 0) return;

        isLoading.value = true;
        error.value = null;
        try {
            const response = await api.get("/socios/all"); // Nota: el controller usa index() para /socios
            if (response.data && response.data.success) {
                socios.value = response.data.data;
                lastFetch.value = Date.now();
            }
        } catch (err) {
            console.error("Error fetching socios:", err);
            error.value = "Error al cargar la lista de socios.";
        } finally {
            isLoading.value = false;
        }
    };

    const fetchSocioDetails = async (id, force = false, silent = false) => {
        // Intentar encontrar en la lista
        const socioExistente = getSocioById(id);

        // Si ya tiene miembros_familiares (indicador de que ya se cargaron los detalles), no pedimos de nuevo
        if (!force && socioExistente && socioExistente.miembros_familiares) {
            if (currentSocio.value && String(currentSocio.value.id_socio) === String(id)) {
                currentSocio.value = socioExistente;
            }
            return socioExistente;
        }

        const isSilentMode = silent || (currentSocio.value && String(currentSocio.value.id_socio) === String(id));
        if (!isSilentMode) isLoading.value = true;

        try {
            const response = await api.get(`/socios/${id}`);
            if (response.data && response.data.success) {
                const fullData = response.data.data;
                // Actualizar o insertar en la lista
                const index = socios.value.findIndex((s) => String(s.id_socio) === String(id));
                if (index !== -1) {
                    socios.value[index] = { ...socios.value[index], ...fullData };
                } else {
                    socios.value.push(fullData);
                }
                
                if (currentSocio.value && String(currentSocio.value.id_socio) === String(id)) {
                    currentSocio.value = { ...currentSocio.value, ...fullData };
                }
                return fullData;
            }
        } catch (err) {
            console.error("Error fetching socio details:", err);
            throw err;
        } finally {
            if (!isSilentMode) isLoading.value = false;
        }
    };

    const updateSocio = async (id, data) => {
        isLoading.value = true;
        try {
            const response = await api.put(`/socios/update/${id}`, data);
            if (response.data && response.data.success) {
                // Actualizar localmente
                const index = socios.value.findIndex((s) => String(s.id_socio) === String(id));
                if (index !== -1) {
                    socios.value[index] = { ...socios.value[index], ...response.data.data };
                }
                return { success: true, data: response.data.data };
            }
        } catch (err) {
            console.error("Error updating socio:", err);
            return { success: false, error: err.response?.data?.message || "Error al actualizar." };
        } finally {
            isLoading.value = false;
        }
    };

    const penalizeSocio = async (id, data) => {
        const status = (data.estatus_cuenta && data.estatus_cuenta.startsWith('PENALIZADO'))
            ? data.estatus_cuenta
            : 'PENALIZADO';
        return await updateSocio(id, { ...data, estatus_cuenta: status });
    };

    const updatePenalizacion = async (id, payload) => {
        // payload puede ser { estatus_penalizacion, dias_penalizacion_reserva?, dias_penalizacion_ludoteca? }
        const body = typeof payload === 'string'
            ? { estatus_penalizacion: payload }
            : { ...payload };

        // Limpiar campos undefined antes de enviar
        Object.keys(body).forEach((k) => body[k] === undefined && delete body[k]);

        try {
            const response = await api.put(`/socios/update/${id}`, body);
            if (response.data && response.data.success) {
                const index = socios.value.findIndex((s) => String(s.id_socio) === String(id));
                if (index !== -1) {
                    socios.value[index] = { ...socios.value[index], ...response.data.data };
                }
                return { success: true, data: response.data.data };
            }
            return { success: false, error: 'Respuesta inesperada del servidor.' };
        } catch (err) {
            console.error('Error updating penalizacion:', err);
            return { success: false, error: err.response?.data?.message || 'Error al actualizar.' };
        }
    };

    const updateEstatusCuenta = async (id, nuevoEstatus) => {
        try {
            const response = await api.patch(`/socios/${id}/estatus-cuenta`, {
                nuevo_estatus: nuevoEstatus,
            });
            if (response.data && response.data.success) {
                // Actualizar localmente
                const index = socios.value.findIndex((s) => String(s.id_socio) === String(id));
                if (index !== -1) {
                    socios.value[index] = {
                        ...socios.value[index],
                        estatus_cuenta: response.data.nuevo_estatus,
                    };
                }
                return { success: true, data: response.data };
            }
            return { success: false, error: 'Respuesta inesperada del servidor.' };
        } catch (err) {
            console.error('Error updating estatus cuenta:', err);
            return { success: false, error: err.response?.data?.message || 'Error al actualizar el estatus.' };
        }
    };

    return {
        socios,
        currentSocio,
        isLoading,
        error,
        fetchSocios,
        getSocioById,
        setCurrentSocio,
        updateSocio,
        penalizeSocio,
        updatePenalizacion,
        updateEstatusCuenta,
        fetchSocioDetails
    };
});
