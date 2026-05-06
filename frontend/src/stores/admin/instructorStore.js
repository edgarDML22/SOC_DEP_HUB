import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/services/api";

export const useInstructorStore = defineStore("instructorAdmin", () => {
    const instructors = ref([]);
    const disciplinesCatalog = ref([]);
    const isLoading = ref(false);
    const error = ref(null);

    // GETTERS
    const getInstructorById = (id) => {
        return instructors.value.find((i) => String(i.id_instructor) === String(id));
    };

    // ACTIONS
    const fetchInstructors = async (force = false) => {
        if (!force && instructors.value.length > 0) return;

        isLoading.value = true;
        error.value = null;
        try {
            const response = await api.get("/instructors/all");
            if (response.data && response.data.success) {
                instructors.value = response.data.data;
            }
        } catch (err) {
            console.error("Error fetching instructors:", err);
            error.value = "Error al cargar instructores.";
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * fetchInstructorDetails
     * Obtiene los detalles de un instructor. Si ya existe en el estado local 
     * con sus disciplinas cargadas (indicador de "detalle"), lo retorna del cache.
     */
    const fetchInstructorDetails = async (id, force = false) => {
        const existing = getInstructorById(id);

        // Si ya lo tenemos y tiene disciplinas (indicador de que es el objeto completo), no pedimos de nuevo
        if (!force && existing && Array.isArray(existing.disciplinas)) {
            return existing;
        }

        isLoading.value = true;
        try {
            const response = await api.get(`/instructors/${id}`);
            if (response.data && response.data.success) {
                const fullData = response.data.data;

                // Actualizar o insertar en la lista local
                const index = instructors.value.findIndex((i) => String(i.id_instructor) === String(id));
                if (index !== -1) {
                    instructors.value[index] = { ...instructors.value[index], ...fullData };
                } else {
                    instructors.value.push(fullData);
                }
                return fullData;
            }
        } catch (err) {
            console.error("Error fetching instructor details:", err);
            error.value = "Error al cargar los detalles del instructor.";
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const updateInstructor = async (id, data) => {
        isLoading.value = true;
        try {
            const response = await api.put(`/instructors/update/${id}`, data);
            if (response.data && response.data.success) {
                const updatedData = response.data.data;

                // Actualizar localmente
                const index = instructors.value.findIndex(
                    (i) => String(i.id_instructor) === String(id)
                );
                if (index !== -1) {
                    instructors.value[index] = {
                        ...instructors.value[index],
                        ...updatedData,
                    };
                }
                return { success: true, data: updatedData };
            }
        } catch (err) {
            console.error("Error updating instructor:", err);
            return { success: false, error: err.response?.data?.message || "Error al actualizar." };
        } finally {
            isLoading.value = false;
        }
    };

    const deleteInstructor = async (id) => {
        isLoading.value = true;
        try {
            const response = await api.delete(`/instructors/delete/${id}`);
            if (response.data && response.data.success) {
                // Eliminar localmente
                instructors.value = instructors.value.filter(
                    (i) => String(i.id_instructor) !== String(id)
                );
                return { success: true };
            }
        } catch (err) {
            console.error("Error deleting instructor:", err);
            return { success: false, error: err.response?.data?.message || "Error al eliminar." };
        } finally {
            isLoading.value = false;
        }
    };

    const fetchStatusImpact = async (id) => {
        try {
            const res = await api.get(`/instructors/${id}/status-impact`);
            return res.data;
        } catch (err) {
            console.error("Error fetching status impact:", err);
            throw err;
        }
    };

    const fetchCandidateSubstitutes = async (activityId) => {
        try {
            const res = await api.get(`/activities/${activityId}/substitutes`);
            return res.data;
        } catch (err) {
            console.error("Error fetching substitutes:", err);
            throw err;
        }
    };

    const applyMeticulousStatus = async (id, data) => {
        try {
            const res = await api.post(`/instructors/${id}/apply-status`, data);
            if (res.data.success) {
                // Forzar refresco de la lista local
                await fetchInstructors(true);
            }
            return res.data;
        } catch (err) {
            console.error("Error applying meticulous status:", err);
            return { success: false, message: err.response?.data?.message || "Error al aplicar cambios" };
        }
    };

    const fetchDisciplinesCatalogAction = async (force = false) => {
        // Cache-first approach
        if (!force && disciplinesCatalog.value && disciplinesCatalog.value.length > 0) return;

        isLoading.value = true;
        error.value = null;
        try {
            const response = await api.get("/disciplinas/all");
            if (response.data && response.data.success) {
                disciplinesCatalog.value = response.data.data;
            }
        } catch (err) {
            console.error("Error fetching disciplines catalog:", err);
            error.value = "Error al cargar el catálogo de disciplinas.";
            throw err; // Propagate for view handling
        } finally {
            isLoading.value = false;
        }
    };

    return {
        instructors,
        isLoading,
        error,
        fetchInstructors,
        fetchInstructorDetails,
        updateInstructor,
        deleteInstructor,
        getInstructorById,
        fetchStatusImpact,
        fetchCandidateSubstitutes,
        applyMeticulousStatus,
        disciplinesCatalog,
        fetchDisciplinesCatalogAction
    };
});
