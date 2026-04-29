import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/services/api";

export const useDisciplinesStore = defineStore("disciplinesAdmin", () => {
    const disciplines = ref([]);
    const isLoading = ref(false);
    const error = ref(null);

    const fetchDisciplines = async (force = false) => {
        if (!force && disciplines.value.length > 0) return;
        isLoading.value = true;
        try {
            const res = await api.get("/disciplinas/all");
            if (res.data.success) {
                disciplines.value = res.data.data;
            }
        } catch (err) {
            console.error("Error fetching disciplines:", err);
            error.value = "Error al cargar disciplinas.";
        } finally {
            isLoading.value = false;
        }
    };

    const fetchDisciplineDetails = async (id) => {
        isLoading.value = true;
        try {
            const res = await api.get(`/disciplinas/${id}`);
            return res.data.data;
        } catch (err) {
            console.error("Error fetching discipline details:", err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const createDiscipline = async (data) => {
        isLoading.value = true;
        try {
            const res = await api.post("/disciplinas/create", data);
            if (res.data.success) {
                await fetchDisciplines(true);
                return { success: true, data: res.data.data };
            }
        } catch (err) {
            return { success: false, error: err.response?.data?.message || "Error al crear." };
        } finally {
            isLoading.value = false;
        }
    };

    const updateDiscipline = async (id, data) => {
        isLoading.value = true;
        try {
            const res = await api.put(`/disciplinas/update/${id}`, data);
            if (res.data.success) {
                await fetchDisciplines(true);
                return { success: true, data: res.data.data };
            }
        } catch (err) {
            return { success: false, error: err.response?.data?.message || "Error al actualizar." };
        } finally {
            isLoading.value = false;
        }
    };

    const deleteDiscipline = async (id) => {
        isLoading.value = true;
        try {
            const res = await api.delete(`/disciplinas/delete/${id}`);
            if (res.data.success) {
                await fetchDisciplines(true);
                return { success: true };
            }
        } catch (err) {
            return { success: false, error: err.response?.data?.message || "Error al eliminar." };
        } finally {
            isLoading.value = false;
        }
    };

    return {
        disciplines,
        isLoading,
        error,
        fetchDisciplines,
        fetchDisciplineDetails,
        createDiscipline,
        updateDiscipline,
        deleteDiscipline
    };
});
