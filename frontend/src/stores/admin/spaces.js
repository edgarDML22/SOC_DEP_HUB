import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/services/api";

export const useSpacesStore = defineStore("spacesAdmin", () => {
    const spaces = ref([]);
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
        isLoading.value = true;
        try {
            const res = await api.get(`/spaces/${id}`);
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

    return {
        spaces,
        isLoading,
        error,
        fetchSpaces,
        fetchSpaceDetails,
        createSpace,
        updateSpace,
        deleteSpace
    };
});
