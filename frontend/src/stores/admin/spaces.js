import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/services/api";

export const useSpacesStore = defineStore("spacesAdmin", () => {
    const spaces = ref([]);
    const currentSpace = ref(null);
    const isLoading = ref(false);
    const error = ref(null);

    const fetchSpaces = async (force = false) => {
        if (!force && spaces.value.length > 0) return;
        isLoading.value = true;
        try {
            const res = await api.get("/spaces/all");
            if (res.data.success) {
                spaces.value = res.data.data;
            }
        } catch (err) {
            console.error("Error fetching spaces:", err);
            error.value = "Error al cargar espacios.";
        } finally {
            isLoading.value = false;
        }
    };

    const fetchSpaceDetails = async (id) => {
        if (currentSpace.value?.id_espacio == id) return currentSpace.value;

        const cached = spaces.value.find(s => s.id_espacio == id);
        if (cached) currentSpace.value = { ...cached };

        isLoading.value = true;
        try {
            const res = await api.get(`/spaces/${id}`);
            currentSpace.value = res.data.data;
            const index = spaces.value.findIndex(s => s.id_espacio == id);
            if (index !== -1) spaces.value[index] = res.data.data;
            return res.data.data;
        } catch (err) {
            console.error("Error fetching space details:", err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const createSpace = async (data) => {
        isLoading.value = true;
        try {
            const res = await api.post("/spaces/create", data);
            if (res.data.success) {
                await fetchSpaces(true);
                return { success: true, data: res.data.data };
            }
        } catch (err) {
            return { success: false, error: err.response?.data?.message || "Error al crear." };
        } finally {
            isLoading.value = false;
        }
    };

    const updateSpace = async (id, data) => {
        isLoading.value = true;
        try {
            const res = await api.put(`/spaces/update/${id}`, data);
            if (res.data.success) {
                await fetchSpaces(true);
                return { success: true, data: res.data.data };
            }
        } catch (err) {
            return { success: false, error: err.response?.data?.message || "Error al actualizar." };
        } finally {
            isLoading.value = false;
        }
    };

    const deleteSpace = async (id) => {
        isLoading.value = true;
        try {
            const res = await api.delete(`/spaces/delete/${id}`);
            if (res.data.success) {
                await fetchSpaces(true);
                return { success: true };
            }
        } catch (err) {
            return { success: false, error: err.response?.data?.message || "Error al eliminar." };
        } finally {
            isLoading.value = false;
        }
    };

    const updateSpaceStatus = async (id, estatus) => {
        isLoading.value = true;
        try {
            const res = await api.patch(`/espacios/${id}/estatus`, { nuevo_estatus: estatus });
            if (res.data.success || res.status === 200) {
                const index = spaces.value.findIndex(s => s.id_espacio == id);
                if (index !== -1) spaces.value[index].estatus = estatus;
                if (currentSpace.value?.id_espacio == id) currentSpace.value.estatus = estatus;
                return { success: true };
            }
        } catch (err) {
            if (err.response?.status === 422) {
                const raw = err.response.data.conflictos;
                // El backend devuelve un objeto con conteos: { reservaciones_activas: N, ... }
                // Lo convertimos a mensajes legibles filtrando los que sean > 0
                const labels = {
                    reservaciones_activas: (n) => `${n} reservación${n !== 1 ? 'es' : ''} activa${n !== 1 ? 's' : ''}`,
                    sesiones_activas:      (n) => `${n} sesión${n !== 1 ? 'es' : ''} activa${n !== 1 ? 's' : ''}`,
                    actividades_programadas:(n) => `${n} actividad${n !== 1 ? 'es' : ''} programada${n !== 1 ? 's' : ''}`,
                    torneos_programados:   (n) => `${n} torneo${n !== 1 ? 's' : ''} programado${n !== 1 ? 's' : ''}`,
                };
                let conflictos;
                if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
                    conflictos = Object.entries(raw)
                        .filter(([, v]) => v > 0)
                        .map(([k, v]) => labels[k] ? labels[k](v) : `${k}: ${v}`);
                } else {
                    conflictos = raw; // ya es array de strings
                }
                return { success: false, conflictos };
            }
            return { success: false, error: err.response?.data?.message || "Error al actualizar estatus." };
        } finally {
            isLoading.value = false;
        }
    };

    return {
        spaces,
        currentSpace,
        isLoading,
        error,
        fetchSpaces,
        fetchSpaceDetails,
        createSpace,
        updateSpace,
        deleteSpace,
        updateSpaceStatus
    };
});


