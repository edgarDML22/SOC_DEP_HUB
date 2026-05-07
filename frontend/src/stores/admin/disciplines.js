import { defineStore } from "pinia";
import { ref } from "vue";
import api from "@/services/api";

export const useDisciplinesStore = defineStore("disciplinesAdmin", () => {
    const disciplines = ref([]);
    const currentDiscipline = ref(null);
    const isLoading = ref(false);
    const error = ref(null);

    const setCurrentDiscipline = (discipline) => {
        currentDiscipline.value = discipline;
    };

    // GETTERS
    const getDisciplineById = (id) => {
        return disciplines.value.find((d) => String(d.id_disciplina) === String(id));
    };

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

    const fetchDisciplineDetails = async (id, force = false, silent = false) => {
        if (!id || id === 'undefined') return null;
        const existing = getDisciplineById(id);
        
        // Si ya lo tenemos y tiene instructores (indicador de objeto completo), retornamos cache
        if (!force && existing && Array.isArray(existing.instructores)) {
            if (currentDiscipline.value && String(currentDiscipline.value.id_disciplina) === String(id)) {
                currentDiscipline.value = existing;
            }
            return existing;
        }

        const isSilentMode = silent || (currentDiscipline.value && String(currentDiscipline.value.id_disciplina) === String(id));
        if (!isSilentMode) isLoading.value = true;

        try {
            const res = await api.get(`/disciplinas/${id}`);
            if (res.data.success) {
                const fullData = res.data.data;
                
                // Actualizar o insertar en la lista local
                const index = disciplines.value.findIndex((d) => String(d.id_disciplina) === String(id));
                if (index !== -1) {
                    disciplines.value[index] = { ...disciplines.value[index], ...fullData };
                } else {
                    disciplines.value.push(fullData);
                }

                if (currentDiscipline.value && String(currentDiscipline.value.id_disciplina) === String(id)) {
                    currentDiscipline.value = { ...currentDiscipline.value, ...fullData };
                }

                return fullData;
            }
        } catch (err) {
            console.error("Error fetching discipline details:", err);
            error.value = "Error al cargar detalles de la disciplina.";
            throw err;
        } finally {
            if (!isSilentMode) isLoading.value = false;
        }
    };

    const createDiscipline = async (data) => {
        isLoading.value = true;
        try {
            const res = await api.post("/disciplinas/create", data);
            if (res.data.success) {
                // Agregar localmente
                disciplines.value.push(res.data.data);
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
                const updatedData = res.data.data;
                // Actualizar localmente
                const index = disciplines.value.findIndex((d) => String(d.id_disciplina) === String(id));
                if (index !== -1) {
                    disciplines.value[index] = { ...disciplines.value[index], ...updatedData };
                }
                return { success: true, data: updatedData };
            }
        } catch (err) {
            return { success: false, error: err.response?.data?.message || "Error al actualizar." };
        } finally {
            isLoading.value = false;
        }
    };

    const changeDisciplineStatus = async (id, status) => {
        isLoading.value = true;
        try {
            const res = await api.patch(`/disciplinas/${id}/estatus`, { nuevo_estatus: status });
            if (res.data) {
                // Actualizar localmente
                const index = disciplines.value.findIndex((d) => String(d.id_disciplina) === String(id));
                if (index !== -1) {
                    disciplines.value[index].estatus = status;
                }
                return { success: true, data: res.data.data };
            }
        } catch (err) {
            return { 
                success: false, 
                error: err.response?.data?.message || "Error al cambiar estatus.",
                conflictos: err.response?.data?.conflictos || null
            };
        } finally {
            isLoading.value = false;
        }
    };

    const deleteDiscipline = async (id) => {
        isLoading.value = true;
        try {
            const res = await api.delete(`/disciplinas/delete/${id}`);
            if (res.data.success) {
                // Eliminar localmente (o marcar como deshabilitado según lógica de negocio)
                const index = disciplines.value.findIndex((d) => String(d.id_disciplina) === String(id));
                if (index !== -1) {
                    disciplines.value[index].estatus = 'DESHABILITADO';
                }
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
        currentDiscipline,
        isLoading,
        error,
        fetchDisciplines,
        fetchDisciplineDetails,
        setCurrentDiscipline,
        createDiscipline,
        updateDiscipline,
        changeDisciplineStatus,
        deleteDiscipline,
        getDisciplineById
    };
});
