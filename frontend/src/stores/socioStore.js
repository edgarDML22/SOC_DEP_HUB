import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/services/api";

export const useSocioStore = defineStore("socioAdmin", () => {
    const socios = ref([]);
    const isLoading = ref(false);
    const error = ref(null);
    const lastFetch = ref(null);

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

    const fetchSocioDetails = async (id) => {
        // Intentar encontrar en la lista
        const socioExistente = getSocioById(id);

        // Si ya tiene miembros_familiares (indicador de que ya se cargaron los detalles), no pedimos de nuevo
        if (socioExistente && socioExistente.miembros_familiares) {
            return socioExistente;
        }

        isLoading.value = true;
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
                return fullData;
            }
        } catch (err) {
            console.error("Error fetching socio details:", err);
            throw err;
        } finally {
            isLoading.value = false;
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
        // Reutiliza updateSocio o usa uno específico si el backend lo requiere
        // Según los requerimientos anteriores, penalizar es cambiar estatus a 'PENALIZADO'
        // Pero el usuario pidió separar el update de los atributos y la parte de penalizar.
        return await updateSocio(id, { ...data, estatus_cuenta: 'PENALIZADO' });
    };

    return {
        socios,
        isLoading,
        error,
        fetchSocios,
        getSocioById,
        updateSocio,
        penalizeSocio,
        fetchSocioDetails
    };
});
